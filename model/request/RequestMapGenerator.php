<?php

/**
 * This class analyses annotations in the controller folder of any
 * loaded package.
 *
 * @author Linus Norton <linusnorton@gmail.com>
 */
class RequestMapGenerator {

    /**
     * This method recusivily looks through the given package for controllers using
     * annotations and generates a request map file which is returned in the form
     * of a string
     * @param string $dir
     * @return string
     */
    private function buildDirectory($dir) {
        if (!is_dir($dir) || false === ($dh = opendir($dir)) || file_exists($dir.'/.ignore')) {
            return;
        }

        //for each file in the directory
        while (($file = readdir($dh)) !== false) {
            $string = '';
            //if it is something we want to ignore...
            if (strpos($file, '.') === 0 || $file == "test" || $file == "vendor") {
                continue;
            }
            //if it is a directory...
            else if (is_dir($dir."/".$file)) {
                $string .= $this->buildDirectory($dir."/".$file);
            }
            //if it is a .php file where the first letter is upper case...
            else if (substr ($file, -4) == ".php" && ucfirst($file) == $file) {
                $string .= $this->analyseClass(realpath($dir."/".$file));
            }
        }

        return $string;
    }

    /**
     * This method uses reflection to see if the given class uses annotations
     * to define a request handler. It returns a string that contains the
     * serialized Resource.
     * 
     * @param string $file
     * @return string
     */
    private function analyseClass($file) {
        //double check we have included addendum
        require_once dirname(__FILE__)."/../util/addendum/annotations.php";

        $class = pathinfo($file , PATHINFO_FILENAME);
        try {
            $reflection = new ReflectionClass($class);
        }
        catch (Exception $ex) {
            return;
        }
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $string = "";

        foreach ($methods as $method) {
            $annotation = new ReflectionAnnotatedMethod($method->class, $method->name);

            //if it is a request handler
            if ($annotation->hasAnnotation("RequestName")) {
                $requestName = $annotation->getAnnotation("RequestName")->value;
                $requestType = '';
            }
            else if ($annotation->hasAnnotation("GET")) {
                $requestName = $annotation->getAnnotation("GET")->value;
                $requestType = Request::GET;
            }
            else if ($annotation->hasAnnotation("POST")) {
                $requestName = $annotation->getAnnotation("POST")->value;
                $requestType = Request::POST;
            }
            else if ($annotation->hasAnnotation("DELETE")) {
                $requestName = $annotation->getAnnotation("DELETE")->value;
                $requestType = Request::DELETE;
            }
            else if ($annotation->hasAnnotation("PUT")) {
                $requestName = $annotation->getAnnotation("PUT")->value;
                $requestType = Request::PUT;
            }
            else {
                continue;
            }

            $mappedParams = $annotation->getAnnotation("RequestParams")->value == null ? array() : $annotation->getAnnotation("RequestParams")->value;
            $cacheLength = $annotation->getAnnotation("CacheLength")->value == null ? false : $annotation->getAnnotation("CacheLength")->value;
            $authenticator = $annotation->getAnnotation("RequestAuthenticator")->value == null ? null : $annotation->getAnnotation("RequestAuthenticator")->value;
            $viewType = $annotation->getAnnotation("ViewType")->value == null ? Registry::get("DEFAULT_VIEW") : $annotation->getAnnotation("ViewType")->value;
            $viewTemplate = $annotation->getAnnotation("ViewTemplate")->value == null ? null : $annotation->getAnnotation("ViewTemplate")->value;
            $customParams = array();
            
            foreach ($annotation->getAllAnnotations("CustomParam") as $custom) {
                $customParams[$custom->name] = $custom->value;
            }

            $resource = new Resource($requestName, 
                                     $requestType,
                                     $method->class,
                                     $method->name,
                                     $mappedParams,
                                     $authenticator,
                                     $cacheLength,
                                     $viewType,
                                     $viewTemplate,
                                     null,
                                     $customParams);

            $string .= "Dispatcher::addResource(unserialize('" . serialize($resource) . "'));\n";
        }

        return $string;
    }

    /**
     * Writes the request-map to the system temp folder.
     * @param string $package
     * @param string $contents
     * @return string
     */
    private static function writeRequestMap($package, $contents) {
        $contents = "<?php\n\n// controllers in: {$package}\n{$contents}";
        $filename = self::getRequestMapFilename($package);

        try {
            file_put_contents($filename, $contents);
        }
        catch (FrameEx $up) {
            $up->setMessage("Could not write to: ".$filename);
            throw $up;
        }

        try {
            chmod($filename, 0777);
        }
        catch (FrameEx $ex) { /* we tried */ }

        return $filename;
    }

    /**
     * Get all the loaded packages and rebuild their request maps
     * @param boolean $include if true, includes the file that is created
     */
    public static function buildAll($include = false) {
        $generator = new RequestMapGenerator();

        foreach (Factory::getLoadedPackages() as $package) {
            $contents = $generator->buildDirectory($package.'/controller/');
            $filename = self::writeRequestMap($package, $contents);

            if ($include) {
                include($filename);
            }
        }
    }

    /**
     * Build a single package
     * @param string $package
     */
    public static function build($package) {
        $generator = new RequestMapGenerator();
        $contents = $generator->buildDirectory($package.'/controller/');
        return self::writeRequestMap($package, $contents);
    }

    /**
     * @param $package
     * @return string
     */
    public static function getRequestMapFilename($package)
    {
        $filename = str_replace("/", "_",realpath($package));
        return sys_get_temp_dir().DIRECTORY_SEPARATOR.$filename.".request-map.php";
    }
}


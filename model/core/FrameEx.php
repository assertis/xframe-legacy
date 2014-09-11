<?php
/**
 * @author Linus Norton <linusnorton@gmail.com>
 * @package core
 *
 * This class encapsulates the default behaviour for framework exceptions.
 *
 */
class FrameEx extends Exception {
    protected $severity;

    const OFF = 0,
          CRITICAL = 1,
          HIGH = 2,
          MEDIUM = 3,
          LOW = 4,
          LOWEST = 5;

    /**
     * Creates the exception with a message and an error code that are
     * shown when the output method is called.
     *
     * @param String $message
     * @param int $code
     * @param int $severity
     * @param Exception $previous
     */
    public function __construct($message = null,
                                $code = 0,
                                $severity = self::HIGH,
                                Exception $previous = null) {
        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            parent::__construct($message, (int) $code, $previous);
        }
        else {
            parent::__construct($message, (int) $code);
        }
        $this->severity = $severity;
    }

    /**
     * Reset the severity level
     * @param int $severity
     */
    public function setSeverity($severity) {
        $this->severity = $severity;
    }

    /**
     * Use the current registry settings to determine whether this error needs
     * to be logged or emailed (or both)
     */
    public function process() {
        if (Registry::get("ERROR_LOG_LEVEL") >= $this->severity) {
            $this->log();
        }
        if (Registry::get("ERROR_EMAIL_LEVEL") >= $this->severity) {
            $this->email();
        }
    }

    /**
     * Log using the error_log and LoggerManager
     */
    protected function log() {
        error_log($_SERVER["REQUEST_URI"].": ".$this->message);
        LoggerManager::getLogger("Exception")->error($this->message);
    }

    /**
     * Email the error to the ADMIN
     */
    protected function email() {
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'From: "'.$_SERVER["SERVER_NAME"].'" <xframe@'.$_SERVER["SERVER_NAME"].'>' . "\r\n";
        $headers .= 'Content-type: text/plain; charset=iso-8859-1' . "\r\n";


        mail(Registry::get("ADMIN"),
             substr($this->message,0,79),
             $this->getContent(),
             $headers);
    }

    /**
     * Get the readable content for this exception
     */
    private function getContent() {
        $xslFile = APP_DIR."view/".Registry::get("PLAIN_TEXT_ERROR").".xsl";
        $transformation = new Transformation("<root><exceptions>".$this->getXML()."</exceptions></root>", $xslFile);
        return $transformation->execute();
    }

    /**
     * Get the XML for this exception
     */
    public function getXML() {
        $location = "".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        if ($location == ""){
            $location = "". $_SERVER["SCRIPT_FILENAME"];
        }
        $out = "<exception>";
        $out .= "<message>".htmlspecialchars($this->message, ENT_COMPAT, "UTF-8", false)."</message>";
        $out .= "<code>".htmlspecialchars($this->code, ENT_COMPAT, "UTF-8", false)."</code>";
        $out .= "<location>".htmlspecialchars($location, ENT_COMPAT, "UTF-8", false)."</location>";
        $out .= "<getVariables>";
        foreach ($_GET as $key => $value){
            $out .= "<variable key='".$key."' value='".$value."' />";
        }
        $out .= "</getVariables>";
        $out .= "<postVariables>";
        foreach ($_POST as $key => $value){
            if (strstr($key,"card")){
                continue;
            }
            else {
                $out .= "<variable key='".$key."' value='".$value."' />";
            }
        }
        $out .= "</postVariables>";
        $out .= "<ipaddress>".$this->getIP()."</ipaddress>";
        $out .= "<backtrace>";
        $i = 1;

        foreach ($this->getReversedTrace() as $back) {
            if ($back["class"] != "FrameEx") {
                $out .= "<step number='".$i++."' line='{$back['line']}' file='{$back['file']}' class='{$back['class']}' function='{$back['function']}' />";
            }
        }
        $out .= "</backtrace>";
        $out .= "</exception>";
        return $out;
    }

    public function getJSON() {
        $location = "".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        if ($location == ""){
            $location = "". $_SERVER["SCRIPT_FILENAME"];
        }

        return array(
            'exception' => array(
                'message' => htmlspecialchars($this->message, ENT_COMPAT, "UTF-8", false),
                'code' => htmlspecialchars($this->code, ENT_COMPAT, "UTF-8", false)
            )
        );
    }

    /**
     * Return the array reversed back trace
     * @return array
     */
    public function getReversedTrace() {
        $trace = array();

        foreach (array_reverse($this->getTrace()) as $back) {
            $back['file'] = (array_key_exists("file", $back)) ? basename($back['file']) : "";
            $back['class'] = (array_key_exists("class", $back)) ? $back['class'] : "";
            $back['line'] = (array_key_exists("line", $back)) ? $back['line'] : "";
            $trace[] = $back;
        }

        return $trace;
    }

    /**
     * @return string
     */
    public function __toString() {
        try {
            return $this->getContent();
        }
        catch (Exception $e) {
            return "Error generating exception content. Original message: ".$this->message;
        }
    }

    /**
     * Save the exception for the next page
     */
    public function persist() {
        if (!is_array($_SESSION["exceptions"])) {
            $_SESSION["exceptions"] = array();
        }

        $_SESSION["exceptions"][] = $this;
    }

    /**
     * Return an array of exceptions that were persisted
     * @return array
     */
    public static function getPersistedExceptions() {
        //deal with any exceptions that were redirected
        if (array_key_exists("exceptions",$_SESSION) && is_array($_SESSION["exceptions"])) {
            //store in temp var
            $execptions = $_SESSION["exceptions"];
            //clear from session
            $_SESSION["exceptions"] = array();
            //return
            return $execptions;
        }

        return array();
    }

    /**
     * Set the error code
     * @param int $code
     */
    public function setCode($code) {
        $this->code = $code;
    }

    /**
     * Set the message
     * @param string $message
     */
    public function setMessage($message) {
        $this->message = $message;
    }


    /**
     * Handles PHP generated errors
     */
    public static function errorHandler($type, $msg, $filename, $line ) {
        if (!(error_reporting() & $type)) {
            // This error code is not included in error_reporting
            return;
        }
        $errortype = array (
                        E_ERROR              => 'Error',
                        E_WARNING            => 'Warning',
                        E_PARSE              => 'Parsing Error',
                        E_NOTICE             => 'Notice',
                        E_CORE_ERROR         => 'Core Error',
                        E_CORE_WARNING       => 'Core Warning',
                        E_COMPILE_ERROR      => 'Compile Error',
                        E_COMPILE_WARNING    => 'Compile Warning',
                        E_USER_ERROR         => 'User Error',
                        E_USER_WARNING       => 'User Warning',
                        E_USER_NOTICE        => 'User Notice',
                        E_STRICT             => 'Runtime Notice',
                        E_RECOVERABLE_ERROR  => 'Recoverable Error'
                    );

        $error = (array_key_exists($type, $errortype)) ? $errortype[$type] : $type;
        throw new FrameEx($error.": ".$msg." (line {$line} of ".basename($filename).")");
    }

    // TODO: temp fatal error catching
    public static function fatalHandler() {
        $error = error_get_last();
        if ($error['type'] === E_ERROR || $error['type'] === E_PARSE) {
            $errno   = $error['type'];
            $errfile = $error['file'];
            $errline = $error['line'];
            $errstr  = $error['message'];

            mail(Registry::get('ADMIN'),
                substr($errstr, 0, 30),
                self::formatError($errno, $errstr, $errfile, $errline)
            );
        }
    }

    // TODO: temp fatal error catching
    public static function formatError($errno, $errstr, $errfile, $errline) {
        $trace = print_r(debug_backtrace(false), true);

        $content  = "<table><thead bgcolor='#c8c8c8'><th>Item</th><th>Description</th></thead><tbody>";
        $content .= "<tr valign='top'><td><b>Error</b></td><td><pre>$errstr</pre></td></tr>";
        $content .= "<tr valign='top'><td><b>Errno</b></td><td><pre>$errno</pre></td></tr>";
        $content .= "<tr valign='top'><td><b>File</b></td><td>$errfile</td></tr>";
        $content .= "<tr valign='top'><td><b>Line</b></td><td>$errline</td></tr>";
        $content .= "<tr valign='top'><td><b>Trace</b></td><td><pre>$trace</pre></td></tr>";
        $content .= '</tbody></table>';

        return $content;
    }

    /**
     * If an exception is thrown that is not in a try catch statement it comes
     * here. It is then output to the screen and code execution stops
     *
     * @param Exception $exception
     */
    public static function exceptionHandler($exception) {
        //if it's not a FrameEx make it one
        if (!($exception instanceof FrameEx)) {
            $exception = new FrameEx($exception->getMessage(),
                                     $exception->getCode(),
                                     FrameEx::HIGH,
                                     $exception);
        }

        try {
            $exception->process();
        }
        catch (Exception $e) {
            trigger_error("Error logging exception: ".$e->getMessage());
        }

        //finally echo it out
        trigger_error($exception->__toString());
    }

    /**
     * Setup the error handling
     */
    public static function init() {
        set_exception_handler(array("FrameEx", "exceptionHandler"));
        set_error_handler(array("FrameEx", "errorHandler"), ini_get("error_reporting"));
        register_shutdown_function(array("FrameEx", "fatalHandler"));
    }

    public function getIP() {
       if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"),
"unknown"))
           $ip = getenv("HTTP_CLIENT_IP");
       else if (getenv("HTTP_X_FORWARDED_FOR") &&
strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
           $ip = getenv("HTTP_X_FORWARDED_FOR");
       else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
           $ip = getenv("REMOTE_ADDR");
       else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] &&
strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
           $ip = $_SERVER['REMOTE_ADDR'];
       else
           $ip = "unknown";
       return($ip);
    }

}

<?php

/**
 * TwigView view wrapper
 *
 * @author Linus Norton <linusnorton@gmail.com>
 */
class TwigView extends View {

    /**
     * @var Twig
     */
    private $twig;
    /**
     * @var array
     */
    private $model;

    /**
     * Constructor sets up the PHPTAL object
     */
    public function  __construct() {
        parent::__construct("", ".html");

        try {
            include_once 'Twig/Autoloader.php';
            Twig_Autoloader::register();
        }
        catch(FrameEx $ex) {
            $ex->setMessage("Twig not installed");
            throw $ex;
        }

        $this->model = array();
        $this->twig = new Twig_Environment(
            new Twig_Loader_Filesystem(APP_DIR."view".DIRECTORY_SEPARATOR),
            array(
                'cache' => sys_get_temp_dir(),
                'debug' => false,
                'auto_reload' => Registry::get("AUTO_REBUILD_TWIG")
            )
        );
    }

    /**
     * Add data to the PHPTAL view
     * @param mixed $data
     * @param mixed $key
     */
    public function add($data, $key = null) {
        if ($key != null) {
            $this->model[$key] = $data;
        }
    }

    /**
     * Use PHPTAL to generate some XHTML
     * @return string
     */
    public function execute() {
        $template = $this->twig->loadTemplate($this->template);
        return $template->render($this->model);
    }

    /**
     * Fall back to a default error view.
     * @return string
     */
    public function getErrorPage() {
        $this->template = ROOT.Registry::get("ERROR_VIEW");
        return $this->execute();
    }

    /**
     * Pass the magic set on to PHPTAL
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value) {
        $this->add($value, $key);
    }

    /**
     * Add a parameter to the view for the template
     * @param string $key
     * @param mixed $value
     */
    public function addParameter($key, $value) {
        $this->add($key, $value);
    }
}


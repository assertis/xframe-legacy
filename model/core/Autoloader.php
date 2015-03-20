<?php

namespace model\core;

/**
 * This Autoloader uses the class name or namespace of the given class to
 * locate it, this means you can use the PEAR naming convention or you can use
 * your nnamespace. For instance:
 *
 * xframe\core\Autoloader = xframe/core/Autoloader.php
 *
 * or
 *
 * xframe_core_Autoloader = xframe/core/Autoloader.php
 *
 * @author Linus Norton <linusnorton@gmail.com>
 * @package autoloader
 */
class Autoloader {
    
    /**
     * @var string classExtension filename extension of class files
     */
    private $classExtension;

    /**
     * Constructs the Autoloader and sets the initial state
     *
     * @param string $classExtension
     */
    public function __construct($classExtension = '.php') {
        $this->classExtension = $classExtension;
    }
    
    /**
     * Add an include path to the autoloader
     * 
     * @param string $path 
     */
    public function addPath($path) {
        set_include_path(get_include_path().PATH_SEPARATOR.$path.'/');
    }

    /**
     * Registers the name based autoloader with the SPL autoloader method and
     * adds the src, lib and test directories to the include path.
     */
    public function register() {        
        spl_autoload_register(array($this, 'loader'));
    }

    /**
     * Uses the class name to locate the file by converting _ or namespace \
     * characters in the name to the system directory separator
     *
     * @param string $class
     */
    public function loader($class) {
        $filename = str_replace(array('\\', '_'), DIRECTORY_SEPARATOR, $class);

        if (is_file($filename.$this->classExtension)) {
            include $filename.$this->classExtension;
        }

        return false;
    }
   
}
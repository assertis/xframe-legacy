<?php

/**
 * @author Linus Norton <linusnorton@gmail.com>
 * @package core
 *
 * This class loads the request map as a package is "booted" 
 */
 class Factory {
    private static $loadedPackages = array();

    /**
     * Include the request mapping and run the init script of the given package
     * @param string $package
     */
    public static function boot($package) {
        $tmpPath = sys_get_temp_dir().'/'.str_replace('/',"_",realpath($package));

        //boot
        try {
            include_once(realpath($package."/init.php"));
        }
        catch (FrameEx $ex) {
            $ex->setMessage("Unable to boot package: {$package}: ".$ex->getMessage());
            throw $ex;
        }

        //load the class mapping
        try {
            include($tmpPath.".request-map.php");
        }
        catch (FrameEx  $ex) {
            RequestMapGenerator::build($package);
            include($tmpPath.".request-map.php");
        }

        self::$loadedPackages[] = $package;
    }

    /**
     * Returns an array of all packages currently loaded
     * @return array
     */
    public static function getLoadedPackages() {
        return self::$loadedPackages;
    }

}


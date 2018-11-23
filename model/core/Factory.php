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

        //boot
        try {
            include_once(realpath($package."/init.php"));
        }
        catch (FrameEx $ex) {
            $ex->setMessage("Unable to boot package: {$package}: ".$ex->getMessage());
            throw $ex;
        }

        $filename = RequestMapGenerator::getRequestMapFilename($package);
        if(!file_exists($filename)) {
            $filename = RequestMapGenerator::build($package);
        }
        include($filename);

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


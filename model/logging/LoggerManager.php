<?php
/**
 * @author Linus Norton <linus.norton@assertis.co.uk>
 *
 * This class manages the pool of loggers
 */
class LoggerManager {
    private static $loggers = array();

    /**
     * Return the requested logger if one does not exist, create on
     * @return Logger
     */
    public static function getLogger($key) {
        if (!array_key_exists($key, self::$loggers) || !self::$loggers[$key] instanceof Logger) {
            self::$loggers[$key] = new Logger($key);
        }
        return self::$loggers[$key];
    }
}

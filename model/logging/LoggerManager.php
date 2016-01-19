<?php

/**
 * @author Linus Norton <linus.norton@assertis.co.uk>
 *
 * This class manages the pool of loggers
 */
class LoggerManager
{
    private static $loggers = array();
    private static $verboseLoggers = array();
    private static $verboseDatabaseLoggers = array();

    /**
     * Return the requested logger if one does not exist, create on
     *
     * @param string $key
     *
     * @return Logger
     */
    public static function getLogger($key)
    {
        if (!array_key_exists($key, self::$loggers) || !self::$loggers[$key] instanceof Logger) {
            self::$loggers[$key] = new Logger($key);
        }
        return self::$loggers[$key];
    }

    /**
     * Return the requested logger with debug level
     *
     * @param string $key
     *
     * @return Logger
     */
    public static function getVerboseLogger($key)
    {
        if (!array_key_exists($key, self::$verboseLoggers) || !self::$verboseLoggers[$key] instanceof Logger) {
            $logger = new Logger($key);
            $logger->setLogLevel(Logger::DEBUG);
            self::$verboseLoggers[$key] = $logger;
        }
        return self::$verboseLoggers[$key];
    }

    /**
     * Return the requested logger that saves also to db
     *
     * @param string $key
     *
     * @return Logger
     */
    public static function getVerboseDatabaseLogger($key)
    {
        if (!array_key_exists($key, self::$verboseDatabaseLoggers)
                || !self::$verboseDatabaseLoggers[$key] instanceof Logger) {
            $logger = new Logger($key);
            $logger->enableDbLog();
            $logger->setLogLevel(Logger::DEBUG);
            self::$verboseDatabaseLoggers[$key] = $logger;
        }
        return self::$verboseDatabaseLoggers[$key];
    }
}

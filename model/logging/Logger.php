<?php

/**
 * @author Linus Norton <linus.norton@assertis.co.uk>, Jason Paige <jason.paige@assertis.co.uk>
 *
 * This class logs to a database record
 *
 * SQL Required for logger is in install/logger.sql
 *
 */
class Logger {
    const DEBUG = 6, INFO = 5, WARN = 4, AUDIT = 3, ERROR = 2, FATAL = 1, OFF = 0;
    private $key;
    private $tableName;
    private $logLevel;
    private $logFile;

    /**
     * @var \Monolog\Logger
     */
    private $client;

    public function __construct($key) {
        $this->key = $key;
        $this->tableName = (Registry::get("LOG_TABLE")) ? Registry::get("LOG_TABLE") : "log";
        $this->logLevel = (Registry::get("LOG_LEVEL")) ? Registry::get("LOG_LEVEL") : self::OFF;

        try {
            $this->initMonolog($key, $this->logLevel);
        } catch (\Exception $e) {
            //Nothing
        }
    }

    private function initMonolog($key, $logLevel) {
        if (!class_exists('\Redis') || !class_exists('\Monolog\Logger')) {
            return null;
        }

        $redisHost = (Registry::get("REDIS_HOST")) ? Registry::get("REDIS_HOST") : '127.0.0.1';
        $redisPort = (Registry::get("REDIS_PORT")) ? Registry::get("REDIS_PORT") : 6379;
        $redisTimeout = (Registry::get("REDIS_TIMEOUT")) ? Registry::get("REDIS_TIMEOUT") : 0.0;

        $systemName = (Registry::get("RESOURCE_SITE")) ? Registry::get("RESOURCE_SITE") : "XFRAME";
        $applicationName = (Registry::get("DATABASE_NAME")) ? Registry::get("DATABASE_NAME") : null;
        $logStashKey = (Registry::get("LOG_TABLE")) ? Registry::get("LOG_TABLE") : "log";

        $codes = [
            self::DEBUG => \Monolog\Logger::DEBUG,
            self::INFO => \Monolog\Logger::INFO,
            self::WARN => \Monolog\Logger::WARNING,
            self::ERROR => \Monolog\Logger::ERROR,
            self::AUDIT => \Monolog\Logger::ERROR,
            self::FATAL => \Monolog\Logger::CRITICAL,
        ];

        $this->client = new \Monolog\Logger($key);
        $redis = new \Redis();
        $redis->connect($redisHost, $redisPort, $redisTimeout);
        $handler = new \Monolog\Handler\RedisHandler($redis, $logStashKey, self::getCode($logLevel, $codes));
        $handler->setFormatter(new \Monolog\Formatter\LogstashFormatter($applicationName, $systemName, null, "_", \Monolog\Formatter\LogstashFormatter::V1));
        $this->client->pushHandler($handler);
    }

    /**
     * Return codes
     *
     * @param $nameOrCode
     * @param array $codes
     * @return int
     */
    private static function getCode($nameOrCode, array $codes) {
        if (isset($codes[$nameOrCode])) {
            return $codes[$nameOrCode];
        }

        try {
            return \Monolog\Logger::INFO;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Override the level of logging set in the Registry
     */
    public function setLogLevel($level) {
        $this->logLevel = $level;
    }

    /**
     * @param string $tableName
     */
    public function setLogTable($tableName) {
        $this->tableName = $tableName;
    }

    /**
     * @param string $fileName
     */
    public function setLogFile($fileName) {
        $this->logFile = $fileName;
    }

    /**
     * Log a debug message (dependant on the level of logging)
     */
    public function debug($message) {
        if ($this->logLevel >= self::DEBUG) {
            $this->log("debug", $message);
        }
    }

    public function info($message) {
        if ($this->logLevel >= self::INFO) {
            $this->log("info", $message);
        }
    }

    public function warn($message) {
        if ($this->logLevel >= self::WARN) {
            $this->log("warn", $message);
        }
    }

    public function audit($message) {
        if ($this->logLevel >= self::AUDIT) {
            $this->log("audit", $message);
        }
    }

    public function error($message) {
        if ($this->logLevel >= self::ERROR) {
            $this->log("error", $message);
        }
    }

    public function fatal($message) {
        if ($this->logLevel >= self::FATAL) {
            $this->log("fatal", $message);
        }
    }

    private function logUsingMonlog($level, $message) {
        if (empty($this->client)) {
            return null;
        }

        $codes = [
            'debug' => \Monolog\Logger::DEBUG,
            'info' => \Monolog\Logger::INFO,
            'warning' => \Monolog\Logger::WARNING,
            'error' => \Monolog\Logger::ERROR,
            'audit' => \Monolog\Logger::ERROR,
            'fatal' => \Monolog\Logger::CRITICAL,
        ];

        $this->client->log(self::getCode($level, $codes), $message);
    }

    private function log($level, $message) {
        try {
            //Log to logstash
            $this->logUsingMonlog($level, $message);
        } catch (\Exception $e) {
            //Nothing
        }

        if ($this->logFile != null) {
            $this->logTofile($message);
        } else {
            $this->logToDatabase($level, $message);
        }
    }

    private function logToFile($message) {
        $fp = fopen($this->logFile, 'a');
        if ($fp) {
            fwrite($fp, $message . "\n");
            fclose($fp);
            @chmod($this->logFile, 0775);
        }
    }

    private function logToDatabase($level, $message) {

        $log = new Record($this->tableName);

        //check ip from share internet
        if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        } else if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } //to check ip is pass from proxy
        else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $log->ip = $ip;
        $log->key = $this->key;
        $log->level = $level;
        $log->message = $message;
        $log->date_time = date("Y-m-d H:i:s");
        $log->session_id = session_id();
        $log->execution_time = number_format(microtime(true) - Controller::getExecutionTime(), 5);

        // Log wihtout transaction.
        $saveGraph = array();
        $log->save(false, $saveGraph, false);
    }

}

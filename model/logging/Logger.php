<?php
use Monolog\Formatter\LogstashFormatter;
use Monolog\Handler\RedisHandler;
use Monolog\Logger as MonologLogger;

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
     * @var MonologLogger
     */
    private $client;

    private static $loggerToMonologCodes = [
        self::DEBUG => MonologLogger::DEBUG,
        self::INFO => MonologLogger::INFO,
        self::WARN => MonologLogger::WARNING,
        self::ERROR => MonologLogger::ERROR,
        self::AUDIT => MonologLogger::ERROR,
        self::FATAL => MonologLogger::CRITICAL,
    ];

    private static $codeNamesToMonologCode = [
        'debug' => MonologLogger::DEBUG,
        'info' => MonologLogger::INFO,
        'warning' => MonologLogger::WARNING,
        'error' => MonologLogger::ERROR,
        'audit' => MonologLogger::ERROR,
        'fatal' => MonologLogger::CRITICAL,
    ];

    public function __construct($key) {
        $this->key = $key;
        $this->tableName = (Registry::get("LOG_TABLE")) ? Registry::get("LOG_TABLE") : "log";

        $systemName = (Registry::get("RESOURCE_SITE")) ? Registry::get("RESOURCE_SITE") : "XFRAME";
        $applicationName = (Registry::get("DATABASE_NAME")) ? Registry::get("DATABASE_NAME") : null;
        $logStashKey = (Registry::get("LOG_TABLE")) ? Registry::get("LOG_TABLE") : "log";

        $logLevel = (Registry::get("LOG_LEVEL")) ? Registry::get("LOG_LEVEL") : self::OFF;
        $this->logLevel = $logLevel;

        $redisHost = (Registry::get("REDIS_HOST")) ? Registry::get("REDIS_HOST") : '127.0.0.1';
        $redisPort = (Registry::get("REDIS_PORT")) ? Registry::get("REDIS_PORT") : 6379;
        $redisTimeout = (Registry::get("REDIS_TIMEOUT")) ? Registry::get("REDIS_TIMEOUT") : 0.0;

        $this->client = new MonologLogger($key);
        $redis = new Redis();
        $redis->connect($redisHost, $redisPort, $redisTimeout);
        $handler = new RedisHandler($redis, $logStashKey, self::getCode($logLevel, self::$loggerToMonologCodes));
        $handler->setFormatter(new LogstashFormatter($applicationName, $systemName, null, "_", LogstashFormatter::V1));
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

        return MonologLogger::INFO;
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
        if ($this->logLevel >= self::DEBUG){
            $this->log("debug", $message);
        }
    }

    public function info($message) {
        if ($this->logLevel >= self::INFO){
            $this->log("info", $message);
        }
    }

    public function warn($message) {
        if ($this->logLevel >= self::WARN){
            $this->log("warn", $message);
        }
    }

    public function audit($message) {
        if ($this->logLevel >= self::AUDIT){
            $this->log("audit", $message);
        }
    }

    public function error($message) {
        if ($this->logLevel >= self::ERROR){
            $this->log("error", $message);
        }
    }

    public function fatal($message) {
        if ($this->logLevel >= self::FATAL){
            $this->log("fatal", $message);
        }
    }

    private function log($level, $message) {
        //Log to logstash
        $this->client->log(self::getCode($level, self::$codeNamesToMonologCode), $message);

        if ($this->logFile != null) {
            $this->logTofile($message);
        }
        else {
            $this->logToDatabase($level, $message);
        }
    }

    private function logToFile($message) {
        $fp = fopen($this->logFile, 'a');
        if ($fp) {
            fwrite($fp, $message."\n");
            fclose($fp);
            @chmod($this->logFile, 0775);
        }
    }

    private function logToDatabase($level, $message) {

        $log = new Record($this->tableName);

        //check ip from share internet
        if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        }
        else if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        //to check ip is pass from proxy
        else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else {
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

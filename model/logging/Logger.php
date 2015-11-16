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

    private $syslogId;

    public function __construct($key) {
        $this->key = $key;
        $this->tableName = (Registry::get("LOG_TABLE")) ? Registry::get("LOG_TABLE") : "log";
        $this->logLevel = (Registry::get("LOG_LEVEL")) ? Registry::get("LOG_LEVEL") : self::OFF;
        $this->syslogId = $this->getSyslogId();
    }

    /**
     * @return string
     */
    private function getSyslogId() {
        $id = Registry::get('LOG_ID');
        if (!$id) {
            $id = array_pop(array_filter(explode('/', Registry::get('APP_DIR'))));
        }
        if (!$id) {
            $id = 'xframe_app';
        }
        return "assertis.{$id}";
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
        if (!is_dir(dirname($fileName))) {
            mkdir(dirname($fileName), 0777, true);
        }

        $this->logFile = $fileName;
    }

    /**
     * Log a debug message (dependant on the level of logging)
     * @param string $message
     * @param array $context
     */
    public function debug($message, array $context = []) {
        if ($this->logLevel >= self::DEBUG){
            $this->log("debug", $message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = []) {
        if ($this->logLevel >= self::INFO){
            $this->log("info", $message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function warn($message, array $context = []) {
        if ($this->logLevel >= self::WARN){
            $this->log("warn", $message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function audit($message, array $context = []) {
        if ($this->logLevel >= self::AUDIT){
            $this->log("audit", $message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function error($message, array $context = []) {
        if ($this->logLevel >= self::ERROR){
            $this->log("error", $message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function fatal($message, array $context = []) {
        if ($this->logLevel >= self::FATAL){
            $this->log("fatal", $message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @return string
     */
    private function interpolateMessage($message, array $context = []) {
        // build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    /**
     * @param string $level
     * @param string $message
     * @param array $context
     */
    private function log($level, $message, array $context = []) {
        $message = $this->interpolateMessage($message, $context);

        $this->logToSyslog($level, $message, $context);

        if ($this->logFile != null) {
            $this->logTofile($message);
        }
        else {
            $this->logToDatabase($level, $message);
        }
    }

    /**
     * @param int $level
     * @return int
     */
    private function getSyslogLevel($level) {
        switch ($level) {
            case self::DEBUG:
            case 'debug':
                return LOG_DEBUG;
            case self::INFO:
            case 'info':
                return LOG_INFO;
            case self::AUDIT:
            case 'audit':
                return LOG_NOTICE;
            case self::WARN:
            case 'warn':
                return LOG_WARNING;
            case self::ERROR:
            case 'error':
                return LOG_ERR;
            case self::FATAL:
            case 'fatal':
                return LOG_CRIT;
            default:
                return LOG_ERR;
        }
    }

    /**
     * @param int $level
     * @return string
     */
    private function getSyslogLevelName($level) {
        switch ($level) {
            case LOG_DEBUG: return 'DEBUG';
            case LOG_INFO: return 'INFO';
            case LOG_WARNING: return 'WARNING';
            case LOG_NOTICE: return 'NOTICE';
            case LOG_ERR: return 'ERROR';
            case LOG_CRIT: return 'CRITICAL';
            default: return '-';
        }
    }

    /**
     * @param int $xframeLevel
     * @param string $message
     * @param array $context
     */
    private function logToSyslog($xframeLevel, $message, array $context = []) {
        $level = $this->getSyslogLevel($xframeLevel);
        $levelName = $this->getSyslogLevelName($level);

        if ($level > LOG_NOTICE) return;

        $message = "<{$levelName}> {$message}";
        if (isset($context['location'])) {
            $message = $this->interpolateMessage($message." at {file}:{line} in {location}", $context);
        }

        openlog($this->syslogId, LOG_PID | LOG_NDELAY, LOG_USER);
        syslog($level, $message);
        closelog();
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
        $saveGraph = [];
        $log->save(false, $saveGraph, false);
    }

}

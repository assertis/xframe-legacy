<?php

/**
 * @author Linus Norton <linus.norton@assertis.co.uk>, Jason Paige <jason.paige@assertis.co.uk>
 *
 * This class logs to a database record
 *
 * SQL Required for logger is in install/logger.sql
 */
class Logger
{
    const DEBUG = 6, INFO = 5, WARN = 4, AUDIT = 3, ERROR = 2, FATAL = 1, OFF = 0;

    private $key;
    private $dbLogEnabled;
    private $tableName;
    private $logLevel;
    private $syslogId;

    /**
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = $key;
        $this->dbLogEnabled = false;
        $this->setSyslogId(Registry::get('LOG_ID'));
        $this->setLogTable(Registry::get('LOG_TABLE', 'log'));
        $this->setLogLevel(Registry::get('LOG_LEVEL', self::OFF));
    }

    /**
     * @param string|null $id
     */
    public function setSyslogId($id)
    {
        if (!$id) {
            $id = array_pop(array_filter(explode('/', Registry::get('APP_DIR'))));
        }
        if (!$id) {
            $id = 'xframe_app';
        }

        $this->syslogId = "assertis.{$id}." . str_replace(' ', '', $this->key);
    }

    /**
     * @param string $tableName
     */
    public function setLogTable($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @param int $level
     */
    public function setLogLevel($level)
    {
        $this->logLevel = $level;
    }

    public function enableDbLog()
    {
        $this->dbLogEnabled = true;
    }

    /**
     * @return bool
     */
    private function isDbLogEnabled()
    {
        return $this->dbLogEnabled;
    }

    /**
     * Log a debug message (dependant on the level of logging)
     *
     * @param string $message
     * @param array $context
     */
    public function debug($message, array $context = [])
    {
        if ($this->logLevel >= self::DEBUG) {
            $this->log("debug", $message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = [])
    {
        if ($this->logLevel >= self::INFO) {
            $this->log("info", $message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function warn($message, array $context = [])
    {
        if ($this->logLevel >= self::WARN) {
            $this->log("warn", $message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function audit($message, array $context = [])
    {
        if ($this->logLevel >= self::AUDIT) {
            $this->log("audit", $message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function error($message, array $context = [])
    {
        if ($this->logLevel >= self::ERROR) {
            $this->log("error", $message, $context);
        }
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function fatal($message, array $context = [])
    {
        if ($this->logLevel >= self::FATAL) {
            $this->log("fatal", $message, $context);
        }
    }

    /**
     * @param string $level
     * @param string $message
     * @param array  $context
     */
    private function log($level, $message, array $context = [])
    {
        $message = $this->interpolateMessage($message, $context);

        $this->logToSyslog($level, $message, $context);
        if ($this->isDbLogEnabled()) {
            $this->logToDatabase($level, $message);
        }
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return string
     */
    private function interpolateMessage($message, array $context = [])
    {
        // build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    /**
     * @param int $xframeLevel
     * @param string $message
     * @param array $context
     */
    private function logToSyslog($xframeLevel, $message, array $context = [])
    {
        $level = $this->getSyslogLevel($xframeLevel);
        $levelName = $this->getLevelName($level);

        $message = "<{$levelName}> {$message}";
        $message = $this->augumentMessageWithOrigin($message, $context);
        $message = $this->augumentMessageWithClientIP($message);
        $message = $this->augumentMessageWithTime($message);
        $message = $this->augumentMessageWithSessionId($message);
        $message = $this->augumentMessageWithExecutionTime($message);

        openlog($this->syslogId, LOG_PID | LOG_NDELAY, LOG_USER);
        syslog($level, $message);
        closelog();
    }

    /**
     * @param int|string $level
     *
     * @return int
     */
    private function getSyslogLevel($level)
    {
        switch ($level) {
            case self::DEBUG:
            case 'debug':
                return LOG_DEBUG;
            case self::INFO:
            case 'info':
                return LOG_INFO;
            case self::WARN:
            case 'warn':
                return LOG_WARNING;
            case self::AUDIT:
            case 'audit':
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
     *
     * @return string
     */
    private function getLevelName($level)
    {
        switch ($level) {
            case LOG_DEBUG:
                return 'DEBUG';
            case LOG_INFO:
                return 'INFO';
            case LOG_WARNING:
                return 'WARNING';
            case LOG_NOTICE:
                return 'NOTICE';
            case LOG_ERR:
                return 'ERROR';
            case LOG_CRIT:
                return 'CRITICAL';
            default:
                return '-';
        }
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return string
     */
    private function augumentMessageWithOrigin($message, array $context)
    {
        if (isset($context['location']) && isset($context['line']) && isset($context['file'])) {
            $message = $this->interpolateMessage($message . " at {file}:{line} in {location}", $context);
        }

        return $message;
    }

    /**
     * @param $message
     *
     * @return string
     */
    private function augumentMessageWithClientIP($message)
    {
        return "{$message} ip:'{$this->getIP()}'";
    }

    /**
     * @return string
     */
    private function getIP()
    {
        if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = 'x.x.x.x';
        }

        return $ip;
    }

    /**
     * @param $message
     *
     * @return string
     */
    private function augumentMessageWithTime($message)
    {
        $time = $this->getDate();

        return "{$message} date_time:'{$time}'";
    }

    /**
     * @return bool|string
     */
    private function getDate()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * @param $message
     *
     * @return string
     */
    private function augumentMessageWithSessionId($message)
    {
        $sessionId = session_id();

        return "{$message} session_id:'{$sessionId}'";
    }

    /**
     * @param $message
     *
     * @return string
     */
    private function augumentMessageWithExecutionTime($message)
    {
        $executionTime = $this->getExecutionTime();

        return "{$message} execution_time:'{$executionTime}'";
    }

    /**
     * @return string
     */
    private function getExecutionTime()
    {
        return number_format(microtime(true) - Controller::getExecutionTime(), 5);
    }

    /**
     * @param int|string $xframeLevel
     * @param string $message
     *
     * @throws FrameEx
     */
    private function logToDatabase($xframeLevel, $message)
    {
        $log = new Record($this->tableName);

        $log->ip = $this->getIP();
        $log->key = $this->key;
        $log->level = $this->getDatabaseLevel($xframeLevel);
        $log->message = $message;
        $log->date_time = $this->getDate();
        $log->session_id = session_id();
        $log->execution_time = $this->getExecutionTime();

        $saveGraph = [];
        $log->save(false, $saveGraph, false);
    }

    /**
     * @param int|string $level
     *
     * @return string
     */
    private function getDatabaseLevel($level)
    {
        switch ($level) {
            case self::DEBUG:
            case 'debug':
                return 'debug';
            case self::INFO:
            case 'info':
                return 'info';
            case self::WARN:
            case 'warn':
                return 'warn';
            case self::AUDIT:
            case 'audit':
                return 'audit';
            case self::ERROR:
            case 'error':
                return 'error';
            case self::FATAL:
            case 'fatal':
                return 'fatal';
            default:
                return 'error';
        }
    }
}

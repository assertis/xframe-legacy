<?php
/**
 * @author Linus Norton <linusnorton@gmail.com>
 * @package core
 *
 * This class encapsulates the default behaviour for framework exceptions.
 *
 */
class FrameEx extends Exception {
    protected $severity;

    const OFF = 0,
          CRITICAL = 1,
          HIGH = 2,
          MEDIUM = 3,
          LOW = 4,
          LOWEST = 5;

    /**
     * Creates the exception with a message and an error code that are
     * shown when the output method is called.
     *
     * @param String $message
     * @param int $code
     * @param int $severity
     * @param Exception $previous
     */
    public function __construct($message = null,
                                $code = 0,
                                $severity = self::HIGH,
                                Exception $previous = null) {
        
        parent::__construct($message, (int) $code, $previous);
        $this->severity = $severity;
    }

    /**
     * Reset the severity level
     * @param int $severity
     */
    public function setSeverity($severity) {
        $this->severity = $severity;
    }

    /**
     * Use the current registry settings to determine whether this error needs
     * to be logged or emailed (or both)
     */
    public function process() {
        if (Registry::get("ERROR_LOG_LEVEL") >= $this->severity) {
            $this->log();
        }
    }

    /**
     * @return string
     */
    private static function getLocation() {
        return empty($_SERVER['REQUEST_URI'])?
            $_SERVER['SCRIPT_FILENAME']:
            $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
    }

    /**
     * Log using the error_log and LoggerManager
     */
    protected function log() {
        error_log($_SERVER["REQUEST_URI"].": ".$this->message);
        
        $logger = LoggerManager::getLogger("Exception");
        $details = [
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'location' => self::getLocation()
        ];

        switch ($this->severity) {
            case self::CRITICAL: 
                $logger->fatal($this->message, $details); break;
            case self::HIGH: 
                $logger->error($this->message, $details); break;
            case self::MEDIUM: 
                $logger->warn($this->message, $details); break;
            case self::LOW: 
                $logger->info($this->message, $details); break;
            case self::LOWEST: 
                $logger->debug($this->message, $details); break;
            default: 
                $logger->warn($this->message, $details); break;
        }
    }

    /**
     * Get the XML for this exception
     */
    public function getXML() {
        $out = "<exception>";
        $out .= "<message>".htmlspecialchars($this->message, ENT_COMPAT, "UTF-8", false)."</message>";
        $out .= "<code>".htmlspecialchars($this->code, ENT_COMPAT, "UTF-8", false)."</code>";
        $out .= "<location>".htmlspecialchars(self::getLocation(), ENT_COMPAT, "UTF-8", false)."</location>";
        $out .= "<getVariables>";
        foreach ($_GET as $key => $value){
            $out .= "<variable key='".$key."' value='".$value."' />";
        }
        $out .= "</getVariables>";
        $out .= "<postVariables>";
        foreach ($_POST as $key => $value){
            if (strstr($key,"card")){
                continue;
            }
            else {
                $out .= "<variable key='".$key."' value='".$value."' />";
            }
        }
        $out .= "</postVariables>";
        $out .= "<ipaddress>".$this->getIP()."</ipaddress>";
        $out .= "<backtrace>";
        $i = 1;

        foreach ($this->getReversedTrace() as $back) {
            if ($back["class"] != "FrameEx") {
                $out .= "<step number='".$i++."' line='{$back['line']}' file='{$back['file']}' class='{$back['class']}' function='{$back['function']}' />";
            }
        }
        $out .= "</backtrace>";
        $out .= "</exception>";
        return $out;
    }

    public function getJSON() {
        return [
            'exception' => [
                'message' => htmlspecialchars($this->message, ENT_COMPAT, "UTF-8", false),
                'code' => htmlspecialchars($this->code, ENT_COMPAT, "UTF-8", false)
            ]
        ];
    }

    /**
     * Return the array reversed back trace
     * @return array
     */
    public function getReversedTrace() {
        $trace = [];

        foreach (array_reverse($this->getTrace()) as $back) {
            $back['file'] = (array_key_exists("file", $back)) ? basename($back['file']) : "";
            $back['class'] = (array_key_exists("class", $back)) ? $back['class'] : "";
            $back['line'] = (array_key_exists("line", $back)) ? $back['line'] : "";
            $trace[] = $back;
        }

        return $trace;
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->message;
    }

    /**
     * Save the exception for the next page
     */
    public function persist() {
        if (!is_array($_SESSION["exceptions"])) {
            $_SESSION["exceptions"] = [];
        }

        $_SESSION["exceptions"][] = $this;
    }

    /**
     * Return an array of exceptions that were persisted
     * @return array
     */
    public static function getPersistedExceptions() {
        //deal with any exceptions that were redirected
        if (array_key_exists("exceptions",$_SESSION) && is_array($_SESSION["exceptions"])) {
            //store in temp var
            $execptions = $_SESSION["exceptions"];
            //clear from session
            $_SESSION["exceptions"] = [];
            //return
            return $execptions;
        }

        return [];
    }

    /**
     * Set the error code
     * @param int $code
     */
    public function setCode($code) {
        $this->code = $code;
    }

    /**
     * Set the message
     * @param string $message
     */
    public function setMessage($message) {
        $this->message = $message;
    }

    /**
     * Handles PHP generated errors
     */
    public static function errorHandler($type, $msg, $filename, $line ) {
        if (!(error_reporting() & $type)) {
            // This error code is not included in error_reporting
            return;
        }

        $errortype = [
            E_ERROR              => 'Error',
            E_WARNING            => 'Warning',
            E_PARSE              => 'Parsing Error',
            E_NOTICE             => 'Notice',
            E_CORE_ERROR         => 'Core Error',
            E_CORE_WARNING       => 'Core Warning',
            E_COMPILE_ERROR      => 'Compile Error',
            E_COMPILE_WARNING    => 'Compile Warning',
            E_USER_ERROR         => 'User Error',
            E_USER_WARNING       => 'User Warning',
            E_USER_NOTICE        => 'User Notice',
            E_STRICT             => 'Strict',
            E_RECOVERABLE_ERROR  => 'Recoverable Error'
        ];

        $error = (array_key_exists($type, $errortype)) ? $errortype[$type] : $type;
        throw new FrameEx($error.": ".$msg." (line {$line} of ".basename($filename).")");
    }

    /**
     * If an exception is thrown that is not in a try catch statement it comes
     * here. It is then output to the screen and code execution stops
     *
     * @param Exception $exception
     */
    public static function exceptionHandler($exception) {
        //if it's not a FrameEx make it one
        if (!($exception instanceof FrameEx)) {
            $exception = new FrameEx($exception->getMessage(),
                                     $exception->getCode(),
                                     FrameEx::HIGH,
                                     $exception);
        }

        try {
            $exception->process();
        }
        catch (Exception $e) {
            trigger_error("Error logging exception: ".$e->getMessage());
        }

        //finally echo it out
        trigger_error($exception->__toString());
    }

    /**
     * Setup the error handling
     */
    public static function init() {
        set_exception_handler(["FrameEx", "exceptionHandler"]);
        set_error_handler(["FrameEx", "errorHandler"], ini_get("error_reporting"));
    }

    public function getIP() {
        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"),"unknown")){
           $ip = getenv("HTTP_CLIENT_IP");
        }
        else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")) {
           $ip = getenv("HTTP_X_FORWARDED_FOR");
        }
        else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")) {
           $ip = getenv("REMOTE_ADDR");
        }
        else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")) {
           $ip = $_SERVER['REMOTE_ADDR'];
        }
        else {
           $ip = "unknown";
        }

       return($ip);
    }

}

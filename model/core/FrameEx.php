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
                                Throwable $previous = null) {
        
        parent::__construct($message, (int) $code, $previous);
        $this->severity = $severity;
    }

    /**
     * @return int
     */
    public function getSeverity(): int {
        return $this->severity;
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
        ErrorHandler::logException($this);
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
        ErrorHandler::logException($this, true);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @return string
     */
    private function getVariableXML($key, $value): string {
        return "<variable key=\"" . htmlspecialchars($key) . "\" value=\"" . htmlspecialchars($value) . "\" />";
    }

    /**
     * Get the XML for this exception
     */
    public function getXML() {
        $out = "<exception>";
        $out .= "<message>".htmlspecialchars($this->message, ENT_COMPAT, "UTF-8", false)."</message>";
        $out .= "<code>".htmlspecialchars($this->code, ENT_COMPAT, "UTF-8", false)."</code>";
        $out .= "<location>".htmlspecialchars(self::getLocation(), ENT_COMPAT, "UTF-8", false)."</location>";
        $out .= $this->getVariablesXML('getVariables', $_GET);
        
        $out .= "<getVariables>";
        foreach ($_GET as $key => $value){
            $out .= $this->getVariableXML($key, $value);
        }
        $out .= "</getVariables>";
        
        $out .= "<postVariables>";
        foreach ($_POST as $key => $value){
            if (!strstr($key,"card")){
                $out .= $this->getVariableXML($key, $value);
            }
        }
        $out .= "</postVariables>";
        
        $out .= "<ipaddress>".htmlspecialchars($this->getIP())."</ipaddress>";

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
     * Setup the error handling
     */
    public static function init() {
        ErrorHandler::init();
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

<?php

/**
 * @author Linus Norton <linusnorton@gmail.com>
 * @author Michał Tatarynowicz <michal.tatarynowicz@assertis.co.uk>
 */
class ErrorHandler
{
    private const ERROR_MAP = [
        E_PARSE             => FrameEx::CRITICAL,
        E_CORE_ERROR        => FrameEx::CRITICAL,
        E_COMPILE_ERROR     => FrameEx::CRITICAL,
        E_ERROR             => FrameEx::HIGH,
        E_USER_ERROR        => FrameEx::HIGH,
        E_RECOVERABLE_ERROR => FrameEx::HIGH,
        E_WARNING           => FrameEx::MEDIUM,
        E_CORE_WARNING      => FrameEx::MEDIUM,
        E_COMPILE_WARNING   => FrameEx::MEDIUM,
        E_USER_WARNING      => FrameEx::MEDIUM,
        E_NOTICE            => FrameEx::LOW,
        E_USER_NOTICE       => FrameEx::LOW,
        E_STRICT            => FrameEx::LOW,
    ];

    private const NAME_MAP = [
        FrameEx::CRITICAL => 'CRITICAL',
        FrameEx::HIGH     => 'ERROR',
        FrameEx::MEDIUM   => 'WARNING',
        FrameEx::LOW      => 'NOTICE',
        FrameEx::LOWEST   => 'INFO',
    ];

    /** @var ExceptionHandlerInterface[] */
    private static $additionalHandlers = [];

    /**
     * Setup the error handling
     */
    public static function init()
    {
        set_exception_handler([self::class, 'exceptionHandlerCallback']);
        set_error_handler([self::class, 'errorHandlerCallback'], ini_get('error_reporting'));
    }

    /**
     * @param ExceptionHandlerInterface $handler
     */
    public static function addHandler(ExceptionHandlerInterface $handler): void
    {
        self::$additionalHandlers[] = $handler;
    }

    /**
     * @param int $type
     * @param string $msg
     * @param string $filename
     * @param int $line
     * @throws FrameEx
     */
    public static function errorHandlerCallback(int $type, string $msg, string $filename, int $line): void
    {
        if (!(error_reporting() & $type)) {
            return;
        }

        throw new FrameEx(
            '[PHP] ' . $msg,
            100,
            FrameEx::HIGH,
            new ErrorException($msg, 100, $type, $filename, $line)
        );
    }

    /**
     * If an exception is thrown that is not in a try catch statement it comes
     * here. It is then output to the screen and code execution stops
     *
     * @param Throwable $exception
     */
    public static function exceptionHandlerCallback(Throwable $exception)
    {
        self::logException($exception);
        self::displayException($exception);
    }

    /**
     * Log using the error_log and LoggerManager
     *
     * @param Throwable $exception
     * @param bool $force
     */
    public static function logException(Throwable $exception, bool $force = false)
    {
        $source = $exception->getPrevious() ?? $exception;
        $severity = self::getSeverity($source);

        if (Registry::get('ERROR_LOG_LEVEL') < $severity && !$force) {
            return;
        }

        foreach (self::$additionalHandlers as $handler) {
            /** @var ExceptionHandlerInterface $handler */
            $handler->exception($exception);
        }

        $message = self::getMessage($source);

        error_log(sprintf(
            '%s: %s\n%s',
            $_SERVER["REQUEST_URI"],
            $message,
            $source->getTraceAsString()
        ));

        $details = [
            'file'     => $source->getFile(),
            'line'     => $source->getLine(),
            'location' => self::getLocation()
        ];

        $logger = LoggerManager::getLogger('Exception');

        switch ($severity) {
            case FrameEx::CRITICAL:
                $logger->fatal($message, $details);
                break;
            case FrameEx::HIGH:
                $logger->error($message, $details);
                break;
            case FrameEx::MEDIUM:
                $logger->warn($message, $details);
                break;
            case FrameEx::LOW:
                $logger->info($message, $details);
                break;
            case FrameEx::LOWEST:
                $logger->debug($message, $details);
                break;
            default:
                $logger->warn($message, $details);
                break;
        }
    }

    /**
     * @param Throwable $exception
     */
    private static function displayException(Throwable $exception): void
    {
        $source = $exception->getPrevious() ?? $exception;
        $message = self::getMessage($source);

        if (php_sapi_name() == 'cli') {
            print sprintf(
                "\n%s\n  at %s:%s\n    %s\n",
                $message,
                $source->getFile(),
                $source->getLine(),
                str_replace("\n", "\n    ", $source->getTraceAsString())
            );
        } else {
            $style = 'display:block;color:#FFF;background-color:#444;margin:1em;padding:1em';
            print sprintf(
                "<pre style='%s'><b>%s</b>\n&nbsp;&nbsp;at %s:%s\n<small>&nbsp;&nbsp;&nbsp;&nbsp;%s</small></pre>",
                $style,
                $message,
                $source->getFile(),
                $source->getLine(),
                str_replace("\n", "\n&nbsp;&nbsp;&nbsp;&nbsp;", $source->getTraceAsString())
            );
        }
    }

    /**
     * @param Throwable $exception
     * @return string
     */
    private static function getMessage(Throwable $exception): string
    {
        $severity = self::getSeverity($exception);

        $name = self::getSeverityName($severity);
        $message = $exception->getMessage();

        return sprintf('[%s] %s', $name, $message);
    }

    /**
     * @param Throwable $exception
     * @return int
     */
    private static function getSeverity(Throwable $exception): int
    {
        if ($exception instanceof ErrorException) {
            return array_key_exists($exception->getSeverity(), self::ERROR_MAP)
                ? self::ERROR_MAP[$exception->getSeverity()]
                : self::ERROR_MAP[E_ERROR];
        } elseif ($exception instanceof FrameEx) {
            return $exception->getSeverity();
        } else {
            return FrameEx::HIGH;
        }
    }

    /**
     * @param int $severity
     * @return string
     */
    private static function getSeverityName(int $severity): string
    {
        return self::NAME_MAP[$severity];
    }

    /**
     * @return string
     */
    private static function getLocation(): string
    {
        return empty($_SERVER['REQUEST_URI'])
            ? $_SERVER['SCRIPT_FILENAME']
            : $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
    }
}

<?php

/**
 * @author Bartłomiej Olewiński <bartlomiej.olewinski@assertis.co.uk>
 * @package core
 *
 * Interface for objects able to capture and handle exception
 */
interface ExceptionHandlerInterface
{
    /**
     * @param Throwable $exception
     */
    public function exception(Throwable $exception): void;
}
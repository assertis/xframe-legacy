<?php

/**
 * @author Linus Norton <linusnorton@gmail.com>
 * @package request
 *
 * This dispatcher stores a mapping of requests to handlers and dispatches requests to their correct handler
 */
class Dispatcher {
    private static $listeners = array();

    /**
     * This method takes the given request finds the resource and gets the response from the
     * resources controller.
     *
     * @param Request $r
     * @return string
     */
    public static function dispatch(Request $r) {
        $res = self::getListener($r->getKey());
        if ($res != NULL) {
            return $res->getController($r)->getResponse();
        }

        //if we rebuild on 404, disable this for performance
        if (Registry::get("AUTO_REBUILD_REQUEST_MAP")) {
            RequestMapGenerator::buildAll(true);

            $res = self::getListener($r->getKey());
            if ($res != NULL) {
                return $res->getController($r)->getResponse();
            }
        }

        //otherwise 404 (no need to add die, execution will end anyway
        header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
    }

    public static function getListener($key) {
        //if we have a mapping for the request
        if (array_key_exists($key, self::$listeners)) {
            //return the response from the controller
            return self::$listeners[$key];
        }

        // try different request types
        $key = substr($key, (strpos($key, '_') != NULL ? strpos($key, '_')+1 : 0));
        if (array_key_exists($key, self::$listeners)) {
            return self::$listeners[$key];
        }

        return NULL;
    }

    /**
     * This registers a method to call for a given a request
     *
     * @param String $request
     * @param String $class
     * @param String $method
     * @param int $cacheLength
     * @param array $parameterMap
     * @param String $requestType
     */
    public static function addListener($requestName, $class, $method, $cacheLength = false, array $parameterMap = array(), $authenticator = null, $requestType = Request::GET) {
        self::$listeners[Request::makeKey($requestType, $requestName)] = new Resource($requestName,
                                                                                      $requestType,
                                                                                      $class,
                                                                                      $method,
                                                                                      $parameterMap,
                                                                                      $authenticator,
                                                                                      $cacheLength);
    }

    /**
     * Adds a resource to the list
     * 
     * @param Resource $resource
     */
    public static function addResource(Resource $resource) {
        self::$listeners[$resource->getKey()] = $resource;
    }

    /**
     * get the cache length for the given request
     *
     * @param $request Request to get the cache length for
     */
    public static function getCacheLength(Request $r) {
        if (array_key_exists($r->getKey(), self::$listeners)) {
            return self::$listeners[$r->getKey()]->getCacheLength();
        }
        else {
           return false;
        }
    }

    /**
     * Get the parameter map for the given request
     * @param array $requestName
     * @return array
     */
    public static function getParameterMap($requestName, $requestType) {
        return array_key_exists(Request::makeKey($resourceType, $requestName), self::$listeners) ? self::$listeners[Request::makeKey($requestType, $requestName)]->getParameterMap() : array();
    }

    /**
     * @return array
     */
    public static function getListeners() {
        return self::$listeners;
    }

    /**
     * Manually set the listeners
     * @param array $listeners
     */
    public static function setListeners($listeners) {
        self::$listeners = $listeners;
    }
}

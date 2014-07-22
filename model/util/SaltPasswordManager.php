<?php

/**
 * Class SaltPasswordManager
 *
 * Class provides method to work with hashed password
 *
 * @author Maciej Romanski <maciej.romanski@assertis.co.uk>
 */
final class SaltPasswordManager {

    /**
     * Method make simple hash for password
     * @param $password
     * @return string
     */
    public static function generateSimpleHash($password) {
        return sha1($password);
    }

    /**
     * Method return salt for password hashing
     * @return string
     */
    public static function generateSalt() {
        return md5(uniqid(mt_rand(), true));
    }

    /**
     * Method return array with password hash and salt
     * @param $password
     * @param null $salt
     * @return array
     */
    public static function generateSaltedPassword($password, $salt = null) {
        if (empty($salt)) {
            $salt = static::generateSalt();
        }
        return array(hash('sha256', $password . $salt), $salt);
    }

    /**
     * Method check if password and credential are equal. Password should be hashed password with salt
     * @param $password
     * @param $credential
     * @param null $salt
     * @return bool
     */
    public static function checkPasswordWithHash($password, $salt, $credential) {
        if (empty($salt)) {
            return false;
        }
        list($hash, $hashSalt) = static::generateSaltedPassword($credential, $salt);
        return $password == $hash;
    }

} 
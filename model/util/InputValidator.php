<?php

/**
 * @author Linus Norton <linusnorton@gmail.com>
 * @package util
 *
 * This is not a sanitizor, it just length, valid email etc
 */
class InputValidator {

    /**
     * Validate the given string to see if it is valid email
     * 
     * @param $email string email address input to validate
     */ 
    public static function isEmail($email) {
        $pattern = '/^([\w\-\.\']+)@((\[([0-9]{1,3}\.){3}[0-9]{1,3}\])|(([\w\-]+\.)+)([a-zA-Z]{2,4}))$/';
        return (preg_match($pattern, $email)) ? true : false;        
    }

    /**
     * Validate the given string to see if it is valid photocard number
     * 
     * @param $number string photocard number to validate
     */ 
    public static function isPhotocardNumber($number) {
        $pattern = '/^(\w{7,8})$/';
        return preg_match($pattern, $number);
    }    

    /**
     * checks to see if the given string is greater than or
     * less than the given length
     *
     * @param $input string string to check
     * @param $length int length to check
     */
    public static function isLength($input, $length) {
        return (strlen($input) >= $length);
    }

    /**
     * check to see if the given input is not empty (has a length > 1)
     *
     * @param $input string string to check
     */
    public static function isEmpty($input) {
        return empty($input);
    }
	
    /**
     * Check to see if the given input is not empty.
     * It do not consider "0" string as a empty.
     *
     * @param $input
     * @return bool
     */
    public static function isEmptyString($input) {
	return strlen((string)$input) === 0;
    }	

    /**
     * Check to see if the string === null
     *
     * @param $input string string to check
     */
    public static function notNull($input) {
        return $input === null;
    }

    /**
     * Check to see if the string is a sha1 hash
     *
     * @param $input string string to check
     * @return int
     */
    public static function isSha1($input) {
        return preg_match("/^[a-f0-9]{40}$/", strtolower($input));
    }

    /**
     * Check if $input is string hashed by our hash methods
     *
     * @param $input
     * @return int
     */
    public static function isPasswordSalted($input) {
        return preg_match("/^[a-f0-9]{64}$/", strtolower($input));
    }

    /**
     * Check if $input is salt
     * @param $input
     * @return int
     */
    public static function isSaltPassword($input) {
        return preg_match("/^[a-f0-9]{32}$/", strtolower($input));
    }

    /**
     * Returns true if the given string is between the given lengths
     * @param string $input
     * @param int $minChars
     * @param int $maxChars
     * @return boolean
     */
    public static function isBetweenLength($input, $minChars, $maxChars) {
        $length = strlen($input);
        return ($length >= $minChars && $length <= $maxChars);
    }


     /**
     * Check if a string is in fact a PHP seralized string or not
     *
     * Bit ungracefull in forcing an error and then disabling the output
     * but PHP doesn't leave you with many options here.
     *
     * NOTE: A seralized boolean false will cause this function to fail.
     *
     * $data === "b:0;" - this checks for a seralized boolean false; cant
     * imagine why anyone would seralize this so its not checked for.
     * If you find you need this, perhaps you should look at why you are
     * seralizing a boolean value.
     *
     * @param string $data
     * @return boolean
     */
    public static function isSerialized($data) {
        return (@unserialize($data) !== false);
    }

    /**
     * Check if a $string is valid XML. This does not check against any schema
     * so it is just seing if it is well formed XML.
     *
     * @param string $string
     * @return boolean
     */
    public static function isValidXml ($string) {
        try {
            DOMDocument::loadXML($string);
            return true;
        } 
        catch (FrameEx $ex) {
            return false;
        }
    }

    /**
     * Check if $postCode is a valid postcode.
     * Based on the rules at: http://www.mrs.org.uk/standards/downloads/postcodeformat.pdf
     * @param <type> $postCode
     * @return boolean
     */
    public static function isPostCode($input) {
        return preg_match("/^([A-PR-UWYZ0-9][A-HK-Y0-9][AEHMNPRTVXY0-9]?[ABEHMNPRVWXY0-9]? {1,2}[0-9][ABD-HJLN-UW-Z]{2}|GIR 0AA)$/", $input);
    }
    
    /**
     * Check if $input is a valid telephone number.
     * @param <type> $input
     * @return boolean
     */
    public static function isPhoneNumber($input) {
        return preg_match("/^[0-9\+ ]{9,20}$/", $input);
    }

	/**
	 * Replace international characters with ASCII versions
	 * @param <type> $txt
	 * @return string
	 */
	public static function translateString($txt) {
	    $transliterationTable = array('á' => 'a', 'Á' => 'A', 'à' => 'a', 'À' => 'A', 'ă' => 'a', 'Ă' => 'A', 'â' => 'a', 'Â' => 'A', 'å' => 'a', 'Å' => 'A', 'ã' => 'a', 'Ã' => 'A', 'ą' => 'a', 'Ą' => 'A', 'ā' => 'a', 'Ā' => 'A', 'ä' => 'ae', 'Ä' => 'AE', 'æ' => 'ae', 'Æ' => 'AE', 'ḃ' => 'b', 'Ḃ' => 'B', 'ć' => 'c', 'Ć' => 'C', 'ĉ' => 'c', 'Ĉ' => 'C', 'č' => 'c', 'Č' => 'C', 'ċ' => 'c', 'Ċ' => 'C', 'ç' => 'c', 'Ç' => 'C', 'ď' => 'd', 'Ď' => 'D', 'ḋ' => 'd', 'Ḋ' => 'D', 'đ' => 'd', 'Đ' => 'D', 'ð' => 'dh', 'Ð' => 'Dh', 'é' => 'e', 'É' => 'E', 'è' => 'e', 'È' => 'E', 'ĕ' => 'e', 'Ĕ' => 'E', 'ê' => 'e', 'Ê' => 'E', 'ě' => 'e', 'Ě' => 'E', 'ë' => 'e', 'Ë' => 'E', 'ė' => 'e', 'Ė' => 'E', 'ę' => 'e', 'Ę' => 'E', 'ē' => 'e', 'Ē' => 'E', 'ḟ' => 'f', 'Ḟ' => 'F', 'ƒ' => 'f', 'Ƒ' => 'F', 'ğ' => 'g', 'Ğ' => 'G', 'ĝ' => 'g', 'Ĝ' => 'G', 'ġ' => 'g', 'Ġ' => 'G', 'ģ' => 'g', 'Ģ' => 'G', 'ĥ' => 'h', 'Ĥ' => 'H', 'ħ' => 'h', 'Ħ' => 'H', 'í' => 'i', 'Í' => 'I', 'ì' => 'i', 'Ì' => 'I', 'î' => 'i', 'Î' => 'I', 'ï' => 'i', 'Ï' => 'I', 'ĩ' => 'i', 'Ĩ' => 'I', 'į' => 'i', 'Į' => 'I', 'ī' => 'i', 'Ī' => 'I', 'ĵ' => 'j', 'Ĵ' => 'J', 'ķ' => 'k', 'Ķ' => 'K', 'ĺ' => 'l', 'Ĺ' => 'L', 'ľ' => 'l', 'Ľ' => 'L', 'ļ' => 'l', 'Ļ' => 'L', 'ł' => 'l', 'Ł' => 'L', 'ṁ' => 'm', 'Ṁ' => 'M', 'ń' => 'n', 'Ń' => 'N', 'ň' => 'n', 'Ň' => 'N', 'ñ' => 'n', 'Ñ' => 'N', 'ņ' => 'n', 'Ņ' => 'N', 'ó' => 'o', 'Ó' => 'O', 'ò' => 'o', 'Ò' => 'O', 'ô' => 'o', 'Ô' => 'O', 'ő' => 'o', 'Ő' => 'O', 'õ' => 'o', 'Õ' => 'O', 'ø' => 'oe', 'Ø' => 'OE', 'ō' => 'o', 'Ō' => 'O', 'ơ' => 'o', 'Ơ' => 'O', 'ö' => 'oe', 'Ö' => 'OE', 'ṗ' => 'p', 'Ṗ' => 'P', 'ŕ' => 'r', 'Ŕ' => 'R', 'ř' => 'r', 'Ř' => 'R', 'ŗ' => 'r', 'Ŗ' => 'R', 'ś' => 's', 'Ś' => 'S', 'ŝ' => 's', 'Ŝ' => 'S', 'š' => 's', 'Š' => 'S', 'ṡ' => 's', 'Ṡ' => 'S', 'ş' => 's', 'Ş' => 'S', 'ș' => 's', 'Ș' => 'S', 'ß' => 'SS', 'ť' => 't', 'Ť' => 'T', 'ṫ' => 't', 'Ṫ' => 'T', 'ţ' => 't', 'Ţ' => 'T', 'ț' => 't', 'Ț' => 'T', 'ŧ' => 't', 'Ŧ' => 'T', 'ú' => 'u', 'Ú' => 'U', 'ù' => 'u', 'Ù' => 'U', 'ŭ' => 'u', 'Ŭ' => 'U', 'û' => 'u', 'Û' => 'U', 'ů' => 'u', 'Ů' => 'U', 'ű' => 'u', 'Ű' => 'U', 'ũ' => 'u', 'Ũ' => 'U', 'ų' => 'u', 'Ų' => 'U', 'ū' => 'u', 'Ū' => 'U', 'ư' => 'u', 'Ư' => 'U', 'ü' => 'ue', 'Ü' => 'UE', 'ẃ' => 'w', 'Ẃ' => 'W', 'ẁ' => 'w', 'Ẁ' => 'W', 'ŵ' => 'w', 'Ŵ' => 'W', 'ẅ' => 'w', 'Ẅ' => 'W', 'ý' => 'y', 'Ý' => 'Y', 'ỳ' => 'y', 'Ỳ' => 'Y', 'ŷ' => 'y', 'Ŷ' => 'Y', 'ÿ' => 'y', 'Ÿ' => 'Y', 'ź' => 'z', 'Ź' => 'Z', 'ž' => 'z', 'Ž' => 'Z', 'ż' => 'z', 'Ż' => 'Z', 'þ' => 'th', 'Þ' => 'Th', 'µ' => 'u', 'а' => 'a', 'А' => 'a', 'б' => 'b', 'Б' => 'b', 'в' => 'v', 'В' => 'v', 'г' => 'g', 'Г' => 'g', 'д' => 'd', 'Д' => 'd', 'е' => 'e', 'Е' => 'e', 'ё' => 'e', 'Ё' => 'e', 'ж' => 'zh', 'Ж' => 'zh', 'з' => 'z', 'З' => 'z', 'и' => 'i', 'И' => 'i', 'й' => 'j', 'Й' => 'j', 'к' => 'k', 'К' => 'k', 'л' => 'l', 'Л' => 'l', 'м' => 'm', 'М' => 'm', 'н' => 'n', 'Н' => 'n', 'о' => 'o', 'О' => 'o', 'п' => 'p', 'П' => 'p', 'р' => 'r', 'Р' => 'r', 'с' => 's', 'С' => 's', 'т' => 't', 'Т' => 't', 'у' => 'u', 'У' => 'u', 'ф' => 'f', 'Ф' => 'f', 'х' => 'h', 'Х' => 'h', 'ц' => 'c', 'Ц' => 'c', 'ч' => 'ch', 'Ч' => 'ch', 'ш' => 'sh', 'Ш' => 'sh', 'щ' => 'sch', 'Щ' => 'sch', 'ъ' => '', 'Ъ' => '', 'ы' => 'y', 'Ы' => 'y', 'ь' => '', 'Ь' => '', 'э' => 'e', 'Э' => 'e', 'ю' => 'ju', 'Ю' => 'ju', 'я' => 'ja', 'Я' => 'ja', 'º' => '');
	    $txt = str_replace(array_keys($transliterationTable), array_values($transliterationTable), $txt);
	    return $txt;
	}
}

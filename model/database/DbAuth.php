<?php
/**
 * Authorise a user against a credential and identity
 * @author Dominic Webb <dominic.webb@assertis.net>, Linus Norton <linusnorton@gmail.com>
 */
class DbAuth implements AuthenticationAdapter {

    const SALT_NAME = "salt_";

    private $authorisedId;
    private $identity;
    private $credential;
    private $saltCredential;
    private $originalCredential;
    private $saltExists = true;
    private $table;
    private $identityColumn;
    private $credentialColumn;
    private $identityKey;

    /**
     *
     * @param string $table The table we are going to query form the authorisation
     * @param string $identityColumn The returned column name that will give us the instance identity
     * @param string $credentialColumn
     * @param string $identityKey
     */
    public function __construct($table,
                                $identityColumn,
                                $credentialColumn,
                                $identityKey){
        $this->table = $table;
        $this->identityColumn = $identityColumn;
        $this->credentialColumn = $credentialColumn;
        $this->identityKey = $identityKey;
    }


    /**
     * Set the identity that is to be authenticated
     * @param string $identity The identity e.g. email, username
     * @return DbAuth
     */
    public function setIdentity($identity) {
        $this->identity = $identity;
        return $this;
    }


    /**
     * Return the identity value that has been set
     * @return string
     */
    public function getIdentity() {
        return $this->identity;
    }

    /**
     * Set the credential to be used in authentications
     * @param string $credential The credential e.g. password or token or key code
     * @param bool $hash
     * @return DbAuth
     */
    public function setCredential($credential, $hash = true) {
        $this->originalCredential = $credential;
        if ($hash) {
            $credential = sha1($credential);
        }

        //Move after if for auto login
        $this->saltCredential = self::hashWithSalt($credential);

        $this->credential = $credential;
        return $this;
    }

    /**
     * Method hash password with generating salt
     * @param $password
     * @return string
     */
    public static function hashWithSalt($password) {
        $salt = md5(uniqid(mt_rand(), true));
        return self::hashPassword($password, $salt);
    }

    /**
     * Method hash password using salt
     * @param $password
     * @param $salt
     * @return string
     */
    public static function hashPassword($password, $salt) {
        return hash('sha256', $password . $salt) . ":" . $salt;
    }

    /**
     * Return the credential value that has been set
     * @return $this->credential | false
     */
    public function getCredential() {
        return $this->credential;
    }

    /**
     * Perform the authorisation request
     * @return DbAuth
     */
    public function authenticate() {
        $credential = $this->getCredential();
        Assert::isNotEmpty($credential, "You must set a password before authentication");

        $identity = $this->getIdentity();

        Assert::isNotEmpty($identity, "You must set an username before authentication");
        //We check first if we have record with password that is salt_password
        $records = $this->checkWithSalt();
        //If we get null it means we don't have salt_password or password was incorrect
        if (empty($records)) {
            $criteria = new Criteria(Restriction::is($this->credentialColumn, $credential));
            $criteria->addAnd(Restriction::is($this->identityColumn, $identity));
            $records = TableGateway::loadMatching($this->table, $criteria);
        }

        if ($records->count() == 0) {
            return false;
        }

        if ($this->saltExists) {
            $this->addSalt();
        }

        return $this->authorisedId = $records->current()->__get($this->identityKey);
    }

    /**
     * Method check if we have password with salt
     * @return null|Results
     */
    private function checkWithSalt() {
        try {
            $criteria = new Criteria(Restriction::is($this->identityColumn, $this->getIdentity()));
            $records = TableGateway::loadMatching($this->table, $criteria);
            if ($records->count() > 0) {
                if (self::authenticateSalt($records->current()->__get(self::SALT_NAME . $this->credentialColumn), $this->originalCredential)) {
                    return $records;
                };
            }
        } catch (FrameEx $e) {
            $this->saltExists = false;
        }
        return null;
    }

    /**
     * Add salt password to model
     */
    private function addSalt() {
        $criteria = new Criteria(Restriction::is($this->identityColumn, $this->getIdentity()));
        $records = TableGateway::loadMatching($this->table, $criteria);
        $record = $records->current();
        $record->__set(self::SALT_NAME . $this->credentialColumn, $this->saltCredential);
        $record->__set($this->credentialColumn, "PASSWORD");
        $record->save();
    }

    /**
     * Method check if password is same with $credential
     * @param $password
     * @param $credential
     * @return bool
     */
    public static function authenticateSalt($password, $credential) {
        list($psw, $salt) = explode(":", $password);
        return $password == self::hashPassword($credential, $salt);
    }


    /**
     * Determines whether the authorisation atempt produced a valid result
     * @return boolean
     */
    public function hasIdentity() {
        return (!$this->authorisedId) ? false : true;
    }

    /**
     * @return mixed
     */
    public function getAuthIdentity() {
        return $this->authorisedId;
    }

    /**
     * @param string $namespace
     */
    public function persistAuthIdentity ($namespace) {
        $_SESSION[$namespace] = $this->getAuthIdentity();
    }

}
<?php
/**
 * Authorise a user against a credential and identity
 * @author Dominic Webb <dominic.webb@assertis.net>, Linus Norton <linusnorton@gmail.com>
 */
class DbAuth implements AuthenticationAdapter {

    private $authorisedId;
    private $identity;
    private $credential;
    private $hash;
    private $table;
    private $identityColumn;
    private $credentialColumn;
    private $slatedCredentialColumn;
    private $credentialSaltColumn;
    private $identityKey;

    /**
     *
     * @param string $table The table we are going to query form the authorisation
     * @param string $identityColumn The returned column name that will give us the instance identity
     * @param string $credentialColumn
     * @param string $slatedCredentialColumn
     * @param string $credentialSaltColumn
     * @param string $identityKey
     */
    public function __construct($table,
                                $identityColumn,
                                $credentialColumn,
                                $identityKey,
                                $slatedCredentialColumn = null,
                                $credentialSaltColumn = null) {
        $this->table = $table;
        $this->identityColumn = $identityColumn;
        $this->credentialColumn = $credentialColumn;
        $this->slatedCredentialColumn = $slatedCredentialColumn;
        $this->credentialSaltColumn = $credentialSaltColumn;
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
        $this->credential = $credential;
        $this->hash = $hash;
        return $this;
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

        //We first get user from db if its exists
        $criteria = new Criteria(Restriction::is($this->identityColumn, $identity));
        $records = TableGateway::loadMatching($this->table, $criteria);

        if ($records->count() == 0) {
            return false;
        }

        /** @var $user Customer */
        $user = $records->current();
        //Yes we need to reassign this to variable.
        $credentialColumn = $this->credentialColumn;
        $credentialSaltColumn = $this->credentialSaltColumn;
        $slatedCredentialColumn = $this->slatedCredentialColumn;

        //We check if we should use salt checking
        if (!empty($credentialSaltColumn) && !empty($slatedCredentialColumn) &&
            !empty($user->$credentialSaltColumn) && !empty($user->$slatedCredentialColumn)
        ) {
            //Fo salt we check if password is same like credential
            $authenticated = SaltPasswordManager::checkPasswordWithHash(
                $user->$slatedCredentialColumn,
                $user->$credentialSaltColumn,
                $this->credential
            );
        } else {

            //If don't have salt we must check if we have hashed password
            if ($this->hash) {
                $credential = SaltPasswordManager::generateSimpleHash($this->credential);
            } else {
                $credential = $this->credential;
            }

            //We check if we are authenticated
            $authenticated = $credential == $user->$credentialColumn;

            /**
             * If we are authenticated and have original password, we can create and add slated password for user and
             * we should do it. It means we are not Auto Login or something.
             */
            if ($authenticated && $this->hash) {
                if (!empty($credentialSaltColumn) && !empty($slatedCredentialColumn)) {
                    list($password, $hash) = SaltPasswordManager::generateSaltedPassword($this->credential);
                    $this->addSalt($user, $password, $hash);
                }
            }
        }

        if (empty($authenticated)) {
            return false;
        }

        return $this->authorisedId = $records->current()->__get($this->identityKey);
    }

    /**
     * Add salt password to model
     */
    private function addSalt(Record $record, $password, $salt) {
        $record->__set($this->credentialColumn, null);
        $record->__set($this->credentialSaltColumn, $salt);
        $record->__set($this->slatedCredentialColumn, $password);
        $record->save();
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
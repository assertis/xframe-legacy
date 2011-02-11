<?php
/**
 * @author Linus Norton <linusnorton@gmail.com>
 * @package util
 *
 * This class provides SFTP connectivity
 */

class SFTP {
    private $connection;
    private $sftp;
    private $host;
    private $port;
    private $username;
    private $password;

    public function __construct($username,
                                $password,
                                $host,
                                $port = 22) {
        $this->username = $username;
        $this->password = $password;
        $this->host = $host;
        $this->port = $port;
    }

    public function connect($retries = 1, $retryTimeout = 10) {
        $attepmts = 1;
        
        $this->connection = @ssh2_connect($this->host, $this->port);
        
        //if could not connect try again in 30
        while (!$this->connection && $attepmts < $retries) {
            sleep($retryTimeout);
            $this->connection = @ssh2_connect($this->host, $this->port);
            $attepmts++;
        }

        if (!$this->connection) {
            throw new FrameEx("Could not connect to {$this->host} on port {$this->port}");
        }

        if (!@ssh2_auth_password($this->connection, $this->username, $this->password)) {
            throw new FrameEx("Invalid username or password");
        }

        $this->sftp = ssh2_sftp($this->connection);
    }

    public function put($localFilename, $remoteFilename, $retries = 1) {
        $contents = @file_get_contents($localFilename);
        if ($contents === false) {
            throw new FrameEx("Could not open local file: {$localFilename}.");
        }

        $stream = fopen("ssh2.sftp://{$this->sftp}/{$remoteFilename}", 'w');
        if (!$stream) {
            throw new FrameEx("Could not open file: {$remoteFilename}");
        }

        $tries = 0;
        $done = false;

        while (!$done && $tries < $retries) {
            if (@fwrite($stream, $contents) === false) {
                $retries++;
            }
            else {
                $done = true;
            }
        }

        if (!$done) {
            throw new FrameEx("Could not send file {$remoteFilename}.");
        }
        @fclose($stream);
    }

    public function get($remoteFilename, $localFilename, $retries = 1) {
        $stream = @fopen("ssh2.sftp://{$this->sftp}{$remoteFilename}", 'r');
        if (!$stream) {
            throw new FrameEx("Could not open file: {$remoteFilename}");
        }

        $tries = 0;
        $done = false;
        
        while (!$done && $tries < $retries) {
            $contents = @fread($stream, filesize("ssh2.sftp://".$this->sftp.$remoteFilename));
            if ($contents === false) {
                $retries++;
            }
            else {
                $done = true;
            }
        }

        if (false === @file_put_contents ($localFilename, $contents)) {
            throw new FrameEx("Could not write to {$localFilename}");
        }
        @fclose($stream);
    }

}
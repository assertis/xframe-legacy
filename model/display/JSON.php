<?php

/**
 */
class JSON extends View {
    private $data = array();
    private $status = 200;

    public function __construct() {
        parent::__construct();
    }

    public function execute() {
        if (!headers_sent()) {
            header("content-type: application/json");
            header("status: " . $this->status);
        }
        return json_encode($this->data);
    }

    public function getErrorPage() {

    }

    public function setStatusCode($code) {
        $this->status = $code;
    }

    public function set($data) {
        $this->data = $data;
    }

    public function add($data, $key = null) {
        if ($key == null) {
            $this->data[] = $data;
        }
        else {
            $this->data[$key] = $data;
        }
    }

    public function addStaticInclude($path) {

    }

}

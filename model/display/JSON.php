<?php

/**
 */
class JSON extends View {
    private $data = array();

    public function __construct() {
        parent::__construct();
    }

    public function execute() {
        if (!headers_sent()) {
            header("content-type: application/json");
        }
        return json_encode($this->data);
    }

    public function getErrorPage() {

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

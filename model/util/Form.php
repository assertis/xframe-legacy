<?php
/**
 * @author Jason Paige <j@jasonpaige.co.uk>
 * @package util
 * This object encapsulates the field values for a form and the respective error messages
 */

class Form implements XML {
    private $INVALID_FIELD_CHARS = array("'",";","&");
    private $field;
    private $hasErrors;

    /**
     * Populates the fields for the form
     *
     * @param Request $request
     */
    public function __construct(Request $request = null) {
        if ($request != null) {
            foreach ($request->getMappedParameters() as $id => $parameter) {
                $this->add($id, $parameter);
            }
            $this->hasErrors = false;
        }
    }

    /**
     * Adds a field to the form with optional error message
     *
     * @param mixed $request
     * @param mixed $value
     * @param string $errorMessage
     * @param int $errorCode
     */
    public function add($id, $value, $errorMessage = null, $errorCode = null) {
        $id = str_replace($this->INVALID_FIELD_CHARS, "", $id);

        $this->field[$id] = array("value" => $value, "error" => $errorMessage, "code" => $errorCode);
        if ($errorMessage != null || $errorCode != null) {
            $this->hasErrors = true;
        }
    }

    /**
     * Returns whether this form currently contains any errors
     *
     * @return boolean
     */
    public function hasErrors() {
        return $this->hasErrors;
    }

    /**
     * Returns to a location complete with field values and errors in the session
     *
     * @param string $location
     */
    public function doSessionPostBack($location) {
        $_SESSION['field'] = array();
        $_SESSION['error'] = array();
        if (is_array($this->field)) {
            foreach ($this->field as $id => $field) {
                $_SESSION['field'][$id] = $field['value'];
                if ($field['error'] != '' || $field['code'] != '') {
                    $_SESSION['error'][$id]['code'] = $field['code'];
                    $_SESSION['error'][$id]['message'] = $field['error'];
                }
            }
        }

        header("Location: {$location}");
        die();
    }

    /**
     * @param array $defaultValues
     * @param boolean $clearErrorsAndFields
     * @return String
     */
    public function getXML($defaultValues = array(), $clearErrors = true) {
        $xml = "<form>";
        if (is_array($_SESSION["field"])) {
            foreach ($_SESSION["field"] as $fieldName => $fieldValue) {
                $xml .= $this->getFieldXML($fieldName, $fieldValue, $defaultValues[$fieldName]);
            }
        }
        if (is_array($defaultValues)) {
            foreach ($defaultValues as $fieldName => $fieldValue) {
                if (!isset($_SESSION['field'][$fieldName])) {
                    $xml .= $this->getFieldXML($fieldName, $fieldValue);
                }
            }
        }
        if (is_array($_SESSION["error"])) {
            foreach ($_SESSION["error"] as $errorId => $error) {
                $xml .= $this->getErrorXML($errorId, $error['message'], $error['code']);
            }
        }
        $xml .= "</form>";

        if ($clearErrors) {
            $this->clearErrors();
        }

        return $xml;
    }

    /**
     * @param string $fieldName
     * @param mixed $fieldValue
     * @param mixed $defaultValue
     */
    private function getFieldXML($fieldName, $fieldValue, $defaultValue = "") {
        $sanitizedName = str_replace('/', '-', $fieldName);

        $xml = "<f-{$sanitizedName} name='".htmlspecialchars($fieldName)."'>";
        if (is_array($fieldValue)) {
            foreach ($fieldValue as $key => $value) {
                $xml .= $this->getFieldXML($key, $value);
            }
        }
        else if (isset($fieldValue)) {
            $xml .= htmlspecialchars($fieldValue);
        }
        else if (is_array($defaultValue)) {
            foreach ($defaultValue as $key => $value) {
                $xml .= $this->getFieldXML($key, $value);
            }
        }
        else if ($defaultValue != "") {
            $xml .= htmlspecialchars($defaultValue);
        }
        $xml .= "</f-{$sanitizedName}>";

        return $xml;
    }

    /**
     * @param string $errorId
     * @param mixed $errorMessage
     * @param mixed $errorCode
     */
    private function getErrorXML($errorId, $errorMessage = "", $errorCode = "") {
        $xml = "<e-{$errorId} code=\"{$errorCode}\">";
        if (isset($errorMessage)) {
            $xml .= htmlspecialchars($errorMessage);
        }
        $xml .= "</e-{$errorId}>";

        return $xml;
    }

    public function clearErrorsAndFields() {
        unset($_SESSION['error']);
        unset($_SESSION['field']);
    }

    private function clearErrors() {
        unset($_SESSION['error']);
    }

    public function __toString() {
        return serialize($this);
    }
}
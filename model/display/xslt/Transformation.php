<?php

/**
 * Description of Transformation
 *
 * @author Linus Norton <linusnorton@gmail.com>
 * @package display
 */
class Transformation implements XML {
    private $xml;
    private $xsl;

    /**
     * The $xslFile is not required if you are just getting the dom document or
     * debug XML
     *
     * @param string $xml
     * @param string $xslFile
     */
    public function __construct($xml, $xslFile = null) {
        $this->xml = self::generateXMLDom($xml, $xslFile);

        if ($xslFile != null) {
            $this->xsl = self::generateXSLDom($xslFile);
        }
    }

    /**
     * Transform the XML/XSL documents into an XML (probably XHTML) document
     * @return string
     */
    public function execute($parameters = array()) {
        $xslt = new XSLTProcessor();
        $xslt->importStylesheet($this->xsl);
        $xslt->setParameter(null, $parameters);

        //unfortunately this doesn't capture any warnings generated whilst transforming
        $transformation = $xslt->transformToXml($this->xml);

        if ($transformation === false) {
            throw new MalformedPage("There was an error tranforming the page");
        }

        return $transformation;
    }

    /**
     * Return the XML
     * @return string
     */
    public function getXML() {
        return $this->xml->saveXML();
    }

    /**
     * Take the given $xml and turn it into a DomDocument and do the xincludes
     *
     * @param string $xml
     * @param string $xmlFilePath
     *
     * @return DomDocument
     */
    private function generateXMLDom($xml, $xmlFilePath = null) {
        $dom = new DomDocument();
        if (null !== $xmlFilePath) {
            $dom->documentURI = $xmlFilePath;
        }
        try {
            $dom->loadXML($xml);
        }
        catch (FrameEx $ex) {
            throw new FrameEx("Could not create DOM: ".$ex->getMessage()." at\n\n".$ex->getTraceAsString()."\n\nwith xml:\n\n".$xml);
        }

        $dom->xinclude();
        return $dom;
    }

    /**
     * Use the given filename to load an xsl document (DomDocument)
     * @param string $xslFile
     * @return DomDocument
     */
    private function generateXSLDom($xslFile) {
        $xsl = new DomDocument();

        //if the xsl has not been set or has been set incorrectly
        if (!file_exists($xslFile)) {
            throw new MalformedPage("Could not locate xsl file: ".$xslFile);
        }

        //if the xsl contained errors
        if (!$xsl->load($xslFile)) {
            throw new MalformedPage("There are errors in the xsl file: ".$xslFile);
        }

        return $xsl;
    }

}


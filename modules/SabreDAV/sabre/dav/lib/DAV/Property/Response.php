<?php

namespace Sabre\DAV\Property;

use
    Sabre\DAV,
    Sabre\HTTP,
    Sabre\HTTP\URLUtil;

/**
 * Response property
 *
 * This class represents the {DAV:}response XML element.
 * This is used by the Server class to encode individual items within a multistatus
 * response.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Response extends DAV\Property implements IHref {

    /**
     * Url for the response
     *
     * @var string
     */
    protected $href;

    /**
     * Propertylist, ordered by HTTP status code
     *
     * @var array
     */
    protected $responseProperties;

    /**
     * The HTTP status for an entire response.
     *
     * This is currently only used in WebDAV-Sync
     *
     * @var string
     */
    protected $httpStatus;

    /**
     * The href argument is a url relative to the root of the server. This
     * class will calculate the full path.
     *
     * The responseProperties argument is a list of properties
     * within an array with keys representing HTTP status codes
     *
     * Besides specific properties, the entire {DAV:}response element may also
     * have a http status code.
     * In most cases you don't need it.
     *
     * This is currently used by the Sync extension to indicate that a node is
     * deleted.
     *
     * @param string $href
     * @param array $responseProperties
     * @param string $httpStatus
     */
    function __construct($href, array $responseProperties, $httpStatus = null) {

        $this->href = $href;
        $this->responseProperties = $responseProperties;
        $this->httpStatus = $httpStatus;

    }

    /**
     * Returns the url
     *
     * @return string
     */
    function getHref() {

        return $this->href;

    }

    /**
     * Returns the httpStatus value
     *
     * @return string
     */
    function getHttpStatus() {

        return $this->httpStatus;

    }

    /**
     * Returns the property list
     *
     * @return array
     */
    function getResponseProperties() {

        return $this->responseProperties;

    }

    /**
     * serialize
     *
     * @param DAV\Server $server
     * @param \DOMElement $dom
     * @return void
     */
    function serialize(DAV\Server $server, \DOMElement $dom) {

        $document = $dom->ownerDocument;
        $properties = $this->responseProperties;

        $xresponse = $document->createElement('d:response');
        $dom->appendChild($xresponse);

        $uri = URLUtil::encodePath($this->href);

        if ($uri==='/') $uri = '';

        // Adding the baseurl to the beginning of the url
        $uri = $server->getBaseUri() . $uri;

        $xresponse->appendChild($document->createElement('d:href',$uri));

        if ($this->httpStatus) {
            $statusString = "HTTP/1.1 " . $this->httpStatus . " " . HTTP\Response::$statusCodes[$this->httpStatus];
            $xresponse->appendChild($document->createElement('d:status', $statusString));
        }

        // The properties variable is an array containing properties, grouped by
        // HTTP status
        foreach($properties as $httpStatus=>$propertyGroup) {

            // The 'href' is also in this array, and it's special cased.
            // We will ignore it
            if ($httpStatus=='href') continue;

            // If there are no properties in this group, we can also just carry on
            if (!count($propertyGroup)) continue;

            $xpropstat = $document->createElement('d:propstat');
            $xresponse->appendChild($xpropstat);

            $xprop = $document->createElement('d:prop');
            $xpropstat->appendChild($xprop);

            $nsList = $server->xmlNamespaces;

            foreach($propertyGroup as $propertyName=>$propertyValue) {

                $propName = null;
                preg_match('/^{([^}]*)}(.*)$/',$propertyName,$propName);

                // special case for empty namespaces
                if ($propName[1]=='') {

                    $currentProperty = $document->createElement($propName[2]);
                    $xprop->appendChild($currentProperty);
                    $currentProperty->setAttribute('xmlns','');

                } else {

                    if (!isset($nsList[$propName[1]])) {
                        $nsList[$propName[1]] = 'x' . count($nsList);
                    }

                    // If the namespace was defined in the top-level xml namespaces, it means
                    // there was already a namespace declaration, and we don't have to worry about it.
                    if (isset($server->xmlNamespaces[$propName[1]])) {
                        $currentProperty = $document->createElement($nsList[$propName[1]] . ':' . $propName[2]);
                    } else {
                        $currentProperty = $document->createElementNS($propName[1],$nsList[$propName[1]].':' . $propName[2]);
                    }
                    $xprop->appendChild($currentProperty);

                }

                if (is_scalar($propertyValue)) {
                    if ($propertyValue!=='') { // we want a self-closing xml element for empty strings.
                        $text = $document->createTextNode($propertyValue);
                        $currentProperty->appendChild($text);
                    }
                } elseif ($propertyValue instanceof DAV\PropertyInterface) {
                    $propertyValue->serialize($server,$currentProperty);
                } elseif (!is_null($propertyValue)) {
                    throw new DAV\Exception('Unknown property value type: ' . gettype($propertyValue) . ' for property: ' . $propertyName);
                }

            }

            $xpropstat->appendChild($document->createElement('d:status','HTTP/1.1 ' . $httpStatus . ' ' . HTTP\Response::$statusCodes[$httpStatus]));

        }

    }

    /**
     * Unserializes the property.
     *
     * This static method should return a an instance of this object.
     *
     * @param \DOMElement $prop
     * @param array $propertyMap
     * @return DAV\IProperty
     */
    static function unserialize(\DOMElement $prop, array $propertyMap) {

        // Delegating this to the ResponseList property. It does make more
        // sense there.

        $result = ResponseList::unserialize($prop, $propertyMap);
        $result = $result->getResponses();

        return $result[0];

    }

}

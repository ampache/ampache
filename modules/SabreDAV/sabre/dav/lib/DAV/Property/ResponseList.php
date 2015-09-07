<?php

namespace Sabre\DAV\Property;

use Sabre\DAV;

/**
 * ResponseList property
 *
 * This class represents multiple {DAV:}response XML elements.
 * This is used by the Server class to encode items within a multistatus
 * response.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class ResponseList extends DAV\Property {

    /**
     * Response objects.
     *
     * @var array
     */
    private $responses;

    /**
     * The only valid argument is a list of Sabre\DAV\Property\Response
     * objects.
     *
     * @param array $responses;
     */
    function __construct($responses) {

        foreach($responses as $response) {
            if (!($response instanceof Response)) {
                throw new \InvalidArgumentException('You must pass an array of Sabre\DAV\Property\Response objects');
            }
        }
        $this->responses = $responses;

    }

    /**
     * Returns the list of Response properties.
     *
     * @return Response[]
     */
    function getResponses() {

        return $this->responses;

    }

    /**
     * serialize
     *
     * @param DAV\Server $server
     * @param \DOMElement $dom
     * @return void
     */
    function serialize(DAV\Server $server,\DOMElement $dom) {

        foreach($this->responses as $response) {
            $response->serialize($server, $dom);
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

        $xpath = new \DOMXPath( $prop->ownerDocument );
        $xpath->registerNamespace('d','urn:DAV');

        // Finding the 'response' element
        $xResponses = $xpath->evaluate(
            'd:response',
            $prop
        );

        $result = [];

        for($jj=0; $jj < $xResponses->length; $jj++) {

            $xResponse = $xResponses->item($jj);

            // Parsing 'href'
            $href = Href::unserialize($xResponse, $propertyMap);

            $properties = [];

            // Parsing 'status' in 'd:response'
            $responseStatus = $xpath->evaluate('string(d:status)', $xResponse);
            if ($responseStatus) {
                list(, $responseStatus,) = explode(' ', $responseStatus, 3);
            }


            // Parsing 'propstat'
            $xPropstat = $xpath->query('d:propstat', $xResponse);

            for($ii=0; $ii < $xPropstat->length; $ii++) {

                // Parsing 'status'
                $status = $xpath->evaluate('string(d:status)', $xPropstat->item($ii));

                list(,$statusCode,) = explode(' ', $status, 3);

                $usedPropertyMap = $statusCode == '200' ? $propertyMap : [];

                // Parsing 'prop'
                $properties[$statusCode] = DAV\XMLUtil::parseProperties($xPropstat->item($ii), $usedPropertyMap);

            }

            $result[] = new Response($href->getHref(), $properties, $responseStatus?$responseStatus:null);

        }

        return new self($result);

    }


}

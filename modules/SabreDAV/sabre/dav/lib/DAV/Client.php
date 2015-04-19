<?php

namespace Sabre\DAV;

use Sabre\HTTP;

/**
 * SabreDAV DAV client
 *
 * This client wraps around Curl to provide a convenient API to a WebDAV
 * server.
 *
 * NOTE: This class is experimental, it's api will likely change in the future.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Client extends HTTP\Client {

    /**
     * The propertyMap is a key-value array.
     *
     * If you use the propertyMap, any {DAV:}multistatus responses with the
     * properties listed in this array, will automatically be mapped to a
     * respective class.
     *
     * The {DAV:}resourcetype property is automatically added. This maps to
     * Sabre\DAV\Property\ResourceType
     *
     * @var array
     */
    public $propertyMap = [];

    protected $baseUri;

    /**
     * Basic authentication
     */
    const AUTH_BASIC = 1;

    /**
     * Digest authentication
     */
    const AUTH_DIGEST = 2;

    /**
     * Identity encoding, which basically does not nothing.
     */
    const ENCODING_IDENTITY = 1;

    /**
     * Deflate encoding
     */
    const ENCODING_DEFLATE = 2;

    /**
     * Gzip encoding
     */
    const ENCODING_GZIP = 4;

    /**
     * Sends all encoding headers.
     */
    const ENCODING_ALL = 7;

    /**
     * Content-encoding
     *
     * @var int
     */
    protected $encoding = self::ENCODING_IDENTITY;

    /**
     * Constructor
     *
     * Settings are provided through the 'settings' argument. The following
     * settings are supported:
     *
     *   * baseUri
     *   * userName (optional)
     *   * password (optional)
     *   * proxy (optional)
     *   * authType (optional)
     *   * encoding (optional)
     *
     *  authType must be a bitmap, using self::AUTH_BASIC and
     *  self::AUTH_DIGEST. If you know which authentication method will be
     *  used, it's recommended to set it, as it will save a great deal of
     *  requests to 'discover' this information.
     *
     *  Encoding is a bitmap with one of the ENCODING constants.
     *
     * @param array $settings
     */
    function __construct(array $settings) {

        if (!isset($settings['baseUri'])) {
            throw new \InvalidArgumentException('A baseUri must be provided');
        }

        parent::__construct();

        $this->baseUri = $settings['baseUri'];

        if (isset($settings['proxy'])) {
            $this->addCurlSetting(CURLOPT_PROXY, $settings['proxy']);
        }

        if (isset($settings['userName'])) {
            $userName = $settings['userName'];
            $password = isset($settings['password'])?$settings['password']:'';

            if (isset($settings['authType'])) {
                $curlType = 0;
                if ($settings['authType'] & self::AUTH_BASIC) {
                    $curlType |= CURLAUTH_BASIC;
                }
                if ($settings['authType'] & self::AUTH_DIGEST) {
                    $curlType |= CURLAUTH_DIGEST;
                }
            } else {
                $curlType = CURLAUTH_BASIC | CURLAUTH_DIGEST;
            }

            $this->addCurlSetting(CURLOPT_HTTPAUTH, $curlType);
            $this->addCurlSetting(CURLOPT_USERPWD, $userName . ':' . $password);

        }

        if (isset($settings['encoding'])) {
            $encoding = $settings['encoding'];

            $encodings = [];
            if ($encoding & self::ENCODING_IDENTITY) {
                $encodings[] = 'identity';
            }
            if ($encoding & self::ENCODING_DEFLATE) {
                $encodings[] = 'deflate';
            }
            if ($encoding & self::ENCODING_GZIP) {
                $encodings[] = 'gzip';
            }
            $this->addCurlSetting(CURLOPT_ENCODING, implode(',', $encodings));
        }

        $this->propertyMap['{DAV:}resourcetype'] = 'Sabre\\DAV\\Property\\ResourceType';

    }


    /**
     * Add trusted root certificates to the webdav client.
     *
     * The parameter certificates should be a absolute path to a file
     * which contains all trusted certificates
     *
     * @deprecated This method will be removed in the future, use
     *             addCurlSetting instead.
     * @param string $certificates
     * @return void
     */
    function addTrustedCertificates($certificates) {
        $this->addCurlSetting(CURLOPT_CAINFO, $certificates);
    }

    /**
     * Enables/disables SSL peer verification
     *
     * @deprecated This method will be removed in the future, use
     *             addCurlSetting instead.
     * @param bool $value
     * @return void
     */
    function setVerifyPeer($value) {
        $this->addCurlSetting(CURLOPT_SSL_VERIFYPEER, $value);
    }

    /**
     * Does a PROPFIND request
     *
     * The list of requested properties must be specified as an array, in clark
     * notation.
     *
     * The returned array will contain a list of filenames as keys, and
     * properties as values.
     *
     * The properties array will contain the list of properties. Only properties
     * that are actually returned from the server (without error) will be
     * returned, anything else is discarded.
     *
     * Depth should be either 0 or 1. A depth of 1 will cause a request to be
     * made to the server to also return all child resources.
     *
     * @param string $url
     * @param array $properties
     * @param int $depth
     * @return array
     */
    function propFind($url, array $properties, $depth = 0) {

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $root = $dom->createElementNS('DAV:', 'd:propfind');
        $prop = $dom->createElement('d:prop');

        foreach($properties as $property) {

            list(
                $namespace,
                $elementName
            ) = XMLUtil::parseClarkNotation($property);

            if ($namespace === 'DAV:') {
                $element = $dom->createElement('d:'.$elementName);
            } else {
                $element = $dom->createElementNS($namespace, 'x:'.$elementName);
            }

            $prop->appendChild( $element );
        }

        $dom->appendChild($root)->appendChild( $prop );
        $body = $dom->saveXML();

        $url = $this->getAbsoluteUrl($url);

        $request = new HTTP\Request('PROPFIND', $url, [
            'Depth' => $depth,
            'Content-Type' => 'application/xml'
        ], $body);

        $response = $this->send($request);

        if ((int)$response->getStatus() >= 400) {
            throw new Exception('HTTP error: ' . $response->getStatus());
        }

        $result = $this->parseMultiStatus($response->getBodyAsString());

        // If depth was 0, we only return the top item
        if ($depth===0) {
            reset($result);
            $result = current($result);
            return isset($result[200])?$result[200]:[];
        }

        $newResult = [];
        foreach($result as $href => $statusList) {

            $newResult[$href] = isset($statusList[200])?$statusList[200]:[];

        }

        return $newResult;

    }

    /**
     * Updates a list of properties on the server
     *
     * The list of properties must have clark-notation properties for the keys,
     * and the actual (string) value for the value. If the value is null, an
     * attempt is made to delete the property.
     *
     * @param string $url
     * @param array $properties
     * @return void
     */
    function propPatch($url, array $properties) {

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $root = $dom->createElementNS('DAV:', 'd:propertyupdate');

        foreach($properties as $propName => $propValue) {

            list(
                $namespace,
                $elementName
            ) = XMLUtil::parseClarkNotation($propName);

            if ($propValue === null) {

                $remove = $dom->createElement('d:remove');
                $prop = $dom->createElement('d:prop');

                if ($namespace === 'DAV:') {
                    $element = $dom->createElement('d:'.$elementName);
                } else {
                    $element = $dom->createElementNS($namespace, 'x:'.$elementName);
                }

                $root->appendChild( $remove )->appendChild( $prop )->appendChild( $element );

            } else {

                $set = $dom->createElement('d:set');
                $prop = $dom->createElement('d:prop');

                if ($namespace === 'DAV:') {
                    $element = $dom->createElement('d:'.$elementName);
                } else {
                    $element = $dom->createElementNS($namespace, 'x:'.$elementName);
                }

                if ( $propValue instanceof Property ) {
                    $propValue->serialize( new Server, $element );
                } else {
                    $element->nodeValue = htmlspecialchars($propValue, ENT_NOQUOTES, 'UTF-8');
                }

                $root->appendChild( $set )->appendChild( $prop )->appendChild( $element );

            }

        }

        $dom->appendChild($root);
        $body = $dom->saveXML();

        $url = $this->getAbsoluteUrl($url);
        $request = new HTTP\Request('PROPPATCH', $url, [
            'Content-Type' => 'application/xml',
        ], $body);
        $this->send($request);
    }

    /**
     * Performs an HTTP options request
     *
     * This method returns all the features from the 'DAV:' header as an array.
     * If there was no DAV header, or no contents this method will return an
     * empty array.
     *
     * @return array
     */
    function options() {

        $request = new HTTP\Request('OPTIONS', $this->getAbsoluteUrl(''));
        $response = $this->send($request);

        $dav = $response->getHeader('Dav');
        if (!$dav) {
            return [];
        }

        $features = explode(',', $dav);
        foreach($features as &$v) {
            $v = trim($v);
        }
        return $features;

    }

    /**
     * Performs an actual HTTP request, and returns the result.
     *
     * If the specified url is relative, it will be expanded based on the base
     * url.
     *
     * The returned array contains 3 keys:
     *   * body - the response body
     *   * httpCode - a HTTP code (200, 404, etc)
     *   * headers - a list of response http headers. The header names have
     *     been lowercased.
     *
     * For large uploads, it's highly recommended to specify body as a stream
     * resource. You can easily do this by simply passing the result of
     * fopen(..., 'r').
     *
     * This method will throw an exception if an HTTP error was received. Any
     * HTTP status code above 399 is considered an error.
     *
     * Note that it is no longer recommended to use this method, use the send()
     * method instead.
     *
     * @param string $method
     * @param string $url
     * @param string|resource|null $body
     * @param array $headers
     * @throws ClientException, in case a curl error occurred.
     * @return array
     */
    function request($method, $url = '', $body = null, array $headers = []) {

        $url = $this->getAbsoluteUrl($url);

        $response = $this->send(new HTTP\Request($method, $url, $headers, $body));
        return [
            'body' => $response->getBodyAsString(),
            'statusCode' => (int)$response->getStatus(),
            'headers' => array_change_key_case($response->getHeaders()),
        ];

    }

    /**
     * Returns the full url based on the given url (which may be relative). All
     * urls are expanded based on the base url as given by the server.
     *
     * @param string $url
     * @return string
     */
    function getAbsoluteUrl($url) {

        // If the url starts with http:// or https://, the url is already absolute.
        if (preg_match('/^http(s?):\/\//', $url)) {
            return $url;
        }

        // If the url starts with a slash, we must calculate the url based off
        // the root of the base url.
        if (strpos($url,'/') === 0) {
            $parts = parse_url($this->baseUri);
            return $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port'])?':' . $parts['port']:'') . $url;
        }

        // Otherwise...
        return $this->baseUri . $url;

    }

    /**
     * Parses a WebDAV multistatus response body
     *
     * This method returns an array with the following structure
     *
     * [
     *   'url/to/resource' => [
     *     '200' => [
     *        '{DAV:}property1' => 'value1',
     *        '{DAV:}property2' => 'value2',
     *     ],
     *     '404' => [
     *        '{DAV:}property1' => null,
     *        '{DAV:}property2' => null,
     *     ],
     *   ],
     *   'url/to/resource2' => [
     *      .. etc ..
     *   ]
     * ]
     *
     *
     * @param string $body xml body
     * @return array
     */
    function parseMultiStatus($body) {

        try {
            $dom = XMLUtil::loadDOMDocument($body);
        } catch (Exception\BadRequest $e) {
            throw new \InvalidArgumentException('The body passed to parseMultiStatus could not be parsed. Is it really xml?');
        }

        $responses = Property\ResponseList::unserialize(
            $dom->documentElement,
            $this->propertyMap
        );

        $result = [];

        foreach($responses->getResponses() as $response) {

            $result[$response->getHref()] = $response->getResponseProperties();

        }

        return $result;

    }

}

<?php
/**
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * AmazonSearch Class
 *
 * This class accepts 3 tokens: a public_key (Amazon API ID),
 * a private_key (Amazon API password used for signing requests),
 * and an associate_tag (Amazon Associate ID tag) and creates a signed
 * query to request information (images) from the Amazon Web Service API.
 * Currently it is semi-hardcoded to do music searches and only return
 * information about the album art.
 *
 * This class has been updated to conform to changes made to the AWS on 02/21/2012.
 * https://affiliate-program.amazon.com/gp/advertising/api/detail/api-changes.html
 */
class AmazonSearch
{

    var $base_url_default = 'webservices.amazon.com';
    var $url_suffix = '/onca/xml';
    var $base_url;
    var $search;
    var $public_key;    // AWSAccessKeyId
    var $private_key;   // AWSSecretKey
    var $associate_tag; // Amazon Affiliate Associate Tag
    var $results = array();  // Array of results
    var $_parser;   // The XML parser
    var $_grabtags; // Tags to grab the contents of
    var $_sourceTag; // source tag don't ask
    var $_subTag; // Stupid hack to make things come our right
    var $_currentTag; // Stupid hack to make things come out right
    var $_currentTagContents;
    var $_currentPage = 0;
    var $_maxPage = 1;
    var $_default_results_pages = 1;
    var $_proxy_host = ""; // Proxy host
    var $_proxy_port = ""; // Proxy port
    var $_proxy_user = ""; // Proxy user
    var $_proxy_pass = ""; // Proxy pass

    /**
     * Class Constructor
     */
    function __construct($public_key, $private_key, $associate_tag, $base_url_param = '')
    {

        /* If we have a base url then use it */
        if ($base_url_param != '') {
            $this->base_url = str_replace('http://', '', $base_url_param);
            debug_event('amazon-search-results', 'Retrieving from ' . $base_url_param . $this->url_suffix, '5');
        } else {
            $this->base_url = $this->base_url_default;
            debug_event('amazon-search-results', 'Retrieving from DEFAULT', '5');
        }

        // AWS credentials
        $this->public_key = $public_key;
        $this->private_key = $private_key;
        $this->associate_tag = $associate_tag;

        $this->_grabtags = array(
            'ASIN',
            'ProductName',
            'Catalog',
            'ErrorMsg',
            'Description',
            'ReleaseDate',
            'Manufacturer',
            'ImageUrlSmall',
            'ImageUrlMedium',
            'ImageUrlLarge',
            'Author',
            'Artist',
            'Title',
            'URL',
            'SmallImage',
            'MediumImage',
            'LargeImage'
        );

    } // AmazonSearch

    /**
     * setProxy
     * Set the class up to search through an http proxy.
     * The parameters are the proxy's hostname or IP address (a string)
     * port, username, and password. These are passed directly to the
     * Requests class when the search is done.
     */
    function setProxy($host = '', $port = '', $user = '', $pass = '')
    {
        if ($host)
            $this->_proxy_host = $host;
        if ($port)
            $this->_proxy_port = $port;
        if ($user)
            $this->_proxy_user = $user;
        if ($pass)
            $this->_proxy_pass = $pass;
    } // setProxy

    /**
     * Create the XML parser to process the response.
     */
    function createParser()
    {
        $this->_parser = xml_parser_create();

        xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, false);

        xml_set_object($this->_parser, $this);

        xml_set_element_handler($this->_parser, 'startElement', 'endElement');

        xml_set_character_data_handler($this->_parser, 'cdata');
    } // createParser

    /**
     * Run a search.
     *
     * @param string $url The URL of the Amazon webservice.
     */
    function runSearch($url)
    {

        // create the parser
        $this->createParser();

        // get the proxy config 
        $options = $this->getProxyConfig();

        debug_event('amazon-search-results', 'Amazon request: ' . $url, 5);
        // make the request and retrieve the response
        $request = Requests::get($url, array(), $options);
        $contents = $request->body;

        //debug_event('AMAZON XML', $contents, 5);
        if (!xml_parse($this->_parser, $contents)) {
            debug_event('amazon-search-results', 'Error:' . sprintf('XML error: %s at line %d', xml_error_string(xml_get_error_code($this->_parser)), xml_get_current_line_number($this->_parser)), '1');
        }

        xml_parser_free($this->_parser);
    } // runSearch

    /**
     * Build the proxy options array.
     *
     * @return array() $options The array of proxy config options.
     */
    function getProxyConfig(){
        
        $options = array();
        if ($this->_proxy_host) {
            $proxy = array();
            $proxy[] = $this->_proxy_host . ( $this->_proxy_port ? ':' . $this->_proxy_port : '');
            if ($this->_proxy_user) {
                $proxy[] = $this->_proxy_user;
                $proxy[] = $this->_proxy_pass;
            }
            $options['proxy'] = $proxy;
        }
        return $options;
    } // getProxyConfig

    /**
     * Create the search string.
     *
     * @param array() $terms The serach terms to include within the query.
     * @param string $type The type of result desired.
     * @return string $results The XML return string.
     */
    function search($terms, $type = 'Music')
    {
        $params = array();

        $params['Service'] = 'AWSECommerceService';
        $params['AWSAccessKeyId'] = $this->public_key;
        $params['AssociateTag'] = $this->associate_tag;
        $params['Timestamp'] = gmdate("Y-m-d\TH:i:s\Z");
        $params['Version'] = '2009-03-31';
        $params['Operation'] = 'ItemSearch';
        $params['Artist'] = $terms['artist'];
        $params['Title'] = $terms['album'];
        $params['Keywords'] = $terms['keywords'];
        $params['SearchIndex'] = $type;

        // sort by keys
        ksort($params);

        $canonicalized_query = array();

        foreach ($params as $param => $value) {
            $param = str_replace("%7E", "~", rawurlencode($param));
            $value = str_replace("%7E", "~", rawurlencode($value));

            $canonicalized_query[] = $param . "=" . $value;
        }

        // build the query string
        $canonicalized_query = implode('&', $canonicalized_query);
        $string_to_sign = 'GET' . "\n" . $this->base_url . "\n" . $this->url_suffix . "\n" . $canonicalized_query;

        $url = 'http://' . $this->base_url . $this->url_suffix . '?' . $canonicalized_query . '&Signature=' . $this->signString($string_to_sign);

        $this->runSearch($url);

        unset($this->results['ASIN']);

        return $this->results;
    } // search

    /**
     * Sign a query string
     *
     * @param string $string_to_sign The string to sign
     * @return string $signature The signed query.
     */
    function signString($string_to_sign){
        
        // hash and encode the query string
        $signature = base64_encode(hash_hmac("sha256", $string_to_sign, $this->private_key, true));

        // urlencode the signed string, replace illegal char
        $signature = str_replace("%7E", "~", rawurlencode($signature));

        return $signature;
    } // signString

    /**
     * Lookup the selected item by the 'Amazon Standard Identification Number'
     *
     * @param string $asin The 'Amazon standard Identification Number'
     * @param string $type The category of results desired from the web service.
     */
    function lookup($asin, $type = 'Music')
    {

        if (is_array($asin)) {

            foreach ($asin as $key => $value) {
                
                $this->runSearchAsin($key);
            }
        } // if array of asin's
        else {

            $this->runSearchAsin($url);
        } // else

        unset($this->results['ASIN']);

        return $this->results;
    } // lookup

    /**
     * Query the AWS for information about the selected item by ASIN and parse the results.
     *
     * @param string $asin The 'Amazon standard Identification Number'
     */
    function runSearchAsin($asin)
    {
        
        // get the proxy config 
        $options = $this->getProxyConfig();

        // create the xml parser
        $this->createParser();

        $options = array();

        $params = array();

        $params['Service'] = 'AWSECommerceService';
        $params['AWSAccessKeyId'] = $this->public_key;
        $params['AssociateTag'] = $this->associate_tag;
        $params['Timestamp'] = gmdate("Y-m-d\TH:i:s\Z");
        $params['Version'] = '2009-03-31';
        $params['Operation'] = 'ItemLookup';
        $params['ItemId'] = $asin;
        $params['ResponseGroup'] = 'Images';

        ksort($params);

        // assemble the query terms
        $canonicalized_query = array();
        foreach ($params as $param => $value) {
            $param = str_replace("%7E", "~", rawurlencode($param));
            $value = str_replace("%7E", "~", rawurlencode($value));

            $canonicalized_query[] = $param . "=" . $value;
        }

        // build the url query string
        $canonicalized_query = implode('&', $canonicalized_query);
        $string_to_sign = 'GET' . "\n" . $this->base_url . "\n" . $this->url_suffix . "\n" . $canonicalized_query;

        $url = 'http://' . $this->base_url . $this->url_suffix . '?' . $canonicalized_query . '&Signature=' . $this->signString($string_to_sign);

        // make the request
        $request = Requests::get($url, array(), $options);
        $contents = $request->body;

        if (!xml_parse($this->_parser, $contents)) {
            debug_event('amazon-search-results', 'Error:' . sprintf('XML error: %s at line %d', xml_error_string(xml_get_error_code($this->_parser)), xml_get_current_line_number($this->_parser)), '1');
        }

        xml_parser_free($this->_parser);
    } // runSearchAsin

    /**
     * Start XML Element.
     */
    function startElement($parser, $tag, $attributes)
    {

        if ($tag == "ASIN") {
            $this->_sourceTag = $tag;
        }
        if ($tag == "SmallImage" || $tag == "MediumImage" || $tag == "LargeImage") {
            $this->_subTag = $tag;
        }

        // If it's in the tag list, don't grab our search results
        if (strlen($this->_sourceTag)) {
            $this->_currentTag = $tag;
        } else {
            if ($tag != "TotalPages") {
                $this->_currentTag = '';
            } else {
                $this->_currentTag = $tag;
            }
        }
    } // startElement

    /**
     * CDATA handler.
     */
    function cdata($parser, $cdata)
    {

        $tag = $this->_currentTag;
        $subtag = $this->_subTag;
        $source = $this->_sourceTag;

        switch ($tag) {
            case 'URL':
                $this->results[$source][$subtag] = trim($cdata);
                break;
            case 'ASIN':
                $this->_sourceTag = trim($cdata);
                break;
            case 'TotalPages':
                debug_event('amazon-search-results', "TotalPages= " . trim($cdata), '5');
                $this->_maxPage = trim($cdata);
                break;
            default:
                if (strlen($tag)) {
                    $this->results[$source][$tag] = trim($cdata);
                }
                break;
        } // end switch
    } // cdata

    /**
     * End XML Element
     */
    function endElement($parser, $tag)
    {

        // zero the tag
        $this->_currentTag = '';
    } // endElement

} // end AmazonSearch
?>

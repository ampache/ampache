<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Ampache\Module\Util;

use WpOrg\Requests\Requests;

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
    public $base_url_default = 'webservices.amazon.com';
    public $url_suffix       = '/onca/xml';
    public $base_url;
    public $search;
    public $public_key; // AWSAccessKeyId
    public $private_key; // AWSSecretKey
    public $associate_tag; // Amazon Affiliate Associate Tag
    public $results = []; // Array of results
    public $_parser; // The XML parser
    public $_grabtags; // Tags to grab the contents of
    public $_sourceTag; // source tag don't ask
    public $_subTag; // Stupid hack to make things come our right
    public $_currentTag; // Stupid hack to make things come out right
    public $_currentTagContents;
    public $_currentPage           = 0;
    public $_maxPage               = 1;
    public $_default_results_pages = 1;
    public $_proxy_host            = ""; // Proxy host
    public $_proxy_port            = ""; // Proxy port
    public $_proxy_user            = ""; // Proxy user
    public $_proxy_pass            = ""; // Proxy pass

    /**
     * Class Constructor
     * @param $public_key
     * @param $private_key
     * @param $associate_tag
     * @param string $base_url_param
     */
    public function __construct(
        $public_key,
        $private_key,
        $associate_tag,
        $base_url_param = ''
    ) {
        // If we have a base url then use it
        if ($base_url_param != '') {
            $this->base_url = str_replace('http://', '', $base_url_param);
            debug_event(self::class, 'Retrieving from ' . $base_url_param . $this->url_suffix, 5);
        } else {
            $this->base_url = $this->base_url_default;
            debug_event(self::class, 'Retrieving from DEFAULT', 5);
        }

        // AWS credentials
        $this->public_key    = $public_key;
        $this->private_key   = $private_key;
        $this->associate_tag = $associate_tag;

        $this->_grabtags = [
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
            'LargeImage',
        ];
    }

    /**
     * setProxy
     * Set the class up to search through an http proxy.
     * The parameters are the proxy's hostname or IP address (a string)
     * port, username, and password. These are passed directly to the
     * Requests class when the search is done.
     * @param string $host
     * @param string $port
     * @param string $user
     * @param string $pass
     */
    public function setProxy($host = '', $port = '', $user = '', $pass = ''): void
    {
        if ($host) {
            $this->_proxy_host = $host;
        }
        if ($port) {
            $this->_proxy_port = $port;
        }
        if ($user) {
            $this->_proxy_user = $user;
        }
        if ($pass) {
            $this->_proxy_pass = $pass;
        }
    }

    /**
     * Create the XML parser to process the response.
     */
    public function createParser(): void
    {
        $this->_parser = xml_parser_create();

        xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, 0);

        xml_set_object($this->_parser, $this);

        xml_set_element_handler($this->_parser, 'startElement', 'endElement');

        xml_set_character_data_handler($this->_parser, 'cdata');
    }

    /**
     * Run a search.
     *
     * @param string $url The URL of the Amazon webservice.
     */
    public function runSearch($url): void
    {
        // create the parser
        $this->createParser();

        // get the proxy config
        $options = $this->getProxyConfig();

        debug_event(self::class, 'Amazon request: ' . $url, 5);
        // make the request and retrieve the response
        $request  = Requests::get($url, [], $options);
        $contents = $request->body;

        //debug_event(self::class, $contents, 5);
        if (!xml_parse($this->_parser, $contents)) {
            debug_event(self::class, 'Error:' . sprintf('XML error: %s at line %d', xml_error_string(xml_get_error_code($this->_parser)), xml_get_current_line_number($this->_parser)), 1);
        }

        xml_parser_free($this->_parser);
    }

    /**
     * getProxyConfig
     * Build the proxy options array.
     * Returning the array of proxy config options.
     * @return array
     */
    public function getProxyConfig(): array
    {
        $options = [];
        if ($this->_proxy_host) {
            $proxy   = [];
            $proxy[] = $this->_proxy_host . (($this->_proxy_port) ? ':' . $this->_proxy_port : '');
            if ($this->_proxy_user) {
                $proxy[] = $this->_proxy_user;
                $proxy[] = $this->_proxy_pass;
            }
            $options['proxy'] = $proxy;
        }

        return $options;
    }

    /**
     * Create an XML search string.
     *
     * @param array $terms The search terms to include within the query.
     * @param string $type The type of result desired.
     * @return array
     */
    public function search($terms, $type = 'Music'): array
    {
        $params = [];

        $params['Service']        = 'AWSECommerceService';
        $params['AWSAccessKeyId'] = $this->public_key;
        $params['AssociateTag']   = $this->associate_tag;
        $params['Timestamp']      = gmdate("Y-m-d\TH:i:s\Z");
        $params['Version']        = '2009-03-31';
        $params['Operation']      = 'ItemSearch';
        $params['Artist']         = $terms['artist'];
        $params['Title']          = $terms['album'];
        $params['Keywords']       = $terms['keywords'];
        $params['SearchIndex']    = $type;

        // sort by keys
        ksort($params);

        $canonicalized_query = [];

        foreach ($params as $param => $value) {
            $param = str_replace("%7E", "~", rawurlencode($param));
            $value = str_replace("%7E", "~", rawurlencode($value));

            $canonicalized_query[] = $param . "=" . $value;
        }

        // build the query string
        $canonicalized_query = implode('&', $canonicalized_query);
        $string_to_sign      = 'GET' . "\n" . $this->base_url . "\n" . $this->url_suffix . "\n" . $canonicalized_query;

        $url = 'http://' . $this->base_url . $this->url_suffix . '?' . $canonicalized_query . '&Signature=' . $this->signString($string_to_sign);

        $this->runSearch($url);

        unset($this->results['ASIN']);

        return $this->results;
    }

    /**
     * signString
     * Sign a query string
     */
    public function signString(string $string_to_sign): string
    {
        // hash and encode the query string
        $signature = base64_encode(hash_hmac("sha256", $string_to_sign, $this->private_key, true));

        // urlencode the signed string, replace illegal char
        return str_replace("%7E", "~", rawurlencode($signature));
    }

    /**
     * Lookup the selected item by the 'Amazon Standard Identification Number'
     *
     * @param string $asin The 'Amazon standard Identification Number'
     * @param string $type The category of results desired from the web service.
     * @return array
     */
    public function lookup($asin, $type = 'Music'): array
    {
        if (is_array($asin)) {
            foreach ($asin as $key => $value) {
                $this->runSearchAsin($key);
            }
        } else {
            // if array of asin's
            $this->runSearchAsin($asin);
        } // else

        unset($this->results['ASIN']);

        return $this->results;
    }

    /**
     * Query the AWS for information about the selected item by ASIN and parse the results.
     *
     * @param string $asin The 'Amazon standard Identification Number'
     */
    public function runSearchAsin($asin): void
    {
        // get the proxy config
        $options = $this->getProxyConfig();

        // create the xml parser
        $this->createParser();

        $params                   = [];
        $params['Service']        = 'AWSECommerceService';
        $params['AWSAccessKeyId'] = $this->public_key;
        $params['AssociateTag']   = $this->associate_tag;
        $params['Timestamp']      = gmdate("Y-m-d\TH:i:s\Z");
        $params['Version']        = '2009-03-31';
        $params['Operation']      = 'ItemLookup';
        $params['ItemId']         = $asin;
        $params['ResponseGroup']  = 'Images';

        ksort($params);

        // assemble the query terms
        $canonicalized_query = [];
        foreach ($params as $param => $value) {
            $param = str_replace("%7E", "~", rawurlencode($param));
            $value = str_replace("%7E", "~", rawurlencode($value));

            $canonicalized_query[] = $param . "=" . $value;
        }

        // build the url query string
        $canonicalized_query = implode('&', $canonicalized_query);
        $string_to_sign      = 'GET' . "\n" . $this->base_url . "\n" . $this->url_suffix . "\n" . $canonicalized_query;

        $url = 'http://' . $this->base_url . $this->url_suffix . '?' . $canonicalized_query . '&Signature=' . $this->signString($string_to_sign);

        // make the request
        $request  = Requests::get($url, [], $options);
        $contents = $request->body;

        if (!xml_parse($this->_parser, $contents)) {
            debug_event(self::class, 'Error:' . sprintf('XML error: %s at line %d', xml_error_string(xml_get_error_code($this->_parser)), xml_get_current_line_number($this->_parser)), 1);
        }

        xml_parser_free($this->_parser);
    }

    /**
     * Start XML Element.
     * @param $parser
     * @param $tag
     * @param $attributes
     */
    public function startElement($parser, $tag, $attributes): void
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
        } elseif ($tag != "TotalPages") {
            $this->_currentTag = '';
        } else {
            $this->_currentTag = $tag;
        }
    }

    /**
     * CDATA handler.
     * @param $parser
     * @param $cdata
     */
    public function cdata($parser, $cdata): void
    {
        $tag    = $this->_currentTag;
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
                debug_event(self::class, "TotalPages= " . trim($cdata), 5);
                $this->_maxPage = trim($cdata);
                break;
            default:
                if (strlen($tag)) {
                    $this->results[$source][$tag] = trim($cdata);
                }
                break;
        } // end switch
    }

    /**
     * End XML Element
     * @param $parser
     * @param $tag
     */
    public function endElement($parser, $tag): void
    {
        // zero the tag
        $this->_currentTag = '';
    }
}

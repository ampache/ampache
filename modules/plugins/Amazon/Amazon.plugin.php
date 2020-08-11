<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

class AmpacheAmazon
{
    public $name           = 'Amazon';
    public $categories     = 'metadata';
    public $description    = 'Amazon arts';
    public $url            = 'http://www.amazon.com';
    public $version        = '000001';
    public $min_ampache    = '370009';
    public $max_ampache    = '999999';

    public $amazon_base_url;
    public $amazon_max_results_pages;
    public $amazon_developer_public_key;
    public $amazon_developer_private_api_key;
    public $amazon_developer_associate_tag;

    /**
     * Constructor
     * This function does nothing
     */
    public function __construct()
    {
        $this->description = T_('Amazon art search');

        return true;
    }

    /**
     * install
     * This is a required plugin function
     */
    public function install()
    {
        if (Preference::exists('amazon_base_url')) {
            return false;
        }

        Preference::insert('amazon_base_url', T_('Amazon base url'), 'http://webservices.amazon.com', 75, 'string', 'plugins', $this->name);
        Preference::insert('amazon_max_results_pages', T_('Amazon max results pages'), 1, 75, 'integer', 'plugins', $this->name);
        Preference::insert('amazon_developer_public_key', T_('Amazon Access Key ID'), '', 75, 'string', 'plugins', $this->name);
        Preference::insert('amazon_developer_private_api_key', T_('Amazon Secret Access Key'), '', 75, 'string', 'plugins', $this->name);
        Preference::insert('amazon_developer_associate_tag', T_('Amazon associate tag'), '', 75, 'string', 'plugins', $this->name);

        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function
     */
    public function uninstall()
    {
        Preference::delete('amazon_base_url');
        Preference::delete('amazon_max_results_pages');
        Preference::delete('amazon_developer_public_key');
        Preference::delete('amazon_developer_private_api_key');
        Preference::delete('amazon_developer_associate_tag');

        return true;
    } // uninstall

    /**
     * load
     * This is a required plugin function; here it populates the prefs we
     * need for this object.
     * @param User $user
     * @return boolean
     */
    public function load($user)
    {
        $user->set_preferences();
        $data = $user->prefs;
        // load system when nothing is given
        if (!strlen(trim($data['amazon_base_url'])) ||
                !strlen(trim($data['amazon_developer_public_key'])) ||
                !strlen(trim($data['amazon_developer_private_api_key'])) ||
                !strlen(trim($data['amazon_max_results_pages'])) ||
                !strlen(trim($data['amazon_developer_associate_tag']))) {
            $data                                     = array();
            $data['amazon_base_url']                  = Preference::get_by_user(-1, 'amazon_base_url');
            $data['amazon_developer_public_key']      = Preference::get_by_user(-1, 'amazon_developer_public_key');
            $data['amazon_developer_private_api_key'] = Preference::get_by_user(-1, 'amazon_developer_private_api_key');
            $data['amazon_max_results_pages']         = Preference::get_by_user(-1, 'amazon_max_results_pages');
            $data['amazon_developer_associate_tag']   = Preference::get_by_user(-1, 'amazon_developer_associate_tag');
        }

        if (strlen(trim($data['amazon_base_url']))) {
            $this->amazon_base_url = trim($data['amazon_base_url']);
        } else {
            debug_event('amazon.plugin', 'No amazon base url, plugin skipped', 3);

            return false;
        }

        if (strlen(trim($data['amazon_developer_public_key']))) {
            $this->amazon_developer_public_key = trim($data['amazon_developer_public_key']);
        } else {
            debug_event('amazon.plugin', 'No amazon developer public key, plugin skipped', 3);

            return false;
        }

        if (strlen(trim($data['amazon_developer_private_api_key']))) {
            $this->amazon_developer_private_api_key = trim($data['amazon_developer_private_api_key']);
        } else {
            debug_event('amazon.plugin', 'No amazon developer private key, plugin skipped', 3);

            return false;
        }

        if (strlen(trim($data['amazon_max_results_pages']))) {
            $this->amazon_max_results_pages = (int) trim($data['amazon_max_results_pages']);
        } else {
            $this->amazon_max_results_pages = 1;
        }

        if (strlen(trim($data['amazon_developer_associate_tag']))) {
            $this->amazon_developer_associate_tag = trim($data['amazon_developer_associate_tag']);
        } else {
            $this->amazon_developer_associate_tag = '';
        }

        return true;
    } // load

    /**
     * gather_arts
     * Returns arts for what we're passed in.
     * @param string $type
     * @param array $options
     * @param integer $limit
     * @return array
     */
    public function gather_arts($type, $options = array(), $limit = 5)
    {
        $images         = array();
        $final_results  = array();
        $possible_keys  = array(
            'LargeImage',
            'MediumImage',
            'SmallImage'
        );

        $mediaType = ($type == 'album' || $type == 'artist') ? 'Music' : 'Video';

        // Prevent the script from timing out
        set_time_limit(0);

        // Create the Search Object
        $amazon = new AmazonSearch($this->amazon_developer_public_key, $this->amazon_developer_private_api_key, $this->amazon_developer_associate_tag, $this->amazon_base_url);
        if (AmpConfig::get('proxy_host') && AmpConfig::get('proxy_port')) {
            $proxyhost = AmpConfig::get('proxy_host');
            $proxyport = AmpConfig::get('proxy_port');
            $proxyuser = AmpConfig::get('proxy_user');
            $proxypass = AmpConfig::get('proxy_pass');
            debug_event('amazon.plugin', 'setProxy', 5);
            $amazon->setProxy($proxyhost, $proxyport, $proxyuser, $proxypass);
        }

        $search_results = array();

        /* Set up the needed variables */
        $max_pages_to_search = max($this->amazon_max_results_pages, $amazon->_default_results_pages);
        // while we have pages to search
        do {
            $raw_results = $amazon->search(array('artist' => '', 'album' => '', 'keywords' => $options['keyword']), $mediaType);
            $total       = count($raw_results) + count($search_results);

            // If we've gotten more then we wanted
            if ($limit && $total > $limit) {
                $raw_results = array_slice($raw_results, 0, -($total - $limit), true);

                debug_event('amazon.plugin', "Found $total, limit $limit; reducing and breaking from loop", 5);
                // Merge the results and BREAK!
                $search_results = array_merge($search_results, $raw_results);
                break;
            } // if limit defined

            $search_results  = array_merge($search_results, $raw_results);
            $pages_to_search = min($max_pages_to_search, $amazon->_maxPage);
            debug_event('amazon.plugin', "Searched results page " . ($amazon->_currentPage + 1) . "/" . $pages_to_search, 5);
            $amazon->_currentPage++;
        } while ($amazon->_currentPage < $pages_to_search);


        // Only do the second search if the first actually returns something
        if (count($search_results)) {
            $final_results = $amazon->lookup($search_results);
        }

        /* Log this if we're doin debug */
        debug_event('amazon.plugin', "Searched using " . $options['keyword'] . ", results: " . count($final_results), 5);

        /* Foreach through what we've found */
        foreach ($final_results as $result) {
            $key = '';
            /* Recurse through the images found */
            foreach ($possible_keys as $k) {
                if (strlen($result[$k])) {
                    $key = $k;
                    break;
                }
            } // foreach

            if (!empty($key)) {
                // Rudimentary image type detection, only JPG and GIF allowed.
                if (substr($result[$key], -4) == '.jpg') {
                    $mime = "image/jpeg";
                } elseif (substr($result[$key], -4) == '.gif') {
                    $mime = "image/gif";
                } elseif (substr($result[$key], -4) == '.png') {
                    $mime = "image/png";
                } else {
                    /* Just go to the next result */
                    continue;
                }

                $data           = array();
                $data['url']    = $result[$key];
                $data['mime']   = $mime;
                $data['title']  = $this->name;

                $images[] = $data;

                if (!empty($limit)) {
                    if (count($images) >= $limit) {
                        return $images;
                    }
                }
            }
        } // if we've got something

        return $images;
    } // gather_arts
} // end AmpacheAmazon

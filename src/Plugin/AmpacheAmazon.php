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

namespace Ampache\Plugin;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\Util\AmazonSearch;

class AmpacheAmazon extends AmpachePlugin implements PluginGatherArtsInterface
{
    public string $name = 'Amazon';

    public string $categories = 'metadata';

    public string $description = 'Amazon arts';

    public string $url = 'http://www.amazon.com';

    public string $version = '000001';

    public string $min_ampache = '370009';

    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private $amazon_base_url;

    private $amazon_max_results_pages;

    private $amazon_developer_public_key;

    private $amazon_developer_private_api_key;

    private $amazon_developer_associate_tag;

    /**
     * Constructor
     * This function does nothing
     */
    public function __construct()
    {
        $this->description = T_('Amazon art search');
    }

    /**
     * install
     * This is a required plugin function
     */
    public function install(): bool
    {
        if (!Preference::insert('amazon_base_url', T_('Amazon base url'), 'http://webservices.amazon.com', AccessLevelEnum::MANAGER->value, 'string', 'plugins', $this->name)) {
            return false;
        }

        if (!Preference::insert('amazon_max_results_pages', T_('Amazon max results pages'), 1, AccessLevelEnum::MANAGER->value, 'integer', 'plugins', $this->name)) {
            return false;
        }

        if (!Preference::insert('amazon_developer_public_key', T_('Amazon Access Key ID'), '', AccessLevelEnum::MANAGER->value, 'string', 'plugins', $this->name)) {
            return false;
        }

        if (!Preference::insert('amazon_developer_private_api_key', T_('Amazon Secret Access Key'), '', AccessLevelEnum::MANAGER->value, 'string', 'plugins', $this->name)) {
            return false;
        }

        return Preference::insert('amazon_developer_associate_tag', T_('Amazon associate tag'), '', AccessLevelEnum::MANAGER->value, 'string', 'plugins', $this->name);
    }

    /**
     * uninstall
     * This is a required plugin function
     */
    public function uninstall(): bool
    {
        return (
            Preference::delete('amazon_base_url') &&
            Preference::delete('amazon_max_results_pages') &&
            Preference::delete('amazon_developer_public_key') &&
            Preference::delete('amazon_developer_private_api_key') &&
            Preference::delete('amazon_developer_associate_tag')
        );
    }

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade(): bool
    {
        return true;
    }

    /**
     * load
     * This is a required plugin function; here it populates the prefs we
     * need for this object.
     */
    public function load(User $user): bool
    {
        $user->set_preferences();
        $data = $user->prefs;
        // load system when nothing is given
        if (
            !strlen(trim((string) $data['amazon_base_url'])) ||
            !strlen(trim((string) $data['amazon_developer_public_key'])) ||
            !strlen(trim((string) $data['amazon_developer_private_api_key'])) ||
            !strlen(trim((string) $data['amazon_max_results_pages'])) ||
            !strlen(trim((string) $data['amazon_developer_associate_tag']))
        ) {
            $data                                     = [];
            $data['amazon_base_url']                  = Preference::get_by_user(-1, 'amazon_base_url');
            $data['amazon_developer_public_key']      = Preference::get_by_user(-1, 'amazon_developer_public_key');
            $data['amazon_developer_private_api_key'] = Preference::get_by_user(-1, 'amazon_developer_private_api_key');
            $data['amazon_max_results_pages']         = Preference::get_by_user(-1, 'amazon_max_results_pages');
            $data['amazon_developer_associate_tag']   = Preference::get_by_user(-1, 'amazon_developer_associate_tag');
        }

        if (strlen(trim((string) $data['amazon_base_url'])) !== 0) {
            $this->amazon_base_url = trim((string) $data['amazon_base_url']);
        } else {
            debug_event('amazon.plugin', 'No amazon base url, plugin skipped', 3);

            return false;
        }

        if (strlen(trim((string) $data['amazon_developer_public_key'])) !== 0) {
            $this->amazon_developer_public_key = trim((string) $data['amazon_developer_public_key']);
        } else {
            debug_event('amazon.plugin', 'No amazon developer public key, plugin skipped', 3);

            return false;
        }

        if (strlen(trim((string) $data['amazon_developer_private_api_key'])) !== 0) {
            $this->amazon_developer_private_api_key = trim((string) $data['amazon_developer_private_api_key']);
        } else {
            debug_event('amazon.plugin', 'No amazon developer private key, plugin skipped', 3);

            return false;
        }

        if (strlen(trim((string) $data['amazon_max_results_pages'])) !== 0) {
            $this->amazon_max_results_pages = (int)trim((string) $data['amazon_max_results_pages']);
        } else {
            $this->amazon_max_results_pages = 1;
        }

        if (strlen(trim((string) $data['amazon_developer_associate_tag'])) !== 0) {
            $this->amazon_developer_associate_tag = trim((string) $data['amazon_developer_associate_tag']);
        } else {
            $this->amazon_developer_associate_tag = '';
        }

        return true;
    }

    /**
     * gather_arts
     * Returns art items for the requested media type
     */
    public function gather_arts(string $type, ?array $options = [], ?int $limit = 5): array
    {
        $images        = [];
        $final_results = [];
        $possible_keys = [
            'LargeImage',
            'MediumImage',
            'SmallImage',
        ];

        $mediaType = ($type === 'album' || $type === 'artist') ? 'Music' : 'Video';

        // Prevent the script from timing out
        set_time_limit(0);

        // Create the Search Object
        $amazon = new AmazonSearch(
            $this->amazon_developer_public_key,
            $this->amazon_developer_private_api_key,
            $this->amazon_developer_associate_tag,
            $this->amazon_base_url
        );
        if (AmpConfig::get('proxy_host') && AmpConfig::get('proxy_port')) {
            $proxyhost = AmpConfig::get('proxy_host');
            $proxyport = AmpConfig::get('proxy_port');
            $proxyuser = AmpConfig::get('proxy_user');
            $proxypass = AmpConfig::get('proxy_pass');
            debug_event('amazon.plugin', 'setProxy', 5);
            $amazon->setProxy($proxyhost, $proxyport, $proxyuser, $proxypass);
        }

        $search_results = [];

        /* Set up the needed variables */
        $max_pages_to_search = max($this->amazon_max_results_pages, $amazon->_default_results_pages);
        // while we have pages to search
        do {
            $raw_results = $amazon->search(
                ['artist' => '', 'album' => '', 'keywords' => ($options['keyword'] ?? '')],
                $mediaType
            );
            $total = count($raw_results) + count($search_results);

            // If we've gotten more then we wanted
            if ($limit && $total > $limit) {
                $raw_results = array_slice($raw_results, 0, -($total - $limit), true);

                debug_event('amazon.plugin', sprintf('Found %d, limit %s; reducing and breaking from loop', $total, $limit), 5);
                // Merge the results and BREAK!
                $search_results = array_merge($search_results, $raw_results);
                break;
            } // if limit defined

            $search_results  = array_merge($search_results, $raw_results);
            $pages_to_search = min($max_pages_to_search, $amazon->_maxPage);
            debug_event(
                'amazon.plugin',
                "Searched results page " . ($amazon->_currentPage + 1) . '/' . $pages_to_search,
                5
            );
            $amazon->_currentPage++;
        } while ($amazon->_currentPage < $pages_to_search);

        // Only do the second search if the first actually returns something
        foreach ($search_results as $result) {
            $final_results[] = $amazon->lookup($result);
        }

        /* Log this if we're doin debug */
        debug_event(
            'amazon.plugin',
            "Searched using " . ($options['keyword'] ?? '') . ", results: " . count($final_results),
            5
        );

        /* Foreach through what we've found */
        foreach ($final_results as $result) {
            $key = '';
            /* Recurse through the images found */
            foreach ($possible_keys as $pKey) {
                if (strlen((string) $result[$pKey]) !== 0) {
                    $key = $pKey;
                    break;
                }
            } // foreach

            if ($key !== '' && $key !== '0') {
                // Rudimentary image type detection, only JPG and GIF allowed.
                if (str_ends_with((string) $result[$key], '.jpg')) {
                    $mime = "image/jpeg";
                } elseif (str_ends_with((string) $result[$key], '.gif')) {
                    $mime = "image/gif";
                } elseif (str_ends_with((string) $result[$key], '.png')) {
                    $mime = "image/png";
                } else {
                    /* Just go to the next result */
                    continue;
                }

                $data          = [];
                $data['url']   = $result[$key];
                $data['mime']  = $mime;
                $data['title'] = $this->name;

                $images[] = $data;

                if ($limit !== null && $limit !== 0 && count($images) >= $limit) {
                    return $images;
                }
            }
        } // if we've got something

        return $images;
    }
}

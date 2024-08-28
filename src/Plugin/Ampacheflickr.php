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

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\System\Core;
use WpOrg\Requests\Requests;

class Ampacheflickr implements PluginGatherArtsInterface
{
    public string $name        = 'Flickr';
    public string $categories  = 'slideshow';
    public string $description = 'Artist photos from Flickr';
    public string $url         = 'http://www.flickr.com';
    public string $version     = '000001';
    public string $min_ampache = '360045';
    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private $api_key;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('Artist photos from Flickr');
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        if (!Preference::insert('flickr_api_key', T_('Flickr API key'), '', AccessLevelEnum::MANAGER->value, 'string', 'plugins', $this->name)) {
            return false;
        }

        return true;
    }

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    public function uninstall(): bool
    {
        return Preference::delete('flickr_api_key');
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
     * @param string $search
     * @param string $category
     * @return array
     */
    public function get_photos($search, $category = 'concert'): array
    {
        $photos = [];
        $url    = "https://api.flickr.com/services/rest/?&method=flickr.photos.search&api_key=" . $this->api_key . "&per_page=20&content_type=1&text=" . rawurlencode(trim($search . " " . $category));
        debug_event('flickr.plugin', 'Calling ' . $url, 5);
        $request = Requests::get($url, [], Core::requests_options());
        if ($request->status_code == 200) {
            $xml = simplexml_load_string($request->body);
            if ($xml && $xml->photos) {
                foreach ($xml->photos->photo as $photo) {
                    $photos[] = [
                        'title' => $photo->title,
                        'url' => "http://farm" . $photo['farm'] . ".static.flickr.com/" . $photo['server'] . '/' . $photo['id'] . "_" . $photo['secret'] . "_m.jpg",
                    ];
                }
            }
        }

        return $photos;
    }

    /**
     * gather_arts
     * Returns art items for the requested media type
     */
    public function gather_arts(string $type, ?array $options = [], ?int $limit = 5): array
    {
        if (!$limit) {
            $limit = 5;
        }

        $images  = $this->get_photos($options['keyword'], '');
        $results = [];
        foreach ($images as $image) {
            $title = $this->name;
            if (!empty($image['title'])) {
                $title .= ' - ' . $image['title'];
            }
            $results[] = [
                'url' => $image['url'],
                'mime' => 'image/jpeg',
                'title' => $title
            ];

            if ($limit && count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes from the preferences.
     */
    public function load(User $user): bool
    {
        $user->set_preferences();
        $data = $user->prefs;
        // load system when nothing is given
        if (!strlen(trim($data['flickr_api_key']))) {
            $data                   = [];
            $data['flickr_api_key'] = Preference::get_by_user(-1, 'flickr_api_key');
        }

        if (strlen(trim($data['flickr_api_key']))) {
            $this->api_key = trim($data['flickr_api_key']);
        } else {
            debug_event('flickr.plugin', 'No Flickr api key, photo plugin skipped', 3);

            return false;
        }

        return true;
    }
}

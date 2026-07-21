<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Api;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use DOMDocument;

/**
 * Api Class
 *
 * This handles functions relating to the API written for Ampache, initially
 * this is very focused on providing functionality for Amarok so it can
 * integrate with Ampache.
 */
class Api
{
    public const API_VERSIONS = [
        3,
        4,
        5,
        6
    ];

    public const DEFAULT_VERSION          = 6; // AMPACHE_VERSION
    public static ?Browse $browse         = null;
    public static string $version         = '8.0.0'; // AMPACHE_VERSION
    public static string $version_numeric = '800000'; // AMPACHE_VERSION

    /**
     * filter_objects
     *
     * This filters the objects based on the limit and offset
     * @param array<int, mixed> $objects
     * @return array<int, mixed>
     */
    public static function filter_objects(array $objects, int $count = 0, int $offset = 0, ?int $limit = null, ?bool $encode = null): array
    {
        if (
            $encode !== false
            && ($limit !== null)
            && ($count > $limit || $offset > 0)
        ) {
            return array_slice($objects, $offset, $limit);
        }

        return $objects;
    }

    /**
     * footer
     *
     * this returns the footer for this document, these are pretty boring
     */
    public static function footer(): string
    {
        return "\n</root>\n";
    }

    public static function getBrowse(User $user): Browse
    {
        if (self::$browse === null) {
            // create new browse
            self::$browse = new Browse(null, false);
        } else {
            // reset existing browse
            self::$browse->reset();
            // ensure _state offset is 0
            self::$browse->set_offset(0);
        }

        // ensure user_id is set
        self::$browse->set_user_id($user);

        return self::$browse;
    }

    public static function getHttpCode(int|string $code): int
    {
        switch ((string) $code) {
            case '4700': // ACCESS_CONTROL_NOT_ENABLED
            case '4703': // ACCESS_DENIED
            case '4742': // FAILED_ACCESS_CHECK
                return 403;
            case '4710': // BAD_REQUEST
            case '4705': // MISSING
                return 400;
            case '4706': // DEPRECATED
                return 410;
            case '4702': // GENERIC_ERROR
                return 500;
            case '4701': // INVALID_HANDSHAKE
                return 401;
            case '4704': // NOT_FOUND
                return 404;
        }

        debug_event(self::class, "Unknown error code: $code", 3);

        return 500;
    }

    /**
     * header
     *
     * this returns a standard header, there are a few types
     * so we allow them to pass a type if they want to
     */
    public static function header(): string
    {
        return "<?xml version=\"1.0\" encoding=\"" . AmpConfig::get('site_charset', 'UTF-8') . "\" ?>\n<root>\n";
    }

    /**
     * keyed_array
     *
     * This will build an xml document from a key'd array,
     * @param array<int|string, mixed> $array
     */
    public static function keyed_array(array $array, bool $callback = false, bool|string $object = false): string
    {
        $string = '';
        // Foreach it
        foreach ($array as $key => $value) {
            $attribute = '';
            if (is_object($value)) {
                $value = (array) $value;
            }
            // See if the key has attributes
            if (is_array($value) && isset($value['attributes'])) {
                $attribute = ' ' . $value['attributes'];
                $key       = $value['value'];
            }

            // If it's an array, run again
            if (is_array($value)) {
                $value = (isset($value[0]))
                    ? self::keyed_array($value, true, $key)
                    : self::keyed_array($value, true);
                $string .= ($object) ? "<$object>\n$value\n</$object>\n" : "<$key$attribute>\n$value\n</$key>\n";
            } else {
                $string .= ($object) ? "\t<$object index=\"" . $key . "\"><![CDATA[" . $value . "]]></$object>\n" : "\t<$key$attribute><![CDATA[" . $value . "]]></$key>\n";
            }
        }

        if (!$callback) {
            $string = self::output_xml($string);
        }

        return $string;
    }

    /**
     * object_array
     *
     * This will build an xml document from an array of arrays, an id is required for the array data
     * <root>
     *   <$object_type> //optional
     *     <$item id="123">
     *       <data></data>
     * @param array<int, array<string, mixed>> $array
     */
    public static function object_array(array $array, string $item, string $object_type = ''): string
    {
        $string = ($object_type == '') ? '' : "<$object_type>\n";
        // Foreach it
        foreach ($array as $object) {
            $string .= "\t<$item id=\"" . ($object['id'] ?? $object['name']) . "\">\n";
            foreach ($object as $name => $value) {
                if ($name === 'widget') {
                    $widget_type = $value[0];
                    $filter      = '';
                    if (is_array($value[1])) {
                        foreach ($value[1] as $key => $val) {
                            $filter .= "\t\t<$widget_type id=\"$key\"><![CDATA[" . $val . "]]></$widget_type>\n";
                        }
                    } else {
                        $filter = "\t\t<$widget_type><![CDATA[" . $value[1] . "]]></$widget_type>\n";
                    }
                } elseif (($name === 'values' || $name === 'subtypes') && is_array($value)) {
                    $filter = '';
                    foreach ($value as $key => $val) {
                        $filter .= "\t\t<value id=\"$key\"><![CDATA[" . $val . "]]></value>\n";
                    }
                } else {
                    $filter = (is_numeric($value)) ? $value : "<![CDATA[" . $value . "]]>";
                }
                $string .= ($name !== 'id') ? "\t\t<$name>$filter</$name>\n" : '';
            }
            $string .= "\t</$item>\n";
        }
        $string .= ($object_type == '') ? '' : "</$object_type>";

        return self::output_xml($string);
    }

    public static function output_xml(string $string, bool $full_xml = true): string
    {
        $xml = "";
        if ($full_xml) {
            $xml .= self::header();
        }
        $xml .= Ui::clean_utf8($string);
        if ($full_xml) {
            $xml .= self::footer();
        }
        // return formatted xml when asking for full_xml
        if ($full_xml) {
            $dom = new DOMDocument();
            // format the string
            $dom->preserveWhiteSpace = false;
            if (!$dom->loadXML($xml)) {
                return $xml;
            }
            $dom->formatOutput = true;

            return $dom->saveXML() ?: '';
        }

        return $xml;
    }

    /**
     * output_xml_from_array
     * This takes a one dimensional array and creates a XML document from it. For
     * use primarily by the ajax mojo.
     * @param array<int|string, mixed> $array
     */
    public static function output_xml_from_array(array $array, bool $callback = false, string $type = ''): string
    {
        $string = '';

        // The type is used for the different XML docs we pass
        switch ($type) {
            case 'itunes':
                foreach ($array as $key => $value) {
                    if (is_array($value)) {
                        $value = xoutput_from_array($value, true, $type);
                        $string .= "\t\t<$key>\n$value\t\t</$key>\n";
                    } elseif ($key == "key") {
                        $string .= "\t\t<$key>$value</$key>\n";
                    } elseif (is_int($value)) {
                        $string .= "\t\t\t<key>$key</key><integer>$value</integer>\n";
                    } elseif ($key == "Date Added") {
                        $string .= "\t\t\t<key>$key</key><date>$value</date>\n";
                    } elseif (is_string($value)) {
                        /* We need to escape the value */
                        $string .= "\t\t\t<key>$key</key><string><![CDATA[" . $value . "]]></string>\n";
                    }
                }

                return $string;
            case 'xspf':
                foreach ($array as $key => $value) {
                    if (is_array($value)) {
                        $value = xoutput_from_array($value, true, $type);
                        $string .= "\t\t<$key>\n$value\t\t</$key>\n";
                    } elseif ($key == "key") {
                        $string .= "\t\t<$key>$value</$key>\n";
                    } elseif (is_numeric($value)) {
                        $string .= "\t\t\t<$key>$value</$key>\n";
                    } elseif (is_string($value)) {
                        /* We need to escape the value */
                        $string .= "\t\t\t<$key><![CDATA[" . $value . "]]></$key>\n";
                    }
                }

                return $string;
            default:
                foreach ($array as $key => $value) {
                    // No numeric keys
                    if (is_numeric($key)) {
                        $key = 'item';
                    }

                    if (is_array($value)) {
                        // Call ourself
                        $value = xoutput_from_array($value, true);
                        $string .= "\t<content div=\"$key\">$value</content>\n";
                    } else {
                        /* We need to escape the value */
                        $string .= "\t<content div=\"$key\"><![CDATA[" . $value . "]]></content>\n";
                    }
                }
                if (!$callback) {
                    $string = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n<root>\n" . $string . "</root>\n";
                }

                return Ui::clean_utf8($string);
        }
    }

    /**
     * parameter_exists
     *
     * This function checks the $input actually has the parameter.
     * A parameter sent with an empty value (e.g. 'filter=') doesn't count as sent.
     * Parameters must be an array of required elements as a string
     *
     * @param array<string, mixed> $input
     * @param string[] $parameters e.g. array('auth', type')
     */
    public static function parameter_exists(array $input, array $parameters): bool|string
    {
        foreach ($parameters as $parameter) {
            if (
                array_key_exists($parameter, $input)
                && $input[$parameter] !== null
                && $input[$parameter] !== ''
                && $input[$parameter] !== []
            ) {
                continue;
            }

            return $parameter;
        }

        return true;
    }

    /**
     * server_details
     *
     * get the server counts for pings and handshakes
     *
     * @return array{
     *     auth?: ?string,
     *     api?: string,
     *     session_expire?: int|string,
     *     update?: string,
     *     add?: string,
     *     clean?: string,
     *     max_song?: int,
     *     max_album?: int,
     *     max_artist?: int,
     *     max_video?: int,
     *     max_podcast?: int,
     *     max_podcast_episode?: int,
     *     songs?: int,
     *     albums?: int,
     *     artists?: int,
     *     genres?: int,
     *     playlists?: int,
     *     searches?: int,
     *     playlists_searches?: int,
     *     users?: int,
     *     catalogs?: int,
     *     videos?: int,
     *     podcasts?: int,
     *     podcast_episodes?: int,
     *     shares?: int,
     *     licenses?: int,
     *     live_streams?: int,
     *     labels?: int,
     *     username?: string,
     * }
     */
    public static function server_details(string $token = ''): array
    {
        // We need to also get the 'last update' of the catalog information in an RFC 2822 Format
        $sql = <<<SQL
            SELECT `catalog`.`update`, `catalog`.`add`, `catalog`.`clean`, `maxid`.`max_song`, `maxid`.`max_album`, `maxid`.`max_artist`, `maxid`.`max_video`, `maxid`.`max_podcast`, `maxid`.`max_podcast_episode`
            FROM (
               SELECT MAX(`last_update`) AS `update`,
                      MAX(`last_add`) AS `add`,
                      MAX(`last_clean`) AS `clean`
               FROM `catalog`
            ) AS `catalog`
            LEFT JOIN (
                SELECT (SELECT MAX(`id`) FROM `song`) AS `max_song`,
                       (SELECT MAX(`id`) FROM `album`) AS `max_album`,
                       (SELECT MAX(`id`) FROM `artist`) AS `max_artist`,
                       (SELECT MAX(`id`) FROM `video`) AS `max_video`,
                       (SELECT MAX(`id`) FROM `podcast`) AS `max_podcast`,
                       (SELECT MAX(`id`) FROM `podcast_episode`) AS `max_podcast_episode`
            ) AS `maxid` ON 1=1;
            SQL;
        $db_results = Dba::read($sql);
        $details    = Dba::fetch_assoc($db_results);

        // Now we need to quickly get the totals
        $client = self::getUserRepository()->findByApiKey(trim($token));
        if (!$client instanceof User || $client->isNew()) {
            return [];
        }

        $counts    = Catalog::get_server_counts($client->id);
        $playlists = (AmpConfig::get('hide_search', false))
            ? $counts['playlist']
            : $counts['playlist'] + $counts['search'];
        $autharray = (!empty($token))
            ? [
                'auth' => $token,
                'streamtoken' => $client->streamtoken
            ]
            : [];
        // perpetual sessions do not expire
        $perpetual      = (bool) AmpConfig::get('perpetual_api_session', false);
        $session_expire = ($perpetual)
            ? 0
            : date("c", time() + AmpConfig::get('session_length', 3600) - 60);

        // send the totals
        $outarray = [
            'api' => self::$version,
            'session_expire' => $session_expire,
            'update' => date("c", (int) $details['update']),
            'add' => date("c", (int) $details['add']),
            'clean' => date("c", (int) $details['clean']),
            'max_song' => (int) $details['max_song'],
            'max_album' => (int) $details['max_album'],
            'max_artist' => (int) $details['max_artist'],
            'max_video' => (int) $details['max_video'],
            'max_podcast' => (int) $details['max_podcast'],
            'max_podcast_episode' => (int) $details['max_podcast_episode'],
            'songs' => $counts['song'],
            'albums' => $counts['album'],
            'artists' => $counts['artist'],
            'genres' => $counts['tag'],
            'playlists' => $counts['playlist'],
            'searches' => $counts['search'],
            'playlists_searches' => $playlists,
            'users' => $counts['user'],
            'catalogs' => $counts['catalog'],
            'videos' => $counts['video'],
            'podcasts' => $counts['podcast'],
            'podcast_episodes' => $counts['podcast_episode'],
            'shares' => $counts['share'],
            'licenses' => $counts['license'],
            'live_streams' => $counts['live_stream'],
            'labels' => $counts['label'],
            'username' => $client->getUsername(),
        ];

        return array_merge($autharray, $outarray);
    }

    /**
     * @deprecated inject by constructor
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }
}

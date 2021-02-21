<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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

declare(strict_types=1);

namespace Ampache\Module\Api\Output;

use Ampache\Module\Api\Xml_Data;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;

final class XmlOutput implements ApiOutputInterface
{
    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory
    ) {
        $this->modelFactory = $modelFactory;
    }

    /**
     * At the moment, this method just acts a proxy
     */
    public function error(int $code, string $message, string $action, string $type): string
    {
        return Xml_Data::error(
            $code,
            $message,
            $action,
            $type
        );
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $albums
     * @param array $include
     * @param int|null $user_id
     * @param bool $fullXml
     * @param int $limit
     * @param int $offset
     *
     * @return array|string
     */
    public function albums(
        array $albums,
        array $include = [],
        ?int $user_id = null,
        bool $fullXml = true,
        int $limit = 0,
        int $offset = 0
    ) {
        Xml_Data::set_offset($offset);
        Xml_Data::set_limit($limit);

        return Xml_Data::albums($albums, $include, $user_id, $fullXml);
    }

    public function emptyResult(string $type): string
    {
        return Xml_Data::empty();
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $artists
     * @param array $include
     * @param null|int $user_id
     * @param boolean $encode
     * @param boolean $object (whether to return as a named object array or regular array)
     * @param int $limit
     * @param int $offset
     *
     * @return array|string
     */
    public function artists(
        array $artists,
        array $include = [],
        ?int $user_id = null,
        bool $encode = true,
        bool $object = true,
        int $limit = 0,
        int $offset = 0
    ) {
        Xml_Data::set_offset($offset);
        Xml_Data::set_limit($limit);

        return Xml_Data::artists(
            $artists,
            $include,
            $user_id
        );
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $songs
     * @param int|null $user_id
     * @param boolean $encode
     * @param boolean $object (whether to return as a named object array or regular array)
     * @param boolean $full_xml
     * @param int $limit
     * @param int $offset
     *
     * @return array|string
     */
    public function songs(
        array $songs,
        ?int $user_id = null,
        bool $encode = true,
        bool $object = true,
        bool $full_xml = true,
        int $limit = 0,
        int $offset = 0
    ) {
        Xml_Data::set_offset($offset);
        Xml_Data::set_limit($limit);

        return Xml_Data::songs(
            $songs,
            $user_id,
            $full_xml
        );
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $users User identifier list
     *
     * @return string
     */
    public function users(array $users): string
    {
        return Xml_Data::users($users);
    }

    /**
     * This handles creating an xml document for a shout list
     *
     * @param int[] $shoutIds Shout identifier list
     */
    public function shouts(array $shoutIds): string
    {
        $result = '';
        foreach ($shoutIds as $shoutId) {
            $shout = $this->modelFactory->createShoutbox($shoutId);
            $user  = $this->modelFactory->createUser((int) $shout->user);

            $result .= "\t<shout id=\"" . $shoutId . "\">\n" . "\t\t<date>" . $shout->date . "</date>\n" . "\t\t<text><![CDATA[" . $shout->text . "]]></text>\n";
            if ($user->id) {
                $result .= "\t\t<user id=\"" . (string)$user->id . "\">\n" . "\t\t\t<username><![CDATA[" . $user->username . "]]></username>\n" . "\t\t</user>\n";
            }
            $result .= "\t</shout>\n";
        }

        return Xml_Data::output_xml($result);
    }

    /**
     * This handles creating an xml document for a user
     */
    public function user(User $user, bool $fullinfo): string
    {
        $user->format();
        $string = "<user id=\"" . (string)$user->id . "\">\n" . "\t<username><![CDATA[" . $user->username . "]]></username>\n";
        if ($fullinfo) {
            $string .= "\t<auth><![CDATA[" . $user->apikey . "]]></auth>\n" .
                "\t<email><![CDATA[" . $user->email . "]]></email>\n" .
                "\t<access>" . (int) $user->access . "</access>\n" .
                "\t<fullname_public>" . (int) $user->fullname_public . "</fullname_public>\n" .
                "\t<validation><![CDATA[" . $user->validation . "]]></validation>\n" .
                "\t<disabled>" . (int) $user->disabled . "</disabled>\n";
        }
        $string .= "\t<create_date>" . (int) $user->create_date . "</create_date>\n" .
            "\t<last_seen>" . (int) $user->last_seen . "</last_seen>\n" .
            "\t<link><![CDATA[" . $user->link . "]]></link>\n" .
            "\t<website><![CDATA[" . $user->website . "]]></website>\n" .
            "\t<state><![CDATA[" . $user->state . "]]></state>\n" .
            "\t<city><![CDATA[" . $user->city . "]]></city>\n";
        if ($user->fullname_public || $fullinfo) {
            $string .= "\t<fullname><![CDATA[" . $user->fullname . "]]></fullname>\n";
        }
        $string .= "</user>\n";

        return Xml_Data::output_xml($string);
    }

    /**
     * This returns genres to the user
     *
     * @param int[] $tagIds
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     */
    public function genres(
        array $tagIds,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        if ((count($tagIds) > $limit || $offset > 0) && $limit) {
            $tagIds = array_splice($tagIds, $offset, $limit);
        }
        $string = "<total_count>" . Catalog::get_count('tag') . "</total_count>\n";

        foreach ($tagIds as $tag_id) {
            $tag    = $this->modelFactory->createTag($tag_id);
            $counts = $tag->count();

            $string .= "<genre id=\"$tag_id\">\n" .
                "\t<name><![CDATA[$tag->name]]></name>\n" .
                "\t<albums>" . (int) ($counts['album']) . "</albums>\n" .
                "\t<artists>" . (int) ($counts['artist']) . "</artists>\n" .
                "\t<songs>" . (int) ($counts['song']) . "</songs>\n" .
                "\t<videos>" . (int) ($counts['video']) . "</videos>\n" .
                "\t<playlists>" . (int) ($counts['playlist']) . "</playlists>\n" .
                "\t<live_streams>" . (int) ($counts['live_stream']) . "</live_streams>\n" .
                "</genre>\n";
        }

        return Xml_Data::output_xml($string);
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $videoIds
     * @param int|null $userId
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     */
    public function videos(
        array $videoIds,
        ?int $userId = null,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        return Xml_Data::videos(
            $videoIds,
            $userId,
            $limit,
            $offset
        );
    }

    public function success(
        string $string,
        array $return_data = []
    ): string {
        $xml_string = "\t<success code=\"1\"><![CDATA[$string]]></success>";
        foreach ($return_data as $title => $data) {
            $xml_string .= "\n\t<$title><![CDATA[$data]]></$title>";
        }

        return Xml_Data::output_xml($xml_string);
    }

    /**
     * This returns licenses to the user
     *
     * @param int[] $licenseIds
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     */
    public function licenses(
        array $licenseIds,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        if ((count($licenseIds) > $limit || $offset > 0) && $limit) {
            $licenseIds = array_splice($licenseIds, $offset, $limit);
        }
        $string = "<total_count>" . Catalog::get_count('license') . "</total_count>\n";

        foreach ($licenseIds as $licenseId) {
            $license = $this->modelFactory->createLicense($licenseId);
            $string .= "<license id=\"$licenseId\">\n" .
                "\t<name><![CDATA[" . $license->getName() . "]]></name>\n" .
                "\t<description><![CDATA[" . $license->getDescription() . "]]></description>\n" .
                "\t<external_link><![CDATA[" . $license->getLink() . "]]></external_link>\n" .
                "</license>\n";
        }

        return Xml_Data::output_xml($string);
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $labelIds
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     */
    public function labels(
        array $labelIds,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        return Xml_Data::labels(
            $labelIds,
            $limit,
            $offset
        );
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $podcasts
     * @param int $userId
     * @param bool $episodes include the episodes of the podcast
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     */
    public function podcasts(
        array $podcasts,
        int $userId,
        bool $episodes = false,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        return Xml_Data::podcasts(
            $podcasts,
            $userId,
            $episodes,
            $limit,
            $offset
        );
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $podcastEpisodeIds
     * @param int $userId
     * @param bool $simple just return the data as an array for pretty somewhere else
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     *
     * @return array|string
     */
    public function podcast_episodes(
        array $podcastEpisodeIds,
        int $userId,
        bool $simple = false,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ) {
        return Xml_Data::podcast_episodes(
            $podcastEpisodeIds,
            $userId,
            $simple !== true,
            $limit,
            $offset
        );
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $playlistIds
     * @param int $userId
     * @param bool $songs
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     */
    public function playlists(
        array $playlists,
        int $userId,
        bool $songs = false,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        return Xml_Data::playlists($playlists, $userId, $limit, $offset);
    }

    public function dict(
        array $data,
        bool $xmlOutput = true,
        ?string $tagName = null
    ): string {
        $string = '';
        // Foreach it
        foreach ($data as $key => $value) {
            $attribute = '';
            // See if the key has attributes
            if (is_array($value) && isset($value['attributes'])) {
                $attribute = ' ' . $value['attributes'];
                $key       = $value['value'];
            }

            // If it's an array, run again
            if (is_array($value)) {
                $value = $this->dict($value, true);
                $string .= ($tagName) ? "<$tagName>\n$value\n</$tagName>\n" : "<$key$attribute>\n$value\n</$key>\n";
            } else {
                $string .= ($tagName) ? "\t<$tagName index=\"" . $key . "\"><![CDATA[$value]]></$tagName>\n" : "\t<$key$attribute><![CDATA[$value]]></$key>\n";
            }
        } // end foreach

        if ($xmlOutput) {
            $string = Xml_Data::output_xml($string);
        }

        return $string;
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $catalogIds group of catalog id's
     * @param bool $asObject (whether to return as a named object array or regular array)
     * @param int $limit
     * @param int $offset
     */
    public function catalogs(
        array $catalogIds,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        if ((count($catalogIds) > $limit || $offset > 0) && $limit) {
            $catalogIds = array_splice($catalogs, $offset, $limit);
        }
        $string = "<total_count>" . Catalog::get_count('catalog') . "</total_count>\n";

        foreach ($catalogIds as $catalogId) {
            $catalog = Catalog::create_from_id($catalogId);
            $catalog->format();
            $string .= "<catalog id=\"$catalogId\">\n" . "\t<name><![CDATA[" . $catalog->name . "]]></name>\n" . "\t<type><![CDATA[" . $catalog->catalog_type . "]]></type>\n" . "\t<gather_types><![CDATA[" . $catalog->gather_types . "]]></gather_types>\n" . "\t<enabled>" . $catalog->enabled . "</enabled>\n" . "\t<last_add><![CDATA[" . $catalog->f_add . "]]></last_add>\n" . "\t<last_clean><![CDATA[" . $catalog->f_clean . "]]></last_clean>\n" . "\t<last_update><![CDATA[" . $catalog->f_update . "]]></last_update>\n" . "\t<path><![CDATA[" . $catalog->f_info . "]]></path>\n" . "\t<rename_pattern><![CDATA[" . $catalog->rename_pattern . "]]></rename_pattern>\n" . "\t<sort_pattern><![CDATA[" . $catalog->sort_pattern . "]]></sort_pattern>\n" . "</catalog>\n";
        }

        return Xml_Data::output_xml($string);
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $activityIds Activity identifier list
     */
    public function timeline(array $activityIds): string
    {
        $string = '';
        foreach ($activityIds as $activityId) {
            $activity = $this->modelFactory->createUseractivity($activityId);
            $user     = $this->modelFactory->createUser((int) $activity->user);
            $string .= "\t<activity id=\"" . $activityId . "\">\n" . "\t\t<date>" . $activity->activity_date . "</date>\n" . "\t\t<object_type><![CDATA[" . $activity->object_type . "]]></object_type>\n" . "\t\t<object_id>" . $activity->object_id . "</object_id>\n" . "\t\t<action><![CDATA[" . $activity->action . "]]></action>\n";
            if ($user->id) {
                $string .= "\t\t<user id=\"" . (string)$user->id . "\">\n" . "\t\t\t<username><![CDATA[" . $user->username . "]]></username>\n" . "\t\t</user>\n";
            }
            $string .= "\t</activity>\n";
        }

        return Xml_Data::_header() . $string . Xml_Data::_footer();
    }

    /**
     * This returns bookmarks to the user
     *
     * @param int[] $bookmarkIds
     * @param int $limit
     * @param int $offset
     */
    public function bookmarks(
        array $bookmarkIds,
        int $limit = 0,
        int $offset = 0
    ): string {
        $string = "";
        foreach ($bookmarkIds as $bookmark_id) {
            $bookmark = $this->modelFactory->createBookmark($bookmark_id);

            $string .= "<bookmark id=\"$bookmark_id\">\n" .
                "\t<user><![CDATA[" . $bookmark->getUserName() . "]]></user>\n" .
                "\t<object_type><![CDATA[" . $bookmark->object_type . "]]></object_type>\n" .
                "\t<object_id>" . $bookmark->object_id . "</object_id>\n" .
                "\t<position>" . $bookmark->position . "</position>\n" .
                "\t<client><![CDATA[" . $bookmark->comment . "]]></client>\n" .
                "\t<creation_date>" . $bookmark->creation_date . "</creation_date>\n" .
                "\t<update_date><![CDATA[" . $bookmark->update_date . "]]></update_date>\n" .
                "</bookmark>\n";
        }

        return Xml_Data::output_xml($string);
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $shareIds Share id's to include
     * @param bool  $asAsOject
     * @param int   $limit
     * @param int   $offset
     */
    public function shares(
        array $shareIds,
        bool $asOject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        return Xml_Data::shares(
            $shareIds,
            $limit,
            $offset
        );
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param array  $array
     * @param string $item
     */
    public function object_array(
        array $array,
        string $item
    ): string {
        return Xml_Data::object_array($array, $item);
    }
}

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

use Ampache\Model\ModelFactoryInterface;
use Ampache\Model\Shoutbox;
use Ampache\Model\User;
use Ampache\Module\Api\Xml_Data;

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
}

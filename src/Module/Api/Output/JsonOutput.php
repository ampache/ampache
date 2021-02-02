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
use Ampache\Module\Api\Json_Data;

final class JsonOutput implements ApiOutputInterface
{
    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory
    ) {
        $this->modelFactory = $modelFactory;
    }

    /**
     * At the moment, this method just acts as a proxy
     */
    public function error(int $code, string $message, string $action, string $type): string
    {
        return Json_Data::error(
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
     * @param bool $encode
     * @param int $limit
     * @param int $offset
     *
     * @return array|string
     */
    public function albums(
        array $albums,
        array $include = [],
        ?int $user_id = null,
        bool $encode = true,
        int $limit = 0,
        int $offset = 0
    ) {
        Json_Data::set_offset($offset);
        Json_Data::set_limit($limit);

        return Json_Data::albums($albums, $include, $user_id, $encode);
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param string $type object type
     *
     * @return string return empty JSON message
     */
    public function emptyResult(string $type): string
    {
        return Json_Data::empty($type);
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
     * @return array|string JSON Object "artist"
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
        Json_Data::set_offset($offset);
        Json_Data::set_limit($limit);

        return Json_Data::artists(
            $artists,
            $include,
            $user_id,
            $encode,
            $object
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
        Json_Data::set_offset($offset);
        Json_Data::set_limit($limit);

        return Json_Data::songs(
            $songs,
            $user_id,
            $encode,
            $object
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
        return Json_Data::users($users);
    }

    /**
     * This handles creating a result for a shout list
     *
     * @param int[] $shoutIds Shout identifier list
     */
    public function shouts(array $shoutIds): string
    {
        $result = [];
        foreach ($shoutIds as $shoutId) {
            $shout = $this->modelFactory->createShoutbox($shoutId);
            $user  = $this->modelFactory->createUser((int) $shout->user);

            $result[] = [
                'id' => (string) $shoutId,
                'date' => $shout->date,
                'text' => $shout->text,
                'user' => [
                    'id' => (string) $shout->user,
                    'username' => $user->username
                ]
            ];
        }

        return json_encode(['shout' => $result], JSON_PRETTY_PRINT);
    }
}

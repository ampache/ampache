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

final class XmlOutput implements ApiOutputInterface
{
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
     * @param integer[] $albums
     * @param array $include
     * @param integer|null $user_id
     * @param bool $encode
     * @param bool $asObject
     * @param integer $limit
     * @param integer $offset
     *
     * @return string
     */
    public function albums(
        array $albums,
        array $include = [],
        ?int $user_id = null,
        bool $encode = true,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ) {
        Xml_Data::set_offset($offset);
        Xml_Data::set_limit($limit);

        return Xml_Data::albums($albums, $include, $user_id, $encode);
    }
}

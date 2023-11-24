<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
 */

namespace Ampache\Module\Util;

interface AmpacheRssInterface
{
    /**
     * get_xml
     * This returns the xmldocument for the current rss type, it calls a sub function that gathers the data
     * and then uses the xmlDATA class to build the document
     *
     * @param null|array{object_type: string, object_id: int} $params
     */
    public function get_xml(string $rssToken, string $type, ?array $params = null): string;

    /**
     * get_description
     * This returns the standardized description for the rss feed based on this->type
     */
    public function get_description(): string;
}

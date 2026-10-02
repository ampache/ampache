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

namespace Ampache\Module\Api\Jellyfin;

use Ampache\Config\AmpConfig;

/**
 * The one synthetic "Music" library view (see plan §F: one merged view vs. one per catalog is still open).
 * Shared by `UserViewsMethod` and `ItemsMethod` (root call, no ParentId/IncludeItemTypes) so both answer
 * identically — real clients (confirmed: Symfonium) use either path interchangeably to discover libraries.
 */
final class JellyfinUserView
{
    private const string VIEW_ID = '1';

    /** @return array<string, mixed> */
    public static function build(): array
    {
        return [
            'Name' => (string) AmpConfig::get('site_title', 'Music'),
            'ServerId' => null,
            'Id' => JellyfinId::encode('view', self::VIEW_ID),
            'Etag' => null,
            'CanDelete' => false,
            'CanDownload' => false,
            'SortName' => 'music',
            'CollectionType' => 'music',
            'Type' => 'UserView',
        ];
    }
}

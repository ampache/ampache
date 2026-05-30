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

namespace Ampache\Module\Playback\Localplay;

use Ampache\Module\Playback\Localplay\HttpQ\AmpacheHttpq;
use Ampache\Module\Playback\Localplay\Mpd\AmpacheMpd;
use Ampache\Module\Playback\Localplay\Upnp\AmpacheUPnP;
use Ampache\Module\Playback\Localplay\Vlc\AmpacheVlc;
use Ampache\Module\Playback\Localplay\Xbmc\AmpacheXbmc;

final class LocalPlayTypeEnum
{
    public const array TYPE_MAPPING = [
        'httpq' => AmpacheHttpq::class,
        'mpd' => AmpacheMpd::class,
        'upnp' => AmpacheUPnP::class,
        'vlc' => AmpacheVlc::class,
        'xbmc' => AmpacheXbmc::class,
    ];
}

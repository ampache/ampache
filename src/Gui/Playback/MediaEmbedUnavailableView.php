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

namespace Ampache\Gui\Playback;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Override;

/**
 * What a frame gets when this server has no player to put in it.
 *
 * A card a scraper stored keeps asking for `embed=1` long after an operator turned the player off, so the
 * answer has to be something that belongs in a 420 pixel frame rather than the site itself.
 */
final class MediaEmbedUnavailableView extends AbstractView
{
    public function __construct(
        private readonly string $linkUrl,
    ) {}

    /**
     * A frame refused by configuration is not a missing page: the object is there, the representation is not.
     */
    public static function statusFor(bool $visible): int
    {
        return ($visible) ? 403 : 404;
    }

    public function getDocumentLanguage(): string
    {
        return str_replace('_', '-', (string) AmpConfig::get('lang', 'en_US'));
    }

    public function getLinkUrl(): string
    {
        return $this->linkUrl;
    }

    /**
     * The instance's own logo, because the visitor came for this site and not for the software behind it.
     */
    public function getLogoUrl(): string
    {
        return (string) AmpConfig::get('custom_logo', '');
    }

    public function getSiteTitle(): string
    {
        return (string) AmpConfig::get('site_title', 'Ampache');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('playback/media_embed_unavailable.phtml');
    }
}

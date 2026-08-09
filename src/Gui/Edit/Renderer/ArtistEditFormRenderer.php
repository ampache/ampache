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

namespace Ampache\Gui\Edit\Renderer;

use Ampache\Config\AmpConfig;
use Ampache\Gui\Edit\AbstractEditFormRenderer;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Override;

/**
 * The artist edit dialog.
 *
 * Its MusicBrainz id reached two `value=` attributes unescaped, and that field is free text.
 */
final class ArtistEditFormRenderer extends AbstractEditFormRenderer
{
    public function getArtistId(): int
    {
        return $this->getItem()->getId();
    }

    public function getGenres(): string
    {
        return Tag::get_display(Tag::get_top_tags('artist', $this->getArtistId(), 0));
    }

    public function getLabels(): string
    {
        return Label::get_display($this->getItem()->get_labels());
    }

    public function getMbid(): string
    {
        return (string) $this->getItem()->mbid;
    }

    public function getName(): string
    {
        return (string) $this->getItem()->get_fullname();
    }

    public function getOwnerId(): int
    {
        return (int) $this->getItem()->user;
    }

    public function getPlaceFormed(): string
    {
        return (string) $this->getItem()->placeformed;
    }

    public function getSummary(): string
    {
        return trim((string) $this->getItem()->summary);
    }

    /**
     * @return array<int, string>
     */
    public function getUsers(): array
    {
        return $this->getContext()->users;
    }

    public function getYearFormed(): string
    {
        return (string) $this->getItem()->yearformed;
    }

    public function mayEditMbid(): bool
    {
        $user = Core::get_global('user');

        return $this->mayManage() || ($user instanceof User && $user->getId() === $this->getItem()->get_user_owner());
    }

    /**
     * A manager may reassign the owner; the owner themselves may only correct the MusicBrainz id.
     */
    public function mayManage(): bool
    {
        $user = Core::get_global('user');

        return $user instanceof User && Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER, $user->getId());
    }

    public function showLabels(): bool
    {
        return (bool) AmpConfig::get('label');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('edit/artist.phtml');
    }

    private function getItem(): Artist
    {
        /** @var Artist $item */
        $item = $this->getContext()->item;

        return $item;
    }
}

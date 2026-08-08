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
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Override;

/**
 * The song edit dialog.
 *
 * Its MusicBrainz id and every custom metadata value reached their `value=` attribute unescaped, and
 * metadata is arbitrary text read from a file's tags.
 */
final class SongEditFormRenderer extends AbstractEditFormRenderer
{
    /**
     * Every artist on the song except the one it is credited to.
     */
    public function getAdditionalArtists(): string
    {
        return Artist::get_display(array_diff($this->getItem()->get_artists(), [$this->getItem()->artist]));
    }

    public function getAlbumId(): int
    {
        return (int) $this->getItem()->album;
    }

    public function getArtistId(): int
    {
        return (int) $this->getItem()->artist;
    }

    public function getComment(): string
    {
        return (string) $this->getItem()->comment;
    }

    public function getComposer(): string
    {
        return (string) $this->getItem()->composer;
    }

    public function getDisk(): string
    {
        return (string) $this->getItem()->disk;
    }

    public function getGenres(): string
    {
        return Tag::get_display(Tag::get_top_tags('song', $this->getSongId(), 0));
    }

    public function getLabel(): string
    {
        return (string) $this->getItem()->label;
    }

    public function getLicenseId(): int
    {
        return (int) $this->getItem()->license;
    }

    public function getMbid(): string
    {
        return (string) $this->getItem()->mbid;
    }

    /**
     * Only public fields the admin has not disabled are offered.
     *
     * @return list<array{id: int, label: string, value: string}>
     */
    public function getMetadata(): array
    {
        $manager  = $this->getContext()->metadataManager;
        $disabled = $manager->getDisabledMetadataFields();
        $fields   = [];
        foreach ($manager->getMetadata($this->getItem()) as $metadata) {
            $field = $metadata->getField();
            if ($field === null || !$field->isPublic() || in_array($field->getName(), $disabled)) {
                continue;
            }

            $fields[] = [
                'id' => $metadata->getId(),
                'label' => ucwords(str_replace('_', ' ', $field->getName())),
                'value' => (string) $metadata->getData(),
            ];
        }

        return $fields;
    }

    public function getOwnerId(): int
    {
        return (int) $this->getItem()->user_upload;
    }

    public function getSongId(): int
    {
        return $this->getItem()->getId();
    }

    public function getTitle(): string
    {
        return (string) $this->getItem()->title;
    }

    public function getTrack(): string
    {
        return (string) $this->getItem()->track;
    }

    /**
     * @return array<int, string>
     */
    public function getUsers(): array
    {
        return $this->getContext()->users;
    }

    public function getYear(): string
    {
        return (string) $this->getItem()->year;
    }

    /**
     * The artist, album and owner controls need manage rights; the MusicBrainz id asks for more.
     */
    public function mayEdit(): bool
    {
        $user = Core::get_global('user');

        return $user instanceof User && Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER, $user->getId());
    }

    public function mayEditMbid(): bool
    {
        return Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER);
    }

    public function mayReassign(): bool
    {
        $user = Core::get_global('user');

        return $this->mayEdit() || ($user instanceof User && $user->getId() === $this->getItem()->get_user_owner());
    }

    public function showAdditionalArtists(): bool
    {
        return count($this->getItem()->get_artists()) > 1;
    }

    public function showLicense(): bool
    {
        return (bool) AmpConfig::get('licensing');
    }

    public function showMetadata(): bool
    {
        return $this->getContext()->metadataManager->isCustomMetadataEnabled();
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('edit/song.phtml');
    }

    private function getItem(): Song
    {
        /** @var Song $item */
        $item = $this->getContext()->item;

        return $item;
    }
}

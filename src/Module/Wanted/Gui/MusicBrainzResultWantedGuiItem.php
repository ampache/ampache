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

namespace Ampache\Module\Wanted\Gui;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax;
use Ampache\Repository\Model\Artist;
use stdClass;

final class MusicBrainzResultWantedGuiItem implements WantedUiItemInterface
{
    private ?Artist $artist;

    private stdClass $musicBrainzResult;

    private string $musicBrainzArtistId;

    private ?string $artistLink;

    public function __construct(
        ?Artist $artist,
        stdClass $musicBrainzResult,
        string $musicBrainzArtistId,
        ?string $artistLink = null
    ) {
        $this->musicBrainzResult   = $musicBrainzResult;
        $this->artist              = $artist;
        $this->musicBrainzArtistId = $musicBrainzArtistId;
        $this->artistLink          = $artistLink;
    }

    public function isAccepted(): bool
    {
        return false;
    }

    public function getYear(): int
    {
        if (!empty($this->musicBrainzResult->{'first-release-date'})) {
            if (strlen((string) $this->musicBrainzResult->{'first-release-date'}) == 4) {
                return (int) $this->musicBrainzResult->{'first-release-date'};
            } else {
                return (int) date("Y", strtotime($this->musicBrainzResult->{'first-release-date'}));
            }
        }

        return 0;
    }

    public function getArtistId(): ?int
    {
        if ($this->artist !== null) {
            return $this->artist->getId();
        }

        return null;
    }

    public function getArtistMusicBrainzId(): ?string
    {
        return $this->musicBrainzArtistId;
    }

    public function getLink(): string
    {
        $link = AmpConfig::get('web_path') . "/albums.php?action=show_missing&mbid=" . $this->musicBrainzResult->id;

        if ($this->artist !== null) {
            $link .= "&artist=" . $this->artist->getId();
        } else {
            $link .= "&artist_mbid=" . $this->musicBrainzArtistId;
        }

        return sprintf(
            "<a href=\"%s\" title=\"%s\">%s</a>",
            $link,
            $this->getName(),
            $this->getName()
        );
    }

    public function getArtistLink(): string
    {
        if ($this->artist !== null) {
            return $this->artist->f_link;
        }

        return $this->artistLink;
    }

    public function getName(): string
    {
        return $this->musicBrainzResult->title;
    }

    public function getUserName(): string
    {
        return '';
    }

    public function getActionButtons(): string
    {
        return Ajax::button(
            sprintf(
                "?page=index&action=add_wanted&mbid=%s%s&name=%s&year=%d",
                $this->musicBrainzResult->id,
                $this->artist ? '&artist=' . $this->artist->getId() : '&artist_mbid=' . $this->musicBrainzArtistId,
                urlencode($this->getName()),
                $this->getYear()
            ),
            'add_wanted',
            T_('Add to wanted list'),
            'wanted_add_' . $this->musicBrainzResult->id
        );
    }

    public function getMusicBrainzId(): string
    {
        return $this->musicBrainzResult->id;
    }
}

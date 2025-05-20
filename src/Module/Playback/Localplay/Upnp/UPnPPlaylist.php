<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Playback\Localplay\Upnp;

use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Session;

class UPnPPlaylist
{
    private string $_deviceGUID;

    /** @var array<int, array{name: string, link: string}> $_songs */
    private array $_songs = [];

    private int $_current = 0;

    /**
     * UPnPPlaylist constructor.
     * Playlist is its own for each UPnP device
     */
    public function __construct(string $deviceGUID)
    {
        $this->_deviceGUID = $deviceGUID;
        $this->PlayListRead();
        if (!is_array($this->_songs)) {
            $this->Clear();
        }
    }

    public function Add(string $name, string $link): void
    {
        $this->_songs[] = ['name' => $name, 'link' => $link];
        $this->PlayListSave();
    }

    /**
     * @param $track
     */
    public function RemoveTrack($track): void
    {
        unset($this->_songs[$track - 1]);
        $this->PlayListSave();
    }

    public function Clear(): void
    {
        $this->_songs   = [];
        $this->_current = 0;
        $this->PlayListSave();
    }

    /**
     * @return array<int, array{name: string, link: string}>
     */
    public function AllItems(): array
    {
        return $this->_songs;
    }

    /**
     * @return array{name?: string, link?: string}
     */
    public function CurrentItem(): array
    {
        return $this->_songs[$this->_current] ?? [];
    }

    /**
     * CurrentPos
     */
    public function CurrentPos(): int
    {
        return $this->_current;
    }

    /**
     * Next
     */
    public function Next(): bool
    {
        if ($this->_current < count($this->_songs) - 1) {
            $this->_current++;
            $this->PlayListSave();

            return true;
        }

        return false;
    }

    /**
     * @return null|array{name: string, link: string}
     */
    public function NextItem(): ?array
    {
        if ($this->_current < count($this->_songs) - 1) {
            $nxt = $this->_current + 1;

            return $this->_songs[$nxt] ?? null;
        }

        return null;
    }

    /**
     * Prev
     */
    public function Prev(): bool
    {
        if ($this->_current > 0) {
            $this->_current--;
            $this->PlayListSave();

            return true;
        }

        return false;
    }

    /**
     * skip
     */
    public function skip(int $track_id): bool
    {
        // note that pos is started from 1 not from zero
        if (
            $track_id >= 1 &&
            $track_id <= count($this->_songs)
        ) {
            $this->_current = $track_id - 1;
            $this->PlayListSave();

            return true;
        }

        return false;
    }

    private function PlayListRead(): void
    {
        $sid      = 'upnp_pls_' . $this->_deviceGUID;
        $pls_data = json_decode(Session::read($sid), true);

        $this->_songs   = $pls_data['upnp_playlist'];
        $this->_current = $pls_data['upnp_current'];
    }

    private function PlayListSave(): void
    {
        $sid      = 'upnp_pls_' . $this->_deviceGUID;
        $pls_data = json_encode(
            [
                'upnp_playlist' => $this->_songs,
                'upnp_current' => $this->_current
            ]
        ) ?: '';
        if (!Session::exists(AccessTypeEnum::STREAM->value, $sid)) {
            Session::create(['type' => 'stream', 'sid' => $sid, 'value' => $pls_data]);
        } else {
            Session::write($sid, $pls_data);
        }
    }
}

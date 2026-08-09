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

namespace Ampache\Gui\Browse\ListRenderer;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Override;

/**
 * The localplay queue browse.
 *
 * Its rows and its empty state were both nested inside a status check, so a player that answered nothing
 * rendered an empty table with no explanation. The status now only decides which row is highlighted.
 */
final class LocalplayPlaylistListRenderer extends AbstractBrowseListRenderer
{
    private ?LocalPlay $localplay = null;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $status = null;

    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
    ) {}

    public function formatName(int $objectId, string $name): string
    {
        return $this->getLocalplay()->format_name($name, $objectId);
    }

    /**
     * @return list<array{track: string, id: int, raw?: string, name?: string|null}>
     */
    public function getTracks(): array
    {
        /** @var list<array{track: string, id: int, raw?: string, name?: string|null}> $tracks */
        $tracks = array_values($this->getContext()->objectIds);

        return $tracks;
    }

    /**
     * @param array{track: string, id: int, raw?: string, name?: string|null} $track
     */
    public function isCurrent(array $track): bool
    {
        $status = $this->getStatus();

        return isset($status['track']) && (string) $status['track'] === (string) $track['track'];
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('browse/localplay_playlist.phtml');
    }

    private function getLocalplay(): LocalPlay
    {
        if ($this->localplay === null) {
            $this->localplay = new LocalPlay((string) $this->configContainer->get('localplay_controller'));
            $this->localplay->connect();
        }

        return $this->localplay;
    }

    /**
     * @return array<string, mixed>
     */
    private function getStatus(): array
    {
        if ($this->status === null) {
            $this->status = $this->getLocalplay()->status() ?? [];
        }

        return $this->status;
    }
}

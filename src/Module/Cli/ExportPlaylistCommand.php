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
 *
 */

namespace Ampache\Module\Cli;

use Ahc\Cli\Input\Command;
use Ampache\Module\Playlist\PlaylistExporter;
use Ampache\Module\Playlist\PlaylistExporterInterface;

final class ExportPlaylistCommand extends Command
{
    private PlaylistExporterInterface $playlistExporter;

    public function __construct(
        PlaylistExporterInterface $playlistExporter
    ) {
        parent::__construct('export:playlist', T_('Export Playlists'));

        $this->playlistExporter = $playlistExporter;

        $this
            ->option('-u|--user', T_('User ID'), 'intval', -1)
            ->option('-w|--web', T_("Return remote play URL's instead of the local file"), 'boolval', false)
            ->argument('<directory>', T_('Output directory'))
            ->argument('<type>', T_("Playlist type ('albums', 'artists', 'playlists', 'smartlists'), (default: playlists)"), 'playlists')
            ->argument('[extension]', T_("Output type ('m3u', 'xspf', 'pls'), (default: m3u)"), 'm3u')
            ->argument('[playlistId]', T_("Playlist ID"), '-1')
            ->usage('<bold>  export:playlist</end> <comment>/tmp playlists m3u</end> ## ' . T_('Export playlists as m3u files to /tmp') . '<eol/>');
    }

    public function execute(
        string $type,
        string $directory,
        string $extension,
        string $playlistId
    ): void {
        if (!in_array($extension, PlaylistExporter::VALID_FILE_EXTENSIONS)) {
            $extension = current(PlaylistExporter::VALID_FILE_EXTENSIONS);
        }
        $values  = $this->values();
        $userId  = $values['user'];
        $urltype = ($values['web'])
            ? 'web'
            : 'file';

        $this->playlistExporter->export(
            $this->app()->io(),
            $directory,
            $type,
            $extension,
            $playlistId,
            $userId,
            $urltype
        );
    }
}

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
 */

namespace Ampache\Module\Song\Deletion;

use Ampache\Model\Art;
use Ampache\Model\Rating;
use Ampache\Model\Song;
use Ampache\Model\Useractivity;
use Ampache\Model\Userflag;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;

final class SongDeleter implements SongDeleterInterface
{
    private ShoutRepositoryInterface $shoutRepository;

    private SongRepositoryInterface $songRepository;

    public function __construct(
        ShoutRepositoryInterface $shoutRepository,
        SongRepositoryInterface $songRepository
    ) {
        $this->shoutRepository = $shoutRepository;
        $this->songRepository  = $songRepository;
    }

    public function delete(Song $song): bool
    {
        if (file_exists($song->file)) {
            $deleted = unlink($song->file);
        } else {
            $deleted = true;
        }
        if ($deleted === true) {
            $deleted = $this->songRepository->delete($song->id);
            if ($deleted) {
                Art::garbage_collection('song', $song->id);
                Userflag::garbage_collection('song', $song->id);
                Rating::garbage_collection('song', $song->id);
                $this->shoutRepository->collectGarbage('song', $song->id);
                Useractivity::garbage_collection('song', $song->id);
            }
        } else {
            debug_event('song.class', 'Cannot delete ' . $song->file . 'file. Please check permissions.', 1);
        }

        return $deleted;
    }
}

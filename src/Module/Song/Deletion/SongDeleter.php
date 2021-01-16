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
use Ampache\Model\Userflag;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UseractivityRepositoryInterface;

final class SongDeleter implements SongDeleterInterface
{
    private ShoutRepositoryInterface $shoutRepository;

    private SongRepositoryInterface $songRepository;

    private UseractivityRepositoryInterface $useractivityRepository;

    public function __construct(
        ShoutRepositoryInterface $shoutRepository,
        SongRepositoryInterface $songRepository,
        UseractivityRepositoryInterface $useractivityRepository
    ) {
        $this->shoutRepository        = $shoutRepository;
        $this->songRepository         = $songRepository;
        $this->useractivityRepository = $useractivityRepository;
    }

    public function delete(Song $song): bool
    {
        if (file_exists($song->file)) {
            $deleted = unlink($song->file);
        } else {
            $deleted = true;
        }
        if ($deleted === true) {
            $songId  = $song->getId();
            $deleted = $this->songRepository->delete($songId);
            if ($deleted) {
                Art::garbage_collection('song', $songId);
                Userflag::garbage_collection('song', $songId);
                Rating::garbage_collection('song', $songId);
                $this->shoutRepository->collectGarbage('song', $songId);
                $this->useractivityRepository->collectGarbage('song', $songId);
            }
        } else {
            debug_event('song.class', 'Cannot delete ' . $song->file . 'file. Please check permissions.', 1);
        }

        return $deleted;
    }
}

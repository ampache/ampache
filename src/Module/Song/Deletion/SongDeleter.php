<?php

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

namespace Ampache\Module\Song\Deletion;

use Ampache\Module\Art\ArtCleanupInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Psr\Log\LoggerInterface;

final class SongDeleter implements SongDeleterInterface
{
    private LoggerInterface $logger;

    private ShoutRepositoryInterface $shoutRepository;

    private SongRepositoryInterface $songRepository;

    private UserActivityRepositoryInterface $useractivityRepository;

    private ArtCleanupInterface $artCleanup;

    public function __construct(
        LoggerInterface $logger,
        ShoutRepositoryInterface $shoutRepository,
        SongRepositoryInterface $songRepository,
        UserActivityRepositoryInterface $useractivityRepository,
        ArtCleanupInterface $artCleanup
    ) {
        $this->logger                 = $logger;
        $this->shoutRepository        = $shoutRepository;
        $this->songRepository         = $songRepository;
        $this->useractivityRepository = $useractivityRepository;
        $this->artCleanup             = $artCleanup;
    }

    public function delete(Song $song): bool
    {
        if (!empty($song->file) && file_exists($song->file)) {
            $deleted = unlink($song->file);
        } else {
            $deleted = true;
        }
        if ($deleted === true) {
            $songId  = $song->getId();
            $deleted = $this->songRepository->delete($songId);
            if ($deleted) {
                $this->artCleanup->collectGarbageForObject('song', $songId);
                Userflag::garbage_collection('song', $songId);
                Rating::garbage_collection('song', $songId);
                $this->shoutRepository->collectGarbage('song', $songId);
                $this->useractivityRepository->collectGarbage('song', $songId);
                $this->songRepository->collectGarbage($song);
            }
        } else {
            $this->logger->critical(
                'Cannot delete ' . $song->file . ' file. Please check permissions.',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
        }

        return $deleted;
    }
}

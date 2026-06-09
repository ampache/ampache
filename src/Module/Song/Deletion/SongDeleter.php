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

final readonly class SongDeleter implements SongDeleterInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private ShoutRepositoryInterface $shoutRepository,
        private SongRepositoryInterface $songRepository,
        private UserActivityRepositoryInterface $useractivityRepository,
        private ArtCleanupInterface $artCleanup,
    ) {
    }

    public function delete(Song $song): bool
    {
        $deleted = !(!in_array($song->file, [null, '', '0'], true) && file_exists($song->file)) || unlink($song->file);

        if ($deleted) {
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
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
        }

        return $deleted;
    }
}

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

namespace Ampache\Module\User\Activity\TypeHandler;

use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;

final class AlbumActivityTypeHandler extends GenericActivityTypeHandler
{
    private UserActivityRepositoryInterface $userActivityRepository;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        UserActivityRepositoryInterface $userActivityRepository,
        ModelFactoryInterface $modelFactory
    ) {
        parent::__construct($userActivityRepository);

        $this->userActivityRepository = $userActivityRepository;
        $this->modelFactory           = $modelFactory;
    }

    public function registerActivity(
        int $objectId,
        string $objectType,
        string $action,
        int $userId,
        int $date
    ): void {
        $album = $this->modelFactory->createAlbum($objectId);
        $album->format();

        $artistName = $album->get_album_artist_fullname();
        $albumName  = $album->get_fullname(true);

        if ($artistName && $albumName) {
            $this->userActivityRepository->registerAlbumEntry(
                $userId,
                $action,
                $objectType,
                $objectId,
                $date,
                $artistName,
                $albumName,
                (string)$album->mbid_group,
                (string)$album->mbid
            );

            return;
        }

        parent::registerActivity(
            $objectId,
            $objectType,
            $action,
            $userId,
            $date
        );
    }
}

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

final class ActivityTypeHandlerMapper implements ActivityTypeHandlerMapperInterface
{
    private const MAP = [
        ActivityTypeEnum::TYPE_SONG => SongActivityTypeHandler::class,
        ActivityTypeEnum::TYPE_ALBUM => AlbumActivityTypeHandler::class,
        ActivityTypeEnum::TYPE_ARTIST => ArtistActivityTypeHandler::class,
    ];

    private UserActivityRepositoryInterface $userActivityRepository;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        UserActivityRepositoryInterface $userActivityRepository,
        ModelFactoryInterface $modelFactory
    ) {
        $this->userActivityRepository = $userActivityRepository;
        $this->modelFactory           = $modelFactory;
    }

    /**
     * Maps a certain object type to a type handler class. Returns a generic handler if
     * no specific one is available for the type
     */
    public function map(string $object_type): ActivityTypeHandlerInterface
    {
        $mapperClass = static::MAP[$object_type] ?? GenericActivityTypeHandler::class;

        return new $mapperClass(
            $this->userActivityRepository,
            $this->modelFactory
        );
    }
}

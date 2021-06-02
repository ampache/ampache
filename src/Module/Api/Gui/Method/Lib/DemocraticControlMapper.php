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

namespace Ampache\Module\Api\Gui\Method\Lib;

use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\DemocraticRepositoryInterface;
use Ampache\Repository\Model\Democratic;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;

/**
 * Maps api string commands like `vote` to an actual action
 */
final class DemocraticControlMapper implements DemocraticControlMapperInterface
{
    private ModelFactoryInterface $modelFactory;

    private DemocraticRepositoryInterface $democraticRepository;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        DemocraticRepositoryInterface $democraticRepository
    ) {
        $this->modelFactory         = $modelFactory;
        $this->democraticRepository = $democraticRepository;
    }

    public function map(string $command): ?callable
    {
        $map = [
            'vote' => function (
                Democratic $democratic,
                ApiOutputInterface $output,
                User $user,
                int $objectId
            ): string {
                $media = $this->modelFactory->createSong($objectId);

                if ($media->isNew()) {
                    throw new ResultEmptyException(
                        sprintf(T_('Not Found: %d'), $objectId)
                    );
                }
                $democratic->add_vote([[
                    'object_type' => 'song',
                    'object_id' => $objectId
                ]]);

                return $output->dict([
                    'method' => 'vote',
                    'result' => true
                ]);
            },
            'devote' => function (
                Democratic $democratic,
                ApiOutputInterface $output,
                User $user,
                int $objectId
            ): string {
                $media = $this->modelFactory->createSong($objectId);

                if ($media->isNew()) {
                    throw new ResultEmptyException(
                        sprintf(T_('Not Found: %s'), $objectId)
                    );
                }

                $democraticObjectId = $democratic->get_uid_from_object_id($objectId);
                $democratic->remove_vote($democraticObjectId);

                return $output->dict([
                    'method' => 'devote',
                    'result' => true
                ]);
            },
            'playlist' => function (
                Democratic $democratic,
                ApiOutputInterface $output,
                User $user,
                int $objectId
            ): string {
                $objects = $democratic->get_items();

                return $output->democratic($objects, $user->getId());
            },
            'play' => function (
                Democratic $democratic,
                ApiOutputInterface $output,
                User $user,
                int $objectId
            ): string {
                return $output->dict([
                    'url' => $democratic->play_url()
                ]);
            }
        ];

        return $map[$command] ?? null;
    }
}

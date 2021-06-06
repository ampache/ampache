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

declare(strict_types=0);

namespace Ampache\Module\Api\Ajax\Handler\Player;

use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\System\Core;
use Ampache\Repository\BroadcastRepositoryInterface;
use Ampache\Repository\Model\Broadcast;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class BroadcastAction implements ActionInterface
{
    private BroadcastRepositoryInterface $broadcastRepository;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        BroadcastRepositoryInterface $broadcastRepository,
        ModelFactoryInterface $modelFactory
    ) {
        $this->broadcastRepository = $broadcastRepository;
        $this->modelFactory        = $modelFactory;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $results      = [];
        $broadcast_id = Core::get_get('broadcast_id');
        if (empty($broadcast_id)) {
            $broadcast_id = $this->broadcastRepository->create(
                Core::get_global('user')->getId(),
                T_('My Broadcast')
            );
        }

        $broadcast = $this->modelFactory->createBroadcast((int) $broadcast_id);
        if ($broadcast->isNew() === false) {
            $key  = Broadcast::generate_key();
            $broadcast->update_state(true, $key);
            $results['broadcast'] = Broadcast::get_unbroadcast_link((int) $broadcast_id) . '' .
                '<script>startBroadcast(\'' . $key . '\');</script>';
        }

        return $results;
    }
}

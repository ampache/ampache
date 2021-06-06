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

namespace Ampache\Module\Api\Ajax\Handler\Index;

use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Channel\ChannelFactoryInterface;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class StartChannelAction implements ActionInterface
{
    private ModelFactoryInterface $modelFactory;

    private ChannelFactoryInterface $channelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        ChannelFactoryInterface $channelFactory
    ) {
        $this->modelFactory   = $modelFactory;
        $this->channelFactory = $channelFactory;
    }

    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $results = [];

        if (Access::check('interface', 75)) {
            ob_start();
            $channel = $this->modelFactory->createChannel((int) Core::get_request('id'));
            if ($channel->isNew() === false) {
                $channelOperator = $this->channelFactory->createChannelOperator($channel);
                if ($channelOperator->checkChannel()) {
                    $channelOperator->stopChannel();
                }
                $channelOperator->startChannel();
                sleep(1);
                echo $channel->get_channel_state();
            }
            $results['channel_state_' . Core::get_request('id')] = ob_get_clean();
        }

        return $results;
    }
}

<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Application\Channel;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\ChannelRepositoryInterface;
use Ampache\Repository\Model\Channel;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowCreateAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show_create';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    private ChannelRepositoryInterface $channelRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        ChannelRepositoryInterface $channelRepository
    ) {
        $this->configContainer   = $configContainer;
        $this->ui                = $ui;
        $this->modelFactory      = $modelFactory;
        $this->channelRepository = $channelRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::CHANNEL) === false) {
            return null;
        }

        /** @var array<string, mixed> $queryParams */
        $queryParams = $request->getParsedBody() ?? $request->getQueryParams();

        $playlistId = (int) ($queryParams['id'] ?? 0);
        $type       = $queryParams['type'] ?? null;

        $this->ui->showHeader();

        if ($type === 'playlist' && $playlistId !== 0) {
            $object = $this->modelFactory->createPlaylist($playlistId);
            if ($object->isNew() === false) {
                $object->format();

                $this->ui->show(
                    'show_add_channel.inc.php',
                    [
                        'object' => $object,
                        'newPort' => $this->channelRepository->getNextPort(Channel::DEFAULT_PORT)
                    ]
                );
            }
        }

        $this->ui->showFooter();

        return null;
    }
}

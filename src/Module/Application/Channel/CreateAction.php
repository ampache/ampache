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

declare(strict_types=0);

namespace Ampache\Module\Application\Channel;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\FormVerificatorInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Channel\ChannelCreatorInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CreateAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'create';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    private ChannelCreatorInterface $channelCreator;

    private FormVerificatorInterface $formVerificator;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        ChannelCreatorInterface $channelCreator,
        FormVerificatorInterface $formVerificator
    ) {
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
        $this->modelFactory    = $modelFactory;
        $this->channelCreator  = $channelCreator;
        $this->formVerificator = $formVerificator;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::CHANNEL) === false) {
            return null;
        }

        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) ||
            $this->formVerificator->verify($request, 'add_channel') === false
        ) {
            throw new AccessDeniedException();
        }

        $body        = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $playlistId = (int) ($queryParams['id'] ?? 0);

        $playlist = $this->modelFactory->createPlaylist($playlistId);

        if ($playlist->isNew() === true) {
            return null;
        }
        $playlist->format();

        $this->ui->showHeader();

        $created = $this->channelCreator->create(
            $body['name'] ?? '',
            $body['description'] ?? '',
            $body['url'] ?? '',
            $queryParams['type'] ?? 'playlist',
            $playlistId,
            $body['interface'] ?? '',
            (int) ($body['port'] ?? 0),
            $body['admin_password'] ?? '',
            isset($body['private']) ? 1 : 0,
            (int) ($body['max_listeners'] ?? 0),
            ($body['random'] ?? 0) ? 1 : 0,
            ($body['loop'] ?? 0) ? 1 : 0,
            $body['stream_type'] ?? '',
            (int) ($body['bitrate'] ?? 0)
        );

        if (!$created) {
            $this->ui->show(
                'show_add_channel.inc.php',
                [
                    'object' => $playlist,
                ]
            );
        } else {
            $this->ui->showConfirmation(
                T_('No Problem'),
                T_('The Channel has been created'),
                sprintf(
                    '%s/browse.php?action=channel',
                    $this->configContainer->getWebPath()
                )
            );
        }

        $this->ui->showFooter();

        return null;
    }
}

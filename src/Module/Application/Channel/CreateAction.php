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

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\Channel;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CreateAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'create';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        ModelFactoryInterface $modelFactory
    ) {
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
        $this->modelFactory    = $modelFactory;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::CHANNEL) === false) {
            return null;
        }

        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) ||
            !Core::form_verify('add_channel')
        ) {
            throw new AccessDeniedException();
        }

        /** @var Playlist $object */
        $object = $this->modelFactory->mapObjectType(
            $_REQUEST['type'],
            (int) $_REQUEST['id'],
        );

        $this->ui->showHeader();

        $created = Channel::create(
            $_REQUEST['name'],
            $_REQUEST['description'],
            $_REQUEST['url'],
            $_REQUEST['type'],
            $_REQUEST['id'],
            $_REQUEST['interface'],
            $_REQUEST['port'],
            $_REQUEST['admin_password'],
            isset($_REQUEST['private']) ? 1 : 0,
            $_REQUEST['max_listeners'],
            $_REQUEST['random'] ?: 0,
            $_REQUEST['loop'] ?: 0,
            $_REQUEST['stream_type'],
            $_REQUEST['bitrate']
        );

        if (!$created) {
            $this->ui->show(
                'show_add_channel.inc.php',
                [
                    'object' => $object
                ]
            );
        } else {
            $this->ui->showConfirmation(
                T_('No Problem'),
                T_('The Channel has been created'),
                AmpConfig::get('web_path') . '/browse.php?action=channel'
            );
        }

        $this->ui->showFooter();

        return null;
    }
}

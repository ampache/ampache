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

declare(strict_types=0);

namespace Ampache\Module\Application\Stream;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class PlayItemAction extends AbstractStreamAction
{
    public const REQUEST_KEY = 'play_item';

    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        LoggerInterface $logger,
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory
    ) {
        parent::__construct($logger, $configContainer);
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->preCheck($gatekeeper) === false) {
            return null;
        }
        $objectType = $_REQUEST['object_type'];
        $objectIds  = explode(',', Core::get_get('object_id'));

        $mediaIds = [];

        if (InterfaceImplementationChecker::is_playable_item($objectType)) {
            foreach ($objectIds as $object_id) {
                $item = $this->modelFactory->mapObjectType(
                    $objectType,
                    (int) $object_id
                );
                $mediaIds   = array_merge($mediaIds, $item->get_medias());

                if ($_REQUEST['custom_play_action']) {
                    foreach ($mediaIds as $mediaId) {
                        if (is_array($mediaId)) {
                            $mediaId['custom_play_action'] = $_REQUEST['custom_play_action'];
                        }
                    }
                }
            }
        }

        return $this->stream(
            $mediaIds,
            [],
            $this->configContainer->get(ConfigurationKeyEnum::PLAY_TYPE)
        );
    }
}

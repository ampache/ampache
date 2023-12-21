<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Application\Stream;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Core;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class PlayItemAction extends AbstractStreamAction
{
    public const REQUEST_KEY = 'play_item';

    private ConfigContainerInterface $configContainer;

    public function __construct(
        LoggerInterface $logger,
        ConfigContainerInterface $configContainer
    ) {
        parent::__construct($logger, $configContainer);
        $this->configContainer = $configContainer;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->preCheck($gatekeeper) === false) {
            return null;
        }
        $objectType = $_REQUEST['object_type'] ?? '';
        $objectIds  = explode(',', Core::get_get('object_id'));
        $mediaIds   = [];
        if (InterfaceImplementationChecker::is_playable_item($objectType)) {
            foreach ($objectIds as $object_id) {
                $className = ObjectTypeToClassNameMapper::map($objectType);
                $item      = new $className($object_id);
                $mediaIds  = array_merge($mediaIds, $item->get_medias());

                if (array_key_exists('custom_play_action', $_REQUEST)) {
                    foreach ($mediaIds as $mediaId) {
                        if (is_array($mediaId)) {
                            $mediaId['custom_play_action'] = $_REQUEST['custom_play_action'];
                        }
                    }
                }
                // record this as a 'play' to help show usage and history for playlists and streams
                if (!empty($mediaIds) && in_array($objectType, array('playlist', 'live_stream'))) {
                    $user   = Core::get_global('user');
                    $client = $_REQUEST['client'] ?? substr(Core::get_server('HTTP_USER_AGENT'), 0, 254);
                    Stats::insert($objectType, $object_id, $user->id, $client, [], 'stream', time());
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

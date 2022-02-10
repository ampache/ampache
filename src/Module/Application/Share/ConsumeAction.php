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

namespace Ampache\Module\Application\Share;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Batch\DefaultAction;
use Ampache\Module\Application\Stream\DownloadAction;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Share;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ConsumeAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'consume';

    private RequestParserInterface $requestParser;

    private ConfigContainerInterface $configContainer;

    private NetworkCheckerInterface $networkChecker;

    private ContainerInterface $dic;

    public function __construct(
        RequestParserInterface $requestParser,
        ConfigContainerInterface $configContainer,
        NetworkCheckerInterface $networkChecker,
        ContainerInterface $dic
    ) {
        $this->requestParser   = $requestParser;
        $this->configContainer = $configContainer;
        $this->networkChecker  = $networkChecker;
        $this->dic             = $dic;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        Preference::init();

        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHARE)) {
            throw new AccessDeniedException('Access Denied: sharing features are not enabled.');
        }

        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

        /**
         * If Access Control is turned on then we don't
         * even want them to be able to get to the login
         * page if they aren't in the ACL
         */
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ACCESS_CONTROL)) {
            if (!$this->networkChecker->check(AccessLevelEnum::TYPE_INTERFACE, null, AccessLevelEnum::LEVEL_GUEST)) {
                throw new AccessDeniedException(
                    sprintf(
                        'Access Denied:%s is not in the Interface Access list',
                        Core::get_server('REMOTE_ADDR')
                    )
                );
            }
        } // access_control is enabled

        $share_id = $this->requestParser->getFromRequest('id');
        $secret   = $_REQUEST['secret'];

        $share = new Share($share_id);
        if (empty($action) && $share->id) {
            if ($share->allow_stream) {
                $action = 'stream';
            } elseif ($share->allow_download) {
                $action = 'download';
            }
        }

        if (!$share->is_valid($secret, $action)) {
            throw new AccessDeniedException();
        }

        $share->save_access();
        if ($action == 'download') {
            if ($share->object_type == 'song' || $share->object_type == 'video') {
                $_REQUEST['action']                    = 'download';
                $_REQUEST['type']                      = $share->object_type;
                $_REQUEST[$share->object_type . '_id'] = $share->object_id;

                return $this->dic->get(DownloadAction::class)->run($request, $gatekeeper);
            } else {
                $_REQUEST['action'] = $share->object_type;
                $_REQUEST['id']     = $share->object_id;

                return $this->dic->get(DefaultAction::class)->run($request, $gatekeeper);
            }
        } elseif ($action == 'stream') {
            require Ui::find_template('show_share.inc.php');
        } else {
            throw new AccessDeniedException('Access Denied: unknown action.');
        }

        return null;
    }
}

<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Application\Upload;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Module\Util\Upload;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DefaultAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private AjaxUriRetrieverInterface $ajaxUriRetriever;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        AjaxUriRetrieverInterface $ajaxUriRetriever
    ) {
        $this->configContainer  = $configContainer;
        $this->ui               = $ui;
        $this->ajaxUriRetriever = $ajaxUriRetriever;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $access_level = AccessLevelEnum::tryFrom(
            (int) $this->configContainer->get(ConfigurationKeyEnum::UPLOAD_ACCESS_LEVEL)
        ) ?? AccessLevelEnum::USER;

        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_UPLOAD) === false ||
            $access_level === AccessLevelEnum::DEFAULT ||
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, $access_level) === false
        ) {
            throw new AccessDeniedException();
        }

        $upload_max = return_bytes(ini_get('upload_max_filesize'));
        $post_max   = return_bytes(ini_get('post_max_size'));
        $ajaxfs     = $this->ajaxUriRetriever->getAjaxServerUri() . '/fs.ajax.php';
        if ($post_max > 0 && ($post_max < $upload_max || $upload_max == 0)) {
            $upload_max = $post_max;
        }
        // Check to handle POST requests exceeding max post size.
        if (
            Core::get_server('CONTENT_LENGTH') > 0 &&
            $post_max > 0 &&
            Core::get_server('CONTENT_LENGTH') > $upload_max
        ) {
            Upload::rerror();

            return null;
        }

        $uploadAction = $_REQUEST['upload_action'] ?? null;
        if ($uploadAction === 'upload') {
            if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
                throw new AccessDeniedException();
            }

            Upload::process();

            return null;
        }

        $this->ui->showHeader();
        if ($this->configContainer->get(ConfigurationKeyEnum::UPLOAD_CATALOG) > 0) {
            $this->ui->show(
                'show_add_upload.inc.php',
                [
                    'upload_max' => $upload_max,
                    'ajaxfs' => $ajaxfs
                ]
            );
        } else {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            echo sprintf(T_('Not Found: %s'), 'upload_catalog') . '&nbsp' . "<a href=\"https://github.com/ampache/ampache/wiki/upload-catalogs\" target=\"_blank\">" . T_('Help') . "</a>";
        }
        // Show the Footer
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}

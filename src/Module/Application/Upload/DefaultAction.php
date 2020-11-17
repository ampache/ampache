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

namespace Ampache\Module\Application\Upload;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Ampache\Module\Util\Upload;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DefaultAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';

    /**
     * @var ConfigContainerInterface
     */
    private ConfigContainerInterface $configContainer;

    /**
     * @var UiInterface
     */
    private UiInterface $ui;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui
    ) {
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
    }

    public function run(ServerRequestInterface $request): ?ResponseInterface
    {
        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_UPLOAD) === false ||
            !Access::check('interface', 25)
        ) {
            Ui::access_denied();

            return null;
        }

        $upload_max = return_bytes(ini_get('upload_max_filesize'));
        $post_max   = return_bytes(ini_get('post_max_size'));
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

        $uploadAction = $_REQUEST['actionp'] ?? null;
        if ($uploadAction === 'upload') {
            if (AmpConfig::get('demo_mode')) {
                Ui::access_denied();

                return null;
            }

            Upload::process();

            return null;
        }

        $this->ui->showHeader();

        require Ui::find_template('show_add_upload.inc.php');

        // Show the Footer
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}

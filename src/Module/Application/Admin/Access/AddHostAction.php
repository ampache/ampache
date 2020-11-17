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

namespace Ampache\Module\Application\Admin\Access;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AddHostAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'add_host';

    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer
    ) {
        $this->ui              = $ui;
        $this->configContainer = $configContainer;
    }

    public function run(ServerRequestInterface $request): ?ResponseInterface
    {
        if (!Access::check('interface', 100)) {
            Ui::access_denied();

            return null;
        }
        // Make sure we've got a valid form submission
        if (!Core::form_verify('add_acl', 'post')) {
            Ui::access_denied();

            return null;
        }

        $this->ui->showHeader();

        Access::create($_POST);

        // Create Additional stuff based on the type
        if (Core::get_post('addtype') == 'stream' ||
            Core::get_post('addtype') == 'all'
        ) {
            $_POST['type'] = 'stream';
            Access::create($_POST);
        }
        if (Core::get_post('addtype') == 'all') {
            $_POST['type'] = 'interface';
            Access::create($_POST);
        }

        if (!AmpError::occurred()) {
            $url = sprintf(
                '%s/admin/access.php',
                $this->configContainer->getWebPath()
            );
            show_confirmation(
                T_('No Problem'),
                T_('Your new Access Control List(s) have been created'),
                $url
            );
        } else {
            $action = 'show_add_' . Core::get_post('type');
            require_once Ui::find_template('show_add_access.inc.php');
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}

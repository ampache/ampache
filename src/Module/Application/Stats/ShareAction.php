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

namespace Ampache\Module\Application\Stats;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Share;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShareAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'share';

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        ConfigContainerInterface $configContainer
    ) {
        $this->ui              = $ui;
        $this->modelFactory    = $modelFactory;
        $this->configContainer = $configContainer;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $this->ui->showHeader();

        define('TABLE_RENDERED', 1);

        // Temporary workaround to avoid sorting on custom base requests
        define('NO_BROWSE_SORTING', true);

        $this->ui->showBoxTop(T_('Shares'));

        $text = <<<TEXT
        <div id="information_actions">
            <ul>
                <li>
                    <a href="%s/share.php?action=clean">%s %s</a>
                </li>
            </ul>
        </div>
        TEXT;

        printf(
            $text,
            $this->configContainer->getWebPath(),
            Ui::get_icon('clean', T_('Clean')),
            T_('Clean Expired Shared Objects')
        );
        $user       = Core::get_global('user');
        $object_ids = (!empty($user))
            ? Share::get_share_list($user)
            : array();
        if (!empty($object_ids)) {
            $browse = $this->modelFactory->createBrowse();
            $browse->set_type('share');
            $browse->set_static_content(true);
            $browse->save_objects($object_ids);
            $browse->show_objects($object_ids);
            $browse->store();

            $this->ui->showBoxBottom();

            show_table_render(false, true);
        } else {
            echo T_('No records found');
        }
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}

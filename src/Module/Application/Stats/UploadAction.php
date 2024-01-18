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
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UploadAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'upload';

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

        $this->ui->showBoxTop(T_('Uploads'));
        $user_id = Core::get_global('user')->id ?? 0;
        $browse  = $this->modelFactory->createBrowse();
        $browse->set_type(
            'song',
            Catalog::get_uploads_sql('song', (int)$user_id)
        );
        $browse->set_simple_browse(true);
        $browse->show_objects();
        $browse->store();

        $browse = $this->modelFactory->createBrowse();
        $browse->set_type(
            'album',
            Catalog::get_uploads_sql('album', (int)$user_id)
        );
        $browse->set_simple_browse(true);
        $browse->show_objects();
        $browse->store();

        if (!$this->configContainer->get(ConfigurationKeyEnum::UPLOAD_USER_ARTIST)) {
            $browse = $this->modelFactory->createBrowse();
            $browse->set_type(
                'artist',
                Catalog::get_uploads_sql('artist', (int)$user_id)
            );
            $browse->set_simple_browse(true);
            $browse->show_objects();
            $browse->store();
        }

        $this->ui->showBoxBottom();

        show_table_render(false, true);

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}

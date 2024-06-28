<?php

declare(strict_types=0);

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

namespace Ampache\Module\Application\Admin\Upload;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowAction extends AbstractUploadAction
{
    public const REQUEST_KEY = 'show';

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

    protected function handle(ServerRequestInterface $request): ?ResponseInterface
    {
        $this->ui->showHeader();
        $this->ui->showBoxTop(T_('Browse Uploads'));

        $browse = $this->modelFactory->createBrowse();
        $browse->set_type(
            'song',
            Catalog::get_uploads_sql('song')
        );
        $browse->set_simple_browse(true);
        $browse->show_objects();
        $browse->store();

        $browse = $this->modelFactory->createBrowse();
        $browse->set_type(
            'album',
            Catalog::get_uploads_sql('album')
        );
        $browse->set_simple_browse(true);
        $browse->show_objects();
        $browse->store();

        if (!$this->configContainer->get(ConfigurationKeyEnum::UPLOAD_USER_ARTIST)) {
            $browse = $this->modelFactory->createBrowse();
            $browse->set_type(
                'artist',
                Catalog::get_uploads_sql('artist')
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

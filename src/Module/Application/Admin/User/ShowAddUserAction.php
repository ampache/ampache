<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Application\Admin\User;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Form\AddUserFormView;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Shows the add-user form
 */
final class ShowAddUserAction extends AbstractUserAction
{
    public const string REQUEST_KEY = 'show_add_user';

    public function __construct(
        private readonly UiInterface $ui,
        private readonly ConfigContainerInterface $configContainer,
        private readonly RequestParserInterface $requestParser,
    ) {}

    protected function handle(ServerRequestInterface $request): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)) {
            return null;
        }

        $this->ui->showHeader();
        echo (new AddUserFormView(
            $this->configContainer->getWebPath('/admin'),
            $this->requestParser->getFromRequest('username'),
            $this->requestParser->getFromRequest('fullname'),
            $this->requestParser->getFromRequest('email'),
            $this->requestParser->getFromRequest('website'),
            (int) AmpConfig::get('max_upload_size'),
            (bool) AmpConfig::get('catalog_filter')
        ))->render();
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}

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

namespace Ampache\Module\Application\Admin\Filter;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DeleteAction extends AbstractFilterAction
{
    public const REQUEST_KEY = 'delete';

    private UiInterface $ui;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer
    ) {
        $this->ui              = $ui;
        $this->configContainer = $configContainer;
    }

    protected function handle(ServerRequestInterface $request): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
            return null;
        }

        $this->ui->showHeader();

        $filter_id   = (int)($request->getQueryParams()['filter_id'] ?? 0);
        $filter_name = $request->getQueryParams()['filter_name'];
        /* HINT: The name of the catalog filter */
        $warning_msg = sprintf(T_('This will permanently delete the catalog filter "%s"'), $filter_name) . '<br>' . T_('Users will be reset to the DEFAULT filter.');
        $this->ui->showConfirmation(
            T_('Are You Sure?'),
            $warning_msg,
            sprintf(
                'admin/filter.php?action=confirm_delete&amp;filter_id=%s&amp;filter_name=%s',
                $filter_id,
                $filter_name
            ),
            1,
            'delete_user'
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}

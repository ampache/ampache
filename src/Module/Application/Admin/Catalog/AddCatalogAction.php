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

namespace Ampache\Module\Application\Admin\Catalog;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\Catalog;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AddCatalogAction extends AbstractCatalogAction
{
    public const REQUEST_KEY = 'add_catalog';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer
    ) {
        parent::__construct($ui);
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
    }

    /**
     * @param int[] $catalogIds
     * @throws AccessDeniedException
     */
    protected function handle(
        ServerRequestInterface $request,
        array $catalogIds
    ): ?ResponseInterface {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
            return null;
        }

        ob_end_flush();

        if (!strlen(filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)) || filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES) == 'none') {
            AmpError::add('general', T_('Please select a Catalog type'));
        }

        if (!strlen(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES))) {
            AmpError::add('general', T_('Please enter a Catalog name'));
        }

        if (!Core::form_verify('add_catalog')) {
            throw new AccessDeniedException();
        }

        // If an error hasn't occurred
        if (!AmpError::occurred()) {
            $catalog_id = Catalog::create($_POST);
            $users      = static::getUserRepository()->getValidArray();

            if (!$catalog_id) {
                require Ui::find_template('show_add_catalog.inc.php');

                return null;
            }

            $catalogIds[] = $catalog_id;
            catalog_worker('add_to_catalog', $catalogIds, $_POST);

            $this->ui->showConfirmation(T_('No Problem'),
                T_('The Catalog creation process has started'),
                sprintf('%s/admin/catalog.php', $this->configContainer->getWebPath()),
                0,
                'confirmation',
                false
            );
        } else {
            require Ui::find_template('show_add_catalog.inc.php');
        }

        return null;
    }

    /**
     * @deprecated inject dependency
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }
}

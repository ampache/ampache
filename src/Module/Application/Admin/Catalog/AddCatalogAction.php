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

namespace Ampache\Module\Application\Admin\Catalog;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\System\AmpError;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Catalog;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AddCatalogAction extends AbstractCatalogAction
{
    public const REQUEST_KEY = 'add_catalog';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private RequestParserInterface $requestParser;

    public function __construct(
        UiInterface $ui,
        ConfigContainerInterface $configContainer,
        RequestParserInterface $requestParser
    ) {
        parent::__construct($ui);
        $this->configContainer = $configContainer;
        $this->ui              = $ui;
        $this->requestParser   = $requestParser;
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

        $body = (array)$request->getParsedBody();
        $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_SPECIAL_CHARS);
        if (
            empty($type) ||
            $type == 'none'
        ) {
            AmpError::add('general', T_('Please select a Catalog type'));
        }

        if (!strlen(htmlspecialchars($body['name'] ?? '', ENT_NOQUOTES))) {
            AmpError::add('general', T_('Please enter a Catalog name'));
        }

        if (!$this->requestParser->verifyForm('add_catalog')) {
            throw new AccessDeniedException();
        }

        // If an error hasn't occurred
        if (!AmpError::occurred()) {
            $catalog_id = Catalog::create($_POST);

            if (!$catalog_id) {
                $this->ui->show('show_add_catalog.inc.php');

                return null;
            }

            // Add catalog to filter table
            Catalog::add_catalog_filter_group_map($catalog_id);

            $catalogIds[] = $catalog_id;
            catalog_worker('add_to_catalog', $catalogIds, $_POST);

            $this->ui->showConfirmation(
                T_('No Problem'),
                T_('The Catalog creation process has started'),
                sprintf('%s/admin/catalog.php', $this->configContainer->getWebPath(false)),
                0,
                'confirmation',
                false
            );
        } else {
            $this->ui->show('show_add_catalog.inc.php');
        }

        return null;
    }
}

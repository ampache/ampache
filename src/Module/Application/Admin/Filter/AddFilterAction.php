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

namespace Ampache\Module\Application\Admin\Filter;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Form\AddCatalogFilterFormView;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\System\AmpError;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\CatalogFilterRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AddFilterAction extends AbstractFilterAction
{
    public const string REQUEST_KEY = 'add_filter';

    public function __construct(
        private readonly UiInterface $ui,
        private readonly ConfigContainerInterface $configContainer,
        private readonly RequestParserInterface $requestParser,
        private readonly CatalogFilterRepositoryInterface $catalogFilterRepository,
    ) {}

    protected function handle(ServerRequestInterface $request): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)) {
            return null;
        }

        if (!$this->requestParser->verifyForm('add_filter')) {
            throw new AccessDeniedException();
        }

        $body = (array) $request->getParsedBody();

        $this->ui->showHeader();

        $filter_name = scrub_in(htmlspecialchars($body['name'] ?? '', ENT_NOQUOTES));
        if (empty($filter_name)) {
            AmpError::add('name', T_('A filter name is required'));
        }

        // make sure the filter doesn't already exist
        if ($this->catalogFilterRepository->groupNameExists($filter_name)) {
            AmpError::add('name', T_('That name already exists'));
        }

        // If we've got an error then show add form!
        if (AmpError::occurred()) {
            echo (new AddCatalogFilterFormView(
                $this->configContainer->getWebPath('/admin'),
                $filter_name,
                (bool) AmpConfig::get('catalog_filter')
            ))->render();

            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        $catalogs = Catalog::get_all_catalogs();

        /** @var array<int, int> $catalog_array */
        $catalog_array = [];

        foreach ($catalogs as $catalog_id) {
            $catalog_array[$catalog_id] = (int) filter_input(INPUT_POST, 'catalog_' . $catalog_id, FILTER_SANITIZE_NUMBER_INT);
        }

        // Attempt to create the filter
        if (!$this->catalogFilterRepository->createGroupWithCatalogs($filter_name, $catalog_array)) {
            AmpError::add('general', T_("The new filter was not created"));
        }

        $this->ui->showConfirmation(
            T_('New Filter Added'),
            sprintf(T_('%1$s has been created'), $filter_name),
            sprintf('%s/filter.php', $this->configContainer->getWebPath('/admin'))
        );

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}

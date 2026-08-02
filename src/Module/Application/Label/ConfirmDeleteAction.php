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

namespace Ampache\Module\Application\Label;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Label\Deletion\LabelDeleterInterface;
use Ampache\Module\Util\DeletionUrlResolverInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ConfirmDeleteAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'confirm_delete';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private UiInterface $ui,
        private LabelDeleterInterface $labelDeleter,
        private LabelRepositoryInterface $labelRepository,
        private DeletionUrlResolverInterface $deletionUrlResolver,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)) {
            $this->ui->showHeader();
            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::LABEL)) {
            throw new AccessDeniedException('Access Denied: label features are not enabled.');
        }

        $body    = $request->getQueryParams();
        $labelId = (int) ($body['label_id'] ?? 0);

        $label = $this->labelRepository->findById($labelId);
        if (
            $label === null
            || !Catalog::can_remove($label)
        ) {
            throw new AccessDeniedException(
                sprintf('Unauthorized to remove the label `%s`', $labelId)
            );
        }

        // A label has no parent object, so leaving its own page can only fall back to the label browser.
        $webPath     = $this->configContainer->getWebPath('/client');
        $burlParam   = (string) ($body['burl'] ?? '');
        $continueUrl = $this->deletionUrlResolver->resolveContinueUrl(
            $this->deletionUrlResolver->resolveBurl($burlParam),
            'label',
            $labelId,
            '',
            sprintf('%s/browse.php?action=label', $webPath)
        );

        $this->labelDeleter->delete($label);

        $this->ui->showHeader();
        $this->ui->showConfirmation(
            T_('No Problem'),
            T_('The Label has been deleted'),
            $continueUrl
        );
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}

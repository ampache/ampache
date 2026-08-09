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

namespace Ampache\Module\Application\Collection;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Form\CreateCollectionFormView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\Model\Collection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Creates an empty collection owned by the current user
 *
 * Members are added afterwards, so this only has to settle the name, who can see it, and whether it is pinned
 * to a single type.
 */
final readonly class CreateAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'create';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private CollectionRepositoryInterface $collectionRepository,
        private RequestParserInterface $requestParser,
        private UiInterface $ui,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $user = $gatekeeper->getUser();
        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHOW_COLLECTION) === false
            || $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) === false
            || $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)
            || $user === null
            || !$this->requestParser->verifyForm('add_collection')
        ) {
            throw new AccessDeniedException();
        }

        $this->ui->showHeader();

        $name = $this->requestParser->getFromRequest('name');
        // A nameless collection cannot be told apart from any other in a list, so it is refused
        if ($name === '') {
            AmpError::add('name', T_('Name is required'));
        }

        // Empty leaves the collection mixed; anything else must be a type a collection can actually hold
        $objectType = $this->requestParser->getFromRequest('object_type');
        if ($objectType !== '' && !Collection::isValidType($objectType)) {
            AmpError::add('object_type', T_('Not a valid item type'));
            $objectType = '';
        }

        if (AmpError::occurred()) {
            echo $this->createFormView()->render();
            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        $collectionId = $this->collectionRepository->create(
            $name,
            $user,
            ($this->requestParser->getFromRequest('type') === 'public') ? 'public' : 'private',
            ($objectType === '') ? null : $objectType
        );

        if ($collectionId === null) {
            AmpError::add('name', T_('Failed to create collection'));
            echo $this->createFormView()->render();
        } else {
            // Straight to the new collection, because the next thing to do is put something in it
            $this->ui->showConfirmation(
                T_('Collection created'),
                $name,
                sprintf('%s/collection.php?action=show&collection=%d', $this->configContainer->getWebPath('/client'), $collectionId)
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }

    private function createFormView(): CreateCollectionFormView
    {
        return new CreateCollectionFormView(
            $this->configContainer->getWebPath(),
            $this->requestParser->getFromRequest('name'),
            $this->requestParser->getFromRequest('type') ?: 'private',
            $this->requestParser->getFromRequest('object_type')
        );
    }
}

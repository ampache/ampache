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

namespace Ampache\Module\Api\Edit;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\ShareRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractEditAction implements ApplicationActionInterface
{
    private BrowseFactoryInterface $browseFactory;
    private ConfigContainerInterface $configContainer;
    private LibraryItemLoaderInterface $libraryItemLoader;
    private LoggerInterface $logger;
    private ShareRepositoryInterface $shareRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        LibraryItemLoaderInterface $libraryItemLoader,
        LoggerInterface $logger,
        ShareRepositoryInterface $shareRepository,
        BrowseFactoryInterface $browseFactory,
    ) {
        $this->browseFactory     = $browseFactory;
        $this->configContainer   = $configContainer;
        $this->libraryItemLoader = $libraryItemLoader;
        $this->logger            = $logger;
        $this->shareRepository   = $shareRepository;
    }

    public function run(
        ServerRequestInterface $request,
        GuiGatekeeperInterface $gatekeeper,
    ): ?ResponseInterface {
        $body   = (array) $request->getParsedBody();
        $query  = $request->getQueryParams();
        $action = $this->readString($body, 'action') ?: $this->readString($query, 'action');

        $this->logger->debug(
            'Called for action: {' . $action . '}',
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        // the posted `type` wins and carries a row suffix (`song_row`) that the item type itself does not; the
        // stripped value is looked up as a LibraryItemEnum while the suffixed one still names a template file
        $source_object_type = $this->readType($body, 'type') ?: $this->readType($query, 'type');
        if ($source_object_type === '') {
            $source_object_type = $this->readType($query, 'object_type');
            $object_type        = $source_object_type;
        } else {
            // a type carrying no suffix strips to nothing, so it falls back to itself rather than resolving as an empty type
            $object_type = implode('_', explode('_', $source_object_type, -1)) ?: $source_object_type;
        }

        $object_id = (int) $this->readString($query, 'id');

        // source Browse
        $browse_id = (int) $this->readString($query, 'browse_id');
        $browse    = ($browse_id > 0)
            ? $this->browseFactory->create($browse_id)
            : null;

        $libitem = $this->loadItem($object_type, $object_id);
        if ($libitem === null) {
            $this->logger->warning(
                sprintf('Type `%s` with id `%d` is not an editable library item.', $object_type, $object_id),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return null;
        }

        if ($libitem instanceof Song) {
            $libitem->fill_ext_info();
        }

        $user  = $gatekeeper->getUser();
        $level = AccessLevelEnum::CONTENT_MANAGER;
        if ($user !== null && $libitem->get_user_owner() == $user->getId()) {
            $level = AccessLevelEnum::USER;
        }
        if ($action === 'show_edit_playlist') {
            $level = AccessLevelEnum::USER;
        }

        // Make sure they got them rights
        if (!$gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, $level) || $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
            return null;
        }

        return $this->handle($request, $gatekeeper, $source_object_type, $libitem, $object_id, $browse);
    }

    abstract protected function handle(
        ServerRequestInterface $request,
        GuiGatekeeperInterface $gatekeeper,
        string $object_type,
        library_item|Share $libitem,
        int $object_id,
        ?Browse $browse = null,
    ): ?ResponseInterface;

    /**
     * Resolves a posted object type and id to a real item, or null when the type is not editable or the row is gone.
     *
     * `share` is the one editable type that is not a `library_item`, so it has to come from its own repository.
     */
    private function loadItem(string $objectType, int $objectId): library_item|Share|null
    {
        if ($objectType === 'share') {
            return $this->shareRepository->findById($objectId);
        }

        $itemType = LibraryItemEnum::tryFrom($objectType);

        return ($itemType instanceof LibraryItemEnum)
            ? $this->libraryItemLoader->load($itemType, $objectId)
            : null;
    }

    /**
     * Reads a request value that is only ever a scalar, so an array-valued parameter cannot reach a string sink.
     *
     * @param array<array-key, mixed> $source
     */
    private function readString(array $source, string $key): string
    {
        $value = $source[$key] ?? '';

        return (is_string($value)) ? $value : '';
    }

    /**
     * Reads an object type, narrowed to the characters a type name and its template file are allowed to contain.
     *
     * @param array<array-key, mixed> $source
     */
    private function readType(array $source, string $key): string
    {
        return strtolower((string) preg_replace('/[^A-Za-z0-9_]/', '', $this->readString($source, $key)));
    }
}

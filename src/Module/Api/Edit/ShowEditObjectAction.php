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
use Ampache\Gui\Edit\EditFormContext;
use Ampache\Gui\Edit\EditFormRendererLocatorInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Metadata\MetadataManagerInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\Share;
use Ampache\Repository\ShareRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final class ShowEditObjectAction extends AbstractEditAction
{
    public const string REQUEST_KEY = 'show_edit_object';

    private EditFormRendererLocatorInterface $editFormRendererLocator;
    private MetadataManagerInterface $metadataManager;
    private ResponseFactoryInterface $responseFactory;
    private StreamFactoryInterface $streamFactory;
    private UserRepositoryInterface $userRepository;
    private ZipHandlerInterface $zipHandler;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer,
        LibraryItemLoaderInterface $libraryItemLoader,
        LoggerInterface $logger,
        ShareRepositoryInterface $shareRepository,
        BrowseFactoryInterface $browseFactory,
        UserRepositoryInterface $userRepository,
        MetadataManagerInterface $metadataManager,
        ZipHandlerInterface $zipHandler,
        EditFormRendererLocatorInterface $editFormRendererLocator,
    ) {
        parent::__construct($configContainer, $libraryItemLoader, $logger, $shareRepository, $browseFactory);
        $this->editFormRendererLocator = $editFormRendererLocator;
        $this->responseFactory         = $responseFactory;
        $this->streamFactory           = $streamFactory;
        $this->userRepository          = $userRepository;
        $this->metadataManager         = $metadataManager;
        $this->zipHandler              = $zipHandler;
    }

    protected function handle(
        ServerRequestInterface $request,
        GuiGatekeeperInterface $gatekeeper,
        string $object_type,
        library_item|Share $libitem,
        int $object_id,
        ?Browse $browse = null,
    ): ResponseInterface {
        $users     = $this->userRepository->getValidArray();
        $users[-1] = T_('System');

        // every form renders through its own view; a type with no renderer has no dialog to show
        $renderer = $this->editFormRendererLocator->find($object_type);
        if ($renderer === null) {
            $this->logger->warning(
                'show_edit_object: no renderer for type {' . $object_type . '}',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            $results = '';
        } else {
            $results = $renderer->renderForm(
                new EditFormContext($object_type, $libitem, $users, $this->metadataManager, $this->zipHandler)
            );
        }

        return $this->responseFactory->createResponse()
            ->withBody(
                $this->streamFactory->createStream((string) $results)
            );
    }
}

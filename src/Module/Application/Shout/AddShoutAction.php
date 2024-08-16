<?php

declare(strict_types=1);

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

namespace Ampache\Module\Application\Shout;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Shout\ShoutCreatorInterface;
use Ampache\Module\Shout\ShoutObjectLoaderInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\LibraryItemEnum;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode;

/**
 * Creates a new shout for an item
 */
final class AddShoutAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'add_shout';

    private ResponseFactoryInterface $responseFactory;

    private ConfigContainerInterface $configContainer;

    private ShoutCreatorInterface $shoutCreator;

    private RequestParserInterface $requestParser;
    private ShoutObjectLoaderInterface $shoutObjectLoader;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        ConfigContainerInterface $configContainer,
        ShoutCreatorInterface $shoutCreator,
        RequestParserInterface $requestParser,
        ShoutObjectLoaderInterface $shoutObjectLoader
    ) {
        $this->responseFactory   = $responseFactory;
        $this->configContainer   = $configContainer;
        $this->shoutCreator      = $shoutCreator;
        $this->requestParser     = $requestParser;
        $this->shoutObjectLoader = $shoutObjectLoader;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $user = $gatekeeper->getUser();

        // Must be at least a user to do this
        if (
            $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) === false ||
            !$this->requestParser->verifyForm('add_shout') ||
            $user === null
        ) {
            throw new AccessDeniedException();
        }

        $body       = (array)$request->getParsedBody();
        $objectType = LibraryItemEnum::from($body['object_type'] ?? '');
        $objectId   = (int) ($body['object_id'] ?? 0);
        $text       = $body['comment'] ?? '';
        $isSticky   = array_key_exists('sticky', $body);

        // `data` is only used to mark a song offset (by clicking on the waveform)
        $songOffset = (int) ($body['data'] ?? 0);

        $libitem = $this->shoutObjectLoader->loadByObjectType($objectType, $objectId);
        if ($libitem === null) {
            throw new ObjectNotFoundException($objectId);
        }

        $this->shoutCreator->create(
            $user,
            $libitem,
            $objectType,
            $text,
            $isSticky,
            $songOffset
        );

        return $this->responseFactory
            ->createResponse(StatusCode::FOUND)
            ->withHeader(
                'Location',
                sprintf(
                    '%s/shout.php?action=show_add_shout&type=%s&id=%d',
                    $this->configContainer->getWebPath('/client'),
                    $objectType->value,
                    $objectId
                )
            );
    }
}

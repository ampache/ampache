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

namespace Ampache\Module\Application\Image;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Authentication\AuthenticationManagerInterface;
use Ampache\Module\Util\Horde_Browser;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final readonly class ShowUserAvatarAction extends AbstractShowAction
{
    public const REQUEST_ACTION = 'show_user_avatar';

    public function __construct(
        private UserRepositoryInterface $userRepository,
        RequestParserInterface $requestParser,
        AuthenticationManagerInterface $authenticationManager,
        ConfigContainerInterface $configContainer,
        Horde_Browser $horde_browser,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $requestParser,
            $authenticationManager,
            $configContainer,
            $horde_browser,
            $responseFactory,
            $streamFactory,
            $logger
        );
    }

    /**
     * @return array{
     *  0: string,
     *  1: int,
     *  2: string
     * }
     */
    protected function getFileName(
        ServerRequestInterface $request,
    ): array {
        $objectId = (int) ($request->getQueryParams()['object_id'] ?? 0);

        $user = $this->userRepository->findById($objectId);

        return [
            (string) $user?->username,
            $objectId,
            'user',
        ];
    }
}

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

namespace Ampache\Module\Api\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Catalog\CountableTableEnum;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShareRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Deletes an existing share
 */
final class ShareDeleteMethod implements MethodInterface
{
    public const string ACTION = 'share_delete';

    public const string REST_ACTION = 'shares_delete';

    private ConfigContainerInterface $configContainer;
    private ShareRepositoryInterface $shareRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ShareRepositoryInterface $shareRepository,
    ) {
        $this->configContainer = $configContainer;
        $this->shareRepository = $shareRepository;
    }

    /**
     * MINIMUM_API_VERSION=420000
     *
     * Delete an existing share.
     *
     * filter = (string) UID of share to delete
     *
     * @param array{
     *     filter?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessDeniedException|RequestParamMissingException|ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!$this->configContainer->get(ConfigurationKeyEnum::SHARE)) {
            throw new AccessDeniedException(
                'Enable: share'
            );
        }

        if (!array_key_exists('filter', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filter')
            );
        }

        $objectId = (int) $input['filter'];

        $share = $this->shareRepository->findById($objectId);
        if (
            $share === null
            || !$share->isAccessible($user)
        ) {
            throw new ResultEmptyException(
                (string) $objectId
            );
        }

        $this->shareRepository->delete($share);

        Catalog::count_table(CountableTableEnum::SHARE);

        $response->getBody()->write(
            $output->success($apiVersion, 'share ' . $objectId . ' deleted')
        );

        return $response;
    }
}

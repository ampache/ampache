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
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShareRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Updates the details of an existing share
 */
final class ShareEditMethod implements MethodInterface
{
    public const string ACTION = 'share_edit';

    public const string REST_ACTION = 'shares_edit';

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
     * Update the description and/or expiration date for an existing share.
     * Takes the share id to update with optional description and expires parameters.
     *
     * filter      = (string) Alpha-numeric search term
     * stream      = (boolean) 0,1 //optional
     * download    = (boolean) 0,1 //optional
     * expires     = (integer) number of whole days before expiry //optional
     * description = (string) update description //optional
     *
     * @param array{
     *     filter?: string,
     *     stream?: int,
     *     download?: int,
     *     expires?: int,
     *     description?: string,
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

        $shareId = (string) $input['filter'];
        $share   = $this->shareRepository->findById((int) $shareId);

        if (
            $share === null
            || !$share->isAccessible($user)
        ) {
            throw new ResultEmptyException(
                $shareId
            );
        }

        $description = (isset($input['description'])) ? htmlspecialchars((string) $input['description']) : $share->description;
        $stream      = (isset($input['stream'])) ? filter_var($input['stream'], FILTER_SANITIZE_NUMBER_INT) : $share->allow_stream;
        $download    = (isset($input['download'])) ? filter_var($input['download'], FILTER_SANITIZE_NUMBER_INT) : $share->allow_download;
        $expires     = (isset($input['expires'])) ? filter_var($input['expires'], FILTER_SANITIZE_NUMBER_INT) : $share->expire_days;

        $data = [
            'max_counter' => $share->max_counter,
            'expire' => $expires,
            'allow_stream' => $stream,
            'allow_download' => $download,
            'description' => $description,
        ];

        if (!$share->update($data, $user)) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $shareId),
                    self::ACTION,
                    'system'
                )
            );

            return $response;
        }

        $response->getBody()->write(
            $output->success($apiVersion, 'share ' . $shareId . ' updated')
        );

        return $response;
    }
}

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
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns a single label based on the UID of said label.
 *
 * The parameters and checks are identical for api versions 6 and 8, so a single method serves
 * both. Only the output data is version specific and that is resolved by the ApiOutputInterface.
 */
final class LabelMethod implements MethodInterface
{
    public const string ACTION = 'label';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private LabelRepositoryInterface $labelRepository,
    ) {}

    /**
     * MINIMUM_API_VERSION=420000
     *
     * This returns a single label based on UID
     *
     * filter = (string) UID of label
     *
     * @param array{
     *     filter?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 6|8 $apiVersion
     * @throws RequestParamMissingException|ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!$this->configContainer->get(ConfigurationKeyEnum::LABEL)) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::ACCESS_DENIED,
                    'Enable: label',
                    self::ACTION,
                    'system'
                )
            );

            return $response;
        }

        if (!array_key_exists('filter', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filter')
            );
        }

        $objectId = (int) $input['filter'];

        $label = $this->labelRepository->findById($objectId);
        if ($label === null) {
            throw new ResultEmptyException((string) $objectId);
        }

        $response->getBody()->write(
            $output->labels($apiVersion, [$objectId], $user, false)
        );

        return $response;
    }
}

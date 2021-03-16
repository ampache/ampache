<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Module\Api\Gui\Method;

use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\PreferenceRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class PreferenceDeleteMethod implements MethodInterface
{
    public const ACTION = 'preference_delete';

    private StreamFactoryInterface $streamFactory;

    private PreferenceRepositoryInterface $preferenceRepository;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        PreferenceRepositoryInterface $preferenceRepository
    ) {
        $this->streamFactory        = $streamFactory;
        $this->preferenceRepository = $preferenceRepository;
    }

    /**
     * MINIMUM_API_VERSION=5.0.0
     *
     * Delete a non-system preference by name
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter = (string) Preference name e.g ('notify_email', 'ajax_load')
     *
     * @return ResponseInterface
     *
     * @throws RequestParamMissingException
     * @throws AccessDeniedException
     * @throws ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        $preferenceName = $input['filter'] ?? null;

        if ($preferenceName === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'filter')
            );
        }

        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN) === false) {
            throw new AccessDeniedException(T_('Require: 100'));
        }

        $preference = $this->preferenceRepository->get($preferenceName, -1);
        if ($preference === []) {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %s'), $preferenceName)
            );
        }

        // Might be a good idea to not delete system preferences
        if (in_array($preferenceName, Preference::SYSTEM_LIST)) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), $preferenceName)
            );
        }

        $this->preferenceRepository->deleteByName($preferenceName);

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->success(
                    sprintf('Deleted: %s', $preferenceName)
                )
            )
        );
    }
}

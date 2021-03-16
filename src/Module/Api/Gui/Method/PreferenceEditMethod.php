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
use Ampache\Module\Preference\UserPreferenceRetrieverInterface;
use Ampache\Module\Preference\UserPreferenceUpdaterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class PreferenceEditMethod implements MethodInterface
{
    public const ACTION = 'preference_edit';

    private StreamFactoryInterface $streamFactory;

    private UserPreferenceUpdaterInterface $userPreferenceUpdater;

    private UserPreferenceRetrieverInterface $userPreferenceRetriever;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        UserPreferenceUpdaterInterface $userPreferenceUpdater,
        UserPreferenceRetrieverInterface $userPreferenceRetriever
    ) {
        $this->streamFactory           = $streamFactory;
        $this->userPreferenceUpdater   = $userPreferenceUpdater;
        $this->userPreferenceRetriever = $userPreferenceRetriever;
    }

    /**
     * MINIMUM_API_VERSION=5.0.0
     *
     * Edit a preference value and apply to all users if allowed
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter = (string) Preference name e.g ('notify_email', 'ajax_load')
     * value  = (string|integer) Preference value
     * all    = (boolean) apply to all users //optional
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
        foreach (['filter', 'value'] as $key) {
            if (!array_key_exists($key, $input)) {
                throw new RequestParamMissingException(
                    sprintf(T_('Bad Request: %s'), $key)
                );
            }
        }

        // don't apply to all when you aren't an admin
        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN) === false) {
            throw new AccessDeniedException(T_('Require: 100'));
        }

        $user = $gatekeeper->getUser();

        // fix preferences that are missing for user
        $user->fixPreferences();

        $all             = (int) ($input['all'] ?? 0) === 1;
        $preferenceName  = (string) $input['filter'];
        $userId          = $user->getId();

        $preference = $this->userPreferenceRetriever->retrieve(
            $userId,
            $preferenceName
        );

        if (empty($preference)) {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %s'), $preferenceName)
            );
        }

        $value = $input['value'];

        if (!$this->userPreferenceUpdater->update($preferenceName, $userId, $value, $all)) {
            throw new RequestParamMissingException(T_('Bad Request'));
        }

        $preference = $this->userPreferenceRetriever->retrieve(
            $userId,
            $preferenceName
        );

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->object_array([$preference], 'preference')
            )
        );
    }
}

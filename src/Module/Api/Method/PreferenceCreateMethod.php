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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\PreferenceRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class PreferenceCreateMethod implements MethodInterface
{
    public const ACTION = 'preference_create';

    private StreamFactoryInterface $streamFactory;

    private PreferenceRepositoryInterface $preferenceRepository;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        PreferenceRepositoryInterface $preferenceRepository
    ) {
        $this->streamFactory           = $streamFactory;
        $this->preferenceRepository    = $preferenceRepository;
    }

    /**
     * MINIMUM_API_VERSION=5.0.0
     *
     * Add a new preference to your server
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter      = (string) preference name
     * type        = (string) 'boolean', 'integer', 'string', 'special'
     * default     = (string|integer) default value
     * category    = (string) 'interface', 'internal', 'options', 'playlist', 'plugins', 'streaming', 'system'
     * description = (string) description of preference //optional
     * subcategory = (string) $subcategory //optional
     * level       = (integer) access level required to change the value (default 100) //optional
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
        foreach (['filter', 'type', 'default', 'category'] as $key) {
            if (!array_key_exists($key, $input)) {
                throw new RequestParamMissingException(
                    sprintf(T_('Bad Request: %s'), $key)
                );
            }
        }

        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN) === false) {
            throw new AccessDeniedException(T_('Require: 100'));
        }

        $preferenceName = (string) $input['filter'];

        $preferenceList = $this->preferenceRepository->get($preferenceName, -1);

        // if you found the preference or it's a system preference; don't add it.
        if ($preferenceList === [] || in_array($preferenceName, Preference::SYSTEM_LIST)) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), $preferenceName)
            );
        }
        $type = (string) $input['type'];
        if (!in_array($type, ['boolean', 'integer', 'string', 'special'])) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), $type)
            );
        }
        $category = (string) $input['category'];
        if (!in_array($category, ['interface', 'internal', 'options', 'playlist', 'plugins', 'streaming', 'system'])) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), $category)
            );
        }

        $level       = (isset($input['level'])) ? (int) $input['level'] : AccessLevelEnum::LEVEL_ADMIN;
        $default     = ($type == 'boolean' || $type == 'integer') ? (int) $input['default'] : (string) $input['default'];
        $description = (string) ($input['description'] ?? '');
        $subcategory = (string) ($input['subcategory'] ?? '');

        // insert and return the new preference
        $this->preferenceRepository->add(
            $preferenceName,
            $description,
            $default,
            $level,
            $type,
            $category,
            $subcategory
        );

        $preferenceList = $this->preferenceRepository->get($preferenceName, -1);
        if ($preferenceList === []) {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %s'), $preferenceName)
            );
        }

        // fix preferences that are missing for user
        $gatekeeper->getUser()->fixPreferences();

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->object_array($preferenceList, 'preference')
            )
        );
    }
}

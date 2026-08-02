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

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\System\Preference;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Adds a new preference to the server
 */
final class PreferenceCreateMethod implements MethodInterface
{
    public const string ACTION = 'preference_create';

    public const string REST_ACTION = 'preferences_create';

    private PrivilegeCheckerInterface $privilegeChecker;

    public function __construct(
        PrivilegeCheckerInterface $privilegeChecker,
    ) {
        $this->privilegeChecker = $privilegeChecker;
    }

    /**
     * MINIMUM_API_VERSION=5.0.0
     *
     * Add a new preference to your server
     *
     * filter      = (string) preference name
     * type        = (string) 'boolean', 'integer', 'string', 'special'
     * default     = (string|integer) default value
     * category    = (string) 'interface', 'internal', 'options', 'playlist', 'plugins', 'streaming'
     * description = (string) description of preference //optional
     * subcategory = (string) $subcategory //optional
     * level       = (integer) access level required to change the value (default 100) //optional
     *
     * @param array{
     *     filter?: string,
     *     type?: string,
     *     default?: string|int,
     *     category?: string,
     *     description?: string,
     *     subcategory?: string,
     *     level?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessFailedException|RequestParamMissingException|ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        foreach (['filter', 'type', 'default', 'category'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        if (
            !$this->privilegeChecker->check(
                AccessTypeEnum::INTERFACE,
                AccessLevelEnum::ADMIN,
                $user->getId()
            )
        ) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::ADMIN->value)
            );
        }

        $prefName = (string) $input['filter'];
        $prefList = Preference::get($prefName, -1);

        // if you found the preference or it's a system preference; don't add it.
        if (
            !empty($prefList)
            || in_array($prefName, array_merge(Preference::SYSTEM_LIST, Preference::PLUGIN_LIST))
        ) {
            return $this->writeBadRequest($response, $output, $apiVersion, $prefName, 'filter');
        }

        $type = (string) $input['type'];
        if (!in_array(strtolower($type), ['boolean', 'integer', 'string', 'special'])) {
            return $this->writeBadRequest($response, $output, $apiVersion, $type, 'type');
        }

        $category = (string) $input['category'];
        if (!in_array($category, ['interface', 'internal', 'options', 'playlist', 'plugins', 'streaming'])) {
            // the legacy code reports the type here, not the category
            return $this->writeBadRequest($response, $output, $apiVersion, $type, 'category');
        }

        $level       = (isset($input['level'])) ? (int) $input['level'] : 100;
        $default     = ($type === 'boolean' || $type === 'integer') ? (int) $input['default'] : (string) $input['default'];
        $description = (string) ($input['description'] ?? '');
        $subcategory = (string) ($input['subcategory'] ?? '');

        // insert and return the new preference
        Preference::insert($prefName, $description, $default, $level, $type, $category, $subcategory);

        $results = Preference::get($prefName, -1);
        if (empty($results)) {
            throw new ResultEmptyException(
                $prefName,
                'system'
            );
        }
        if ($apiVersion >= 8) {
            $results[0]['id'] = (string) $results[0]['id'];
        }

        $response->getBody()->write(
            $output->objectArray($apiVersion, $results[0], $results, 'preference')
        );

        // fix preferences that are missing for user
        Preference::fix_user_preferences($user->getId());

        return $response;
    }

    private function writeBadRequest(
        ResponseInterface $response,
        ApiOutputInterface $output,
        int $apiVersion,
        string $value,
        string $type,
    ): ResponseInterface {
        $response->getBody()->write(
            $output->error(
                $apiVersion,
                ErrorCodeEnum::BAD_REQUEST,
                sprintf('Bad Request: %s', $value),
                self::ACTION,
                $type
            )
        );

        return $response;
    }
}

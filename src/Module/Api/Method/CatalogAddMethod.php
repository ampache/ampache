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
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Catalog\Catalog_local;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Creates a new catalog
 */
final class CatalogAddMethod implements MethodInterface
{
    public const string ACTION = 'catalog_add';

    private PrivilegeCheckerInterface $privilegeChecker;

    public function __construct(
        PrivilegeCheckerInterface $privilegeChecker,
    ) {
        $this->privilegeChecker = $privilegeChecker;
    }

    /**
     * MINIMUM_API_VERSION=6.0.0
     *
     * Create a new catalog
     *
     * name           = (string) catalog_name
     * path           = (string) URL or folder path for your catalog
     * type           = (string) catalog_type default: local ('local', 'beets', 'remote', 'subsonic', 'seafile', 'beetsremote') //optional
     * media_type     = (string) Default: 'music' ('music', 'podcast', 'video') //optional
     * file_pattern   = (string) Pattern used identify tags from the file name. Default '%T - %t' //optional
     * folder_pattern = (string) Pattern used identify tags from the folder name. Default '%a/%A' //optional
     * username       = (string) login to remote catalog ('remote', 'subsonic', 'seafile') //optional
     * password       = (string) password to remote catalog ('remote', 'subsonic', 'seafile', 'beetsremote') //optional
     *
     * @param array{
     *     name?: string,
     *     path?: string,
     *     type?: string,
     *     beetsdb?: string,
     *     media_type?: string,
     *     file_pattern?: string,
     *     folder_pattern?: string,
     *     username?: string,
     *     password?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessFailedException|RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (
            !$this->privilegeChecker->check(
                AccessTypeEnum::INTERFACE,
                AccessLevelEnum::MANAGER,
                $user->getId()
            )
        ) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::MANAGER->value)
            );
        }

        foreach (['name', 'path'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        $path          = (string) $input['path'];
        $name          = (string) $input['name'];
        $type          = (string) ($input['type'] ?? 'local');
        $renamePattern = (string) ($input['file_pattern'] ?? '%T - %t');
        $sortPattern   = (string) ($input['folder_pattern'] ?? '%a/%A');
        $username      = (isset($input['username'])) ? (string) $input['username'] : null;
        $password      = (isset($input['password'])) ? (string) $input['password'] : null;
        $gatherTypes   = (string) ($input['media_type'] ?? 'music');
        if (in_array($gatherTypes, ['clip', 'tvshow', 'movie', 'personal_video'])) {
            $gatherTypes = 'video';
        }

        // confirm the correct data
        if (!in_array(strtolower($type), ['local', 'beets', 'remote', 'subsonic', 'seafile'])) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $type),
                    self::ACTION,
                    'type'
                )
            );

            return $response;
        }

        $isRemote = in_array($type, ['remote', 'subsonic', 'beetsremote', 'seafile']);
        if ($isRemote) {
            if (!$username) {
                return $this->writeBadRequest($response, $output, $apiVersion, 'username');
            }

            if (!$password) {
                return $this->writeBadRequest($response, $output, $apiVersion, 'password');
            }
        }

        $pathOk = ($isRemote)
            ? filter_var(urldecode($path), FILTER_VALIDATE_URL)
            : Catalog_local::check_path($path);
        if (!$pathOk) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $path),
                    self::ACTION,
                    'path'
                )
            );

            return $response;
        }

        $object = [
            'name' => $name,
            'path' => $path, // local, beets
            'uri' => $path, // remote, subsonic, beetsremote
            'type' => $type,
            'rename_pattern' => $renamePattern,
            'sort_pattern' => $sortPattern,
            'gather_media' => $gatherTypes,
            'username' => $username,
            'password' => $password,
        ];
        if ($type === 'seafile') {
            $object['library_name']   = $name;
            $object['server_uri']     = $path;
            $object['api_call_delay'] = 250;
        }

        if ($type === 'beetsdb') {
            $object['beetsdb'] = (string) ($input['beetsdb'] ?? '');
        }

        // create it then retrieve it
        $catalogId = Catalog::create($object);
        if ($catalogId === 0) {
            return $this->writeBadRequest($response, $output, $apiVersion, 'system');
        }

        $catalog = Catalog::create_from_id($catalogId);
        if ($catalog === null) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, null)
            );

            return $response;
        }

        $response->getBody()->write(
            $output->catalogs($apiVersion, [$catalog->getId()], $user, false)
        );

        return $response;
    }

    private function writeBadRequest(
        ResponseInterface $response,
        ApiOutputInterface $output,
        int $apiVersion,
        string $type,
    ): ResponseInterface {
        $response->getBody()->write(
            $output->error(
                $apiVersion,
                ErrorCodeEnum::BAD_REQUEST,
                'Bad Request',
                self::ACTION,
                $type
            )
        );

        return $response;
    }
}

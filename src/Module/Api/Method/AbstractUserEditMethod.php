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
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Preference;
use Ampache\Module\User\Authorization\UserKeyGeneratorInterface;
use Ampache\Module\User\UserStateTogglerInterface;
use Ampache\Module\Util\Mailer;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Updates an existing user
 *
 * The two live api versions only differ in how they name the user: version 6 reports it as
 * `username` and version 8 as `filter`, each accepting the other as an alias. The version classes
 * supply that pair of names; everything else is shared.
 */
abstract class AbstractUserEditMethod implements MethodInterface
{
    public const string ACTION = 'user_edit';

    public const string REST_ACTION = 'users_edit';

    // the alias the version prefers when both names are supplied; overridden per version
    protected const string FILTER_ALIAS = 'username';

    // the name the version reports the user under; overridden per version
    protected const string FILTER_KEY = 'filter';

    private ConfigContainerInterface $configContainer;
    private PrivilegeCheckerInterface $privilegeChecker;
    private UserKeyGeneratorInterface $userKeyGenerator;
    private UserStateTogglerInterface $userStateToggler;

    public function __construct(
        ConfigContainerInterface $configContainer,
        PrivilegeCheckerInterface $privilegeChecker,
        UserKeyGeneratorInterface $userKeyGenerator,
        UserStateTogglerInterface $userStateToggler,
    ) {
        $this->configContainer  = $configContainer;
        $this->privilegeChecker = $privilegeChecker;
        $this->userKeyGenerator = $userKeyGenerator;
        $this->userStateToggler = $userStateToggler;
    }

    /**
     * MINIMUM_API_VERSION=6.0.0
     *
     * Update an existing user.
     * Takes the username with optional parameters.
     *
     * maxbitrate is bits per second from API8 onwards, matching every other rate argument. API6 sends kbps.
     *
     * @param array{
     *     filter?: int|string,
     *     username?: string,
     *     fullname?: string,
     *     password?: string,
     *     email?: string,
     *     website?: string,
     *     state?: string,
     *     city?: string,
     *     disable?: int,
     *     group?: int,
     *     maxbitrate?: int,
     *     fullname_public?: int,
     *     reset_apikey?: int,
     *     reset_streamtoken?: int,
     *     clear_stats?: int,
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
                AccessLevelEnum::ADMIN,
                $user->getId()
            )
        ) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::ADMIN->value)
            );
        }

        $username = $input[static::FILTER_ALIAS] ?? $input[static::FILTER_KEY] ?? null;
        if ($username === null) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', static::FILTER_KEY)
            );
        }

        // identify the user to modify
        $updateUser = (is_numeric($username))
            ? User::get_from_id((int) $username)
            : User::get_from_username((string) $username);
        if ($updateUser === null) {
            return $this->writeBadRequest($response, $output, $apiVersion, (string) $username);
        }

        $password = $input['password'] ?? null;

        // an admin password cannot be changed through the api
        if ($password && $updateUser->access === AccessLevelEnum::ADMIN->value) {
            return $this->writeBadRequest($response, $output, $apiVersion, (string) $username);
        }

        $userId = $updateUser->getId();
        if ($userId === 0) {
            return $this->writeBadRequest($response, $output, $apiVersion, (string) $username);
        }

        $fullname = $input['fullname'] ?? null;
        $email    = (array_key_exists('email', $input)) ? urldecode($input['email']) : null;
        $website  = (isset($input['website']))
            ? filter_var(urldecode($input['website']), FILTER_VALIDATE_URL) ?: null
            : null;
        $state              = $input['state'] ?? null;
        $city               = $input['city'] ?? null;
        $disable            = (isset($input['disable'])) ? (int) $input['disable'] : null;
        $catalogFilterGroup = $input['group'] ?? null;
        $maxBitrate         = (int) ($input['maxbitrate'] ?? 0);
        $fullnamePublic     = (isset($input['fullname_public'])) ? (bool) $input['fullname_public'] : null;
        $resetApikey        = $input['reset_apikey'] ?? null;
        $resetStreamToken   = $input['reset_streamtoken'] ?? null;
        $clearStats         = $input['clear_stats'] ?? null;

        if ($password && !$this->configContainer->get(ConfigurationKeyEnum::SIMPLE_USER_MODE)) {
            $updateUser->update_password('', $password);
        }

        if ($fullname) {
            $updateUser->update_fullname($fullname);
        }

        if ($email && Mailer::validate_address($email)) {
            $updateUser->update_email($email);
        }

        if ($website) {
            $updateUser->update_website($website);
        }

        if ($state) {
            $updateUser->update_state($state);
        }

        if ($city) {
            $updateUser->update_city($city);
        }

        if ((int) $user->disabled === 0 && $disable === 1) {
            $this->userStateToggler->disable($updateUser);
        } elseif ((int) $user->disabled === 1 && $disable === 0) {
            $this->userStateToggler->enable($updateUser);
        }

        if ($catalogFilterGroup !== null) {
            $updateUser->update_catalog_filter_group((int) $catalogFilterGroup);
        }

        if ($maxBitrate > 0) {
            // API8 takes bps to match every other rate argument; API6 and older sent kbps
            $bitrate = ($apiVersion >= 8)
                ? $maxBitrate
                : $maxBitrate * 1000;
            Preference::update('transcode_bitrate', $userId, $bitrate);
        }

        if ($fullnamePublic !== null) {
            $updateUser->update_fullname_public($fullnamePublic);
        }

        if ($resetApikey) {
            $this->userKeyGenerator->generateApikey($updateUser);
        }

        if ($resetStreamToken) {
            $this->userKeyGenerator->generateStreamToken($updateUser);
        }

        if ($clearStats) {
            Stats::clear($userId);
        }

        $response->getBody()->write(
            $output->success($apiVersion, 'successfully updated: ' . $username)
        );

        return $response;
    }

    private function writeBadRequest(
        ResponseInterface $response,
        ApiOutputInterface $output,
        int $apiVersion,
        string $username,
    ): ResponseInterface {
        $response->getBody()->write(
            $output->error(
                $apiVersion,
                ErrorCodeEnum::BAD_REQUEST,
                sprintf('Bad Request: %s', $username),
                static::ACTION,
                'system'
            )
        );

        return $response;
    }
}

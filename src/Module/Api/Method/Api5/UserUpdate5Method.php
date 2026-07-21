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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\User\UserStateTogglerInterface;
use Ampache\Module\Util\Mailer;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Updates an existing user.
 *
 * Version 5 knows a smaller set of fields than the later versions, so it keeps a method of its own.
 */
final class UserUpdate5Method implements MethodInterface
{
    public const string ACTION = 'user_update';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private PrivilegeCheckerInterface $privilegeChecker,
        private StreamFactoryInterface $streamFactory,
        private UserStateTogglerInterface $userStateToggler,
    ) {}

    /**
     * user_update
     * MINIMUM_API_VERSION=400001
     *
     * Update an existing user.
     * Takes the username with optional parameters.
     *
     * username = (string) $username
     * password = (string) hash('sha256', $password)) //optional
     * fullname = (string) $fullname //optional
     * email = (string) $email //optional
     * website = (string) $website //optional
     * state = (string) $state //optional
     * city = (string) $city //optional
     * disable = (integer) 0,1 true to disable, false to enable //optional
     * maxbitrate = (integer) $maxbitrate in kbps //optional
     *
     * @param array{
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
     * @param 5 $apiVersion
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
                $user->id
            )
        ) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::ADMIN->value)
            );
        }

        if (!array_key_exists('username', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'username')
            );
        }

        $username = $input['username'];
        $password = $input['password'] ?? null;
        $fullname = $input['fullname'] ?? null;
        $email    = (array_key_exists('email', $input)) ? urldecode($input['email']) : null;
        $website  = (isset($input['website']))
            ? filter_var(urldecode($input['website']), FILTER_VALIDATE_URL) ?: null
            : null;
        $state      = $input['state'] ?? null;
        $city       = $input['city'] ?? null;
        $disable    = (isset($input['disable'])) ? (int) $input['disable'] : null;
        $maxbitrate = (int) ($input['maxbitrate'] ?? 0);

        // identify the user to modify
        $update_user = User::get_from_username($username);
        if ($update_user === null) {
            return $this->writeBadRequest($response, $output, $apiVersion, $username, 'username');
        }

        if ($password && $update_user->access == 100) {
            return $this->writeBadRequest($response, $output, $apiVersion, $username, 'system');
        }

        $user_id = $update_user->getId();
        if ($user_id > 0) {
            if ($password && !$this->configContainer->get(ConfigurationKeyEnum::SIMPLE_USER_MODE)) {
                $update_user->update_password('', $password);
            }

            if ($fullname) {
                $update_user->update_fullname($fullname);
            }

            if ($email && Mailer::validate_address($email)) {
                $update_user->update_email($email);
            }

            if ($website) {
                $update_user->update_website($website);
            }

            if ($state) {
                $update_user->update_state($state);
            }

            if ($city) {
                $update_user->update_city($city);
            }

            if ($disable === 1) {
                $this->userStateToggler->disable($update_user);
            } elseif ($disable === 0) {
                $this->userStateToggler->enable($update_user);
            }

            if ($maxbitrate > 0) {
                // maxbitrate has always been kbps here by convention (it was never documented); transcode_bitrate is bps
                Preference::update('transcode_bitrate', $user_id, $maxbitrate * 1000);
            }

            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->success($apiVersion, 'successfully updated: ' . $username)
                )
            );
        }

        return $this->writeBadRequest($response, $output, $apiVersion, $username, 'system');
    }

    /**
     * @param 5 $apiVersion
     */
    private function writeBadRequest(
        ResponseInterface $response,
        ApiOutputInterface $output,
        int $apiVersion,
        string $value,
        string $type,
    ): ResponseInterface {
        return $response->withBody(
            $this->streamFactory->createStream(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $value),
                    self::ACTION,
                    $type
                )
            )
        );
    }
}

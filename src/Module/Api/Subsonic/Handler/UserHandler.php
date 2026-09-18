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

namespace Ampache\Module\Api\Subsonic\Handler;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Subsonic\SubsonicResponseHandlerInterface;
use Ampache\Module\Api\Subsonic_Api;
use Ampache\Module\Api\Subsonic_Json_Data;
use Ampache\Module\Api\Subsonic_Xml_Data;
use Ampache\Module\Api\SubsonicApiApplication;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Preference;
use Ampache\Module\Util\Mailer;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;

final class UserHandler implements UserHandlerInterface
{
    public function __construct(
        private readonly SubsonicResponseHandlerInterface $responseHandler,
        private readonly Subsonic_Json_Data $subsonicJsonData,
        private readonly Subsonic_Xml_Data $subsonicXmlData,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * changePassword
     *
     * Changes the password of an existing user on the server.
     * https://www.subsonic.org/pages/api.jsp#changepassword
     * @param array<string, mixed> $input
     */
    public function changepassword(array $input, User $user): void
    {
        $username = $this->responseHandler->checkParameter($input, 'username', __FUNCTION__);
        if ($username === false) {
            return;
        }

        $inp_pass = $this->responseHandler->checkParameter($input, 'password', __FUNCTION__);
        if ($inp_pass === false) {
            return;
        }

        $password = SubsonicApiApplication::decryptPassword($inp_pass);
        if ($user->username == $username || $user->access === 100) {
            $update_user = User::get_from_username((string) $username);
            if ($update_user instanceof User && !AmpConfig::get('simple_user_mode')) {
                $update_user->update_password($password);
                $this->responseHandler->responseOutput($input, __FUNCTION__);
            } else {
                $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * createUser
     *
     * Creates a new user on the server.
     * https://www.subsonic.org/pages/api.jsp#createuser
     * @param array<string, mixed> $input
     */
    public function createuser(array $input, User $user): void
    {
        $username = $this->responseHandler->checkParameter($input, 'username', __FUNCTION__);
        if ($username === false) {
            return;
        }

        $password = $this->responseHandler->checkParameter($input, 'password', __FUNCTION__);
        if ($password === false) {
            return;
        }

        $email = $this->responseHandler->checkParameter($input, 'email', __FUNCTION__);
        if ($email === false) {
            return;
        }

        $email        = urldecode($email);
        $adminRole    = (array_key_exists('adminRole', $input) && $input['adminRole'] == 'true');
        $downloadRole = (array_key_exists('downloadRole', $input) && $input['downloadRole'] == 'true');
        $uploadRole   = (array_key_exists('uploadRole', $input) && $input['uploadRole'] == 'true');
        $coverArtRole = (array_key_exists('coverArtRole', $input) && $input['coverArtRole'] == 'true');
        $shareRole    = (array_key_exists('shareRole', $input) && $input['shareRole'] == 'true');

        if ($user->access >= AccessLevelEnum::ADMIN->value) {
            $access = AccessLevelEnum::USER;
            if ($coverArtRole) {
                $access = AccessLevelEnum::MANAGER;
            }
            if ($adminRole) {
                $access = AccessLevelEnum::ADMIN;
            }
            $password = SubsonicApiApplication::decryptPassword($password);
            $user_id  = User::create($username, $username, $email, '', $password, $access);
            if ($user_id > 0) {
                if ($downloadRole) {
                    Preference::update('download', $user_id, 1);
                }
                if ($uploadRole) {
                    Preference::update('allow_upload', $user_id, 1);
                }
                if ($shareRole) {
                    Preference::update('share', $user_id, 1);
                }
                $this->responseHandler->responseOutput($input, __FUNCTION__);
            } else {
                $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * deleteUser
     *
     * Deletes an existing user on the server.
     * https://www.subsonic.org/pages/api.jsp#deleteuser
     * @param array<string, mixed> $input
     */
    public function deleteuser(array $input, User $user): void
    {
        $username = $this->responseHandler->checkParameter($input, 'username', __FUNCTION__);
        if ($username === false) {
            return;
        }

        if ($user->access === 100) {
            $update_user = User::get_from_username((string) $username);
            if ($update_user instanceof User) {
                $update_user->delete();

                $this->responseHandler->responseOutput($input, __FUNCTION__);
            } else {
                $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * getUser
     *
     * Get details about a given user, including which authorization roles and folder access it has.
     * https://www.subsonic.org/pages/api.jsp#getuser
     * @param array<string, mixed> $input
     */
    public function getuser(array $input, User $user): void
    {
        $username = $this->responseHandler->checkParameter($input, 'username', __FUNCTION__);
        if ($username === false) {
            return;
        }

        if ($user->access === 100 || $user->username == $username) {
            if ($user->username == $username) {
                $update_user = $user;
            } else {
                $update_user = User::get_from_username((string) $username);
            }
            if (!$update_user) {
                $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            } else {
                $format = (string) ($input['f'] ?? 'xml');
                if ($format === 'xml') {
                    $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
                    $response = $this->subsonicXmlData->addUser($response, $update_user);
                } else {
                    $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
                    $response = $this->subsonicJsonData->addUser($response, $update_user);
                }
                $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
            }
        } else {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * getUsers
     *
     * Get details about all users, including which authorization roles and folder access they have.
     * https://www.subsonic.org/pages/api.jsp#getusers
     * @param array<string, mixed> $input
     */
    public function getusers(array $input, User $user): void
    {
        if ($user->access !== 100) {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);

            return;
        }

        $users  = $this->userRepository->getValid();
        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addUsers($response, $users);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addUsers($response, $users);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * updateUser
     *
     * Modifies an existing user on the server.
     * https://www.subsonic.org/pages/api.jsp#updateuser
     * @param array<string, mixed> $input
     */
    public function updateuser(array $input, User $user): void
    {
        $username = $this->responseHandler->checkParameter($input, 'username', __FUNCTION__);
        if ($username === false) {
            return;
        }

        $password     = $input['password'] ?? false;
        $email        = (array_key_exists('email', $input)) ? urldecode($input['email']) : false;
        $adminRole    = (array_key_exists('adminRole', $input) && $input['adminRole'] == 'true');
        $downloadRole = (array_key_exists('downloadRole', $input) && $input['downloadRole'] == 'true');
        $uploadRole   = (array_key_exists('uploadRole', $input) && $input['uploadRole'] == 'true');
        $coverArtRole = (array_key_exists('coverArtRole', $input) && $input['coverArtRole'] == 'true');
        $shareRole    = (array_key_exists('shareRole', $input) && $input['shareRole'] == 'true');
        $maxbitrate   = (int) ($input['maxBitRate'] ?? 0);

        if ($user->access === 100) {
            $access = 25;
            if ($coverArtRole) {
                $access = 75;
            }
            if ($adminRole) {
                $access = 100;
            }
            // identify the user to modify
            $update_user = User::get_from_username((string) $username);
            if ($update_user instanceof User) {
                $user_id = $update_user->id;
                // update access level
                $update_user->update_access($access);
                // update password
                if ($password && !AmpConfig::get('simple_user_mode')) {
                    $password = SubsonicApiApplication::decryptPassword($password);
                    $update_user->update_password($password);
                }
                // update e-mail
                if ($email && Mailer::validate_address($email)) {
                    $update_user->update_email($email);
                }
                // set preferences
                if ($downloadRole) {
                    Preference::update('download', $user_id, 1);
                }
                if ($uploadRole) {
                    Preference::update('allow_upload', $user_id, 1);
                }
                if ($shareRole) {
                    Preference::update('share', $user_id, 1);
                }
                if ($maxbitrate > 0) {
                    // Subsonic maxBitRate is kbps; transcode_bitrate is stored in bps
                    Preference::update('transcode_bitrate', $user_id, $maxbitrate * 1000);
                }
                $this->responseHandler->responseOutput($input, __FUNCTION__);
            } else {
                $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }
}

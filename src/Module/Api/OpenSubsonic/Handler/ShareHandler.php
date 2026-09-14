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

namespace Ampache\Module\Api\OpenSubsonic\Handler;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\OpenSubsonic\OpenSubsonicResponseHandlerInterface;
use Ampache\Module\Api\OpenSubsonic_Api;
use Ampache\Module\Api\OpenSubsonic_Json_Data;
use Ampache\Module\Api\OpenSubsonic_Xml_Data;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Share\ShareCreatorInterface;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShareRepositoryInterface;

final class ShareHandler implements ShareHandlerInterface
{
    public function __construct(
        private readonly PasswordGeneratorInterface $passwordGenerator,
        private readonly OpenSubsonicResponseHandlerInterface $responseHandler,
        private readonly ShareCreatorInterface $shareCreator,
        private readonly ShareRepositoryInterface $shareRepository,
        private readonly OpenSubsonic_Json_Data $openSubsonicJsonData,
        private readonly OpenSubsonic_Xml_Data $openSubsonicXmlData,
    ) {}

    /**
     * createShare
     *
     * Creates a public URL that can be used by anyone to stream music or video from the server.
     * https://opensubsonic.netlify.app/docs/endpoints/createshare/
     * @param array<string, mixed> $input
     */
    public function createshare(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        if (is_array($sub_id)) {
            $object      = OpenSubsonic_Api::getAmpacheObject($sub_id[0]);
            $object_type = OpenSubsonic_Api::getAmpacheType($sub_id[0]);
        } else {
            $object      = OpenSubsonic_Api::getAmpacheObject($sub_id);
            $object_type = OpenSubsonic_Api::getAmpacheType($sub_id);
        }

        if (!$object instanceof library_item || !$object_type) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $description = $input['description'] ?? null;
        if (AmpConfig::get('share')) {
            $share_expire = AmpConfig::get('share_expire', 7);
            $expire_days  = (isset($input['expires']))
                ? Share::get_expiry(((int) filter_var($input['expires'], FILTER_SANITIZE_NUMBER_INT)) / 1000)
                : $share_expire;
            if (is_array($sub_id) && $object_type === 'song') {
                debug_event(self::class, 'createShare: sharing song list (album)', 5);
                $song_id     = OpenSubsonic_Api::getAmpacheId($sub_id[0]);
                $tmp_song    = new Song($song_id);
                $sub_id      = OpenSubsonic_Api::getAlbumSubId($tmp_song->album);
                $object      = new Album($tmp_song->album);
                $object_type = 'album';
            }
            debug_event(self::class, 'createShare: sharing ' . $object_type . ' ' . $sub_id, 4);
            if (
                !in_array(
                    $object_type,
                    [
                        'album',
                        'album_disk',
                        'artist',
                        'playlist',
                        'podcast',
                        'podcast_episode',
                        'search',
                        'song',
                        'video',
                    ]
                )
            ) {
                $object_type = '';
            }

            if (!empty($object_type) && !empty($sub_id) && !$object->isNew()) {
                $share = $this->shareCreator->create(
                    $user,
                    LibraryItemEnum::from($object_type),
                    $object->getId(),
                    true,
                    Access::check_function(AccessFunctionEnum::FUNCTION_DOWNLOAD),
                    (int) $expire_days,
                    $this->passwordGenerator->generate_token(),
                    0,
                    $description
                );
                if ($share === null) {
                    $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_GENERIC, __FUNCTION__);

                    return;
                }

                $shares = [$share];
                $format = (string) ($input['f'] ?? 'xml');
                if ($format === 'xml') {
                    $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
                    $response = $this->openSubsonicXmlData->addShares($response, $shares);
                } else {
                    $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
                    $response = $this->openSubsonicJsonData->addShares($response, $shares);
                }
                $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
            } else {
                $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * deleteShare
     *
     * Deletes an existing share.
     * https://opensubsonic.netlify.app/docs/endpoints/deleteshare/
     * @param array<string, mixed> $input
     */
    public function deleteshare(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        if (AmpConfig::get('share')) {
            $shareRepository = $this->shareRepository;

            $share_id = OpenSubsonic_Api::getAmpacheId($sub_id);
            $share    = ($share_id)
                ? $shareRepository->findById($share_id)
                : null;

            if (
                $share === null
                || !$share->isAccessible($user)
            ) {
                $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            } else {
                $shareRepository->delete($share);

                $this->responseHandler->responseOutput($input, __FUNCTION__);
            }
        } else {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * getShares
     *
     * Returns information about shared media this user is allowed to manage.
     * https://opensubsonic.netlify.app/docs/endpoints/getshares/
     * @param array<string, mixed> $input
     */
    public function getshares(array $input, User $user): void
    {
        $shares = $this->shareRepository->getIdsByUser($user);
        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addShares($response, $shares);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addShares($response, $shares);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * updateShare
     *
     * Updates the description and/or expiration date for an existing share.
     * https://opensubsonic.netlify.app/docs/endpoints/updateshare/
     * @param array<string, mixed> $input
     */
    public function updateshare(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        if (AmpConfig::get('share')) {
            $share = new Share(OpenSubsonic_Api::getAmpacheId($sub_id));
            if ($share->id > 0 && !$share->isAccessible($user)) {
                $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);
            } elseif ($share->id > 0) {
                $expires = (isset($input['expires']))
                    ? Share::get_expiry(((int) filter_var($input['expires'], FILTER_SANITIZE_NUMBER_INT)) / 1000)
                    : $share->expire_days;
                $data = [
                    'max_counter' => $share->max_counter,
                    'expire' => $expires,
                    'allow_stream' => $share->allow_stream,
                    'allow_download' => $share->allow_download,
                    'description' => $input['description'] ?? $share->description,
                ];
                if ($share->update($data, $user)) {
                    $this->responseHandler->responseOutput($input, __FUNCTION__);
                } else {
                    $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);
                }
            } else {
                $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }
}

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

use Ampache\Module\Api\Subsonic\MusicFolderResolverInterface;
use Ampache\Module\Api\Subsonic\SubsonicResponseHandlerInterface;
use Ampache\Module\Api\Subsonic_Api;
use Ampache\Module\Api\Subsonic_Json_Data;
use Ampache\Module\Api\Subsonic_Xml_Data;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\System\Preference;
use Ampache\Repository\Model\User;

final class RatingHandler implements RatingHandlerInterface
{
    public function __construct(
        private readonly MusicFolderResolverInterface $musicFolderResolver,
        private readonly SubsonicResponseHandlerInterface $responseHandler,
        private readonly Subsonic_Json_Data $subsonicJsonData,
        private readonly Subsonic_Xml_Data $subsonicXmlData,
    ) {}

    /**
     * getStarred
     *
     * Returns starred songs, albums and artists.
     * https://www.subsonic.org/pages/api.jsp#getstarred
     * @param array<string, mixed> $input
     */
    public function getstarred(array $input, User $user, string $elementName = 'starred'): void
    {
        // hide ratings and flags for other users if single user data is enabled
        $by_user     = (bool) Preference::get_by_user($user->id, 'subsonic_single_user_data') === true;
        $output_user = ($by_user)
            ? $user
            : null;

        $musicFolderId = $this->musicFolderResolver->musicFolderId($input, $user);
        $artists       = Userflag::get_latest('artist', $output_user, 10000, 0, 0, 0, $by_user, $musicFolderId);
        $albums        = Userflag::get_latest('album', $output_user, 10000, 0, 0, 0, $by_user, $musicFolderId);
        $songs         = Userflag::get_latest('song', $output_user, 10000, 0, 0, 0, $by_user, $musicFolderId);

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = ($elementName === 'starred2')
                ? $this->subsonicXmlData->addStarred2($response, $artists, $albums, $songs)
                : $this->subsonicXmlData->addStarred($response, $artists, $albums, $songs);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = ($elementName === 'starred2')
                ? $this->subsonicJsonData->addStarred2($response, $artists, $albums, $songs)
                : $this->subsonicJsonData->addStarred($response, $artists, $albums, $songs);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getStarred2
     *
     * Returns starred songs, albums and artists.
     * https://www.subsonic.org/pages/api.jsp#getstarred2
     * @param array<string, mixed> $input
     */
    public function getstarred2(array $input, User $user): void
    {
        $this->getstarred($input, $user, "starred2");
    }

    /**
     * setRating
     *
     * Sets the rating for a music file.
     * https://www.subsonic.org/pages/api.jsp#setrating
     * @param array<string, mixed> $input
     */
    public function setrating(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $rating = $this->responseHandler->checkParameter($input, 'rating', __FUNCTION__);
        if ($rating === false) {
            return;
        }

        $type = Subsonic_Api::getAmpacheType($sub_id);
        // a rating that is not a number is refused rather than cast to 0, which would silently unrate the object
        $stars = (is_numeric($rating)) ? (int) $rating : -1;
        $robj  = (!empty($type))
            ? new Rating(Subsonic_Api::getAmpacheId($sub_id), $type)
            : null;

        if ($robj != null && $stars >= 0 && $stars <= 5) {
            $robj->set_rating($stars, $user->id);

            $this->responseHandler->responseOutput($input, __FUNCTION__);
        } else {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);
        }
    }

    /**
     * star
     *
     * Attaches a star to a song, album or artist.
     * https://www.subsonic.org/pages/api.jsp#star
     * @param array<string, mixed> $input
     */
    public function star(array $input, User $user): void
    {
        $this->setStar($input, $user, true);
    }

    /**
     * unstar
     *
     * Attaches a star to a song, album or artist.
     * https://www.subsonic.org/pages/api.jsp#unstar
     * @param array<string, mixed> $input
     */
    public function unstar(array $input, User $user): void
    {
        $this->setStar($input, $user, false);
    }

    /**
     * @param array<string, mixed> $input
     */
    private function setStar(array $input, User $user, bool $star): void
    {
        $sub_ids  = $input['id'] ?? null;
        $albumId  = $input['albumId'] ?? null;
        $artistId = $input['artistId'] ?? null;

        // Normalize all in one array
        $objects = [];

        if ($sub_ids) {
            if (!is_array($sub_ids)) {
                $sub_ids = [$sub_ids];
            }
            foreach ($sub_ids as $item) {
                $object_id   = Subsonic_Api::getAmpacheId($item);
                $object_type = Subsonic_Api::getAmpacheType($item);
                $objects[]   = [
                    'id' => $object_id,
                    'type' => $object_type
                ];
            }
        } elseif ($albumId) {
            if (!is_array($albumId)) {
                $albumId = [$albumId];
            }
            foreach ($albumId as $album) {
                $object_id = Subsonic_Api::getAmpacheId($album);
                $objects[] = [
                    'id' => $object_id,
                    'type' => 'album'
                ];
            }
        } elseif ($artistId) {
            if (!is_array($artistId)) {
                $artistId = [$artistId];
            }
            foreach ($artistId as $artist) {
                $object_id = Subsonic_Api::getAmpacheId($artist);
                $objects[] = [
                    'id' => $object_id,
                    'type' => 'artist'
                ];
            }
        } else {
            $this->responseHandler->errorOutput($input, Subsonic_Api::SSERROR_MISSINGPARAM, __FUNCTION__);

            return;
        }

        foreach ($objects as $object) {
            $flag = new Userflag($object['id'], $object['type']);
            $flag->set_flag($star, $user->id);
        }

        $this->responseHandler->responseOutput($input, __FUNCTION__);
    }
}

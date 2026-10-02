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

use Ampache\Module\Api\OpenSubsonic\OpenSubsonicResponseHandlerInterface;
use Ampache\Module\Api\OpenSubsonic_Api;
use Ampache\Module\Api\OpenSubsonic_Json_Data;
use Ampache\Module\Api\OpenSubsonic_Xml_Data;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\User;
use DateTime;

final class BookmarkHandler implements BookmarkHandlerInterface
{
    public function __construct(
        private readonly BookmarkRepositoryInterface $bookmarkRepository,
        private readonly OpenSubsonicResponseHandlerInterface $responseHandler,
        private readonly OpenSubsonic_Json_Data $openSubsonicJsonData,
        private readonly OpenSubsonic_Xml_Data $openSubsonicXmlData,
    ) {}

    /**
     * createBookmark
     *
     * Creates or updates a bookmark.
     * https://opensubsonic.netlify.app/docs/endpoints/createbookmark/
     * @param array<string, mixed> $input
     */
    public function createbookmark(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $position = $this->responseHandler->checkParameter($input, 'position', __FUNCTION__);
        if ($position === false) {
            return;
        }

        $comment   = (string) ($input['comment'] ?? '');
        $object_id = OpenSubsonic_Api::getAmpacheId($sub_id);
        $type      = OpenSubsonic_Api::getAmpacheType($sub_id);

        if (!empty($object_id) && !empty($type)) {
            $bookmark = new Bookmark($object_id, $type);
            if ($bookmark->isNew()) {
                Bookmark::create(
                    [
                        'object_id' => $object_id,
                        'object_type' => $type,
                        'comment' => $comment,
                        'position' => (int) $position
                    ],
                    $user->id,
                    time()
                );
            } else {
                $this->bookmarkRepository->update($bookmark->getId(), (int) $position, new DateTime());
            }
            $this->responseHandler->responseOutput($input, __FUNCTION__);
        } else {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);
        }
    }

    /**
     * deleteBookmark
     *
     * Creates or updates a bookmark.
     * https://opensubsonic.netlify.app/docs/endpoints/deletebookmark/
     * @param array<string, mixed> $input
     */
    public function deletebookmark(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $object_id = OpenSubsonic_Api::getAmpacheId($sub_id);
        $type      = OpenSubsonic_Api::getAmpacheType($sub_id);

        $bookmark = new Bookmark($object_id, $type, $user->id);
        if ($bookmark->isNew()) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);
        } else {
            $this->bookmarkRepository->delete($bookmark->getId());

            $this->responseHandler->responseOutput($input, __FUNCTION__);
        }
    }

    /**
     * getBookmarks
     *
     * Returns all bookmarks for this user.
     * https://opensubsonic.netlify.app/docs/endpoints/getbookmarks/
     * @param array<string, mixed> $input
     */
    public function getbookmarks(array $input, User $user): void
    {
        $bookmarks = [];

        $bookmarkRepository = $this->bookmarkRepository;
        foreach ($bookmarkRepository->getByUser($user) as $bookmarkId) {
            $bookmark = $bookmarkRepository->findById($bookmarkId);

            if ($bookmark !== null) {
                $bookmarks[] = $bookmark;
            }
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addBookmarks($response, $bookmarks);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addBookmarks($response, $bookmarks);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }
}

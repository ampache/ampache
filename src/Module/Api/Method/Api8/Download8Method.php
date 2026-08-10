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

namespace Ampache\Module\Api\Method\Api8;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Database\Query\Random;
use Ampache\Module\System\Core;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\container_item;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Downloads a given media file, or a zip of a whole container object.
 */
final class Download8Method implements MethodInterface
{
    public const string ACTION = 'download';

    public function __construct(
        private ModelFactoryInterface $modelFactory,
        private LibraryItemLoaderInterface $libraryItemLoader,
        private ZipHandlerInterface $zipHandler,
        private FunctionCheckerInterface $functionChecker,
    ) {}

    /**
     * MINIMUM_API_VERSION=400001
     *
     * Downloads a given media file. set format=raw to download the full file
     * Search and Playlist will only stream a random object not the whole thing, unless zip=1
     *
     * id = (string) $song_id|$podcast_episode_id|$search_id|$playlist_id
     * type = (string) 'song', 'podcast_episode', 'search', 'playlist'
     * bitrate = (integer) max bitrate for transcoding in bytes (e.g 192000=192Kb) //optional SONG ONLY
     * format = (string) 'mp3', 'ogg', etc use 'raw' to skip transcoding //optional SONG ONLY
     * stats = (integer) 0,1, if false disable stat recording when playing the object (default: 1) //optional
     * zip = (integer) 0,1, download as a zip if the type/id is a container object and zipping is enabled //optional, API8 only
     *
     * @param array{
     *     filter?: string,
     *     id?: string,
     *     type?: string,
     *     bitrate?: int,
     *     format?: string,
     *     stats?: string,
     *     zip?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessDeniedException|RequestParamMissingException|ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        $filter = $input['id'] ?? $input['filter'] ?? null;
        if ($filter === null || !isset($input['type'])) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filter/type')
            );
        }

        $objectId = (int) $filter;
        $type     = (string) $input['type'];

        if (
            $objectId === 0
            && (
                $type === 'playlist'
                || $type === 'search'
            )
        ) {
            // The API can use searches as playlists so check for those too
            $objectId = (int) str_replace('smart_', '', (string) $filter);
            $type     = 'search';
        }

        $wantsZip = ((int) ($input['zip'] ?? 0)) === 1;

        if ($wantsZip && LibraryItemEnum::tryFrom($type) !== null && $this->zipHandler->isZipable($type)) {
            return $this->downloadZip($response, $type, $objectId, $user);
        }

        $maxBitRate  = (int) ($input['bitrate'] ?? 0);
        $format      = $input['format'] ?? null; // mp3, flv or raw
        $params      = '&client=api&action=download';
        $recordStats = (int) ($input['stats'] ?? 1);

        if (AmpConfig::get('api_always_download') || $recordStats == 0) {
            $params .= '&cache=1';
        }

        if ($format && in_array($type, ['song', 'search', 'playlist'])) {
            $params .= '&format=' . $format;
        }
        if ($format != 'raw' && $maxBitRate > 0 && in_array($type, ['song', 'search', 'playlist'])) {
            $params .= '&bitrate=' . $maxBitRate;
        }

        $media = null;
        if ($type === 'song') {
            $media = $this->modelFactory->createSong($objectId);
        }
        if ($type === 'podcast_episode' || $type === 'podcast') {
            $media = $this->modelFactory->createPodcastEpisode($objectId);
        }
        if ($type === 'search' || $type === 'playlist') {
            $songId = Random::get_single_song($type, $user, $objectId);
            $media  = $this->modelFactory->createSong($songId);
        }

        $url = $media?->play_url($params, 'api', false, $user->getId(), $user->streamtoken) ?? '';
        if ($url === '') {
            throw new ResultEmptyException((string) $objectId);
        }

        return $response
            ->withStatus(302)
            ->withHeader('Location', str_replace(':443/play', '/play', $url));
    }

    /**
     * Builds a zip response for a whole container object (album, artist, playlist, podcast, ...)
     *
     * @throws AccessDeniedException|ResultEmptyException
     */
    private function downloadZip(
        ResponseInterface $response,
        string $type,
        int $objectId,
        User $user,
    ): ResponseInterface {
        if (!$this->functionChecker->check(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD)) {
            throw new AccessDeniedException();
        }

        $libItem = $this->libraryItemLoader->load(
            LibraryItemEnum::from($type),
            $objectId,
        );

        if (!$libItem instanceof container_item) {
            throw new ResultEmptyException((string) $objectId);
        }

        $mediaIds = $libItem->get_medias();

        if (!User::stream_control($mediaIds, $user)) {
            throw new AccessDeniedException();
        }

        return $this->zipHandler->zip(
            $response,
            (string) $libItem->get_fullname(),
            $this->getMediaFiles($mediaIds),
            $type === LibraryItemEnum::PLAYLIST->value,
        );
    }

    /**
     * Takes an array of media ids (as returned by container_item::get_medias()) and returns
     * the actual filenames, grouped by their parent's name
     *
     * @param array<int, array{object_type: LibraryItemEnum, object_id: int}> $medias
     * @return array{
     *     files: array<string, list<string>>,
     *     total_size: int
     * }
     */
    private function getMediaFiles(array $medias): array
    {
        $mediaFiles = [];
        $totalSize  = 0;
        foreach ($medias as $element) {
            $media = $this->libraryItemLoader->load(
                $element['object_type'],
                $element['object_id'],
            );

            if ($media === null) {
                continue;
            }

            if (
                $media instanceof container_item
                && property_exists($media, 'enabled')
                && $media->enabled
                && !empty($media->file)
            ) {
                $totalSize += $media->size ?? 0;
                $dirname = '';
                $parent  = $media->get_parent();
                if ($parent !== null) {
                    $className = ObjectTypeToClassNameMapper::map($parent['object_type']->value);
                    /** @var class-string<library_item> $className */
                    $pobj    = new $className($parent['object_id']);
                    $dirname = (string) $pobj->get_fullname();
                }

                if ($dirname !== '' && $dirname !== '0' && !array_key_exists($dirname, $mediaFiles)) {
                    $mediaFiles[$dirname] = [];
                }

                $mediaFiles[$dirname][] = Core::conv_lc_file($media->file);
            }
        }

        return [
            'files' => $mediaFiles,
            'total_size' => $totalSize,
        ];
    }
}

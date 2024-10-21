<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Application\Image;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authentication\AuthenticationManagerInterface;
use Ampache\Module\Util\Horde_Browser;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Video;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final readonly class ShowAction extends AbstractShowAction
{
    public const REQUEST_ACTION = 'show';

    public function __construct(
        RequestParserInterface $requestParser,
        AuthenticationManagerInterface $authenticationManager,
        private ConfigContainerInterface $configContainer,
        Horde_Browser $horde_browser,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        private LibraryItemLoaderInterface $libraryItemLoader,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $requestParser,
            $authenticationManager,
            $configContainer,
            $horde_browser,
            $responseFactory,
            $streamFactory,
            $logger
        );
    }

    /**
     * @return null|array{
     *  0: string,
     *  1: int,
     *  2: string
     * }
     */
    protected function getFileName(
        ServerRequestInterface $request,
    ): ?array {
        $queryParams = $request->getQueryParams();

        /**
         * @deprecated FIXME: Legacy stuff - should be removed after a version or so
         */
        $objectType = $queryParams['object_type'] ??
            ($this->configContainer->get(ConfigurationKeyEnum::SHOW_SONG_ART) ? 'song' : 'album');

        if (!Art::is_valid_type($objectType)) {
            return null;
        }

        $objectId = (int) ($queryParams['object_id'] ?? 0);

        $item = $this->libraryItemLoader->load(
            LibraryItemEnum::from($objectType),
            $objectId
        );

        if ($item instanceof Song || $item instanceof Video || $item instanceof Podcast_Episode) {
            $filename = $item->title;
        } elseif ($item instanceof Podcast) {
            $filename = $item->getTitle();
        } else {
            // Album || Artist || Broadcast || Label || License || Live_Stream || Wanted
            $filename = $item->name ?? '';
        }

        if ($item instanceof Podcast_Episode) {
            $objectId        = $item->podcast;
            $objectType      = 'podcast';
        }

        return [
            $filename,
            $objectId,
            $objectType,
        ];
    }
}

<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Module\Playback\MediaUrlListGenerator;

use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Stream\Url\StreamUrlParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\DemocraticRepositoryInterface;
use Ampache\Repository\Model\Democratic;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * This 'votes' on the songs; it inserts them into a tmp_playlist with user
 * set to -1.
 */
final class DemocraticMediaUrlListGeneratorType extends AbstractMediaUrlListGeneratorType
{
    private StreamUrlParserInterface $streamUrlParser;

    private DemocraticRepositoryInterface $democraticRepository;

    private UiInterface $ui;

    private StreamFactoryInterface $streamFactory;

    public function __construct(
        StreamUrlParserInterface $streamUrlParser,
        DemocraticRepositoryInterface $democraticRepository,
        UiInterface $ui,
        StreamFactoryInterface $streamFactory
    ) {
        $this->streamUrlParser      = $streamUrlParser;
        $this->democraticRepository = $democraticRepository;
        $this->ui                   = $ui;
        $this->streamFactory        = $streamFactory;
    }

    public function generate(
        Stream_Playlist $playlist,
        ResponseInterface $response
    ): ResponseInterface {
        // @todo $democratic = $this->democraticRepository->getCurrent();
        $democratic = Democratic::get_current_playlist();
        $democratic->set_parent();
        $items = [];

        foreach ($playlist->urls as $url) {
            $data    = $this->streamUrlParser->parse($url->url);
            $items[] = [$data['type'], $data['id']];
        }
        if (!$items !== []) {
            $democratic->add_vote($items);

            $response = $response->withBody(
                $this->streamFactory->createStream(
                    $this->ui->displayNotification(T_('Vote added'))
                )
            );
        }

        return $response;
    }
}

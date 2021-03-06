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
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * This should really only be used if all of the content is ASF files.
 */
final class AsxMediaUrlListGeneratorType extends AbstractMediaUrlListGeneratorType
{
    private UiInterface $ui;

    private StreamFactoryInterface $streamFactory;

    public function __construct(
        UiInterface $ui,
        StreamFactoryInterface $streamFactory
    ) {
        $this->ui            = $ui;
        $this->streamFactory = $streamFactory;
    }

    public function generate(
        Stream_Playlist $playlist,
        ResponseInterface $response
    ): ResponseInterface {
        $ret = '<ASX VERSION="3.0" BANNERBAR="auto">' . "\n";
        $ret .= "<TITLE>" . ($playlist->title ?: T_("Ampache ASX Playlist")) . "</TITLE>\n";
        $ret .= '<PARAM NAME="Encoding" VALUE="utf-8"' . "></PARAM>\n";

        foreach ($playlist->urls as $url) {
            $ret .= "<ENTRY>\n";
            $ret .= '<TITLE>' . $this->ui->scrubOut($url->title) . "</TITLE>\n";
            $ret .= '<AUTHOR>' . $this->ui->scrubOut($url->author) . "</AUTHOR>\n";
            // @todo FIXME: duration looks hacky and wrong
            $ret .= "\t\t" . '<DURATION VALUE="00:00:' . $url->time . '" />' . "\n";
            $ret .= "\t\t" . '<PARAM NAME="Album" Value="' . $this->ui->scrubOut($url->album) . '" />' . "\n";
            $ret .= "\t\t" . '<PARAM NAME="Composer" Value="' . $this->ui->scrubOut($url->author) . '" />' . "\n";
            $ret .= "\t\t" . '<PARAM NAME="Prebuffer" Value="false" />' . "\n";
            $ret .= '<REF HREF="' . $url->url . '" />' . "\n";
            $ret .= "</ENTRY>\n";
        }

        $ret .= "</ASX>\n";

        return $this
            ->setHeader($response, 'asx', 'video/x-ms-asf')
            ->withBody($this->streamFactory->createStream($ret));
    }
}

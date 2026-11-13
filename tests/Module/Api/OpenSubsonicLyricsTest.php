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

namespace Ampache\Module\Api;

use Ampache\MockeryTestCase;
use Ampache\Module\Api\OpenSubsonic\Handler\SystemHandler;
use Ampache\Module\Api\OpenSubsonic\OpenSubsonicResponseHandlerInterface;
use Ampache\Module\Api\OpenSubsonic\SonicAnalysisPluginResolverInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\SongRepositoryInterface;
use SimpleXMLElement;

class OpenSubsonicLyricsTest extends MockeryTestCase
{
    public function testLyricsJsonAndXmlCarryMatchingTextAndOffsets(): void
    {
        [$json, $xml]  = $this->serializers();
        $song          = $this->mock(Song::class);
        $song->enabled = true;
        $song->title   = 'Title';
        $song->shouldReceive('isNew')->andReturn(false);
        $song->shouldReceive('get_parent_fullname')->andReturn('Artist');
        $song->shouldReceive('get_lyrics')->andReturn(['text' => '[00:01.00]<00:01.00>Café & <00:02.00>tea']);

        $response   = $json->addLyricsList(['subsonic-response' => []], $song, true);
        $lyrics     = $response['subsonic-response']['lyricsList']['structuredLyrics'][0];
        $document   = $xml->addLyricsList(new SimpleXMLElement('<subsonic-response/>'), $song, true);
        $structured = $document->lyricsList->structuredLyrics;
        self::assertSame($lyrics['line'][0]['value'], (string) $structured->line[0]);
        self::assertFalse(isset($structured->line[0]['value']));
        foreach ($lyrics['cueLine'][0]['cue'] as $index => $cue) {
            $xmlCue = $structured->cueLine->cue[$index];
            self::assertSame($cue['value'], (string) $xmlCue);
            self::assertSame($cue['byteStart'], (int) $xmlCue['byteStart']);
            self::assertSame($cue['byteEnd'], (int) $xmlCue['byteEnd']);
        }

        $legacy = $json->addLyricsList(['subsonic-response' => []], $song, false);
        self::assertArrayNotHasKey('cueLine', $legacy['subsonic-response']['lyricsList']['structuredLyrics'][0]);
    }

    public function testSongLyricsVersionsAreAdvertisedInJsonAndXml(): void
    {
        [$json, $xml]    = $this->serializers();
        $responseHandler = $this->mock(OpenSubsonicResponseHandlerInterface::class);
        $resolver        = $this->mock(SonicAnalysisPluginResolverInterface::class);
        $user            = $this->mock(User::class);
        $resolver->shouldReceive('resolve')->with($user)->twice()->andReturn(null);
        $responseHandler->shouldReceive('addJsonResponse')->once()->andReturn(['subsonic-response' => []]);
        $responseHandler->shouldReceive('addXmlResponse')->once()->andReturn(new SimpleXMLElement('<subsonic-response/>'));
        $responses = [];
        $responseHandler->shouldReceive('responseOutput')->twice()->andReturnUsing(
            static function (array $input, string $function, array|SimpleXMLElement|null $response) use (&$responses): void {
                $responses[$input['f']] = $response;
            }
        );
        $handler = new SystemHandler($responseHandler, $json, $xml, $resolver);
        $handler->getopensubsonicextensions(['f' => 'json'], $user);
        $handler->getopensubsonicextensions(['f' => 'xml'], $user);

        $extensions = $responses['json']['subsonic-response']['openSubsonicExtensions'];
        $byName     = array_column($extensions, 'versions', 'name');
        self::assertSame([1, 2], $byName['songLyrics']);
        $xmlExtensions = $responses['xml']->xpath('openSubsonicExtensions[@name="songLyrics"]/versions');
        self::assertSame(['1', '2'], array_map(strval(...), $xmlExtensions));
    }

    private function serializers(): array
    {
        $albums  = $this->mock(AlbumRepositoryInterface::class);
        $folders = $this->mock(FolderRepositoryInterface::class);
        $songs   = $this->mock(SongRepositoryInterface::class);
        $fields  = new OpenSubsonic_Fields($this->mock(BookmarkRepositoryInterface::class), $this->mock(LabelRepositoryInterface::class));

        return [
            new OpenSubsonic_Json_Data($albums, $folders, $fields, $songs),
            new OpenSubsonic_Xml_Data($albums, $folders, $fields, $songs),
        ];
    }
}

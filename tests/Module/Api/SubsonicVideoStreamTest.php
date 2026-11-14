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
use Ampache\Module\Api\OpenSubsonic\Handler\StreamingHandler as OpenSubsonicStreamingHandler;
use Ampache\Module\Api\OpenSubsonic\OpenSubsonicResponseHandlerInterface;
use Ampache\Module\Api\Subsonic\Handler\StreamingHandler;
use Ampache\Module\Api\Subsonic\SubsonicResponseHandlerInterface;
use Ampache\Module\Database\database_object;
use Ampache\Repository\Model\User;
use DI\Container;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;

/** DSub could list videos but not play them (#2927) */
class SubsonicVideoStreamTest extends MockeryTestCase
{
    private const int VIDEO = 29271;

    /**
     * @return iterable<string, array{bool, string}>
     */
    public static function endpoints(): iterable
    {
        yield 'subsonic stream' => [false, 'stream'];
        yield 'subsonic download' => [false, 'download'];
        yield 'opensubsonic stream' => [true, 'stream'];
        yield 'opensubsonic download' => [true, 'download'];
    }

    #[DataProvider('endpoints')]
    public function testAVideoIsServed(bool $openSubsonic, string $endpoint): void
    {
        $responseHandler = $this->mock($openSubsonic ? OpenSubsonicResponseHandlerInterface::class : SubsonicResponseHandlerInterface::class);
        $responseHandler->shouldReceive('checkParameter')->andReturn('vi-' . self::VIDEO);
        $responseHandler->shouldNotReceive('errorOutput');

        $subject = $openSubsonic
            ? new OpenSubsonicStreamingHandler($responseHandler, $this->mock(OpenSubsonic_Json_Data::class), $this->mock(OpenSubsonic_Xml_Data::class))
            : new StreamingHandler($responseHandler, $this->mock(Subsonic_Json_Data::class), $this->mock(Subsonic_Xml_Data::class));

        $user              = $this->mock(User::class);
        $user->id          = 1;
        $user->streamtoken = null;

        // the proxy drops the buffer it streams through
        ob_start();
        $subject->$endpoint(['id' => 'vi-' . self::VIDEO], $user);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // priming the cache keeps `new Video()` off the database
        database_object::add_to_cache('video', self::VIDEO, [
            'id' => self::VIDEO,
            'title' => 'Clip',
            'type' => 'mp4',
        ]);

        $logger = $this->mock(LoggerInterface::class);
        $logger->shouldIgnoreMissing();

        $dic = $this->mock(Container::class);
        $dic->shouldReceive('get')->andReturn($logger);
        $GLOBALS['dic'] = $dic;
    }
}

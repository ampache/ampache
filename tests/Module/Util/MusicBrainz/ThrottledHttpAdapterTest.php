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

namespace Ampache\Module\Util\MusicBrainz;

use MusicBrainz\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Both settings fail quietly when they are wrong: a bad url silently serves musicbrainz.org, and a throttle
 * that is ignored looks exactly like a slow mirror. Neither shows up anywhere but here.
 *
 * The wait counts hundredths of a second so a private mirror can be called faster than once a second, which
 * whole seconds could not express: below the public server's limit there was only no wait at all.
 */
class ThrottledHttpAdapterTest extends TestCase
{
    private const array OPTIONS = ['user-agent' => 'Ampache/8.1.1 (https://example.com)'];

    /**
     * sleep() throws on a negative number, and nothing stops an admin from typing one in the form; a
     * negative value is clamped the same way zero is, rather than reaching usleep() at all
     */
    public function testANegativeWaitIsNotTaken(): void
    {
        $started = microtime(true);
        $this->adapter(null, -1)->call('artist/', [], self::OPTIONS);

        self::assertLessThan(0.5, microtime(true) - $started);
    }

    public function testAServerThatIsNotAUrlFallsBackToThePublicOne(): void
    {
        $subject = $this->adapter('mb.example.com');
        $subject->call('artist/', [], self::OPTIONS);

        self::assertSame('https://musicbrainz.org/ws/2/artist/', $subject->requested);
    }

    /**
     * A stray very large value is capped rather than stalling every call for however long an admin mistyped
     */
    public function testAThrottleAboveTheMaximumIsClampedDown(): void
    {
        self::assertSame(1000, $this->configuredThrottle(5000));
    }

    /**
     * A value below the public server's own minimum is raised to it, not left as a wait too short to matter
     */
    public function testAThrottleBelowTheMinimumIsClampedUp(): void
    {
        self::assertSame(100, $this->configuredThrottle(25));
    }

    public function testAuthenticationIsRefusedWithoutCredentials(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Authentication is required');

        $this->adapter()->call('artist/', [], self::OPTIONS, true);
    }

    public function testNothingConfiguredKeepsThePublicServer(): void
    {
        self::assertSame('https://musicbrainz.org/ws/2', (new ThrottledHttpAdapter())->endpoint);
        self::assertSame('https://musicbrainz.org/ws/2', (new ThrottledHttpAdapter(null))->endpoint);
    }

    public function testTheConfiguredServerIsTheOneCalled(): void
    {
        $subject = $this->adapter('https://mb.example.com/ws/2');
        $subject->call('artist/', ['query' => 'nirvana', 'fmt' => 'json'], self::OPTIONS);

        self::assertSame('https://mb.example.com/ws/2/artist/?query=nirvana&fmt=json', $subject->requested);
    }

    public function testTheRequestIsRefusedWithoutAUserAgent(): void
    {
        $this->expectException(Exception::class);

        $this->adapter()->call('artist/');
    }

    /**
     * The whole point of the setting: a mirror of your own is not bound by the rule the wait exists for
     */
    public function testTheWaitIsSkippedEntirelyWhenItIsZero(): void
    {
        $started = microtime(true);
        $this->adapter(null, 0)->call('artist/', [], self::OPTIONS);

        self::assertLessThan(0.5, microtime(true) - $started);
    }

    /**
     * The point of the unit: a configured wait is actually taken, at the minimum the public server allows —
     * the upper bound is what catches a wait that ignores the setting and serves the public server's second
     */
    public function testTheWaitLastsAsLongAsTheSettingSays(): void
    {
        $started = microtime(true);
        $this->adapter(null, 100)->call('artist/', [], self::OPTIONS);
        $elapsed = microtime(true) - $started;

        self::assertGreaterThanOrEqual(1.0, $elapsed);
        self::assertLessThan(1.6, $elapsed);
    }

    private function adapter(?string $endpoint = null, int $throttle = 0): RecordingThrottledHttpAdapter
    {
        return new RecordingThrottledHttpAdapter($endpoint, $throttle);
    }

    /**
     * Reads the clamped value back without waiting it out, for the boundary cases alone
     */
    private function configuredThrottle(int $throttle): int
    {
        $property = new ReflectionProperty(ThrottledHttpAdapter::class, 'throttle');

        return $property->getValue($this->adapter(null, $throttle));
    }
}

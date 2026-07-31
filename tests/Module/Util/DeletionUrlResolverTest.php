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

namespace Ampache\Module\Util;

use Ampache\Config\ConfigContainerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeletionUrlResolverTest extends TestCase
{
    private const string WEB_PATH = 'https://ampache.test/music';

    private ConfigContainerInterface&MockObject $configContainer;
    private DeletionUrlResolver $subject;

    /**
     * @return list<array{0: null|string}>
     */
    public static function malformedBurlDataProvider(): array
    {
        return [
            [null],
            [''],
            ['not valid base64 !!!'],
            [base64_encode('')],
        ];
    }

    /**
     * @return list<array{0: string}>
     */
    public static function unusableBurlDataProvider(): array
    {
        return [
            'script scheme' => ['javascript:alert(1)'],
            'protocol relative' => ['//evil.example.com/albums.php'],
            'external host' => ['https://evil.example.com/albums.php'],
            'host prefix trap' => ['https://ampache.test.evil.com/music/albums.php'],
            'other install directory' => ['https://ampache.test/other/albums.php'],
            'web path without separator' => ['https://ampache.testing/albums.php'],
            'not a page' => ['https://ampache.test/music/albums'],
            'attribute break' => ['https://ampache.test/music/albums.php?x="onload="alert(1)'],
        ];
    }

    #[DataProvider('malformedBurlDataProvider')]
    public function testResolveBurlRejectsMalformedInput(?string $encodedBurl): void
    {
        $this->configContainer->expects(static::never())
            ->method('getWebPath');

        static::assertSame(
            '',
            $this->subject->resolveBurl($encodedBurl)
        );
    }

    #[DataProvider('unusableBurlDataProvider')]
    public function testResolveBurlRejectsUnusableTargets(string $burl): void
    {
        $this->configContainer->method('getWebPath')
            ->willReturn(self::WEB_PATH);

        static::assertSame(
            '',
            $this->subject->resolveBurl(base64_encode($burl))
        );
    }

    public function testResolveBurlReturnsAnAddressInsideTheInstall(): void
    {
        $burl = self::WEB_PATH . '/albums.php?action=show&album=42';

        $this->configContainer->expects(static::once())
            ->method('getWebPath')
            ->willReturn(self::WEB_PATH);

        static::assertSame(
            $burl,
            $this->subject->resolveBurl(base64_encode($burl))
        );
    }

    public function testResolveBurlReturnsTheWebRootItself(): void
    {
        $burl = self::WEB_PATH . '/';

        $this->configContainer->expects(static::once())
            ->method('getWebPath')
            ->willReturn(self::WEB_PATH);

        static::assertSame(
            $burl,
            $this->subject->resolveBurl(base64_encode($burl))
        );
    }

    public function testResolveContinueUrlFallsBackWhenLeavingTheOwnPageWithoutAParent(): void
    {
        static::assertSame(
            'fallback-url',
            $this->subject->resolveContinueUrl(
                self::WEB_PATH . '/albums.php?action=show&album=42',
                'album',
                42,
                '',
                'fallback-url'
            )
        );
    }

    public function testResolveContinueUrlFallsBackWithoutAnOriginPageOrParent(): void
    {
        static::assertSame(
            'fallback-url',
            $this->subject->resolveContinueUrl('', 'album', 42, '', 'fallback-url')
        );
    }

    public function testResolveContinueUrlKeepsTheOriginPageForADifferentObject(): void
    {
        $burl = self::WEB_PATH . '/albums.php?action=show&album=43';

        static::assertSame(
            $burl,
            $this->subject->resolveContinueUrl($burl, 'album', 42, 'parent-url', 'fallback-url')
        );
    }

    public function testResolveContinueUrlKeepsTheOriginPageForANonScalarParameter(): void
    {
        $burl = self::WEB_PATH . '/albums.php?action=show&album[]=42';

        static::assertSame(
            $burl,
            $this->subject->resolveContinueUrl($burl, 'album', 42, 'parent-url', 'fallback-url')
        );
    }

    public function testResolveContinueUrlKeepsTheOriginPageForAnUnknownObjectId(): void
    {
        $burl = self::WEB_PATH . '/albums.php?action=show&album=0';

        static::assertSame(
            $burl,
            $this->subject->resolveContinueUrl($burl, 'album', 0, 'parent-url', 'fallback-url')
        );
    }

    public function testResolveContinueUrlReturnsTheOriginPage(): void
    {
        $burl = self::WEB_PATH . '/artists.php?action=show&artist=7';

        static::assertSame(
            $burl,
            $this->subject->resolveContinueUrl($burl, 'album', 42, 'parent-url', 'fallback-url')
        );
    }

    public function testResolveContinueUrlReturnsTheParentWhenLeavingTheOwnPage(): void
    {
        static::assertSame(
            'parent-url',
            $this->subject->resolveContinueUrl(
                self::WEB_PATH . '/albums.php?action=show&album=42',
                'album',
                42,
                'parent-url',
                'fallback-url'
            )
        );
    }

    public function testResolveContinueUrlReturnsTheParentWithoutAnOriginPage(): void
    {
        static::assertSame(
            'parent-url',
            $this->subject->resolveContinueUrl('', 'album', 42, 'parent-url', 'fallback-url')
        );
    }

    protected function setUp(): void
    {
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);

        $this->subject = new DeletionUrlResolver(
            $this->configContainer
        );
    }
}

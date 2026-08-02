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
 */

namespace Ampache\Module\Share;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ShareUiLinkRendererTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private FunctionCheckerInterface&MockObject $functionChecker;
    private ShareUiLinkRenderer $subject;
    private ZipHandlerInterface&MockObject $zipHandler;

    public function testRenderIncludesBatchDownloadLinkWhenBatchDownloadAllowedAndZipable(): void
    {
        $this->configContainer->method('getWebPath')
            ->willReturn('https://example.com');

        $this->configContainer->method('isFeatureEnabled')
            ->willReturnMap([
                [ConfigurationKeyEnum::DOWNLOAD, true],
                [ConfigurationKeyEnum::REQUIRE_SESSION, false],
            ]);

        $this->functionChecker->expects(static::once())
            ->method('check')
            ->with(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD)
            ->willReturn(true);

        $this->zipHandler->expects(static::once())
            ->method('isZipable')
            ->with('album')
            ->willReturn(true);

        $result = $this->subject->render(LibraryItemEnum::ALBUM, 21);

        static::assertStringContainsString(
            'https://example.com/batch.php?action=album&id=21',
            $result,
        );
    }

    public function testRenderIncludesDirectDownloadLinkForSongs(): void
    {
        $this->configContainer->method('getWebPath')
            ->willReturn('https://example.com');

        $this->configContainer->method('isFeatureEnabled')
            ->willReturnMap([
                [ConfigurationKeyEnum::DOWNLOAD, true],
                [ConfigurationKeyEnum::REQUIRE_SESSION, false],
            ]);

        $result = $this->subject->render(LibraryItemEnum::SONG, 42);

        static::assertStringContainsString(
            'https://example.com/play/index.php?action=download&type=song&oid=42&uid=-1',
            $result,
        );
    }

    public function testRenderOmitsBatchDownloadLinkWhenNotZipable(): void
    {
        $this->configContainer->method('getWebPath')
            ->willReturn('https://example.com');

        $this->configContainer->method('isFeatureEnabled')
            ->willReturnMap([
                [ConfigurationKeyEnum::DOWNLOAD, true],
                [ConfigurationKeyEnum::REQUIRE_SESSION, false],
            ]);

        $this->functionChecker->method('check')
            ->willReturn(true);

        $this->zipHandler->expects(static::once())
            ->method('isZipable')
            ->with('album')
            ->willReturn(false);

        $result = $this->subject->render(LibraryItemEnum::ALBUM, 21);

        static::assertStringNotContainsString('batch.php', $result);
    }

    public function testRenderOmitsDownloadLinkWhenFeatureDisabled(): void
    {
        $this->configContainer->method('getWebPath')
            ->willReturn('https://example.com');

        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DOWNLOAD)
            ->willReturn(false);

        $this->functionChecker->expects(static::never())
            ->method('check');

        $result = $this->subject->render(LibraryItemEnum::SONG, 42);

        static::assertStringNotContainsString('action=download', $result);
        static::assertStringContainsString('action=show_create&type=song&id=42', $result);
    }

    protected function setUp(): void
    {
        $this->functionChecker = $this->createMock(FunctionCheckerInterface::class);
        $this->zipHandler      = $this->createMock(ZipHandlerInterface::class);
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);

        $this->subject = new ShareUiLinkRenderer(
            $this->functionChecker,
            $this->zipHandler,
            $this->configContainer,
        );

        // `Plugin` reads its stored version through the `global $dic` bridge
        $dic = $this->createMock(ContainerInterface::class);
        $dic->method('get')->willReturnCallback(fn(string $id): object => match ($id) {
            UpdateInfoRepositoryInterface::class => $this->createMock(UpdateInfoRepositoryInterface::class),
            default => $this->createMock(LoggerInterface::class),
        });

        $GLOBALS['dic'] = $dic;
    }
}

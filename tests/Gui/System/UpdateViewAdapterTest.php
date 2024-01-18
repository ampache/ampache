<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Gui\System;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\System\Update\Migration\MigrationInterface;
use Ampache\Module\System\Update\UpdateHelperInterface;
use Ampache\Module\System\Update\UpdaterInterface;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use ArrayIterator;
use Mockery\MockInterface;
use PHPUnit\Framework\MockObject\MockObject;

class UpdateViewAdapterTest extends MockeryTestCase
{
    private MockInterface $configContainer;

    private UpdateInfoRepositoryInterface&MockObject $updateInfoRepository;

    private UpdateHelperInterface&MockObject $updateHelper;

    private UpdaterInterface&MockObject $updater;

    private UpdateViewAdapter $subject;

    protected function setUp(): void
    {
        $this->configContainer      = $this->mock(ConfigContainerInterface::class);
        $this->updateInfoRepository = $this->createMock(UpdateInfoRepositoryInterface::class);
        $this->updateHelper         = $this->createMock(UpdateHelperInterface::class);
        $this->updater              = $this->createMock(UpdaterInterface::class);

        $this->subject = new UpdateViewAdapter(
            $this->configContainer,
            $this->updateInfoRepository,
            $this->updateHelper,
            $this->updater
        );
    }

    public function testGetHtmlLanguageReturnsSetLang(): void
    {
        $lang = 'kl_KL';

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::LANG)
            ->once()
            ->andReturn($lang);

        $this->assertSame(
            'kl-KL',
            $this->subject->getHtmlLanguage()
        );
    }

    public function testGetCharsetReturnsValue(): void
    {
        $value = 'some-charset';

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::SITE_CHARSET)
            ->once()
            ->andReturn($value);

        $this->assertSame(
            $value,
            $this->subject->getCharset()
        );
    }

    public function testGetTitleReturnsValue(): void
    {
        $value = 'some-title';

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::SITE_TITLE)
            ->once()
            ->andReturn($value);

        $this->assertSame(
            sprintf(
                '%s - Update',
                $value
            ),
            $this->subject->getTitle()
        );
    }

    public function testGetInstallationTitleReturnsValue(): void
    {
        $this->assertSame(
            'Ampache :: For the Love of Music - Installation',
            $this->subject->getInstallationTitle()
        );
    }

    public function testGetUpdateActionUrlReturnsValue(): void
    {
        $webPath = 'some-web-path';

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->assertSame(
            sprintf(
                '%s/update.php?action=update',
                $webPath
            ),
            $this->subject->getUpdateActionUrl()
        );
    }

    public function testGetWebPathReturnsValue(): void
    {
        $webPath = 'some-web-path';

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->assertSame(
            $webPath,
            $this->subject->getWebPath()
        );
    }

    public function testGetUpdateInfoReturnsData(): void
    {
        $version   = 'version-string';
        $changelog = ['some-changelog'];
        $warning   = true;

        $migration = $this->createMock(MigrationInterface::class);

        $this->updater->expects(static::once())
            ->method('getPendingUpdates')
            ->willReturn(new ArrayIterator([[
                'versionFormatted' => $version,
                'migration' => $migration
            ]]));

        $migration->expects(static::once())
            ->method('getChangelog')
            ->willReturn($changelog);
        $migration->expects(static::once())
            ->method('hasWarning')
            ->willReturn($warning);

        static::assertSame(
            [[
                'title' => 'Version: ' . $version,
                'changelog' => $changelog,
                'warning' => $warning
            ]],
            iterator_to_array($this->subject->getUpdateInfo())
        );
    }
}

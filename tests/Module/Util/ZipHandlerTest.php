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

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Mockery\MockInterface;
use Nyholm\Psr7\Response;
use Override;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

class ZipHandlerTest extends MockeryTestCase
{
    private MockInterface&ConfigContainerInterface $configContainer;
    private MockInterface&LoggerInterface $logger;
    private StreamFactoryInterface&MockObject $streamFactory;
    private ZipHandler $subject;
    private ?string $tmpDir = null;

    public function testIsZipableReturnsFalseIfNotSupported(): void
    {
        $type = 'foobar';

        $this->configContainer->shouldReceive('getTypesAllowedForZip')
            ->withNoArgs()
            ->once()
            ->andReturn(['snoosnoo']);

        self::assertFalse(
            $this->subject->isZipable($type)
        );
    }

    public function testIsZipableReturnsTrueIfSupported(): void
    {
        $type = 'foobar';

        $this->configContainer->shouldReceive('getTypesAllowedForZip')
            ->withNoArgs()
            ->once()
            ->andReturn([$type]);

        self::assertTrue(
            $this->subject->isZipable($type)
        );
    }

    public function testZipAnswersNotFoundWhenNothingCouldBeAdded(): void
    {
        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::ALBUM_ART_PREFERRED_FILENAME)
            ->andReturn('folder.jpg');
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::ART_ZIP_ADD)
            ->andReturnFalse();
        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::FILE_ZIP_COMMENT)
            ->andReturn('');
        $this->logger->shouldReceive('debug')->zeroOrMoreTimes();
        $this->logger->shouldReceive('warning')->once();

        // the tell that the crash is gone: no stream is opened on an archive that was never written
        $this->streamFactory->expects(self::never())
            ->method('createStreamFromFile');

        // zip() drops the output buffer a page request leaves open, so the test has to provide one of its own
        ob_start();
        $response = $this->subject->zip(
            new Response(),
            'Nothing To Send',
            ['total_size' => 0, 'files' => []],
            false
        );

        self::assertSame(404, $response->getStatusCode());
    }

    // a worker killed mid download never runs its shutdown function, so a later zip must sweep its archive
    public function testZipRemovesArchivesADeadWorkerLeftBehind(): void
    {
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('ziphandler-test-');
        mkdir($this->tmpDir);
        AmpConfig::set('tmp_dir_path', $this->tmpDir, true);

        $dayAndAHalfAgo = time() - 129600;
        $stale          = ['ampache-zip-6b01eb51d1e85', 'ampache-zip-6b01eb4a4eaa9.4sahlt.part'];
        $kept           = ['ampache-zip-6b01eb9f00000', 'waveform-6b01eb51d1e85'];
        foreach ([...$stale, $kept[1]] as $name) {
            touch($this->tmpDir . DIRECTORY_SEPARATOR . $name, $dayAndAHalfAgo);
        }

        touch($this->tmpDir . DIRECTORY_SEPARATOR . $kept[0]);

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::ALBUM_ART_PREFERRED_FILENAME)
            ->andReturn('folder.jpg');
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::ART_ZIP_ADD)
            ->andReturnFalse();
        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::FILE_ZIP_COMMENT)
            ->andReturn('');
        $this->logger->shouldReceive('debug')->zeroOrMoreTimes();
        $this->logger->shouldReceive('warning')->zeroOrMoreTimes();

        ob_start();
        $this->subject->zip(new Response(), 'Nothing To Send', ['total_size' => 0, 'files' => []], false);

        $left = array_values(array_diff((array) scandir($this->tmpDir), ['.', '..']));
        sort($left);

        // a download still running is fresh, and files that are not ours are none of the sweep's business
        self::assertSame($kept, $left);
    }

    #[Override]
    protected function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->streamFactory   = $this->createMock(StreamFactoryInterface::class);
        $this->logger          = $this->mock(LoggerInterface::class);

        $this->subject = new ZipHandler(
            $this->configContainer,
            $this->streamFactory,
            $this->logger
        );
    }

    #[Override]
    protected function tearDown(): void
    {
        if ($this->tmpDir !== null) {
            array_map(unlink(...), glob($this->tmpDir . DIRECTORY_SEPARATOR . '*') ?: []);
            rmdir($this->tmpDir);
            AmpConfig::set('tmp_dir_path', '', true);
        }

        parent::tearDown();
    }
}

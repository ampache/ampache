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
use Ampache\MockeryTestCase;
use Override;
use Psr\Container\ContainerInterface;

/**
 * Covers the `.lrc` sidecar reader and where its text ranks against lyrics embedded in the file.
 */
class VaInfoLrcSidecarTest extends MockeryTestCase
{
    private string $folder;
    private mixed $previousDic = null;

    public function testAMissingFileHasNoTime(): void
    {
        $this->assertNull(VaInfo::last_modified($this->folder . '/gone.mp3', true));
    }

    /**
     * With `catalog_verify_by_time` on, a verify skips a song whose file is older than its last read; a newer .lrc must not be skipped.
     */
    public function testANewerSidecarCountsAsAChangeToTheSong(): void
    {
        file_put_contents($this->folder . '/song.mp3', 'audio');
        file_put_contents($this->folder . '/song.lrc', '[00:01.00]Edited');
        touch($this->folder . '/song.mp3', 1000);
        touch($this->folder . '/song.lrc', 5000);

        $last_read = 3000;

        $this->assertSame(5000, VaInfo::last_modified($this->folder . '/song.mp3', true));
        $this->assertGreaterThan($last_read, VaInfo::last_modified($this->folder . '/song.mp3', true));
    }

    public function testAnOlderSidecarLeavesTheFileTime(): void
    {
        file_put_contents($this->folder . '/song.mp3', 'audio');
        file_put_contents($this->folder . '/song.lrc', '[00:01.00]Old');
        touch($this->folder . '/song.mp3', 5000);
        touch($this->folder . '/song.lrc', 1000);

        $this->assertSame(5000, VaInfo::last_modified($this->folder . '/song.mp3', true));
    }

    public function testASidecarBeatsEmbeddedPlainLyrics(): void
    {
        file_put_contents($this->folder . '/song.lrc', '[00:01.00]Timed');

        $this->stubContainer();
        $info = VaInfo::clean_tag_info(['id3v2' => ['lyrics' => 'Plain']], ['id3v2'], $this->folder . '/song.mp3');

        $this->assertSame('[00:01.00]Timed', $info['lyrics']);
    }

    public function testConvertsAWindowsCodepageFile(): void
    {
        file_put_contents($this->folder . '/song.lrc', "[00:01.00]Caf\xE9");

        $this->assertSame('[00:01.00]Café', VaInfo::read_lrc_sidecar($this->folder . '/song.mp3'));
    }

    public function testConvertsUtf16WithAByteOrderMark(): void
    {
        file_put_contents($this->folder . '/song.lrc', "\xFF\xFE" . mb_convert_encoding('[00:01.00]Café', 'UTF-16LE', 'UTF-8'));

        $this->assertSame('[00:01.00]Café', VaInfo::read_lrc_sidecar($this->folder . '/song.mp3'));
    }

    public function testEmbeddedLyricsAreStoredAsPlainLines(): void
    {
        $this->stubContainer();
        $info = VaInfo::clean_tag_info(['id3v2' => ['lyrics' => "One<br />\r\nTwo <b>bold</b>"]], ['id3v2'], $this->folder . '/song.mp3');

        $this->assertSame("One\nTwo bold", $info['lyrics']);
    }

    public function testEmbeddedTimedLyricsBeatASidecar(): void
    {
        file_put_contents($this->folder . '/song.lrc', '[00:01.00]From the sidecar');

        $this->stubContainer();
        $info = VaInfo::clean_tag_info(['id3v2' => ['lyrics' => '[00:01.00]From the tag']], ['id3v2'], $this->folder . '/song.mp3');

        $this->assertSame('[00:01.00]From the tag', $info['lyrics']);
    }

    public function testIgnoresAnEmptySidecar(): void
    {
        file_put_contents($this->folder . '/song.lrc', "\r\n  \n");

        $this->assertNull(VaInfo::read_lrc_sidecar($this->folder . '/song.mp3'));
    }

    public function testIgnoresAnOversizeSidecar(): void
    {
        file_put_contents($this->folder . '/song.lrc', str_repeat('a', 524289));

        $this->assertNull(VaInfo::read_lrc_sidecar($this->folder . '/song.mp3'));
    }

    public function testIgnoresASidecarForAnotherFile(): void
    {
        file_put_contents($this->folder . '/other.lrc', '[00:01.00]Not mine');

        $this->assertNull(VaInfo::read_lrc_sidecar($this->folder . '/song.mp3'));
    }

    /**
     * A symlinked sidecar must not reach a file outside the audio file's own folder.
     */
    public function testIgnoresASidecarLinkingOutOfTheFolder(): void
    {
        mkdir($this->folder . '/elsewhere');
        file_put_contents($this->folder . '/elsewhere/secret.txt', 'not lyrics');
        symlink($this->folder . '/elsewhere/secret.txt', $this->folder . '/song.lrc');

        $this->assertNull(VaInfo::lrc_sidecar_path($this->folder . '/song.mp3'));
    }

    public function testKeepsEnhancedWordCues(): void
    {
        file_put_contents($this->folder . '/song.lrc', '[00:01.00]<00:01.00>Hello <00:01.50>world');

        $this->assertSame('[00:01.00]<00:01.00>Hello <00:01.50>world', VaInfo::read_lrc_sidecar($this->folder . '/song.mp3'));
    }

    public function testReadsAnUpperCaseExtension(): void
    {
        file_put_contents($this->folder . '/song.LRC', '[00:01.00]Loud');

        $this->assertSame('[00:01.00]Loud', VaInfo::read_lrc_sidecar($this->folder . '/song.mp3'));
    }

    public function testReadsTheSidecarWithTheSameName(): void
    {
        file_put_contents($this->folder . '/01 - Song.lrc', "[00:01.00]First\r\n[00:02.00]Second\r\n");

        $this->assertSame("[00:01.00]First\n[00:02.00]Second", VaInfo::read_lrc_sidecar($this->folder . '/01 - Song.flac'));
    }

    public function testStripsAUtf8ByteOrderMark(): void
    {
        file_put_contents($this->folder . '/song.lrc', "\xEF\xBB\xBF[00:01.00]Café");

        $this->assertSame('[00:01.00]Café', VaInfo::read_lrc_sidecar($this->folder . '/song.mp3'));
    }

    /**
     * Only songs read lyrics, so a video beside a same-named .lrc is judged by its own file alone.
     */
    public function testTheSidecarIsIgnoredWhenNotAsked(): void
    {
        file_put_contents($this->folder . '/clip.mp4', 'video');
        file_put_contents($this->folder . '/clip.lrc', '[00:01.00]Not for videos');
        touch($this->folder . '/clip.mp4', 1000);
        touch($this->folder . '/clip.lrc', 5000);

        $this->assertSame(1000, VaInfo::last_modified($this->folder . '/clip.mp4', false));
    }

    #[Override]
    protected function setUp(): void
    {
        $this->previousDic = $GLOBALS['dic'] ?? null;
        $this->folder      = sys_get_temp_dir() . '/ampache-lrc-' . bin2hex(random_bytes(6));
        mkdir($this->folder);
    }

    #[Override]
    protected function tearDown(): void
    {
        foreach (glob($this->folder . '/{,.}*', GLOB_BRACE) ?: [] as $path) {
            if (is_file($path) || is_link($path)) {
                unlink($path);
            }
        }
        foreach (glob($this->folder . '/*', GLOB_ONLYDIR) ?: [] as $path) {
            foreach (glob($path . '/*') ?: [] as $inner) {
                unlink($inner);
            }
            rmdir($path);
        }
        rmdir($this->folder);
        $GLOBALS['dic'] = $this->previousDic;
    }

    /**
     * clean_tag_info() asks the container whether custom metadata is on.
     */
    private function stubContainer(): void
    {
        $config = $this->mock(ConfigContainerInterface::class);
        $config->shouldReceive('get')->andReturnFalse();
        $dic = $this->mock(ContainerInterface::class);
        $dic->shouldReceive('get')->with(ConfigContainerInterface::class)->andReturn($config);
        $GLOBALS['dic'] = $dic;
    }
}

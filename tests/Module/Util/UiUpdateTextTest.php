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

use Ampache\MockeryTestCase;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

class UiUpdateTextTest extends MockeryTestCase
{
    /**
     * The client renders this SSE message with jQuery .text(), which never decodes entities, so a value a
     * caller already ran through scrub_out() for the non-SSE .html() sink must be decoded back to plain text here.
     */
    #[RunInSeparateProcess]
    public function testSseOutputIsPlainTextNotHtmlEscaped(): void
    {
        define('SSE_OUTPUT', true);

        ob_start();
        ob_start();
        Ui::update_text('Found', scrub_out('1 - Rock & Roll.mp3'));
        ob_end_clean();
        $output = ob_get_clean();

        $lines = explode("\n", trim($output));

        $this->assertSame('id: 1', $lines[0]);
        $this->assertStringStartsWith('data: ', $lines[1]);

        $payload = json_decode(substr($lines[1], strlen('data: ')), true);

        $this->assertSame('displayNotification', $payload['fn']);
        $this->assertSame('Found: 1 - Rock & Roll.mp3', $payload['args'][0]);
    }

    /**
     * SseApiApplication drains every output buffer level before streaming, so update_text() must not assume
     * one is still open; calling ob_flush() with none active raises "No buffer to flush" on every notification.
     */
    #[RunInSeparateProcess]
    public function testSseOutputWithNoOpenBufferDoesNotWarn(): void
    {
        define('SSE_OUTPUT', true);

        // drain every level, including the one PHPUnit's own output capture opened for this test
        $levelsDrained = 0;
        while (ob_get_level() > 0) {
            ob_end_clean();
            $levelsDrained++;
        }

        set_error_handler(
            function (int $errno, string $errstr): bool {
                self::fail('Unexpected warning: ' . $errstr);
            }
        );

        try {
            Ui::update_text('Found', 'test value');
        } finally {
            restore_error_handler();
            // PHPUnit's teardown expects to close the buffer level it opened; put it back as found
            for ($i = 0; $i < $levelsDrained; $i++) {
                ob_start();
            }
        }

        $this->addToAssertionCount(1);
    }
}

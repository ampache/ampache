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

namespace Ampache\Module\System;

use Ampache\MockeryTestCase;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

class AmpErrorTest extends MockeryTestCase
{
    /**
     * The client renders this SSE message with jQuery .text(), which never decodes entities, so a message a
     * caller already ran through scrub_out() for the html display() sink must be decoded back to plain text here.
     */
    #[RunInSeparateProcess]
    public function testSseOutputIsPlainTextNotHtmlEscaped(): void
    {
        define('SSE_OUTPUT', true);

        ob_start();
        ob_start();
        AmpError::add('general', scrub_out('Found & Lost'));
        ob_end_clean();
        $output = ob_get_clean();

        $this->assertStringStartsWith('data: ', $output);

        $payload = json_decode(substr($output, strlen('data: ')), true);

        $this->assertSame('display_sse_error', $payload['fn']);
        $this->assertSame('Found & Lost', $payload['args'][0]);
    }
}

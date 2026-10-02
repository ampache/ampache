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

namespace Ampache\Config;

use PHPUnit\Framework\TestCase;

class DisplayNotificationTest extends TestCase
{
    public function testAccentsSurviveUnescaped(): void
    {
        $this->assertStringContainsString('mises à jour', $this->emit('mises à jour'));
    }

    public function testNothingCanCloseTheScriptBlockOrTheStringLiteral(): void
    {
        $html = $this->emit('</script><script>alert(1)</script> "quoted" \'apostrophe\'');

        $this->assertStringNotContainsString('<script>alert', $html);
        $this->assertSame(1, substr_count($html, '</script>'), 'the payload must not close the block early');
    }

    public function testTheMessageIsNotWrappedInQuotesTwice(): void
    {
        $this->assertStringContainsString('displayNotification("Saved.", 5000);', $this->emit('Saved.'));
    }

    private function emit(string $message): string
    {
        ob_start();
        display_notification($message);

        return (string) ob_get_clean();
    }
}

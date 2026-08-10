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

namespace Ampache\Gui\View;

use Ampache\MockeryTestCase;
use Override;
use RuntimeException;

/**
 * A view over the fixture template, used to exercise `AbstractView` without a real page.
 */
final class ExampleView extends AbstractView
{
    public function __construct(
        private readonly string $value,
        private readonly string $template = 'example.phtml',
    ) {}

    public function getValue(): string
    {
        return $this->value;
    }

    #[Override]
    protected function templateFile(): string
    {
        return __DIR__ . '/fixtures/' . $this->template;
    }
}

class AbstractViewTest extends MockeryTestCase
{
    public function testRenderDiscardsTheBufferWhenTheTemplateThrows(): void
    {
        $subject = new ExampleView('value', 'throws.phtml');

        $level = ob_get_level();

        $this->expectException(RuntimeException::class);

        try {
            $subject->render();
        } finally {
            $this->assertSame($level, ob_get_level());
        }
    }

    public function testRenderEscapesSingleQuotes(): void
    {
        $subject = new ExampleView("it's");

        $this->assertStringContainsString('<p>it&#039;s</p>', $subject->render());
    }

    public function testRenderEscapesThroughTheEscapingSeamOnly(): void
    {
        $subject = new ExampleView('<b>"x"</b>');

        $this->assertSame(
            "<p>&lt;b&gt;&quot;x&quot;&lt;/b&gt;</p>\n<div><b>\"x\"</b></div>\n",
            $subject->render()
        );
    }

    public function testRenderRunsTheTemplateInTheViewScope(): void
    {
        $subject = new ExampleView('plain');

        $this->assertSame(
            "<p>plain</p>\n<div>plain</div>\n",
            $subject->render()
        );
    }
}

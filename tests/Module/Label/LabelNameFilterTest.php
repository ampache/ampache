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

namespace Ampache\Module\Label;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LabelNameFilterTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private LabelNameFilter $subject;

    /**
     * @return list<array{0: string}>
     */
    public static function placeholderNameDataProvider(): array
    {
        return [
            ['[no label]'],
            // tagging tools run several fields together, so the placeholder is only a prefix
            ['[no label]B.D. Records'],
            ['[no label]Many Hats EndeavorsOat Milk Industries'],
            ['[FWD:'],
            ['Not On Label (Mortiis Self-released)'],
            ['Not On Label (Scott Bradlee & Postmodern Jukebox Self-released)'],
            ['Not On Label (Static-x Self-released)'],
            ['Self Release'],
            ['Self Released'],
            ['Self-Released'],
            ['Self-Released/Independent'],
            ['Wild Fire (Self-Released)'],
            // fewer than two letters or digits is always a fragment, never a publisher
            ['/<'],
            ['/v/'],
            ['−N'],
        ];
    }

    /**
     * @return list<array{0: string}>
     */
    public static function realNameDataProvider(): array
    {
        return [
            ['B.D. Records'],
            ['Netwaves Records'],
            ['Negative Gain Productions'],
            ['Warp Records'],
            // short names are real labels as long as they carry two alphanumerics
            ['XL'],
            ['4AD'],
            ['!K7'],
            // these share a prefix with the self-released placeholders but are genuine
            ['Self Esteem Records'],
            ['Selfish Records'],
        ];
    }

    public function testFilterDropsPlaceholdersAndReindexes(): void
    {
        $this->configContainer->method('get')
            ->willReturn(null);

        static::assertSame(
            ['Warp Records', 'B.D. Records'],
            $this->subject->filter(['Warp Records', '[no label]', 'B.D. Records', 'Self-Released'])
        );
    }

    #[DataProvider('placeholderNameDataProvider')]
    public function testIsIgnoredDetectsPlaceholderNames(string $labelName): void
    {
        static::assertTrue($this->subject->isIgnored($labelName));
    }

    public function testIsIgnoredKeepsEveryNameWhenThePatternCannotMatch(): void
    {
        $this->configContainer->method('get')
            ->with(ConfigurationKeyEnum::LABEL_IGNORE_PATTERN)
            ->willReturn('(?!)');

        static::assertFalse($this->subject->isIgnored('[no label]'));
    }

    #[DataProvider('realNameDataProvider')]
    public function testIsIgnoredKeepsRealNames(string $labelName): void
    {
        static::assertFalse($this->subject->isIgnored($labelName));
    }

    public function testIsIgnoredKeepsTheNameWhenThePatternIsBroken(): void
    {
        $this->configContainer->method('get')
            ->with(ConfigurationKeyEnum::LABEL_IGNORE_PATTERN)
            ->willReturn('([unclosed');

        // dropping a label because the admin mistyped a regex would be silent data loss
        static::assertFalse(@$this->subject->isIgnored('[no label]'));
    }

    public function testIsIgnoredUsesAConfiguredPatternInsteadOfTheDefault(): void
    {
        $this->configContainer->method('get')
            ->with(ConfigurationKeyEnum::LABEL_IGNORE_PATTERN)
            ->willReturn('^banned');

        static::assertTrue($this->subject->isIgnored('Banned Records'));
        // the configured pattern replaces the default, so the built-in placeholders now pass
        static::assertFalse($this->subject->isIgnored('[no label]'));
    }

    protected function setUp(): void
    {
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);

        $this->subject = new LabelNameFilter(
            $this->configContainer,
        );
    }
}

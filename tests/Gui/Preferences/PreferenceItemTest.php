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

namespace Ampache\Gui\Preferences;

use PHPUnit\Framework\TestCase;

class PreferenceItemTest extends TestCase
{
    public function testASecretNeverReportsWhetherItDiffersFromTheShippedDefault(): void
    {
        $secret = $this->item(value: '25', shippedDefault: '10', systemValue: '99', isSecret: true);

        $this->assertFalse($secret->differsFromShipped());
    }

    public function testDiffersFromShippedComparesAgainstTheShippedValue(): void
    {
        $this->assertFalse($this->item(value: '10')->differsFromShipped());
        $this->assertTrue($this->item(value: '25')->differsFromShipped());
    }

    public function testDiffersFromShippedIsFalseWhenNoDefaultIsKnown(): void
    {
        $this->assertFalse($this->item(value: '', shippedDefault: null)->differsFromShipped());
    }

    public function testInputIdIsNamespaced(): void
    {
        $this->assertSame('pref-popular_threshold', $this->item()->inputId());
    }

    private function item(
        string $value = '10',
        ?string $shippedDefault = '10',
        ?string $systemValue = '10',
        bool $isSecret = false,
        ?array $choices = null,
    ): PreferenceItem {
        return new PreferenceItem(
            name: 'popular_threshold',
            description: 'Popular Threshold',
            type: PreferenceType::INTEGER,
            subcategory: 'query',
            level: 25,
            value: $value,
            shippedDefault: $shippedDefault,
            systemValue: $systemValue,
            choices: $choices,
            editable: true,
            isSecret: $isSecret,
            secretIsSet: false,
            help: null,
        );
    }
}

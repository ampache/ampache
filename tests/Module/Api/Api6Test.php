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

namespace Ampache\Module\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * NOTE: Api6 carries its own copy of parameter_exists(), so it needs its own
 */
class Api6Test extends TestCase
{
    /**
     * values that must be rejected as "not sent"
     *
     * @return array<string, array{0: mixed}>
     */
    public static function emptyValueProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'empty array' => [[]],
        ];
    }

    /**
     * values that must be accepted as sent -- `0` and `'0'` are real values, not absence
     *
     * @return array<string, array{0: mixed}>
     */
    public static function presentValueProvider(): array
    {
        return [
            'non-empty string' => ['123'],
            'string zero' => ['0'],
            'int zero' => [0],
            'bool false' => [false],
            'non-empty array' => [['song']],
        ];
    }

    public function testParameterExistsMatchesTheApiImplementation(): void
    {
        $input = ['filter' => '', 'type' => 'song'];

        self::assertSame(
            Api::parameter_exists($input, ['filter', 'type']),
            Api6::parameter_exists($input, ['filter', 'type'])
        );
    }

    public function testParameterExistsReportsTheFirstMissingParameter(): void
    {
        self::assertSame(
            'type',
            Api6::parameter_exists(['filter' => '123', 'type' => '', 'auth' => ''], ['filter', 'type', 'auth'])
        );
    }

    public function testParameterExistsReturnsTheNameOfAnAbsentParameter(): void
    {
        self::assertSame('filter', Api6::parameter_exists([], ['filter']));
    }

    public function testParameterExistsReturnsTrueForAnEmptyParameterList(): void
    {
        self::assertTrue(Api6::parameter_exists([], []));
    }

    public function testParameterExistsReturnsTrueWhenEveryParameterIsSet(): void
    {
        self::assertTrue(
            Api6::parameter_exists(['filter' => '123', 'type' => 'song'], ['filter', 'type'])
        );
    }

    #[DataProvider(methodName: 'emptyValueProvider')]
    public function testParameterExistsTreatsEmptyValuesAsMissing(mixed $value): void
    {
        self::assertSame('filter', Api6::parameter_exists(['filter' => $value], ['filter']));
    }

    #[DataProvider(methodName: 'presentValueProvider')]
    public function testParameterExistsTreatsPresentValuesAsSent(mixed $value): void
    {
        self::assertTrue(Api6::parameter_exists(['filter' => $value], ['filter']));
    }
}

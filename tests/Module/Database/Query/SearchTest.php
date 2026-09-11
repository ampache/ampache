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

namespace Ampache\Module\Database\Query;

use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Repository\CatalogRepositoryInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

class SearchTest extends MockeryTestCase
{
    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function maliciousOperatorProvider(): array
    {
        return [
            'and stays and' => ['and', 'and'],
            'or stays or' => ['or', 'or'],
            'uppercase or' => ['OR', 'or'],
            'injection collapses' => ['AND (SELECT 1)-- -', 'and'],
            'garbage collapses' => ['x', 'and'],
        ];
    }

    /**
     * rule names the search form offers that get_rule_type_by_name() once failed to resolve, with their basetype
     *
     * @return list<array{string, string}>
     */
    public static function ruleNameProvider(): array
    {
        return [
            ['summary', 'text'],
            ['placeformed', 'text'],
            ['release_type', 'text'],
            ['release_status', 'text'],
            ['version', 'text'],
            ['barcode', 'text'],
            ['catalog_number', 'text'],
            ['favorite', 'text'],
            ['favorite_album', 'text'],
            ['favorite_artist', 'text'],
            ['yearformed', 'numeric'],
            ['bitrate', 'numeric'],
            ['image_width', 'numeric'],
            ['image_height', 'numeric'],
            ['video_count', 'numeric'],
            ['genre_count_song', 'numeric'],
            ['genre_count_album', 'numeric'],
            ['genre_count_artist', 'numeric'],
            ['weight_song', 'numeric'],
            ['weight_album', 'numeric'],
            ['weight_artist', 'numeric'],
            ['weight_podcast', 'numeric'],
            ['weight_podcast_episode', 'numeric'],
            ['has_image', 'boolean'],
            ['waveform', 'boolean'],
            ['duplicate_mbid_group', 'is_true'],
            ['type', 'boolean_numeric'],
            ['owner', 'boolean_numeric'],
        ];
    }

    /**
     * every type that Search::set_order_by() can sort by popularity weight
     *
     * @return list<array{string}>
     */
    public static function weightTypeProvider(): array
    {
        return [
            ['album'],
            ['album_disk'],
            ['artist'],
            ['podcast'],
            ['podcast_episode'],
            ['song'],
            ['video'],
        ];
    }

    #[DataProvider('ruleNameProvider')]
    public function testEveryOfferedRuleNameResolvesToItsBaseType(string $rule, string $expected): void
    {
        $search = new Search(0, 'song');

        $this->assertSame($expected, $search->get_rule_type_by_name($rule), sprintf('rule "%s" does not resolve and would be dropped', $rule));
    }

    #[DataProvider('maliciousOperatorProvider')]
    public function testNormalizeLogicOperatorOnlyEverYieldsAndOrOr(string $operator, string $expected): void
    {
        // the shared normaliser is what update() applies too; anything but "or" must become "and"
        $method = new ReflectionMethod(Search::class, 'normalizeLogicOperator');

        self::assertSame($expected, $method->invoke(null, $operator));
    }

    /**
     * set_rules() drops a rule whose name does not resolve, so an unresolvable name makes the search return every
     * object instead of a filtered list
     */
    #[DataProvider('maliciousOperatorProvider')]
    public function testSetRulesNeutralisesTheLogicOperator(string $operator, string $expected): void
    {
        // the operator glues the where conditions together, so anything but "or" must collapse to "and"
        $search = new Search(0, 'song');
        $search->set_rules(['operator' => $operator]);

        self::assertSame($expected, $search->logic_operator);
    }

    /**
     * MySQL rejects an ORDER BY column missing from a DISTINCT select list (error 3065), which broke every header
     * bar artist search once weight sorting was added
     */
    #[DataProvider('weightTypeProvider')]
    public function testWeightSortedSearchSelectsEveryOrderedColumn(string $type): void
    {
        $sql = Search::prepare(['limit' => 5, 'type' => $type, 'weight' => true])['sql'];

        $this->assertMatchesRegularExpression('/ORDER BY `' . $type . '`\.`weight` DESC/', $sql);

        preg_match('/^SELECT (.*?) FROM /', $sql, $select);
        preg_match('/ ORDER BY (.*?)( LIMIT |$)/', $sql, $order);
        $this->assertNotEmpty($select);
        $this->assertNotEmpty($order);

        // only DISTINCT queries are affected here, a plain select is free to order by any column of a joined table
        if (!str_starts_with($select[1], 'DISTINCT')) {
            return;
        }

        foreach (explode(',', $order[1]) as $column) {
            $column = trim(str_ireplace([' DESC', ' ASC'], '', $column));
            $this->assertStringContainsString($column, $select[1], sprintf('%s is ordered by %s but does not select it', $type, $column));
        }
    }

    #[Override]
    protected function setUp(): void
    {
        // Search::prepare() builds a user, which reaches Catalog::get_catalogs() through the `global $dic` bridge
        $catalogRepository = $this->createMock(CatalogRepositoryInterface::class);
        $catalogRepository->method('getIds')->willReturn([]);

        $globalDic = $this->createMock(ContainerInterface::class);
        $globalDic->method('get')->willReturnCallback(fn(string $id): object => match ($id) {
            CatalogRepositoryInterface::class => $catalogRepository,
            // the searches ask whether the searcher may see withdrawn items before adding their condition
            PrivilegeCheckerInterface::class => $this->createMock(PrivilegeCheckerInterface::class),
            default => $this->createMock(LoggerInterface::class),
        });
        $GLOBALS['dic'] = $globalDic;
    }
}

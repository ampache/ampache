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

use Ampache\Config\AmpConfig;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Repository\CatalogRepositoryInterface;
use Override;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

class SearchTest extends MockeryTestCase
{
    private string $originalDatabaseName;

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

    /**
     * a duration budget stops once what remains is too little to usefully fit anything else
     */
    public function testApplyDurationAndSizeLimitsCapsByTotalDuration(): void
    {
        $rows = [
            1 => ['size' => 0, 'time' => 120],
            2 => ['size' => 0, 'time' => 120],
            3 => ['size' => 0, 'time' => 60],
            4 => ['size' => 0, 'time' => 60],
        ];

        $this->assertSame([1, 2, 3], Search::applyDurationAndSizeLimits($rows, 6, 0));
    }

    /**
     * a size budget skips an oversized row rather than stopping the whole pass there, so a smaller row
     * further down the ordered list can still fit -- proves the fuzzy behavior, not just a hard cutoff
     */
    public function testApplyDurationAndSizeLimitsFuzzilySkipsAnOversizedRow(): void
    {
        $rows = [
            1 => ['size' => 3 * 1024 * 1024, 'time' => 0],
            2 => ['size' => 3 * 1024 * 1024, 'time' => 0],
            3 => ['size' => 5 * 1024 * 1024, 'time' => 0],
            4 => ['size' => 1 * 1024 * 1024, 'time' => 0],
        ];

        $this->assertSame([1, 2, 4], Search::applyDurationAndSizeLimits($rows, 0, 10));
    }

    /**
     * no budget set at all returns every id, in the order it was handed in
     */
    public function testApplyDurationAndSizeLimitsReturnsEveryIdWhenNoBudgetIsSet(): void
    {
        $rows = [
            5 => ['size' => 999999, 'time' => 999999],
            2 => ['size' => 999999, 'time' => 999999],
        ];

        $this->assertSame([5, 2], Search::applyDurationAndSizeLimits($rows, 0, 0));
    }

    #[DataProvider('ruleNameProvider')]
    public function testEveryOfferedRuleNameResolvesToItsBaseType(string $rule, string $expected): void
    {
        $search = new Search(0, 'song');

        $this->assertSame($expected, $search->get_rule_type_by_name($rule), sprintf('rule "%s" does not resolve and would be dropped', $rule));
    }

    /**
     * a saved search predating groups has no group-0 wrapper at all, so the primary selector falls back
     * to the search's own operator -- the value that combined those rules before
     */
    public function testGetPrimaryGroupOperatorFallsBackToLogicOperatorForLegacyData(): void
    {
        $search                 = new Search(0, 'song');
        $search->logic_operator = 'or';
        $search->rules          = [['title', 'contain', 'Rock', null]];

        $this->assertSame('or', $search->getPrimaryGroupOperator());
    }

    /**
     * the primary group's own operator is read from its saved wrapper, not just mirrored from the
     * search's outer operator -- the two can diverge once the user edits either selector independently
     */
    public function testGetPrimaryGroupOperatorReadsTheSavedGroupZeroWrapper(): void
    {
        $search                 = new Search(0, 'song');
        $search->logic_operator = 'and';
        $search->rules          = [
            [
                'group_id' => 0,
                'group_operator' => 'or',
                'rules' => [['title', 'contain', 'Rock', null]],
            ],
        ];

        $this->assertSame('or', $search->getPrimaryGroupOperator());
    }

    #[DataProvider('maliciousOperatorProvider')]
    public function testNormalizeLogicOperatorOnlyEverYieldsAndOrOr(string $operator, string $expected): void
    {
        // the shared normalizer is what update() applies too; anything but "or" must become "and"
        $method = new ReflectionMethod(Search::class, 'normalizeLogicOperator');

        self::assertSame($expected, $method->invoke(null, $operator));
    }

    /**
     * loading a saved grouped search hard-crashed here under `strict_types=1` before the fix, because a group
     * wrapper has no integer key `0` for `_set_rule_name()` to index into
     */
    public function testNormalizeRuleNamesRecursesIntoAGroupWrapper(): void
    {
        $search        = new Search(0, 'song');
        $search->rules = [
            ['song', 'equal', 'Rock', null],
            [
                'group_operator' => 'or',
                'rules' => [
                    ['song', 'equal', 'Pop', null],
                    ['tag', 'equal', 'Jazz', null],
                ],
            ],
        ];

        $method = new ReflectionMethod(Search::class, '_normalizeRuleNames');
        $method->invoke($search);

        $this->assertSame('title', $search->rules[0][0]);
        $this->assertSame('title', $search->rules[1]['rules'][0][0]);
        $this->assertSame('genre', $search->rules[1]['rules'][1][0]);
    }

    /**
     * `prepare(require_rules: true)` reads the raw per-builder `'where'` array (not `'where_sql'`) to decide
     * whether any rule matched -- a grouped search must still leave it non-empty, not just the combined string
     */
    public function testPrepareWithRequireRulesSeesATruthyWhereAfterGrouping(): void
    {
        $this->stubDbaEscape();

        $result = Search::prepare([
            'type' => 'song',
            'rule_1' => 'title',
            'rule_1_operator' => 4,
            'rule_1_input' => 'Rock',
            'rule_1_group' => 1,
            'rule_2' => 'title',
            'rule_2_operator' => 4,
            'rule_2_input' => 'Pop',
            'rule_2_group' => 1,
            'group_1_operator' => 'or',
        ], null, true);

        $this->assertNotSame('', $result['sql']);
    }

    /**
     * `rule_<N>_group` buckets rules into a `group_operator`/`rules` wrapper, using that group's own operator
     */
    public function testSetRulesBucketsGroupedRulesIntoAWrapper(): void
    {
        $this->stubDbaEscape();
        $search = new Search(0, 'song');
        $search->set_rules([
            'operator' => 'and',
            'rule_1' => 'title',
            'rule_1_operator' => 4,
            'rule_1_input' => 'Rock',
            'rule_1_group' => 1,
            'rule_2' => 'title',
            'rule_2_operator' => 4,
            'rule_2_input' => 'Pop',
            'rule_2_group' => 1,
            'group_1_operator' => 'or',
        ]);

        $this->assertSame([
            [
                'group_id' => 1,
                'group_operator' => 'or',
                'rules' => [
                    ['title', 'equal', 'Rock', null],
                    ['title', 'equal', 'Pop', null],
                ],
            ],
        ], $search->rules);
    }

    /**
     * two distinct group ids, with rule numbers interleaved rather than consecutive per group, each
     * keep only their own rules and operator; the ungrouped rule stays at the top level as before
     */
    public function testSetRulesBucketsMultipleIndependentGroups(): void
    {
        $this->stubDbaEscape();
        $search = new Search(0, 'song');
        $search->set_rules([
            'operator' => 'and',
            'rule_1' => 'title',
            'rule_1_operator' => 4,
            'rule_1_input' => 'Rock',
            'rule_1_group' => 1,
            'rule_2' => 'time',
            'rule_2_operator' => 4,
            'rule_2_input' => '3',
            'rule_3' => 'title',
            'rule_3_operator' => 4,
            'rule_3_input' => 'Foo',
            'rule_3_group' => 2,
            'rule_4' => 'title',
            'rule_4_operator' => 4,
            'rule_4_input' => 'Pop',
            'rule_4_group' => 1,
            'rule_5' => 'title',
            'rule_5_operator' => 4,
            'rule_5_input' => 'Bar',
            'rule_5_group' => 2,
            'group_1_operator' => 'or',
            'group_2_operator' => 'and',
        ]);

        $this->assertSame([
            ['time', 'gt', '3', null],
            [
                'group_id' => 1,
                'group_operator' => 'or',
                'rules' => [
                    ['title', 'equal', 'Rock', null],
                    ['title', 'equal', 'Pop', null],
                ],
            ],
            [
                'group_id' => 2,
                'group_operator' => 'and',
                'rules' => [
                    ['title', 'equal', 'Foo', null],
                    ['title', 'equal', 'Bar', null],
                ],
            ],
        ], $search->rules);
    }

    /**
     * `_filter_request()`'s prefix allowlist once dropped `group_<G>_operator` silently, so a sent "or" always
     * came back as the "and" default -- this is the fix, not polish
     */
    public function testSetRulesDoesNotDropTheGroupOperatorKey(): void
    {
        $this->stubDbaEscape();
        $search = new Search(0, 'song');
        $search->set_rules([
            'rule_1' => 'title',
            'rule_1_operator' => 4,
            'rule_1_input' => 'Rock',
            'rule_1_group' => 5,
            'group_5_operator' => 'or',
        ]);

        $this->assertSame('or', $search->rules[0]['group_operator']);
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
     * a legacy payload with no group keys must produce byte-identical `$rules` to today -- the critical
     * regression guarantee for the grouped-rules feature
     */
    public function testSetRulesWithNoGroupKeysProducesTheLegacyFlatShape(): void
    {
        $this->stubDbaEscape();
        $search = new Search(0, 'song');
        $search->set_rules([
            'operator' => 'and',
            'rule_1' => 'title',
            'rule_1_operator' => 4,
            // 'equal'
            'rule_1_input' => 'Rock',
        ]);

        $this->assertSame([['title', 'equal', 'Rock', null]], $search->rules);
    }

    /**
     * a saved primary-group (id 0) rule next to an explicit group must keep its own id on reconstruction,
     * not get shifted to 1 by a sequential counter that can't tell the two wrapper shapes apart
     */
    public function testToJsKeepsThePrimaryGroupIdSeparateFromAnExplicitGroup(): void
    {
        $search        = new Search(0, 'song');
        $search->rules = [
            [
                'group_id' => 0,
                'group_operator' => 'and',
                'rules' => [['title', 'contain', 'Primary Rule Song', null]],
            ],
            [
                'group_id' => 1,
                'group_operator' => 'or',
                'rules' => [['title', 'contain', 'Grouped Rule Song', null]],
            ],
        ];

        $this->assertSame(
            '<script>SearchRow.add("title","contain","Primary Rule Song", "", 0); </script>'
            . '<script>SearchRow.addGroup(1, "or"); </script>'
            . '<script>SearchRow.add("title","contain","Grouped Rule Song", "", 1); </script>',
            $search->to_js()
        );
    }

    /**
     * `to_js()` hard-crashed here under `strict_types=1` before the fix, because a group wrapper has no
     * integer key `0`/`1`/`2`/`3` for the old unguarded `$rule[0]`-style indexing
     */
    public function testToJsOpensAGroupBeforeItsRulesAndTargetsThem(): void
    {
        $search        = new Search(0, 'song');
        $search->rules = [
            [
                'group_operator' => 'or',
                'rules' => [['title', 'contain', 'Rock', null]],
            ],
        ];

        $this->assertSame(
            '<script>SearchRow.addGroup(1, "or"); </script>'
            . '<script>SearchRow.add("title","contain","Rock", "", 1); </script>',
            $search->to_js()
        );
    }

    /**
     * an ungrouped (legacy) rule reconstructs into the always-present primary group, id `0`
     */
    public function testToJsTargetsThePrimaryGroupForAnUngroupedRule(): void
    {
        $search        = new Search(0, 'song');
        $search->rules = [['title', 'contain', 'Rock', null]];

        $this->assertSame(
            '<script>SearchRow.add("title","contain","Rock", "", 0); </script>',
            $search->to_js()
        );
    }

    /**
     * truncateByLimits() never reaches `Dba::read()` for a type with no natural per-row size/time of its own
     * (an aggregate type like album or artist), so this is provable without a database
     */
    public function testTruncateByLimitsLeavesNonSongVideoTypesUntouched(): void
    {
        $this->assertSame([1, 2, 3], Search::truncateByLimits([1, 2, 3], 'album', 60, 100));
    }

    /**
     * with no length and no size_limit set there is nothing to trim, so the ids pass straight through
     * without a database query
     */
    public function testTruncateByLimitsLeavesTheListUntouchedWithNoBudgetSet(): void
    {
        $this->assertSame([1, 2, 3], Search::truncateByLimits([1, 2, 3], 'song', 0, 0));
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
        $this->originalDatabaseName = (string) AmpConfig::get('database_name', '');

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

    #[Override]
    protected function tearDown(): void
    {
        // AmpConfig is a static global shared by the whole process; undo stubDbaEscape() so later tests in this
        // run don't inherit a fake database name and a mock PDO that cannot run a real query
        AmpConfig::set('database_name', $this->originalDatabaseName, true);
        AmpConfig::set('dbh_test', null, true);
    }

    /**
     * `set_rules()` routes every `rule_*`/`group_*` value through `Dba::escape()`, which answers `''` with no
     * database configured -- give it a mock `PDO` handle so a plain alphanumeric value round-trips unchanged.
     */
    private function stubDbaEscape(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('quote')->willReturnCallback(static fn(string $value): string => "'" . addslashes($value) . "'");

        AmpConfig::set('database_name', 'test', true);
        AmpConfig::set('dbh_test', $pdo, true);
    }
}

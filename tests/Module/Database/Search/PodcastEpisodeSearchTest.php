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

namespace Ampache\Module\Database\Search;

use Ampache\Config\AmpConfig;
use Ampache\MockeryTestCase;
use Ampache\Module\Database\Query\Search;
use Ampache\Repository\Model\User;

class PodcastEpisodeSearchTest extends MockeryTestCase
{
    /**
     * `?` placeholder order must match parameter order across two groups, not rule-definition order
     */
    public function testParameterOrderMatchesAcrossGroups(): void
    {
        $result = $this->ruleSql([
            [
                'group_operator' => 'or',
                'rules' => [
                    ['id', 'gt', 5],
                    ['id', 'gt', 9],
                ],
            ],
            ['played_times', 'gt', 3],
        ]);

        $this->assertSame([5, 9, 3], $result['parameters']);
    }

    /**
     * an OR'd group combined with an ungrouped rule parenthesizes only the group
     */
    public function testTwoGroupsCombineWithParensAndOuterOperator(): void
    {
        $result = $this->ruleSql([
            [
                'group_operator' => 'or',
                'rules' => [
                    ['id', 'gt', 5],
                    ['id', 'gt', 9],
                ],
            ],
            ['played_times', 'gt', 3],
        ]);

        $this->assertSame(
            '(`podcast_episode`.`id` > ? OR `podcast_episode`.`id` > ?) AND (`podcast_episode`.`total_count` > ?)',
            $result['where_sql']
        );
    }

    /**
     * an ungrouped rule list must produce today's exact flat implode, with no parens added anywhere
     */
    public function testUngroupedRulesProduceAFlatImplode(): void
    {
        $result = $this->ruleSql([
            ['id', 'gt', 5],
            ['played_times', 'gt', 3],
        ]);

        $this->assertSame('`podcast_episode`.`id` > ? AND (`podcast_episode`.`total_count` > ?)', $result['where_sql']);
        $this->assertSame([5, 3], $result['parameters']);
    }

    /**
     * @param list<array<int, mixed>|array{group_operator: string, rules: list<array<int, mixed>>}> $rules
     * @return array{where_sql: string, parameters: array<int, mixed>}
     */
    private function ruleSql(array $rules): array
    {
        AmpConfig::set('catalog_disable', false, true);
        AmpConfig::set('catalog_filter', false, true);

        $user = $this->mock(User::class);
        $user->shouldReceive('getId')->andReturn(2);

        $search              = new Search(0, 'podcast_episode', $user);
        $search->search_user = $user;
        // set_rules() reaches an access check, so the rules are given in the shape it would have produced
        $search->rules = $rules;

        $result = new PodcastEpisodeSearch()->getSql($search);

        return [
            'where_sql' => $result['where_sql'],
            'parameters' => $result['parameters'],
        ];
    }
}

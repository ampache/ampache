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
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;

class QueryTest extends MockeryTestCase
{
    /**
     * @return list<array{0: string}>
     */
    public static function refusedModeDataProvider(): array
    {
        return [
            [''],
            ['not_like'],
            ['alpha_match'],
            ['exact_match'],
            ['1 OR 1=1'],
        ];
    }

    public function testABrowseStoredBeforeCustomSqlStillServesItsBaseAsIs(): void
    {
        // an old serialized browse holds its custom query in `base` with the `custom` flag set;
        // that stored shape has to keep working after the upgrade
        $query = $this->subject();
        $state = new ReflectionProperty(Query::class, '_state');

        $rebuilt           = $state->getValue($query);
        $rebuilt['type']   = 'song';
        $rebuilt['custom'] = true;
        $rebuilt['base']   = 'SELECT `id` FROM `legacy_view` ';
        $state->setValue($query, $rebuilt);

        $sql = (string) new ReflectionMethod(Query::class, '_get_sql')->invoke($query, false, false);

        self::assertSame('SELECT `id` FROM `legacy_view`', trim($sql));
    }

    public function testACustomBaseRestrictsTheQueryInsteadOfReplacingIt(): void
    {
        // a custom base used to overwrite the whole base query, which silently dropped every
        // filter, group and sort the browse carried. It now joins as a derived table, so the
        // normal query survives around it.
        $query = $this->subject();
        $query->set_type('song', 'SELECT `id` FROM `song` WHERE `user_upload` = 42', []);
        $query->set_filter('license', 3);

        $sql = (string) new ReflectionMethod(Query::class, '_get_sql')->invoke($query, false, false);

        self::assertStringContainsString(
            'JOIN (SELECT `id` FROM `song` WHERE `user_upload` = 42) AS `custom_base` ON `custom_base`.`id` = `song`.`id`',
            $sql
        );
        self::assertStringContainsString("`song`.`license` = '3'", $sql, 'the filter has to survive the custom base');
        self::assertStringStartsWith('SELECT `song`.`id` FROM `song`', $sql, 'the normal base has to stay intact');
    }

    public function testClearFilterIgnoresAFilterThatWasNeverSet(): void
    {
        $query = $this->subject();
        $query->clear_filter('like');

        $this->assertNull($query->get_filter('like'));
    }

    public function testMatchModeDefaultsToStartsWith(): void
    {
        $this->assertSame('starts_with', $this->subject()->get_match_mode());
    }

    /**
     * A rejected mode has to leave the previous one alone: the filter box posts whatever arrives in the
     * request, and falling back to the default would silently switch the user's choice back.
     */
    #[DataProvider('refusedModeDataProvider')]
    public function testMatchModeRefusesAnythingElse(string $refused): void
    {
        $query = $this->subject();
        $query->set_match_mode('like');
        $query->set_match_mode($refused);

        $this->assertSame('like', $query->get_match_mode());
    }

    public function testMatchModeRemembersAnOfferedMode(): void
    {
        $query = $this->subject();
        $query->set_match_mode('like');

        $this->assertSame('like', $query->get_match_mode());
    }

    public function testMatchModesAreTheOnesTheFilterBoxOffers(): void
    {
        $this->assertSame(['starts_with', 'like'], Query::MATCH_MODES);
    }

    /**
     * An uncached query keeps its state in memory and never reaches the database.
     */
    public function testRebuildingAFilterDoesNotReplayTheChildTypeSetup(): void
    {
        // A browse restored from tmp_browse carries its type in state but no query object yet.
        // Browse::set_type() replays the view cookies, and restoring the alpha one sets a filter,
        // which needs the query object, which used to go back through the virtual set_type():
        // an infinite recursion that took the whole page down. The filter path has to resolve
        // the type without handing control back to the child override.
        $this->bootPrivilegeChecker();

        $query = new class (0, false) extends Query {
            public int $overrideCalls = 0;

            public function set_type(string $type, ?string $custom_base = '', ?array $parameters = []): void
            {
                $this->overrideCalls++;
                if ($this->overrideCalls > 3) {
                    throw new RuntimeException('recursed through the child set_type override');
                }

                // what Browse does when the alpha view cookie says false
                $this->set_filter('regex_not_match', '');
                parent::set_type($type, $custom_base, $parameters);
            }
        };

        // the state a rebuilt browse is in: a type on record, no query object resolved yet
        $state           = new ReflectionProperty(Query::class, '_state');
        $rebuilt         = $state->getValue($query);
        $rebuilt['type'] = 'song';
        $state->setValue($query, $rebuilt);

        $query->set_type('song');

        $this->assertSame('song', $query->get_type());
        $this->assertSame(1, $query->overrideCalls);
    }

    /**
     * A regex_match/regex_not_match value reaches SQL REGEXP verbatim, so an unbounded one is a
     * catastrophic-backtracking DoS risk; it must be refused rather than silently truncated
     */
    public function testSetFilterRefusesARegexMatchOverTheLengthLimit(): void
    {
        $query = $this->subject();

        $accepted = $query->set_filter('regex_match', str_repeat('a', 100));
        $refused  = $query->set_filter('regex_not_match', str_repeat('a', 101));

        $this->assertTrue($accepted);
        $this->assertFalse($refused);
        $this->assertSame(str_repeat('a', 100), $query->get_filter('regex_match'));
        $this->assertNull($query->get_filter('regex_not_match'));
    }

    /**
     * An uncached query keeps its state in memory and never reaches the database.
     */
    /**
     * The browse asks whether the caller may see withdrawn rows, so a checker has to be reachable.
     */
    private function bootPrivilegeChecker(): void
    {
        $privilegeChecker = $this->mock(PrivilegeCheckerInterface::class);
        $privilegeChecker->shouldReceive('check')->andReturnFalse();

        $dic = $this->mock(ContainerInterface::class);
        $dic->shouldReceive('get')->andReturn($privilegeChecker);

        $GLOBALS['dic'] = $dic;
    }

    private function subject(): Query
    {
        $this->bootPrivilegeChecker();

        return new Query(0, false);
    }
}

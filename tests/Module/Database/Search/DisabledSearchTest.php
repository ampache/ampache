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

use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Database\Query\Search;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\PlaylistRepositoryInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * A smartlist reaches the rows without going through a browse, so the withdrawn items have to be excluded
 * here on their own. The rule is what lets the manager who can see them list them.
 */
class DisabledSearchTest extends TestCase
{
    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function typeProvider(): array
    {
        return [
            ['album', '`album`.`enabled` = 1'],
            ['artist', '`artist`.`enabled` = 1'],
            ['song', '`song`.`enabled` = 1'],
        ];
    }

    #[DataProvider(methodName: 'typeProvider')]
    public function testAListenerNeverSeesAWithdrawnItemInASmartlist(string $type, string $expected): void
    {
        $search = $this->search($type, false);

        self::assertStringContainsString($expected, $search->to_sql()['where_sql']);
    }

    #[DataProvider(methodName: 'typeProvider')]
    public function testAManagerSearchesWithoutTheCondition(string $type, string $expected): void
    {
        $search = $this->search($type, true);

        self::assertStringNotContainsString($expected, $search->to_sql()['where_sql']);
    }

    /**
     * The counterpart of the browse rule: a smartlist of songs reaches the rows on its own, and it must not
     * start filtering on the parent either, or a song published on its own would vanish from it.
     */
    public function testASongSmartlistNeverFiltersOnTheStateOfItsParents(): void
    {
        $where = $this->search('song', false)->to_sql()['where_sql'];

        self::assertStringNotContainsString('`album`.`enabled`', $where);
        self::assertStringNotContainsString('`artist`.`enabled`', $where);
    }

    /**
     * The rule is what lets the manager who can see withdrawn items list them; an unregistered name is
     * dropped by set_rules() without a word, so the registration is what has to be pinned.
     */
    #[DataProvider(methodName: 'typeProvider')]
    public function testTheRuleIsOfferedOnEveryType(string $type, string $expected): void
    {
        $search = $this->search($type, true);

        self::assertSame('boolean', $search->get_rule_type_by_name('enabled'));
        self::assertContains('enabled', array_column($search->get_rule_types(), 'name'));
    }

    private function search(string $type, bool $isManager): Search
    {
        $privilegeChecker = $this->createMock(PrivilegeCheckerInterface::class);
        $privilegeChecker->method('check')->willReturn($isManager);

        $catalogRepository = $this->createMock(CatalogRepositoryInterface::class);
        $catalogRepository->method('getIds')->willReturn([]);

        $dic = $this->createMock(ContainerInterface::class);
        $dic->method('get')->willReturnCallback(fn(string $id): object => match ($id) {
            CatalogRepositoryInterface::class => $catalogRepository,
            PlaylistRepositoryInterface::class => $this->createMock(PlaylistRepositoryInterface::class),
            PrivilegeCheckerInterface::class => $privilegeChecker,
            default => $this->createMock(LoggerInterface::class),
        });

        $GLOBALS['dic'] = $dic;

        return new Search(0, $type);
    }
}

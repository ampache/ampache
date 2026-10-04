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

use Ampache\Plugin\AmpacheRatingMatch;
use Ampache\Plugin\PluginPreferenceHelpInterface;
use Ampache\PluginFactoryMockTrait;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use DI\Container;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PreferenceCollectorPluginHelpTest extends TestCase
{
    use PluginFactoryMockTrait;

    private int $builds = 0;

    public function testAnUnknownPluginIsNotFatal(): void
    {
        $this->assertNull($this->helpFor($this->row('whatever', 'plugins', 'NoSuchPlugin')));
    }

    public function testAPluginAnswersForItsOwnPreference(): void
    {
        $help = $this->helpFor($this->row('ratingmatch_write_tags'));

        $this->assertInstanceOf(PreferenceHelp::class, $help);
        $this->assertStringContainsString('rating', $help->text);
    }

    public function testAPluginDeclinesForAPreferenceItDoesNotOwn(): void
    {
        $this->assertNull($this->helpFor($this->row('some_other_option')));
    }

    public function testAPluginPreferenceWithNoOwnerNamedIsNeverAsked(): void
    {
        $this->assertNull($this->helpFor($this->row('whatever', 'plugins', null)));
    }

    public function testAPreferenceOutsideThePluginsCategoryIsNeverAsked(): void
    {
        $this->helpFor($this->row('download', 'options'));

        $this->assertSame(0, $this->builds, 'a preference outside the plugins category must not build one');
    }

    public function testTheAnswerDescribesTheValueTheFieldHolds(): void
    {
        $empty = $this->helpFor($this->row('ratingmatch_star3_rule', value: ''));
        $plays = $this->helpFor($this->row('ratingmatch_star3_rule', value: '5'));
        $both  = $this->helpFor($this->row('ratingmatch_star3_rule', value: '5,2'));

        $this->assertNotNull($empty);
        $this->assertNotNull($plays);
        $this->assertNotNull($both);
        $this->assertStringContainsString('never applied', $empty->text);
        $this->assertStringContainsString('5 plays', $plays->text);
        $this->assertStringContainsString('5 plays and 2 skips', $both->text);
        $this->assertNotSame($plays->text, $both->text, 'the same field with a different value reads differently');
    }

    public function testThePluginIsBuiltOnlyOncePerRequest(): void
    {
        $helps = $this->helpForAll([$this->row('ratingmatch_star1_rule'), $this->row('ratingmatch_star2_rule')]);

        $this->assertCount(2, array_filter($helps));
        $this->assertSame(1, $this->builds, 'a tab of plugin preferences must not rebuild the plugin per row');
    }

    public function testTheRatingMatchPluginDeclaresTheInterface(): void
    {
        $this->assertContains(
            PluginPreferenceHelpInterface::class,
            class_implements(AmpacheRatingMatch::class) ?: []
        );
    }

    protected function setUp(): void
    {
        // `Plugin` builds instances with make() through the `global $dic` bridge
        $dic = $this->createMock(Container::class);
        $dic->method('get')->willReturnCallback(fn(string $id): object => match ($id) {
            UpdateInfoRepositoryInterface::class => $this->createMock(UpdateInfoRepositoryInterface::class),
            default => $this->createMock(LoggerInterface::class),
        });
        $dic->method('make')->willReturnCallback(function (string $id): object {
            $this->builds++;

            return $this->buildWithMockedDependencies($id);
        });
        $GLOBALS['dic'] = $dic;

    }

    /**
     * The help `collect()` attaches to one row, which is what the screen shows under the field.
     *
     * @param array<string, mixed> $row
     */
    private function helpFor(array $row): ?PreferenceHelp
    {
        return $this->helpForAll([$row])[0];
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<?PreferenceHelp>
     */
    private function helpForAll(array $rows): array
    {
        $userRepository = $this->createMock(\Ampache\Repository\UserRepositoryInterface::class);
        $userRepository->method('getPreferenceRows')->willReturnCallback(
            fn(int $userId): array => ($userId === \Ampache\Repository\Model\User::INTERNAL_SYSTEM_USER_ID)
                ? []
                : array_map(static fn(array $r): array => $r + ['description' => '', 'type' => 'string', 'level' => 0], $rows)
        );
        $configContainer = $this->createMock(\Ampache\Config\ConfigContainerInterface::class);
        $configContainer->method('isFeatureEnabled')->willReturn(false);
        $configContainer->method('get')->willReturn(null);
        $choiceProvider = $this->createMock(PreferenceChoiceProviderInterface::class);
        $choiceProvider->method('find')->willReturn(null);

        $collector = new PreferenceCollector(
            $userRepository,
            new PreferenceHelpCatalog(),
            $choiceProvider,
            new PreferencePrerequisiteCatalog(),
            $configContainer,
        );

        $operator         = $this->createMock(\Ampache\Repository\Model\User::class);
        $operator->access = 100;
        $collected        = $collector->collect(PreferenceSubject::ownPreferences($operator), $operator);

        $items = [];
        foreach ($rows as $row) {
            $found = null;
            foreach ($collected[$row['category']] ?? [] as $item) {
                if ($item->name === $row['name']) {
                    $found = $item->help;
                    break;
                }
            }

            $items[] = $found;
        }

        return $items;
    }

    /** @return array<string, mixed> */
    private function row(string $name, string $category = 'plugins', ?string $subcategory = 'RatingMatch', mixed $value = ''): array
    {
        return ['name' => $name, 'category' => $category, 'subcategory' => $subcategory, 'value' => $value];
    }
}

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

use Ampache\Repository\Model\User;
use PHPUnit\Framework\TestCase;

class PreferenceBoxViewTest extends TestCase
{
    public function testAnAnchorIsSafeToPutInAnHref(): void
    {
        $this->assertSame('pref-section-last-fm', $this->view([])->subcategoryAnchor('Last.FM'));
    }

    public function testAReferenceValueIsShownWithTheLabelTheControlUses(): void
    {
        $view = $this->view([]);
        $item = $this->item('download', 'feature');

        $this->assertSame(T_('On'), $view->displayValue($item, '1'), 'the reference column speaks the language of the control');
        $this->assertSame(T_('Off'), $view->displayValue($item, '0'));
        $this->assertSame(T_('(empty)'), $view->displayValue($item, ''));
    }

    public function testASectionlessPreferenceGetsTheCatchAllHeading(): void
    {
        $view = $this->view([$this->item('loose', null)]);

        $this->assertSame(T_('Other'), $view->formatSubcategory(null));
        $this->assertSame('pref-section-other', $view->subcategoryAnchor(null));
        $this->assertSame(['pref-section-other' => T_('Other')], $view->getSubcategories());
    }

    public function testPreferencesWithNoSectionAreMovedToTheEnd(): void
    {
        $view = $this->view([
            $this->item('loose_one', null),
            $this->item('in_a_section', 'player'),
            $this->item('loose_two', null),
            $this->item('also_in_a_section', 'player'),
        ]);

        $this->assertSame(
            ['in_a_section', 'also_in_a_section', 'loose_one', 'loose_two'],
            array_map(static fn(PreferenceItem $item): string => $item->name, $view->getItems()),
            'the catch-all section is leftovers, so it comes last and keeps its inner order'
        );
    }

    public function testTheAdminColumnsOnlyExistForTheServer(): void
    {
        $this->assertFalse($this->view([])->showsAdminControls());
        $this->assertSame(4, $this->view([])->getColumnCount());
        $this->assertTrue($this->view([], server: true)->showsAdminControls());
        $this->assertSame(6, $this->view([], server: true)->getColumnCount(), 'apply-to-all and level add two columns');
    }

    public function testTheFilterMatchesTheNameTheLabelAndTheHelp(): void
    {
        $text = $this->view([])->searchText(
            $this->item('hide_genres', 'browse', new PreferenceHelp('Hides the Genre column'), 'Cacher la colonne Genre')
        );

        $this->assertStringContainsString('hide_genres', $text, 'the key is searchable');
        $this->assertStringContainsString('cacher la colonne genre', $text, 'the label is searchable');
        $this->assertStringContainsString('genre column', $text, 'the help text is searchable too');
        $this->assertSame(strtolower($text), $text, 'the filter lowercases what the visitor types');
    }

    public function testTheJumpListFollowsTheOrderOfTheTable(): void
    {
        $view = $this->view([
            $this->item('a', null),
            $this->item('b', 'player'),
            $this->item('c', 'theme'),
        ]);

        $this->assertSame(
            ['pref-section-player', 'pref-section-theme', 'pref-section-other'],
            array_keys($view->getSubcategories())
        );
    }

    private function item(string $name, ?string $subcategory, ?PreferenceHelp $help = null, string $description = ''): PreferenceItem
    {
        return new PreferenceItem(
            name: $name,
            description: ($description === '') ? ucfirst($name) : $description,
            type: PreferenceType::BOOLEAN,
            subcategory: $subcategory,
            level: 25,
            value: '1',
            shippedDefault: '0',
            systemValue: '0',
            choices: null,
            editable: true,
            isSecret: false,
            secretIsSet: false,
            help: $help,
        );
    }

    private function view(array $items, bool $server = false): PreferenceBoxView
    {
        $user           = $this->createMock(User::class);
        $user->fullname = 'u';
        $user->method('getId')->willReturn(1);
        $subject = ($server) ? PreferenceSubject::serverPreferences($user) : PreferenceSubject::ownPreferences($user);

        return new PreferenceBoxView($items, $subject, new PreferenceInputRenderer());
    }
}

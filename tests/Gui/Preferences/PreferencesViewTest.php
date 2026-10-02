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

class PreferencesViewTest extends TestCase
{
    public function testASecretIsNeverCountedAsDiffering(): void
    {
        $view = $this->view(PreferenceSubject::ownPreferences($this->user(1, 'a')), [
            $this->item('daap_pass', '', '', isSecret: true),
        ]);

        $this->assertSame(0, $view->countDiffering(), 'a secret answers neither comparison');
    }

    public function testEditingSomeoneElsePostsToTheGuardedAdminEndpoint(): void
    {
        $other = $this->view(PreferenceSubject::otherUser($this->user(7, 'bituur'), $this->user(1, 'admin')));
        $self  = $this->view(PreferenceSubject::ownPreferences($this->user(1, 'admin')));

        $this->assertSame('/amp/preferences.php?action=admin_update_preferences', $other->getActionUrl());
        $this->assertSame('/amp/preferences.php?action=update_preferences', $self->getActionUrl());
    }

    public function testNothingIsWarnedAboutWhenEditingYourOwnAccount(): void
    {
        $this->assertSame('', $this->view(PreferenceSubject::ownPreferences($this->user(1, 'a')))->getSubjectWarning());
        $this->assertNotSame('', $this->view(PreferenceSubject::serverPreferences($this->user(1, 'a')))->getSubjectWarning());
        $this->assertStringContainsString(
            'bituur',
            $this->view(PreferenceSubject::otherUser($this->user(7, 'bituur'), $this->user(1, 'a')))->getSubjectWarning()
        );
    }

    public function testOnlyATabWithPreferencesInItGetsAForm(): void
    {
        $subject = PreferenceSubject::ownPreferences($this->user(1, 'a'));
        $items   = [$this->item('show_lyrics', '1', '1')];

        $this->assertTrue($this->view($subject, $items, tab: 'interface')->hasPreferenceForm());
        $this->assertFalse($this->view($subject, $items, tab: 'account')->hasPreferenceForm());
        $this->assertFalse($this->view($subject, $items, tab: 'quickconnect')->hasPreferenceForm());
        $this->assertFalse($this->view($subject, [], tab: 'interface')->hasPreferenceForm(), 'an unknown tab has nothing to edit');
    }

    public function testOnlySomeoneElsesPreferencesCarryTheUserId(): void
    {
        $this->assertTrue($this->view(PreferenceSubject::otherUser($this->user(7, 'b'), $this->user(1, 'a')))->isOtherUser());
        $this->assertFalse($this->view(PreferenceSubject::ownPreferences($this->user(1, 'a')))->isOtherUser());
        $this->assertFalse($this->view(PreferenceSubject::serverPreferences($this->user(1, 'a')))->isOtherUser());
    }

    public function testTheCountsAreWhatTheFilterChipsShow(): void
    {
        $view = $this->view(PreferenceSubject::ownPreferences($this->user(1, 'a')), [
            $this->item('same', '1', '1'),
            $this->item('changed', '0', '1'),
            $this->item('unknown_default', '0', null),
        ]);

        $this->assertSame(3, $view->countAll());
        $this->assertSame(1, $view->countDiffering(), 'nothing is shipped for it, so there is nothing to differ from');
    }

    public function testTheSubjectIsNamedInTheReadersWordsAndNeverTwice(): void
    {
        $server = $this->view(PreferenceSubject::serverPreferences($this->user(1, 'admin')));
        $self   = $this->view(PreferenceSubject::ownPreferences($this->user(1, 'admin')));

        $this->assertSame(T_('Server'), $server->getSubjectKind());
        $this->assertSame('', $server->getSubjectName(), 'the server has no name of its own to repeat');
        $this->assertSame(T_('Account'), $self->getSubjectKind());
        $this->assertSame('admin', $self->getSubjectName());
    }

    private function item(string $name, string $value, ?string $shippedDefault, bool $isSecret = false): PreferenceItem
    {
        return new PreferenceItem(
            name: $name,
            description: $name,
            type: PreferenceType::STRING,
            subcategory: null,
            level: 25,
            value: $value,
            shippedDefault: $shippedDefault,
            systemValue: null,
            choices: null,
            editable: true,
            isSecret: $isSecret,
            secretIsSet: false,
            help: null,
        );
    }

    private function user(int $id, string $fullname): User
    {
        $user           = $this->createMock(User::class);
        $user->fullname = $fullname;
        $user->method('getId')->willReturn($id);

        return $user;
    }

    private function view(PreferenceSubject $subject, array $items = [], string $tab = 'interface'): PreferencesView
    {
        return new PreferencesView('/amp', $subject, $items, $tab, true, false, new PreferenceInputRenderer());
    }
}

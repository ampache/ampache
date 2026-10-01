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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PreferencesViewFactoryTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private PreferencesViewFactory $subject;
    private UserRepositoryInterface&MockObject $userRepository;

    public function testAnUnknownTabRendersNothingRatherThanEverything(): void
    {
        $user = $this->user();
        $this->userRepository->method('getPreferenceRows')->willReturn([$this->row('show_lyrics', 'interface')]);

        $view = $this->subject->create($this->gatekeeper(), PreferenceSubject::ownPreferences($user), $user, 'nope');

        $this->assertSame(0, $view->countAll());
    }

    public function testTheSubjectIsCarriedThroughToTheView(): void
    {
        $user = $this->user();
        $this->userRepository->method('getPreferenceRows')->willReturn([]);

        $server = $this->subject->create($this->gatekeeper(), PreferenceSubject::serverPreferences($user), $user, 'system');

        $this->assertTrue($server->isServerSubject());
        $this->assertSame('/amp/preferences.php?action=update_preferences', $server->getActionUrl());
    }

    public function testTheViewOnlyReceivesThePreferencesOfTheTabAsked(): void
    {
        $user = $this->user();
        $this->userRepository->method('getPreferenceRows')->willReturn([
            $this->row('show_lyrics', 'interface'),
            $this->row('download', 'options'),
        ]);

        $view = $this->subject->create($this->gatekeeper(), PreferenceSubject::ownPreferences($user), $user, 'interface');

        $this->assertSame(1, $view->countAll());
        $this->assertSame('interface', $view->getTab());
    }

    protected function setUp(): void
    {
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);
        $this->userRepository  = $this->createMock(UserRepositoryInterface::class);

        $this->configContainer->method('getWebPath')->willReturn('/amp');

        $this->subject = new PreferencesViewFactory(
            $this->configContainer,
            new PreferenceCollector(
                $this->userRepository,
                new PreferenceHelpCatalog(),
                $this->createMock(PreferenceChoiceProviderInterface::class),
                new PreferencePrerequisiteCatalog(),
                $this->configContainer,
            ),
            new PreferenceInputRenderer(),
        );
    }

    private function gatekeeper(bool $admin = true): GuiGatekeeperInterface&MockObject
    {
        $gatekeeper = $this->createMock(GuiGatekeeperInterface::class);
        $gatekeeper->method('mayAdminister')->willReturn($admin);

        return $gatekeeper;
    }

    /** @return array<string, mixed> */
    private function row(string $name, string $category): array
    {
        return [
            'name' => $name,
            'description' => $name,
            'category' => $category,
            'subcategory' => null,
            'type' => 'boolean',
            'level' => 25,
            'value' => '1',
        ];
    }

    private function user(): User
    {
        $user           = $this->createMock(User::class);
        $user->fullname = 'u';
        $user->access   = 100;
        $user->method('getId')->willReturn(1);

        return $user;
    }
}

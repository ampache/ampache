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

namespace Ampache\Gui\Partial;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Plugin\PluginEnum;
use Ampache\Plugin\PluginExternalShareInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use DI\FactoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * The line a visitor shares from: the permalink to copy, and the share dialog for an account only.
 *
 * The network icons are not covered: `Plugin::get_plugins()` keeps a process-wide cache, so a plugin
 * installed here would still be installed for every later test.
 */
class ShareLineViewTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $config = [];

    // a level the list never offers falls back to accounts rather than opening the line to guests
    public function testAnUnknownLevelOffersTheLineToAccounts(): void
    {
        AmpConfig::set('share_access_level', 7, true);

        self::assertFalse(ShareLineView::isOfferedTo($this->user(AccessLevelEnum::GUEST)));
        self::assertTrue(ShareLineView::isOfferedTo($this->user(AccessLevelEnum::USER)));
    }

    // the page router would reload the page on a plain link, and without the href no keyboard reaches it
    public function testCopyLinkIsAPlainLinkTheRouterLeavesAlone(): void
    {
        $html = $this->render(mayShareAdvanced: false);

        self::assertStringContainsString('<a class="nohtml" href="https://music.example/song.php?action=show_song&amp;song_id=7"', $html);
    }

    // the level list shows 0 as Disabled, so it hides the line even from an admin
    public function testDisabledOffersTheLineToNobody(): void
    {
        AmpConfig::set('share_access_level', AccessLevelEnum::DEFAULT->value, true);

        self::assertFalse(ShareLineView::isOfferedTo($this->user(AccessLevelEnum::ADMIN)));
    }

    // a network without its own svg shows the red error icon in the share line
    public function testEveryNetworkHasItsIcon(): void
    {
        foreach (PluginEnum::LIST as $key => $class) {
            if (is_subclass_of($class, PluginExternalShareInterface::class)) {
                self::assertFileExists(__DIR__ . '/../../../resources/images/icon_share_' . $key . '.svg');
            }
        }
    }

    // behind logins a visitor who has not signed in gets no line, even at the guest level
    public function testSomeoneNotSignedInIsOfferedNothing(): void
    {
        AmpConfig::set('share_access_level', AccessLevelEnum::GUEST->value, true);

        self::assertFalse(ShareLineView::isOfferedTo(null));
    }

    // without logins the visitor is the guest user, so the guest level opens the line to everyone
    public function testTheLevelTheAdminSetDecides(): void
    {
        $guest = $this->user(AccessLevelEnum::GUEST);
        self::assertFalse(ShareLineView::isOfferedTo($guest));

        AmpConfig::set('share_access_level', AccessLevelEnum::GUEST->value, true);
        self::assertTrue(ShareLineView::isOfferedTo($guest));
    }

    public function testTheLineCarriesThePermalinkForAnyVisitor(): void
    {
        $html = $this->render(mayShareAdvanced: false);

        self::assertStringContainsString('data-copy-link="https://music.example/song.php?action=show_song&amp;song_id=7"', $html);
        self::assertStringNotContainsString('data-share-dialog', $html);
    }

    public function testTheLineGoesWithTheFeature(): void
    {
        AmpConfig::set('share', false, true);

        self::assertFalse(ShareLineView::isOfferedTo($this->user(AccessLevelEnum::ADMIN)));
    }

    public function testTheLinkIsEscapedOnItsWayOut(): void
    {
        $html = $this->render(mayShareAdvanced: false, link: 'https://music.example/?a="><script>');

        self::assertStringNotContainsString('"><script>', $html);
    }

    // the phone's share sheet reaches the apps no plugin covers, signal or sms, from the same permalink
    public function testThePhoneShareSheetGetsThePermalink(): void
    {
        $html = $this->render(mayShareAdvanced: false);

        self::assertStringContainsString('data-native-share="https://music.example/song.php?action=show_song&amp;song_id=7"', $html);
    }

    public function testTheShareDialogIsOfferedToAnAccount(): void
    {
        $html = $this->render(mayShareAdvanced: true);

        self::assertStringContainsString('data-share-dialog="song" data-share-id="7"', $html);
    }

    protected function setUp(): void
    {
        foreach (['share', 'share_access_level'] as $key) {
            $this->config[$key] = AmpConfig::get($key);
        }

        AmpConfig::set('share', true, true);
        AmpConfig::set('share_access_level', AccessLevelEnum::USER->value, true);

        // the installed plugins are read through the global container, and none is installed here
        $dic = $this->createMockForIntersectionOfInterfaces([ContainerInterface::class, FactoryInterface::class]);
        $dic->method('get')
            ->willReturnMap([
                [UpdateInfoRepositoryInterface::class, $this->createMock(UpdateInfoRepositoryInterface::class)],
                [LoggerInterface::class, $this->createMock(LoggerInterface::class)],
            ]);
        $GLOBALS['dic'] = $dic;
    }

    protected function tearDown(): void
    {
        foreach ($this->config as $key => $value) {
            AmpConfig::set($key, $value, true);
        }
    }

    private function render(bool $mayShareAdvanced, string $link = 'https://music.example/song.php?action=show_song&song_id=7'): string
    {
        return new ShareLineView('song', 7, $link, '/amp', $mayShareAdvanced)->render();
    }

    private function user(AccessLevelEnum $level): User
    {
        $user         = $this->createMock(User::class);
        $user->access = $level->value;

        return $user;
    }
}

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

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Repository\Model\User;
use DI\Container;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The account form posted to an action that verifies a token it never carried, so every save was a 403.
 */
class AccountViewTest extends TestCase
{
    public function testAReadOnlyAccountOffersNoFormAtAll(): void
    {
        $html = $this->render(readOnly: true);

        $this->assertStringNotContainsString('form_validation', $html);
        $this->assertStringNotContainsString('type="submit"', $html);
    }

    public function testTheAccountValuesAreEscapedOnTheirWayOut(): void
    {
        $user           = $this->client();
        $user->fullname = '"><script>alert(1)</script>';

        $html = (new AccountView($user, '/amp', false))->render();

        $this->assertStringNotContainsString('<script>alert', $html);
    }

    public function testTheFormCarriesTheTokenTheUpdateActionVerifies(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('action="/amp/preferences.php?action=update_user"', $html);
        $this->assertMatchesRegularExpression(
            '/<form[^>]*action="[^"]*update_user".*?name="form_validation".*?<\/form>/s',
            $html,
            'without the token inside the form, UpdateUserAction answers 403 to every save'
        );
    }

    public function testTheTokenIsRegisteredForThatFormAndNoOther(): void
    {
        $_SESSION['forms'] = [];
        $html              = $this->render();

        preg_match('/name="form_validation" value="([^"]+)"/', $html, $matches);

        $this->assertArrayHasKey($matches[1] ?? '', $_SESSION['forms']);
        $this->assertSame('update_user', $_SESSION['forms'][$matches[1]]['name']);
    }

    protected function setUp(): void
    {
        // the template asks whether the viewer is an admin, which goes through the container
        $checker = $this->createMock(PrivilegeCheckerInterface::class);
        $checker->method('check')->willReturn(false);
        $dic = $this->createMock(Container::class);
        $dic->method('get')->willReturnCallback(fn(string $id): object => match ($id) {
            PrivilegeCheckerInterface::class => $checker,
            default => $this->createMock(LoggerInterface::class),
        });
        $GLOBALS['dic'] = $dic;

        AmpConfig::set('registration_display_fields', [], true);
    }

    private function client(): User
    {
        $user           = $this->createMock(User::class);
        $user->fullname = 'UiDemo';
        $user->email    = 'demo@example.com';
        $user->apikey   = '';
        $user->method('getId')->willReturn(42);

        return $user;
    }

    private function render(bool $readOnly = false): string
    {
        return (new AccountView($this->client(), '/amp', $readOnly))->render();
    }
}

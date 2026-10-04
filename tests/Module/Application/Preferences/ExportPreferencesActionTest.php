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

namespace Ampache\Module\Application\Preferences;

use Ampache\Gui\Preferences\PreferenceExporterInterface;
use Ampache\Gui\Preferences\PreferenceSubject;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Downloading a configuration is a read of someone else's settings, so it is gated exactly like the
 * screen that shows them.
 */
class ExportPreferencesActionTest extends MockeryTestCase
{
    private MockInterface|PreferenceExporterInterface $exporter;
    private MockInterface|ModelFactoryInterface $modelFactory;
    private ExportPreferencesAction $subject;

    #[Override]
    public function setUp(): void
    {
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);
        $this->exporter     = $this->mock(PreferenceExporterInterface::class);

        $this->subject = new ExportPreferencesAction(
            new Psr17Factory(),
            $this->modelFactory,
            $this->exporter,
        );
    }

    public function testAnAdminDownloadsTheAccountAskedFor(): void
    {
        $operator = $this->user(42);
        $target   = $this->user(7);
        $target->shouldReceive('isNew')->andReturnFalse();

        $gatekeeper = $this->gatekeeper(admin: true);
        $gatekeeper->shouldReceive('getUser')->andReturn($operator);
        $this->modelFactory->shouldReceive('createUser')->with(7)->once()->andReturn($target);

        $captured = null;
        $this->exporter->shouldReceive('export')
            ->andReturnUsing(function (PreferenceSubject $subject) use (&$captured): array {
                $captured = $subject;

                return [];
            });
        $this->exporter->shouldReceive('fileName')->andReturn('export.json');

        $this->subject->run($this->request(['user_id' => '7']), $gatekeeper);

        $this->assertSame(7, $captured?->userId, 'the file must hold the account that was asked for');
        $this->assertFalse($captured?->isSelf);
    }

    public function testANonAdminCannotDownloadAnotherAccount(): void
    {
        $gatekeeper = $this->gatekeeper(admin: false);
        $gatekeeper->shouldReceive('getUser')->andReturn($this->user(42));
        $this->modelFactory->shouldNotReceive('createUser');

        $this->expectException(AccessDeniedException::class);

        $this->subject->run($this->request(['user_id' => '7']), $gatekeeper);
    }

    public function testANonAdminCannotDownloadTheServerConfiguration(): void
    {
        $gatekeeper = $this->gatekeeper(admin: false);
        $gatekeeper->shouldReceive('getUser')->andReturn($this->user(42));

        $this->expectException(AccessDeniedException::class);

        $this->subject->run($this->request(['method' => 'admin']), $gatekeeper);
    }

    public function testAnUnknownAccountIsRefused(): void
    {
        $gatekeeper = $this->gatekeeper(admin: true);
        $gatekeeper->shouldReceive('getUser')->andReturn($this->user(42));

        $target = $this->mock(User::class);
        $target->shouldReceive('isNew')->once()->andReturnTrue();
        $this->modelFactory->shouldReceive('createUser')->with(666)->once()->andReturn($target);

        $this->expectException(AccessDeniedException::class);

        $this->subject->run($this->request(['user_id' => '666']), $gatekeeper);
    }

    public function testAVisitorWithoutAnAccountGetsNothing(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->subject->run($this->request([]), $this->gatekeeper(user: false));
    }

    /**
     * Demo mode grants every privilege, so the admin check alone would hand the whole configuration out.
     */
    public function testDemoModeIsRefusedEvenThoughItGrantsAdmin(): void
    {
        $gatekeeper = $this->gatekeeper(admin: true, demo: true);
        $gatekeeper->shouldReceive('getUser')->andReturn($this->user(42));
        $this->modelFactory->shouldNotReceive('createUser');

        $this->expectException(AccessDeniedException::class);

        $this->subject->run($this->request(['user_id' => '7']), $gatekeeper);
    }

    public function testYourOwnConfigurationNeedsNoAdmin(): void
    {
        $operator   = $this->user(42);
        $gatekeeper = $this->gatekeeper();
        $gatekeeper->shouldReceive('getUser')->andReturn($operator);

        $captured = null;
        $this->exporter->shouldReceive('export')
            ->andReturnUsing(function (PreferenceSubject $subject) use (&$captured): array {
                $captured = $subject;

                return ['preferences' => []];
            });
        $this->exporter->shouldReceive('fileName')->andReturn('export.json');

        $response = $this->subject->run($this->request([]), $gatekeeper);

        $this->assertSame(42, $captured?->userId);
        $this->assertTrue($captured?->isSelf);
        $this->assertStringContainsString('attachment; filename="export.json"', $response->getHeaderLine('Content-Disposition'));
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
    }

    private function gatekeeper(bool $user = true, bool $admin = false, bool $demo = false): MockInterface|GuiGatekeeperInterface
    {
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)
            ->andReturn($user);
        $gatekeeper->shouldReceive('mayAdminister')->andReturn($admin);
        $gatekeeper->shouldReceive('isDemoMode')->andReturn($demo);

        return $gatekeeper;
    }

    private function request(array $params): MockInterface|ServerRequestInterface
    {
        $request = $this->mock(ServerRequestInterface::class);
        $request->shouldReceive('getQueryParams')->andReturn($params);

        return $request;
    }

    private function user(int $id): MockInterface|User
    {
        $user           = $this->mock(User::class);
        $user->fullname = 'u' . $id;
        $user->username = 'u' . $id;
        $user->shouldReceive('getId')->andReturn($id);

        return $user;
    }
}

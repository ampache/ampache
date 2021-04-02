<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Module\Application\Preferences;

use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\QrCodeGeneratorInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class ShowActionTest extends MockeryTestCase
{
    /** @var UiInterface|MockInterface */
    private MockInterface $ui;

    /** @var QrCodeGeneratorInterface|MockInterface */
    private MockInterface $qrCodeGenerator;

    private ShowAction $subject;

    public function setUp(): void
    {
        $this->ui              = $this->mock(UiInterface::class);
        $this->qrCodeGenerator = $this->mock(QrCodeGeneratorInterface::class);

        $this->subject = new ShowAction(
            $this->ui,
            $this->qrCodeGenerator
        );
    }

    public function testRunRendersAndReturnsNull(): void
    {
        $user       = $this->mock(User::class);
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $apiKey       = 'some-api-key';
        $apiKeyQrCode = 'some-api-key-qrcode';
        $preferences  = ['some-preferences'];
        $userName     = 'some-name';

        $user->apikey   = $apiKey;
        $user->fullname = $userName;

        $this->qrCodeGenerator->shouldReceive('generate')
            ->with($apiKey, 156)
            ->once()
            ->andReturn($apiKeyQrCode);

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['tab' => 'account']);

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $user->shouldReceive('get_preferences')
            ->with('account')
            ->once()
            ->andReturn($preferences);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('show')
            ->with(
                'show_preferences.inc.php',
                [
                    'fullname' => $userName,
                    'preferences' => $preferences,
                    'apiKeyQrCode' => $apiKeyQrCode,
                    'ui' => $this->ui,
                ]
            )
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }
}

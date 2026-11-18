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

namespace Ampache\Module\Application\Song;

use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;

class ShowLyricsActionTest extends MockeryTestCase
{
    private GuiGatekeeperInterface&MockInterface $gatekeeper;
    private ModelFactoryInterface&MockInterface $modelFactory;
    private RequestParserInterface&MockInterface $requestParser;
    private Song&MockInterface $song;
    private ShowLyricsAction $subject;
    private UiInterface&MockInterface $ui;
    private User&MockInterface $user;

    public function testRunRefusesASongOutsideTheUsersCatalogs(): void
    {
        $this->user->catalogs['music'] = [2];

        $this->expectException(ObjectNotFoundException::class);

        $this->subject->run($this->mock(ServerRequestInterface::class), $this->gatekeeper);
    }

    public function testRunRefusesAWriteFromSomeoneWhoMayNotEditTheSong(): void
    {
        $request = $this->mock(ServerRequestInterface::class);
        $request->shouldReceive('getParsedBody')->andReturn(['lyrics_action' => 'clear']);
        $this->gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            ->andReturnFalse();
        $this->requestParser->shouldNotReceive('verifyForm');
        $this->song->shouldNotReceive('get_lyrics');

        $this->expectException(AccessDeniedException::class);

        $this->subject->run($request, $this->gatekeeper);
    }

    public function testRunRefusesAWriteWithoutAValidFormToken(): void
    {
        $request = $this->mock(ServerRequestInterface::class);
        $request->shouldReceive('getParsedBody')->andReturn(['lyrics_action' => 'save', 'lyrics' => 'some-lyrics']);
        $this->gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            ->andReturnTrue();
        $this->requestParser->shouldReceive('verifyForm')->with(ShowLyricsAction::FORM_NAME)->once()->andReturnFalse();
        $this->song->shouldNotReceive('get_lyrics');

        $this->expectException(AccessDeniedException::class);

        $this->subject->run($request, $this->gatekeeper);
    }

    #[Override]
    protected function setUp(): void
    {
        $this->requestParser = $this->mock(RequestParserInterface::class);
        $this->ui            = $this->mock(UiInterface::class);
        $this->modelFactory  = $this->mock(ModelFactoryInterface::class);
        $this->gatekeeper    = $this->mock(GuiGatekeeperInterface::class);
        $this->song          = $this->mock(Song::class);
        $this->user          = $this->mock(User::class);

        $this->song->catalog            = 1;
        $this->user->catalogs['music']  = [1];

        $this->requestParser->shouldReceive('getFromRequest')->with('song_id')->andReturn('42');
        $this->modelFactory->shouldReceive('createSong')->with(42)->andReturn($this->song);
        $this->gatekeeper->shouldReceive('getUser')->andReturn($this->user);
        $this->song->shouldReceive('isNew')->andReturnFalse();
        $this->song->shouldReceive('isVisible')->with($this->user)->andReturnTrue();
        $this->song->shouldReceive('get_user_owner')->andReturn(7);
        $this->user->shouldReceive('getId')->andReturn(3);

        $this->subject = new ShowLyricsAction($this->requestParser, $this->ui, $this->modelFactory);
    }
}

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

namespace Ampache\Module\Application\Shout;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Shout\ShoutCreatorInterface;
use Ampache\Module\Shout\ShoutObjectLoaderInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AddShoutActionTest extends MockeryTestCase
{
    private ConfigContainerInterface&MockInterface $configContainer;
    private RequestParserInterface&MockInterface $requestParser;
    private ResponseFactoryInterface&MockInterface $responseFactory;
    private ShoutCreatorInterface&MockInterface $shoutCreator;
    private ShoutObjectLoaderInterface&MockInterface $shoutObjectLoader;
    private AddShoutAction $subject;

    public function testRunHonoursStickyForAContentManager(): void
    {
        $this->runWith(true, true);
    }

    public function testRunIgnoresStickyForANonContentManager(): void
    {
        // the checkbox is only rendered for a content manager, so an ordinary user posting sticky is not honoured
        $this->runWith(false, false);
    }

    #[Override]
    protected function setUp(): void
    {
        $this->responseFactory   = $this->mock(ResponseFactoryInterface::class);
        $this->configContainer   = $this->mock(ConfigContainerInterface::class);
        $this->shoutCreator      = $this->mock(ShoutCreatorInterface::class);
        $this->requestParser     = $this->mock(RequestParserInterface::class);
        $this->shoutObjectLoader = $this->mock(ShoutObjectLoaderInterface::class);

        $this->subject = new AddShoutAction(
            $this->responseFactory,
            $this->configContainer,
            $this->shoutCreator,
            $this->requestParser,
            $this->shoutObjectLoader
        );
    }

    private function runWith(bool $isContentManager, bool $expectedSticky): void
    {
        $user    = $this->mock(User::class);
        $libitem = $this->mock(library_item::class);

        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $gatekeeper->shouldReceive('getUser')->andReturn($user);
        $gatekeeper->shouldReceive('mayAccess')
            ->andReturnUsing(static fn(AccessTypeEnum $type, AccessLevelEnum $level): bool => match ($level) {
                AccessLevelEnum::CONTENT_MANAGER => $isContentManager,
                default => true,
            });

        $this->requestParser->shouldReceive('verifyForm')->with('add_shout')->andReturnTrue();

        $request = $this->mock(ServerRequestInterface::class);
        $request->shouldReceive('getParsedBody')->andReturn([
            'object_type' => 'song',
            'object_id' => '42',
            'comment' => 'hi',
            'sticky' => 'on',
        ]);

        $this->shoutObjectLoader->shouldReceive('loadByObjectType')
            ->with(LibraryItemEnum::SONG, 42)
            ->andReturn($libitem);

        $this->shoutCreator->shouldReceive('create')
            ->with($user, $libitem, LibraryItemEnum::SONG, 'hi', $expectedSticky, 0)
            ->once();

        $response = $this->mock(ResponseInterface::class);
        $response->shouldReceive('withHeader')->andReturnSelf();
        $this->responseFactory->shouldReceive('createResponse')->andReturn($response);
        $this->configContainer->shouldReceive('getWebPath')->andReturn('');

        $this->subject->run($request, $gatekeeper);
    }
}

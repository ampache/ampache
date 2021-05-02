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
 */

declare(strict_types=1);

namespace Ampache\Module\Api\Gui\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Share\ExpirationDateCalculatorInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\ShareInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShareRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class ShareEditMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ShareRepositoryInterface|MockInterface|null */
    private MockInterface $shareRepository;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var MockInterface|ModelFactoryInterface|null */
    private MockInterface $modelFactory;

    /** @var MockInterface|ExpirationDateCalculatorInterface|null */
    private MockInterface $expirationDateCalculator;

    private ShareEditMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory            = $this->mock(StreamFactoryInterface::class);
        $this->shareRepository          = $this->mock(ShareRepositoryInterface::class);
        $this->configContainer          = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory             = $this->mock(ModelFactoryInterface::class);
        $this->expirationDateCalculator = $this->mock(ExpirationDateCalculatorInterface::class);

        $this->subject = new ShareEditMethod(
            $this->streamFactory,
            $this->shareRepository,
            $this->configContainer,
            $this->modelFactory,
            $this->expirationDateCalculator
        );
    }

    public function testHandleThrowsExceptionIfSharingIsDisabled(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(FunctionDisabledException::class);
        $this->expectExceptionMessage('Enable: share');

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function testHandleThrowsExceptionIfFilterParamIsMissing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: filter');

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnTrue();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function testHandleThrowsExceptionIfShareWasNotFound(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $objectId = 666;

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf('Not Found: %d', $objectId));

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnTrue();

        $this->shareRepository->shouldReceive('getList')
            ->with($user)
            ->once()
            ->andReturn([]);

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $objectId]
        );
    }

    public function testHandleThrowsExceptionIfUpdateFails(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $share      = $this->mock(ShareInterface::class);

        $objectId      = 666;
        $description   = 'some-description';
        $allowStream   = true;
        $allowDownload = true;
        $expireDays    = 42;
        $maxCounter    = 33;

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %d', $objectId));

        $share->shouldReceive('getDescription')
            ->withNoArgs()
            ->once()
            ->andReturn($description);
        $share->shouldReceive('getAllowStream')
            ->withNoArgs()
            ->once()
            ->andReturn($allowStream);
        $share->shouldReceive('getAllowDownload')
            ->withNoArgs()
            ->once()
            ->andReturn($allowDownload);
        $share->shouldReceive('getExpireDays')
            ->withNoArgs()
            ->once()
            ->andReturn($expireDays);
        $share->shouldReceive('getMaxCounter')
            ->withNoArgs()
            ->once()
            ->andReturn($maxCounter);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnTrue();

        $this->shareRepository->shouldReceive('getList')
            ->with($user)
            ->once()
            ->andReturn([$objectId]);

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $this->modelFactory->shouldReceive('createShare')
            ->with($objectId)
            ->once()
            ->andReturn($share);

        $share->shouldReceive('update')
            ->with(
                [
                    'max_counter' => $maxCounter,
                    'expire' => $expireDays,
                    'allow_stream' => $allowStream,
                    'allow_download' => $allowDownload,
                    'description' => $description,
                ],
                $user
            )
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $objectId]
        );
    }

    public function testHandleReturnsResult(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $share      = $this->mock(ShareInterface::class);
        $stream     = $this->mock(StreamInterface::class);

        $objectId      = 666;
        $description   = 'some-description';
        $allowStream   = true;
        $allowDownload = true;
        $expireDays    = 42;
        $maxCounter    = 33;
        $result        = 'some-result';

        $share->shouldReceive('getMaxCounter')
            ->withNoArgs()
            ->once()
            ->andReturn($maxCounter);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnTrue();

        $this->shareRepository->shouldReceive('getList')
            ->with($user)
            ->once()
            ->andReturn([$objectId]);

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $this->modelFactory->shouldReceive('createShare')
            ->with($objectId)
            ->once()
            ->andReturn($share);

        $share->shouldReceive('update')
            ->with(
                [
                    'max_counter' => $maxCounter,
                    'expire' => $expireDays,
                    'allow_stream' => $allowStream,
                    'allow_download' => $allowDownload,
                    'description' => $description,
                ],
                $user
            )
            ->once()
            ->andReturnTrue();

        $output->shouldReceive('success')
            ->with(sprintf('share %d updated', $objectId))
            ->once()
            ->andReturn($result);

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->expirationDateCalculator->shouldReceive('calculate')
            ->with($expireDays)
            ->once()
            ->andReturn($expireDays);

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'filter' => (string) $objectId,
                    'description' => $description,
                    'stream' => 1,
                    'download' => 1,
                    'expires' => $expireDays
                ]
            )
        );
    }
}

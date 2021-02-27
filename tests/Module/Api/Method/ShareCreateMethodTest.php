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

namespace Ampache\Module\Api\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Share\ExpirationDateCalculatorInterface;
use Ampache\Module\Share\ShareCreatorInterface;
use Ampache\Module\User\PasswordGenerator;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class ShareCreateMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var FunctionCheckerInterface|MockInterface|null */
    private MockInterface $functionChecker;

    /** @var PasswordGeneratorInterface|MockInterface|null */
    private MockInterface $passwordGenerator;

    /** @var ExpirationDateCalculatorInterface|MockInterface|null */
    private MockInterface $expirationDateCalculator;

    /** @var UpdateInfoRepositoryInterface|MockInterface|null */
    private MockInterface $updateInfoRepository;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var ShareCreatorInterface|MockInterface|null */
    private MockInterface $shareCreator;

    private ShareCreateMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory            = $this->mock(StreamFactoryInterface::class);
        $this->modelFactory             = $this->mock(ModelFactoryInterface::class);
        $this->functionChecker          = $this->mock(FunctionCheckerInterface::class);
        $this->passwordGenerator        = $this->mock(PasswordGeneratorInterface::class);
        $this->expirationDateCalculator = $this->mock(ExpirationDateCalculatorInterface::class);
        $this->updateInfoRepository     = $this->mock(UpdateInfoRepositoryInterface::class);
        $this->configContainer          = $this->mock(ConfigContainerInterface::class);
        $this->shareCreator             = $this->mock(ShareCreatorInterface::class);

        $this->subject = new ShareCreateMethod(
            $this->streamFactory,
            $this->modelFactory,
            $this->functionChecker,
            $this->passwordGenerator,
            $this->expirationDateCalculator,
            $this->updateInfoRepository,
            $this->configContainer,
            $this->shareCreator
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

    /**
     * @dataProvider requestParamDataProvider
     */
    public function testHandleThrowsExceptionIfRequestParamIsMissing(
        array $input,
        string $keyName
    ): void {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %s', $keyName));

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnTrue();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            $input
        );
    }

    public function requestParamDataProvider(): array
    {
        return [
            [[], 'type'],
            [['type' => 1], 'filter']
        ];
    }

    public function testHandleThrowsExceptionIfTypeIsNotSupported(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request');

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnTrue();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'type' => 'foobar',
                'filter' => 666
            ]
        );
    }

    public function testHandleThrowsExceptionIfItemWasNotFound(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);

        $objectId = 666;
        $type     = 'song';

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf('Not Found: %d', $objectId));

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnTrue();

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($type, $objectId)
            ->once()
            ->andReturn($song);

        $song->id = null;

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'type' => $type,
                'filter' => (string) $objectId
            ]
        );
    }

    public function testHandleThrowsExceptionIfShareCouldNotBeCreated(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);

        $objectId             = 666;
        $type                 = 'song';
        $expiryDate           = 1234;
        $password             = 'some-password';
        $description          = 'some-description';
        $calculatedExpiryDate = 33;

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request');

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnTrue();

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($type, $objectId)
            ->once()
            ->andReturn($song);

        $song->id = $objectId;

        $this->shareCreator->shouldReceive('create')
            ->with(
                $type,
                $objectId,
                true,
                true,
                $calculatedExpiryDate,
                $password,
                0,
                $description
            )
            ->once()
            ->andReturnNull();

        $this->functionChecker->shouldReceive('check')
            ->with(AccessLevelEnum::FUNCTION_DOWNLOAD)
            ->once()
            ->andReturnTrue();

        $this->expirationDateCalculator->shouldReceive('calculate')
            ->with($expiryDate)
            ->once()
            ->andReturn($calculatedExpiryDate);

        $this->passwordGenerator->shouldReceive('generate')
            ->with(PasswordGenerator::DEFAULT_LENGTH)
            ->once()
            ->andReturn($password);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'type' => $type,
                'filter' => (string) $objectId,
                'description' => $description,
                'expires' => (string) $expiryDate
            ]
        );
    }

    public function testHandleCreatesShare(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);
        $stream     = $this->mock(StreamInterface::class);

        $objectId             = 666;
        $type                 = 'song';
        $expiryDate           = 1234;
        $password             = 'some-password';
        $description          = 'some-description';
        $calculatedExpiryDate = 33;
        $result               = 'some-result';
        $shareId              = 21;

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::SHARE)
            ->once()
            ->andReturnTrue();

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($type, $objectId)
            ->once()
            ->andReturn($song);

        $song->id = $objectId;

        $this->shareCreator->shouldReceive('create')
            ->with(
                $type,
                $objectId,
                true,
                true,
                $calculatedExpiryDate,
                $password,
                0,
                $description
            )
            ->once()
            ->andReturn($shareId);

        $this->functionChecker->shouldReceive('check')
            ->with(AccessLevelEnum::FUNCTION_DOWNLOAD)
            ->once()
            ->andReturnTrue();

        $this->expirationDateCalculator->shouldReceive('calculate')
            ->with($expiryDate)
            ->once()
            ->andReturn($calculatedExpiryDate);

        $this->passwordGenerator->shouldReceive('generate')
            ->with(PasswordGenerator::DEFAULT_LENGTH)
            ->once()
            ->andReturn($password);

        $this->updateInfoRepository->shouldReceive('updateCountByTableName')
            ->with('share')
            ->once();

        $output->shouldReceive('shares')
            ->with([$shareId], false)
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

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'type' => $type,
                    'filter' => (string) $objectId,
                    'description' => $description,
                    'expires' => (string) $expiryDate
                ]
            )
        );
    }
}

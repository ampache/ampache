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

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\User\NewPasswordSenderInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Sending the new password reaches the mailer, so only the guards are exercised here.
 */
class LostPasswordMethodTest extends MockeryTestCase
{
    private ConfigContainerInterface|MockInterface|null $configContainer;
    private ModelFactoryInterface|MockInterface|null $modelFactory;
    private MockInterface|NewPasswordSenderInterface|null $newPasswordSender;
    private ?LostPasswordMethod $subject;
    private MockInterface|UserRepositoryInterface|null $userRepository;

    /**
     * The same method serves both api versions; the version is only handed to the output
     *
     * @return array<string, array{0: int}>
     */
    public static function apiVersionProvider(): array
    {
        return [
            'api6' => [6],
            'api8' => [8],
        ];
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleReturnsErrorIfMailIsDisabled(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $result = 'error-result';

        AmpConfig::set('mail_enable', false, true);

        $output->shouldReceive('error')
            ->with(
                $apiVersion,
                ErrorCodeEnum::BAD_REQUEST,
                'Bad Request',
                LostPasswordMethod::ACTION,
                'system'
            )
            ->once()
            ->andReturn($result);

        $response->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn($stream);
        $stream->shouldReceive('write')
            ->with($result)
            ->once();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['auth' => 'some-token', 'api_format' => 'json'],
                $user,
                $apiVersion
            )
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleThrowsIfAuthIsMissing(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        AmpConfig::set('mail_enable', true, true);
        AmpConfig::set('demo_mode', false, true);

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::SIMPLE_USER_MODE)
            ->once()
            ->andReturnFalse();

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf(T_('Bad Request: %s'), 'auth'));

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['api_format' => 'json'],
            $user,
            $apiVersion
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleThrowsIfSimpleUserMode(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        AmpConfig::set('mail_enable', true, true);
        AmpConfig::set('demo_mode', false, true);

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::SIMPLE_USER_MODE)
            ->once()
            ->andReturnTrue();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('simple_user_mode');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['auth' => 'some-token', 'api_format' => 'json'],
            $user,
            $apiVersion
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->configContainer   = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory      = $this->mock(ModelFactoryInterface::class);
        $this->newPasswordSender = $this->mock(NewPasswordSenderInterface::class);
        $this->userRepository    = $this->mock(UserRepositoryInterface::class);

        $this->subject = new LostPasswordMethod(
            $this->configContainer,
            $this->modelFactory,
            $this->newPasswordSender,
            $this->userRepository
        );
    }
}

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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

/**
 * Creating the user goes through the User::create() database static, so only the guards ahead of it
 * can be exercised without a database fixture.
 */
class RegisterMethodTest extends MockeryTestCase
{
    private ConfigContainerInterface|MockInterface|null $configContainer;
    private ModelFactoryInterface|MockInterface|null $modelFactory;
    private ?RegisterMethod $subject;
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

    /**
     * @return array<string, array{0: array<string, string>, 1: string}>
     */
    public static function missingParameterProvider(): array
    {
        return [
            'no username' => [['password' => 'p', 'email' => 'e'], 'username'],
            'no password' => [['username' => 'u', 'email' => 'e'], 'password'],
            'no email' => [['username' => 'u', 'password' => 'p'], 'email'],
        ];
    }

    /**
     * @param array<string, string> $input
     */
    #[DataProvider(methodName: 'missingParameterProvider')]
    public function testHandleThrowsIfParameterIsMissing(array $input, string $missing): void
    {
        $apiVersion = 8;

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::ALLOW_PUBLIC_REGISTRATION)
            ->once()
            ->andReturnTrue();

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf(T_('Bad Request: %s'), $missing));

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [...$input, 'api_format' => 'json'],
            $user,
            $apiVersion
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleThrowsIfPublicRegistrationIsDisabled(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::ALLOW_PUBLIC_REGISTRATION)
            ->once()
            ->andReturnFalse();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Enable: allow_public_registration');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['api_format' => 'json'],
            $user,
            $apiVersion
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);
        $this->userRepository  = $this->mock(UserRepositoryInterface::class);

        $this->subject = new RegisterMethod(
            $this->configContainer,
            $this->modelFactory,
            $this->userRepository
        );
    }
}

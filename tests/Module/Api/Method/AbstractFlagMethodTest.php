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
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Api6\Flag6Method;
use Ampache\Module\Api\Method\Api8\Flag8Method;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Setting the flag runs through the Userflag database statics, so only the guards can be exercised
 * without a database fixture.
 *
 * The two versions differ only in how they name the object id, so both are driven through here.
 */
class AbstractFlagMethodTest extends MockeryTestCase
{
    private ConfigContainerInterface|MockInterface|null $configContainer;

    /**
     * Each version, with the name it reports the object id under and the alias it also accepts
     *
     * @return array<string, array{0: class-string<AbstractFlagMethod>, 1: int, 2: string, 3: string}>
     */
    public static function versionProvider(): array
    {
        return [
            'api6 names it id' => [Flag6Method::class, 6, 'id', 'filter'],
            'api8 names it filter' => [Flag8Method::class, 8, 'filter', 'id'],
        ];
    }

    /**
     * @param class-string<AbstractFlagMethod> $className
     */
    #[DataProvider(methodName: 'versionProvider')]
    public function testHandleAcceptsTheAliasName(
        string $className,
        int $apiVersion,
        string $filterKey,
        string $alias,
    ): void {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $result = 'error-result';

        $this->mockRatingsEnabled();

        // an unsupported type proves the id resolved through the alias and reached the type check
        $output->shouldReceive('error')
            ->with(
                $apiVersion,
                ErrorCodeEnum::BAD_REQUEST,
                'Bad Request: bogus',
                AbstractFlagMethod::ACTION,
                'type'
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
            $this->createSubject($className)->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    $alias => '666',
                    'type' => 'bogus',
                    'flag' => 1,
                    'api_format' => 'json',
                    'auth' => 'some-auth',
                ],
                $user,
                $apiVersion
            )
        );
    }

    /**
     * @param class-string<AbstractFlagMethod> $className
     */
    #[DataProvider(methodName: 'versionProvider')]
    public function testHandleThrowsIfDisabled(
        string $className,
        int $apiVersion,
        string $filterKey,
        string $alias,
    ): void {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::RATINGS)
            ->once()
            ->andReturnFalse();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(T_('Enable: ratings'));

        $this->createSubject($className)->handle(
            $gatekeeper,
            $response,
            $output,
            ['api_format' => 'json', 'auth' => 'some-auth'],
            $user,
            $apiVersion
        );
    }

    /**
     * The missing-parameter error has to name the id the way that version names it
     *
     * @param class-string<AbstractFlagMethod> $className
     */
    #[DataProvider(methodName: 'versionProvider')]
    public function testHandleThrowsWithTheVersionsOwnFilterName(
        string $className,
        int $apiVersion,
        string $filterKey,
        string $alias,
    ): void {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $this->mockRatingsEnabled();

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf(T_('Bad Request: %s'), $filterKey));

        $this->createSubject($className)->handle(
            $gatekeeper,
            $response,
            $output,
            ['type' => 'song', 'flag' => 1, 'api_format' => 'json', 'auth' => 'some-auth'],
            $user,
            $apiVersion
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
    }

    /**
     * @param class-string<AbstractFlagMethod> $className
     */
    private function createSubject(string $className): AbstractFlagMethod
    {
        return new $className($this->configContainer);
    }

    private function mockRatingsEnabled(): void
    {
        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::RATINGS)
            ->once()
            ->andReturnTrue();
    }
}

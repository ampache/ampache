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
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\Query\Browse;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Building the name list goes through the Catalog::get_name_array() database static, so the result
 * path cannot be exercised without a database fixture. The guards and the empty browse can.
 */
class ListMethodTest extends MockeryTestCase
{
    private ConfigContainerInterface|MockInterface|null $configContainer;
    private ModelFactoryInterface|MockInterface|null $modelFactory;
    private ?ListMethod $subject;

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
    public function testHandleReturnsEmptyResult(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $browse     = $this->mock(Browse::class);
        $stream     = $this->mock(StreamInterface::class);

        $result = 'empty-result';

        $this->modelFactory->shouldReceive('createBrowse')
            ->with(null, false)
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_user_id')
            ->with($user)
            ->once();
        $browse->shouldReceive('set_type')
            ->with('song')
            ->once();
        $browse->shouldReceive('set_sort_order')
            ->with('', ['name', 'ASC'])
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('alpha_match', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('add', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('update', '')
            ->once();
        $browse->shouldReceive('set_conditions')
            ->with('')
            ->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $output->shouldReceive('writeEmpty')
            ->with($apiVersion, 'song')
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
                ['type' => 'song', 'api_format' => 'json', 'auth' => 'some-auth'],
                $user,
                $apiVersion
            )
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleReturnsErrorIfTypeIsDisabled(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $result = 'error-result';

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::ALLOW_VIDEO)
            ->once()
            ->andReturnFalse();

        $output->shouldReceive('error')
            ->with(
                $apiVersion,
                ErrorCodeEnum::ACCESS_DENIED,
                'Enable: video',
                ListMethod::ACTION,
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
                ['type' => 'video', 'api_format' => 'json', 'auth' => 'some-auth'],
                $user,
                $apiVersion
            )
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleReturnsErrorIfTypeIsNotSupported(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $result = 'error-result';

        $output->shouldReceive('error')
            ->with(
                $apiVersion,
                ErrorCodeEnum::BAD_REQUEST,
                'Bad Request: bogus',
                ListMethod::ACTION,
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
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['type' => 'bogus', 'api_format' => 'json', 'auth' => 'some-auth'],
                $user,
                $apiVersion
            )
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleThrowsIfTypeIsMissing(int $apiVersion): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf(T_('Bad Request: %s'), 'type'));

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['api_format' => 'json', 'auth' => 'some-auth'],
            $user,
            $apiVersion
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);

        $this->subject = new ListMethod(
            $this->configContainer,
            $this->modelFactory
        );
    }
}

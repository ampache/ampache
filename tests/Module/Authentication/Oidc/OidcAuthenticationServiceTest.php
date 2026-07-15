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

namespace Ampache\Module\Authentication\Oidc;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authentication\Oidc\Exception\OidcException;
use Jumbojett\OpenIDConnectClient;
use Jumbojett\OpenIDConnectClientException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Override;
use Psr\Log\LoggerInterface;

class OidcAuthenticationServiceTest extends MockeryTestCase
{
    private MockInterface|OidcClientFactoryInterface|null $clientFactory;
    private MockInterface|ConfigContainerInterface|null $configContainer;
    private MockInterface|LoggerInterface|null $logger;
    private ?OidcAuthenticationService $subject;

    public function testHandleCallbackFailsIfNotConfigured(): void
    {
        $this->clientFactory->shouldReceive('create')
            ->withNoArgs()
            ->once()
            ->andThrow(new OidcException('oidc_url is missing'));

        $this->logger->shouldReceive('error')->once();

        $result = $this->subject->handleCallback();

        self::assertFalse($result['success']);
        self::assertArrayNotHasKey('username', $result);
    }

    public function testHandleCallbackFailsIfTheProviderRejectsTheLogin(): void
    {
        $client = Mockery::mock(OpenIDConnectClient::class);
        $client->shouldReceive('authenticate')
            ->withNoArgs()
            ->once()
            ->andThrow(new OpenIDConnectClientException('Unable to determine state'));

        $this->clientFactory->shouldReceive('create')
            ->withNoArgs()
            ->once()
            ->andReturn($client);

        $this->logger->shouldReceive('error')->once();

        $result = $this->subject->handleCallback();

        self::assertFalse($result['success']);
        self::assertArrayNotHasKey('username', $result);
        self::assertStringNotContainsString('state', (string) $result['error']);
    }

    public function testHandleCallbackFailsIfTheUsernameClaimIsMissing(): void
    {
        $this->mockClient((object) ['email' => 'some-user@example.com']);

        $this->logger->shouldReceive('error')->once();

        $this->configureWith([]);

        $result = $this->subject->handleCallback();

        self::assertFalse($result['success']);
        self::assertArrayNotHasKey('username', $result);
    }

    public function testHandleCallbackFallsBackToTheIdTokenClaims(): void
    {
        $client = $this->mockClient(
            (object) ['preferred_username' => 'some-username', 'name' => 'Some Name']
        );
        $client->shouldReceive('requestUserInfo')
            ->withNoArgs()
            ->once()
            ->andThrow(new OpenIDConnectClientException('no userinfo endpoint'));

        $this->logger->shouldReceive('warning')->once();

        $this->configureWith([]);

        $result = $this->subject->handleCallback();

        self::assertTrue($result['success']);
        self::assertSame('some-username', $result['username']);
        self::assertSame('Some Name', $result['name']);
    }

    public function testHandleCallbackReturnsTheConfiguredClaims(): void
    {
        $this->mockClient(
            (object) [
                'upn' => 'some-username',
                'displayName' => 'Some Name',
                'mail' => 'some-user@example.com',
                'preferred_username' => 'ignored-username',
            ]
        );

        $this->configureWith([
            ConfigurationKeyEnum::OIDC_USERNAME_CLAIM => 'upn',
            ConfigurationKeyEnum::OIDC_NAME_CLAIM => 'displayName',
            ConfigurationKeyEnum::OIDC_EMAIL_CLAIM => 'mail',
        ]);

        self::assertSame(
            [
                'success' => true,
                'type' => 'oidc',
                'username' => 'some-username',
                'name' => 'Some Name',
                'email' => 'some-user@example.com',
            ],
            $this->subject->handleCallback()
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->configContainer = Mockery::mock(ConfigContainerInterface::class);
        $this->clientFactory   = Mockery::mock(OidcClientFactoryInterface::class);
        $this->logger          = Mockery::mock(LoggerInterface::class);

        $this->subject = new OidcAuthenticationService(
            $this->configContainer,
            $this->clientFactory,
            $this->logger
        );
    }

    /**
     * @param array<string, string> $config
     */
    private function configureWith(array $config): void
    {
        $config[ConfigurationKeyEnum::OIDC_NAME_CLAIM] ??= 'name';
        $config[ConfigurationKeyEnum::OIDC_EMAIL_CLAIM] ??= 'email';

        $this->configContainer->shouldReceive('get')
            ->andReturnUsing(static fn(string $configKey): ?string => $config[$configKey] ?? null);
    }

    private function mockClient(object $claims): MockInterface|OpenIDConnectClient
    {
        $client = Mockery::mock(OpenIDConnectClient::class);
        $client->shouldReceive('authenticate')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();
        $client->shouldReceive('getVerifiedClaims')
            ->withNoArgs()
            ->once()
            ->andReturn($claims);
        $client->shouldReceive('requestUserInfo')
            ->withNoArgs()
            ->andReturn($claims)
            ->byDefault();

        $this->clientFactory->shouldReceive('create')
            ->withNoArgs()
            ->once()
            ->andReturn($client);

        return $client;
    }
}

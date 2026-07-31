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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

class OidcClientFactoryTest extends MockeryTestCase
{
    private MockInterface|ConfigContainerInterface|null $configContainer;
    private ?OidcClientFactory $subject;

    /**
     * @return list<array{0: string}>
     */
    public static function requiredConfigKeyDataProvider(): array
    {
        return [
            [ConfigurationKeyEnum::OIDC_URL],
            [ConfigurationKeyEnum::OIDC_CLIENT_ID],
            [ConfigurationKeyEnum::OIDC_CLIENT_SECRET],
        ];
    }

    public function testCreateBuildsAClient(): void
    {
        $this->configureWith([
            ConfigurationKeyEnum::OIDC_URL => 'https://idp.example.com',
            ConfigurationKeyEnum::OIDC_CLIENT_ID => 'ampache',
            ConfigurationKeyEnum::OIDC_CLIENT_SECRET => 'some-secret',
        ]);

        $client = $this->subject->create();

        self::assertSame(
            'https://music.example.com/oidc/',
            $client->getRedirectURL()
        );
    }

    public function testCreateDeclaresPkceSupportWhenTheEndpointsAreSetByHand(): void
    {
        $this->configureWith([
            ConfigurationKeyEnum::OIDC_URL => 'https://idp.example.com',
            ConfigurationKeyEnum::OIDC_CLIENT_ID => 'ampache',
            ConfigurationKeyEnum::OIDC_CLIENT_SECRET => 'some-secret',
            ConfigurationKeyEnum::OIDC_AUTHORIZATION_ENDPOINT => 'https://idp.example.com/auth',
        ]);

        $providerConfig = new ReflectionProperty(OpenIDConnectClient::class, 'providerConfig');

        self::assertSame(
            ['S256'],
            $providerConfig->getValue($this->subject->create())['code_challenge_methods_supported'] ?? null
        );
    }

    public function testCreateDoesNotRepeatTheOpenidScope(): void
    {
        $this->configureWith([
            ConfigurationKeyEnum::OIDC_URL => 'https://idp.example.com',
            ConfigurationKeyEnum::OIDC_CLIENT_ID => 'ampache',
            ConfigurationKeyEnum::OIDC_CLIENT_SECRET => 'some-secret',
            ConfigurationKeyEnum::OIDC_SCOPES => 'openid,profile,email',
        ]);

        self::assertSame(
            ['profile', 'email'],
            $this->subject->create()->getScopes()
        );
    }

    #[DataProvider('requiredConfigKeyDataProvider')]
    public function testCreateThrowsIfRequiredConfigIsMissing(string $missingConfigKey): void
    {
        $config = [
            ConfigurationKeyEnum::OIDC_URL => 'https://idp.example.com',
            ConfigurationKeyEnum::OIDC_CLIENT_ID => 'ampache',
            ConfigurationKeyEnum::OIDC_CLIENT_SECRET => 'some-secret',
        ];
        $config[$missingConfigKey] = '';

        $this->configureWith($config);

        static::expectException(OidcException::class);

        $this->subject->create();
    }

    #[Override]
    protected function setUp(): void
    {
        $this->configContainer = Mockery::mock(ConfigContainerInterface::class);

        $this->subject = new OidcClientFactory(
            $this->configContainer
        );
    }

    /**
     * @param array<string, string> $config
     */
    private function configureWith(array $config): void
    {
        $this->configContainer->shouldReceive('get')
            ->andReturnUsing(static fn(string $configKey): ?string => $config[$configKey] ?? null);

        $this->configContainer->shouldReceive('getWebPath')
            ->andReturn('https://music.example.com');

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->andReturn(false);
    }
}

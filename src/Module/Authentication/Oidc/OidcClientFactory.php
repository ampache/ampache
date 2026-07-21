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

final readonly class OidcClientFactory implements OidcClientFactoryInterface
{
    /**
     * Redirect without query string; some providers (e.g. Entra ID) refuse reply urls containing query strings
     */
    public const string CALLBACK_PATH = '/oidc/';

    /**
     * Scopes requested when none are configured
     */
    private const string DEFAULT_SCOPES = 'openid,profile,email';

    private const array ENDPOINT_OVERRIDES = [
        'authorization_endpoint' => ConfigurationKeyEnum::OIDC_AUTHORIZATION_ENDPOINT,
        'token_endpoint' => ConfigurationKeyEnum::OIDC_TOKEN_ENDPOINT,
        'userinfo_endpoint' => ConfigurationKeyEnum::OIDC_USERINFO_ENDPOINT,
        'jwks_uri' => ConfigurationKeyEnum::OIDC_JWKS_URI,
        'end_session_endpoint' => ConfigurationKeyEnum::OIDC_END_SESSION_ENDPOINT,
    ];

    public function __construct(
        private ConfigContainerInterface $configContainer,
    ) {}

    /**
     * @throws OidcException
     */
    public function create(): OpenIDConnectClient
    {
        $providerUrl  = $this->readConfig(ConfigurationKeyEnum::OIDC_URL);
        $clientId     = $this->readConfig(ConfigurationKeyEnum::OIDC_CLIENT_ID);
        $clientSecret = $this->readConfig(ConfigurationKeyEnum::OIDC_CLIENT_SECRET);
        $issuer       = $this->readConfig(ConfigurationKeyEnum::OIDC_ISSUER);

        if ($providerUrl === '' || $clientId === '' || $clientSecret === '') {
            throw new OidcException(
                'OpenID Connect requires oidc_url, oidc_client_id and oidc_client_secret to be set'
            );
        }

        $client = new OpenIDConnectClient(
            $providerUrl,
            $clientId,
            $clientSecret,
            ($issuer === '') ? null : $issuer
        );

        $client->setRedirectURL($this->configContainer->getWebPath() . self::CALLBACK_PATH);
        $client->setCodeChallengeMethod('S256');

        // the client always requests the `openid` scope itself, so passing it along would duplicate it
        $scopes = $this->readConfig(ConfigurationKeyEnum::OIDC_SCOPES);
        $client->addScope(
            array_values(
                array_unique(
                    array_filter(
                        array_map('trim', explode(',', ($scopes === '') ? self::DEFAULT_SCOPES : $scopes)),
                        static fn(string $scope): bool => $scope !== '' && $scope !== 'openid'
                    )
                )
            )
        );

        $certPath = $this->readConfig(ConfigurationKeyEnum::OIDC_CERT_PATH);
        if ($certPath !== '') {
            $client->setCertPath($certPath);
        }

        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::OIDC_DISABLE_SSL_VERIFY)) {
            $client->setVerifyHost(false);
            $client->setVerifyPeer(false);
        }

        foreach (self::ENDPOINT_OVERRIDES as $parameter => $configKey) {
            $endpoint = $this->readConfig($configKey);
            if ($endpoint !== '') {
                $client->providerConfigParam([$parameter => $endpoint]);
            }
        }

        // Only sends a pkce challenge when the provider announces support for one
        if ($this->readConfig(ConfigurationKeyEnum::OIDC_AUTHORIZATION_ENDPOINT) !== '') {
            $client->providerConfigParam(['code_challenge_methods_supported' => ['S256']]);
        }

        return $client;
    }

    private function readConfig(string $configKey): string
    {
        $value = $this->configContainer->get($configKey);

        return is_scalar($value) ? trim((string) $value) : '';
    }
}

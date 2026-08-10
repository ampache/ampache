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
use Ampache\Module\System\LegacyLogger;
use Jumbojett\OpenIDConnectClient;
use Jumbojett\OpenIDConnectClientException;
use Psr\Log\LoggerInterface;

final readonly class OidcAuthenticationService implements OidcAuthenticationServiceInterface
{
    public const string AUTH_TYPE = 'oidc';

    public const string SESSION_REFERRER_KEY = 'oidc_referrer';

    /**
     * Claims which are copied to the local account when configured. Mapping to the field used by Login\DefaultAction
     */
    private const array OPTIONAL_CLAIMS = [
        'name' => ConfigurationKeyEnum::OIDC_NAME_CLAIM,
        'email' => ConfigurationKeyEnum::OIDC_EMAIL_CLAIM,
        'website' => ConfigurationKeyEnum::OIDC_WEBSITE_CLAIM,
        'state' => ConfigurationKeyEnum::OIDC_STATE_CLAIM,
        'city' => ConfigurationKeyEnum::OIDC_CITY_CLAIM,
    ];

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private OidcClientFactoryInterface $clientFactory,
        private LoggerInterface $logger,
    ) {}

    /**
     * @return array{
     *     success: bool,
     *     type?: string,
     *     username?: string,
     *     name?: string,
     *     email?: string,
     *     website?: string,
     *     state?: string,
     *     city?: string,
     *     error?: string
     * }
     */
    public function handleCallback(): array
    {
        try {
            $client = $this->clientFactory->create();
            $client->authenticate();

            $claims = $this->collectClaims($client);
        } catch (OidcException $error) {
            $this->logger->error(
                $error->getMessage(),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return [
                'success' => false,
                'error' => T_('OpenID Connect is not configured correctly'),
            ];
        } catch (OpenIDConnectClientException $error) {
            $this->logger->error(
                sprintf('OpenID Connect authentication failed: %s', $error->getMessage()),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return [
                'success' => false,
                'error' => T_('OpenID Connect authentication failed'),
            ];
        } finally {
            if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
                session_start();
            }
        }

        $usernameClaim = $this->readConfig(ConfigurationKeyEnum::OIDC_USERNAME_CLAIM) ?: 'preferred_username';
        $username      = $this->readClaim($claims, $usernameClaim);

        if ($username === '') {
            $this->logger->error(
                sprintf('OpenID Connect: the claim "%s" is missing or empty', $usernameClaim),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return [
                'success' => false,
                'error' => T_('The identity provider did not return a username'),
            ];
        }

        $result = [
            'success' => true,
            'type' => self::AUTH_TYPE,
            'username' => $username,
        ];

        foreach (self::OPTIONAL_CLAIMS as $field => $configKey) {
            $claimName = $this->readConfig($configKey);
            if ($claimName === '') {
                continue;
            }

            $value = $this->readClaim($claims, $claimName);
            if ($value !== '') {
                $result[$field] = $value;
            }
        }

        return $result;
    }

    /**
     * @throws OidcException
     */
    public function redirectToProvider(string $referrer = ''): void
    {
        $_SESSION[self::SESSION_REFERRER_KEY] = $referrer;

        // sends a Location header and terminates the request
        $this->clientFactory->create()->authenticate();
    }

    /**
     * @return array<string, mixed>
     */
    private function collectClaims(OpenIDConnectClient $client): array
    {
        $claims = [];

        $verifiedClaims = $client->getVerifiedClaims();
        if (is_object($verifiedClaims)) {
            $claims = get_object_vars($verifiedClaims);
        }

        $useUserInfo = $this->configContainer->get(ConfigurationKeyEnum::OIDC_USE_USERINFO);
        if ($useUserInfo === null || (bool) $useUserInfo) {
            try {
                $userInfo = $client->requestUserInfo();
                if (is_object($userInfo)) {
                    $claims = array_merge($claims, get_object_vars($userInfo));
                }
            } catch (OpenIDConnectClientException $error) {
                $this->logger->warning(
                    sprintf('OpenID Connect: the userinfo request failed, using the id token claims: %s', $error->getMessage()),
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }
        }

        return $claims;
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function readClaim(array $claims, string $claimName): string
    {
        $value = $claims[$claimName] ?? null;
        if (!is_scalar($value)) {
            return '';
        }

        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', (string) $value) ?? '';

        // the username column holds 128 characters
        return mb_substr(trim($value), 0, 128);
    }

    private function readConfig(string $configKey): string
    {
        $value = $this->configContainer->get($configKey);

        return is_scalar($value) ? trim((string) $value) : '';
    }
}

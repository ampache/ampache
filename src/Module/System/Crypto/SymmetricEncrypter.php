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

namespace Ampache\Module\System\Crypto;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Throwable;

/**
 * AES-256-GCM implementation of the symmetric encrypter. GCM authenticates the ciphertext, so a payload that has been
 * edited in the database, truncated, or written under a different `secret_key` fails to decrypt instead of returning
 * plausible garbage. The stored payload is `base64(iv . tag . ciphertext)` with a fresh random iv for every write.
 */
final readonly class SymmetricEncrypter implements SymmetricEncrypterInterface
{
    private const string CIPHER = 'aes-256-gcm';

    private const int IV_LENGTH = 12;

    private const int TAG_LENGTH = 16;

    public function __construct(
        private ConfigContainerInterface $configContainer,
    ) {}

    public function decrypt(string $payload): ?string
    {
        $key = $this->deriveKey();
        if ($key === null || $payload === '') {
            return null;
        }

        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) <= self::IV_LENGTH + self::TAG_LENGTH) {
            return null;
        }

        $plaintext = openssl_decrypt(
            substr($raw, self::IV_LENGTH + self::TAG_LENGTH),
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            substr($raw, 0, self::IV_LENGTH),
            substr($raw, self::IV_LENGTH, self::TAG_LENGTH)
        );

        return ($plaintext === false)
            ? null
            : $plaintext;
    }

    public function encrypt(string $plaintext): ?string
    {
        $key = $this->deriveKey();
        if ($key === null) {
            return null;
        }

        try {
            $iv = random_bytes(self::IV_LENGTH);
        } catch (Throwable) {
            return null;
        }

        $tag        = '';
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', self::TAG_LENGTH);
        if ($ciphertext === false) {
            return null;
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * `secret_key` is a user-supplied string of arbitrary length, so it is hashed down to the 32 raw bytes AES-256
     * requires rather than being passed through as-is
     */
    private function deriveKey(): ?string
    {
        $secretKey = (string) $this->configContainer->get(ConfigurationKeyEnum::SECRET_KEY);

        return ($secretKey === '')
            ? null
            : hash('sha256', $secretKey, true);
    }
}

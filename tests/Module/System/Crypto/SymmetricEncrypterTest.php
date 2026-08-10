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
use Ampache\MockeryTestCase;
use Mockery\MockInterface;
use Override;

class SymmetricEncrypterTest extends MockeryTestCase
{
    private const string SECRET_KEY = 'some-secret-key';

    private ConfigContainerInterface|MockInterface|null $configContainer = null;
    private ?SymmetricEncrypter $subject                                 = null;

    public function testDecryptReturnsNullOnGarbage(): void
    {
        $this->expectSecretKey(self::SECRET_KEY);

        self::assertNull($this->subject->decrypt('not base64 at all'));
        self::assertNull($this->subject->decrypt(base64_encode('short')));
        self::assertNull($this->subject->decrypt(''));
    }

    public function testDecryptReturnsNullOnTamperedPayload(): void
    {
        $this->expectSecretKey(self::SECRET_KEY);

        $payload = (string) $this->subject->encrypt('some-subsonic-password');
        // flip the final base64 character so the authentication tag no longer matches the ciphertext
        $tampered = substr($payload, 0, -1) . (str_ends_with($payload, 'A') ? 'B' : 'A');

        self::assertNull($this->subject->decrypt($tampered));
    }

    public function testDecryptReturnsNullWhenTheSecretKeyChanged(): void
    {
        // the first key is used for the write, the rotated one for the read back
        $this->expectSecretKey(self::SECRET_KEY, 'a-rotated-secret-key');

        $payload = (string) $this->subject->encrypt('some-subsonic-password');

        self::assertNull($this->subject->decrypt($payload));
    }

    public function testEncryptAndDecryptReturnNullWithoutASecretKey(): void
    {
        $this->expectSecretKey('');

        self::assertNull($this->subject->encrypt('some-subsonic-password'));
        self::assertNull($this->subject->decrypt('anything'));
    }

    public function testEncryptDecryptRoundTrip(): void
    {
        $this->expectSecretKey(self::SECRET_KEY);

        $payload = $this->subject->encrypt('some-subsonic-password');

        self::assertIsString($payload);
        self::assertSame('some-subsonic-password', $this->subject->decrypt($payload));
    }

    public function testEncryptProducesADifferentPayloadEveryTime(): void
    {
        $this->expectSecretKey(self::SECRET_KEY);

        self::assertNotSame(
            $this->subject->encrypt('some-subsonic-password'),
            $this->subject->encrypt('some-subsonic-password')
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);

        $this->subject = new SymmetricEncrypter($this->configContainer);
    }

    /**
     * Queues the `secret_key` values the subject will read; the final value is repeated once the list runs out
     */
    private function expectSecretKey(string ...$secretKeys): void
    {
        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::SECRET_KEY)
            ->andReturn(...$secretKeys);
    }
}

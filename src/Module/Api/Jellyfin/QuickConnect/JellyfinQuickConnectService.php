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

namespace Ampache\Module\Api\Jellyfin\QuickConnect;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;

/**
 * Holds every QuickConnect policy decision (TTL, code/secret generation, rate limiting, consumption) so no
 * Method class has to reimplement the security rules the plan's §B.5 lays out.
 */
final class JellyfinQuickConnectService
{
    /**
     * Real Jellyfin's own QuickConnect code is plain digits, and clients build their entry UI on that
     * assumption — confirmed against Finamp, whose code field is numeric-only and simply cannot accept a
     * letter. An alphanumeric code (the original, more entropy-per-character choice) is unusable there.
     */
    private const string CODE_ALPHABET = '0123456789';

    private const int CODE_LENGTH = 6;

    private const int MAX_AUTHORIZE_ATTEMPTS = 5;

    private const int MAX_INITIATE_PER_WINDOW = 10;

    /** Same window used to bound both the code/secret lifetime and the per-device `/Initiate` count. */
    private const int TTL_SECONDS = 300;

    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly JellyfinQuickConnectRepositoryInterface $repository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * Bridges `Session::garbage_collection()` (a static, legacy-style method with no DI access of its own)
     * into this DI-constructed service, the same way `Song_Preview::garbage_collection()` reaches its
     * repository.
     */
    public static function garbageCollection(): void
    {
        global $dic;

        $dic->get(JellyfinQuickConnectRepositoryInterface::class)->deleteExpired(time());
    }

    /**
     * `forbidden` (a non-admin passed a foreign `userId`) is the only case that maps to HTTP 403 — an
     * invalid/expired/locked-out code is still a plain `success: false` per the spec's own response list.
     *
     * @return array{success: bool, forbidden: bool}
     */
    public function authorize(string $code, User $callingUser, ?int $overrideUserId): array
    {
        if ($overrideUserId !== null && $overrideUserId !== $callingUser->getId() && !$callingUser->has_access(AccessLevelEnum::ADMIN)) {
            return ['success' => false, 'forbidden' => true];
        }

        $now = time();
        $row = $this->repository->findByCode($code, $now);
        if ($row === null) {
            return ['success' => false, 'forbidden' => false];
        }

        $attempts = $this->repository->incrementAuthorizeAttempts((int) $row['id']);
        if ($attempts > self::MAX_AUTHORIZE_ATTEMPTS) {
            debug_event(self::class, 'QuickConnect code locked out after too many attempts: ' . $code, 3);

            return ['success' => false, 'forbidden' => false];
        }

        $boundUserId = $overrideUserId ?? $callingUser->getId();
        $this->repository->markAuthorized((int) $row['id'], $boundUserId);
        debug_event(self::class, 'QuickConnect authorized: code=' . $code . ' user=' . $boundUserId, 4);

        return ['success' => true, 'forbidden' => false];
    }

    /**
     * @return array{ok: bool, user: ?User}
     */
    public function consume(string $secret): array
    {
        $userId = $this->repository->consume($secret, time());
        if ($userId === null) {
            return ['ok' => false, 'user' => null];
        }

        $user = $this->userRepository->findById($userId);
        // account state is re-checked here, not trusted from when the code was approved
        if ($user === null || $user->disabled) {
            return ['ok' => false, 'user' => null];
        }

        debug_event(self::class, 'QuickConnect consumed for user: ' . $userId, 4);

        return ['ok' => true, 'user' => $user];
    }

    /** @return array<string, mixed>|null */
    public function findBySecret(string $secret): ?array
    {
        return $this->repository->findBySecret($secret, time());
    }

    /**
     * The returned shape matches a stored row closely enough that `QuickConnectResultMapper` can build a
     * `QuickConnectResult` from either. Null means the per-device rate limit was hit — the caller answers
     * 503, the same signal a disabled preference uses.
     *
     * @return array{secret: string, code: string, device_id: string, device_name: string, app_name: string, app_version: string, date_added: int, authorized: bool}|null
     */
    public function initiate(string $deviceId, string $deviceName, string $appName, string $appVersion): ?array
    {
        $now = time();
        if ($this->repository->countRecentByDeviceId($deviceId, $now - self::TTL_SECONDS) >= self::MAX_INITIATE_PER_WINDOW) {
            debug_event(self::class, 'QuickConnect /Initiate rate limit hit for device: ' . $deviceId, 3);

            return null;
        }

        $secret = bin2hex(random_bytes(32));
        $code   = $this->generateCode();

        $this->repository->create([
            'secret' => $secret,
            'code' => $code,
            'device_id' => $deviceId,
            'device_name' => $deviceName,
            'app_name' => $appName,
            'app_version' => $appVersion,
            'date_added' => $now,
            'expires' => $now + self::TTL_SECONDS,
        ]);
        debug_event(self::class, 'QuickConnect initiated for device: ' . $deviceId, 4);

        return [
            'secret' => $secret,
            'code' => $code,
            'device_id' => $deviceId,
            'device_name' => $deviceName,
            'app_name' => $appName,
            'app_version' => $appVersion,
            'date_added' => $now,
            'authorized' => false,
        ];
    }

    public function isEnabled(): bool
    {
        return $this->configContainer->getBool(ConfigurationKeyEnum::JELLYFIN_QUICKCONNECT_ENABLE);
    }

    private function generateCode(): string
    {
        $code = '';
        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
        }

        return $code;
    }
}

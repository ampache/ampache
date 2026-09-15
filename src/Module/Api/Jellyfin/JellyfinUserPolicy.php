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

namespace Ampache\Module\Api\Jellyfin;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\User;

/**
 * The `Policy` object real Jellyfin nests on every `UserDto`, wherever one appears — the login response's
 * `User`, `GET /Users/Me`, and `GET /Users/{userId}` all carry it. Shared so the three can't drift.
 *
 * Every field here is one Finamp's own generated model (`UserPolicy` in `jellyfin_models.dart`) declares
 * `required` on a non-nullable type, confirmed by reading that file directly — a missing or `null` value on
 * any of them throws `type 'Null' is not a subtype of type 'bool'` (or `int`/`String`) in its generated
 * `fromJson`, which crashed login entirely rather than just showing a broken field. The vendored spec marks
 * most of these `nullable: true`, so this is a case of a real client being stricter than the spec, not the
 * spec being wrong — the earlier version of this class trusted the spec's nullability and got it wrong.
 * `EnableLiveTvManagement`/`EnableLiveTvAccess`/`EnablePlaybackRemuxing` are always false and
 * `SyncPlayAccess` is always `'None'` because Ampache has nothing behind any of them.
 */
final class JellyfinUserPolicy
{
    /** @return array<string, mixed> */
    public static function build(User $user): array
    {
        $isAdmin   = $user->has_access(AccessLevelEnum::ADMIN);
        $isManager = $user->has_access(AccessLevelEnum::MANAGER);
        $canStream = AmpConfig::get_bool('allow_stream_playback') && (bool) $user->getPreferenceValue('allow_stream_playback');

        return [
            'IsAdministrator' => $isAdmin,
            'IsHidden' => false,
            'IsDisabled' => (bool) $user->disabled,
            'EnableUserPreferenceAccess' => true,
            'EnableRemoteControlOfOtherUsers' => $isAdmin,
            'EnableSharedDeviceControl' => $isAdmin,
            'EnableRemoteAccess' => true,
            'EnableLiveTvManagement' => false,
            'EnableLiveTvAccess' => false,
            'EnableMediaPlayback' => $canStream,
            'EnableAudioPlaybackTranscoding' => $canStream,
            'EnableVideoPlaybackTranscoding' => false,
            'EnablePlaybackRemuxing' => false,
            'ForceRemoteSourceTranscoding' => false,
            'EnableContentDeletion' => $isManager,
            'EnableContentDeletionFromFolders' => [],
            'EnableContentDownloading' => AmpConfig::get_bool('download'),
            'EnableSyncTranscoding' => $canStream,
            'EnableMediaConversion' => $isManager,
            'EnableAllDevices' => true,
            'EnableAllChannels' => true,
            'EnableAllFolders' => true,
            'InvalidLoginAttemptCount' => 0,
            'LoginAttemptsBeforeLockout' => -1,
            'MaxActiveSessions' => 0,
            'EnablePublicSharing' => false,
            'RemoteClientBitrateLimit' => 0,
            'AuthenticationProviderId' => 'Default',
            'PasswordResetProviderId' => 'Default',
            'SyncPlayAccess' => 'None',
        ];
    }
}

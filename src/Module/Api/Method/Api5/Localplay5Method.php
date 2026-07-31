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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Controls the localplay instance.
 *
 * Version 5 gates on the interface access type and does not know the `filter` aliases of the later
 * versions, so it keeps a method of its own.
 */
final class Localplay5Method implements MethodInterface
{
    public const string ACTION = 'localplay';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private PrivilegeCheckerInterface $privilegeChecker,
        private StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * localplay
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=5.0.0
     *
     * This is for controlling Localplay
     *
     * command = (string) 'next', 'prev', 'stop', 'play', 'pause', 'add', 'volume_up', 'volume_down', 'volume_mute', 'delete_all', 'skip', 'status'
     * oid = (integer) object_id //optional
     * type = (string) 'Song', 'Video', 'Podcast_Episode', 'Broadcast', 'Democratic', 'Live_Stream' //optional
     * clear = (integer) 0,1 Clear the current playlist before adding //optional
     * track = (integer) used in conjunction with skip to skip to the track id (use localplay_songs to get your track list) //optional
     *
     * @param array{
     *     command?: string,
     *     oid?: string,
     *     type?: string,
     *     clear?: int,
     *     track?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 5 $apiVersion
     * @throws AccessDeniedException|AccessFailedException|RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!array_key_exists('command', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'command')
            );
        }

        // localplay is actually meant to be behind permissions
        $level = AccessLevelEnum::from(
            (int) ($this->configContainer->get(ConfigurationKeyEnum::LOCALPLAY_LEVEL) ?? AccessLevelEnum::ADMIN->value)
        );

        if (!$this->privilegeChecker->check(AccessTypeEnum::LOCALPLAY, $level, $user->id)) {
            throw new AccessFailedException(
                sprintf('Require: %s', $level->value)
            );
        }

        // Load their Localplay instance
        $localplay = new LocalPlay((string) ($this->configContainer->get(ConfigurationKeyEnum::LOCALPLAY_CONTROLLER) ?? ''));
        if (empty($localplay->type) || !$localplay->connect()) {
            return $this->writeNoController($response, $output, $apiVersion);
        }

        $result  = false;
        $status  = null;
        $command = strtolower($input['command']);
        switch ($command) {
            case 'add':
                // for add commands get the object details
                $object_id = (int) ($input['oid'] ?? 0);
                $type      = LibraryItemEnum::tryFrom(strtolower($input['type'] ?? '')) ?? LibraryItemEnum::SONG;

                if (
                    !$this->configContainer->get(ConfigurationKeyEnum::ALLOW_VIDEO)
                    && $type === LibraryItemEnum::VIDEO
                ) {
                    throw new AccessDeniedException(
                        'Enable: video'
                    );
                }

                $clear = (int) ($input['clear'] ?? 0);
                if ($localplay->type === 'mpd') {
                    $localplay->set_block_clear(make_bool((string) $clear));
                }

                // clear before the add
                if ($clear === 1) {
                    $localplay->delete_all();
                }

                $media = [
                    'object_type' => $type,
                    'object_id' => $object_id,
                ];
                $playlist = new Stream_Playlist();
                $playlist->add([$media], '&client=' . $localplay->type);
                foreach ($playlist->urls as $streams) {
                    $result = $localplay->add_url($streams);
                }
                break;
            case 'skip':
                // localplay_songs 'track' starts at 1 but localplay starts at 0 behind the scenes
                $result = $localplay->skip((int) ($input['track'] ?? 1) - 1);
                break;
            case 'next':
                $result = $localplay->next();
                break;
            case 'prev':
                $result = $localplay->prev();
                break;
            case 'stop':
                $result = $localplay->stop();
                break;
            case 'play':
                $result = $localplay->play();
                break;
            case 'pause':
                $result = $localplay->pause();
                break;
            case 'volume_up':
                $result = $localplay->volume_up();
                break;
            case 'volume_down':
                $result = $localplay->volume_down();
                break;
            case 'volume_mute':
                $result = $localplay->volume_mute();
                break;
            case 'delete_all':
                $result = $localplay->delete_all();
                break;
            case 'status':
                $status = $localplay->status();
                break;
            default:
                // They are doing it wrong
                return $response->withBody(
                    $this->streamFactory->createStream(
                        $output->error(
                            $apiVersion,
                            ErrorCodeEnum::BAD_REQUEST,
                            'Bad Request',
                            self::ACTION,
                            'command'
                        )
                    )
                );
        }

        if ($command === 'status' && empty($status)) {
            return $this->writeNoController($response, $output, $apiVersion);
        }

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->localplayResult(
                    $apiVersion,
                    $input['command'],
                    (!empty($status)) ? $status : $result
                )
            )
        );
    }

    /**
     * @param 5 $apiVersion
     */
    private function writeNoController(
        ResponseInterface $response,
        ApiOutputInterface $output,
        int $apiVersion,
    ): ResponseInterface {
        return $response->withBody(
            $this->streamFactory->createStream(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    'Unable to connect to localplay controller',
                    self::ACTION,
                    'account'
                )
            )
        );
    }
}

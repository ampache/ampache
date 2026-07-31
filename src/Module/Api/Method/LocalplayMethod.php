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
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Controls the localplay instance
 */
final class LocalplayMethod implements MethodInterface
{
    public const string ACTION = 'localplay';

    /** @var string[] the commands that can also arrive as `filter` */
    private const array FILTER_COMMANDS = [
        'next',
        'prev',
        'stop',
        'play',
        'pause',
        'volume_up',
        'volume_down',
        'volume_mute',
        'delete_all',
        'skip',
        'status',
    ];

    private ConfigContainerInterface $configContainer;
    private PrivilegeCheckerInterface $privilegeChecker;

    public function __construct(
        ConfigContainerInterface $configContainer,
        PrivilegeCheckerInterface $privilegeChecker,
    ) {
        $this->configContainer  = $configContainer;
        $this->privilegeChecker = $privilegeChecker;
    }

    /**
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=5.0.0
     *
     * This is for controlling Localplay
     *
     * command = (string) 'next', 'prev', 'stop', 'play', 'pause', 'add', 'volume_up', 'volume_down',
     *                    'volume_mute', 'delete_all', 'skip', 'status'
     * oid     = (string) object_id //optional
     * type    = (string) 'Song', 'Video', 'Podcast_Episode', 'Broadcast', 'Live_Stream', 'Democratic' //optional
     * clear   = (integer) 0,1 Clear the current playlist before adding //optional
     * track   = (integer) used with skip to skip to the track id //optional
     *
     * @param array{
     *     command?: string,
     *     filter?: string,
     *     oid?: string,
     *     type?: string,
     *     clear?: int,
     *     track?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
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
        // a bare command may arrive as `filter`
        if (
            !isset($input['command'])
            && isset($input['filter'])
            && in_array($input['filter'], self::FILTER_COMMANDS, true)
        ) {
            $input['command'] = $input['filter'];
        }

        if (!array_key_exists('command', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'command')
            );
        }

        // localplay is actually meant to be behind permissions
        $level = AccessLevelEnum::from(
            (int) ($this->configContainer->get('localplay_level') ?? AccessLevelEnum::ADMIN->value)
        );

        if (!$this->privilegeChecker->check(AccessTypeEnum::LOCALPLAY, $level, $user->getId())) {
            throw new AccessFailedException(
                sprintf('Require: %s', $level->value)
            );
        }

        // Load their Localplay instance
        $localplay = new LocalPlay((string) ($this->configContainer->get('localplay_controller') ?? ''));
        if (empty($localplay->type)) {
            return $this->writeNoController(
                $response,
                $output,
                $apiVersion,
                'No localplay controller is configured'
            );
        }

        if (!$localplay->connect()) {
            return $this->writeNoController($response, $output, $apiVersion);
        }

        $command = strtolower((string) $input['command']);
        $result  = false;
        $status  = null;

        switch ($command) {
            case 'add':
                // for add commands get the object details
                $objectId = (int) ($input['filter'] ?? $input['oid'] ?? 0);
                $type     = LibraryItemEnum::tryFrom(strtolower($input['type'] ?? '')) ?? LibraryItemEnum::SONG;

                if (
                    $type === LibraryItemEnum::VIDEO
                    && !$this->configContainer->get(ConfigurationKeyEnum::ALLOW_VIDEO)
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

                $playlist = new Stream_Playlist();
                $playlist->add(
                    [['object_type' => $type, 'object_id' => $objectId]],
                    '&client=' . $localplay->type
                );

                foreach ($playlist->urls as $streams) {
                    $result = $localplay->add_url($streams);
                }
                break;
            case 'skip':
                if (!array_key_exists('track', $input)) {
                    throw new RequestParamMissingException(
                        sprintf('Bad Request: %s', 'track')
                    );
                }

                // localplay_songs 'track' starts at 1 but localplay starts at 0 behind the scenes
                $result = $localplay->skip((int) $input['track'] - 1);
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
                $response->getBody()->write(
                    $output->error(
                        $apiVersion,
                        ErrorCodeEnum::BAD_REQUEST,
                        'Bad Request',
                        self::ACTION,
                        'command'
                    )
                );

                return $response;
        }

        if ($command === 'status' && empty($status)) {
            return $this->writeNoController($response, $output, $apiVersion);
        }

        $response->getBody()->write(
            $output->localplayResult($apiVersion, $command, (!empty($status)) ? $status : $result)
        );

        return $response;
    }

    private function writeNoController(
        ResponseInterface $response,
        ApiOutputInterface $output,
        int $apiVersion,
        string $message = 'Unable to connect to localplay controller',
    ): ResponseInterface {
        $response->getBody()->write(
            $output->error(
                $apiVersion,
                ErrorCodeEnum::BAD_REQUEST,
                $message,
                self::ACTION,
                'account'
            )
        );

        return $response;
    }
}

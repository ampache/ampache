<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

declare(strict_types=0);

namespace Ampache\Module\Broadcast;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Broadcast;
use Ampache\Module\System\Core;
use Exception;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ampache\Module\System\Session;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Playback\WebPlayer;

class Broadcast_Server implements MessageComponentInterface
{
    public const BROADCAST_SONG               = "SONG";
    public const BROADCAST_SONG_POSITION      = "SONG_POSITION";
    public const BROADCAST_PLAYER_PLAY        = "PLAYER_PLAY";
    public const BROADCAST_REGISTER_BROADCAST = "REGISTER_BROADCAST";
    public const BROADCAST_REGISTER_LISTENER  = "REGISTER_LISTENER";
    public const BROADCAST_ENDED              = "ENDED";
    public const BROADCAST_INFO               = "INFO";
    public const BROADCAST_NB_LISTENERS       = "NB_LISTENERS";
    public const BROADCAST_AUTH_SID           = "AUTH_SID";

    public $verbose;
    /**
     * @var ConnectionInterface[] $clients
     */
    protected $clients;
    /**
     * @var string[] $sids
     */
    protected $sids;
    /**
     * @var ConnectionInterface[] $listeners
     */
    protected $listeners;
    /**
     * @var Broadcast[] $broadcasters
     */
    protected $broadcasters;

    public function __construct()
    {
        $this->verbose      = false;
        $this->clients      = array();
        $this->sids         = array();
        $this->listeners    = array();
        $this->broadcasters = array();
    }

    /**
     *
     * @param ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients[$conn->resourceId] = $conn;
    }

    /**
     *
     * @param ConnectionInterface $from
     * @param string $msg
     */
    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $commands = explode(';', (string)$msg);
        foreach ($commands as $command) {
            $command = trim((string)$command);
            if (!empty($command)) {
                $cmdinfo = explode(':', $command, 2);

                if (count($cmdinfo) == 2) {
                    switch ($cmdinfo[0]) {
                        case self::BROADCAST_SONG:
                            $this->notifySong($from, (int)$cmdinfo[1]);
                            break;
                        case self::BROADCAST_SONG_POSITION:
                            $this->notifySongPosition($from, (int)$cmdinfo[1]);
                            break;
                        case self::BROADCAST_PLAYER_PLAY:
                            $this->notifyPlayerPlay($from, make_bool($cmdinfo[1]));
                            break;
                        case self::BROADCAST_ENDED:
                            $this->notifyEnded($from);
                            break;
                        case self::BROADCAST_REGISTER_BROADCAST:
                            $this->registerBroadcast($from, (string)$cmdinfo[1]);
                            break;
                        case self::BROADCAST_REGISTER_LISTENER:
                            $this->registerListener($from, (int)$cmdinfo[1]);
                            break;
                        case self::BROADCAST_AUTH_SID:
                            $this->authSid($from, $cmdinfo[1]);
                            break;
                        default:
                            self::echo_message(
                                $this->verbose,
                                "[" . time() . "][warning]Unknown message code." . "\r\n"
                            );
                            break;
                    }
                } else {
                    self::echo_message(
                        $this->verbose,
                        "[" . time() . "][error]Wrong message format (" . $command . ")." . "\r\n"
                    );
                }
            }
        }
    }

    /**
     *
     * @param int $song_id
     */
    protected function getSongJS($song_id): string
    {
        $media   = array();
        $media[] = array(
            'object_type' => 'song',
            'object_id' => $song_id
        );
        $item          = Stream_Playlist::media_to_urlarray($media);
        $transcode_cfg = AmpConfig::get('transcode');

        return WebPlayer::get_media_js_param($item[0], $transcode_cfg);
    }

    /**
     *
     * @param ConnectionInterface $from
     * @param int $song_id
     */
    protected function notifySong(ConnectionInterface $from, $song_id): void
    {
        if ($this->isBroadcaster($from)) {
            $broadcast = $this->broadcasters[$from->resourceId];
            $clients   = $this->getListeners($broadcast);

            Session::extend(Stream::get_session(), 'stream');

            $broadcast->update_song($song_id);
            $this->broadcastMessage($clients, self::BROADCAST_SONG, base64_encode($this->getSongJS($song_id)));

            self::echo_message(
                $this->verbose,
                "[" . time() . "][info]Broadcast " . $broadcast->id . " now playing song " . $song_id . "." . "\r\n"
            );
        } else {
            debug_event(self::class, 'Action unauthorized.', 3);
        }
    }

    /**
     *
     * @param ConnectionInterface $from
     * @param int $song_position
     */
    protected function notifySongPosition(ConnectionInterface $from, $song_position): void
    {
        if ($this->isBroadcaster($from)) {
            $broadcast = $this->broadcasters[$from->resourceId];
            $seekdiff  = $broadcast->song_position - $song_position;
            if ($seekdiff > 2 || $seekdiff < -2) {
                $clients = $this->getListeners($broadcast);
                $this->broadcastMessage($clients, self::BROADCAST_SONG_POSITION, (string)$song_position);
            }
            $broadcast->song_position = $song_position;

            self::echo_message(
                $this->verbose,
                "[" . time() . "][info]Broadcast " . $broadcast->id . " has song position to " . $song_position . "." . "\r\n"
            );
        } else {
            debug_event(self::class, 'Action unauthorized.', 3);
        }
    }

    /**
     *
     * @param ConnectionInterface $from
     * @param bool $play
     */
    protected function notifyPlayerPlay(ConnectionInterface $from, $play): void
    {
        if ($this->isBroadcaster($from)) {
            $broadcast = $this->broadcasters[$from->resourceId];
            $clients   = $this->getListeners($broadcast);
            $this->broadcastMessage($clients, self::BROADCAST_PLAYER_PLAY, $play ? 'true' : 'false');

            self::echo_message(
                $this->verbose,
                "[" . time() . "][info]Broadcast " . $broadcast->id . " player state: " . $play . "." . "\r\n"
            );
        } else {
            debug_event(self::class, 'Action unauthorized.', 3);
        }
    }

    /**
     *
     * @param ConnectionInterface $from
     */
    protected function notifyEnded(ConnectionInterface $from): void
    {
        if ($this->isBroadcaster($from)) {
            $broadcast = $this->broadcasters[$from->resourceId];
            $clients   = $this->getListeners($broadcast);
            $this->broadcastMessage($clients, self::BROADCAST_ENDED);

            self::echo_message(
                $this->verbose,
                "[" . time() . "][info]Broadcast " . $broadcast->id . " ended." . "\r\n"
            );
        } else {
            debug_event(self::class, 'Action unauthorized.', 3);
        }
    }

    /**
     *
     * @param ConnectionInterface $from
     * @param string $broadcast_key
     */
    protected function registerBroadcast(ConnectionInterface $from, $broadcast_key): void
    {
        $broadcast = Broadcast::get_broadcast($broadcast_key);
        if ($broadcast) {
            $this->broadcasters[$from->resourceId] = $broadcast;
            $this->listeners[$broadcast->id]       = array();

            self::echo_message($this->verbose, "[info]Broadcast " . $broadcast->id . " registered." . "\r\n");
        }
    }

    /**
     *
     * @param ConnectionInterface $conn
     */
    protected function unregisterBroadcast(ConnectionInterface $conn): void
    {
        $broadcast = $this->broadcasters[$conn->resourceId];
        $clients   = $this->getListeners($broadcast);
        $this->broadcastMessage($clients, self::BROADCAST_ENDED);
        $broadcast->update_state(false);

        unset($this->listeners[$broadcast->id]);
        unset($this->broadcasters[$conn->resourceId]);

        self::echo_message(
            $this->verbose,
            "[" . time() . "][info]Broadcast " . $broadcast->id . " unregistered." . "\r\n"
        );
    }

    /**
     * getRunningBroadcast
     * @param int $broadcast_id
     * @return Broadcast
     */
    protected function getRunningBroadcast($broadcast_id): ?Broadcast
    {
        $result = null;
        foreach ($this->broadcasters as $broadcast) {
            if ($broadcast->id == $broadcast_id) {
                $result = $broadcast;
                break;
            }
        }

        return $result;
    }

    /**
     *
     * @param ConnectionInterface $from
     * @param int $broadcast_id
     */
    protected function registerListener(ConnectionInterface $from, $broadcast_id): void
    {
        $broadcast = $this->getRunningBroadcast($broadcast_id);

        if ($broadcast && (!$broadcast->is_private || !AmpConfig::get('require_session') || Session::exists('stream', $this->sids[$from->resourceId]))) {
            $this->listeners[$broadcast->id][] = $from;

            // Send current song and song position to
            $this->broadcastMessage(
                array($from),
                self::BROADCAST_SONG,
                base64_encode($this->getSongJS($broadcast->song))
            );
            $this->broadcastMessage(array($from), self::BROADCAST_SONG_POSITION, (string)$broadcast->song_position);
            $this->notifyNbListeners($broadcast);

            self::echo_message($this->verbose, "[info]New listener on broadcast " . $broadcast->id . "." . "\r\n");
        } else {
            debug_event(self::class, 'Listener unauthorized.', 3);
        }
    }

    /**
     *
     * @param ConnectionInterface $conn
     * @param string $sid
     */
    protected function authSid(ConnectionInterface $conn, $sid): void
    {
        if (Session::exists('stream', $sid)) {
            $this->sids[$conn->resourceId] = $sid;
        } else {
            self::echo_message($this->verbose, "Wrong listener session " . $sid . "\r\n");
        }
    }

    /**
     *
     * @param ConnectionInterface $conn
     */
    protected function unregisterListener(ConnectionInterface $conn): void
    {
        foreach ($this->listeners as $broadcast_id => $brlisteners) {
            $lindex = array_search($conn, $brlisteners);
            if ($lindex) {
                unset($this->listeners[$broadcast_id][$lindex]);
                echo "[info]Listener left broadcast " . $broadcast_id . "." . "\r\n";

                foreach ($this->broadcasters as $broadcast) {
                    if ($broadcast->id == $broadcast_id) {
                        $this->notifyNbListeners($broadcast);
                        break;
                    }
                }

                break;
            }
        }
    }

    /**
     *
     * @param Broadcast $broadcast
     */
    protected function notifyNbListeners(Broadcast $broadcast): void
    {
        $broadcaster_id = array_search($broadcast, $this->broadcasters);
        if ($broadcaster_id) {
            $clients      = $this->listeners[$broadcast->id];
            $clients[]    = $this->clients[$broadcaster_id];
            $nb_listeners = count($this->listeners[$broadcast->id]);
            $broadcast->update_listeners($nb_listeners);
            $this->broadcastMessage($clients, self::BROADCAST_NB_LISTENERS, (string)$nb_listeners);
        }
    }

    /**
     *
     * @param Broadcast $broadcast
     * @return ConnectionInterface[]
     */
    protected function getListeners(Broadcast $broadcast)
    {
        return $this->listeners[$broadcast->id];
    }

    /**
     * isBroadcaster
     */
    protected function isBroadcaster(ConnectionInterface $conn): bool
    {
        return array_key_exists($conn->resourceId, $this->broadcasters);
    }

    /**
     *
     * @param ConnectionInterface[] $clients
     * @param string $cmd
     * @param string $value
     */
    protected function broadcastMessage($clients, $cmd, $value = ''): void
    {
        $msg = $cmd . ':' . $value . ';';
        foreach ($clients as $client) {
            $sid = $this->sids[$client->resourceId];
            if ($sid) {
                Session::extend($sid, 'stream');
            }
            $client->send($msg);
        }
    }

    /**
     *
     * @param ConnectionInterface $conn
     */
    public function onClose(ConnectionInterface $conn): void
    {
        if ($this->isBroadcaster($conn)) {
            $this->unregisterBroadcast($conn);
        } else {
            $this->unregisterListener($conn);
        }

        unset($this->clients[$conn->resourceId]);
        unset($this->sids[$conn->resourceId]);
    }

    /**
     * onError
     * @param ConnectionInterface $conn
     * @param Exception $error
     * @noinspection PhpParameterNameChangedDuringInheritanceInspection
     */
    public function onError(ConnectionInterface $conn, Exception $error): void
    {
        debug_event(self::class, 'Broadcast error: ' . $error->getMessage(), 1);
        $conn->close();
    }

    /**
     * get_address
     */
    public static function get_address(): string
    {
        $websocket_address = AmpConfig::get('websocket_address');
        if (empty($websocket_address)) {
            $websocket_address = 'ws://' . Core::get_server('SERVER_NAME') . ':8100';
        }

        return $websocket_address . '/broadcast';
    }

    /**
     * echo_message
     * @param bool $verbose
     * @param string $message
     */
    private static function echo_message($verbose, $message): void
    {
        if ($verbose) {
            echo $message;
        }
    }
}

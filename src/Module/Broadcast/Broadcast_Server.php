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

namespace Ampache\Module\Broadcast;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Playback\WebPlayer;
use Ampache\Module\System\Core;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\Broadcast;
use Ampache\Repository\Model\LibraryItemEnum;
use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class Broadcast_Server implements MessageComponentInterface
{
    public const string BROADCAST_AUTH_SID = "AUTH_SID";

    public const string BROADCAST_ENDED = "ENDED";

    public const string BROADCAST_INFO = "INFO";

    public const string BROADCAST_NB_LISTENERS = "NB_LISTENERS";

    public const string BROADCAST_PLAYER_PLAY = "PLAYER_PLAY";

    public const string BROADCAST_REGISTER_BROADCAST = "REGISTER_BROADCAST";

    public const string BROADCAST_REGISTER_LISTENER = "REGISTER_LISTENER";

    public const string BROADCAST_SONG = "SONG";

    public const string BROADCAST_SONG_POSITION = "SONG_POSITION";

    public bool $verbose = false;

    /** @var Broadcast[] $broadcasters */
    protected array $broadcasters = [];

    /** @var ConnectionInterface[] $clients */
    protected array $clients = [];

    /** @var array<int, array<int, ConnectionInterface>> $listeners */
    protected array $listeners = [];

    /** @var string[] $sids */
    protected array $sids = [];

    /**
     * get_address
     */
    public static function get_address(): string
    {
        $websocket_address = AmpConfig::get('websocket_address');
        if (empty($websocket_address)) {
            // a page served over https may only open a wss socket, so the scheme has to follow the site's
            $scheme            = (str_starts_with((string) AmpConfig::get('web_path'), 'https://')) ? 'wss' : 'ws';
            $websocket_address = $scheme . '://' . Core::get_server('SERVER_NAME') . ':8100';
        }

        return $websocket_address . '/broadcast';
    }

    /**
     *
     */
    public function onClose(ConnectionInterface $conn): void
    {
        $role = ($this->isBroadcaster($conn)) ? 'broadcaster' : 'listener';
        debug_event(self::class, 'Connection closed (' . $role . '), resourceId ' . $conn->resourceId, 5);

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
     * @noinspection PhpParameterNameChangedDuringInheritanceInspection
     */
    public function onError(ConnectionInterface $conn, Exception $error): void
    {
        debug_event(self::class, 'Broadcast error: ' . $error->getMessage(), 1);
        $conn->close();
    }

    /**
     *
     * @param string $msg
     */
    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $commands = explode(';', (string) $msg);
        foreach ($commands as $command) {
            $command = trim($command);
            if ($command !== '' && $command !== '0') {
                $cmdinfo = explode(':', $command, 2);

                if (count($cmdinfo) === 2) {
                    match ($cmdinfo[0]) {
                        self::BROADCAST_SONG => $this->notifySong($from, (int) $cmdinfo[1]),
                        self::BROADCAST_SONG_POSITION => $this->notifySongPosition($from, (int) $cmdinfo[1]),
                        self::BROADCAST_PLAYER_PLAY => $this->notifyPlayerPlay($from, make_bool($cmdinfo[1])),
                        self::BROADCAST_ENDED => $this->notifyEnded($from),
                        self::BROADCAST_REGISTER_BROADCAST => $this->registerBroadcast($from, $cmdinfo[1]),
                        self::BROADCAST_REGISTER_LISTENER => $this->registerListener($from, (int) $cmdinfo[1]),
                        self::BROADCAST_AUTH_SID => $this->authSid($from, $cmdinfo[1]),
                        default => $this->echo_message($this->verbose, "[" . time() . "][warning]Unknown message code." . "\r\n"),
                    };
                } else {
                    $this->echo_message($this->verbose, "[" . time() . "][error]Wrong message format (" . $command . ")." . "\r\n");
                }
            }
        }
    }

    /**
     * onOpen
     */
    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients[$conn->resourceId] = $conn;
    }

    /**
     *
     */
    protected function authSid(ConnectionInterface $conn, string $sid): void
    {
        if (Session::exists(AccessTypeEnum::STREAM->value, $sid)) {
            $this->sids[$conn->resourceId] = $sid;
        } else {
            $this->echo_message($this->verbose, "Wrong listener session " . $sid . "\r\n");
        }
    }

    /**
     *
     * @param ConnectionInterface[] $clients
     */
    protected function broadcastMessage(array $clients, string $cmd, string $value = ''): void
    {
        $msg = $cmd . ':' . $value . ';';
        foreach ($clients as $client) {
            $sid = $this->sids[$client->resourceId];
            if ($sid) {
                Session::extend($sid, AccessTypeEnum::STREAM->value);
            }

            $client->send($msg);
        }
    }

    /**
     *
     * @return array<int, ConnectionInterface>
     */
    protected function getListeners(Broadcast $broadcast): array
    {
        return $this->listeners[$broadcast->id];
    }

    /**
     * getRunningBroadcast
     */
    protected function getRunningBroadcast(int $broadcast_id): ?Broadcast
    {
        return array_find($this->broadcasters, fn($broadcast) => $broadcast->id == $broadcast_id);
    }

    /**
     *
     */
    protected function getSongJS(int $song_id): string
    {
        $media   = [];
        $media[] = [
            'object_type' => LibraryItemEnum::SONG,
            'object_id' => $song_id,
        ];
        $item          = Stream_Playlist::media_to_urlarray($media);
        $transcode_cfg = AmpConfig::get('transcode', 'default');

        return WebPlayer::get_media_js_param($item[0], (string) $transcode_cfg);
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
     */
    protected function notifyEnded(ConnectionInterface $from): void
    {
        if ($this->isBroadcaster($from)) {
            $broadcast = $this->broadcasters[$from->resourceId];
            $clients   = $this->getListeners($broadcast);
            $this->broadcastMessage($clients, self::BROADCAST_ENDED);

            $this->echo_message($this->verbose, "[" . time() . "][info]Broadcast " . $broadcast->id . " ended." . "\r\n");
        } else {
            debug_event(self::class, 'Action unauthorized.', 3);
        }
    }

    /**
     *
     */
    protected function notifyNbListeners(Broadcast $broadcast): void
    {
        $broadcaster_id = array_search($broadcast, $this->broadcasters, true);
        if ($broadcaster_id) {
            $clients      = $this->listeners[$broadcast->id];
            $clients[]    = $this->clients[$broadcaster_id];
            $nb_listeners = count($this->listeners[$broadcast->id]);
            $broadcast->update_listeners($nb_listeners);
            $this->broadcastMessage($clients, self::BROADCAST_NB_LISTENERS, (string) $nb_listeners);
        }
    }

    /**
     *
     */
    protected function notifyPlayerPlay(ConnectionInterface $from, bool $play): void
    {
        if ($this->isBroadcaster($from)) {
            $broadcast = $this->broadcasters[$from->resourceId];
            $clients   = $this->getListeners($broadcast);
            $this->broadcastMessage(
                $clients,
                self::BROADCAST_PLAYER_PLAY,
                ($play) ? 'true' : 'false'
            );

            $this->echo_message($this->verbose, "[" . time() . "][info]Broadcast " . $broadcast->id . " player state: " . $play . "." . "\r\n");
        } else {
            debug_event(self::class, 'Action unauthorized.', 3);
        }
    }

    /**
     *
     */
    protected function notifySong(ConnectionInterface $from, int $song_id): void
    {
        if ($this->isBroadcaster($from)) {
            $broadcast = $this->broadcasters[$from->resourceId];
            $clients   = $this->getListeners($broadcast);

            Session::extend(Stream::get_session(), AccessTypeEnum::STREAM->value);

            $broadcast->update_song($song_id);
            $this->broadcastMessage($clients, self::BROADCAST_SONG, base64_encode($this->getSongJS($song_id)));

            $this->echo_message($this->verbose, "[" . time() . "][info]Broadcast " . $broadcast->id . " now playing song " . $song_id . "." . "\r\n");
        } else {
            debug_event(self::class, 'Action unauthorized.', 3);
        }
    }

    /**
     *
     */
    protected function notifySongPosition(ConnectionInterface $from, int $song_position): void
    {
        if ($this->isBroadcaster($from)) {
            $broadcast = $this->broadcasters[$from->resourceId];
            $seekdiff  = $broadcast->song_position - $song_position;
            if ($seekdiff > 2 || $seekdiff < -2) {
                $clients = $this->getListeners($broadcast);
                $this->broadcastMessage($clients, self::BROADCAST_SONG_POSITION, (string) $song_position);
            }

            $broadcast->song_position = $song_position;

            $this->echo_message($this->verbose, "[" . time() . "][info]Broadcast " . $broadcast->id . " has song position to " . $song_position . "." . "\r\n");
        } else {
            debug_event(self::class, 'Action unauthorized.', 3);
        }
    }

    protected function registerBroadcast(ConnectionInterface $from, string $broadcast_key): void
    {
        $broadcast = Broadcast::get_broadcast($broadcast_key);
        if ($broadcast instanceof Broadcast) {
            $this->broadcasters[$from->resourceId] = $broadcast;
            $this->listeners[$broadcast->id]       = [];

            $this->echo_message($this->verbose, "[info]Broadcast " . $broadcast->id . " registered." . "\r\n");
        }
    }

    /**
     *
     */
    protected function registerListener(ConnectionInterface $from, int $broadcast_id): void
    {
        $broadcast = $this->getRunningBroadcast($broadcast_id);

        if ($broadcast && (!$broadcast->is_private || !AmpConfig::get('require_session') || Session::exists(AccessTypeEnum::STREAM->value, $this->sids[$from->resourceId]))) {
            $this->listeners[$broadcast->id][] = $from;

            // Send current song and song position to
            $this->broadcastMessage(
                [$from],
                self::BROADCAST_SONG,
                base64_encode($this->getSongJS($broadcast->song))
            );
            $this->broadcastMessage([$from], self::BROADCAST_SONG_POSITION, (string) $broadcast->song_position);
            $this->notifyNbListeners($broadcast);

            $this->echo_message($this->verbose, "[info]New listener on broadcast " . $broadcast->id . "." . "\r\n");
        } else {
            debug_event(self::class, 'Listener unauthorized.', 3);
        }
    }

    /**
     *
     */
    protected function unregisterBroadcast(ConnectionInterface $conn): void
    {
        $broadcast = $this->broadcasters[$conn->resourceId];
        $clients   = $this->getListeners($broadcast);
        $this->broadcastMessage($clients, self::BROADCAST_ENDED);
        $broadcast->update_state(0);

        unset($this->listeners[$broadcast->id]);
        unset($this->broadcasters[$conn->resourceId]);

        $this->echo_message($this->verbose, "[" . time() . "][info]Broadcast " . $broadcast->id . " unregistered." . "\r\n");
    }

    /**
     *
     */
    protected function unregisterListener(ConnectionInterface $conn): void
    {
        $listeners = $this->listeners;
        foreach ($listeners as $broadcast_id => $brlisteners) {
            $lindex = array_search($conn, $brlisteners, true);
            if (
                $lindex
                && isset($brlisteners[$lindex]) // @phpstan-ignore-line
            ) {
                unset($listeners[$broadcast_id][$lindex]); // @phpstan-ignore-line
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
     * echo_message
     */
    private function echo_message(bool $verbose, string $message): void
    {
        if ($verbose) {
            echo $message;
        }
    }
}

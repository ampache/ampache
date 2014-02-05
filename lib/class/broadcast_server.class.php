<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Broadcast_Server extends MessageComponentInterface
{
    const BROADCAST_SONG = "SONG";
    const BROADCAST_SONG_POSITION = "SONG_POSITION";
    const BROADCAST_PLAYER_PLAY = "PLAYER_PLAY";
    const BROADCAST_REGISTER_BROADCAST = "REGISTER_BROADCAST";
    const BROADCAST_REGISTER_LISTENER = "REGISTER_LISTENER";
    const BROADCAST_ENDED = "ENDED";

    protected $clients;
    protected $listeners;
    protected $broadcasters;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $commands = explode(';', $msg);
        foreach ($commands as $command) {
            $cmdinfo = explode('=', $msg, 2);

            if (count($cmdinfo) == 2) {
                switch ($cmdinfo[0]) {
                    case self::BROADCAST_SONG:
                        $this->notifySong($from, $cmdinfo[1]);
                    break;
                    case self::BROADCAST_SONG_POSITION:
                        $this->notifySongPosition($from, $cmdinfo[1]);
                    break;
                    case self::BROADCAST_PLAYER_PLAY:
                        $this->notifyPlayerPlay($from, $cmdinfo[1]);
                    break;
                    case self::BROADCAST_ENDED:
                        $this->notifyEnded($from);
                    break;
                    case self::BROADCAST_REGISTER_BROADCAST:
                        $this->registerBroadcast($from, $cmdinfo[1]);
                    break;
                    case self::BROADCAST_REGISTER_LISTENER:
                        $this->registerListener($from, $cmdinfo[1]);
                    break;
                }
            }
        }
    }

    protected function notifySong($from, $song_id)
    {
        $broadcast = $this->getConnectionBroadcast($from);
        if ($broadcast) {
            if ($this->isBroadcaster($from, $broadcast)) {
                $clients = $this->getListeners($broadcast);
                $this->broadcastMessage($clients, self::BROADCAST_SONG, $song_id);
            } else {
                debug_event('broadcast', 'Action unauthorized.', '3');
            }
        }
    }

    protected function notifySongPosition($from, $song_position)
    {
        $broadcast = $this->getConnectionBroadcast($from);
        if ($broadcast) {
            if ($this->isBroadcaster($from, $broadcast)) {
                $clients = $this->getListeners($broadcast);
                $this->broadcastMessage($clients, self::BROADCAST_SONG_POSITION, $song_position);
            } else {
                debug_event('broadcast', 'Action unauthorized.', '3');
            }
        }
    }

    protected function notifyPlayerPlay($from, $play)
    {
        $broadcast = $this->getConnectionBroadcast($from);
        if ($broadcast) {
            if ($this->isBroadcaster($from, $broadcast)) {
                $clients = $this->getListeners($broadcast);
                $this->broadcastMessage($clients, self::BROADCAST_PLAYER_PLAY, $play);
            } else {
                debug_event('broadcast', 'Action unauthorized.', '3');
            }
        }
    }

    protected function registerBroadcast($from, $broadcast_key)
    {
    }

    protected function unregisterBroadcast($broadcast)
    {
        $clients = $this->getListeners($broadcast);
        $this->broadcastMessage($clients, self::BROADCAST_ENDED);
    }

    protected function registerListener($from, $broadcast_id)
    {
    }

    protected function unregisterListener($conn)
    {
    }

    protected function getConnectionBroadcast($conn)
    {
    }

    protected function getListeners($broadcast)
    {
    }

    protected function isBroadcaster($conn, $broadcast)
    {
    }

    protected function broadcastMessage($clients, $cmd, $value='')
    {
        $msg = $cmd . '=' . $value . ';';
        foreach ($clients as $client) {
            $client->send($msg);
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $broadcast = $this->getConnectionBroadcast($conn);
        if ($broadcast) {
            if ($this->isBroadcaster($conn, $broadcast)) {
                $this->unregisterBroadcast($broadcast);
            } else {
                $this->unregisterListener($conn);
            }
        }
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->close();
    }

} // end of broadcast_server class

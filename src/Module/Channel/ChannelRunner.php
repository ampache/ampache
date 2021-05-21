<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

namespace Ampache\Module\Channel;

use Ahc\Cli\IO\Interactor;
use Ampache\Repository\Model\ModelFactoryInterface;

final class ChannelRunner implements ChannelRunnerInterface
{
    private HttpServerInterface $httpServer;

    private ChannelFactoryInterface $channelFactory;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        HttpServerInterface $httpServer,
        ChannelFactoryInterface $channelFactory,
        ModelFactoryInterface $modelFactory
    ) {
        $this->httpServer     = $httpServer;
        $this->channelFactory = $channelFactory;
        $this->modelFactory   = $modelFactory;
    }

    public function run(
        Interactor $interactor,
        int $channelId,
        ?int $portNumber
    ): void {
        $metadata_interval = 16000;

        $start_date = time();

        $channel = $this->modelFactory->createChannel($channelId);
        if ($channel->isNew()) {
            $interactor->error(
                sprintf('Unknown channel id `%d`', $channelId),
                true
            );

            return;
        }

        $interactor->info(
            sprintf('Starting Channel `%d`', $channelId),
            true
        );

        if ($portNumber === null) {
            if ($channel->getFixedEndpoint()) {
                $address       = $channel->getInterface();
                $portNumber    = $channel->getPort();
            } else {
                $address = '127.0.0.1';
                // Try to find an available port
                for ($p = 8200; $p < 8300; ++$p) {
                    $connection = fsockopen($address, $p);
                    if (is_resource($connection)) {
                        fclose($connection);
                    } else {
                        $interactor->info(
                            sprintf(
                                T_('Found available port: `%d`'),
                                $p
                            ),
                            true
                        );
                        $portNumber = $p;
                        break;
                    }
                }
            }
        }

        $channelManager = $this->channelFactory->createChannelManager($channel);

        ob_start();

        $server_uri = 'tcp://' . $address . ':' . $portNumber;
        $server     = stream_socket_server($server_uri, $errno, $errorMessage);
        if ($server === false) {
            $interactor->error(
                sprintf('Could not bind to socket: %d', $errorMessage),
                true
            );

            return;
        }
        $channelManager->updateStart($start_date, $address, (int) $portNumber, (int) getmypid());

        $interactor->info(
            sprintf('Listening on `%s:%d`', $address, $portNumber),
            true
        );

        $stream_clients = array();
        $client_socks   = array();
        $last_stream    = microtime(true);
        $chunk_buffer   = '';

        $channelStreamer = $this->channelFactory->createChannelStreamer($channel);

        $chunkSize = $channelStreamer->getChunkSize();

        stream_set_blocking($server , false);
        while (stream_get_meta_data($server)['timed_out'] == null) {
            //prepare readable sockets
            $read_socks = $client_socks;
            if (count($client_socks) < $channel->getMaxListeners()) {
                $read_socks[] = $server;
            }
            //start reading and use a large timeout
            if (stream_select($read_socks, $write, $except, 1)) {
                //new client
                if (in_array($server, $read_socks)) {
                    $new_client = stream_socket_accept($server);

                    if ($new_client) {
                        debug_event('channel_run', 'Connection accepted from ' . stream_socket_get_name($new_client, true) . '.', 5);
                        $client_socks[] = $new_client;
                        $channelManager->updateListeners(count($client_socks), true);
                        debug_event('channel_run', 'Now there are total ' . count($client_socks) . ' clients.', 4);

                        $interactor->info('Client connected', true);

                        ob_flush();
                    }

                    //delete the server socket from the read sockets
                    unset($read_socks[array_search($server, $read_socks)]);
                }

                // Get new message from existing client
                foreach ($read_socks as $sock) {
                    // Handle data parse
                    $this->httpServer->serve(
                        $channelManager,
                        $channelStreamer,
                        $interactor,
                        $channel,
                        $client_socks,
                        $stream_clients,
                        $read_socks,
                        $sock
                    );
                }
            }

            if ($channel->getBitrate()) {
                $time_offset = microtime(true) - $last_stream;

                //debug_event('channel_run', 'time_offset : '. $time_offset, 5);
                //debug_event('channel_run', 'last_stream: '.$last_stream, 5);

                if ($time_offset < 1) {
                    usleep(1000000 - ($time_offset * 1000000));
                } // always at least 1 second between cycles

                $last_stream = microtime(true);
                $mtime       = ($time_offset > 1) ? $time_offset : 1;
                $nb_chunks   = ceil(($mtime * ($channel->getBitrate() + 1 / 100 * $channel->getBitrate()) * 1000 / 8) / $chunkSize); // channel->bitrate+1% ... leave some headroom for metadata / headers

                // we only send full blocks, save remainder and apply when appropriate: allows more granular/arbitrary average bitrates
                if ($nb_chunks - ($mtime * ($channel->getBitrate() + 1 / 100 * $channel->getBitrate()) * 1000 / 8 / $chunkSize) > 0) {
                    $nb_chunks_remainder += $nb_chunks - ($mtime * $channel->getBitrate() * 1000 / 8 / $chunkSize);
                }
                if ($nb_chunks >= 1 && $nb_chunks_remainder >= 1) {
                    $nb_chunks -= 1;
                    $nb_chunks_remainder -= 1;
                    //debug_event('channel_run', 'REMAINDER: '.$nb_chunks_remainder, 5);
                }
                //debug_event('channel_run', 'mtime '.$mtime, 5);
                //debug_event('channel_run', 'nb_chunks: '.$nb_chunks, 5);
            } else {
                $nb_chunks = 1;
            }

            // Get multiple chunks according to bitrate to return enough data per second (because sleep with socket select)
            for ($count = 0; $count < $nb_chunks; $count++) {
                $chunk    = $channelStreamer->retrieveChunk();

                $chunklen = strlen((string) $chunk);
                $chunk_buffer .= $chunk;

                //buffer maintenance
                while (strlen($chunk_buffer) > (15 * $nb_chunks * $chunkSize)) { // buffer 15 seconds
                    if (strtolower($channel->getStreamType()) == "ogg" && $this->strtohex(substr($chunk_buffer, 0, 4)) == "4F676753") { //maintain ogg chunk alignment --- "4F676753" == "OggS"
                        // read OggS segment length
                        $hex                = $this->strtohex(substr($chunk_buffer, 0, 27));
                        $ogg_nr_of_segments = (int) hexdec(substr($hex, 26 * 2, 2));
                        $hex .= $this->strtohex(substr($chunk_buffer, 27, $ogg_nr_of_segments));
                        $ogg_sum_segm_laces = 0;
                        for ($segm = 0; $segm < $ogg_nr_of_segments; $segm++) {
                            $ogg_sum_segm_laces += hexdec(substr($hex, 27 * 2 + $segm * 2, 2));
                        }
                        //$naive = strpos(substr($chunk_buffer, 4), 'OggS') + 4; // naive search for next header
                        //remove 1 whole OggS chunk
                        $chunk_buffer = substr($chunk_buffer, (int) (27 + $ogg_nr_of_segments + $ogg_sum_segm_laces));
                    //debug_event('channel_run', '$new chunk buffer : '.substr($chunk_buffer,0,300) . ' $hex: '.strtohex(substr($chunk_buffer,0,600)) . ' $ogg_nr_of_segments: ' .$ogg_nr_of_segments . ' bytes cut off: '.(27 + $ogg_nr_of_segments + $ogg_sum_segm_laces) . ' naive: ' .$naive, 5);
                    } elseif (strtolower($channel->getStreamType()) == "ogg") {
                        debug_event('channel_run', 'Ogg alignament broken! Trying repair...', 4);
                        $manual_search = strpos($chunk_buffer, 'OggS');
                        $chunk_buffer  = substr($chunk_buffer, $manual_search);
                    } else { // no chunk alignment required
                        $chunk_buffer = substr($chunk_buffer, $chunklen);
                    }
                    //debug_event('channel_run', 'removed chunk from buffer ', 5);
                }

                if ($chunklen > 0) {
                    foreach ($stream_clients as $key => $client) {
                        $sock    = $client['socket'];
                        $clchunk = $chunk;

                        if (!is_resource($sock)) {
                            $this->httpServer->disconnect(
                                $channelManager,
                                $interactor,
                                $channel,
                                $client_socks,
                                $stream_clients,
                                $sock
                            );
                            continue;
                        }

                        if ($client['isnew'] == 1) {
                            $client['isnew'] = 0;
                            //fwrite($sock, $channel->header_chunk);
                            //debug_event('channel_run', 'IS NEW' . $channel->header_chunk, 5);
                            $clchunk_buffer = $channelStreamer->getHeaderChunk() . $chunk_buffer;
                            if ($client['metadata']) { //stub
                                //if (strtolower($channel->stream_type) == "ogg")
                                while (strlen($clchunk_buffer) > $metadata_interval) {
                                    fwrite($sock, substr($clchunk_buffer, 0, $metadata_interval) . chr(0x00));
                                    $clchunk_buffer = substr($clchunk_buffer, $metadata_interval);
                                }
                                fwrite($sock, $clchunk_buffer);
                                $client['metadata_lastsent'] = 0;
                                $client['length'] += strlen($clchunk_buffer);
                            } else {
                                //fwrite($sock, $channel->header_chunk);
                                $buffer_bytes_written = fwrite($sock, $clchunk_buffer);
                                while ($buffer_bytes_written != strlen($clchunk_buffer)) {
                                    debug_event('channel_run', 'I HERPED WHEN I SHOULD HAVE DERPED!', 5);
                                    //debug_event('channel_run', 'chunk_buffer bytes written:' .$buffer_bytes_written .'strlen $chunk_buffer: '.strlen($chunk_buffer), 5);
                                    $clchunk_buffer       = substr($clchunk_buffer, $buffer_bytes_written);
                                    $buffer_bytes_written = fwrite($sock, $clchunk_buffer);
                                }
                            }
                            $stream_clients[$key] = $client;
                            continue;
                        }

                        // Check if we need to insert metadata information
                        if ($client['metadata']) {
                            $chkmdlen = ($client['length'] + $chunklen) - $client['metadata_lastsent'];
                            if ($chkmdlen >= $metadata_interval) {
                                $subpos = ($client['metadata_lastsent'] + $metadata_interval) - $client['length'];
                                fwrite($sock, substr($clchunk, 0, $subpos));
                                $client['length'] += $subpos;

                                $media = $channelStreamer->getMedia();

                                if ($media !== null && $media->getId() != $client['metadata_lastsong']) {
                                    $metadata = "StreamTitle='" . str_replace('-', ' ', $media->f_artist) . "-" . $media->f_title . "';";
                                    $metadata .= chr(0x00);
                                    $metadatalen = ceil(strlen($metadata) / 16);
                                    $metadata    = str_pad($metadata, $metadatalen * 16, chr(0x00), STR_PAD_RIGHT);
                                    //debug_event('channel_run', 'Sending metadata to client...', 5);
                                    fwrite($sock, chr($metadatalen) . $metadata);
                                    $client['metadata_lastsong'] = $media->getId();
                                } else {
                                    fwrite($sock, chr(0x00));
                                }
                                $client['metadata_lastsent'] = $client['length'];
                                $clchunk                     = substr($chunk, $subpos);
                            }
                        }

                        if (strlen($clchunk) > 0) {
                            fwrite($sock, $clchunk);
                            $client['length'] += strlen($clchunk);
                        }
                        $stream_clients[$key] = $client;
                        //debug_event('channel_run', 'Client stream current length: ' . $client['length'], 5);
                    }
                } else {
                    $channelManager->updateListeners(0);
                    debug_event('channel_run', 'No more data, stream ended.', 4);
                    die('No more data, stream ended');
                }
            }
        }
    }

    /**
     * @param string $string
     * @return string
     */
    private function strtohex(string $string): string
    {
        $hex = '';
        foreach (str_split($string) as $char) {
            $hex .= sprintf("%02X", ord($char));
        }

        return $hex;
    }
}

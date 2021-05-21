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
 */

declare(strict_types=0);

namespace Ampache\Module\Channel;

use Ampache\Config\AmpConfig;
use Ampache\Module\Catalog\Loader\CatalogLoaderInterface;
use Ampache\Module\Playback\Stream;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\ChannelInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Song;
use Psr\Log\LoggerInterface;

final class ChannelStreamer implements ChannelStreamerInterface
{
    private LoggerInterface $logger;

    private CatalogLoaderInterface $catalogLoader;

    private ChannelInterface $channel;

    private int $chunk_size = 4096;

    private bool $is_init = false;

    private int $song_pos = 0;

    /** @var array<Song> */
    private array $songs = [];

    private ?Playlist $playlist = null;

    private ?Song $media = null;

    /**
     * @var null|array{
     *  handle: ?resource,
     *  stderr: ?resource,
     *  process: ?resource
     * }
     */
    private ?array $transcoder = null;

    private int $media_bytes_streamed = 0;

    private ?string $header_chunk = null;

    private ?int $header_chunk_remainder = null;

    public function __construct(
        LoggerInterface $logger,
        CatalogLoaderInterface $catalogLoader,
        ChannelInterface $channel
    ) {
        $this->logger        = $logger;
        $this->catalogLoader = $catalogLoader;
        $this->channel       = $channel;
    }

    protected function init_channel_songs(): void
    {
        $this->playlist = $this->channel->get_target_object();
        if ($this->playlist) {
            if (!$this->channel->getRandom()) {
                $this->songs = $this->playlist->get_songs();
            }
        }
        $this->is_init = true;
    }

    public function retrieveChunk(): ?string
    {
        $chunk = null;

        if (!$this->is_init) {
            $this->init_channel_songs();
        }

        if ($this->is_init) {
            // Move to next song
            while ($this->media == null && ($this->channel->getRandom() || $this->song_pos < count($this->songs))) {
                if ($this->channel->getRandom()) {
                    $randsongs   = $this->playlist->get_random_items(1);
                    $this->media = new Song($randsongs[0]['object_id']);
                } else {
                    $this->media = new Song($this->songs[$this->song_pos]);
                }
                $this->media->format();

                $mediaCatalogId = $this->media->getCatalogId();
                if ($mediaCatalogId) {
                    $catalog = $this->catalogLoader->byId($mediaCatalogId);
                    if ($this->media->isEnabled()) {
                        if (AmpConfig::get('lock_songs')) {
                            if (!Stream::check_lock_media($this->media->id, 'song')) {
                                $this->logger->warning(
                                    'Media ' . $this->media->id . ' locked, skipped.',
                                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                                );
                                $this->media = null;
                            }
                        }
                    }

                    if ($this->media != null) {
                        $this->media = $catalog->prepare_media($this->media);

                        if (!$this->media->file || !Core::is_readable(Core::conv_lc_file($this->media->file))) {
                            $this->logger->warning(
                                'Cannot read media ' . $this->media->id . ' file, skipped.',
                                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                            );
                            $this->media = null;
                        } else {
                            $valid_types = $this->media->get_stream_types();
                            if (!in_array('transcode', $valid_types)) {
                                $this->logger->warning(
                                    'Missing settings to transcode ' . $this->media->file . ', skipped.',
                                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                                );
                                $this->media = null;
                            } else {
                                $this->logger->info(
                                    'Now listening to ' . $this->media->file . '.',
                                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                                );
                            }
                        }
                    }
                } else {
                    $this->logger->warning(
                        'Media ' . $this->media->id . ' doesn\'t have catalog, skipped.',
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                    $this->media = null;
                }

                $this->song_pos++;
                // Restart from beginning for next song if the channel is 'loop' enabled
                // and load fresh data from database
                if ($this->media != null && $this->song_pos == count($this->songs) && $this->channel->getLoop()) {
                    $this->init_channel_songs();
                }
            }

            if ($this->media != null) {
                // Stream not yet initialized for this media, start it
                if (!$this->transcoder) {
                    $options = array(
                        'bitrate' => $this->channel->getBitrate()
                    );
                    $this->transcoder           = Stream::start_transcode($this->media, $this->channel->getStreamType(), null, $options);
                    $this->media_bytes_streamed = 0;
                }

                if (is_resource($this->transcoder['handle'])) {
                    if (ftell($this->transcoder['handle']) == 0) {
                        $this->header_chunk = '';
                    }
                    $chunk = (string) fread($this->transcoder['handle'], $this->chunk_size);
                    $this->media_bytes_streamed += strlen((string)$chunk);

                    if ((ftell($this->transcoder['handle']) < 10000 && strtolower((string) $this->channel->getStreamType()) == "ogg") || $this->header_chunk_remainder) {
                        // debug_event(self::class, 'File handle pointer: ' . ftell($this->transcoder['handle']), 5);
                        $clchunk = $chunk;

                        if ($this->header_chunk_remainder) {
                            $this->header_chunk .= substr($clchunk, 0, $this->header_chunk_remainder);
                            if (strlen((string)$clchunk) >= $this->header_chunk_remainder) {
                                $clchunk                      = substr($clchunk, $this->header_chunk_remainder);
                                $this->header_chunk_remainder = 0;
                            } else {
                                $this->header_chunk_remainder = $this->header_chunk_remainder - strlen((string)$clchunk);
                                $clchunk                      = '';
                            }
                        }
                        // see ChannelRunner for explanation what's happening here
                        while ($this->strtohex(substr($clchunk, 0, 4)) == "4F676753") {
                            $hex                = $this->strtohex(substr($clchunk, 0, 27));
                            $ogg_nr_of_segments = hexdec(substr($hex, 26 * 2, 2));
                            if ((substr($clchunk, (int)(27 + $ogg_nr_of_segments + 1),
                                        6) == "vorbis") || (substr($clchunk, (int)(27 + $ogg_nr_of_segments),
                                        4) == "Opus")) {
                                $hex .= $this->strtohex(substr($clchunk, 27, (int)$ogg_nr_of_segments));
                                $ogg_sum_segm_laces = 0;
                                for ($segm = 0; $segm < $ogg_nr_of_segments; $segm++) {
                                    $ogg_sum_segm_laces += hexdec(substr($hex, 27 * 2 + $segm * 2, 2));
                                }
                                $this->header_chunk .= substr($clchunk, 0,
                                    (int)(27 + $ogg_nr_of_segments + $ogg_sum_segm_laces));
                                if (strlen((string)$clchunk) < (27 + $ogg_nr_of_segments + $ogg_sum_segm_laces)) {
                                    $this->header_chunk_remainder = (int)(27 + $ogg_nr_of_segments + $ogg_sum_segm_laces - strlen((string)$clchunk));
                                }
                                $clchunk = substr($clchunk, (int)(27 + $ogg_nr_of_segments + $ogg_sum_segm_laces));
                            } else {
                                // no more interesting headers
                                $clchunk = '';
                            }
                        }
                    }

                    // End of file, prepare to move on for next call
                    if (feof($this->transcoder['handle'])) {
                        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                            fread($this->transcoder['stderr'], 4096);
                            fclose($this->transcoder['stderr']);
                        }
                        fclose($this->transcoder['handle']);
                        Stream::kill_process($this->transcoder);

                        $this->media      = null;
                        $this->transcoder = null;
                    }
                } else {
                    $this->media      = null;
                    $this->transcoder = null;
                }

                if (!strlen((string)$chunk)) {
                    $chunk = $this->retrieveChunk();
                }
            }
        }

        return $chunk;
    }

    private function strtohex(string $source): string
    {
        $string = '';
        foreach (str_split($source) as $char) {
            $string .= sprintf("%02X", ord($char));
        }

        return ($string);
    }

    public function getHeaderChunk(): ?string
    {
        return $this->header_chunk;
    }

    public function getMedia(): ?Song
    {
        return $this->media;
    }

    public function getChunkSize(): int
    {
        return $this->chunk_size;
    }
}

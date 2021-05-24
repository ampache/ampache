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
use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\Model\Channel;
use Ampache\Repository\Model\Song;
use RuntimeException;

final class HttpServer implements HttpServerInterface
{
    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        $this->configContainer = $configContainer;
    }

    public function serve(
        Interactor $interactor,
        Channel $channel,
        array &$client_socks,
        array &$stream_clients,
        array &$read_socks,
        $sock
    ): void {
        $data = fread($sock, 1024);
        if (!$data) {
            $this->disconnect($interactor, $channel, $client_socks, $stream_clients, $sock);

            return;
        }

        $headers = explode("\n", $data);
        $h_count = count($headers);

        if ($h_count > 0) {
            $cmd = explode(" ", $headers[0]);
            if ($cmd['0'] == 'GET') {
                switch ($cmd['1']) {
                    case '/stream.' . $channel->stream_type:
                        $options = array(
                            'socket' => $sock,
                            'length' => 0,
                            'isnew' => 1
                        );

                        //debug_event('channel_run', 'HTTP HEADERS: '.$data, 5);
                        for ($count = 1; $count < $h_count; $count++) {
                            $headerpart = explode(":", $headers[$count], 2);
                            $header     = strtolower(trim($headerpart[0]));
                            $value      = trim($headerpart[1]);
                            switch ($header) {
                                case 'icy-metadata':
                                    $options['metadata']          = ($value == '1');
                                    $options['metadata_lastsent'] = 0;
                                    $options['metadata_lastsong'] = 0;
                                    break;
                            }
                        }

                        // Stream request
                        if ($options['metadata']) {
                            //$http = "ICY 200 OK\r\n");
                            $http = "HTTP/1.0 200 OK\r\n";
                        } else {
                            $http = "HTTP/1.1 200 OK\r\n";
                            $http .= "Cache-Control: no-store, no-cache, must-revalidate\r\n";
                        }
                        $http .= "Content-Type: " . Song::type_to_mime($channel->stream_type) . "\r\n";
                        $http .= "Accept-Ranges: none\r\n";

                        $genre = $channel->get_genre();
                        // Send Shoutcast metadata on demand
                        //if ($options['metadata']) {
                        $http .= "icy-notice1: " . $this->configContainer->get('site_title') . "\r\n";
                        $http .= "icy-name: " . $channel->name . "\r\n";
                        if (!empty($genre)) {
                            $http .= "icy-genre: " . $genre . "\r\n";
                        }
                        $http .= "icy-url: " . $channel->url . "\r\n";
                        $http .= "icy-pub: " . (($channel->is_private) ? "0" : "1") . "\r\n";
                        if ($channel->bitrate) {
                            $http .= "icy-br: " . strval($channel->bitrate) . "\r\n";
                        }
                        global $metadata_interval;
                        $http .= "icy-metaint: " . strval($metadata_interval) . "\r\n";
                        //}
                        // Send additional Icecast metadata
                        $http .= "x-audiocast-server-url: " . $channel->url . "\r\n";
                        $http .= "x-audiocast-name: " . $channel->name . "\r\n";
                        $http .= "x-audiocast-description: " . $channel->description . "\r\n";
                        $http .= "x-audiocast-url: " . $channel->url . "\r\n";
                        if (!empty($genre)) {
                            $http .= "x-audiocast-genre: " . $genre . "\r\n";
                        }
                        $http .= "x-audiocast-bitrate: " . strval($channel->bitrate) . "\r\n";
                        $http .= "x-audiocast-public: " . (($channel->is_private) ? "0" : "1") . "\r\n";

                        $http .= "\r\n";

                        fwrite($sock, $http);

                        // Add to stream clients list
                        $key                  = array_search($sock, $read_socks);
                        $stream_clients[$key] = $options;
                        break;
                    case '/':
                    case '/status.xsl':
                        // Stream request
                        fwrite($sock, "HTTP/1.0 200 OK\r\n");
                        fwrite($sock, "Cache-Control: no-store, no-cache, must-revalidate\r\n");
                        fwrite($sock, "Content-Type: text/html\r\n");
                        fwrite($sock, "\r\n");

                        // Create xsl structure

                        // Header
                        $xsl = "";
                        $xsl .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" . "\n";
                        $xsl .= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">" . "\n";
                        $xsl .= "<html xmlns=\"http://www.w3.org/1999/xhtml\">" . "\n";
                        $xsl .= "<head>" . "\n";
                        $xsl .= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />" . "\n";
                        $xsl .= "<title>" . T_("Icecast Streaming Media Server") . " - " . T_('Ampache') . "</title>" . "\n";
                        $xsl .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"style.css\" />" . "\n";
                        $xsl .= "<link rel=\"shortcut icon\" href=\"favicon.ico\" />";
                        $xsl .= "</head>" . "\n";
                        $xsl .= "<body>" . "\n";
                        $xsl .= "<div class=\"main\">" . "\n";

                        // Content
                        $xsl .= "<div class=\"roundcont\">" . "\n";
                        $xsl .= "<div class=\"roundtop\">" . "\n";
                        $xsl .= "<img src=\"images/corner_topleft.jpg\" class=\"corner\" style=\"display: none\" alt=\"\" />" . "\n";
                        $xsl .= "</div>" . "\n";
                        $xsl .= "<div class=\"newscontent\">" . "\n";
                        $xsl .= "<div class=\"streamheader\">" . "\n";
                        $xsl .= "<table>" . "\n";
                        $xsl .= "<colgroup align=\"left\"></colgroup>" . "\n";
                        $xsl .= "<colgroup align=\"right\" width=\"300\"></colgroup>" . "\n";
                        $xsl .= "<tr>" . "\n";
                        $xsl .= "<td><h3>Mount Point: <a href=\"stream." . $channel->stream_type . "\">stream." . $channel->stream_type . "</a></h3></td>" . "\n";
                        $xsl .= "<td align=\"right\">" . "\n";
                        $xsl .= "<a href=\"stream." . $channel->stream_type . ".m3u\">M3U</a>" . "\n";
                        $xsl .= "</td>" . "\n";
                        $xsl .= "</tr>" . "\n";
                        $xsl .= "</table>" . "\n";
                        $xsl .= "</div>" . "\n";
                        $xsl .= "<table>" . "\n";
                        $xsl .= "<tr>" . "\n";
                        $xsl .= "<td>Stream Title:</td>" . "\n";
                        $xsl .= "<td class=\"streamdata\">" . $channel->name . "</td>" . "\n";
                        $xsl .= "</tr>" . "\n";
                        $xsl .= "<tr>" . "\n";
                        $xsl .= "<td>Stream Description:</td>" . "\n";
                        $xsl .= "<td class=\"streamdata\">" . $channel->description . "</td>" . "\n";
                        $xsl .= "</tr>" . "\n";
                        $xsl .= "<tr>" . "\n";
                        $xsl .= "<td>Content Type:</td>" . "\n";
                        $xsl .= "<td class=\"streamdata\">" . Song::type_to_mime($channel->stream_type) . "</td>" . "\n";
                        $xsl .= "</tr>" . "\n";
                        $xsl .= "<tr>" . "\n";
                        $xsl .= "<td>Mount Start:</td>" . "\n";
                        $xsl .= "<td class=\"streamdata\">" . get_datetime($channel->start_date) . "</td>" . "\n";
                        $xsl .= "</tr>" . "\n";
                        $xsl .= "<tr>" . "\n";
                        $xsl .= "<td>Bitrate:</td>" . "\n";
                        $xsl .= "<td class=\"streamdata\">" . $channel->bitrate . "</td>" . "\n";
                        $xsl .= "</tr>" . "\n";
                        $xsl .= "<tr>" . "\n";
                        $xsl .= "<td>Current Listeners:</td>" . "\n";
                        $xsl .= "<td class=\"streamdata\">" . $channel->listeners . "</td>" . "\n";
                        $xsl .= "</tr>" . "\n";
                        $xsl .= "<tr>" . "\n";
                        $xsl .= "<td>Peak Listeners:</td>" . "\n";
                        $xsl .= "<td class=\"streamdata\">" . $channel->peak_listeners . "</td>" . "\n";
                        $xsl .= "</tr>" . "\n";
                        $genre = $channel->get_genre();
                        $xsl .= "<tr>" . "\n";
                        $xsl .= "<td>Stream Genre:</td>" . "\n";
                        $xsl .= "<td class=\"streamdata\">" . $genre . "</td>" . "\n";
                        $xsl .= "</tr>" . "\n";
                        $xsl .= "<tr>" . "\n";
                        $xsl .= "<td>Stream URL:</td>" . "\n";
                        $xsl .= "<td class=\"streamdata\"><a href=\"" . $channel->url . "\" target=\"_blank\">" . $channel->url . "</a></td>" . "\n";
                        $xsl .= "</tr>" . "\n";
                        $currentsong = "";
                        if ($channel->media) {
                            $currentsong = $channel->media->f_artist . " - " . $channel->media->f_title;
                        }
                        $xsl .= "<tr>" . "\n";
                        $xsl .= "<td>Current Song:</td>" . "\n";
                        $xsl .= "<td class=\"streamdata\">" . $currentsong . "</td>" . "\n";
                        $xsl .= "</tr>" . "\n";
                        $xsl .= "</table>" . "\n";
                        $xsl .= "</div>" . "\n";
                        $xsl .= "<div class=\"roundbottom\">" . "\n";
                        $xsl .= "<img src=\"images/corner_bottomleft.jpg\" class=\"corner\" style=\"display: none\" alt=\"\" />" . "\n";
                        $xsl .= "</div>" . "\n";
                        $xsl .= "</div>" . "\n";
                        $xsl .= "<br /><br />" . "\n";

                        // Footer
                        $xsl .= "<div class=\"poster\">" . "\n";
                        $xsl .= "Support Ampache at <a target=\"_blank\" href=\"http://www.ampache.org\">www.ampache.org</a>" . "\n";
                        $xsl .= "</div>" . "\n";
                        $xsl .= "</div>" . "\n";
                        $xsl .= "</body>" . "\n";
                        $xsl .= "</html>" . "\n";

                        fwrite($sock, $xsl);

                        fclose($sock);
                        unset($client_socks[array_search($sock, $client_socks)]);
                        break;
                    case '/style.css':
                    case '/favicon.ico':
                    case '/images/corner_bottomleft.jpg':
                    case '/images/corner_bottomright.jpg':
                    case '/images/corner_topleft.jpg':
                    case '/images/corner_topright.jpg':
                    case '/images/icecast.png':
                    case '/images/key.png':
                    case '/images/tunein.png':
                        // Get read file data
                        $fpath = __DIR__ . '/../../../public/channel' . $cmd['1'];
                        $pinfo = pathinfo($fpath);

                        $content_type = 'text/html';
                        switch ($pinfo['extension']) {
                            case 'css':
                                $content_type = "text/css";
                                break;
                            case 'jpg':
                                $content_type = "image/jpeg";
                                break;
                            case 'png':
                                $content_type = "image/png";
                                break;
                            case 'ico':
                                $content_type = "image/vnd.microsoft.icon";
                                break;
                        }
                        fwrite($sock, "HTTP/1.0 200 OK\r\n");
                        fwrite($sock, "Content-Type: " . $content_type . "\r\n");
                        $fdata = file_get_contents($fpath);
                        fwrite($sock, "Content-Length: " . strlen($fdata) . "\r\n");
                        fwrite($sock, "\r\n");
                        fwrite($sock, $fdata);
                        fclose($sock);
                        unset($client_socks[array_search($sock, $client_socks)]);
                        break;
                    case '/stream.' . $channel->stream_type . '.m3u':
                        fwrite($sock, "HTTP/1.0 200 OK\r\n");
                        fwrite($sock, "Cache-control: public\r\n");
                        fwrite($sock, "Content-Disposition: filename=stream." . $channel->stream_type . ".m3u\r\n");
                        fwrite($sock, "Content-Type: audio/x-mpegurl\r\n");
                        fwrite($sock, "\r\n");

                        fwrite($sock, $channel->get_stream_url() . "\n");

                        fclose($sock);
                        unset($client_socks[array_search($sock, $client_socks)]);
                        break;
                    default:
                        debug_event('channel_run', 'Unknown request. Closing connection.', 3);
                        fclose($sock);
                        unset($client_socks[array_search($sock, $client_socks)]);
                        break;
                }
            }
        }
    }

    public function disconnect(
        Interactor $interactor,
        Channel $channel,
        array &$client_socks,
        array &$stream_clients,
        $sock
    ): void {
        $key = array_search($sock, $client_socks);
        unset($client_socks[$key]);
        unset($stream_clients[$key]);
        if (fclose($sock) === false) {
            throw new RuntimeException('The file handle ' . $sock . ' could not be closed');
        }
        $channel->update_listeners(count($client_socks));
        debug_event('channel_run', 'A client disconnected. Now there are total ' . count($client_socks) . ' clients.', 4);

        $interactor->info('Client disconnected', true);

        ob_flush();
    }
}

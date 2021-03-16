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

namespace Ampache\Module\Api\Gui\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Plugin\Adapter\UserMediaPlaySaverAdapterInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final class ScrobbleMethod implements MethodInterface
{
    public const ACTION = 'scrobble';

    private StreamFactoryInterface $streamFactory;

    private UserRepositoryInterface $userRepository;

    private UserMediaPlaySaverAdapterInterface $userMediaPlaySaverAdapter;

    private ModelFactoryInterface $modelFactory;

    private LoggerInterface $logger;

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private SongRepositoryInterface $songRepository;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        UserRepositoryInterface $userRepository,
        UserMediaPlaySaverAdapterInterface $userMediaPlaySaverAdapter,
        ModelFactoryInterface $modelFactory,
        LoggerInterface $logger,
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        SongRepositoryInterface $songRepository
    ) {
        $this->streamFactory             = $streamFactory;
        $this->userRepository            = $userRepository;
        $this->userMediaPlaySaverAdapter = $userMediaPlaySaverAdapter;
        $this->modelFactory              = $modelFactory;
        $this->logger                    = $logger;
        $this->configContainer           = $configContainer;
        $this->ui                        = $ui;
        $this->songRepository            = $songRepository;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * Search for a song using text info and then record a play if found.
     * This allows other sources to record play history to Ampache
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * song       = (string)  $song_name
     * artist     = (string)  $artist_name
     * album      = (string)  $album_name
     * songmbid   = (string)  $song_mbid //optional
     * artistmbid = (string)  $artist_mbid //optional
     * albummbid  = (string)  $album_mbid //optional
     * date       = (integer) UNIXTIME() //optional
     * client     = (string)  $agent //optional
     *
     * @return ResponseInterface
     * @throws RequestParamMissingException
     * @throws ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        foreach (['song', 'artist', 'album'] as $key) {
            if (!array_key_exists($key, $input)) {
                throw new RequestParamMissingException(
                    sprintf(T_('Bad Request: %s'), $key)
                );
            }
        }

        $user   = $gatekeeper->getUser();
        $userId = $user->getId();

        // validate supplied user
        if (in_array($userId, $this->userRepository->getValid()) === false) {
            throw new RequestParamMissingException(
                sprintf(T_('Not Found: %s'), $userId)
            );
        }

        $charset    = $this->configContainer->get(ConfigurationKeyEnum::SITE_CHARSET);
        $songName   = (string) html_entity_decode($this->ui->scrubOut($input['song']), ENT_QUOTES, $charset);
        $artistName = (string) html_entity_decode($this->ui->scrubIn((string) $input['artist']), ENT_QUOTES, $charset);
        $albumName  = (string) html_entity_decode($this->ui->scrubIn((string) $input['album']), ENT_QUOTES, $charset);
        $songMbid   = (string) $this->ui->scrubIn($input['song_mbid'] ?? ''); //optional
        $artistMbid = (string) $this->ui->scrubIn($input['artist_mbid'] ?? ''); //optional
        $albumMbid  = (string) $this->ui->scrubIn($input['album_mbid'] ?? ''); //optional
        $date       = (int) ($input['date'] ?? time()); //optional

        // validate minimum required options
        if (!$songName || !$albumName || !$artistName) {
            throw new RequestParamMissingException(
                T_('Bad Request')
            );
        }

        // validate client string or fall back to 'api'
        $agent = $input['client'] ?? 'api';

        $scrobbleId = $this->songRepository->canScrobble(
            $songName,
            $artistName,
            $albumName,
            (string) $songMbid,
            (string) $artistMbid,
            (string) $albumMbid
        );

        $media = $this->modelFactory->createSong((int) $scrobbleId);
        if ($media->isNew()) {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %s'), $scrobbleId)
            );
        }

        $this->logger->debug(
            sprintf(
                'scrobble: %d for %s using %s %d',
                (int) $scrobbleId,
                $user->username,
                $agent,
                $date
            ),
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        // internal scrobbling (user_activity and object_count tables)
        if ($media->set_played($userId, $agent, [], $date)) {
            // scrobble plugins
            $this->userMediaPlaySaverAdapter->save($user, $media);
        }

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->success(
                    sprintf('successfully scrobbled: %s', $scrobbleId)
                )
            )
        );
    }
}

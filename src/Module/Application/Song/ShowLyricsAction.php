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

namespace Ampache\Module\Application\Song;

use Ampache\Config\AmpConfig;
use Ampache\Gui\Song\LyricsView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\LyricsSourceEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A song's lyrics; an editor can also save, clear or pick from what each lyric plugin found.
 */
final readonly class ShowLyricsAction implements ApplicationActionInterface
{
    public const string FORM_NAME   = 'song_lyrics';
    public const string REQUEST_KEY = 'show_lyrics';

    public function __construct(
        private RequestParserInterface $requestParser,
        private UiInterface $ui,
        private ModelFactoryInterface $modelFactory,
    ) {}

    public function run(
        ServerRequestInterface $request,
        GuiGatekeeperInterface $gatekeeper,
    ): ?ResponseInterface {
        $song_id = (int) $this->requestParser->getFromRequest('song_id');
        $song    = $this->modelFactory->createSong($song_id);
        $user    = $gatekeeper->getUser();
        if ($song->isNew() || !$user instanceof User || !$song->isVisible($user)) {
            throw new ObjectNotFoundException($song_id);
        }

        $catalogs = $user->catalogs['music'] ?? User::get_user_catalogs($user->getId());
        if (!in_array($song->catalog, $catalogs)) {
            throw new ObjectNotFoundException($song_id);
        }

        $mayEdit = $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            || $user->getId() === $song->get_user_owner();

        $body = $request->getParsedBody();
        if (is_array($body) && isset($body['lyrics_action'])) {
            if (!$mayEdit || !$this->requestParser->verifyForm(self::FORM_NAME)) {
                throw new AccessDeniedException();
            }

            $text = ($body['lyrics_action'] === 'save')
                ? str_replace("\r\n", "\n", (string) ($body['lyrics'] ?? ''))
                : '';
            Song::update_lyrics($text, $song->getId());
            $song->lyrics        = (trim($text) === '') ? null : $text;
            $song->lyrics_source = ($song->lyrics === null) ? null : LyricsSourceEnum::USER;
        }

        $stored = $song->get_lyrics(true);

        // an editor chooses what is stored, so they see every plugin's answer and nothing is saved behind their back
        $candidates = [];
        $refreshed  = $mayEdit && $this->requestParser->getFromRequest('refresh') === '1';
        if ($mayEdit && ($stored === [] || $refreshed)) {
            $candidates = $song->get_lyrics_candidates($user);
        }

        $lyrics = $stored;
        if ($lyrics === []) {
            $lyrics = ($mayEdit) ? (current($candidates) ?: []) : $song->get_lyrics();
        }

        $this->ui->showHeader();

        echo new LyricsView(
            AmpConfig::get_web_path('/client'),
            $song,
            $lyrics,
            $stored !== [],
            $mayEdit,
            $candidates,
            $refreshed
        )->render();

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}

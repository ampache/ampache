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

namespace Ampache\Module\Application\Mashup;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Stats\WrappedView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class WrappedAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'wrapped';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private RequestParserInterface $requestParser,
        private UiInterface $ui,
        private UserRepositoryInterface $userRepository,
        private BrowseFactoryInterface $browseFactory,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHOW_WRAPPED)) {
            throw new AccessDeniedException('Access Denied');
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $userId = (int) $this->requestParser->getFromRequest('user_id');

        $user = $this->userRepository->findById($userId);
        if ($user === null) {
            throw new ObjectNotFoundException('user_id');
        }

        $year = $this->requestParser->getFromRequest('year');
        if ($year === '') {
            $year = 'Y';
        }

        $startTime = strtotime(date($year . '-01-01'));
        if ($startTime === false) {
            throw new ObjectNotFoundException('year');
        }

        $endTime = strtotime(date($year . '-12-31')) ?: time();

        $this->ui->showHeader();
        $threshold = (int) AmpConfig::get('stats_threshold', 7);
        $limit     = (int) AmpConfig::get('popular_threshold', 10);

        // the Ratings box has always rendered as a plain list rather than a mashup, and stored its browse
        $sections = [
            ['title' => T_('Artists'), 'type' => 'artist', 'grid' => true, 'mashup' => true, 'store' => false,
                'objectIds' => Stats::get_top('artist', $limit, $threshold, 0, $user, false, $startTime, $endTime)],
            ['title' => T_('Albums'), 'type' => 'album', 'grid' => true, 'mashup' => true, 'store' => false,
                'objectIds' => Stats::get_top('album', $limit, $threshold, 0, $user, false, $startTime, $endTime)],
            ['title' => T_('Songs'), 'type' => 'song', 'grid' => false, 'mashup' => true, 'store' => false,
                'objectIds' => Stats::get_top('song', $limit, $threshold, 0, $user, false, $startTime, $endTime)],
            ['title' => T_('Favorites'), 'type' => 'song', 'grid' => false, 'mashup' => true, 'store' => false,
                'objectIds' => Userflag::get_latest('song', $user, -1, 0, $startTime, $endTime, true)],
            ['title' => T_('Ratings'), 'type' => 'song', 'grid' => false, 'mashup' => false, 'store' => true,
                'objectIds' => Rating::get_latest('song', $user, -1, 0, $startTime, $endTime)],
        ];

        echo (new WrappedView(
            $this->browseFactory,
            date($year),
            (int) Stats::get_object_data('song_count', $startTime, $endTime, $user),
            (string) Stats::get_object_data('song_minutes', $startTime, $endTime, $user),
            $sections
        ))->render();
        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}

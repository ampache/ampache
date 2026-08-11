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

namespace Ampache\Module\Application\Browse;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Browse\BrowseContentView;
use Ampache\Gui\Mood\MoodCloudView;
use Ampache\Gui\Mood\MoodFormView;
use Ampache\Gui\Mood\MoodOrderView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\MoodRepositoryInterface;
use Ampache\Repository\VideoRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The mood cloud, and the browse of whatever the picked mood is on.
 */
final readonly class MoodAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'mood';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private RequestParserInterface $requestParser,
        private BrowseFactoryInterface $browseFactory,
        private UiInterface $ui,
        private MoodRepositoryInterface $moodRepository,
        private VideoRepositoryInterface $videoRepository,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->get(ConfigurationKeyEnum::SHOW_MOOD) === false) {
            return null;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $this->ui->showHeader();

        $countOrder   = $this->requestParser->getFromRequest('sort') ?: 'name';
        $request_type = $this->requestParser->getFromRequest('type');
        $browse_type  = (Browse::is_valid_type($request_type))
            ? $request_type
            : ($this->configContainer->get(ConfigurationKeyEnum::ALBUM_GROUP) ? 'album' : 'album_disk');
        if ($request_type !== $browse_type) {
            $_REQUEST['type'] = $browse_type;
        }

        // `mood` only counts the four types with a column, so an album_disk browse weighs its albums
        $countType = ($browse_type === 'album_disk')
            ? 'album'
            : $browse_type;

        $moods = $this->moodRepository->getMoods($countType, 0, $countOrder);

        $this->ui->showBoxTop(T_('Moods'), 'box box_mood_cloud');

        $browse = $this->browseFactory->create();
        $browse->set_type($browse_type);

        $showMood = $this->requestParser->getFromRequest('show_mood');
        echo (new MoodCloudView(
            new MoodFormView(
                AmpConfig::get_web_path(),
                $browse_type,
                AmpConfig::get_bool('album_group'),
                AmpConfig::get_bool('allow_video') && $this->videoRepository->getItemCount() > 0
            ),
            new MoodOrderView(AmpConfig::get_web_path('/client'), $browse_type, $countOrder),
            $browse->getId(),
            $moods,
            ($showMood !== '') ? (int) $showMood : null
        ))->render();

        $this->ui->showBoxBottom();
        echo (new BrowseContentView($browse->get_content_div()))->render();

        $browse->store();

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}

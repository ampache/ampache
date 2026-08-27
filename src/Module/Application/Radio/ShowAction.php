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

namespace Ampache\Module\Application\Radio;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\LiveStream\LiveStreamView;
use Ampache\Gui\Partial\PageMeta;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final readonly class ShowAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show';

    public function __construct(
        private RequestParserInterface $requestParser,
        private ConfigContainerInterface $configContainer,
        private UiInterface $ui,
        private LoggerInterface $logger,
        private ModelFactoryInterface $modelFactory,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::RADIO) === false) {
            throw new AccessDeniedException();
        }

        $user     = $gatekeeper->getUser() ?? $this->modelFactory->createUser(-1);
        $catalogs = User::get_user_catalogs($user->id);
        $radio_id = (int) $this->requestParser->getFromRequest('radio');
        $radio    = $this->modelFactory->createLiveStream($radio_id);
        $shown    = !$radio->isNew() && in_array($radio->catalog, $catalogs);

        if ($shown) {
            $webPath = AmpConfig::get_web_path();
            PageMeta::set(
                [(string) $radio->site_url, (string) $radio->codec],
                'music.radio_station',
                (string) $radio->get_fullname(),
                $webPath . '/radio.php?action=show&radio=' . $radio_id,
                $webPath . '/image.php?object_id=' . $radio_id . '&object_type=live_stream&size=600x600'
            );
        }

        $this->ui->showHeader();

        if (!$shown) {
            $this->logger->warning(
                'Requested a live_stream that does not exist',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            echo T_('You have requested an object that does not exist');
        } else {
            echo new LiveStreamView(
                $radio,
                Ui::is_grid_view('live_stream'),
                (bool) AmpConfig::get('directplay'),
                Stream_Playlist::check_autoplay_next(),
                Stream_Playlist::check_autoplay_append(),
                Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)
            )->render();
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}

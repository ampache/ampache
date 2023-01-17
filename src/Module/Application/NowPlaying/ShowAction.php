<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

namespace Ampache\Module\Application\NowPlaying;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\Ui;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';

    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    private LoggerInterface $logger;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
        LoggerInterface $logger
    ) {
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;
        $this->logger          = $logger;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        /* Check Perms */
        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::USE_NOW_PLAYING_EMBEDDED) === false ||
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true
        ) {
            return null;
        }

        Stream::garbage_collection();

        $css                    = '';
        $refreshLimit           = '';
        $nowPlayingCssFile      = $this->configContainer->get(ConfigurationKeyEnum::NOW_PLAYING_CSS_FILE);
        $nowPlayingRefreshLimit = $this->configContainer->get(ConfigurationKeyEnum::NOW_PLAYING_REFRESH_LIMIT);
        $language               = $this->configContainer->get(ConfigurationKeyEnum::LANG);

        if ($nowPlayingCssFile) {
            $css = sprintf(
                '<link rel="stylesheet" href="%s/%s" type="text/css" media="screen" />',
                $this->configContainer->getWebPath(),
                $nowPlayingCssFile
            );
        }

        if ($nowPlayingRefreshLimit) {
            $refreshLimit = sprintf(
                '<script>reload = window.setInterval(function(){ window.location.reload(); }, %d * 1000);</script>',
                $nowPlayingRefreshLimit
            );
        }

        $header = <<<HEAD
        <!DOCTYPE html>
        <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="%s" lang="%s" dir="%s">
        <head>
            <!-- Propelled by Ampache | ampache.org -->
            <meta http-equiv="Content-Type" content="application/xhtml+xml; charset=%s" />
            <title>%s</title>
            %s
            %s
        </head>
        <body>
        HEAD;

        printf(
            $header,
            $language,
            $language,
            is_rtl($language) ? 'rtl' : 'ltr',
            $this->configContainer->get(ConfigurationKeyEnum::SITE_CHARSET),
            sprintf(
                T_('%s - Now Playing'),
                $this->configContainer->get(ConfigurationKeyEnum::SITE_TITLE)
            ),
            $css,
            $refreshLimit
        );
        $user_id = (int)Core::get_request('user_id');
        $results = Stream::get_now_playing($user_id);
        if ($user_id > 0) {
            if (empty($results)) {
                $last_song = Stats::get_last_play(Core::get_request('user_id'));
                $media     = $this->modelFactory->createSong((int) $last_song['object_id']);
                $media->format();

                $client = $this->modelFactory->createUser((int) $last_song['user']);
                $client->format();

                $results[] = [
                    'media' => $media,
                    'client' => $client,
                    'agent' => $last_song['agent'],
                    'expire' => ''
                ];

                $this->logger->debug(
                    'no result; getting last song played instead: ' . $media->id,
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );
            }
            // If the URL specifies a specific user, filter the results on that user
            $results = array_filter($results, function ($item) {
                return ($item['client']->id === Core::get_request('user_id'));
            });
        }

        require Ui::find_template('show_now_playing.inc.php');

        print('</body></html>');

        return null;
    }
}

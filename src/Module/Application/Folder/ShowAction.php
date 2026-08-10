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

namespace Ampache\Module\Application\Folder;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Folder\FolderView;
use Ampache\Gui\Form\StatsFormViewFactoryInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final readonly class ShowAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show';

    public function __construct(
        private ModelFactoryInterface $modelFactory,
        private ConfigContainerInterface $configContainer,
        private UiInterface $ui,
        private LoggerInterface $logger,
        private FolderRepositoryInterface $folderRepository,
        private BrowseFactoryInterface $browseFactory,
        private StatsFormViewFactoryInterface $statsFormViewFactory,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHOW_FOLDER)) {
            throw new AccessDeniedException('Access Denied: folder features are not enabled.');
        }

        $this->ui->showHeader();

        $input = $request->getQueryParams();

        // lookup by ID
        $user      = $gatekeeper->getUser() ?? $this->modelFactory->createUser(-1);
        $folder_id = (isset($input['folder'])) ? (int) $input['folder'] : -1;
        $folder    = ($folder_id > 0)
            ? $this->folderRepository->findById($folder_id)
            : new Folder(-1);

        if (!$folder_id && $folder === null) {
            $this->logger->warning(
                'Requested a folder that does not exist',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            echo T_('You have requested an object that does not exist');
            $this->ui->showFooter();

            return null;
        } elseif ($folder instanceof Folder) {
            $browse = $this->browseFactory->create();
            $browse->set_type('folder');
            $browse->set_use_pages(true);
            $browse->set_simple_browse(true);
            $browse->set_skip_catalog_check($folder->id !== -1);
            $browse->add_supplemental_object('folder', $folder);
            $browse->set_sort('name', 'ASC', false);
            $browse->set_filter('int_id', $folder->id);

            $mayInteract = $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
                || $gatekeeper->mayAccess(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);

            echo (new FolderView(
                $folder,
                $browse,
                $this->statsFormViewFactory->createBrowse()->render(),
                ($folder->getId() > 0) ? $folder->get_media_count() : 0,
                (int) AmpConfig::get('direct_play_limit', 500),
                (bool) AmpConfig::get('directplay'),
                $mayInteract,
                Stream_Playlist::check_autoplay_next(),
                Stream_Playlist::check_autoplay_append()
            ))->render();

            $this->ui->showFooter();

            return null;
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}

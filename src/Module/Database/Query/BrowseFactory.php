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

namespace Ampache\Module\Database\Query;

use Ampache\Gui\Browse\ListRenderer\BrowseListRendererLocatorInterface;
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\Shout\ShoutObjectLoaderInterface;
use Ampache\Module\User\Following\UserFollowStateRendererInterface;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\CollectionRepositoryInterface;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\VideoRepositoryInterface;
use Ampache\Repository\WantedRepositoryInterface;

final readonly class BrowseFactory implements BrowseFactoryInterface
{
    public function __construct(
        private AjaxUriRetrieverInterface $ajaxUriRetriever,
        private CollectionRepositoryInterface $collectionRepository,
        private GatekeeperFactoryInterface $gatekeeperFactory,
        private GuiFactoryInterface $guiFactory,
        private LibraryItemLoaderInterface $libraryItemLoader,
        private LicenseRepositoryInterface $licenseRepository,
        private PodcastRepositoryInterface $podcastRepository,
        private ShoutObjectLoaderInterface $shoutObjectLoader,
        private ShoutRepositoryInterface $shoutRepository,
        private UiInterface $ui,
        private UserFollowStateRendererInterface $userFollowStateRenderer,
        private VideoRepositoryInterface $videoRepository,
        private WantedRepositoryInterface $wantedRepository,
        private ZipHandlerInterface $zipHandler,
        private BrowseListRendererLocatorInterface $browseListRendererLocator,
    ) {}

    public function create(
        ?int $browse_id = null,
        bool $cached = true,
    ): Browse {
        return new Browse(
            $this->ajaxUriRetriever,
            $this->collectionRepository,
            $this->gatekeeperFactory,
            $this->guiFactory,
            $this->libraryItemLoader,
            $this->licenseRepository,
            $this->podcastRepository,
            $this->shoutObjectLoader,
            $this->shoutRepository,
            $this->ui,
            $this->userFollowStateRenderer,
            $this->videoRepository,
            $this->wantedRepository,
            $this->zipHandler,
            $this->browseListRendererLocator,
            (int) $browse_id,
            $cached
        );
    }
}

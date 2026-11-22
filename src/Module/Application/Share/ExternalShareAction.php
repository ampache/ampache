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

namespace Ampache\Module\Application\Share;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Share\ShareCreatorInterface;
use Ampache\Module\System\Core;
use Ampache\Module\System\Plugin\Plugin;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Plugin\PluginExternalShareInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\container_item;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode\RFC\RFC7231;

final readonly class ExternalShareAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'external_share';

    public function __construct(
        private RequestParserInterface $requestParser,
        private ConfigContainerInterface $configContainer,
        private PasswordGeneratorInterface $passwordGenerator,
        private ResponseFactoryInterface $responseFactory,
        private FunctionCheckerInterface $functionChecker,
        private ShareCreatorInterface $shareCreator,
        private LibraryItemLoaderInterface $libraryItemLoader,
        private ModelFactoryInterface $modelFactory,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ResponseInterface
    {
        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHARE)) {
            throw new AccessDeniedException('Access Denied: sharing features are not enabled.');
        }

        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)) {
            throw new AccessDeniedException();
        }

        $pluginName = Core::get_get('plugin');
        if ($pluginName === '') {
            throw new AccessDeniedException('Access Denied - Unknown external share plugin');
        }

        $plugin = new Plugin($pluginName);
        if (!$plugin->_plugin instanceof PluginExternalShareInterface) {
            throw new AccessDeniedException('Access Denied - Unknown external share plugin');
        }

        // a visitor can now reach this, so a made up type is refused instead of failing with a server error
        $type     = LibraryItemEnum::tryFrom($this->requestParser->getFromRequest('type'))
            ?? throw new AccessDeniedException('Access Denied - Unknown or hidden object');
        $objectId = (int) $this->requestParser->getFromRequest('id');

        if ($this->configContainer->isAuthenticationEnabled()) {
            $user = $gatekeeper->getUser();
            if (!$user instanceof User) {
                throw new AccessDeniedException();
            }

            $share = $this->createShare($user, $type, $objectId);
            $url   = $share->public_url ?? throw new AccessDeniedException('Access Denied - Unable to create share');
            $text  = $share->getObjectName();
        } else {
            // the page is open to everyone, so its own address is the link and no share is created
            $user = $gatekeeper->getUser() ?? $this->modelFactory->createUser(-1);
            $item = $this->shownItem($type, $objectId, $user);
            $url  = $item->get_link();
            $text = $this->shareText($item);
        }

        $plugin->load($user);

        return $this->responseFactory
            ->createResponse(RFC7231::FOUND)
            ->withHeader(
                'Location',
                $plugin->_plugin->external_share($url, $text)
            );
    }

    private function createShare(User $user, LibraryItemEnum $type, int $objectId): Share
    {
        $allow_download = ($type === LibraryItemEnum::SONG && $this->functionChecker->check(AccessFunctionEnum::FUNCTION_DOWNLOAD))
            || $this->functionChecker->check(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD);

        $share_id = $this->shareCreator->create(
            $user,
            $type,
            $objectId,
            true,
            $allow_download,
            (int) ($this->configContainer->get(ConfigurationKeyEnum::SHARE_EXPIRE) ?? 7),
            $this->passwordGenerator->generate_token()
        );

        return new Share($share_id);
    }

    /**
     * The sentence posted before the link, as the visitor would say it
     */
    private function shareText(library_item $item): string
    {
        $parent = ($item instanceof container_item) ? $item->get_parent_fullname() : '';
        $site   = (string) $this->configContainer->get(ConfigurationKeyEnum::SITE_TITLE);

        // a whole sentence per kind of item, so a translator places the names where the language wants
        $sentence = match (true) {
            /* HINT: %1 video title, %3 site name */
            $item instanceof Video => T_("I'm watching %1\$s on %3\$s"),
            /* HINT: %1 playlist name, %3 site name */
            $item instanceof Playlist => T_("I'm listening to the playlist %1\$s on %3\$s"),
            /* HINT: %1 artist, or a song or album without one, %3 site name */
            $parent === '' => T_("I'm listening to %1\$s on %3\$s"),
            /* HINT: %1 album title, %2 artist name, %3 site name */
            $item instanceof Album, $item instanceof AlbumDisk => T_("I'm listening to the album %1\$s by %2\$s on %3\$s"),
            /* HINT: %1 song title, %2 artist name, %3 site name */
            default => T_("I'm listening to %1\$s by %2\$s on %3\$s"),
        };

        return sprintf($sentence, (string) $item->get_fullname(), $parent, ($site !== '') ? $site : 'Ampache');
    }

    /**
     * The item as its own page would show it to this viewer, so nothing the page hides is named or linked
     */
    private function shownItem(LibraryItemEnum $type, int $objectId, User $user): library_item
    {
        $item     = $this->libraryItemLoader->load($type, $objectId, [Song::class, Album::class, AlbumDisk::class, Artist::class, Playlist::class, Video::class]);
        $catalogs = $user->catalogs['music'] ?? User::get_user_catalogs($user->id);

        // the same refusals as the show actions, type by type
        $shown = match (true) {
            $item instanceof Song, $item instanceof AlbumDisk => in_array($item->catalog, $catalogs) && $item->isVisible($user),
            $item instanceof Album => ($item->catalog === 0 || in_array($item->catalog, $catalogs)) && $item->isVisible($user),
            $item instanceof Artist, $item instanceof Playlist => $item->isVisible($user),
            $item instanceof Video => true,
            default => false,
        };

        if (!$item instanceof library_item || !$shown) {
            throw new AccessDeniedException('Access Denied - Unknown or hidden object');
        }

        return $item;
    }
}

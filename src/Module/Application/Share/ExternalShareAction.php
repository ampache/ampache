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
use Ampache\Gui\Partial\ShareLineView;
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
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
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

    /** The items shared one by one, here and in the share form; a smartlist is checked by its share */
    public const array SHAREABLE_ITEMS = [
        Song::class,
        Album::class,
        AlbumDisk::class,
        Artist::class,
        Playlist::class,
        Podcast::class,
        Podcast_Episode::class,
        Video::class,
    ];

    public function __construct(
        private RequestParserInterface $requestParser,
        private ConfigContainerInterface $configContainer,
        private PasswordGeneratorInterface $passwordGenerator,
        private ResponseFactoryInterface $responseFactory,
        private FunctionCheckerInterface $functionChecker,
        private ShareCreatorInterface $shareCreator,
        private LibraryItemLoaderInterface $libraryItemLoader,
    ) {}

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ResponseInterface
    {
        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SHARE)) {
            throw new AccessDeniedException('Access Denied: sharing features are not enabled.');
        }

        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE)) {
            throw new AccessDeniedException();
        }

        $user = $gatekeeper->getUser();
        if (!$user instanceof User || !ShareLineView::isOfferedTo($user)) {
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

        // a visitor can reach this, so a made up type is refused rather than ending in a server error
        $type     = LibraryItemEnum::tryFrom($this->requestParser->getFromRequest('type'))
            ?? throw new AccessDeniedException('Access Denied - Unknown or hidden object');
        $objectId = (int) $this->requestParser->getFromRequest('id');

        $logins = $this->configContainer->isAuthenticationEnabled();
        $item   = $this->libraryItemLoader->load($type, $objectId, self::SHAREABLE_ITEMS);

        // the same refusals as the show actions, so nothing a page hides is named, linked or shared
        $shown = match (true) {
            $item instanceof Song, $item instanceof Album, $item instanceof AlbumDisk => $item->isShownTo($user),
            $item instanceof Artist, $item instanceof Playlist => $item->isVisible($user),
            // never linked bare: only a share carries a podcast, so there must be logins to share one
            $item instanceof Podcast, $item instanceof Podcast_Episode => $logins
                && in_array($item->getCatalogId(), $user->catalogs['podcast'] ?? User::get_user_catalogs($user->id)),
            $item instanceof Video => true,
            // a smartlist is checked by the share itself, and an id that names nothing is refused
            default => $logins && $type === LibraryItemEnum::SEARCH,
        };
        if (!$shown) {
            throw new AccessDeniedException('Access Denied - Unknown or hidden object');
        }

        if (!$logins && $item instanceof container_item) {
            // the page is open to everyone, so its own address is the link and no share is created
            $url  = $item->get_link();
            $text = $this->shareText($item, $user);
        } else {
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

            $share = new Share($share_id);
            $url   = $share->public_url ?? throw new AccessDeniedException('Access Denied - Unable to create share');

            // a smartlist has no sentence of its own, so it is posted under its share's name
            $text = ($item instanceof container_item) ? $this->shareText($item, $user) : $share->getObjectName();
        }

        $plugin->load($user);

        return $this->responseFactory
            ->createResponse(RFC7231::FOUND)
            ->withHeader(
                'Location',
                $plugin->_plugin->external_share($url, $text)
            );
    }

    /**
     * Fills in each {name}; a [part] holding an empty one is left out, so no dangling "by" is posted
     *
     * @param array<string, string> $values
     */
    private function fillShareText(string $text, array $values): string
    {
        $text = (string) preg_replace_callback(
            '/\[([^][]*)]/',
            static function (array $part) use ($values): string {
                preg_match_all('/{(\w+)}/', $part[1], $names);
                foreach ($names[1] as $name) {
                    if (($values[$name] ?? '') === '') {
                        return '';
                    }
                }

                return $part[1];
            },
            $text
        );

        $pairs = [];
        foreach ($values as $name => $value) {
            $pairs['{' . $name . '}'] = $value;
        }

        // strtr replaces in one pass, so a title that itself reads {site} is posted as typed
        return trim(strtr($text, $pairs));
    }

    /**
     * The first three tags as hashtags, stripped of the spaces and signs a network would cut a hashtag at
     *
     * @param array<int, array{name: string}> $tags
     */
    private function hashtags(array $tags): string
    {
        $hashtags = [];
        foreach ($tags as $tag) {
            $word = (string) preg_replace('/[^\p{L}\p{N}_]+/u', '', $tag['name']);
            // a tag made only of signs would post a bare #
            if ($word !== '') {
                $hashtags[] = '#' . $word;
            }
        }

        return implode(' ', array_slice($hashtags, 0, 3));
    }

    /**
     * The sentence posted before the link, as the visitor would say it
     */
    private function shareText(container_item $item, User $user): string
    {
        $parent = $item->get_parent_fullname();
        $site   = (string) $this->configContainer->get(ConfigurationKeyEnum::SITE_TITLE);

        $custom = trim((string) $this->configContainer->get(ConfigurationKeyEnum::SHARE_TEXT));
        if ($custom !== '') {
            return $this->fillShareText($custom, [
                'title' => (string) $item->get_fullname(),
                'artist' => match (true) {
                    $item instanceof Artist => (string) $item->get_fullname(),
                    $item instanceof Song, $item instanceof Album, $item instanceof AlbumDisk => $parent,
                    default => '',
                },
                'album' => match (true) {
                    $item instanceof Song => $item->get_album_fullname(),
                    $item instanceof Album, $item instanceof AlbumDisk => $item->get_fullname(),
                    default => '',
                },
                'site' => $site,
                'tags' => match (true) {
                    $item instanceof Song, $item instanceof Album, $item instanceof AlbumDisk,
                    $item instanceof Artist, $item instanceof Video => $this->hashtags($item->get_tags()),
                    default => '',
                },
                // the visitor without an account is the guest user, who has no name worth posting
                'user' => ($user->id > 0) ? (string) $user->get_fullname() : '',
                'license' => ($item instanceof Song) ? (string) $item->getLicense()?->getName() : '',
            ]);
        }

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

        return sprintf($sentence, (string) $item->get_fullname(), $parent, $site);
    }
}

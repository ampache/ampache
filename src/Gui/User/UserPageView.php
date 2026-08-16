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

namespace Ampache\Gui\User;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Playback\Tmp_Playlist;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Plugin\Plugin;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Module\System\Preference;
use Ampache\Module\User\Activity\Useractivity;
use Ampache\Module\User\Activity\UserActivityRendererInterface;
use Ampache\Module\User\Following\UserFollowStateRendererInterface;
use Ampache\Module\Util\Upload;
use Ampache\Plugin\PluginDisplayUserFieldInterface;
use Ampache\Repository\Model\displayable_item;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Override;

/**
 * A user's public page: their details and the tabbed play history, uploads, lists and timeline.
 */
final class UserPageView extends AbstractView
{
    /**
     * @param array<int> $following
     * @param array<int> $followers
     * @param array<int> $activities
     */
    public function __construct(
        private readonly User $client,
        private readonly ?User $currentUser,
        private readonly array $following,
        private readonly array $followers,
        private readonly array $activities,
        private readonly BrowseFactoryInterface $browseFactory,
        private readonly UserActivityRendererInterface $userActivityRenderer,
        private readonly UserFollowStateRendererInterface $userFollowStateRenderer,
        private readonly LibraryItemLoaderInterface $libraryItemLoader,
        private readonly string $webPath,
        private readonly bool $mayUse,
        private readonly bool $mayManage,
        private readonly bool $isAdmin,
    ) {}

    public function createBrowse(): Browse
    {
        return $this->browseFactory->create();
    }

    public function getActivities(): string
    {
        if (!Preference::get_by_user($this->getClientId(), 'allow_personal_info_recent')) {
            return '';
        }

        Useractivity::build_cache($this->activities);
        $output = '';
        foreach ($this->activities as $activityId) {
            $output .= $this->userActivityRenderer->show(new Useractivity($activityId));
        }

        return $output;
    }

    public function getAdminPath(): string
    {
        return AmpConfig::get_web_path('/admin');
    }

    public function getAvatar(): string
    {
        return $this->client->get_f_avatar('f_avatar');
    }

    public function getClient(): User
    {
        return $this->client;
    }

    public function getClientId(): int
    {
        return $this->client->getId();
    }

    public function getCreateDate(): string
    {
        return ($this->client->create_date) ? get_datetime((int) $this->client->create_date) : T_('Unknown');
    }

    /**
     * @return array<int>
     */
    public function getFollowers(): array
    {
        return $this->followers;
    }

    /**
     * @return array<int>
     */
    public function getFollowing(): array
    {
        return $this->following;
    }

    public function getFollowState(): string
    {
        return ($this->currentUser instanceof User)
            ? $this->userFollowStateRenderer->render($this->client, $this->currentUser)
            : '';
    }

    public function getFullname(): string
    {
        return (string) $this->client->get_fullname();
    }

    public function getLastSeen(): string
    {
        if (!Preference::get_by_user($this->getClientId(), 'allow_personal_info_time')) {
            return T_('Never');
        }

        return ($this->client->last_seen) ? get_datetime((int) $this->client->last_seen) : T_('Never');
    }

    /**
     * @return array<int>
     */
    public function getPlaylistIds(): array
    {
        return $this->client->get_playlists($this->isSelf() || $this->isAdmin);
    }

    /**
     * The widgets a plugin contributes to a user's page, already rendered.
     *
     * @return list<string>
     */
    public function getPluginFields(): array
    {
        if (!$this->currentUser instanceof User || !AmpConfig::get('sociable')) {
            return [];
        }

        $fields = [];
        foreach (Plugin::get_plugins(PluginTypeEnum::USER_FIELD_WIDGET) as $name) {
            $plugin = new Plugin($name);
            if ($plugin->_plugin instanceof PluginDisplayUserFieldInterface && $plugin->load($this->client)) {
                ob_start();
                $plugin->_plugin->display_user_field();
                $fields[] = (string) ob_get_clean();
            }
        }

        return $fields;
    }

    /**
     * The recently played and skipped lists, already configured for this user.
     */
    public function getRecentlyPlayed(): RecentlyPlayedViewFactoryResult
    {
        $allTypes = (bool) AmpConfig::get('home_recently_played_all');
        $data     = ($allTypes)
            ? Stats::get_recently_played($this->getClientId(), 'stream', null, true)
            : Stats::get_recently_played($this->getClientId(), 'stream', 'song', true);
        if (!$allTypes) {
            Song::build_cache(array_keys($data));
        }

        return new RecentlyPlayedViewFactoryResult($allTypes, $data);
    }

    /**
     * @return array<int, array{object_type: mixed, object_id: int}>
     */
    public function getTemporaryPlaylistItems(): array
    {
        $listId = Tmp_Playlist::get_from_username((string) $this->client->username);
        if (!$listId) {
            return [];
        }

        /** @var array<int, array{object_type: mixed, object_id: int}> $items */
        $items = (new Tmp_Playlist($listId))->get_items();

        return $items;
    }

    public function getUploadsSql(): string
    {
        return Catalog::get_uploads_sql('artist', $this->getClientId());
    }

    public function getUsage(): string
    {
        return $this->client->get_f_usage();
    }

    public function getUsername(): string
    {
        return (string) $this->client->username;
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    public function getWrappedYear(): string
    {
        return date('Y') ?: '';
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function isOnline(): bool
    {
        return (bool) $this->client->is_logged_in() && $this->client->is_online();
    }

    public function isSelf(): bool
    {
        return $this->currentUser instanceof User && $this->getClientId() === $this->currentUser->getId();
    }

    public function itemLink(mixed $objectType, int $objectId): string
    {
        $item = $this->libraryItemLoader->load($objectType, $objectId);

        return ($item instanceof displayable_item) ? $item->get_f_link() : '';
    }

    public function mayManage(): bool
    {
        return $this->mayManage;
    }

    public function mayUpload(): bool
    {
        return Upload::can_upload($this->currentUser);
    }

    public function mayUse(): bool
    {
        return $this->mayUse;
    }

    public function showGraphs(): bool
    {
        return (bool) AmpConfig::get('statistical_graphs');
    }

    public function showNowPlaying(): bool
    {
        return (bool) AmpConfig::get('use_now_playing_embedded');
    }

    public function showSociable(): bool
    {
        return (bool) AmpConfig::get('sociable');
    }

    public function showWrapped(): bool
    {
        return (bool) AmpConfig::get('show_wrapped');
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('user/user.phtml');
    }
}

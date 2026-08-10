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

namespace Ampache\Gui\Stats;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\Rss\Type\RssFeedTypeEnum;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Override;

/**
 * The recently played, recently played (all types) and recently skipped lists.
 *
 * They were three near-identical templates that each carried their own copy of the interval formatter, and
 * each left the agent cell unclosed whenever the agent string was empty.
 */
final class RecentlyPlayedView extends AbstractView
{
    /**
     * @param array<int, array{user: int, object_type: string, object_id: int, agent: string, user_recent: int, user_time: int, date?: null|int, activity_id: int}> $data
     */
    public function __construct(
        private readonly RecentlyPlayedMode $mode,
        private readonly array $data,
        private readonly ?User $user,
        private readonly int $userId,
        private readonly bool $userOnly,
        private readonly string $webPath,
    ) {}

    public function getBoxClass(): string
    {
        return ($this->mode === RecentlyPlayedMode::SKIPPED) ? 'box box_recently_skipped' : 'box_recently_played';
    }

    public function getBoxTitle(): string
    {
        $title = ($this->mode === RecentlyPlayedMode::SKIPPED) ? T_('Recently Skipped') : T_('Recently Played');
        if ($this->mode === RecentlyPlayedMode::SONGS && AmpConfig::get('use_rss')) {
            $title .= '&nbsp' . Ui::getRssLink(RssFeedTypeEnum::RECENTLY_PLAYED, $this->user);
        }

        return $title . '&nbsp' . $this->getRefreshButton();
    }

    /**
     * @return list<array{class: string, label: string}>
     */
    public function getColumns(): array
    {
        $isAll   = $this->mode === RecentlyPlayedMode::ALL_TYPES;
        $columns = [
            ['class' => 'cel_play', 'label' => ''],
            ['class' => 'cel_song', 'label' => ($isAll) ? T_('Title') : T_('Song')],
            ['class' => 'cel_add', 'label' => ''],
            ['class' => 'cel_artist', 'label' => ($isAll) ? T_('Artist') : T_('Song Artist')],
            ['class' => 'cel_album', 'label' => T_('Album')],
            ['class' => 'cel_year', 'label' => T_('Year')],
        ];

        if ($this->showUser()) {
            $columns[] = ['class' => 'cel_username', 'label' => T_('Username')];
        }

        $columns[] = ['class' => $this->getPlayedColumnClass(), 'label' => T_('Last Played')];

        if ($this->isAdmin()) {
            $columns[] = ['class' => 'cel_agent', 'label' => T_('Agent')];
            $columns[] = ['class' => 'cel_delete', 'label' => ''];
        }

        return $columns;
    }

    public function getDeleteButton(int $activityId): string
    {
        $action = ($this->mode === RecentlyPlayedMode::SKIPPED) ? 'delete_skip' : 'delete_play';

        return Ajax::button(
            '?page=stats&action=' . $action . '&activity_id=' . $activityId . $this->getUserParam(),
            'close',
            T_('Delete'),
            'activity_remove_' . $activityId
        );
    }

    public function getMoreLink(): string
    {
        return $this->webPath . '/stats.php?action=recent_song' . (($this->userId > 0) ? '&user_id=' . $this->userId : '');
    }

    public function getPlayedColumnClass(): string
    {
        return ($this->mode === RecentlyPlayedMode::SKIPPED) ? 'cel_lastskipped' : 'cel_lastplayed';
    }

    /**
     * The play, add and dialog controls all name the type, so a mixed list must not hardcode `song`.
     *
     * @return list<array{media: Live_Stream|Podcast_Episode|Song|Video, type: string, agent: string, time: string, userId: int, username: string, activityId: int}>
     */
    public function getRows(): array
    {
        $rows = [];
        foreach ($this->data as $row) {
            $type      = ($this->mode === RecentlyPlayedMode::ALL_TYPES) ? $row['object_type'] : 'song';
            $className = ObjectTypeToClassNameMapper::map($type);
            /** @var Live_Stream|Podcast_Episode|Song|Video $media */
            $media = new $className($row['object_id']);
            if ($media->isNew()) {
                continue;
            }

            // a user who hides their recent activity drops out of the list entirely
            $rowUserId = ($row['user'] > 0) ? (int) $row['user'] : -1;
            $isOwn     = $this->isAdmin() || $this->userId === $rowUserId;
            if (!$isOwn && !$row['user_recent']) {
                continue;
            }

            $rows[] = [
                'media' => $media,
                'type' => $type,
                'agent' => ($this->isAdmin()) ? $row['agent'] : '',
                'time' => ($isOwn || $row['user_time']) ? $this->getTimeString($row['date'] ?? 0) : '-',
                'userId' => $rowUserId,
                'username' => (string) (new User($rowUserId))->fullname,
                'activityId' => $row['activity_id'],
            ];
        }

        return $rows;
    }

    public function getUserUrl(int $userId): string
    {
        return $this->webPath . '/stats.php?action=show_user&user_id=' . $userId;
    }

    public function isAdmin(): bool
    {
        return Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN);
    }

    public function isAlbumGrouped(): bool
    {
        return $this->mode !== RecentlyPlayedMode::SKIPPED && (bool) AmpConfig::get('album_group');
    }

    public function isDirectPlay(): bool
    {
        return (bool) AmpConfig::get('directplay');
    }

    public function showMore(): bool
    {
        return $this->mode === RecentlyPlayedMode::SONGS;
    }

    public function showUser(): bool
    {
        return !$this->userOnly && $this->userId > 0;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('recently_played.phtml');
    }

    private function getRefreshButton(): string
    {
        if ($this->mode === RecentlyPlayedMode::SKIPPED) {
            return Ajax::button('?page=stats&action=refresh_skipped' . $this->getUserParam(), 'refresh', T_('Refresh'), 'refresh_skipped', 'box box_recently_played');
        }

        return Ajax::button('?page=index&action=refresh_index' . $this->getUserParam(), 'refresh', T_('Refresh'), 'refresh_index', 'box box_recently_played');
    }

    /**
     * Rounds the age down to its largest whole unit, so "3 days ago" rather than a timestamp.
     */
    private function getTimeString(int $date): string
    {
        $interval = time() - $date;
        $units    = [
            [60, 1, '%d second ago', '%d seconds ago'],
            [3600, 60, '%d minute ago', '%d minutes ago'],
            [86400, 3600, '%d hour ago', '%d hours ago'],
            [604800, 86400, '%d day ago', '%d days ago'],
            [2592000, 604800, '%d week ago', '%d weeks ago'],
            [31556926, 2592000, '%d month ago', '%d months ago'],
            [631138519, 31556926, '%d year ago', '%d years ago'],
        ];

        foreach ($units as [$ceiling, $divisor, $singular, $plural]) {
            if ($interval < $ceiling) {
                $value = (int) floor($interval / $divisor);

                return sprintf(nT_($singular, $plural, $value), $value);
            }
        }

        $value = (int) floor($interval / 315569260);

        return sprintf(nT_('%d decade ago', '%d decades ago', $value), $value);
    }

    private function getUserParam(): string
    {
        return ($this->userOnly) ? '&user_only=1&user_id=' . $this->userId : '';
    }
}

<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Tag\TagListUpdaterInterface;
use Ampache\Module\Util\Ui;
use Ampache\Repository\BroadcastRepositoryInteface;

final class Broadcast extends database_object implements BroadcastInterface
{
    protected const DB_TABLENAME = 'broadcast';

    private BroadcastRepositoryInteface $broadcastRepository;

    private TagListUpdaterInterface $tagListUpdater;

    public int $id;

    /** @var array<string, int|string>|null */
    private ?array $dbData = null;

    public function __construct(
        BroadcastRepositoryInteface $broadcastRepository,
        TagListUpdaterInterface $tagListUpdater,
        int $id
    ) {
        $this->broadcastRepository = $broadcastRepository;
        $this->tagListUpdater      = $tagListUpdater;
        $this->id                  = $id;
    }

    private function getDbData(): array
    {
        if ($this->dbData === null) {
            $this->dbData = $this->broadcastRepository->getDataById($this->id);
        }

        return $this->dbData;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return $this->getDbData() === [];
    }

    public function getName(): string
    {
        return $this->getDbData()['name'] ?? '';
    }

    public function getUserId(): int
    {
        return (int) ($this->getDbData()['user'] ?? 0);
    }

    public function getIsPrivate(): int
    {
        return (int) ($this->getDbData()['is_private'] ?? 0);
    }

    public function getSongPosition(): int
    {
        return (int) ($this->getDbData()['song_position'] ?? 0);
    }

    public function setSongPosition(int $value): void
    {
        $this->dbData['song_position'] = $value;
    }

    public function getSongId(): int
    {
        return (int) ($this->getDbData()['song'] ?? 0);
    }

    public function setSongId(int $value): void
    {
        $this->dbData['song'] = $value;
    }

    public function getListeners(): int
    {
        return (int) ($this->getDbData()['listeners'] ?? 0);
    }

    public function setListeners(int $value): void
    {
        $this->dbData['listeners'] = $value;
    }

    public function getStarted(): int
    {
        return (int) ($this->getDbData()['started'] ?? 0);
    }

    public function setStarted(int $value): void
    {
        $this->dbData['started'] = $value;
    }

    public function getTags(): array
    {
        return Tag::get_top_tags('broadcast', $this->getId());
    }

    public function getTagsFormatted(): string
    {
        return Tag::get_display($this->getTags(), true, 'broadcast');
    }

    public function getLinkFormatted(): string
    {
        return sprintf(
            '<a href="%s/broadcast.php?id=%d">%s</a>',
            AmpConfig::get('web_path'),
            $this->getId(),
            scrub_out($this->getName())
        );
    }

    /**
     * Update broadcast state.
     * @param boolean $started
     * @param string $key
     */
    public function update_state($started, $key = '')
    {
        $this->broadcastRepository->updateState(
            (int) $started,
            $key
        );

        $this->setStarted($started);
    }

    /**
     * Update broadcast listeners.
     * @param integer $listeners
     */
    public function update_listeners($listeners)
    {
        $this->broadcastRepository->updateListeners(
            $this->getId(),
            $listeners
        );

        $this->setListeners($listeners);
    }

    /**
     * Update broadcast current song.
     * @param integer $song_id
     */
    public function update_song($song_id)
    {
        $this->broadcastRepository->updateSong($this->getId(), $song_id);

        $this->setSongId($song_id);
        $this->setSongPosition(0);
    }

    /**
     * Update a broadcast from data array.
     * @param array $data
     * @return integer
     */
    public function update(array $data)
    {
        if (isset($data['edit_tags'])) {
            $this->tagListUpdater->update($data['edit_tags'], 'broadcast', $this->id, true);
        }

        $this->broadcastRepository->update(
            $this->getId(),
            $data['name'],
            $data['description'],
            $data['private'] ?? 0
        );

        return $this->id;
    }

    /**
     * @param boolean $details
     */
    public function format($details = true)
    {
    }

    /**
     * Get item keywords for metadata searches.
     * @return array
     */
    public function get_keywords()
    {
        return array();
    }

    /**
     * Get item fullname.
     * @return string
     */
    public function get_fullname()
    {
        return $this->getName();
    }

    /**
     * Get parent item description.
     * @return array|null
     */
    public function get_parent()
    {
        return null;
    }

    /**
     * Get item childrens.
     * @return array
     */
    public function get_childrens()
    {
        return array();
    }

    /**
     * Search for item childrens.
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        debug_event(self::class, 'search_childrens ' . $name, 5);

        return array();
    }

    /**
     * Get all childrens and sub-childrens medias.
     * @param string $filter_type
     * @return array
     */
    public function get_medias($filter_type = null)
    {
        // Not a media, shouldn't be that
        $medias = array();
        if ($filter_type === null || $filter_type == 'broadcast') {
            $medias[] = array(
                'object_type' => 'broadcast',
                'object_id' => $this->id
            );
        }

        return $medias;
    }

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return integer[]
     */
    public function get_catalogs()
    {
        return array();
    }

    /**
     * Get item's owner.
     * @return integer|null
     */
    public function get_user_owner()
    {
        return $this->getUserId();
    }

    /**
     * Get default art kind for this item.
     * @return string
     */
    public function get_default_art_kind()
    {
        return 'default';
    }

    /**
     * @return mixed|null
     */
    public function get_description()
    {
        return null;
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        if (Art::has_db($this->id, 'broadcast') || $force) {
            echo Art::display('broadcast', $this->id, $this->get_fullname(), $thumb);
        }
    }

    /**
     * Generate a new broadcast key.
     * @return string
     */
    public static function generate_key()
    {
        // Should be improved for security reasons!
        return md5(uniqid((string)rand(), true));
    }

    /**
     * Get broadcast link.
     * @return string
     */
    public static function get_broadcast_link()
    {
        $link = "<div class=\"broadcast-action\">";
        $link .= "<a href=\"#\" onclick=\"showBroadcastsDialog(event);\">" . Ui::get_icon('broadcast',
                T_('Broadcast')) . "</a>";
        $link .= "</div>";

        return $link;
    }

    /**
     * Get unbroadcast link.
     * @param integer $broadcast_id
     * @return string
     */
    public static function get_unbroadcast_link($broadcast_id)
    {
        $link = "<div class=\"broadcast-action\">";
        $link .= Ajax::button('?page=player&action=unbroadcast&broadcast_id=' . $broadcast_id, 'broadcast',
            T_('Unbroadcast'), 'broadcast_action');
        $link .= "</div>";
        $link .= "<div class=\"broadcast-info\">(<span id=\"broadcast_listeners\">0</span>)</div>";

        return $link;
    }

    /**
     * Get play url.
     *
     * @param string $additional_params
     * @param string $player
     * @param boolean $local
     * @return integer
     */
    public function play_url($additional_params = '', $player = null, $local = false)
    {
        return $this->id;
    }

    public function isEnabled(): bool
    {
        return true;
    }
}

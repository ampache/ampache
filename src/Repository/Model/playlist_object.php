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

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Art\Mosaic\PlaylistArtBuilderInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\Ui;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * playlist_object
 * Abstracting out functionality needed by both normal and smart playlists
 */
abstract class playlist_object extends database_object implements
    library_item,
    container_item,
    displayable_item
{
    public ?string $collaborate = '';
    public int $date            = 0;

    // Database variables
    public int $id                 = 0;
    public ?int $last_count        = 0;
    public ?int $last_duration     = 0;
    public ?int $last_update       = 0;
    public ?string $link           = null;
    public ?string $name           = null;
    public ?string $type           = null;
    public ?int $user              = null;
    public ?string $username       = null;
    private ?string $f_last_update = null;
    private ?string $f_link        = null;
    private ?string $f_name        = null;
    private ?string $f_type        = null;
    private ?bool $has_art         = null;

    /**
     * display_art
     * @param array{width: int, height: int} $size
     */
    public function display_art(array $size, bool $force = false, bool $link = true): void
    {
        if (AmpConfig::get('playlist_art') || $force) {
            $add_link  = ($link) ? $this->get_link() : null;
            $list_type = ($this instanceof Search)
                ? 'search'
                : 'playlist';
            Art::display($list_type, $this->id, (string) $this->get_fullname(), $size, $add_link);
        }
    }

    /**
     * gather_art
     */
    public function gather_art(int $limit): array
    {
        $web_path = AmpConfig::get_web_path('/client');

        $medias = $this->get_medias();
        $count  = 0;
        $images = [];
        $tiles  = [];
        $seen   = [];
        $title  = T_('Playlist Items');
        $mosaic = make_bool(AmpConfig::get(ConfigurationKeyEnum::PLAYLIST_ART_MOSAIC, true));
        // Shuffle so the covers picked aren't just the first few, but seed it from the playlist and its
        // contents so the same playlist keeps producing the same art. Re-running an art gather would
        // otherwise hand the user a different mosaic every time for a playlist that never changed.
        $seed    = crc32($this->id . ':' . implode(',', array_column($medias, 'object_id')));
        $medias  = (new Randomizer(new Mt19937($seed)))->shuffleArray($medias);
        foreach ($medias as $media) {
            // Only the mosaic is capped, so the caller still gets the full list of covers to choose from
            // when it falls back to picking one.
            if ($count >= $limit) {
                break;
            }

            if (InterfaceImplementationChecker::is_library_item($media['object_type']->value)) {
                if (!Art::has_db($media['object_id'], $media['object_type']->value)) {
                    $className = ObjectTypeToClassNameMapper::map($media['object_type']->value);
                    /** @var container_item $libitem */
                    $libitem = new $className($media['object_id']);
                    $parent  = $libitem->get_parent();
                    if ($parent !== null) {
                        $media = $parent;
                    }
                }

                // Skip covers we've already taken so a single-album playlist doesn't repeat one tile.
                $key = $media['object_type']->value . '-' . $media['object_id'];
                if (isset($seen[$key])) {
                    continue;
                }

                $art = new Art($media['object_id'], $media['object_type']->value);
                if ($art->has_db_info()) {
                    $seen[$key] = true;
                    $link       = $web_path . "/image.php?object_id=" . $media['object_id'] . "&object_type=" . $media['object_type']->value;
                    // The row id matters as well as the link: `url` is relative, so anything reading these
                    // back (the art picker, image.php) can't fetch it and would show the cover as invalid.
                    $images[]   = [
                        'db' => $art->id,
                        'url' => $link,
                        'mime' => $art->raw_mime,
                        'title' => $title
                    ];
                    if (
                        $mosaic
                        && count($tiles) < PlaylistArtBuilderInterface::MAX_TILES
                        && $art->raw !== null
                        && $art->raw !== ''
                    ) {
                        $tiles[] = $art->raw;
                    }

                    ++$count;
                }
            }
        }

        if ($mosaic && count($tiles) >= PlaylistArtBuilderInterface::MIN_TILES) {
            $stitched = $this->getPlaylistArtBuilder()->build($tiles);
            if ($stitched !== null) {
                // First choice for whoever is gathering art automatically, but the individual covers stay
                // in the list: the art picker can't offer a mosaic (raw bytes can't survive the session)
                // and would have nothing to show if this were the only result.
                array_unshift($images, [
                    'raw' => $stitched,
                    'mime' => 'image/png',
                    'title' => $title,
                ]);
            }
        }

        return $images;
    }

    public function get_default_art_kind(): string
    {
        return 'default';
    }

    /**
     * get_description
     */
    public function get_description(): string
    {
        return '';
    }

    /**
     * Get item update date of the playlist
     */
    public function get_f_last_update(): string
    {
        // don't do anything if it's formatted
        if ($this->f_last_update === null) {
            $this->f_last_update = ($this->last_update)
                ? get_datetime((int) $this->last_update)
                : T_('Unknown');
        }

        return $this->f_last_update;
    }

    /**
     * Get item link.
     */
    public function get_f_link(?string $title = null): string
    {
        // don't do anything if it's formatted
        if ($this->f_link === null) {
            $link_text    = scrub_out($title ?? $this->get_fullname());
            $this->f_link = '<a href="' . $this->get_link() . '" title="' . $link_text . '">' . $link_text . '</a>';
        }

        return $this->f_link;
    }

    /**
     * Return a formatted link to the parent object (if appliccable)
     */
    public function get_f_parent_link(): ?string
    {
        return null;
    }

    /**
     * Get item f_time or f_time_h.
     */
    public function get_f_time(): string
    {
        return '';
    }

    /**
     * Get item type (public / private).
     */
    public function get_f_type(): string
    {
        // don't do anything if it's formatted
        if ($this->f_type === null) {
            $this->f_type = ($this->type == 'private') ? Ui::get_material_symbol('lock', T_('Private')) : '';
        }

        return $this->f_type;
    }

    /**
     * get_fullname
     */
    public function get_fullname(): ?string
    {
        if ($this->f_name === null) {
            $show_fullname = AmpConfig::get('show_playlist_username');
            $my_playlist   = (Core::get_global('user') instanceof User && ($this->user == Core::get_global('user')->id));
            $this->f_name  = ($my_playlist || !$show_fullname)
                ? $this->name
                : $this->name . " (" . $this->username . ")";
        }

        return $this->f_name;
    }

    /**
     * @return array<int, array{
     *     object_type: LibraryItemEnum,
     *     object_id: int,
     *     track: int,
     *     track_id: int,
     *     time: int
     * }>
     */
    abstract public function get_items(): array;

    /**
     * Get item keywords for metadata searches.
     * @return array<string, array{important: bool, label: string, value: string}>
     */
    public function get_keywords(): array
    {
        return [];
    }

    /**
     * Get item link.
     */
    public function get_link(): string
    {
        // don't do anything if it's formatted
        if ($this->link === null) {
            $web_path = AmpConfig::get_web_path('/client');

            $this->link = ($this instanceof Search)
                ? $web_path . '/smartplaylist.php?action=show&playlist_id=' . $this->id
                : $web_path . '/playlist.php?action=show&playlist_id=' . $this->id;
        }

        return $this->link ?? '';
    }

    /**
     * @return array<int, array{
     *     object_type: LibraryItemEnum,
     *     object_id: int,
     *     track: int,
     *     track_id: int,
     *     time: int
     * }>
     */
    public function get_medias(?string $filter_type = null): array
    {
        if ($filter_type) {
            return array_filter(
                $this->get_items(),
                static fn(array $item): bool => $item['object_type']->value === $filter_type
            );
        }

        return $this->get_items();
    }

    /**
     * get_parent
     * Return parent `object_type`, `object_id`; null otherwise.
     */
    public function get_parent(): ?array
    {
        return null;
    }

    public function get_parent_fullname(): string
    {
        return '';
    }

    public function get_user_owner(): ?int
    {
        return $this->user;
    }

    public function getFullname(): string
    {
        return scrub_out($this->get_fullname());
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * has_access
     * This function returns true or false if the current user
     * has access to this playlist
     */
    public function has_access(?User $user = null): bool
    {
        if (
            $user instanceof User
            && (
                $user->access === AccessLevelEnum::ADMIN->value
                || $this->user === $user->getId()
            )
        ) {
            return true;
        }

        if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)) {
            return true;
        }

        if (!Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) {
            return false;
        }

        return (
            Core::get_global('user') instanceof User
            && $this->user == Core::get_global('user')->id
        );
    }

    /**
     * does the item have art?
     */
    public function has_art(): bool
    {
        if ($this->has_art === null) {
            $this->has_art = ($this instanceof Search)
                ? Art::has_db($this->id, 'search')
                : Art::has_db($this->id, 'playlist');
        }

        return $this->has_art ?? false;
    }

    /**
     * has_collaborate
     * This function returns true or false if the current user
     * has access to collaborate (Add/remove items) for this playlist
     */
    public function has_collaborate(?User $user = null): bool
    {
        if ($this->has_access($user)) {
            return true;
        }

        $user = ($user instanceof User)
            ? $user
            : Core::get_global('user');

        return (
            $user instanceof User
            && !empty($this->collaborate)
            && in_array($user->getId(), array_map('intval', explode(',', (string) $this->collaborate)))
        );
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    abstract public function set_last(int $count, string $column): void;

    /**
     * update
     * This function takes a key'd array of data and runs updates
     * @param null|array{
     *     name?: ?string,
     *     playlist_type?: ?string,
     *     playlist_user?: ?int,
     *     collaborate?: null|list<string>,
     *     last_count?: ?int,
     *     last_duration?: ?int,
     *     random?: ?int,
     *     limit?: int,
     *     operator?: int,
     * } $data
     */
    public function update(?array $data = null): int
    {
        if ($this->isNew() || $data === null) {
            return 0;
        }

        if (isset($data['name']) && $data['name'] != $this->name) {
            $this->update_item('name', $data['name']);
        }

        if (isset($data['playlist_type']) && $data['playlist_type'] != $this->type) {
            $this->update_item('type', $data['playlist_type']);
        }

        if (isset($data['playlist_user']) && $data['playlist_user'] != $this->user) {
            $this->user     = (int) $data['playlist_user'];
            $this->username = User::get_username($this->user);
            $this->update_item('user', $data['playlist_user']);
            $this->update_item('username', $this->username);
        }

        if ($this instanceof Search) {
            $random = $data['random'] ?? $this->random;
            if ($random != $this->random) {
                $this->update_item('random', $random);
            }

            $limit = $data['limit'] ?? $this->limit;
            if ($limit != $this->limit) {
                $this->update_item('limit', $limit);
            }

            if (!empty($data['operator'])) {
                $this->update_item('logic_operator', $data['operator']);
            }

            $this->update_item('rules', json_encode($this->rules) ?: null);
        }

        $new_list    = (!empty($data['collaborate'])) ? $data['collaborate'] : [];
        $collaborate = (!empty($new_list)) ? implode(',', $new_list) : '';
        if ($collaborate != $this->collaborate) {
            $playlist_id = ($this instanceof Search)
                ? 'smart_' . $this->id
                : $this->id;
            $this->_update_collaborate($new_list, $playlist_id);
        }

        if (isset($data['last_count']) && $data['last_count'] != $this->last_count) {
            $this->set_last($data['last_count'], 'last_count');
        }

        if (isset($data['last_duration']) && $data['last_duration'] != $this->last_duration) {
            $this->set_last($data['last_duration'], 'last_duration');
        }

        return $this->id;
    }

    /**
     * update_item
     * This is the generic update function, it does the escaping and error checking
     */
    abstract public function update_item(string $field, int|string $value): bool;

    /**
     * _update_collaborate
     * This updates playlist collaborators, it calls the generic update_item function
     * @param string[] $new_list
     */
    private function _update_collaborate(array $new_list, int|string $playlist_id): void
    {
        /** @var int[] $ids */
        $ids = array_filter(
            array_map('intval', $new_list)
        );

        $collaborate = implode(',', $ids);
        if ($this->update_item('collaborate', $collaborate)) {
            $sql = (empty($collaborate))
                ? "DELETE FROM `user_playlist_map` WHERE `playlist_id` = ?;"
                : "DELETE FROM `user_playlist_map` WHERE `playlist_id` = ? AND `user_id` NOT IN (" . $collaborate . ");";
            Dba::write($sql, [$playlist_id]);

            foreach ($new_list as $user_id) {
                $sql = "INSERT IGNORE INTO `user_playlist_map` (`playlist_id`, `user_id`) VALUES (?, ?);";
                Dba::write($sql, [$playlist_id, $user_id]);
            }
        }
    }

    private function getPlaylistArtBuilder(): PlaylistArtBuilderInterface
    {
        global $dic;

        return $dic->get(PlaylistArtBuilderInterface::class);
    }
}

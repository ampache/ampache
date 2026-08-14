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
use Ampache\Module\Database\database_object;
use Ampache\Module\System\Core;
use Ampache\Repository\MoodRepositoryInterface;

/**
 * Mood Class
 *
 * The same shape a genre has in `tag`/`tag_map`, for the mood a file is tagged with (id3v2 `TMOO`, vorbis/APE `MOOD`).
 *
 * A mood has no hidden form and nothing merges into it.
 */
class Mood extends database_object implements GarbageCollectibleInterface
{
    public const int NO_USER = 0;

    /**
     * What `mood_map`.`object_type` accepts. A type outside this list would be refused by the column.
     */
    public const array OBJECT_TYPES = [
        'album',
        'album_disk',
        'artist',
        'podcast',
        'podcast_episode',
        'song',
        'video',
    ];
    protected const string DB_TABLENAME = 'mood';

    public int $album    = 0;
    public int $artist   = 0;
    public int $id       = 0;
    public ?string $name = null;
    public int $song     = 0;
    public int $video    = 0;

    /**
     * constructor
     * This takes a mood id and returns all of the relevant information
     */
    public function __construct(?int $mood_id = 0)
    {
        if (!$mood_id) {
            return;
        }

        $info = $this->get_info($mood_id, static::DB_TABLENAME);
        if ($info === []) {
            return;
        }

        $this->album  = (int) ($info['album'] ?? 0);
        $this->artist = (int) ($info['artist'] ?? 0);
        $this->id     = (int) ($info['id'] ?? 0);
        $this->name   = $info['name'] ?? null;
        $this->song   = (int) ($info['song'] ?? 0);
        $this->video  = (int) ($info['video'] ?? 0);
    }

    /**
     * add
     * Creates the mood if it is new, then maps it onto the object
     */
    public static function add(string $type, int $object_id, string $value, int $user_id = self::NO_USER): int
    {
        if (!self::_is_mappable($type)) {
            return 0;
        }

        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $mood_id = self::mood_exists($value);
        if ($mood_id === 0) {
            debug_event(self::class, 'Adding new mood {' . $value . '}', 5);
            $mood_id = (int) self::_add_mood($value);
        }

        if ($mood_id === 0) {
            debug_event(self::class, 'Error unable to create mood value:' . $value . ' unknown error', 1);

            return 0;
        }

        if (!self::_mood_map_exists($type, $object_id, $mood_id, $user_id)) {
            return self::_add_mood_map($type, $object_id, $mood_id, $user_id);
        }

        return 0;
    }

    /**
     * build_cache
     * Caches a set of moods in one query rather than one per object
     *
     * @param list<int> $ids
     */
    public static function build_cache(array $ids): bool
    {
        if ($ids === []) {
            return false;
        }

        // with the cache off these rows are discarded and the per-object queries still run, so this is a net loss
        if (!database_object::isCacheEnabled()) {
            return false;
        }

        foreach (self::getMoodRepository()->getRowsByIds($ids) as $row) {
            parent::add_to_cache('mood', (int) $row['id'], $row);
        }

        return true;
    }

    /**
     * clean_to_existing
     * Narrows a list to the moods that already exist, so an uploader editing their own item cannot coin new ones
     */
    public static function clean_to_existing(string $moods_comma): string
    {
        $existing = [];
        foreach (self::_clean_list($moods_comma) as $mood) {
            if (self::mood_exists($mood) > 0) {
                $existing[] = $mood;
            }
        }

        return implode(',', $existing);
    }

    /**
     * construct_from_name
     * Builds the mood from its name rather than its id
     */
    public static function construct_from_name(string $name): Mood
    {
        return new Mood(self::mood_exists($name));
    }

    /**
     * garbage_collection
     * Drops the maps of objects that are gone, then the moods nothing points at
     */
    public static function garbage_collection(): void
    {
        self::getMoodRepository()->collectGarbage();
    }

    /**
     * get_display
     * Returns a csv formatted version of the moods that we are given
     *
     * @param array<int, array{id: int, name: string, user?: int, count?: int}> $moods
     */
    public static function get_display(array $moods, ?bool $link = false, ?string $filter_type = ''): string
    {
        if ($moods === []) {
            return '';
        }

        $web_path = AmpConfig::get_web_path('/client');
        $results  = '';

        foreach ($moods as $value) {
            if ($link) {
                $results .= '<a href="' . $web_path . '/browse.php?action=mood&show_mood=' . $value['id'] . (empty($filter_type) ? '' : '&type=' . $filter_type) . '" title="' . scrub_out($value['name']) . '">';
            }

            $results .= scrub_out($value['name']);
            if ($link) {
                $results .= '</a>';
            }

            // only when emitting html, so the plain form stays usable as an input value
            if ($link && (int) ($value['user'] ?? self::NO_USER) > self::NO_USER) {
                $results .= '<span class="user-set" title="' . scrub_out(T_('User set')) . '">*</span>';
            }

            $results .= ', ';
        }

        return rtrim($results, ', ');
    }

    /**
     * get_object_moods
     * Every mood mapped onto a type, or onto one object of it
     *
     * @return list<array{id: int, name: string, user: int}>
     */
    public static function get_object_moods(string $type, ?int $object_id = null): array
    {
        if (!self::_is_mappable($type)) {
            return [];
        }

        return self::getMoodRepository()->getObjectMoods($type, $object_id);
    }

    /**
     * get_top_moods
     * The moods mapped onto one object, heaviest first
     *
     * `user` is the owner of the map: 0 when the mood came from the file tags, otherwise whoever set it by hand.
     *
     * @return list<array{id: int, name: string, user: int, count: int}>
     */
    public static function get_top_moods(string $type, int $object_id, ?int $limit = 10): array
    {
        if (!self::_is_mappable($type)) {
            return [];
        }

        return self::getMoodRepository()->getTopMoods($type, $object_id, (int) $limit);
    }

    /**
     * Move the maps of one object onto another
     */
    public static function migrate(string $object_type, int $old_object_id, int $new_object_id): void
    {
        self::getMoodRepository()->migrateMaps($object_type, $old_object_id, $new_object_id);
    }

    /**
     * mood_exists
     * The id of the mood with this name, 0 when there is none
     */
    public static function mood_exists(string $value): int
    {
        if (parent::is_cached('mood_name', $value)) {
            return (int) (parent::get_from_cache('mood_name', $value)[0] ?? 0);
        }

        $mood_id = self::getMoodRepository()->findIdByName($value) ?? 0;

        parent::add_to_cache('mood_name', $value, [$mood_id]);

        return $mood_id;
    }

    /**
     * update_mood_list
     * Update the moods of an object from a comma separated list (ex. mood1,mood2,mood3)
     *
     * @param null|int $user_id Who the change belongs to; null resolves to nobody for a tag read and to the
     *                          person acting otherwise
     * @param bool $from_file_tags True when the list was read out of the file, which is what makes a mood
     *                             somebody set by hand survive it
     */
    public static function update_mood_list(
        string $moods_comma,
        string $object_type,
        int $object_id,
        bool $overwrite,
        ?int $user_id = null,
        bool $from_file_tags = false,
    ): bool {
        if (!self::_is_mappable($object_type)) {
            return false;
        }

        $user_id ??= ($from_file_tags)
            ? self::NO_USER
            : self::_getCurrentUserId();

        if ($moods_comma === '') {
            // a file with no mood must not take away what somebody set by hand; clearing the field yourself does
            return self::_remove_all_maps($object_type, $object_id, ($from_file_tags) ? self::NO_USER : null);
        }

        $editedMoods = self::_clean_list($moods_comma);

        $change        = false;
        $current_moods = self::get_top_moods($object_type, $object_id, 0);
        foreach ($current_moods as $cmv) {
            if ($cmv['id'] < 1) {
                continue;
            }

            $found = false;
            foreach ($editedMoods as $key => $mood_name) {
                // `name` is a _ci column, fold the same way
                if (mb_strtolower((string) $cmv['name']) === mb_strtolower($mood_name)) {
                    $found = true;
                    unset($editedMoods[$key]);
                    break;
                }
            }

            if ($found) {
                continue;
            }

            // a hand-set mood is not in the file, so treat it as present and leave the removing to a manual edit
            if ($from_file_tags && $cmv['user'] > self::NO_USER) {
                continue;
            }

            if ($overwrite) {
                debug_event(self::class, 'update_mood_list ' . $object_type . ' delete {' . $cmv['name'] . '}', 5);
                // an edit removes the mood whoever set it, so a manager is not locked out by a user's choice
                $mood = new Mood($cmv['id']);
                $mood->remove_map($object_type, $object_id, ($from_file_tags) ? self::NO_USER : null);
                $change = true;
            }
        }

        foreach ($editedMoods as $mood_name) {
            if ($mood_name !== '') {
                debug_event(self::class, 'update_mood_list ' . $object_type . ' add {' . $mood_name . '}', 5);
                self::add($object_type, $object_id, $mood_name, $user_id);
                $change = true;
            }
        }

        return $change;
    }

    /**
     * add_mood
     * Creates the mood row itself
     */
    private static function _add_mood(string $value): ?int
    {
        if ($value === '') {
            return null;
        }

        $insert_id = self::getMoodRepository()->create($value);

        parent::add_to_cache('mood_name', $value, [$insert_id]);

        return $insert_id;
    }

    /**
     * add_mood_map
     * Maps an existing mood onto an object
     */
    private static function _add_mood_map(string $type, int $object_id, int $mood_id, int $user_id = self::NO_USER): int
    {
        if (!self::_is_mappable($type) || $mood_id < 1 || $object_id < 1) {
            return 0;
        }

        $moodRepository = self::getMoodRepository();
        $insert_id      = $moodRepository->addMap($mood_id, $type, $object_id, $user_id);

        // only the four types with a counter column on `mood` get counted; the map itself takes more than those
        $countType = MoodCountTypeEnum::tryFrom($type);
        if ($countType instanceof MoodCountTypeEnum) {
            $moodRepository->incrementCount($mood_id, $countType);
        }

        return $insert_id;
    }

    /**
     * Splits the comma separated list an edit or a tag read hands over
     *
     * @return list<string>
     */
    private static function _clean_list(string $moods_comma): array
    {
        $parts = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', $moods_comma) ?: [];

        $moods = [];
        foreach ($parts as $mood) {
            $mood = trim($mood);
            if ($mood !== '' && !in_array($mood, $moods, true)) {
                $moods[] = $mood;
            }
        }

        return $moods;
    }

    /**
     * The user a manual mood change is attributed to, or nobody when there is no session (a cli scan, a cron)
     */
    private static function _getCurrentUserId(): int
    {
        $user = Core::get_global('user');

        return ($user instanceof User && $user->id > 0)
            ? $user->id
            : self::NO_USER;
    }

    /**
     * Whether `mood_map` will take this object type at all
     */
    private static function _is_mappable(string $type): bool
    {
        if (in_array($type, self::OBJECT_TYPES, true)) {
            return true;
        }

        debug_event(self::class, $type . ' does not take a mood.', 3);

        return false;
    }

    /**
     * mood_map_exists
     * Whether this object already carries this mood for this owner
     */
    private static function _mood_map_exists(string $type, int $object_id, int $mood_id, int $user_id = self::NO_USER): bool
    {
        if (!self::_is_mappable($type)) {
            return false;
        }

        return self::getMoodRepository()->mapExists($type, $object_id, $mood_id, $user_id);
    }

    /**
     * remove_all_maps
     * Drops every mood from an object. A null user removes them whoever set them.
     */
    private static function _remove_all_maps(string $object_type, int $object_id, ?int $user_id = null): bool
    {
        if (!self::_is_mappable($object_type)) {
            return false;
        }

        $moodRepository = self::getMoodRepository();
        // nothing was mapped, nothing to recount
        if ($moodRepository->removeAllMaps($object_type, $object_id, $user_id) === 0) {
            return true;
        }

        $countType = MoodCountTypeEnum::tryFrom($object_type);
        if ($countType instanceof MoodCountTypeEnum) {
            $moodRepository->recountType($countType);
        }

        return true;
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getMoodRepository(): MoodRepositoryInterface
    {
        global $dic;

        return $dic->get(MoodRepositoryInterface::class);
    }

    public function get_fullname(): ?string
    {
        return $this->name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * remove_map
     * Drops this mood from an object. A null user removes it whoever set it.
     */
    public function remove_map(string $type, int $object_id, ?int $user_id = null): bool
    {
        if (!self::_is_mappable($type)) {
            return false;
        }

        $moodRepository = self::getMoodRepository();
        $moodRepository->removeMap($this->id, $type, $object_id, $user_id);

        $countType = MoodCountTypeEnum::tryFrom($type);
        if ($countType instanceof MoodCountTypeEnum) {
            $moodRepository->decrementCount($this->id, $countType);
        }

        return true;
    }
}

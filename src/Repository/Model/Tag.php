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
use Ampache\Module\Art\Art;
use Ampache\Module\Database\database_object;
use Ampache\Module\System\Core;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Repository\TagRepositoryInterface;

/**
 * Tag Class
 *
 * This class handles all of the genre related operations
 *
 */
class Tag extends database_object implements library_item, displayable_item, container_item, GarbageCollectibleInterface
{
    public const int NO_USER            = 0;
    protected const string DB_TABLENAME = 'tag';

    public int $album       = 0;
    public int $artist      = 0;
    public int $id          = 0;
    public int $is_hidden   = 0;
    public ?string $name    = null;
    public int $song        = 0;
    public int $video       = 0;
    private ?string $f_link = null;
    private ?string $link   = null;

    /**
     * constructor
     * This takes a tag id and returns all of the relevant information
     */
    public function __construct(?int $tag_id = 0)
    {
        if (!$tag_id) {
            return;
        }

        $info = $this->get_info($tag_id, static::DB_TABLENAME);
        if ($info === []) {
            return;
        }

        $this->album     = (int) ($info['album'] ?? 0);
        $this->artist    = (int) ($info['artist'] ?? 0);
        $this->id        = (int) ($info['id'] ?? 0);
        $this->is_hidden = (int) ($info['is_hidden'] ?? 0);
        $this->name      = $info['name'] ?? null;
        $this->song      = (int) ($info['song'] ?? 0);
        $this->video     = (int) ($info['video'] ?? 0);
    }

    /**
     * add
     * This is a wrapper function, it figures out what we need to add, be it a tag
     * and map, or just the mapping
     */
    public static function add(string $type, int $object_id, string $value, int $user_id = self::NO_USER): int
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return 0;
        }

        $cleaned_value = str_replace('Folk, World, & Country', 'Folk World & Country', $value);
        if ((string) $cleaned_value === '') {
            return 0;
        }

        // Check and see if the tag exists, if not create it, we need the tag id from this
        if (($tag_id = self::tag_exists($cleaned_value)) === 0) {
            debug_event(self::class, 'Adding new tag {' . $cleaned_value . '}', 5);
            $tag_id = self::add_tag($cleaned_value);
        }

        if (!$tag_id) {
            debug_event(self::class, 'Error unable to create tag value:' . $cleaned_value . ' unknown error', 1);

            return 0;
        }

        // We've got the tag id, let's see if it's already got a map, if not then create the map and return the value
        if (!self::tag_map_exists($type, $object_id, $tag_id, $user_id)) {
            return self::add_tag_map($type, $object_id, $tag_id, $user_id);
        }

        return 0;
    }

    /**
     * build_cache
     * This takes an array of object ids and caches all of their information
     * in a single query, cuts down on the connections
     * @param int[]|string[] $ids
     */
    public static function build_cache(array $ids): bool
    {
        if (empty($ids)) {
            return false;
        }

        // with the cache off these rows are discarded and the per-object queries still run, so this is a net loss
        if (!database_object::isCacheEnabled()) {
            return false;
        }

        foreach (self::getTagRepository()->getRowsByIds($ids) as $row) {
            parent::add_to_cache('tag', (int) $row['id'], $row);
        }

        return true;
    }

    /**
     * clean_to_existing
     * Clean tag list to existing tag list only
     * @param string[]|string $tags
     * @return string[]|string
     */
    public static function clean_to_existing(array|string $tags): array|string
    {
        if (is_array($tags)) {
            $taglist = $tags;
        } else {
            $filterfolk  = str_replace('Folk, World, & Country', 'Folk World & Country', $tags);
            $filterunder = str_replace('_', ', ', $filterfolk);
            $filter      = str_replace(';', ', ', $filterunder);
            $filter_list = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', $filter);
            $taglist     = (is_array($filter_list)) ? array_unique($filter_list) : [];
        }

        $ret = [];
        foreach ($taglist as $tag) {
            $tag = trim((string) $tag);
            if (
                $tag !== ''
                && $tag !== '0'
                && self::tag_exists($tag)
            ) {
                $ret[] = $tag;
            }
        }

        return (is_array($tags)
            ? $ret
            : implode(",", $ret));
    }

    /**
     * construct_from_name
     * This attempts to construct the tag from a name, rather then the ID
     */
    public static function construct_from_name(string $name): Tag
    {
        $tag_id = self::tag_exists($name);

        return new Tag($tag_id);
    }

    /**
     * garbage_collection
     *
     * This cleans out tag_maps that are obsolete and then removes tags that
     * have no maps.
     */
    public static function garbage_collection(): void
    {
        self::getTagRepository()->collectGarbage();
    }

    /**
     * get_display
     * This returns a csv formatted version of the tags that we are given
     * it also takes a type so that it knows how to return it, this is used
     * by the formatting functions of the different objects
     * @param array<int, array{id: int, name: string, is_hidden: int, user?: int, count: int}> $tags
     */
    public static function get_display(array $tags, ?bool $link = false, ?string $filter_type = ''): string
    {
        //debug_event(self::class, 'Get display tags called...', 5);
        if (empty($tags)) {
            return '';
        }

        $web_path = AmpConfig::get_web_path();

        $results = '';

        // Iterate through the tags, format them according to type and element id
        foreach ($tags as $value) {
            if ($link) {
                $results .= '<a href="' . $web_path . '/browse.php?action=tag&show_tag=' . $value['id'] . (empty($filter_type) ? '' : '&type=' . $filter_type) . '" title="' . scrub_out($value['name']) . '">';
            }

            $results .= scrub_out($value['name']);
            if ($link) {
                $results .= '</a>';
            }

            // only when emitting html; the plain form feeds edit inputs and the daap and upnp genre fields, where a span would land in the value
            if ($link && (int) ($value['user'] ?? self::NO_USER) > self::NO_USER) {
                $results .= '<span class="user-set" title="' . scrub_out(T_('User set')) . '">*</span>';
            }

            $results .= ', ';
        }

        return rtrim($results, ', ');
    }

    /**
     * get_merged_count
     */
    public static function get_merged_count(): int
    {
        return self::getTagRepository()->getMergedCount();
    }

    /**
     * get_object_tags
     * Display all tags that apply to matching target type of the specified id
     * @return array<int, array{id: int, name: string, is_hidden: int, user: int}>
     */
    public static function get_object_tags(string $type, ?int $object_id = null): array
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return [];
        }

        return self::getTagRepository()->getObjectTags($type, $object_id);
    }

    /**
     * get_tag_objects
     * This gets the objects from a specified tag and returns an array of object ids, nothing more
     * @return int[]
     */
    public static function get_tag_objects(string $type, int $tag_id, int $count = 0, int $offset = 0, int $catalog_id = 0): array
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return [];
        }

        return self::getTagRepository()->getTagObjects($type, $tag_id, $count, $offset, $catalog_id);
    }

    /**
     * get_tags
     * This is a non-object non type dependent function that just returns tags
     * we've got, it can take filters (this is used by the tag cloud)
     * @return array<int, array{id: int, name: string, is_hidden: int, count: int}>
     */
    public static function get_tags(?string $type = '', ?int $limit = 0, ?string $order = 'count'): array
    {
        $cacheType = (empty($type)) ? 'all' : $type;
        if (parent::is_cached('tags_list', $cacheType)) {
            //debug_event(self::class, 'Tags list found into cache memory!', 5);
            return parent::get_from_cache('tags_list', $cacheType);
        }

        $results = self::getTagRepository()->getTags($type, (int) $limit, (string) $order);

        parent::add_to_cache('tags_list', $cacheType, $results);

        return $results;
    }

    /**
     * get_top_tags
     * This gets the top tags for the specified object using limit
     *
     * `user` is the owner of the map: 0 for a genre read out of the file tags, otherwise whoever set it by hand.
     *
     * @return array<int, array{id: int, name: string, is_hidden: int, user: int, count: int}>
     */
    public static function get_top_tags(string $type, int $object_id, ?int $limit = 10): array
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return [];
        }

        return self::getTagRepository()->getTopTags($type, $object_id, (int) $limit);
    }

    /**
     * Migrate an object associate stats to a new object
     */
    public static function migrate(string $object_type, int $old_object_id, int $new_object_id): void
    {
        self::getTagRepository()->migrateMaps($object_type, $old_object_id, $new_object_id);
    }

    /**
     * tag_exists
     * This checks to see if a tag exists, this has nothing to do with objects or maps
     */
    public static function tag_exists(string $value): int
    {
        if (parent::is_cached('tag_name', $value)) {
            return (int) (parent::get_from_cache('tag_name', $value))[0];
        }

        $tag_id = self::getTagRepository()->findIdByName($value);
        if ($tag_id !== null) {
            parent::add_to_cache('tag_name', $value, [$tag_id]);

            return $tag_id;
        }

        return 0;
    }

    /**
     * update_tag_list
     * Update the tags list based on a comma-separated list
     *  (ex. tag1,tag2,tag3,..)
     */
    public static function update_tag_list(
        string $tags_comma,
        string $object_type,
        int $object_id,
        bool $overwrite,
        ?int $user_id = null,
        bool $from_file_tags = false,
    ): bool {
        // a tag read belongs to nobody; anything else is attributed to the person acting, which is what makes it survive the next read
        $user_id ??= ($from_file_tags)
            ? self::NO_USER
            : self::getCurrentUserId();

        if (!strlen($tags_comma) > 0) {
            // a file with no genre must not take away what somebody set by hand; clearing the field yourself does
            return self::remove_all_maps($object_type, $object_id, ($from_file_tags) ? self::NO_USER : null);
        }

        debug_event(self::class, sprintf('update_tag_list %s {%d}', $object_type, $object_id), 5);
        // tags from your file can be in a terrible format
        $filterfolk  = str_replace('Folk, World, & Country', 'Folk World & Country', $tags_comma);
        $filterunder = str_replace('_', ', ', $filterfolk);
        $filter      = str_replace(';', ', ', $filterunder);
        $filter_list = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', $filter);
        $editedTags  = (is_array($filter_list)) ? array_unique($filter_list) : [];

        $change       = false;
        $current_tags = self::get_top_tags($object_type, $object_id, 0);
        foreach ($current_tags as $ctv) {
            $found = false;
            if ($ctv['id'] > 0) {
                $ctag = new Tag($ctv['id']);
                if ($ctag->isNew()) {
                    continue;
                }

                //debug_event(self::class, 'update_tag_list ' . $object_type . ' current_tag ' . print_r($ctv, true), 5);
                foreach ($editedTags as $tag_name) {
                    if (strtolower((string) $ctag->name) === strtolower($tag_name)) {
                        $found = true;
                        break;
                    }

                    // check if this thing has been renamed into something else
                    $merged = self::construct_from_name($tag_name);
                    if ($merged->id && $merged->is_hidden && $merged->has_merge((string) $ctag->name)) {
                        $found = true;
                        break;
                    }
                }

                if ($found) {
                    //debug_event(self::class, 'update_tag_list ' . $object_type . ' matched {' . $ctag->id . '} to ' . $tag_name, 5);
                    if (($key = array_search((string) $ctag->name, $editedTags)) !== false) {
                        unset($editedTags[$key]);
                    }
                }

                // a hand-set genre is not in the file, so treat it as present and leave the removing to a manual edit
                if (
                    !$found
                    && $from_file_tags
                    && $ctv['user'] > self::NO_USER
                ) {
                    continue;
                }

                if (
                    !$found
                    && $overwrite
                ) {
                    debug_event(self::class, 'update_tag_list ' . $object_type . ' delete {' . $ctag->name . '}', 5);
                    // an edit removes the genre whoever set it, so a manager is not locked out by a user's choice
                    $ctag->remove_map($object_type, $object_id, ($from_file_tags) ? self::NO_USER : null);
                    $change = true;
                }
            }
        }

        // Look if we need to add some new tags
        foreach ($editedTags as $tag_name) {
            if ($tag_name != '') {
                debug_event(self::class, 'update_tag_list ' . $object_type . ' add {' . $tag_name . '}', 5);
                self::add($object_type, $object_id, $tag_name, $user_id);
                $change = true;
            }
        }

        return $change;
    }

    /**
     * add_tag
     * This function adds a new tag, for now we're going to limit the tagging a bit
     */
    private static function add_tag(string $value): ?int
    {
        if ($value === '') {
            return null;
        }

        $insert_id = self::getTagRepository()->create($value);

        parent::add_to_cache('tag_name', $value, [$insert_id]);

        return $insert_id;
    }

    /**
     * add_tag_map
     * This adds a specific tag to the map for specified object
     */
    private static function add_tag_map(string $type, int|string $object_id, int|string $tag_id, int $user_id = self::NO_USER): int
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            debug_event(self::class, $type . " is not a library item.", 3);

            return 0;
        }

        $tag_id  = (int) ($tag_id);
        $item_id = (int) ($object_id);

        if (!$tag_id || !$item_id) {
            return 0;
        }

        // If tag merged to another one, add reference to the merge destination
        $parent = new Tag($tag_id);
        $merges = $parent->get_merged_tags();
        if ($parent->is_hidden === 0) {
            $merges[] = ['id' => $parent->id, 'name' => $parent->name];
        }

        $tagRepository = self::getTagRepository();
        // only the four types with a counter column on `tag` get counted; the map itself accepts any library item
        $countType = TagCountTypeEnum::tryFrom($type);
        $insert_id = 0;
        foreach ($merges as $tag) {
            $insert_id = $tagRepository->addMap((int) $tag['id'], $type, $item_id, $user_id);
            parent::add_to_cache(
                'tag_map_' . $type,
                $insert_id,
                [
                    'tag_id' => $tag_id,
                    'user' => $user_id,
                    'object_type' => $type,
                    'object_id' => $item_id
                ]
            );

            if ($countType instanceof TagCountTypeEnum) {
                $tagRepository->incrementCount((int) $tag['id'], $countType);
            }
        }

        return $insert_id;
    }

    /**
     * The user a manual genre change is attributed to, or nobody when there is no session (a cli scan, a cron)
     */
    private static function getCurrentUserId(): int
    {
        $user = Core::get_global('user');

        return ($user instanceof User && $user->id > 0)
            ? $user->id
            : self::NO_USER;
    }

    /**
     * @deprecated inject dependency
     */
    private static function getTagRepository(): TagRepositoryInterface
    {
        global $dic;

        return $dic->get(TagRepositoryInterface::class);
    }

    /**
     * remove_all_maps
     * Clear all the tags from an object when there isn't anything there
     */
    private static function remove_all_maps(string $object_type, int $object_id, ?int $user_id = null): bool
    {
        if (!InterfaceImplementationChecker::is_library_item($object_type)) {
            return false;
        }

        $tagRepository = self::getTagRepository();
        $tagRepository->removeAllMaps($object_type, $object_id, $user_id);

        // only the four counted types have a column to put back in step; the rest never had one to begin with
        $countType = TagCountTypeEnum::tryFrom($object_type);
        if ($countType instanceof TagCountTypeEnum) {
            $tagRepository->recountType($countType);
        }

        return true;
    }

    /**
     * tag_map_exists
     * This looks to see if the current mapping of the current object exists
     */
    private static function tag_map_exists(string $type, int $object_id, int $tag_id, int $user_id = self::NO_USER): bool
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            debug_event(self::class, 'Requested type is not a library item.', 3);

            return false;
        }

        return self::getTagRepository()->mapExists($type, $object_id, $tag_id, $user_id);
    }

    /**
     * delete
     *
     * Delete the tag and all maps
     */
    public function delete(): void
    {
        self::getTagRepository()->delete($this->id);

        // Call the garbage collector to clean everything
        self::garbage_collection();

        parent::clear_cache();
    }

    /**
     * display_art
     * @param array{width: int, height: int} $size
     */
    public function display_art(array $size, bool $force = false): void
    {
        if ($this->has_art() || $force) {
            Art::display('tag', $this->id, (string) $this->get_fullname(), $size);
        }
    }

    /**
     * get_default_art_kind
     */
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
     * Get item f_link.
     */
    public function get_f_link(?string $title = null): string
    {
        // don't do anything if it's formatted
        if ($this->f_link === null) {
            $this->f_link = "<a href=\"" . $this->get_link() . "\" title=\"" . scrub_out($this->get_fullname()) . "\">" . scrub_out($title ?? $this->get_fullname()) . "</a>";
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
     * get_fullname
     */
    public function get_fullname(): ?string
    {
        return $this->name;
    }

    /**
     * Get item keywords for metadata searches.
     * @return array{tag: array{important: true, label: string, value: string}}
     */
    public function get_keywords(): array
    {
        return [
            'tag' => [
                'important' => true,
                'label' => T_('Genre'),
                'value' => (string) $this->name,
            ]
        ];
    }

    /**
     * Get item link.
     */
    public function get_link(): string
    {
        // don't do anything if it's formatted
        if ($this->link === null) {
            $this->link = AmpConfig::get_web_path() . '/browse.php?action=tag&show_tag=' . $this->id;
        }

        return $this->link;
    }

    /**
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function get_medias(?string $filter_type = null): array
    {
        // an unasked genre expands to its songs, the way an album does, so it can be played and curated
        $objectType = $filter_type ?? 'song';

        $medias = [];
        foreach (self::get_tag_objects($objectType, $this->id) as $object_id) {
            $medias[] = ['object_type' => LibraryItemEnum::from($objectType), 'object_id' => $object_id];
        }

        return $medias;
    }

    /**
     * get_merged_tags
     * Get merged tags to this tag.
     * @return array<int, array{id: int, name: string, is_hidden: int, count: int}>
     */
    public function get_merged_tags(): array
    {
        return self::getTagRepository()->getMergedTags($this->id);
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
        return null;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMediaType(): LibraryItemEnum
    {
        return LibraryItemEnum::TAG;
    }

    public function has_art(): bool
    {
        return Art::has_db($this->id, 'tag');
    }

    /**
     * has_merge
     * Get merged tags to this tag.
     */
    public function has_merge(string $name): bool
    {
        return in_array($name, self::getTagRepository()->getMergedNames($this->id));
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * merge
     * merges this tag to another one.
     */
    public function merge(int $merge_to, bool $is_persistent): void
    {
        if ($this->id != $merge_to) {
            debug_event(self::class, 'Merging tag ' . $this->id . ' into ' . $merge_to . ')...', 5);

            $tagRepository = self::getTagRepository();
            $tagRepository->mergeInto($this->id, $merge_to);
            if ($is_persistent) {
                $tagRepository->persistMerge($this->id, $merge_to);
            }
        }
    }

    /**
     * remove_map
     * This will only remove tag maps for the current user
     */
    public function remove_map(string $type, int $object_id, ?int $user_id = null): bool
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return false;
        }

        $tagRepository = self::getTagRepository();
        $tagRepository->removeMap($this->id, $type, $object_id, $user_id);

        $countType = TagCountTypeEnum::tryFrom($type);
        if ($countType instanceof TagCountTypeEnum) {
            $tagRepository->decrementCount($this->id, $countType);
        }

        return true;
    }

    /**
     * remove_merges
     * Remove merged tags from this tag.
     */
    public function remove_merges(): void
    {
        self::getTagRepository()->removeMerges($this->id);
    }

    /**
     * update
     * Update the name of the tag
     */
    public function update(array $data): ?int
    {
        if ((string) $data['name'] === '') {
            return null;
        }

        $name      = $data['name'] ?? $this->name;
        $is_hidden = (array_key_exists('is_hidden', $data))
            ? (int) $data['is_hidden']
            : 0;

        if ($name != $this->name) {
            debug_event(self::class, 'Updating tag {' . $this->id . '} with name {' . $data['name'] . '}...', 5);
            self::getTagRepository()->rename($this->id, (string) $name);
        }

        if ($is_hidden !== $this->is_hidden) {
            debug_event(self::class, 'Hidden tag {' . $this->id . '} with status {' . $is_hidden . '}...', 5);
            self::getTagRepository()->setHidden($this->id, $is_hidden, $is_hidden == 1 && $this->is_hidden == 0);
            // if you had previously hidden this tag then remove the merges too
            if ($is_hidden == 0 && $this->is_hidden == 1) {
                debug_event(self::class, 'Unhiding tag {' . $this->id . '} removing all previous merges', 5);
                $this->remove_merges();
            }

            $this->is_hidden = $is_hidden;
        }

        if (array_key_exists('edit_tags', $data) && $data['edit_tags']) {
            $filterfolk  = str_replace('Folk, World, & Country', 'Folk World & Country', (string) $data['edit_tags']);
            $filterunder = str_replace('_', ', ', $filterfolk);
            $filter      = str_replace(';', ', ', $filterunder);
            $filter_list = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', $filter);
            $tag_names   = (is_array($filter_list)) ? array_unique($filter_list) : [];

            // remove merges that don't exist before adding new ones
            $this->remove_merges();

            // apply the new merge list
            foreach ($tag_names as $tag) {
                $merge_to = self::construct_from_name($tag);
                if ($merge_to->id == 0) {
                    self::add_tag($tag);
                    $merge_to = self::construct_from_name($tag);
                }

                $this->merge($merge_to->id, array_key_exists('merge_persist', $data));
            }

            if (!array_key_exists('keep_existing', $data)) {
                $tagRepository = self::getTagRepository();
                $tagRepository->removeMapsForTag($this->id);
                if (!array_key_exists('merge_persist', $data)) {
                    $this->delete();
                } else {
                    $tagRepository->setHidden($this->id, 1, false);
                }
            }
        }

        return $this->id;
    }
}

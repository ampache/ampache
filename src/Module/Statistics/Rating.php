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

namespace Ampache\Module\Statistics;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Database\database_object;
use Ampache\Module\System\Core;
use Ampache\Module\System\Plugin\Plugin;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Module\User\Activity\UserActivityPosterInterface;
use Ampache\Plugin\AmpacheRatingMatch;
use Ampache\Repository\Model\User;
use Ampache\Repository\RatingRepositoryInterface;
use Exception;

/**
 * This tracks ratings for songs, albums, artists, videos...
 */
class Rating extends database_object
{
    protected const string DB_TABLENAME = 'rating';
    private const array RATING_TYPES    = [
        'album_disk',
        'album',
        'artist',
        'collection',
        'folder',
        'live_stream',
        'playlist',
        'podcast_episode',
        'podcast',
        'search',
        'song',
        'stream',
        'video',
    ];

    // Public variables
    public int $id; // The object_type of object we want

    /**
     * Constructor
     * This is run every time a new object is created, and requires
     * the id and type of object that we need to pull the rating for
     */
    public function __construct(
        ?int $rating_id,
        public string $type,
    ) {
        $this->id   = (int) $rating_id;
    }

    /**
     * build_cache
     * This attempts to get everything we'll need for this page load in a
     * single query, saving on connection overhead
     * @param array<int|string> $ids
     */
    public static function build_cache(string $type, array $ids, ?int $user_id = null): bool
    {
        if ($ids === []) {
            return false;
        }

        // with the cache off these rows are discarded and the per-object queries still run, so this is a net loss
        if (!database_object::isCacheEnabled()) {
            return false;
        }

        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
        }

        if ($user_id === 0) {
            return false;
        }

        $repository   = self::getRatingRepository();
        $user_ratings = $repository->getUserRatings($type, array_values($ids), $user_id);
        $ratings      = $repository->getAverageRatings($type, array_values($ids));

        foreach ($ids as $object_id) {
            // First store the user-specific rating
            $rating = (isset($user_ratings[$object_id])) ? (int) $user_ratings[$object_id] : 0;

            parent::add_to_cache('rating_' . $type . '_user' . $user_id, $object_id, [$rating]);
            // Then store the average
            // keep the float precision the query returned; 0 means "no average", as get_average_rating() reports
            $rating = (isset($ratings[$object_id])) ? round($ratings[$object_id], 2) : 0;

            parent::add_to_cache('rating_' . $type . '_all', $object_id, [$rating]);
        }

        return true;
    }

    /**
     * garbage_collection
     *
     * Remove ratings for items that no longer exist.
     */
    public static function garbage_collection(?string $object_type = null, ?int $object_id = null): void
    {
        self::getRatingRepository()->collectGarbage($object_type, $object_id);
    }

    /**
     * get_highest
     * Get objects with the highest average rating.
     * @return int[]
     */
    public static function get_highest(string $input_type, int $count = 0, int $offset = 0, ?int $user_id = null, bool $by_user = false, int $catalog_id = 0): array
    {
        if ($count === 0) {
            $count = AmpConfig::get_int('popular_threshold', 10);
        }

        if ($count === -1) {
            $count  = 0;
            $offset = 0;
        }

        return self::getRatingRepository()->findHighestIds($input_type, $count, $offset, $user_id, $by_user, $catalog_id);
    }

    /**
     * get_latest
     * Get the latest user flagged objects
     * @return int[]
     */
    public static function get_latest(
        string $type,
        ?User $user = null,
        int $count = 0,
        int $offset = 0,
        int $since = 0,
        int $before = 0,
    ): array {
        if ($count === 0) {
            $count = AmpConfig::get_int('popular_threshold', 10);
        }

        if ($count === -1) {
            $count  = 0;
            $offset = 0;
        }

        return self::getRatingRepository()->findLatestIds($type, $user, $count, $offset, $since, $before);
    }

    public static function is_valid(string $type): bool
    {
        return in_array($type, self::RATING_TYPES, true);
    }

    /**
     * Migrate an object associate stats to a new object\
     */
    public static function migrate(string $object_type, int $old_object_id, int $new_object_id): void
    {
        self::getRatingRepository()->migrate($object_type, $old_object_id, $new_object_id);
    }

    /**
     * save_rating
     * Forward rating value to plugins
     */
    public static function save_rating(int $object_id, string $object_type, int $new_rating, int $user_id): void
    {
        $rating = new Rating($object_id, $object_type);
        $user   = new User($user_id);
        if ($rating->id !== 0) {
            foreach (Plugin::get_plugins(PluginTypeEnum::RATING_SAVER) as $plugin_name) {
                try {
                    $plugin = new Plugin($plugin_name);
                    if ($plugin->_plugin instanceof AmpacheRatingMatch && $plugin->load($user)) {
                        debug_event(self::class, 'save_rating... ' . $plugin_name, 5);
                        $plugin->_plugin->save_rating($rating, $new_rating);
                    }
                } catch (Exception $error) {
                    debug_event(self::class, 'save_rating plugin error: ' . $error->getMessage(), 1);
                }
            }
        }
    }

    /**
     * show
     * This takes an id and a type and displays the rating if ratings are enabled.
     * If $show_global_rating is true, also show the average from all users.
     */
    public static function show(int $object_id, string $type, bool $show_global_rating = false): string
    {
        // If ratings aren't enabled don't do anything
        if (!AmpConfig::get('ratings') || !in_array($type, self::RATING_TYPES, true)) {
            return '';
        }

        $rating = new Rating($object_id, $type);

        $base_url = '?action=set_rating&rating_type=' . $rating->type . '&object_id=' . $rating->id;
        $rate     = ($rating->get_user_rating() ?? 0);

        $ratings = '';

        for ($count = 0; $count < 6; ++$count) {
            if ($count === 0) {
                $action = -1;
                $alt    = T_('0 Stars');
                $icon   = 'hide_source';
            } else {
                $action = $count;
                $alt    = ($count === 1)
                    ? T_('1 Star')
                    : T_($count . ' Stars');
                $icon = ($rate < $count)
                    ? 'star'
                    : 'star-fill';
            }

            $action = $base_url . '&rating=' . $action;
            $source = 'rating' . $count . '_' . $rating->id . '_' . $rating->type;
            $text   = Ajax::button($action, $icon, $alt, $source);
            $ratings .= sprintf(
                '<li>%s</li>',
                $text
            );
        }

        if ($show_global_rating) {
            $global_rating_value = $rating->get_average_rating();
            if ($global_rating_value > 0) {
                $ratings .= sprintf(
                    '<li><span class="global-rating" title="%s">(%s)</span></li>',
                    T_('Average from all users'),
                    $global_rating_value
                );
            }
        }

        $ratedText = ($rate < 1)
            ? T_('not rated yet')
            : sprintf(T_('%s of 5'), $rate);

        return sprintf(
            '<div class="star-rating dynamic-star-rating">
                <span class="current-rating">%s: %s</span>
                <ul>
                    %s
                </ul>
            </div>',
            T_('Current rating'),
            $ratedText,
            $ratings
        );
    }

    /**
     * @deprecated inject dependency
     */
    private static function getRatingRepository(): RatingRepositoryInterface
    {
        global $dic;

        return $dic->get(RatingRepositoryInterface::class);
    }

    /**
     * get_average_rating
     * Get the floored average rating of what everyone has rated this object as.
     */
    public function get_average_rating(): ?float
    {
        $key = 'rating_' . $this->type . '_all';
        // a cached 0 is the answer "nothing rated it enough", so it must not fall through to the query
        if (parent::is_cached($key, $this->id)) {
            $cached = (float) parent::get_from_cache($key, $this->id)[0];

            return ($cached > 0) ? $cached : null;
        }

        $rating = self::getRatingRepository()->getAverageRating($this->id, $this->type);
        if ($rating === null) {
            return null;
        }

        parent::add_to_cache($key, $this->id, [$rating]);

        return $rating;
    }

    /**
     * get_user_rating
     * Get a user's rating. If no userid is passed in, we use the currently logged in user.
     */
    public function get_user_rating(?int $user_id = null): ?int
    {
        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
        }

        if ($user_id === 0) {
            return null;
        }

        $key = 'rating_' . $this->type . '_user' . $user_id;
        // cached 0 = no rating. don't re-query it.
        if (parent::is_cached($key, $this->id)) {
            $cached = (int) parent::get_from_cache($key, $this->id)[0];

            return ($cached > 0) ? $cached : null;
        }

        $rating = self::getRatingRepository()->getUserRating($this->id, $this->type, $user_id);
        if ($rating === null) {
            return null;
        }

        parent::add_to_cache($key, $this->id, [$rating]);

        return $rating;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * set_rating
     * This function sets the rating for the current object.
     * If no user_id is passed in, we use the currently logged in user.
     */
    public function set_rating(int $rating, ?int $user_id = null, bool $write_back = true): bool
    {
        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
        }

        if ($user_id === 0) {
            return false;
        }

        if (!self::is_valid($this->type)) {
            return false;
        }

        if (self::get_user_rating($user_id) === $rating) {
            return true;
        }

        $time       = time();
        $repository = self::getRatingRepository();
        debug_event(self::class, sprintf('Setting rating for %s %d to %d', $this->type, $this->id, $rating), 5);

        if ($rating < 1) {
            // If score is negative or 0, then remove rating
            $repository->adjustWeight($this->type, $this->id, -1);
            $repository->deleteRating($this->id, $this->type, $user_id);
        } else {
            $this->getUserActivityPoster()->post((int) $user_id, 'rating', $this->type, $this->id, $time);
            $repository->adjustWeight($this->type, $this->id, 1);
            $repository->setRating($this->id, $this->type, $rating, $user_id, $time);
        }

        parent::add_to_cache('rating_' . $this->type . '_user' . $user_id, $this->id, [$rating]);

        // sometimes we're reading the rating so don't always write back
        if ($write_back) {
            self::save_rating($this->id, $this->type, $rating, (int) $user_id);
        }

        return true;
    }

    /**
     * @deprecated inject dependency
     */
    private function getUserActivityPoster(): UserActivityPosterInterface
    {
        global $dic;

        return $dic->get(UserActivityPosterInterface::class);
    }
}

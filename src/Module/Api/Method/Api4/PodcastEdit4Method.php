<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api4;
use Ampache\Repository\PodcastRepositoryInterface;

/**
 * Class PodcastEdit4Method
 */
final class PodcastEdit4Method
{
    public const ACTION = 'podcast_edit';

    /**
     * podcast_edit
     * MINIMUM_API_VERSION=420000
     * Update the description and/or expiration date for an existing podcast.
     * Takes the podcast id to update with optional description and expires parameters.
     *
     * filter      = (string) Alpha-numeric search term
     * feed        = (string) feed url (xml!) //optional
     * title       = (string) title string //optional
     * website     = (string) source website url //optional
     * description = (string) //optional
     * generator   = (string) //optional
     * copyright   = (string) //optional
     */
    public static function podcast_edit(array $input, User $user): bool
    {
        if (!AmpConfig::get('podcast')) {
            Api4::message('error', T_('Access Denied: podcast features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!Api4::check_access(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER, $user->id, 'edit_podcast', $input['api_format'])) {
            return false;
        }
        if (!Api4::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }
        $podcast_id = $input['filter'];
        $podcast    = self::getPodcastRepository()->findById((int) $podcast_id);
        if ($podcast === null) {
            Api4::message('error', 'podcast ' . $podcast_id . ' was not found', '404', $input['api_format']);

            return false;
        }

        $feed        = (array_key_exists('feed', $input) && filter_var($input['feed'], FILTER_VALIDATE_URL)) ? filter_var($input['feed'], FILTER_VALIDATE_URL) : $podcast->getFeedUrl();
        $title       = (array_key_exists('title', $input)) ? scrub_in((string) $input['title']) : $podcast->getTitle();
        $website     = (array_key_exists('website', $input) && filter_var($input['website'], FILTER_VALIDATE_URL)) ? filter_var($input['website'], FILTER_VALIDATE_URL) : $podcast->getWebsite();
        $description = (array_key_exists('description', $input)) ? scrub_in((string) $input['description']) : $podcast->get_description();
        $generator   = (array_key_exists('generator', $input)) ? scrub_in((string) $input['generator']) : $podcast->getGenerator();
        $copyright   = (array_key_exists('copyright', $input)) ? scrub_in((string) $input['copyright']) : $podcast->getCopyright();

        $podcast->setTitle($title)
            ->setFeedUrl($feed)
            ->setWebsite($website)
            ->setDescription($description)
            ->setGenerator($generator)
            ->setCopyright($copyright)
            ->save();

        Api4::message('success', 'podcast ' . $podcast_id . ' updated', null, $input['api_format']);

        return true;
    }

    /**
     * @deprecated inject by constructor
     */
    private static function getPodcastRepository(): PodcastRepositoryInterface
    {
        global $dic;

        return $dic->get(PodcastRepositoryInterface::class);
    }
}

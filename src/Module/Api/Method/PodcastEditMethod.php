<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Api\Method;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;

/**
 * Class PodcastEditMethod
 * @package Lib\ApiMethods
 */
final class PodcastEditMethod
{
    public const ACTION = 'podcast_edit';

    /**
     * podcast_edit
     * MINIMUM_API_VERSION=420000
     * CHANGED_IN_API_VERSION=5.0.0
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
            Api::error(T_('Enable: podcast'), ErrorCodeEnum::ACCESS_DENIED, self::ACTION, 'system', $input['api_format']);

            return false;
        }
        if (!Api::check_access('interface', 50, $user->id, self::ACTION, $input['api_format'])) {
            return false;
        }
        if (!Api::check_parameter($input, array('filter'), self::ACTION)) {
            return false;
        }
        $podcast_id = $input['filter'];
        $podcast    = new Podcast($podcast_id);

        if ($podcast->isNew()) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf(T_('Not Found: %s'), $podcast_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

            return false;
        }

        $feed        = (array_key_exists('feed', $input) && filter_var($input['feed'], FILTER_VALIDATE_URL)) ? filter_var($input['feed'], FILTER_VALIDATE_URL) : $podcast->getFeedUrl();
        $title       = (array_key_exists('title', $input)) ? scrub_in((string) $input['title']) : $podcast->getTitle();
        $website     = (array_key_exists('website', $input) && filter_var($input['website'], FILTER_VALIDATE_URL)) ? filter_var($input['website'], FILTER_VALIDATE_URL) : $podcast->getWebsite();
        $description = (array_key_exists('description', $input)) ? scrub_in((string) $input['description']) : $podcast->get_description();
        $generator   = (array_key_exists('generator', $input)) ? scrub_in((string) $input['generator']) : $podcast->getGenerator();
        $copyright   = (array_key_exists('copyright', $input)) ? scrub_in((string) $input['copyright']) : $podcast->getCopyright();

        $podcast->setFeedUrl($feed)
            ->setTitle($title)
            ->setWebsite($website)
            ->setDescription($description)
            ->setGenerator($generator)
            ->setCopyright($copyright)
            ->save();

        Api::message('podcast ' . $podcast_id . ' updated', $input['api_format']);

        return true;
    }
}

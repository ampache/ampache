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
 */

declare(strict_types=0);

namespace Ampache\Module\Video;

use Ampache\Module\Tag\TagListUpdaterInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\Video;

final class VideoFromTagUpdater implements VideoFromTagUpdaterInterface
{
    private TagListUpdaterInterface $tagListUpdater;

    public function __construct(
        TagListUpdaterInterface $tagListUpdater
    ) {
        $this->tagListUpdater = $tagListUpdater;
    }

    public function update(
        array $results,
        Video $video
    ): array {
        /* Setup the vars */
        $new_video                = new Video();
        $new_video->setFile($results['file']);
        $new_video->title         = $results['title'];
        $new_video->setSize((int) $results['size']);
        $new_video->video_codec   = $results['video_codec'];
        $new_video->audio_codec   = $results['audio_codec'];
        $new_video->resolution_x  = $results['resolution_x'];
        $new_video->resolution_y  = $results['resolution_y'];
        $new_video->time          = $results['time'];
        $new_video->release_date  = $results['release_date'] ?: 0;
        $new_video->bitrate       = $results['bitrate'];
        $new_video->mode          = $results['mode'];
        $new_video->channels      = $results['channels'];
        $new_video->display_x     = $results['display_x'];
        $new_video->display_y     = $results['display_y'];
        $new_video->frame_rate    = $results['frame_rate'];
        $new_video->video_bitrate = (int) Catalog::check_int($results['video_bitrate'], 4294967294, 0);
        $tags                     = Tag::get_object_tags('video', $video->id);
        if ($tags) {
            foreach ($tags as $tag) {
                $video->tags[] = $tag['name'];
            }
        }
        $new_video->tags = $results['genre'];

        $info = Video::compare_video_information($video, $new_video);
        if ($info['change']) {
            debug_event(self::class, $video->getFile() . " : differences found, updating database", 5);

            Video::update_video($video->id, $new_video);

            if ($video->tags != $new_video->tags) {
                $this->tagListUpdater->update(implode(',', $new_video->tags), 'video', $video->id, true);
            }
        } else {
            debug_event(self::class, $video->getFile() . " : no differences found", 5);
        }

        return $info;
    }
}

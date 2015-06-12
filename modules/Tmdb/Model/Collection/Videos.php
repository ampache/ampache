<?php
/**
 * This file is part of the Tmdb PHP API created by Michael Roterman.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Tmdb
 * @author Michael Roterman <michael@wtfz.net>
 * @copyright (c) 2013, Michael Roterman
 * @version 0.0.1
 */
namespace Tmdb\Model\Collection;

use Tmdb\Model\Common\GenericCollection;
use Tmdb\Model\Video;

/**
 * Class Videos
 * @package Tmdb\Model\Collection
 */
class Videos extends GenericCollection
{
    /**
     * Returns all videos
     *
     * @return array
     */
    public function getVideos()
    {
        return $this->data;
    }

    /**
     * Retrieve a video from the collection
     *
     * @param $id
     * @return null
     */
    public function getVideo($id)
    {
        return $this->filterId($id);
    }

    /**
     * Add a video to the collection
     *
     * @param Video $video
     */
    public function addVideo(Video $video)
    {
        $this->add(null, $video);
    }
}

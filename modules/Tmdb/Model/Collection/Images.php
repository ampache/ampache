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
use Tmdb\Model\Filter\ImageFilter;
use Tmdb\Model\Image;

/**
 * Class Images
 * @package Tmdb\Model\Collection
 */
class Images extends GenericCollection
{
    /**
     * Returns all images
     *
     * @return array
     */
    public function getImages()
    {
        return $this->data;
    }

    /**
     * Retrieve a image from the collection
     *
     * @param $id
     * @return null
     */
    public function getImage($id)
    {
        return $this->filterId($id);
    }

    /**
     * Add a image to the collection
     *
     * @param Image $image
     */
    public function addImage(Image $image)
    {
        $this->add(null, $image);
    }

    /**
     * Filter poster images
     *
     * @return Images
     */
    public function filterPosters()
    {
        return $this->filter(
            function ($key, $value) {
                if ($value instanceof ImageFilter && $value instanceof Image\PosterImage) {
                    return true;
                }
            }
        );
    }

    /**
     * Filter backdrop images
     *
     * @return Images
     */
    public function filterBackdrops()
    {
        return $this->filter(
            function ($key, $value) {
                if ($value instanceof ImageFilter && $value instanceof Image\BackdropImage) {
                    return true;
                }
            }
        );
    }

    /**
     * Filter profile images
     *
     * @return Images
     */
    public function filterProfile()
    {
        return $this->filter(
            function ($key, $value) {
                if ($value instanceof ImageFilter && $value instanceof Image\ProfileImage) {
                    return true;
                }
            }
        );
    }

    /**
     * Filter still images
     *
     * @return Images
     */
    public function filterStills()
    {
        return $this->filter(
            function ($key, $value) {
                if ($value instanceof ImageFilter && $value instanceof Image\StillImage) {
                    return true;
                }
            }
        );
    }

    /**
     * Filter by image size
     *
     * @param $width
     * @return Images
     */
    public function filterMaxWidth($width)
    {
        return $this->filter(
            function ($key, $value) use ($width) {
                if ($value instanceof ImageFilter && $value->getWidth() <= $width && $value->getWidth() !== null) {
                    return true;
                }
            }
        );
    }

    /**
     * Filter by image size
     *
     * @param $width
     * @return Images
     */
    public function filterMinWidth($width)
    {
        return $this->filter(
            function ($key, $value) use ($width) {
                if ($value instanceof ImageFilter && $value->getWidth() >= $width && $value->getWidth() !== null) {
                    return true;
                }
            }
        );
    }

    /**
     * Filter by image size
     *
     * @param $height
     * @return Images
     */
    public function filterMaxHeight($height)
    {
        return $this->filter(
            function ($key, $value) use ($height) {
                if ($value instanceof ImageFilter &&
                    $value->getHeight() <= $height && $value->getHeight() !== null
                ) {
                    return true;
                }
            }
        );
    }

    /**
     * Filter by image size
     *
     * @param $height
     * @return Images
     */
    public function filterMinHeight($height)
    {
        return $this->filter(
            function ($key, $value) use ($height) {
                if ($value instanceof ImageFilter &&
                    $value->getHeight() >= $height &&
                    $value->getHeight() !== null) {
                    return true;
                }
            }
        );
    }

    /**
     * Return a single image that is rated highest
     *
     * @return ImageFilter|null
     */
    public function filterBestVotedImage()
    {
        $currentImage = null;
        $voteAverage  = 0;

        /**
         * @var $image Image
         */
        foreach ($this->data as $image) {
            if ($image->getVoteAverage() > $voteAverage) {
                $voteAverage  = $image->getVoteAverage();
                $currentImage = $image;
            }
        }

        return $currentImage;
    }
}

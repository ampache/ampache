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
namespace Tmdb\Helper;

use Tmdb\Model\Configuration;
use Tmdb\Model\Image;

/**
 * Class ImageHelper
 * @package Tmdb\Helper
 */
class ImageHelper
{
    private $config;

    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Load the image configuration collection
     *
     * @return \Tmdb\Model\Common\GenericCollection
     */
    public function getImageConfiguration()
    {
        return $this->config->getImages();
    }

    /**
     * Get the url for the image resource
     *
     * @param  Image|string $image Either an instance of Image or the file_path
     * @param  string       $size
     * @return string
     */
    public function getUrl($image, $size = 'original')
    {
        $config = $this->getImageConfiguration();

        return $config['base_url'] . $size . $image;
    }

    /**
     * Get an img html tag for the image in the specified size
     *
     * @param  Image|string $image  Either an instance of Image or the file_path
     * @param  string       $size
     * @param  int|null     $width
     * @param  int|null     $height
     * @return string
     */
    public function getHtml($image, $size = 'original', $width = null, $height = null)
    {
        if ($image instanceof Image) {
            if (null == $image->getFilePath()) {
                return '';
            }

            $aspectRatio = $image->getAspectRatio();

            if (null !== $width && null == $height && $aspectRatio !== null) {
                $height = round($width / $aspectRatio);
            }

            if (null !== $height && null == $width && $aspectRatio !== null) {
                $width = round($height * $aspectRatio);
            }

            if (null == $width) {
                $width = $image->getWidth();
            }

            if (null == $height) {
                $height = $image->getHeight();
            }
        }

        return sprintf(
            '<img src="%s" width="%s" height="%s" />',
            $this->getUrl($image, $size),
            $width,
            $height
        );
    }
}

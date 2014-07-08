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
namespace Tmdb\Model\Common\Video;
use Tmdb\Model\Common\Video;

/**
 * Class Youtube
 * @package Tmdb\Model\Common\Trailer
 */
class Youtube extends Video
{
    const URL_FORMAT = 'http://www.youtube.com/watch?v=%s';

    public function __construct()
    {
        $this->setUrlFormat(self::URL_FORMAT);
    }
}

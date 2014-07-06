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
namespace Tmdb\Model\Lists;

use Tmdb\Model\AbstractModel;

/**
 * Class Result
 * @package Tmdb\Model\Lists
 */
class Result extends AbstractModel
{
    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var string
     */
    private $statusMessage;

    /**
     * @var array
     */
    public static $properties = array(
        'status_code',
        'status_message'
    );

    /**
     * @param  int   $statusCode
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param  string $statusMessage
     * @return $this
     */
    public function setStatusMessage($statusMessage)
    {
        $this->statusMessage = $statusMessage;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatusMessage()
    {
        return $this->statusMessage;
    }
}

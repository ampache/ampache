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

/**
 * Class ResultWithListId
 * @package Tmdb\Model\Lists
 */
class ResultWithListId extends Result
{
    /**
     * @var string
     */
    private $listId;

    /**
     * @var array
     */
    public static $properties = array(
        'status_code',
        'status_message',
        'list_id'
    );

    /**
     * @param  string $listId
     * @return $this
     */
    public function setListId($listId)
    {
        $this->listId = $listId;

        return $this;
    }

    /**
     * @return string
     */
    public function getListId()
    {
        return $this->listId;
    }
}

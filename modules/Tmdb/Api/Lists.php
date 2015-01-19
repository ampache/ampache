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
namespace Tmdb\Api;

/**
 * Class Lists
 * @package Tmdb\Api
 * @see http://docs.themoviedb.apiary.io/#lists
 */
class Lists extends AbstractApi
{
    /**
     * Get a list by id.
     *
     * @param $list_id
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getList($list_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('list/' . $list_id, $parameters, $headers);
    }

    /**
     * This method lets users create a new list. A valid session id is required.
     *
     * @param  string $name
     * @param  string $description
     * @param  array  $parameters
     * @param  array  $headers
     * @return mixed
     */
    public function createList($name, $description, array $parameters = array(), array $headers = array())
    {
        return $this->postJson('list', array('name' => $name, 'description' => $description), $parameters, $headers);
    }

    /**
     * Check to see if a movie ID is already added to a list.
     *
     * @param  string $id
     * @param  int    $movieId
     * @param  array  $parameters
     * @param  array  $headers
     * @return mixed
     */
    public function getItemStatus($id, $movieId, array $parameters = array(), array $headers = array())
    {
        return $this->get(
            'list/' . $id . '/item_status',
            array_merge($parameters, array('movie_id' => $movieId)),
            $headers
        );
    }

    /**
     * This method lets users add new movies to a list that they created. A valid session id is required.
     *
     * @param  string $id
     * @param  string $mediaId
     * @return mixed
     */
    public function addMediaToList($id, $mediaId)
    {
        return $this->postJson('list/' . $id . '/add_item', array('media_id' => $mediaId));
    }

    /**
     * This method lets users delete movies from a list that they created. A valid session id is required.
     *
     * @param  string $id
     * @param  string $mediaId
     * @return mixed
     */
    public function removeMediaFromList($id, $mediaId)
    {
        return $this->postJson('list/' . $id . '/remove_item', array('media_id' => $mediaId));
    }

    /**
     * This method lets users delete a list that they created. A valid session id is required.
     *
     * @param  string $id
     * @return mixed
     */
    public function deleteList($id)
    {
        return $this->delete('list/' . $id);
    }

    /**
     * Clear all of the items within a list.
     *
     * This is a irreversible action and should be treated with caution.
     * A valid session id is required.
     *
     * @param  string  $id
     * @param  boolean $confirm
     * @return mixed
     */
    public function clearList($id, $confirm)
    {
        return $this->post(sprintf(
                'list/%s/clear?confirm=%s',
                $id,
                (bool) $confirm === true ? 'true':'false'
            ));
    }
}

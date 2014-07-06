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
namespace Tmdb\Repository;

use Tmdb\Factory\ListFactory;
use Tmdb\Model\Lists\ItemStatus;
use Tmdb\Model\Lists;

/**
 * Class ListRepository
 * @package Tmdb\Repository
 * @see http://docs.themoviedb.apiary.io/#lists
 */
class ListRepository extends AbstractRepository
{
    /**
     * Get a list by id.
     *
     * @param  string $id
     * @param  array  $parameters
     * @param  array  $headers
     * @return Lists
     */
    public function load($id, array $parameters = array(), array $headers = array())
    {
        return $this->getFactory()->create(
            $this->getApi()->getList($id, $parameters, $headers)
        );
    }

    /**
     * Check to see if a movie ID is already added to a list.
     *
     * @param  string     $id
     * @param  int        $mediaId
     * @param  array      $parameters
     * @param  array      $headers
     * @return ItemStatus
     */
    public function getItemStatus($id, $mediaId, array $parameters = array(), array $headers = array())
    {
        return $this->getFactory()->createItemStatus(
            $this->getApi()->getItemStatus($id, $mediaId, $parameters, $headers)
        );
    }

    /**
     * This method lets users create a new list. A valid session id is required.
     *
     * @param  string $name
     * @param  string $description
     * @param  array  $parameters
     * @param  array  $headers
     * @return string The list id
     */
    public function createList($name, $description = null, array $parameters = array(), array $headers = array())
    {
        return $this->getFactory()->createResultWithListId(
            $this->getApi()->createList($name, $description, $parameters, $headers)
        );
    }

    /**
     * This method lets users add new movies to a list that they created.
     * A valid session id is required.
     *
     * @param  string     $id
     * @param  int        $mediaId
     * @return ItemStatus
     */
    public function add($id, $mediaId)
    {
        return $this->getFactory()->createResult(
            $this->getApi()->addMediaToList($id, $mediaId)
        );
    }

    /**
     * This method lets users delete movies from a list that they created.
     * A valid session id is required.
     *
     * @param  string     $id
     * @param  int        $mediaId
     * @return ItemStatus
     */
    public function remove($id, $mediaId)
    {
        return $this->getFactory()->createResult(
            $this->getApi()->removeMediaFromList($id, $mediaId)
        );
    }

    /**
     * This method lets users delete a list that they created.
     * A valid session id is required.
     *
     * @param  string     $id
     * @return ItemStatus
     */
    public function deleteList($id)
    {
        return $this->getFactory()->createResult(
            $this->getApi()->deleteList($id)
        );
    }

    /**
     * Clear all of the items within a list.
     *
     * This is a irreversible action and should be treated with caution.
     * A valid session id is required.
     *
     * @param  string     $id
     * @param  boolean    $confirm
     * @return ItemStatus
     */
    public function clearList($id, $confirm)
    {
        return $this->getFactory()->createResult(
            $this->getApi()->deleteList($id, (bool) $confirm)
        );
    }

    /**
     * Return the related API class
     *
     * @return \Tmdb\Api\Lists
     */
    public function getApi()
    {
        return $this->getClient()->getListsApi();
    }

    /**
     * @return ListFactory
     */
    public function getFactory()
    {
        return new ListFactory();
    }
}

<?php

/**
 * API calls for managing personal catalogs
 *
 * @link      http://developer.echonest.com/docs/v4/catalog.html#overview
 * @author    Brent Shaffer <bshafs at gmail dot com>
 * @license   MIT License
 */
class EchoNest_Api_Catalog extends EchoNest_Api
{
  /**
   * Creates a catalog.
   * http://developer.echonest.com/docs/v4/catalog.html#create
   *
   * @param   string  $name         The name of the catalog
   * @param   string  $type         The type of the catalog (artist or song)
   * @return  array                 response object
   */
  public function create($name, $type)
  {
    $response = $this->client->post('catalog/create', array(
      'name'    => $name,
      'type'    => $type
    ));

    return $this->returnResponse($response);
  }
  
  /**
   * Updates (adds or deletes) items from a catalog. The body of the post should include an item block that describes modifications to the catalog.
   * When a catalog is updated, the contents of the catalog are resolved to Echo Nest IDs. This resolving process happens asynchronously to the call. The update method returns a 'ticket' that can be used with the catalog/status call to check on the status of the update
   * http://developer.echonest.com/docs/v4/catalog.html#update
   *
   * @param   string  $id         The ID of the catalog
   * @param   string  $data_type  The type of data to be uploaded (json, itunes, xspf, m3u)
   * @param   string  $data       The data to be uploaded (data in the form specified by 'data_type')
   * @return  array                 response object
   */
  public function update($id, $data, $data_type = 'json')
  {
    $response = $this->client->post('catalog/update', array(
      'id'        => $id,
      'data'      => $data,
      'data_type' => $data_type,
    ));

    return $this->returnResponse($response);
  }
  
  /**
   * Checks the status of a catalog update.
   * http://developer.echonest.com/docs/v4/catalog.html#status
   *
   * @param   string  $ticket     The ticket to check (returned by upload or update)
   * @return  array               response object
   */
  public function status($ticket)
  {
    $response = $this->client->get('catalog/status', array(
      'ticket'     => $ticket,
    ));

    return $this->returnResponse($response);
  }
  
  /**
   * Get basic information on a catalog
   * http://developer.echonest.com/docs/v4/catalog.html#profile
   *
   * @param   string  $id         The ID or name of the catalog
   * @param   bool    $byName     If the name of the catalog is used as the first argument, this must be set to true
   * @return  array               response object
   */
  public function profile($id, $byName = false)
  {
    $response = $this->client->get('catalog/profile', array(
      $byName ? 'name' : 'id' => $id,
    ));

    return $this->returnResponse($response, 'catalog');
  }
  
  /**
   * Returns all of the data stored in the catalog. Also returns Echo Nest IDs for items that have been resolved to Echo Nest IDs along with information requested via bucket.
   * http://developer.echonest.com/docs/v4/catalog.html#read
   *
   * @param   string  $id             The ID of the catalog
   * @param   integer $results        the number of results desired (0 < $results < 100)
   * @param   string  $start          the desired index of the first result returned
   * @param   string|array $bucket    indicates what data should be returned with each artist
   * @return  array                   response object
   */
  public function read($id, $results = 15, $start = 0, $bucket = null)
  {
    $response = $this->client->get('catalog/read', array(
      'id'              => $id,
      'results'         => $results,
      'start'           => $start,
      'bucket'          => $bucket,
    ));

    return $this->returnResponse($response, 'catalog');
  }
  
  /**
   * Returns feeds based on the artists in a personal catalog. Unlike catalog/read method, the catalog/feed method interleaves items and sorts them by date.
   * http://developer.echonest.com/docs/v4/catalog.html#feed
   *
   * @param   string  $id             The ID of the catalog
   * @param   integer $results        the number of results desired (0 < $results < 100)
   * @param   string  $start          the desired index of the first result returned
   * @param   string|array $bucket    indicates what data should be returned with each artist
   * @param   string  $since          limit the items to those that have occurred since the given date (YYYY-mm-dd)
   * @return  array                   response object
   */
  public function feed($id, $results = 15, $start = 0, $bucket = null, $since = null)
  {
    $response = $this->client->get('catalog/feed', array(
      'id'              => $id,
      'results'         => $results,
      'start'           => $start,
      'bucket'          => $bucket,
      'since'           => $since,
    ));

    return $this->returnResponse($response, 'feed');
  }
  
  /**
   * Deletes the entire catalog. Only the API key used to create a catalog can be used to delete that catalog.
   * http://developer.echonest.com/docs/v4/catalog.html#delete
   *
   * @param   string  $id             The ID of the catalog
   * @return  array                   response object
   */
  public function delete($id)
  {
    $response = $this->client->post('catalog/delete', array(
      'id'              => $id,
    ));

    return $this->returnResponse($response);
  }

  /**
   * Returns a list of all taste profiles that are similar to the given set of taste profiles.
   * This method returns similar taste profiles of the given use type.
   * Similarity search is restricted to taste profiles that were created with the caller's API key.
   * http://developer.echonest.com/docs/v4/catalog.html#similar-beta
   *
   * @param   string  $id             The ID of the catalog
   * @return  array                   response object
   */


  public function similar($id)
  {
    $response = $this->client->get("catalog/similar", array(
      "id"            => $id
    ));

    return $this->returnResponse($response);
  } 

  /**
   * Returns a list of all catalogs created on this key
   * http://developer.echonest.com/docs/v4/catalog.html#list
   *
   * @param   integer $results        the number of results desired (0 < $results < 100)
   * @param   string  $start          the desired index of the first result returned
   * @return  array                   response object
   */
  public function getList($results = 15, $start = 0)
  {
    $response = $this->client->get('catalog/list', array(
      'results'         => $results,
      'start'           => $start,
    ));

    return $this->returnResponse($response, 'catalogs');
  }
}

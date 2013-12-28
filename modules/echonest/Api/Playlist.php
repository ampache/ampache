<?php

/**
 * Api calls for generating playlists
 *
 * @link      http://developer.echonest.com/docs/v4/playlist.html
 * @author    Brent Shaffer <bshafs at gmail dot com>
 * @license   MIT License
 */
class EchoNest_Api_Playlist extends EchoNest_Api
{
  /**
   * Returns a static playlist. A static playlist is generated once from an initial set of parameters, and returned as an ordered list of songs.
   * http://developer.echonest.com/docs/v4/playlist.html#static
   *
   * @param   array $options          see the EchoNest documentation for a list of options
   * @return  array                   array of songs matching the supplied options for the playlist
   */
  public function getStatic(array $options = array())
  {
    $response = $this->client->get('playlist/static', $options);

    return $this->returnResponse($response, 'songs');
  }
  
  /**
   * Returns a dynamic playlist. A dynamic playlist is created with an initial set of parameters and songs are fetched one at a time using a session identifier. The playlist is dynamic and will return songs based on the listener's feedback.
   * http://developer.echonest.com/docs/v4/playlist.html#dynamic
   *
   * @param   array $options          see the EchoNest documentation for a list of options
   * @return  array                   array of songs matching the supplied options for the playlist
   */
  public function getDynamic(array $options = array())
  {
    $response = $this->client->get('playlist/dynamic', $options);

    return $this->returnResponse($response);
  }

  /**
   * Returns state information for dynamic playlists.
   * http://developer.echonest.com/docs/v4/playlist.html#session-info
   *
   * @param   array $session_id       The id of the current playlist session. To start a new session, call playlist/dynamic with no session ID.
   * @return  array                   array of session info
   */  
  public function getSessionInfo($session_id)
  {
    $response = $this->client->get('playlist/dynamic', array(
      'session_id'  => $session_id,
    ));

    return $this->returnResponse($response);
  }
}
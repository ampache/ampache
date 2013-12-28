<?php

/**
 * Methods for analyzing or getting info about tracks.
 *
 * @link      http://developer.echonest.com/docs/v4/track.html
 * @author    Brent Shaffer <bshafs at gmail dot com>
 * @license   MIT License
 */
class EchoNest_Api_Track extends EchoNest_Api
{
  /**
   * Analyze a previously uploaded track with the latest version of the analyzer.
   * http://developer.echonest.com/docs/v4/track.html#analyze
   *
   * @param   string  $id             the ID of the previously uploaded track
   * @param   bool    $wait           if true wait for the analysis to be completed
   * @param   string|array  $bucket   Indicates which track data should be returned (only valid if wait is true)
   * @return  array                   response object
   */
  public function analyze($id, $wait = true, $bucket = null)
  {
    $response = $this->client->post('track/analyze', array(
      'id'     => $id,
      'wait'   => $wait,
      'bucket' => $bucket,
    ));

    return $this->returnResponse($response);
  }

  /**
   * Same as "analyze", but uses an md5 instead of an id. The md5 parameter is the file md5.
   *
   * @param   string  $md5            the md5 of the previously uploaded track
   * @param   bool    $wait           if true wait for the analysis to be completed
   * @param   string|array  $bucket   Indicates which track data should be returned (only valid if wait is true)
   * @return  array                   response object
   */
  public function analyzeMd5($md5, $wait = true, $bucket = null)
  {
    $response = $this->client->post('track/analyze', array(
      'md5'    => $md5,
      'wait'   => $wait,
      'bucket' => $bucket,
    ));

    return $this->returnResponse($response);
  }
  
  /**
   * Get info about tracks given an id.
   * http://developer.echonest.com/docs/v4/track.html#profile
   *
   * @param   string  $id             the ID of the previously uploaded track
   * @param   string|array  $bucket   Indicates which track data should be returned (only valid if wait is true)
   * @return  array                   response object
   */
  public function profile($id, $bucket = null)
  {
    $response = $this->client->get('track/profile', array(
      'id'     => $id,
      'bucket' => $bucket,
    ));

    return $this->returnResponse($response);
  }

  /**
   * Same as "profile", but uses an md5 instead of an id. The md5 parameter is the file md5.
   *
   * @param   string  $md5            the md5 of the previously uploaded track
   * @param   string|array  $bucket   Indicates which track data should be returned (only valid if wait is true)
   * @return  array                   response object
   */
  public function profileMd5($md5, $bucket = null)
  {
      $response = $this->client->get('track/profile', array(
          'md5'    => $md5,
          'wait'   => $wait,
          'bucket' => $bucket,
          ));

      return $this->returnResponse($response);
  }

    /**
     * Upload a track to The Echo Nest's analyzer for analysis. The track will be analyzed. This method takes either a url parameter, or a local audio file, which should be the contents of the request body.
     *
     * @param   string  $url            one of url to an audio file or a local audio file
     * @param   bool    $wait           if true wait for the analysis to be completed
     * @param   string  $filetype       the type of audio file to be analyzed (wav, mp3, au, ogg). required if uploading a local file
     * @param   string|array  $bucket   Indicates which track data should be returned (only valid if wait is true)
     * @param   string  $track          the track data (required in a POST if using the 'multipart/form-data' Content-Type)
     * @return  array                   response object
     */
    public function upload($url, $wait = true, $filetype = null, $bucket = null, $track = null)
    {
        $response = $this->client->post('track/upload', array(
            'url'      => $url,
            'wait'     => $wait,
            'filetype' => $filetype,
            'bucket'   => $bucket,
            'track'    => $track,
    ));

    return $this->returnResponse($response);
    }
}

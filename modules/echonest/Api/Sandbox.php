<?php


Class SignatureCreation {

  public static function oauth_hmacsha1($key, $data) {
      return base64_encode(self::hmacsha1($key, $data));
  }

  public static function hmacsha1($key,$data) {
      $blocksize=64;
      $hashfunc='sha1';
      if (strlen($key)>$blocksize)
          $key=pack('H*', $hashfunc($key));
      $key=str_pad($key,$blocksize,chr(0x00));
      $ipad=str_repeat(chr(0x36),$blocksize);
      $opad=str_repeat(chr(0x5c),$blocksize);
      $hmac = pack(
                  'H*',$hashfunc(
                      ($key^$opad).pack(
                          'H*',$hashfunc(
                              ($key^$ipad).$data
                          )
                      )
                  )
              );
      return $hmac;
  }

  public static function oauth_signature($url,$params, $secret) {

    // sort the parameters
    ksort($params);

    $param_string = "";
    $i = 0;
    foreach ($params as $k => $v) {
      if ($i != 0) {
        $param_string .= "&";
      }
      if (is_array($v)) {
        $j = 0;
        foreach($v as $key => $val) {
          if ($j != 0) {
            $param_string .= "&"; 
          }
          $param_string .= $k . "=" . $val;
          $j++;
        }
      } else {
        $param_string .= $k . "=" . $v;
      }
      $i++;
    }
    
    $data =  "GET&".urlencode($url). "&".urlencode($param_string);


    $key = $secret."&";

    return self::oauth_hmacsha1($key, $data);
  }
}


/**
 * API calls for managing sandboxes
 *
 * @link      http://developer.echonest.com/docs/v4/sandbox.html#overview
 * @author    Syd Lawrence <sydlawrence at gmail dot com>
 * @license   MIT License
 */
class EchoNest_Api_Sandbox extends EchoNest_Api
{

  // the current sandbox key
  protected $sandbox_key = "";

  // the keys etc for the oauth
  protected $oauth_config = array
  (
    "consumer_key" => "",
    "consumer_secret" => "",
  );

  // set the current sandbox
  function setSandbox($sandbox_key)
  {
    if ($sandbox_key)
      $this->sandbox_key = $sandbox_key;
    return $this;
  }

  // set the oauth config
  function setOAuthConfig($config)
  {
    foreach ($this->oauth_config as $key => $val) {

      if (!isset($config[$key]) || $config[$key] == "") {
        // @todo make this thrown an exception
        throw new Exception('Missing sandbox oauth config: '.$key);
      }
      $this->oauth_config[$key] = $config[$key];
    }
    return $this;

  }


  /**
   * Lists assets in a sandbox.
   * http://developer.echonest.com/docs/v4/sandbox.html#list
   *
   * @param   int    $start       The starting index of the assets
   * @param   int    $per_page    How many assets to return per page
   * @return  array               response object
   */
  function assets($start = 0, $per_page=100)
  {

    // this one is simples
    $response = $this->client->get('sandbox/list', array(
      'sandbox'    => $this->sandbox_key,
      'results'    => $per_page,
      'start'      => $start
    ));
    
    $response = $this->returnResponse($response);
    
    $total = $response['total']; 
    
    $assets = $response['assets'];
    
    /*
    leave out due to api limits
    if (count($assets) < $total) {
      $next = $this->assets($soFar);
      $assets = array_merge($assets, $next);
    }
    */
    
    return $assets;

  }

  /**
   * Access assets inside a sandbox.
   * http://developer.echonest.com/docs/v4/sandbox.html#access
   *
   * @param   int/array    $id       The id of the individual asset or an array of asset ids
   * @return  array                  response object
   */
  function access($id)
  {

    // this is the endpoint we want this time
    $endpoint = "sandbox/access";
  
    // used for nonce and timestamp
    $time = time();
    
    // set up the parameters
    $params = array(
      "api_key" => $this->client->getHttpClient()->getOption('api_key'),
      "id" => $id,
      "oauth_nonce" => md5($time),
      "oauth_timestamp" => $time,
      "format" => $this->client->getHttpClient()->getOption('format'),
      "oauth_signature_method" => "HMAC-SHA1",
      "oauth_version" => "1.0",
      "oauth_consumer_key" => $this->oauth_config['consumer_key'],
      "sandbox" => $this->sandbox_key
    );

    // create the base url
    $url = strtr($this->client->getHttpClient()->getOption('url'), array(
      ':api_version' => $this->client->getHttpClient()->getOption('api_version'),
      ':protocol'    => $this->client->getHttpClient()->getOption('protocol'),
      ':path'        => trim($endpoint, '/')
    ));

    // generate the signature
    $sig = SignatureCreation::oauth_signature($url,$params, $this->oauth_config['consumer_secret']);

    // add the signature to the params
    $params['oauth_signature'] = $sig;

    // get the response
    $response = $this->client->get($endpoint, $params);

    // return the response
    return $this->returnResponse($response);

  }
}

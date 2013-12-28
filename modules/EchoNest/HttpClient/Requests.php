<?php

/**
 * Performs requests on EchoNest API. API documentation should be self-explanatory.
 *
 * @author    Brent Shaffer <bshafs at gmail dot com>
 * @license   MIT License
 */
class EchoNest_HttpClient_Requests extends EchoNest_HttpClient
{
    /**
    * Send a request to the server, receive a response
    *
    * @param  string   $apiPath       Request API path
    * @param  array    $parameters    Parameters
    * @param  string   $httpMethod    HTTP method to use
    *
    * @return string   HTTP response
    */
    protected function doRequest($url, array $parameters = array(), $httpMethod = 'GET', array $options = array())
    {
        if($this->options['api_key'])
        {
          $parameters = array_merge(array(
              'format'  => $this->options['format'],
              'api_key' => $this->options['api_key']
          ), $parameters);
        }
        
        $headers = array();
        $headers['User-Agent'] = $this->options['user_agent'];

        if ('GET' === $httpMethod && !empty($parameters)) {
            $queryString = utf8_encode($this->buildQuery($parameters));
            $url .= '?' . $queryString;
            
            $this->debug('send ' . $httpMethod . ' request: ' . $url);
            $request = Requests::get($url, $headers);
        } 
        else {
            $this->debug('send ' . $httpMethod . ' request: ' . $url);
            $request = Requests::post($url, $headers, $parameters);
        }

        return $request->body;
    }
  
    protected function buildQuery($parameters)
    {
        $append = '';
        foreach ($parameters as $key => $value) 
        {
            // multiple parameter passed
            if (is_array($value)) {
                foreach ($value as $val) {
                    $append.=sprintf('&%s=%s', $key, $val);
                }
                unset($parameters[$key]);
            }
            elseif (is_bool($value)) {
                $parameters[$key] = $value ? 'true' : 'false';
            }
        }
 
        return http_build_query($parameters, '', '&') . $append;
    }

    protected function debug($message)
    {
        if($this->options['debug'])
        {
            print $message."\n";
        }
    }
}

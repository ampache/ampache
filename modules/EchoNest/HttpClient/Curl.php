<?php

/**
 * Performs requests on EchoNest API. API documentation should be self-explanatory.
 *
 * @author    Brent Shaffer <bshafs at gmail dot com>
 * @license   MIT License
 */
class EchoNest_HttpClient_Curl extends EchoNest_HttpClient
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

        $curlOptions = array();

        if (!empty($parameters))
        {
            $queryString = utf8_encode($this->buildQuery($parameters));

            if('GET' === $httpMethod)
            {
                $url .= '?' . $queryString;
            }
            else
            {
                $curlOptions += array(
                    CURLOPT_POST        => true,
                    CURLOPT_POSTFIELDS  => $queryString
                );
            }
        }

        $this->debug('send '.$httpMethod.' request: '.$url);

        $curlOptions += array(
            CURLOPT_URL             => $url,
            CURLOPT_PORT            => $this->options['http_port'],
            CURLOPT_USERAGENT       => $this->options['user_agent'],
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => $this->options['timeout']
        );

        $response = $this->doCurlCall($curlOptions);

        return $response['response'];
    }
  

    protected function doCurlCall(array $curlOptions)
    {
        $curl = curl_init();

        curl_setopt_array($curl, $curlOptions);

        $response = curl_exec($curl);
        $headers = curl_getinfo($curl);
        $errorNumber = curl_errno($curl);
        $errorMessage = curl_error($curl);

        curl_close($curl);

        return compact('response', 'headers', 'errorNumber', 'errorMessage');
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

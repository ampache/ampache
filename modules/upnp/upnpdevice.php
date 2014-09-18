<?php

class upnpdevice
{
    private $curlHandle = null;
    private $_controlURL = null;


    public function upnpdevice($controlUrl)
    {
        debug_event('upnpdevice', 'constructor: ' . $controlUrl, 5);
        $this->_controlURL = $controlUrl;
    }

    public function sendRequestToDevice( $method, $arguments, $type = 'RenderingControl:1', $controlUrl = null)
    {
        if( is_null( $controlUrl ) ) $controlUrl = $this->getControlURL();

        $body  ='<?xml version="1.0" encoding="utf-8"?>';
        $body .='<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">';
        $body .=' <s:Body>';
        $body .='  <u:'.$method.' xmlns:u="urn:schemas-upnp-org:service:' . $type . ':1">';

        foreach ($arguments as $arg=>$value) {
            $body .='   <'.$arg.'>'.$value.'</'.$arg.'>';
        }

        $body .='  </u:'.$method.'>';
        $body .=' </s:Body>';
        $body .='</s:Envelope>';
        debug_event('upnpdevice', 'sendRequestToDevice: ' . $method . ' | ' . $controlUrl . ' | ' . $body, 5);

        $urldata = parse_url(controlUrl);

        $header = array(
            'SOAPACTION: "urn:schemas-upnp-org:service:' . $type . ':1#' . $method . '"',
            'CONTENT-TYPE: text/xml ; charset="utf-8"',
            'HOST: ' . $urldata['host'] . ':' . $urldata['port'],
            'Connection: close',
            'Content-Length: ' . mb_strlen($body),
        );

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $controlUrl );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $body );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
        curl_setopt( $ch, CURLOPT_HEADER, TRUE );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, TRUE );

        $response = curl_exec( $ch );
        curl_close( $ch );

        debug_event('upnpdevice', 'sendRequestToDevice response: ' . $response, 5);
        return $response;
    }

    // helper function for calls that require only an instance id
    public function instanceOnly($command, $type = 'AVTransport', $id = 0)
    {
        $args = array( 'InstanceID' => $id );
        $response = $this->sendRequestToDevice($command, $args, $type);
        ///$response = \Format::forge($response,'xml:ns')->to_array();
        ///return $response['s:Body']['u:' . $command . 'Response'];
        return $response;
    }

    public function getControlURL()
    {
        if( is_null( $this->_controlURL ) )
            throw new Exception('You must set URL.');
        return $this->_controlURL;
    }

    public function setControlURL( $url )
    {
        $this->controlURL = $url;
    }


}

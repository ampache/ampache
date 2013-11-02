<?php

class XBMC_RPC_Response {
    
    /**
     * @var string The id of the response as returned from the server.
     * @access private
     */
    private $id;
    
    /**
     * @var string The data returned from the server if the response was successful.
     * @access private
     */
    private $data;
    
    /**
     * Constructor.
     *
     * @param string $response The JSON-decoded response string
     * as returned from the server
     */
    public function __construct($response) {
        $response = $this->decodeResponse($response);
        $this->id = $response['id'];
        if (isset($response['error'])) {
            throw new XBMC_RPC_ResponseException($response['error']['message'], $response['error']['code']);
        } elseif (!isset($response['result'])) {
            throw new XBMC_RPC_ResponseException('Invalid JSON RPC response');
        }
        $this->data = $response['result'];
    }
    
    /**
     * Gets the response data.
     *
     * @return mixed The response data.
     */
    public function getData() {
        return $this->data;
    }
    
    /**
     * Gets the response id.
     *
     * @return string The response id.
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * Takes a JSON string as returned from the server and decodes it into an
     * associative array.
     */
    private function decodeResponse($json) {
        if (extension_loaded('mbstring')) {
            $encoding = mb_detect_encoding($json, 'ASCII,UTF-8,ISO-8859-1,windows-1252,iso-8859-15');
            if ($encoding && !in_array($encoding, array('UTF-8', 'ASCII'))) {
                $json = mb_convert_encoding($json, 'UTF-8', $encoding);
            }
        }
        return json_decode($json, true);
    }
    
}
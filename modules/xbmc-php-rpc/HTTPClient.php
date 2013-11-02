<?php

require_once 'Client.php';

class XBMC_RPC_HTTPClient extends XBMC_RPC_Client {
    
    /**
     * @var string The URI of the XBMC server to which requests will be made.
     */
    private $uri;
    
    /**
     * @var resource The curl resource used for making requests.
     */
    private $curlResource;
    
    /**
     * @var int The amount of time for which the script will try to connect before giving up.
     */
    private $timeout = 15;
    
    /**
     * Destructor.
     *
     * Cleans up the curl resource if necessary.
     */
    public function __destruct() {
        if (is_resource($this->curlResource)) {
            curl_close($this->curlResource);
        }
    }
    
    /**
     * Sets the number of seconds the script will wait while trying to connect
     * to the server before giving up.
     *
     * @param int $seconds The number of seconds to wait.
     * @return void
     */
    public function setTimeout($seconds) {
        $this->timeout = (int) $seconds;
        if ($this->timeout < 0) {
            $this->timeout = 0;
        }
    }
    
    /**
     * Asserts that the server is reachable and a connection can be made.
     *
     * @return void
     * @exception XBMC_RPC_ConnectionException if it is not possible to connect to
     * the server.
     */
    protected function assertCanConnect() {
        if (extension_loaded('curl')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $this->uri);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            if (!curl_exec($ch) || !in_array(curl_getinfo($ch, CURLINFO_HTTP_CODE), array('200', '401'))) {
                throw new XBMC_RPC_ConnectionException('Unable to connect to XBMC server via HTTP');
            }
        } else {
            throw new XBMC_RPC_ConnectionException('Cannot test if XBMC server is reachable via HTTP because cURL is not installed');
        }
    }
    
    /**
     * Prepares for a connection to XBMC via HTTP.
     *
     * @exception XBMC_RPC_ClientException if it was not possible to prepare for
     * connection successfully.
     * @access protected
     */
    protected function prepareConnection() {
        if (!$uri = $this->buildUri()) {
            throw new XBMC_RPC_ClientException('Unable to parse server parameters into valid URI string');
        }
        $this->uri = $uri;
    }
    
    /**
     * Sends a JSON-RPC request to XBMC and returns the result.
     *
     * @param string $json A JSON-encoded string representing the remote procedure call.
     * This string should conform to the JSON-RPC 2.0 specification.
     * @param string $rpcId The unique ID of the remote procedure call.
     * @return string The JSON-encoded response string from the server.
     * @exception XBMC_RPC_RequestException if it was not possible to make the request.
     * @access protected
     * @link http://groups.google.com/group/json-rpc/web/json-rpc-2-0 JSON-RPC 2.0 specification
     */
    protected function sendRequest($json, $rpcId) {
        if (empty($this->curlResource)) {
            $this->curlResource = $this->createCurlResource();
        }
        curl_setopt($this->curlResource, CURLOPT_POSTFIELDS, $json);
        curl_setopt($this->curlResource, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        if (!$response = curl_exec($this->curlResource)) {
            throw new XBMC_RPC_RequestException('Could not make a request the server');
        }
        return $response;
    }
    
    /**
     * Builds the server URI from the supplied parameters.
     *
     * @return string The server URI.
     * @access private
     */
    private function buildUri() {
        $parameters = $this->server->getParameters();
        $credentials = '';
        if (!empty($parameters['user'])) {
            $credentials = $parameters['user'];
            $credentials .= empty($parameters['pass']) ? '@' : ':' . $parameters['pass'] . '@';
        }
        return sprintf('http://%s%s:%d/jsonrpc', $credentials, $parameters['host'], $parameters['port']);
    }
    
    /**
     * Creates a curl resource with the correct settings for making JSON-RPC calls
     * to XBMC.
     *
     * @return resource A new curl resource.
     * @access private
     */
    private function createCurlResource() {
        $curlResource = curl_init();
        curl_setopt($curlResource, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlResource, CURLOPT_POST, 1);
        curl_setopt($curlResource, CURLOPT_URL, $this->uri);
        return $curlResource;
    }
    
}

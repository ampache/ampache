<?php

require_once 'Client.php';

class XBMC_RPC_TCPClient extends XBMC_RPC_Client {
    
    /**
     * @var resource A file pointer resource for reading over the connected socket.
     * @access private
     */
    private $fp;
    
    /**
     * Destructor.
     *
     * Cleans up the file resource if necessary.
     */
    public function __destruct() {
        if (is_resource($this->fp)) {
            fclose($this->fp);
        }
    }
    
    /**
     * Asserts that the server is reachable and a connection can be made.
     *
     * @return void
     * @exception XBMC_RPC_ConnectionException if it is not possible to connect to
     * the server.
     * @access protected
     */
    protected function assertCanConnect() {
        if (!$this->canConnect()) {
            throw new XBMC_RPC_ConnectionException('Unable to connect to XBMC server via TCP');
        }
    }
    
    /**
     * Prepares for a connection to XBMC via TCP.
     *
     * @return void
     * @access protected
     */
    protected function prepareConnection() {
        $parameters = $this->server->getParameters();
        $this->fp = @fsockopen($parameters['host'], $parameters['port']);
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
        $this->prepareConnection();
        if (!$this->canConnect()) {
            throw new XBMC_RPC_ConnectionException('Lost connection to XBMC server');
        }
        fwrite($this->fp, $json);
        while (true) {
            $result = $this->readJsonObject();
            if (strpos($result, '"id" : "' . $rpcId . '"') !== false) {
                break;
            }
            if (strpos($result, '"id":"' . $rpcId . '"') !== false) {
                break;
            }
        }
        fclose($this->fp);
        return $result;
    }
    
    /**
     * Checks if it is possible to connect to the server.
     *
     * @return bool True if it is possible to connect, false if not.
     * @access private
     */
    private function canConnect() {
        return is_resource($this->fp);
    }
    
    /**
     * Reads a single JSON object from the socket and returns it.
     *
     * @return string The JSON object string from the server.
     * @access private
     */
    private function readJsonObject() {
        
        $open = $close = 0;
        $escaping = false;
        $quoteChar = null;
        $result = '';
        
        while (false !== ($char = fgetc($this->fp))) {
            if (!$escaping) {
                switch ($char) {
                    case "'":
                    case '"':
                        if (null === $quoteChar) {
                            $quoteChar = $char;
                        } elseif ($quoteChar == $char) {
                            $quoteChar = null;
                        }
                        break;
                    case '{':
                        if (null === $quoteChar) {
                            ++$open;
                        }
                        break;
                    case '}':
                        if (null === $quoteChar) {
                            ++$close;
                        }
                        break;
                    case '\\':
                        $escaping = true;
                        break;
                }
            } else {
                $escaping = false;
            }
            $result .= $char;
            if ($open == $close) {
                break;
            }
        }
        
        return $result;
    }
    
}
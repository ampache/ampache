<?php

require_once 'ServerException.php';

class XBMC_RPC_Server {
    
    /**
     * @var mixed An array of parameters used to connect to the server.
     * @access private
     */
    private $parameters = array();
    
    /**
     * Constructor.
     *
     * @param mixed $parameters An associative array of connection parameters,
     * or a valid connection URI as a string. If supplying an array, the following
     * paramters are accepted: host, port, user and pass. Any other parameters
     * are discarded.
     * @exception XBMC_RPC_ServerException if the supplied parameters could not
     * be parsed successfully.
     * @access public
     */
    public function __construct($parameters) {
        if (!$parameters = $this->parseParameters($parameters)) {
            throw new XBMC_RPC_ServerException('Unable to parse server parameters');
        }
        $this->parameters = $parameters;
    }
    
    /**
     * Checks if the server is connected.
     *
     * @return bool True if the server is connected, false if not.
     * @access public
     */
    public function isConnected() {
        return $this->connected;
    }
    
    /**
     * Gets the connection parameters/
     *
     * @return mixed The connection parameters as an associative array.
     * @access public
     */
    public function getParameters() {
        return $this->parameters;
    }
    
    /**
     * Parses the supplied parameters into a standard associative array format.
     *
     * @param mixed $parameters An associative array of connection parameters,
     * or a valid connection URI as a string. If supplying an array, the following
     * paramters are accepted: host, port, user and pass. Any other parameters
     * are discarded.
     * @return mixed The connection parameters as an associative array, or false
     * if the parameters could not be parsed. The array will have the following
     * keys: host, port, user and pass.
     * @access private
     */
    private function parseParameters($parameters) {
        
        if (is_string($parameters)) {
            $parameters = preg_replace('#^[a-z]+://#i', '', trim($parameters));
            if (!$parameters = parse_url('http://' . $parameters)) {
                return false;
            }
        }
        
        if (!is_array($parameters)) {
            // if parameters are not a string or an array, something is wrong
            return false;
        }
        
        $defaults = array(
            'host' => 'localhost',
            'port' => 8080,
            'user' => null,
            'pass' => null
        );
        $parameters = array_intersect_key(array_merge($defaults, $parameters), $defaults);
        return $parameters;

    }
    
}
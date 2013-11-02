<?php

require_once 'ClientException.php';
require_once 'ConnectionException.php';
require_once 'RequestException.php';
require_once 'ResponseException.php';
require_once 'Server.php';
require_once 'Namespace.php';
require_once 'Response.php';

abstract class XBMC_RPC_Client {
    
    /**
     * @var XBMC_RPC_Server A server object instance representing the server
     * to be used for remote procedure calls.
     * @access protected
     */
    protected $server;
    
    /**
     * @var XBMC_RPC_Namespace The root namespace instance.
     * @access private
     */
    private $rootNamespace;
    
    /**
     * @var bool A flag to indicate if the JSON-RPC version is legacy, ie before
     * the XBMC Eden updates. This can be used to determine the format of commands
     * to be used with this library, allowing client code to support legacy systems.
     * @access private
     */
    private $isLegacy = false;
    
    /**
     * Constructor.
     *
     * Connects to the server and populates a list of available commands by
     * having the server introspect.
     *
     * @param mixed $parameters An associative array of connection parameters,
     * or a valid connection URI as a string. If supplying an array, the following
     * paramters are accepted: host, port, user and pass. Any other parameters
     * are discarded.
     * @exception XBMC_RPC_ConnectionException if it is not possible to connect to
     * the server.
     * @access public
     */
    public function __construct($parameters) {
        try {
            $server = new XBMC_RPC_Server($parameters);
        } catch (XBMC_RPC_ServerException $e) {
            throw new XBMC_RPC_ConnectionException($e->getMessage(), $e->getCode(), $e);
        }
        $this->server = $server;
        $this->prepareConnection();
        $this->assertCanConnect();
        $this->createRootNamespace();
    }
    
    /**
     * Delegates any direct Command calls to the root namespace.
     *
     * @param string $name The name of the called command.
     * @param mixed $arguments An array of arguments used to call the command.
     * @return The result of the command call as returned from the namespace.
     * @exception XBMC_RPC_InvalidCommandException if the called command does not
     * exist in the root namespace.
     * @access public
     */
    public function __call($name, array $arguments) {
        return call_user_func_array(array($this->rootNamespace, $name), $arguments);
    }
    
    /**
     * Delegates namespace accesses to the root namespace.
     *
     * @param string $name The name of the requested namespace.
     * @return XBMC_RPC_Namespace The requested namespace.
     * @exception XBMC_RPC_InvalidNamespaceException if the namespace does not
     * exist in the root namespace.
     * @access public
     */
    public function __get($name) {
        return $this->rootNamespace->$name;
    }
    
    /**
     * Executes a remote procedure call using the supplied XBMC_RPC_Command
     * object.
     *
     * @param XBMC_RPC_Command The command to execute.
     * @return XBMC_RPC_Response The response from the remote procedure call.
     * @access public
     */
    public function executeCommand(XBMC_RPC_Command $command) {
        return $this->sendRpc($command->getFullName(), $command->getArguments());
    }
    
    /**
     * Determines if the XBMC system to which the client is connected is legacy
     * (pre Eden) or not. This is useful because the format of commands/params
     * is different in the Eden RPC implementation.
     *
     * @return bool True if the system is legacy, false if not.
     * @access public
     */
    public function isLegacy() {
        return $this->isLegacy;
    }
    
    /**
     * Asserts that the server is reachable and a connection can be made.
     *
     * @return void
     * @exception XBMC_RPC_ConnectionException if it is not possible to connect to
     * the server.
     * @abstract
     * @access protected
     */
    protected abstract function assertCanConnect();
    
    /**
     * Prepares for a connection to XBMC.
     *
     * Should be used by child classes for any pre-connection logic which is necessary.
     *
     * @return void
     * @exception XBMC_RPC_ClientException if it was not possible to prepare for
     * connection successfully.
     * @abstract
     * @access protected
     */
    protected abstract function prepareConnection();
    
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
    protected abstract function sendRequest($json, $rpcId);
    
    /**
     * Build a JSON-RPC 2.0 compatable json_encoded string representing the
     * specified command, parameters and request id.
     *
     * @param string $command The name of the command to be called.
     * @param mixed $params An array of paramters to be passed to the command.
     * @param string $rpcId A unique string used for identifying the request.
     * @access private
     */
    private function buildJson($command, $params, $rpcId) {
        $data = array(
            'jsonrpc' => '2.0',
            'method' => $command,
            'params' => $params,
            'id' => $rpcId
        );
        return json_encode($data);
    }
    
    /**
     * Ensures that the recieved response from a remote procedure call is valid.
     *
     * $param XBMC_RPC_Response $response A response object encapsulating remote
     * procedure call response data as returned from Client::sendRequest().
     * @return bool True of the reponse is valid, false if not.
     * @access private
     */
    private function checkResponse(XBMC_RPC_Response $response, $rpcId) {
        return ($response->getId() == $rpcId);
    }
    
    /**
     * Creates the root namespace instance.
     *
     * @return void
     * @access private
     */
    private function createRootNamespace() {
        $commands = $this->loadAvailableCommands();
        $this->rootNamespace = new XBMC_RPC_Namespace('root', $commands, $this);
    }
    
    /**
     * Generates a unique string to be used as a remote procedure call ID.
     *
     * @return string A unique string.
     * @access private
     */
    private function getRpcId() {
        return uniqid();
    }
    
    /**
     * Retrieves an array of commands by requesting the RPC server to introspect.
     *
     * @return mixed An array of available commands which may be executed on the server.
     * @exception XBMC_RPC_RequestException if it is not possible to retrieve a list of
     * available commands.
     * @access private
     */
    private function loadAvailableCommands() {
        try {
            $response = $this->sendRpc('JSONRPC.Introspect');
        } catch (XBMC_RPC_Exception $e) {
            throw new XBMC_RPC_RequestException(
                'Unable to retrieve list of available commands: ' . $e->getMessage()
            );
        }
        if (isset($response['commands'])) {
            $this->isLegacy = true;
            return $this->loadAvailableCommandsLegacy($response);
        }
        $commands = array();
        foreach (array_keys($response['methods']) as $command) {
            $array = $this->commandStringToArray($command);
            $commands = $this->mergeCommandArrays($commands, $array);
        }
        return $commands;
    }
    
    /**
     * Retrieves an array of commands by requesting the RPC server to introspect.
     *
     * This method supports the legacy implementation of XBMC's RPC.
     * 
     * @return mixed An array of available commands which may be executed on the server.
     * @access private
     */
    private function loadAvailableCommandsLegacy($response) {
        $commands = array();
        foreach ($response['commands'] as $command) {
            $array = $this->commandStringToArray($command['command']);
            $commands = $this->mergeCommandArrays($commands, $array);
        }
        return $commands;
    }
    
    /**
     * Converts a dot-delimited command name to a multidimensional array format.
     *
     * @return mixed An array representing the command.
     * @access private
     */
    private function commandStringToArray($command) {
        $path = explode('.', $command);
        if (count($path) === 1) {
            $commands[] = $path[0];
            continue;
        }
        $command = array_pop($path);
        $array = array();
        $reference =& $array;
        foreach ($path as $i => $key) {
            if (is_numeric($key) && intval($key) > 0 || $key === '0') {
                $key = intval($key);
            }
            if ($i === count($path) - 1) {
                $reference[$key] = array($command);
            } else {
                if (!isset($reference[$key])) {
                    $reference[$key] = array();
                }
                $reference =& $reference[$key];
            }
        }
        return $array;
    }
    
    /**
     * Recursively merges the supplied arrays whilst ensuring that commands are
     * not duplicated within a namespace.
     *
     * Note that array_merge_recursive is not suitable here as it does not ensure
     * that values are distinct within an array.
     *
     * @param mixed $base The base array into which $append will be merged.
     * @param mixed $append The array to merge into $base.
     * @return mixed The merged array of commands and namespaces.
     * @access private
     */
    private function mergeCommandArrays(array $base, array $append) {
        foreach ($append as $key => $value) {
            if (!array_key_exists($key, $base) && !is_numeric($key)) {
                $base[$key] = $append[$key];
                continue;
            }
            if (is_array($value) || is_array($base[$key])) {
                $base[$key] = $this->mergeCommandArrays($base[$key], $append[$key]);
            } elseif (is_numeric($key)) {
                if (!in_array($value, $base)) {
                    $base[] = $value;
                }
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }
    
    /**
     * Executes a remote procedure call using the supplied command name and parameters.
     *
     * @param string $command The full, dot-delimited name of the command to call.
     * @param mixed $params An array of parameters to be passed to the called method.
     * @return mixed The data returned from the response.
     * @exception XBMC_RPC_RequestException if it was not possible to make the request.
     * @exception XBMC_RPC_ResponseException if the response was not being properly received.
     * @access private
     */
    private function sendRpc($command, $params = array()) {
        $rpcId = $this->getRpcId();
        $json = $this->buildJson($command, $params, $rpcId);
        $response = new XBMC_RPC_Response($this->sendRequest($json, $rpcId));
        if (!$this->checkResponse($response, $rpcId)) {
            throw new XBMC_RPC_ResponseException('JSON RPC request/response ID mismatch');
        }
        return $response->getData();
    }
    
}
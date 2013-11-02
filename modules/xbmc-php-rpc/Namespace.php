<?php

require_once 'InvalidNamespaceException.php';
require_once 'InvalidCommandException.php';
require_once 'Command.php';

/**
 * A collection of commands and namespaces.
 */
class XBMC_RPC_Namespace {
    
    /**
     * @var string The name of the namespace.
     * @access private
     */
    private $name;
    
    /**
     * @var XBMC_RPC_Command The parent namespace of the current instance.
     * @access private
     */
    private $parentNamespace;
    
    /**
     * @var The tree of commands and namespaces which are children of the current
     * instance.
     * @access private
     */
    private $children = array();
    
    /**
     * @var A cache of child XBMC_RPC_Command and XBMC_RPC_Namespace objects.
     * @access private
     */
    private $objectCache = array();
    
    /**
     * @var An instance of XBMC_RPC_Client to which this XBMC_RPC_Namespace
     * instance belongs.
     * @access private
     */
    private $client;
    
    /**
     * Constructor.
     *
     * @param string $name The name of the namespace.
     * @param mixed $children An array of commands and namespaces which are
     * children of the current instance.
     * @param XBMC_RPC_Client $client The client instance to which this instance
     * belongs.
     * @param mixed $parent The parent XBMC_RPC_Namespace object, or null if this
     * instance is the root namespace.
     * @access public
     */
    public function __construct($name, array $children, XBMC_RPC_Client $client, XBMC_RPC_Namespace $parent = null) {
        $this->name = $name;
        $this->children = $children;
        $this->client = $client;
        $this->parentNamespace = $parent;
    }
    
    /**
     * Executes the called command.
     *
     * @param string $name The name of the command to call.
     * @arguments mixed An array of arguments to be send with the remote
     * procedure call.
     * @return XBMC_RPC_Response The response of the remote procedure call.
     * @exception XBMC_RPC_InvalidCommandException if the requested command does
     * not exist in this namespace.
     * @access public
     */
    public function __call($name, array $arguments) {
        $this->assertHasChildCommand($name);
        if (empty($this->objectCache[$name])) {
            $this->objectCache[$name] = new XBMC_RPC_Command($name, $this->client, $this);
        }
        return $this->objectCache[$name]->execute($arguments);
    }
    
    /**
     * Gets the requested child namespace.
     *
     * @param string $name The name of the namespace to get.
     * @return XBMC_RPC_Namespace The requested child namespace.
     * @exception XBMC_RPC_InvalidNamespaceException if the requested namespace does
     * not exist in this namespace.
     * @access public
     */
    public function __get($name) {
        $this->assertHasChildNamespace($name);
        if (empty($this->objectCache[$name])) {
            $this->objectCache[$name] = new XBMC_RPC_Namespace($name, $this->children[$name], $this->client, $this);
        }
        return $this->objectCache[$name];
    }
    
    /**
     * Gets the full dot-delimited string representing the path from the root
     * namespace to the current namespace.
     *
     * @return string The dot-delimited string.
     * @access public
     */
    public function getFullName() {
        $name = '';
        if (!empty($this->parentNamespace)) {
            $name = $this->parentNamespace->getFullName() . '.' . $this->name;
        }
        return trim($name, '.');
    }
    
    /**
     * Asserts that the the namespace contains the specified command as a direct
     * child.
     *
     * @param string $name The name of the command to check for.
     * @exception XBMC_RPC_InvalidCommandException if the command is not a direct
     * child of this namespace.
     * @access private
     */
    private function assertHasChildCommand($name) {
        if (!$this->hasChildCommand($name)) {
            throw new XBMC_RPC_InvalidCommandException("Command $name does not exist in namespace $this->name");
        }
    }
    
    /**
     * Asserts that the the namespace contains the specified namespace as a direct
     * child.
     *
     * @param string $name The name of the namespace to check for.
     * @exception XBMC_RPC_InvalidNamespaceException if the namespace is not a direct
     * child of this namespace.
     * @access private
     */
    private function assertHasChildNamespace($name) {
        if (!$this->hasChildNamespace($name)) {
            throw new XBMC_RPC_InvalidNamespaceException("Namespace $name does not exist in namespace $this->name");
        }
    }
    
    /**
     * Checks if the namespace has the specified command as a direct child.
     *
     * @param string $name The name of the command to check for.
     * @return bool True if the command exists in this namespace, false if not.
     * @access private
     */
    private function hasChildCommand($name) {
        return in_array($name, $this->children);
    }
    
    /**
     * Checks if the namespace has the specified namespace as a direct child.
     *
     * @param string $name The name of the namespace to check for.
     * @return bool True if the namespace exists in this namespace, false if not.
     * @access private
     */
    private function hasChildNamespace($name) {
        return array_key_exists($name, $this->children);
    }
    
}
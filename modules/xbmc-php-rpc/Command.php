<?php

/**
 * A JSON-RPC command.
 */
class XBMC_RPC_Command {
    
    /**
     * @var string The name of the command.
     * @access private
     */
    private $name;
    
    /**
     * @var The namespace to which the instance belongs.
     * @access private
     */
    private $parentNamespace;
    
    /**
     * @var The client to be used for executing the command.
     * @access private
     */
    private $client;
    
    /**
     * @var An array of arguments to be passed with the command.
     * @access private
     */
    private $arguments = array();
    
    /**
     * Constructor.
     *
     * @param string $name The name of the desired command.
     * @param XBMC_RPC_Client $client The client instance responsible for operating
     * the Command instance.
     * @param mixed $parent The parent XBMC_RPC_Command object, or null if there is
     * no parent of this instance.
     * @access public
     */
    public function __construct($name, XBMC_RPC_Client $client, XBMC_RPC_Namespace $parent) {
        $this->name = $name;
        $this->parentNamespace = $parent;
        $this->client = $client;
    }
    
    /**
     * Executes the remote procedure call command.
     *
     * @param mixed $arguments An array of arguments to be passed along with the
     * command.
     * @return mixed The response data as returned from XBMC_RPC_Client::sendRpc().
     * @exception XBMC_RPC_Exception if the remote procedure call could be carried
     * out successfully.
     * @access public
     */
    public function execute(array $arguments = array()) {
        if (count($arguments) == 1) {
            $arguments = array_shift($arguments);
        }
        $this->arguments = $arguments;
        return $this->client->executeCommand($this);
    }
    
    /**
     * Gets an array of arguments which accompany the command.
     *
     * @return mixed The array of argument which accompany this command.
     * @access public
     */
    public function getArguments() {
        return $this->arguments;
    }
    
    /**
     * Gets the full, dot-delimited name of the command including its namespace path.
     *
     * @return string The command name.
     * @access public
     */
    public function getFullName() {
        return $this->parentNamespace->getFullName() . '.' . $this->name;
    }
    
}
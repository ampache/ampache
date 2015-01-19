<?php

/**
 * Supplies Redis server store backend for OpenID servers and consumers.
 * Uses Predis library {@see https://github.com/nrk/predis}.
 * Requires PHP >= 5.3.
 *
 * LICENSE: See the COPYING file included in this distribution.
 *
 * @package OpenID
 * @author Ville Mattila <ville@eventio.fi>
 * @copyright 2008 JanRain Inc., 2013 Eventio Oy / Ville Mattila
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache
 * Contributed by Eventio Oy <http://www.eventio.fi/>
 */

/**
 * Import the interface for creating a new store class.
 */
require_once 'Auth/OpenID/Interface.php';

/**
 * Supplies Redis server store backend for OpenID servers and consumers.
 * Uses Predis library {@see https://github.com/nrk/predis}.
 * Requires PHP >= 5.3.
 * 
 * @package OpenID
 */
class Auth_OpenID_PredisStore extends Auth_OpenID_OpenIDStore {

    /**
     * @var \Predis\Client
     */
    protected $redis;

    /**
     * Prefix for Redis keys
     * @var string
     */
    protected $prefix;

    /**
     * Initializes a new {@link Auth_OpenID_PredisStore} instance.
     *
     * @param \Predis\Client $redis  Predis client object
     * @param string         $prefix Prefix for all keys stored to the Redis
     */
    function Auth_OpenID_PredisStore(\Predis\Client $redis, $prefix = '')
    {
        $this->prefix = $prefix;
        $this->redis = $redis;
    }

    /**
     * Store association until its expiration time in Redis server. 
     * Overwrites any existing association with same server_url and 
     * handle. Handles list of associations for every server. 
     */
    function storeAssociation($server_url, $association)
    {
        // create Redis keys for association itself 
        // and list of associations for this server
        $associationKey = $this->associationKey($server_url, 
            $association->handle);
        $serverKey = $this->associationServerKey($server_url);
        
        // save association to server's associations' keys list
        $this->redis->lpush(
            $serverKey,
            $associationKey
        );

        // Will touch the association list expiration, to avoid filling up
        $newExpiration = ($association->issued + $association->lifetime);

        $expirationKey = $serverKey.'_expires_at';
        $expiration = $this->redis->get($expirationKey);
        if (!$expiration || $newExpiration > $expiration) {
            $this->redis->set($expirationKey, $newExpiration);
            $this->redis->expireat($serverKey, $newExpiration);
            $this->redis->expireat($expirationKey, $newExpiration);
        }

        // save association itself, will automatically expire
        $this->redis->setex(
            $associationKey,
            $newExpiration - time(),
            serialize($association)
        );
    }

    /**
     * Read association from Redis. If no handle given 
     * and multiple associations found, returns latest issued
     */
    function getAssociation($server_url, $handle = null)
    {
        // simple case: handle given
        if ($handle !== null) {
            return $this->getAssociationFromServer(
                $this->associationKey($server_url, $handle)
            );
        }
        
        // no handle given, receiving the latest issued
        $serverKey = $this->associationServerKey($server_url);
        $lastKey = $this->redis->lpop($serverKey);
        if (!$lastKey) { return null; }

        // get association, return null if failed
        return $this->getAssociationFromServer($lastKey);
    }
    
    /**
     * Function to actually receive and unserialize the association
     * from the server.
     */
    private function getAssociationFromServer($associationKey)
    {
        $association = $this->redis->get($associationKey);
        return $association ? unserialize($association) : null;
    }

    /**
     * Immediately delete association from Redis.
     */
    function removeAssociation($server_url, $handle)
    {
        // create Redis keys
        $serverKey = $this->associationServerKey($server_url);
        $associationKey = $this->associationKey($server_url, 
            $handle);
        
        // Removing the association from the server's association list
        $removed = $this->redis->lrem($serverKey, 0, $associationKey);
        if ($removed < 1) {
            return false;
        }

        // Delete the association itself
        return $this->redis->del($associationKey);
    }

    /**
     * Create nonce for server and salt, expiring after 
     * $Auth_OpenID_SKEW seconds.
     */
    function useNonce($server_url, $timestamp, $salt)
    {
        global $Auth_OpenID_SKEW;
        
        // save one request to memcache when nonce obviously expired 
        if (abs($timestamp - time()) > $Auth_OpenID_SKEW) {
            return false;
        }
        
        // SETNX will set the value only of the key doesn't exist yet.
        $nonceKey = $this->nonceKey($server_url, $salt);
        $added = $this->predis->setnx($nonceKey);
        if ($added) {
            // Will set expiration
            $this->predis->expire($nonceKey, $Auth_OpenID_SKEW);
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Build up nonce key
     */
    private function nonceKey($server_url, $salt)
    {
        return $this->prefix .
               'openid_nonce_' .
               sha1($server_url) . '_' . sha1($salt);
    }
    
    /**
     * Key is prefixed with $prefix and 'openid_association_' string
     */
    function associationKey($server_url, $handle = null) 
    {
        return $this->prefix .
               'openid_association_' .
               sha1($server_url) . '_' . sha1($handle);
    }
    
    /**
     * Key is prefixed with $prefix and 'openid_association_server_' string
     */
    function associationServerKey($server_url) 
    {
        return $this->prefix .
               'openid_association_server_' .
               sha1($server_url);
    }
    
    /**
     * Report that this storage doesn't support cleanup
     */
    function supportsCleanup()
    {
        return false;
    }

}


<?php
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */



/**
 * This class defines custom LDAP exceptions that will be used in the
 * main LDAP class.
 */
class LDAPException extends Exception
{
    /**
     * A LDAPException may be constructed thanks to a message, or an error
     * code. If the given argument is an integer, the exception will be
     * produced with message:
     *
     *     LDAP error: [errno] errmsg
     *
     * Otherwise, the provided message will be used.
     *
     * @param mixed $message
     */
    public function __construct ($message)
    {
        if (is_int ($message)) {
            $message = 'LDAP error: [' . $message . '] ' . ldap_err2str($message);
        }

        debug_event('LDAP', 'Exception: ' . $message, 6);
        parent::__construct ($message);
    }
}



/**
 * This class handles all the contacts with a LDAP server
 */
class LDAP
{
    /**
     * Constructor
     *
     * This should never be called
     */
    public function __construct()
    {
        debug_event('LDAP', '__construct has been called. This should not happen', 2);
    }


    /**
     * Connect to the LDAP
     * Note: This does not open a connection. It checks whether
     * the given parameters are plausibe and can be used to open a
     * connection as soon as one is needed.
     */
    private static function connect ()
    {
        if (! $url = AmpConfig::get('ldap_url')) {
            throw new LDAPException('Required configuration value missing: ldap_url');
        }

        if (! $link = ldap_connect ($url)) {
            throw new LDAPException('Could not connect to ' . $url);
        }

        $protocol_version = AmpConfig::get('ldap_protocol_version', 3);
        if (! ldap_set_option ($link, LDAP_OPT_PROTOCOL_VERSION, $protocol_version)) {
            throw new LDAPException('Could not set option PROTOCOL_VERSION to ' . $protocol_version);
        }

        if (AmpConfig::get('ldap_start_tls', "false") != "false") {
            if (! ldap_start_tls ($link)) {
                throw new LDAPException('Could not use StartTLS');
            }
        }

        return $link;
    }

    
    /**
     * Binds to the LDAP
     */
    private static function bind ($link, $username = null, $password = null)
    {
        if ($username === null && $password === null) {
            $username = AmpConfig::get('ldap_username', '');
            $password = AmpConfig::get('ldap_password', '');
        }

        if (! ldap_bind ($link, $ampache_username, $ampache_password)) {
            throw new LDAPException("Could not bind to server using username `$username`");
        }
    }

    
    /**
     * Unbinds from the LDAP
     */
    private static function unbind ($link)
    {
        ldap_unbind ($link);
    }


    /**
     * Read attributes for a DN from the LDAP
     */
    private static function read ($link, $dn, $attrs = [], $filter='objectClass=*')
    {
        if (! $result = ldap_read ($link, $dn, $filter, $attrs)) {
            $attrs = json_encode ($attrs);
            throw new LDAPException("Could not read attributes `$attrs` for dn `$dn`");
        }

        if (! $infos = ldap_get_entries ($link, $result)) {
            throw new LDAPException("Empty search result for dn `$dn`");
        }

        return $infos[0];
    }


    /**
     * Search for a DN in the LDAP
     */
    private static function search ($link, $base_dn, $filter)
    {
        if (! $result = ldap_search ($link, $base_dn, $filter)) {
            throw new LDAPException(ldap_errno($link));
        }

        if (! $entries = ldap_get_entries ($link, $result)) {
            throw new LDAPException("Empty search results for filter `$filter`");
        }

        return $entries[0];
    }
    

    
    /**
     * ldap_auth
     *
     * This handles authentication against a LDAP server.
     *
     * @param string $username
     * @param string $password
     * @return array
     */
    public static function auth ($username, $password)
    {
        try {
            $link = self::connect ();
            self::bind($link);

            /* Search for the user with given base_dn, filter, objectclass and username */

            if (! $filter = AmpConfig::get('ldap_filter')) {
                throw new LDAPException('Required configuration value missing: ldap_filter');
            }

            if (strpos ($filter, '%v') !== false) {
                $filter = str_replace('%v', $username, $filter);
            } else {
                $filter = "($filter=$username)"; // Backward compatibility
            }

            if (! $objectclass = AmpConfig::get('ldap_objectclass')) {
                throw new LDAPException('Required configuration value missing: ldap_objectclass');
            }

            $search = "(&(objectclass=$objectclass)$filter)";
            debug_event ('LDAP', 'search: ' . $search, 5);

            if (! $base_dn = AmpConfig::get('ldap_search_dn')) {
                throw new LDAPException('Required configuration value missing: ldap_search_dn');
            }

            $user_entry = self::search ($link, $base_dn, $search);

            if (! $user_dn = ldap_get_dn ($link, $user_entry)) {
                throw new LDAPException(ldap_errno($link));
            }

            self::bind ($link, $user_dn, $password);

            /* Test if the user is in the required group (optional) */

            if ($group_dn = AmpConfig::get('ldap_require_group')) {
                $member_attribute = AmpConfig::get('ldap_member_attribute', 'member');

                $group_infos = self::read ($link, $group_dn, [$member_attribute]);

                /* if (! in_array ($username, $group_infos[$member_attribute])) { */
        if (! preg_grep("/^$user_dn\$/i", $group_infos[$member_attribute])) {
            throw new LDAPException("`$username` is not member of the group `$group_dn`");
        }
            }

            /* Obtain name and email field. Reconstruct name field to allow
               custom things like "givenName sn" */

            $name_field  = AmpConfig::get('ldap_name_field', 'cn');
            $name_fields = explode(' ', $name_field);
            $names       = array_map (function ($name_field) use ($user_entry) {
                    return $user_entry[$name_field][0]; }, $name_fields);
            $name = trim(implode(' ', $names));

            $email_field = AmpConfig::get('ldap_email_field', 'mail');
            $email       = $user_entry[$email_field][0];

            $return_value = [
                'success'  => true,
                'type'     => 'ldap',
                'username' => $username,
                'name'     => $name,
                'email'    => $email
            ];
        } catch (LDAPException $e) {
            $message = $e->getMessage();

            debug_event('LDAP', 'Error during authentication: ' . $message, 3);

            $return_value = [
                'success' => false,
                'error'   => $message
            ];
        }

        if (isset ($link)) {
            self::unbind ($link);
        }

        debug_event('LDAP', 'Return value of authentication: ' . json_encode($return_value), 6);

        return $return_value;
    }
}

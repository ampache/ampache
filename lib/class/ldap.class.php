<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

/**
 * array_filter_key
 *
 * @param array $array
 * @param string $callback
 * @return array
 */
function array_filter_key($array, $callback)
{
    return array_filter($array, $callback, ARRAY_FILTER_USE_KEY);
}

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
    public function __construct($message)
    {
        if (is_int($message)) {
            $message = 'LDAP error: [' . $message . '] ' . ldap_err2str($message);
        }

        debug_event(self::class, 'Exception: ' . $message, 3);
        parent::__construct($message);
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
        debug_event(self::class, '__construct has been called. This should not happen', 2);
    }

    /** Utility functions */

    /**
     * clean_search_results
     *
     * This function is here to return a real array {number} => {field} => {value array}
     * instead of the custom LDAP search results provided by the ldap_* library.
     * @param array $searchresult
     * @return array
     */
    private static function clean_search_results($searchresult)
    {
        $sr_clean = [];

        foreach (array_filter_key($searchresult, 'is_int') as $i => $result) {
            $sr_clean[$i] = [];

            foreach ($result as $field => $values) {
                if ($field == 'count' || is_int($field)) {
                    continue;
                } elseif ($field == 'dn') {
                    $sr_clean[$i][$field] = $values;
                } else {
                    $sr_clean[$i][$field] = array_filter_key($values, 'is_int');
                }
            }
        }

        return $sr_clean;
    }

    /** Actual LDAP functions */

    /**
     * Connect to the LDAP
     * Note: This does not open a connection. It checks whether
     * the given parameters are plausible and can be used to open a
     * connection as soon as one is needed.
     * @return resource|false
     * @throws LDAPException
     */
    private static function connect()
    {
        if (! $url = AmpConfig::get('ldap_url')) {
            throw new LDAPException('Required configuration value missing: ldap_url');
        }

        if (! $link = ldap_connect($url)) {
            throw new LDAPException('Could not connect to ' . $url);
        }

        $protocol_version = AmpConfig::get('ldap_protocol_version', 3);
        if (! ldap_set_option($link, LDAP_OPT_PROTOCOL_VERSION, $protocol_version)) {
            throw new LDAPException('Could not set option PROTOCOL_VERSION to ' . $protocol_version);
        }

        if (AmpConfig::get('ldap_start_tls', "false") != "false") {
            if (! ldap_start_tls($link)) {
                throw new LDAPException('Could not use StartTLS');
            }
        }

        return $link;
    }

    /**
     * Binds to the LDAP
     * @param $link
     * @param string $username
     * @param string $password
     * @throws LDAPException
     */
    private static function bind($link, $username = null, $password = null)
    {
        if ($username === null && $password === null) {
            $username = AmpConfig::get('ldap_username', '');
            $password = AmpConfig::get('ldap_password', '');
        }
        debug_event(self::class, "binding with username `$username`", 5);

        if (! ldap_bind($link, $username, $password)) {
            throw new LDAPException("Could not bind to server using username `$username`");
        }
    }

    /**
     * Unbinds from the LDAP
     * @param $link
     */
    private static function unbind($link)
    {
        ldap_unbind($link);
    }

    /**
     * Read attributes for a DN from the LDAP
     * @param $link
     * @param string $base_dn
     * @param array $attrs
     * @param string $filter
     * @return mixed
     * @throws LDAPException
     */
    private static function read($link, $base_dn, $attrs = [], $filter = 'objectClass=*')
    {
        $attrs_json = json_encode($attrs);
        debug_event(self::class, "reading attributes $attrs_json in `$base_dn`", 5);

        if (! $result = ldap_read($link, $base_dn, $filter, $attrs)) {
            throw new LDAPException("Could not read attributes `$attrs_json` for dn `$base_dn`");
        }

        if (! $infos = ldap_get_entries($link, $result)) {
            throw new LDAPException("Empty search result for dn `$base_dn`");
        }

        return $infos[0];
    }

    /**
     * Search for a DN in the LDAP
     * @param $link
     * @param $base_dn
     * @param string $filter
     * @param boolean $only_one_result
     * @return array
     * @throws LDAPException
     */
    private static function search($link, $base_dn, $filter, $only_one_result = true)
    {
        debug_event(self::class, "searching in `$base_dn` for `$filter`", 5);

        if (! $result = ldap_search($link, $base_dn, $filter)) {
            throw new LDAPException(ldap_errno($link));
        }

        $entries = ldap_get_entries($link, $result);

        $entries = self::clean_search_results($entries);

        if ($only_one_result) {
            if (count($entries) < 1) {
                throw new LDAPException("Empty search results for filter `$filter`");
            }

            if (count($entries) > 1) {
                throw new LDAPException("Too many search results for filter `$filter`");
            }

            return $entries[0];
        } else {
            return $entries;
        }
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
    public static function auth($username, $password)
    {
        try {
            $link = self::connect();
            self::bind($link);

            /* Search for the user with given base_dn, filter, objectclass and username */

            if (! $filter = AmpConfig::get('ldap_filter')) {
                throw new LDAPException('Required configuration value missing: ldap_filter');
            }

            if (strpos($filter, '%v') !== false) {
                $filter = str_replace('%v', $username, $filter);
            } else {
                $filter = "($filter=$username)"; // Backward compatibility
            }

            if (! $objectclass = AmpConfig::get('ldap_objectclass')) {
                throw new LDAPException('Required configuration value missing: ldap_objectclass');
            }

            $search = "(&(objectclass=$objectclass)$filter)";
            debug_event(self::class, 'search: ' . $search, 5);

            if (! $base_dn = AmpConfig::get('ldap_search_dn')) {
                throw new LDAPException('Required configuration value missing: ldap_search_dn');
            }

            $user_entry = self::search($link, $base_dn, $search);
            $user_dn    = $user_entry['dn'];

            self::bind($link, $user_dn, $password);

            /* Test if the user is in the required group (optional) */

            if ($group_dn = AmpConfig::get('ldap_require_group')) {
                $member_attribute = AmpConfig::get('ldap_member_attribute', 'member');

                $group_infos = self::read($link, $group_dn, [$member_attribute]);

                if (! preg_grep("/^$user_dn\$/i", $group_infos[$member_attribute])) {
                    throw new LDAPException("`$user_dn` is not member of the group `$group_dn`");
                }
            }

            /* Obtain name and email field. Reconstruct name field to allow
               custom things like "givenName sn" */

            $name_field  = AmpConfig::get('ldap_name_field', 'cn');
            $name        = $user_entry[strtolower((string) $name_field)][0];

            $email_field = AmpConfig::get('ldap_email_field', 'mail');
            $email       = $user_entry[strtolower((string) $email_field)][0];

            $return_value = [
                'success' => true,
                'type' => 'ldap',
                'username' => $username,
                'name' => $name,
                'email' => $email
            ];

            if (($state_field = AmpConfig::get('ldap_state_field')) !== null) {
                $return_value['state'] = $user_entry[strtolower((string) $state_field)][0];
            }

            if (($city_field = AmpConfig::get('ldap_city_field')) !== null) {
                $return_value['city'] = $user_entry[strtolower((string) $city_field)][0];
            }

            if (($avatar_field = AmpConfig::get('ldap_avatar_field')) !== null) {
                $return_value['avatar'] = [
            'data' => $user_entry[strtolower((string) $avatar_field)][0],
            'mime' => AmpConfig::get('ldap_avatar_mime', 'image/jpeg'),
        ];
            }
        } catch (LDAPException $error) {
            $message = $error->getMessage();

            debug_event(self::class, 'Error during authentication: ' . $message, 3);

            $return_value = [
                'success' => false,
                'error' => $message
            ];
        }

        if (isset($link)) {
            self::unbind($link);
        }

        debug_event(self::class, 'Return value of authentication: ' . json_encode($return_value), 5);

        return $return_value;
    }
} // end ldap.class

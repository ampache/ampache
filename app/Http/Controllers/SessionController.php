<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Session;

class SessionController extends Controller
{

    /**
     * exists
     *
     * This checks to see if the specified session of the specified type
     * exists
     * based on the type.
     */
    public static function exists($type, $key)
    {
        // Switch on the type they pass
        switch ($type) {
            case 'api':
                return true;
            case 'stream':
                $result = \App\Models\Session::where([['id','=', $key], ['expiry', '>', time()]])->get();
    
                if ($results) {
                    return true;
                }
                break;
            case 'interface':
                $result = \App\Models\Session::where([['id','=', $key], ['expiry', '>', time()]])->get();
                
                if ($result->count() > 0) {
                    return true;
                }
                break;
            default:
                return false;
        }
    
        // Default to false
        return false;
    }
    /**
     * create
     * This is called when you want to create a new session
     * it takes care of setting the initial cookie, and inserting the first
     * chunk of data, nifty ain't it!
     */
    public static function create($data)
    {
        // Regenerate the session ID to prevent fixation
        switch ($data['type']) {
            case 'api':
                $key = isset($data['apikey'])
                ? $data['apikey']
                : md5(uniqid(rand(), true));
                break;
            case 'stream':
                $key = isset($data['sid'])
                ? $data['sid']
                : md5(uniqid(rand(), true));
                break;
            case 'mysql':
            default:
    
                // Before refresh we don't have the cookie so we
                // have to use session ID
                $key = $_COOKIE[config('session.session_name')];
                break;
        } // end switch on data type
    
        $username = '';
        if (isset($data['username'])) {
            $username = $data['username'];
        }
        $ip    = $_SERVER['REMOTE_ADDR'] ? $_SERVER['REMOTE_ADDR'] : '0';
        $type  = $data['type'];
        $value = '';
        if (isset($data['value'])) {
            $value = $data['value'];
        }
        $agent = (!empty($data['agent'])) ? $data['agent'] : substr($_SERVER['HTTP_USER_AGENT'], 0, 254);
    
        if ($type == 'stream') {
            $expiry = time() + (config('session.stream_length') * 1200);
        } else {
            $expiry = time() + (config('session.lifetime') * 1200);
        }
    
        $latitude = null;
        if (isset($data['geo_latitude'])) {
            $latitude = $data['geo_latitude'];
        }
        $longitude = null;
        if (isset($data['geo_longitude'])) {
            $longitude = $data['geo_longitude'];
        }
        $geoname = null;
        if (isset($data['geo_name'])) {
            $geoname = $data['geo_name'];
        }
    
        if (!strlen($value)) {
            $value = ' ';
        }
        
        $db_results = Session::create(array('id' => $key, 'username' => $username,'ip' => $ip,'type' => $type, 'user_agent' => $agent,
            'value' => $value, 'expiry' => $expiry, 'geo_latitude' => $latitude, 'geo_longitude' => $longitude, 'geo_name' => $geoname
        ));
    
        if (!$db_results) {
            debug_event('session', 'Session creation failed', '1');
    
            return false;
        }
    
        //		debug_event('session', 'Session created: ' . $key, '5');
    
        return $key;
    }
}

<?php
/*
Copyright (C) 2012 raydan

http://code.google.com/p/unofficial-google-music-api-php/

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
if(!defined('IN_GMAPI')) { die('...'); }

class GM_Session {
	private static $_LOGIN_NORMAL = 0;
	private static $_LOGIN_USE_SESSION = 1;
	private static $_LOGIN_USE_FILE = 2;

	private static $_user_agent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
	private static $_android_url = 'http://android.clients.google.com';
	private static $_jumper_url = 'http://uploadsj.clients.google.com';
	
	private $_logged_in;
	private $_cookie_string;
	private $_cookie_xt;
	private $_auth;
	private $_sid;
	private $_hash;
	private $_gmapi;
	private $_login_type;
	
	public function __construct($gmapi) {
		$this->_login_type = GM_Session::$_LOGIN_NORMAL;
		$this->_logged_in = false;
		$this->_gmapi = $gmapi;
	}
	
	public function __destruct() {
	
	}
	
	public function getLoginResultType() {
		if($this->_logged_in == false)
			return "fail";
		switch($this->_login_type)
		{
			case GM_Session::$_LOGIN_USE_SESSION:
				return "session";
			case GM_Session::$_LOGIN_USE_FILE:
				return "file";
			default:
				return "normal";
		}
	}
	
	private function getSessionName($name) {
		return "GMAPI_".$this->_hash."_".$name;
	}
	
	private function LoadSessionFromFile() {
		$path = GMAPI_SESSION_PATH.$this->_hash.".txt";
		if(!file_exists($path))
			return;
		
		$content = file_get_contents($path);
		$array = unserialize($content);
		
		if(!isset($array['EXPIRE_TIME']) || date("U") > $array['EXPIRE_TIME']) {
			$this->logout();
			@unlink($path);
			return;
		}
		$_SESSION[$this->getSessionName('SID')] = $array['SID'];
		$_SESSION[$this->getSessionName('AUTH')] = $array['AUTH'];
		$_SESSION[$this->getSessionName('COOKIE')] = $array['COOKIE'];
		$_SESSION[$this->getSessionName('COOKIE_XT')] = $array['COOKIE_XT'];
	}
	
	private function SaveSessionToFile() {
		if(!$this->_gmapi->isEnableSessionFile())
			return;
		
		$path = GMAPI_SESSION_PATH.$this->_hash.".txt";
		
		$array = Array();
		$array['SID'] = $_SESSION[$this->getSessionName('SID')];
		$array['AUTH'] = $_SESSION[$this->getSessionName('AUTH')];
		$array['COOKIE'] = $_SESSION[$this->getSessionName('COOKIE')];
		$array['COOKIE_XT'] = $_SESSION[$this->getSessionName('COOKIE_XT')];
		$array['EXPIRE_TIME'] = date("U")+(60*60*20);
		$Handle = fopen($path, 'w');
		if($Handle)
		{
			fwrite($Handle, serialize($array));
			fclose($Handle);
		} else {
			GMAPIPrintDebug("unable write session file");
		}
	}
	
	private function restore($email, $password) {
		if(empty($_SESSION[$this->getSessionName('SID')]) ||
			empty($_SESSION[$this->getSessionName('AUTH')]) ||
			empty($_SESSION[$this->getSessionName('COOKIE')]) ||
			empty($_SESSION[$this->getSessionName('COOKIE_XT')]))
		{
			$this->LoadSessionFromFile();
		} else {
			$this->_login_type = GM_Session::$_LOGIN_USE_SESSION;
		}

		if(!empty($_SESSION[$this->getSessionName('SID')]) &&
			!empty($_SESSION[$this->getSessionName('AUTH')]) &&
			!empty($_SESSION[$this->getSessionName('COOKIE')]) &&
			!empty($_SESSION[$this->getSessionName('COOKIE_XT')]))
		{
			$this->_sid = $_SESSION[$this->getSessionName('SID')];
			$this->_auth = $_SESSION[$this->getSessionName('AUTH')];
			$this->_cookie_string = $_SESSION[$this->getSessionName('COOKIE')];
			$this->_cookie_xt = $_SESSION[$this->getSessionName('COOKIE_XT')];
			$this->_logged_in = true;
			
			if($this->_gmapi->isSkipRestoreCheck()) {
				if($this->_login_type == GM_Session::$_LOGIN_NORMAL)
				{
					$this->_login_type = GM_Session::$_LOGIN_USE_FILE;
				}
				return;
			}
			
			$headers = array(
				'Authorization: GoogleLogin auth='. $this->_auth,
				'User-agent: '.GM_Session::$_user_agent
			);
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://play.google.com/music/listen");
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_NOBODY, true);
			$result = GMAPIUtils::curl_redir_exec($ch, true);
			$code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
			curl_close($ch);
			
			switch($code) {
				case 200:
					$this->_logged_in = true;
					if($this->_login_type == GM_Session::$_LOGIN_NORMAL)
					{
						$this->_login_type = GM_Session::$_LOGIN_USE_FILE;
					}
					break;
				default:
					GMAPIPrintDebug("unable restore login session");
					$this->logout();
					break;
			}
		}
	}
	
	public static function clearAllSession() {
		foreach($_SESSION as $key=>$value) {
			if(strncmp($key, "GMAPI_", strlen("GMAPI_")) == 0) {
				unset($_SESSION[$key]);
			}
		}
	}
	
	public function logout() {
		$this->_logged_in = false;
		unset($_SESSION[$this->getSessionName('SID')]);
		unset($_SESSION[$this->getSessionName('AUTH')]);
		unset($_SESSION[$this->getSessionName('COOKIE')]);
		unset($_SESSION[$this->getSessionName('COOKIE_XT')]);
		$this->_sid = "";
		$this->_auth = "";
		$this->_cookie_string = "";
		$this->_cookie_xt = "";
	}
	
	public function isLoggedIn() {
		return ($this->_logged_in);
	}
	
	private function generateHash($email, $password) {
		return md5(GMAPI_SECRET.$email.$password);
	}
	
	public function login($email, $password) {
		$this->_hash = $this->generateHash($email, $password);
		
		if($this->_gmapi->isEnableRestore()) {
			$this->restore($email, $password);
		}
		
		if($this->isLoggedIn())
			return true;
		
		$start = microtime();
		$start = explode(" ",$start);
		$start_time = $start[1] + $start[0];
		
		$data = array('accountType' => 'HOSTED_OR_GOOGLE',
			'Email' => $email,
			'Passwd' => $password,
			'source' => 'GM_API',
			'service'=>'sj');
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://www.google.com/accounts/ClientLogin");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-agent: '.GM_Session::$_user_agent));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$result = GMAPIUtils::curl_redir_exec($ch);
		curl_close($ch);
		$returns = array();
		foreach(explode("\n",$result) as $line)
		{
			$line = trim($line);
			if (!$line) continue;
			list($k,$v) = explode("=",$line,2);
			$returns[$k] = $v;
		}

		if($returns['Error']) {
			return false;
		}
		
		$this->_sid = "SID=".$returns['SID'];
		$this->_auth = $returns['Auth'];
		$_SESSION[$this->getSessionName('SID')] = $this->_sid;
		$_SESSION[$this->getSessionName('AUTH')] = $this->_auth;
		
		if(!$this->getXT()) {
			$this->logout();
		} else {
			$this->_logged_in = true;
			$this->SaveSessionToFile();
		}
		
		return $this->_logged_in;
	}
	
	private function getXT() {
		$ch = curl_init();

		$headers = array(
			'Authorization: GoogleLogin auth='. $this->_auth,
			'User-agent: '.GM_Session::$_user_agent
		);
		
		curl_setopt($ch, CURLOPT_URL, "https://play.google.com/music/listen");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		
		$result = GMAPIUtils::curl_redir_exec($ch, true);
		
		curl_close($ch);
		preg_match_all('%Set-Cookie: ([^;]+);%',$result,$cookie);

		if(is_array($cookie)) {
			$cookie = $cookie[count($cookie)-1];
		}
		
		if(count($cookie) < 2) {
			GMAPIPrintDebug("unable get xt\n".$result);
			return false;
		}
		
        foreach($cookie as $c) {
            parse_str($c);
        }
		
		if(empty($xt)) {
			GMAPIPrintDebug("unable get xt\n".$result);
		}
		if(empty($sjsaid)) {
			GMAPIPrintDebug("unable get sjsaid\n".$result);
		}
		
		$this->_cookie_xt = (!empty($xt)) ? $xt : null;
		$_SESSION[$this->getSessionName('COOKIE_XT')] = $this->_cookie_xt;
		
		$this->_cookie_string = "sjsaid=".$sjsaid;
		$_SESSION[$this->getSessionName('COOKIE')] = $this->_cookie_string;

		return true;
	}
	
	public function open_sj_https_url($url_builder, $query=null) {
		if(!$this->_logged_in)
			return false;
		
		$url = "";
		$isPost = true;
		if(is_subclass_of($url_builder,"iSJProtocol")) {
			$url = $url_builder->buildUrl($query);
			$isPost = $url_builder->isPost();
		} else if(is_string($url_builder)) {
			$url = $url_builder;
		} else {
			GMAPIPrintDebug("url not a string");
			return false;
		}
		
		$post_data = $query;
		$headers = array(
			'Authorization: GoogleLogin auth='. $this->_auth,
			'User-agent: '.GM_Session::$_user_agent,
			'Content-Type: application/json'
		);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_COOKIE, $this->_cookie_string);
		curl_setopt($ch, CURLOPT_POST, $isPost);
		if($isPost) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		}
		$result = GMAPIUtils::curl_redir_exec($ch);
		$code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
		curl_close($ch);
		
		if($code == 200)
			return $result;
		
		GMAPIPrintDebug("got error code: ".$code." ".$url);
		return false;
	}
	
	public function open_authed_https_url($url_builder, $query=null) {
		if(!$this->_logged_in)
			return false;
		
		$url = "";
		$isPost = true;
		if(is_subclass_of($url_builder,"iWCProtocol")) {
			$url = $url_builder->buildUrl(array("xt"=>$this->_cookie_xt));
			$isPost = $url_builder->isPost();
		} else if(is_string($url_builder)) {
			$url = $url_builder;
		} else {
			GMAPIPrintDebug("url not a string");
			return false;
		}
		
		$post_data = "";
		if(!$isPost) {
			$url .= $query;
		}
		
		$post_data = $query;
		$headers = array(
			'Authorization: GoogleLogin auth='. $this->_auth,
			'User-agent: '.GM_Session::$_user_agent,
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_COOKIE, $this->_cookie_string);
		curl_setopt($ch, CURLOPT_POST, $isPost);
		if($isPost) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		}

		$result = GMAPIUtils::curl_redir_exec($ch);
		$code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
		curl_close($ch);
		
		if($code == 200)
			return $result;
		
		GMAPIPrintDebug("got error code: ".$code." ".$url);
		return false;
	}
	
	
	public function jumper_post($url, $encoded_data, $headers=null) {
		if(!$this->isLoggedIn())
			return false;
		
		$myheaders = array(
			'Content-Type: application/x-www-form-urlencoded',
			'Cookie: '.$this->_sid,
			'User-Agent: Music Manager (1, 0, 12, 3443 ¡V Windows)'
		);
		
		if($headers != null) {
			$myheaders = array_merge($myheaders, $headers);
		}

		$url = str_replace(GM_Session::$_jumper_url,"",$url);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_COOKIE, $this->_sid);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_URL, GM_Session::$_jumper_url.$url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded_data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $myheaders);
		$result = GMAPIUtils::curl_redir_exec($ch);
		curl_close($ch);
		
		return $result;
	}
	
	public function protopost($path, $proto) {
		if(!$this->isLoggedIn())
			return false;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_URL, GM_Session::$_android_url."/upsj/".$path);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $proto->SerializeToString());
		curl_setopt($ch, CURLOPT_COOKIE, $this->_sid);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-google-protobuf'));
		$result = GMAPIUtils::curl_redir_exec($ch);
		$code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
		curl_close($ch);
		
		if($code == 200)
			return $result;
		
		GMAPIPrintDebug("got error code: ".$code);
		return false;
	}
	
}
	
?>
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

class GMAPIUtils {
	public static function to_camel_case($str, $capitalise_first_char = true) {
		if($capitalise_first_char) {
			$str[0] = strtoupper($str[0]);
		}
		$func = create_function('$c', 'return strtoupper($c[1]);');
		return preg_replace_callback('/_([a-z])/', $func, $str);
	}

	public static function findAttr($meta, $name) {
		foreach($meta as $m) {
			if($m['name'] == $name)
				return $m;
		}
		return false;
	}

	public static function getAttr($meta, $name) {
		$m = GMAPIUtils::findAttr($meta, $name);
		if(!$m)
			return false;
		return $m;
	}

	public static function toArray($input) {
		if(is_array($input))
			return $input;
		else
			return array($input);
	}

	public static function flushOutput() {
		if(ob_get_length())
		{
			@ob_flush();
			@flush();
			@ob_end_flush();
		}   
		@ob_start();
	}
	
	public static function curl_redir_exec($ch, $return_header = false) {
		if (!ini_get('open_basedir') && !ini_get('safe_mode')) {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			return curl_exec($ch);
		}

		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);

		$temp = explode("\r\n\r\n", $result, 2);
		$header = $temp[0];
		$data = $temp[1];
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($http_code == 301 || $http_code == 302)
		{
			$matches = array();
			preg_match('/Location:(.*?)\n/', $header, $matches);
			$url = @parse_url(trim(array_pop($matches)));
			if (!$url)
			{
				return ($return_header) ? $result : $data;
			}
			$last_url = parse_url(curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
			if (!$url['scheme'])
				$url['scheme'] = $last_url['scheme'];
			if (!$url['host'])
				$url['host'] = $last_url['host'];
			if (!$url['path'])
				$url['path'] = $last_url['path'];
			$new_url = $url['scheme'] . '://' . $url['host'] . $url['path'] . ($url['query']?'?'.$url['query']:'');
			curl_setopt($ch, CURLOPT_URL, $new_url);
			return GMAPIUtils::curl_redir_exec($ch, $return_header);
		} else {
			return ($return_header) ? $result : $data;
		}
	}
}

function GMAPIPrintDebug($message)
{
	if(!GMApi::IsDebug())
		return;

	$message = htmlentities($message, ENT_QUOTES, "UTF-8");
	$stacktrace = debug_backtrace();
	$message = str_replace("\n",'<br/>',$message);
	echo '<div style="border: 1px solid #000000; margin: 2px 0 2px 0; padding: 2px 2px 2px 2px;">';
	echo '<b>[DEBUG] '.$message.'</b></br>';
	
	$i = 1;
	foreach($stacktrace as $element) {
		echo '#'.$i.' '.$element['file'].' line ('.$element['line'].') - '.$element['function']."<br/>\n";
		$i++;
	}
	echo '</div>';
	
	GMAPIUtils::flushOutput();
}
?>
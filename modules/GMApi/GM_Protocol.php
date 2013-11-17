<?php
if(!defined('IN_GMAPI')) { die('...'); }

class GM_Protocol {
	
	public static function getSJProtocol($service_name) {
		$name = "SJ_".$service_name;
		if(!class_exists($name))
			return false;
		
		return new $name();
	}
	
	public static function getWCProtocol($service_name) {
		$name = "WC_".$service_name;
		if(!class_exists($name))
			return false;
		
		return new $name();
	}
}

?>
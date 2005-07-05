<?php					// -*-c++-*-
// by Edd Dumbill (C) 1999-2002
// <edd@usefulinc.com>
// $Id: xmlrpc.inc,v 1.40 2005/04/24 18:17:18 ggiunta Exp $


// Copyright (c) 1999,2000,2002 Edd Dumbill.
// All rights reserved.
//
// Redistribution and use in source and binary forms, with or without
// modification, are permitted provided that the following conditions
// are met:
//
//    * Redistributions of source code must retain the above copyright
//      notice, this list of conditions and the following disclaimer.
//
//    * Redistributions in binary form must reproduce the above
//      copyright notice, this list of conditions and the following
//      disclaimer in the documentation and/or other materials provided
//      with the distribution.
//
//    * Neither the name of the "XML-RPC for PHP" nor the names of its
//      contributors may be used to endorse or promote products derived
//      from this software without specific prior written permission.
//
// THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
// "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
// LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
// FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
// REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
// INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
// (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
// SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
// HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
// STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
// ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
// OF THE POSSIBILITY OF SUCH DAMAGE.

	if (!function_exists('xml_parser_create'))
	{
		// Win 32 fix. From: 'Leo West' <lwest@imaginet.fr>
		if($WINDIR)
		{
			dl('php3_xml.dll');
		}
		else
		{
			dl('xml.so');
		}
	}

	// G. Giunta 2005/01/29: declare global these variables,
	// so that xmlrpc.inc will work even if included from within a function
	// NB: it will give warnings in PHP3, so we comment it out
	// Milosch: Next round, maybe we should explicitly request these via $GLOBALS where used.
	if (phpversion() >= '4')
	{
		global $xmlrpcI4;
		global $xmlrpcInt;
		global $xmlrpcDouble;
		global $xmlrpcString;
		global $xmlrpcDateTime;
		global $xmlrpcBase64;
		global $xmlrpcArray;
		global $xmlrpcStruct;

		global $xmlrpcTypes;
		global $xmlEntities;
		global $xmlrpcerr;
		global $xmlrpcstr;
		global $xmlrpc_defencoding;
		global $xmlrpc_internalencoding;
		global $xmlrpcName;
		global $xmlrpcVersion;
		global $xmlrpcerruser;
		global $xmlrpcerrxml;
		global $xmlrpc_backslash;
		global $_xh;
	}
	$xmlrpcI4='i4';
	$xmlrpcInt='int';
	$xmlrpcBoolean='boolean';
	$xmlrpcDouble='double';
	$xmlrpcString='string';
	$xmlrpcDateTime='dateTime.iso8601';
	$xmlrpcBase64='base64';
	$xmlrpcArray='array';
	$xmlrpcStruct='struct';

	$xmlrpcTypes=array(
		$xmlrpcI4       => 1,
		$xmlrpcInt      => 1,
		$xmlrpcBoolean  => 1,
		$xmlrpcString   => 1,
		$xmlrpcDouble   => 1,
		$xmlrpcDateTime => 1,
		$xmlrpcBase64   => 1,
		$xmlrpcArray    => 2,
		$xmlrpcStruct   => 3
	);

	$xmlEntities=array(
		'amp'  => '&',
		'quot' => '"',
		'lt'   => '<',
		'gt'   => '>',
		'apos' => "'"
	);

	$xmlrpcerr['unknown_method']=1;
	$xmlrpcstr['unknown_method']='Unknown method';
	$xmlrpcerr['invalid_return']=2;
	$xmlrpcstr['invalid_return']='Invalid return payload: enable debugging to examine incoming payload';
	$xmlrpcerr['incorrect_params']=3;
	$xmlrpcstr['incorrect_params']='Incorrect parameters passed to method';
	$xmlrpcerr['introspect_unknown']=4;
	$xmlrpcstr['introspect_unknown']="Can't introspect: method unknown";
	$xmlrpcerr['http_error']=5;
	$xmlrpcstr['http_error']="Didn't receive 200 OK from remote server.";
	$xmlrpcerr['no_data']=6;
	$xmlrpcstr['no_data']='No data received from server.';
	$xmlrpcerr['no_ssl']=7;
	$xmlrpcstr['no_ssl']='No SSL support compiled in.';
	$xmlrpcerr['curl_fail']=8;
	$xmlrpcstr['curl_fail']='CURL error';


	$xmlrpcerr['multicall_notstruct'] = 9;
	$xmlrpcstr['multicall_notstruct'] = 'system.multicall expected struct';
	$xmlrpcerr['multicall_nomethod']  = 10;
	$xmlrpcstr['multicall_nomethod']  = 'missing methodName';
	$xmlrpcerr['multicall_notstring'] = 11;
	$xmlrpcstr['multicall_notstring'] = 'methodName is not a string';
	$xmlrpcerr['multicall_recursion'] = 12;
	$xmlrpcstr['multicall_recursion'] = 'recursive system.multicall forbidden';
	$xmlrpcerr['multicall_noparams']  = 13;
	$xmlrpcstr['multicall_noparams']  = 'missing params';
	$xmlrpcerr['multicall_notarray']  = 14;
	$xmlrpcstr['multicall_notarray']  = 'params is not an array';

	// The charset encoding expected by the server for received messages and
	// by the client for received responses
	$xmlrpc_defencoding='UTF-8';
	// The encoding used by PHP.
	// String values received will be converted to this.
	$xmlrpc_internalencoding='ISO-8859-1';

	$xmlrpcName='XML-RPC for PHP';
	$xmlrpcVersion='1.1.1';

	// let user errors start at 800
	$xmlrpcerruser=800;
	// let XML parse errors start at 100
	$xmlrpcerrxml=100;

	// formulate backslashes for escaping regexp
	$xmlrpc_backslash=chr(92).chr(92);

	// used to store state during parsing
	// quick explanation of components:
	//   st - used to build up a string for evaluation
	//   ac - used to accumulate values
	//   qt - used to decide if quotes are needed for evaluation
	//   cm - used to denote struct or array (comma needed)
	//   isf - used to indicate a fault
	//   lv - used to indicate "looking for a value": implements
	//        the logic to allow values with no types to be strings
	//   params - used to store parameters in method calls
	//   method - used to store method name

	$_xh=array();

	/**
	* To help correct communication of non-ascii chars inside strings, regardless
	* of the charset used when sending requests, parsing them, sending responses
	* and parsing responses, convert all non-ascii chars present in the message
	* into their equivalent 'charset entity'. Charset entities enumerated this way
	* are independent of the charset encoding used to transmit them, and all XML
	* parsers are bound to understand them.
	*/
	function xmlrpc_entity_decode($string)
	{
		$top=split('&', $string);
		$op='';
		$i=0;
		while($i<sizeof($top))
		{
			if (ereg("^([#a-zA-Z0-9]+);", $top[$i], $regs))
			{
				$op.=ereg_replace("^[#a-zA-Z0-9]+;",
				xmlrpc_lookup_entity($regs[1]),
				$top[$i]);
			}
			else
			{
				if ($i==0)
				{
					$op=$top[$i];
				}
				else
				{
					$op.='&' . $top[$i];
				}
			}
			$i++;
		}
		return $op;
	}

	function xmlrpc_lookup_entity($ent)
	{
		global $xmlEntities;

		if (isset($xmlEntities[strtolower($ent)]))
		{
			return $xmlEntities[strtolower($ent)];
		}
		if (ereg("^#([0-9]+)$", $ent, $regs))
		{
			return chr($regs[1]);
		}
		return '?';
	}

	/**
	 * These entities originate from HTML specs (1.1, proposed 2.0, etc),
	 * and are taken directly from php-4.3.1/ext/mbstring/html_entities.c.
	 * Until php provides functionality to translate these entities in its
	 * core library, use this function.
	 */
	function xmlrpc_html_entity_xlate($data = '')
	{
		$entities = array(
			"&nbsp;" => "&#160;",
			"&iexcl;" => "&#161;",
			"&cent;" => "&#162;",
			"&pound;" => "&#163;",
			"&curren;" => "&#164;",
			"&yen;" => "&#165;",
			"&brvbar;" => "&#166;",
			"&sect;" => "&#167;",
			"&uml;" => "&#168;",
			"&copy;" => "&#169;",
			"&ordf;" => "&#170;",
			"&laquo;" => "&#171;",
			"&not;" => "&#172;",
			"&shy;" => "&#173;",
			"&reg;" => "&#174;",
			"&macr;" => "&#175;",
			"&deg;" => "&#176;",
			"&plusmn;" => "&#177;",
			"&sup2;" => "&#178;",
			"&sup3;" => "&#179;",
			"&acute;" => "&#180;",
			"&micro;" => "&#181;",
			"&para;" => "&#182;",
			"&middot;" => "&#183;",
			"&cedil;" => "&#184;",
			"&sup1;" => "&#185;",
			"&ordm;" => "&#186;",
			"&raquo;" => "&#187;",
			"&frac14;" => "&#188;",
			"&frac12;" => "&#189;",
			"&frac34;" => "&#190;",
			"&iquest;" => "&#191;",
			"&Agrave;" => "&#192;",
			"&Aacute;" => "&#193;",
			"&Acirc;" => "&#194;",
			"&Atilde;" => "&#195;",
			"&Auml;" => "&#196;",
			"&Aring;" => "&#197;",
			"&AElig;" => "&#198;",
			"&Ccedil;" => "&#199;",
			"&Egrave;" => "&#200;",
			"&Eacute;" => "&#201;",
			"&Ecirc;" => "&#202;",
			"&Euml;" => "&#203;",
			"&Igrave;" => "&#204;",
			"&Iacute;" => "&#205;",
			"&Icirc;" => "&#206;",
			"&Iuml;" => "&#207;",
			"&ETH;" => "&#208;",
			"&Ntilde;" => "&#209;",
			"&Ograve;" => "&#210;",
			"&Oacute;" => "&#211;",
			"&Ocirc;" => "&#212;",
			"&Otilde;" => "&#213;",
			"&Ouml;" => "&#214;",
			"&times;" => "&#215;",
			"&Oslash;" => "&#216;",
			"&Ugrave;" => "&#217;",
			"&Uacute;" => "&#218;",
			"&Ucirc;" => "&#219;",
			"&Uuml;" => "&#220;",
			"&Yacute;" => "&#221;",
			"&THORN;" => "&#222;",
			"&szlig;" => "&#223;",
			"&agrave;" => "&#224;",
			"&aacute;" => "&#225;",
			"&acirc;" => "&#226;",
			"&atilde;" => "&#227;",
			"&auml;" => "&#228;",
			"&aring;" => "&#229;",
			"&aelig;" => "&#230;",
			"&ccedil;" => "&#231;",
			"&egrave;" => "&#232;",
			"&eacute;" => "&#233;",
			"&ecirc;" => "&#234;",
			"&euml;" => "&#235;",
			"&igrave;" => "&#236;",
			"&iacute;" => "&#237;",
			"&icirc;" => "&#238;",
			"&iuml;" => "&#239;",
			"&eth;" => "&#240;",
			"&ntilde;" => "&#241;",
			"&ograve;" => "&#242;",
			"&oacute;" => "&#243;",
			"&ocirc;" => "&#244;",
			"&otilde;" => "&#245;",
			"&ouml;" => "&#246;",
			"&divide;" => "&#247;",
			"&oslash;" => "&#248;",
			"&ugrave;" => "&#249;",
			"&uacute;" => "&#250;",
			"&ucirc;" => "&#251;",
			"&uuml;" => "&#252;",
			"&yacute;" => "&#253;",
			"&thorn;" => "&#254;",
			"&yuml;" => "&#255;",
			"&OElig;" => "&#338;",
			"&oelig;" => "&#339;",
			"&Scaron;" => "&#352;",
			"&scaron;" => "&#353;",
			"&Yuml;" => "&#376;",
			"&fnof;" => "&#402;",
			"&circ;" => "&#710;",
			"&tilde;" => "&#732;",
			"&Alpha;" => "&#913;",
			"&Beta;" => "&#914;",
			"&Gamma;" => "&#915;",
			"&Delta;" => "&#916;",
			"&Epsilon;" => "&#917;",
			"&Zeta;" => "&#918;",
			"&Eta;" => "&#919;",
			"&Theta;" => "&#920;",
			"&Iota;" => "&#921;",
			"&Kappa;" => "&#922;",
			"&Lambda;" => "&#923;",
			"&Mu;" => "&#924;",
			"&Nu;" => "&#925;",
			"&Xi;" => "&#926;",
			"&Omicron;" => "&#927;",
			"&Pi;" => "&#928;",
			"&Rho;" => "&#929;",
			"&Sigma;" => "&#931;",
			"&Tau;" => "&#932;",
			"&Upsilon;" => "&#933;",
			"&Phi;" => "&#934;",
			"&Chi;" => "&#935;",
			"&Psi;" => "&#936;",
			"&Omega;" => "&#937;",
			"&beta;" => "&#946;",
			"&gamma;" => "&#947;",
			"&delta;" => "&#948;",
			"&epsilon;" => "&#949;",
			"&zeta;" => "&#950;",
			"&eta;" => "&#951;",
			"&theta;" => "&#952;",
			"&iota;" => "&#953;",
			"&kappa;" => "&#954;",
			"&lambda;" => "&#955;",
			"&mu;" => "&#956;",
			"&nu;" => "&#957;",
			"&xi;" => "&#958;",
			"&omicron;" => "&#959;",
			"&pi;" => "&#960;",
			"&rho;" => "&#961;",
			"&sigmaf;" => "&#962;",
			"&sigma;" => "&#963;",
			"&tau;" => "&#964;",
			"&upsilon;" => "&#965;",
			"&phi;" => "&#966;",
			"&chi;" => "&#967;",
			"&psi;" => "&#968;",
			"&omega;" => "&#969;",
			"&thetasym;" => "&#977;",
			"&upsih;" => "&#978;",
			"&piv;" => "&#982;",
			"&ensp;" => "&#8194;",
			"&emsp;" => "&#8195;",
			"&thinsp;" => "&#8201;",
			"&zwnj;" => "&#8204;",
			"&zwj;" => "&#8205;",
			"&lrm;" => "&#8206;",
			"&rlm;" => "&#8207;",
			"&ndash;" => "&#8211;",
			"&mdash;" => "&#8212;",
			"&lsquo;" => "&#8216;",
			"&rsquo;" => "&#8217;",
			"&sbquo;" => "&#8218;",
			"&ldquo;" => "&#8220;",
			"&rdquo;" => "&#8221;",
			"&bdquo;" => "&#8222;",
			"&dagger;" => "&#8224;",
			"&Dagger;" => "&#8225;",
			"&bull;" => "&#8226;",
			"&hellip;" => "&#8230;",
			"&permil;" => "&#8240;",
			"&prime;" => "&#8242;",
			"&Prime;" => "&#8243;",
			"&lsaquo;" => "&#8249;",
			"&rsaquo;" => "&#8250;",
			"&oline;" => "&#8254;",
			"&frasl;" => "&#8260;",
			"&euro;" => "&#8364;",
			"&weierp;" => "&#8472;",
			"&image;" => "&#8465;",
			"&real;" => "&#8476;",
			"&trade;" => "&#8482;",
			"&alefsym;" => "&#8501;",
			"&larr;" => "&#8592;",
			"&uarr;" => "&#8593;",
			"&rarr;" => "&#8594;",
			"&darr;" => "&#8595;",
			"&harr;" => "&#8596;",
			"&crarr;" => "&#8629;",
			"&lArr;" => "&#8656;",
			"&uArr;" => "&#8657;",
			"&rArr;" => "&#8658;",
			"&dArr;" => "&#8659;",
			"&hArr;" => "&#8660;",
			"&forall;" => "&#8704;",
			"&part;" => "&#8706;",
			"&exist;" => "&#8707;",
			"&empty;" => "&#8709;",
			"&nabla;" => "&#8711;",
			"&isin;" => "&#8712;",
			"&notin;" => "&#8713;",
			"&ni;" => "&#8715;",
			"&prod;" => "&#8719;",
			"&sum;" => "&#8721;",
			"&minus;" => "&#8722;",
			"&lowast;" => "&#8727;",
			"&radic;" => "&#8730;",
			"&prop;" => "&#8733;",
			"&infin;" => "&#8734;",
			"&ang;" => "&#8736;",
			"&and;" => "&#8743;",
			"&or;" => "&#8744;",
			"&cap;" => "&#8745;",
			"&cup;" => "&#8746;",
			"&int;" => "&#8747;",
			"&there4;" => "&#8756;",
			"&sim;" => "&#8764;",
			"&cong;" => "&#8773;",
			"&asymp;" => "&#8776;",
			"&ne;" => "&#8800;",
			"&equiv;" => "&#8801;",
			"&le;" => "&#8804;",
			"&ge;" => "&#8805;",
			"&sub;" => "&#8834;",
			"&sup;" => "&#8835;",
			"&nsub;" => "&#8836;",
			"&sube;" => "&#8838;",
			"&supe;" => "&#8839;",
			"&oplus;" => "&#8853;",
			"&otimes;" => "&#8855;",
			"&perp;" => "&#8869;",
			"&sdot;" => "&#8901;",
			"&lceil;" => "&#8968;",
			"&rceil;" => "&#8969;",
			"&lfloor;" => "&#8970;",
			"&rfloor;" => "&#8971;",
			"&lang;" => "&#9001;",
			"&rang;" => "&#9002;",
			"&loz;" => "&#9674;",
			"&spades;" => "&#9824;",
			"&clubs;" => "&#9827;",
			"&hearts;" => "&#9829;",
			"&diams;" => "&#9830;");
		return strtr($data, $entities);
	}

	function xmlrpc_encode_entitites($data) 
	{
		$length = strlen($data);
		$escapeddata = "";
		for($position = 0; $position < $length; $position++)
		{
			$character = substr($data, $position, 1);
			$code = Ord($character);
			switch($code) {
				case 34:
				$character = "&quot;";
				break;
				case 38:
				$character = "&amp;";
				break;
				case 39:
				$character = "&apos;";
				break;
				case 60:
				$character = "&lt;";
				break;
				case 62:
				$character = "&gt;";
				break;
				default:
				if ($code < 32 || $code > 159)
					$character = ("&#".strval($code).";");
				break;
			}
			$escapeddata .= $character;
		}
		return $escapeddata;
	}

	function xmlrpc_se($parser, $name, $attrs)
	{
		global $_xh, $xmlrpcDateTime, $xmlrpcString;

		switch($name)
		{
			case 'STRUCT':
			case 'ARRAY':
				$_xh[$parser]['st'].='array(';
				$_xh[$parser]['cm']++;
				// this last line turns quoting off
				// this means if we get an empty array we'll
				// simply get a bit of whitespace in the eval
				$_xh[$parser]['qt']=0;
				break;
			case 'NAME':
				$_xh[$parser]['st'].='"';
				$_xh[$parser]['ac']='';
				break;
			case 'FAULT':
				$_xh[$parser]['isf']=1;
				break;
			case 'PARAM':
				$_xh[$parser]['st']='';
				break;
			case 'VALUE':
				$_xh[$parser]['st'].='new xmlrpcval(';
				$_xh[$parser]['vt']=$xmlrpcString;
				$_xh[$parser]['ac']='';
				$_xh[$parser]['qt']=0;
				$_xh[$parser]['lv']=1;
				// look for a value: if this is still 1 by the
				// time we reach the first data segment then the type is string
				// by implication and we need to add in a quote
				break;
			case 'I4':
			case 'INT':
			case 'STRING':
			case 'BOOLEAN':
			case 'DOUBLE':
			case 'DATETIME.ISO8601':
			case 'BASE64':
				$_xh[$parser]['ac']=''; // reset the accumulator

				if ($name=='DATETIME.ISO8601' || $name=='STRING')
				{
					$_xh[$parser]['qt']=1;
					if ($name=='DATETIME.ISO8601')
					{
						$_xh[$parser]['vt']=$xmlrpcDateTime;
					}
				}
				elseif ($name=='BASE64')
				{
					$_xh[$parser]['qt']=2;
				}
				else
				{
					// No quoting is required here -- but
					// at the end of the element we must check
					// for data format errors.
					$_xh[$parser]['qt']=0;
				}
				break;
			case 'MEMBER':
				$_xh[$parser]['ac']='';
				break;
			default:
				break;
		}

		if ($name!='VALUE')
		{
			$_xh[$parser]['lv']=0;
		}
	}

	function xmlrpc_ee($parser, $name)
	{
		global $_xh,$xmlrpcTypes,$xmlrpcString;

		switch($name)
		{
			case 'STRUCT':
			case 'ARRAY':
				if ($_xh[$parser]['cm'] && substr($_xh[$parser]['st'], -1) ==',')
				{
					$_xh[$parser]['st']=substr($_xh[$parser]['st'],0,-1);
				}
				$_xh[$parser]['st'].=')';
				$_xh[$parser]['vt']=strtolower($name);
				$_xh[$parser]['cm']--;
				break;
			case 'NAME':
				$_xh[$parser]['st'].= $_xh[$parser]['ac'] . '" => ';
				break;
			case 'BOOLEAN':
				// special case here: we translate boolean 1 or 0 into PHP
				// constants true or false
				// NB: this simple checks helps a lot sanitizing input, ie no
				// security problems around here
				if ($_xh[$parser]['ac']=='1')
				{
					$_xh[$parser]['ac']='true';
				}
				else
				{
					$_xh[$parser]['ac']='false';
				}
				$_xh[$parser]['vt']=strtolower($name);
				// Drop through intentionally.
			case 'I4':
			case 'INT':
			case 'STRING':
			case 'DOUBLE':
			case 'DATETIME.ISO8601':
			case 'BASE64':
				if ($_xh[$parser]['qt']==1)
				{
					// we use double quotes rather than single so backslashification works OK
					$_xh[$parser]['st'].='"'. $_xh[$parser]['ac'] . '"';
				}
				elseif ($_xh[$parser]['qt']==2)
				{
					$_xh[$parser]['st'].='base64_decode("'. $_xh[$parser]['ac'] . '")';
				}
				elseif ($name=='BOOLEAN')
				{
					$_xh[$parser]['st'].=$_xh[$parser]['ac'];
				}
				elseif ($name=='DOUBLE')
				{
					// we have a DOUBLE
					// we must check that only 0123456789-.<space> are characters here
					if (!ereg("^[+-]?[eE0123456789 \\t\\.]+$", $_xh[$parser]['ac']))
					{
						// TODO: find a better way of throwing an error
						// than this!
						error_log('XML-RPC: non numeric value received in DOUBLE: '.$_xh[$parser]['ac']);
						$_xh[$parser]['st'].="'ERROR_NON_NUMERIC_FOUND'";
					}
					else
					{
						// it's ok, add it on
						$_xh[$parser]['st'].=(double)$_xh[$parser]['ac'];
					}
				}
				else
				{
					// we have an I4/INT
					// we must check that only 0123456789-<space> are characters here
					if (!ereg("^[+-]?[0123456789 \\t]+$", $_xh[$parser]['ac']))
					{
						// TODO: find a better way of throwing an error
						// than this!
						error_log('XML-RPC: non numeric value received in INT: '.$_xh[$parser]['ac']);
						$_xh[$parser]['st'].="'ERROR_NON_NUMERIC_FOUND'";
					}
					else
					{
						// it's ok, add it on
						$_xh[$parser]['st'].=(int)$_xh[$parser]['ac'];
					}
				}
				$_xh[$parser]['ac']='';
				$_xh[$parser]['qt']=0;
				$_xh[$parser]['lv']=3; // indicate we've found a value
				break;
			case 'VALUE':
				// deal with a string value
				if (strlen($_xh[$parser]['ac'])>0 &&
					$_xh[$parser]['vt']==$xmlrpcString)
				{
					$_xh[$parser]['st'].='"'. $_xh[$parser]['ac'] . '"';
				}
				// This if() detects if no scalar was inside <VALUE></VALUE>
				// and pads an empty ''.
				if($_xh[$parser]['st'][strlen($_xh[$parser]['st'])-1] == '(')
				{
					$_xh[$parser]['st'].= '""';
				}
				// G. Giunta 2005/03/12 save some chars in the reconstruction of string vals...
				if ($_xh[$parser]['vt'] != $xmlrpcString)
					$_xh[$parser]['st'].=", '" . $_xh[$parser]['vt'] . "')";
				else
					$_xh[$parser]['st'].=")";
				if ($_xh[$parser]['cm'])
				{
					$_xh[$parser]['st'].=',';
				}
				break;
			case 'MEMBER':
				$_xh[$parser]['ac']='';
				$_xh[$parser]['qt']=0;
				break;
			case 'DATA':
				$_xh[$parser]['ac']='';
				$_xh[$parser]['qt']=0;
				break;
			case 'PARAM':
				$_xh[$parser]['params'][]=$_xh[$parser]['st'];
				break;
			case 'METHODNAME':
				$_xh[$parser]['method']=ereg_replace("^[\n\r\t ]+", '', $_xh[$parser]['ac']);
				break;
			// BOOLEAN HAS BEEN ENUMERATED ABOVE!
			/*case 'BOOLEAN':
				// special case here: we translate boolean 1 or 0 into PHP
				// constants true or false
				if ($_xh[$parser]['ac']=='1')
				{
					$_xh[$parser]['ac']='true';
				}
				else
				{
					$_xh[$parser]['ac']='false';
					$_xh[$parser]['vt']=strtolower($name);
				}
				break;*/
			default:
				break;
		}
		// if it's a valid type name, set the type
		if (isset($xmlrpcTypes[strtolower($name)]))
		{
			$_xh[$parser]['vt']=strtolower($name);
		}
	}

	function xmlrpc_cd($parser, $data)
	{
		global $_xh, $xmlrpc_backslash;

		//if (ereg("^[\n\r \t]+$", $data)) return;
		// print "adding [${data}]\n";

		if ($_xh[$parser]['lv']!=3)
		{
			// "lookforvalue==3" means that we've found an entire value
			// and should discard any further character data
			if ($_xh[$parser]['lv']==1)
			{
				// if we've found text and we're just in a <value> then
				// turn quoting on, as this will be a string
				$_xh[$parser]['qt']=1;
				// and say we've found a value
				$_xh[$parser]['lv']=2;
			}
			if(!@isset($_xh[$parser]['ac']))
			{
				$_xh[$parser]['ac'] = '';
			}
			$_xh[$parser]['ac'].=str_replace('$', '\$', str_replace('"', '\"', str_replace(chr(92),$xmlrpc_backslash, $data)));
		}
	}

	function xmlrpc_dh($parser, $data)
	{
		global $_xh, $xmlrpc_backslash;
		if (substr($data, 0, 1) == '&' && substr($data, -1, 1) == ';')
		{
			if ($_xh[$parser]['lv']==1)
			{
				$_xh[$parser]['qt']=1;
				$_xh[$parser]['lv']=2;
			}
			$_xh[$parser]['ac'].=str_replace('$', '\$', str_replace('"', '\"', str_replace(chr(92),$xmlrpc_backslash, $data)));
		}
	}

	class xmlrpc_client
	{
		var $path;
		var $server;
		var $port;
		var $errno;
		var $errstr;
		var $debug=0;
		var $username='';
		var $password='';
		var $cert='';
		var $certpass='';
		var $verifypeer=1;
		var $verifyhost=1;
		var $no_multicall=false;

		function xmlrpc_client($path, $server, $port=0)
		{
			$this->port=$port; $this->server=$server; $this->path=$path;
		}

		function setDebug($in)
		{
			if ($in)
			{
				$this->debug=1;
			}
			else
			{
				$this->debug=0;
			}
		}

		function setCredentials($u, $p)
		{
			$this->username=$u;
			$this->password=$p;
		}

		function setCertificate($cert, $certpass)
		{
			$this->cert = $cert;
			$this->certpass = $certpass;
		}

		function setSSLVerifyPeer($i)
		{
			$this->verifypeer = $i;
		}

		function setSSLVerifyHost($i)
		{
			$this->verifyhost = $i;
		}

		function send($msg, $timeout=0, $method='http')
		{
			if (is_array($msg))
			{
				// $msg is an array of xmlrpcmsg's
				return $this->multicall($msg, $timeout, $method);
			}

			// where msg is an xmlrpcmsg
			$msg->debug=$this->debug;

			if ($method == 'https')
			{
				return $this->sendPayloadHTTPS($msg,
				$this->server,
				$this->port, $timeout,
				$this->username, $this->password,
				$this->cert,
				$this->certpass);
			}
			else
			{
				return $this->sendPayloadHTTP10($msg, $this->server, $this->port,
				$timeout, $this->username, 
				$this->password);
			}
		}

		function sendPayloadHTTP10($msg, $server, $port, $timeout=0,$username='', $password='')
		{
			global $xmlrpcerr, $xmlrpcstr, $xmlrpcName, $xmlrpcVersion, $xmlrpc_defencoding;
			if ($port==0)
			{
				$port=80;
			}
			if($timeout>0)
			{
				$fp=@fsockopen($server, $port,$this->errno, $this->errstr, $timeout);
			}
			else
			{
				$fp=@fsockopen($server, $port,$this->errno, $this->errstr);
			}
			if ($fp)
			{
				if ($timeout>0 && function_exists('stream_set_timeout'))
					stream_set_timeout($fp, $timeout);
			}
			else
			{
				$this->errstr='Connect error';
				$r=new xmlrpcresp(0, $xmlrpcerr['http_error'],$xmlrpcstr['http_error']);
				return $r;
			}
			// Only create the payload if it was not created previously
			if(empty($msg->payload))
			{
				$msg->createPayload();
			}

			// thanks to Grant Rauscher <grant7@firstworld.net>
			// for this
			$credentials='';
			if ($username!='')
			{
				$credentials='Authorization: Basic ' . base64_encode($username . ':' . $password) . "\r\n";
			}

			$op= "POST " . $this->path. " HTTP/1.0\r\n" .
				"User-Agent: " . $xmlrpcName . " " . $xmlrpcVersion . "\r\n" .
				"Host: ". $server . "\r\n" .
				$credentials . 
				"Accept-Charset: " . $xmlrpc_defencoding . "\r\n" .
				"Content-Type: text/xml\r\nContent-Length: " .
				strlen($msg->payload) . "\r\n\r\n" .
				$msg->payload;

			if (!fputs($fp, $op, strlen($op)))
			{
				$this->errstr='Write error';
				$r=new xmlrpcresp(0, $xmlrpcerr['http_error'], $xmlrpcstr['http_error']);
				return $r;
			}
			$resp=$msg->parseResponseFile($fp);
			fclose($fp);
			return $resp;
		}

		// contributed by Justin Miller <justin@voxel.net>
		// requires curl to be built into PHP
		function sendPayloadHTTPS($msg, $server, $port, $timeout=0,$username='', $password='', $cert='',$certpass='')
		{
			global $xmlrpcerr, $xmlrpcstr, $xmlrpcVersion, $xmlrpc_internalencoding;
			if ($port == 0)
			{
				$port = 443;
			}

			// Only create the payload if it was not created previously
			if(empty($msg->payload))
			{
				$msg->createPayload();
			}

			if (!function_exists('curl_init'))
			{
				$this->errstr='SSL unavailable on this install';
				$r=new xmlrpcresp(0, $xmlrpcerr['no_ssl'], $xmlrpcstr['no_ssl']);
				return $r;
			}

			$curl = curl_init('https://' . $server . ':' . $port . $this->path);

			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			// results into variable
			if ($this->debug)
			{
				curl_setopt($curl, CURLOPT_VERBOSE, 1);
			}
			curl_setopt($curl, CURLOPT_USERAGENT, 'PHP XMLRPC '.$xmlrpcVersion);
			// required for XMLRPC
			curl_setopt($curl, CURLOPT_POST, 1);
			// post the data
			curl_setopt($curl, CURLOPT_POSTFIELDS, $msg->payload);
			// the data
			curl_setopt($curl, CURLOPT_HEADER, 1);
			// return the header too
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/xml', 'Accept-Charset: '.$xmlrpc_internalencoding));
			// whether to verify remote host's cert
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->verifypeer);
			// whether to verify cert's common name (CN); 0 for no, 1 to verify that it exists, and 2 to verify that it matches the hostname used
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $this->verifyhost);
			// required for XMLRPC
			if ($timeout)
			{
				curl_setopt($curl, CURLOPT_TIMEOUT, $timeout == 1 ? 1 : $timeout - 1);
			}
			// timeout is borked
			if ($username && $password)
			{
				curl_setopt($curl, CURLOPT_USERPWD,"$username:$password");
			}
			// set auth stuff
			if ($cert)
			{
				curl_setopt($curl, CURLOPT_SSLCERT, $cert);
			}
			// set cert file
			if ($certpass)
			{
				curl_setopt($curl, CURLOPT_SSLCERTPASSWD,$certpass);
			}
			// set cert password

			$result = curl_exec($curl);

			if (!$result)
			{
				$this->errstr='no response';
				$resp=new xmlrpcresp(0, $xmlrpcerr['curl_fail'], $xmlrpcstr['curl_fail']. ': '. curl_error($curl));
				curl_close($curl);
			}
			else
			{
				curl_close($curl);
				$resp = $msg->parseResponse($result);
			}
			return $resp;
		}

		function multicall($msgs, $timeout=0, $method='http')
		{
			$results = false;

			if (! $this->no_multicall)
			{
				$results = $this->_try_multicall($msgs, $timeout, $method);
				/* TODO - this is not php3-friendly */
				// if($results !== false)
				if(is_array($results))
				{
					// Either the system.multicall succeeded, or the send
					// failed (e.g. due to HTTP timeout). In either case,
					// we're done for now.
					return $results;
				}
				else
				{
					// system.multicall unsupported by server,
					// don't try it next time...
					$this->no_multicall = true;
				}
			}

			// system.multicall is unupported by server:
			//   Emulate multicall via multiple requests
			$results = array();
			//foreach($msgs as $msg)
			@reset($msgs);
			while(list(,$msg) = @each($msgs))
			{
				$results[] = $this->send($msg, $timeout, $method);
			}
			return $results;
		}

		// Attempt to boxcar $msgs via system.multicall.
		function _try_multicall($msgs, $timeout, $method)
		{
			// Construct multicall message
			$calls = array();
			//foreach($msgs as $msg)
			@reset($msgs);
			while(list(,$msg) = @each($msgs))
			{
				$call['methodName'] = new xmlrpcval($msg->method(),'string');
				$numParams = $msg->getNumParams();
				$params = array();
				for ($i = 0; $i < $numParams; $i++)
				{
					$params[$i] = $msg->getParam($i);
				}
				$call['params'] = new xmlrpcval($params, 'array');
				$calls[] = new xmlrpcval($call, 'struct');
			}
			$multicall = new xmlrpcmsg('system.multicall');
			$multicall->addParam(new xmlrpcval($calls, 'array'));

			// Attempt RPC call
			$result = $this->send($multicall, $timeout, $method);
			if(!is_object($result))
			{
				return ($result || 0); // transport failed
			}

			if($result->faultCode() != 0)
			{
				return false;		// system.multicall failed
			}

			// Unpack responses.
			$rets = $result->value();
			if($rets->kindOf() != 'array')
			{
				return false;		// bad return type from system.multicall
			}
			$numRets = $rets->arraysize();
			if($numRets != count($msgs))
			{
				return false;		// wrong number of return values.
			}

			$response = array();
			for ($i = 0; $i < $numRets; $i++)
			{
				$val = $rets->arraymem($i);
				switch ($val->kindOf())
				{
				case 'array':
					if($val->arraysize() != 1)
					{
						return false;		// Bad value
					}
					// Normal return value
					$response[$i] = new xmlrpcresp($val->arraymem(0));
					break;
				case 'struct':
					$code = $val->structmem('faultCode');
					if($code->kindOf() != 'scalar' || $code->scalartyp() != 'int')
					{
						return false;
					}
					$str = $val->structmem('faultString');
					if($str->kindOf() != 'scalar' || $str->scalartyp() != 'string')
					{
						return false;
					}
					$response[$i] = new xmlrpcresp(0, $code->scalarval(), $str->scalarval());
					break;
				default:
					return false;
				}
			}
			return $response;
		}
	} // end class xmlrpc_client

	class xmlrpcresp
	{
		var $val = 0;
		var $errno = 0;
		var $errstr = '';
		var $hdrs = array();

		function xmlrpcresp($val, $fcode = 0, $fstr = '')
		{
			if ($fcode != 0)
			{
				// error
				$this->errno = $fcode;
				$this->errstr = $fstr;
				//$this->errstr = htmlspecialchars($fstr); // XXX: encoding probably shouldn't be done here; fix later.
			}
			elseif (!is_object($val))
			{
				// programmer error
				error_log("Invalid type '" . gettype($val) . "' (value: $val) passed to xmlrpcresp. Defaulting to empty value.");
				$this->val = new xmlrpcval();
			}
			else
			{
				// success
				$this->val = $val;
			}
		}

		function faultCode()
		{
			return $this->errno;
		}

		function faultString()
		{
			return $this->errstr;
		}

		function value()
		{
			return $this->val;
		}

		function serialize()
		{
			$result = "<methodResponse>\n";
			if ($this->errno)
			{
				// G. Giunta 2005/2/13: let non-ASCII response messages be tolerated by clients
				$result .= '<fault>
<value>
<struct>
<member>
<name>faultCode</name>
<value><int>' . $this->errno . '</int></value>
</member>
<member>
<name>faultString</name>
<value><string>' . xmlrpc_encode_entitites($this->errstr) . '</string></value>
</member>
</struct>
</value>
</fault>';
			}
			else
			{
				$result .= "<params>\n<param>\n" .
					$this->val->serialize() . 
					"</param>\n</params>";
			}
			$result .= "\n</methodResponse>";
			return $result;
		}
	}

	class xmlrpcmsg
	{
		var $payload;
		var $methodname;
		var $params=array();
		var $debug=0;

		function xmlrpcmsg($meth, $pars=0)
		{
			$this->methodname=$meth;
			if (is_array($pars) && sizeof($pars)>0)
			{
				for($i=0; $i<sizeof($pars); $i++)
				{
					$this->addParam($pars[$i]);
				}
			}
		}

		function xml_header()
		{
			return "<?xml version=\"1.0\"?" . ">\n<methodCall>\n";
		}

		function xml_footer()
		{
			return "</methodCall>\n";
		}

		function createPayload()
		{
			$this->payload=$this->xml_header();
			$this->payload.='<methodName>' . $this->methodname . "</methodName>\n";
			//	if (sizeof($this->params)) {
			$this->payload.="<params>\n";
			for($i=0; $i<sizeof($this->params); $i++)
			{
				$p=$this->params[$i];
				$this->payload.="<param>\n" . $p->serialize() .
				"</param>\n";
			}
			$this->payload.="</params>\n";
			// }
			$this->payload.=$this->xml_footer();
			//$this->payload=str_replace("\n", "\r\n", $this->payload);
		}

		function method($meth='')
		{
			if ($meth!='')
			{
				$this->methodname=$meth;
			}
			return $this->methodname;
		}

		function serialize()
		{
			$this->createPayload();
			return $this->payload;
		}

		function addParam($par) { $this->params[]=$par; }
		function getParam($i) { return $this->params[$i]; }
		function getNumParams() { return sizeof($this->params); }

		function parseResponseFile($fp)
		{
			$ipd='';
			while($data=fread($fp, 32768))
			{
				$ipd.=$data;
			}
			return $this->parseResponse($ipd);
		}

		function parseResponse($data='')
		{
			global $_xh,$xmlrpcerr,$xmlrpcstr;
			global $xmlrpc_defencoding, $xmlrpc_internalencoding;

			$hdrfnd = 0;
			if($this->debug)
			{
				//by maHo, replaced htmlspecialchars with htmlentities
				print "<PRE>---GOT---\n" . htmlentities($data) . "\n---END---\n</PRE>";
			}

			if($data == '')
			{
				error_log('No response received from server.');
				$r = new xmlrpcresp(0, $xmlrpcerr['no_data'], $xmlrpcstr['no_data']);
				return $r;
			}
			// see if we got an HTTP 200 OK, else bomb
			// but only do this if we're using the HTTP protocol.
			if(ereg("^HTTP",$data))
			{
				// Strip HTTP 1.1 100 Continue header if present
				while (ereg('^HTTP/1.1 1[0-9]{2}', $data))
				{
					$pos = strpos($data, 'HTTP', 12);
					// server sent a Continue header without any (valid) content following...
					// give the client a chance to know it
					if (!$pos && !is_int($pos)) // works fine in php 3, 4 and 5
						break;
					$data = substr($data, $pos);
				}
				if (!ereg("^HTTP/[0-9\\.]+ 200 ", $data))
				{
					$errstr= substr($data, 0, strpos($data, "\n")-1);
					error_log('HTTP error, got response: ' .$errstr);
					$r=new xmlrpcresp(0, $xmlrpcerr['http_error'], $xmlrpcstr['http_error']. ' (' . $errstr . ')');
					return $r;
				}
			}
			$parser = xml_parser_create($xmlrpc_defencoding);

			// G. Giunta 2004/04/06
			// Clean up the accumulator, or it will grow indefinitely long
			// if making xmlrpc calls for a while
			$_xh=array();
			$_xh[$parser]=array();
			$_xh[$parser]['headers'] = array();

			// separate HTTP headers from data
			if (ereg("^HTTP", $data))
			{
				// be tolerant to usage of \n instead of \r\n to separate headers and data
				// (even though it is not valid http)
				$pos = strpos($data,"\r\n\r\n");
				if($pos || is_int($pos))
					$bd = $pos+4;
				else
				{
					$pos = strpos($data,"\n\n");
					if($pos || is_int($pos))
						$bd = $pos+2;
					else
					{
						// No separation between response headers and body: fault?
						$bd = 0;
					}
				}
				// be tolerant to line endings, and extra empty lines
				$ar = split("\r?\n", trim(substr($data, 0, $pos)));
				while (list(,$line) = @each($ar))
				{
					// take care of multi-line headers
					$arr = explode(':',$line);
					if(count($arr) > 1)
					{
						$header_name = trim($arr[0]);
						// TO DO: some headers (the ones that allow a CSV list of values)
						// do allow many values to be passed using multiple header lines.
						// We should add content to $_xh[$parser]['headers'][$header_name]
						// instead of replacing it for those...
						$_xh[$parser]['headers'][$header_name] = $arr[1];
						for ($i = 2; $i < count($arr); $i++)
						{
							$_xh[$parser]['headers'][$header_name] .= ':'.$arr[$i];
						} // while
						$_xh[$parser]['headers'][$header_name] = trim($_xh[$parser]['headers'][$header_name]);
					} else if (isset($header_name))
					{
						$_xh[$parser]['headers'][$header_name] .= ' ' . trim($line);
					}
				}
				$data = substr($data, $bd);

				if ($this->debug && count($_xh[$parser]['headers']))
				{
					print '<PRE>';
					//foreach ($_xh[$parser]['headers'] as $header)
					@reset($_xh[$parser]['headers']);
					while(list($header, $value) = @each($_xh[$parser]['headers']))
					{
						print "HEADER: $header: $value\n";
					}
					print "</PRE>\n";
				}
			}

			// be tolerant of extra whitespace in response body
			$data = trim($data);

			// be tolerant of junk after methodResponse (e.g. javascript automatically inserted by free hosts)
			// idea from Luca Mariano <luca.mariano@email.it> originally in PEARified version of the lib
			$bd = false;
			$pos = strpos($data, "</methodResponse>");
			while ($pos || is_int($pos))
			{
				$bd = $pos+17;
				$pos = strpos($data, "</methodResponse>", $bd);
			}
			if ($bd)
				$data = substr($data, 0, $bd);

			$_xh[$parser]['st']='';
			$_xh[$parser]['cm']=0;
			$_xh[$parser]['isf']=0;
			$_xh[$parser]['ac']='';
			$_xh[$parser]['qt']='';

			xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, true);
			// G. Giunta 2005/02/13: PHP internally uses ISO-8859-1, so we have to tell
			// the xml parser to give us back data in the expected charset
			xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, $xmlrpc_internalencoding);

			xml_set_element_handler($parser, 'xmlrpc_se', 'xmlrpc_ee');
			xml_set_character_data_handler($parser, 'xmlrpc_cd');
			xml_set_default_handler($parser, 'xmlrpc_dh');
			//$xmlrpc_value=new xmlrpcval;

			if (!xml_parse($parser, $data, sizeof($data)))
			{
				// thanks to Peter Kocks <peter.kocks@baygate.com>
				if((xml_get_current_line_number($parser)) == 1)
				{
					$errstr = 'XML error at line 1, check URL';
				}
				else
				{
					$errstr = sprintf('XML error: %s at line %d',
						xml_error_string(xml_get_error_code($parser)),
						xml_get_current_line_number($parser));
				}
				error_log($errstr);
				$r=new xmlrpcresp(0, $xmlrpcerr['invalid_return'], $xmlrpcstr['invalid_return'].' ('.$errstr.')');
				xml_parser_free($parser);
				if ($this->debug)
					echo $errstr;
				$r->hdrs = $_xh[$parser]['headers'];
				return $r;
			}
			xml_parser_free($parser);
			if ($this->debug)
			{
				print "<PRE>---EVALING---[" .
				strlen($_xh[$parser]['st']) . " chars]---\n" .
				htmlspecialchars($_xh[$parser]['st']) . ";\n---END---</PRE>";
			}
			if (strlen($_xh[$parser]['st'])==0)
			{
				// then something odd has happened
				// and it's time to generate a client side error
				// indicating something odd went on
				$r=new xmlrpcresp(0, $xmlrpcerr['invalid_return'],
				$xmlrpcstr['invalid_return']);
			}
			else
			{
				$allOK=0;
				@eval('$v=' . $_xh[$parser]['st'] . '; $allOK=1;');
				if (!$allOK)
				{
					$r = new xmlrpcresp(0, $xmlrpcerr['invalid_return'], $xmlrpcstr['invalid_return']);
				}
				else
				if ($_xh[$parser]['isf'])
				{
					$errno_v = $v->structmem('faultCode');
					$errstr_v = $v->structmem('faultString');
					$errno = $errno_v->scalarval();

					if ($errno == 0)
					{
						// FAULT returned, errno needs to reflect that
						$errno = -1;
					}

					$r = new xmlrpcresp($v, $errno, $errstr_v->scalarval());
				}
				else
				{
					$r=new xmlrpcresp($v);
				}
			}

			$r->hdrs = $_xh[$parser]['headers'];
			return $r;
		}
	}

	class xmlrpcval
	{
		var $me=array();
		var $mytype=0;

		function xmlrpcval($val=-1, $type='')
		{
			global $xmlrpcTypes;
			$this->me=array();
			$this->mytype=0;
			if ($val!=-1 || !is_int($val) || $type!='')
			{
				if ($type=='')
				{
					$type='string';
				}
				if ($xmlrpcTypes[$type]==1)
				{
					$this->addScalar($val,$type);
				}
				elseif ($xmlrpcTypes[$type]==2)
				{
					$this->addArray($val);
				}
				elseif ($xmlrpcTypes[$type]==3)
				{
					$this->addStruct($val);
				}
			}
		}

		function addScalar($val, $type='string')
		{
			global $xmlrpcTypes, $xmlrpcBoolean;

			if ($this->mytype==1)
			{
				echo '<B>xmlrpcval</B>: scalar can have only one value<BR>';
				return 0;
			}
			$typeof=$xmlrpcTypes[$type];
			if ($typeof!=1)
			{
				echo '<B>xmlrpcval</B>: not a scalar type (${typeof})<BR>';
				return 0;
			}

			if ($type==$xmlrpcBoolean)
			{
				if (strcasecmp($val,'true')==0 || $val==1 || ($val==true && strcasecmp($val,'false')))
				{
					$val=1;
				}
				else
				{
					$val=0;
				}
			}

			if ($this->mytype==2)
			{
				// we're adding to an array here
				$ar=$this->me['array'];
				$ar[]=new xmlrpcval($val, $type);
				$this->me['array']=$ar;
			}
			else
			{
				// a scalar, so set the value and remember we're scalar
				$this->me[$type]=$val;
				$this->mytype=$typeof;
			}
			return 1;
		}

		function addArray($vals)
		{
			global $xmlrpcTypes;
			if ($this->mytype!=0)
			{
				echo '<B>xmlrpcval</B>: already initialized as a [' . $this->kindOf() . ']<BR>';
				return 0;
			}

			$this->mytype=$xmlrpcTypes['array'];
			$this->me['array']=$vals;
			return 1;
		}

		function addStruct($vals)
		{
			global $xmlrpcTypes;
			if ($this->mytype!=0)
			{
				echo '<B>xmlrpcval</B>: already initialized as a [' . $this->kindOf() . ']<BR>';
				return 0;
			}
			$this->mytype=$xmlrpcTypes['struct'];
			$this->me['struct']=$vals;
			return 1;
		}

		function dump($ar)
		{
			reset($ar);
			while ( list( $key, $val ) = each( $ar ) )
			{
				echo "$key => $val<br>";
				if ($key == 'array')
				{
					while ( list( $key2, $val2 ) = each( $val ) )
					{
						echo "-- $key2 => $val2<br>";
					}
				}
			}
		}

		function kindOf()
		{
			switch($this->mytype)
			{
				case 3:
					return 'struct';
					break;
				case 2:
					return 'array';
					break;
				case 1:
					return 'scalar';
					break;
				default:
					return 'undef';
			}
		}

		function serializedata($typ, $val)
		{
			$rs='';
			global $xmlrpcTypes, $xmlrpcBase64, $xmlrpcString,
			$xmlrpcBoolean;
			switch(@$xmlrpcTypes[$typ])
			{
				case 3:
					// struct
					$rs.="<struct>\n";
					reset($val);
					while(list($key2, $val2)=each($val))
					{
						$rs.="<member><name>${key2}</name>\n";
						$rs.=$this->serializeval($val2);
						$rs.="</member>\n";
					}
					$rs.='</struct>';
					break;
				case 2:
					// array
					$rs.="<array>\n<data>\n";
					for($i=0; $i<sizeof($val); $i++)
					{
						$rs.=$this->serializeval($val[$i]);
					}
					$rs.="</data>\n</array>";
					break;
				case 1:
					switch ($typ)
					{
						case $xmlrpcBase64:
							$rs.="<${typ}>" . base64_encode($val) . "</${typ}>";
							break;
						case $xmlrpcBoolean:
							$rs.="<${typ}>" . ($val ? '1' : '0') . "</${typ}>";
							break;
						case $xmlrpcString:
							// G. Giunta 2005/2/13: do NOT use htmlentities, since
							// it will produce named html entities, which are invalid xml
							$rs.="<${typ}>" . xmlrpc_encode_entitites($val). "</${typ}>";
							// $rs.="<${typ}>" . htmlentities($val). "</${typ}>";
							break;
						default:
							$rs.="<${typ}>${val}</${typ}>";
					}
					break;
				default:
					break;
			}
			return $rs;
		}

		function serialize()
		{
			return $this->serializeval($this);
		}

		function serializeval($o)
		{
			//global $xmlrpcTypes;
			$rs='';
			$ar=$o->me;
			reset($ar);
			list($typ, $val) = each($ar);
			$rs.='<value>';
			$rs.=$this->serializedata($typ, $val);
			$rs.="</value>\n";
			return $rs;
		}

		function structmem($m)
		{
			$nv=$this->me['struct'][$m];
			return $nv;
		}

		function structreset()
		{
			reset($this->me['struct']);
		}

		function structeach()
		{
			return each($this->me['struct']);
		}

		function getval()
		{
			// UNSTABLE
			global $xmlrpcBoolean, $xmlrpcBase64;
			reset($this->me);
			list($a,$b)=each($this->me);
			// contributed by I Sofer, 2001-03-24
			// add support for nested arrays to scalarval
			// i've created a new method here, so as to
			// preserve back compatibility

			if (is_array($b))
			{
				@reset($b);
				while(list($id,$cont) = @each($b))
				{
					$b[$id] = $cont->scalarval();
				}
			}

			// add support for structures directly encoding php objects
			if (is_object($b))
			{
				$t = get_object_vars($b);
				@reset($t);
				while(list($id,$cont) = @each($t))
				{
					$t[$id] = $cont->scalarval();
				}
				@reset($t);
				while(list($id,$cont) = @each($t))
				{
					eval('$b->'.$id.' = $cont;');
				}
			}
			// end contrib
			return $b;
		}

		function scalarval()
		{
			//global $xmlrpcBoolean, $xmlrpcBase64;
			reset($this->me);
			list($a,$b)=each($this->me);
			return $b;
		}

		function scalartyp()
		{
			global $xmlrpcI4, $xmlrpcInt;
			reset($this->me);
			list($a,$b)=each($this->me);
			if ($a==$xmlrpcI4)
			{
				$a=$xmlrpcInt;
			}
			return $a;
		}

		function arraymem($m)
		{
			$nv=$this->me['array'][$m];
			return $nv;
		}

		function arraysize()
		{
			reset($this->me);
			list($a,$b)=each($this->me);
			return sizeof($b);
		}
	}

	// date helpers
	function iso8601_encode($timet, $utc=0)
	{
		// return an ISO8601 encoded string
		// really, timezones ought to be supported
		// but the XML-RPC spec says:
		//
		// "Don't assume a timezone. It should be specified by the server in its
		// documentation what assumptions it makes about timezones."
		// 
		// these routines always assume localtime unless 
		// $utc is set to 1, in which case UTC is assumed
		// and an adjustment for locale is made when encoding
		if (!$utc)
		{
			$t=strftime("%Y%m%dT%H:%M:%S", $timet);
		}
		else
		{
			if (function_exists('gmstrftime'))
			{
				// gmstrftime doesn't exist in some versions
				// of PHP
				$t=gmstrftime("%Y%m%dT%H:%M:%S", $timet);
			}
			else
			{
				$t=strftime("%Y%m%dT%H:%M:%S", $timet-date('Z'));
			}
		}
		return $t;
	}

	function iso8601_decode($idate, $utc=0)
	{
		// return a timet in the localtime, or UTC
		$t=0;
		if (ereg("([0-9]{4})([0-9]{2})([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})", $idate, $regs))
		{
			if ($utc)
			{
				$t=gmmktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
			}
			else
			{
				$t=mktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
			}
		} 
		return $t;
	}

	/****************************************************************
	* xmlrpc_decode takes a message in PHP xmlrpc object format and *
	* tranlates it into native PHP types.                           *
	*                                                               *
	* author: Dan Libby (dan@libby.com)                             *
	****************************************************************/
	function php_xmlrpc_decode($xmlrpc_val)
	{
		$kind = $xmlrpc_val->kindOf();

		if($kind == 'scalar')
		{
			return $xmlrpc_val->scalarval();
		}
		elseif($kind == 'array')
		{
			$size = $xmlrpc_val->arraysize();
			$arr = array();

			for($i = 0; $i < $size; $i++)
			{
				$arr[] = php_xmlrpc_decode($xmlrpc_val->arraymem($i));
			}
			return $arr;
		}
		elseif($kind == 'struct')
		{
			$xmlrpc_val->structreset();
			$arr = array();

			while(list($key,$value)=$xmlrpc_val->structeach())
			{
				$arr[$key] = php_xmlrpc_decode($value);
			}
			return $arr;
		}
	}

	if(function_exists('xmlrpc_decode'))
	{
		define('XMLRPC_EPI_ENABLED','1');
	}
	else
	{
		define('XMLRPC_EPI_ENABLED','0');
		function xmlrpc_decode($xmlrpc_val)
		{
			$kind = $xmlrpc_val->kindOf();

			if($kind == 'scalar')
			{
				return $xmlrpc_val->scalarval();
			}
			elseif($kind == 'array')
			{
				$size = $xmlrpc_val->arraysize();
				$arr = array();

				for($i = 0; $i < $size; $i++)
				{
					$arr[]=xmlrpc_decode($xmlrpc_val->arraymem($i));
				}
				return $arr;
			}
			elseif($kind == 'struct')
			{
				$xmlrpc_val->structreset();
				$arr = array();

				while(list($key,$value)=$xmlrpc_val->structeach())
				{
					$arr[$key] = xmlrpc_decode($value);
				}
				return $arr;
			}
		}
	}

	/****************************************************************
	* xmlrpc_encode takes native php types and encodes them into    *
	* xmlrpc PHP object format.                                     *
	* BUG: All sequential arrays are turned into structs.  I don't  *
	* know of a good way to determine if an array is sequential     *
	* only.                                                         *
	*                                                               *
	* feature creep -- could support more types via optional type   *
	* argument.                                                     *
	*                                                               *
	* author: Dan Libby (dan@libby.com)                             *
	****************************************************************/
	function php_xmlrpc_encode($php_val)
	{
		global $xmlrpcInt;
		global $xmlrpcDouble;
		global $xmlrpcString;
		global $xmlrpcArray;
		global $xmlrpcStruct;
		global $xmlrpcBoolean;

		$type = gettype($php_val);
		$xmlrpc_val = new xmlrpcval;

		switch($type)
		{
			case 'array':
			case 'object':
				$arr = array();
				while (list($k,$v) = each($php_val))
				{
					$arr[$k] = php_xmlrpc_encode($v);
				}
				$xmlrpc_val->addStruct($arr);
				break;
			case 'integer':
				$xmlrpc_val->addScalar($php_val, $xmlrpcInt);
				break;
			case 'double':
				$xmlrpc_val->addScalar($php_val, $xmlrpcDouble);
				break;
			case 'string':
				$xmlrpc_val->addScalar($php_val, $xmlrpcString);
				break;
				// <G_Giunta_2001-02-29>
				// Add support for encoding/decoding of booleans, since they are supported in PHP
			case 'boolean':
				$xmlrpc_val->addScalar($php_val, $xmlrpcBoolean);
				break;
				// </G_Giunta_2001-02-29>
			// catch "resource", "NULL", "user function", "unknown type"
			//case 'unknown type':
			default:
				// giancarlo pinerolo <ping@alt.it>
				// it has to return 
				// an empty object in case (which is already
				// at this point), not a boolean. 
				break;
			}
			return $xmlrpc_val;
	}

	if(XMLRPC_EPI_ENABLED == '0')
	{
		function xmlrpc_encode($php_val)
		{
			global $xmlrpcInt;
			global $xmlrpcDouble;
			global $xmlrpcString;
			global $xmlrpcArray;
			global $xmlrpcStruct;
			global $xmlrpcBoolean;

			$type = gettype($php_val);
			$xmlrpc_val = new xmlrpcval;

			switch($type)
			{
				case 'array':
				case 'object':
					$arr = array();
					while (list($k,$v) = each($php_val))
					{
						$arr[$k] = xmlrpc_encode($v);
					}
					$xmlrpc_val->addStruct($arr);
					break;
				case 'integer':
					$xmlrpc_val->addScalar($php_val, $xmlrpcInt);
					break;
				case 'double':
					$xmlrpc_val->addScalar($php_val, $xmlrpcDouble);
					break;
				case 'string':
					$xmlrpc_val->addScalar($php_val, $xmlrpcString);
					break;
					// <G_Giunta_2001-02-29>
					// Add support for encoding/decoding of booleans, since they are supported in PHP
				case 'boolean':
					$xmlrpc_val->addScalar($php_val, $xmlrpcBoolean);
					break;
					// </G_Giunta_2001-02-29>
				//case 'unknown type':
				default:
					// giancarlo pinerolo <ping@alt.it>
					// it has to return 
					// an empty object in case (which is already
					// at this point), not a boolean. 
					break;
			}
			return $xmlrpc_val;
		}
	}
?>

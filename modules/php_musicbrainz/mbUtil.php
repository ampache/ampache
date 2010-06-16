<?php
/* vim:set tabstop=4 softtabstop=4 shiftwidth=4 noexpandtab: */
/*
 Copyright 2009, 2010 Timothy John Wood, Paul Arthur MacIain

 This file is part of php_musicbrainz
 
 php_musicbrainz is free software: you can redistribute it and/or modify
 it under the terms of the GNU Lesser General Public License as published by
 the Free Software Foundation, either version 2.1 of the License, or
 (at your option) any later version.
 
 php_musicbrainz is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU Lesser General Public License for more details.
 
 You should have received a copy of the GNU Lesser General Public License
 along with php_musicbrainz.  If not, see <http://www.gnu.org/licenses/>.
*/
class mbValueError extends Exception {}

function extractFragment($type) {
    if (($p = parse_url($type)) == false) {
        return $type;
    }
    return $p['fragment'];
}

function extractEntityType($uri) {
	if (empty($uri)) { return $uri; }
	
	$types = array('artist/', 'release/', 'track/', 'label/', 'release-group/');
	foreach ($types as $type) {
		if (strpos($uri, $type) !== false) {
			return rtrim($type, '/');
		}
	}
	throw new mbValueError("$uri is not a valid MusicBrainz URI.", 1);
}

function extractUuid($uid) {
    if (empty($uid)) { return $uid; }

	if (strlen($uid) == 36) { return $uid; }

    $types = array("artist/", "release/", "track/", "label/", "release-group/");
	foreach ($types as $type) {
        if (($pos = strpos($uid, $type)) !== false) {
            $pos += strlen($type);
            if ($pos + 36 == strlen($uid)) {
                return substr($uid, $pos, 36);
            }
        }
    }

    throw new mbValueError("$uid is not a valid MusicBrainz ID.", 1);
}

require_once('mbUtil_countrynames.php');
function getCountryName($id) {
    if (isset($mbCountryNames[$id])) {
		return $mbCountryNames[$id];
	}

    return "";
}

require_once('mbUtil_languagenames.php');
function getLanguageName($id) {
    if (isset($mbLanguageNames[$id])) {
		return $mbLanguageNames[$id];
	}

    return "";
}

require_once('mbUtil_scriptnames.php');
function getScriptName($id) {
    if (isset($mbScriptNames[$id])) {
		return $mbScriptNames[$id];
	}

    return "";
}

require_once('mbUtil_releasetypenames.php');
function getReleaseTypeName($id) {
    if (isset($mbReleaseTypeNames[$id])) {
		return $mbReleaseTypeNames[$id];
	}

    return "";
}
?>

<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
//                                                             //
// /demo/demo.mysql.php - part of getID3()                     //
// Sample script for recursively scanning directories and      //
// storing the results in a database                           //
// See readme.txt for more details                             //
//                                                            ///
/////////////////////////////////////////////////////////////////

// OPTIONS:
$getid3_demo_mysql_encoding = 'ISO-8859-1';
$getid3_demo_mysql_md5_data = false;        // All data hashes are by far the slowest part of scanning
$getid3_demo_mysql_md5_file = false;


if (!@mysql_connect('localhost', 'getid3', 'getid3')) {
	die('Could not connect to MySQL host: <BLOCKQUOTE STYLE="background-color: #FF9933; padding: 10px;">'.mysql_error().'</BLOCKQUOTE>');
}
if (!@mysql_select_db('getid3')) {
	die('Could not select database: <BLOCKQUOTE STYLE="background-color: #FF9933; padding: 10px;">'.mysql_error().'</BLOCKQUOTE>');
}

if (!@include_once('../getid3/getid3.php')) {
	die('Cannot open '.realpath('../getid3/getid3.php'));
}
// Initialize getID3 engine
$getID3 = new getID3;
$getID3->option_md5_data = $getid3_demo_mysql_md5_data;
$getID3->encoding        = $getid3_demo_mysql_encoding;


function RemoveAccents($string) {
	// Revised version by markstewardØhotmail*com
	return strtr(strtr($string, 'ŠŽšžŸÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝàáâãäåçèéêëìíîïñòóôõöøùúûüýÿ', 'SZszYAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy'), array('Þ' => 'TH', 'þ' => 'th', 'Ð' => 'DH', 'ð' => 'dh', 'ß' => 'ss', 'Œ' => 'OE', 'œ' => 'oe', 'Æ' => 'AE', 'æ' => 'ae', 'µ' => 'u'));
}

function FixTextFields($text) {
	$text = getid3_lib::SafeStripSlashes($text);
	$text = htmlentities($text, ENT_QUOTES);
	return $text;
}

function BitrateColor($bitrate, $BitrateMaxScale=768) {
	// $BitrateMaxScale is bitrate of maximum-quality color (bright green)
	// below this is gradient, above is solid green

	$bitrate *= (256 / $BitrateMaxScale); // scale from 1-[768]kbps to 1-256
	$bitrate = round(min(max($bitrate, 1), 256));
	$bitrate--;    // scale from 1-256kbps to 0-255kbps

	$Rcomponent = max(255 - ($bitrate * 2), 0);
	$Gcomponent = max(($bitrate * 2) - 255, 0);
	if ($bitrate > 127) {
		$Bcomponent = max((255 - $bitrate) * 2, 0);
	} else {
		$Bcomponent = max($bitrate * 2, 0);
	}
	return str_pad(dechex($Rcomponent), 2, '0', STR_PAD_LEFT).str_pad(dechex($Gcomponent), 2, '0', STR_PAD_LEFT).str_pad(dechex($Bcomponent), 2, '0', STR_PAD_LEFT);
}

function BitrateText($bitrate, $decimals=0) {
	return '<SPAN STYLE="color: #'.BitrateColor($bitrate).'">'.number_format($bitrate, $decimals).' kbps</SPAN>';
}

function fileextension($filename, $numextensions=1) {
	if (strstr($filename, '.')) {
		$reversedfilename = strrev($filename);
		$offset = 0;
		for ($i = 0; $i < $numextensions; $i++) {
			$offset = strpos($reversedfilename, '.', $offset + 1);
			if ($offset === false) {
				return '';
			}
		}
		return strrev(substr($reversedfilename, 0, $offset));
	}
	return '';
}

if (!empty($_REQUEST['renamefilefrom']) && !empty($_REQUEST['renamefileto'])) {

	if ($_REQUEST['renamefilefrom'] === $_REQUEST['renamefileto']) {
		$results = '<SPAN STYLE="color: #FF0000;"><B>Source and Destination filenames identical</B><BR>FAILED to rename';
	} elseif (!file_exists($_REQUEST['renamefilefrom'])) {
		$results = '<SPAN STYLE="color: #FF0000;"><B>Source file does not exist</B><BR>FAILED to rename';
	} elseif (file_exists($_REQUEST['renamefileto']) && (strtolower($_REQUEST['renamefilefrom']) !== strtolower($_REQUEST['renamefileto']))) {
		$results = '<SPAN STYLE="color: #FF0000;"><B>Destination file already exists</B><BR>FAILED to rename';
	} elseif (@rename($_REQUEST['renamefilefrom'], $_REQUEST['renamefileto'])) {
		$SQLquery = 'DELETE FROM `files` WHERE (filename = "'.mysql_escape_string($_REQUEST['renamefilefrom']).'")';
		safe_mysql_query($SQLquery);
		$results = '<SPAN STYLE="color: #008000;">Successfully renamed';
	} else {
		$results = '<BR><SPAN STYLE="color: #FF0000;">FAILED to rename';
	}
	$results .= ' from:<BR><I>'.$_REQUEST['renamefilefrom'].'</I><BR>to:<BR><I>'.$_REQUEST['renamefileto'].'</I></SPAN><HR>';
	echo $results;
	exit;

} elseif (!empty($_REQUEST['m3ufilename'])) {

	header('Content-type: audio/x-mpegurl');
	echo '#EXTM3U'."\n";
	echo WindowsShareSlashTranslate($_REQUEST['m3ufilename'])."\n";
	exit;

} elseif (!isset($_REQUEST['m3u']) && !isset($_REQUEST['m3uartist']) && !isset($_REQUEST['m3utitle'])) {

	echo '<HTML><HEAD><TITLE>getID3() demo - /demo/mysql.php</TITLE><STYLE>BODY, TD, TH { font-family: sans-serif; font-size: 10pt; } A { text-decoration: none; } A:hover { text-decoration: underline; } A:visited { font-style: italic; }</STYLE></HEAD><BODY>';

}


function WindowsShareSlashTranslate($filename) {
	if (substr($filename, 0, 2) == '//') {
		return str_replace('/', '\\', $filename);
	}
	return $filename;
}

function safe_mysql_query($SQLquery) {
	$result = @mysql_query($SQLquery);
	if (mysql_error()) {
		die('<FONT COLOR="red">'.mysql_error().'</FONT><HR><TT>'.$SQLquery.'</TT>');
	}
	return $result;
}

function mysql_table_exists($tablename) {
	return (bool) mysql_query('DESCRIBE '.$tablename);
}

function AcceptableExtensions($fileformat, $audio_dataformat='', $video_dataformat='') {
	static $AcceptableExtensionsAudio = array();
	if (empty($AcceptableExtensionsAudio)) {
		$AcceptableExtensionsAudio['mp3']['mp3']  = array('mp3');
		$AcceptableExtensionsAudio['mp2']['mp2']  = array('mp2');
		$AcceptableExtensionsAudio['mp1']['mp1']  = array('mp1');
		$AcceptableExtensionsAudio['asf']['asf']  = array('asf');
		$AcceptableExtensionsAudio['asf']['wma']  = array('wma');
		$AcceptableExtensionsAudio['riff']['mp3'] = array('wav');
		$AcceptableExtensionsAudio['riff']['wav'] = array('wav');
	}
	static $AcceptableExtensionsVideo = array();
	if (empty($AcceptableExtensionsVideo)) {
		$AcceptableExtensionsVideo['mp3']['mp3']  = array('mp3');
		$AcceptableExtensionsVideo['mp2']['mp2']  = array('mp2');
		$AcceptableExtensionsVideo['mp1']['mp1']  = array('mp1');
		$AcceptableExtensionsVideo['asf']['asf']  = array('asf');
		$AcceptableExtensionsVideo['asf']['wmv']  = array('wmv');
		$AcceptableExtensionsVideo['gif']['gif']  = array('gif');
		$AcceptableExtensionsVideo['jpg']['jpg']  = array('jpg');
		$AcceptableExtensionsVideo['png']['png']  = array('png');
		$AcceptableExtensionsVideo['bmp']['bmp']  = array('bmp');
	}
	if (!empty($video_dataformat)) {
		return (isset($AcceptableExtensionsVideo[$fileformat][$video_dataformat]) ? $AcceptableExtensionsVideo[$fileformat][$video_dataformat] : array());
	} else {
		return (isset($AcceptableExtensionsAudio[$fileformat][$audio_dataformat]) ? $AcceptableExtensionsAudio[$fileformat][$audio_dataformat] : array());
	}
}


if (!empty($_REQUEST['scan'])) {
	if (mysql_table_exists('files')) {
		$SQLquery  = 'DROP TABLE files';
		safe_mysql_query($SQLquery);
	}
}
if (!mysql_table_exists('files')) {
	$SQLquery  = 'CREATE TABLE `files` (';
	$SQLquery .= ' `ID` mediumint(8) unsigned NOT NULL auto_increment,';
	$SQLquery .= ' `filename` text NOT NULL,';
	$SQLquery .= ' `LastModified` text NOT NULL,';
	$SQLquery .= ' `md5_file` varchar(32) NOT NULL default "",';
	$SQLquery .= ' `md5_data` varchar(32) NOT NULL default "",';
	$SQLquery .= ' `md5_data_source` varchar(32) NOT NULL default "",';
	$SQLquery .= ' `filesize` int(10) unsigned NOT NULL default "0",';
	$SQLquery .= ' `fileformat` varchar(255) NOT NULL default "",';
	$SQLquery .= ' `audio_dataformat` varchar(255) NOT NULL default "",';
	$SQLquery .= ' `video_dataformat` varchar(255) NOT NULL default "",';
	$SQLquery .= ' `audio_bitrate` float NOT NULL default "0",';
	$SQLquery .= ' `video_bitrate` float NOT NULL default "0",';
	$SQLquery .= ' `playtime_seconds` varchar(255) NOT NULL default "",';
	$SQLquery .= ' `tags` varchar(255) NOT NULL default "",';
	$SQLquery .= ' `artist` varchar(255) NOT NULL default "",';
	$SQLquery .= ' `title` varchar(255) NOT NULL default "",';
	$SQLquery .= ' `album` varchar(255) NOT NULL default "",';
	$SQLquery .= ' `genre` varchar(255) NOT NULL default "",';
	$SQLquery .= ' `comment` varchar(255) NOT NULL default "",';
	$SQLquery .= ' `track` varchar(7) NOT NULL default "",';
	$SQLquery .= ' `comments_all` text NOT NULL,';
	$SQLquery .= ' `comments_id3v2` text NOT NULL,';
	$SQLquery .= ' `comments_ape` text NOT NULL,';
	$SQLquery .= ' `comments_lyrics3` text NOT NULL,';
	$SQLquery .= ' `comments_id3v1` text NOT NULL,';
	$SQLquery .= ' `warning` text NOT NULL,';
	$SQLquery .= ' `error` text NOT NULL,';
	$SQLquery .= ' `track_volume` float NOT NULL default "0",';
	$SQLquery .= ' `encoder_options` varchar(255) NOT NULL default "",';
	$SQLquery .= ' `vbr_method` varchar(255) NOT NULL default "",';
	$SQLquery .= ' PRIMARY KEY (`ID`)';
	$SQLquery .= ') TYPE=MyISAM;';

	safe_mysql_query($SQLquery);
}

$ExistingTableFields = array();
$result = mysql_query('DESCRIBE `files`');
while ($row = mysql_fetch_array($result)) {
	$ExistingTableFields[$row['Field']] = $row;
}
if (!isset($ExistingTableFields['encoder_options'])) { // Added in 1.7.0b2
	echo '<B>adding field `encoder_options`</B><BR>';
	mysql_query('ALTER TABLE `files` ADD `encoder_options` VARCHAR(255) DEFAULT "" NOT NULL AFTER `error`');
	mysql_query('OPTIMIZE TABLE `files`');
}
if (isset($ExistingTableFields['track']) && ($ExistingTableFields['track']['Type'] != 'varchar(7)')) { // Changed in 1.7.0b2
	echo '<B>changing field `track` to VARCHAR(7)</B><BR>';
	mysql_query('ALTER TABLE `files` CHANGE `track` `track` VARCHAR(7) DEFAULT "" NOT NULL');
	mysql_query('OPTIMIZE TABLE `files`');
}
if (!isset($ExistingTableFields['track_volume'])) { // Added in 1.7.0b5
	echo '<H1><FONT COLOR="red">WARNING! You should erase your database and rescan everything because the comment storing has been changed since the last version</FONT></H1><HR>';
	echo '<B>adding field `track_volume`</B><BR>';
	mysql_query('ALTER TABLE `files` ADD `track_volume` FLOAT NOT NULL AFTER `error`');
	mysql_query('OPTIMIZE TABLE `files`');
}


function SynchronizeAllTags($filename, $synchronizefrom='all', $synchronizeto='A12', &$errors) {
	global $getID3;

	set_time_limit(30);

	$ThisFileInfo = $getID3->analyze($filename);
	getid3_lib::CopyTagsToComments($ThisFileInfo);

	if ($synchronizefrom == 'all') {
		$SourceArray = $ThisFileInfo['comments'];
	} elseif (!empty($ThisFileInfo['tags'][$synchronizefrom])) {
		$SourceArray = $ThisFileInfo['tags'][$synchronizefrom];
	} else {
		die('ERROR: $ThisFileInfo[tags]['.$synchronizefrom.'] does not exist');
	}

	$SQLquery = 'DELETE FROM `files` WHERE (filename = "'.mysql_escape_string($filename).'")';
	safe_mysql_query($SQLquery);


	$TagFormatsToWrite = array();
	if ((strpos($synchronizeto, '2') !== false) && ($synchronizefrom != 'id3v2')) {
		$TagFormatsToWrite[] = 'id3v2.3';
	}
	if ((strpos($synchronizeto, 'A') !== false) && ($synchronizefrom != 'ape')) {
		$TagFormatsToWrite[] = 'ape';
	}
	if ((strpos($synchronizeto, 'L') !== false) && ($synchronizefrom != 'lyrics3')) {
		$TagFormatsToWrite[] = 'lyrics3';
	}
	if ((strpos($synchronizeto, '1') !== false) && ($synchronizefrom != 'id3v1')) {
		$TagFormatsToWrite[] = 'id3v1';
	}

	getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'write.php', __FILE__, true);
	$tagwriter = new getid3_writetags;
	$tagwriter->filename       = $filename;
	$tagwriter->tagformats     = $TagFormatsToWrite;
	$tagwriter->overwrite_tags = true;
	$tagwriter->tag_encoding   = $getID3->encoding;
	$tagwriter->tag_data       = $SourceArray;

	if ($tagwriter->WriteTags()) {
		$errors = $tagwriter->errors;
		return true;
	}
	$errors = $tagwriter->errors;
	return false;
}

$IgnoreNoTagFormats = array('', 'png', 'jpg', 'gif', 'bmp', 'swf', 'zip', 'mid', 'mod', 'xm', 'it', 's3m');

if (!empty($_REQUEST['scan']) || !empty($_REQUEST['newscan']) || !empty($_REQUEST['rescanerrors'])) {

	$SQLquery  = 'DELETE from `files` WHERE (fileformat = "")';
	safe_mysql_query($SQLquery);

	$FilesInDir = array();

	if (!empty($_REQUEST['rescanerrors'])) {

		echo '<A HREF="'.$_SERVER['PHP_SELF'].'">abort</A><HR>';

		echo 'Re-scanning all media files already in database that had errors and/or warnings in last scan<HR>';

		$SQLquery = 'SELECT filename FROM `files` WHERE (error <> "") OR (warning <> "") ORDER BY filename ASC';
		$result = safe_mysql_query($SQLquery);
		while ($row = mysql_fetch_array($result)) {

			if (!file_exists($row['filename'])) {
				echo '<B>File missing: '.$row['filename'].'</B><BR>';
				$SQLquery = 'DELETE FROM `files` WHERE (filename = "'.mysql_escape_string($row['filename']).'")';
				safe_mysql_query($SQLquery);
			} else {
				$FilesInDir[] = $row['filename'];
			}

		}

	} elseif (!empty($_REQUEST['scan']) || !empty($_REQUEST['newscan'])) {

		echo '<A HREF="'.$_SERVER['PHP_SELF'].'">abort</A><HR>';

		echo 'Scanning all media files in <B>'.str_replace('\\', '/', realpath(!empty($_REQUEST['scan']) ? $_REQUEST['scan'] : $_REQUEST['newscan'])).'</B> (and subdirectories)<HR>';

		$SQLquery  = 'SELECT COUNT(*) AS num, filename';
		$SQLquery .= ' FROM `files`';
		$SQLquery .= ' GROUP BY filename';
		$SQLquery .= ' ORDER BY num DESC';
		$result = safe_mysql_query($SQLquery);
		$DupesDeleted = 0;
		while ($row = mysql_fetch_array($result)) {
			set_time_limit(30);
			if ($row['num'] <= 1) {
				break;
			}
			$SQLquery = 'DELETE FROM `files` WHERE filename LIKE "'.mysql_escape_string($row['filename']).'"';
			safe_mysql_query($SQLquery);
			$DupesDeleted++;
		}
		if ($DupesDeleted > 0) {
			echo 'Deleted <B>'.number_format($DupesDeleted).'</B> duplicate filenames<HR>';
		}

		if (!empty($_REQUEST['newscan'])) {
			$AlreadyInDatabase = array();
			set_time_limit(60);
			$SQLquery = 'SELECT filename FROM `files` ORDER BY filename ASC';
			$result = safe_mysql_query($SQLquery);
			while ($row = mysql_fetch_array($result)) {
				//$AlreadyInDatabase[] = strtolower($row['filename']);
				$AlreadyInDatabase[] = $row['filename'];
			}
		}

		$DirectoriesToScan  = array(realpath(!empty($_REQUEST['scan']) ? $_REQUEST['scan'] : $_REQUEST['newscan']));
		$DirectoriesScanned = array();
		while (count($DirectoriesToScan) > 0) {
			foreach ($DirectoriesToScan as $DirectoryKey => $startingdir) {
				if ($dir = @opendir($startingdir)) {
					set_time_limit(30);
					echo '<B>'.str_replace('\\', '/', $startingdir).'</B><BR>';
					flush();
					while (($file = readdir($dir)) !== false) {
						if (($file != '.') && ($file != '..')) {
							$RealPathName = realpath($startingdir.'/'.$file);
							if (is_dir($RealPathName)) {
								if (!in_array($RealPathName, $DirectoriesScanned) && !in_array($RealPathName, $DirectoriesToScan)) {
									$DirectoriesToScan[] = $RealPathName;
								}
							} else if (is_file($RealPathName)) {
								if (!empty($_REQUEST['newscan'])) {
									//if (!in_array(strtolower(str_replace('\\', '/', $RealPathName)), $AlreadyInDatabase)) {
									if (!in_array(str_replace('\\', '/', $RealPathName), $AlreadyInDatabase)) {
										$FilesInDir[] = $RealPathName;
									} else {
									}
								} elseif (!empty($_REQUEST['scan'])) {
									$FilesInDir[] = $RealPathName;
								}
							}
						}
					}
					closedir($dir);
				} else {
					echo '<FONT COLOR="RED">Failed to open directory <B>'.$startingdir.'</B></FONT><BR><BR>';
				}
				$DirectoriesScanned[] = $startingdir;
				unset($DirectoriesToScan[$DirectoryKey]);
			}
		}
		echo '<I>List of files to scan complete (added '.number_format(count($FilesInDir)).' files to scan)</I><HR>';
		flush();
	}

	$FilesInDir = array_unique($FilesInDir);
	sort($FilesInDir);

	$starttime = time();
	$rowcounter = 0;
	$totaltoprocess = count($FilesInDir);

	foreach ($FilesInDir as $filename) {
		set_time_limit(300);

		echo '<BR>'.date('H:i:s').' ['.number_format(++$rowcounter).' / '.number_format($totaltoprocess).'] '.str_replace('\\', '/', $filename);

		$ThisFileInfo = $getID3->analyze($filename);
		getid3_lib::CopyTagsToComments($ThisFileInfo);

		if (file_exists($filename)) {
			$ThisFileInfo['file_modified_time'] = filemtime($filename);
			$ThisFileInfo['md5_file']           = ($getid3_demo_mysql_md5_file ? md5_file($filename) : '');
		}

		if (empty($ThisFileInfo['fileformat'])) {

			echo ' (<SPAN STYLE="color: #990099;">unknown file type</SPAN>)';

		} else {

			if (!empty($ThisFileInfo['error'])) {
				echo ' (<SPAN STYLE="color: #FF0000;">errors</SPAN>)';
			} elseif (!empty($ThisFileInfo['warning'])) {
				echo ' (<SPAN STYLE="color: #FF9999;">warnings</SPAN>)';
			} else {
				echo ' (<SPAN STYLE="color: #009900;">OK</SPAN>)';
			}

			if (!empty($_REQUEST['rescanerrors'])) {

				$SQLquery  = 'UPDATE `files` SET ';
				$SQLquery .= 'LastModified = "'.mysql_escape_string(@$ThisFileInfo['file_modified_time']).'", ';
				$SQLquery .= 'md5_file = "'.mysql_escape_string(@$ThisFileInfo['md5_file']).'", ';
				$SQLquery .= 'md5_data = "'.mysql_escape_string(@$ThisFileInfo['md5_data']).'", ';
				$SQLquery .= 'md5_data_source = "'.mysql_escape_string(@$ThisFileInfo['md5_data_source']).'", ';
				$SQLquery .= 'filesize = "'.mysql_escape_string(@$ThisFileInfo['filesize']).'", ';
				$SQLquery .= 'fileformat = "'.mysql_escape_string(@$ThisFileInfo['fileformat']).'", ';
				$SQLquery .= 'audio_dataformat = "'.mysql_escape_string(@$ThisFileInfo['audio']['dataformat']).'", ';
				$SQLquery .= 'video_dataformat = "'.mysql_escape_string(@$ThisFileInfo['video']['dataformat']).'", ';
				$SQLquery .= 'audio_bitrate = "'.mysql_escape_string(@$ThisFileInfo['audio']['bitrate']).'", ';
				$SQLquery .= 'video_bitrate = "'.mysql_escape_string(@$ThisFileInfo['video']['bitrate']).'", ';
				$SQLquery .= 'playtime_seconds = "'.mysql_escape_string(@$ThisFileInfo['playtime_seconds']).'", ';
				$SQLquery .= 'tags = "'.mysql_escape_string(@implode("\t", @array_keys(@$ThisFileInfo['tags']))).'", ';
				$SQLquery .= 'artist = "'.mysql_escape_string(@implode("\t", @$ThisFileInfo['comments']['artist'])).'", ';
				$SQLquery .= 'title = "'.mysql_escape_string(@implode("\t", @$ThisFileInfo['comments']['title'])).'", ';
				$SQLquery .= 'album = "'.mysql_escape_string(@implode("\t", @$ThisFileInfo['comments']['album'])).'", ';
				$SQLquery .= 'genre = "'.mysql_escape_string(@implode("\t", @$ThisFileInfo['comments']['genre'])).'", ';
				$SQLquery .= 'comment = "'.mysql_escape_string(@implode("\t", @$ThisFileInfo['comments']['comment'])).'", ';
				$SQLquery .= 'track = "'.mysql_escape_string(@implode("\t", @$ThisFileInfo['comments']['track'])).'", ';
				$SQLquery .= 'comments_all = "'.mysql_escape_string(@serialize(@$ThisFileInfo['comments'])).'", ';
				$SQLquery .= 'comments_id3v2 = "'.mysql_escape_string(@serialize(@$ThisFileInfo['tags']['id3v2'])).'", ';
				$SQLquery .= 'comments_ape = "'.mysql_escape_string(@serialize(@$ThisFileInfo['tags']['ape'])).'", ';
				$SQLquery .= 'comments_lyrics3 = "'.mysql_escape_string(@serialize(@$ThisFileInfo['tags']['lyrics3'])).'", ';
				$SQLquery .= 'comments_id3v1 = "'.mysql_escape_string(@serialize(@$ThisFileInfo['tags']['id3v1'])).'", ';
				$SQLquery .= 'warning = "'.mysql_escape_string(@implode("\t", @$ThisFileInfo['warning'])).'", ';
				$SQLquery .= 'error = "'.mysql_escape_string(@implode("\t", @$ThisFileInfo['error'])).'", ';
				$SQLquery .= 'encoder_options = "'.mysql_escape_string(trim(@$ThisFileInfo['audio']['encoder'].' '.@$ThisFileInfo['audio']['encoder_options'])).'", ';
				$SQLquery .= 'vbr_method = "'.mysql_escape_string(@$ThisFileInfo['mpeg']['audio']['VBR_method']).'", ';
				$SQLquery .= 'track_volume = "'.mysql_escape_string(@$ThisFileInfo['replay_gain']['track']['volume']).'" ';
				$SQLquery .= 'WHERE (filename = "'.mysql_escape_string(@$ThisFileInfo['filenamepath']).'")';

			} elseif (!empty($_REQUEST['scan']) || !empty($_REQUEST['newscan'])) {

				$SQLquery  = 'INSERT INTO `files` (filename, LastModified, md5_file, md5_data, md5_data_source, filesize, fileformat, audio_dataformat, video_dataformat, audio_bitrate, video_bitrate, playtime_seconds, tags, artist, title, album, genre, comment, track, comments_all, comments_id3v2, comments_ape, comments_lyrics3, comments_id3v1, warning, error, encoder_options, vbr_method, track_volume) VALUES (';
				$SQLquery .= '"'.mysql_escape_string(@$ThisFileInfo['filenamepath']).'", ';
				$SQLquery .= '"'.mysql_escape_string(@$ThisFileInfo['file_modified_time']).'", ';
				$SQLquery .= '"'.mysql_escape_string(@$ThisFileInfo['md5_file']).'", ';
				$SQLquery .= '"'.mysql_escape_string(@$ThisFileInfo['md5_data']).'", ';
				$SQLquery .= '"'.mysql_escape_string(@$ThisFileInfo['md5_data_source']).'", ';
				$SQLquery .= '"'.mysql_escape_string(@$ThisFileInfo['filesize']).'", ';
				$SQLquery .= '"'.mysql_escape_string(@$ThisFileInfo['fileformat']).'", ';
				$SQLquery .= '"'.mysql_escape_string(@$ThisFileInfo['audio']['dataformat']).'", ';
				$SQLquery .= '"'.mysql_escape_string(@$ThisFileInfo['video']['dataformat']).'", ';
				$SQLquery .= '"'.mysql_escape_string(@$ThisFileInfo['audio']['bitrate']).'", ';
				$SQLquery .= '"'.mysql_escape_string(@$ThisFileInfo['video']['bitrate']).'", ';
				$SQLquery .= '"'.mysql_escape_string(@$ThisFileInfo['playtime_seconds']).'", ';
				$SQLquery .= '"'.mysql_escape_string(@implode("\t", @array_keys(@$ThisFileInfo['tags']))).'", ';
				$SQLquery .= '"'.mysql_escape_string(@implode("\t", @$ThisFileInfo['comments']['artist'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(@implode("\t", @$ThisFileInfo['comments']['title'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(@implode("\t", @$ThisFileInfo['comments']['album'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(@implode("\t", @$ThisFileInfo['comments']['genre'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(@implode("\t", @$ThisFileInfo['comments']['comment'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(@implode("\t", @$ThisFileInfo['comments']['track'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(@serialize(@$ThisFileInfo['comments'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(@serialize(@$ThisFileInfo['tags']['id3v2'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(@serialize(@$ThisFileInfo['tags']['ape'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(@serialize(@$ThisFileInfo['tags']['lyrics3'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(@serialize(@$ThisFileInfo['tags']['id3v1'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(@implode("\t", @$ThisFileInfo['warning'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(@implode("\t", @$ThisFileInfo['error'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(trim(@$ThisFileInfo['audio']['encoder'].' '.@$ThisFileInfo['audio']['encoder_options'])).'", ';
				$SQLquery .= '"'.mysql_escape_string(!empty($ThisFileInfo['mpeg']['audio']['LAME']) ? 'LAME' : @$ThisFileInfo['mpeg']['audio']['VBR_method']).'", ';
				$SQLquery .= '"'.mysql_escape_string(@$ThisFileInfo['replay_gain']['track']['volume']).'")';

			}
			flush();
			safe_mysql_query($SQLquery);
		}

	}

	$SQLquery = 'OPTIMIZE TABLE `files`';
	safe_mysql_query($SQLquery);

	echo '<HR>Done scanning!<HR>';

} elseif (!empty($_REQUEST['missingtrackvolume'])) {

	$MissingTrackVolumeFilesScanned  = 0;
	$MissingTrackVolumeFilesAdjusted = 0;
	$MissingTrackVolumeFilesDeleted  = 0;
	$SQLquery  = 'SELECT filename';
	$SQLquery .= ' FROM `files`';
	$SQLquery .= ' WHERE (track_volume = "0")';
	$SQLquery .= ' AND (audio_bitrate > "0")';
	$result = safe_mysql_query($SQLquery);
	echo 'Scanning <SPAN ID="missingtrackvolumeNowScanning">0</SPAN> / '.number_format(mysql_num_rows($result)).' files for track volume information:<HR>';
	while ($row = mysql_fetch_array($result)) {
		set_time_limit(30);
		echo '<SCRIPT>missingtrackvolumeNowScanning.innerHTML="'.number_format($MissingTrackVolumeFilesScanned++).'"</SCRIPT>. ';
		flush();
		if (file_exists($row['filename'])) {

			$ThisFileInfo = $getID3->analyze($row['filename']);
			if (!empty($ThisFileInfo['replay_gain']['track']['volume'])) {
				$MissingTrackVolumeFilesAdjusted++;
				$SQLquery  = 'UPDATE `files`';
				$SQLquery .= ' SET track_volume = "'.$ThisFileInfo['replay_gain']['track']['volume'].'"';
				$SQLquery .= ' WHERE (filename = "'.mysql_escape_string($row['filename']).'")';
				safe_mysql_query($SQLquery);
			}

		} else {

			$MissingTrackVolumeFilesDeleted++;
			$SQLquery  = 'DELETE FROM `files`';
			$SQLquery .= ' WHERE (filename = "'.mysql_escape_string($row['filename']).'")';
			safe_mysql_query($SQLquery);

		}
	}
	echo '<HR>Scanned '.number_format($MissingTrackVolumeFilesScanned).' files with no track volume information.<BR>';
	echo 'Found track volume information for '.number_format($MissingTrackVolumeFilesAdjusted).' of them (could not find info for '.number_format($MissingTrackVolumeFilesScanned - $MissingTrackVolumeFilesAdjusted).' files; deleted '.number_format($MissingTrackVolumeFilesDeleted).' records of missing files)<HR>';

} elseif (!empty($_REQUEST['deadfilescheck'])) {

	$SQLquery  = 'SELECT COUNT(*) AS num, filename';
	$SQLquery .= ' FROM `files`';
	$SQLquery .= ' GROUP BY filename';
	$SQLquery .= ' ORDER BY num DESC';
	$result = safe_mysql_query($SQLquery);
	$DupesDeleted = 0;
	while ($row = mysql_fetch_array($result)) {
		set_time_limit(30);
		if ($row['num'] <= 1) {
			break;
		}
		echo FixTextFields($row['filename']).'<BR>';
		$SQLquery = 'DELETE FROM `files` WHERE filename LIKE "'.mysql_escape_string($row['filename']).'"';
		safe_mysql_query($SQLquery);
		$DupesDeleted++;
	}
	if ($DupesDeleted > 0) {
		echo '<HR>Deleted <B>'.number_format($DupesDeleted).'</B> duplicate filenames<HR>';
	}

	$SQLquery  = 'SELECT filename, filesize, LastModified FROM `files` ORDER BY filename ASC';
	$result = safe_mysql_query($SQLquery);
	$totalchecked = 0;
	$totalremoved = 0;
	while ($row = mysql_fetch_array($result)) {
		$totalchecked++;
		set_time_limit(30);
		if (!file_exists($row['filename']) || ($row['LastModified'] != filemtime($row['filename'])) || (filesize($row['filename']) != $row['filesize'])) {
			$totalremoved++;
			echo FixTextFields($row['filename']).'<BR>';
			flush();
			$SQLquery = 'DELETE FROM `files` WHERE (filename = "'.mysql_escape_string($row['filename']).'")';
			safe_mysql_query($SQLquery);
		}
	}

	echo '<HR><B>'.number_format($totalremoved).' of '.number_format($totalchecked).' files in database no longer exist, or have been altered since last scan. Removed from database.</B><HR>';

} elseif (!empty($_REQUEST['encodedbydistribution'])) {

	if (!empty($_REQUEST['m3u'])) {

		header('Content-type: audio/x-mpegurl');
		echo '#EXTM3U'."\n";

		$SQLquery = 'SELECT filename, comments_id3v2 FROM `files` WHERE (encoder_options = "'.mysql_escape_string($_REQUEST['encodedbydistribution']).'")';
		$result = mysql_query($SQLquery);
		$NonBlankEncodedBy = '';
		$BlankEncodedBy = '';
		while ($row = mysql_fetch_array($result)) {
			set_time_limit(30);
			$CommentArray = unserialize($row['comments_id3v2']);
			if (isset($CommentArray['encoded_by'][0])) {
				$NonBlankEncodedBy .= WindowsShareSlashTranslate($row['filename'])."\n";
			} else {
				$BlankEncodedBy    .= WindowsShareSlashTranslate($row['filename'])."\n";
			}
		}
		echo $NonBlankEncodedBy;
		echo $BlankEncodedBy;
		exit;

	} elseif (!empty($_REQUEST['showfiles'])) {

		echo '<A HREF="'.$_SERVER['PHP_SELF'].'?encodedbydistribution='.urlencode('%').'">show all</A><BR>';
		echo '<TABLE BORDER="1">';

		$SQLquery = 'SELECT filename, comments_id3v2 FROM `files`';
		$result = mysql_query($SQLquery);
		while ($row = mysql_fetch_array($result)) {
			set_time_limit(30);
			$CommentArray = unserialize($row['comments_id3v2']);
			if (($_REQUEST['encodedbydistribution'] == '%') || (!empty($CommentArray['encoded_by'][0]) && ($_REQUEST['encodedbydistribution'] == $CommentArray['encoded_by'][0]))) {
				echo '<TR><TD><A HREF="'.$_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($row['filename']).'">m3u</A></TD>';
				echo '<TD><A HREF="demo.browse.php?filename='.rawurlencode($row['filename']).'">'.FixTextFields($row['filename']).'</A></TD></TR>';
			}
		}
		echo '</TABLE>';

	} else {

		$SQLquery = 'SELECT encoder_options, comments_id3v2 FROM `files` ORDER BY (encoder_options LIKE "LAME%") DESC,  (encoder_options LIKE "CBR%") DESC';
		$result = mysql_query($SQLquery);
		$EncodedBy = array();
		while ($row = mysql_fetch_array($result)) {
			set_time_limit(30);
			$CommentArray = unserialize($row['comments_id3v2']);
			if (isset($EncodedBy[$row['encoder_options']][@$CommentArray['encoded_by'][0]])) {
				$EncodedBy[$row['encoder_options']][@$CommentArray['encoded_by'][0]]++;
			} else {
				$EncodedBy[$row['encoder_options']][@$CommentArray['encoded_by'][0]] = 1;
			}
		}
		echo '<A HREF="'.$_SERVER['PHP_SELF'].'?encodedbydistribution='.urlencode('%').'&m3u=1">.m3u version</A><BR>';
		echo '<TABLE BORDER="1"><TR><TH>m3u</TH><TH>Encoder Options</TH><TH>Encoded By (ID3v2)</TH></TR>';
		foreach ($EncodedBy as $key => $value) {
			echo '<TR><TD VALIGN="TOP"><A HREF="'.$_SERVER['PHP_SELF'].'?encodedbydistribution='.urlencode($key).'&showfiles=1&m3u=1">m3u</A></TD>';
			echo '<TD VALIGN="TOP"><B>'.$key.'</B></TD>';
			echo '<TD><TABLE BORDER="0" WIDTH="100%">';
			arsort($value);
			foreach ($value as $string => $count) {
				echo '<TR><TD ALIGN="RIGHT" WIDTH="50"><I>'.number_format($count).'</I></TD><TD>&nbsp;</TD>';
				echo '<TD><A HREF="'.$_SERVER['PHP_SELF'].'?encodedbydistribution='.urlencode($string).'&showfiles=1">'.$string.'</A></TD></TR>';
			}
			echo '</TABLE></TD></TR>';
		}
		echo '</TABLE>';

	}

} elseif (!empty($_REQUEST['audiobitrates'])) {

	getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.audio.mp3.php', __FILE__, true);
	$BitrateDistribution = array();
	$SQLquery  = 'SELECT ROUND(audio_bitrate / 1000) AS RoundBitrate, COUNT(*) AS num';
	$SQLquery .= ' FROM `files`';
	$SQLquery .= ' WHERE (audio_bitrate > 0)';
	$SQLquery .= ' GROUP BY RoundBitrate';
	$result = safe_mysql_query($SQLquery);
	while ($row = mysql_fetch_array($result)) {
		@$BitrateDistribution[getid3_mp3::ClosestStandardMP3Bitrate($row['RoundBitrate'] * 1000)] += $row['num'];  // safe_inc
	}

	echo '<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="3">';
	echo '<TR><TH>Bitrate</TH><TH>Count</TH></TR>';
	foreach ($BitrateDistribution as $Bitrate => $Count) {
		echo '<TR>';
		echo '<TD ALIGN="RIGHT">'.round($Bitrate / 1000).' kbps</TD>';
		echo '<TD ALIGN="RIGHT">'.number_format($Count).'</TD>';
		echo '</TR>';
	}
	echo '</TABLE>';


} elseif (!empty($_REQUEST['emptygenres'])) {

	$SQLquery  = 'SELECT fileformat, filename, genre FROM `files` WHERE (genre = "") OR (genre = "Unknown") OR (genre = "Other") ORDER BY filename ASC';
	$result = safe_mysql_query($SQLquery);

	if (!empty($_REQUEST['m3u'])) {

		header('Content-type: audio/x-mpegurl');
		echo '#EXTM3U'."\n";
		while ($row = mysql_fetch_array($result)) {
			if (!in_array($row['fileformat'], $IgnoreNoTagFormats)) {
				echo WindowsShareSlashTranslate($row['filename'])."\n";
			}
		}
		exit;

	} else {

		echo '<A HREF="'.$_SERVER['PHP_SELF'].'?emptygenres='.urlencode($_REQUEST['emptygenres']).'&m3u=1">.m3u version</A><BR>';
		$EmptyGenreCounter = 0;
		echo '<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="3">';
		echo '<TR><TH>m3u</TH><TH>filename</TH></TR>';
		while ($row = mysql_fetch_array($result)) {
			if (!in_array($row['fileformat'], $IgnoreNoTagFormats)) {
				$EmptyGenreCounter++;
				echo '<TR>';
				echo '<TD><A HREF="'.$_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($row['filename']).'">m3u</A></TD>';
				echo '<TD><A HREF="demo.browse.php?filename='.rawurlencode($row['filename']).'">'.FixTextFields($row['filename']).'</A></TD>';
				echo '</TR>';
			}
		}
		echo '</TABLE>';
		echo '<B>'.number_format($EmptyGenreCounter).'</B> files with empty genres';

	}

} elseif (!empty($_REQUEST['nonemptycomments'])) {

	$SQLquery  = 'SELECT filename, comment FROM `files` WHERE (comment <> "") ORDER BY comment ASC';
	$result = safe_mysql_query($SQLquery);

	if (!empty($_REQUEST['m3u'])) {

		header('Content-type: audio/x-mpegurl');
		echo '#EXTM3U'."\n";
		while ($row = mysql_fetch_array($result)) {
			echo WindowsShareSlashTranslate($row['filename'])."\n";
		}
		exit;

	} else {

		$NonEmptyCommentsCounter = 0;
		echo '<A HREF="'.$_SERVER['PHP_SELF'].'?nonemptycomments='.urlencode($_REQUEST['nonemptycomments']).'&m3u=1">.m3u version</A><BR>';
		echo '<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="3">';
		echo '<TR><TH>m3u</TH><TH>filename</TH><TH>comments</TH></TR>';
		while ($row = mysql_fetch_array($result)) {
			$NonEmptyCommentsCounter++;
			echo '<TR>';
			echo '<TD><A HREF="'.$_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($row['filename']).'">m3u</A></TD>';
			echo '<TD><A HREF="demo.browse.php?filename='.rawurlencode($row['filename']).'">'.FixTextFields($row['filename']).'</A></TD>';
			if (strlen(trim($row['comment'])) > 0) {
				echo '<TD>'.FixTextFields($row['comment']).'</TD>';
			} else {
				echo '<TD><I>space</I></TD>';
			}
			echo '</TR>';
		}
		echo '</TABLE>';
		echo '<B>'.number_format($NonEmptyCommentsCounter).'</B> files with non-empty comments';

	}

} elseif (!empty($_REQUEST['trackzero'])) {

	$SQLquery  = 'SELECT filename, track FROM `files` WHERE (track <> "") AND ((track < "1") OR (track > "99")) ORDER BY filename ASC';
	$result = safe_mysql_query($SQLquery);

	if (!empty($_REQUEST['m3u'])) {

		header('Content-type: audio/x-mpegurl');
		echo '#EXTM3U'."\n";
		while ($row = mysql_fetch_array($result)) {
			if ((strlen($row['track']) > 0) && ($row['track'] < 1) || ($row['track'] > 99)) {
				echo WindowsShareSlashTranslate($row['filename'])."\n";
			}
		}
		exit;

	} else {

		echo '<A HREF="'.$_SERVER['PHP_SELF'].'?trackzero='.urlencode($_REQUEST['trackzero']).'&m3u=1">.m3u version</A><BR>';
		$TrackZeroCounter = 0;
		echo '<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="3">';
		echo '<TR><TH>m3u</TH><TH>filename</TH><TH>track</TH></TR>';
		while ($row = mysql_fetch_array($result)) {
			if ((strlen($row['track']) > 0) && ($row['track'] < 1) || ($row['track'] > 99)) {
				$TrackZeroCounter++;
				echo '<TR>';
				echo '<TD><A HREF="'.$_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($row['filename']).'">m3u</A></TD>';
				echo '<TD><A HREF="demo.browse.php?filename='.rawurlencode($row['filename']).'">'.FixTextFields($row['filename']).'</A></TD>';
				echo '<TD>'.FixTextFields($row['track']).'</TD>';
				echo '</TR>';
			}
		}
		echo '</TABLE>';
		echo '<B>'.number_format($TrackZeroCounter).'</B> files with track "zero"';

	}


} elseif (!empty($_REQUEST['synchronizetagsfrom']) && !empty($_REQUEST['filename'])) {

	echo 'Applying new tags from <B>'.$_REQUEST['synchronizetagsfrom'].'</B> in <B>'.FixTextFields($_REQUEST['filename']).'</B><UL>';
	$errors = array();
	if (SynchronizeAllTags($_REQUEST['filename'], $_REQUEST['synchronizetagsfrom'], 'A12', $errors)) {
		echo '<LI>Sucessfully wrote tags</LI>';
	} else {
		echo '<LI>Tag writing had errors: <UL><LI>'.implode('</LI><LI>', $errors).'</LI></UL></LI>';
	}
	echo '</UL>';


} elseif (!empty($_REQUEST['unsynchronizedtags'])) {

	$NotOKfiles        = 0;
	$FieldsToCompare   = array('title', 'artist', 'album', 'year', 'genre', 'comment', 'track');
	$TagsToCompare     = array('id3v2'=>false, 'ape'=>false, 'lyrics3'=>false, 'id3v1'=>false);
	$ID3v1FieldLengths = array('title'=>30, 'artist'=>30, 'album'=>30, 'year'=>4, 'genre'=>99, 'comment'=>28);
	if (strpos($_REQUEST['unsynchronizedtags'], '2') !== false) {
		$TagsToCompare['id3v2'] = true;
	}
	if (strpos($_REQUEST['unsynchronizedtags'], 'A') !== false) {
		$TagsToCompare['ape'] = true;
	}
	if (strpos($_REQUEST['unsynchronizedtags'], 'L') !== false) {
		$TagsToCompare['lyrics3'] = true;
	}
	if (strpos($_REQUEST['unsynchronizedtags'], '1') !== false) {
		$TagsToCompare['id3v1'] = true;
	}

	echo '<A HREF="'.$_SERVER['PHP_SELF'].'?unsynchronizedtags='.urlencode($_REQUEST['unsynchronizedtags']).'&autofix=1">Auto-fix empty tags</A><BR><BR>';
	echo '<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="3">';
	echo '<TR>';
	echo '<TH>View</TH>';
	echo '<TH>Filename</TH>';
	echo '<TH>Combined</TH>';
	if ($TagsToCompare['id3v2']) {
		echo '<TH>ID3v2</TH>';
	}
	if ($TagsToCompare['ape']) {
		echo '<TH>APE</TH>';
	}
	if ($TagsToCompare['lyrics3']) {
		echo '<TH>Lyrics3</TH>';
	}
	if ($TagsToCompare['id3v1']) {
		echo '<TH>ID3v1</TH>';
	}
	echo '</TR>';

	$SQLquery  = 'SELECT filename, comments_all, comments_id3v2, comments_ape, comments_lyrics3, comments_id3v1';
	$SQLquery .= ' FROM `files`';
	$SQLquery .= ' WHERE (fileformat = "mp3")';
	$SQLquery .= ' ORDER BY filename ASC';
	$result = safe_mysql_query($SQLquery);
	while ($row = mysql_fetch_array($result)) {

		set_time_limit(30);

		$FileOK      = true;
		$Mismatched  = array('id3v2'=>false, 'ape'=>false, 'lyrics3'=>false, 'id3v1'=>false);
		$SemiMatched = array('id3v2'=>false, 'ape'=>false, 'lyrics3'=>false, 'id3v1'=>false);
		$EmptyTags   = array('id3v2'=>true,  'ape'=>true,  'lyrics3'=>true,  'id3v1'=>true);

		$Comments['all']     = @unserialize($row['comments_all']);
		$Comments['id3v2']   = @unserialize($row['comments_id3v2']);
		$Comments['ape']     = @unserialize($row['comments_ape']);
		$Comments['lyrics3'] = @unserialize($row['comments_lyrics3']);
		$Comments['id3v1']   = @unserialize($row['comments_id3v1']);

		if (isset($Comments['ape']['tracknumber'])) {
			$Comments['ape']['track'] = $Comments['ape']['tracknumber'];
			unset($Comments['ape']['tracknumber']);
		}


		$FileOK = true;

		$ThisLine  = '<TR>';
		$ThisLine .= '<TD><A HREF="demo.browse.php?filename='.rawurlencode($row['filename']).'">view</A></TD>';
		$ThisLine .= '<TD><A HREF="'.$_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($row['filename']).'">'.FixTextFields($row['filename']).'</A></TD>';
		$tagvalues = '';
		foreach ($FieldsToCompare as $fieldname) {
			$tagvalues .= $fieldname.' = '.@implode("\n", @$Comments['all'][$fieldname])."\n";
		}
		$ThisLine .= '<TD><A HREF="'.$_SERVER['PHP_SELF'].'?synchronizetagsfrom=all&filename='.urlencode($row['filename']).'" TITLE="'.htmlentities(rtrim($tagvalues, "\n"), ENT_QUOTES).'" TARGET="retagwindow">all</A></TD>';
		foreach ($TagsToCompare as $tagtype => $CompareThisTagType) {
			if ($CompareThisTagType) {
				$tagvalues = '';
				$tagempty  = true;
				foreach ($FieldsToCompare as $fieldname) {

					if ($tagtype == 'id3v1') {

						getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.tag.id3v1.php', __FILE__, true);
						if (($fieldname == 'genre') && !getid3_id3v1::LookupGenreID(@$Comments['all'][$fieldname][0])) {
							// non-standard genres can never match, so just ignore
							$tagvalues .= $fieldname.' = '.@$Comments[$tagtype][$fieldname][0]."\n";
						} elseif ($fieldname == 'comment') {
							if (rtrim(substr(@$Comments[$tagtype][$fieldname][0], 0, 28)) != rtrim(substr(@$Comments['all'][$fieldname][0], 0, 28))) {
								$tagvalues .= $fieldname.' = [['.@$Comments[$tagtype][$fieldname][0].']]'."\n";
								if (trim(strtolower(RemoveAccents(substr(@$Comments[$tagtype][$fieldname][0], 0, 28)))) == trim(strtolower(RemoveAccents(substr(@$Comments['all'][$fieldname][0], 0, 28))))) {
									$SemiMatched[$tagtype] = true;
								} else {
									$Mismatched[$tagtype]  = true;
								}
								$FileOK = false;
							} else {
								$tagvalues .= $fieldname.' = '.@$Comments[$tagtype][$fieldname][0]."\n";
							}
						} elseif (rtrim(substr(@$Comments[$tagtype][$fieldname][0], 0, 30)) != rtrim(substr(@$Comments['all'][$fieldname][0], 0, 30))) {
							$tagvalues .= $fieldname.' = [['.@$Comments[$tagtype][$fieldname][0].']]'."\n";
							if (strtolower(RemoveAccents(trim(substr(@$Comments[$tagtype][$fieldname][0], 0, 30)))) == strtolower(RemoveAccents(trim(substr(@$Comments['all'][$fieldname][0], 0, 30))))) {
								$SemiMatched[$tagtype] = true;
							} else {
								$Mismatched[$tagtype]  = true;
							}
							$FileOK = false;
							if (strlen(trim(@$Comments[$tagtype][$fieldname][0])) > 0) {
								$EmptyTags[$tagtype] = false;
							}
						} else {
							$tagvalues .= $fieldname.' = '.@$Comments[$tagtype][$fieldname][0]."\n";
							if (strlen(trim(@$Comments[$tagtype][$fieldname][0])) > 0) {
								$EmptyTags[$tagtype] = false;
							}
						}

					} elseif (($tagtype == 'ape') && ($fieldname == 'year')) {

						if ((@$Comments['ape']['date'][0] != @$Comments['all']['year'][0]) && (@$Comments['ape']['year'][0] != @$Comments['all']['year'][0])) {
							$tagvalues .= $fieldname.' = [['.@$Comments['ape']['date'][0].']]'."\n";
							$Mismatched[$tagtype]  = true;
							$FileOK = false;
							if (strlen(trim(@$Comments['ape']['date'][0])) > 0) {
								$EmptyTags[$tagtype] = false;
							}
						} else {
							$tagvalues .= $fieldname.' = '.@$Comments[$tagtype][$fieldname][0]."\n";
							if (strlen(trim(@$Comments[$tagtype][$fieldname][0])) > 0) {
								$EmptyTags[$tagtype] = false;
							}
						}

					} elseif (($fieldname == 'genre') && in_array($Comments[$tagtype][$fieldname][0], $Comments['all'][$fieldname])) {

						$tagvalues .= $fieldname.' = '.@$Comments[$tagtype][$fieldname][0]."\n";
						if (strlen(trim(@$Comments[$tagtype][$fieldname][0])) > 0) {
							$EmptyTags[$tagtype] = false;
						}

					} elseif (@$Comments[$tagtype][$fieldname][0] != @$Comments['all'][$fieldname][0]) {

						$tagvalues .= $fieldname.' = [['.@$Comments[$tagtype][$fieldname][0].']]'."\n";
						if (trim(strtolower(RemoveAccents(@$Comments[$tagtype][$fieldname][0]))) == trim(strtolower(RemoveAccents(@$Comments['all'][$fieldname][0])))) {
							$SemiMatched[$tagtype] = true;
						} else {
							$Mismatched[$tagtype]  = true;
						}
						$FileOK = false;
						if (strlen(trim(@$Comments[$tagtype][$fieldname][0])) > 0) {
							$EmptyTags[$tagtype] = false;
						}

					} else {

						$tagvalues .= $fieldname.' = '.@$Comments[$tagtype][$fieldname][0]."\n";
						if (strlen(trim(@$Comments[$tagtype][$fieldname][0])) > 0) {
							$EmptyTags[$tagtype] = false;
						}

					}
				}

				if ($EmptyTags[$tagtype]) {
					$ThisLine .= '<TD BGCOLOR="#0099CC">';
				} elseif ($SemiMatched[$tagtype]) {
					$ThisLine .= '<TD BGCOLOR="#FF9999">';
				} elseif ($Mismatched[$tagtype]) {
					$ThisLine .= '<TD BGCOLOR="#FF0000">';
				} else {
					$ThisLine .= '<TD BGCOLOR="#00CC00">';
				}
				$ThisLine .= '<A HREF="'.$_SERVER['PHP_SELF'].'?synchronizetagsfrom='.$tagtype.'&filename='.urlencode($row['filename']).'" TITLE="'.htmlentities(rtrim($tagvalues, "\n"), ENT_QUOTES).'" TARGET="retagwindow">'.$tagtype.'</A>';
				$ThisLine .= '</TD>';
			}
		}
		$ThisLine .= '</TR>';

		if (!$FileOK) {
			$NotOKfiles++;

			if (!empty($_REQUEST['autofix'])) {

				$AnyMismatched = false;
				foreach ($Mismatched as $key => $value) {
					if ($value && ($EmptyTags["$key"] === false)) {
						$AnyMismatched = true;
					}
				}
				if ($AnyMismatched) {

					echo $ThisLine;

				} else {

					$TagsToSynch = '';
					foreach ($EmptyTags as $key => $value) {
						if ($value) {
							switch ($key) {
								case 'id3v1':
									$TagsToSynch .= '1';
									break;
								case 'id3v2':
									$TagsToSynch .= '2';
									break;
								case 'ape':
									$TagsToSynch .= 'A';
									break;
							}
						}
					}
					$errors = array();
					if (SynchronizeAllTags($row['filename'], 'all', $TagsToSynch, $errors)) {
						echo '<TR BGCOLOR="#00CC00">';
					} else {
						echo '<TR BGCOLOR="#FF0000">';
					}
					echo '<TD><A HREF="'.$_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($row['filename']).'" TITLE="'.FixTextFields(implode("\n", $errors)).'">'.FixTextFields($row['filename']).'</A></TD>';
					echo '<TD><TABLE BORDER="0">';
					echo '<TR><TD><B>'.$TagsToSynch.'</B></TD></TR>';
					echo '</TABLE></TD></TR>';
				}

			} else {

				echo $ThisLine;

			}
		}
	}

	echo '</TABLE><BR>';
	echo 'Found <B>'.number_format($NotOKfiles).'</B> files with unsynchronzed tags';

} elseif (!empty($_REQUEST['filenamepattern'])) {

	$patterns['A'] = 'artist';
	$patterns['T'] = 'title';
	$patterns['M'] = 'album';
	$patterns['N'] = 'track';
	$patterns['G'] = 'genre';

	$FieldsToUse = explode(' ', wordwrap(eregi_replace('[^A-Z]', '', $_REQUEST['filenamepattern']), 1, ' ', 1));
	foreach ($FieldsToUse as $FieldID) {
		$FieldNames[] = $patterns["$FieldID"];
	}

	$SQLquery  = 'SELECT filename, fileformat, '.implode(', ', $FieldNames);
	$SQLquery .= ' FROM `files`';
	$SQLquery .= ' WHERE (fileformat NOT LIKE "'.implode('") AND (fileformat NOT LIKE "', $IgnoreNoTagFormats).'")';
	$SQLquery .= ' ORDER BY filename ASC';
	$result = safe_mysql_query($SQLquery);
	echo 'Files that do not match naming pattern:<BR>';
	echo '<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="3">';
	echo '<TR><TH>view</TH><TH>Why</TH><TD><B>Actual filename</B><BR>(click to play/edit file)</TD><TD><B>Correct filename (based on tags)</B><BR>(click to rename file to this)</TD></TR>';
	$nonmatchingfilenames = 0;
	$Pattern = $_REQUEST['filenamepattern'];
	$PatternLength = strlen($Pattern);
	while ($row = mysql_fetch_array($result)) {
		set_time_limit(10);
		$PatternFilename = '';
		for ($i = 0; $i < $PatternLength; $i++) {
			if (isset($patterns[$Pattern{$i}])) {
				$PatternFilename .= trim(strtr($row[$patterns[$Pattern{$i}]], ':\\/*<>|', ';-~-[] '), ' ');
			} else {
				$PatternFilename .= $Pattern{$i};
			}
		}

		// Replace "~" with "-" if characters immediately before and after are both numbers
		// "/" has been replaced with "~" above which is good for multi-song medley dividers,
		// but for things like 24/7, 7/8, etc it looks better if it's 24-7, 7-8, etc.
		$tildepos = 0;
		while ($tildepos = strpos($PatternFilename, '~', $tildepos)) {
			if (ereg('[0-9]~[0-9]', substr($PatternFilename, $tildepos - 1, 3))) {
				$PatternFilename{$tildepos} = '-';
			} else {
				$tildepos++;
			}
		}

		// get rid of leading & trailing spaces if end items (artist or title for example) are missing
		$PatternFilename  = str_replace(' "', ' “', $PatternFilename);
		$PatternFilename  = str_replace('("', '(“', $PatternFilename);
		$PatternFilename  = str_replace('-"', '-“', $PatternFilename);
		$PatternFilename  = str_replace('" ', '” ', $PatternFilename.' ');
		$PatternFilename  = str_replace('"', '”', $PatternFilename);
		$PatternFilename  = str_replace('?', '', $PatternFilename);
		$PatternFilename  = str_replace('  ', ' ', $PatternFilename);
		$PatternFilename  = trim($PatternFilename, ' -');
		$PatternFilename .= '.'.$row['fileformat'];
		$ActualFilename = basename($row['filename']);
		if ($ActualFilename != $PatternFilename) {

			$NotMatchedReasons = '';
			if (strtolower($ActualFilename) === strtolower($PatternFilename)) {
				$NotMatchedReasons .= 'Aa ';
			} elseif (RemoveAccents($ActualFilename) === RemoveAccents($PatternFilename)) {
				$NotMatchedReasons .= 'ée ';
			}
			$ShortestName = min(strlen($ActualFilename), strlen($PatternFilename));
			for ($DifferenceOffset = 0; $DifferenceOffset < $ShortestName; $DifferenceOffset++) {
				if ($ActualFilename{$DifferenceOffset} !== $PatternFilename{$DifferenceOffset}) {
					break;
				}
			}
			echo '<TR>';
			echo '<TD><A HREF="demo.browse.php?filename='.rawurlencode($row['filename']).'">view</A></TD>';
			echo '<TD>&nbsp;'.$NotMatchedReasons.'</TD>';
			echo '<TD><A HREF="'.$_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($row['filename']).'">'.FixTextFields($ActualFilename).'</A></TD>';
			echo '<TD><A HREF="'.$_SERVER['PHP_SELF'].'?filenamepattern='.urlencode($_REQUEST['filenamepattern']).'&renamefilefrom='.urlencode($row['filename']).'&renamefileto='.urlencode(dirname($row['filename']).'/'.$PatternFilename).'" TITLE="'.FixTextFields(basename($row['filename']))."\n".FixTextFields(basename($PatternFilename)).'" TARGET="renamewindow">'.substr($PatternFilename, 0, $DifferenceOffset).'<B>'.substr($PatternFilename, $DifferenceOffset).'</B></A></TD>';
			echo '</TR>';

			$nonmatchingfilenames++;
		}
	}
	echo '</TABLE><BR>';
	echo 'Found '.number_format($nonmatchingfilenames).' files that do not match naming pattern<BR>';


} elseif (!empty($_REQUEST['encoderoptionsdistribution'])) {

	if (isset($_REQUEST['showtagfiles'])) {
		$SQLquery  = 'SELECT filename, encoder_options FROM `files`';
		$SQLquery .= ' WHERE (encoder_options LIKE "'.mysql_escape_string($_REQUEST['showtagfiles']).'")';
		$SQLquery .= ' AND (fileformat NOT LIKE "'.implode('") AND (fileformat NOT LIKE "', $IgnoreNoTagFormats).'")';
		$SQLquery .= ' ORDER BY filename ASC';
		$result = safe_mysql_query($SQLquery);

		if (!empty($_REQUEST['m3u'])) {

			header('Content-type: audio/x-mpegurl');
			echo '#EXTM3U'."\n";
			while ($row = mysql_fetch_array($result)) {
				echo WindowsShareSlashTranslate($row['filename'])."\n";
			}
			exit;

		} else {

			echo '<A HREF="'.$_SERVER['PHP_SELF'].'?encoderoptionsdistribution=1">Show all Encoder Options</A><HR>';
			echo 'Files with Encoder Options <B>'.$_REQUEST['showtagfiles'].'</B>:<BR>';
			echo '<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="3">';
			while ($row = mysql_fetch_array($result)) {
				echo '<TR>';
				echo '<TD><A HREF="demo.browse.php?filename='.rawurlencode($row['filename']).'">'.FixTextFields($row['filename']).'</A></TD>';
				echo '<TD>'.$row['encoder_options'].'</TD>';
				echo '</TR>';
			}
			echo '</TABLE>';

		}

	} elseif (!isset($_REQUEST['m3u'])) {

		$SQLquery  = 'SELECT encoder_options, COUNT(*) AS num FROM `files`';
		$SQLquery .= ' WHERE (fileformat NOT LIKE "'.implode('") AND (fileformat NOT LIKE "', $IgnoreNoTagFormats).'")';
		$SQLquery .= ' GROUP BY encoder_options';
		$SQLquery .= ' ORDER BY (encoder_options LIKE "LAME%") DESC, (encoder_options LIKE "CBR%") DESC, num DESC, encoder_options ASC';
		$result = safe_mysql_query($SQLquery);
		echo 'Files with Encoder Options:<BR>';
		echo '<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="3">';
		echo '<TR><TH>Encoder Options</TH><TH>Count</TH><TH>M3U</TH></TR>';
		while ($row = mysql_fetch_array($result)) {
			echo '<TR>';
			echo '<TD>'.$row['encoder_options'].'</TD>';
			echo '<TD ALIGN="RIGHT"><A HREF="'.$_SERVER['PHP_SELF'].'?encoderoptionsdistribution=1&showtagfiles='.($row['encoder_options'] ? urlencode($row['encoder_options']) : '').'">'.number_format($row['num']).'</A></TD>';
			echo '<TD ALIGN="RIGHT"><A HREF="'.$_SERVER['PHP_SELF'].'?encoderoptionsdistribution=1&showtagfiles='.($row['encoder_options'] ? urlencode($row['encoder_options']) : '').'&m3u=.m3u">m3u</A></TD>';
			echo '</TR>';
		}
		echo '</TABLE><HR>';

	}

} elseif (!empty($_REQUEST['tagtypes'])) {

	if (!isset($_REQUEST['m3u'])) {
		$SQLquery  = 'SELECT tags, COUNT(*) AS num FROM `files`';
		$SQLquery .= ' WHERE (fileformat NOT LIKE "'.implode('") AND (fileformat NOT LIKE "', $IgnoreNoTagFormats).'")';
		$SQLquery .= ' GROUP BY tags';
		$SQLquery .= ' ORDER BY num DESC';
		$result = safe_mysql_query($SQLquery);
		echo 'Files with tags:<BR>';
		echo '<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="3">';
		echo '<TR><TH>Tags</TH><TH>Count</TH><TH>M3U</TH></TR>';
		while ($row = mysql_fetch_array($result)) {
			echo '<TR>';
			echo '<TD>'.$row['tags'].'</TD>';
			echo '<TD ALIGN="RIGHT"><A HREF="'.$_SERVER['PHP_SELF'].'?tagtypes=1&showtagfiles='.($row['tags'] ? urlencode($row['tags']) : '').'">'.number_format($row['num']).'</A></TD>';
			echo '<TD ALIGN="RIGHT"><A HREF="'.$_SERVER['PHP_SELF'].'?tagtypes=1&showtagfiles='.($row['tags'] ? urlencode($row['tags']) : '').'&m3u=.m3u">m3u</A></TD>';
			echo '</TR>';
		}
		echo '</TABLE><HR>';
	}

	if (isset($_REQUEST['showtagfiles'])) {
		$SQLquery  = 'SELECT filename, tags FROM `files`';
		$SQLquery .= ' WHERE (tags LIKE "'.mysql_escape_string($_REQUEST['showtagfiles']).'")';
		$SQLquery .= ' AND (fileformat NOT LIKE "'.implode('") AND (fileformat NOT LIKE "', $IgnoreNoTagFormats).'")';
		$SQLquery .= ' ORDER BY filename ASC';
		$result = safe_mysql_query($SQLquery);

		if (!empty($_REQUEST['m3u'])) {

			header('Content-type: audio/x-mpegurl');
			echo '#EXTM3U'."\n";
			while ($row = mysql_fetch_array($result)) {
				echo WindowsShareSlashTranslate($row['filename'])."\n";
			}
			exit;

		} else {

			echo '<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="3">';
			while ($row = mysql_fetch_array($result)) {
				echo '<TR>';
				echo '<TD><A HREF="demo.browse.php?filename='.rawurlencode($row['filename']).'">'.FixTextFields($row['filename']).'</A></TD>';
				echo '<TD>'.$row['tags'].'</TD>';
				echo '</TR>';
			}
			echo '</TABLE>';

		}
	}


} elseif (!empty($_REQUEST['md5datadupes'])) {

	$OtherFormats = '';
	$AVFormats    = '';

	$SQLquery  = 'SELECT md5_data, filename, COUNT(*) AS num';
	$SQLquery .= ' FROM `files`';
	$SQLquery .= ' WHERE (md5_data <> "")';
	$SQLquery .= ' GROUP BY md5_data';
	$SQLquery .= ' ORDER BY num DESC';
	$result = safe_mysql_query($SQLquery);
	while (($row = mysql_fetch_array($result)) && ($row['num'] > 1)) {
		set_time_limit(30);

		$filenames = array();
		$tags      = array();
		$md5_data  = array();
		$SQLquery  = 'SELECT fileformat, filename, tags FROM `files`';
		$SQLquery .= ' WHERE (md5_data = "'.mysql_escape_string($row['md5_data']).'")';
		$SQLquery .= ' ORDER BY filename ASC';
		$result2 = safe_mysql_query($SQLquery);
		while ($row2 = mysql_fetch_array($result2)) {
			$thisfileformat = $row2['fileformat'];
			$filenames[] = $row2['filename'];
			$tags[]      = $row2['tags'];
			$md5_data[]  = $row['md5_data'];
		}

		$thisline  = '<TR>';
		$thisline .= '<TD VALIGN="TOP" STYLE="font-family: monospace;">'.implode('<BR>', $md5_data).'</TD>';
		$thisline .= '<TD VALIGN="TOP" NOWRAP>'.implode('<BR>', $tags).'</TD>';
		$thisline .= '<TD VALIGN="TOP">'.implode('<BR>', $filenames).'</TD>';
		$thisline .= '</TR>';

		if (in_array($thisfileformat, $IgnoreNoTagFormats)) {
			$OtherFormats .= $thisline;
		} else {
			$AVFormats .= $thisline;
		}
	}
	echo 'Duplicated MD5_DATA (Audio/Video files):<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="2">';
	echo $AVFormats.'</TABLE><HR>';
	echo 'Duplicated MD5_DATA (Other files):<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="2">';
	echo $OtherFormats.'</TABLE><HR>';


} elseif (!empty($_REQUEST['artisttitledupes'])) {

	if (isset($_REQUEST['m3uartist']) && isset($_REQUEST['m3utitle'])) {

		header('Content-type: audio/x-mpegurl');
		echo '#EXTM3U'."\n";
		$SQLquery  = 'SELECT filename FROM `files`';
		$SQLquery .= ' WHERE (artist = "'.mysql_escape_string($_REQUEST['m3uartist']).'")';
		$SQLquery .= ' AND (title = "'.mysql_escape_string($_REQUEST['m3utitle']).'")';
		$SQLquery .= ' ORDER BY filename ASC';
		$result = safe_mysql_query($SQLquery);
		while ($row = mysql_fetch_array($result)) {
			echo WindowsShareSlashTranslate($row['filename'])."\n";
		}
		exit;

	}

	$SQLquery  = 'SELECT artist, title, filename, COUNT(*) AS num FROM `files`';
	$SQLquery .= ' WHERE (artist <> "")';
	$SQLquery .= ' AND (title <> "")';
	$SQLquery .= ' GROUP BY artist, title';
	$SQLquery .= ' ORDER BY num DESC, artist ASC, title ASC';
	$result = safe_mysql_query($SQLquery);
	$uniquetitles = 0;
	$uniquefiles  = 0;

	if (!empty($_REQUEST['m3u'])) {

		header('Content-type: audio/x-mpegurl');
		echo '#EXTM3U'."\n";
		while (($row = mysql_fetch_array($result)) && ($row['num'] > 1)) {
			$SQLquery  = 'SELECT filename FROM `files`';
			$SQLquery .= ' WHERE (artist = "'.mysql_escape_string($row['artist']).'")';
			$SQLquery .= ' AND (title = "'.mysql_escape_string($row['title']).'")';
			$SQLquery .= ' ORDER BY filename ASC';
			$result2 = safe_mysql_query($SQLquery);
			while ($row2 = mysql_fetch_array($result2)) {
				echo WindowsShareSlashTranslate($row2['filename'])."\n";
			}
		}
		exit;

	} else {

		echo 'Duplicated aritst + title:<BR>';
		echo '(<A HREF="'.$_SERVER['PHP_SELF'].'?artisttitledupes=1&m3u=.m3u">.m3u version</A>)<BR>';
		echo '<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="2">';

		while (($row = mysql_fetch_array($result)) && ($row['num'] > 1)) {
			$uniquetitles++;
			set_time_limit(30);

			$filenames = array();
			$artists   = array();
			$titles    = array();
			$bitrates  = array();
			$playtimes = array();
			$SQLquery  = 'SELECT filename, artist, title, audio_bitrate, vbr_method, playtime_seconds, encoder_options FROM `files`';
			$SQLquery .= ' WHERE (artist = "'.mysql_escape_string($row['artist']).'")';
			$SQLquery .= ' AND (title = "'.mysql_escape_string($row['title']).'")';
			$SQLquery .= ' ORDER BY filename ASC';
			$result2 = safe_mysql_query($SQLquery);
			while ($row2 = mysql_fetch_array($result2)) {
				$uniquefiles++;
				$filenames[] = $row2['filename'];
				$artists[]   = $row2['artist'];
				$titles[]    = $row2['title'];
				if ($row2['vbr_method']) {
					$bitrates[]  = '<B'.($row2['encoder_options'] ? ' STYLE="text-decoration: underline; cursor: help;" TITLE="'.$row2['encoder_options'] : '').'">'.BitrateText($row2['audio_bitrate'] / 1000).'</B>';
				} else {
					$bitrates[]  = BitrateText($row2['audio_bitrate'] / 1000);
				}
				$playtimes[] = getid3_lib::PlaytimeString($row2['playtime_seconds']);
			}

			echo '<TR>';
			echo '<TD NOWRAP VALIGN="TOP">';
			foreach ($filenames as $file) {
				echo '<A HREF="demo.browse.php?deletefile='.urlencode($file).'&noalert=1" onClick="return confirm(\'Are you sure you want to delete '.addslashes($file).'? \n(this action cannot be un-done)\');" TITLE="Permanently delete '."\n".FixTextFields($file)."\n".'" TARGET="deletedupewindow">delete</A><BR>';
			}
			echo '</TD>';
			echo '<TD NOWRAP VALIGN="TOP">';
			foreach ($filenames as $file) {
				echo '<A HREF="'.$_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($file).'">play</A><BR>';
			}
			echo '</TD>';
			echo '<TD VALIGN="MIDDLE" ALIGN="CENTER" ><A HREF="'.$_SERVER['PHP_SELF'].'?artisttitledupes=1&m3uartist='.urlencode($artists[0]).'&m3utitle='.urlencode($titles[0]).'">play all</A></TD>';
			echo '<TD VALIGN="TOP" NOWRAP>'.implode('<BR>', $artists).'</TD>';
			echo '<TD VALIGN="TOP" NOWRAP>'.implode('<BR>', $titles).'</TD>';
			echo '<TD VALIGN="TOP" NOWRAP ALIGN="RIGHT">'.implode('<BR>', $bitrates).'</TD>';
			echo '<TD VALIGN="TOP" NOWRAP ALIGN="RIGHT">'.implode('<BR>', $playtimes).'</TD>';

			echo '<TD VALIGN="TOP" NOWRAP ALIGN="LEFT"><TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0">';
			foreach ($filenames as $file) {
				echo '<TR><TD NOWRAP ALIGN="RIGHT"><A HREF="demo.browse.php?filename='.rawurlencode($file).'"><SPAN STYLE="color: #339966;">'.dirname($file).'/</SPAN>'.basename($file).'</A></TD></TR>';
			}
			echo '</TABLE></TD>';

			echo '</TR>';
		}

	}
	echo '</TABLE>';
	echo number_format($uniquefiles).' files with '.number_format($uniquetitles).' unique <I>aritst + title</I><BR>';
	echo '<HR>';

} elseif (!empty($_REQUEST['filetypelist'])) {

	list($fileformat, $audioformat) = explode('|', $_REQUEST['filetypelist']);
	$SQLquery  = 'SELECT filename, fileformat, audio_dataformat';
	$SQLquery .= ' FROM `files`';
	$SQLquery .= ' WHERE (fileformat = "'.mysql_escape_string($fileformat).'")';
	$SQLquery .= ' AND (audio_dataformat = "'.mysql_escape_string($audioformat).'")';
	$SQLquery .= ' ORDER BY filename ASC';
	$result = safe_mysql_query($SQLquery);
	echo 'Files of format <B>'.$fileformat.'.'.$audioformat.'</B>:<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="4">';
	echo '<TR><TH>file</TH><TH>audio</TH><TH>filename</TH></TR>';
	while ($row = mysql_fetch_array($result)) {
		echo '<TR>';
		echo '<TD>'.$row['fileformat'].'</TD>';
		echo '<TD>'.$row['audio_dataformat'].'</TD>';
		echo '<TD><A HREF="demo.browse.php?filename='.rawurlencode($row['filename']).'">'.FixTextFields($row['filename']).'</A></TD>';
		echo '</TR>';
	}
	echo '</TABLE><HR>';

} elseif (!empty($_REQUEST['trackinalbum'])) {

	$SQLquery  = 'SELECT filename, album';
	$SQLquery .= ' FROM `files`';
	$SQLquery .= ' WHERE (album LIKE "% [%")';
	$SQLquery .= ' ORDER BY album ASC, filename ASC';
	$result = safe_mysql_query($SQLquery);
	if (!empty($_REQUEST['m3u'])) {

		header('Content-type: audio/x-mpegurl');
		echo '#EXTM3U'."\n";
		while ($row = mysql_fetch_array($result)) {
			echo WindowsShareSlashTranslate($row['filename'])."\n";
		}
		exit;

	} elseif (!empty($_REQUEST['autofix'])) {

		getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.tag.id3v1.php', __FILE__, true);
		getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.tag.id3v2.php', __FILE__, true);

		while ($row = mysql_fetch_array($result)) {
			set_time_limit(30);
			$ThisFileInfo = $getID3->analyze($filename);
			getid3_lib::CopyTagsToComments($ThisFileInfo);

			if (!empty($ThisFileInfo['tags'])) {

				$Album = trim(str_replace(strstr($ThisFileInfo['comments']['album'][0], ' ['), '', $ThisFileInfo['comments']['album'][0]));
				$Track = (string) intval(str_replace(' [', '', str_replace(']', '', strstr($ThisFileInfo['comments']['album'][0], ' ['))));
				if ($Track == '0') {
					$Track = '';
				}
				if ($Album && $Track) {
					echo '<HR>'.FixTextFields($row['filename']).'<BR>';
					echo '<I>'.$Album.'</I> (track #'.$Track.')<BR>';
					echo '<B>ID3v2:</B> '.(RemoveID3v2($row['filename'], false) ? 'removed' : 'REMOVAL FAILED!').', ';
					echo '<B>ID3v1:</B> '.(WriteID3v1($row['filename'], @$ThisFileInfo['comments']['title'][0], @$ThisFileInfo['comments']['artist'][0], $Album, @$ThisFileInfo['comments']['year'][0], @$ThisFileInfo['comments']['comment'][0], @$ThisFileInfo['comments']['genreid'][0], $Track, false) ? 'updated' : 'UPDATE FAILED').'<BR>';
				} else {
					echo ' . ';
				}

			} else {

				echo '<HR>FAILED<BR>'.FixTextFields($row['filename']).'<HR>';

			}
			flush();
		}

	} else {

		echo '<B>'.number_format(mysql_num_rows($result)).'</B> files with <B>[??]</B>-format track numbers in album field:<BR>';
		if (mysql_num_rows($result) > 0) {
			echo '(<A HREF="'.$_SERVER['PHP_SELF'].'?trackinalbum=1&m3u=.m3u">.m3u version</A>)<BR>';
			echo '<A HREF="'.$_SERVER['PHP_SELF'].'?trackinalbum=1&autofix=1">Try to auto-fix</A><BR>';
			echo '<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="4">';
			while ($row = mysql_fetch_array($result)) {
				echo '<TR>';
				echo '<TD>'.$row['album'].'</TD>';
				echo '<TD><A HREF="demo.browse.php?filename='.rawurlencode($row['filename']).'">'.FixTextFields($row['filename']).'</A></TD>';
				echo '</TR>';
			}
			echo '</TABLE>';
		}
		echo '<HR>';

	}

} elseif (!empty($_REQUEST['fileextensions'])) {

	$SQLquery  = 'SELECT filename, fileformat, audio_dataformat, video_dataformat, tags';
	$SQLquery .= ' FROM `files`';
	$SQLquery .= ' ORDER BY filename ASC';
	$result = safe_mysql_query($SQLquery);
	$invalidextensionfiles = 0;
	$invalidextensionline  = '<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="4">';
	$invalidextensionline .= '<TR><TH>file</TH><TH>audio</TH><TH>video</TH><TH>tags</TH><TH>actual</TH><TH>correct</TH><TH>filename</TH></TR>';
	while ($row = mysql_fetch_array($result)) {
		set_time_limit(30);

		$acceptableextensions = AcceptableExtensions($row['fileformat'], $row['audio_dataformat'], $row['video_dataformat']);
		$actualextension      = strtolower(fileextension($row['filename']));
		if ($acceptableextensions && !in_array($actualextension, $acceptableextensions)) {
			$invalidextensionfiles++;

			$invalidextensionline .= '<TR>';
			$invalidextensionline .= '<TD>'.$row['fileformat'].'</TD>';
			$invalidextensionline .= '<TD>'.$row['audio_dataformat'].'</TD>';
			$invalidextensionline .= '<TD>'.$row['video_dataformat'].'</TD>';
			$invalidextensionline .= '<TD>'.$row['tags'].'</TD>';
			$invalidextensionline .= '<TD>'.$actualextension.'</TD>';
			$invalidextensionline .= '<TD>'.implode('; ', $acceptableextensions).'</TD>';
			$invalidextensionline .= '<TD><A HREF="demo.browse.php?filename='.rawurlencode($row['filename']).'">'.FixTextFields($row['filename']).'</A></TD>';
			$invalidextensionline .= '</TR>';
		}
	}
	$invalidextensionline .= '</TABLE><HR>';
	echo number_format($invalidextensionfiles).' files with incorrect filename extension:<BR>';
	echo $invalidextensionline;

} elseif (isset($_REQUEST['genredistribution'])) {

	if (!empty($_REQUEST['m3u'])) {

		header('Content-type: audio/x-mpegurl');
		echo '#EXTM3U'."\n";
		$SQLquery  = 'SELECT filename';
		$SQLquery .= ' FROM `files`';
		$SQLquery .= ' WHERE (BINARY genre = "'.$_REQUEST['genredistribution'].'")';
		$SQLquery .= ' AND (fileformat NOT LIKE "'.implode('") AND (fileformat NOT LIKE "', $IgnoreNoTagFormats).'")';
		$SQLquery .= ' ORDER BY filename ASC';
		$result = safe_mysql_query($SQLquery);
		while ($row = mysql_fetch_array($result)) {
			echo WindowsShareSlashTranslate($row['filename'])."\n";
		}
		exit;

	} else {

		if ($_REQUEST['genredistribution'] == '%') {

			$SQLquery  = 'SELECT COUNT(*) AS num, genre';
			$SQLquery .= ' FROM `files`';
			$SQLquery .= ' WHERE (fileformat NOT LIKE "'.implode('") AND (fileformat NOT LIKE "', $IgnoreNoTagFormats).'")';
			$SQLquery .= ' GROUP BY genre';
			$SQLquery .= ' ORDER BY num DESC';
			$result = safe_mysql_query($SQLquery);
			getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'module.tag.id3v1.php', __FILE__, true);
			echo '<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="4">';
			echo '<TR><TH>Count</TH><TH>Genre</TH><TH>m3u</TH></TR>';
			while ($row = mysql_fetch_array($result)) {
				$GenreID = getid3_id3v1::LookupGenreID($row['genre']);
				if (is_numeric($GenreID)) {
					echo '<TR BGCOLOR="#00FF00;">';
				} else {
					echo '<TR BGCOLOR="#FF9999;">';
				}
				echo '<TD><A HREF="'.$_SERVER['PHP_SELF'].'?genredistribution='.urlencode($row['genre']).'">'.number_format($row['num']).'</A></TD>';
				echo '<TD NOWRAP>'.str_replace("\t", '<BR>', $row['genre']).'</TD>';
				echo '<TD><A HREF="'.$_SERVER['PHP_SELF'].'?m3u=.m3u&genredistribution='.urlencode($row['genre']).'">.m3u</A></TD>';
				echo '</TR>';
			}
			echo '</TABLE><HR>';

		} else {

			$SQLquery  = 'SELECT filename, genre';
			$SQLquery .= ' FROM `files`';
			$SQLquery .= ' WHERE (genre LIKE "'.mysql_escape_string($_REQUEST['genredistribution']).'")';
			$SQLquery .= ' ORDER BY filename ASC';
			$result = safe_mysql_query($SQLquery);
			echo '<A HREF="'.$_SERVER['PHP_SELF'].'?genredistribution='.urlencode('%').'">All Genres</A><BR>';
			echo '<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="4">';
			echo '<TR><TH>Genre</TH><TH>m3u</TH><TH>Filename</TH></TR>';
			while ($row = mysql_fetch_array($result)) {
				echo '<TR>';
				echo '<TD NOWRAP>'.str_replace("\t", '<BR>', $row['genre']).'</TD>';
				echo '<TD><A HREF="'.$_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($row['filename']).'">m3u</A></TD>';
				echo '<TD><A HREF="demo.browse.php?filename='.rawurlencode($row['filename']).'">'.FixTextFields($row['filename']).'</A></TD>';
				echo '</TR>';
			}
			echo '</TABLE><HR>';

		}


	}

} elseif (!empty($_REQUEST['formatdistribution'])) {

	$SQLquery  = 'SELECT fileformat, audio_dataformat, COUNT(*) AS num';
	$SQLquery .= ' FROM `files`';
	$SQLquery .= ' GROUP BY fileformat, audio_dataformat';
	$SQLquery .= ' ORDER BY num DESC';
	$result = safe_mysql_query($SQLquery);
	echo 'File format distribution:<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="4">';
	echo '<TR><TH>Number</TH><TH>Format</TH></TR>';
	while ($row = mysql_fetch_array($result)) {
		echo '<TR>';
		echo '<TD ALIGN="RIGHT">'.number_format($row['num']).'</TD>';
		echo '<TD><A HREF="'.$_SERVER['PHP_SELF'].'?filetypelist='.$row['fileformat'].'|'.$row['audio_dataformat'].'">'.($row['fileformat'] ? $row['fileformat'] : '<I>unknown</I>').(($row['audio_dataformat'] && ($row['audio_dataformat'] != $row['fileformat'])) ? '.'.$row['audio_dataformat'] : '').'</A></TD>';
		echo '</TR>';
	}
	echo '</TABLE><HR>';

} elseif (!empty($_REQUEST['errorswarnings'])) {

	$SQLquery  = 'SELECT filename, error, warning';
	$SQLquery .= ' FROM `files`';
	$SQLquery .= ' WHERE (error <> "")';
	$SQLquery .= ' OR (warning <> "")';
	$SQLquery .= ' ORDER BY filename ASC';
	$result = safe_mysql_query($SQLquery);

	if (!empty($_REQUEST['m3u'])) {

		header('Content-type: audio/x-mpegurl');
		echo '#EXTM3U'."\n";
		while ($row = mysql_fetch_array($result)) {
			echo WindowsShareSlashTranslate($row['filename'])."\n";
		}
		exit;

	} else {

		echo number_format(mysql_num_rows($result)).' files with errors or warnings:<BR>';
		echo '(<A HREF="'.$_SERVER['PHP_SELF'].'?errorswarnings=1&m3u=.m3u">.m3u version</A>)<BR>';
		echo '<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="4">';
		echo '<TR><TH>Filename</TH><TH>Error</TH><TH>Warning</TH></TR>';
		while ($row = mysql_fetch_array($result)) {
			echo '<TR>';
			echo '<TD><A HREF="demo.browse.php?filename='.rawurlencode($row['filename']).'">'.FixTextFields($row['filename']).'</A></TD>';
			echo '<TD>'.(!empty($row['error'])   ? '<LI>'.str_replace("\t", '<LI>', FixTextFields($row['error'])).'</LI>' : '&nbsp;').'</TD>';
			echo '<TD>'.(!empty($row['warning']) ? '<LI>'.str_replace("\t", '<LI>', FixTextFields($row['warning'])).'</LI>' : '&nbsp;').'</TD>';
			echo '</TR>';
		}
	}
	echo '</TABLE><HR>';

} elseif (!empty($_REQUEST['fixid3v1padding'])) {

	getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'write.id3v1.php', __FILE__, true);
	$id3v1_writer = new getid3_write_id3v1;

	$SQLquery  = 'SELECT filename, error, warning';
	$SQLquery .= ' FROM `files`';
	$SQLquery .= ' WHERE (fileformat = "mp3")';
	$SQLquery .= ' AND ((error <> "")';
	$SQLquery .= ' OR (warning <> ""))';
	$SQLquery .= ' ORDER BY filename ASC';
	$result = safe_mysql_query($SQLquery);
	$totaltofix = mysql_num_rows($result);
	$rowcounter = 0;
	while ($row = mysql_fetch_array($result)) {
		set_time_limit(30);
		if (strpos($row['warning'], 'Some ID3v1 fields do not use NULL characters for padding') !== false) {
			set_time_limit(30);
			$id3v1_writer->filename = $row['filename'];
			echo ($id3v1_writer->FixID3v1Padding() ? '<SPAN STYLE="color: #009900;">fixed - ' : '<SPAN STYLE="color: #FF0000;">error - ');
		} else {
			echo '<SPAN STYLE="color: #0000FF;">No error? - ';
		}
		echo '['.++$rowcounter.' / '.$totaltofix.'] ';
		echo FixTextFields($row['filename']).'</SPAN><BR>';
		flush();
	}

} elseif (!empty($_REQUEST['vbrmethod'])) {

	if ($_REQUEST['vbrmethod'] == '1') {

		$SQLquery  = 'SELECT COUNT(*) AS num, vbr_method';
		$SQLquery .= ' FROM `files`';
		$SQLquery .= ' GROUP BY vbr_method';
		$SQLquery .= ' ORDER BY vbr_method';
		$result = safe_mysql_query($SQLquery);
		echo 'VBR methods:<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="4">';
		echo '<TR><TH>Count</TH><TH>VBR Method</TH></TR>';
		while ($row = mysql_fetch_array($result)) {
			echo '<TR>';
			echo '<TD ALIGN="RIGHT">'.FixTextFields(number_format($row['num'])).'</TD>';
			if ($row['vbr_method']) {
				echo '<TD><A HREF="'.$_SERVER['PHP_SELF'].'?vbrmethod='.$row['vbr_method'].'">'.FixTextFields($row['vbr_method']).'</A></TD>';
			} else {
				echo '<TD><I>CBR</I></TD>';
			}
			echo '</TR>';
		}
		echo '</TABLE>';

	} else {

		$SQLquery  = 'SELECT filename';
		$SQLquery .= ' FROM `files`';
		$SQLquery .= ' WHERE (vbr_method = "'.mysql_escape_string($_REQUEST['vbrmethod']).'")';
		$result = safe_mysql_query($SQLquery);
		echo number_format(mysql_num_rows($result)).' files with VBR_method of "'.$_REQUEST['vbrmethod'].'":<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="3">';
		while ($row = mysql_fetch_array($result)) {
			echo '<TR><TD><A HREF="'.$_SERVER['PHP_SELF'].'?m3ufilename='.urlencode($row['filename']).'">m3u</A></TD>';
			echo '<TD><A HREF="demo.browse.php?filename='.rawurlencode($row['filename']).'">'.FixTextFields($row['filename']).'</A></TD></TR>';
		}
		echo '</TABLE>';

	}
	echo '<HR>';

} elseif (!empty($_REQUEST['correctcase'])) {

	$SQLquery  = 'SELECT filename, fileformat';
	$SQLquery .= ' FROM `files`';
	$SQLquery .= ' WHERE (fileformat <> "")';
	$SQLquery .= ' ORDER BY filename ASC';
	$result = safe_mysql_query($SQLquery);
	echo 'Copy and paste the following into a DOS batch file. You may have to run this script more than once to catch all the changes (remember to scan for deleted/changed files and rescan directory between scans)<HR>';
	echo '<PRE>';
	$lastdir = '';
	while ($row = mysql_fetch_array($result)) {
		set_time_limit(30);
		$CleanedFilename = CleanUpFileName($row['filename']);
		if ($row['filename'] != $CleanedFilename) {
			if (strtolower($lastdir) != strtolower(str_replace('/', '\\', dirname($row['filename'])))) {
				$lastdir = str_replace('/', '\\', dirname($row['filename']));
				echo 'cd "'.$lastdir.'"'."\n";
			}
			echo 'ren "'.basename($row['filename']).'" "'.basename(CleanUpFileName($row['filename'])).'"'."\n";
		}
	}
	echo '</PRE>';
	echo '<HR>';

}

function CleanUpFileName($filename) {
	$DirectoryName = dirname($filename);
	$FileExtension = fileextension(basename($filename));
	$BaseFilename  = basename($filename, '.'.$FileExtension);

	$BaseFilename = strtolower($BaseFilename);
	$BaseFilename = str_replace('_', ' ', $BaseFilename);
	//$BaseFilename = str_replace('-', ' - ', $BaseFilename);
	$BaseFilename = str_replace('(', ' (', $BaseFilename);
	$BaseFilename = str_replace('( ', '(', $BaseFilename);
	$BaseFilename = str_replace(')', ') ', $BaseFilename);
	$BaseFilename = str_replace(' )', ')', $BaseFilename);
	$BaseFilename = str_replace(' \'\'', ' “', $BaseFilename);
	$BaseFilename = str_replace('\'\' ', '” ', $BaseFilename);
	$BaseFilename = str_replace(' vs ', ' vs. ', $BaseFilename);
	while (strstr($BaseFilename, '  ') !== false) {
		$BaseFilename = str_replace('  ', ' ', $BaseFilename);
	}
	$BaseFilename = trim($BaseFilename);

	return $DirectoryName.'/'.BetterUCwords($BaseFilename).'.'.strtolower($FileExtension);
}

function BetterUCwords($string) {
	$stringlength = strlen($string);

	$string{0} = strtoupper($string{0});
	for ($i = 1; $i < $stringlength; $i++) {
		if (($string{$i - 1} == '\'') && ($i > 1) && (($string{$i - 2} == 'O') || ($string{$i - 2} == ' '))) {
			// O'Clock, 'Em
			$string{$i} = strtoupper($string{$i});
		} elseif (ereg('^[\'A-Za-z0-9À-ÿ]$', $string{$i - 1})) {
			$string{$i} = strtolower($string{$i});
		} else {
			$string{$i} = strtoupper($string{$i});
		}
	}

	static $LowerCaseWords = array('vs.', 'feat.');
	static $UpperCaseWords = array('DJ', 'USA', 'II', 'MC', 'CD', 'TV', '\'N\'');

	$OutputListOfWords = array();
	$ListOfWords = explode(' ', $string);
	foreach ($ListOfWords as $ThisWord) {
		if (in_array(strtolower(str_replace('(', '', $ThisWord)), $LowerCaseWords)) {
			$ThisWord = strtolower($ThisWord);
		} elseif (in_array(strtoupper(str_replace('(', '', $ThisWord)), $UpperCaseWords)) {
			$ThisWord = strtoupper($ThisWord);
		} elseif ((substr($ThisWord, 0, 2) == 'Mc') && (strlen($ThisWord) > 2)) {
			$ThisWord{2} = strtoupper($ThisWord{2});
		} elseif ((substr($ThisWord, 0, 3) == 'Mac') && (strlen($ThisWord) > 3)) {
			$ThisWord{3} = strtoupper($ThisWord{3});
		}
		$OutputListOfWords[] = $ThisWord;
	}
	$UCstring = implode(' ', $OutputListOfWords);
	$UCstring = str_replace(' From “', ' from “', $UCstring);
	$UCstring = str_replace(' \'n\' ', ' \'N\' ', $UCstring);

	return $UCstring;
}



echo '<HR><FORM ACTION="'.FixTextFields($_SERVER['PHP_SELF']).'">';
echo '<B>Warning:</B> Scanning a new directory will erase all previous entries in the database!<BR>';
echo 'Directory: <INPUT TYPE="TEXT" NAME="scan" VALUE="'.FixTextFields(!empty($_REQUEST['scan']) ? $_REQUEST['scan'] : '').'"> ';
echo '<INPUT TYPE="SUBMIT" VALUE="Go" onClick="return confirm(\'Are you sure you want to erase all entries in the database and start scanning again?\');">';
echo '</FORM>';
echo '<HR><FORM ACTION="'.FixTextFields($_SERVER['PHP_SELF']).'">';
echo 'Re-scanning a new directory will only add new, previously unscanned files into the list (and not erase the database).<BR>';
echo 'Directory: <INPUT TYPE="TEXT" NAME="newscan" VALUE="'.FixTextFields(!empty($_REQUEST['newscan']) ? $_REQUEST['newscan'] : '').'"> ';
echo '<INPUT TYPE="SUBMIT" VALUE="Go">';
echo '</FORM><HR>';
echo '<UL>';
echo '<LI><A HREF="'.$_SERVER['PHP_SELF'].'?deadfilescheck=1">Remove deleted or changed files from database</A></LI>';
echo '<LI><A HREF="'.$_SERVER['PHP_SELF'].'?md5datadupes=1">List files with identical MD5_DATA values</A></LI>';
echo '<LI><A HREF="'.$_SERVER['PHP_SELF'].'?artisttitledupes=1">List files with identical artist + title</A></LI>';
echo '<LI><A HREF="'.$_SERVER['PHP_SELF'].'?fileextensions=1">File with incorrect file extension</A></LI>';
echo '<LI><A HREF="'.$_SERVER['PHP_SELF'].'?formatdistribution=1">File Format Distribution</A></LI>';
echo '<LI><A HREF="'.$_SERVER['PHP_SELF'].'?audiobitrates=1">Audio Bitrate Distribution</A></LI>';
echo '<LI><A HREF="'.$_SERVER['PHP_SELF'].'?vbrmethod=1">VBR_Method Distribution</A></LI>';
echo '<LI><A HREF="'.$_SERVER['PHP_SELF'].'?tagtypes=1">Tag Type Distribution</A></LI>';
echo '<LI><A HREF="'.$_SERVER['PHP_SELF'].'?genredistribution='.urlencode('%').'">Genre Distribution</A></LI>';
//echo '<LI><A HREF="'.$_SERVER['PHP_SELF'].'?missingtrackvolume=1">Scan for missing track volume information (update database from pre-v1.7.0b5)</A></LI>';
echo '<LI><A HREF="'.$_SERVER['PHP_SELF'].'?encoderoptionsdistribution=1">Encoder Options Distribution</A></LI>';
echo '<LI><A HREF="'.$_SERVER['PHP_SELF'].'?encodedbydistribution='.urlencode('%').'">Encoded By (ID3v2) Distribution</A></LI>';
echo '<LI><A HREF="'.$_SERVER['PHP_SELF'].'?trackinalbum=1">Track number in Album field</A></LI>';
echo '<LI><A HREF="'.$_SERVER['PHP_SELF'].'?emptygenres=1">Blank genres</A></LI>';
echo '<LI><A HREF="'.$_SERVER['PHP_SELF'].'?trackzero=1">Track "zero"</A></LI>';
echo '<LI><A HREF="'.$_SERVER['PHP_SELF'].'?nonemptycomments=1">non-empty comments</A></LI>';
echo '<LI><A HREF="'.$_SERVER['PHP_SELF'].'?unsynchronizedtags=2A1">Tags that are not synchronized</A> (<A HREF="'.$_SERVER['PHP_SELF'].'?unsynchronizedtags=2A1&autofix=1">autofix</A>)</LI>';
echo '<LI><A HREF="'.$_SERVER['PHP_SELF'].'?filenamepattern='.urlencode('A - T').'">Filenames that don\'t match pattern</A></LI>';
echo '<LI><A HREF="'.$_SERVER['PHP_SELF'].'?correctcase=1">Correct filename case (Win/DOS)</A></LI>';
echo '<LI><A HREF="'.$_SERVER['PHP_SELF'].'?fixid3v1padding=1">Fix ID3v1 invalid padding</A></LI>';
echo '<LI><A HREF="'.$_SERVER['PHP_SELF'].'?errorswarnings=1">Files with Errors and/or Warnings</A></LI>';
echo '<LI><A HREF="'.$_SERVER['PHP_SELF'].'?rescanerrors=1">Re-scan only files with Errors and/or Warnings</A></LI>';
echo '</UL>';

$SQLquery = 'SELECT COUNT(*) AS TotalFiles, SUM(playtime_seconds) AS TotalPlaytime, SUM(filesize) AS TotalFilesize, AVG(playtime_seconds) AS AvgPlaytime, AVG(filesize) AS AvgFilesize, AVG(audio_bitrate + video_bitrate) AS AvgBitrate FROM `files`';
$result = mysql_query($SQLquery);
if ($row = mysql_fetch_array($result)) {
	echo '<HR><B>Currently in the database:</B><TABLE>';
	echo '<TR><TH ALIGN="LEFT">Total Files</TH><TD>'.number_format($row['TotalFiles']).'</TD></TR>';
	echo '<TR><TH ALIGN="LEFT">Total Filesize</TH><TD>'.number_format($row['TotalFilesize'] / 1048576).' MB</TD></TR>';
	echo '<TR><TH ALIGN="LEFT">Total Playtime</TH><TD>'.number_format($row['TotalPlaytime'] / 3600, 1).' hours</TD></TR>';
	echo '<TR><TH ALIGN="LEFT">Average Filesize</TH><TD>'.number_format($row['AvgFilesize'] / 1048576, 1).' MB</TD></TR>';
	echo '<TR><TH ALIGN="LEFT">Average Playtime</TH><TD>'.getid3_lib::PlaytimeString($row['AvgPlaytime']).'</TD></TR>';
	echo '<TR><TH ALIGN="LEFT">Average Bitrate</TH><TD>'.BitrateText($row['AvgBitrate'] / 1000, 1).'</TD></TR>';
	echo '</TABLE>';
}

?>
</BODY>
</HTML>
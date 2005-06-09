<HTML>
<HEAD>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8"/>
<TITLE>getID3() - Sample file browser</TITLE>
</HEAD>
<BODY>
<?php
/////////////////////////////////////////////////////////////////
/// getID3() by James Heinrich <info@getid3.org>               //
//  available at http://getid3.sourceforge.net                 //
//            or http://www.getid3.org                         //
/////////////////////////////////////////////////////////////////
//                                                             //
// /demo/demo.browse.php - part of getID3()                     //
// Sample script for browsing/scanning files and displaying    //
// information returned by getID3()                            //
// See readme.txt for more details                             //
//                                                            ///
/////////////////////////////////////////////////////////////////


require_once('../getid3/getid3.php');

// Initialize getID3 engine
$getID3 = new getID3;


$getID3checkColor_Head           = 'CCCCDD';
$getID3checkColor_DirectoryLight = 'EEBBBB';
$getID3checkColor_DirectoryDark  = 'FFCCCC';
$getID3checkColor_FileLight      = 'EEEEEE';
$getID3checkColor_FileDark       = 'DDDDDD';
$getID3checkColor_UnknownLight   = 'CCCCFF';
$getID3checkColor_UnknownDark    = 'BBBBDD';



if (!function_exists('getmicrotime')) {
	function getmicrotime() {
		list($usec, $sec) = explode(' ', microtime());
		return ((float) $usec + (float) $sec);
	}
}



ob_start();
echo '<HTML><HEAD>';
echo '<TITLE>getID3() - /demo/demo.browse.php (sample script)</TITLE>';
echo '<STYLE>BODY,TD,TH { font-family: sans-serif; font-size: 9pt; }</STYLE>';
echo '</HEAD><BODY>';

if (isset($_REQUEST['deletefile'])) {
	if (file_exists($_REQUEST['deletefile'])) {
		if (unlink($_REQUEST['deletefile'])) {
			$deletefilemessage = 'Successfully deleted '.addslashes($_REQUEST['deletefile']);
		} else {
			$deletefilemessage = 'FAILED to delete '.addslashes($_REQUEST['deletefile']).' - error deleting file';
		}
	} else {
		$deletefilemessage = 'FAILED to delete '.addslashes($_REQUEST['deletefile']).' - file does not exist';
	}
	if (isset($_REQUEST['noalert'])) {
		echo '<B><FONT COLOR="'.(($deletefilemessage{0} == 'F') ? '#FF0000' : '#008000').'">'.$deletefilemessage.'</FONT></B><HR>';
	} else {
		echo '<SCRIPT LANGUAGE="JavaScript">alert("'.$deletefilemessage.'");</SCRIPT>';
	}
}


if (isset($_REQUEST['filename'])) {
	if (!file_exists($_REQUEST['filename'])) {
		die($_REQUEST['filename'].' does not exist');
	}
	$starttime = getmicrotime();
	$AutoGetHashes = (bool) (filesize($_REQUEST['filename']) < 52428800); // auto-get md5_data, md5_file, sha1_data, sha1_file if filesize < 50MB

	$getID3->option_md5_data  = $AutoGetHashes;
	$getID3->option_sha1_data = $AutoGetHashes;
	$ThisFileInfo = $getID3->analyze($_REQUEST['filename']);
	if ($AutoGetHashes) {
		$ThisFileInfo['md5_file']  = getid3_lib::md5_file($_REQUEST['filename']);
		$ThisFileInfo['sha1_file'] = getid3_lib::sha1_file($_REQUEST['filename']);
	}


	getid3_lib::CopyTagsToComments($ThisFileInfo);

	$listdirectory = dirname(getid3_lib::SafeStripSlashes($_REQUEST['filename']));
	$listdirectory = realpath($listdirectory); // get rid of /../../ references

	if (GETID3_OS_ISWINDOWS) {
		// this mostly just gives a consistant look to Windows and *nix filesystems
		// (windows uses \ as directory seperator, *nix uses /)
		$listdirectory = str_replace('\\', '/', $listdirectory.'/');
	}

	if (strstr($_REQUEST['filename'], 'http://') || strstr($_REQUEST['filename'], 'ftp://')) {
		echo '<I>Cannot browse remote filesystems</I><BR>';
	} else {
		echo 'Browse: <A HREF="'.$_SERVER['PHP_SELF'].'?listdirectory='.urlencode($listdirectory).'">'.$listdirectory.'</A><BR>';
	}

	echo table_var_dump($ThisFileInfo);
	$endtime = getmicrotime();
	echo 'File parsed in '.number_format($endtime - $starttime, 3).' seconds.<BR>';

} else {

	$listdirectory = (isset($_REQUEST['listdirectory']) ? getid3_lib::SafeStripSlashes($_REQUEST['listdirectory']) : '.');
	$listdirectory = realpath($listdirectory); // get rid of /../../ references
	$currentfulldir = $listdirectory.'/';

	if (GETID3_OS_ISWINDOWS) {
		// this mostly just gives a consistant look to Windows and *nix filesystems
		// (windows uses \ as directory seperator, *nix uses /)
		$currentfulldir = str_replace('\\', '/', $listdirectory.'/');
	}

	if ($handle = @opendir($listdirectory)) {

		echo str_repeat(' ', 300); // IE buffers the first 300 or so chars, making this progressive display useless - fill the buffer with spaces
		echo 'Processing';

		$starttime = getmicrotime();

		$TotalScannedUnknownFiles  = 0;
		$TotalScannedKnownFiles    = 0;
		$TotalScannedPlaytimeFiles = 0;
		$TotalScannedBitrateFiles  = 0;
		$TotalScannedFilesize      = 0;
		$TotalScannedPlaytime      = 0;
		$TotalScannedBitrate       = 0;
		$FilesWithWarnings         = 0;
		$FilesWithErrors           = 0;

		while ($file = readdir($handle)) {
			set_time_limit(30); // allocate another 30 seconds to process this file - should go much quicker than this unless intense processing (like bitrate histogram analysis) is enabled
			echo ' .'; // progress indicator dot
			flush();  // make sure the dot is shown, otherwise it's useless
			$currentfilename = $listdirectory.'/'.$file;

			// symbolic-link-resolution enhancements by davidbullockØtech-center*com
			$TargetObject     = realpath($currentfilename);  // Find actual file path, resolve if it's a symbolic link
			$TargetObjectType = filetype($TargetObject);     // Check file type without examining extension

			if($TargetObjectType == 'dir') {
				switch ($file) {
					case '..':
						$ParentDir = realpath($file.'/..').'/';
						if (GETID3_OS_ISWINDOWS) {
							$ParentDir = str_replace('\\', '/', $ParentDir);
						}
						$DirectoryContents[$currentfulldir]['dir'][$file]['filename'] = $ParentDir;
						break;

					case '.':
						// ignore
						break;

					default:
						$DirectoryContents[$currentfulldir]['dir'][$file]['filename'] = $file;
						break;
				}

			} elseif ($TargetObjectType == 'file') {

				$getID3->option_md5_data = isset($_REQUEST['ShowMD5']);
				$fileinformation = $getID3->analyze($currentfilename);

				getid3_lib::CopyTagsToComments($fileinformation);

				$TotalScannedFilesize += @$fileinformation['filesize'];

				if (isset($_REQUEST['ShowMD5'])) {
					$fileinformation['md5_file'] = md5($currentfilename);
				}

				if (!empty($fileinformation['fileformat'])) {
					$DirectoryContents[$currentfulldir]['known'][$file] = $fileinformation;
					$TotalScannedPlaytime += @$fileinformation['playtime_seconds'];
					$TotalScannedBitrate  += @$fileinformation['bitrate'];
					$TotalScannedKnownFiles++;
				} else {
					$DirectoryContents[$currentfulldir]['other'][$file] = $fileinformation;
					$DirectoryContents[$currentfulldir]['other'][$file]['playtime_string'] = '-';
					$TotalScannedUnknownFiles++;
				}
				if (isset($fileinformation['playtime_seconds']) && ($fileinformation['playtime_seconds'] > 0)) {
					$TotalScannedPlaytimeFiles++;
				}
				if (isset($fileinformation['bitrate']) && ($fileinformation['bitrate'] > 0)) {
					$TotalScannedBitrateFiles++;
				}
			}
		}
		$endtime = getmicrotime();
		closedir($handle);
		echo 'done<BR>';
		echo 'Directory scanned in '.number_format($endtime - $starttime, 2).' seconds.<BR>';
		flush();

		$columnsintable = 14;
		echo '<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="3">';

		echo '<TR BGCOLOR="#'.$getID3checkColor_Head.'"><TH COLSPAN="'.$columnsintable.'">Files in '.$currentfulldir.'</TH></TR>';
		$rowcounter = 0;
		foreach ($DirectoryContents as $dirname => $val) {
			if (is_array($DirectoryContents[$dirname]['dir'])) {
				uksort($DirectoryContents[$dirname]['dir'], 'MoreNaturalSort');
				foreach ($DirectoryContents[$dirname]['dir'] as $filename => $fileinfo) {
					echo '<TR BGCOLOR="#'.(($rowcounter++ % 2) ? $getID3checkColor_DirectoryDark : $getID3checkColor_DirectoryLight).'">';
					if ($filename == '..') {
						echo '<TD COLSPAN="'.$columnsintable.'">Parent directory: <A HREF="'.$_SERVER['PHP_SELF'].'?listdirectory='.urlencode($dirname.$filename).'"><B>';
						if (GETID3_OS_ISWINDOWS) {
							echo str_replace('\\', '/', realpath($dirname.$filename));
						} else {
							echo realpath($dirname.$filename);
						}
						echo '/</B></A></TD>';
					} else {
						echo '<TD COLSPAN="'.$columnsintable.'"><A HREF="'.$_SERVER['PHP_SELF'].'?listdirectory='.urlencode($dirname.$filename).'"><B>'.FixTextFields($filename).'</B></A></TD>';
					}
					echo '</TR>';
				}
			}

			echo '<TR BGCOLOR="#'.$getID3checkColor_Head.'">';
			echo '<TH>Filename</TH>';
			echo '<TH>File Size</TH>';
			echo '<TH>Format</TH>';
			echo '<TH>Playtime</TH>';
			echo '<TH>Bitrate</TH>';
			echo '<TH>Artist</TH>';
			echo '<TH>Title</TH>';
			if (isset($_REQUEST['ShowMD5'])) {
				echo '<TH>MD5&nbsp;File (File) (<A HREF="'.$_SERVER['PHP_SELF'].'?listdirectory='.rawurlencode(isset($_REQUEST['listdirectory']) ? $_REQUEST['listdirectory'] : '.').'">disable</A>)</TH>';
				echo '<TH>MD5&nbsp;Data (File) (<A HREF="'.$_SERVER['PHP_SELF'].'?listdirectory='.rawurlencode(isset($_REQUEST['listdirectory']) ? $_REQUEST['listdirectory'] : '.').'">disable</A>)</TH>';
				echo '<TH>MD5&nbsp;Data (Source) (<A HREF="'.$_SERVER['PHP_SELF'].'?listdirectory='.rawurlencode(isset($_REQUEST['listdirectory']) ? $_REQUEST['listdirectory'] : '.').'">disable</A>)</TH>';
			} else {
				echo '<TH COLSPAN="3">MD5&nbsp;Data (<A HREF="'.$_SERVER['PHP_SELF'].'?listdirectory='.rawurlencode(isset($_REQUEST['listdirectory']) ? $_REQUEST['listdirectory'] : '.').'&ShowMD5=1">enable</A>)</TH>';
			}
			echo '<TH>Tags</TH>';
			echo '<TH>Errors & Warnings</TH>';
			echo '<TH>Edit</TH>';
			echo '<TH>Delete</TH>';
			echo '</TR>';

			if (isset($DirectoryContents[$dirname]['known']) && is_array($DirectoryContents[$dirname]['known'])) {
				uksort($DirectoryContents[$dirname]['known'], 'MoreNaturalSort');
				foreach ($DirectoryContents[$dirname]['known'] as $filename => $fileinfo) {
//var_dump($fileinfo);
					echo '<TR BGCOLOR="#'.(($rowcounter++ % 2) ? $getID3checkColor_FileDark : $getID3checkColor_FileLight).'">';
					echo '<TD><A HREF="'.$_SERVER['PHP_SELF'].'?filename='.urlencode($dirname.$filename).'" TITLE="View detailed analysis">'.FixTextFields(getid3_lib::SafeStripSlashes($filename)).'</A></TD>';
					echo '<TD ALIGN="RIGHT">&nbsp;'.number_format($fileinfo['filesize']).'</TD>';
					echo '<TD ALIGN="RIGHT">&nbsp;'.NiceDisplayFiletypeFormat($fileinfo).'</TD>';
					echo '<TD ALIGN="RIGHT">&nbsp;'.(isset($fileinfo['playtime_string']) ? $fileinfo['playtime_string'] : '-').'</TD>';
					echo '<TD ALIGN="RIGHT">&nbsp;'.(isset($fileinfo['bitrate']) ? BitrateText($fileinfo['bitrate'] / 1000, 0, ((@$fileinfo['audio']['bitrate_mode'] == 'vbr') ? true : false)) : '-').'</TD>';
					echo '<TD ALIGN="LEFT">&nbsp;'.(isset($fileinfo['comments_html']['artist']) ? implode('<BR>', $fileinfo['comments_html']['artist']) : '').'</TD>';
					echo '<TD ALIGN="LEFT">&nbsp;'.(isset($fileinfo['comments_html']['title']) ? implode('<BR>', $fileinfo['comments_html']['title']) : '').'</TD>';
					if (isset($_REQUEST['ShowMD5'])) {
						echo '<TD ALIGN="LEFT"><TT>'.(isset($fileinfo['md5_file'])        ? $fileinfo['md5_file']        : '&nbsp;').'</TT></TD>';
						echo '<TD ALIGN="LEFT"><TT>'.(isset($fileinfo['md5_data'])        ? $fileinfo['md5_data']        : '&nbsp;').'</TT></TD>';
						echo '<TD ALIGN="LEFT"><TT>'.(isset($fileinfo['md5_data_source']) ? $fileinfo['md5_data_source'] : '&nbsp;').'</TT></TD>';
					} else {
						echo '<TD ALIGN="CENTER" COLSPAN="3">-</TD>';
					}
					echo '<TD ALIGN="LEFT">&nbsp;'.@implode(', ', array_keys($fileinfo['tags'])).'</TD>';

					echo '<TD ALIGN="LEFT">&nbsp;';
					if (!empty($fileinfo['warning'])) {
						$FilesWithWarnings++;
						echo '<A HREF="javascript:alert(\''.FixTextFields(implode('\\n', $fileinfo['warning'])).'\');" TITLE="'.FixTextFields(implode("\n", $fileinfo['warning'])).'">warning</A><BR>';
					}
					if (!empty($fileinfo['error'])) {
						$FilesWithErrors++;
						echo '<A HREF="javascript:alert(\''.FixTextFields(implode('\\n', $fileinfo['error'])).'\');" TITLE="'.FixTextFields(implode("\n", $fileinfo['error'])).'">error</A><BR>';
					}
					echo '</TD>';

					echo '<TD ALIGN="LEFT">&nbsp;';
					switch (@$fileinfo['fileformat']) {
						case 'mp3':
						case 'mp2':
						case 'mp1':
						case 'flac':
						case 'mpc':
							echo '<A HREF="demo.write.php?Filename='.urlencode($dirname.$filename).'" TITLE="Edit tags">edit&nbsp;tags</A>';
							break;
						case 'ogg':
							switch (@$fileinfo['audio']['dataformat']) {
								case 'vorbis':
									echo '<A HREF="demo.write.php?Filename='.urlencode($dirname.$filename).'" TITLE="Edit tags">edit&nbsp;tags</A>';
									break;
							}
							break;
						default:
							break;
					}
					echo '</TD>';
					echo '<TD ALIGN="LEFT">&nbsp;<A HREF="'.$_SERVER['PHP_SELF'].'?listdirectory='.urlencode($listdirectory).'&deletefile='.urlencode($dirname.$filename).'" onClick="return confirm(\'Are you sure you want to delete '.addslashes($dirname.$filename).'? \n(this action cannot be un-done)\');" TITLE="Permanently delete '."\n".FixTextFields($filename)."\n".' from'."\n".' '.FixTextFields($dirname).'">delete</A></TD>';
					echo '</TR>';
				}
			}

			if (isset($DirectoryContents[$dirname]['other']) && is_array($DirectoryContents[$dirname]['other'])) {
				uksort($DirectoryContents[$dirname]['other'], 'MoreNaturalSort');
				foreach ($DirectoryContents[$dirname]['other'] as $filename => $fileinfo) {
					echo '<TR BGCOLOR="#'.(($rowcounter++ % 2) ? $getID3checkColor_UnknownDark : $getID3checkColor_UnknownLight).'">';
					echo '<TD><A HREF="'.$_SERVER['PHP_SELF'].'?filename='.urlencode($dirname.$filename).'"><I>'.$filename.'</I></A></TD>';
					echo '<TD ALIGN="RIGHT">&nbsp;'.(isset($fileinfo['filesize']) ? number_format($fileinfo['filesize']) : '-').'</TD>';
					echo '<TD ALIGN="RIGHT">&nbsp;'.NiceDisplayFiletypeFormat($fileinfo).'</TD>';
					echo '<TD ALIGN="RIGHT">&nbsp;'.(isset($fileinfo['playtime_string']) ? $fileinfo['playtime_string'] : '-').'</TD>';
					echo '<TD ALIGN="RIGHT">&nbsp;'.(isset($fileinfo['bitrate']) ? BitrateText($fileinfo['bitrate'] / 1000) : '-').'</TD>';
					echo '<TD ALIGN="LEFT">&nbsp;</TD>'; // Artist
					echo '<TD ALIGN="LEFT">&nbsp;</TD>'; // Title
					echo '<TD ALIGN="LEFT" COLSPAN="3">&nbsp;</TD>'; // MD5_data
					echo '<TD ALIGN="LEFT">&nbsp;</TD>'; // Tags
					echo '<TD ALIGN="LEFT">&nbsp;</TD>'; // Warning/Error
					echo '<TD ALIGN="LEFT">&nbsp;</TD>'; // Edit
					echo '<TD ALIGN="LEFT">&nbsp;<A HREF="'.$_SERVER['PHP_SELF'].'?listdirectory='.urlencode($listdirectory).'&deletefile='.urlencode($dirname.$filename).'" onClick="return confirm(\'Are you sure you want to delete '.addslashes($dirname.$filename).'? \n(this action cannot be un-done)\');" TITLE="Permanently delete '.addslashes($dirname.$filename).'">delete</A></TD>';
					echo '</TR>';
				}
			}

			echo '<TR BGCOLOR="#'.$getID3checkColor_Head.'">';
			echo '<TD><B>Average:</B></TD>';
			echo '<TD ALIGN="RIGHT">'.number_format($TotalScannedFilesize / max($TotalScannedKnownFiles, 1)).'</TD>';
			echo '<TD>&nbsp;</TD>';
			echo '<TD ALIGN="RIGHT">'.getid3_lib::PlaytimeString($TotalScannedPlaytime / max($TotalScannedPlaytimeFiles, 1)).'</TD>';
			echo '<TD ALIGN="RIGHT">'.BitrateText(round(($TotalScannedBitrate / 1000) / max($TotalScannedBitrateFiles, 1))).'</TD>';
			echo '<TD ROWSPAN="2" COLSPAN="'.($columnsintable - 5).'"><TABLE BORDER="0" CELLSPACING="0" CELLPADDING="2"><TR><TH ALIGN="RIGHT">Identified Files:</TH><TD ALIGN="RIGHT">'.number_format($TotalScannedKnownFiles).'</TD><TD>&nbsp;&nbsp;&nbsp;</TD><TH ALIGN="RIGHT">Errors:</TH><TD ALIGN="RIGHT">'.number_format($FilesWithErrors).'</TD></TR><TR><TH ALIGN="RIGHT">Unknown Files:</TH><TD ALIGN="RIGHT">'.number_format($TotalScannedUnknownFiles).'</TD><TD>&nbsp;&nbsp;&nbsp;</TD><TH ALIGN="RIGHT">Warnings:</TH><TD ALIGN="RIGHT">'.number_format($FilesWithWarnings).'</TD></TR></TABLE>';
			echo '</TR>';
			echo '<TR BGCOLOR="#'.$getID3checkColor_Head.'">';
			echo '<TD><B>Total:</B></TD>';
			echo '<TD ALIGN="RIGHT">'.number_format($TotalScannedFilesize).'</TD>';
			echo '<TD>&nbsp;</TD>';
			echo '<TD ALIGN="RIGHT">'.getid3_lib::PlaytimeString($TotalScannedPlaytime).'</TD>';
			echo '<TD>&nbsp;</TD>';
			echo '</TR>';
		}
		echo '</TABLE>';
	} else {
		echo '<B>ERROR: Could not open directory: <U>'.$currentfulldir.'</U></B><BR>';
	}
}
echo PoweredBygetID3();
echo '</BODY></HTML>';
ob_end_flush();








function RemoveAccents($string) {
	// return strtr($string, 'ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ', 'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');
	// Revised version by markstewardØhotmail*com
	return strtr(strtr($string, 'ŠŽšžŸÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝàáâãäåçèéêëìíîïñòóôõöøùúûüýÿ', 'SZszYAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy'), array('Þ' => 'TH', 'þ' => 'th', 'Ð' => 'DH', 'ð' => 'dh', 'ß' => 'ss', 'Œ' => 'OE', 'œ' => 'oe', 'Æ' => 'AE', 'æ' => 'ae', 'µ' => 'u'));
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

function BitrateText($bitrate, $decimals=0, $vbr=false) {
	return '<SPAN STYLE="color: #'.BitrateColor($bitrate).($vbr ? '; font-weight: bold;' : '').'">'.number_format($bitrate, $decimals).' kbps</SPAN>';
}

function FixTextFields($text) {
	$text = getid3_lib::SafeStripSlashes($text);
	$text = htmlentities($text, ENT_QUOTES);
	return $text;
}


function string_var_dump($variable) {
	ob_start();
	var_dump($variable);
	$dumpedvariable = ob_get_contents();
	ob_end_clean();
	return $dumpedvariable;
}


function table_var_dump($variable) {
	$returnstring = '';
	switch (gettype($variable)) {
		case 'array':
			$returnstring .= '<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="2">';
			foreach ($variable as $key => $value) {
				$returnstring .= '<TR><TD VALIGN="TOP"><B>'.str_replace("\x00", ' ', $key).'</B></TD>';
				$returnstring .= '<TD VALIGN="TOP">'.gettype($value);
				if (is_array($value)) {
					$returnstring .= '&nbsp;('.count($value).')';
				} elseif (is_string($value)) {
					$returnstring .= '&nbsp;('.strlen($value).')';
				}
				if (($key == 'data') && isset($variable['image_mime']) && isset($variable['dataoffset'])) {
					$imagechunkcheck = getid3_lib::GetDataImageSize($value);
					$DumpedImageSRC = (!empty($_REQUEST['filename']) ? $_REQUEST['filename'] : '.getid3').'.'.$variable['dataoffset'].'.'.getid3_lib::ImageTypesLookup($imagechunkcheck[2]);
					if ($tempimagefile = fopen($DumpedImageSRC, 'wb')) {
						fwrite($tempimagefile, $value);
						fclose($tempimagefile);
					}
					$returnstring .= '</TD><TD><IMG SRC="'.$DumpedImageSRC.'" WIDTH="'.$imagechunkcheck[0].'" HEIGHT="'.$imagechunkcheck[1].'"></TD></TR>';
				} else {
					$returnstring .= '</TD><TD>'.table_var_dump($value).'</TD></TR>';
				}
			}
			$returnstring .= '</TABLE>';
			break;

		case 'boolean':
			$returnstring .= ($variable ? 'TRUE' : 'FALSE');
			break;

		case 'integer':
		case 'double':
		case 'float':
			$returnstring .= $variable;
			break;

		case 'object':
		case 'null':
			$returnstring .= string_var_dump($variable);
			break;

		case 'string':
			$variable = str_replace("\x00", ' ', $variable);
			$varlen = strlen($variable);
			for ($i = 0; $i < $varlen; $i++) {
				if (ereg('['."\x0A\x0D".' -;0-9A-Za-z]', $variable{$i})) {
					$returnstring .= $variable{$i};
				} else {
					$returnstring .= '&#'.str_pad(ord($variable{$i}), 3, '0', STR_PAD_LEFT).';';
				}
			}
			$returnstring = nl2br($returnstring);
			break;

		default:
			$imagechunkcheck = getid3_lib::GetDataImageSize($variable);
			if (($imagechunkcheck[2] >= 1) && ($imagechunkcheck[2] <= 3)) {
				$returnstring .= '<TABLE BORDER="1" CELLSPACING="0" CELLPADDING="2">';
				$returnstring .= '<TR><TD><B>type</B></TD><TD>'.getid3_lib::ImageTypesLookup($imagechunkcheck[2]).'</TD></TR>';
				$returnstring .= '<TR><TD><B>width</B></TD><TD>'.number_format($imagechunkcheck[0]).' px</TD></TR>';
				$returnstring .= '<TR><TD><B>height</B></TD><TD>'.number_format($imagechunkcheck[1]).' px</TD></TR>';
				$returnstring .= '<TR><TD><B>size</B></TD><TD>'.number_format(strlen($variable)).' bytes</TD></TR></TABLE>';
			} else {
				$returnstring .= nl2br(htmlspecialchars(str_replace("\x00", ' ', $variable)));
			}
			break;
	}
	return $returnstring;
}


function NiceDisplayFiletypeFormat(&$fileinfo) {

	if (empty($fileinfo['fileformat'])) {
		return '-';
	}

	$output  = $fileinfo['fileformat'];
	if (empty($fileinfo['video']['dataformat']) && empty($fileinfo['audio']['dataformat'])) {
		return $output;  // 'gif'
	}
	if (empty($fileinfo['video']['dataformat']) && !empty($fileinfo['audio']['dataformat'])) {
		if ($fileinfo['fileformat'] == $fileinfo['audio']['dataformat']) {
			return $output; // 'mp3'
		}
		$output .= '.'.$fileinfo['audio']['dataformat']; // 'ogg.flac'
		return $output;
	}
	if (!empty($fileinfo['video']['dataformat']) && empty($fileinfo['audio']['dataformat'])) {
		if ($fileinfo['fileformat'] == $fileinfo['video']['dataformat']) {
			return $output; // 'mpeg'
		}
		$output .= '.'.$fileinfo['video']['dataformat']; // 'riff.avi'
		return $output;
	}
	if ($fileinfo['video']['dataformat'] == $fileinfo['audio']['dataformat']) {
		if ($fileinfo['fileformat'] == $fileinfo['video']['dataformat']) {
			return $output; // 'real'
		}
		$output .= '.'.$fileinfo['video']['dataformat']; // any examples?
		return $output;
	}
	$output .= '.'.$fileinfo['video']['dataformat'];
	$output .= '.'.$fileinfo['audio']['dataformat']; // asf.wmv.wma
	return $output;

}

/* not needed Allan Hansen
function ListOfAssumeFormatExtensions() {
	// These values should almost never get used - the only use for them
	// is to possibly help getID3() correctly identify a file that has
	// garbage data at the beginning of the file, but a correct filename
	// extension.

	//$AssumeFormatExtensions[<filename extension>]  = <file format>;

	$AssumeFormatExtensions['aac']  = 'aac';
	$AssumeFormatExtensions['iff']  = 'aiff';
	$AssumeFormatExtensions['aif']  = 'aiff';
	$AssumeFormatExtensions['aifc'] = 'aiff';
	$AssumeFormatExtensions['iff']  = 'aiff';
	$AssumeFormatExtensions['aiff'] = 'aiff';
	$AssumeFormatExtensions['wmv']  = 'asf';
	$AssumeFormatExtensions['wma']  = 'asf';
	$AssumeFormatExtensions['asf']  = 'asf';
	$AssumeFormatExtensions['au']   = 'au';
	$AssumeFormatExtensions['bmp']  = 'bmp';
	$AssumeFormatExtensions['mod']  = 'bonk';
	$AssumeFormatExtensions['bonk'] = 'bonk';
	$AssumeFormatExtensions['flac'] = 'flac';
	$AssumeFormatExtensions['gif']  = 'gif';
	$AssumeFormatExtensions['iso']  = 'iso';
	$AssumeFormatExtensions['jpeg'] = 'jpg';
	$AssumeFormatExtensions['jpg']  = 'jpg';
	$AssumeFormatExtensions['la']   = 'la';
	$AssumeFormatExtensions['pac']  = 'lpac';
	$AssumeFormatExtensions['mac']  = 'mac';
	$AssumeFormatExtensions['ape']  = 'mac';
	$AssumeFormatExtensions['mid']  = 'midi';
	$AssumeFormatExtensions['midi'] = 'midi';
	$AssumeFormatExtensions['mid']  = 'midi';
	$AssumeFormatExtensions['xm']   = 'mod';
	$AssumeFormatExtensions['it']   = 'mod';
	$AssumeFormatExtensions['s3m']  = 'mod';
	$AssumeFormatExtensions['mp3']  = 'mp3';
	$AssumeFormatExtensions['mp2']  = 'mp3';
	$AssumeFormatExtensions['mp1']  = 'mp3';
	$AssumeFormatExtensions['mpc']  = 'mpc';
	$AssumeFormatExtensions['mpg']  = 'mpeg';
	$AssumeFormatExtensions['mpeg'] = 'mpeg';
	$AssumeFormatExtensions['nsv']  = 'nsv';
	$AssumeFormatExtensions['ofr']  = 'ofr';
	$AssumeFormatExtensions['spx']  = 'ogg';
	$AssumeFormatExtensions['ogg']  = 'ogg';
	$AssumeFormatExtensions['png']  = 'png';
	$AssumeFormatExtensions['mov']  = 'quicktime';
	$AssumeFormatExtensions['qt']   = 'quicktime';
	$AssumeFormatExtensions['rar']  = 'rar';
	$AssumeFormatExtensions['ra']   = 'real';
	$AssumeFormatExtensions['ram']  = 'real';
	$AssumeFormatExtensions['rm']   = 'real';
	$AssumeFormatExtensions['wav']  = 'riff';
	$AssumeFormatExtensions['wv']   = 'riff';
	$AssumeFormatExtensions['vox']  = 'riff';
	$AssumeFormatExtensions['cda']  = 'riff';
	$AssumeFormatExtensions['xvid'] = 'riff';
	$AssumeFormatExtensions['avi']  = 'riff';
	$AssumeFormatExtensions['divx'] = 'riff';
	$AssumeFormatExtensions['avi']  = 'riff';
	$AssumeFormatExtensions['wav']  = 'riff';
	$AssumeFormatExtensions['rka']  = 'rkau';
	$AssumeFormatExtensions['swf']  = 'swf';
	$AssumeFormatExtensions['sz']   = 'szip';
	$AssumeFormatExtensions['voc']  = 'voc';
	$AssumeFormatExtensions['vqf']  = 'vqf';
	$AssumeFormatExtensions['zip']  = 'zip';

	return $AssumeFormatExtensions;
}
*/


function MoreNaturalSort($ar1, $ar2) {
	if ($ar1 === $ar2) {
		return 0;
	}
	$len1     = strlen($ar1);
	$len2     = strlen($ar2);
	$shortest = min($len1, $len2);
	if (substr($ar1, 0, $shortest) === substr($ar2, 0, $shortest)) {
		// the shorter argument is the beginning of the longer one, like "str" and "string"
		if ($len1 < $len2) {
			return -1;
		} elseif ($len1 > $len2) {
			return 1;
		}
		return 0;
	}
	$ar1 = RemoveAccents(strtolower(trim($ar1)));
	$ar2 = RemoveAccents(strtolower(trim($ar2)));
	$translatearray = array('\''=>'', '"'=>'', '_'=>' ', '('=>'', ')'=>'', '-'=>' ', '  '=>' ', '.'=>'', ','=>'');
	foreach ($translatearray as $key => $val) {
		$ar1 = str_replace($key, $val, $ar1);
		$ar2 = str_replace($key, $val, $ar2);
	}

	if ($ar1 < $ar2) {
		return -1;
	} elseif ($ar1 > $ar2) {
		return 1;
	}
	return 0;
}

function PoweredBygetID3($string='<BR><HR NOSHADE><DIV STYLE="font-size: 8pt; font-face: sans-serif;">Powered by <A HREF="http://getid3.sourceforge.net" TARGET="_blank"><B>getID3() v<!--GETID3VER--></B><BR>http://getid3.sourceforge.net</A></DIV>') {
	return str_replace('<!--GETID3VER-->', GETID3_VERSION, $string);
}

?>
</BODY>
</HTML>
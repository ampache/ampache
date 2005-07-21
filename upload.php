<?php
/*

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
/*

 Copyright (c) 2003 Lamar
 All rights reserved.

 **Revised by Vollmerk**

 upload.php script.
 saves all uploaded files to the temp/ directory
 then processes the files and moves them to the
 proper directory.

 There are two basic modes of operation.  HTML
 mode and GUI mode.  If GUI mode is enabled a response
 with header 200 will be sent to the GUI. 

*/

require_once ("modules/init.php");



// Set page header
show_template('header');
show_menu_items('Upload');

// Access Control
if(!$user->prefs['upload'] || conf('demo_mode'))  {
	access_denied();
}

/* Action Settings */
$action = scrub_in($_REQUEST['action']);


/* 
	FILE UPLOAD SECTION 
	This section handles file uploads.  File types should
	be declared in  the $types hash.  This will provide
	an easy lookup mechanism.
*/
$types = array( 
		'mp3'=>'music',
		'MP3'=>'music',
		'ogg'=>'music',
		'OGG'=>'music',
		'WMA'=>'music',
		'FLAC'=>'music',
		'flac'=>'music',
		'm4a' =>'music',
		'aac' =>'music',
		'.gz'=>'compressed',
		'tar'=>'compressed',
		'zip'=>'compressed',
		'ZIP'=>'compressed',
	);

/*  Upload Section Which Processes All Files Sent As Post  */
$audio_info = new Audioinfo();

switch ($action) { 
	case 'upload_now':
		// Verify the needed settings are in place
		if (!@chdir($user->prefs['upload_dir']) || strlen($user->prefs['upload_dir']) < 1) { 
			break;
		}

		//FIXME: Set which catalog it goes into somewhere....
		$sql = "SELECT * FROM catalog LIMIT 1";
		$db_results = mysql_query($sql, dbh()); 

		$results = mysql_fetch_object($db_results);

		$catalog = new Catalog($results->id);

		// Create arrays
		$filelist = array();
		
		foreach($_FILES as $tagname=>$file){
			/* Skip blank file names  */
	
			if( strlen($file['name'] ) ){
		
			// Determine tempfile name
			$tempfile = $file['tmp_name'];

			// Determine real file name
			$realname = $user->prefs['upload_dir'] . "/" .  $file['name'];
		
			/* Determine Extension */
			$ext = substr( $file['name'], -3 );

			/* Prevent Unauthorized file types   */
			if(  $types[$ext] == 'compressed' ){
	                        // This section is currently disabled
			}
			elseif( $types[$ext] == 'music' ){
				$error = @move_uploaded_file($tempfile, $realname );
				if( $error )
				{
					$filelist = array( $realname => $file['name'] );
				}
				else{
					switch ($file['error']) {
						case '1':
							$error_text = _("The uploaded file exceeds the upload_max_filesize directive in php.ini");
							break;
						case '2':
							$error_text = _("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.");
							break;
						case '3':
							$error_text = _("The uploaded file was only partially uploaded.");
							break;
						case '4':
							$error_text = _("No file was uploaded.");
							break;
						default:
							$error_text = _("An Unknown Error has occured.");
							break;
					} // end switch 
					if (conf('debug')) { 
						log_event($_SESSION['userdata']['username'],'upload',$error_text);
					}
					$message[$file['name']] .= "Error:  $error_text";	
					$errorenum[$file['name']]=true;
				}
			} // end if known
			// If unknown filetype
			else{
				$message[$file['name']] .= "Error:  Unsupported File Type- $ext<br />";
				$errorenum[$file['name']]=true;
			}
			// foreach through files uploaded
			foreach( $filelist as $fullpath => $music ) {
				
				// If we are quarantining the file
				if ($user->prefs['quarantine']) { 
					// Log the upload but don't do anything
					$message[$music] .= _("Successfully-Quarantined");
					/* Log the upload */
					$sql = "INSERT INTO upload (`user`,`file`,`addition_time`)" .
						" VALUES ('$user->username','" . sql_escape($fullpath) . "','" . time() . "')";
					$db_results = mysql_query($sql, dbh());
				} // if quarantine

				// Go ahead and insert the file
				else {
					$catalog->insert_local_song($fullpath,filesize($fullpath));
					$message[$music] .= _("Successfully-Cataloged");
				  } // end foreach
			flush();
			}
		}
		
		} // end foreach
		
		flush();
		/* Display Upload results  */
		if( $message ){
			print( "<table align='center'>\n" );
			print( "<th>Filename</th><th>Result</th>\n" );
		
			foreach ( $message as $key => $value ){
				if( $errorenum[$key] ){
					$color="color='red'";
				}
				else{
					$color="color='green'";
				}
				print( "<tr>\n");
				print(	"<td><font $color>$key</font></td><td><font $color>$value</font></td>\n");
				print( "</tr>\n");
			}
			print( "</table>\n" );
		}
		require_once(conf('prefix') . "/templates/show_upload.inc");
		break;
	case 'add':
	case 'delete':
	default:
		require_once(conf('prefix') . "/templates/show_upload.inc");
		break;
} // end of switch on action

echo "\n<br /><br />\n";

/* 
	SHOW QUARANTINE SONGS
	This Section Displays Quarantined Songs
	Always process all files in quarantine directory.
	Make a list (and check it twice)
*/
$songs = array();

if ( $handle = @opendir($user->prefs['upload_dir'] ) ){
	/* Loop Through the directory */
	while( false !== ($file = readdir( $handle ))){

		/* Find extension */
		$ext =  substr( $file, -4 );

		if(( $ext == '.mp3' )||( $ext == '.ogg' )){
			$songs[$file]=$user->prefs['upload_dir'] . "/" . "$file";
		}
	}	
} // end if upload_dir
?>

<table class="tabledata" cellspacing="0" cellpadding="0" align="center">
	<tr class="table-header">
		<td><?php echo _("Action"); ?></td>
		<td><?php echo _("Song"); ?></td>
		<td><?php echo _("Artist"); ?></td>
		<td><?php echo _("Album"); ?></td>
		<td><?php echo _("Genre"); ?></td>
		<td><?php echo _("Time"); ?></td>
		<td><?php echo _("Bitrate"); ?></td>
		<td><?php echo _("Size"); ?></td>
		<td><?php echo _("Filename"); ?></td>
		<td><?php echo _("User"); ?></td>
		<td><?php echo _("Date"); ?></td>
	</tr>

<?
	/* Only populate table if valid songs exist */
	if( sizeof($songs) ) {

		/* Get file info */
		$audio_info = new Audioinfo();
		$order = conf('id3tag_order');

		foreach( $songs as $file=>$song ){

			if( $class == "odd" ){
				$class = "even";
			}
			else{
				$class = "odd";
			}
			
			$sql = "SELECT user,addition_time FROM upload WHERE file = '$song'";				
			$db_result = mysql_query($sql, dbh());
			
			if( $r = mysql_fetch_object($db_result) ){
				$temp_user = new User($r->user);
				$uname = $temp_user->fullname;
			}
			else{
				$uname = _("Unknown");
			}

			/* Get filesize */
			$filesize = @filesize( $song );
			$add_time = date( "r",filemtime( $song ) );

			/* get audio information */
			$results = $audio_info->Info($song);

                	$key = get_tag_type($results);
	
			// Crappy Math time boys and girls!
			//FIXME: Do this right
			$min = floor($results['playing_time']/60);
			$sec = floor($results['playing_time'] - ($min*60));
			$time = $min . ":" . $sec;	

			echo  "		<tr class=\"".$class."\">\n";

			if( $user->access === 'admin' ){
				echo "			<td>\n" .
					"		<a href=\"" . $web_path . "upload.php/?action=add&amp;song=$file\">" . _("Add") . "</a><br />\n" .
					"		<a href=\"" . $web_path . "upload.php/?action=delete&amp;song=$file\">" . _("Delete") . "</a><br />\n" .
					"			</td>\n"; 
			}
			else{
				echo "			<td>" . _("Quarantined") . "</td>\n";
			}
		
			  
			echo  "			<td><a href='" . $web_path . 
				"/play/pupload.php?action=m3u&amp;song=$file&amp;uid=$user->username'>" . 
				$results[$key][title] . "</a></td>\n";


			echo  "			<td>" . $results[$key]['artist'] . "&nbsp</td>\n";
			echo  "			<td>" . $results[$key]['album'] . "&nbsp</td>\n";
			echo  "			<td>" . $results[$key]['genre'] . "</td>\n";
			echo  "			<td>" . $time . " </td>\n";
			echo  "			<td>" . intval($results['avg_bit_rate']/1000) . "-" . $results['bitrate_mode'] . "</td>\n";
			echo  "			<td>" . sprintf("%.2f",($filesize/1048576)) . "</td>\n";
			echo  "			<td>$file </td>\n";
			echo  "			<td>$uname</td>\n";
			echo  "			<td>$add_time </td>\n";
			echo  "		</tr>\n";		
		}
	}


?>
	</table>
<br />
<br />
<br />
<?php show_page_footer ('Upload', '',$user->prefs['display_menu']); ?>
	

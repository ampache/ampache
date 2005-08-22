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
 **Chopped to bits and made into PHP nuggets by RosenSama** 2005

*/

/* FIXME:  Things left to do
	--need to add debug logging
	--Add purge link in catalog admin
	--Why do I need to echo something before the message table to show it?
	--Handle when uploaded file is a compressed arvchive
	--play quar song by admin
	--TEST!
*/

require_once( "modules/init.php" );
// Set page header
show_template('header');
show_menu_items('Upload');
show_clear();

// Access Control
if(!$user->prefs['upload'] || conf('demo_mode'))  {
	access_denied();
}

$action = scrub_in( $_REQUEST['action'] );

switch( $action ) {
	case 'upload':

		/* Break if they don't have rights */
		if (!$user->prefs['upload'] OR !$user->has_access(25)) { 
			break;
		}
	
		/* IF we need to quarantine this */
		if ($user->prefs['quarantine']) { 
			/* Make sure the quarantine dir is writeable */
			if (!check_upload_directory(conf('quarantine_dir'))) { 
				$GLOBALS['error']->add_error('general',"Error: Quarantine Directory isn't writeable");
				if (conf('debug')) { 
					log_event($user->username,' upload ',"Error: Quarantine Directory isn't writeable");
				}
			} // if unwriteable

			$catalog_id = find_upload_catalog(conf('quarantine_dir'));

			/* Make sure that it's not in a catalog dir */
			if ($catalog_id) { 
				$GLOBALS['error']->add_error('general',"Error: Quarantine Directory inside a catalog");
				if (conf('debug')) { 
					log_event($user->username,' upload ',"Error: Quarantine Directory inside a catalog");
				}
			} // if in catalog dir

			foreach ($_FILES as $key => $file) { 
				
				if (strlen($_FILES[$key]['name'])) { 
					/* Check size and extension */
					if (!check_upload_extension($key)) { 
						$GLOBALS['error']->add_error($key,"Error: Invalid Extension");
					}
					if (!check_upload_size($key)) { 
						$GLOBALS['error']->add_error($key,"Error: File to large");
					}

					if (!$GLOBALS['error']->error_state) { 
						$new_filename = upload_file($key,conf('quarantine_dir')); 
						/* Record this upload then we're done */
						if ($new_filename) { insert_quarantine_record($user->username,'quarantine',$new_filename); }
					} // if we havn't had an error

				} // end if there is a file to check 

			} // end foreach files
			
			if ($GLOBALS['error']->error_state) { 
				show_upload(); 
			}
			else { 
				show_confirmation("Upload Quarantined", "Your Upload(s) have been quarantined and will be reviewed for addition","upload.php");
			}

		} // if quarantine
		
		/* Else direct upload time baby! */
		else { 
                        /* Make sure the quarantine dir is writeable */
                        if (!check_upload_directory($user->prefs['upload_dir'])) {
                                $GLOBALS['error']->add_error('general',"Error: Upload Directory isn't writeable");
                                if (conf('debug')) {
                                        log_event($user->username,' upload ',"Error: Upload Directory isn't writeable");
                                }
                        } // if unwriteable

			$catalog_id = find_upload_catalog($user->prefs['upload_dir']);
			$catalog = new Catalog($catalog_id);
			

                        /* Make sure that it's not in a catalog dir */
                        if (!$catalog_id) {
                                $GLOBALS['error']->add_error('general',"Error: Upload Directory not inside a catalog");
                                if (conf('debug')) {
                                        log_event($user->username,' upload ',"Error: Upload Directory not inside a catalog");
                                }
                        } // if in catalog dir


                        foreach ($_FILES as $key => $file) {

                                if (strlen($_FILES[$key]['name'])) {
                                        /* Check size and extension */
                                        if (!check_upload_extension($key)) {
                                                $GLOBALS['error']->add_error($key,"Error: Invalid Extension");
                                        }
                                        if (!check_upload_size($key)) {
                                                $GLOBALS['error']->add_error($key,"Error: File to large");
                                        }

                                        if (!$GLOBALS['error']->error_state) {
 						$new_filename = upload_file($key,$user->prefs['upload_dir']);

						/* We aren't doing the quarantine thing, so just insert it */
						if ($new_filename) { $catalog->insert_local_song($new_filename,filesize($new_filename)); }
                                        } // if we havn't had an error

				} // if there is a file to check

                        } // end foreach files

                        if ($GLOBALS['error']->error_state) {
                                show_upload();
                        }
                        else {
                                show_confirmation("Files Uploaded", "Your Upload(s) have been inserted into Ampache and are now live","upload.php");
                        }

		} // man this is a bad idea, the catch all should be the conservative option... oooh well
		
	break;
	case 'add':
		/* Make sure they have access */
		if($user->has_access(100)) {
			$id = scrub_in($_REQUEST['id']);
			update_quarantine_record($id,'add');
			show_confirmation("Upload Added","The Upload has been scheduled for a catalog add, please run command line script to add file","upload.php");
		} 
		else { 
			access_denied();
		} 
		break;
	case 'delete':
		/* Make sure they got them rights */
		if($user->has_access(100)) {
			$id = scrub_in($_REQUEST['id']);
			update_quarantine_record($id,'delete');
                        show_confirmation("Upload Deleted","The Upload has been scheduled for deletion, please run command line script to permently delete this file","upload.php");
						
		} 
		else { 
			access_denied();
		}
		break;
	case 'ack':
		// everything is ready to bulk ack once we pass multiple ids and put them in $id[]
		if( $user->has_access( 100 ) ) {
			$id[] = scrub_in( $_REQUEST['id'] );
			$status = upload_ack( $id );
		} else {
			access_denied();
		}
		break;

	case 'purge':
		if( $user->has_access( 100 ) ) {
			$status = upload_purge();
		} else {
			access_denied();
		}
		break;
	 	
	default:
		show_upload();
	break;
}	// end switch on $action

// display any messages
if( $status ) {
	print( "<table align='center'>\n" );
	print( "<th>Filename</th><th>Result</th>\n" );
	foreach( $status as $status_row ) {
		$filename = $status_row[0];
		$result = $status_row[1];
		$color = "color='green'";
		if( $status_row[2] ) {
			$color = "color='red'";
		}
		print( "<tr>\n");
		print(	"<td><font $color>$filename</font></td><td><font $color>$result</font></td>\n");
		print( "</tr>\n");
	} // end for each status element
	print( "</table>\n" );
} // end if any messages
 

?>

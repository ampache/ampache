<?php  
/*
 *  nj-jukebox.php - Netjuke MPD-based jukebox.
 *  Copyright (C) 2003  Benjamin Carlisle (bcarlisle@24oz.com)
 *  http://mpd.24oz.com/
 *
 *  This has been modified to work with Ampache (http://www.ampache.org) It was 
 *  initially written for NetJuke (http://netjuke.sourceforge.net/)
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */ 

require_once("modules/init.php");


/* We need to create a MPD object here */

$myMpd = init_mpd();

if (is_object($myMpd)) {
  switch ($_REQUEST['action']) {
    case "add":
      if (!$user->has_access(25)) { break; }
      $song_ids = array();
      $song_ids[0] = $_REQUEST[song_id];
      addToPlaylist( $myMpd, $song_ids );
      break;
    case "rem":
      if (!$user->has_access(25)) { break; }
      if ( is_null($myMpd->PLRemove($_REQUEST[id])) ) echo "ERROR: " .$myMpd->errStr."\n";
      mpd_redirect();
      break;
    case ' > ':
    case "play":
      if (!$user->has_access(25)) { break; }
      if ( is_null($myMpd->Play()) ) echo "ERROR: " .$myMpd->errStr."\n";
      mpd_redirect();
      break;
    case "stop":
    case ' X ':
      if (!$user->has_access(25)) { break; }
      if ( is_null($myMpd->Stop()) ) echo "ERROR: " .$myMpd->errStr."\n";
      mpd_redirect();
      break;
    case ' | | ':
    case ' = ':
    case "pause":
      if (!$user->has_access(25)) { break; }
      if ( is_null($myMpd->Pause()) ) echo "ERROR: " .$myMpd->errStr."\n";
      mpd_redirect();
      break;
    case '|< ':
    case "Prev":
      if (!$user->has_access(25)) { break; }
      if ( is_null($myMpd->Previous()) ) echo "ERROR: " . $myMpd->errStr."\n";
      mpd_redirect();
      break;
    case ' >|';
    case "Next":
      if (!$user->has_access(25)) { break; }
      if ( is_null($myMpd->Next()) ) echo "ERROR: " . $myMpd->errStr."\n";
      mpd_redirect();
      break;
    case "shuffle":
      if (!$user->has_access(25)) { break; }
      if ( is_null($myMpd->PLShuffle()) ) echo "ERROR: " .$myMpd->errStr."\n";
      mpd_redirect();
      break;
    case "clear":
      if (!$user->has_access(25)) { break; }
      if ( is_null($myMpd->PLClear()) ) echo "ERROR: " .$myMpd->errStr."\n";
      mpd_redirect();
      break;
    case "loop":
      if (!$user->has_access(25)) { break; }
      if ($_REQUEST['val'] == "On") { $_REQUEST['val'] = '1'; }
      else { $_REQUEST['val'] = '0'; }
      if ( is_null($myMpd->SetRepeat($_REQUEST['val'])) ) echo "ERROR: " .$myMpd->errStr."\n";
      mpd_redirect();
      break;
    case "random":
      if (!$user->has_access(25)) { break; }
                        if ($_REQUEST['val'] == "On") { $_REQUEST['val'] = '1'; }
                        else { $_REQUEST['val'] = '0'; }
      if ( is_null($myMpd->SetRandom($_REQUEST['val']))) echo "ERROR: " .$myMpd->errStr."\n";
                        mpd_redirect();
                        break;
    case "adjvol":
      if (!$user->has_access(25)) { break; }
      if ( is_null($myMpd->AdjustVolume($_REQUEST[val])) ) echo "ERROR: " .$myMpd->errStr."\n";
      mpd_redirect();
      break;
    case "setvol":
      if (!$user->has_access(25)) { break; }
      if ( is_null($myMpd->SetVolume($_REQUEST[val])) ) echo "ERROR: " .$myMpd->errStr."\n";
      mpd_redirect();
      break;
    case "skipto":
      if (!$user->has_access(25)) { break; }
      if ( is_null($myMpd->SkipTo($_REQUEST[val])) ) echo "ERROR: " .$myMpd->errStr."\n";
      mpd_redirect();
      break;
    case "pladd":
      if (!$user->has_access(25)) { break; }
      $plist = new Playlist( $_REQUEST[pl_id] );
      $song_ids = $plist->get_songs();
      addToPlaylist( $myMpd, $song_ids );
      break;
    case "albadd":
      if (!$user->has_access(25)) { break; }
      $album = new Album( $_REQUEST[alb_id] );
      $song_ids = $album->get_song_ids( );
      addToPlaylist( $myMpd, $song_ids );
      break;
    case "show_control":
      require (conf('prefix') . "/templates/show_mpdplay.inc");
      break;
                case "mute":
                        if (!$user->has_access(25)) { break; }
                        if ( is_null($myMpd->SetVolume(0)) ) echo "ERROR: " .$myMpd->errStr."\n";
      mpd_redirect();
                        break;
                case "condPL":
                        if (!$user->has_access(25)) { break; }
                        $condPL = (conf('condPL')==1 ? 0 : 1);
                        conf(array('condPL' => $condPL),1);
      $pref_id = get_preference_id('condPL');
      $user->update_preference($pref_id,$condPL);
      mpd_redirect();
                        break;
                case "crop":
                        if (!$user->has_access(25)) { break; }
                        $pl = $myMpd->playlist;
                        $cur = $myMpd->current_track_id;
                        foreach ($pl as $id => $entry ) {
                           if ($id != $cur) {
                              { if ( is_null($myMpd->PLRemove($id < $cur ? 0 : 1))) {echo "ERROR: " .$myMpd->errStr."\n"; } }
                           }
                        }
                        mpd_redirect();
                        break;
                case "plact":
                        if (!$user->has_access(25)) { break; }
                        switch ($_REQUEST['todo'])
                        {
                          case "Add to Playlist":
                            if( isset( $_REQUEST['song'] ) ) {
                              $pl = $myMpd->playlist;
                              if( is_null($pl) ) {
                                echo "ERROR: ".$myMpd->errStr."\n";
                              } else {
                                $mpd_dir = conf('mpd_dir') . '/';
                                $pl_ids = scrub_in( $_REQUEST['song'] );
                                foreach( $pl as $id => $entry ) {
                                  // remember in_array( needle, haystack )
                                  if( in_array( $id,  $pl_ids ) ) {
                                    $song_files[] = $mpd_dir . $entry['file'];
                                  }
                                }
                                // basically copied from playlist.php Add to case
                                $pl_id = scrub_in( $_REQUEST['playlist_id'] );
                                $playlist = new Playlist($pl_id);
                                if( $pl_id == 0 ) { // new playlist
                                  $playlist_name = _("New Playlist") . " - " . date("m/j/y, g:i a");
                                  $playlist->create_playlist($playlist_name, $user->username, 'private');
                                  $pl_id = $playlist->id;
                                }
                                foreach( $song_files as $song_file ) {
                                  $song_file = sql_escape( $song_file );
                                  $sql = "SELECT id FROM song WHERE `file` = '$song_file'";
                                  $db_results = mysql_query( $sql, dbh() );
                                  $results = mysql_fetch_object( $db_results );
                                  $song_ids[] = $results->id;
                                }
                                if (isset($song_ids) && is_array($song_ids)) {
                                  $playlist->add_songs($song_ids, true);  // $is_ordered = true
                                }
                              }
                            } 
                          // would be nice to redirect to the playlist page for the new $pl_id, but header( "Location: " ) doesn't seem to do it
                          $page = 'playlist.php?playlist_id=' . $pl_id . '&action=view_list';
                          break;
                           case "Delete":
                                $shrunkby =0;
                                foreach ($_REQUEST['song'] as $id ) {
                                   if ( is_null($myMpd->PLRemove($id-$shrunkby)) ) echo "ERROR: " .$myMpd->errStr."\n";
                                   $shrunkby++;
                                   }
                                break;
                           case "move Next":
                /* This does not work yet */
                                $fromabovenp = 0;
                                $frombelownp = 0;
                                $reversed = array_reverse ($_REQUEST[song]);
                                foreach ($reversed as $id => $entry) {
                                   echo "id=".$id;
                                   if ($id > $myMpd->current_track_id) {
                        echo " fromabovenp=".$fromabovenp." source=".$id+$fromabovenp." dest=".($myMpd->current_track_id+1)."<br>";
                                      if (is_null($myMpd->PLMoveTrack($id+$fromabovenp,$myMpd->current_track_id+1))) echo "ERROR: ".$myMpd->errStr."\n";
                                      $fromabovenp++;
                                      }
                                   elseif ($id < $myMpd->current_track_id) {
                        echo " frombelownp=".$frombelownp." source=".$id." dest=".$myMpd->current_track_id+1-frombelownp."<br>";
                                      if (is_null($myMpd->PLMoveTrack($id,$myMpd->current_track_id+1-frombelownp))) echo "ERROR: ".$myMpd->errStr."\n";
                                      $frombelownp++;
                                      }
                                   }
                                break;
                           default:
                                echo "todo='".$_REQUEST['todo']."'<br>";
                                foreach ($_REQUEST[song] as $id => $entry)
                                   {
                                   echo "id=".$id." entry=".$entry."_REQUEST[song][id]=".$_REQUEST[song][$id]."<br>";
                                   }
                           }
      if( $page ) {
        mpd_redirect( $page );
      } else {
        mpd_redirect();
      }
                        break;
                case "movenext":
                        if (!$user->has_access(25)) { break; }
                        if ($_REQUEST[val] < $myMpd->current_track_id) $offset=-1;
                        if (is_null($myMpd->PLMoveTrack($_REQUEST[val],$myMpd->current_track_id+1+$offset)))
                                echo "ERROR: ".$myMpd->errStr."\n"; 
      mpd_redirect();
                        break;
    default:
      mpd_redirect();
      break;
  } // end switch

  // We're done let's disconnect
  $myMpd->Disconnect();
} // end else
?>

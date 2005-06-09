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
	@function               get_song_files
	@discussion             tmakes array of song ids and returns
	                        array of path to actual files
	@param $song_ids        an array of song ids whose filenames you need
*/
function get_song_files( $song_ids ) {
        global $user;
        $song_files = array();
        foreach( $song_ids as $song_id ) {
                $song = new Song( $song_id );
		/* Don't archive disabled songs */
		if ($song->status != 'disabled') { 
	                $user->update_stats( $song_id );
	                $total_size += sprintf("%.2f",($song->size/1048576));;
	                array_push( $song_files, $song->file );
		} // if song isn't disabled
        }
        return array($song_files,$total_size);
} //get_song_files


/*!
        @function               send_zip
        @discussion             takes array of full paths to songs
                                zips them and sends them
        @param $song_files      array of full paths to songs to zip
                                create w/ call to get_song_files
*/
function send_zip( $name, $song_files ) {
        require_once(conf('prefix') . '/lib/archive.php' );
        $arc = new zip_file( $name . ".zip" );
        $options = array(
                'inmemory'      => 1,   // create archive in memory
                'storepaths'    => 0,   // only store file name, not full path
                'level'         => 0    // no compression
        );
        $arc->set_options( $options );
        $arc->add_files( $song_files );
        $arc->create_archive();
        $arc->download_file();
}
?>

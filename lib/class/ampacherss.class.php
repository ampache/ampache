<?php
/*

 Copyright Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; version 2
 of the License.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. 

*/

/**
 * AmpacheRSS Class
 * This is not currently used by the stable version of ampache, really here for future use and
 * due to the fact it was back-ported from /trunk
 */
class AmpacheRSS {

	private $type; 
	public $data; 

	/**
	 * Constructor
	 * This takes a flagged.id and then pulls in the information for said flag entry
	 */
	public function __construct($type) { 	

		$this->type = self::validate_type($type); 
		
	} // constructor

	/**
	 * get_xml
	 * This returns the xmldocument for the current rss type, it calls a sub function that gathers the data
	 * and then uses the xmlDATA class to build the document
	 */
	public function get_xml() { 

		// Function call name
		$data_function = 'load_' . $this->type; 
		$pub_date_function = 'pubdate_' . $this->type; 

		$data = call_user_func(array('AmpacheRSS',$data_function)); 
		$pub_date = call_user_func(array('AmpacheRSS',$pub_date_function)); 

		xmlData::set_type('rss'); 
		$xml_document = xmlData::rss_feed($data,$this->get_title(),$this->get_description(),$pub_date); 

		return $xml_document; 

	} // get_xml

	/**
	 * get_title
	 * This returns the standardized title for the rss feed based on this->type
	 */
	public function get_title() { 

		$titles = array('now_playing'=>_('Now Playing'),
				'recently_played'=>_('Recently Played'),
				'latest_album'=>_('Newest Albums'),
				'latest_artist'=>_('Newest Artists')); 

		return scrub_out(Config::get('site_title')) . ' - ' . $titles[$this->type]; 

	} // get_title

	/**
	 * get_description
	 * This returns the standardized description for the rss feed based on this->type
	 */
	public function get_description() { 

		//FIXME: For now don't do any kind of translating
		return 'Ampache RSS Feeds'; 

	} // get_description

	/**
	 * validate_type
	 * this returns a valid type for an rss feed, if the specified type is invalid it returns a default value
	 */
	public static function validate_type($type) { 

		$valid_types = array('now_playing','recently_played','latest_album','latest_artist','latest_song',
				'popular_song','popular_album','popular_artist'); 
		
		if (!in_array($type,$valid_types)) { 
			return 'now_playing'; 
		} 

		return $type; 

	} // validate_type

	/**
 	 * get_display
	 * This dumps out some html and an icon for the type of rss that we specify
	 */
	public static function get_display($type='now_playing') { 

		// Default to now playing
		$type = self::validate_type($type); 

		$string = '<a href="' . Config::get('web_path') . '/rss.php?type=' . $type . '">' . get_user_icon('feed',_('RSS Feed')) . '</a>';  

		return $string; 

	} // get_display

	// type specific functions below, these are called semi-dynamically based on the current type //

	/**
	 * load_now_playing
	 * This loads in the now playing information. This is just the raw data with key=>value pairs that could be turned
	 * into an xml document if we so wished
	 */
	public static function load_now_playing() { 

		$data = Stream::get_now_playing(); 

		$results = array(); 

		foreach ($data as $element) { 
			$song = $element['media']; 
			$client = $element['user']; 
			$xml_array = array('title'=>$song->f_title . ' - ' . $song->f_artist . ' - ' . $song->f_album,
					'link'=>$song->link,
					'description'=>$song->title . ' - ' . $song->f_artist_full . ' - ' . $song->f_album_full,
					'comments'=>$client->fullname . ' - ' . $element['agent'],
					'pubDate'=>date("r",$element['expire'])
					); 
			$results[] = $xml_array; 
		} // end foreach 

		return $results; 

	} // load_now_playing

	/**
	 * pubdate_now_playing
	 * this is the pub date we should use for the now playing information, 
	 * this is a little specific as it uses the 'newest' expire we can find
	 */
	public static function pubdate_now_playing() { 

		// Little redundent, should be fixed by an improvement in the get_now_playing stuff
		$data = Stream::get_now_playing(); 

		$element = array_shift($data); 

		return $element['expire']; 

	} // pubdate_now_playing

	/**
	 * load_recently_played
	 * This loads in the recently played information and formats it up real nice like
	 */
	public static function load_recently_played() { 

		//FIXME: The time stuff should be centralized, it's currently in two places, lame

		$time_unit = array('',_('seconds ago'),_('minutes ago'),_('hours ago'),_('days ago'),_('weeks ago'),_('months ago'),_('years ago'));
		$data = Song::get_recently_played(); 

		$results = array(); 

		foreach ($data as $item) { 
			$client = new User($item['user']); 
			$song = new Song($item['object_id']); 
			$song->format(); 
			$amount = intval(time() - $item['date']+2); 
			$time_place = '0'; 
		        while ($amount >= 1) {
		                $final = $amount;
		                $time_place++;
	        	        if ($time_place <= 2) {
	        	                $amount = floor($amount/60);
		                }
		                if ($time_place == '3') {
		                        $amount = floor($amount/24);
		                }
		                if ($time_place == '4') {
		                        $amount = floor($amount/7);
		                }
		                if ($time_place == '5') {
		                        $amount = floor($amount/4);
		                }
		                if ($time_place == '6') {
		                        $amount = floor ($amount/12);
		                }
		                if ($time_place > '6') {
		                        $final = $amount . '+';
		                        break;
				} 
		        } // end while

			$time_string = $final . ' ' . $time_unit[$time_place];

			$xml_array = array('title'=>$song->f_title . ' - ' . $song->f_artist . ' - ' . $song->f_album,
						'link'=>str_replace('&amp;', '&', $song->link),
						'description'=>$song->title . ' - ' . $song->f_artist_full . ' - ' . $song->f_album_full . ' - ' . $time_string,
						'comments'=>$client->username,
						'pubDate'=>date("r",$item['date'])); 
			$results[] = $xml_array; 

		} // end foreach 

		return $results; 

	} // load_recently_played

	/**
	 * pubdate_recently_played
	 * This just returns the 'newest' recently played entry
	 */
	public static function pubdate_recently_played() { 

		$data = Song::get_recently_played(); 

		$element = array_shift($data);
		
		return $element['date']; 

	} // pubdate_recently_played

} // end AmpacheRSS class

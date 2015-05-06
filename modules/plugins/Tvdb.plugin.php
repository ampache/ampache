<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

class AmpacheTvdb {

    public $name           = 'Tvdb';
    public $categories     = 'metadata';
    public $description    = 'Tvdb metadata integration';
    public $url            = 'http://thetvdb.com';
    public $version        = '000001';
    public $min_ampache    = '370009';
    public $max_ampache    = '999999';
    
    // These are internal settings used by this class, run this->load to
    // fill them out
    private $api_key;

    /**
     * Constructor
     * This function does nothing
     */
    public function __construct() {
        return true;
    }

    /**
     * install
     * This is a required plugin function
     */
    public function install() {
        
        if (Preference::exists('tvdb_api_key')) { return false; }

        Preference::insert('tvdb_api_key','Tvdb api key','','75','string','plugins');
        
        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function
     */
    public function uninstall() {
    
        Preference::delete('tvdb_api_key');
        
        return true;
    } // uninstall

    /**
     * load
     * This is a required plugin function; here it populates the prefs we 
     * need for this object.
     */
    public function load($user) {
        
        $user->set_preferences();
        $data = $user->prefs;

        if (strlen(trim($data['tvdb_api_key']))) {
            $this->api_key = trim($data['tvdb_api_key']);
        }
        else {
            debug_event($this->name,'No Tvdb api key, metadata plugin skipped','3');
            return false;
        }
        
        return true;
    } // load

    /**
     * get_metadata
     * Returns song metadata for what we're passed in.
     */
    public function get_metadata($gather_types, $media_info) {
        debug_event('tvdb', 'Getting metadata from Tvdb...', '5');

        // TVShow metadata only
        if (!in_array('tvshow', $gather_types)) {
            debug_event('tvdb', 'Not a valid media type, skipped.', '5');
            return null;
        }
        
        try {
            $results = array();
            $release = array();
            $tvdburl = 'http://thetvdb.com';
            $client = new Moinax\TvDb\Client($tvdburl, $this->api_key);
           $show_info = $this->parseFileName($media_info['file']);
           $results['tvshow'] = trim($show_info[0]);
    	   $results['tvshow_season'] = $show_info[1];
    	   $results['tvshow_episode'] = $show_info[2];
    	   $results['year'] = $show_info[3];
           if ($results['tvshow']){
                $releases = $client->getSeries($results['tvshow']);
                $release = $this->getReleaseByTitle($releases, $results['tvshow'], $results['year']);
                    $results['tvdb_tvshow_id'] = $release->id;
                    $results['tvshow_imdb_id'] = $release->imdbid;
                    $results['overview'] = $release->overview;
                    if ($release->firstAired) {
                        $results['tvshow_year'] = $release->firstAired->format('Y');
                    }
                    if ($release->banner) {
                        $results['tvshow_banner_art'] = $tvdburl . '/banners/' . $release->banner;
                    }
                    
                    $baseSeries = $client->getSerie($results['tvdb_tvshow_id']);
                    
                    if (isset($baseSeries->contentRating)) {
                    	$results['content_rating'] = $baseSeries->contentRating;
                    }
                    else {
                        $results['content_rating'] = "NR";
                    }
                                       
                    if (count($baseSeries->genres) > 0) {
                        $results['genre'] = $baseSeries->genres;
                    }
                    
                    $banners = $client->getBanners($results['tvdb_tvshow_id']);
                    foreach ($banners as $banner) {
                        if ($banner->language == "en") {
                                    if (!$results['tvshow_art']) {
                                if ($banner->type == "poster") {
                                    $results['tvshow_art'] = $tvdburl . '/banners/' . $banner->path;
                                }
                            }
                            
                            if ($results['tvshow_season'] && !$results['tvshow_season_art']) {
                                if ($banner->type == "season" && $banner->season == $results['tvshow_season']) {
                                    $results['tvshow_season_art'] = $tvdburl . '/banners/' . $banner->path;
                                }
                            }
                        }
                    }
                    
                    if ($results['tvshow_season'] && $results['tvshow_episode']) {
                        $release = $client->getEpisode($results['tvdb_tvshow_id'], $results['tvshow_season'], $results['tvshow_episode']);
                        if ($release->id) {
                            $results['tvdb_id'] = $release->id;
                            $results['tvshow_season'] = $release->season;
                            $results['tvshow_episode'] = $release->number;
                            $results['original_name'] = $release->name;
                            $results['imdb_id'] = $release->imdbId ;
                            if ($release->firstAired) {
                                $results['release_date'] = $release->firstAired->getTimestamp();
                                $results['year'] = $release->firstAired->format('Y');;
                            }
                            $results['description'] = $release->overview;
                            if ($release->thumbnail) {
                                $results['art'] = $tvdburl . '/banners/' . $release->thumbnail;
                            }
                        }
                    }
                }
       } catch (Exception $e) {
            debug_event('tvdb', 'Error getting metadata: ' . $e->getMessage(), '1');
        }
        
        return $results;
    } // get_metadata
    
    private function getReleaseByTitle($results, $title, $year)
    {
         $titles = array();   
         foreach ($results as $index)
        {
            $pos = strpos($index->name, $title);
                if ($pos !== false) {
                    $titles[] = $index;
                }
        }
 
        if ((count($titles) > 1) && ($year != null)) {
            foreach ($titles as $index)
            {
                $y = $index->firstAired->format('Y');
                if ($year == $y) {
                    return $index;
                }
            }
        }
        return count($titles) > 0 ? $titles[0] : $results[0];
    }
        
    public function gather_arts($type, $options = array(), $limit = 5)
    {
        debug_event('Tvdb', 'gather_arts for type `' . $type . '`', 5);
        return Art::gather_metadata_plugin($this, $type, $options);
    }
    
   /**
    * 
    * @param string $path
    * @return multitype:string array
    * 
    * parses TV show name variations:
    * 1. title[date].S#[#]E#[#].ext		(Upper/lower case)	
    * 2. title[date].#[#]X#[#].ext		(both upper/lower case letters
    * 3. title[date].Season #[#] Episode #[#].ext
    * 4. title[date].###.ext		(maximum of 9 seasons)
    *  parse directory  path for name, season and episode numbers
    *     /show name/season #/## episode name.ext
    */
    private function parseFileName($path)
    {
        $file = pathinfo($path,PATHINFO_FILENAME);
             
            if (preg_match("~[Ss](\d+)[Ee](\d+)~", $file, $seasonEpisode)) {
                $temp = preg_split("~([1|2][0-9]{3})?(((\.|_|\s)[Ss]\d+(\.|_)*[Ee]\d+)|((\.|_|\s)\d+[x|X]\d+))~",$file,2);
                preg_match("~[sS](\d+)[eE](\d+)~",$seasonEpisode[0],$tmp);
            }
            else {
  	             if (preg_match("~[\.\s](\d)[xX](\d{2})[\.\s]~", $file, $seasonEpisode)) {
                    $temp = preg_split("~([1|2][0-9]{3})?[\._\s]\d[xX]\d{2}[\.\s]~",$file,2);
                    preg_match("~[\.\s](\d)[xX](\d+)[\.\s]~",$seasonEpisode[0],$tmp);
                 }
                else {
                    if (preg_match("~[S|s]eason[-\.\s](\d+)[\.-\s\,]?\ ?[e|E]pisode[\ -\.\s](\d+)[\.\s-]?~", $file, $seasonEpisode)) {
                        $temp = preg_split("~[\s\.-]?([1|2][0-9]{3})?[\.\s-][S|s]eason[\s-\.\,](\d+)[\.\s-,]?\s?[e|E]pisode[\s-\.](\d+)~",$file,2);
                        $tmp = $seasonEpisode;
                    }
                    else {
           	       if (preg_match("~\.(\d)(\d\d)[\.\z]~", $file, $seasonEpisode)) {
                            $temp = preg_split("~([1|2][0-9]{3})?\.(\d){3}[\.\z]~",$file,3);
                            preg_match("~\.(\d)(\d\d)\.?~",$seasonEpisode[0],$tmp);
                   }
                     else {
                        	if (strpos($path, '/') !== false) {
                             	$slash_type = '~/~';
							} else {
								$slash_type = "~\\\\~";
							}
                                $matches = preg_split($slash_type,$path, -1,PREG_SPLIT_DELIM_CAPTURE);
								$rmatches = array_reverse($matches);
								$episode = preg_split('~^(\d{1,2})~',$rmatches[0], 0,  PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
								$episode[0] = ltrim($episode[0], "0");
								preg_match('~\d{1,2}\z~', $rmatches[1], $season);
								$season[0] = ltrim($season[0], "0");
								$title = ucwords($rmatches[2]);
        						return [$title, $season[0], $episode[0], null];                          
                      }
	               }
               }
        }
        preg_match("~[1|2][0-9]{3}~", $file, $tyear);
        $year = isset($tyear[0]) ? $tyear[0] : null;
        $seasonEpisode = array_reverse($tmp);
        $episode = ltrim($seasonEpisode[0],"0");
        $season = ltrim($seasonEpisode[1],"0");
        $title = str_replace(['.','_'], ' ',trim($temp[0], " \t\n\r\0\x0B\.\_"));
        return [ucwords($title), $season, $episode, $year];
    }
} // end AmpacheTvdb
?>

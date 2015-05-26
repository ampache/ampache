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

class AmpacheTmdb {

    public $name           = 'Tmdb';
    public $categories     = 'metadata';
    public $description    = 'Tmdb metadata integration';
    public $url            = 'https://www.themoviedb.org';
    public $version        = '000002';
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
        
        if (Preference::exists('tmdb_api_key')) { return false; }

        Preference::insert('tmdb_api_key','Tmdb api key','','75','string','plugins');
        
        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function
     */
    public function uninstall() {
    
        Preference::delete('tmdb_api_key');
        
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

        if (strlen(trim($data['tmdb_api_key']))) {
            $this->api_key = trim($data['tmdb_api_key']);
        }
        else {
            debug_event($this->name,'No Tmdb api key, metadata plugin skipped','3');
            return false;
        }
        
        return true;
    } // load

    /**
     * get_metadata
     * Returns song metadata for what we're passed in.
     */
    public function get_metadata($gather_types, $media_info) {
        debug_event('tmdb', 'Getting metadata from Tmdb...', '5');

        // TVShow / Movie metadata only
        if (!in_array('tvshow', $gather_types) && !in_array('movie', $gather_types)) {
            debug_event('tmdb', 'Not a valid media type, skipped.', '5');
            return null;
        }
        
        try {
            $token = new \Tmdb\ApiToken($this->api_key);
            $client = new \Tmdb\Client($token);
            $configRepository = new \Tmdb\Repository\ConfigurationRepository($client);
            $config = $configRepository->load();
            $imageHelper = new \Tmdb\Helper\ImageHelper($config);
            $results = array();
            $release_info = $this->parseFileName($media_info['file'], $gather_types);
            if (empty($release_info[0])) {
                debug_event('tmdb', 'Could not parse title, skipped.', '5');
                return null;
            }
            if (in_array('tvshow', $gather_types)) {
               $results['tvshow'] = trim($release_info[0]);
               $results['tvshow_season'] = $release_info[1];
               $results['tvshow_episode'] = $release_info[2];
               $results['year'] = $release_info[3];
            }
            else {
                $results['title'] = $release_info[0];
                $results['year'] = $release_info[1];
           }
           if (in_array('movie', $gather_types)) {
                $apires = $client->getSearchApi()->searchMovies($results['title']);
           }
           else {
                $apires = $client->getSearchApi()->searchTv($results['tvshow']);
            }
            $result = $apires['results'][0];
            $title = in_array('movie', $gather_types) ? $results['title'] : $results['tvshow'];
            $result = $this->getResultByTitle($apires['results'], $title, $gather_types, $results['year']);
            if (in_array('movie', $gather_types)) {
                $results['tmdb_id'] = $result['id'];
                $repository = new \Tmdb\Repository\MovieRepository($client);
                $movie = $repository->load($results['tmdb_id']);
                $results['original_name'] = $movie->getOriginalTitle();
                if ($datetime = $movie->getReleaseDate()) {
                    $results['release_date'] = $datetime->getTimestamp();
                    $results['year'] = date_format($datetime, 'Y');
                }
                if ($movie->getPosterPath()) {
                    $results['art'] = $imageHelper->getUrl($movie->getPosterPath());
                }
                if ($movie->getBackdropPath()) {
                    $results['background_art'] = $imageHelper->getUrl($movie->getBackdropPath());
                }
                $results['overview'] = $movie->getOverview();
                $results['genre'] = self::get_genres($movie);
                $results['content_rating'] = $this->get_release($movie, $gather_types);
              }

            if (in_array('tvshow', $gather_types)) {
                $results['tmdb_tvshow_id'] = $result['id'];
                $repository = new \Tmdb\Repository\TvRepository($client);
                $tvshow  = $repository->load($results['tmdb_tvshow_id']);
                $results['tvshow'] = $tvshow->getName();
                if ($tvshow->getFirstAirDate()) {
                    $release_date = $tvshow->getFirstAirDate();
                    $results['year'] = date_format($release_date, 'Y');
                }
                if ($tvshow->getPosterPath()) {
                    $results['tvshow_art'] = $imageHelper->getUrl($tvshow->getPosterPath());
                }
                if ($tvshow->getBackDropPath()) {
                    $results['tvshow_background_art'] = $imageHelper->getUrl($tvshow->getBackdropPath());
                }
                $results['overview'] = $tvshow->getOverview();
                $results['genre'] = self::get_genres($tvshow);
                $results['content_rating'] = $this->get_release($tvshow, $gather_types);

                if ($results['tvshow_season']) {
                    $release = $client->getTvSeasonApi()->getSeason($results['tmdb_tvshow_id'], $results['tvshow_season']);
                    if ($release['id']) {
                         if ($release['poster_path']) {
                            $results['tvshow_season_art'] = $imageHelper->getUrl($release['poster_path']);
                        }
                        $episode = $release['episodes'][$results['tvshow_episode'] - 1];
                        $results['tmdb_id'] = $episode['id]'];
                        $results['episode_title'] = $episode['name'];
                        if ($episode['air_date']) {
                            $results['release_date'] = strtotime($episode['air_date']);
                        }
                        $results['description'] = $episode['overview'];
                        if ($episode['still_path']) {
                            $results['art'] = $imageHelper->getUrl($episode['still_path']);
                        } else {
                            $results['art'] = $imageHelper->getUrl($release['poster_path']);
                        }
                   }
               }
            }
        } catch (Exception $e) {
            debug_event('tmdb', 'Error getting metadata: ' . $e->getMessage(), '1');
        }
        
        return $results;
    } // get_metadata
    
    private function getResultByTitle($results, $title, $gather_type, $year)
    {
         $titles = array();   
        
        foreach ($results as $index)
        {
            if (in_array('movie', $gather_type)) {
                if ((strtoupper($title) == strtoupper($index['title'])) && (strtoupper($index['original_title']) == strtoupper($title))) {
                    $titles[] = $index;
                }
            }
            else {
                if ((strtoupper($title) == strtoupper($index['name'])) && (strtoupper($index['original_name']) == strtoupper($title))) {
                    $titles[] = $index;
                }
            }
        }
        if ((count($titles) > 1) && ($year != null)) {
            foreach ($titles as $index)
            {
                $y = in_array('movie', $gather_type) ? date("Y",strtotime($index['release_date'])) : date("Y",strtotime($index['first_air_date']));
                if ($year == $y) {
                    return $index;
                }
            }
        }
        return count($titles) > 0 ? $titles[0] : $results[0];
    }
    
    public function gather_arts($type, $options = array(), $limit = 5)
    {
        debug_event('Tmdb', 'gather_arts for type `' . $type . '`', 5);
        return Art::gather_metadata_plugin($this, $type, $options);
    }
    
    private function get_release($show, $gather_type)
    {
        $releases = in_array('movie', $gather_type) ? $show->getReleases()  : $show->getContentRatings();
        $data = $releases->toArray();
        $iso31661 = AmpConfig::get('certification_country') ? : "US";
        foreach ($data as $country) {
            if ($iso31661 == $country->getIso31661()) {
                if (in_array('movie', $gather_type)) {
                    $certification = $country->getCertification();
                   return (empty($certification)) ? "NR" : $certification;
                } else {
                    return $country->getRating();
                }
            }
        }
        return "NR";
    }
    
    private static function get_genres($show)
    {
        $genres = array();
        $data = $show->getGenres();
        $Genres = $data->getGenres();
            foreach ($Genres as $genre) {
                if (!$genre->getName() == false) {
                    $genres[] = $genre->getName();
                }
            }
        return $genres;
    }
    
   /**
    *  parses TV show name variations:
    *    1. title[date].S#[#]E#[#].ext		(Upper/lower case)
    *    2. title[date].#[#]X#[#].ext		(both upper/lower case letters
    *    3. title[date].Season #[#] Episode #[#].ext
    *    4. title[date].###.ext		(maximum of 9 seasons)
    *  parse directory  path for name, season and episode numbers
    *     /show name/season #/## episode name.ext
    *  parse movie names:
    *    title.[date].ext
    */
    private function parseFileName($filename, $gather_types)
    {
    	$file = pathinfo($filename,PATHINFO_FILENAME);
        
        if (in_array('tvshow', $gather_types)) {
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
                    if (preg_match("~[S|s]eason[-\.\s](\d+)[\.\-\s\,]?\s?[e|E]pisode[\s-\.\s](\d+)[\.\s-]?~", $file, $seasonEpisode)) {
                        $temp = preg_split("~[\s\.-]?([1|2][0-9]{3})?[\.\s-][S|s]eason[\s-\.\,](\d+)[\.\s-,]?\s?[e|E]pisode[\s-\.](\d+)~",$file,2);
                        $tmp = $seasonEpisode;
                    }
                    else {
            	       if (preg_match("~\.(\d)(\d\d)[\.\z]~", $file, $seasonEpisode)) {
                            $temp = preg_split("~([1|2][0-9]{3})?\.(\d){3}[\.\z]~",$file,3);
                            preg_match("~\.(\d)(\d\d)\.?~",$seasonEpisode[0],$tmp);
                     }
                     else {
                        	if (strpos($filename, '/') !== false) {
                             	$slash_type = '~/~';
							} else {
								$slash_type = "~\\\\~";
						}
                               $matches = preg_split($slash_type,$filename, -1,PREG_SPLIT_DELIM_CAPTURE);
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

    if (in_array('movie', $gather_types)) {
	    $temp = preg_split("~(\.(\(([12][0-9]{3})\)))?(?(1)|\.[12][0-9]{3})~",$file,2);
	    $title = str_replace(['.','_'], ' ',trim($temp[0], " \t\n\r\0\x0B\.\_"));
	    preg_match("~[1|2][0-9]{3}~", $file, $tyear);
	    $year = isset($tyear[0]) ? $tyear[0] : null; 
	    return [ucwords($title), $year];
    }
  }
} // end AmpacheTmdb
?>

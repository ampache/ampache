<?php
declare(strict_types=0);
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

/**
 * Label class
 *
 * This is the class responsible for handling the Label object
 * it is related to the label table in the database.
 */
class Label extends database_object implements library_item
{
    /* Variables from DB */

    /**
     *  @var integer $id
     */
    public $id;
    /**
     *  @var string $name
     */
    public $name;
    /**
     *  @var string $category
     */
    public $category;
    /**
     *  @var string $address
     */
    public $address;
    /**
     *  @var string $email
     */
    public $email;
    /**
     *  @var string $website
     */
    public $website;
    /**
     *  @var string $summary
     */
    public $summary;
    /**
     *  @var integer $user
     */
    public $user;

    /**
     * @var string $f_name
     */
    public $f_name;
    /**
     * @var string $link
     */
    public $link;
    /**
     * @var string $f_link
     */
    public $f_link;
    /**
     * @var integer $artists
     */
    public $artists;

    /**
     * __construct
     * @param $label_id
     */
    public function __construct($label_id)
    {
        $info = $this->get_info($label_id);

        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        return true;
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb, $force = false)
    {
        if (Art::has_db($this->id, 'label') || $force) {
            Art::display('label', $this->id, $this->get_fullname(), $thumb, $this->link);
        }
    }

    /**
     * @param boolean $details
     */
    public function format($details = true)
    {
        unset($details);
        $this->f_name       = scrub_out($this->name);
        $this->link         = AmpConfig::get('web_path') . '/labels.php?action=show&label=' . scrub_out($this->id);
        $this->f_link       = "<a href=\"" . $this->link . "\" title=\"" . $this->f_name . "\">" . $this->f_name;
        $this->artists      = count($this->get_artists());
    }

    /**
     * @return array|integer[]
     */
    public function get_catalogs()
    {
        return array();
    }

    /**
     * @return array
     */
    public function get_childrens()
    {
        $medias  = array();
        $artists = $this->get_artists();
        foreach ($artists as $artist_id) {
            $medias[] = array(
                'object_type' => 'artist',
                'object_id' => $artist_id
            );
        }

        return array('artist' => $medias);
    }

    /**
     * @return string
     */
    public function get_default_art_kind()
    {
        return 'default';
    }

    /**
     * @return string
     */
    public function get_description()
    {
        return $this->summary;
    }

    /**
     * @return string
     */
    public function get_fullname()
    {
        return $this->f_name;
    }

    /**
     * get_keywords
     * @return array
     */
    public function get_keywords()
    {
        $keywords          = array();
        $keywords['label'] = array('important' => true,
            'label' => T_('Label'),
            'value' => $this->f_name);

        return $keywords;
    }

    /**
     * @param string $filter_type
     * @return array|mixed
     */
    public function get_medias($filter_type = null)
    {
        $medias = array();
        if ($filter_type === null || $filter_type == 'song') {
            $songs = $this->get_songs();
            foreach ($songs as $song_id) {
                $medias[] = array(
                    'object_type' => 'song',
                    'object_id' => $song_id
                );
            }
        }

        return $medias;
    }

    /**
     * @return null
     */
    public function get_parent()
    {
        return null;
    }

    /**
     * @return integer
     */
    public function get_user_owner()
    {
        return $this->user;
    }

    /**
     * search_childrens
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        $search                    = array();
        $search['type']            = "artist";
        $search['rule_0_input']    = $name;
        $search['rule_0_operator'] = 4;
        $search['rule_0']          = "title";
        $artists                   = Search::run($search);

        $childrens = array();
        foreach ($artists as $artist_id) {
            $childrens[] = array(
                'object_type' => 'artist',
                'object_id' => $artist_id
            );
        }

        return $childrens;
    }

    /**
     * can_edit
     * @param string $user_id
     * @return boolean
     */
    public function can_edit($user_id = null)
    {
        if (!$user_id) {
            $user_id = Core::get_global('user')->id;
        }

        if (!$user_id) {
            return false;
        }

        if (AmpConfig::get('upload_allow_edit')) {
            if ($this->user !== null && $user_id == $this->user) {
                return true;
            }
        }

        return Access::check('interface', 50, $user_id);
    }

    /**
     * update
     * @param array $data
     * @return integer
     */
    public function update(array $data)
    {
        if (self::lookup($data, $this->id) !== 0) {
            return false;
        }

        $name     = isset($data['name']) ? $data['name'] : $this->name;
        $category = isset($data['category']) ? $data['category'] : $this->category;
        $summary  = isset($data['summary']) ? $data['summary'] : $this->summary;
        $address  = isset($data['address']) ? $data['address'] : $this->address;
        $email    = isset($data['email']) ? $data['email'] : $this->email;
        $website  = isset($data['website']) ? $data['website'] : $this->website;

        $sql = "UPDATE `label` SET `name` = ?, `category` = ?, `summary` = ?, `address` = ?, `email` = ?, `website` = ? WHERE `id` = ?";
        Dba::write($sql, array($name, $category, $summary, $address, $email, $website, $this->id));

        $this->name     = $name;
        $this->category = $category;
        $this->summary  = $summary;
        $this->address  = $address;
        $this->email    = $email;
        $this->website  = $website;

        return $this->id;
    }

    /**
     * helper
     * @param string $name
     * @return string
     */
    public static function helper(string $name)
    {
        $label_data = array(
            'name' => $name,
            'category' => 'tag_generated',
            'summary' => null,
            'address' => null,
            'email' => null,
            'website' => null,
            'user' => 0,
            'creation_date' => time()
        );

        return self::create($label_data);
    }

    /**
     * create
     * @param array $data
     * @return string
     */
    public static function create(array $data)
    {
        if (self::lookup($data) !== 0) {
            return false;
        }

        $name          = $data['name'];
        $category      = $data['category'];
        $summary       = $data['summary'];
        $address       = $data['address'];
        $email         = $data['email'];
        $website       = $data['website'];
        $user          = $data['user'] ?: Core::get_global('user')->id;
        $creation_date = $data['creation_date'] ?: time();

        $sql = "INSERT INTO `label` (`name`, `category`, `summary`, `address`, `email`, `website`, `user`, `creation_date`) " .
               "VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        Dba::write($sql, array($name, $category, $summary, $address, $email, $website, $user, $creation_date));

        return Dba::insert_id();
    }

    /**
     * lookup
     * @param array $data
     * @param integer $label_id
     * @return integer
     */
    public static function lookup(array $data, $label_id = 0)
    {
        $ret  = -1;
        $name = trim((string) $data['name']);
        if (!empty($name)) {
            $ret    = 0;
            $sql    = "SELECT `id` FROM `label` WHERE `name` = ?";
            $params = array($name);
            if ($label_id > 0) {
                $sql .= " AND `id` != ?";
                $params[] = $label_id;
            }
            $db_results = Dba::read($sql, $params);
            if ($row = Dba::fetch_assoc($db_results)) {
                $ret = $row['id'];
            }
        }

        return $ret;
    }

    /**
     * garbage_collection
     * @return mixed|void
     */
    public static function garbage_collection()
    {
        // Don't remove labels, it could still be used as description in a search
    }

    /**
     * get_artists
     * @return integer[]
     */
    public function get_artists()
    {
        $sql        = "SELECT `artist` FROM `label_asso` WHERE `label` = ?";
        $db_results = Dba::read($sql, array($this->id));
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['artist'];
        }

        return $results;
    }

    /**
     * add_artist_assoc
     * @param integer $artist_id
     * @return PDOStatement|boolean
     */
    public function add_artist_assoc($artist_id)
    {
        $sql = "INSERT INTO `label_asso` (`label`, `artist`, `creation_date`) VALUES (?, ?, ?)";

        return Dba::write($sql, array($this->id, $artist_id, time()));
    }

    /**
     * remove_artist_assoc
     * @param integer $artist_id
     * @return PDOStatement|boolean
     */
    public function remove_artist_assoc($artist_id)
    {
        $sql = "DELETE FROM `label_asso` WHERE `label` = ? AND `artist` = ?";

        return Dba::write($sql, array($this->id, $artist_id));
    }

    /**
     * get_songs
     * gets the songs for this label, based on label name
     * @return integer[]
     */
    public function get_songs()
    {
        $sql = "SELECT `song`.`id` FROM `song` " .
               "LEFT JOIN `song_data` ON `song_data`.`song_id` = `song`.`id` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
        }
        $sql .= "WHERE `song_data`.`label` = ? ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "AND `catalog`.`enabled` = '1' ";
        }
        $sql .= "ORDER BY `song`.`album`, `song`.`track`";
        $db_results = Dba::read($sql, array($this->name));

        $results = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    } // get_songs

    /**
     * remove
     * @return PDOStatement|boolean
     */
    public function remove()
    {
        $sql     = "DELETE FROM `label` WHERE `id` = ?";
        $deleted = Dba::write($sql, array($this->id));
        if ($deleted) {
            Art::garbage_collection('label', $this->id);
            Userflag::garbage_collection('label', $this->id);
            Rating::garbage_collection('label', $this->id);
            Shoutbox::garbage_collection('label', $this->id);
            Useractivity::garbage_collection('label', $this->id);
        }

        return $deleted;
    }

    /**
     * get_all_labels
     * @return array
     */
    public static function get_all_labels()
    {
        $sql        = "SELECT `id`, `name` FROM `label`";
        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['id']] = $row['name'];
        }

        return $results;
    }

    /**
     * @param integer $artist_id
     * @return array
     */
    public static function get_labels($artist_id)
    {
        $sql = "SELECT `label`.`id`, `label`.`name` FROM `label` " .
               "LEFT JOIN `label_asso` ON `label_asso`.`label` = `label`.`id` " .
               "WHERE `label_asso`.`artist` = ?";
        $db_results = Dba::read($sql, array($artist_id));
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['id']] = $row['name'];
        }

        return $results;
    }

    /**
     * get_display
     * This returns a csv formated version of the labels that we are given
     * @param $labels
     * @param boolean $link
     * @return string
     */
    public static function get_display($labels, $link = false)
    {
        if (!is_array($labels)) {
            return '';
        }

        $results = '';

        // Iterate through the labels, format them according to type and element id
        foreach ($labels as $label_id => $value) {
            if ($link) {
                $results .= '<a href="' . AmpConfig::get('web_path') . '/labels.php?action=show&label=' . $label_id . '" title="' . $value . '">';
            }
            $results .= $value;
            if ($link) {
                $results .= '</a>';
            }
            $results .= ', ';
        }

        $results = rtrim((string) $results, ', ');

        return $results;
    } // get_display

    /**
     * update_label_list
     * Update the labels list based on commated list (ex. label1,label2,label3,..)
     * @param $labels_comma
     * @param integer $artist_id
     * @param boolean $overwrite
     * @return boolean
     */
    public static function update_label_list($labels_comma, $artist_id, $overwrite)
    {
        debug_event(self::class, 'Updating labels for values {' . $labels_comma . '} artist {' . $artist_id . '}', 5);

        $clabels      = Label::get_labels($artist_id);
        $filter_list  = preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', $labels_comma);
        $editedLabels = (is_array($filter_list)) ? array_unique($filter_list) : array();

        foreach ($clabels as $clid => $clv) {
            if ($clid) {
                $clabel = new Label($clid);
                debug_event(self::class, 'Processing label {' . $clabel->name . '}...', 5);
                $found   = false;
                $lstring = '';

                foreach ($editedLabels as $key => $value) {
                    if ($clabel->name == $value) {
                        $found   = true;
                        $lstring = $key;
                        break;
                    }
                }

                if ($found) {
                    debug_event(self::class, 'Already found. Do nothing.', 5);
                    unset($editedLabels[$lstring]);
                } elseif ($overwrite) {
                    debug_event(self::class, 'Not found in the new list. Delete it.', 5);
                    $clabel->remove_artist_assoc($artist_id);
                }
            }
        }

        // Look if we need to add some new labels
        foreach ($editedLabels as $key => $value) {
            if ($value != '') {
                debug_event(self::class, 'Adding new label {' . $value . '}', 4);
                $label_id = Label::lookup(array('name' => $value));
                if ($label_id === 0) {
                    debug_event(self::class, 'Creating a label directly from artist editing is not allowed.', 3);
                    //$label_id = Label::create(array('name' => $lv));
                }
                if ($label_id > 0) {
                    $clabel = new Label($label_id);
                    $clabel->add_artist_assoc($artist_id);
                }
            }
        }

        return true;
    } // update_tag_list

    /**
     * clean_to_existing
     * Clean label list to existing label list only
     * @param array|string $labels
     * @return array|string
     */
    public static function clean_to_existing($labels)
    {
        $array = (is_array($labels)) ? $labels : preg_split('/(\s*,*\s*)*,+(\s*,*\s*)*/', $labels);
        $ret   = array();
        foreach ($array as $label) {
            $label = trim((string) $label);
            if (!empty($label)) {
                if (Label::lookup(array('name' => $label)) > 0) {
                    $ret[] = $label;
                }
            }
        }

        return (is_array($labels) ? $ret : implode(",", $ret));
    }
} // end label.class

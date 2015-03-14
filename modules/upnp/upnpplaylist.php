<?php
class UPnPPlaylist
{
    private $_songs;
    private $_current = 0;

    public function UPnPPlaylist()
    {
        $this->PlayListRead();
        if (! is_array($this->_songs))
            $this->Clear();
    }

    public function Add($name, $link)
    {
        $this->_songs[] = array('name' => $name, 'link' => $link);
        $this->PlayListSave();
    }

    public function RemoveTrack($track)
    {
        debug_event('upnpPlayer DEL1', print_r($this->_playlist, true), 5);
        unset($this->_songs[$track - 1]);
        debug_event('upnpPlayer DEL2', print_r($this->_playlist, true), 5);
        $this->PlayListSave();
    }

    public function Clear()
    {
        $this->_songs = array();
        $this->_current = 0;
        $this->PlayListSave();
    }

    public function AllItems()
    {
        return $this->_songs;
    }

    public function CurrentItem()
    {
        $item = $this->_songs[$this->_current];
        return $item;
    }

    public function CurrentPos()
    {
        return $this->_current;
    }

    public function Next()
    {
        if ($this->_current < count($this->_songs) - 1) {
            $this->_current++;
            $this->PlayListSave();
        }
    }

    public function NextItem()
    {
        if ($this->_current < count($this->_songs) - 1) {
            $nxt = $this->_current + 1;
            return $this->_songs[$nxt];
        }
        return null;
    }

    public function Prev()
    {
        if ($this->_current > 0) {
            $this->_current--;
            $this->PlayListSave();
        }
    }

    public function Skip($pos)
    {
        // note that pos is started from 1 not from zero
        if (($pos >= 1) && ($pos <= count($this->_songs))) {
            $this->_current = $pos - 1;
            $this->PlayListSave();
        }
    }

    private function PlayListRead()
    {
        $this->_songs = $_SESSION['upnp_playlist'];
        $this->_current = $_SESSION['upnp_current'];
    }

    private function PlayListSave()
    {
        $_SESSION['upnp_playlist'] = $this->_songs;
        $_SESSION['upnp_current'] = $this->_current;
    }

}

?>
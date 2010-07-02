<?php
/* vim:set tabstop=4 softtabstop=4 shiftwidth=4 noexpandtab: */
/*
 Copyright 2009, 2010 Timothy John Wood, Paul Arthur MacIain

 This file is part of php_musicbrainz
 
 php_musicbrainz is free software: you can redistribute it and/or modify
 it under the terms of the GNU Lesser General Public License as published by
 the Free Software Foundation, either version 2.1 of the License, or
 (at your option) any later version.
 
 php_musicbrainz is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU Lesser General Public License for more details.
 
 You should have received a copy of the GNU Lesser General Public License
 along with php_musicbrainz.  If not, see <http://www.gnu.org/licenses/>.
*/
interface mbIFactory {
    public function newArtist();
    public function newArtistAlias();
    public function newDisc();
    public function newTrack();
    public function newRating();
    public function newRelation();
    public function newRelease();
    public function newReleaseEvent();
    public function newTag();
    public function newLabel();
    public function newLabelAlias();
    public function newUser();
}

class mbDefaultFactory implements mbIFactory {
    public function newArtist() { return new mbArtist(); }
    public function newArtistAlias() { return new mbArtistAlias(); }
    public function newDisc() { return new mbDisc(); }
    public function newTrack() { return new mbTrack(); }
    public function newRating() { return new mbRating(); }
    public function newRelation() { return new mbRelation(); }
    public function newRelease() { return new mbRelease(); }
    public function newReleaseEvent() { return new mbReleaseEvent(); }
    public function newTag() { return new mbTag(); }
    public function newLabel() { return new mbLabel(); }
    public function newLabelAlias() { return new mbLabelAlias(); }
    public function newUser() { return new mbUser(); }
}
?>

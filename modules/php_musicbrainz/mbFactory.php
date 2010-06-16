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
    function newArtist();
    function newArtistAlias();
    function newDisc();
    function newTrack();
    function newRating();
    function newRelation();
    function newRelease();
    function newReleaseEvent();
    function newTag();
    function newLabel();
    function newLabelAlias();
    function newUser();
}

class mbDefaultFactory implements mbIFactory {
    function newArtist() { return new mbArtist(); }
    function newArtistAlias() { return new mbArtistAlias(); }
    function newDisc() { return new mbDisc(); }
    function newTrack() { return new mbTrack(); }
    function newRating() { return new mbRating(); }
    function newRelation() { return new mbRelation(); }
    function newRelease() { return new mbRelease(); }
    function newReleaseEvent() { return new mbReleaseEvent(); }
    function newTag() { return new mbTag(); }
    function newLabel() { return new mbLabel(); }
    function newLabelAlias() { return new mbLabelAlias(); }
    function newUser() { return new mbUser(); }
}
?>

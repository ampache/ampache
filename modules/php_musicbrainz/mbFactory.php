<?php
/* vim:set tabstop=4 softtabstop=4 shiftwidth=4 noexpandtab: */
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

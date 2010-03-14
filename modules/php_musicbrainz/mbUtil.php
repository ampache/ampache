<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
    class mbValueError extends Exception {}
    
    function extractFragment( $type ) {
        if ( ( $p = parse_url( $type ) ) == false ) {
            return $type;
        }
        return $p['fragment'];
    }

    function extractUuid( $uid ) {
        if ( empty($uid) )
          return $uid;
          
        $types = array( "artist/", "release/", "track/" );
        for ( $i = 0; $i < 3; $i++ ) {
            if ( ($pos = strpos( $uid, $types[$i] )) !== false ) {
                $pos += strlen($types[$i]);
                if ( $pos + 36 == strlen($uid) ) {
                    return substr( $uid, $pos, 36 );
                }
            }
        }

        if ( strlen($uid) == 36 )
          return $uid;

        throw new mbValueError( "$uid is not a valid MusicBrainz ID.", 1 );
    }

    require_once( 'mbUtil_countrynames.php' );
    function getCountryName( $id ) {
        if ( isset( $mbCountryNames[$id] ) )
          return $mbCountryNames[$id];

        return "";
    }

    require_once( 'mbUtil_languagenames.php' );
    function getLanguageName( $id ) {
        if ( isset( $mbLanguageNames[$id] ) )
          return $mbLanguageNames[$id];

        return "";
    }

    require_once( 'mbUtil_scriptnames.php' );
    function getScriptName( $id ) {
        if ( isset( $mbScriptNames[$id] ) )
          return $mbScriptNames[$id];

        return "";
    }
    
    require_once( 'mbUtil_releasetypenames.php' );
    function getReleaseTypeName( $id ) {
        if ( isset( $mbReleaseTypeNames[$id] ) )
          return $mbReleaseTypeNames[$id];

        return "";
    }
?>

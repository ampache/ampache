<?php
//
//  PHP itself sort of supports the behavior defined here,
//    but I don't trust it, and I think it's better to do
//    application-level database abstraction.
//

function db_connect($host='localhost',$user=null,$password=null)
{
    static $dbh = null;
    // If we haven't already connected, do so
    //   The first call must include this info
    //   Subsequent calls that provide this info may bork db_query() below if you're not careful,
    //     but until I can have static class variables, I'm not going to make an object
    //     out of this mojo.
    if(!empty($host) && isset($user) && isset($password)) $dbh = @mysql_connect($host,$user,$password);

    // If we've already connected successfully, we're good
    if(is_resource($dbh)){ return $dbh; }
    // On a failed connection, let's just die?
    else die("Unable to create database connection in db_connect()");
}

function db_makeinsert($vars, $table)
{
    static $tables = array();
    $dbh = db_connect();
    if(!isset($tables[$table])) $tables[$table] = db_describe($table);
    $fields = $tables[$table];

    foreach($fields as $field)
    {
        //only addslashes if magic quotes is off
        if(get_magic_quotes_gpc) $vars[$field['Field']] = stripslashes($vars[$field['Field']]);
        addslashes($vars[$field['Field']]);

        if(isset($vars[$field['Field']]))
        {

            $q1 = isset($q1)? $q1.','.$field['Field']:'INSERT INTO '.$table.'('.$field['Field'];
            $q2 = isset($q2)? $q2.",\"".$field[$var['Field']]."\"":" VALUES(\"".$vars[$field['Field']]."\"";
        }
    }
    $q1.=')';
    $q2.=')';
    $query = $q1.$q2;
    return $query;
}


function db_select($database, $dbh=null)
{
    if(is_resource($dbh)) @mysql_select_db($database);
    else @mysql_select_db($database, db_connect());
}

function db_describe($thingy)
{
    $descriptions = array();
    foreach( (explode(',',$thingy)) as $field)
    {
        db_query("DESCRIBE $field");
        while($row = db_fetch()){ $descriptions[] = $row; }
    }
    return $descriptions;
}

function db_query($qry=null, $dbh=null)
{
    static $result = null;
    if(!is_resource($dbh)) $dbh = db_connect();
    if(is_null($qry))
    {
        if(is_resource($result)) return $result;
        else return false;
    }
    else
    {
        $result = @mysql_query($qry, $dbh);
        return $result;
    }
}

function db_fetch($result=null)
{
    if(!is_resource($result)) return @mysql_fetch_array(db_query());
    else return @mysql_fetch_array($result);
}

function db_scrub($var,$htmlok=false)
{
    if(!get_magic_quotes_gpc()) $var = addslashes($var);
    return $var;
}


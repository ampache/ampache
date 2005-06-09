<?php

function read_config($config_file, $debug = 0) {
    $fp = fopen($config_file,'r');
    if(!is_resource($fp)) die("Can't open config file $config_file");
    $file_data = fread($fp,filesize($config_file));
    fclose($fp);

    // explode the var by \n's
    $data = explode("\n",$file_data);
    if($debug) echo "<pre>";

    $count = 0;
    $config_name = '';
    foreach($data as $value)
    {
        $count++;
        if (preg_match("/^\[([A-Za-z]+)\]$/",$value,$matches))
        {
            // If we have previous data put it into $results...
            if (!empty($config_name) && count(${$config_name})) $results[$config_name] = ${$config_name};
            $config_name = $matches[1];
        } // if it is a [section] name

        elseif ($config_name)
        {
            // if it's not a comment
            if (preg_match("/^(\w[\w\d]*)\s*=\s*\"{1}(.*?)\"{1};*$/",$value,$matches)
                || preg_match("/^(\w[\w\d]*)\s*=\s*\'{1}(.*?)\'{1};*$/", $value, $matches)
                || preg_match("/^(\w[\w\d]*)\s*=\s*[\'\"]{0}(.*)[\'\"]{0};*$/",$value,$matches))
            {
                if (isset(${$config_name}[$matches[1]]) && is_array(${$config_name}[$matches[1]]) && isset($matches[2]) )
                {
                    if($debug)
                        echo "Adding value <strong>$matches[2]</strong> to existing key <strong>$matches[1]</strong>\n";
                    array_push(${$config_name}[$matches[1]], $matches[2]);
                }
                elseif (isset(${$config_name}[$matches[1]]) && isset($matches[2]) )
                {
                    if($debug)
                        echo "Adding value <strong>$matches[2]</strong> to existing key $matches[1]</strong>\n";
                    ${$config_name}[$matches[1]] = array(${$config_name}[$matches[1]],$matches[2]);
                }
                elseif ($matches[2] !== "")
                {
                    if($debug)
                        echo "Adding value <strong>$matches[2]</strong> for key <strong>$matches[1]</strong>\n";
                    ${$config_name}[$matches[1]] = $matches[2];
                }

                // if there is something there and it's not a comment
                elseif ($value{0} !== "#" AND strlen(trim($value)) > 0)
                {
                    echo "Error Invalid Config Entry --> Line:$count"; die;
                } // else if it's not a comment and there is something there

                else
                {
                    if($debug)
                        echo "Key <strong>$matches[1]</strong> defined, but no value set\n";
                }
            } // end if it's not a comment

        } // else if no config_name


        elseif (preg_match("/^([\w\d]+)\s+=\s+[\"]{1}(.*?)[\"]{1}$/",$value,$matches)
                            || preg_match("/^([\w\d]+)\s+=\s+[\']{1}(.*?)[\']{1}$/", $value, $matches)
                            || preg_match("/^([\w\d]+)\s+=\s+[\'\"]{0}(.*)[\'\"]{0}$/",$value,$matches))
        {
            if (is_array($results[$matches[1]]) && isset($matches[2]) )
            {
                if($debug)
                    echo "Adding value <strong>$matches[2]</strong> to existing key <strong>$matches[1]</strong>\n";
                array_push($results[$matches[1]], $matches[2]);
            }
            elseif (isset($results[$matches[1]]) && isset($matches[2]) )
            {
                if($debug)
                    echo "Adding value <strong>$matches[2]</strong> to existing key $matches[1]</strong>\n";
                $results[$matches[1]] = array($results[$matches[1]],$matches[2]);
            }
            elseif ($matches[2] !== "")
            {
                if($debug)
                    echo "Adding value <strong>$matches[2]</strong> for key <strong>$matches[1]</strong>\n";
                $results[$matches[1]] = $matches[2];
            }

            // if there is something there and it's not a comment
            elseif ($value{0} !== "#" AND strlen(trim($value)) > 0)
            {
                echo "Error Invalid Config Entry --> Line:$count"; die;
            } // else if it's not a comment and there is something there

            else
            {
                if($debug)
                    echo "Key <strong>$matches[1]</strong> defined, but no value set\n";
            }

        } // end else

    } // foreach

    if (count(${$config_name}))
    {
        $results[$config_name] = ${$config_name};
    }

    if($debug) echo "</pre>";

    return $results;

} // end read_config

function libglue_param($param,$clobber=0)
{
	static $params = array();
	if(is_array($param))
	//meaning we are setting values
	{
		foreach ($param as $key=>$val)
		{
			if(!$clobber && isset($params[$key]))
			{
				echo "Error: attempting to clobber $key = $val\n";
				exit();
			}
			$params[$key] = $val;
		}
		return true;
	}
	else
	//meaning we are trying to retrieve a parameter
	{
		if(isset($params[$param])) return $params[$param];
		else return false;
	}
}

function conf($param,$clobber=0)
{
	static $params = array();
	if(is_array($param))
	//meaning we are setting values
	{
		foreach ($param as $key=>$val)
		{
			if(!$clobber && isset($params[$key]))
			{
				echo "Error: attempting to clobber $key = $val\n";
				exit();
			}
			$params[$key] = $val;
		}
		return true;
	}
	else
	//meaning we are trying to retrieve a parameter
	{
		if(isset($params[$param])) return $params[$param];
		else return false;
	}
}

function dbh($str='')
{
    if($str !== '') $dbh = libglue_param(libglue_param($str));
    else $dbh = libglue_param(libglue_param('dbh'));
    if(!is_resource($dbh)) die("Bad database handle: $dbh");
    else return $dbh;
}

<?php
/*
validateEmail.php
version 2.0
by Clay Loveless <clay@killersoft.com>


Originally
By: Jon S. Stevens jon@clearink.com
Copyright 1998 Jon S. Stevens, Clear Ink
This code has all the normal disclaimers.
It is free for any use, just keep the credits intact.

Enhancements and modifications:

           By:  Shane Y. Gibson  shane@tuna.org
Organization:  The Unix Network Archives (http://www.tuna.org/)
         Date:  November 16th, 1998
      Changes:  - Added **all** comments, as original code lacked them.
                - Added some return codes to include a bit more description
                  for useability.

           By:  berber
Organization:  webdev.berber.co.il
         Date:  April 10th, 1999
      Changes:  - The script now handles all kinds of domains (not only @xxx.yyy) as before.
                - Added a debugging mode which also works as a verbose mode.
                
           By:    Frank Vogel vogel@simec.com
Organization:  Simec Corp. (http://www.simec.com)
         Date:  June 13th, 2000
      Changes:  - Check for MX records for each qualification step of  the domain name
                - Use nobody@$SERVER_NAME as MAIL FROM: argument
  Disclaimers:  I disclaim nothing...nor do I claim anything...but
                it would be nice if you included this disclaimer...


         NOTE:  berber and Frank Vogel made some of the same changes regarding
                domain name checking to seperate versions of Shane Gibson's validateEmail variant.
                Their changes have been merged into version 2.0.


           By:    Clay Loveless <clay@killersoft.com>
Organization:  KillerSoft < http://www.killersoft.com/ >
         Date:  March 12th, 2002
      Changes:  - Added 'Preferences' section, enabling several variables to be easily set
                - Changed "nobody@$SERVER_NAME" for MAIL FROM: argument to be
                  "$from@$serverName" - set via Preferences section
                - Signifcantly enhanced berber's 'debug' mode. It has become 'Verbose' mode
                  to ease debugging.
                - Made 'Verbose' mode a function argument. Call validateEmail($email,1) to enable.
                - Added environment detection - 'Verbose' output is adaptable to command-line
                  execution and embedded web execution.
                - Added $socketTimeout Preferences variable for controlling how long we'll wait
                  during fsockopen() to any given host.
                - Added $waitTimeout Preferences variable to control how long we'll wait for
                  a server we've successfully connected with to actually respond with an SMTP greeting.
                  Note -- this is a complete replacement of the previous "wait" method of simply
                  increasing a counter, which proved extremely inadequate in testing on sluggish hosts.
                - Added $mxcutoff Preferences variable to control how many MX hosts we're willing to
                  talk to before calling it quits. (So we're not required to hear "no" from 14
                  hotmail.com mail servers if we don't want to.)
                - Added routine to check SMTP server greeting line for ESTMP, and respond accordingly
                  with EHLO.
                - Added routines to listen for multi-line output from servers.
                - Fixed all commands ending in "\n" to end in "\r\n" as specified by accurate SMTP
                  communication. THIS FIXES THE "HANG" PROBLEM EXPERIENCED WITH MANY MAIL SERVERS,  
                  INCLUDING AOL.COM. (See Disclaimers about AOL.com connections, though ...)
                - Added support for Jeffrey E.F. Friedl's definitive email format regex, translated
                  from perl into PHP. Will reject email addresses with invalid formatting before  
                  opening any server connections.
                - Changed initial "listening" routine to listen for one of two SMTP greeting responses
                  (220 or 421) instead of just listening for anything. validateEmail is now well-behaved
                  if a 421 "temporary rejection" code is received.
                - Assorted optimizations -- using explode() instead of split(), preg_match()
                  instead of ereg(), etc.
                - Improved error reporting on failures.
                - Fixed typos in comments. : )
                - Modified comments where Shane Gibson's were no longer needed or accurate (due to changes).
                  Added the comments for features that didn't exist in Shane's version.
                - Incremented version number.
                  
  Disclaimers:  - All additions and modifications Copyright 2002 KillerSoft.com.
                - Program is free for any use as long as these notes & credits remain intact.
                - Yes, I know there is no foolproof way to validate an e-mail address. But this is better than
                  nothing.
                - Yes, I know that fewer and fewer mail servers are supporting the type of connection
                  and validation that this script performs. There are still a hell of a lot more of them
                  that DO support it than those that DON'T. Yes, this may change over time.
                - This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
                  without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
                - By using this code you agree to indemnify Clay Loveless, KillerSoft, and Crawlspace, Inc.
                  from any liability that might arise from its use.
                - Use at your own risk. This may not work for you. It may produce results other than what you'd expect,
                  or even prefer.
                - AOL.COM Disclaimer: As of this release, mail servers operated by AOL.com (netscape.com,
                  aol.com, cs.com, anything with aoltw.net, just to name a few) return "250" (recipient OK) codes for
                  _any_ address you throw at them. Bounces for invalid recipients are handled and sent out
                  through alternate means. So -- this script won't help you in validating AOL.com (and affiliated)
                  e-mail addresses. BUT ... at least it won't choke/hang on them either, as previous versions
                  of this script would.
                  
                - Please send bugs, comments or suggestions to info@killersoft.com!
*/

/*  This function takes in an email address (say 'shane@tuna.org')
*  and tests to see if it's a valid email address.
*
*  An array with the results is passed back to the caller.
*
*  Possible result codes for the array items are:
*
*  Item 0:  [true|false]        true for valid email address
*                    false for NON-valid email address
*
*  Item 1:  [SMTP Code]        if a valid MX mail server found, then
*                    fill this array in with failed SMTP
*                    reply codes
*                  IF no MX mail server found or connected to,
*                  errors will be explained in this response.
*
*                  Possible Internal error messages:
*                     Invalid email address (bad domain name) [ default message from the old days ]
*                     fsockopen error $errno: $errstr
*                     554 No MX records found for $domain
*                     554 No DNS reverse record found for $domain
*
*                     (554 Response code borrowed from ESMTP's "Transaction failed" response)
*
*  Item 2:  [true|false]        true for valid mail server found for
*                    host/domain
*                    false if no valid mail server found
*
*  Item 3:  [MX server]        if a valid MX host was found and
*                    connected to then fill in this item
*                    with the MX server hostname
*
*  EXAMPLE CODE for use is available at:
*      http://www.killersoft.com/contrib/
*/

function validateEmail ( $email, $verbose=0 ) {
    global $SERVER_NAME;

    // DEFINE PREFERENCES
    
    // Passed along with the HELO/EHLO statement.
    // Leave blank to use $SERVER_NAME.
    // Note that most modern MTAs will ignore (but require) whatever you say here ...
    // the server will determine your domain via other means.
	if (Config::get('mail_check')) {
		$mail_check = Config::get('mail_check');
	} else {
		$mail_check = "strict";
	}

	if ($mail_check == 'strict' && strncmp(PHP_OS,'WIN',3) === TRUE) {
		$mail_check = "easy";
	}

    if (Config::get('mail_domain')) {
	    $serverName = Config::get('mail_domain');
    } 
    else {
    	$serverName = "domain.tld";
    }
    // MAIL FROM -- who's asking?
    // Good values: nobody, postmaster, info, buckwheat, gumby
    $from = "info";
        
    // fsockopen() timeout - in seconds
    $socketTimeout = 15;
    
    // waitTimeout - how long we'll wait for a server to respond after
    // a successful connection. In seconds.
    // Recommended to keep this above 35 seconds - some servers are really slow.
    $waitTimeout = 50;
    
    // MX Server cutoff
    // Some hosts (like hotmail.com) have MANY MX hosts -- 12 or more.
    // Set this to a number where you'd like to say "I get the picture"
    // ... so you don't wind up having to hit EVERY MX host.
    $mxcutoff = 15;
    
    // END OF PREFERENCES
    
    ///////////////////////////////////////////////////////////////////////////////
    // DO NOT EDIT BELOW THIS LINE
    ///////////////////////////////////////////////////////////////////////////////


    // Default initiation statement
    $send = "HELO";

    // Let's give good commands
    $CRLF = "\r\n";
    
    // Make a few adjustments for verbose mode
    if ( $verbose ) {

        // Version
        $version = "validateEmail 2.0 - http://killersoft.com/contrib/";
    
        // Start stopwatch
        list ( $msecStart, $secStart ) = explode ( " ", microtime() );
    
        // Adjust verbose output format
        // for php.cgi or webserver interface
        $sapi_type = php_sapi_name();
        if ( $sapi_type == "cgi" ) {
            // format < >
            $leftCarrot = "<";
            $rightCarrot = ">";
            // set type of "new line"
            $vNL = "echo \"\n\";";
            // verbose Flush Only
            $vFlush = "";
            // output for debugging
            eval("echo \"Internal: $version - running as ".AddSlashes($sapi_type)."\"; $vNL");
        } else {
            // format < >
            $leftCarrot = "&lt;";
            $rightCarrot = "&gt;";
            // set type of "new line" ... flush output for web browsing
            echo "<pre>";
            $vNL = "echo \"\n\"; flush();";
            // verbose Flush Only
            $vFlush = "flush();";
            // output for debugging
            eval("echo \"Internal: $version - running as ".AddSlashes($sapi_type)."\"; $vNL");
        }
    }
        
    // How we'll identify ourselves in SMTP HELO/EHLO argument
    if ( $serverName == "" ) $serverName = "$SERVER_NAME";
    if ( $serverName == "" ) $serverName = "localhost";
    
    // Initialize return values with default
    $return[0] = false;
    $return[1] = "Invalid email address (bad domain name)";
    $return[2] = false;
    $return[3] = "";
    
    // make sure that we're dealing with a valid email address format
    $isValid = true; // just in case validateEmailFormat is not available
    if ( function_exists('validateEmailFormat') ) $isValid = validateEmailFormat ( $email );
    
    // abort if necessary
    if ( !$isValid ) {
        if ( $verbose ) eval("echo \"Internal: $email format is invalid! Quitting ...\"; $vNL");
        return $return;
        
    } else {
        if ( $verbose ) eval("echo \"Internal: $email is a valid RFC 822 formatted address\"; $vNL");
    
        // assign our user part and domain parts respectively to seperate
        // variables
        list ( $user, $domain ) = explode ( "@", $email );
        if ( $verbose ) {
            eval("echo \"Internal: user ..... $user\"; $vNL");
            eval("echo \"Internal: domain ... $domain\"; $vNL");
        }
        
        // split up the domain into sub-parts
        $arr = explode ( ".", $domain );
        
        // figure out how many parts there are in the host/domain name portion
        $count = count ( $arr );
        
        // flag to indicate success
        $bSuccess = false;
        
        // we try this for each qualification step of domain name
        // (from full qualified to TopLevel)
        for ( $i = 0; $i < $count - 1 && !$bSuccess; $i = $i + 1 ) {
        
            // create the domain name
            $domain = "";
            for ( $j = $i; $j < $count; $j = $j + 1 ) {
                $domain = $domain . $arr[$j];
                if ( $j < $count - 1 )
                    // tack on the last dot
                    $domain = $domain . ".";
            }
            if ( $verbose ) eval("echo \"Internal: checking DNS for $domain ... \"; $vNL");
            
		// Strict Mail Check
		if($mail_check == "strict") {
            // check that an MX record exists for Top-Level domain
            // If it exists, start our email address checking
	    if (function_exists('checkdnsrr')) { 
            if ( checkdnsrr ( $domain, "MX" ) ) {
                
                // Okay -- we've got a valid DNS reverse record.
                if ( $verbose ) eval("echo \"Internal: ... Check DNS RR OK!\"; $vNL");
                // Test that MX record for host exists,
                // then fill 'mxhosts' and 'weight' arrays with correct info
                if ( getmxrr ( $domain, $mxhosts, $weight ) ) {
                    
                    // Now we've got MX records
                    if ( $verbose ) {
                        eval("echo \"Internal: MX LOOKUP RESULTS:\"; $vNL");
                        for ( $i = 0; $i < count ( $mxhosts ); $i++) {
                            eval("echo \"     $mxhosts[$i]\"; $vNL");
                        }
                    }
                    // sift through the 'mxhosts', connecting to each one
                    // ONLY until we get a good match
                    $mxcount = count( $mxhosts );
                    // determine our MX host cutoff
                    $mxstop = ($mxcount > $mxcutoff) ? $mxcutoff : $mxcount;
                    for ( $i = 0; $i < $mxstop ; $i++ ) {
                    
                        // open socket on port 25 to mxhost, setting
                        // returned socket pointer to $sp
                        if( $verbose ) eval("echo \"Internal: attempting to open $mxhosts[$i] ...\"; $vNL");
                        $sp = fsockopen ( $mxhosts[$i], 25, $errno, $errstr, $socketTimeout);
                        
                        // Greeting Code default
                        // Sets default greeting code to 421, just in case we
                        // don't ever hear ANYTHING from this host.
                        // If we hear nothing, we'll want to skip it.
                        $greetCode = "421";
                        
                        // if $sp connection is good, let's rock on
                        if ( $sp ) {
                            if ( $verbose ) {
                                eval("echo \"* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\"; $vNL");
                                eval("echo \"Internal: socket open to $mxhosts[$i]\"; $vNL");
                            }
                            // work variables
                            $waitMarker = 0;
                            $msec = 0; // milisec count
                            $tsec = 0; // tensec count
                            $out = "";
                            
                            // set our created socket for $sp to
                            // non-blocking mode so that our fgets()
                            // calls will return with a quickness
                            set_socket_blocking ( $sp, false );
                            
                            // as long as our 'out' variable does not begin
                            // with a valid SMTP greeting (220 or 421),
                            // keep looping (do) until we get something
                            do {
                            
                                // prepare for clean debug output if necessary
                                // (puts a line break after the waitMarkers)
                                if ( $verbose && $msec > 0 ) {
                                    $elapsed = $tsec + ($msec/4);
                                    $clean = "echo \"($elapsed seconds)\"; $vNL";
                                }
                                // output of the stream assigned to
                                // 'out' variable
                                $out = fgets ( $sp, 2500 );
                                
                                // Check for multi-line output (###-)
                                if ( preg_match ( "/^2..-/", $out ) ) {
                                    $end = false;
                                    while ( !$end ) {
                                        // keep listening
                                        $line = fgets ( $sp, 2500 );
                                        $out .= $line;
                                        if ( preg_match ( "/^2.. /", $line ) ) {
                                            // the last line of output shouldn't
                                            // have a dash after the response code
                                            $end = true;
                                        }
                                    }
                                }
                                
                                if ( $verbose && $out != "" ) eval("$clean echo \"Server: ".AddSlashes($out)."\"; $vNL");
                                
                                // if we get a "220" code (service ready),
                                // we're ready to rock on
                                if ( substr ( $out, 0, 3 ) == "220" ) {
                                    if ( $verbose ) eval("echo \"Internal: service ready on $mxhosts[$i] ... moving on\"; $vNL");
                                    $return[2] = true;
                                    $return[3] = "$mxhosts[$i]";
                                    // determine if we should speak in terms of HELO or EHLO
                                    if ( preg_match ( "/ESMTP/", $out ) ) {
                                        $send = "HELO";
                                    } else {
                                        $send = "HELO";
                                    }
                                    
                                    // Set Greeting Code
                                    $greetCode = "220";
                                    
                                }
                                
                                // else if ...
                                // Perhaps we've gotten a 421 Temporarily Refused error
                                else if ( substr ( $out, 0, 3 ) == "421" ) {
                                
                                    //if ( $verbose ) echo " ... moving on\n";
                                    if ( $verbose ) eval("echo \"Internal: $mxhosts[$i] temporarily rejected connection. (421 response)\"; $vNL");
                                    $return[2] = false;
                                    // Set Greeting Code
                                    $greetCode = "421";
                                    break; // get out of this loop
                                    
                                }
                                
                                // increase our waitTimeout counters
                                // if we still haven't heard anything ...
                                // Note that the time looping isn't an exact science
                                // with usleep or the Windows hack ... but
                                // it's in the ballpark. Close enough.
                                if ( $out == "" && $msec < $waitTimeout ) {

                                    // wait for a quarter of a second
                                    if ( $verbose ) {
                                        if ( $msec == 0 ) {
                                            eval("echo \"Internal: Waiting: one '.' ~ 0.25 seconds of waiting\"; $vNL");
                                        }
                                        eval("echo \".\"; $vFlush");
                                        $waitMarker++;
                                        if ( $waitMarker == 40 ) {
                                            // ten seconds
                                            $tsec += 10;
                                            eval("echo \" ($tsec seconds)\"; $vNL");
                                            $waitMarker = 0;
                                        }
                                    }
                                    $msec = $msec + 0.25;
                                    usleep(250000);
                                    
                                } elseif ( $msec == $waitTimeout ) {
                                    
                                    // let's get out of here. Toooo sloooooww ...
                                    if ( $verbose ) eval("$clean echo \"Internal: !! we've waited $waitTimeout seconds !!\nbreaking ...\"; $vNL");
                                    break;

                                }
                                
                                                            
                                // end of 'do' loop
                            } while ( substr ( $out, 0, 3 ) != "220" );
                            
                            // Make sure we got a "220" greetCode
                            // before we start shoveling requests
                            // at this server.
                            if ( $greetCode == "220" ) {
                            
                                // reset our file pointer to blocking mode,
                                // so we can wait for communication to finish
                                // before moving on ...
                                set_socket_blocking ( $sp, true );
                                
                                // talk to the MX mail server, attempt to validate
                                // ourself. Use "HELO" or "EHLO", as determined above
                                fputs ( $sp, "$send $serverName"."$CRLF" );
                                if ( $verbose ) eval("echo \"Client: $send $serverName\"; $vNL");
                                
                                // get the mail server's reply, check it
                                //
                                $originalOutput = fgets ( $sp, 2500 );
                                // Check for multi-line positive output
                                if ( preg_match ( "/^...-/", $originalOutput ) ) {
                                    $end = false;
                                    while ( !$end ) {
                                        // keep listening
                                        $line = fgets ( $sp, 2500 );
                                        $originalOutput .= $line;
                                        if ( preg_match ( "/^... /", $line ) ) {
                                            // the last line of output shouldn't
                                            // have a dash after the response code
                                            $end = true;
                                        }
                                    }
                                }
                                if ( $verbose ) eval("echo \"Server: ".AddSlashes($originalOutput)."\"; $vNL");
                                
                                
                                // if there's a HELP option, let's see it
                                if ( $verbose ) {
                                    if( preg_match( "/250.HELP/m", $originalOutput ) && $verbose == true ) {
                                        
                                        eval("echo \"Internal: VERBOSE-MODE ONLY: Getting the HELP output\"; $vNL");
                                        // Get the output of the HELP command
                                        fputs ( $sp, "HELP"."$CRLF" );
                                        if ( $verbose ) eval("echo \"Client: HELP\"; $vNL");
                                        // Get output again
                                        $output = fgets ( $sp, 2500 );
                                        // Check for multi-line positive output
                                        if ( preg_match ( "/^...-/", $output ) ) {
                                            $end = false;
                                            while ( !$end ) {
                                                // keep listening
                                                $line = fgets ( $sp, 2500 );
                                                $output .= $line;
                                                if ( preg_match ( "/^... /", $line ) ) {
                                                    // the last line of output shouldn't
                                                    // have a dash after the response code
                                                    $end = true;
                                                }
                                            }
                                        }
                                        if ( $verbose ) eval("echo \"Server: ".AddSlashes($output)."\"; $vNL");
                                                            
                                    }
                                }
                                
                                // Give the MAIL FROM: header to the server
                                fputs ( $sp, "MAIL FROM: <$from" . "@" . "$serverName" . ">"."$CRLF");
                                if ( $verbose ) eval("echo \"Client: MAIL FROM: $leftCarrot"."$from" . "@" . "$serverName" . "$rightCarrot\"; $vNL");
                                
                                // Get output again
                                $output = fgets ( $sp, 2500 );
                                // Check for multi-line positive output
                                if ( preg_match ( "/^...-/", $output ) ) {
                                    $end = false;
                                    while ( !$end ) {
                                        // keep listening
                                        $line = fgets ( $sp, 2500 );
                                        $output .= $line;
                                        if ( preg_match ( "/^... /", $line ) ) {
                                            // the last line of output shouldn't
                                            // have a dash after the response code
                                            $end = true;
                                        }
                                    }
                                }
                                if ( $verbose ) eval("echo \"Server: ".AddSlashes($output)."\"; $vNL");
                                
                                // Give the RCPT TO: header for the email address we're testing
                                fputs ( $sp, "RCPT TO: <$email>"."$CRLF" );
                                if ( $verbose ) eval("echo \"Client: RCPT TO: $leftCarrot"."$email"."$rightCarrot\"; $vNL");
                                
                                // Get output again
                                // This will be the one we check for validity
                                $output = fgets ( $sp, 2500 );
                                // Check for multi-line positive output
                                if ( preg_match ( "/^...-/", $output ) ) {
                                    $end = false;
                                    while ( !$end ) {
                                        // keep listening
                                        $line = fgets ( $sp, 2500 );
                                        $output .= $line;
                                        if ( preg_match ( "/^... /", $line ) ) {
                                            // the last line of output shouldn't
                                            // have a dash after the response code
                                            $end = true;
                                        }
                                    }
                                }
                                if ( $verbose ) eval("echo \"Server: ".AddSlashes($output)."\"; $vNL");
                                
                                // test the last reply code from the mail server
                                // for the 250 (okay) response
                                if ( substr ( $output, 0, 3 ) == "250" ) {
                                    
                                    // set our true/false(ness)
                                    // array item for testing
                                    $return[0] = true;
                                    $return[1] = $output;
                                    if ( $verbose ) eval("echo \"Internal: Check for 250 ... Recipient OK\"; $vNL");
                                    
                                } else {
                                
                                    // we didn't get a 250
                                    // may be a bogus address
                                    if ( $verbose ) eval("echo \"Internal: Check for 250 ... Response did not begin with 250!\"; $vNL");
                                    // fill in 2nd array item with mail server's
                                    // reply for user to test if they want
                                    $return[0] = false;
                                    $return[1] = $output;
                                    
                                }
                                
                                // tell the mail server we're done
                                fputs ( $sp, "QUIT"."$CRLF" );
                                if ( $verbose ) {
                                    eval("echo \"Client: QUIT\"; $vNL");
                                    eval("echo \"* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\"; $vNL $vNL");                            
                                }
                                
                                // close the socket/file pointer
                                fclose ( $sp );
                                
                                // If we got a good response back on RCPT TO,
                                // break here
                                // Otherwise, keep trying MX servers until we
                                // get a good response or run out of MX servers
                                // to try.
                                if ( $return[0] == true ) {
                                    if ( $verbose ) {
                                        eval("echo \"Internal: Recipient is OK - thanks, $mxhosts[$i]!\"; $vNL");
                                        eval("echo \"Internal: Stop checking MX hosts ...\"; $vNL");                                        
                                    }
                                    $bSuccess = true;
                                    break;
                                }
                            
                            } else {
                                
                                // greetCode wasn't "220"
                                // we better skip this one and move on
                                if ( $verbose ) eval("echo \"Internal: SKIPPING $mxhosts[$i] -- never got 220 welcome\"; $vNL");
                                // close out this connection
                                fclose ( $sp );
                                
                            } // end of greetCode check
                        
                        } else {
                            // $sp socket pointer was false -- couldn't open it
                            if ( $verbose ) {
                                eval("echo \"* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\"; $vNL");                            
                                eval("echo \"Internal: could not open socket to $mxhosts[$i]!\"; $vNL");
                                eval("echo \"fsockopen error $errno: $errstr\"; $vNL");
                                eval("echo \"* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\"; $vNL $vNL");                            
                            }
                            $return[0] = false;
                            $return[1] = "fsockopen error $errno: $errstr";
                        } // end of $sp check
                    
                    } // end for $mxhosts
                    
                } //  getmxrr test
                  else {
                    // getmxrr failed
                    if ( $verbose ) eval("echo \"Internal: No MX reverse records found for $domain\"; $vNL");
                    $return[0] = false;
                    $return[1] = "554 No MX records found for $domain";
                } // end getmxrr test
            
            } // continue checkdnsrr test
                else {
                if ( $verbose ) eval("echo \"Internal: No DNS Reverse Record available!\"; $vNL");
                $return[0] = false;
                $return[1] = "554 No DNS reverse record found for $domain";
            } // end checkdnsrr test

	} // if function doesn't exist
    } elseif ($mail_check == "easy") { // easy email address check
		$pattern = "^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$";
		if(eregi($pattern, $email)) {
			$return[0] = true;
			$return[1] = "OK";
		} else {
			$return[0] = false;
			$return[1] = "NG";
		}
	} else { // Nothing to do
		$return[0] = true;
		$return[1] = "No Check";
	}

        } // end walking through each domain possibility
    
    } // end isValid
    
    // output elapsed time if Verbose
    if ( $verbose ) {
        list ( $msecStop, $secStop ) = explode ( " ", microtime() );
        $elapsedTime = (double)($secStop + $msecStop) - ($secStart + $msecStart);
        $elapsedTime = number_format($elapsedTime,3);
        eval("echo \"Internal: VERBOSE-MODE execution time: $elapsedTime seconds (silent mode somewhat faster)\"; $vNL");
        if ( $sapi_type != "cgi" ) echo "</pre>";
    }
    
    // return the array for the user to test against
    return $return;

} // END validateEmail-2.0

?>

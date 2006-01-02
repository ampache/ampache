<?php
////////////////////////////////////////////////////////////////////////
//
// validateEmailFormat.php - v 1.0
//
// PHP translation of Email Regex Program (optimized)
// Derived from:
//   Appendix B - Email Regex Program
//   _Mastering Regular Expressions_ (First Edition, May 1997 revision)
//     by Jeffrey E.F. Friedl
//     Copyright 1997 O'Reilly & Associates
//     ISBN: 1-56592-257-3
//   For more info on this title, see:
//     http://www.oreilly.com/catalog/regex/
//   For original perl version, see:
//     http://examples.oreilly.com/regex/
//
// Follows RFC 822 about as close as is possible.
// http://www.faqs.org/rfcs/rfc822.html
//
//
// DESCRIPTION:
//  bool validateEmailFormat ( string string )
//
//  Returns TRUE if the email address passed is in a valid format
//  according to RFC 822, returns FALSE if email address passed
//  is not in a valid format.
//
// EXAMPLES:
//  Example #1:
//  $email = "Jeffy <\"That Tall Guy\"@foo.com (blah blah blah)";
//  $isValid = validateEmailFormat($email);
//  if($isValid) {
//    ...  // Yes, the above address is a valid format!
//  } else {
//    echo "sorry, that address isn't formatted properly.";
//  }
//
//  Example #2:
//  $email = "foo@bar.co.il";
//  $isValid = validateEmailFormat($email);
//  if($isValid) {
//    ...
//  } else {
//    echo "sorry ...";
//  }
//
// Translated by Clay Loveless <clay@killersoft.com> on March 11, 2002
// ... in hopes that the "here's how to check an e-mail address!"
// discussion can finally end. After all ...
//
//       Friedl is the master -- Hail to the King, baby!
//
////////////////////////////////////////////////////////////////////////
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
//
// Hell, it might not even work for you.
//
// By using this code you agree to indemnify Clay Loveless,
// KillerSoft, and Crawlspace, Inc. from any liability that might
// arise from its use.
//
// Have fun!
//
////////////////////////////////////////////////////////////////////////
function validateEmailFormat ( $email ) {

    // Some shortcuts for avoiding backslashitis
    $esc        = '\\\\';               $Period      = '\.';
    $space      = '\040';               $tab         = '\t';
    $OpenBR     = '\[';                 $CloseBR     = '\]';
    $OpenParen  = '\(';                 $CloseParen  = '\)';
    $NonASCII   = '\x80-\xff';          $ctrl        = '\000-\037';
    $CRlist     = '\n\015';  // note: this should really be only \015.

    // Items 19, 20, 21 -- see table on page 295 of 'Mastering Regular Expressions'
    $qtext = "[^$esc$NonASCII$CRlist\"]";                // for within "..."
    $dtext = "[^$esc$NonASCII$CRlist$OpenBR$CloseBR]";    // for within [...]
    $quoted_pair = " $esc [^$NonASCII] ";    // an escaped character

    // *********************************************
    // Items 22 and 23, comment.
    // Impossible to do properly with a regex, I make do by allowing at most
    // one level of nesting.
    $ctext = " [^$esc$NonASCII$CRlist()] ";
    
    // $Cnested matches one non-nested comment.
    // It is unrolled, with normal of $ctext, special of $quoted_pair.
    $Cnested = "";
        $Cnested .= "$OpenParen";                    // (
        $Cnested .= "$ctext*";                        //       normal*
        $Cnested .= "(?: $quoted_pair $ctext* )*";    //       (special normal*)*
        $Cnested .= "$CloseParen";                    //                         )
    
    // $comment allows one level of nested parentheses
    // It is unrolled, with normal of $ctext, special of ($quoted_pair|$Cnested)
    $comment = "";
        $comment .= "$OpenParen";                        //  (
        $comment .= "$ctext*";                            //     normal*
        $comment .= "(?:";                                //       (
        $comment .= "(?: $quoted_pair | $Cnested )";    //         special
        $comment .= "$ctext*";                            //         normal*
        $comment .= ")*";                                //            )*
        $comment .= "$CloseParen";                        //                )
        
    // *********************************************
    // $X is optional whitespace/comments
    $X = "";
        $X .= "[$space$tab]*";                    // Nab whitespace
        $X .= "(?: $comment [$space$tab]* )*";    // If comment found, allow more spaces
        
        
    // Item 10: atom
    $atom_char = "[^($space)<>\@,;:\".$esc$OpenBR$CloseBR$ctrl$NonASCII]";
    $atom = "";
        $atom .= "$atom_char+";        // some number of atom characters ...
        $atom .= "(?!$atom_char)";    // ... not followed by something that
                                    //     could be part of an atom
                                    
    // Item 11: doublequoted string, unrolled.
    $quoted_str = "";
        $quoted_str .= "\"";                            // "
        $quoted_str .= "$qtext *";                        //   normal
        $quoted_str .= "(?: $quoted_pair $qtext * )*";    //   ( special normal* )*
        $quoted_str .= "\"";                            //        "
    
    
    // Item 7: word is an atom or quoted string
    $word = "";
        $word .= "(?:";
        $word .= "$atom";        // Atom
        $word .= "|";            // or
        $word .= "$quoted_str";    // Quoted string
        $word .= ")";
        
    // Item 12: domain-ref is just an atom
    $domain_ref = $atom;
    
    // Item 13: domain-literal is like a quoted string, but [...] instead of "..."
    $domain_lit = "";
        $domain_lit .= "$OpenBR";                        // [
        $domain_lit .= "(?: $dtext | $quoted_pair )*";    //   stuff
        $domain_lit .= "$CloseBR";                        //         ]

    // Item 9: sub-domain is a domain-ref or a domain-literal
    $sub_domain = "";
        $sub_domain .= "(?:";
        $sub_domain .= "$domain_ref";
        $sub_domain .= "|";
        $sub_domain .= "$domain_lit";
        $sub_domain .= ")";
        $sub_domain .= "$X"; // optional trailing comments
        
    // Item 6: domain is a list of subdomains separated by dots
    $domain = "";
        $domain .= "$sub_domain";
        $domain .= "(?:";
        $domain .= "$Period $X $sub_domain";
        $domain .= ")*";
        
    // Item 8: a route. A bunch of "@ $domain" separated by commas, followed by a colon.
    $route = "";
        $route .= "\@ $X $domain";
        $route .= "(?: , $X \@ $X $domain )*"; // additional domains
        $route .= ":";
        $route .= "$X"; // optional trailing comments
        
    // Item 5: local-part is a bunch of $word separated by periods
    $local_part = "";
        $local_part .= "$word $X";
        $local_part .= "(?:";
        $local_part .= "$Period $X $word $X"; // additional words
        $local_part .= ")*";
        
    // Item 2: addr-spec is local@domain
    $addr_spec = "$local_part \@ $X $domain";

    // Item 4: route-addr is <route? addr-spec>
    $route_addr = "";
        $route_addr .= "< $X";
        $route_addr .= "(?: $route )?"; // optional route
        $route_addr .= "$addr_spec";    // address spec
        $route_addr .= ">";
        
    // Item 3: phrase........
    $phrase_ctrl = '\000-\010\012-\037'; // like ctrl, but without tab
    
    // Like atom-char, but without listing space, and uses phrase_ctrl.
    // Since the class is negated, this matches the same as atom-char plus space and tab
    $phrase_char = "[^()<>\@,;:\".$esc$OpenBR$CloseBR$NonASCII$phrase_ctrl]";

    // We've worked it so that $word, $comment, and $quoted_str to not consume trailing $X
    // because we take care of it manually.
    $phrase = "";
        $phrase .= "$word";                            // leading word
        $phrase .= "$phrase_char *";                   // "normal" atoms and/or spaces
        $phrase .= "(?:";
        $phrase .= "(?: $comment | $quoted_str )";     // "special" comment or quoted string
        $phrase .= "$phrase_char *";                //  more "normal"
        $phrase .= ")*";

    // Item 1: mailbox is an addr_spec or a phrase/route_addr
    $mailbox = "";
        $mailbox .= "$X";                    // optional leading comment
        $mailbox .= "(?:";
        $mailbox .= "$addr_spec";            // address
        $mailbox .= "|";                    // or
        $mailbox .= "$phrase  $route_addr";    // name and address
        $mailbox .= ")";

    // test it and return results
    $isValid = preg_match("/^$mailbox$/xS",$email);
    
    return($isValid);
} // END validateEmailFormat
?>

<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/*!
 @header Style File
 @discussion this is the css that handles the menu mojo (#sidebar, etc) and the 
        outer layer of layout (#maincontainer, #topbar, #content, etc
*/
?>
<style type="text/css">
<!--
/**
 * Div Definitions
 * These define how the page is laid out, be careful with these as changes to them
 * can cause drastic layout changes
 */
        #maincontainer
                {
                        margin: 0px;
                }
        #topbar
                {
                        height: 80px;
                        padding-top:10px;
                        padding-left:10px;
                        background-color: <?php echo conf('bg_color1'); ?>;
                }
        #topbarright
                {
                        float: right;
                }
        #topbarleft
                {
                        float: left;
                }
        .nodisplay { display: none;}
        .display {}
        #mpdpl td {
                padding: 0px 2px 0px 2px;
                text-align: left;
                }
/**
 * End Div Definitions
 * This is the end of the main structure def's
 */

/**
 * Experimental for menus (Thx Sigger)
 * TO DO: Fill in 1px border around menus & submenu items
 * Make padding appply to the li, not just an a.  Moving paddng: to li throws off the dropdown menu alignment.
 */
	#content {
/*                float: left;    /*                      use for horizontal menu; comment out otherwise */
           padding-left:155px;
           padding-right:5px;
	}
    #sidebar {
        clear: both;
        height: 100%;
        margin-right: 5px;
        float: left;
        padding: 0;
        list-style: none;
        border: 1px solid #000;
        line-height: 1.0;
    }
    #sidebar ul {
        margin: 0px;
        list-style: none;
        padding: 0px;
        font-family: verdana, arial, Helvetica, sans-serif;
        line-height: 1.0;
    }
    #sidebar li {
        margin: 0;
        display: block;
        border-bottom: 1px solid white;
        border-left: 1px solid white;
        border-right: 1px solid white;
	border-top: 1px solid white;
/*        float: left;                /*          use for horizontal menu; comment out otherwise */
        padding: 5px 0px 5px 10px;
        width: 10.5em;
        background-color: <?php echo conf('row_color2'); ?>;
    }
    #sidebar a, .navbutton {
        display: block;  /*Not sure why this is neccesary, but it is for IE*/
        text-decoration: none;
        }
    #sidebar li:hover, #sidebar li.sfhover {
        color: <?php echo conf('font_color2'); ?>;
        background-color: <?php echo conf('row_color3'); ?>;
    }
    #sidebar li:active {
        background-color: <?php echo conf('row_color1'); ?>;
	z-index:30;
    }
    #sidebar li ul {
        float: left;
        position: absolute;
        width: 9em;
	margin: -1.5em 0 0 10.5em;  /* for vertical menu; comment out otherwise */
/*	margin: 0.5em 0 0 -1.1em;  /* for horizontal menu;  comment out otherwise */

        left: -999em;  /* this -999em puts the submenu item way off to the left until it's called back by a hover (below) */
	z-index:30;
    }
    #sidebar li:hover ul, #sidebar li.sfhover ul {
        left: auto;  /* this calls the submenu back when the parent li is hovered. */
    }
/*star rating styles */
       /*             styles for the star rater                */      

       .star-rating{
               list-style:none;
               margin: 0px;
               padding:0px;
               width: 80px;
               height: 15px;
               position: relative;
               background: url(<?php echo conf('web_path'); ?>/images/ratings/star_rating.gif) top left repeat-x;                
       }
       .star-rating li{
               padding:0px;
               margin:0px;
               /*\*/
               float: right;
              /* */
       }
       .star-rating li a{
               display:block;
               width:16px;
               height: 15px;
               text-decoration: none;
               text-indent: -9000px;
               z-index: 20;
               position: absolute;
               padding: 0px;
       }
       .star-rating li a:hover{
               background: url(<?php echo conf('web_path'); ?>/images/ratings/star_rating.gif) left center;
               z-index: 2;
               left: 0px;
       }
       li.zero-stars a:hover { 
       		background: url(<?Php echo conf('web_path'); ?>/images/ratings/x.gif);
		height: 15px;
		left: 80px;
		display: block;
	}
       a.zero-stars {
	       background: url(<?php echo conf('web_path'); ?>/images/ratings/x_off.gif);
	       height: 15px;
	       left: 80px;
	       display: block;
	}
       a.one-stars{
               left: 0px;
       }
       a.one-stars:hover{
               width:16px;
       }
       a.two-stars{
               left:16px;
       }
       a.two-stars:hover{
               width: 32px;
       }
       a.three-stars{
               left: 32px;
       }
       a.three-stars:hover{
               width: 48px;
       }
       a.four-stars{
               left: 48px;
       }       
       a.four-stars:hover{
               width: 64px;
       }
       a.five-stars{
               left: 64px;
       }
       a.five-stars:hover{
               width: 80px;
       }
       li.current-rating{
               background: url(<?php echo conf('web_path'); ?>/images/ratings/star_rating.gif) left bottom;
               position: absolute;
               height: 15px;
               display: block;
               text-indent: -9000px;
               z-index: 1;
       }               
	#tablist {
		padding: 3px 0;
		margin: 12px 0 0 0;
		font: bold 12px Verdana, sans-serif;
	}

	#tablist li {
		list-style: none;
		margin: 0;
		display: inline;
	}

	#tablist li a {
		padding: 3px 0.5em;
		margin-left: 3px;
		border: 1px solid <?php echo conf('row_color1'); ?>;
		border-bottom: none;
		background: <?php echo conf('row_color3'); ?>;
		text-decoration: none;
	}

	#tablist li a:link { color: <?php echo conf('font_color1'); ?>; }
	#tablist li a:visited { color: <?php echo conf('bg_color2'); ?>; }

	#tablist li a:hover {
		color: <?php echo conf('font_color2'); ?>;
		background: <?php echo conf('row_color2'); ?>;
		border-color: <?php echo conf('bg_color2'); ?>;
	}

	#tablist li a#current {
		color: <?php echo conf('font_color2'); ?>;
		background: <?php echo conf('row_color2'); ?>;
		border-color: <?php echo conf('bg_color2'); ?>;
		border-bottom: 1px solid <?php echo conf('bg_color2'); ?>; 
	}
/* Other Required Styles */
        .confirmation-box {
                padding-left:5px;
                padding-top:5px;
                padding-right:5px;
                margin-bottom:10px;
		display: table-cell;
                background-color: <?php echo conf('base_color1'); ?>;
                border-right:2px solid <?php echo conf('bg_color2'); ?>;
                border-bottom:2px solid <?php echo conf('bg_color2'); ?>;
                border-left:2px solid <?php echo conf('bg_color2'); ?>;
                border-top:2px solid <?php echo conf('bg_color2'); ?>;
	}
	.text-action, .text-action li { 
		margin-top:5px;
		list-style: none;
		margin-bottom:5px;
		padding-left:0px;
	}
	.text-action a, .text-action span { 
		background: <?php echo conf('base_color2'); ?>;
		border:1px solid <?php echo conf('bg_color2'); ?>;	
		padding-left:2px;
		padding-right:2px;
		text-decoration: none;
	} 
	.text-action #pt_active {
		background: <?php echo conf('bg_color2'); ?>;
		color: <?php echo conf('font_color3'); ?>;
		border:1px solid <?php echo conf('base_color2'); ?>;
	}
-->
</style>

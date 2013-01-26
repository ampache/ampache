-- Copyright 2010 - 2013 Ampache.org 
--
-- This program is free software; you can redistribute it and/or
-- modify it under the terms of the GNU General Public License v2
-- as published by the Free Software Foundation.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

CREATE TABLE `twitter_users` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `ampache_id` int(10),
    `oauth_provider` varchar(10),
    `oauth_uid` text,
    `oauth_token` text,
    `oauth_secret` text,
    `username` text,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM

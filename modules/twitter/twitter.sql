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

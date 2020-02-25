CREATE TABLE `%s` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `type` varchar(16) NOT NULL,
    `group` varchar(32) NOT NULL,
    `hook` varchar(64) NOT NULL,
    `data` longtext,
    `timestamp` int(11) DEFAULT NULL,
    `status` varchar(8) NOT NULL DEFAULT 'pending',
    PRIMARY KEY (`id`)
) %s;

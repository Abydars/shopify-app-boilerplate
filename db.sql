DROP TABLE IF EXISTS `tbl_stores`;
CREATE TABLE `tbl_stores`
(
    `id`         int(11) NOT NULL AUTO_INCREMENT,
    `shop`       varchar(255) DEFAULT NULL,
    `token`      varchar(255) DEFAULT NULL,
    `session_id` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`id`)
);

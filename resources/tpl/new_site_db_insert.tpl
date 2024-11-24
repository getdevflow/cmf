CREATE TABLE `{site_prefix}content` (
`content_id` varchar(36) NOT NULL,
`content_title` varchar(191) NOT NULL,
`content_slug` varchar(191) NOT NULL,
`content_body` longtext DEFAULT NULL,
`content_author` varchar(191) NOT NULL,
`content_type` varchar(191) NOT NULL,
`content_parent` varchar(191) DEFAULT NULL,
`content_sidebar` int(11) DEFAULT 0,
`content_show_in_menu` int(11) DEFAULT 0,
`content_show_in_search` int(11) DEFAULT 0,
`content_featured_image` varchar(191) DEFAULT NULL,
`content_status` varchar(36) NOT NULL DEFAULT 'draft',
`content_created` varchar(191) DEFAULT NULL,
`content_created_gmt` datetime DEFAULT NULL,
`content_published` varchar(191) DEFAULT NULL,
`content_published_gmt` datetime DEFAULT NULL,
`content_modified` varchar(191) DEFAULT '0000-00-00 00:00:00',
`content_modified_gmt` datetime DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `{site_prefix}contentmeta` (
`meta_id` varchar(36) NOT NULL,
`content_id` varchar(191) DEFAULT NULL,
`meta_key` varchar(191) DEFAULT NULL,
`meta_value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `{site_prefix}content_type` (
`content_type_id` varchar(36) NOT NULL,
`content_type_title` varchar(191) DEFAULT NULL,
`content_type_slug` varchar(191) DEFAULT NULL,
`content_type_description` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `{site_prefix}elfinder_file` (
`id` int(7) UNSIGNED NOT NULL,
`parent_id` int(7) UNSIGNED NOT NULL,
`name` varchar(255) NOT NULL,
`content` longblob NOT NULL,
`size` int(10) UNSIGNED NOT NULL DEFAULT 0,
`mtime` int(10) UNSIGNED NOT NULL DEFAULT 0,
`mime` varchar(256) NOT NULL DEFAULT 'unknown',
`read` enum('1','0') NOT NULL DEFAULT '1',
`write` enum('1','0') NOT NULL DEFAULT '1',
`locked` enum('1','0') NOT NULL DEFAULT '0',
`hidden` enum('1','0') NOT NULL DEFAULT '0',
`width` int(5) NOT NULL DEFAULT 0,
`height` int(5) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `{site_prefix}elfinder_file` VALUES(1, 0, 'DATABASE', '', 0, 0, 'directory', '1', '1', '0', '0', 0, 0);

CREATE TABLE `{site_prefix}elfinder_trash` (
`id` int(7) UNSIGNED NOT NULL,
`parent_id` int(7) UNSIGNED NOT NULL,
`name` varchar(255) NOT NULL,
`content` longblob NOT NULL,
`size` int(10) UNSIGNED NOT NULL DEFAULT 0,
`mtime` int(10) UNSIGNED NOT NULL DEFAULT 0,
`mime` varchar(256) NOT NULL DEFAULT 'unknown',
`read` enum('1','0') NOT NULL DEFAULT '1',
`write` enum('1','0') NOT NULL DEFAULT '1',
`locked` enum('1','0') NOT NULL DEFAULT '0',
`hidden` enum('1','0') NOT NULL DEFAULT '0',
`width` int(5) NOT NULL DEFAULT 0,
`height` int(5) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `{site_prefix}elfinder_trash` VALUES(1, 0, 'DB Trash', '', 0, 0, 'directory', '1', '1', '0', '0', 0, 0);

CREATE TABLE `{site_prefix}event_store` (
`event_id` varchar(36) NOT NULL,
`transaction_id` varchar(36) DEFAULT NULL,
`event_type` varchar(191) NOT NULL,
`event_classname` varchar(191) NOT NULL,
`payload` longtext NOT NULL,
`metadata` longtext NOT NULL,
`aggregate_id` varchar(36) NOT NULL,
`aggregate_type` varchar(191) NOT NULL,
`aggregate_playhead` int(11) NOT NULL,
`recorded_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `{site_prefix}option` (
`option_id` varchar(36) NOT NULL,
`option_key` varchar(191) DEFAULT NULL,
`option_value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `{site_prefix}option` VALUES('{ulid_1}', 'sitename', '{sitename}');
INSERT INTO `{site_prefix}option` VALUES('{ulid_2}', 'site_description', 'Just another Devflow site');
INSERT INTO `{site_prefix}option` VALUES('{ulid_3}', 'admin_email', '{admin_email}');
INSERT INTO `{site_prefix}option` VALUES('{ulid_4}', 'site_locale', 'en');
INSERT INTO `{site_prefix}option` VALUES('{ulid_5}', 'cookieexpire', '604800');
INSERT INTO `{site_prefix}option` VALUES('{ulid_6}', 'cookiepath', '/');
INSERT INTO `{site_prefix}option` VALUES('{ulid_7}', 'site_timezone', '{timezone}');
INSERT INTO `{site_prefix}option` VALUES('{ulid_8}', 'api_key', '{api_key}');
INSERT INTO `{site_prefix}option` VALUES('{ulid_9}', 'date_format', 'd F Y');
INSERT INTO `{site_prefix}option` VALUES('{ulid_10}', 'time_format', 'h:i A');
INSERT INTO `{site_prefix}option` VALUES('{ulid_11}', 'admin_skin', 'skin-red');
INSERT INTO `{site_prefix}option` VALUES('{ulid_12}', 'content_per_page', '6');

CREATE TABLE `{site_prefix}plugin` (
`plugin_id` varchar(36) NOT NULL,
`plugin_classname` varchar(191) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `{site_prefix}product` (
`product_id` varchar(36) NOT NULL,
`product_title` varchar(191) NOT NULL,
`product_slug` varchar(191) NOT NULL,
`product_body` longtext DEFAULT NULL,
`product_author` varchar(191) NOT NULL,
`product_sku` varchar(191) NOT NULL,
`product_price` varchar(191) DEFAULT '0',
`product_currency` varchar(255) DEFAULT 'USD',
`product_purchase_url` varchar(191) DEFAULT NULL,
`product_show_in_menu` int(11) DEFAULT 0,
`product_show_in_search` int(11) DEFAULT 0,
`product_featured_image` varchar(191) DEFAULT NULL,
`product_status` varchar(36) NOT NULL DEFAULT 'draft',
`product_created` varchar(191) DEFAULT NULL,
`product_created_gmt` datetime DEFAULT NULL,
`product_published` varchar(191) DEFAULT NULL,
`product_published_gmt` datetime DEFAULT NULL,
`product_modified` varchar(191) DEFAULT '0000-00-00 00:00:00',
`product_modified_gmt` datetime DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `{site_prefix}productmeta` (
`meta_id` varchar(36) NOT NULL,
`product_id` varchar(191) DEFAULT NULL,
`meta_key` varchar(191) DEFAULT NULL,
`meta_value` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `{site_prefix}content`
ADD PRIMARY KEY (`content_id`),
ADD UNIQUE KEY `{site_prefix}contentId` (`content_id`),
ADD KEY `{site_prefix}contentIndex` (`content_slug`,`content_type`,`content_parent`),
ADD KEY `{site_prefix}contentAuthor` (`content_author`),
ADD KEY `{site_prefix}contentTypeSlug` (`content_type`),
ADD KEY `{site_prefix}contentParent` (`content_parent`);

ALTER TABLE `{site_prefix}contentmeta`
ADD PRIMARY KEY (`meta_id`),
ADD UNIQUE KEY `{site_prefix}contentMetaId` (`meta_id`),
ADD UNIQUE KEY `{site_prefix}contentMetaIndex` (`content_id`,`meta_key`);

ALTER TABLE `{site_prefix}content_type`
ADD PRIMARY KEY (`content_type_id`),
ADD UNIQUE KEY `{site_prefix}contentTypeId` (`content_type_id`),
ADD KEY `{site_prefix}contentTypeIndex` (`content_type_slug`);

ALTER TABLE `{site_prefix}elfinder_file`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `parent_name` (`parent_id`,`name`),
ADD KEY `parent_id` (`parent_id`);

ALTER TABLE `{site_prefix}elfinder_trash`
ADD PRIMARY KEY (`id`),
ADD UNIQUE KEY `parent_name` (`parent_id`,`name`),
ADD KEY `parent_id` (`parent_id`);

ALTER TABLE `{site_prefix}event_store`
ADD PRIMARY KEY (`event_id`),
ADD UNIQUE KEY `{site_prefix}eventId` (`event_id`),
ADD UNIQUE KEY `{site_prefix}domainEvent` (`aggregate_id`,`aggregate_type`,`aggregate_playhead`);

ALTER TABLE `{site_prefix}option`
ADD PRIMARY KEY (`option_id`),
ADD UNIQUE KEY `{site_prefix}optionId` (`option_id`),
ADD UNIQUE KEY `{site_prefix}optionIndex` (`option_key`);

ALTER TABLE `{site_prefix}plugin`
ADD PRIMARY KEY (`plugin_id`),
ADD UNIQUE KEY `{site_prefix}pluginId` (`plugin_id`),
ADD UNIQUE KEY `{site_prefix}pluginIndex` (`plugin_classname`);

ALTER TABLE `{site_prefix}product`
ADD PRIMARY KEY (`product_id`),
ADD UNIQUE KEY `{site_prefix}productId` (`product_id`),
ADD KEY `{site_prefix}productIndex` (`product_slug`,`product_sku`),
ADD KEY `{site_prefix}productAuthor` (`product_author`);

ALTER TABLE `{site_prefix}productmeta`
ADD PRIMARY KEY (`meta_id`),
ADD UNIQUE KEY `{site_prefix}productMetaId` (`meta_id`),
ADD UNIQUE KEY `{site_prefix}productMetaIndex` (`product_id`,`meta_key`);

ALTER TABLE `{site_prefix}elfinder_file`
MODIFY `id` int(7) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `{site_prefix}elfinder_trash`
MODIFY `id` int(7) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `{site_prefix}content`
ADD CONSTRAINT `{site_prefix}contentAuthor` FOREIGN KEY (`content_author`) REFERENCES `{base_prefix}user` (`user_id`),
ADD CONSTRAINT `{site_prefix}contentParent` FOREIGN KEY (`content_parent`) REFERENCES `{site_prefix}content` (`content_id`),
ADD CONSTRAINT `{site_prefix}contentTypeSlug` FOREIGN KEY (`content_type`) REFERENCES `{site_prefix}content_type` (`content_type_slug`);

ALTER TABLE `{site_prefix}contentmeta`
ADD CONSTRAINT `{site_prefix}contentIdMeta` FOREIGN KEY (`content_id`) REFERENCES `{site_prefix}content` (`content_id`);

ALTER TABLE `{site_prefix}product`
ADD CONSTRAINT `{site_prefix}productAuthor` FOREIGN KEY (`product_author`) REFERENCES `{base_prefix}user` (`user_id`);

ALTER TABLE `{site_prefix}productmeta`
ADD CONSTRAINT `{site_prefix}productIdMeta` FOREIGN KEY (`product_id`) REFERENCES `{site_prefix}product` (`product_id`);
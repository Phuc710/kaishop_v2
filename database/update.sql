-- SQL Update for KaiShop v2
ALTER TABLE `setting` 
ADD COLUMN `support_tele` VARCHAR(255) DEFAULT NULL COMMENT 'Link Telegram hỗ trợ khách hàng' AFTER `telegram_maintenance_message`,
ADD COLUMN `discord_admin` VARCHAR(100) DEFAULT NULL COMMENT 'Username Discord hỗ trợ' AFTER `support_tele`;


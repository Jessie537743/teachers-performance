-- Runs once on first container init (when /var/lib/mysql is empty).
-- Provisions the central (multi-tenancy control plane) database alongside
-- the default teachers_performance DB created from MYSQL_DATABASE, and
-- grants the application user full access to it.
--
-- For existing volumes this script is a no-op; apply the same statements
-- manually if upgrading an existing install.

CREATE DATABASE IF NOT EXISTS `central`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON `central`.* TO 'tp_user'@'%';
FLUSH PRIVILEGES;

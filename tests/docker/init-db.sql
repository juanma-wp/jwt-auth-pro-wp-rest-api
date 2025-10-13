-- Initialize additional databases for cross-origin testing
CREATE DATABASE IF NOT EXISTS wordpress_api;
GRANT ALL PRIVILEGES ON wordpress_api.* TO 'wordpress'@'%';
FLUSH PRIVILEGES;

-- AuditEngine Database Schema
-- Standard MySQL / MariaDB DDL

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(100) NOT NULL COMMENT 'UserRegistered, ProductPriceChanged, ContentPublished, dll',
    entity_type VARCHAR(50) NOT NULL COMMENT 'user, product, content, cart, role',
    entity_id VARCHAR(100) NOT NULL COMMENT 'ID entitas yang mengalami perubahan',
    actor_id BIGINT UNSIGNED NULL COMMENT 'ID user yang melakukan aksi',
    old_values JSON NULL COMMENT 'Snapshot data sebelum perubahan',
    new_values JSON NULL COMMENT 'Snapshot data setelah perubahan',
    ip_address VARCHAR(45) NULL COMMENT 'IP v4/v6 penggagas aksi',
    user_agent VARCHAR(255) NULL COMMENT 'Browser/Client user agent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_actor (actor_id),
    INDEX idx_event (event_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

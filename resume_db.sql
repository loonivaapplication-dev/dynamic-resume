CREATE DATABASE IF NOT EXISTS resume_db
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    USE resume_db;

CREATE TABLE users (
    id INT UNSIGNED NOT NULL, AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    headline VARCHAR(150) NOT NULL,
    summary     TEXT         NOT NULL,
    email       VARCHAR(120) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    avatar_path VARCHAR(255) NULL DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE resume_entries (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    entry_type   ENUM('experience','education','project') NOT NULL DEFAULT 'experience',
    title        VARCHAR(120) NOT NULL,
    organization VARCHAR(120) NOT NULL,
    start_date   DATE NOT NULL,
    end_date     DATE NULL DEFAULT NULL,
    description  TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: replace the hash below with the output of make_hash.php (see next step)
INSERT INTO users (full_name, headline, summary, email, password) VALUES
('Your Name', 'CS Undergraduate', 'I love building web apps.',
 'you@example.com', '$2y$12$nRLwirOEH7raIZsv86ui9.54agiA63T8OQNXP4uiUAJW6S2BL0Mju');

INSERT INTO resume_entries (user_id, entry_type, title, organization, start_date, end_date, description) VALUES
(1,'education','B.S. Intelligence Computing','Your University','2025-03-01',NULL,'Web, databases, AI.'),
(1,'project','Static CV','Web Programming I','2026-03-01','2026-06-01','HTML5/CSS3 resume.');
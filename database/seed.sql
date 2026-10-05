-- Testovací data (jen pro vývoj). Spusť až po schema.sql.
-- Testovací admin:  admin@casting.local  /  Admin123!   (na ostrém serveru smazat!)
USE casting_db;

INSERT INTO users (email, password_hash, first_name, last_name, role) VALUES
('admin@casting.local', '$2y$10$WjfS7UpRJ5gOnMD7afPPzONxyyMdJ3kLWZiNMqMyPSgTCgozjHpRe', 'Admin', 'Test', 'admin');

INSERT INTO castings (title, description, location, casting_date, status, created_by) VALUES
('Reklama na kávu', 'Hledáme tváře do televizní reklamy. Věk 25-40 let.', 'Praha', '2026-11-15 10:00:00', 'open', 1),
('Studentský film "Návrat"', 'Casting do krátkého studentského filmu, hlavní i vedlejší role.', 'Pardubice', '2026-11-22 14:00:00', 'open', 1),
('Divadelní hra - podzim', 'Amatérské divadlo hledá herce do nové inscenace.', 'Brno', '2026-10-30 18:00:00', 'closed', 1);

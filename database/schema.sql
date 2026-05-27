CREATE DATABASE IF NOT EXISTS nexus_sla
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE nexus_sla;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(40) NOT NULL DEFAULT 'user',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS priorities (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(40) NOT NULL UNIQUE,
  slug VARCHAR(40) NOT NULL UNIQUE,
  color VARCHAR(20) NOT NULL,
  sla_hours INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS statuses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(40) NOT NULL UNIQUE,
  slug VARCHAR(40) NOT NULL UNIQUE,
  position TINYINT UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS demands (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  description TEXT NOT NULL,
  client_sector VARCHAR(140) NOT NULL,
  responsible_id INT UNSIGNED NULL,
  priority_id INT UNSIGNED NOT NULL,
  status_id INT UNSIGNED NOT NULL,
  opened_at DATETIME NOT NULL,
  due_at DATETIME NOT NULL,
  finished_at DATETIME NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_demands_responsible FOREIGN KEY (responsible_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_demands_priority FOREIGN KEY (priority_id) REFERENCES priorities(id),
  CONSTRAINT fk_demands_status FOREIGN KEY (status_id) REFERENCES statuses(id)
) ENGINE=InnoDB;

-- Inserir usuários com senhas bcrypt
-- Senhas: 
--   admin / Kirashi@2026 / Hash: $2y$10$Zr7MtO2QDud6WvHdho7MZ.hksfWxiPE3ytYvcwQgT.nQNmmIiE06G
--   usuario / Demandas@2026 / Hash: $2y$10$CTKLjqLewrUJmo6Llf847.SIqZl0I95DZ5Gos6MZQF/QiMsMC3tHG
--   gestor / Gestor@2026 / Hash: $2y$10$m9gsmEpcGfh44IqpyEMAsOAuhAKl4ktn6/TJjpzOdt1ybxG0PrFA6
--   rh / RH@2026 / Hash: $2y$10$VT6o.Qs0JEGApdP9GwGj8.gfIQXHNEK4as3bC4RoP5dC69jXO/8S6

INSERT INTO users (name, email, password, role, active) VALUES
('kirashi_natsu', 'kirashi_natsu@administrator.local', '$2y$10$Zr7MtO2QDud6WvHdho7MZ.hksfWxiPE3ytYvcwQgT.nQNmmIiE06G', 'admin', 1),
('Recursos Humanos', 'rh@nexus.local', '$2y$10$VT6o.Qs0JEGApdP9GwGj8.gfIQXHNEK4as3bC4RoP5dC69jXO/8S6', 'rh', 1),
('Usuario Operacional', 'usuario@nexus.local', '$2y$10$CTKLjqLewrUJmo6Llf847.SIqZl0I95DZ5Gos6MZQF/QiMsMC3tHG', 'usuario', 1),
('Gestor de Demandas', 'gestor@nexus.local', '$2y$10$m9gsmEpcGfh44IqpyEMAsOAuhAKl4ktn6/TJjpzOdt1ybxG0PrFA6', 'gestor', 1)
ON DUPLICATE KEY UPDATE email = email;

INSERT INTO priorities (name, slug, color, sla_hours) VALUES
('Baixo', 'baixo', '#22c55e', 72),
('Médio', 'medio', '#38bdf8', 48),
('Médio Alto', 'medio-alto', '#f59e0b', 24),
('Alto', 'alto', '#ef4444', 8),
('Super Alto', 'super-alto', '#a855f7', 2)
ON DUPLICATE KEY UPDATE color = VALUES(color), sla_hours = VALUES(sla_hours);

INSERT INTO statuses (name, slug, position) VALUES
('Aberto', 'aberto', 1),
('Em andamento', 'em-andamento', 2),
('Pausado', 'pausado', 3),
('Finalizado', 'finalizado', 4),
('Atrasado', 'atrasado', 5)
ON DUPLICATE KEY UPDATE position = VALUES(position);


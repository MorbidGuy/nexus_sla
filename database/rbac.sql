USE nexus_sla;

CREATE TABLE IF NOT EXISTS roles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(60) NOT NULL UNIQUE,
  parent_id INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_roles_parent FOREIGN KEY (parent_id) REFERENCES roles(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permissions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  module VARCHAR(60) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS role_permissions (
  role_id INT UNSIGNED NOT NULL,
  permission_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_roles (
  user_id INT UNSIGNED NOT NULL,
  role_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, role_id),
  CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_user_id INT UNSIGNED NULL,
  event_type VARCHAR(80) NOT NULL,
  entity_type VARCHAR(80) NULL,
  entity_id VARCHAR(80) NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  context JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_actor_created (actor_user_id, created_at),
  INDEX idx_audit_event_created (event_type, created_at),
  CONSTRAINT fk_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO roles (name, slug) VALUES
('Visitante', 'visitante'),
('Colaborador', 'colaborador'),
('Operador', 'operador'),
('Producao', 'producao'),
('Engenharia/TI', 'engenharia_ti'),
('RH', 'rh'),
('Gestor', 'gestor'),
('Auditor', 'auditor'),
('Administrador', 'administrador')
ON DUPLICATE KEY UPDATE name = VALUES(name);

UPDATE roles child
JOIN roles parent ON parent.slug = 'visitante'
SET child.parent_id = parent.id
WHERE child.slug = 'colaborador';

UPDATE roles child
JOIN roles parent ON parent.slug = 'colaborador'
SET child.parent_id = parent.id
WHERE child.slug IN ('operador', 'engenharia_ti', 'rh', 'auditor');

UPDATE roles child
JOIN roles parent ON parent.slug = 'operador'
SET child.parent_id = parent.id
WHERE child.slug = 'producao';

UPDATE roles child
JOIN roles parent ON parent.slug = 'producao'
SET child.parent_id = parent.id
WHERE child.slug = 'gestor';

UPDATE roles child
JOIN roles parent ON parent.slug = 'gestor'
SET child.parent_id = parent.id
WHERE child.slug = 'administrador';

INSERT INTO permissions (name, slug, module) VALUES
('Visualizar dashboard', 'dashboard.view', 'core'),
('Visualizar banco de dados', 'database.view', 'admin'),
('Gerenciar usuarios', 'users.manage', 'admin'),
('Visualizar demandas', 'demands.view', 'sla'),
('Criar demandas', 'demands.create', 'sla'),
('Gerenciar demandas', 'demands.manage', 'sla'),
('Atuar em demandas', 'demands.work', 'sla'),
('Mover kanban', 'kanban.move', 'sla'),
('Visualizar auditoria', 'audit.view', 'audit'),
('Visualizar RH', 'hr.view', 'hr'),
('Gerenciar RH', 'hr.manage', 'hr'),
('Visualizar producao', 'production.view', 'production'),
('Gerenciar producao', 'production.manage', 'production'),
('Visualizar Engenharia/TI', 'it.view', 'it'),
('Gerenciar Engenharia/TI', 'it.manage', 'it')
ON DUPLICATE KEY UPDATE name = VALUES(name), module = VALUES(module);

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.slug IN ('dashboard.view')
WHERE r.slug = 'visitante';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.slug IN ('demands.view')
WHERE r.slug = 'colaborador';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.slug IN ('demands.create', 'demands.work', 'kanban.move')
WHERE r.slug IN ('operador', 'producao');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.slug IN ('hr.view', 'hr.manage')
WHERE r.slug = 'rh';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.slug IN ('it.view', 'it.manage')
WHERE r.slug = 'engenharia_ti';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.slug IN ('demands.manage', 'users.manage', 'database.view')
WHERE r.slug = 'gestor';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.slug IN ('audit.view')
WHERE r.slug = 'auditor';

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.slug = 'administrador';

INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN roles r ON r.slug = CASE
  WHEN u.role IN ('admin', 'administrator') THEN 'administrador'
  WHEN u.role IN ('gestor', 'manager') THEN 'gestor'
  WHEN u.role IN ('rh', 'user_manager') THEN 'rh'
  WHEN u.role IN ('usuario', 'demand_operator') THEN 'operador'
  ELSE 'colaborador'
END;

CREATE INDEX IF NOT EXISTS idx_users_role_active ON users(role, active);
CREATE INDEX IF NOT EXISTS idx_demands_responsible_status ON demands(responsible_id, status_id);
CREATE INDEX IF NOT EXISTS idx_demands_due_status ON demands(due_at, status_id);
CREATE INDEX IF NOT EXISTS idx_demands_created ON demands(created_at);

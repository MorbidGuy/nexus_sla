USE nexus_sla;

DELETE FROM demands;
DELETE FROM users;

INSERT INTO users (name, email, password, role, active) VALUES
('Administrador', 'admin@nexus.local', '$2y$10$Zr7MtO2QDud6WvHdho7MZ.hksfWxiPE3ytYvcwQgT.nQNmmIiE06G', 'admin', 1),
('Gestor', 'gestor@nexus.local', '$2y$10$m9gsmEpcGfh44IqpyEMAsOAuhAKl4ktn6/TJjpzOdt1ybxG0PrFA6', 'gestor', 1);

SELECT id, name, email, role, active FROM users ORDER BY id;


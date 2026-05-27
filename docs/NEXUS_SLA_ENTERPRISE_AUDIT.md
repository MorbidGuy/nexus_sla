# Nexus SLA - Auditoria Enterprise

Data: 2026-05-27

## Sumario executivo

O Nexus SLA possui uma base MVC PHP funcional para controle interno de demandas, com bons sinais iniciais de seguranca: PDO com prepared statements, `password_hash`, `password_verify`, CSRF em formularios, headers HTTP, `HttpOnly`, `SameSite`, expiracao de sessao e throttle basico.

O principal risco arquitetural estava no RBAC hardcoded, sem tabelas, sem heranca, sem cache e com autorizacao espalhada entre controllers e views. Tambem havia endpoints com exposicao excessiva, rota usada pelo frontend sem implementacao, validacoes incompletas e infraestrutura Docker adequada para desenvolvimento, mas ainda distante de producao enterprise.

## Correcoes implementadas nesta etapa

- Criado `app/models/Rbac.php` com permissoes por banco, heranca recursiva de papeis, cache em memoria por request e fallback compativel com os papeis legados.
- Criado `database/rbac.sql` com tabelas `roles`, `permissions`, `role_permissions`, `user_roles`, `audit_logs`, seeds dos papeis obrigatorios e indices essenciais.
- Atualizado `BaseController::can()` para normalizar permissoes antigas e consultar RBAC centralizado.
- Criada rota `GET demands/api_latest`, usada pelo frontend mas inexistente.
- Implementado `DemandController::latest()` respeitando visibilidade por papel.
- Protegido `DemandController::details()` contra leitura de demandas de outros operadores.
- Adicionada validacao de prioridade/status antes de criar ou atualizar demandas.
- Adicionados metodos `priorityExists()`, `statusExists()` e `latestAssignedId()` no model `Demand`.
- Removida exposicao publica de `APP_ENV` e `PHP_VERSION` em `public/api/status.php`.
- Alterado `.env` local para `APP_DEBUG=0`.

## Vulnerabilidades e riscos encontrados

| Area | Problema | Risco | Solucao ideal |
|---|---|---|---|
| RBAC | Permissoes hardcoded no controller e duplicadas na view | Escalada de privilegio, menus inconsistentes, dificil auditoria | RBAC por tabela, middleware e helpers de view |
| API/status | Exposicao publica de ambiente e versao PHP | Fingerprinting para ataque direcionado | Health check publico minimo e health interno autenticado |
| Debug | `.env` estava com `APP_DEBUG=1` | Vazamento de stack trace, DSN e detalhes internos | Debug sempre `0` em producao |
| Segredos | `.env` contem senha real simples | Vazamento por backup/zip/git | Rotacionar senha, usar secret manager e nunca versionar `.env` |
| Demand details | Endpoint autenticado, mas sem escopo por responsavel | Operador poderia consultar demanda fora do escopo por ID | Checagem por RBAC e ownership |
| Validacao | IDs de prioridade/status aceitos sem existencia previa | Erro fatal, dados invalidos ou manipulacao de fluxo | Validadores centralizados e constraints |
| Rate limit | JSON em arquivo local | Concorrencia, crescimento indefinido, nao escala horizontal | Redis com TTL e chaves por rota/IP/usuario |
| Auditoria | Logs em arquivo texto sem integridade transacional | Auditoria incompleta e dificil consulta | `audit_logs` transacional e trilha por entidade |
| Docker | Banco exposto em `3306:3306` | Superficie de ataque na rede local/host | Expor DB apenas internamente ou via perfil dev |
| Banco | Poucos indices em `demands` | Lentidao em SLA, dashboard e kanban | Indices por responsavel/status/due/created |

## Arquitetura ideal

```text
app/
  Core/
    Http/Request.php
    Http/Response.php
    Middleware/AuthMiddleware.php
    Middleware/PermissionMiddleware.php
    Exceptions/Handler.php
  Domain/
    SLA/
      DTO/
      Services/
      Repositories/
      Events/
    Identity/
      Services/
      Repositories/
      RBAC/
    Production/
    HR/
    IT/
  Infrastructure/
    Database/
    Cache/
    Queue/
    Logging/
  controllers/
  views/
config/
database/
  migrations/
  seeders/
public/
routes/
tests/
```

## Roadmap backend

1. Introduzir `Request`, `Response`, middleware pipeline e exception handler global.
2. Separar controller fino, service layer com regra de negocio e repository com SQL.
3. Criar DTOs para `CreateDemandDTO`, `UpdateDemandDTO`, `CreateUserDTO`.
4. Padronizar JSON: `{ "success": true, "data": ..., "error": null }`.
5. Implementar paginacao, filtros por status/prioridade/responsavel/SLA e busca.
6. Criar eventos: `DemandCreated`, `DemandAssigned`, `DemandOverdue`, `UserRoleChanged`.
7. Criar notificacoes e filas para e-mail, WebSocket e alertas SLA.

## Roadmap seguranca

1. Aplicar `database/rbac.sql` e migrar telas para `can('permissao.slug')`.
2. Rotacionar `DB_PASS`, remover `.env` de qualquer versionamento e manter apenas `.env.example`.
3. Migrar rate limit para Redis.
4. Criar bloqueio progressivo de login por IP, e-mail e usuario.
5. Persistir auditoria em `audit_logs` para login, logout, CRUD, permissao negada e exportacoes.
6. Adicionar cookies com nome customizado, `SameSite=Strict` onde aplicavel e timeout absoluto.
7. Separar API web session de API JWT/OAuth para integracoes.

## Roadmap banco de dados

1. Criar migrations versionadas.
2. Adicionar `companies` e `company_id` para multiempresa em usuarios, demandas e modulos.
3. Criar tabelas de historico: `demand_events`, `demand_comments`, `attachments`.
4. Criar constraints de integridade e indices compostos por tenant.
5. Evitar `ORDER BY RAND()` em producao; usar estrategia por contagem/offset ou fila de distribuicao.

## Roadmap DevOps

1. Separar compose de desenvolvimento e producao.
2. Remover exposicao publica do MariaDB em producao.
3. Criar health checks para app e banco.
4. Adicionar Nginx/Apache hardened com HTTPS, HSTS e logs estruturados.
5. Criar GitHub Actions para lint, testes, build Docker e scan de vulnerabilidades.
6. Adicionar backup automatico testado com restore.
7. Adicionar Redis para cache, sessoes, rate limit e filas.

## Roadmap frontend

1. Remover scripts inline para cumprir CSP sem `unsafe-inline`.
2. Criar componentes reutilizaveis para tabela, filtros, modal, toast e cards.
3. Implementar dark/light mode real por preferencia de usuario.
4. Adicionar busca global, filtros salvos, exportacao PDF/Excel e dashboard analitico.
5. Criar timeline visual de demanda usando `demand_events`.

## Modulos futuros

### Producao

- `production_orders`, `machines`, `maintenance_orders`, `machine_failures`, `operational_checklists`, `parts_inventory`, `production_kpis`.

### RH

- `employees`, `work_shifts`, `time_bank_entries`, `warnings`, `vacations`, `medical_certificates`, `recruitment_candidates`, `employee_documents`, `digital_signatures`.

### Engenharia/TI

- `it_tickets`, `assets`, `hardware_inventory`, `software_licenses`, `network_devices`, `monitoring_events`, `knowledge_base_articles`, `technical_sla_policies`.

## Comandos recomendados

Aplicar RBAC no banco:

```bash
mysql -h db -u nexus -p nexus_sla < database/rbac.sql
```

Validar PHP dentro do container:

```bash
docker compose exec app php -l app/models/Rbac.php
docker compose exec app php -l app/controllers/BaseController.php
docker compose exec app php -l app/controllers/DemandController.php
docker compose exec app php -l app/models/Demand.php
```

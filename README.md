# Nexus SLA

Aplicacao PHP 8.2 para controle de demandas e SLA, preparada para deploy em Render/Railway usando Docker.

## Deploy rapido no Render

1. Envie o projeto ao GitHub.
2. No Render, crie um Web Service a partir do repositorio.
3. Use Docker e mantenha o `render.yaml`.
4. Configure `DATABASE_URL` com a URL externa do MySQL/MariaDB.
5. No primeiro acesso, defina temporariamente `APP_SETUP_ENABLED=1`, acesse `/setup`, crie o administrador e volte para `APP_SETUP_ENABLED=0`.

## Variaveis principais

Use `DATABASE_URL` ou as variaveis `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASS`.
Para bancos externos que exigem TLS, mantenha `DB_SSL_MODE=required` e informe `DB_SSL_CA` quando o provedor fornecer um certificado CA.

Veja todas as chaves em `.env.example`.

## Local

Copie `.env.example` para `.env`, preencha as credenciais e rode:

```sh
sh RUN.sh
```

No Windows, use `RUN.bat`.

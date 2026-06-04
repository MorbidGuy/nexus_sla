<section class="login-wrap">
    <div class="login-panel">
        <div class="brand mb-4">Nexus <span>SLA</span></div>
        <h1>Entrar</h1>
        <p class="text-secondary">Acesse o painel de demandas corporativas.</p>

        <form method="post" action="index.php?route=login" class="mt-4">
            <?= $csrfField ?? '' ?>
            <div class="mb-3">
                <label class="form-label">E-mail</label>
                <input type="email" name="email" class="form-control" value="" autocomplete="username" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Senha</label>
                <input type="password" name="password" class="form-control" value="" autocomplete="current-password" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">Acessar sistema</button>
        </form>

        <?php if (!empty($bootstrapEnabled)): ?>
            <hr class="border-secondary my-4">
            <h2 class="h5">Criar usuario inicial</h2>
            <form method="post" action="index.php?route=login/bootstrap-user" class="mt-3">
                <?= $csrfField ?? '' ?>
                <div class="mb-3">
                    <label class="form-label">Nome</label>
                    <input type="text" name="name" class="form-control" autocomplete="name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">E-mail</label>
                    <input type="email" name="email" class="form-control" autocomplete="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Senha autorizada</label>
                    <input type="password" name="password" class="form-control" autocomplete="new-password" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Perfil</label>
                    <select name="role" class="form-select">
                        <option value="admin">Administrador</option>
                        <option value="gestor">Gestor</option>
                        <option value="usuario">Usuario operacional</option>
                    </select>
                </div>
                <button class="btn btn-outline-light w-100" type="submit">Criar ou atualizar usuario</button>
            </form>
        <?php endif; ?>
    </div>
</section>

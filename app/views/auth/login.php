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

        <div class="manager-signup mt-4">
            <button class="manager-signup-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#managerSignup" aria-expanded="false" aria-controls="managerSignup">
                <span>Novo usuario de gestor</span>
                <small>Criar acesso com e-mail e senha</small>
            </button>

            <div class="collapse" id="managerSignup">
                <form method="post" action="index.php?route=login/manager-user" class="manager-signup-form">
                    <?= $csrfField ?? '' ?>
                    <div class="mb-3">
                        <label class="form-label">Nome do gestor</label>
                        <input type="text" name="name" class="form-control" autocomplete="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-mail do gestor</label>
                        <input type="email" name="email" class="form-control" autocomplete="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Senha de acesso</label>
                        <input type="password" name="password" class="form-control" autocomplete="new-password" minlength="12" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmar senha de acesso</label>
                        <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password" minlength="12" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Senha de confirmacao</label>
                        <input type="password" name="manager_confirmation_password" class="form-control" autocomplete="off" required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Criar usuario gestor</button>
                </form>
            </div>
        </div>
    </div>
</section>

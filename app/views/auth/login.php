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
    </div>
</section>

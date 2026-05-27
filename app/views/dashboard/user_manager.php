<div class="page-heading">
    <div>
        <span class="eyebrow"><?= htmlspecialchars($roleLabel) ?></span>
        <h1>Painel de usuários</h1>
    </div>
    <a href="index.php?route=users/create" class="btn btn-primary">Novo usuário</a>
</div>

<section class="content-panel">
    <div class="panel-title">
        <h2>Usuários cadastrados</h2>
        <a href="index.php?route=users">Gerenciar usuários</a>
    </div>
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead>
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Perfil</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= htmlspecialchars($item['email']) ?></td>
                    <td><span class="status-pill"><?= htmlspecialchars($item['role']) ?></span></td>
                    <td><?= (int) $item['active'] ? 'Ativo' : 'Inativo' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>


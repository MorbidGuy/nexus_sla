<div class="page-heading">
    <div>
        <span class="eyebrow">Controle de acesso</span>
        <h1>Usuários</h1>
    </div>
    <a href="index.php?route=users/create" class="btn btn-primary">Novo usuário</a>
</div>

<section class="content-panel">
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead>
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Perfil</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= htmlspecialchars($item['email']) ?></td>
                    <td><span class="status-pill"><?= htmlspecialchars($item['role']) ?></span></td>
                    <td><?= (int) $item['active'] ? 'Ativo' : 'Inativo' ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-light" href="index.php?route=users/edit&id=<?= (int) $item['id'] ?>">Editar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>


<?php
$isEdit = !empty($managedUser);
$action = $isEdit ? 'users/update' : 'users/store';
?>
<div class="page-heading">
    <div>
        <span class="eyebrow">Controle de acesso</span>
        <h1><?= htmlspecialchars($title) ?></h1>
    </div>
    <a href="index.php?route=users" class="btn btn-outline-light">Voltar</a>
</div>

<section class="content-panel">
    <form method="post" action="index.php?route=<?= $action ?>" class="row g-3">
        <?= $csrfField ?? '' ?>
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $managedUser['id'] ?>">
        <?php endif; ?>

        <div class="col-md-6">
            <label class="form-label">Nome</label>
            <input name="name" class="form-control" required value="<?= htmlspecialchars($managedUser['name'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">E-mail</label>
            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($managedUser['email'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label"><?= $isEdit ? 'Nova senha' : 'Senha' ?></label>
            <input type="password" name="password" class="form-control" <?= $isEdit ? '' : 'required' ?>>
        </div>
        <div class="col-md-4">
            <label class="form-label">Perfil</label>
            <select name="role" class="form-select" required>
                <?php foreach ($roles as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value) ?>" <?= ($managedUser['role'] ?? '') === $value ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <label class="form-check">
                <input type="checkbox" name="active" class="form-check-input" <?= (int) ($managedUser['active'] ?? 1) === 1 ? 'checked' : '' ?>>
                <span class="form-check-label">Ativo</span>
            </label>
        </div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Salvar</button>
            <?php if ($isEdit): ?>
                <button class="btn btn-outline-danger" form="delete-user-form" type="submit">Excluir</button>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($isEdit): ?>
        <form id="delete-user-form" method="post" action="index.php?route=users/delete" onsubmit="return confirm('Excluir este usuario?')">
            <?= $csrfField ?? '' ?>
            <input type="hidden" name="id" value="<?= (int) $managedUser['id'] ?>">
        </form>
    <?php endif; ?>
</section>

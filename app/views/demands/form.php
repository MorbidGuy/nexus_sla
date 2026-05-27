<?php
$isEdit = !empty($demand);
$action = $isEdit ? 'demands/update' : 'demands/store';
?>
<div class="page-heading">
    <div>
        <span class="eyebrow">Demandas</span>
        <h1><?= htmlspecialchars($title) ?></h1>
    </div>
    <a href="index.php?route=demands" class="btn btn-outline-light">Voltar</a>
</div>

<section class="content-panel">
    <form method="post" action="index.php?route=<?= $action ?>" class="row g-3">
        <?= $csrfField ?? '' ?>
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $demand['id'] ?>">
        <?php endif; ?>

        <div class="col-md-8">
            <label class="form-label">Titulo</label>
            <input name="title" class="form-control" required value="<?= htmlspecialchars($demand['title'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Cliente/Setor</label>
            <input name="client_sector" class="form-control" required value="<?= htmlspecialchars($demand['client_sector'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="form-label">Descricao</label>
            <textarea name="description" rows="4" class="form-control" required><?= htmlspecialchars($demand['description'] ?? '') ?></textarea>
        </div>
        <div class="col-md-4">
            <label class="form-label">Responsavel</label>
            <select name="responsible_id" class="form-select">
                <option value="">Sem responsavel</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= (int) $user['id'] ?>" <?= (int) ($demand['responsible_id'] ?? 0) === (int) $user['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Prioridade</label>
            <select name="priority_id" class="form-select" required>
                <?php foreach ($priorities as $priority): ?>
                    <option value="<?= (int) $priority['id'] ?>" <?= (int) ($demand['priority_id'] ?? 0) === (int) $priority['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($priority['name']) ?> - <?= (int) $priority['sla_hours'] ?>h
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Status</label>
            <select name="status_id" class="form-select" required>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= (int) $status['id'] ?>" <?= (int) ($demand['status_id'] ?? 0) === (int) $status['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($status['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label">Observacoes</label>
            <textarea name="notes" rows="3" class="form-control"><?= htmlspecialchars($demand['notes'] ?? '') ?></textarea>
        </div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Salvar</button>
            <?php if ($isEdit): ?>
                <button class="btn btn-outline-danger" form="delete-form" type="submit">Excluir</button>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($isEdit): ?>
        <form id="delete-form" method="post" action="index.php?route=demands/delete" onsubmit="return confirm('Excluir esta demanda?')">
            <?= $csrfField ?? '' ?>
            <input type="hidden" name="id" value="<?= (int) $demand['id'] ?>">
        </form>
    <?php endif; ?>
</section>

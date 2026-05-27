<div class="page-heading">
    <div>
        <span class="eyebrow"><?= htmlspecialchars($roleLabel) ?></span>
        <h1>Operação de demandas</h1>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?route=kanban" class="btn btn-outline-light">Kanban</a>
        <a href="index.php?route=demands/create" class="btn btn-primary">Nova demanda</a>
    </div>
</div>

<?php require APP_ROOT . '/app/views/dashboard/partials/demand_stats.php'; ?>

<section class="content-panel mt-4">
    <div class="panel-title">
        <h2>Fila recente</h2>
        <a href="index.php?route=demands">Ver demandas</a>
    </div>
    <?php $canManageDemand = true; require APP_ROOT . '/app/views/demands/table.php'; ?>
</section>


<div class="page-heading">
    <div>
        <span class="eyebrow"><?= htmlspecialchars($roleLabel) ?></span>
        <h1>Visão gerencial</h1>
    </div>
    <a href="index.php?route=kanban" class="btn btn-outline-light">Ver Kanban</a>
</div>

<?php require APP_ROOT . '/app/views/dashboard/partials/demand_stats.php'; ?>

<section class="content-panel mt-4">
    <div class="panel-title">
        <h2>Status das demandas</h2>
        <a href="index.php?route=demands">Ver relatório</a>
    </div>
    <?php $canManageDemand = false; require APP_ROOT . '/app/views/demands/table.php'; ?>
</section>


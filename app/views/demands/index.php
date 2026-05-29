<div class="page-heading">
    <div>
        <span class="eyebrow">Gestao</span>
        <h1>Demandas</h1>
    </div>
    <?php if (!empty($canCreateDemand)): ?>
        <a href="index.php?route=demands/create" class="btn btn-primary">Nova demanda</a>
    <?php endif; ?>
</div>

<section class="content-panel">
    <?php require APP_ROOT . '/app/views/demands/table.php'; ?>
</section>

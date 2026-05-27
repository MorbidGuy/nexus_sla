<div class="page-heading">
    <div>
        <span class="eyebrow">Operação</span>
        <h1>Dashboard SLA</h1>
    </div>
    <a href="index.php?route=demands/create" class="btn btn-primary">Nova demanda</a>
</div>

<div class="stats-grid">
    <div class="metric-card"><small>Total</small><strong><?= (int) $stats['total'] ?></strong></div>
    <div class="metric-card danger"><small>Atrasadas</small><strong><?= (int) $stats['overdue'] ?></strong></div>
    <div class="metric-card warning"><small>Em andamento</small><strong><?= (int) $stats['in_progress'] ?></strong></div>
    <div class="metric-card success"><small>Finalizadas</small><strong><?= (int) $stats['finished'] ?></strong></div>
    <div class="metric-card purple"><small>Críticas</small><strong><?= (int) $stats['critical'] ?></strong></div>
</div>

<section class="content-panel mt-4">
    <div class="panel-title">
        <h2>Visão rápida</h2>
    </div>
    <div class="chart-bars">
        <?php foreach ([
            'Atrasadas' => (int) $stats['overdue'],
            'Andamento' => (int) $stats['in_progress'],
            'Finalizadas' => (int) $stats['finished'],
            'Críticas' => (int) $stats['critical'],
        ] as $label => $value): ?>
            <?php $height = max(8, min(100, $value * 18)); ?>
            <div class="chart-item">
                <div class="bar" style="height: <?= $height ?>%"></div>
                <span><?= $label ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="content-panel mt-4">
    <div class="panel-title">
        <h2>Demandas recentes</h2>
        <a href="index.php?route=demands">Ver todas</a>
    </div>
    <?php require APP_ROOT . '/app/views/demands/table.php'; ?>
</section>


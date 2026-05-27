<div class="stats-grid">
    <div class="metric-card"><small>Total</small><strong><?= (int) $stats['total'] ?></strong></div>
    <div class="metric-card danger"><small>Atrasadas</small><strong><?= (int) $stats['overdue'] ?></strong></div>
    <div class="metric-card warning"><small>Em andamento</small><strong><?= (int) $stats['in_progress'] ?></strong></div>
    <div class="metric-card success"><small>Finalizadas</small><strong><?= (int) $stats['finished'] ?></strong></div>
    <div class="metric-card purple"><small>Críticas</small><strong><?= (int) $stats['critical'] ?></strong></div>
</div>


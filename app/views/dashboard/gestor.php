<?php
$formatBytes = static function (int $bytes): string {
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return number_format($bytes / 1024, 2, ',', '.') . ' KB';
    }
    if ($bytes < 1073741824) {
        return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
    }
    return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
};
?>
<div class="page-heading">
    <div>
        <span class="eyebrow"><?= htmlspecialchars($roleLabel) ?></span>
        <h1>Gestao geral</h1>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?route=users" class="btn btn-outline-light">Usuarios</a>
        <a href="index.php?route=kanban" class="btn btn-outline-light">Kanban</a>
        <a href="index.php?route=demands/create" class="btn btn-primary">Enviar demanda</a>
    </div>
</div>

<?php if (!empty($diskStats['is_warning'])): ?>
    <div class="alert alert-warning border-0 shadow-sm">
        Atencao: o disco esta com <?= number_format((float) ($diskStats['used_percent'] ?? 0), 2, ',', '.') ?>% de uso.
        Espaco disponivel: <?= $formatBytes((int) ($diskStats['free_bytes'] ?? 0)) ?>.
    </div>
<?php endif; ?>

<?php require APP_ROOT . '/app/views/dashboard/partials/demand_stats.php'; ?>

<div class="stats-grid mt-4">
    <div class="metric-card"><small>Tabelas</small><strong><?= (int) ($storageStats['tables_count'] ?? 0) ?></strong></div>
    <div class="metric-card"><small>Registros</small><strong><?= (int) ($storageStats['rows_count'] ?? 0) ?></strong></div>
    <div class="metric-card success"><small>Disco livre</small><strong><?= $formatBytes((int) ($diskStats['free_bytes'] ?? 0)) ?></strong></div>
    <div class="metric-card warning"><small>Disco usado</small><strong><?= number_format((float) ($diskStats['used_percent'] ?? 0), 1, ',', '.') ?>%</strong></div>
    <div class="metric-card purple"><small>Usuarios</small><strong><?= count($users) ?></strong></div>
</div>

<section class="content-panel mt-4">
    <div class="panel-title">
        <h2>Visualizar dashboard de usuario</h2>
    </div>
    <form method="get" action="index.php" class="row g-3 align-items-end">
        <input type="hidden" name="route" value="dashboard">
        <div class="col-md-8">
            <label class="form-label">Usuario</label>
            <select name="user_id" class="form-select" required>
                <option value="">Selecione um usuario</option>
                <?php foreach ($dashboardTargets as $target): ?>
                    <option value="<?= (int) $target['id'] ?>" <?= (int) ($_GET['user_id'] ?? 0) === (int) $target['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($target['name']) ?> - <?= htmlspecialchars($target['email']) ?> - <?= htmlspecialchars($target['role']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <button class="btn btn-primary w-100" type="submit">Abrir dashboard</button>
        </div>
    </form>
</section>

<?php if (!empty($selectedDashboardUser)): ?>
    <section class="content-panel mt-4">
        <div class="panel-title">
            <div>
                <h2>Dashboard de <?= htmlspecialchars($selectedDashboardUser['name']) ?></h2>
                <span class="text-secondary"><?= htmlspecialchars($selectedDashboardUser['email']) ?></span>
            </div>
            <a href="index.php?route=dashboard" class="btn btn-sm btn-outline-light">Limpar visao</a>
        </div>

        <?php $stats = $selectedDashboardStats; require APP_ROOT . '/app/views/dashboard/partials/demand_stats.php'; ?>

        <div class="mt-4">
            <div class="panel-title">
                <h2>Fila atribuida</h2>
            </div>
            <?php
            $demands = $selectedDashboardDemands;
            $canCreateDemand = true;
            $canManageDemand = true;
            $canWorkDemand = false;
            require APP_ROOT . '/app/views/demands/table.php';
            ?>
        </div>
    </section>
<?php elseif (isset($_GET['user_id']) && (int) $_GET['user_id'] > 0): ?>
    <div class="alert alert-warning border-0 shadow-sm mt-4">
        Usuario nao encontrado ou inativo.
    </div>
<?php endif; ?>

<section class="content-panel mt-4">
    <div class="panel-title">
        <h2>Demandas recentes</h2>
        <a href="index.php?route=demands">Ver todas</a>
    </div>
    <?php $canCreateDemand = true; $canManageDemand = true; require APP_ROOT . '/app/views/demands/table.php'; ?>
</section>

<?php
$dataBytes = (int) ($storageStats['data_bytes'] ?? 0);
$indexBytes = (int) ($storageStats['index_bytes'] ?? 0);
$totalBytes = $dataBytes + $indexBytes;
$formatBytes = static function (int $bytes): string {
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return number_format($bytes / 1024, 2, ',', '.') . ' KB';
    }
    return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
};
?>
<div class="page-heading">
    <div>
        <span class="eyebrow"><?= htmlspecialchars($roleLabel) ?></span>
        <h1>Saude do banco</h1>
    </div>
</div>

<?php if (!empty($diskStats['is_warning'])): ?>
    <div class="alert alert-warning border-0 shadow-sm">
        Atencao: o disco esta com <?= number_format((float) ($diskStats['used_percent'] ?? 0), 2, ',', '.') ?>% de uso.
        Espaco disponivel: <?= $formatBytes((int) ($diskStats['free_bytes'] ?? 0)) ?>.
    </div>
<?php endif; ?>

<div class="stats-grid">
    <div class="metric-card"><small>Tabelas</small><strong><?= (int) ($storageStats['tables_count'] ?? 0) ?></strong></div>
    <div class="metric-card"><small>Registros</small><strong><?= (int) ($storageStats['rows_count'] ?? 0) ?></strong></div>
    <div class="metric-card success"><small>Disco livre</small><strong><?= $formatBytes((int) ($diskStats['free_bytes'] ?? 0)) ?></strong></div>
    <div class="metric-card warning"><small>Disco usado</small><strong><?= number_format((float) ($diskStats['used_percent'] ?? 0), 1, ',', '.') ?>%</strong></div>
    <div class="metric-card purple"><small>Banco</small><strong><?= $formatBytes($totalBytes) ?></strong></div>
</div>

<section class="content-panel mt-4">
    <div class="panel-title">
        <h2>Armazenamento por tabela</h2>
    </div>
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead>
            <tr>
                <th>Tabela</th>
                <th>Registros</th>
                <th>Dados</th>
                <th>Indices</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($tableStats as $table): ?>
                <tr>
                    <td><?= htmlspecialchars($table['table_name']) ?></td>
                    <td><?= (int) ($table['rows_count'] ?? $table['table_rows'] ?? 0) ?></td>
                    <td><?= $formatBytes((int) ($table['data_bytes'] ?? $table['data_length'] ?? 0)) ?></td>
                    <td><?= $formatBytes((int) ($table['index_bytes'] ?? $table['index_length'] ?? 0)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

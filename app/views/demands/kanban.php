<div class="page-heading">
    <div>
        <span class="eyebrow">Fluxo</span>
        <h1>Kanban</h1>
    </div>
    <?php if (!empty($canMoveCards)): ?>
        <a href="index.php?route=demands/create" class="btn btn-primary">Nova demanda</a>
    <?php endif; ?>
</div>

<div class="kanban-board">
    <?php foreach ($statuses as $status): ?>
        <section class="kanban-column" data-status-id="<?= (int) $status['id'] ?>">
            <header><?= htmlspecialchars($status['name']) ?></header>
            <div class="kanban-dropzone">
                <?php foreach ($demands as $item): ?>
                    <?php if ($item['status_slug'] === $status['slug']): ?>
                        <?php $cleanDesc = html_entity_decode($item['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        <article class="kanban-card js-view-demand" 
                                 draggable="<?= !empty($canMoveCards) ? 'true' : 'false' ?>" 
                                 data-demand-id="<?= (int) $item['id'] ?>"
                                 data-id="<?= (int) $item['id'] ?>"
                                 data-title="<?= htmlspecialchars($item['title']) ?>"
                                 data-description="<?= htmlspecialchars($cleanDesc) ?>"
                                 data-notes="<?= htmlspecialchars($item['notes'] ?? '') ?>"
                                 data-client="<?= htmlspecialchars($item['client_sector']) ?>"
                                 data-responsible="<?= htmlspecialchars($item['responsible_name'] ?? 'Não atribuído') ?>"
                                 data-priority="<?= htmlspecialchars($item['priority_name']) ?>"
                                 data-priority-color="<?= htmlspecialchars($item['priority_color']) ?>"
                                 data-status="<?= htmlspecialchars($item['status_name']) ?>">
                            <strong><?= htmlspecialchars($item['title']) ?></strong>
                            <span><?= htmlspecialchars($item['client_sector']) ?></span>
                            <p class="kanban-desc"><?= nl2br(htmlspecialchars($cleanDesc)) ?></p>
                            <div class="card-meta">
                                <span class="priority-badge" style="--priority: <?= htmlspecialchars($item['priority_color']) ?>"><?= htmlspecialchars($item['priority_name']) ?></span>
                                <span class="sla-counter <?= (int) $item['is_overdue'] ? 'is-overdue' : '' ?>" data-due="<?= htmlspecialchars($item['due_at']) ?>" data-finished="<?= htmlspecialchars($item['finished_at'] ?? '') ?>"></span>
                            </div>
                        </article>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>

<style>
.kanban-desc {
    font-size: 0.75rem;
    color: #94a3b8;
    margin: 8px 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
<script src="assets/js/kanban.js"></script>

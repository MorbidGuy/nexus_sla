<?php $rows = $demands ?? $recentDemands ?? []; ?>

<!-- Versão Desktop: Tabela visível apenas em MD para cima -->
<div class="table-responsive d-none d-md-block">
    <table class="table table-dark table-hover align-middle mb-0">
        <thead>
        <tr>
            <th>Título</th>
            <th>Cliente/Setor</th>
            <th>Responsável</th>
            <th>Prioridade / Status</th>
            <th>SLA</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $item): ?>
            <?php $cleanDesc = html_entity_decode($item['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
            <tr class="cursor-pointer js-view-demand"
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
                <td>
                    <div class="fw-bold text-white"><?= htmlspecialchars($item['title']) ?></div>
                    <div class="small text-secondary line-clamp-1" style="max-width: 350px; opacity: 0.8;">
                        <?= htmlspecialchars(mb_strimwidth($cleanDesc, 0, 100, "...")) ?>
                    </div>
                </td>
                <td><?= htmlspecialchars($item['client_sector']) ?></td>
                <td><small class="text-info"><?= htmlspecialchars($item['responsible_name'] ?? 'Sem responsável') ?></small></td>
                <td>
                    <span class="priority-badge mb-1 d-inline-block" style="--priority: <?= htmlspecialchars($item['priority_color']) ?>"><?= htmlspecialchars($item['priority_name']) ?></span><br>
                    <span class="status-pill" style="font-size: 0.75rem;"><?= htmlspecialchars($item['status_name']) ?></span>
                </td>
                <td>
                    <span class="sla-counter <?= (int) $item['is_overdue'] ? 'is-overdue' : '' ?>" data-due="<?= htmlspecialchars($item['due_at']) ?>" data-finished="<?= htmlspecialchars($item['finished_at'] ?? '') ?>"></span>
                </td>
                <td class="text-end">
                    <?php if (!empty($canManageDemand)): ?>
                        <a class="btn btn-sm btn-outline-light" href="index.php?route=demands/edit&id=<?= (int) $item['id'] ?>">Editar</a>
                        <?php if (($item['status_slug'] ?? '') === 'finalizado'): ?>
                            <form method="post" action="index.php?route=demands/reopen" class="d-inline">
                                <?= $csrfField ?? "" ?>
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <input type="hidden" name="notes" value="Reaberta pelo gestor: demanda não foi concluída.">
                                <button class="btn btn-sm btn-outline-warning" type="submit">Não concluído</button>
                            </form>
                        <?php endif; ?>
                    <?php elseif (!empty($canWorkDemand)): ?>
                        <form method="post" action="index.php?route=demands/status" class="d-inline-flex gap-1 align-items-center">
                            <?= $csrfField ?? "" ?>
                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                            <input type="hidden" name="notes" value="Atualizado pelo usuário operacional.">
                            <button class="btn btn-sm btn-outline-info" name="status" value="em-andamento" type="submit">Iniciar</button>
                            <button class="btn btn-sm btn-outline-success" name="status" value="finalizado" type="submit">Concluir</button>
                        </form>
                    <?php else: ?>
                        <span class="text-secondary">Leitura</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <tr><td colspan="7" class="text-center text-secondary py-4">Nenhuma demanda cadastrada.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Versão Mobile: Cards visíveis apenas abaixo de MD -->
<div class="d-md-none">
    <?php foreach ($rows as $item): ?>
        <?php $cleanDesc = html_entity_decode($item['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        <div class="card bg-dark border-secondary mb-3 shadow-sm js-view-demand" 
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
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="card-title text-cyan mb-0"><?= htmlspecialchars($item['title']) ?></h6>
                    <span class="priority-badge" style="--priority: <?= htmlspecialchars($item['priority_color']) ?>"><?= htmlspecialchars($item['priority_name']) ?></span>
                </div>
                
                <p class="small text-secondary line-clamp-2 mb-3" style="opacity: 0.8;"><?= nl2br(htmlspecialchars($cleanDesc)) ?></p>
                
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <small class="text-secondary d-block">Cliente/Setor</small>
                        <span class="small"><?= htmlspecialchars($item['client_sector']) ?></span>
                    </div>
                    <div class="col-6">
                        <small class="text-secondary d-block">SLA</small>
                        <span class="sla-counter <?= (int) $item['is_overdue'] ? 'is-overdue' : '' ?>" data-due="<?= htmlspecialchars($item['due_at']) ?>" data-finished="<?= htmlspecialchars($item['finished_at'] ?? '') ?>"></span>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center border-top border-secondary pt-2">
                    <span class="status-pill"><?= htmlspecialchars($item['status_name']) ?></span>
                    <div class="btn-group">
                        <?php if (!empty($canManageDemand)): ?>
                            <a class="btn btn-sm btn-outline-light" href="index.php?route=demands/edit&id=<?= (int) $item['id'] ?>">Editar</a>
                            <?php if (($item['status_slug'] ?? '') === 'finalizado'): ?>
                                <form method="post" action="index.php?route=demands/reopen" class="d-inline">
                                    <?= $csrfField ?? "" ?>
                                    <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                    <input type="hidden" name="notes" value="Reaberta pelo gestor: demanda não foi concluída.">
                                    <button class="btn btn-sm btn-outline-warning" type="submit">Reabrir</button>
                                </form>
                            <?php endif; ?>
                        <?php elseif (!empty($canWorkDemand)): ?>
                            <form method="post" action="index.php?route=demands/status" class="d-inline-flex gap-1">
                                <?= $csrfField ?? "" ?>
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <button class="btn btn-sm btn-outline-info" name="status" value="em-andamento" type="submit">Iniciar</button>
                                <button class="btn btn-sm btn-outline-success" name="status" value="finalizado" type="submit">Concluir</button>
                            </form>
                        <?php else: ?>
                            <span class="text-secondary small">Leitura</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (!$rows): ?>
        <div class="text-center text-secondary py-5 bg-dark rounded">
            <p>Nenhuma demanda encontrada.</p>
        </div>
    <?php endif; ?>
</div>

<style>
.text-cyan { color: #0dcaf0; }
.card { transition: transform 0.2s; }
.card:active { transform: scale(0.98); }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>


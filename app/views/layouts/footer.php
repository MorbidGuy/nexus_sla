    </main>
</div>
<?php if (!empty($_SESSION['user'])): ?>
<div class="modal fade" id="modalDetalhes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark border-secondary text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-cyan" id="modalTitulo">Demanda</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <small class="text-secondary d-block">Cliente/Setor</small>
                        <strong id="modalSetor">-</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-secondary d-block">Responsavel</small>
                        <strong id="modalResponsavel" class="text-info">-</strong>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="text-cyan small fw-bold text-uppercase">Descricao</label>
                    <div id="modalDescricao" class="detail-box"></div>
                </div>

                <div class="mb-0">
                    <label class="text-warning small fw-bold text-uppercase">Observacoes</label>
                    <div id="modalObservacoes" class="detail-box detail-box-warning"></div>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <a id="modalEditarDemanda" href="#" class="btn btn-outline-info">Editar</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/kanban.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>

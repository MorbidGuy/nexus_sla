<div class="page-heading">
    <div>
        <span class="eyebrow">Gestão</span>
        <h1>Demandas</h1>
    </div>
    <?php if (!empty($canCreateDemand)): ?>
        <a href="index.php?route=demands/create" class="btn btn-primary">Nova demanda</a>
    <?php endif; ?>
</div>

<section class="content-panel">
    <?php require APP_ROOT . '/app/views/demands/table.php'; ?>
</section>

<audio id="nexus-alert" preload="auto">
    <source src="assets/audio/dingo.wav" type="audio/wav">
</audio>

<div id="audio-unlocker" class="audio-badge" onclick="unlockAudio()">
    <span id="audio-status-text">🔇 Som Desativado</span>
</div>

<style>
.audio-badge {
    position: fixed;
    bottom: 20px;
    left: 20px;
    z-index: 9999;
    background: rgba(30, 41, 59, 0.9);
    border: 1px solid #334155;
    padding: 8px 15px;
    border-radius: 50px;
    color: #94a3b8;
    font-size: 0.75rem;
    cursor: pointer;
    backdrop-filter: blur(4px);
    transition: all 0.3s ease;
}
.audio-badge.unlocked {
    border-color: #0dcaf0;
    color: #0dcaf0;
    box-shadow: 0 0 10px rgba(13, 202, 240, 0.2);
}
</style>

<script>
let audioEnabled = false;
const alertSound = document.getElementById('nexus-alert');
alertSound.volume = 0.4;
const statusText = document.getElementById('audio-status-text');
const badge = document.getElementById('audio-unlocker');


let lastId = parseInt(localStorage.getItem('nexus_last_id')) || 0;

function unlockAudio() {
    alertSound.play().then(() => {
        alertSound.pause();
        alertSound.currentTime = 0;
        audioEnabled = true;
        badge.classList.add('unlocked');
        statusText.innerText = "🔊 Som Ativado (Nexus)";
        localStorage.setItem('nexus_audio_allowed', 'true');
        
        syncLastId();
    }).catch(e => console.error("Erro ao liberar áudio:", e));
}

async function syncLastId() {
    const lastStored = localStorage.getItem('nexus_last_id');
    try {
        const response = await fetch('index.php?route=demands/api_latest');
        const data = await response.json();
        if (data.id && !lastStored) {
            lastId = parseInt(data.id);
            localStorage.setItem('nexus_last_id', lastId);
        }
    } catch (err) { /* Falha silenciosa se a rota não existir ainda */ }
}

async function checkForNewDemands() {
    if (!audioEnabled) return;

    try {
        const response = await fetch('index.php?route=demands/api_latest');
        const data = await response.json();

        const currentMaxId = parseInt(data.id);

        if (currentMaxId > lastId) {
            alertSound.currentTime = 0;
            alertSound.play().catch(e => {
                console.warn("Navegador bloqueou o som. Clique na página primeiro.");
                audioEnabled = false; 
            });
            
            lastId = currentMaxId;
            localStorage.setItem('nexus_last_id', lastId);
        }
    } catch (err) {
        console.error("Erro na verificação de demandas:", err);
    }
}

if (localStorage.getItem('nexus_audio_allowed') === 'true') {
    audioEnabled = true;
    badge.classList.add('unlocked');
    statusText.innerText = "🔊 Som Ativado";
}

setInterval(checkForNewDemands, 10000);

if (window.location.search.includes('success=1')) {
    setTimeout(() => {
        alertSound.play().catch(() => {});
    }, 500);
}
</script>

<div class="modal fade" id="demandViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark border-secondary text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-cyan" id="m-title"></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <small class="text-secondary d-block">Status / Prioridade</small>
                        <span id="m-status" class="status-pill"></span>
                        <span id="m-priority" class="priority-badge"></span>
                    </div>
                    <div class="col-md-4">
                        <small class="text-secondary d-block">Cliente/Setor</small>
                        <strong id="m-client"></strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-secondary d-block">Responsável</small>
                        <strong id="m-responsible" class="text-info"></strong>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="text-cyan small fw-bold uppercase">Descrição</label>
                    <div id="m-description" class="p-3 rounded bg-black-50 border border-secondary-subtle" style="white-space: pre-wrap; background: rgba(0,0,0,0.2);"></div>
                </div>

                <div id="m-notes-container" class="mb-0">
                    <label class="text-warning small fw-bold uppercase">Observações Internas</label>
                    <div id="m-notes" class="p-3 rounded border border-warning-subtle text-warning-emphasis" style="white-space: pre-wrap; background: rgba(255,193,7,0.05);"></div>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <a id="m-edit-link" href="#" class="btn btn-outline-info">Editar Detalhes</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<style>
.bg-black-50 { background-color: rgba(0, 0, 0, 0.3); }
.uppercase { text-transform: uppercase; letter-spacing: 1px; font-size: 0.7rem; }
@media (max-width: 768px) {
    .modal-dialog { margin: 0.5rem; }
}
</style>

<script>
function decodeAndNl2br(text) {
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = text;
    const decodedText = tempDiv.textContent; 

    return decodedText.replace(/\n/g, '<br>');
}



document.addEventListener('DOMContentLoaded', function() {
    const modalElement = document.getElementById('demandViewModal');
    const modal = new bootstrap.Modal(modalElement);

    document.addEventListener('click', function(e) {
        const target = e.target.closest('.js-view-demand');
        if (!target) return;
        
        if (e.target.closest('button') || e.target.closest('a')) return;

        document.getElementById('m-title').textContent = target.dataset.title;
        document.getElementById('m-description').innerHTML = decodeAndNl2br(target.dataset.description);
        
        const notes = target.dataset.notes;
        const notesCont = document.getElementById('m-notes-container');
        if (notes && notes.trim() !== '') {
            document.getElementById('m-notes').innerHTML = decodeAndNl2br(notes);
            notesCont.style.display = 'block';
        } else {
            notesCont.style.display = 'none';
        }

        document.getElementById('m-client').textContent = target.dataset.client;
        document.getElementById('m-responsible').textContent = target.dataset.responsible;
        
        const statusPill = document.getElementById('m-status');
        statusPill.textContent = target.dataset.status;
        
        const priorityBadge = document.getElementById('m-priority');
        priorityBadge.textContent = target.dataset.priority;
        priorityBadge.style.setProperty('--priority', target.dataset.priorityColor);

        document.getElementById('m-edit-link').href = 'index.php?route=demands/edit&id=' + target.dataset.id;

        modal.show();
    });
});
</script>

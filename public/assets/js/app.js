let isMuted = localStorage.getItem('nexus_muted') === 'true';
let lastMaxDemandId = 0;
const notificationAudio = new Audio('assets/audio/ding.wav');
notificationAudio.preload = 'auto';
notificationAudio.volume = 0.4;
let audioUnlocked = localStorage.getItem('nexus_audio_unlocked') === 'true';

function unlockNotificationAudio() {
    if (audioUnlocked) return;

    notificationAudio.play()
        .then(() => {
            notificationAudio.pause();
            notificationAudio.currentTime = 0;
            audioUnlocked = true;
            localStorage.setItem('nexus_audio_unlocked', 'true');
        })
        .catch(() => {});
}

document.addEventListener('pointerdown', unlockNotificationAudio, { once: true });
document.addEventListener('keydown', unlockNotificationAudio, { once: true });

function updateSlaCounters() {
    document.querySelectorAll('.sla-counter').forEach((counter) => {
        if (counter.dataset.finished) {
            counter.textContent = 'Finalizado';
            counter.classList.remove('is-overdue');
            return;
        }

        const due = new Date(counter.dataset.due.replace(' ', 'T'));
        const diff = due.getTime() - Date.now();
        const overdue = diff < 0;
        const abs = Math.abs(diff);
        const hours = Math.floor(abs / 36e5);
        const minutes = Math.floor((abs % 36e5) / 6e4);

        counter.textContent = overdue ? `Atrasado ${hours}h ${minutes}m` : `${hours}h ${minutes}m`;
        counter.classList.toggle('is-overdue', overdue);
    });
}

updateSlaCounters();
setInterval(updateSlaCounters, 60000);

document.addEventListener('click', function(e) {
    const demandTarget = e.target.closest('[data-demand-id]');
    const isAction = e.target.closest('a, button, input, select, .dropdown, .no-details');

    if (demandTarget && !isAction) {
        const id = demandTarget.getAttribute('data-demand-id');
        const modalElement = document.getElementById('modalDetalhes');
        if (!modalElement) return;

        const detailModal = bootstrap.Modal.getOrCreateInstance(modalElement);
        demandTarget.style.opacity = '0.5';

        fetch(`index.php?route=demands/details&id=${encodeURIComponent(id)}`, { cache: 'no-store' })
            .then(res => res.json())
            .then(data => {
                demandTarget.style.opacity = '1';
                if (data.error) return;
                fillDemandModal(data, id);
                detailModal.show();
            })
            .catch(() => {
                demandTarget.style.opacity = '1';
            });
        return;
    }

    const link = e.target.closest('a');
    if (link) {
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

        const isExternal = link.hostname !== window.location.hostname;
        const isSpecial = href.includes('logout') || link.target === '_blank' || link.classList.contains('no-spa');
        if (isExternal || isSpecial) return;

        e.preventDefault();
        navigateTo(link.href);
    }
});

function fillDemandModal(data, id) {
    const title = document.getElementById('modalTitulo');
    const description = document.getElementById('modalDescricao');
    const notes = document.getElementById('modalObservacoes');
    const sector = document.getElementById('modalSetor');
    const responsible = document.getElementById('modalResponsavel');
    const editLink = document.getElementById('modalEditarDemanda');

    if (title) title.innerText = data.title || 'Demanda';
    if (description) description.innerHTML = data.description || 'Sem descricao.';
    if (notes) notes.innerHTML = data.notes || 'Sem observacoes.';
    if (sector) sector.innerText = data.client_sector || '-';
    if (responsible) responsible.innerText = data.responsible || 'Nao atribuido';
    if (editLink) editLink.href = `index.php?route=demands/edit&id=${encodeURIComponent(id)}`;
}

function navigateTo(url) {
    const mainContent = document.querySelector('#main-content');
    if (mainContent) mainContent.style.opacity = '0';

    fetch(url, { cache: 'no-store' })
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContent = doc.querySelector('#main-content');

            if (newContent) {
                mainContent.innerHTML = newContent.innerHTML;
                document.title = doc.title;
                window.history.pushState({}, '', url);
                initAllComponents();
                updateSidebarActive(url);

                setTimeout(() => {
                    mainContent.style.opacity = '1';
                }, 50);
            } else {
                window.location.href = url;
            }
        })
        .catch(err => {
            console.error('Erro na navegacao:', err);
            if (mainContent) mainContent.style.opacity = '1';
            window.location.href = url;
        });
}

function updateSidebarActive(url) {
    const urlObj = new URL(url, window.location.origin);
    const route = urlObj.searchParams.get('route') || 'dashboard';

    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
        const linkUrl = new URL(link.href, window.location.origin);
        const linkRoute = linkUrl.searchParams.get('route') || 'dashboard';
        link.classList.toggle('active', route.startsWith(linkRoute));
    });
}

function initAllComponents() {
    updateSlaCounters();
    initSourceProtection();

    const ids = Array.from(document.querySelectorAll('[data-demand-id]')).map(el => parseInt(el.dataset.demandId, 10));
    if (ids.length > 0) {
        lastMaxDemandId = Math.max(lastMaxDemandId, ...ids);
        localStorage.setItem('nexus_last_id', String(lastMaxDemandId));
    }

    const mainContent = document.querySelector('#main-content');
    if (mainContent) {
        mainContent.style.transition = 'opacity 0.3s ease-in-out';
    }

    if (typeof initKanbanDragDrop === 'function') initKanbanDragDrop();
}

document.addEventListener('DOMContentLoaded', () => {
    initAllComponents();

    const muteIcon = document.getElementById('mute-icon');
    const muteBtn = document.getElementById('toggle-mute');
    if (muteIcon && muteBtn) {
        muteIcon.textContent = isMuted ? 'Som off' : 'Som on';
        muteBtn.classList.toggle('opacity-50', isMuted);
        muteBtn.classList.toggle('opacity-75', !isMuted);
    }
});

function playNotification() {
    if (isMuted) return;

    notificationAudio.currentTime = 0;
    notificationAudio.play().catch(err => {
        console.warn('O navegador bloqueou o audio automatico. Interaja com a pagina primeiro.', err);
    });
}

document.addEventListener('click', function(e) {
    const muteBtn = e.target.closest('#toggle-mute');
    if (muteBtn) {
        isMuted = !isMuted;
        localStorage.setItem('nexus_muted', isMuted);
        const muteIcon = document.getElementById('mute-icon');
        if (muteIcon) muteIcon.textContent = isMuted ? 'Som off' : 'Som on';
        muteBtn.classList.toggle('opacity-50', isMuted);
        muteBtn.classList.toggle('opacity-75', !isMuted);
    }
});

setInterval(() => window.location.reload(), 180000);

function refreshData() {
    const modalOpen = document.querySelector('.modal.show');
    const isTyping = ['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName);

    if (modalOpen || isTyping) return;

    fetch(window.location.href, { cache: 'no-store' })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const selectors = ['.kanban-board', '.table-responsive', '.d-md-none', '.content-refresh', '.stats-grid'];
            let updated = false;

            const newIds = Array.from(doc.querySelectorAll('[data-demand-id]')).map(el => parseInt(el.dataset.demandId, 10));
            if (newIds.length > 0) {
                const currentMaxId = Math.max(...newIds);
                if (lastMaxDemandId > 0 && currentMaxId > lastMaxDemandId) {
                    playNotification();
                }
                lastMaxDemandId = Math.max(lastMaxDemandId, currentMaxId);
                localStorage.setItem('nexus_last_id', String(lastMaxDemandId));
            }

            selectors.forEach(selector => {
                const newNodes = Array.from(doc.querySelectorAll(selector));
                const oldNodes = Array.from(document.querySelectorAll(selector));

                oldNodes.forEach((oldContent, index) => {
                    const newContent = newNodes[index];
                    if (newContent && oldContent.innerHTML !== newContent.innerHTML) {
                        oldContent.innerHTML = newContent.innerHTML;
                        updated = true;
                    }
                });
            });

            if (updated) initAllComponents();
        })
        .catch(err => console.warn('Erro na atualizacao automatica:', err));
}

function syncLatestDemandId() {
    fetch('index.php?route=demands/api_latest', { cache: 'no-store' })
        .then(response => response.ok ? response.json() : null)
        .then(data => {
            if (!data || typeof data.id === 'undefined') return;

            const currentMaxId = parseInt(data.id, 10) || 0;
            const storedMaxId = parseInt(localStorage.getItem('nexus_last_id') || '0', 10) || 0;
            const baseline = Math.max(lastMaxDemandId, storedMaxId);

            if (baseline > 0 && currentMaxId > baseline) {
                playNotification();
            }

            if (currentMaxId > 0) {
                lastMaxDemandId = Math.max(lastMaxDemandId, currentMaxId);
                localStorage.setItem('nexus_last_id', String(lastMaxDemandId));
            }
        })
        .catch(() => {});
}

window.onpopstate = () => window.location.reload();

function initSourceProtection() {
    if (document.body.dataset.sourceProtectionReady === 'true') return;
    document.body.dataset.sourceProtectionReady = 'true';

    document.addEventListener('contextmenu', (event) => {
        event.preventDefault();
    });

    document.addEventListener('keydown', (event) => {
        const key = event.key.toLowerCase();
        const blocked =
            event.key === 'F12' ||
            (event.ctrlKey && event.shiftKey && ['i', 'j', 'c'].includes(key)) ||
            (event.ctrlKey && ['u', 's'].includes(key));

        if (blocked) {
            event.preventDefault();
            event.stopPropagation();
        }
    }, true);
}

setInterval(refreshData, 5000);
syncLatestDemandId();
setInterval(syncLatestDemandId, 10000);

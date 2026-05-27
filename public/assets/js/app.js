let isMuted = localStorage.getItem('nexus_muted') === 'true';
let lastMaxDemandId = 0;
const notificationAudio = new Audio('assets/audio/dingo.wav');

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

        fetch(`index.php?route=demands/details&id=${id}`)
            .then(res => res.json())
            .then(data => {
                demandTarget.style.opacity = '1';
                if (data.error) return;
                document.getElementById('modalTitulo').innerText = data.title;
                document.getElementById('modalDescricao').innerHTML = data.description;
                document.getElementById('modalObservacoes').innerHTML = data.notes;
                document.getElementById('modalSetor').innerText = data.client_sector;
                document.getElementById('modalResponsavel').innerText = data.responsible;
                detailModal.show();
            })
            .catch(() => demandTarget.style.opacity = '1');
        return;
    }

    const link = e.target.closest('a');
    if (link) {
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

        const isExternal = link.hostname !== window.location.hostname;
        const isSpecial = href.includes('logout') || link.target === '_blank' || link.classList.contains('no-spa');
        
        e.preventDefault();
        navigateTo(link.href);
    }
});

function navigateTo(url) {
    const mainContent = document.querySelector('#main-content');
    if (mainContent) mainContent.style.opacity = '0'; 

    localStorage.setItem('nexus_last_url', url);

    fetch(url)
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
                
                setTimeout(() => { mainContent.style.opacity = '1'; }, 50);
            } else {
                window.location.href = url;
            }
        })
        .catch(err => {
            console.error('Erro na navegação:', err);
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

    const ids = Array.from(document.querySelectorAll('[data-demand-id]')).map(el => parseInt(el.dataset.demandId));
    if (ids.length > 0) {
        lastMaxDemandId = Math.max(...ids);
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
        muteIcon.textContent = isMuted ? '🔇' : '🔊';
        muteBtn.classList.toggle('opacity-50', isMuted);
        muteBtn.classList.toggle('opacity-75', !isMuted);
    }
});

function playNotification() {
    if (isMuted) return;
    
    notificationAudio.play().catch(err => {
        console.warn('O navegador bloqueou o áudio automático. Interaja com a página primeiro.', err);
    });
}

document.addEventListener('click', function(e) {
    const muteBtn = e.target.closest('#toggle-mute');
    if (muteBtn) {
        isMuted = !isMuted;
        localStorage.setItem('nexus_muted', isMuted);
        const muteIcon = document.getElementById('mute-icon');
        if (muteIcon) muteIcon.textContent = isMuted ? '🔇' : '🔊';
        muteBtn.classList.toggle('opacity-50', isMuted);
        muteBtn.classList.toggle('opacity-75', !isMuted);
    }
});

setInterval(() => window.location.reload(), 180000);

function refreshData() {
    const modalOpen = document.querySelector('.modal.show');
    const isTyping = ['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName);
    
    if (modalOpen || isTyping) return;

    fetch(window.location.href)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            const selectors = ['.kanban-container', '.table-responsive', '.content-refresh'];
            let updated = false;
            
            const newIds = Array.from(doc.querySelectorAll('[data-demand-id]')).map(el => parseInt(el.dataset.demandId));
            if (newIds.length > 0) {
                const currentMaxId = Math.max(...newIds);
                if (currentMaxId > lastMaxDemandId) {
                    lastMaxDemandId = currentMaxId;
                    playNotification();
                }
            }

            selectors.forEach(selector => {
                const newContent = doc.querySelector(selector);
                const oldContent = document.querySelector(selector);
                
                if (newContent && oldContent) {
                    if (oldContent.innerHTML !== newContent.innerHTML) {
                        oldContent.innerHTML = newContent.innerHTML;
                        updated = true;
                    }
                }
            });

            if (updated) initAllComponents();
        })
        .catch(err => console.warn('Erro na atualização automática:', err));
}

window.onpopstate = () => window.location.reload();

document.addEventListener('DOMContentLoaded', () => {
    const lastUrl = localStorage.getItem('nexus_last_url');
    const currentUrl = window.location.href;
    const isBaseUrl = !window.location.search;

    if (isBaseUrl && lastUrl && lastUrl !== currentUrl && !currentUrl.includes('logout')) {
        navigateTo(lastUrl);
    }
});

setInterval(refreshData, 5000);

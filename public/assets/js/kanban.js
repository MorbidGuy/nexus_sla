let draggedCard = null;

function initKanbanDragDrop() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    document.querySelectorAll('.kanban-card').forEach((card) => {
        if (card.dataset.kanbanReady === 'true') return;
        card.dataset.kanbanReady = 'true';

        card.addEventListener('dragstart', () => {
            draggedCard = card;
            card.classList.add('dragging');
        });

        card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
            draggedCard = null;
        });
    });

    document.querySelectorAll('.kanban-column').forEach((column) => {
        if (column.dataset.kanbanReady === 'true') return;
        column.dataset.kanbanReady = 'true';

        column.addEventListener('dragover', (event) => event.preventDefault());

        column.addEventListener('drop', async () => {
            if (!draggedCard) return;

            const previousParent = draggedCard.parentElement;
            const dropzone = column.querySelector('.kanban-dropzone');
            dropzone.appendChild(draggedCard);

            const body = new URLSearchParams({
                id: draggedCard.dataset.demandId,
                status_id: column.dataset.statusId,
                _token: csrfToken,
            });

            try {
                const response = await fetch('index.php?route=kanban/update-status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body,
                });

                if (!response.ok) {
                    previousParent?.appendChild(draggedCard);
                }
            } catch (error) {
                previousParent?.appendChild(draggedCard);
                console.warn('Erro ao mover card do kanban:', error);
            }
        });
    });
}

initKanbanDragDrop();

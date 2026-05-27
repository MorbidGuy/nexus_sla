let draggedCard = null;
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

document.querySelectorAll('.kanban-card').forEach((card) => {
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
    column.addEventListener('dragover', (event) => event.preventDefault());

    column.addEventListener('drop', async () => {
        if (!draggedCard) return;

        column.querySelector('.kanban-dropzone').appendChild(draggedCard);

        const body = new URLSearchParams({
            id: draggedCard.dataset.demandId,
            status_id: column.dataset.statusId,
            _token: csrfToken,
        });

        await fetch('index.php?route=kanban/update-status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body,
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const detailModal = new bootstrap.Modal(document.getElementById('modalDetalhes'));
    
    document.addEventListener('click', function(e) {
        const target = e.target.closest('.btn-view-details');
        if (!target) return;

        const id = target.getAttribute('data-id');
        
        fetch(`index.php?route=demands/details&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) return alert(data.error);

                document.getElementById('modalTitulo').innerText = data.title;
                document.getElementById('modalDescricao').innerHTML = data.description;
                document.getElementById('modalObservacoes').innerHTML = data.notes;
                document.getElementById('modalSetor').innerText = data.client_sector;
                
                detailModal.show();
            })
            .catch(error => console.error('Erro ao carregar detalhes:', error));
    });
});
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', () => {
            if (!form.checkValidity()) form.classList.add('was-validated');
        });
    });
});

function fetchDetails(id) {
    const row = document.querySelector(`a[onclick*="${id}"]`).closest('tr');
    document.getElementById('modalBody').innerHTML = `
        <p><b>Job #${id}</b></p>
        <p>Customer: ${row.cells[1].innerText}</p>
        <p>Device: ${row.cells[2].innerText}</p>
        <p>Issue: ${row.cells[3].innerText}</p>
        <p>Status: ${row.cells[4].innerText}</p>`;
    document.getElementById('jobModal').style.display = 'block';
}
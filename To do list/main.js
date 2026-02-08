let tasks = [];
document.addEventListener('DOMContentLoaded', () => {
    loadTasks();
});

function loadTasks() {
    fetch('ambil_tugas.php')
        .then(response => response.json())
        .then(data => {
            tasks = data.map(task => ({
                ...task,
                 schedule: new Date(task.jadwal),
                createdAt: new Date(task.created_at)
            }));
            renderTasks();
        });
}
let currentView = 'today';
let currentListId = null;
let tasks = [];
let lists = [];
let sortOrder = 'desc';
let pollInterval = null;
let sortField = 'createdAt';

document.addEventListener('DOMContentLoaded', function() {
    const token = localStorage.getItem('token');
    if (!token && window.location.pathname === '/') {
        window.location.href = '/login';
        return;
    }
    initApp();
});

function getToken() {
    return localStorage.getItem('token');
}

function apiHeaders() {
    return {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + getToken()
    };
}

async function apiGet(url) {
    const res = await fetch(url, { headers: apiHeaders() });
    const data = await res.json();
    if (res.status === 401) {
        localStorage.removeItem('token');
        window.location.href = '/login';
        return null;
    }
    return data;
}

async function apiPost(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        headers: apiHeaders(),
        body: JSON.stringify(body)
    });
    return res.json();
}

async function apiPatch(url, body) {
    const res = await fetch(url, {
        method: 'PATCH',
        headers: apiHeaders(),
        body: JSON.stringify(body)
    });
    return res.json();
}

async function apiDelete(url) {
    const res = await fetch(url, { method: 'DELETE', headers: apiHeaders() });
    return res.json();
}

async function initApp() {
    setupEventListeners();
    await loadUser();
    await loadLists();
    switchView('today');

    pollInterval = setInterval(() => {
        if (currentView !== 'dashboard') loadTasks();
    }, 30000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && currentView !== 'dashboard') loadTasks();
    });
}

async function loadUser() {
    const user = await apiGet('/api/users/me');
    if (user) {
        document.getElementById('userName').textContent = user.name;
    }
}

async function loadLists() {
    lists = await apiGet('/api/lists') || [];
    renderLists();
}

function switchView(view) {
    currentView = view;
    const taskView = document.getElementById('taskView');
    const dashView = document.getElementById('dashboardView');

    if (view === 'dashboard') {
        taskView.style.display = 'none';
        dashView.style.display = 'block';
        loadDashboard();
    } else {
        taskView.style.display = 'block';
        dashView.style.display = 'none';
        loadTasks();
    }
}

async function loadTasks() {
    let url = '/api/tasks';
    const params = new URLSearchParams();

    if (currentView === 'trash') {
        url = '/api/tasks/trashed';
    } else {
        if (currentView !== 'all') params.set('view', currentView);
        if (currentListId) params.set('list', currentListId);

        const priority = document.getElementById('filterPriority').value;
        if (priority !== 'all') params.set('priority', priority);

        const status = document.getElementById('filterStatus').value;
        if (status !== 'all') params.set('status', status);

        const search = document.getElementById('searchInput').value.trim();
        if (search) params.set('search', search);

        params.set('sort', sortField);
        params.set('order', sortOrder);
    }

    const query = params.toString();
    if (query) url += '?' + query;

    tasks = await apiGet(url) || [];
    renderTasks();
}

async function loadDashboard() {
    const stats = await apiGet('/api/dashboard/stats');
    if (!stats) return;
    document.getElementById('statTotal').textContent = stats.totalTasks || 0;
    document.getElementById('statCompleted').textContent = stats.completedTasks || 0;
    document.getElementById('statPending').textContent = stats.pendingTasks || 0;
    document.getElementById('statOverdue').textContent = stats.overdueTasks || 0;
    document.getElementById('statUpcoming').textContent = stats.upcomingTasks || 0;
    document.getElementById('statStreak').textContent = (stats.currentStreak || 0) + ' days';

    renderChart(stats.completions7 || []);
}

function renderChart(data) {
    const chart = document.getElementById('chart7');
    chart.innerHTML = '';
    if (!data.length) return;

    const maxCount = Math.max(...data.map(d => d.count), 1);
    data.forEach(d => {
        const bar = document.createElement('div');
        bar.className = 'chart-bar';
        const pct = (d.count / maxCount) * 100;
        bar.style.height = Math.max(pct, 2) + '%';
        bar.innerHTML = '<span class="chart-bar-label">' + d.date.slice(5) + '</span>';
        bar.title = d.date + ': ' + d.count + ' completed';
        chart.appendChild(bar);
    });
}

function renderLists() {
    const container = document.getElementById('taskLists');
    container.innerHTML = '';
    lists.forEach(list => {
        const li = document.createElement('li');
        const a = document.createElement('a');
        a.href = '#';
        a.className = 'nav-item' + (currentListId === list._id ? ' active' : '');
        a.dataset.listId = list._id;
        a.innerHTML = '<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:' + list.color + ';margin-right:8px"></span>' + escapeHtml(list.name);
        a.addEventListener('click', function(e) {
            e.preventDefault();
            currentListId = list._id;
            currentView = null;
            document.getElementById('viewTitle').textContent = list.name;
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            a.classList.add('active');
            switchView('list');
        });
        li.appendChild(a);
        container.appendChild(li);
    });
}

function renderTasks() {
    const container = document.getElementById('taskList');
    const emptyState = document.getElementById('emptyState');
    container.innerHTML = '';

    if (!tasks || tasks.length === 0) {
        emptyState.style.display = 'block';
        return;
    }
    emptyState.style.display = 'none';

    tasks.forEach(task => {
        const card = document.createElement('div');
        card.className = 'task-card';
        if (task.status === 'completed') card.classList.add('completed');
        if (task.priority) card.classList.add('priority-' + task.priority);
        if (isOverdue(task)) card.classList.add('overdue');

        const completedSubtasks = (task.subtasks || []).filter(s => s.isCompleted).length;
        const totalSubtasks = (task.subtasks || []).length;

        card.innerHTML = `
            <div class="task-header">
                <input type="checkbox" class="task-checkbox" ${task.status === 'completed' ? 'checked' : ''} data-id="${task._id}">
                <div class="task-info">
                    <div class="task-title">${escapeHtml(task.title)}</div>
                    ${task.description ? `<div class="task-description">${escapeHtml(task.description)}</div>` : ''}
                    <div class="task-meta">
                        <span class="badge badge-${task.priority || 'medium'}">${task.priority || 'medium'}</span>
                        <span class="badge badge-${task.status || 'pending'}">${(task.status || 'pending').replace('_', ' ')}</span>
                        ${task.dueAt ? `<span>Due: ${formatDate(task.dueAt)}</span>` : ''}
                        ${task.isRecurring ? '<span title="Recurring">&#x1F504;</span>' : ''}
                        ${totalSubtasks > 0 ? `<span class="subtask-summary">${completedSubtasks}/${totalSubtasks} subtasks</span>` : ''}
                        ${task.attachments && task.attachments.length > 0 ? `<span>&#x1F4CE; ${task.attachments.length}</span>` : ''}
                    </div>
                </div>
                <div class="task-actions">
                    <button class="edit-task" data-id="${task._id}" title="Edit">&#x270F;&#xFE0F;</button>
                    <button class="delete-task" data-id="${task._id}" title="Delete">&#x1F5D1;&#xFE0F;</button>
                </div>
            </div>
        `;

        card.querySelector('.task-checkbox').addEventListener('change', function() {
            toggleComplete(task._id, this.checked);
        });

        card.querySelector('.edit-task').addEventListener('click', function(e) {
            e.stopPropagation();
            openTaskModal(task);
        });

        card.querySelector('.delete-task').addEventListener('click', function(e) {
            e.stopPropagation();
            if (currentView === 'trash') {
                confirmAction('Delete permanently?', 'This cannot be undone.', () => permanentDeleteTask(task._id));
            } else {
                confirmAction('Move to trash?', 'You can restore it later.', () => softDeleteTask(task._id));
            }
        });

        container.appendChild(card);
    });
}

function isOverdue(task) {
    if (!task.dueAt || task.status === 'completed') return false;
    const dueDate = new Date(task.dueAt);
    return dueDate < new Date();
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function setupEventListeners() {
    document.querySelectorAll('[data-view]').forEach(el => {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            const view = this.dataset.view;
            currentListId = null;
            const label = this.textContent.trim();
            document.getElementById('viewTitle').textContent = label;
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            this.classList.add('active');
            switchView(view);
        });
    });

    document.getElementById('addTaskBtn').addEventListener('click', async function() {
        await loadLists();
        openTaskModal(null);
    });

    document.getElementById('addListBtn').addEventListener('click', function() {
        const name = prompt('Enter list name:');
        if (name && name.trim()) {
            apiPost('/api/lists', { name: name.trim() }).then(() => loadLists());
        }
    });

    document.getElementById('logoutBtn').addEventListener('click', function() {
        apiPost('/api/auth/logout', {});
        localStorage.removeItem('token');
        window.location.href = '/login';
    });

    document.getElementById('exportBtn').addEventListener('click', async function() {
        const res = await fetch('/api/export?format=json', { headers: apiHeaders() });
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'cloudtasks-export-' + new Date().toISOString().slice(0,10) + '.json';
        a.click();
        URL.revokeObjectURL(url);
    });

    document.getElementById('mobileMenuBtn').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('open');
    });

    document.getElementById('menuToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.remove('open');
    });

    document.getElementById('filterPriority').addEventListener('change', () => loadTasks());
    document.getElementById('filterStatus').addEventListener('change', () => loadTasks());
    document.getElementById('sortBy').addEventListener('change', function() {
        sortField = this.value;
        loadTasks();
    });
    document.getElementById('sortOrderBtn').addEventListener('click', function() {
        sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
        this.innerHTML = sortOrder === 'asc' ? '&uarr;' : '&darr;';
        loadTasks();
    });

    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(loadTasks, 400);
    });

    document.getElementById('taskRecurring').addEventListener('change', function() {
        document.getElementById('taskRecurrenceRule').style.display = this.checked ? 'block' : 'none';
    });

    document.querySelectorAll('.modal-close, .modal-close-btn').forEach(el => {
        el.addEventListener('click', closeModals);
    });

    document.getElementById('taskForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveTask();
    });

    document.getElementById('confirmYes').addEventListener('click', function() {
        if (window._confirmCallback) {
            window._confirmCallback();
            window._confirmCallback = null;
        }
        closeModals();
    });

    document.getElementById('confirmNo').addEventListener('click', function() {
        window._confirmCallback = null;
        closeModals();
    });

    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModals();
        }
    });
}

async function openTaskModal(task) {
    const modal = document.getElementById('taskModal');
    document.getElementById('modalTitle').textContent = task ? 'Edit Task' : 'New Task';
    document.getElementById('taskId').value = task ? task._id : '';
    document.getElementById('taskTitle').value = task ? task.title : '';
    document.getElementById('taskDescription').value = task ? (task.description || '') : '';
    document.getElementById('taskPriority').value = task ? task.priority : 'medium';

    if (task && task.dueAt) {
        const d = new Date(task.dueAt);
        document.getElementById('taskDueAt').value = d.toISOString().slice(0, 16);
    } else {
        document.getElementById('taskDueAt').value = '';
    }

    const listSelect = document.getElementById('taskList');
    listSelect.innerHTML = '<option value="">Uncategorized</option>';
    if (Array.isArray(lists)) {
        lists.forEach(list => {
            if (list && list._id) {
                const opt = document.createElement('option');
                opt.value = list._id;
                opt.textContent = list.name || 'Unnamed';
                if (task && task.taskListId === list._id) opt.selected = true;
                listSelect.appendChild(opt);
            }
        });
    }

    document.getElementById('taskRecurring').checked = task ? task.isRecurring : false;
    document.getElementById('taskRecurrenceRule').value = task ? (task.recurrenceRule || 'daily') : 'daily';
    document.getElementById('taskRecurrenceRule').style.display = task && task.isRecurring ? 'block' : 'none';
    document.getElementById('recurringOptions').style.display = task && task.isRecurring ? 'block' : 'none';

    modal.style.display = 'flex';
}

function closeModals() {
    document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
}

async function saveTask() {
    const id = document.getElementById('taskId').value;
    const data = {
        title: document.getElementById('taskTitle').value,
        description: document.getElementById('taskDescription').value,
        priority: document.getElementById('taskPriority').value,
        taskListId: document.getElementById('taskList').value || null,
        dueAt: document.getElementById('taskDueAt').value || null,
        isRecurring: document.getElementById('taskRecurring').checked,
        recurrenceRule: document.getElementById('taskRecurring').checked ? document.getElementById('taskRecurrenceRule').value : null
    };

    if (!data.title.trim()) return;

    if (id) {
        await apiPatch('/api/tasks/' + id, data);
    } else {
        await apiPost('/api/tasks', data);
    }

    closeModals();
    loadTasks();
}

async function toggleComplete(id, completed) {
    await apiPatch('/api/tasks/' + id + '/complete', { completed });
    loadTasks();
}

async function softDeleteTask(id) {
    await apiDelete('/api/tasks/' + id);
    loadTasks();
}

async function permanentDeleteTask(id) {
    await apiDelete('/api/tasks/' + id + '/permanent');
    loadTasks();
}

function confirmAction(title, message, callback) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    window._confirmCallback = callback;
    document.getElementById('confirmModal').style.display = 'flex';
}

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CloudTasks - Task Manager</title>
<link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div id="app">
    <aside id="sidebar">
        <div class="sidebar-header">
            <h1>CloudTasks</h1>
            <button id="menuToggle" class="btn-icon">&times;</button>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">
                <h3>Views</h3>
                <ul>
                    <li><a href="#" data-view="today" class="nav-item active">Today</a></li>
                    <li><a href="#" data-view="upcoming" class="nav-item">Upcoming</a></li>
                    <li><a href="#" data-view="overdue" class="nav-item">Overdue</a></li>
                    <li><a href="#" data-view="completed" class="nav-item">Completed</a></li>
                    <li><a href="#" data-view="all" class="nav-item">All Tasks</a></li>
                </ul>
            </div>
            <div class="nav-section collapsible">
                <h3 class="collapse-toggle" data-target="listsContent">
                    Lists <span class="collapse-indicator">▼</span>
                </h3>
                <div id="listsContent" class="collapse-content">
                    <ul id="taskLists"></ul>
                    <button id="addListBtn" class="btn-text">+ New List</button>
                </div>
            </div>
            <div class="nav-section">
                <h3>More</h3>
                <ul>
                    <li><a href="#" data-view="trash" class="nav-item">Trash</a></li>
                    <li><a href="#" data-view="dashboard" class="nav-item">Dashboard</a></li>
                    <li><a href="#" id="settingsBtn" class="nav-item">Settings</a></li>
                </ul>
            </div>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <span id="userName"></span>
                <button id="logoutBtn" class="btn-text">Logout</button>
            </div>
        </div>
    </aside>

    <main id="main">
        <header class="main-header">
            <button id="mobileMenuBtn" class="btn-icon">&equiv;</button>
            <h2 id="viewTitle">Today</h2>
            <div class="header-actions">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search tasks...">
                </div>
                <button id="exportBtn" class="btn btn-secondary">Export</button>
            </div>
        </header>

        <div id="content">
            <div id="taskView">
                <div class="view-controls">
                    <div class="filter-group">
                        <select id="filterPriority">
                            <option value="all">All Priorities</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                        <select id="filterStatus">
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                        <select id="sortBy">
                            <option value="createdAt">Created</option>
                            <option value="dueAt">Due Date</option>
                            <option value="priority">Priority</option>
                            <option value="title">Title</option>
                        </select>
                        <button id="sortOrderBtn" class="btn-icon">&uarr;</button>
                    </div>
                    <button id="addTaskBtn" class="btn btn-primary">+ New Task</button>
                </div>

                <div class="loading" id="taskLoading" style="display:none"><div class="spinner"></div><span>Loading tasks...</span></div>
                <div id="taskList" class="task-list"></div>
                <div id="emptyState" class="empty-state" style="display:none">
                    <p>No tasks found</p>
                </div>
            </div>

            <div id="dashboardView" style="display:none">
                <div class="loading" id="dashLoading"><div class="spinner"></div><span>Loading dashboard...</span></div>
                <h2 style="display:none">Dashboard</h2>
                <div class="stats-grid" style="display:none">
                    <div class="stat-card"><h3>Total Tasks</h3><p id="statTotal">0</p></div>
                    <div class="stat-card"><h3>Completed</h3><p id="statCompleted">0</p></div>
                    <div class="stat-card"><h3>Pending</h3><p id="statPending">0</p></div>
                    <div class="stat-card"><h3>Overdue</h3><p id="statOverdue">0</p></div>
                    <div class="stat-card"><h3>Upcoming</h3><p id="statUpcoming">0</p></div>
                    <div class="stat-card"><h3>Streak</h3><p id="statStreak">0 days</p></div>
                </div>
                <div class="chart-section">
                    <h3>Last 7 Days</h3>
                    <div id="chart7" class="chart"></div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Task Modal -->
<div id="taskModal" class="modal" style="display:none">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <h3 id="modalTitle">New Task</h3>
        <form id="taskForm">
            <input type="hidden" id="taskId">
            <div class="form-group">
                <label for="taskTitle">Title</label>
                <input type="text" id="taskTitle" required>
            </div>
            <div class="form-group">
                <label for="taskDescription">Description</label>
                <textarea id="taskDescription" rows="3"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="taskPriority">Priority</label>
                    <select id="taskPriority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="taskDueAt">Due Date</label>
                    <input type="datetime-local" id="taskDueAt">
                </div>
            </div>
            <div class="form-group">
                <label for="taskListSelect">List</label>
                <select id="taskListSelect"></select>
            </div>
            <div class="form-group">
                <label class="check-label">
                    <input type="checkbox" id="taskRecurring">
                    Recurring task
                </label>
                <select id="taskRecurrenceRule" style="display:none">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                </select>
            </div>
            <div id="recurringOptions" style="display:none">
                <p class="recurring-note">Apply changes to: <label><input type="radio" name="recurringEdit" value="this" checked> This occurrence</label> <label><input type="radio" name="recurringEdit" value="future"> All future</label></p>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save</button>
                <button type="button" class="btn btn-secondary modal-close-btn">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Settings Modal -->
<div id="settingsModal" class="modal" style="display:none">
    <div class="modal-content modal-sm">
        <span class="modal-close">&times;</span>
        <h3>Settings</h3>
        <form id="settingsForm">
            <div class="form-group">
                <label for="settingsName">Name</label>
                <input type="text" id="settingsName" required>
            </div>
            <div class="form-group">
                <label for="settingsTimezone">Timezone</label>
                <select id="settingsTimezone"></select>
            </div>
            <div class="form-group">
                <label for="settingsTheme">Theme</label>
                <select id="settingsTheme">
                    <option value="light">Light</option>
                    <option value="dark">Dark</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Settings</button>
                <button type="button" class="btn btn-secondary modal-close-btn">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Confirm Modal -->
<div id="confirmModal" class="modal" style="display:none">
    <div class="modal-content modal-sm">
        <h3 id="confirmTitle">Confirm</h3>
        <p id="confirmMessage"></p>
        <div class="form-actions">
            <button id="confirmYes" class="btn btn-danger">Yes</button>
            <button id="confirmNo" class="btn btn-secondary">Cancel</button>
        </div>
    </div>
</div>

<script src="/assets/js/app.js"></script>
</body>
</html>

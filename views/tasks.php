<?php
$pageTitle = 'Aufgaben';
require __DIR__ . '/_layout.php';
?>

<div x-data="tasksApp()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center gap-4 mb-6">
        <div class="flex-1 flex items-center gap-3">
            <!-- Filter -->
            <select x-model="filterStatus" @change="loadTasks()"
                    class="bg-gray-800 border border-gray-700 text-white text-sm rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">Alle Status</option>
                <template x-for="s in statuses" :key="s.id">
                    <option :value="s.id" x-text="s.name"></option>
                </template>
            </select>
            <select x-model="filterAssignee" @change="loadTasks()"
                    class="bg-gray-800 border border-gray-700 text-white text-sm rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                <option value="">Alle Mitarbeiter</option>
                <option value="me">Mir zugewiesen</option>
                <template x-for="u in users" :key="u.id">
                    <option :value="u.id" x-text="u.first_name + ' ' + u.last_name"></option>
                </template>
            </select>
            <input type="search" x-model.debounce.300ms="filterSearch" @input="loadTasks()"
                   placeholder="Suche…"
                   class="bg-gray-800 border border-gray-700 text-white text-sm rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 w-48">
        </div>
        <button @click="openCreate()"
                class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Neue Aufgabe
        </button>
    </div>

    <!-- Kanban Board -->
    <div x-show="!loading" class="flex gap-4 overflow-x-auto pb-4">
        <template x-for="col in columns" :key="col.id">
            <div class="flex-shrink-0 w-72">
                <!-- Column Header -->
                <div class="flex items-center gap-2 mb-3 px-1">
                    <div class="w-3 h-3 rounded-full flex-shrink-0" :style="'background:' + col.color"></div>
                    <span class="text-sm font-semibold text-white" x-text="col.name"></span>
                    <span class="ml-auto text-xs text-gray-500 bg-gray-800 rounded-full px-2 py-0.5"
                          x-text="getTasksForColumn(col.id).length"></span>
                </div>

                <!-- Cards -->
                <div class="space-y-3 min-h-8">
                    <template x-for="task in getTasksForColumn(col.id)" :key="task.id">
                        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 cursor-pointer hover:border-gray-600 transition-colors"
                             @click="openDetail(task)">
                            <div class="text-sm font-medium text-white mb-2" x-text="task.title"></div>
                            <template x-if="task.description">
                                <div class="text-xs text-gray-500 mb-2 line-clamp-2" x-text="task.description"></div>
                            </template>
                            <div class="flex items-center gap-2 mt-2">
                                <template x-if="task.deadline">
                                    <span class="text-xs px-2 py-0.5 rounded-full"
                                          :class="isPastDeadline(task.deadline) ? 'bg-red-900/40 text-red-400' : 'bg-gray-800 text-gray-400'"
                                          x-text="formatDate(task.deadline)"></span>
                                </template>
                                <template x-if="task.priority">
                                    <span class="text-xs px-2 py-0.5 rounded-full"
                                          :class="{'bg-red-900/30 text-red-400': task.priority==='high', 'bg-yellow-900/30 text-yellow-400': task.priority==='medium', 'bg-gray-800 text-gray-400': task.priority==='low'}"
                                          x-text="{'high':'Hoch','medium':'Mittel','low':'Niedrig'}[task.priority] || task.priority"></span>
                                </template>
                                <template x-if="task.assignee_name">
                                    <span class="ml-auto text-xs text-gray-500" x-text="task.assignee_name"></span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Add card quick button -->
                <button @click="openCreateInStatus(col.id)"
                        class="w-full mt-3 text-xs text-gray-600 hover:text-gray-400 py-2 rounded-xl hover:bg-gray-900 border border-dashed border-gray-800 hover:border-gray-700 transition-all">
                    + Aufgabe
                </button>
            </div>
        </template>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="text-gray-500 text-sm">Laden…</div>

    <!-- Task Modal -->
    <div x-show="modal.open" x-cloak
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-start justify-end"
         @click.self="modal.open = false">
        <div class="bg-gray-900 border-l border-gray-800 h-full w-full max-w-xl overflow-y-auto shadow-2xl"
             @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-800 sticky top-0 bg-gray-900 z-10">
                <h2 class="font-semibold text-white" x-text="modal.isNew ? 'Neue Aufgabe' : 'Aufgabe bearbeiten'"></h2>
                <button @click="modal.open = false" class="text-gray-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Titel *</label>
                    <input x-model="form.title" type="text"
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"
                           placeholder="Aufgabentitel">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Beschreibung</label>
                    <textarea x-model="form.description" rows="3"
                              class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 resize-none"
                              placeholder="Details…"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Status</label>
                        <select x-model="form.status_id"
                                class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <template x-for="s in statuses" :key="s.id">
                                <option :value="s.id" x-text="s.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Priorität</label>
                        <select x-model="form.priority"
                                class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="">–</option>
                            <option value="low">Niedrig</option>
                            <option value="medium">Mittel</option>
                            <option value="high">Hoch</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Zugewiesen an</label>
                        <select x-model="form.assigned_to"
                                class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="">–</option>
                            <template x-for="u in users" :key="u.id">
                                <option :value="u.id" x-text="u.first_name + ' ' + u.last_name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Fälligkeitsdatum</label>
                        <input x-model="form.deadline" type="date"
                               class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Projekt</label>
                    <select x-model="form.project_id"
                            class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="">–</option>
                        <template x-for="p in projects" :key="p.id">
                            <option :value="p.id" x-text="p.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Geschätzter Aufwand (Stunden)</label>
                    <input x-model="form.estimated_hours" type="number" step="0.5" min="0"
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>

                <!-- Comments (only in edit mode) -->
                <template x-if="!modal.isNew && modal.task">
                    <div>
                        <h4 class="text-xs font-medium text-gray-400 mb-2">Kommentare</h4>
                        <template x-for="c in comments" :key="c.id">
                            <div class="py-2 border-b border-gray-800 last:border-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-medium text-white" x-text="c.author_name || 'Unbekannt'"></span>
                                    <span class="text-xs text-gray-600" x-text="formatDate(c.created_at)"></span>
                                </div>
                                <p class="text-sm text-gray-300" x-text="c.content"></p>
                            </div>
                        </template>
                        <div class="flex gap-2 mt-3">
                            <input x-model="newComment" type="text" placeholder="Kommentar…"
                                   @keydown.enter="addComment()"
                                   class="flex-1 bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <button @click="addComment()"
                                    class="bg-brand-500 hover:bg-brand-600 text-white px-3 py-2 rounded-xl text-sm">
                                Senden
                            </button>
                        </div>
                    </div>
                </template>
            </div>
            <div class="px-6 py-4 border-t border-gray-800 flex items-center gap-3">
                <button @click="saveTask()"
                        class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors">
                    <span x-text="modal.isNew ? 'Erstellen' : 'Speichern'"></span>
                </button>
                <template x-if="!modal.isNew">
                    <button @click="deleteTask()"
                            class="bg-red-900/40 hover:bg-red-900/60 text-red-400 text-sm font-medium px-4 py-2 rounded-xl transition-colors">
                        Löschen
                    </button>
                </template>
                <button @click="modal.open = false" class="text-gray-400 hover:text-white text-sm ml-auto">
                    Abbrechen
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function tasksApp() {
    return {
        tasks: [],
        statuses: [],
        users: [],
        projects: [],
        comments: [],
        loading: true,
        filterStatus: '',
        filterAssignee: '',
        filterSearch: '',
        newComment: '',
        modal: { open: false, isNew: true, task: null },
        form: {},

        get columns() {
            return this.statuses;
        },

        async init() {
            await Promise.all([this.loadStatuses(), this.loadUsers(), this.loadProjects()]);
            await this.loadTasks();
        },

        async loadStatuses() {
            const r = await fetch('/api/tasks/statuses');
            this.statuses = await r.json();
        },

        async loadUsers() {
            const r = await fetch('/api/users');
            const data = await r.json();
            this.users = Array.isArray(data) ? data : (data.users || []);
        },

        async loadProjects() {
            const r = await fetch('/api/projects?limit=200');
            const data = await r.json();
            this.projects = Array.isArray(data) ? data : (data.projects || []);
        },

        async loadTasks() {
            this.loading = true;
            let url = '/api/tasks?limit=200';
            if (this.filterStatus) url += '&status_id=' + this.filterStatus;
            if (this.filterAssignee === 'me') url += '&my=1';
            else if (this.filterAssignee) url += '&assigned_to=' + this.filterAssignee;
            if (this.filterSearch) url += '&search=' + encodeURIComponent(this.filterSearch);
            const r = await fetch(url);
            const data = await r.json();
            this.tasks = Array.isArray(data) ? data : (data.tasks || []);
            this.loading = false;
        },

        getTasksForColumn(statusId) {
            return this.tasks.filter(t => t.status_id === statusId);
        },

        openCreate() {
            this.form = { title: '', description: '', status_id: this.statuses[0]?.id || '', priority: 'medium', assigned_to: '', deadline: '', project_id: '', estimated_hours: '' };
            this.modal = { open: true, isNew: true, task: null };
        },

        openCreateInStatus(statusId) {
            this.openCreate();
            this.form.status_id = statusId;
        },

        async openDetail(task) {
            this.form = { ...task };
            this.modal = { open: true, isNew: false, task };
            this.comments = [];
            const r = await fetch('/api/tasks/' + task.id + '/comments');
            const data = await r.json();
            this.comments = Array.isArray(data) ? data : [];
        },

        async saveTask() {
            if (!this.form.title?.trim()) return alert('Titel ist erforderlich');
            if (this.modal.isNew) {
                const r = await fetch('/api/tasks', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.form) });
                if (r.ok) { this.modal.open = false; await this.loadTasks(); }
                else { const e = await r.json(); alert(e.detail || 'Fehler'); }
            } else {
                const r = await fetch('/api/tasks/' + this.modal.task.id, { method: 'PUT', headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.form) });
                if (r.ok) { this.modal.open = false; await this.loadTasks(); }
                else { const e = await r.json(); alert(e.detail || 'Fehler'); }
            }
        },

        async deleteTask() {
            if (!confirm('Aufgabe löschen?')) return;
            await fetch('/api/tasks/' + this.modal.task.id, { method: 'DELETE' });
            this.modal.open = false;
            await this.loadTasks();
        },

        async addComment() {
            if (!this.newComment.trim()) return;
            const r = await fetch('/api/tasks/' + this.modal.task.id + '/comments', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ content: this.newComment })
            });
            if (r.ok) {
                this.newComment = '';
                const cr = await fetch('/api/tasks/' + this.modal.task.id + '/comments');
                const data = await cr.json();
                this.comments = Array.isArray(data) ? data : [];
            }
        },

        formatDate(d) {
            if (!d) return '';
            return new Date(d).toLocaleDateString('de-DE', {day:'2-digit',month:'2-digit',year:'numeric'});
        },

        isPastDeadline(d) {
            return d && new Date(d) < new Date();
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

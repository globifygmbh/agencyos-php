<?php $pageTitle = 'Aufgaben'; ?>
<?php require __DIR__ . '/_layout.php'; ?>

<div x-data="tasksApp()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center gap-3 mb-6 flex-wrap">
        <div class="flex-1 flex items-center gap-2 flex-wrap">
            <select x-model="filterStatus" @change="loadTasks()" class="input-field w-auto">
                <option value="">Alle Status</option>
                <template x-for="s in statuses" :key="s.id">
                    <option :value="s.id" x-text="s.name"></option>
                </template>
            </select>
            <select x-model="filterAssignee" @change="loadTasks()" class="input-field w-auto">
                <option value="">Alle Mitarbeiter</option>
                <option value="me">Mir zugewiesen</option>
                <template x-for="u in users" :key="u.id">
                    <option :value="u.id" x-text="u.first_name + ' ' + u.last_name"></option>
                </template>
            </select>
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="search" x-model.debounce.300ms="filterSearch" @input="loadTasks()"
                       placeholder="Suche…" class="input-field pl-9 w-44">
            </div>
        </div>
        <button @click="openCreate()" class="btn-primary">
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
                    <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="'background:' + col.color"></div>
                    <span class="text-sm font-semibold text-white font-heading" x-text="col.name"></span>
                    <span class="ml-auto text-xs text-white/40 bg-white/8 rounded-full px-2 py-0.5"
                          x-text="getTasksForColumn(col.id).length"></span>
                </div>

                <!-- Task Cards -->
                <div class="space-y-2 min-h-8">
                    <template x-for="task in getTasksForColumn(col.id)" :key="task.id">
                        <div class="glass-card p-4 cursor-pointer hover:border-white/20 transition-all hover:-translate-y-0.5"
                             style="border: 1px solid rgba(255,255,255,0.08);"
                             @click="openDetail(task)">
                            <div class="text-sm font-medium text-white mb-2" x-text="task.title"></div>
                            <template x-if="task.description">
                                <div class="text-xs text-white/40 mb-2 line-clamp-2" x-text="task.description"></div>
                            </template>
                            <div class="flex items-center gap-2 mt-2 flex-wrap">
                                <template x-if="task.deadline">
                                    <span class="text-xs px-2 py-0.5 rounded-full"
                                          :class="isPastDeadline(task.deadline) ? 'bg-red-500/15 text-red-400' : 'bg-white/8 text-white/40'"
                                          x-text="formatDate(task.deadline)"></span>
                                </template>
                                <template x-if="task.priority">
                                    <span class="text-xs px-2 py-0.5 rounded-full"
                                          :class="{'bg-red-500/15 text-red-400': task.priority==='high', 'bg-yellow-500/15 text-yellow-400': task.priority==='medium', 'bg-white/8 text-white/30': task.priority==='low'}"
                                          x-text="{'high':'🔴 Hoch','medium':'🟡 Mittel','low':'🟢 Niedrig'}[task.priority] || task.priority"></span>
                                </template>
                                <template x-if="task.assignee_name">
                                    <span class="ml-auto text-xs text-white/30" x-text="task.assignee_name"></span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Add card button -->
                <button @click="openCreateInStatus(col.id)"
                        class="w-full mt-2 text-xs text-white/25 hover:text-white/50 py-2.5 rounded-xl hover:bg-white/4 border border-dashed border-white/10 hover:border-white/20 transition-all">
                    + Aufgabe
                </button>
            </div>
        </template>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="flex items-center justify-center py-16">
        <svg class="w-6 h-6 animate-spin text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
    </div>

    <!-- Task Modal (slide-in panel) -->
    <div x-show="modal.open" x-cloak
         class="fixed inset-0 z-50"
         style="background: rgba(0,0,0,0.6); backdrop-filter: blur(8px);"
         @click.self="modal.open = false">
        <div class="absolute inset-y-0 right-0 w-full max-w-xl flex flex-col"
             style="background: #0a0a0a; border-left: 1px solid rgba(255,255,255,0.08);"
             @click.stop>
            <!-- Panel Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-white/8 flex-shrink-0"
                 style="background: rgba(0,0,0,0.5); backdrop-filter: blur(20px);">
                <h2 class="font-heading font-semibold text-white" x-text="modal.isNew ? 'Neue Aufgabe' : 'Aufgabe bearbeiten'"></h2>
                <button @click="modal.open = false" class="p-2 rounded-xl text-white/40 hover:text-white hover:bg-white/8 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Panel Body -->
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-white/50 mb-1.5">Titel *</label>
                    <input x-model="form.title" type="text" class="input-field" placeholder="Aufgabentitel">
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/50 mb-1.5">Beschreibung</label>
                    <textarea x-model="form.description" rows="3" class="input-field resize-none" placeholder="Details…"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-white/50 mb-1.5">Status</label>
                        <select x-model="form.status_id" class="input-field">
                            <template x-for="s in statuses" :key="s.id">
                                <option :value="s.id" x-text="s.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/50 mb-1.5">Priorität</label>
                        <select x-model="form.priority" class="input-field">
                            <option value="">–</option>
                            <option value="low">🟢 Niedrig</option>
                            <option value="medium">🟡 Mittel</option>
                            <option value="high">🔴 Hoch</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-white/50 mb-1.5">Zugewiesen an</label>
                        <select x-model="form.assigned_to" class="input-field">
                            <option value="">–</option>
                            <template x-for="u in users" :key="u.id">
                                <option :value="u.id" x-text="u.first_name + ' ' + u.last_name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/50 mb-1.5">Fälligkeitsdatum</label>
                        <input x-model="form.deadline" type="date" class="input-field">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-white/50 mb-1.5">Projekt</label>
                        <select x-model="form.project_id" class="input-field">
                            <option value="">–</option>
                            <template x-for="p in projects" :key="p.id">
                                <option :value="p.id" x-text="p.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/50 mb-1.5">Aufwand (Std.)</label>
                        <input x-model="form.estimated_hours" type="number" step="0.5" min="0" class="input-field">
                    </div>
                </div>

                <!-- Comments -->
                <template x-if="!modal.isNew">
                    <div class="pt-2 border-t border-white/8">
                        <h4 class="text-xs font-semibold text-white/40 mb-3 uppercase tracking-wider">Kommentare</h4>
                        <div class="space-y-3 mb-4">
                            <template x-for="c in comments" :key="c.id">
                                <div class="glass-light p-3 rounded-xl">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs font-medium text-white" x-text="c.author_name || 'Unbekannt'"></span>
                                        <span class="text-xs text-white/30" x-text="formatDate(c.created_at)"></span>
                                    </div>
                                    <p class="text-sm text-white/70" x-text="c.content"></p>
                                </div>
                            </template>
                        </div>
                        <div class="flex gap-2">
                            <input x-model="newComment" type="text" placeholder="Kommentar…"
                                   @keydown.enter="addComment()"
                                   class="input-field flex-1">
                            <button @click="addComment()" class="btn-primary px-3 py-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Panel Footer -->
            <div class="px-6 py-4 border-t border-white/8 flex items-center gap-3 flex-shrink-0" style="background: rgba(0,0,0,0.3);">
                <button @click="saveTask()" class="btn-primary">
                    <span x-text="modal.isNew ? 'Erstellen' : 'Speichern'"></span>
                </button>
                <template x-if="!modal.isNew">
                    <button @click="deleteTask()"
                            class="px-4 py-2 rounded-xl text-sm font-medium text-red-400 hover:bg-red-500/10 transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Löschen
                    </button>
                </template>
                <button @click="modal.open = false" class="ml-auto text-sm text-white/40 hover:text-white transition-colors">Abbrechen</button>
            </div>
        </div>
    </div>
</div>

<script>
function tasksApp() {
    return {
        tasks: [], statuses: [], users: [], projects: [], comments: [],
        loading: true, filterStatus: '', filterAssignee: '', filterSearch: '',
        newComment: '',
        modal: { open: false, isNew: true, task: null },
        form: {},

        get columns() { return this.statuses; },

        async init() {
            await Promise.all([this.loadStatuses(), this.loadUsers(), this.loadProjects()]);
            await this.loadTasks();
            // Support deep-link ?task=ID
            const params = new URLSearchParams(window.location.search);
            if (params.get('task')) {
                const task = this.tasks.find(t => t.id === params.get('task'));
                if (task) this.openDetail(task);
            }
            window.addEventListener('openTaskModal', e => {
                this.openCreate();
                if (e.detail?.prefill) Object.assign(this.form, e.detail.prefill);
            });
        },

        async loadStatuses() {
            const r = await fetch('/api/tasks/statuses');
            this.statuses = await r.json();
        },
        async loadUsers() {
            const r = await fetch('/api/users');
            const d = await r.json();
            this.users = Array.isArray(d) ? d : (d.users || []);
        },
        async loadProjects() {
            const r = await fetch('/api/projects?limit=200');
            const d = await r.json();
            this.projects = Array.isArray(d) ? d : (d.projects || []);
        },
        async loadTasks() {
            this.loading = true;
            let url = '/api/tasks?limit=200';
            if (this.filterStatus) url += '&status_id=' + this.filterStatus;
            if (this.filterAssignee === 'me') url += '&my=1';
            else if (this.filterAssignee) url += '&assigned_to=' + this.filterAssignee;
            if (this.filterSearch) url += '&search=' + encodeURIComponent(this.filterSearch);
            const r = await fetch(url);
            const d = await r.json();
            this.tasks = Array.isArray(d) ? d : (d.tasks || []);
            this.loading = false;
        },
        getTasksForColumn(statusId) { return this.tasks.filter(t => t.status_id === statusId); },
        openCreate() {
            this.form = { title: '', description: '', status_id: this.statuses[0]?.id || '', priority: 'medium', assigned_to: '', deadline: '', project_id: '', estimated_hours: '' };
            this.modal = { open: true, isNew: true, task: null };
        },
        openCreateInStatus(statusId) { this.openCreate(); this.form.status_id = statusId; },
        async openDetail(task) {
            this.form = { ...task };
            this.modal = { open: true, isNew: false, task };
            this.comments = [];
            const r = await fetch('/api/tasks/' + task.id + '/comments');
            const d = await r.json();
            this.comments = Array.isArray(d) ? d : [];
        },
        async saveTask() {
            if (!this.form.title?.trim()) return alert('Titel ist erforderlich');
            const method = this.modal.isNew ? 'POST' : 'PUT';
            const url = this.modal.isNew ? '/api/tasks' : '/api/tasks/' + this.modal.task.id;
            const r = await fetch(url, { method, headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.form) });
            if (r.ok) { this.modal.open = false; await this.loadTasks(); }
            else { const e = await r.json(); alert(e.detail || 'Fehler'); }
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
                method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ content: this.newComment })
            });
            if (r.ok) {
                this.newComment = '';
                const cr = await fetch('/api/tasks/' + this.modal.task.id + '/comments');
                const d = await cr.json();
                this.comments = Array.isArray(d) ? d : [];
            }
        },
        formatDate(d) { if (!d) return ''; return new Date(d).toLocaleDateString('de-DE', {day:'2-digit',month:'2-digit',year:'numeric'}); },
        isPastDeadline(d) { return d && new Date(d) < new Date(); }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

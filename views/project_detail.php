<?php
$projectId = $routeParam ?? '';
$pageTitle = 'Projekt';
require __DIR__ . '/_layout.php';
?>

<div x-data="projectDetail('<?= htmlspecialchars($projectId) ?>')" x-init="init()">

    <div x-show="loading" class="text-gray-500 text-sm">Laden…</div>

    <div x-show="!loading && project">
        <!-- Header -->
        <div class="flex items-start gap-4 mb-6">
            <div class="flex-shrink-0 w-3 h-12 rounded-full mt-1" :style="'background:' + (project.color || '#6366f1')"></div>
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-1">
                    <h2 class="text-2xl font-bold text-white" x-text="project.name"></h2>
                    <span class="text-xs px-2.5 py-1 rounded-full font-medium"
                          :class="{
                            'bg-green-900/30 text-green-400': project.status === 'active',
                            'bg-blue-900/30 text-blue-400': project.status === 'completed',
                            'bg-yellow-900/30 text-yellow-400': project.status === 'on_hold',
                          }"
                          x-text="{active:'Aktiv',completed:'Abgeschlossen',on_hold:'Pausiert',cancelled:'Abgebrochen'}[project.status] || project.status"></span>
                </div>
                <p class="text-gray-400 text-sm" x-text="project.description || ''"></p>
            </div>
            <div class="flex items-center gap-2">
                <button @click="editModal = true"
                        class="bg-gray-800 hover:bg-gray-700 text-white text-sm px-3 py-2 rounded-xl transition-colors">
                    Bearbeiten
                </button>
                <a href="/projects" class="text-gray-400 hover:text-white text-sm">← Zurück</a>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
                <div class="text-xs text-gray-500 mb-1">Fortschritt</div>
                <div class="text-xl font-bold text-white" x-text="(project.progress || 0) + '%'"></div>
                <div class="h-1 bg-gray-800 rounded-full mt-2">
                    <div class="h-full rounded-full bg-brand-500" :style="'width:' + (project.progress || 0) + '%'"></div>
                </div>
            </div>
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
                <div class="text-xs text-gray-500 mb-1">Aufgaben</div>
                <div class="text-xl font-bold text-white" x-text="tasks.length"></div>
            </div>
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
                <div class="text-xs text-gray-500 mb-1">Zeiterfassung</div>
                <div class="text-xl font-bold text-white" x-text="formatDuration(totalTime)"></div>
            </div>
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
                <div class="text-xs text-gray-500 mb-1">Fälligkeitsdatum</div>
                <div class="text-xl font-bold text-white" x-text="formatDate(project.deadline) || '–'"></div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-800 mb-6">
            <div class="flex gap-1">
                <template x-for="tab in ['tasks','milestones','files','team','time']" :key="tab">
                    <button @click="activeTab = tab"
                            class="px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px"
                            :class="activeTab === tab ? 'text-brand-400 border-brand-500' : 'text-gray-400 border-transparent hover:text-white'"
                            x-text="{tasks:'Aufgaben',milestones:'Meilensteine',files:'Dateien',team:'Team',time:'Zeit'}[tab]"></button>
                </template>
            </div>
        </div>

        <!-- Tasks Tab -->
        <div x-show="activeTab === 'tasks'" class="space-y-3">
            <div class="flex justify-end mb-3">
                <button @click="openCreateTask()"
                        class="bg-brand-500 hover:bg-brand-600 text-white text-sm px-3 py-2 rounded-xl transition-colors">
                    + Aufgabe
                </button>
            </div>
            <template x-for="task in tasks" :key="task.id">
                <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 flex items-center gap-4">
                    <div class="w-3 h-3 rounded-full flex-shrink-0" :style="'background:' + (task.status_color || '#6b7280')"></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-white" x-text="task.title"></div>
                        <div class="text-xs text-gray-500 mt-0.5" x-text="task.status_name || '–'"></div>
                    </div>
                    <div class="text-xs text-gray-500" x-text="task.assignee_name || '–'"></div>
                    <template x-if="task.deadline">
                        <div class="text-xs text-gray-500" x-text="formatDate(task.deadline)"></div>
                    </template>
                </div>
            </template>
            <template x-if="tasks.length === 0">
                <p class="text-gray-500 text-sm text-center py-8">Keine Aufgaben in diesem Projekt</p>
            </template>
        </div>

        <!-- Milestones Tab -->
        <div x-show="activeTab === 'milestones'" class="space-y-3">
            <div class="flex justify-end mb-3">
                <button @click="openCreateMilestone()"
                        class="bg-brand-500 hover:bg-brand-600 text-white text-sm px-3 py-2 rounded-xl transition-colors">
                    + Meilenstein
                </button>
            </div>
            <template x-for="m in milestones" :key="m.id">
                <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 flex items-center gap-4">
                    <div class="w-5 h-5 rounded-full flex-shrink-0 flex items-center justify-center"
                         :class="m.is_completed ? 'bg-green-500' : 'bg-gray-700'">
                        <svg x-show="m.is_completed" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-medium text-white" x-text="m.title"></div>
                        <div class="text-xs text-gray-500 mt-0.5" x-text="m.description || ''"></div>
                    </div>
                    <div class="text-xs text-gray-500" x-text="formatDate(m.due_date) || '–'"></div>
                    <template x-if="!m.is_completed">
                        <button @click="completeMilestone(m.id)"
                                class="text-xs text-green-400 hover:text-green-300 px-2 py-1 rounded-lg bg-green-900/20">
                            Abschließen
                        </button>
                    </template>
                </div>
            </template>
        </div>

        <!-- Team Tab -->
        <div x-show="activeTab === 'team'" class="space-y-3">
            <template x-for="m in teamMembers" :key="m.id">
                <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 flex items-center gap-4">
                    <div class="w-8 h-8 rounded-full bg-brand-500 flex items-center justify-center text-white text-xs font-bold">
                        <span x-text="(m.first_name || '?').charAt(0).toUpperCase()"></span>
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-medium text-white" x-text="m.first_name + ' ' + m.last_name"></div>
                        <div class="text-xs text-gray-500" x-text="m.role_in_project || m.role || ''"></div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Files Tab -->
        <div x-show="activeTab === 'files'" class="space-y-3">
            <div class="mb-3">
                <label class="bg-brand-500 hover:bg-brand-600 text-white text-sm px-3 py-2 rounded-xl cursor-pointer transition-colors">
                    Datei hochladen
                    <input type="file" class="hidden" @change="uploadFile($event)">
                </label>
            </div>
            <template x-for="f in files" :key="f.id">
                <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 flex items-center gap-4">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <div class="flex-1 min-w-0">
                        <a :href="f.file_url" target="_blank" class="text-sm text-brand-400 hover:text-brand-300 truncate block" x-text="f.file_name"></a>
                        <div class="text-xs text-gray-500 mt-0.5" x-text="formatDate(f.created_at)"></div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Time Tab -->
        <div x-show="activeTab === 'time'" class="space-y-3">
            <template x-for="entry in timeEntries" :key="entry.id">
                <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 flex items-center gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm text-white" x-text="entry.description || 'Kein Titel'"></div>
                        <div class="text-xs text-gray-500 mt-0.5" x-text="entry.user_name + ' · ' + formatDate(entry.start_time)"></div>
                    </div>
                    <div class="text-sm font-medium text-white" x-text="formatDuration(entry.duration || 0)"></div>
                </div>
            </template>
            <div class="text-right text-sm font-semibold text-white" x-show="timeEntries.length > 0">
                Gesamt: <span x-text="formatDuration(totalTime)"></span>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div x-show="editModal" x-cloak
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         @click.self="editModal = false">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl w-full max-w-lg shadow-2xl" @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-800">
                <h2 class="font-semibold text-white">Projekt bearbeiten</h2>
                <button @click="editModal = false" class="text-gray-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Name</label>
                    <input x-model="editForm.name" type="text"
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Beschreibung</label>
                    <textarea x-model="editForm.description" rows="2"
                              class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 resize-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Status</label>
                        <select x-model="editForm.status"
                                class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="active">Aktiv</option>
                            <option value="on_hold">Pausiert</option>
                            <option value="completed">Abgeschlossen</option>
                            <option value="cancelled">Abgebrochen</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Farbe</label>
                        <input x-model="editForm.color" type="color"
                               class="w-full h-10 bg-gray-800 border border-gray-700 rounded-xl px-2 py-1">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Fälligkeitsdatum</label>
                        <input x-model="editForm.deadline" type="date"
                               class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Budget (€)</label>
                        <input x-model="editForm.budget" type="number"
                               class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-800 flex gap-3">
                <button @click="saveEdit()"
                        class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-xl">
                    Speichern
                </button>
                <button @click="editModal = false" class="text-gray-400 hover:text-white text-sm">Abbrechen</button>
            </div>
        </div>
    </div>

    <!-- Milestone Modal -->
    <div x-show="milestoneModal" x-cloak
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         @click.self="milestoneModal = false">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl w-full max-w-md shadow-2xl" @click.stop>
            <div class="px-6 py-4 border-b border-gray-800">
                <h2 class="font-semibold text-white">Neuer Meilenstein</h2>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Titel *</label>
                    <input x-model="milestoneForm.title" type="text"
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Beschreibung</label>
                    <input x-model="milestoneForm.description" type="text"
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Fälligkeitsdatum</label>
                    <input x-model="milestoneForm.due_date" type="date"
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-800 flex gap-3">
                <button @click="saveMilestone()"
                        class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-xl">
                    Erstellen
                </button>
                <button @click="milestoneModal = false" class="text-gray-400 hover:text-white text-sm">Abbrechen</button>
            </div>
        </div>
    </div>
</div>

<script>
function projectDetail(projectId) {
    return {
        projectId,
        project: null,
        tasks: [],
        milestones: [],
        files: [],
        teamMembers: [],
        timeEntries: [],
        totalTime: 0,
        loading: true,
        activeTab: 'tasks',
        editModal: false,
        editForm: {},
        milestoneModal: false,
        milestoneForm: {},

        async init() {
            const [pr, tr, mr, fr, tm, te] = await Promise.all([
                fetch('/api/projects/' + projectId).then(r => r.json()),
                fetch('/api/tasks?project_id=' + projectId + '&limit=200').then(r => r.json()),
                fetch('/api/projects/' + projectId + '/milestones').then(r => r.json()),
                fetch('/api/projects/' + projectId + '/files').then(r => r.json()),
                fetch('/api/projects/' + projectId + '/members').then(r => r.json()),
                fetch('/api/projects/' + projectId + '/time').then(r => r.json()),
            ]);
            this.project = pr;
            this.editForm = { ...pr };
            this.tasks = Array.isArray(tr) ? tr : (tr.tasks || []);
            this.milestones = Array.isArray(mr) ? mr : [];
            this.files = Array.isArray(fr) ? fr : [];
            this.teamMembers = Array.isArray(tm) ? tm : [];
            this.timeEntries = Array.isArray(te) ? te : [];
            this.totalTime = this.timeEntries.reduce((s, e) => s + (e.duration || 0), 0);
            this.loading = false;
        },

        async saveEdit() {
            const r = await fetch('/api/projects/' + this.projectId, { method: 'PUT', headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.editForm) });
            if (r.ok) { const p = await r.json(); this.project = p; this.editModal = false; }
            else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        openCreateMilestone() {
            this.milestoneForm = { title: '', description: '', due_date: '' };
            this.milestoneModal = true;
        },

        async saveMilestone() {
            const r = await fetch('/api/projects/' + this.projectId + '/milestones', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.milestoneForm) });
            if (r.ok) { const m = await r.json(); this.milestones.push(m); this.milestoneModal = false; }
            else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        async completeMilestone(id) {
            await fetch('/api/projects/' + this.projectId + '/milestones/' + id + '/complete', { method: 'POST' });
            const i = this.milestones.findIndex(m => m.id === id);
            if (i !== -1) this.milestones[i].is_completed = true;
        },

        async uploadFile(ev) {
            const file = ev.target.files[0];
            if (!file) return;
            const fd = new FormData();
            fd.append('file', file);
            const r = await fetch('/api/projects/' + this.projectId + '/files', { method: 'POST', body: fd });
            if (r.ok) { const f = await r.json(); this.files.push(f); }
        },

        formatDate(d) {
            if (!d) return '';
            return new Date(d).toLocaleDateString('de-DE', {day:'2-digit',month:'2-digit',year:'numeric'});
        },

        formatDuration(secs) {
            const h = Math.floor(secs / 3600);
            const m = Math.floor((secs % 3600) / 60);
            return h + 'h ' + String(m).padStart(2,'0') + 'm';
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

<?php
$pageTitle = 'Projekte';
require __DIR__ . '/_layout.php';
?>

<div x-data="projectsApp()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center gap-4 mb-6">
        <div class="flex-1">
            <input type="search" x-model.debounce.300ms="search" @input="load()"
                   placeholder="Projekte suchen…"
                   class="input-field w-72">
        </div>
        <select x-model="filterStatus" @change="load()" class="input-field">
            <option value="">Alle Status</option>
            <option value="active">Aktiv</option>
            <option value="completed">Abgeschlossen</option>
            <option value="on_hold">Pausiert</option>
            <option value="cancelled">Abgebrochen</option>
        </select>
        <button @click="openCreate()"
                class="btn-primary flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Neues Projekt
        </button>
    </div>

    <!-- Grid -->
    <div x-show="loading" class="text-white/50 text-sm">Laden…</div>
    <div x-show="!loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <template x-for="p in projects" :key="p.id">
            <a :href="'/projects/' + p.id"
               class="bg-white/5 border border-white/8 rounded-2xl p-5 hover:border-white/20 transition-all block">
                <!-- Color bar -->
                <div class="h-1 rounded-full mb-4" :style="'background:' + (p.color || '#6366f1')"></div>
                <div class="flex items-start justify-between gap-2 mb-2">
                    <h3 class="font-heading font-semibold text-white text-sm leading-tight" x-text="p.name"></h3>
                    <span class="text-xs px-2 py-0.5 rounded-full flex-shrink-0"
                          :class="{
                            'bg-green-900/30 text-green-400': p.status === 'active',
                            'bg-blue-900/30 text-blue-400': p.status === 'completed',
                            'bg-yellow-900/30 text-yellow-400': p.status === 'on_hold',
                            'bg-red-900/30 text-red-400': p.status === 'cancelled',
                          }"
                          x-text="{active:'Aktiv',completed:'Abgeschlossen',on_hold:'Pausiert',cancelled:'Abgebrochen'}[p.status] || p.status"></span>
                </div>
                <p class="text-xs text-white/50 line-clamp-2 mb-3" x-text="p.description || '–'"></p>
                <div class="flex items-center gap-3 text-xs text-white/50">
                    <template x-if="p.deadline">
                        <span x-text="'Fällig: ' + formatDate(p.deadline)"></span>
                    </template>
                    <template x-if="p.customer_name">
                        <span x-text="p.customer_name"></span>
                    </template>
                </div>
                <template x-if="p.progress !== undefined">
                    <div class="mt-3">
                        <div class="flex justify-between text-xs text-white/50 mb-1">
                            <span>Fortschritt</span>
                            <span x-text="(p.progress || 0) + '%'"></span>
                        </div>
                        <div class="h-1.5 bg-white/8 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all"
                                 style="background: #009dde;"
                                 :style="'width:' + (p.progress || 0) + '%; background: #009dde;'"></div>
                        </div>
                    </div>
                </template>
            </a>
        </template>
        <template x-if="!loading && projects.length === 0">
            <div class="col-span-3 text-center py-12 text-white/50">Keine Projekte gefunden</div>
        </template>
    </div>

    <!-- Create Modal -->
    <div x-show="modal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);"
         @click.self="modal = false">
        <div class="glass-card rounded-2xl w-full max-w-lg shadow-2xl" @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-white/10">
                <h2 class="font-heading font-semibold text-white">Neues Projekt</h2>
                <button @click="modal = false" class="text-white/40 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Name *</label>
                    <input x-model="form.name" type="text" class="input-field">
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Beschreibung</label>
                    <textarea x-model="form.description" rows="2" class="input-field resize-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Status</label>
                        <select x-model="form.status" class="input-field">
                            <option value="active">Aktiv</option>
                            <option value="on_hold">Pausiert</option>
                            <option value="completed">Abgeschlossen</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Farbe</label>
                        <input x-model="form.color" type="color"
                               class="w-full h-10 bg-white/8 border border-white/10 rounded-xl px-2 py-1">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Startdatum</label>
                        <input x-model="form.start_date" type="date" class="input-field">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Fälligkeitsdatum</label>
                        <input x-model="form.deadline" type="date" class="input-field">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Budget (€)</label>
                    <input x-model="form.budget" type="number" class="input-field">
                </div>
            </div>
            <div class="px-6 py-4 border-t border-white/10 flex gap-3">
                <button @click="saveProject()" class="btn-primary">
                    Erstellen
                </button>
                <button @click="modal = false" class="btn-ghost">Abbrechen</button>
            </div>
        </div>
    </div>
</div>

<script>
function projectsApp() {
    return {
        projects: [],
        loading: true,
        search: '',
        filterStatus: '',
        modal: false,
        form: {},

        async init() { await this.load(); },

        async load() {
            this.loading = true;
            let url = '/api/projects?limit=200';
            if (this.filterStatus) url += '&status=' + this.filterStatus;
            if (this.search) url += '&search=' + encodeURIComponent(this.search);
            const r = await fetch(url);
            const data = await r.json();
            this.projects = Array.isArray(data) ? data : (data.projects || []);
            this.loading = false;
        },

        openCreate() {
            this.form = { name: '', description: '', status: 'active', color: '#6366f1', start_date: '', deadline: '', budget: '' };
            this.modal = true;
        },

        async saveProject() {
            if (!this.form.name?.trim()) return alert('Name erforderlich');
            const r = await fetch('/api/projects', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.form) });
            if (r.ok) { const p = await r.json(); this.modal = false; window.location.href = '/projects/' + p.id; }
            else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        formatDate(d) {
            if (!d) return '';
            return new Date(d).toLocaleDateString('de-DE', {day:'2-digit',month:'2-digit',year:'numeric'});
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

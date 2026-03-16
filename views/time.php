<?php
$pageTitle = 'Zeiterfassung';
require __DIR__ . '/_layout.php';
?>

<div x-data="timeApp()" x-init="init()">

    <!-- Timer Card -->
    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 mb-6">
        <div class="flex items-center gap-6">
            <!-- Timer display -->
            <div class="text-4xl font-mono font-bold text-white w-36 tabular-nums" x-text="timerDisplay">00:00:00</div>

            <div class="flex-1 space-y-3">
                <input x-model="timerDesc" type="text" placeholder="Was arbeitest du gerade?"
                       class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                <div class="flex items-center gap-3">
                    <select x-model="timerProject"
                            class="bg-gray-800 border border-gray-700 text-white text-sm rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 flex-1">
                        <option value="">Kein Projekt</option>
                        <template x-for="p in projects" :key="p.id">
                            <option :value="p.id" x-text="p.name"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <template x-if="!timerRunning">
                    <button @click="startTimer()"
                            class="w-14 h-14 rounded-full bg-green-500 hover:bg-green-400 text-white flex items-center justify-center transition-colors shadow-lg">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                    </button>
                </template>
                <template x-if="timerRunning">
                    <button @click="stopTimer()"
                            class="w-14 h-14 rounded-full bg-red-500 hover:bg-red-400 text-white flex items-center justify-center transition-colors shadow-lg">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M6 6h12v12H6z"/>
                        </svg>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 text-center">
            <div class="text-xs text-gray-500 mb-1">Heute</div>
            <div class="text-xl font-bold text-white" x-text="formatDuration(stats.today || 0)"></div>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 text-center">
            <div class="text-xs text-gray-500 mb-1">Diese Woche</div>
            <div class="text-xl font-bold text-white" x-text="formatDuration(stats.week || 0)"></div>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 text-center">
            <div class="text-xs text-gray-500 mb-1">Dieser Monat</div>
            <div class="text-xl font-bold text-white" x-text="formatDuration(stats.month || 0)"></div>
        </div>
    </div>

    <!-- Filter + Manual Entry -->
    <div class="flex items-center gap-3 mb-4">
        <input type="date" x-model="filterDate" @change="loadEntries()"
               class="bg-gray-800 border border-gray-700 text-white text-sm rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
        <select x-model="filterProject" @change="loadEntries()"
                class="bg-gray-800 border border-gray-700 text-white text-sm rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
            <option value="">Alle Projekte</option>
            <template x-for="p in projects" :key="p.id">
                <option :value="p.id" x-text="p.name"></option>
            </template>
        </select>
        <button @click="manualModal = true"
                class="ml-auto bg-gray-800 hover:bg-gray-700 text-white text-sm px-3 py-2 rounded-xl transition-colors">
            + Manuell eintragen
        </button>
        <a href="/api/time/export?csv=1" target="_blank"
           class="bg-gray-800 hover:bg-gray-700 text-white text-sm px-3 py-2 rounded-xl transition-colors">
            CSV Export
        </a>
    </div>

    <!-- Entries -->
    <div x-show="loading" class="text-gray-500 text-sm">Laden…</div>
    <div x-show="!loading" class="space-y-2">
        <template x-for="entry in entries" :key="entry.id">
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 flex items-center gap-4">
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-white" x-text="entry.description || 'Kein Titel'"></div>
                    <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-500">
                        <span x-text="formatDateTime(entry.start_time)"></span>
                        <template x-if="entry.end_time">
                            <span x-text="'– ' + formatTime(entry.end_time)"></span>
                        </template>
                        <template x-if="entry.project_name">
                            <span class="px-1.5 py-0.5 bg-gray-800 rounded" x-text="entry.project_name"></span>
                        </template>
                    </div>
                </div>
                <div class="text-sm font-medium text-white font-mono" x-text="formatDuration(entry.duration || 0)"></div>
                <div class="flex items-center gap-1">
                    <button @click="deleteEntry(entry.id)"
                            class="text-gray-500 hover:text-red-400 p-1 rounded transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </template>
        <template x-if="!loading && entries.length === 0">
            <p class="text-gray-500 text-sm text-center py-8">Keine Einträge für diesen Zeitraum</p>
        </template>
    </div>

    <!-- Manual Entry Modal -->
    <div x-show="manualModal" x-cloak
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         @click.self="manualModal = false">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl w-full max-w-md shadow-2xl" @click.stop>
            <div class="px-6 py-4 border-b border-gray-800">
                <h2 class="font-semibold text-white">Manueller Eintrag</h2>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Beschreibung</label>
                    <input x-model="manualForm.description" type="text"
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Startzeit</label>
                        <input x-model="manualForm.start_time" type="datetime-local"
                               class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Endzeit</label>
                        <input x-model="manualForm.end_time" type="datetime-local"
                               class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Projekt</label>
                    <select x-model="manualForm.project_id"
                            class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="">–</option>
                        <template x-for="p in projects" :key="p.id">
                            <option :value="p.id" x-text="p.name"></option>
                        </template>
                    </select>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-800 flex gap-3">
                <button @click="saveManual()"
                        class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-xl">
                    Speichern
                </button>
                <button @click="manualModal = false" class="text-gray-400 text-sm">Abbrechen</button>
            </div>
        </div>
    </div>
</div>

<script>
function timeApp() {
    return {
        entries: [],
        projects: [],
        stats: {},
        loading: true,
        filterDate: new Date().toISOString().split('T')[0],
        filterProject: '',
        timerRunning: false,
        timerSeconds: 0,
        timerStart: null,
        timerInterval: null,
        timerDesc: '',
        timerProject: '',
        timerEntryId: null,
        manualModal: false,
        manualForm: {},

        get timerDisplay() {
            const h = Math.floor(this.timerSeconds / 3600);
            const m = Math.floor((this.timerSeconds % 3600) / 60);
            const s = this.timerSeconds % 60;
            return String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
        },

        async init() {
            const r = await fetch('/api/projects?limit=200');
            const d = await r.json();
            this.projects = Array.isArray(d) ? d : (d.projects || []);
            await this.loadEntries();
            await this.loadStats();
        },

        async loadEntries() {
            this.loading = true;
            let url = '/api/time?limit=100';
            if (this.filterDate) url += '&date=' + this.filterDate;
            if (this.filterProject) url += '&project_id=' + this.filterProject;
            const r = await fetch(url);
            const d = await r.json();
            this.entries = Array.isArray(d) ? d : (d.entries || []);
            this.loading = false;
        },

        async loadStats() {
            const r = await fetch('/api/time/stats');
            this.stats = await r.json();
        },

        async startTimer() {
            this.timerStart = Date.now();
            const r = await fetch('/api/time', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ description: this.timerDesc, project_id: this.timerProject || null, start_time: new Date().toISOString() })
            });
            if (r.ok) {
                const entry = await r.json();
                this.timerEntryId = entry.id;
                this.timerRunning = true;
                this.timerInterval = setInterval(() => {
                    this.timerSeconds = Math.floor((Date.now() - this.timerStart) / 1000);
                }, 1000);
            }
        },

        async stopTimer() {
            clearInterval(this.timerInterval);
            this.timerRunning = false;
            if (this.timerEntryId) {
                await fetch('/api/time/' + this.timerEntryId + '/stop', { method: 'POST' });
                this.timerEntryId = null;
            }
            this.timerSeconds = 0;
            this.timerDesc = '';
            this.timerProject = '';
            await this.loadEntries();
            await this.loadStats();
        },

        async deleteEntry(id) {
            if (!confirm('Eintrag löschen?')) return;
            await fetch('/api/time/' + id, { method: 'DELETE' });
            this.entries = this.entries.filter(e => e.id !== id);
            await this.loadStats();
        },

        async saveManual() {
            const r = await fetch('/api/time', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.manualForm) });
            if (r.ok) { this.manualModal = false; await this.loadEntries(); await this.loadStats(); }
            else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        formatDuration(secs) {
            const h = Math.floor(secs / 3600);
            const m = Math.floor((secs % 3600) / 60);
            return h + 'h ' + String(m).padStart(2,'0') + 'm';
        },

        formatDateTime(d) {
            if (!d) return '';
            return new Date(d).toLocaleDateString('de-DE', {day:'2-digit',month:'2-digit'}) + ' ' +
                   new Date(d).toLocaleTimeString('de-DE', {hour:'2-digit',minute:'2-digit'});
        },

        formatTime(d) {
            if (!d) return '';
            return new Date(d).toLocaleTimeString('de-DE', {hour:'2-digit',minute:'2-digit'});
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

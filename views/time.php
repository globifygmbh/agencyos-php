<?php $pageTitle = 'Zeiterfassung'; ?>
<?php require __DIR__ . '/_layout.php'; ?>

<div x-data="timeApp()" x-init="init()" class="max-w-4xl mx-auto">

    <!-- Timer Card -->
    <div class="glass-card p-6 mb-6" style="background: linear-gradient(135deg, rgba(0,157,222,0.08), rgba(0,157,222,0.03));">
        <div class="flex items-center gap-6">
            <div class="font-mono text-4xl font-bold text-white tabular-nums" x-text="timerDisplay">00:00:00</div>
            <div class="flex-1 space-y-2">
                <input x-model="timerDesc" type="text" placeholder="Was arbeitest du gerade?"
                       class="input-field">
                <select x-model="timerProject" class="input-field">
                    <option value="">Kein Projekt</option>
                    <template x-for="p in projects" :key="p.id">
                        <option :value="p.id" x-text="p.name"></option>
                    </template>
                </select>
            </div>
            <template x-if="!timerRunning">
                <button @click="startTimer()"
                        class="w-14 h-14 rounded-full flex items-center justify-center transition-all hover:scale-110 shadow-lg"
                        style="background: linear-gradient(135deg, #34c759, #30a845);">
                    <svg class="w-6 h-6 ml-0.5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </button>
            </template>
            <template x-if="timerRunning">
                <button @click="stopTimer()"
                        class="w-14 h-14 rounded-full flex items-center justify-center transition-all hover:scale-110 shadow-lg relative"
                        style="background: linear-gradient(135deg, #ff3b30, #cc2f26);">
                    <div class="absolute inset-0 rounded-full animate-ping opacity-20" style="background: #ff3b30;"></div>
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M6 6h12v12H6z"/></svg>
                </button>
            </template>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="glass-card p-4 text-center">
            <div class="text-xs text-white/40 mb-1">Heute</div>
            <div class="font-mono text-xl font-bold text-white" x-text="formatDuration(stats.today || 0)">0h 00m</div>
        </div>
        <div class="glass-card p-4 text-center">
            <div class="text-xs text-white/40 mb-1">Diese Woche</div>
            <div class="font-mono text-xl font-bold text-white" x-text="formatDuration(stats.week || 0)">0h 00m</div>
        </div>
        <div class="glass-card p-4 text-center">
            <div class="text-xs text-white/40 mb-1">Dieser Monat</div>
            <div class="font-mono text-xl font-bold text-white" x-text="formatDuration(stats.month || 0)">0h 00m</div>
        </div>
    </div>

    <!-- Filter + Actions -->
    <div class="flex items-center gap-2 mb-4 flex-wrap">
        <input type="date" x-model="filterDate" @change="loadEntries()" class="input-field w-auto">
        <select x-model="filterProject" @change="loadEntries()" class="input-field w-auto">
            <option value="">Alle Projekte</option>
            <template x-for="p in projects" :key="p.id">
                <option :value="p.id" x-text="p.name"></option>
            </template>
        </select>
        <div class="ml-auto flex gap-2">
            <button @click="manualModal = true" class="btn-ghost text-sm py-2 px-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Manuell
            </button>
            <a href="/api/time/export?csv=1" target="_blank" class="btn-ghost text-sm py-2 px-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                CSV
            </a>
        </div>
    </div>

    <!-- Entries -->
    <div x-show="loading" class="flex justify-center py-12">
        <svg class="w-6 h-6 animate-spin text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
    </div>
    <div x-show="!loading" class="space-y-2">
        <template x-for="entry in entries" :key="entry.id">
            <div class="glass-card p-4 flex items-center gap-4">
                <div class="w-1 h-10 rounded-full flex-shrink-0" style="background: #009dde;"></div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-white" x-text="entry.description || 'Kein Titel'"></div>
                    <div class="flex items-center gap-2 mt-0.5 text-xs text-white/40">
                        <span x-text="formatDateTime(entry.start_time)"></span>
                        <template x-if="entry.end_time">
                            <span x-text="'– ' + formatTime(entry.end_time)"></span>
                        </template>
                        <template x-if="entry.project_name">
                            <span class="px-1.5 py-0.5 bg-white/8 rounded-md" x-text="entry.project_name"></span>
                        </template>
                        <template x-if="entry.customer_name">
                            <span class="px-1.5 py-0.5 bg-primary-500/10 text-primary-400 rounded-md" x-text="entry.customer_name"></span>
                        </template>
                    </div>
                </div>
                <div class="font-mono text-sm font-semibold text-white" x-text="formatDuration(entry.duration || 0)"></div>
                <button @click="deleteEntry(entry.id)"
                        class="p-2 text-white/20 hover:text-red-400 rounded-xl hover:bg-red-500/10 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </template>
        <template x-if="!loading && entries.length === 0">
            <div class="text-center py-12 text-white/30">
                <svg class="w-10 h-10 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm">Keine Einträge für diesen Zeitraum</p>
            </div>
        </template>
    </div>

    <!-- Manual Entry Modal -->
    <div x-show="manualModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);"
         @click.self="manualModal = false">
        <div class="glass-card w-full max-w-md" @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-white/8">
                <h2 class="font-heading font-semibold text-white">Manueller Eintrag</h2>
                <button @click="manualModal = false" class="p-1.5 rounded-xl text-white/40 hover:text-white hover:bg-white/8 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-white/50 mb-1.5">Beschreibung</label>
                    <input x-model="manualForm.description" type="text" class="input-field">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-white/50 mb-1.5">Startzeit</label>
                        <input x-model="manualForm.start_time" type="datetime-local" class="input-field">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/50 mb-1.5">Endzeit</label>
                        <input x-model="manualForm.end_time" type="datetime-local" class="input-field">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/50 mb-1.5">Projekt</label>
                    <select x-model="manualForm.project_id" class="input-field">
                        <option value="">–</option>
                        <template x-for="p in projects" :key="p.id">
                            <option :value="p.id" x-text="p.name"></option>
                        </template>
                    </select>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-white/8 flex gap-3">
                <button @click="saveManual()" class="btn-primary">Speichern</button>
                <button @click="manualModal = false" class="text-white/40 hover:text-white text-sm transition-colors">Abbrechen</button>
            </div>
        </div>
    </div>
</div>

<script>
function timeApp() {
    return {
        entries: [], projects: [], stats: {}, loading: true,
        filterDate: new Date().toISOString().split('T')[0],
        filterProject: '',
        timerRunning: false, timerSeconds: 0, timerStart: null, timerInterval: null,
        timerDesc: '', timerProject: '', timerEntryId: null,
        manualModal: false, manualForm: {},

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
            // Restore active timer from layout state
            if (window.appLayoutData) {
                const layout = window.appLayoutData;
                if (layout.activeTimer) {
                    this.timerRunning = true;
                    this.timerEntryId = layout.activeTimer.id;
                    this.timerStart = new Date(layout.activeTimer.start_time).getTime();
                    this.timerSeconds = Math.floor((Date.now() - this.timerStart) / 1000);
                    this.timerDesc = layout.activeTimer.description || '';
                    this.timerInterval = setInterval(() => { this.timerSeconds = Math.floor((Date.now() - this.timerStart) / 1000); }, 1000);
                }
            }
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
            const r = await fetch('/api/time/start', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ description: this.timerDesc, project_id: this.timerProject || null, start_time: new Date().toISOString() })
            });
            if (r.ok) {
                const entry = await r.json();
                this.timerEntryId = entry.id;
                this.timerRunning = true;
                this.timerInterval = setInterval(() => { this.timerSeconds = Math.floor((Date.now() - this.timerStart) / 1000); }, 1000);
            }
        },

        async stopTimer() {
            clearInterval(this.timerInterval);
            this.timerRunning = false;
            if (this.timerEntryId) {
                await fetch('/api/time/' + this.timerEntryId + '/stop', { method: 'POST' });
                this.timerEntryId = null;
            }
            this.timerSeconds = 0; this.timerDesc = ''; this.timerProject = '';
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

        formatDuration(secs) { return Math.floor(secs/3600) + 'h ' + String(Math.floor((secs%3600)/60)).padStart(2,'0') + 'm'; },
        formatDateTime(d) { if (!d) return ''; const dt = new Date(d); return dt.toLocaleDateString('de-DE',{day:'2-digit',month:'2-digit'}) + ' ' + dt.toLocaleTimeString('de-DE',{hour:'2-digit',minute:'2-digit'}); },
        formatTime(d) { if (!d) return ''; return new Date(d).toLocaleTimeString('de-DE',{hour:'2-digit',minute:'2-digit'}); }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

<?php
$pageTitle = 'Urlaub';
require __DIR__ . '/_layout.php';
?>

<div x-data="vacationApp()" x-init="init()">

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6" x-show="stats">
        <div class="glass-card rounded-2xl p-5">
            <div class="text-xs text-white/50 mb-1">Urlaubstage gesamt</div>
            <div class="text-2xl font-bold text-white" x-text="stats.total_days || 0"></div>
        </div>
        <div class="glass-card rounded-2xl p-5">
            <div class="text-xs text-white/50 mb-1">Genommen</div>
            <div class="text-2xl font-bold text-white" x-text="stats.used_days || 0"></div>
        </div>
        <div class="glass-card rounded-2xl p-5">
            <div class="text-xs text-white/50 mb-1">Verbleibend</div>
            <div class="text-2xl font-bold text-green-400" x-text="stats.remaining_days || 0"></div>
        </div>
        <div class="glass-card rounded-2xl p-5">
            <div class="text-xs text-white/50 mb-1">Ausstehend</div>
            <div class="text-2xl font-bold text-yellow-400" x-text="stats.pending_days || 0"></div>
        </div>
    </div>

    <!-- Actions + Filter -->
    <div class="flex items-center gap-3 mb-6">
        <select x-model="filterStatus" @change="loadVacations()"
                class="input-field text-sm rounded-xl px-3 py-2">
            <option value="">Alle</option>
            <option value="pending">Ausstehend</option>
            <option value="approved">Genehmigt</option>
            <option value="rejected">Abgelehnt</option>
        </select>
        <button @click="openCreate()"
                class="btn-primary ml-auto flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Urlaub beantragen
        </button>
    </div>

    <!-- Vacation List -->
    <div x-show="loading" class="text-white/50 text-sm">Laden…</div>
    <div x-show="!loading" class="space-y-3">
        <template x-for="v in vacations" :key="v.id">
            <div class="bg-white/5 border border-white/8 rounded-xl p-4 flex items-center gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm font-medium text-white"
                              x-text="formatDate(v.start_date) + ' – ' + formatDate(v.end_date)"></span>
                        <span class="text-xs px-2 py-0.5 rounded-full"
                              :class="{
                                'bg-yellow-900/30 text-yellow-400': v.status === 'pending',
                                'bg-green-900/30 text-green-400': v.status === 'approved',
                                'bg-red-900/30 text-red-400': v.status === 'rejected',
                              }"
                              x-text="{pending:'Ausstehend',approved:'Genehmigt',rejected:'Abgelehnt'}[v.status] || v.status"></span>
                    </div>
                    <div class="text-xs text-white/50" x-text="v.reason || ''"></div>
                    <template x-if="v.user_name">
                        <div class="text-xs text-white/40 mt-0.5" x-text="'Von: ' + v.user_name"></div>
                    </template>
                </div>
                <div class="text-right">
                    <div class="text-sm font-medium text-white" x-text="v.days + ' Tag(e)'"></div>
                    <template x-if="isAdmin && v.status === 'pending'">
                        <div class="flex gap-2 mt-2">
                            <button @click="approve(v.id)"
                                    class="text-xs bg-green-900/30 text-green-400 px-2 py-1 rounded-lg hover:bg-green-900/50">
                                Genehmigen
                            </button>
                            <button @click="reject(v.id)"
                                    class="text-xs bg-red-900/30 text-red-400 px-2 py-1 rounded-lg hover:bg-red-900/50">
                                Ablehnen
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </template>
        <template x-if="!loading && vacations.length === 0">
            <p class="text-white/50 text-sm text-center py-8">Keine Urlaubsanträge</p>
        </template>
    </div>

    <!-- Team Overview -->
    <div class="mt-8 glass-card rounded-2xl p-5">
        <h3 class="text-sm font-heading font-semibold text-white mb-4">Team Übersicht – nächste 4 Wochen</h3>
        <div class="space-y-2">
            <template x-for="v in teamVacations" :key="v.id">
                <div class="flex items-center gap-3 text-sm">
                    <div class="w-28 flex-shrink-0 text-white/40 truncate" x-text="v.user_name"></div>
                    <div class="flex-1 text-white/70" x-text="formatDate(v.start_date) + ' – ' + formatDate(v.end_date)"></div>
                    <div class="text-xs text-white/50" x-text="v.days + ' T.'"></div>
                </div>
            </template>
            <template x-if="teamVacations.length === 0">
                <p class="text-white/50 text-xs">Keine Abwesenheiten in den nächsten 4 Wochen</p>
            </template>
        </div>
    </div>

    <!-- Create Modal -->
    <div x-show="modal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);"
         @click.self="modal = false">
        <div class="glass-card rounded-2xl w-full max-w-md shadow-2xl" @click.stop>
            <div class="px-6 py-4 border-b border-white/10">
                <h2 class="font-heading font-semibold text-white">Urlaub beantragen</h2>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Von *</label>
                        <input x-model="form.start_date" type="date" class="input-field">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Bis *</label>
                        <input x-model="form.end_date" type="date" class="input-field">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Grund (optional)</label>
                    <textarea x-model="form.reason" rows="2" class="input-field resize-none"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-white/10 flex gap-3">
                <button @click="submitVacation()" class="btn-primary">
                    Beantragen
                </button>
                <button @click="modal = false" class="btn-ghost">Abbrechen</button>
            </div>
        </div>
    </div>
</div>

<script>
function vacationApp() {
    return {
        vacations: [],
        teamVacations: [],
        stats: {},
        loading: true,
        filterStatus: '',
        modal: false,
        form: {},
        isAdmin: <?= json_encode(in_array($currentUser['role'] ?? '', ['admin', 'owner'])) ?>,

        async init() {
            await Promise.all([this.loadVacations(), this.loadStats(), this.loadTeam()]);
        },

        async loadVacations() {
            this.loading = true;
            let url = '/api/vacations?limit=100';
            if (this.filterStatus) url += '&status=' + this.filterStatus;
            const r = await fetch(url);
            const d = await r.json();
            this.vacations = Array.isArray(d) ? d : [];
            this.loading = false;
        },

        async loadStats() {
            const r = await fetch('/api/vacations/stats');
            if (r.ok) this.stats = await r.json();
        },

        async loadTeam() {
            const from = new Date().toISOString().split('T')[0];
            const to   = new Date(Date.now() + 28 * 86400000).toISOString().split('T')[0];
            const r = await fetch('/api/vacations/team?from=' + from + '&to=' + to);
            if (r.ok) { const d = await r.json(); this.teamVacations = Array.isArray(d) ? d : []; }
        },

        openCreate() {
            this.form = { start_date: '', end_date: '', reason: '' };
            this.modal = true;
        },

        async submitVacation() {
            if (!this.form.start_date || !this.form.end_date) return alert('Datum erforderlich');
            const r = await fetch('/api/vacations', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.form) });
            if (r.ok) { this.modal = false; await this.loadVacations(); await this.loadStats(); }
            else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        async approve(id) {
            await fetch('/api/vacations/' + id + '/approve', { method: 'POST' });
            await this.loadVacations();
        },

        async reject(id) {
            const reason = prompt('Ablehnungsgrund (optional):') ?? '';
            await fetch('/api/vacations/' + id + '/reject', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ reason }) });
            await this.loadVacations();
        },

        formatDate(d) {
            if (!d) return '';
            return new Date(d).toLocaleDateString('de-DE', {day:'2-digit',month:'2-digit',year:'numeric'});
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

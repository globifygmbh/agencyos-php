<?php
$pageTitle = 'Formulare';
require __DIR__ . '/_layout.php';
?>

<div x-data="formsApp()" x-init="init()">

    <!-- Tabs -->
    <div class="border-b border-white/10 mb-6">
        <div class="flex gap-1">
            <button @click="tab = 'expenses'"
                    class="px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px"
                    :class="tab === 'expenses' ? 'text-[#009dde] border-[#009dde]' : 'text-white/40 border-transparent hover:text-white'">
                Spesenabrechnung
            </button>
            <button @click="tab = 'shooting'"
                    class="px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px"
                    :class="tab === 'shooting' ? 'text-[#009dde] border-[#009dde]' : 'text-white/40 border-transparent hover:text-white'">
                Shooting Docs
            </button>
        </div>
    </div>

    <!-- Expenses Tab -->
    <div x-show="tab === 'expenses'">
        <div class="flex justify-end mb-4">
            <button @click="openExpense()"
                    class="btn-primary flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Neue Abrechnung
            </button>
        </div>

        <div x-show="loading" class="text-white/50 text-sm">Laden…</div>
        <div x-show="!loading" class="space-y-3">
            <template x-for="e in expenses" :key="e.id">
                <div class="bg-white/5 border border-white/8 rounded-xl p-4 flex items-start gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-white" x-text="e.title"></div>
                        <div class="flex items-center gap-2 mt-1 text-xs text-white/50">
                            <span x-text="formatDate(e.date)"></span>
                            <span x-text="'·'"></span>
                            <span x-text="e.category || 'Sonstiges'"></span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-bold text-white" x-text="formatMoney(e.amount) + ' €'"></div>
                        <span class="text-xs px-2 py-0.5 rounded-full"
                              :class="{
                                'bg-yellow-900/30 text-yellow-400': e.status === 'pending',
                                'bg-green-900/30 text-green-400': e.status === 'approved',
                                'bg-red-900/30 text-red-400': e.status === 'rejected',
                              }"
                              x-text="{pending:'Ausstehend',approved:'Genehmigt',rejected:'Abgelehnt'}[e.status] || e.status"></span>
                    </div>
                    <?php if (in_array($currentUser['role'] ?? '', ['admin', 'owner'])): ?>
                    <template x-if="e.status === 'pending'">
                        <div class="flex gap-1">
                            <button @click="approveExpense(e.id)"
                                    class="text-xs bg-green-900/30 text-green-400 px-2 py-1 rounded-lg">
                                ✓
                            </button>
                            <button @click="rejectExpense(e.id)"
                                    class="text-xs bg-red-900/30 text-red-400 px-2 py-1 rounded-lg">
                                ✗
                            </button>
                        </div>
                    </template>
                    <?php endif; ?>
                </div>
            </template>
            <template x-if="!loading && expenses.length === 0">
                <p class="text-white/50 text-sm text-center py-8">Keine Spesenabrechnungen</p>
            </template>
        </div>
    </div>

    <!-- Shooting Docs Tab -->
    <div x-show="tab === 'shooting'">
        <div class="flex justify-end mb-4">
            <button @click="openShooting()"
                    class="btn-primary flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Neues Shooting-Dokument
            </button>
        </div>

        <div class="space-y-3">
            <template x-for="s in shootingDocs" :key="s.id">
                <div class="bg-white/5 border border-white/8 rounded-xl p-4 flex items-start gap-4">
                    <div class="flex-1">
                        <div class="text-sm font-medium text-white" x-text="s.title"></div>
                        <div class="text-xs text-white/50 mt-1" x-text="'Shooting: ' + formatDate(s.shooting_date)"></div>
                        <div class="text-xs text-white/50" x-text="s.location || ''"></div>
                    </div>
                    <template x-if="s.file_url">
                        <a :href="s.file_url" target="_blank" class="text-xs hover:opacity-80 transition-opacity" style="color: #009dde;">
                            Download
                        </a>
                    </template>
                </div>
            </template>
            <template x-if="shootingDocs.length === 0">
                <p class="text-white/50 text-sm text-center py-8">Keine Shooting-Dokumente</p>
            </template>
        </div>
    </div>

    <!-- Expense Modal -->
    <div x-show="expenseModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);"
         @click.self="expenseModal = false">
        <div class="glass-card rounded-2xl w-full max-w-md shadow-2xl" @click.stop>
            <div class="px-6 py-4 border-b border-white/10">
                <h2 class="font-heading font-semibold text-white">Neue Spesenabrechnung</h2>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Bezeichnung *</label>
                    <input x-model="expForm.title" type="text" class="input-field">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Betrag (€)</label>
                        <input x-model="expForm.amount" type="number" step="0.01" class="input-field">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Datum</label>
                        <input x-model="expForm.date" type="date" class="input-field">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Kategorie</label>
                    <select x-model="expForm.category" class="input-field">
                        <option value="">–</option>
                        <option value="travel">Reise</option>
                        <option value="meals">Verpflegung</option>
                        <option value="equipment">Equipment</option>
                        <option value="software">Software</option>
                        <option value="other">Sonstiges</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Notiz</label>
                    <textarea x-model="expForm.notes" rows="2" class="input-field resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Beleg</label>
                    <input type="file" @change="expFile = $event.target.files[0]" class="input-field">
                </div>
            </div>
            <div class="px-6 py-4 border-t border-white/10 flex gap-3">
                <button @click="saveExpense()" class="btn-primary">
                    Einreichen
                </button>
                <button @click="expenseModal = false" class="btn-ghost">Abbrechen</button>
            </div>
        </div>
    </div>

    <!-- Shooting Modal -->
    <div x-show="shootingModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);"
         @click.self="shootingModal = false">
        <div class="glass-card rounded-2xl w-full max-w-md shadow-2xl" @click.stop>
            <div class="px-6 py-4 border-b border-white/10">
                <h2 class="font-heading font-semibold text-white">Neues Shooting-Dokument</h2>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Titel *</label>
                    <input x-model="shootForm.title" type="text" class="input-field">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Shooting-Datum</label>
                        <input x-model="shootForm.shooting_date" type="date" class="input-field">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Location</label>
                        <input x-model="shootForm.location" type="text" class="input-field">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Kunde</label>
                    <input x-model="shootForm.customer_name" type="text" class="input-field">
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Notizen</label>
                    <textarea x-model="shootForm.notes" rows="3" class="input-field resize-none"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-white/10 flex gap-3">
                <button @click="saveShooting()" class="btn-primary">
                    Erstellen
                </button>
                <button @click="shootingModal = false" class="btn-ghost">Abbrechen</button>
            </div>
        </div>
    </div>
</div>

<script>
function formsApp() {
    return {
        tab: 'expenses',
        expenses: [],
        shootingDocs: [],
        loading: true,
        expenseModal: false,
        shootingModal: false,
        expForm: {},
        expFile: null,
        shootForm: {},

        async init() {
            await Promise.all([this.loadExpenses(), this.loadShooting()]);
        },

        async loadExpenses() {
            this.loading = true;
            const r = await fetch('/api/expenses');
            const d = await r.json();
            this.expenses = Array.isArray(d) ? d : [];
            this.loading = false;
        },

        async loadShooting() {
            const r = await fetch('/api/shooting-docs');
            const d = await r.json();
            this.shootingDocs = Array.isArray(d) ? d : [];
        },

        openExpense() {
            this.expForm = { title: '', amount: '', date: new Date().toISOString().split('T')[0], category: '', notes: '' };
            this.expFile = null;
            this.expenseModal = true;
        },

        async saveExpense() {
            if (!this.expForm.title?.trim()) return alert('Bezeichnung erforderlich');
            const fd = new FormData();
            Object.entries(this.expForm).forEach(([k, v]) => v !== null && v !== '' && fd.append(k, v));
            if (this.expFile) fd.append('receipt', this.expFile);
            const r = await fetch('/api/expenses', { method: 'POST', body: fd });
            if (r.ok) { const e = await r.json(); this.expenses.unshift(e); this.expenseModal = false; }
            else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        async approveExpense(id) {
            await fetch('/api/expenses/' + id + '/approve', { method: 'POST' });
            const i = this.expenses.findIndex(e => e.id === id);
            if (i !== -1) this.expenses[i].status = 'approved';
        },

        async rejectExpense(id) {
            await fetch('/api/expenses/' + id + '/reject', { method: 'POST' });
            const i = this.expenses.findIndex(e => e.id === id);
            if (i !== -1) this.expenses[i].status = 'rejected';
        },

        openShooting() {
            this.shootForm = { title: '', shooting_date: '', location: '', customer_name: '', notes: '' };
            this.shootingModal = true;
        },

        async saveShooting() {
            if (!this.shootForm.title?.trim()) return alert('Titel erforderlich');
            const r = await fetch('/api/shooting-docs', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.shootForm) });
            if (r.ok) { const s = await r.json(); this.shootingDocs.unshift(s); this.shootingModal = false; }
            else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        formatDate(d) {
            if (!d) return '';
            return new Date(d).toLocaleDateString('de-DE', {day:'2-digit',month:'2-digit',year:'numeric'});
        },

        formatMoney(n) {
            return parseFloat(n || 0).toFixed(2).replace('.', ',');
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

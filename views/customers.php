<?php
$pageTitle = 'Kunden';
require __DIR__ . '/_layout.php';
?>

<div x-data="customersApp()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center gap-4 mb-6">
        <div class="flex-1">
            <input type="search" x-model.debounce.300ms="search" @input="load()"
                   placeholder="Kunden suchen…"
                   class="bg-gray-800 border border-gray-700 text-white text-sm rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 w-72">
        </div>
        <button @click="openCreate()"
                class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Neuer Kunde
        </button>
    </div>

    <!-- Grid -->
    <div x-show="loading" class="text-gray-500 text-sm">Laden…</div>
    <div x-show="!loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <template x-for="c in customers" :key="c.id">
            <a :href="'/customers/' + c.id"
               class="bg-gray-900 border border-gray-800 rounded-2xl p-5 hover:border-gray-600 transition-all block">
                <div class="flex items-start gap-4 mb-3">
                    <template x-if="c.logo_url">
                        <img :src="c.logo_url" class="w-10 h-10 rounded-xl object-contain bg-gray-800" alt="">
                    </template>
                    <template x-if="!c.logo_url">
                        <div class="w-10 h-10 rounded-xl bg-brand-500/20 flex items-center justify-center flex-shrink-0">
                            <span class="text-brand-400 font-bold text-sm" x-text="(c.company || c.name || '?').charAt(0).toUpperCase()"></span>
                        </div>
                    </template>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-white text-sm truncate" x-text="c.company || c.name"></h3>
                        <p class="text-xs text-gray-500 truncate" x-text="c.email || ''"></p>
                    </div>
                </div>
                <div class="flex items-center gap-3 text-xs text-gray-500">
                    <template x-if="c.phone">
                        <span x-text="c.phone"></span>
                    </template>
                    <template x-if="c.city">
                        <span x-text="c.city"></span>
                    </template>
                    <template x-if="c.is_active !== undefined">
                        <span class="ml-auto px-2 py-0.5 rounded-full"
                              :class="c.is_active ? 'bg-green-900/30 text-green-400' : 'bg-gray-800 text-gray-500'"
                              x-text="c.is_active ? 'Aktiv' : 'Inaktiv'"></span>
                    </template>
                </div>
            </a>
        </template>
        <template x-if="!loading && customers.length === 0">
            <div class="col-span-3 text-center py-12 text-gray-500">Keine Kunden gefunden</div>
        </template>
    </div>

    <!-- Create Modal -->
    <div x-show="modal" x-cloak
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         @click.self="modal = false">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl w-full max-w-lg shadow-2xl" @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-800">
                <h2 class="font-semibold text-white">Neuer Kunde</h2>
                <button @click="modal = false" class="text-gray-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Firma *</label>
                        <input x-model="form.company" type="text"
                               class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Name</label>
                        <input x-model="form.name" type="text"
                               class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">E-Mail</label>
                        <input x-model="form.email" type="email"
                               class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Telefon</label>
                        <input x-model="form.phone" type="tel"
                               class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Website</label>
                        <input x-model="form.website" type="url" placeholder="https://"
                               class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Branche</label>
                        <input x-model="form.industry" type="text"
                               class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Notizen</label>
                    <textarea x-model="form.notes" rows="2"
                              class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 resize-none"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-800 flex gap-3">
                <button @click="saveCustomer()"
                        class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-xl">
                    Erstellen
                </button>
                <button @click="modal = false" class="text-gray-400 hover:text-white text-sm">Abbrechen</button>
            </div>
        </div>
    </div>
</div>

<script>
function customersApp() {
    return {
        customers: [],
        loading: true,
        search: '',
        modal: false,
        form: {},

        async init() { await this.load(); },

        async load() {
            this.loading = true;
            let url = '/api/customers?limit=200';
            if (this.search) url += '&search=' + encodeURIComponent(this.search);
            const r = await fetch(url);
            const data = await r.json();
            this.customers = Array.isArray(data) ? data : (data.customers || []);
            this.loading = false;
        },

        openCreate() {
            this.form = { company: '', name: '', email: '', phone: '', website: '', industry: '', notes: '' };
            this.modal = true;
        },

        async saveCustomer() {
            if (!this.form.company?.trim()) return alert('Firma erforderlich');
            const r = await fetch('/api/customers', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.form) });
            if (r.ok) { const c = await r.json(); this.modal = false; window.location.href = '/customers/' + c.id; }
            else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

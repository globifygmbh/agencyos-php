<?php
$customerId = $routeParam ?? '';
$pageTitle = 'Kunde';
require __DIR__ . '/_layout.php';
?>

<div x-data="customerDetail('<?= htmlspecialchars($customerId) ?>')" x-init="init()">
    <div x-show="loading" class="text-gray-500 text-sm">Laden…</div>

    <div x-show="!loading && customer">
        <!-- Header -->
        <div class="flex items-start gap-5 mb-6">
            <template x-if="customer && customer.logo_url">
                <img :src="customer.logo_url" class="w-16 h-16 rounded-2xl object-contain bg-gray-800 p-1" alt="">
            </template>
            <template x-if="customer && !customer.logo_url">
                <div class="w-16 h-16 rounded-2xl bg-brand-500/20 flex items-center justify-center flex-shrink-0">
                    <span class="text-brand-400 font-bold text-2xl" x-text="(customer?.company || '?').charAt(0).toUpperCase()"></span>
                </div>
            </template>
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-white" x-text="customer?.company"></h2>
                <div class="flex items-center gap-4 mt-1 text-sm text-gray-400">
                    <span x-text="customer?.email || ''"></span>
                    <span x-text="customer?.phone || ''"></span>
                    <template x-if="customer?.website">
                        <a :href="customer.website" target="_blank" class="text-brand-400 hover:text-brand-300" x-text="customer.website"></a>
                    </template>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button @click="editModal = true"
                        class="bg-gray-800 hover:bg-gray-700 text-white text-sm px-3 py-2 rounded-xl">
                    Bearbeiten
                </button>
                <a href="/customers" class="text-gray-400 hover:text-white text-sm">← Zurück</a>
            </div>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-800 mb-6">
            <div class="flex gap-1">
                <template x-for="tab in ['overview','projects','content','credentials']" :key="tab">
                    <button @click="activeTab = tab"
                            class="px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px"
                            :class="activeTab === tab ? 'text-brand-400 border-brand-500' : 'text-gray-400 border-transparent hover:text-white'"
                            x-text="{overview:'Übersicht',projects:'Projekte',content:'Content Plan',credentials:'Zugänge'}[tab]"></button>
                </template>
            </div>
        </div>

        <!-- Overview -->
        <div x-show="activeTab === 'overview'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
                <h3 class="text-sm font-semibold text-white mb-4">Kontaktinformationen</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex gap-2">
                        <dt class="text-gray-500 w-28 flex-shrink-0">Firma</dt>
                        <dd class="text-white" x-text="customer?.company || '–'"></dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="text-gray-500 w-28 flex-shrink-0">Ansprechpartner</dt>
                        <dd class="text-white" x-text="customer?.name || '–'"></dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="text-gray-500 w-28 flex-shrink-0">E-Mail</dt>
                        <dd><a :href="'mailto:' + customer?.email" class="text-brand-400 hover:text-brand-300" x-text="customer?.email || '–'"></a></dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="text-gray-500 w-28 flex-shrink-0">Telefon</dt>
                        <dd class="text-white" x-text="customer?.phone || '–'"></dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="text-gray-500 w-28 flex-shrink-0">Website</dt>
                        <dd><a :href="customer?.website" target="_blank" class="text-brand-400 hover:text-brand-300" x-text="customer?.website || '–'"></a></dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="text-gray-500 w-28 flex-shrink-0">Branche</dt>
                        <dd class="text-white" x-text="customer?.industry || '–'"></dd>
                    </div>
                    <div class="flex gap-2">
                        <dt class="text-gray-500 w-28 flex-shrink-0">Stadt</dt>
                        <dd class="text-white" x-text="customer?.city || '–'"></dd>
                    </div>
                </dl>
            </div>
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
                <h3 class="text-sm font-semibold text-white mb-4">Notizen</h3>
                <p class="text-sm text-gray-300 whitespace-pre-wrap" x-text="customer?.notes || 'Keine Notizen'"></p>
            </div>
        </div>

        <!-- Projects -->
        <div x-show="activeTab === 'projects'" class="space-y-3">
            <template x-for="p in projects" :key="p.id">
                <a :href="'/projects/' + p.id"
                   class="bg-gray-900 border border-gray-800 rounded-xl p-4 flex items-center gap-4 hover:border-gray-600 transition-colors block">
                    <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="'background:' + (p.color || '#6366f1')"></div>
                    <div class="flex-1">
                        <div class="text-sm font-medium text-white" x-text="p.name"></div>
                        <div class="text-xs text-gray-500 mt-0.5" x-text="{active:'Aktiv',completed:'Abgeschlossen',on_hold:'Pausiert'}[p.status] || p.status"></div>
                    </div>
                    <div class="text-xs text-gray-500" x-text="formatDate(p.deadline) || ''"></div>
                </a>
            </template>
            <template x-if="projects.length === 0">
                <p class="text-gray-500 text-sm text-center py-8">Keine Projekte für diesen Kunden</p>
            </template>
        </div>

        <!-- Content Plan -->
        <div x-show="activeTab === 'content'" class="space-y-3">
            <div class="flex justify-end mb-3">
                <button @click="contentModal = true"
                        class="bg-brand-500 hover:bg-brand-600 text-white text-sm px-3 py-2 rounded-xl">
                    + Content
                </button>
            </div>
            <template x-for="c in contentPlan" :key="c.id">
                <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 flex items-start gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-white" x-text="c.title"></div>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-gray-500" x-text="c.platform || ''"></span>
                            <span class="text-xs px-2 py-0.5 rounded-full"
                                  :class="{
                                    'bg-yellow-900/30 text-yellow-400': c.status==='draft',
                                    'bg-blue-900/30 text-blue-400': c.status==='in_review',
                                    'bg-green-900/30 text-green-400': c.status==='published',
                                  }"
                                  x-text="{draft:'Entwurf',in_review:'Review',published:'Veröffentlicht',approved:'Genehmigt'}[c.status] || c.status"></span>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 flex-shrink-0" x-text="formatDate(c.publish_date) || ''"></div>
                </div>
            </template>
            <template x-if="contentPlan.length === 0">
                <p class="text-gray-500 text-sm text-center py-8">Kein Content geplant</p>
            </template>
        </div>

        <!-- Credentials -->
        <div x-show="activeTab === 'credentials'" class="space-y-3">
            <div class="flex justify-end mb-3">
                <button @click="credentialModal = true"
                        class="bg-brand-500 hover:bg-brand-600 text-white text-sm px-3 py-2 rounded-xl">
                    + Zugang
                </button>
            </div>
            <template x-for="c in credentials" :key="c.id">
                <div class="bg-gray-900 border border-gray-800 rounded-xl p-4 flex items-center gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-white" x-text="c.title"></div>
                        <div class="text-xs text-gray-500 mt-0.5" x-text="c.username || ''"></div>
                    </div>
                    <button @click="toggleCred(c)"
                            class="text-xs text-gray-400 hover:text-white px-2 py-1 bg-gray-800 rounded-lg">
                        <span x-text="c.showPass ? 'Verbergen' : 'Anzeigen'"></span>
                    </button>
                    <template x-if="c.showPass">
                        <span class="text-xs font-mono text-white bg-gray-800 px-2 py-1 rounded" x-text="c.password_plain || '–'"></span>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <!-- Edit Modal -->
    <div x-show="editModal" x-cloak
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         @click.self="editModal = false">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl w-full max-w-lg shadow-2xl overflow-y-auto max-h-[90vh]" @click.stop>
            <div class="px-6 py-4 border-b border-gray-800 flex items-center justify-between">
                <h2 class="font-semibold text-white">Kunde bearbeiten</h2>
                <button @click="editModal = false" class="text-gray-400 hover:text-white">×</button>
            </div>
            <div class="px-6 py-4 space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">Firma</label>
                        <input x-model="editForm.company" type="text" class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">Ansprechpartner</label>
                        <input x-model="editForm.name" type="text" class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">E-Mail</label>
                        <input x-model="editForm.email" type="email" class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">Telefon</label>
                        <input x-model="editForm.phone" type="tel" class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1">Website</label>
                    <input x-model="editForm.website" type="url" class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1">Notizen</label>
                    <textarea x-model="editForm.notes" rows="3" class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 resize-none"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-800 flex gap-3">
                <button @click="saveEdit()" class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-xl">Speichern</button>
                <button @click="editModal = false" class="text-gray-400 text-sm">Abbrechen</button>
            </div>
        </div>
    </div>

    <!-- Credential Modal -->
    <div x-show="credentialModal" x-cloak
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         @click.self="credentialModal = false">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl w-full max-w-md shadow-2xl" @click.stop>
            <div class="px-6 py-4 border-b border-gray-800">
                <h2 class="font-semibold text-white">Neuer Zugang</h2>
            </div>
            <div class="px-6 py-4 space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1">Bezeichnung *</label>
                    <input x-model="credForm.title" type="text" class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1">Benutzername / E-Mail</label>
                    <input x-model="credForm.username" type="text" class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1">Passwort</label>
                    <input x-model="credForm.password_plain" type="text" class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1">URL</label>
                    <input x-model="credForm.url" type="url" class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-800 flex gap-3">
                <button @click="saveCred()" class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-xl">Erstellen</button>
                <button @click="credentialModal = false" class="text-gray-400 text-sm">Abbrechen</button>
            </div>
        </div>
    </div>
</div>

<script>
function customerDetail(customerId) {
    return {
        customerId,
        customer: null,
        projects: [],
        contentPlan: [],
        credentials: [],
        loading: true,
        activeTab: 'overview',
        editModal: false,
        editForm: {},
        credentialModal: false,
        credForm: {},
        contentModal: false,

        async init() {
            const [cr, pr, cp, cc] = await Promise.all([
                fetch('/api/customers/' + customerId).then(r => r.json()),
                fetch('/api/projects?customer_id=' + customerId).then(r => r.json()),
                fetch('/api/customers/' + customerId + '/content').then(r => r.json()),
                fetch('/api/customers/' + customerId + '/credentials').then(r => r.json()),
            ]);
            this.customer = cr;
            this.editForm = { ...cr };
            this.projects = Array.isArray(pr) ? pr : (pr.projects || []);
            this.contentPlan = Array.isArray(cp) ? cp : [];
            this.credentials = (Array.isArray(cc) ? cc : []).map(c => ({ ...c, showPass: false }));
            this.loading = false;
        },

        async saveEdit() {
            const r = await fetch('/api/customers/' + this.customerId, { method: 'PUT', headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.editForm) });
            if (r.ok) { this.customer = await r.json(); this.editForm = { ...this.customer }; this.editModal = false; }
            else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        async saveCred() {
            if (!this.credForm.title?.trim()) return alert('Bezeichnung erforderlich');
            const r = await fetch('/api/customers/' + this.customerId + '/credentials', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.credForm) });
            if (r.ok) { const c = await r.json(); this.credentials.push({ ...c, showPass: false }); this.credentialModal = false; }
            else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        toggleCred(cred) { cred.showPass = !cred.showPass; },

        formatDate(d) {
            if (!d) return '';
            return new Date(d).toLocaleDateString('de-DE', {day:'2-digit',month:'2-digit',year:'numeric'});
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

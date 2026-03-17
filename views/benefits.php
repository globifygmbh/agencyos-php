<?php
$pageTitle = 'Benefits';
require __DIR__ . '/_layout.php';
?>

<div x-data="benefitsApp()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <p class="text-white/40 text-sm">Deine Mitarbeiterbenefits</p>
        <?php if (in_array($currentUser['role'] ?? '', ['admin', 'owner'])): ?>
        <button @click="openCreate()"
                class="btn-primary flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Neuer Benefit
        </button>
        <?php endif; ?>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="text-white/50 text-sm">Laden…</div>

    <!-- Grid -->
    <div x-show="!loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <template x-for="b in benefits" :key="b.id">
            <div class="bg-white/5 border border-white/8 rounded-2xl p-5 hover:border-white/20 transition-all">
                <div class="flex items-start gap-4 mb-3">
                    <template x-if="b.logo_url">
                        <img :src="b.logo_url" class="w-12 h-12 rounded-xl object-contain bg-white/8 p-1" alt="">
                    </template>
                    <template x-if="!b.logo_url">
                        <div class="w-12 h-12 rounded-xl bg-[#009dde]/20 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6" style="color: #009dde;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                            </svg>
                        </div>
                    </template>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-heading font-semibold text-white text-sm" x-text="b.title"></h3>
                        <template x-if="b.category">
                            <span class="text-xs bg-[#009dde]/10 px-1.5 py-0.5 rounded mt-1 inline-block" style="color: #009dde;" x-text="b.category"></span>
                        </template>
                    </div>
                </div>
                <p class="text-xs text-white/40 line-clamp-3 mb-3" x-text="b.description || ''"></p>
                <div class="flex items-center gap-2">
                    <template x-if="b.url">
                        <a :href="b.url" target="_blank"
                           class="text-xs flex items-center gap-1 hover:opacity-80 transition-opacity" style="color: #009dde;">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            Mehr erfahren
                        </a>
                    </template>
                    <?php if (in_array($currentUser['role'] ?? '', ['admin', 'owner'])): ?>
                    <button @click="deleteBenefit(b.id)"
                            class="ml-auto text-white/40 hover:text-red-400 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </template>
        <template x-if="!loading && benefits.length === 0">
            <div class="col-span-3 text-center py-12 text-white/50">Noch keine Benefits vorhanden</div>
        </template>
    </div>

    <!-- Create Modal -->
    <div x-show="modal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);"
         @click.self="modal = false">
        <div class="glass-card rounded-2xl w-full max-w-md shadow-2xl" @click.stop>
            <div class="px-6 py-4 border-b border-white/10">
                <h2 class="font-heading font-semibold text-white">Neuer Benefit</h2>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Titel *</label>
                    <input x-model="form.title" type="text" class="input-field">
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Beschreibung</label>
                    <textarea x-model="form.description" rows="3" class="input-field resize-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Kategorie</label>
                        <input x-model="form.category" type="text" placeholder="z.B. Gesundheit" class="input-field">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">URL</label>
                        <input x-model="form.url" type="url" class="input-field">
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-white/10 flex gap-3">
                <button @click="saveBenefit()" class="btn-primary">
                    Erstellen
                </button>
                <button @click="modal = false" class="btn-ghost">Abbrechen</button>
            </div>
        </div>
    </div>
</div>

<script>
function benefitsApp() {
    return {
        benefits: [],
        loading: true,
        modal: false,
        form: {},

        async init() {
            const r = await fetch('/api/benefits');
            const d = await r.json();
            this.benefits = Array.isArray(d) ? d : [];
            this.loading = false;
        },

        openCreate() {
            this.form = { title: '', description: '', category: '', url: '' };
            this.modal = true;
        },

        async saveBenefit() {
            if (!this.form.title?.trim()) return alert('Titel erforderlich');
            const r = await fetch('/api/benefits', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.form) });
            if (r.ok) { const b = await r.json(); this.benefits.push(b); this.modal = false; }
            else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        async deleteBenefit(id) {
            if (!confirm('Benefit löschen?')) return;
            await fetch('/api/benefits/' + id, { method: 'DELETE' });
            this.benefits = this.benefits.filter(b => b.id !== id);
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

<?php
$pageTitle = 'Notizen';
require __DIR__ . '/_layout.php';
?>

<div x-data="notesApp()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
        <input type="search" x-model.debounce.200ms="search"
               placeholder="Notizen suchen…"
               class="input-field w-64">
        <div class="flex gap-1 ml-auto">
            <template x-for="c in noteColors" :key="c.value">
                <button @click="filterColor = filterColor === c.value ? '' : c.value"
                        :title="c.label"
                        class="w-7 h-7 rounded-full border-2 transition-all"
                        :class="filterColor === c.value ? 'scale-125 border-white' : 'border-transparent'"
                        :style="'background:' + c.value"></button>
            </template>
        </div>
        <button @click="openCreate()" class="btn-primary flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Neue Notiz
        </button>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="text-white/50 text-sm">Laden…</div>

    <!-- Masonry Grid -->
    <div x-show="!loading" class="columns-1 sm:columns-2 lg:columns-3 xl:columns-4 gap-4 space-y-0">
        <!-- Pinned section -->
        <template x-if="pinnedNotes.length > 0">
            <div class="break-inside-avoid mb-1">
                <p class="text-xs text-white/40 uppercase tracking-wider font-medium mb-3 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg>
                    Angeheftet
                </p>
            </div>
        </template>

        <template x-for="note in filteredNotes" :key="note.id">
            <div class="break-inside-avoid mb-4 group cursor-pointer"
                 @click="openEdit(note)">
                <div class="rounded-2xl p-4 transition-all hover:scale-[1.01] hover:shadow-xl relative"
                     :style="'background: ' + noteColorBg(note.color) + '; border: 1px solid ' + noteColorBorder(note.color) + ';'">
                    <!-- Pin + menu row -->
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <template x-if="note.is_pinned">
                            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" style="color: rgba(255,255,255,0.5)" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/>
                            </svg>
                        </template>
                        <div class="flex-1 min-w-0">
                            <template x-if="note.title">
                                <h3 class="font-semibold text-white text-sm leading-snug" x-text="note.title"></h3>
                            </template>
                        </div>
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity" @click.stop>
                            <button @click="togglePin(note)"
                                    :title="note.is_pinned ? 'Loslösen' : 'Anheften'"
                                    class="p-1 rounded-lg hover:bg-white/20 transition-colors text-white/60 hover:text-white">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg>
                            </button>
                            <button @click="deleteNote(note.id)"
                                    class="p-1 rounded-lg hover:bg-red-500/30 transition-colors text-white/60 hover:text-red-300">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <template x-if="note.content">
                        <p class="text-sm text-white/80 leading-relaxed whitespace-pre-wrap break-words"
                           x-text="note.content.length > 300 ? note.content.slice(0, 300) + '…' : note.content"></p>
                    </template>

                    <!-- Footer -->
                    <div class="mt-3 pt-2 border-t border-white/10 text-xs text-white/30"
                         x-text="formatDate(note.updated_at)"></div>
                </div>
            </div>
        </template>

        <template x-if="!loading && filteredNotes.length === 0">
            <div class="col-span-4 text-center py-16 text-white/40">
                <div class="text-4xl mb-3">📝</div>
                <p class="text-sm">Keine Notizen gefunden</p>
                <button @click="openCreate()" class="mt-3 btn-ghost text-xs">+ Erste Notiz erstellen</button>
            </div>
        </template>
    </div>

    <!-- Create / Edit Modal -->
    <div x-show="modal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);"
         @click.self="modal = false">
        <div class="rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden"
             :style="'background: ' + noteColorBg(form.color) + '; border: 1px solid ' + noteColorBorder(form.color)"
             @click.stop>
            <div class="px-5 py-4 border-b border-white/10 flex items-center justify-between">
                <h2 class="font-heading font-semibold text-white" x-text="editingId ? 'Notiz bearbeiten' : 'Neue Notiz'"></h2>
                <button @click="modal = false" class="text-white/40 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-5 py-4 space-y-3">
                <input x-model="form.title" type="text" placeholder="Titel (optional)"
                       class="w-full bg-transparent border-b border-white/20 pb-2 text-white placeholder-white/30 outline-none text-sm font-medium focus:border-white/40 transition-colors">
                <textarea x-model="form.content" rows="8"
                          placeholder="Notiz schreiben…"
                          class="w-full bg-transparent text-white placeholder-white/30 outline-none resize-none text-sm leading-relaxed"></textarea>

                <!-- Color Picker -->
                <div class="flex items-center gap-3 pt-2 border-t border-white/10">
                    <span class="text-xs text-white/40">Farbe:</span>
                    <div class="flex gap-2">
                        <template x-for="c in noteColors" :key="c.value">
                            <button @click="form.color = c.value"
                                    :title="c.label"
                                    class="w-6 h-6 rounded-full border-2 transition-all hover:scale-110"
                                    :class="form.color === c.value ? 'border-white scale-125' : 'border-transparent'"
                                    :style="'background:' + c.value"></button>
                        </template>
                    </div>
                    <button @click="form.is_pinned = !form.is_pinned"
                            :class="form.is_pinned ? 'text-white' : 'text-white/30'"
                            class="ml-auto flex items-center gap-1.5 text-xs hover:text-white transition-colors">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/></svg>
                        <span x-text="form.is_pinned ? 'Angeheftet' : 'Anheften'"></span>
                    </button>
                </div>
            </div>
            <div class="px-5 py-3 border-t border-white/10 flex gap-3">
                <button @click="saveNote()" class="btn-primary">
                    <span x-text="editingId ? 'Speichern' : 'Erstellen'"></span>
                </button>
                <button @click="modal = false" class="btn-ghost">Abbrechen</button>
                <template x-if="editingId">
                    <button @click="deleteNote(editingId); modal = false"
                            class="ml-auto text-sm text-red-400 hover:text-red-300 transition-colors">Löschen</button>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function notesApp() {
    return {
        notes: [],
        loading: true,
        search: '',
        filterColor: '',
        modal: false,
        form: {},
        editingId: null,
        noteColors: [
            { value: '#FFFFFF', label: 'Weiß' },
            { value: '#FFF3CD', label: 'Gelb' },
            { value: '#D4EDDA', label: 'Grün' },
            { value: '#CCE5FF', label: 'Blau' },
            { value: '#F8D7DA', label: 'Rosa' },
        ],

        async init() {
            await this.load();
        },

        async load() {
            this.loading = true;
            const r = await fetch('/api/notes');
            const d = await r.json();
            this.notes = Array.isArray(d) ? d : [];
            this.loading = false;
        },

        get pinnedNotes() {
            return this.filteredNotes.filter(n => n.is_pinned);
        },

        get filteredNotes() {
            let list = this.notes;
            if (this.search) {
                const q = this.search.toLowerCase();
                list = list.filter(n => (n.title || '').toLowerCase().includes(q) || (n.content || '').toLowerCase().includes(q));
            }
            if (this.filterColor) {
                list = list.filter(n => n.color === this.filterColor);
            }
            return list;
        },

        openCreate() {
            this.editingId = null;
            this.form = { title: '', content: '', color: '#FFF3CD', is_pinned: false };
            this.modal = true;
        },

        openEdit(note) {
            this.editingId = note.id;
            this.form = { title: note.title || '', content: note.content || '', color: note.color || '#FFFFFF', is_pinned: !!note.is_pinned };
            this.modal = true;
        },

        async saveNote() {
            const payload = { title: this.form.title, content: this.form.content, color: this.form.color, is_pinned: this.form.is_pinned ? 1 : 0 };
            let r;
            if (this.editingId) {
                r = await fetch('/api/notes/' + this.editingId, { method: 'PUT', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            } else {
                r = await fetch('/api/notes', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            }
            if (r.ok) { this.modal = false; await this.load(); }
            else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        async togglePin(note) {
            await fetch('/api/notes/' + note.id, { method: 'PUT', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ is_pinned: note.is_pinned ? 0 : 1 }) });
            await this.load();
        },

        async deleteNote(id) {
            if (!confirm('Notiz löschen?')) return;
            await fetch('/api/notes/' + id, { method: 'DELETE' });
            await this.load();
        },

        noteColorBg(color) {
            const map = {
                '#FFFFFF': 'rgba(255,255,255,0.08)',
                '#FFF3CD': 'rgba(255,243,205,0.12)',
                '#D4EDDA': 'rgba(212,237,218,0.10)',
                '#CCE5FF': 'rgba(0,157,222,0.12)',
                '#F8D7DA': 'rgba(248,215,218,0.12)',
            };
            return map[color] || 'rgba(255,255,255,0.06)';
        },

        noteColorBorder(color) {
            const map = {
                '#FFFFFF': 'rgba(255,255,255,0.15)',
                '#FFF3CD': 'rgba(255,193,7,0.25)',
                '#D4EDDA': 'rgba(40,167,69,0.25)',
                '#CCE5FF': 'rgba(0,157,222,0.25)',
                '#F8D7DA': 'rgba(220,53,69,0.25)',
            };
            return map[color] || 'rgba(255,255,255,0.10)';
        },

        noteColorDot(color) {
            return color || '#FFFFFF';
        },

        formatDate(d) {
            if (!d) return '';
            return new Date(d).toLocaleDateString('de-DE', {day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit'});
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

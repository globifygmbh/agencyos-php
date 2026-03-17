<?php
$pageTitle = 'Zugänge & Tools';
require __DIR__ . '/_layout.php';
?>

<div x-data="accessApp()" x-init="init()">

    <!-- Tabs -->
    <div class="flex items-center gap-1 mb-6 bg-white/5 rounded-xl p-1 w-fit">
        <button @click="tab = 'tools'"
                :class="tab === 'tools' ? 'bg-white/10 text-white' : 'text-white/50 hover:text-white'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H4a2 2 0 01-2-2V5a2 2 0 012-2h16a2 2 0 012 2v10a2 2 0 01-2 2h-1"/>
            </svg>
            Firmen-Tools
        </button>
        <button @click="tab = 'personal'"
                :class="tab === 'personal' ? 'bg-white/10 text-white' : 'text-white/50 hover:text-white'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            Meine Passwörter
        </button>
    </div>

    <!-- ===== TOOLS TAB ===== -->
    <div x-show="tab === 'tools'">
        <div class="flex items-center gap-3 mb-5">
            <input type="search" x-model.debounce.200ms="toolSearch"
                   placeholder="Tools suchen…"
                   class="input-field w-64">
            <select x-model="toolCategory" class="input-field w-40">
                <option value="">Alle Kategorien</option>
                <template x-for="cat in toolCategories" :key="cat">
                    <option :value="cat" x-text="cat"></option>
                </template>
            </select>
            <?php if (in_array($currentUser['role'] ?? '', ['CHEF', 'BUCHHALTUNG'])): ?>
            <button @click="openToolCreate()" class="btn-primary ml-auto flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tool hinzufügen
            </button>
            <?php endif; ?>
        </div>

        <div x-show="loadingTools" class="text-white/50 text-sm">Laden…</div>

        <!-- Tools by category -->
        <div x-show="!loadingTools" class="space-y-6">
            <template x-for="cat in filteredToolCategories" :key="cat">
                <div>
                    <h3 class="text-xs uppercase tracking-wider text-white/40 font-medium mb-3" x-text="cat"></h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                        <template x-for="tool in toolsByCategory(cat)" :key="tool.id">
                            <div class="bg-white/5 border border-white/8 rounded-xl p-4 hover:border-white/15 transition-all group">
                                <!-- Header -->
                                <div class="flex items-start justify-between gap-2 mb-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold"
                                             style="background: rgba(0,157,222,0.15); color: #009dde;"
                                             x-text="(tool.name || '?').charAt(0).toUpperCase()"></div>
                                        <div>
                                            <p class="text-sm font-medium text-white" x-text="tool.name"></p>
                                            <template x-if="tool.url">
                                                <a :href="tool.url" target="_blank"
                                                   class="text-xs text-white/40 hover:text-[#009dde] truncate block max-w-28 transition-colors"
                                                   x-text="tool.url.replace(/^https?:\/\//, '').split('/')[0]"
                                                   @click.stop></a>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <?php if (in_array($currentUser['role'] ?? '', ['CHEF', 'BUCHHALTUNG'])): ?>
                                        <button @click="openToolEdit(tool)"
                                                class="p-1.5 rounded-lg hover:bg-white/10 text-white/40 hover:text-white transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <?php endif; ?>
                                        <template x-if="tool.url">
                                            <a :href="tool.url" target="_blank"
                                               class="p-1.5 rounded-lg hover:bg-white/10 text-white/40 hover:text-white transition-colors"
                                               title="Öffnen">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                </svg>
                                            </a>
                                        </template>
                                    </div>
                                </div>

                                <!-- Credentials -->
                                <div class="space-y-1.5">
                                    <template x-if="tool.username">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-white/30 w-16 flex-shrink-0">Benutzer</span>
                                            <span class="text-xs text-white/70 font-mono flex-1 truncate" x-text="tool.username"></span>
                                            <button @click="copyText(tool.username)"
                                                    class="p-1 rounded hover:bg-white/10 text-white/30 hover:text-white transition-colors flex-shrink-0">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="tool.password && tool.password !== '***'">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-white/30 w-16 flex-shrink-0">Passwort</span>
                                            <span class="text-xs text-white/70 font-mono flex-1 truncate"
                                                  x-text="showPw[tool.id] ? tool.password : '••••••••'"></span>
                                            <button @click="showPw[tool.id] = !showPw[tool.id]"
                                                    class="p-1 rounded hover:bg-white/10 text-white/30 hover:text-white transition-colors flex-shrink-0">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <template x-if="!showPw[tool.id]">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </template>
                                                    <template x-if="showPw[tool.id]">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                                    </template>
                                                </svg>
                                            </button>
                                            <button @click="copyText(tool.password)"
                                                    class="p-1 rounded hover:bg-white/10 text-white/30 hover:text-white transition-colors flex-shrink-0">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="tool.notes">
                                        <p class="text-xs text-white/30 pt-1 italic" x-text="tool.notes"></p>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
            <template x-if="!loadingTools && filteredTools.length === 0">
                <div class="text-center py-12 text-white/40">
                    <div class="text-3xl mb-2">🔑</div>
                    <p class="text-sm">Keine Tools gefunden</p>
                </div>
            </template>
        </div>
    </div>

    <!-- ===== PERSONAL PASSWORDS TAB ===== -->
    <div x-show="tab === 'personal'">
        <div class="flex items-center gap-3 mb-5">
            <input type="search" x-model.debounce.200ms="pwSearch"
                   placeholder="Passwörter suchen…"
                   class="input-field w-64">
            <button @click="openPwCreate()" class="btn-primary ml-auto flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Passwort hinzufügen
            </button>
        </div>

        <div x-show="loadingPw" class="text-white/50 text-sm">Laden…</div>
        <div x-show="!loadingPw" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <template x-for="pw in filteredPasswords" :key="pw.id">
                <div class="bg-white/5 border border-white/8 rounded-xl p-4 hover:border-white/15 transition-all group">
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold bg-purple-500/15 text-purple-400"
                                 x-text="(pw.title || '?').charAt(0).toUpperCase()"></div>
                            <div>
                                <p class="text-sm font-medium text-white" x-text="pw.title"></p>
                                <template x-if="pw.url">
                                    <a :href="pw.url" target="_blank"
                                       class="text-xs text-white/40 hover:text-[#009dde] truncate block max-w-32 transition-colors"
                                       x-text="pw.url.replace(/^https?:\/\//, '').split('/')[0]"
                                       @click.stop></a>
                                </template>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="openPwEdit(pw)"
                                    class="p-1.5 rounded-lg hover:bg-white/10 text-white/40 hover:text-white transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button @click="deletePw(pw.id)"
                                    class="p-1.5 rounded-lg hover:bg-red-500/20 text-white/40 hover:text-red-400 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <template x-if="pw.username">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-white/30 w-16 flex-shrink-0">Benutzer</span>
                                <span class="text-xs text-white/70 font-mono flex-1 truncate" x-text="pw.username"></span>
                                <button @click="copyText(pw.username)"
                                        class="p-1 rounded hover:bg-white/10 text-white/30 hover:text-white transition-colors flex-shrink-0">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                        <template x-if="pw.password">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-white/30 w-16 flex-shrink-0">Passwort</span>
                                <span class="text-xs text-white/70 font-mono flex-1 truncate"
                                      x-text="showPwPersonal[pw.id] ? pw.password : '••••••••'"></span>
                                <button @click="showPwPersonal[pw.id] = !showPwPersonal[pw.id]"
                                        class="p-1 rounded hover:bg-white/10 text-white/30 hover:text-white transition-colors flex-shrink-0">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                                <button @click="copyText(pw.password)"
                                        class="p-1 rounded hover:bg-white/10 text-white/30 hover:text-white transition-colors flex-shrink-0">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                        <template x-if="pw.notes">
                            <p class="text-xs text-white/30 pt-1 italic truncate" x-text="pw.notes"></p>
                        </template>
                    </div>
                </div>
            </template>
            <template x-if="!loadingPw && filteredPasswords.length === 0">
                <div class="col-span-3 text-center py-12 text-white/40">
                    <div class="text-3xl mb-2">🔒</div>
                    <p class="text-sm">Keine Passwörter gespeichert</p>
                </div>
            </template>
        </div>
    </div>

    <!-- Copy Toast -->
    <div x-show="copyToast" x-cloak x-transition
         class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-green-500/20 border border-green-500/30 text-green-400 text-sm px-4 py-2 rounded-xl backdrop-blur-sm">
        ✓ In Zwischenablage kopiert
    </div>

    <!-- Tool Modal -->
    <div x-show="toolModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);"
         @click.self="toolModal = false">
        <div class="glass-card rounded-2xl w-full max-w-md shadow-2xl" @click.stop>
            <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
                <h2 class="font-heading font-semibold text-white" x-text="editingToolId ? 'Tool bearbeiten' : 'Tool hinzufügen'"></h2>
                <button @click="toolModal = false" class="text-white/40 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-4 space-y-3">
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Name *</label>
                    <input x-model="toolForm.name" type="text" class="input-field">
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">URL</label>
                    <input x-model="toolForm.url" type="url" placeholder="https://" class="input-field">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Benutzername</label>
                        <input x-model="toolForm.username" type="text" class="input-field">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Passwort</label>
                        <input x-model="toolForm.password" type="text" class="input-field font-mono">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Kategorie</label>
                    <input x-model="toolForm.category" type="text" placeholder="z.B. Marketing, Design, Entwicklung" class="input-field" list="cat-suggestions">
                    <datalist id="cat-suggestions">
                        <template x-for="cat in toolCategories" :key="cat">
                            <option :value="cat"></option>
                        </template>
                    </datalist>
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Notizen</label>
                    <textarea x-model="toolForm.notes" rows="2" class="input-field resize-none"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-white/10 flex gap-3">
                <button @click="saveTool()" class="btn-primary">Speichern</button>
                <button @click="toolModal = false" class="btn-ghost">Abbrechen</button>
                <template x-if="editingToolId">
                    <button @click="deleteTool(editingToolId)"
                            class="ml-auto text-sm text-red-400 hover:text-red-300 transition-colors">Löschen</button>
                </template>
            </div>
        </div>
    </div>

    <!-- Password Modal -->
    <div x-show="pwModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);"
         @click.self="pwModal = false">
        <div class="glass-card rounded-2xl w-full max-w-md shadow-2xl" @click.stop>
            <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
                <h2 class="font-heading font-semibold text-white" x-text="editingPwId ? 'Passwort bearbeiten' : 'Passwort hinzufügen'"></h2>
                <button @click="pwModal = false" class="text-white/40 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-4 space-y-3">
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Titel *</label>
                    <input x-model="pwForm.title" type="text" class="input-field">
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">URL</label>
                    <input x-model="pwForm.url" type="url" placeholder="https://" class="input-field">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Benutzername</label>
                        <input x-model="pwForm.username" type="text" class="input-field">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Passwort</label>
                        <div class="relative">
                            <input x-model="pwForm.password" :type="showPwInput ? 'text' : 'password'" class="input-field pr-9 font-mono">
                            <button @click="showPwInput = !showPwInput"
                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-white/30 hover:text-white transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Notizen</label>
                    <textarea x-model="pwForm.notes" rows="2" class="input-field resize-none"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-white/10 flex gap-3">
                <button @click="savePw()" class="btn-primary">Speichern</button>
                <button @click="pwModal = false" class="btn-ghost">Abbrechen</button>
                <template x-if="editingPwId">
                    <button @click="deletePw(editingPwId); pwModal = false"
                            class="ml-auto text-sm text-red-400 hover:text-red-300 transition-colors">Löschen</button>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function accessApp() {
    return {
        tab: 'tools',
        tools: [],
        passwords: [],
        loadingTools: true,
        loadingPw: true,
        toolSearch: '',
        pwSearch: '',
        toolCategory: '',
        toolModal: false,
        pwModal: false,
        editingToolId: null,
        editingPwId: null,
        toolForm: {},
        pwForm: {},
        showPw: {},
        showPwPersonal: {},
        showPwInput: false,
        copyToast: false,

        async init() {
            await Promise.all([this.loadTools(), this.loadPasswords()]);
        },

        async loadTools() {
            this.loadingTools = true;
            const r = await fetch('/api/tools');
            if (r.ok) { const d = await r.json(); this.tools = Array.isArray(d) ? d : []; }
            this.loadingTools = false;
        },

        async loadPasswords() {
            this.loadingPw = true;
            const r = await fetch('/api/personal-passwords');
            if (r.ok) { const d = await r.json(); this.passwords = Array.isArray(d) ? d : []; }
            this.loadingPw = false;
        },

        get toolCategories() {
            const cats = [...new Set(this.tools.map(t => t.category || 'Allgemein').filter(Boolean))];
            return cats.sort();
        },

        get filteredTools() {
            let list = this.tools;
            if (this.toolSearch) {
                const q = this.toolSearch.toLowerCase();
                list = list.filter(t => (t.name || '').toLowerCase().includes(q) || (t.url || '').toLowerCase().includes(q));
            }
            if (this.toolCategory) {
                list = list.filter(t => (t.category || 'Allgemein') === this.toolCategory);
            }
            return list;
        },

        get filteredToolCategories() {
            return [...new Set(this.filteredTools.map(t => t.category || 'Allgemein'))].sort();
        },

        toolsByCategory(cat) {
            return this.filteredTools.filter(t => (t.category || 'Allgemein') === cat);
        },

        get filteredPasswords() {
            if (!this.pwSearch) return this.passwords;
            const q = this.pwSearch.toLowerCase();
            return this.passwords.filter(p => (p.title || '').toLowerCase().includes(q) || (p.username || '').toLowerCase().includes(q));
        },

        openToolCreate() {
            this.editingToolId = null;
            this.toolForm = { name: '', url: '', username: '', password: '', notes: '', category: 'Allgemein' };
            this.toolModal = true;
        },

        openToolEdit(tool) {
            this.editingToolId = tool.id;
            this.toolForm = { name: tool.name || '', url: tool.url || '', username: tool.username || '', password: tool.password === '***' ? '' : (tool.password || ''), notes: tool.notes || '', category: tool.category || 'Allgemein' };
            this.toolModal = true;
        },

        async saveTool() {
            if (!this.toolForm.name?.trim()) return alert('Name erforderlich');
            let r;
            if (this.editingToolId) {
                r = await fetch('/api/tools/' + this.editingToolId, { method: 'PUT', headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.toolForm) });
            } else {
                r = await fetch('/api/tools', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.toolForm) });
            }
            if (r.ok) { this.toolModal = false; await this.loadTools(); }
            else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        async deleteTool(id) {
            if (!confirm('Tool löschen?')) return;
            await fetch('/api/tools/' + id, { method: 'DELETE' });
            this.toolModal = false;
            await this.loadTools();
        },

        openPwCreate() {
            this.editingPwId = null;
            this.pwForm = { title: '', url: '', username: '', password: '', notes: '' };
            this.showPwInput = false;
            this.pwModal = true;
        },

        openPwEdit(pw) {
            this.editingPwId = pw.id;
            this.pwForm = { title: pw.title || '', url: pw.url || '', username: pw.username || '', password: pw.password || '', notes: pw.notes || '' };
            this.showPwInput = false;
            this.pwModal = true;
        },

        async savePw() {
            if (!this.pwForm.title?.trim()) return alert('Titel erforderlich');
            let r;
            if (this.editingPwId) {
                r = await fetch('/api/personal-passwords/' + this.editingPwId, { method: 'PUT', headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.pwForm) });
            } else {
                r = await fetch('/api/personal-passwords', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.pwForm) });
            }
            if (r.ok) { this.pwModal = false; await this.loadPasswords(); }
            else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        async deletePw(id) {
            if (!confirm('Passwort löschen?')) return;
            await fetch('/api/personal-passwords/' + id, { method: 'DELETE' });
            this.pwModal = false;
            await this.loadPasswords();
        },

        async copyText(text) {
            try {
                await navigator.clipboard.writeText(text);
                this.copyToast = true;
                setTimeout(() => this.copyToast = false, 2000);
            } catch(e) {
                // Fallback
                const el = document.createElement('textarea');
                el.value = text;
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
                this.copyToast = true;
                setTimeout(() => this.copyToast = false, 2000);
            }
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

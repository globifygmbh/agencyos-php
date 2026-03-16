<?php
$pageTitle = 'KI-Assistent';
require __DIR__ . '/_layout.php';
?>

<div x-data="aiApp()" x-init="init()">

    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-500 to-purple-600 mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H4a2 2 0 01-2-2V5a2 2 0 012-2h16a2 2 0 012 2v10a2 2 0 01-2 2h-1"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-white">KI-Assistent</h2>
            <p class="text-gray-400 text-sm mt-1">Powered by OpenAI / Anthropic Claude</p>
        </div>

        <!-- Mode Tabs -->
        <div class="flex gap-2 mb-6 bg-gray-900 border border-gray-800 rounded-2xl p-2">
            <button @click="mode = 'text'"
                    class="flex-1 py-2 text-sm font-medium rounded-xl transition-colors"
                    :class="mode === 'text' ? 'bg-brand-500 text-white' : 'text-gray-400 hover:text-white'">
                Text bearbeiten
            </button>
            <button @click="mode = 'generate'"
                    class="flex-1 py-2 text-sm font-medium rounded-xl transition-colors"
                    :class="mode === 'generate' ? 'bg-brand-500 text-white' : 'text-gray-400 hover:text-white'">
                Inhalt generieren
            </button>
            <button @click="mode = 'analyze'"
                    class="flex-1 py-2 text-sm font-medium rounded-xl transition-colors"
                    :class="mode === 'analyze' ? 'bg-brand-500 text-white' : 'text-gray-400 hover:text-white'">
                Analyse
            </button>
        </div>

        <!-- Text Edit Mode -->
        <div x-show="mode === 'text'" class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Aktion</label>
                <div class="grid grid-cols-3 gap-2">
                    <template x-for="a in textActions" :key="a.id">
                        <button @click="selectedAction = a.id"
                                class="py-2 px-3 text-xs rounded-xl border transition-all text-left"
                                :class="selectedAction === a.id
                                    ? 'border-brand-500 bg-brand-500/10 text-brand-400'
                                    : 'border-gray-700 bg-gray-800/50 text-gray-400 hover:text-white hover:border-gray-600'">
                            <div class="font-medium" x-text="a.label"></div>
                            <div class="text-gray-500 text-xs mt-0.5" x-text="a.desc"></div>
                        </button>
                    </template>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Text eingeben</label>
                <textarea x-model="inputText" rows="5"
                          placeholder="Füge hier deinen Text ein…"
                          class="w-full bg-gray-900 border border-gray-700 text-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 resize-none"></textarea>
            </div>
        </div>

        <!-- Generate Mode -->
        <div x-show="mode === 'generate'" class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Was soll generiert werden?</label>
                <select x-model="selectedAction"
                        class="w-full bg-gray-900 border border-gray-700 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="write_post">Social-Media-Post</option>
                    <option value="write_email">E-Mail / Newsletter</option>
                    <option value="write_caption">Caption für Bild</option>
                    <option value="write_description">Produktbeschreibung</option>
                    <option value="write_proposal">Projektvorschlag</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Prompt / Thema</label>
                <textarea x-model="inputText" rows="4"
                          placeholder="Beschreibe was du brauchst…"
                          class="w-full bg-gray-900 border border-gray-700 text-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 resize-none"></textarea>
            </div>
        </div>

        <!-- Analyze Mode -->
        <div x-show="mode === 'analyze'" class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Analyse-Art</label>
                <select x-model="selectedAction"
                        class="w-full bg-gray-900 border border-gray-700 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    <option value="sentiment">Stimmungsanalyse</option>
                    <option value="summarize">Zusammenfassung</option>
                    <option value="keywords">Keywords extrahieren</option>
                    <option value="readability">Lesbarkeit prüfen</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Text</label>
                <textarea x-model="inputText" rows="5"
                          placeholder="Text eingeben…"
                          class="w-full bg-gray-900 border border-gray-700 text-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 resize-none"></textarea>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="mt-4">
            <button @click="process()" :disabled="loading"
                    class="w-full bg-brand-500 hover:bg-brand-600 disabled:opacity-50 text-white font-medium py-3 rounded-xl transition-colors flex items-center justify-center gap-2">
                <template x-if="loading">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </template>
                <span x-text="loading ? 'Verarbeitung…' : 'Verarbeiten'"></span>
            </button>
        </div>

        <!-- Result -->
        <template x-if="result">
            <div class="mt-6 bg-gray-900 border border-gray-800 rounded-2xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-white">Ergebnis</h3>
                    <button @click="copyResult()"
                            class="text-xs text-gray-400 hover:text-white bg-gray-800 hover:bg-gray-700 px-3 py-1.5 rounded-lg transition-colors">
                        <span x-text="copied ? '✓ Kopiert' : 'Kopieren'"></span>
                    </button>
                </div>
                <div class="text-sm text-gray-200 whitespace-pre-wrap leading-relaxed" x-text="result"></div>
            </div>
        </template>

        <!-- Error -->
        <template x-if="error">
            <div class="mt-4 bg-red-900/20 border border-red-800 text-red-400 rounded-xl p-4 text-sm" x-text="error"></div>
        </template>
    </div>
</div>

<script>
function aiApp() {
    return {
        mode: 'text',
        selectedAction: 'improve',
        inputText: '',
        result: '',
        error: '',
        loading: false,
        copied: false,
        textActions: [
            { id: 'improve',    label: 'Verbessern',   desc: 'Stil & Grammatik' },
            { id: 'shorter',    label: 'Kürzen',       desc: 'Kompakter machen' },
            { id: 'longer',     label: 'Erweitern',    desc: 'Ausführlicher' },
            { id: 'formal',     label: 'Formell',      desc: 'Professioneller Ton' },
            { id: 'casual',     label: 'Locker',       desc: 'Freundlicher Ton' },
            { id: 'translate_en', label: 'Englisch',   desc: 'Übersetzen' },
        ],

        init() {
            // nothing to preload
        },

        async process() {
            if (!this.inputText.trim()) return;
            this.loading = true;
            this.result = '';
            this.error = '';
            try {
                const r = await fetch('/api/ai/process', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ action: this.selectedAction, text: this.inputText })
                });
                const d = await r.json();
                if (r.ok) {
                    this.result = d.result || d.text || JSON.stringify(d);
                } else {
                    this.error = d.detail || 'Fehler bei der Verarbeitung';
                }
            } catch(e) {
                this.error = 'Netzwerkfehler: ' + e.message;
            }
            this.loading = false;
        },

        async copyResult() {
            await navigator.clipboard.writeText(this.result);
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

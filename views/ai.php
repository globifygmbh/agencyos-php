<?php $pageTitle = 'KI-Assistent'; ?>
<?php require __DIR__ . '/_layout.php'; ?>

<div x-data="aiPage()" x-init="init()" class="max-w-3xl mx-auto">

    <!-- Header -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-4 relative"
             style="background: linear-gradient(135deg, #009dde, #0070a8);">
            <svg class="w-8 h-8 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </div>
        <h2 class="font-heading text-2xl font-semibold text-white mb-1">AgencyOS Assistent</h2>
        <p class="text-white/40 text-sm">Sag einfach, was erledigt werden soll.</p>
    </div>

    <!-- Main Chat Area -->
    <div class="glass-card overflow-hidden">

        <!-- Input Step -->
        <div x-show="step === 'input'" class="p-6">
            <!-- Quick Actions -->
            <div class="flex flex-wrap gap-2 mb-4">
                <button @click="setQuickAction('Erstelle einen neuen Task')"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-colors"
                        style="background: rgba(0,157,222,0.1); color: #009dde; border: 1px solid rgba(0,157,222,0.2);">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                    Task erstellen
                </button>
                <button @click="setQuickAction('Erstelle einen Termin für')"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-colors"
                        style="background: rgba(52,199,89,0.1); color: #34c759; border: 1px solid rgba(52,199,89,0.2);">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Termin buchen
                </button>
                <button @click="setQuickAction('Starte die Zeiterfassung für')"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-colors"
                        style="background: rgba(255,204,0,0.1); color: #ffcc00; border: 1px solid rgba(255,204,0,0.2);">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Zeit starten
                </button>
                <button @click="setQuickAction('Beantrage Urlaub vom')"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-colors"
                        style="background: rgba(88,86,214,0.1); color: #5856d6; border: 1px solid rgba(88,86,214,0.2);">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4" stroke-width="1.75"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2"/></svg>
                    Urlaub beantragen
                </button>
                <button @click="setQuickAction('Zeige mir meine offenen Tasks')"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-colors"
                        style="background: rgba(255,59,48,0.1); color: #ff3b30; border: 1px solid rgba(255,59,48,0.2);">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Tasks abfragen
                </button>
                <button @click="setQuickAction('Erstelle ein neues Projekt')"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-colors"
                        style="background: rgba(245,158,11,0.1); color: #f59e0b; border: 1px solid rgba(245,158,11,0.2);">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Projekt erstellen
                </button>
                <button @click="setQuickAction('Erstelle einen Bericht über')"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-colors"
                        style="background: rgba(52,199,89,0.1); color: #34c759; border: 1px solid rgba(52,199,89,0.2);">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Bericht erstellen
                </button>
            </div>

            <!-- Input Area -->
            <div class="relative">
                <textarea x-model="input"
                          @keydown.enter.prevent="if (!$event.shiftKey) processInput()"
                          :disabled="isProcessing"
                          placeholder="z.B. Erstelle einen Task für Website Design bis morgen..."
                          rows="3"
                          class="input-field resize-none pr-24 py-3 text-sm"></textarea>
                <div class="absolute right-3 bottom-3 flex gap-2">
                    <!-- Voice Button -->
                    <button @click="isRecording ? stopRecording() : startRecording()"
                            :class="isRecording ? 'bg-red-500 text-white animate-pulse' : 'bg-white/10 text-white/60 hover:text-white hover:bg-white/20'"
                            class="p-2 rounded-xl transition-all"
                            :title="isRecording ? 'Aufnahme stoppen' : 'Sprache eingeben'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 016 0v6a3 3 0 01-3 3z"/>
                        </svg>
                    </button>
                    <!-- Send Button -->
                    <button @click="processInput()"
                            :disabled="isProcessing || !input.trim()"
                            :class="input.trim() && !isProcessing ? 'opacity-100' : 'opacity-40'"
                            class="p-2 rounded-xl transition-all text-white"
                            style="background: #009dde;">
                        <svg x-show="!isProcessing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <svg x-show="isProcessing" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Recording indicator -->
            <div x-show="isRecording" x-cloak class="flex items-center gap-2 mt-2 text-red-400 text-sm">
                <span class="w-2 h-2 bg-red-400 rounded-full animate-pulse"></span>
                Aufnahme läuft... Klicke auf das Mikrofon zum Beenden
            </div>

            <!-- Processing indicator -->
            <div x-show="isProcessing && !isRecording" x-cloak class="flex items-center gap-2 mt-2 text-white/40 text-sm">
                <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                KI verarbeitet deine Anfrage...
            </div>
        </div>

        <!-- Confirm Step -->
        <div x-show="step === 'confirm'" x-cloak class="p-6">
            <div x-show="result">
                <!-- Action Card -->
                <div class="rounded-2xl p-4 mb-4" :style="'background: ' + actionBg(result?.action)">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="text-2xl" x-text="actionEmoji(result?.action)">✨</span>
                        <div>
                            <h3 class="font-heading font-semibold text-white text-sm" x-text="result?.message || 'Aktion erkannt'"></h3>
                            <p class="text-xs text-white/50" x-text="actionTypeLabel(result?.action)"></p>
                        </div>
                    </div>

                    <!-- Task details -->
                    <template x-if="result?.action === 'create_task'">
                        <div class="space-y-2 text-sm">
                            <div class="flex gap-2"><span class="text-white/40 w-20 flex-shrink-0">Titel:</span><span class="text-white font-medium" x-text="result?.data?.title"></span></div>
                            <div x-show="result?.data?.description" class="flex gap-2"><span class="text-white/40 w-20 flex-shrink-0">Beschr.:</span><span class="text-white/80" x-text="result?.data?.description"></span></div>
                            <div x-show="result?.data?.deadline" class="flex gap-2"><span class="text-white/40 w-20 flex-shrink-0">Deadline:</span><span class="text-white/80" x-text="formatDateDisplay(result?.data?.deadline)"></span></div>
                            <div x-show="result?.data?.priority" class="flex gap-2"><span class="text-white/40 w-20 flex-shrink-0">Priorität:</span><span class="text-white/80" x-text="priorityLabel(result?.data?.priority)"></span></div>
                        </div>
                    </template>

                    <!-- Event details -->
                    <template x-if="result?.action === 'create_event'">
                        <div class="space-y-2 text-sm">
                            <div class="flex gap-2"><span class="text-white/40 w-20 flex-shrink-0">Titel:</span><span class="text-white font-medium" x-text="result?.data?.title"></span></div>
                            <div x-show="result?.data?.start_date" class="flex gap-2"><span class="text-white/40 w-20 flex-shrink-0">Datum:</span><span class="text-white/80" x-text="formatDateDisplay(result?.data?.start_date) + (result?.data?.start_time ? ' ' + result?.data?.start_time : '')"></span></div>
                            <div x-show="result?.data?.location" class="flex gap-2"><span class="text-white/40 w-20 flex-shrink-0">Ort:</span><span class="text-white/80" x-text="result?.data?.location"></span></div>
                        </div>
                    </template>

                    <!-- Vacation details -->
                    <template x-if="result?.action === 'request_vacation'">
                        <div class="space-y-2 text-sm">
                            <div class="flex gap-2"><span class="text-white/40 w-20 flex-shrink-0">Von:</span><span class="text-white font-medium" x-text="formatDateDisplay(result?.data?.start_date)"></span></div>
                            <div class="flex gap-2"><span class="text-white/40 w-20 flex-shrink-0">Bis:</span><span class="text-white font-medium" x-text="formatDateDisplay(result?.data?.end_date)"></span></div>
                        </div>
                    </template>

                    <!-- Query/Report result -->
                    <template x-if="result?.executed && result?.execResult">
                        <div class="mt-2 p-3 rounded-xl bg-white/5 text-sm text-white/80">
                            <div x-show="result?.execResult?.text" x-text="result?.execResult?.text"></div>
                            <div x-show="result?.execResult?.data" class="font-mono text-xs overflow-auto max-h-32" x-text="JSON.stringify(result?.execResult?.data, null, 2)"></div>
                        </div>
                    </template>

                    <!-- Unknown action -->
                    <template x-if="result?.action === 'unknown'">
                        <div class="text-sm text-white/60" x-text="result?.message || 'Ich konnte diese Anfrage leider nicht verstehen.'"></div>
                    </template>
                </div>

                <!-- Multi-action list -->
                <template x-if="result?.action === 'multi_action'">
                    <div class="space-y-2 mb-4">
                        <p class="text-xs text-white/40 font-medium mb-2">Folgende Aktionen werden ausgeführt:</p>
                        <template x-for="(action, idx) in multiActions" :key="idx">
                            <div class="flex items-center gap-2 p-3 rounded-xl bg-white/5 group">
                                <span class="text-lg flex-shrink-0" x-text="actionEmoji(action.action)"></span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-white" x-text="action.data?.title || action.data?.description || action.action"></p>
                                    <p class="text-xs text-white/40" x-text="actionTypeLabel(action.action)"></p>
                                </div>
                                <button @click="multiActions.splice(idx, 1)"
                                        class="opacity-0 group-hover:opacity-100 p-1 rounded-lg hover:bg-red-500/20 text-red-400 transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- Action Buttons -->
                <div x-show="!result?.executed" class="flex gap-3">
                    <button @click="step = 'input'; result = null;"
                            class="flex-1 py-2.5 rounded-xl text-sm font-medium text-white/60 hover:bg-white/8 transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                        Bearbeiten
                    </button>
                    <button @click="result?.action === 'multi_action' ? executeMultiActions() : executeAction()"
                            :disabled="isProcessing"
                            class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white transition-all hover:opacity-90 flex items-center justify-center gap-2"
                            style="background: #009dde;">
                        <svg x-show="!isProcessing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <svg x-show="isProcessing" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span x-text="result?.action === 'multi_action' ? multiActions.length + ' Aktionen bestätigen' : 'Bestätigen'"></span>
                    </button>
                </div>

                <!-- Already executed = show "Neue Anfrage" -->
                <div x-show="result?.executed" class="flex justify-center">
                    <button @click="step = 'input'; result = null; input = '';"
                            class="py-2.5 px-6 rounded-xl text-sm font-medium transition-colors"
                            style="background: rgba(0,157,222,0.15); color: #009dde;">
                        Neue Anfrage stellen
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading Step -->
        <div x-show="step === 'loading'" x-cloak class="p-12 flex flex-col items-center">
            <div class="w-12 h-12 rounded-full flex items-center justify-center mb-4 animate-spin"
                 style="border: 2px solid rgba(0,157,222,0.3); border-top-color: #009dde;">
            </div>
            <p class="text-white/50 text-sm">Aktionen werden ausgeführt...</p>
        </div>

        <!-- Success Step -->
        <div x-show="step === 'success'" x-cloak class="p-12 flex flex-col items-center">
            <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4"
                 style="background: rgba(52,199,89,0.2);">
                <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <p class="font-heading font-semibold text-white mb-1">Erledigt!</p>
            <p class="text-white/40 text-sm">Aktion erfolgreich ausgeführt</p>
            <button @click="step = 'input'; result = null; input = '';"
                    class="mt-4 py-2 px-6 rounded-xl text-sm font-medium transition-colors"
                    style="background: rgba(0,157,222,0.15); color: #009dde;">
                Neue Anfrage stellen
            </button>
        </div>

    </div>

    <!-- Chat History (recent results) -->
    <div x-show="history.length > 0" x-cloak class="mt-6 space-y-3">
        <h3 class="text-sm font-medium text-white/40">Verlauf</h3>
        <template x-for="(item, idx) in history.slice().reverse()" :key="idx">
            <div class="glass-card p-4 flex items-start gap-3">
                <span class="text-lg flex-shrink-0" x-text="actionEmoji(item.action)"></span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-white font-medium" x-text="item.message"></p>
                    <p class="text-xs text-white/30 mt-0.5" x-text="item.time"></p>
                </div>
                <span class="px-2 py-0.5 rounded-full text-xs"
                      :class="item.success ? 'bg-green-500/10 text-green-400' : 'bg-red-500/10 text-red-400'"
                      x-text="item.success ? 'Erledigt' : 'Fehler'"></span>
            </div>
        </template>
    </div>

</div>

<!-- Floating AI Button (on all pages via layout — but also shown here when on the AI page) -->
<script>
function aiPage() {
    return {
        input: '',
        isProcessing: false,
        isRecording: false,
        step: 'input', // input, confirm, loading, success
        result: null,
        multiActions: [],
        history: [],
        mediaRecorder: null,
        audioChunks: [],

        init() {
            // Load history from session
        },

        setQuickAction(text) {
            this.input = text;
            this.$nextTick(() => {
                const ta = this.$el.querySelector('textarea');
                if (ta) { ta.focus(); ta.setSelectionRange(text.length, text.length); }
            });
        },

        async startRecording() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                this.audioChunks = [];
                this.mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });
                this.mediaRecorder.ondataavailable = (e) => { if (e.data.size > 0) this.audioChunks.push(e.data); };
                this.mediaRecorder.onstop = async () => {
                    stream.getTracks().forEach(t => t.stop());
                    const blob = new Blob(this.audioChunks, { type: 'audio/webm' });
                    await this.transcribeAudio(blob);
                };
                this.mediaRecorder.start();
                this.isRecording = true;
            } catch (e) {
                alert('Mikrofonzugriff verweigert. Bitte Berechtigungen prüfen.');
            }
        },

        stopRecording() {
            if (this.mediaRecorder && this.isRecording) {
                this.mediaRecorder.stop();
                this.isRecording = false;
            }
        },

        async transcribeAudio(blob) {
            this.isProcessing = true;
            try {
                const fd = new FormData();
                fd.append('file', blob, 'recording.webm');
                const r = await fetch('/api/ai-assistant/transcribe', { method: 'POST', body: fd });
                const d = await r.json();
                if (d.success && d.text) {
                    this.input = d.text;
                    await this.processInput(d.text);
                } else {
                    alert('Spracherkennung fehlgeschlagen');
                }
            } catch (e) {
                alert('Fehler bei der Spracherkennung');
            } finally {
                this.isProcessing = false;
            }
        },

        async processInput(text) {
            text = text || this.input;
            if (!text.trim()) return;
            this.isProcessing = true;
            try {
                const r = await fetch('/api/ai-assistant/process', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ text })
                });
                const data = await r.json();
                if (data.success || data.action) {
                    const immediateActions = ['report', 'query_tasks', 'query_calendar', 'query_projects', 'query_vacation', 'query_team', 'summary', 'search', 'gamification'];
                    const isImmediate = immediateActions.includes(data.action) ||
                        (data.action === 'time_tracking' && ['start', 'stop'].includes(data.data?.operation));

                    if (isImmediate) {
                        const execR = await fetch('/api/ai-assistant/execute', {
                            method: 'POST', headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ action: data.action, data: data.data })
                        });
                        const execData = await execR.json();
                        this.result = { ...data, executed: true, execResult: execData };
                        this.addHistory(data.action, data.message || 'Abfrage ausgeführt', true);
                    } else if (data.action === 'multi_action') {
                        this.multiActions = (data.data?.actions || []).map(a => ({ ...a }));
                        this.result = data;
                    } else {
                        this.result = data;
                    }
                    this.step = 'confirm';
                } else {
                    this.result = { action: 'unknown', message: data.message || 'Konnte nicht verstanden werden.' };
                    this.step = 'confirm';
                }
            } catch (e) {
                alert('Fehler beim Verarbeiten');
            } finally {
                this.isProcessing = false;
            }
        },

        async executeAction() {
            if (!this.result) return;
            this.isProcessing = true;
            try {
                const r = await fetch('/api/ai-assistant/execute', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: this.result.action, data: this.result.data })
                });
                const data = await r.json();
                if (data.success) {
                    this.addHistory(this.result.action, this.result.message || 'Aktion ausgeführt', true);
                    this.step = 'success';
                    setTimeout(() => { this.step = 'input'; this.result = null; this.input = ''; }, 2000);
                } else {
                    alert(data.message || 'Fehler beim Speichern');
                }
            } catch (e) {
                alert('Fehler beim Erstellen');
            } finally {
                this.isProcessing = false;
            }
        },

        async executeMultiActions() {
            if (!this.multiActions.length) return;
            this.step = 'loading';
            this.isProcessing = true;
            let ok = 0, fail = 0;
            for (const action of this.multiActions) {
                try {
                    const r = await fetch('/api/ai-assistant/execute', {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: action.action, data: action.data })
                    });
                    const d = await r.json();
                    d.success ? ok++ : fail++;
                } catch (e) { fail++; }
            }
            this.isProcessing = false;
            if (fail === 0) {
                this.addHistory('multi_action', ok + ' Aktionen erstellt', true);
                this.step = 'success';
                setTimeout(() => { this.step = 'input'; this.result = null; this.input = ''; }, 2000);
            } else {
                alert(ok + ' erstellt, ' + fail + ' fehlgeschlagen');
                this.step = 'input';
            }
        },

        addHistory(action, message, success) {
            const now = new Date();
            this.history.push({ action, message, success, time: now.toTimeString().slice(0, 5) });
            if (this.history.length > 20) this.history.shift();
        },

        actionEmoji(action) {
            const map = {
                create_task: '✅', create_event: '📅', create_project: '🚀',
                time_tracking: '⏱️', request_vacation: '🌴', multi_action: '⚡',
                report: '📊', query_tasks: '📋', query_calendar: '📅',
                query_team: '👥', query_vacation: '🌴', summary: '📝',
                search: '🔍', gamification: '🏆', unknown: '❓'
            };
            return map[action] || '✨';
        },

        actionBg(action) {
            const map = {
                create_task: 'rgba(0,157,222,0.1)', create_event: 'rgba(52,199,89,0.1)',
                create_project: 'rgba(88,86,214,0.1)', time_tracking: 'rgba(255,204,0,0.1)',
                request_vacation: 'rgba(52,199,89,0.1)', multi_action: 'rgba(245,158,11,0.1)',
                report: 'rgba(88,86,214,0.1)', unknown: 'rgba(255,59,48,0.1)'
            };
            return map[action] || 'rgba(0,157,222,0.1)';
        },

        actionTypeLabel(action) {
            const map = {
                create_task: 'Task erstellen', create_event: 'Termin erstellen',
                create_project: 'Projekt erstellen', time_tracking: 'Zeiterfassung',
                request_vacation: 'Urlaubsantrag', multi_action: 'Mehrere Aktionen',
                report: 'Bericht / Auswertung', query_tasks: 'Task-Abfrage',
                query_calendar: 'Kalender-Abfrage', query_team: 'Team-Abfrage',
                query_vacation: 'Urlaubs-Abfrage', summary: 'Zusammenfassung',
                search: 'Suche', gamification: 'Achievements', unknown: 'Unbekannte Anfrage'
            };
            return map[action] || action || '';
        },

        priorityLabel(p) {
            if (p === 'high' || p === 1) return '🔴 Hoch';
            if (p === 'low' || p === 3) return '🟢 Niedrig';
            return '🟡 Mittel';
        },

        formatDateDisplay(d) {
            if (!d) return '';
            try {
                const date = new Date(d);
                const days = ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'];
                const months = ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];
                return days[date.getDay()] + ', ' + date.getDate() + '. ' + months[date.getMonth()] + ' ' + date.getFullYear();
            } catch(e) { return d; }
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

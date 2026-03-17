        </main>

        <!-- ===== MOBILE BOTTOM TAB BAR ===== -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 flex items-stretch"
             style="background: rgba(10,10,10,0.92); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border-top: 1px solid rgba(255,255,255,0.08); padding-bottom: env(safe-area-inset-bottom, 0);">
            <?php
            $btabs = [
                ['href' => '/',          'label' => 'Start',   'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                ['href' => '/tasks',     'label' => 'Tasks',   'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                ['href' => '/time',      'label' => 'Zeiten',  'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['href' => '/chat',      'label' => 'Chat',    'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', 'badge' => true],
                ['href' => '/profile',   'label' => 'Profil',  'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ];
            $cp = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
            foreach ($btabs as $tab):
                $active = ($cp === $tab['href']) || ($tab['href'] !== '/' && str_starts_with($cp, $tab['href']));
            ?>
            <a href="<?= $tab['href'] ?>" class="flex-1 flex flex-col items-center justify-center py-2.5 gap-1 relative transition-colors <?= $active ? 'text-[#009dde]' : 'text-white/40' ?>">
                <div class="relative">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="<?= $active ? '2' : '1.75' ?>" d="<?= $tab['icon'] ?>"/>
                    </svg>
                    <?php if (!empty($tab['badge'])): ?>
                    <span x-show="unreadChat > 0" x-cloak
                          class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-[#009dde] text-white text-[9px] font-bold flex items-center justify-center"
                          x-text="unreadChat > 9 ? '9+' : unreadChat"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] font-medium leading-none"><?= $tab['label'] ?></span>
                <?php if ($active): ?>
                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-4 h-0.5 rounded-full bg-[#009dde]"></div>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </div>
</div>

<!-- ===== FLOATING AI ASSISTANT ===== -->
<div x-data="floatingAI()" x-init="init()" class="fixed bottom-6 right-6 z-50">

    <!-- Chat Panel -->
    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-2"
         class="mb-4 w-80 glass-card rounded-2xl shadow-2xl flex flex-col overflow-hidden"
         style="max-height: 480px; border: 1px solid rgba(0,157,222,0.3);">

        <!-- Header -->
        <div class="flex items-center gap-3 px-4 py-3 border-b border-white/10 flex-shrink-0"
             style="background: linear-gradient(135deg, rgba(0,157,222,0.15), transparent);">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background: linear-gradient(135deg, #009dde, #0070a8);">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-semibold text-white font-heading">AgencyOS Assistent</p>
                <p class="text-xs text-white/40">Frag mich alles!</p>
            </div>
            <a href="/ai" class="text-xs text-white/40 hover:text-white transition-colors px-2 py-1 rounded-lg hover:bg-white/8" title="Vollbild">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
            </a>
            <button @click="open = false" class="text-white/30 hover:text-white transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
        </div>

        <!-- Messages -->
        <div class="flex-1 overflow-y-auto px-3 py-3 space-y-3" id="ai-chat-messages">
            <template x-if="messages.length === 0">
                <!-- Quick Actions -->
                <div class="space-y-2">
                    <p class="text-xs text-white/40 text-center mb-3">Wie kann ich helfen?</p>
                    <div class="grid grid-cols-2 gap-1.5">
                        <template x-for="qa in quickActions" :key="qa.text">
                            <button @click="sendMessage(qa.prompt)"
                                    class="flex items-center gap-1.5 px-2.5 py-2 rounded-xl text-xs font-medium transition-colors text-left"
                                    :style="'background:' + qa.bg + '; color:' + qa.color + '; border: 1px solid ' + qa.color + '30'">
                                <span x-text="qa.icon" class="flex-shrink-0"></span>
                                <span x-text="qa.text" class="leading-tight"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>

            <template x-for="msg in messages" :key="msg.id">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div class="max-w-[85%] px-3 py-2 rounded-2xl text-sm"
                         :class="msg.role === 'user'
                            ? 'text-white rounded-br-sm'
                            : 'bg-white/8 text-white/90 rounded-bl-sm'"
                         :style="msg.role === 'user' ? 'background: #009dde;' : ''">
                        <template x-if="msg.role === 'assistant' && msg.loading">
                            <div class="flex items-center gap-1.5 py-1">
                                <div class="w-1.5 h-1.5 rounded-full bg-white/50 animate-bounce" style="animation-delay: 0ms"></div>
                                <div class="w-1.5 h-1.5 rounded-full bg-white/50 animate-bounce" style="animation-delay: 150ms"></div>
                                <div class="w-1.5 h-1.5 rounded-full bg-white/50 animate-bounce" style="animation-delay: 300ms"></div>
                            </div>
                        </template>
                        <template x-if="!msg.loading">
                            <p x-text="msg.content" class="leading-relaxed whitespace-pre-wrap break-words"></p>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <!-- Input -->
        <div class="px-3 py-3 border-t border-white/10 flex-shrink-0">
            <div class="flex items-center gap-2 bg-white/8 rounded-xl px-3 py-2">
                <input x-model="input" type="text"
                       placeholder="Nachricht schreiben…"
                       class="flex-1 bg-transparent text-sm text-white placeholder-white/30 outline-none"
                       @keydown.enter="sendMessage()"
                       :disabled="loading">
                <!-- Voice button -->
                <button @click="toggleVoice()" :disabled="loading"
                        class="flex-shrink-0 transition-all"
                        :class="recording ? 'text-red-400 animate-pulse' : 'text-white/30 hover:text-white/70'"
                        title="Spracheingabe">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4M12 3a4 4 0 014 4v4a4 4 0 01-8 0V7a4 4 0 014-4z"/>
                    </svg>
                </button>
                <button @click="sendMessage()"
                        :disabled="loading || !input.trim()"
                        class="flex-shrink-0 transition-colors"
                        :style="input.trim() && !loading ? 'color: #009dde' : 'color: rgba(255,255,255,0.2)'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Toggle Button -->
    <button @click="open = !open"
            class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-2xl transition-all hover:scale-110 active:scale-95 relative"
            style="background: linear-gradient(135deg, #009dde, #0070a8);">
        <template x-if="!open">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </template>
        <template x-if="open">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </template>
        <!-- Pulse ring when idle -->
        <template x-if="!open && messages.length === 0">
            <span class="absolute inset-0 rounded-2xl animate-ping opacity-20" style="background: #009dde;"></span>
        </template>
    </button>
</div>

<script>
function floatingAI() {
    return {
        open: false,
        input: '',
        messages: [],
        loading: false,
        recording: false,
        mediaRecorder: null,
        audioChunks: [],
        quickActions: [
            { icon: '📋', text: 'Task erstellen', prompt: 'Erstelle einen neuen Task', bg: 'rgba(0,157,222,0.1)', color: '#009dde' },
            { icon: '📅', text: 'Termin buchen', prompt: 'Erstelle einen Termin für', bg: 'rgba(52,199,89,0.1)', color: '#34c759' },
            { icon: '⏱️', text: 'Zeit starten', prompt: 'Starte die Zeiterfassung', bg: 'rgba(255,204,0,0.1)', color: '#ffcc00' },
            { icon: '🌴', text: 'Urlaub beantragen', prompt: 'Beantrage Urlaub vom', bg: 'rgba(88,86,214,0.1)', color: '#7c3aed' },
        ],
        msgCount: 0,

        init() {},

        async sendMessage(text) {
            const msg = text || this.input.trim();
            if (!msg) return;
            this.input = '';
            this.messages.push({ id: ++this.msgCount, role: 'user', content: msg });
            this.scrollToBottom();

            const loadingId = ++this.msgCount;
            this.messages.push({ id: loadingId, role: 'assistant', loading: true, content: '' });
            this.loading = true;

            try {
                const r = await fetch('/api/ai-assistant/process', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ text: msg })
                });
                const data = await r.json();
                const idx = this.messages.findIndex(m => m.id === loadingId);
                if (idx !== -1) {
                    this.messages[idx] = { id: loadingId, role: 'assistant', loading: false, content: data.message || data.response || (data.detail ? '❌ ' + data.detail : '✅ Erledigt!') };
                }
            } catch(e) {
                const idx = this.messages.findIndex(m => m.id === loadingId);
                if (idx !== -1) {
                    this.messages[idx] = { id: loadingId, role: 'assistant', loading: false, content: '❌ Verbindungsfehler' };
                }
            }

            this.loading = false;
            this.scrollToBottom();
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const el = document.getElementById('ai-chat-messages');
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        async toggleVoice() {
            if (this.recording) {
                this.mediaRecorder && this.mediaRecorder.stop();
                return;
            }
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                this.audioChunks = [];
                this.mediaRecorder = new MediaRecorder(stream);
                this.mediaRecorder.ondataavailable = e => { if (e.data.size > 0) this.audioChunks.push(e.data); };
                this.mediaRecorder.onstop = async () => {
                    stream.getTracks().forEach(t => t.stop());
                    this.recording = false;
                    await this.transcribeAudio();
                };
                this.mediaRecorder.start();
                this.recording = true;
            } catch (e) {
                console.error('Mikrofon nicht verfügbar', e);
            }
        },

        async transcribeAudio() {
            if (!this.audioChunks.length) return;
            const blob = new Blob(this.audioChunks, { type: 'audio/webm' });
            const fd = new FormData();
            fd.append('audio', blob, 'voice.webm');
            try {
                const r = await fetch('/api/ai-assistant/transcribe', { method: 'POST', body: fd });
                const data = await r.json();
                if (data.text) {
                    this.input = data.text;
                    this.$nextTick(() => document.querySelector('#ai-chat-messages')?.closest('.glass-card')?.querySelector('input')?.focus());
                }
            } catch (e) { console.error('Transkription fehlgeschlagen', e); }
        }
    };
}
</script>
</body>
</html>

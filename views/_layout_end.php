        </main>
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
                const r = await fetch('/api/ai/assist', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: msg })
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
        }
    };
}
</script>
</body>
</html>

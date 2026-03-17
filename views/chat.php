<?php $pageTitle = 'Chat'; ?>
<?php require __DIR__ . '/_layout.php'; ?>

<div x-data="chatApp()" x-init="init()" class="flex -m-6 overflow-hidden" style="height: calc(100vh - 3.5rem)">

    <!-- Conversations List -->
    <div class="w-72 flex-shrink-0 flex flex-col border-r border-white/8"
         style="background: rgba(10,10,10,0.8);">
        <div class="p-4 border-b border-white/8">
            <div class="relative mb-3">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="search" x-model="convSearch" placeholder="Suchen…"
                       class="input-field pl-9 text-sm">
            </div>
            <button @click="openNewConv()" class="btn-primary w-full justify-center text-sm py-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Neue Unterhaltung
            </button>
        </div>

        <div class="flex-1 overflow-y-auto">
            <template x-for="conv in filteredConvs" :key="conv.id">
                <button @click="selectConv(conv)"
                        class="w-full text-left px-4 py-3 transition-colors border-b border-white/5 last:border-0"
                        :class="activeConv?.id === conv.id ? 'bg-primary-500/10' : 'hover:bg-white/4'">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-bold text-white"
                             style="background: linear-gradient(135deg, #009dde, #0070a8);"
                             x-text="(conv.name || conv.other_user_name || '?').charAt(0).toUpperCase()"></div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-white truncate"
                                 x-text="conv.name || conv.other_user_name || 'Unbekannt'"></div>
                            <div class="text-xs text-white/30 truncate" x-text="conv.last_message || ''">&nbsp;</div>
                        </div>
                        <template x-if="conv.unread_count > 0">
                            <span class="text-white text-xs rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0 font-bold"
                                  style="background: #009dde;"
                                  x-text="conv.unread_count"></span>
                        </template>
                    </div>
                </button>
            </template>
            <template x-if="conversations.length === 0">
                <div class="flex flex-col items-center py-12 px-4 text-white/30">
                    <svg class="w-8 h-8 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <p class="text-sm">Noch keine Unterhaltungen</p>
                </div>
            </template>
        </div>
    </div>

    <!-- Chat Area -->
    <div class="flex-1 flex flex-col min-w-0">
        <template x-if="!activeConv">
            <div class="flex-1 flex flex-col items-center justify-center text-white/20">
                <svg class="w-16 h-16 mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <p class="text-sm">Wähle eine Unterhaltung aus</p>
            </div>
        </template>

        <template x-if="activeConv">
            <div class="flex flex-col h-full">
                <!-- Chat Header -->
                <div class="px-6 py-4 border-b border-white/8 flex items-center gap-3"
                     style="background: rgba(0,0,0,0.4); backdrop-filter: blur(20px);">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold"
                         style="background: linear-gradient(135deg, #009dde, #0070a8);"
                         x-text="(activeConv.name || activeConv.other_user_name || '?').charAt(0).toUpperCase()"></div>
                    <div>
                        <div class="text-sm font-semibold text-white" x-text="activeConv.name || activeConv.other_user_name"></div>
                        <div class="text-xs text-white/30" x-text="activeConv.is_group ? 'Gruppe' : 'Direktnachricht'"></div>
                    </div>
                </div>

                <!-- Messages -->
                <div class="flex-1 overflow-y-auto px-6 py-4 space-y-3" x-ref="msgContainer">
                    <template x-for="msg in messages" :key="msg.id">
                        <div class="flex items-end gap-2"
                             :class="msg.sender_id === currentUserId ? 'flex-row-reverse' : 'flex-row'">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                 style="background: linear-gradient(135deg, #009dde, #0070a8);"
                                 x-text="(msg.sender_name || '?').charAt(0).toUpperCase()"></div>
                            <div class="max-w-xs lg:max-w-sm xl:max-w-md">
                                <div class="px-4 py-2.5 rounded-2xl text-sm leading-relaxed"
                                     :class="msg.sender_id === currentUserId
                                        ? 'text-white rounded-br-sm'
                                        : 'bg-white/8 text-white rounded-bl-sm'"
                                     :style="msg.sender_id === currentUserId ? 'background: #009dde;' : ''">
                                    <template x-if="msg.file_url">
                                        <a :href="msg.file_url" target="_blank"
                                           class="flex items-center gap-2 underline opacity-80 hover:opacity-100 mb-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                            </svg>
                                            <span x-text="msg.file_name || 'Datei'"></span>
                                        </a>
                                    </template>
                                    <span x-text="msg.content"></span>
                                </div>
                                <div class="text-xs text-white/20 mt-1"
                                     :class="msg.sender_id === currentUserId ? 'text-right' : 'text-left'"
                                     x-text="formatTime(msg.created_at)"></div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Input -->
                <div class="px-4 py-4 border-t border-white/8" style="background: rgba(0,0,0,0.3);">
                    <div class="flex items-center gap-2">
                        <label class="text-white/30 hover:text-white cursor-pointer transition-colors p-2 rounded-xl hover:bg-white/8">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                            </svg>
                            <input type="file" class="hidden" @change="sendFile($event)">
                        </label>
                        <input x-model="newMessage" type="text" placeholder="Nachricht schreiben…"
                               @keydown.enter="sendMessage()"
                               class="input-field flex-1">
                        <button @click="sendMessage()"
                                class="p-2.5 rounded-xl text-white transition-colors hover:opacity-90"
                                style="background: #009dde;">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- New Conversation Modal -->
    <div x-show="newConvModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);"
         @click.self="newConvModal = false">
        <div class="glass-card w-full max-w-md" @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-white/8">
                <h2 class="font-heading font-semibold text-white">Neue Unterhaltung</h2>
                <button @click="newConvModal = false" class="p-1.5 rounded-xl text-white/40 hover:text-white hover:bg-white/8 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-white/50 mb-1.5">Empfänger</label>
                    <select x-model="newConvUser" class="input-field">
                        <option value="">Benutzer wählen</option>
                        <template x-for="u in allUsers" :key="u.id">
                            <option :value="u.id" x-text="u.first_name + ' ' + u.last_name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/50 mb-1.5">Erste Nachricht</label>
                    <input x-model="newConvMsg" type="text" class="input-field" placeholder="Hallo...">
                </div>
            </div>
            <div class="px-6 py-4 border-t border-white/8 flex gap-3">
                <button @click="createConv()" class="btn-primary">Starten</button>
                <button @click="newConvModal = false" class="text-white/40 hover:text-white text-sm transition-colors">Abbrechen</button>
            </div>
        </div>
    </div>
</div>

<script>
const CURRENT_USER_ID = '<?= htmlspecialchars($currentUser['id']) ?>';

function chatApp() {
    return {
        conversations: [], messages: [], activeConv: null, newMessage: '',
        convSearch: '', newConvModal: false, newConvUser: '', newConvMsg: '',
        allUsers: [], pollInterval: null, currentUserId: CURRENT_USER_ID,

        get filteredConvs() {
            if (!this.convSearch) return this.conversations;
            const q = this.convSearch.toLowerCase();
            return this.conversations.filter(c => (c.name || c.other_user_name || '').toLowerCase().includes(q));
        },

        async init() {
            await this.loadConversations();
            const r = await fetch('/api/users');
            const d = await r.json();
            this.allUsers = (Array.isArray(d) ? d : (d.users || [])).filter(u => u.id !== this.currentUserId);
            this.pollInterval = setInterval(() => {
                if (this.activeConv) this.loadMessages(false);
                else this.loadConversations();
            }, 5000);
        },

        async loadConversations() {
            const r = await fetch('/api/chat/conversations');
            const d = await r.json();
            this.conversations = Array.isArray(d) ? d : [];
        },

        async selectConv(conv) { this.activeConv = conv; await this.loadMessages(true); },

        async loadMessages(scrollBottom = true) {
            if (!this.activeConv) return;
            const r = await fetch('/api/chat/conversations/' + this.activeConv.id + '/messages');
            const d = await r.json();
            this.messages = Array.isArray(d) ? d : [];
            if (scrollBottom) this.$nextTick(() => { const c = this.$refs.msgContainer; if (c) c.scrollTop = c.scrollHeight; });
        },

        async sendMessage() {
            if (!this.newMessage.trim() || !this.activeConv) return;
            const msg = this.newMessage; this.newMessage = '';
            const r = await fetch('/api/chat/conversations/' + this.activeConv.id + '/messages', {
                method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ content: msg })
            });
            if (r.ok) await this.loadMessages(true);
        },

        async sendFile(ev) {
            const file = ev.target.files[0];
            if (!file || !this.activeConv) return;
            const fd = new FormData(); fd.append('file', file);
            const r = await fetch('/api/chat/conversations/' + this.activeConv.id + '/files', { method: 'POST', body: fd });
            if (r.ok) await this.loadMessages(true);
        },

        openNewConv() { this.newConvUser = ''; this.newConvMsg = ''; this.newConvModal = true; },

        async createConv() {
            if (!this.newConvUser) return alert('Benutzer wählen');
            const r = await fetch('/api/chat/conversations', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ participant_id: this.newConvUser, initial_message: this.newConvMsg })
            });
            if (r.ok) {
                const conv = await r.json(); this.newConvModal = false;
                await this.loadConversations();
                const found = this.conversations.find(c => c.id === conv.id);
                if (found) await this.selectConv(found);
            } else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        formatTime(d) {
            if (!d) return '';
            const date = new Date(d); const now = new Date();
            if (date.toDateString() === now.toDateString()) return date.toLocaleTimeString('de-DE', {hour:'2-digit',minute:'2-digit'});
            return date.toLocaleDateString('de-DE', {day:'2-digit',month:'2-digit'});
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

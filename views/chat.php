<?php
$pageTitle = 'Chat';
require __DIR__ . '/_layout.php';
?>

<div x-data="chatApp()" x-init="init()" class="flex h-full -m-6 overflow-hidden" style="height: calc(100vh - 3.5rem)">

    <!-- Conversations List -->
    <div class="w-72 flex-shrink-0 bg-gray-900 border-r border-gray-800 flex flex-col">
        <!-- Search + New -->
        <div class="p-4 border-b border-gray-800">
            <input type="search" x-model="convSearch" placeholder="Suchen…"
                   class="w-full bg-gray-800 border border-gray-700 text-white text-sm rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 mb-3">
            <button @click="openNewConv()"
                    class="w-full bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium py-2 rounded-xl transition-colors">
                + Neue Unterhaltung
            </button>
        </div>

        <!-- List -->
        <div class="flex-1 overflow-y-auto">
            <template x-for="conv in filteredConvs" :key="conv.id">
                <button @click="selectConv(conv)"
                        class="w-full text-left px-4 py-3 hover:bg-gray-800 transition-colors border-b border-gray-800/50 last:border-0"
                        :class="activeConv?.id === conv.id ? 'bg-gray-800' : ''">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-brand-500/30 flex items-center justify-center flex-shrink-0 text-brand-400 text-sm font-bold"
                             x-text="(conv.name || conv.other_user_name || '?').charAt(0).toUpperCase()"></div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-white truncate"
                                 x-text="conv.name || conv.other_user_name || 'Unbekannt'"></div>
                            <div class="text-xs text-gray-500 truncate" x-text="conv.last_message || ''"></div>
                        </div>
                        <template x-if="conv.unread_count > 0">
                            <span class="bg-brand-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0"
                                  x-text="conv.unread_count"></span>
                        </template>
                    </div>
                </button>
            </template>
            <template x-if="conversations.length === 0">
                <p class="text-gray-500 text-sm text-center py-8 px-4">Noch keine Unterhaltungen</p>
            </template>
        </div>
    </div>

    <!-- Chat Area -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- No conversation selected -->
        <template x-if="!activeConv">
            <div class="flex-1 flex items-center justify-center text-gray-500 text-sm">
                Wähle eine Unterhaltung aus
            </div>
        </template>

        <!-- Active Conversation -->
        <template x-if="activeConv">
            <div class="flex flex-col h-full">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-800 flex items-center gap-3 bg-gray-900/50">
                    <div class="w-8 h-8 rounded-full bg-brand-500/30 flex items-center justify-center text-brand-400 text-sm font-bold"
                         x-text="(activeConv.name || activeConv.other_user_name || '?').charAt(0).toUpperCase()"></div>
                    <div>
                        <div class="text-sm font-semibold text-white"
                             x-text="activeConv.name || activeConv.other_user_name"></div>
                        <div class="text-xs text-gray-500" x-text="activeConv.is_group ? 'Gruppe' : 'Direktnachricht'"></div>
                    </div>
                </div>

                <!-- Messages -->
                <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4" x-ref="msgContainer">
                    <template x-for="msg in messages" :key="msg.id">
                        <div class="flex items-end gap-2"
                             :class="msg.sender_id === currentUserId ? 'flex-row-reverse' : 'flex-row'">
                            <div class="w-6 h-6 rounded-full bg-brand-500/30 flex items-center justify-center text-brand-400 text-xs font-bold flex-shrink-0"
                                 x-text="(msg.sender_name || '?').charAt(0).toUpperCase()"></div>
                            <div class="max-w-xs lg:max-w-sm xl:max-w-md">
                                <div class="px-4 py-2 rounded-2xl text-sm"
                                     :class="msg.sender_id === currentUserId
                                        ? 'bg-brand-500 text-white rounded-br-sm'
                                        : 'bg-gray-800 text-gray-100 rounded-bl-sm'">
                                    <!-- File attachment -->
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
                                <div class="text-xs text-gray-600 mt-1"
                                     :class="msg.sender_id === currentUserId ? 'text-right' : 'text-left'"
                                     x-text="formatTime(msg.created_at)"></div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Input -->
                <div class="px-6 py-4 border-t border-gray-800 bg-gray-900/50">
                    <div class="flex items-center gap-3">
                        <label class="text-gray-400 hover:text-white cursor-pointer transition-colors p-2 rounded-xl hover:bg-gray-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                            </svg>
                            <input type="file" class="hidden" @change="sendFile($event)">
                        </label>
                        <input x-model="newMessage" type="text" placeholder="Nachricht schreiben…"
                               @keydown.enter="sendMessage()"
                               class="flex-1 bg-gray-800 border border-gray-700 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <button @click="sendMessage()"
                                class="bg-brand-500 hover:bg-brand-600 text-white p-2.5 rounded-xl transition-colors">
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
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         @click.self="newConvModal = false">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl w-full max-w-md shadow-2xl" @click.stop>
            <div class="px-6 py-4 border-b border-gray-800">
                <h2 class="font-semibold text-white">Neue Unterhaltung</h2>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Empfänger</label>
                    <select x-model="newConvUser"
                            class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="">Benutzer wählen</option>
                        <template x-for="u in allUsers" :key="u.id">
                            <option :value="u.id" x-text="u.first_name + ' ' + u.last_name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Erste Nachricht</label>
                    <input x-model="newConvMsg" type="text"
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-800 flex gap-3">
                <button @click="createConv()"
                        class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-xl">
                    Starten
                </button>
                <button @click="newConvModal = false" class="text-gray-400 text-sm">Abbrechen</button>
            </div>
        </div>
    </div>
</div>

<script>
const CURRENT_USER_ID = '<?= htmlspecialchars($currentUser['id']) ?>';

function chatApp() {
    return {
        conversations: [],
        messages: [],
        activeConv: null,
        newMessage: '',
        convSearch: '',
        newConvModal: false,
        newConvUser: '',
        newConvMsg: '',
        allUsers: [],
        pollInterval: null,
        currentUserId: CURRENT_USER_ID,

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
            // Auto-poll for new messages every 5s
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

        async selectConv(conv) {
            this.activeConv = conv;
            await this.loadMessages(true);
        },

        async loadMessages(scrollBottom = true) {
            if (!this.activeConv) return;
            const r = await fetch('/api/chat/conversations/' + this.activeConv.id + '/messages');
            const d = await r.json();
            this.messages = Array.isArray(d) ? d : [];
            if (scrollBottom) {
                this.$nextTick(() => {
                    const c = this.$refs.msgContainer;
                    if (c) c.scrollTop = c.scrollHeight;
                });
            }
        },

        async sendMessage() {
            if (!this.newMessage.trim() || !this.activeConv) return;
            const msg = this.newMessage;
            this.newMessage = '';
            const r = await fetch('/api/chat/conversations/' + this.activeConv.id + '/messages', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ content: msg })
            });
            if (r.ok) await this.loadMessages(true);
        },

        async sendFile(ev) {
            const file = ev.target.files[0];
            if (!file || !this.activeConv) return;
            const fd = new FormData();
            fd.append('file', file);
            const r = await fetch('/api/chat/conversations/' + this.activeConv.id + '/files', { method: 'POST', body: fd });
            if (r.ok) await this.loadMessages(true);
        },

        openNewConv() {
            this.newConvUser = '';
            this.newConvMsg = '';
            this.newConvModal = true;
        },

        async createConv() {
            if (!this.newConvUser) return alert('Benutzer wählen');
            const r = await fetch('/api/chat/conversations', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ participant_id: this.newConvUser, initial_message: this.newConvMsg })
            });
            if (r.ok) {
                const conv = await r.json();
                this.newConvModal = false;
                await this.loadConversations();
                const found = this.conversations.find(c => c.id === conv.id);
                if (found) await this.selectConv(found);
            } else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        formatTime(d) {
            if (!d) return '';
            const date = new Date(d);
            const now = new Date();
            if (date.toDateString() === now.toDateString()) {
                return date.toLocaleTimeString('de-DE', {hour:'2-digit',minute:'2-digit'});
            }
            return date.toLocaleDateString('de-DE', {day:'2-digit',month:'2-digit'});
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

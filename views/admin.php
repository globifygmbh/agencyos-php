<?php
$pageTitle = 'Admin';
require __DIR__ . '/_layout.php';
?>

<div x-data="adminApp()" x-init="init()">

    <!-- Tabs -->
    <div class="border-b border-white/10 mb-6">
        <div class="flex gap-1 overflow-x-auto">
            <template x-for="t in tabs" :key="t.id">
                <button @click="activeTab = t.id"
                        class="px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px whitespace-nowrap"
                        :class="activeTab === t.id ? 'text-[#009dde] border-[#009dde]' : 'text-white/40 border-transparent hover:text-white'"
                        x-text="t.label"></button>
            </template>
        </div>
    </div>

    <!-- Settings Tab -->
    <div x-show="activeTab === 'settings'" class="max-w-xl space-y-6">
        <div class="glass-card rounded-2xl p-6">
            <h3 class="text-sm font-heading font-semibold text-white mb-4">Systemeinstellungen</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Firmenname</label>
                    <input x-model="settings.company_name" type="text" class="input-field">
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Firmen-E-Mail</label>
                    <input x-model="settings.company_email" type="email" class="input-field">
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Standard-Urlaubstage / Jahr</label>
                    <input x-model="settings.default_vacation_days" type="number" class="input-field">
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Standard-Arbeitsstunden / Tag</label>
                    <input x-model="settings.default_work_hours" type="number" step="0.5" class="input-field">
                </div>
                <div class="flex items-center gap-3">
                    <input x-model="settings.email_notifications_enabled" type="checkbox" id="emailNotif" class="rounded">
                    <label for="emailNotif" class="text-sm text-white/70">E-Mail-Benachrichtigungen aktiviert</label>
                </div>
                <button @click="saveSettings()" class="btn-primary">
                    Speichern
                </button>
            </div>
        </div>
    </div>

    <!-- Users Tab -->
    <div x-show="activeTab === 'users'">
        <div class="flex justify-end mb-4">
            <button @click="inviteModal = true"
                    class="btn-primary flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                Mitarbeiter einladen
            </button>
        </div>

        <div class="space-y-3">
            <template x-for="u in users" :key="u.id">
                <div class="bg-white/5 border border-white/8 rounded-xl p-4 flex items-center gap-4">
                    <div class="w-9 h-9 rounded-full bg-[#009dde]/20 flex items-center justify-center text-sm font-bold flex-shrink-0"
                         style="color: #009dde;"
                         x-text="(u.first_name || '?').charAt(0).toUpperCase()"></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-white" x-text="u.first_name + ' ' + u.last_name"></div>
                        <div class="text-xs text-white/50" x-text="u.email"></div>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-white/8 text-white/40" x-text="u.role"></span>
                    <span class="text-xs px-2 py-0.5 rounded-full"
                          :class="u.is_active ? 'bg-green-900/30 text-green-400' : 'bg-white/8 text-white/50'"
                          x-text="u.is_active ? 'Aktiv' : 'Inaktiv'"></span>
                    <button @click="toggleUser(u)"
                            class="text-xs text-white/50 hover:text-white bg-white/8 hover:bg-white/10 px-2 py-1 rounded-lg transition-colors"
                            x-text="u.is_active ? 'Deaktivieren' : 'Aktivieren'"></button>
                </div>
            </template>
        </div>
    </div>

    <!-- Task Statuses Tab -->
    <div x-show="activeTab === 'statuses'">
        <div class="flex justify-end mb-4">
            <button @click="openStatusCreate()"
                    class="btn-primary flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Neuer Status
            </button>
        </div>

        <div class="space-y-2">
            <template x-for="s in statuses" :key="s.id">
                <div class="bg-white/5 border border-white/8 rounded-xl p-4 flex items-center gap-3">
                    <div class="w-4 h-4 rounded-full flex-shrink-0" :style="'background:' + s.color"></div>
                    <div class="flex-1 text-sm font-medium text-white" x-text="s.name"></div>
                    <span class="text-xs text-white/50" x-text="s.is_done ? 'Abgeschlossen' : 'Offen'"></span>
                    <span class="text-xs text-white/40" x-text="'Pos. ' + s.position"></span>
                    <button @click="deleteStatus(s.id)"
                            class="text-white/40 hover:text-red-400 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- Audit Log Tab -->
    <div x-show="activeTab === 'audit'">
        <div class="space-y-2">
            <template x-for="log in auditLogs" :key="log.id">
                <div class="bg-white/5 border border-white/8 rounded-xl px-4 py-3 flex items-start gap-4">
                    <div class="w-28 flex-shrink-0 text-xs text-white/50" x-text="formatDate(log.created_at)"></div>
                    <div class="flex-1 min-w-0">
                        <span class="text-sm text-white" x-text="log.user_name"></span>
                        <span class="text-sm text-white/40 ml-1" x-text="log.action"></span>
                        <span class="text-sm text-white/50 ml-1" x-text="(log.entity_type || '') + ' ' + (log.entity_id ? '('+log.entity_id.slice(0,8)+'…)' : '')"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Invite Modal -->
    <div x-show="inviteModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);"
         @click.self="inviteModal = false">
        <div class="glass-card rounded-2xl w-full max-w-md shadow-2xl" @click.stop>
            <div class="px-6 py-4 border-b border-white/10">
                <h2 class="font-heading font-semibold text-white">Mitarbeiter einladen</h2>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Vorname</label>
                        <input x-model="inviteForm.first_name" type="text" class="input-field">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Nachname</label>
                        <input x-model="inviteForm.last_name" type="text" class="input-field">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">E-Mail *</label>
                    <input x-model="inviteForm.email" type="email" class="input-field">
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Rolle</label>
                    <select x-model="inviteForm.role" class="input-field">
                        <option value="employee">Mitarbeiter</option>
                        <option value="admin">Admin</option>
                        <option value="owner">Owner</option>
                    </select>
                </div>
                <template x-if="inviteResult">
                    <div class="bg-green-900/20 border border-green-800 rounded-xl p-3 text-xs text-green-400">
                        <p x-text="'Einladung gesendet. Setup-Link: ' + (inviteResult.setup_url || '')"></p>
                    </div>
                </template>
            </div>
            <div class="px-6 py-4 border-t border-white/10 flex gap-3">
                <button @click="sendInvite()" class="btn-primary">
                    Einladen
                </button>
                <button @click="inviteModal = false; inviteResult = null" class="btn-ghost">Schließen</button>
            </div>
        </div>
    </div>

    <!-- Status Create Modal -->
    <div x-show="statusModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);"
         @click.self="statusModal = false">
        <div class="glass-card rounded-2xl w-full max-w-sm shadow-2xl" @click.stop>
            <div class="px-6 py-4 border-b border-white/10">
                <h2 class="font-heading font-semibold text-white">Neuer Task-Status</h2>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Name</label>
                    <input x-model="statusForm.name" type="text" class="input-field">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Farbe</label>
                        <input x-model="statusForm.color" type="color"
                               class="w-full h-10 bg-white/8 border border-white/10 rounded-xl px-2 py-1">
                    </div>
                    <div class="flex items-end gap-2 pb-2">
                        <input x-model="statusForm.is_done" type="checkbox" id="isDone" class="rounded">
                        <label for="isDone" class="text-sm text-white/70">Ist "Fertig"</label>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-white/10 flex gap-3">
                <button @click="saveStatus()" class="btn-primary">
                    Erstellen
                </button>
                <button @click="statusModal = false" class="btn-ghost">Abbrechen</button>
            </div>
        </div>
    </div>
</div>

<script>
function adminApp() {
    return {
        activeTab: 'settings',
        tabs: [
            { id: 'settings', label: 'Einstellungen' },
            { id: 'users',    label: 'Benutzer' },
            { id: 'statuses', label: 'Task-Status' },
            { id: 'audit',    label: 'Audit Log' },
        ],
        settings: {},
        users: [],
        statuses: [],
        auditLogs: [],
        inviteModal: false,
        inviteForm: { first_name: '', last_name: '', email: '', role: 'employee' },
        inviteResult: null,
        statusModal: false,
        statusForm: {},

        async init() {
            await Promise.all([this.loadSettings(), this.loadUsers(), this.loadStatuses(), this.loadAudit()]);
        },

        async loadSettings() {
            const r = await fetch('/api/admin/settings');
            if (r.ok) this.settings = await r.json();
        },

        async saveSettings() {
            const r = await fetch('/api/admin/settings', { method: 'PUT', headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.settings) });
            if (r.ok) alert('Gespeichert');
            else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        async loadUsers() {
            const r = await fetch('/api/users');
            const d = await r.json();
            this.users = Array.isArray(d) ? d : (d.users || []);
        },

        async toggleUser(u) {
            const action = u.is_active ? 'deactivate' : 'activate';
            await fetch('/api/admin/users/' + u.id + '/' + action, { method: 'POST' });
            u.is_active = !u.is_active;
        },

        async sendInvite() {
            const r = await fetch('/api/admin/users/invite', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.inviteForm) });
            if (r.ok) { this.inviteResult = await r.json(); await this.loadUsers(); }
            else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        async loadStatuses() {
            const r = await fetch('/api/tasks/statuses');
            const d = await r.json();
            this.statuses = Array.isArray(d) ? d : [];
        },

        openStatusCreate() {
            this.statusForm = { name: '', color: '#6366f1', is_done: false };
            this.statusModal = true;
        },

        async saveStatus() {
            const r = await fetch('/api/admin/task-statuses', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.statusForm) });
            if (r.ok) { const s = await r.json(); this.statuses.push(s); this.statusModal = false; }
            else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        async deleteStatus(id) {
            if (!confirm('Status löschen?')) return;
            await fetch('/api/admin/task-statuses/' + id, { method: 'DELETE' });
            this.statuses = this.statuses.filter(s => s.id !== id);
        },

        async loadAudit() {
            const r = await fetch('/api/admin/audit-logs?limit=100');
            const d = await r.json();
            this.auditLogs = Array.isArray(d) ? d : [];
        },

        formatDate(d) {
            if (!d) return '';
            return new Date(d).toLocaleString('de-DE', {day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'});
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

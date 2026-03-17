<?php
$pageTitle = 'Admin';
require __DIR__ . '/_layout.php';
?>

<div x-data="adminApp()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-heading text-2xl font-bold text-white">⚙️ Administration</h1>
            <p class="text-xs text-white/40 mt-0.5">Systemverwaltung & Einstellungen</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-white/8 mb-6">
        <div class="flex gap-1 overflow-x-auto">
            <template x-for="t in tabs" :key="t.id">
                <button @click="activeTab = t.id"
                        class="px-4 py-3 text-sm font-medium transition-colors border-b-2 -mb-px whitespace-nowrap flex items-center gap-2"
                        :class="activeTab === t.id ? 'text-[#009dde] border-[#009dde]' : 'text-white/40 border-transparent hover:text-white'">
                    <span x-text="t.icon"></span>
                    <span x-text="t.label"></span>
                </button>
            </template>
        </div>
    </div>

    <!-- ===== EINSTELLUNGEN ===== -->
    <div x-show="activeTab === 'settings'" class="max-w-2xl space-y-6">
        <div class="glass-card rounded-2xl p-6">
            <h3 class="font-heading font-semibold text-white mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-[#009dde]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Firmendaten
            </h3>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Firmenname</label>
                        <input x-model="settings.company_name" type="text" class="input-field" placeholder="Meine Agentur GmbH">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Firmen-E-Mail</label>
                        <input x-model="settings.company_email" type="email" class="input-field" placeholder="info@firma.de">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Telefon</label>
                        <input x-model="settings.company_phone" type="text" class="input-field" placeholder="+49 123 456789">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Website</label>
                        <input x-model="settings.company_website" type="url" class="input-field" placeholder="https://firma.de">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Adresse</label>
                    <input x-model="settings.company_address" type="text" class="input-field" placeholder="Musterstraße 1, 10115 Berlin">
                </div>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-6">
            <h3 class="font-heading font-semibold text-white mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-[#009dde]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Arbeitszeit & Urlaub
            </h3>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Standard-Urlaubstage / Jahr</label>
                        <input x-model="settings.default_vacation_days" type="number" class="input-field" placeholder="28">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Arbeitsstunden / Woche</label>
                        <input x-model="settings.default_work_hours" type="number" step="0.5" class="input-field" placeholder="40">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Urlaubsantrag Vorlaufzeit (Tage)</label>
                        <input x-model="settings.vacation_request_advance_days" type="number" class="input-field" placeholder="14">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Max. Urlaubstage am Stück</label>
                        <input x-model="settings.max_consecutive_vacation_days" type="number" class="input-field" placeholder="30">
                    </div>
                </div>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-6">
            <h3 class="font-heading font-semibold text-white mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-[#009dde]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Benachrichtigungen
            </h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-white">E-Mail-Benachrichtigungen</div>
                        <div class="text-xs text-white/40">Automatische E-Mails für wichtige Ereignisse</div>
                    </div>
                    <input x-model="settings.email_notifications_enabled" type="checkbox"
                           class="w-5 h-5 rounded accent-[#009dde]">
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-white">Aufgaben-E-Mails</div>
                        <div class="text-xs text-white/40">Bei neuer Aufgabenzuweisung</div>
                    </div>
                    <input x-model="settings.task_email_notifications" type="checkbox"
                           class="w-5 h-5 rounded accent-[#009dde]">
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-white">Urlaubsanfragen</div>
                        <div class="text-xs text-white/40">Genehmigungsanfragen per E-Mail</div>
                    </div>
                    <input x-model="settings.vacation_email_notifications" type="checkbox"
                           class="w-5 h-5 rounded accent-[#009dde]">
                </div>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-6">
            <h3 class="font-heading font-semibold text-white mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-[#009dde]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                Aktivitätstypen (Zeiterfassung)
            </h3>
            <p class="text-xs text-white/40 mb-3">Definiere Kategorien für die Zeiterfassung</p>
            <div class="flex flex-wrap gap-2 mb-3">
                <template x-for="(type, idx) in (settings.activity_types || [])" :key="idx">
                    <div class="flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium"
                         style="background: rgba(0,157,222,0.12); color: #009dde; border: 1px solid rgba(0,157,222,0.3);">
                        <span x-text="type"></span>
                        <button @click="removeActivityType(idx)" class="text-[#009dde]/60 hover:text-[#009dde] ml-1">✕</button>
                    </div>
                </template>
            </div>
            <div class="flex gap-2">
                <input x-model="newActivityType" type="text" class="input-field flex-1" placeholder="z.B. Development"
                       @keydown.enter="addActivityType()">
                <button @click="addActivityType()" class="btn-primary px-3">+</button>
            </div>
        </div>

        <button @click="saveSettings()" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Einstellungen speichern
        </button>
    </div>

    <!-- ===== BENUTZER ===== -->
    <div x-show="activeTab === 'users'">
        <div class="flex justify-between items-center mb-4">
            <p class="text-sm text-white/40" x-text="users.length + ' Benutzer'"></p>
            <button @click="inviteModal = true" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                Mitarbeiter einladen
            </button>
        </div>

        <div class="space-y-2">
            <template x-for="u in users" :key="u.id">
                <div class="glass-card p-4 flex items-center gap-4 rounded-2xl">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0 text-white"
                         :style="'background:' + (u.color || '#009dde')"
                         x-text="((u.first_name || '') + ' ' + (u.last_name || '')).trim().charAt(0).toUpperCase() || u.username.charAt(0).toUpperCase()"></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-white" x-text="(u.first_name || '') + ' ' + (u.last_name || '')"></div>
                        <div class="text-xs text-white/40" x-text="u.email"></div>
                        <div class="text-xs text-white/30" x-text="u.position || ''"></div>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full border"
                          :class="{
                            'text-purple-400 bg-purple-500/10 border-purple-500/20': u.role === 'CHEF',
                            'text-blue-400 bg-blue-500/10 border-blue-500/20': u.role === 'MANAGER',
                            'text-white/50 bg-white/5 border-white/10': u.role === 'EMPLOYEE'
                          }"
                          x-text="u.role"></span>
                    <span class="text-xs px-2 py-0.5 rounded-full"
                          :class="u.is_active ? 'bg-green-900/30 text-green-400' : 'bg-white/8 text-white/40'"
                          x-text="u.is_active ? 'Aktiv' : 'Inaktiv'"></span>
                    <div class="flex items-center gap-1">
                        <button @click="editUser(u)" class="p-1.5 text-white/40 hover:text-white hover:bg-white/8 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button @click="toggleUser(u)"
                                class="text-xs px-2.5 py-1 rounded-lg transition-colors"
                                :class="u.is_active ? 'text-red-400 hover:bg-red-500/10' : 'text-green-400 hover:bg-green-500/10'"
                                x-text="u.is_active ? 'Deaktivieren' : 'Aktivieren'"></button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- ===== TASK-STATUS ===== -->
    <div x-show="activeTab === 'statuses'">
        <div class="flex justify-end mb-4">
            <button @click="openStatusCreate()" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Neuer Status
            </button>
        </div>

        <div class="glass-card rounded-2xl overflow-hidden">
            <div class="px-4 py-3 border-b border-white/8 text-xs font-medium text-white/40 uppercase tracking-wider flex gap-4">
                <span class="w-6"></span>
                <span class="flex-1">Name</span>
                <span class="w-24">Typ</span>
                <span class="w-16">Standard</span>
                <span class="w-20">Position</span>
                <span class="w-16"></span>
            </div>
            <template x-for="s in statuses" :key="s.id">
                <div class="px-4 py-3.5 flex items-center gap-4 border-b border-white/5 last:border-0 hover:bg-white/3 transition-colors">
                    <div class="w-4 h-4 rounded-full flex-shrink-0" :style="'background:' + s.color"></div>
                    <div class="flex-1">
                        <span class="text-sm font-medium text-white" x-text="s.name"></span>
                        <template x-if="s.emoji">
                            <span class="ml-2 text-sm" x-text="s.emoji"></span>
                        </template>
                    </div>
                    <span class="w-24 text-xs"
                          :class="s.is_done ? 'text-green-400' : 'text-white/40'"
                          x-text="s.is_done ? '✅ Abgeschlossen' : '⬜ Offen'"></span>
                    <span class="w-16 text-xs"
                          :class="s.is_default ? 'text-[#009dde]' : 'text-white/25'"
                          x-text="s.is_default ? '⭐ Ja' : '–'"></span>
                    <span class="w-20 text-xs text-white/40" x-text="'Pos. ' + s.sort_order"></span>
                    <div class="w-16 flex gap-1">
                        <button @click="editStatus(s)" class="p-1.5 text-white/40 hover:text-white hover:bg-white/8 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button @click="deleteStatus(s.id)" class="p-1.5 text-white/40 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- ===== TEAM WORKLOAD ===== -->
    <div x-show="activeTab === 'workload'">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="u in workloadData" :key="u.id">
                <div class="glass-card p-4 rounded-2xl">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                             :style="'background:' + (u.color || '#009dde')"
                             x-text="(u.first_name || '?').charAt(0)"></div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-white truncate" x-text="u.first_name + ' ' + u.last_name"></div>
                            <div class="text-xs text-white/40" x-text="u.open_tasks + ' offene Aufgaben'"></div>
                        </div>
                        <div class="text-xs font-mono font-bold"
                             :class="{
                                'text-red-400': u.workload_pct >= 100,
                                'text-yellow-400': u.workload_pct >= 70,
                                'text-green-400': u.workload_pct < 70
                             }"
                             x-text="Math.round(u.workload_pct || 0) + '%'"></div>
                    </div>
                    <!-- Progress bar -->
                    <div class="h-1.5 bg-white/8 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all"
                             :style="'width:' + Math.min(u.workload_pct || 0, 100) + '%; background:' + (u.workload_pct >= 100 ? '#ef4444' : u.workload_pct >= 70 ? '#eab308' : '#22c55e')"></div>
                    </div>
                    <div class="text-xs text-white/30 mt-1" x-text="(u.weekly_hours || 40) + 'h / Woche'"></div>
                </div>
            </template>
        </div>
    </div>

    <!-- ===== AUDIT LOG ===== -->
    <div x-show="activeTab === 'audit'">
        <div class="flex justify-end mb-4">
            <button @click="loadAudit()" class="btn-ghost text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Aktualisieren
            </button>
        </div>
        <div class="glass-card rounded-2xl overflow-hidden">
            <template x-for="log in auditLogs" :key="log.id">
                <div class="px-4 py-3 flex items-start gap-4 border-b border-white/5 last:border-0 hover:bg-white/3 transition-colors">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 text-white"
                         style="background: rgba(0,157,222,0.3);"
                         x-text="(log.user_name || '?').charAt(0).toUpperCase()"></div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline gap-2">
                            <span class="text-sm text-white font-medium" x-text="log.user_name || 'System'"></span>
                            <span class="text-xs px-1.5 py-0.5 rounded bg-white/8 text-white/50" x-text="log.action"></span>
                            <span class="text-xs text-white/30" x-text="(log.entity_type || '') + (log.entity_id ? ' #' + log.entity_id.slice(0,8) : '')"></span>
                        </div>
                        <template x-if="log.details">
                            <div class="text-xs text-white/30 mt-0.5 truncate" x-text="typeof log.details === 'string' ? log.details : JSON.stringify(log.details).slice(0,100)"></div>
                        </template>
                    </div>
                    <div class="text-xs text-white/25 whitespace-nowrap flex-shrink-0" x-text="formatDate(log.created_at)"></div>
                </div>
            </template>
            <template x-if="auditLogs.length === 0">
                <div class="py-10 text-center text-white/30 text-sm">Keine Einträge</div>
            </template>
        </div>
    </div>

    <!-- ===== INVITE MODAL ===== -->
    <div x-show="inviteModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);"
         @click.self="inviteModal = false">
        <div class="glass-card rounded-2xl w-full max-w-md shadow-2xl" @click.stop>
            <div class="px-6 py-4 border-b border-white/8">
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
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Benutzername *</label>
                    <input x-model="inviteForm.username" type="text" class="input-field">
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">E-Mail *</label>
                    <input x-model="inviteForm.email" type="email" class="input-field">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Rolle</label>
                        <select x-model="inviteForm.role" class="input-field">
                            <option value="EMPLOYEE">Mitarbeiter</option>
                            <option value="MANAGER">Manager</option>
                            <option value="CHEF">Chef / Admin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Position</label>
                        <input x-model="inviteForm.position" type="text" class="input-field" placeholder="z.B. Designer">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Wochenstunden</label>
                        <input x-model="inviteForm.weekly_hours" type="number" class="input-field" placeholder="40">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Urlaubstage</label>
                        <input x-model="inviteForm.vacation_days" type="number" class="input-field" placeholder="28">
                    </div>
                </div>
                <template x-if="inviteResult">
                    <div class="bg-green-900/20 border border-green-800/50 rounded-xl p-3 text-xs text-green-400">
                        ✅ Benutzer erstellt! Login: <strong x-text="inviteResult.username"></strong> / Passwort per E-Mail gesendet.
                    </div>
                </template>
            </div>
            <div class="px-6 py-4 border-t border-white/8 flex gap-3">
                <button @click="sendInvite()" class="btn-primary">Erstellen & Einladen</button>
                <button @click="inviteModal = false; inviteResult = null" class="btn-ghost">Schließen</button>
            </div>
        </div>
    </div>

    <!-- ===== EDIT USER MODAL ===== -->
    <div x-show="editUserModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);"
         @click.self="editUserModal = false">
        <div class="glass-card rounded-2xl w-full max-w-md shadow-2xl" @click.stop>
            <div class="px-6 py-4 border-b border-white/8">
                <h2 class="font-heading font-semibold text-white">Benutzer bearbeiten</h2>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Vorname</label>
                        <input x-model="editUserForm.first_name" type="text" class="input-field">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Nachname</label>
                        <input x-model="editUserForm.last_name" type="text" class="input-field">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">E-Mail</label>
                    <input x-model="editUserForm.email" type="email" class="input-field">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Rolle</label>
                        <select x-model="editUserForm.role" class="input-field">
                            <option value="EMPLOYEE">Mitarbeiter</option>
                            <option value="MANAGER">Manager</option>
                            <option value="CHEF">Chef / Admin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Position</label>
                        <input x-model="editUserForm.position" type="text" class="input-field">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Wochenstunden</label>
                        <input x-model="editUserForm.weekly_hours" type="number" class="input-field">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Urlaubstage</label>
                        <input x-model="editUserForm.vacation_days" type="number" class="input-field">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Farbe</label>
                    <input x-model="editUserForm.color" type="color" class="w-full h-10 rounded-xl border border-white/10 bg-transparent p-1">
                </div>
            </div>
            <div class="px-6 py-4 border-t border-white/8 flex gap-3">
                <button @click="saveUserEdit()" class="btn-primary">Speichern</button>
                <button @click="editUserModal = false" class="btn-ghost">Abbrechen</button>
            </div>
        </div>
    </div>

    <!-- ===== STATUS MODAL ===== -->
    <div x-show="statusModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);"
         @click.self="statusModal = false">
        <div class="glass-card rounded-2xl w-full max-w-sm shadow-2xl" @click.stop>
            <div class="px-6 py-4 border-b border-white/8">
                <h2 class="font-heading font-semibold text-white" x-text="statusForm.id ? 'Status bearbeiten' : 'Neuer Task-Status'"></h2>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-white/40 mb-1.5">Name</label>
                    <input x-model="statusForm.name" type="text" class="input-field" placeholder="z.B. In Arbeit">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Farbe</label>
                        <input x-model="statusForm.color" type="color"
                               class="w-full h-10 bg-white/8 border border-white/10 rounded-xl p-1">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Emoji</label>
                        <input x-model="statusForm.emoji" type="text" class="input-field" placeholder="📋">
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <input x-model="statusForm.is_done" type="checkbox" id="isDone" class="w-4 h-4 accent-[#009dde]">
                    <label for="isDone" class="text-sm text-white/70">Gilt als "Fertig"</label>
                </div>
                <div class="flex items-center gap-3">
                    <input x-model="statusForm.is_default" type="checkbox" id="isDefault" class="w-4 h-4 accent-[#009dde]">
                    <label for="isDefault" class="text-sm text-white/70">Standard-Status (für neue Aufgaben)</label>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-white/8 flex gap-3">
                <button @click="saveStatus()" class="btn-primary" x-text="statusForm.id ? 'Speichern' : 'Erstellen'"></button>
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
            { id: 'settings', label: 'Einstellungen', icon: '⚙️' },
            { id: 'users',    label: 'Benutzer',      icon: '👥' },
            { id: 'statuses', label: 'Task-Status',   icon: '📊' },
            { id: 'workload', label: 'Workload',      icon: '📈' },
            { id: 'audit',    label: 'Audit Log',     icon: '🔍' },
        ],
        settings: { activity_types: [] },
        newActivityType: '',
        users: [],
        statuses: [],
        auditLogs: [],
        workloadData: [],
        inviteModal: false,
        inviteForm: { first_name: '', last_name: '', username: '', email: '', role: 'EMPLOYEE', position: '', weekly_hours: 40, vacation_days: 28 },
        inviteResult: null,
        editUserModal: false,
        editUserForm: {},
        editUserId: null,
        statusModal: false,
        statusForm: {},

        async init() {
            await Promise.all([this.loadSettings(), this.loadUsers(), this.loadStatuses(), this.loadAudit(), this.loadWorkload()]);
        },

        async loadSettings() {
            const r = await fetch('/api/admin/settings');
            if (r.ok) {
                const d = await r.json();
                this.settings = d || {};
                if (!this.settings.activity_types) this.settings.activity_types = [];
            }
        },

        async saveSettings() {
            const r = await fetch('/api/admin/settings', {
                method: 'PUT', headers: {'Content-Type':'application/json'},
                body: JSON.stringify(this.settings)
            });
            if (r.ok) alert('Einstellungen gespeichert');
            else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        addActivityType() {
            if (this.newActivityType.trim() && !this.settings.activity_types.includes(this.newActivityType.trim())) {
                this.settings.activity_types = [...this.settings.activity_types, this.newActivityType.trim()];
                this.newActivityType = '';
            }
        },

        removeActivityType(idx) {
            this.settings.activity_types = this.settings.activity_types.filter((_, i) => i !== idx);
        },

        async loadUsers() {
            const r = await fetch('/api/users');
            const d = await r.json();
            this.users = Array.isArray(d) ? d : (d.users || []);
        },

        async toggleUser(u) {
            const action = u.is_active ? 'deactivate' : 'activate';
            const r = await fetch('/api/admin/users/' + u.id + '/' + action, { method: 'POST' });
            if (r.ok) u.is_active = !u.is_active;
        },

        editUser(u) {
            this.editUserId = u.id;
            this.editUserForm = {
                first_name: u.first_name || '',
                last_name: u.last_name || '',
                email: u.email || '',
                role: u.role || 'EMPLOYEE',
                position: u.position || '',
                weekly_hours: u.weekly_hours || 40,
                vacation_days: u.vacation_days || 28,
                color: u.color || '#009dde',
            };
            this.editUserModal = true;
        },

        async saveUserEdit() {
            const r = await fetch('/api/users/' + this.editUserId, {
                method: 'PUT', headers: {'Content-Type':'application/json'},
                body: JSON.stringify(this.editUserForm)
            });
            if (r.ok) {
                this.editUserModal = false;
                await this.loadUsers();
            } else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        async sendInvite() {
            const r = await fetch('/api/users', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({
                    ...this.inviteForm,
                    password: Math.random().toString(36).slice(2, 10)
                })
            });
            if (r.ok) {
                this.inviteResult = await r.json();
                await this.loadUsers();
            } else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        async loadStatuses() {
            const r = await fetch('/api/tasks/statuses');
            const d = await r.json();
            this.statuses = Array.isArray(d) ? d : [];
        },

        openStatusCreate() {
            this.statusForm = { name: '', color: '#6366f1', emoji: '', is_done: false, is_default: false };
            this.statusModal = true;
        },

        editStatus(s) {
            this.statusForm = { ...s };
            this.statusModal = true;
        },

        async saveStatus() {
            const isEdit = !!this.statusForm.id;
            const url = isEdit ? '/api/admin/task-statuses/' + this.statusForm.id : '/api/admin/task-statuses';
            const r = await fetch(url, {
                method: isEdit ? 'PUT' : 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify(this.statusForm)
            });
            if (r.ok) {
                this.statusModal = false;
                await this.loadStatuses();
            } else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        async deleteStatus(id) {
            if (!confirm('Status löschen? Aufgaben mit diesem Status werden auf keinen Status gesetzt.')) return;
            await fetch('/api/admin/task-statuses/' + id, { method: 'DELETE' });
            this.statuses = this.statuses.filter(s => s.id !== id);
        },

        async loadWorkload() {
            const r = await fetch('/api/team/workload-overview');
            if (r.ok) {
                const d = await r.json();
                // Calculate workload percentage from open_tasks and weekly_hours
                this.workloadData = (Array.isArray(d) ? d : []).map(u => ({
                    ...u,
                    workload_pct: u.weekly_hours > 0 ? Math.min((u.open_tasks * 2 / u.weekly_hours) * 100, 150) : 0
                }));
            }
        },

        async loadAudit() {
            const r = await fetch('/api/admin/audit-logs?limit=200');
            const d = await r.json();
            this.auditLogs = Array.isArray(d) ? d : [];
        },

        formatDate(d) {
            if (!d) return '';
            return new Date(d).toLocaleString('de-DE', {day:'2-digit',month:'2-digit',year:'2-digit',hour:'2-digit',minute:'2-digit'});
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

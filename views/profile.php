<?php
$pageTitle = 'Mein Profil';
require __DIR__ . '/_layout.php';
?>

<div x-data="profileApp()" x-init="init()">

    <div class="max-w-2xl mx-auto space-y-6">

        <!-- Avatar & Name -->
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            <div class="flex items-start gap-6">
                <div class="relative">
                    <template x-if="user && user.profile_image">
                        <img :src="user.profile_image" class="w-20 h-20 rounded-2xl object-cover" alt="">
                    </template>
                    <template x-if="!user || !user.profile_image">
                        <div class="w-20 h-20 rounded-2xl bg-brand-500 flex items-center justify-center text-white text-2xl font-bold">
                            <?= strtoupper(substr($currentUser['first_name'] ?? '', 0, 1)) . strtoupper(substr($currentUser['last_name'] ?? '', 0, 1)) ?>
                        </div>
                    </template>
                    <label class="absolute -bottom-2 -right-2 w-7 h-7 bg-gray-800 border border-gray-700 rounded-full flex items-center justify-center cursor-pointer hover:bg-gray-700 transition-colors">
                        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <input type="file" accept="image/*" class="hidden" @change="uploadAvatar($event)">
                    </label>
                </div>
                <div class="flex-1">
                    <div class="text-xl font-bold text-white" x-text="(user?.first_name || '') + ' ' + (user?.last_name || '')"></div>
                    <div class="text-sm text-gray-400 mt-0.5" x-text="user?.email || ''"></div>
                    <span class="inline-block mt-2 text-xs px-2.5 py-1 rounded-full bg-brand-500/10 text-brand-400"
                          x-text="user?.role || ''"></span>
                </div>
            </div>
        </div>

        <!-- Personal Info -->
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            <h3 class="text-sm font-semibold text-white mb-4">Persönliche Informationen</h3>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Vorname</label>
                        <input x-model="form.first_name" type="text"
                               class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Nachname</label>
                        <input x-model="form.last_name" type="text"
                               class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">E-Mail</label>
                    <input x-model="form.email" type="email"
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Telefon</label>
                        <input x-model="form.phone" type="tel"
                               class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Geburtstag</label>
                        <input x-model="form.birthday" type="date"
                               class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Position / Jobtitel</label>
                    <input x-model="form.job_title" type="text"
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <button @click="saveProfile()" x-show="saved !== null"
                        class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors">
                    <span x-text="saved === true ? '✓ Gespeichert' : 'Speichern'"></span>
                </button>
                <button @click="saveProfile()" x-show="saved === null"
                        class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors">
                    Speichern
                </button>
            </div>
        </div>

        <!-- Change Password -->
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            <h3 class="text-sm font-semibold text-white mb-4">Passwort ändern</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Aktuelles Passwort</label>
                    <input x-model="pwForm.old_password" type="password"
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Neues Passwort</label>
                    <input x-model="pwForm.new_password" type="password"
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <template x-if="pwError">
                    <p class="text-sm text-red-400" x-text="pwError"></p>
                </template>
                <template x-if="pwSuccess">
                    <p class="text-sm text-green-400">Passwort erfolgreich geändert</p>
                </template>
                <button @click="changePassword()"
                        class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors">
                    Passwort ändern
                </button>
            </div>
        </div>

        <!-- Sessions -->
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            <h3 class="text-sm font-semibold text-white mb-4">Aktive Sessions</h3>
            <div class="space-y-2">
                <template x-for="s in sessions" :key="s.token">
                    <div class="flex items-center gap-3 py-2 border-b border-gray-800 last:border-0">
                        <div class="flex-1 text-xs text-gray-400">
                            <div class="font-mono" x-text="s.token"></div>
                            <div class="mt-0.5" x-text="(s.ip_address || '') + ' · ' + formatDate(s.created_at)"></div>
                        </div>
                        <div class="text-xs text-gray-500" x-text="'Läuft ab: ' + formatDate(s.expires_at)"></div>
                    </div>
                </template>
            </div>
            <button @click="logoutAll()"
                    class="mt-4 text-xs text-red-400 hover:text-red-300 bg-red-900/20 hover:bg-red-900/30 px-3 py-2 rounded-xl transition-colors">
                Alle anderen Sessions beenden
            </button>
        </div>
    </div>
</div>

<script>
function profileApp() {
    return {
        user: null,
        form: {},
        pwForm: { old_password: '', new_password: '' },
        sessions: [],
        saved: null,
        pwError: '',
        pwSuccess: false,

        async init() {
            const r = await fetch('/api/auth/me');
            this.user = await r.json();
            this.form = {
                first_name: this.user.first_name || '',
                last_name:  this.user.last_name  || '',
                email:      this.user.email      || '',
                phone:      this.user.phone      || '',
                birthday:   (this.user.birthday  || '').split('T')[0],
                job_title:  this.user.job_title  || '',
            };
            const sr = await fetch('/api/auth/sessions');
            this.sessions = await sr.json();
        },

        async saveProfile() {
            const r = await fetch('/api/users/' + this.user.id, {
                method: 'PUT',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify(this.form)
            });
            if (r.ok) {
                this.user = await r.json();
                this.saved = true;
                setTimeout(() => this.saved = null, 2000);
            } else {
                const e = await r.json();
                alert(e.detail || 'Fehler');
            }
        },

        async changePassword() {
            this.pwError = '';
            this.pwSuccess = false;
            const r = await fetch('/api/auth/password', {
                method: 'PUT',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify(this.pwForm)
            });
            if (r.ok) {
                this.pwSuccess = true;
                this.pwForm = { old_password: '', new_password: '' };
            } else {
                const e = await r.json();
                this.pwError = e.detail || 'Fehler';
            }
        },

        async uploadAvatar(ev) {
            const file = ev.target.files[0];
            if (!file) return;
            const fd = new FormData();
            fd.append('avatar', file);
            const r = await fetch('/api/users/' + this.user.id + '/avatar', { method: 'POST', body: fd });
            if (r.ok) { const d = await r.json(); this.user.profile_image = d.profile_image; }
        },

        async logoutAll() {
            await fetch('/api/auth/logout-all', { method: 'POST' });
            window.location.href = '/logout';
        },

        formatDate(d) {
            if (!d) return '';
            return new Date(d).toLocaleDateString('de-DE', {day:'2-digit',month:'2-digit',year:'numeric'});
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

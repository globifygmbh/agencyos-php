<?php $pageTitle = 'Dashboard'; ?>
<?php require __DIR__ . '/_layout.php'; ?>

<div x-data="dashboardApp()" x-init="init()" class="max-w-7xl mx-auto space-y-5">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h1 class="font-heading text-2xl md:text-3xl font-semibold tracking-tight text-white" x-text="greeting + ', <?= htmlspecialchars($currentUser['first_name'] ?? 'User') ?>!'">Hallo!</h1>
            <p class="text-white/40 text-sm mt-0.5" x-text="currentDateStr">Lädt...</p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="configMode = !configMode"
                    :class="configMode ? 'bg-primary-500/20 text-primary-400' : 'text-white/50 hover:text-white hover:bg-white/8'"
                    class="p-2 rounded-xl transition-colors" title="Dashboard anpassen">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <circle cx="12" cy="12" r="3" stroke-width="2"/>
                </svg>
            </button>
            <div class="font-mono text-2xl font-semibold text-white tracking-tight" x-text="clockStr">00:00:00</div>
        </div>
    </div>

    <!-- Widget Config Panel -->
    <div x-show="configMode" x-cloak class="glass-card p-4">
        <p class="text-sm font-medium text-white/70 mb-3">Widgets ein-/ausblenden:</p>
        <div class="flex flex-wrap gap-2">
            <template x-for="w in widgetList" :key="w.key">
                <button @click="toggleWidget(w.key)"
                        :class="widgets[w.key] ? 'bg-primary-500/20 text-primary-400 border-primary-500/30' : 'bg-white/5 text-white/40 border-white/10'"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium border flex items-center gap-1.5 transition-colors">
                    <span x-text="widgets[w.key] ? '👁' : '🙈'" class="text-xs"></span>
                    <span x-text="w.label"></span>
                </button>
            </template>
        </div>
    </div>

    <!-- Bento Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">

        <!-- Timer Widget -->
        <div x-show="widgets.showTimer" class="glass-card p-4">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-4 h-4 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-semibold text-white font-heading">Zeiterfassung</span>
            </div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-mono text-2xl font-semibold text-white" x-text="timerStr">00:00:00</p>
                    <p class="text-xs text-white/40 mt-0.5" x-text="activeTimer ? 'Zeit läuft...' : 'Bereit zum Starten'"></p>
                </div>
                <button @click="activeTimer ? showStopModal = true : startTimer()"
                        :class="activeTimer ? 'bg-red-500/20 text-red-400 hover:bg-red-500/30' : 'bg-primary-500/20 text-primary-400 hover:bg-primary-500/30'"
                        class="w-12 h-12 rounded-full flex items-center justify-center transition-colors">
                    <svg x-show="!activeTimer" class="w-5 h-5 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    <svg x-show="activeTimer" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
                </button>
            </div>
            <div class="mt-3 pt-3 border-t border-white/8">
                <div class="flex justify-between text-sm">
                    <span class="text-white/40">Heute</span>
                    <span class="font-medium text-white" x-text="(dashboard.today_hours || 0) + 'h'">0h</span>
                </div>
            </div>
        </div>

        <!-- Week Stats Widget -->
        <div x-show="widgets.showStats" class="glass-card p-4">
            <div class="flex items-center gap-2 mb-3">
                <span class="text-base">📊</span>
                <span class="text-sm font-semibold text-white font-heading">Wochenübersicht</span>
            </div>
            <div class="space-y-3">
                <div>
                    <div class="flex justify-between text-sm mb-1.5">
                        <span class="text-white/40">Wochenstunden</span>
                        <span class="font-medium text-white">
                            <span x-text="dashboard.week_hours || 0"></span>h /
                            <span x-text="dashboard.week_target || 40"></span>h
                        </span>
                    </div>
                    <div class="w-full h-2 bg-white/10 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500"
                             style="background: linear-gradient(90deg, #009dde, #00c4ff);"
                             :style="'width: ' + Math.min(((dashboard.week_hours || 0) / (dashboard.week_target || 40)) * 100, 100) + '%'"></div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2.5 rounded-xl bg-white/5">
                        <p class="text-xl font-semibold text-white" x-text="dashboard.remaining_vacation_days || 0">0</p>
                        <p class="text-xs text-white/40 mt-0.5">🌴 Resturlaub</p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-white/5">
                        <p class="text-xl font-semibold text-white" x-text="(dashboard.tasks || []).length">0</p>
                        <p class="text-xs text-white/40 mt-0.5">📋 Offene Tasks</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Weather Widget -->
        <div x-show="widgets.showWeather" class="glass-card p-4" style="background: linear-gradient(135deg, rgba(0,157,222,0.12), rgba(0,157,222,0.04));">
            <template x-if="weather">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-semibold text-white" x-text="Math.round(weather.temp) + '°'">--°</p>
                        <p class="text-sm text-white/50 capitalize mt-0.5" x-text="weather.description">Lädt...</p>
                        <p class="text-xs text-white/30 mt-1">📍 <span x-text="weather.location">Wien</span></p>
                    </div>
                    <div class="w-14 h-14 rounded-full flex items-center justify-center text-3xl"
                         style="background: rgba(0,157,222,0.2);">
                        <span x-text="weatherEmoji(weather.icon)">🌤️</span>
                    </div>
                </div>
            </template>
            <template x-if="!weather">
                <div class="flex items-center justify-center py-6">
                    <div class="text-center text-white/30">
                        <div class="text-3xl mb-1">🌤️</div>
                        <p class="text-sm">Wetter lädt...</p>
                    </div>
                </div>
            </template>
        </div>

        <!-- Challenge Points Widget -->
        <a href="/achievements" class="glass-card p-4 block hover:opacity-90 transition-opacity" style="background: linear-gradient(135deg, rgba(245,158,11,0.12), rgba(245,158,11,0.04));">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-3xl font-semibold text-white" x-text="dashboard.challenge_points || 0">0</p>
                    <p class="text-sm text-white/50 mt-0.5">Challenge-Punkte</p>
                    <p class="text-xs text-amber-400 mt-1 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        Weiter sammeln!
                    </p>
                </div>
                <div class="w-14 h-14 rounded-full flex items-center justify-center" style="background: rgba(245,158,11,0.2);">
                    <svg class="w-7 h-7 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                </div>
            </div>
        </a>

        <!-- Tasks Widget (spans 2 cols) -->
        <div x-show="widgets.showTasks" class="glass-card p-4 lg:col-span-2">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <span class="text-sm font-semibold text-white font-heading">Meine Tasks</span>
                </div>
                <a href="/tasks" class="text-xs text-primary-400 hover:text-primary-300 flex items-center gap-1 transition-colors">
                    Alle anzeigen
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
            <template x-if="(dashboard.tasks || []).length > 0">
                <div class="space-y-2">
                    <template x-for="task in (dashboard.tasks || []).slice(0,4)" :key="task.id">
                        <a :href="'/tasks?task=' + task.id"
                           class="flex items-center justify-between p-2.5 rounded-xl bg-white/4 hover:bg-white/8 transition-colors">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span x-text="statusEmoji(task.status)" class="text-base flex-shrink-0"></span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-white truncate" x-text="task.title"></p>
                                    <p x-show="task.deadline" class="text-xs text-white/40" x-text="formatDate(task.deadline)"></p>
                                </div>
                            </div>
                            <span :class="statusClass(task.status)" class="status-badge ml-2 flex-shrink-0" x-text="statusLabel(task.status)"></span>
                        </a>
                    </template>
                </div>
            </template>
            <template x-if="(dashboard.tasks || []).length === 0">
                <div class="flex flex-col items-center justify-center py-8 text-white/30">
                    <svg class="w-10 h-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <p class="text-sm">Keine offenen Tasks</p>
                    <a href="/tasks" class="mt-2 btn-ghost text-xs py-1.5 px-3">+ Task erstellen</a>
                </div>
            </template>
        </div>

        <!-- Team Vacations Widget -->
        <div x-show="widgets.showTeam" class="glass-card p-4">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="4" stroke-width="1.75"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
                </svg>
                <span class="text-sm font-semibold text-white font-heading">Diese Woche abwesend</span>
            </div>
            <template x-if="(dashboard.vacations_this_week || []).length > 0">
                <div class="space-y-2">
                    <template x-for="v in (dashboard.vacations_this_week || []).slice(0,3)" :key="v.id">
                        <div class="flex items-center gap-2 p-2 rounded-xl" style="background: rgba(52,199,89,0.08);">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-emerald-400 flex-shrink-0"
                                 style="background: rgba(52,199,89,0.15);"
                                 x-text="(v.user_name || '?').charAt(0).toUpperCase()"></div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-white" x-text="v.user_name"></p>
                                <p class="text-xs text-white/40" x-text="formatDate(v.start_date) + ' – ' + formatDate(v.end_date)"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
            <template x-if="(dashboard.vacations_this_week || []).length === 0">
                <p class="text-white/30 text-sm text-center py-4">👍 Alle da diese Woche!</p>
            </template>
        </div>

        <!-- Calendar Events Widget -->
        <div x-show="widgets.showCalendar" class="glass-card p-4" style="background: linear-gradient(135deg, rgba(255,59,48,0.08), rgba(255,59,48,0.02));">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-sm font-semibold text-white font-heading">Kommende Termine</span>
                </div>
                <a href="/calendar" class="text-xs text-red-400 hover:text-red-300 flex items-center gap-1 transition-colors">
                    Kalender <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
            <template x-if="(dashboard.upcoming_events || []).length > 0">
                <div class="space-y-2">
                    <template x-for="evt in (dashboard.upcoming_events || []).slice(0,3)" :key="evt.id">
                        <a href="/calendar" class="flex items-center gap-2.5 p-2.5 rounded-xl bg-white/4 hover:bg-white/8 transition-colors">
                            <div class="w-1.5 h-10 rounded-full flex-shrink-0" :style="'background-color: ' + (evt.color || '#34c759')"></div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-white truncate" x-text="evt.title"></p>
                                <p class="text-xs text-white/40" x-text="formatDateTime(evt.start)"></p>
                                <p x-show="evt.location" class="text-xs text-white/30" x-text="'📍 ' + evt.location"></p>
                            </div>
                        </a>
                    </template>
                </div>
            </template>
            <template x-if="(dashboard.upcoming_events || []).length === 0">
                <div class="flex flex-col items-center py-5 text-white/30">
                    <svg class="w-8 h-8 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-sm">Keine Termine diese Woche</p>
                    <a href="/calendar" class="mt-2 btn-ghost text-xs py-1.5 px-3">+ Termin erstellen</a>
                </div>
            </template>
        </div>

    </div>

    <!-- Stop Timer Modal -->
    <div x-show="showStopModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);">
        <div class="glass-card w-full max-w-md" @click.outside="showStopModal = false">
            <div class="p-6 border-b border-white/8" style="background: linear-gradient(135deg, rgba(0,157,222,0.12), transparent);">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl" style="background: rgba(0,157,222,0.2);">⏱️</div>
                    <div>
                        <p class="font-heading font-semibold text-white">Zeit stoppen</p>
                        <p class="font-mono text-2xl font-bold" style="color: #009dde;" x-text="timerStr">00:00:00</p>
                    </div>
                </div>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <button @click="stopTimer(true)"
                            class="flex flex-col items-center gap-2 p-4 rounded-2xl transition-all hover:scale-[1.02] active:scale-[0.98]"
                            style="background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.2);">
                        <span class="text-3xl">☕</span>
                        <span class="text-sm font-medium text-amber-400">Pause</span>
                    </button>
                    <button @click="stopTimer(false)"
                            class="flex flex-col items-center gap-2 p-4 rounded-2xl transition-all hover:scale-[1.02] active:scale-[0.98]"
                            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
                        <span class="text-3xl">📋</span>
                        <span class="text-sm font-medium text-white/60">Allgemein</span>
                    </button>
                </div>
                <div class="flex items-center gap-2 text-white/20 my-2">
                    <div class="flex-1 h-px bg-white/10"></div>
                    <span class="text-xs uppercase tracking-wider">oder buchen auf</span>
                    <div class="flex-1 h-px bg-white/10"></div>
                </div>
                <div>
                    <label class="text-xs text-white/50 mb-1.5 block font-medium">🏢 Kunde</label>
                    <select x-model="stopForm.customer_id" class="input-field">
                        <option value="">Kunde auswählen...</option>
                        <template x-for="c in customers" :key="c.id">
                            <option :value="c.id" x-text="c.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-white/50 mb-1.5 block font-medium">🎯 Aktivität</label>
                    <select x-model="stopForm.activity_type" class="input-field">
                        <option value="">Was hast du gemacht?</option>
                        <option value="Grafik">🎨 Grafik</option>
                        <option value="Videoschnitt">🎬 Videoschnitt</option>
                        <option value="Reporting">📊 Reporting</option>
                        <option value="Entwicklung">💻 Entwicklung</option>
                        <option value="Meeting">👥 Meeting</option>
                        <option value="Planung">📅 Planung</option>
                        <option value="Support">🛟 Support</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-white/50 mb-1.5 block font-medium">💬 Notiz (optional)</label>
                    <textarea x-model="stopForm.description" class="input-field resize-none" rows="2" placeholder="Kurze Beschreibung..."></textarea>
                </div>
            </div>
            <div class="flex gap-3 p-6 pt-0">
                <button @click="cancelTimer()" class="flex-1 py-3 rounded-xl text-red-400 hover:bg-red-500/10 transition-colors text-sm font-medium flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Verwerfen
                </button>
                <button @click="stopTimer(false)" class="flex-1 py-3 rounded-xl font-semibold text-white transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2" style="background: #009dde;">
                    ✨ Zeit speichern
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function dashboardApp() {
    return {
        dashboard: { tasks: [], vacations_this_week: [], upcoming_events: [] },
        weather: null,
        activeTimer: null,
        timerSeconds: 0,
        timerInterval: null,
        clockStr: '00:00:00',
        timerStr: '00:00:00',
        showStopModal: false,
        customers: [],
        configMode: false,
        widgets: { showTimer: true, showStats: true, showTasks: true, showWeather: true, showCalendar: true, showTeam: true },
        widgetList: [
            { key: 'showTimer', label: 'Timer' }, { key: 'showStats', label: 'Statistiken' },
            { key: 'showTasks', label: 'Tasks' }, { key: 'showWeather', label: 'Wetter' },
            { key: 'showCalendar', label: 'Kalender' }, { key: 'showTeam', label: 'Team' },
        ],
        stopForm: { customer_id: '', activity_type: '', description: '' },
        greeting: 'Hallo',
        currentDateStr: '',

        init() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);
            this.loadDashboard();
            this.loadWeather();
            this.loadTimerState();
            this.loadCustomers();
        },

        updateClock() {
            const now = new Date();
            const h = now.getHours();
            this.clockStr = now.toTimeString().slice(0, 8);
            const days = ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'];
            const months = ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'];
            this.currentDateStr = days[now.getDay()] + ', ' + now.getDate() + '. ' + months[now.getMonth()] + ' ' + now.getFullYear();
            if (h >= 5 && h < 12) this.greeting = '🌅 Guten Morgen';
            else if (h >= 12 && h < 18) this.greeting = '☀️ Guten Tag';
            else this.greeting = '🌙 Guten Abend';
        },

        loadDashboard() {
            fetch('/api/dashboard').then(r => r.json()).then(d => { this.dashboard = d || {}; }).catch(() => {});
        },
        loadWeather() {
            fetch('/api/weather').then(r => r.json()).then(d => { this.weather = d && d.temp !== undefined ? d : null; }).catch(() => {});
        },
        loadTimerState() {
            fetch('/api/time/active').then(r => r.json()).then(d => {
                if (d && d.id) {
                    this.activeTimer = d;
                    const start = new Date(d.start_time);
                    this.timerSeconds = Math.floor((Date.now() - start.getTime()) / 1000);
                    this.timerStr = this.formatSecs(this.timerSeconds);
                    this.startTimerTick();
                }
            }).catch(() => {});
        },
        loadCustomers() {
            fetch('/api/customers').then(r => r.json()).then(d => { this.customers = Array.isArray(d) ? d.filter(c => !c.is_archived) : []; }).catch(() => {});
        },
        startTimerTick() {
            if (this.timerInterval) clearInterval(this.timerInterval);
            this.timerInterval = setInterval(() => { this.timerSeconds++; this.timerStr = this.formatSecs(this.timerSeconds); }, 1000);
        },
        startTimer() {
            fetch('/api/time/start', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({}) })
                .then(r => r.json()).then(d => { this.activeTimer = d; this.timerSeconds = 0; this.startTimerTick(); }).catch(() => {});
        },
        stopTimer(isPause = false) {
            if (!this.activeTimer) { this.showStopModal = false; return; }
            const body = { customer_id: this.stopForm.customer_id || null, activity_type: this.stopForm.activity_type || null, description: this.stopForm.description || '', is_pause: isPause };
            fetch('/api/time/' + this.activeTimer.id + '/stop', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
                .then(() => {
                    this.activeTimer = null; this.timerSeconds = 0; this.timerStr = '00:00:00';
                    if (this.timerInterval) clearInterval(this.timerInterval);
                    this.showStopModal = false; this.stopForm = { customer_id: '', activity_type: '', description: '' };
                    this.loadDashboard();
                }).catch(() => {});
        },
        cancelTimer() {
            if (this.activeTimer) {
                fetch('/api/time/' + this.activeTimer.id + '/cancel', { method: 'DELETE' }).then(() => {
                    this.activeTimer = null; this.timerSeconds = 0; this.timerStr = '00:00:00';
                    if (this.timerInterval) clearInterval(this.timerInterval);
                }).catch(() => {});
            }
            this.showStopModal = false;
        },
        toggleWidget(key) {
            this.widgets[key] = !this.widgets[key];
            fetch('/api/users/me/preferences', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ dashboard_widgets: this.widgets }) }).catch(() => {});
        },
        formatSecs(s) {
            return Math.floor(s/3600).toString().padStart(2,'0') + ':' + Math.floor((s%3600)/60).toString().padStart(2,'0') + ':' + (s%60).toString().padStart(2,'0');
        },
        formatDate(d) {
            if (!d) return '';
            const date = new Date(d);
            const months = ['Jan','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'];
            return date.getDate() + '. ' + months[date.getMonth()];
        },
        formatDateTime(d) {
            if (!d) return '';
            const date = new Date(d);
            const days = ['So','Mo','Di','Mi','Do','Fr','Sa'];
            return days[date.getDay()] + ', ' + this.formatDate(d) + ' ' + date.toTimeString().slice(0,5);
        },
        weatherEmoji(icon) {
            const map = { '01d':'☀️','01n':'🌙','02d':'⛅','02n':'⛅','03d':'☁️','03n':'☁️','04d':'☁️','04n':'☁️','09d':'🌧️','09n':'🌧️','10d':'🌦️','10n':'🌧️','11d':'⛈️','11n':'⛈️','13d':'❄️','13n':'❄️','50d':'🌫️','50n':'🌫️' };
            return icon && map[icon] ? map[icon] : '🌤️';
        },
        statusEmoji(s) { return { open:'🟢', in_progress:'🟡', review:'🔵', overdue:'🔴', done:'✅', completed:'✅' }[s] || '📋'; },
        statusLabel(s) { return { open:'Offen', in_progress:'In Arbeit', review:'Review', overdue:'Überfällig', done:'Erledigt', completed:'Erledigt' }[s] || s; },
        statusClass(s) { return { open:'badge-open', in_progress:'badge-progress', review:'badge-review', done:'badge-done', completed:'badge-done', overdue:'badge-blocked' }[s] || 'badge-open'; }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

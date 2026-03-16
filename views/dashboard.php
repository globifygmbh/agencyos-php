<?php
$pageTitle = 'Dashboard';
require __DIR__ . '/_layout.php';
?>

<div x-data="dashboard()" x-init="init()">

    <!-- Greeting -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-white">
            Guten <?= (date('H') < 12 ? 'Morgen' : (date('H') < 18 ? 'Tag' : 'Abend')) ?>,
            <?= htmlspecialchars($currentUser['first_name'] ?? '') ?>!
        </h2>
        <p class="text-gray-400 mt-1"><?= date('l, d. F Y') ?></p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Heute</div>
            <div class="text-2xl font-bold text-white" x-text="formatDuration(data.time_today || 0)">–</div>
            <div class="text-xs text-gray-500 mt-1">Erfasste Zeit</div>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Diese Woche</div>
            <div class="text-2xl font-bold text-white" x-text="formatDuration(data.time_week || 0)">–</div>
            <div class="text-xs text-gray-500 mt-1">Erfasste Zeit</div>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Offene Tasks</div>
            <div class="text-2xl font-bold text-white" x-text="(data.tasks || []).length">–</div>
            <div class="text-xs text-gray-500 mt-1">Mir zugewiesen</div>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Urlaub</div>
            <div class="text-2xl font-bold text-white" x-text="(data.vacations || []).length">–</div>
            <div class="text-xs text-gray-500 mt-1">Diese Woche abwesend</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Tasks -->
        <div class="lg:col-span-2 bg-gray-900 border border-gray-800 rounded-2xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-white">Meine Aufgaben</h3>
                <a href="/tasks" class="text-xs text-brand-400 hover:text-brand-300">Alle anzeigen →</a>
            </div>
            <div x-show="loading" class="text-gray-500 text-sm">Laden…</div>
            <div x-show="!loading">
                <template x-if="(data.tasks || []).length === 0">
                    <p class="text-gray-500 text-sm">Keine offenen Aufgaben 🎉</p>
                </template>
                <template x-for="task in (data.tasks || [])" :key="task.id">
                    <div class="flex items-start gap-3 py-3 border-b border-gray-800 last:border-0">
                        <div class="w-2.5 h-2.5 rounded-full mt-1.5 flex-shrink-0"
                             :style="'background:' + (task.status_color || '#6b7280')"></div>
                        <div class="flex-1 min-w-0">
                            <a :href="'/tasks'" class="text-sm text-white hover:text-brand-400 font-medium truncate block"
                               x-text="task.title"></a>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs text-gray-500" x-text="task.status_name || '–'"></span>
                                <template x-if="task.deadline">
                                    <span class="text-xs text-gray-500" x-text="'Fällig: ' + formatDate(task.deadline)"></span>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Right column -->
        <div class="space-y-4">
            <!-- Calendar Events -->
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-white">Bevorstehende Events</h3>
                    <a href="/calendar" class="text-xs text-brand-400 hover:text-brand-300">Kalender →</a>
                </div>
                <div x-show="!loading">
                    <template x-if="(data.calendar_events || []).length === 0">
                        <p class="text-gray-500 text-sm">Keine Events diese Woche</p>
                    </template>
                    <template x-for="ev in (data.calendar_events || []).slice(0,4)" :key="ev.id">
                        <div class="flex items-start gap-3 py-2.5 border-b border-gray-800 last:border-0">
                            <div class="text-center flex-shrink-0 w-8">
                                <div class="text-xs text-gray-500" x-text="formatDateShort(ev.start_date).split(' ')[0]"></div>
                                <div class="text-sm font-bold text-white" x-text="formatDateShort(ev.start_date).split(' ')[1]"></div>
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm text-white truncate" x-text="ev.title"></div>
                                <div class="text-xs text-gray-500" x-text="ev.all_day ? 'Ganztägig' : formatTime(ev.start_date)"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Notes -->
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-white">Notizen</h3>
                </div>
                <div x-show="!loading">
                    <template x-if="(data.notes || []).length === 0">
                        <p class="text-gray-500 text-sm">Keine Notizen vorhanden</p>
                    </template>
                    <template x-for="note in (data.notes || [])" :key="note.id">
                        <div class="py-2.5 border-b border-gray-800 last:border-0">
                            <div class="text-sm text-white font-medium truncate" x-text="note.title"></div>
                            <div class="text-xs text-gray-500 mt-0.5 line-clamp-2" x-text="note.content"></div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function dashboard() {
    return {
        data: {},
        loading: true,
        async init() {
            try {
                const r = await fetch('/api/dashboard');
                this.data = await r.json();
            } catch(e) { console.error(e); }
            this.loading = false;
        },
        formatDuration(secs) {
            const h = Math.floor(secs / 3600);
            const m = Math.floor((secs % 3600) / 60);
            return h + 'h ' + String(m).padStart(2,'0') + 'm';
        },
        formatDate(d) {
            if (!d) return '';
            return new Date(d).toLocaleDateString('de-DE', {day:'2-digit',month:'2-digit',year:'numeric'});
        },
        formatDateShort(d) {
            if (!d) return '';
            const date = new Date(d);
            return date.toLocaleDateString('de-DE', {month:'short'}) + ' ' + date.getDate();
        },
        formatTime(d) {
            if (!d) return '';
            return new Date(d).toLocaleTimeString('de-DE', {hour:'2-digit',minute:'2-digit'});
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

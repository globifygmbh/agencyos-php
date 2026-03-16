<?php
$pageTitle = 'Kalender';
require __DIR__ . '/_layout.php';
?>

<div x-data="calendarApp()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center gap-4 mb-6">
        <button @click="prevMonth()" class="p-2 text-gray-400 hover:text-white hover:bg-gray-800 rounded-xl transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
        <h2 class="text-xl font-bold text-white min-w-48 text-center" x-text="monthTitle"></h2>
        <button @click="nextMonth()" class="p-2 text-gray-400 hover:text-white hover:bg-gray-800 rounded-xl transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
        <button @click="goToday()" class="ml-2 text-sm text-gray-400 hover:text-white px-3 py-2 bg-gray-800 hover:bg-gray-700 rounded-xl transition-colors">
            Heute
        </button>
        <button @click="openCreate()"
                class="ml-auto bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Neues Event
        </button>
    </div>

    <!-- Calendar Grid -->
    <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden">
        <!-- Weekday headers -->
        <div class="grid grid-cols-7 border-b border-gray-800">
            <template x-for="day in ['Mo','Di','Mi','Do','Fr','Sa','So']" :key="day">
                <div class="py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wide" x-text="day"></div>
            </template>
        </div>

        <!-- Days grid -->
        <div class="grid grid-cols-7">
            <template x-for="(cell, idx) in calendarCells" :key="idx">
                <div class="border-b border-r border-gray-800 min-h-28 p-2"
                     :class="{
                        'bg-gray-800/30': !cell.currentMonth,
                        'bg-brand-500/5': cell.isToday,
                     }"
                     @click="openCreateOnDay(cell.date)">

                    <div class="text-xs mb-1 w-6 h-6 flex items-center justify-center rounded-full font-medium"
                         :class="{
                            'text-gray-600': !cell.currentMonth,
                            'bg-brand-500 text-white': cell.isToday,
                            'text-gray-300': cell.currentMonth && !cell.isToday,
                         }"
                         x-text="cell.day"></div>

                    <template x-for="ev in getEventsForDate(cell.date)" :key="ev.id">
                        <div class="text-xs px-1.5 py-0.5 rounded mb-0.5 truncate cursor-pointer hover:opacity-80 transition-opacity"
                             :style="'background:' + (ev.color || '#6366f1') + '30; color:' + (ev.color || '#818cf8')"
                             @click.stop="openDetail(ev)"
                             x-text="ev.title"></div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <!-- Event Modal (Create/Edit) -->
    <div x-show="modal" x-cloak
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
         @click.self="modal = false">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl w-full max-w-md shadow-2xl" @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-800">
                <h2 class="font-semibold text-white" x-text="isNew ? 'Neues Event' : 'Event bearbeiten'"></h2>
                <button @click="modal = false" class="text-gray-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Titel *</label>
                    <input x-model="form.title" type="text"
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Beschreibung</label>
                    <textarea x-model="form.description" rows="2"
                              class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 resize-none"></textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input x-model="form.all_day" type="checkbox" id="allday" class="rounded">
                    <label for="allday" class="text-sm text-gray-300">Ganztägig</label>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Start</label>
                        <input x-model="form.start_date" :type="form.all_day ? 'date' : 'datetime-local'"
                               class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Ende</label>
                        <input x-model="form.end_date" :type="form.all_day ? 'date' : 'datetime-local'"
                               class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Kategorie</label>
                        <select x-model="form.category"
                                class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="">–</option>
                            <option value="meeting">Meeting</option>
                            <option value="deadline">Deadline</option>
                            <option value="vacation">Urlaub</option>
                            <option value="birthday">Geburtstag</option>
                            <option value="other">Sonstiges</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1.5">Farbe</label>
                        <input x-model="form.color" type="color"
                               class="w-full h-10 bg-gray-800 border border-gray-700 rounded-xl px-2 py-1">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-400 mb-1.5">Ort</label>
                    <input x-model="form.location" type="text"
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-800 flex items-center gap-3">
                <button @click="saveEvent()"
                        class="bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium px-4 py-2 rounded-xl">
                    <span x-text="isNew ? 'Erstellen' : 'Speichern'"></span>
                </button>
                <template x-if="!isNew && currentEvent">
                    <button @click="deleteEvent()"
                            class="bg-red-900/40 hover:bg-red-900/60 text-red-400 text-sm px-4 py-2 rounded-xl">
                        Löschen
                    </button>
                </template>
                <button @click="modal = false" class="text-gray-400 text-sm ml-auto">Abbrechen</button>
            </div>
        </div>
    </div>
</div>

<script>
function calendarApp() {
    return {
        events: [],
        year: new Date().getFullYear(),
        month: new Date().getMonth(),
        modal: false,
        isNew: true,
        currentEvent: null,
        form: {},

        get monthTitle() {
            return new Date(this.year, this.month, 1)
                .toLocaleDateString('de-DE', {month:'long', year:'numeric'});
        },

        get calendarCells() {
            const first = new Date(this.year, this.month, 1);
            const last  = new Date(this.year, this.month + 1, 0);
            const today = new Date().toISOString().split('T')[0];

            // Start from Monday
            let startDow = first.getDay() || 7; // 1=Mon..7=Sun
            const cells = [];

            // Previous month days
            for (let i = startDow - 1; i > 0; i--) {
                const d = new Date(this.year, this.month, 1 - i);
                cells.push({ day: d.getDate(), date: d.toISOString().split('T')[0], currentMonth: false, isToday: false });
            }
            // Current month
            for (let d = 1; d <= last.getDate(); d++) {
                const date = new Date(this.year, this.month, d).toISOString().split('T')[0];
                cells.push({ day: d, date, currentMonth: true, isToday: date === today });
            }
            // Fill to 6 rows
            while (cells.length < 42) {
                const d = new Date(this.year, this.month + 1, cells.length - last.getDate() - (startDow - 2));
                cells.push({ day: d.getDate(), date: d.toISOString().split('T')[0], currentMonth: false, isToday: false });
            }
            return cells;
        },

        getEventsForDate(date) {
            return this.events.filter(ev => {
                const start = (ev.start_date || '').split('T')[0];
                const end   = (ev.end_date   || ev.start_date || '').split('T')[0];
                return date >= start && date <= end;
            });
        },

        async init() { await this.loadEvents(); },

        async loadEvents() {
            const from = new Date(this.year, this.month - 1, 1).toISOString().split('T')[0];
            const to   = new Date(this.year, this.month + 2, 0).toISOString().split('T')[0];
            const r = await fetch('/api/calendar?from=' + from + '&to=' + to);
            const d = await r.json();
            this.events = Array.isArray(d) ? d : [];
        },

        prevMonth() { if (this.month === 0) { this.month = 11; this.year--; } else this.month--; this.loadEvents(); },
        nextMonth() { if (this.month === 11) { this.month = 0; this.year++; } else this.month++; this.loadEvents(); },
        goToday() { this.year = new Date().getFullYear(); this.month = new Date().getMonth(); this.loadEvents(); },

        openCreate() {
            const today = new Date().toISOString().slice(0, 16);
            this.form = { title: '', description: '', all_day: false, start_date: today, end_date: today, category: '', color: '#6366f1', location: '' };
            this.isNew = true;
            this.currentEvent = null;
            this.modal = true;
        },

        openCreateOnDay(date) {
            this.form = { title: '', description: '', all_day: true, start_date: date, end_date: date, category: '', color: '#6366f1', location: '' };
            this.isNew = true;
            this.currentEvent = null;
            this.modal = true;
        },

        openDetail(ev) {
            this.currentEvent = ev;
            this.form = { ...ev, start_date: (ev.start_date || '').slice(0, 16), end_date: (ev.end_date || '').slice(0, 16) };
            if (ev.all_day) { this.form.start_date = (ev.start_date || '').split('T')[0]; this.form.end_date = (ev.end_date || '').split('T')[0]; }
            this.isNew = false;
            this.modal = true;
        },

        async saveEvent() {
            if (!this.form.title?.trim()) return alert('Titel erforderlich');
            if (this.isNew) {
                const r = await fetch('/api/calendar', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.form) });
                if (r.ok) { this.modal = false; await this.loadEvents(); }
                else { const e = await r.json(); alert(e.detail || 'Fehler'); }
            } else {
                const r = await fetch('/api/calendar/' + this.currentEvent.id, { method: 'PUT', headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.form) });
                if (r.ok) { this.modal = false; await this.loadEvents(); }
                else { const e = await r.json(); alert(e.detail || 'Fehler'); }
            }
        },

        async deleteEvent() {
            if (!confirm('Event löschen?')) return;
            await fetch('/api/calendar/' + this.currentEvent.id, { method: 'DELETE' });
            this.modal = false;
            await this.loadEvents();
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

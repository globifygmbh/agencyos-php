<?php $pageTitle = 'Kalender'; ?>
<?php require __DIR__ . '/_layout.php'; ?>

<div x-data="calendarApp()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
        <button @click="prevMonth()" class="p-2 text-white/40 hover:text-white hover:bg-white/8 rounded-xl transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
        <h2 class="font-heading text-xl font-bold text-white min-w-48 text-center" x-text="monthTitle"></h2>
        <button @click="nextMonth()" class="p-2 text-white/40 hover:text-white hover:bg-white/8 rounded-xl transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
        <button @click="goToday()" class="text-sm text-white/50 hover:text-white px-3 py-2 bg-white/6 hover:bg-white/10 rounded-xl transition-colors">
            Heute
        </button>
        <button @click="openCreate()" class="ml-auto btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Neues Event
        </button>
    </div>

    <!-- Calendar Grid -->
    <div class="glass-card overflow-hidden">
        <!-- Weekday headers -->
        <div class="grid grid-cols-7 border-b border-white/8">
            <template x-for="day in ['Mo','Di','Mi','Do','Fr','Sa','So']" :key="day">
                <div class="py-3 text-center text-xs font-medium text-white/30 uppercase tracking-wider" x-text="day"></div>
            </template>
        </div>

        <!-- Days grid -->
        <div class="grid grid-cols-7">
            <template x-for="(cell, idx) in calendarCells" :key="idx">
                <div class="border-b border-r border-white/6 min-h-28 p-2 transition-colors cursor-pointer hover:bg-white/3"
                     :class="{
                        'opacity-40': !cell.currentMonth,
                        'bg-primary-500/5': cell.isToday,
                     }"
                     @click="openCreateOnDay(cell.date)">

                    <div class="text-xs mb-1 w-6 h-6 flex items-center justify-center rounded-full font-medium transition-colors"
                         :class="{
                            'text-white/20': !cell.currentMonth,
                            'text-white font-bold': cell.isToday,
                            'text-white/60': cell.currentMonth && !cell.isToday,
                         }"
                         :style="cell.isToday ? 'background: #009dde;' : ''"
                         x-text="cell.day"></div>

                    <template x-for="ev in getEventsForDate(cell.date)" :key="ev.id">
                        <div class="text-xs px-1.5 py-0.5 rounded-md mb-0.5 truncate cursor-pointer hover:opacity-80 transition-opacity font-medium"
                             :style="'background:' + (ev.color || '#009dde') + '25; color:' + (ev.color || '#009dde')"
                             @click.stop="openDetail(ev)"
                             x-text="ev.title"></div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <!-- Event Modal -->
    <div x-show="modal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);"
         @click.self="modal = false">
        <div class="glass-card w-full max-w-md" @click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-white/8">
                <h2 class="font-heading font-semibold text-white" x-text="isNew ? 'Neues Event' : 'Event bearbeiten'"></h2>
                <button @click="modal = false" class="p-1.5 rounded-xl text-white/40 hover:text-white hover:bg-white/8 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-white/50 mb-1.5">Titel *</label>
                    <input x-model="form.title" type="text" class="input-field" placeholder="Terminbezeichnung">
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/50 mb-1.5">Beschreibung</label>
                    <textarea x-model="form.description" rows="2" class="input-field resize-none"></textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input x-model="form.all_day" type="checkbox" id="allday"
                           class="w-4 h-4 rounded border-white/20 bg-white/5 accent-primary-500">
                    <label for="allday" class="text-sm text-white/70">Ganztägig</label>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-white/50 mb-1.5">Start</label>
                        <input x-model="form.start_date" :type="form.all_day ? 'date' : 'datetime-local'" class="input-field">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/50 mb-1.5">Ende</label>
                        <input x-model="form.end_date" :type="form.all_day ? 'date' : 'datetime-local'" class="input-field">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-white/50 mb-1.5">Kategorie</label>
                        <select x-model="form.category" class="input-field">
                            <option value="">–</option>
                            <option value="meeting">👥 Meeting</option>
                            <option value="deadline">⏰ Deadline</option>
                            <option value="vacation">🌴 Urlaub</option>
                            <option value="birthday">🎂 Geburtstag</option>
                            <option value="other">📌 Sonstiges</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/50 mb-1.5">Farbe</label>
                        <input x-model="form.color" type="color" class="w-full h-10 rounded-xl cursor-pointer bg-transparent border border-white/10 p-1">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-white/50 mb-1.5">📍 Ort</label>
                    <input x-model="form.location" type="text" class="input-field" placeholder="Adresse oder Videolink">
                </div>
            </div>
            <div class="px-6 py-4 border-t border-white/8 flex items-center gap-3">
                <button @click="saveEvent()" class="btn-primary">
                    <span x-text="isNew ? 'Erstellen' : 'Speichern'"></span>
                </button>
                <template x-if="!isNew && currentEvent">
                    <button @click="deleteEvent()" class="px-4 py-2 rounded-xl text-sm text-red-400 hover:bg-red-500/10 transition-colors">Löschen</button>
                </template>
                <button @click="modal = false" class="ml-auto text-sm text-white/40 hover:text-white transition-colors">Abbrechen</button>
            </div>
        </div>
    </div>
</div>

<script>
function calendarApp() {
    return {
        events: [], year: new Date().getFullYear(), month: new Date().getMonth(),
        modal: false, isNew: true, currentEvent: null, form: {},

        get monthTitle() {
            return new Date(this.year, this.month, 1).toLocaleDateString('de-DE', {month:'long', year:'numeric'});
        },

        get calendarCells() {
            const first = new Date(this.year, this.month, 1);
            const last  = new Date(this.year, this.month + 1, 0);
            const today = new Date().toISOString().split('T')[0];
            let startDow = first.getDay() || 7;
            const cells = [];
            for (let i = startDow - 1; i > 0; i--) {
                const d = new Date(this.year, this.month, 1 - i);
                cells.push({ day: d.getDate(), date: d.toISOString().split('T')[0], currentMonth: false, isToday: false });
            }
            for (let d = 1; d <= last.getDate(); d++) {
                const date = new Date(this.year, this.month, d).toISOString().split('T')[0];
                cells.push({ day: d, date, currentMonth: true, isToday: date === today });
            }
            while (cells.length < 42) {
                const d = new Date(this.year, this.month + 1, cells.length - last.getDate() - (startDow - 2));
                cells.push({ day: d.getDate(), date: d.toISOString().split('T')[0], currentMonth: false, isToday: false });
            }
            return cells;
        },

        getEventsForDate(date) {
            return this.events.filter(ev => {
                const start = (ev.start_date || '').split('T')[0];
                const end   = (ev.end_date || ev.start_date || '').split('T')[0];
                return date >= start && date <= end;
            });
        },

        async init() {
            await this.loadEvents();
            window.addEventListener('openEventModal', e => {
                this.openCreate();
                if (e.detail?.prefill) Object.assign(this.form, e.detail.prefill);
            });
        },

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
            this.form = { title: '', description: '', all_day: false, start_date: today, end_date: today, category: '', color: '#009dde', location: '' };
            this.isNew = true; this.currentEvent = null; this.modal = true;
        },

        openCreateOnDay(date) {
            this.form = { title: '', description: '', all_day: true, start_date: date, end_date: date, category: '', color: '#009dde', location: '' };
            this.isNew = true; this.currentEvent = null; this.modal = true;
        },

        openDetail(ev) {
            this.currentEvent = ev;
            this.form = { ...ev, start_date: (ev.start_date || '').slice(0, 16), end_date: (ev.end_date || '').slice(0, 16) };
            if (ev.all_day) { this.form.start_date = (ev.start_date || '').split('T')[0]; this.form.end_date = (ev.end_date || '').split('T')[0]; }
            this.isNew = false; this.modal = true;
        },

        async saveEvent() {
            if (!this.form.title?.trim()) return alert('Titel erforderlich');
            const method = this.isNew ? 'POST' : 'PUT';
            const url = this.isNew ? '/api/calendar' : '/api/calendar/' + this.currentEvent.id;
            const r = await fetch(url, { method, headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.form) });
            if (r.ok) { this.modal = false; await this.loadEvents(); }
            else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        async deleteEvent() {
            if (!confirm('Event löschen?')) return;
            await fetch('/api/calendar/' + this.currentEvent.id, { method: 'DELETE' });
            this.modal = false; await this.loadEvents();
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

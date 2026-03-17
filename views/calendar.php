<?php $pageTitle = 'Kalender'; ?>
<?php require __DIR__ . '/_layout.php'; ?>

<div x-data="calendarApp()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
        <button @click="prev()" class="p-2 text-white/40 hover:text-white hover:bg-white/8 rounded-xl transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
        <h2 class="font-heading text-xl font-bold text-white min-w-56 text-center" x-text="periodTitle"></h2>
        <button @click="next()" class="p-2 text-white/40 hover:text-white hover:bg-white/8 rounded-xl transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
        <button @click="goToday()" class="text-sm text-white/50 hover:text-white px-3 py-2 bg-white/6 hover:bg-white/10 rounded-xl transition-colors">
            Heute
        </button>

        <!-- View Toggle -->
        <div class="flex items-center gap-1 bg-white/5 rounded-xl p-1 ml-2">
            <button @click="view = 'month'"
                    :class="view === 'month' ? 'bg-white/10 text-white' : 'text-white/40 hover:text-white'"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">Monat</button>
            <button @click="view = 'week'"
                    :class="view === 'week' ? 'bg-white/10 text-white' : 'text-white/40 hover:text-white'"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">Woche</button>
            <button @click="view = 'day'"
                    :class="view === 'day' ? 'bg-white/10 text-white' : 'text-white/40 hover:text-white'"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">Tag</button>
        </div>

        <!-- iCloud Sync Button -->
        <button @click="showSyncModal = true"
                class="ml-auto flex items-center gap-2 px-3 py-2 rounded-xl text-white/50 hover:text-white hover:bg-white/8 transition-colors text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Sync
        </button>

        <button @click="openCreate()" class="btn-primary flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Neues Event
        </button>
    </div>

    <!-- ===== iCLOUD SYNC MODAL ===== -->
    <div x-show="showSyncModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center"
         style="background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);"
         @click.self="showSyncModal = false">
        <div class="glass-card rounded-2xl p-6 w-full max-w-lg mx-4 shadow-2xl max-h-[90vh] overflow-y-auto">

            <div class="flex items-center justify-between mb-5">
                <h3 class="font-heading text-lg font-bold text-white">Kalender-Synchronisation</h3>
                <button @click="showSyncModal = false" class="text-white/30 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- AgencyOS als iCal abonnieren -->
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(0,157,222,0.2);">
                        <svg class="w-4 h-4" style="color:#009dde" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white">AgencyOS → iPhone/iPad</p>
                        <p class="text-xs text-white/40">Deine Events in Apple Kalender abonnieren</p>
                    </div>
                </div>
                <div class="bg-white/5 rounded-xl p-4 space-y-2 text-sm text-white/70">
                    <p class="font-medium text-white/90">So verbindest du AgencyOS mit deinem Apple Kalender:</p>
                    <ol class="space-y-1.5 list-decimal list-inside text-white/60">
                        <li>Öffne <strong class="text-white/80">Einstellungen → Kalender → Accounts → Account hinzufügen</strong></li>
                        <li>Tippe auf <strong class="text-white/80">„Andere"</strong> → <strong class="text-white/80">„Kalender-Abo hinzufügen"</strong></li>
                        <li>Füge die URL unten ein und tippe auf <strong class="text-white/80">„Weiter"</strong></li>
                        <li>Bestätige mit <strong class="text-white/80">„Abonnieren"</strong></li>
                    </ol>
                </div>
                <div class="mt-3 flex gap-2">
                    <input type="text" readonly
                           :value="icalExportUrl"
                           class="input-field flex-1 font-mono text-xs text-white/60"
                           @click="$el.select()">
                    <button @click="copyIcalUrl()"
                            class="btn-ghost px-3 py-2 text-xs flex-shrink-0">
                        <template x-if="!icalCopied">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </template>
                        <template x-if="icalCopied">
                            <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </template>
                    </button>
                </div>
            </div>

            <!-- Divider -->
            <div class="border-t border-white/8 my-5"></div>

            <!-- iCloud → AgencyOS (externe Kalender einbinden) -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0" style="background: rgba(52,199,89,0.15);">
                        <svg class="w-4 h-4" style="color:#34c759" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white">Externen Kalender einbinden</p>
                        <p class="text-xs text-white/40">iCloud, Google oder beliebige iCal-URL</p>
                    </div>
                </div>
                <div class="bg-white/5 rounded-xl p-4 mb-3 text-sm text-white/60 space-y-1.5">
                    <p class="font-medium text-white/80">iCloud-URL ermitteln (iOS):</p>
                    <ol class="space-y-1 list-decimal list-inside">
                        <li>Öffne <strong class="text-white/70">iCloud.com → Kalender</strong></li>
                        <li>Klicke neben deinem Kalender auf das <strong class="text-white/70">Teilen-Symbol</strong></li>
                        <li>Aktiviere <strong class="text-white/70">„Öffentlichen Kalender"</strong></li>
                        <li>Kopiere die <strong class="text-white/70">webcal://...</strong> URL</li>
                    </ol>
                </div>

                <template x-for="(cal, idx) in syncCalendars" :key="idx">
                    <div class="flex items-center gap-2 mb-2">
                        <input :value="cal.name" @input="cal.name = $event.target.value"
                               type="text" placeholder="Bezeichnung (z.B. Privat)"
                               class="input-field w-32 flex-shrink-0">
                        <input :value="cal.url" @input="cal.url = $event.target.value"
                               type="text" placeholder="webcal:// oder https://..."
                               class="input-field flex-1">
                        <input :value="cal.color" @input="cal.color = $event.target.value"
                               type="color" class="w-8 h-8 rounded-lg cursor-pointer border border-white/10 bg-transparent flex-shrink-0">
                        <button @click="syncCalendars.splice(idx, 1)"
                                class="text-white/30 hover:text-red-400 transition-colors flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </template>

                <button @click="syncCalendars.push({name:'',url:'',color:'#34c759'})"
                        class="w-full mt-1 py-2 rounded-xl border border-dashed border-white/15 text-white/40 hover:text-white/70 hover:border-white/30 transition-colors text-sm flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Kalender hinzufügen
                </button>
            </div>

            <!-- Save -->
            <div class="flex justify-end gap-2 mt-5">
                <button @click="showSyncModal = false" class="btn-ghost text-sm px-4 py-2">Abbrechen</button>
                <button @click="saveSyncSettings()" class="btn-primary text-sm px-4 py-2 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Speichern
                </button>
            </div>

            <!-- Sync status -->
            <p x-show="syncSaved" x-cloak class="text-xs text-green-400 text-center mt-3">✓ Einstellungen gespeichert!</p>
        </div>
    </div>

    <!-- ===== MONTH VIEW ===== -->
    <div x-show="view === 'month'" class="glass-card overflow-hidden">
        <div class="grid grid-cols-7 border-b border-white/8">
            <template x-for="day in ['Mo','Di','Mi','Do','Fr','Sa','So']" :key="day">
                <div class="py-3 text-center text-xs font-medium text-white/30 uppercase tracking-wider" x-text="day"></div>
            </template>
        </div>
        <div class="grid grid-cols-7">
            <template x-for="(cell, idx) in calendarCells" :key="idx">
                <div class="border-b border-r border-white/6 min-h-28 p-2 transition-colors cursor-pointer hover:bg-white/3"
                     :class="{
                        'opacity-40': !cell.currentMonth,
                        'bg-primary-500/5': cell.isToday,
                     }"
                     @click="openCreateOnDay(cell.date)">
                    <div class="text-xs mb-1 w-6 h-6 flex items-center justify-center rounded-full font-medium"
                         :class="{ 'text-white/20': !cell.currentMonth, 'text-white font-bold': cell.isToday, 'text-white/60': cell.currentMonth && !cell.isToday }"
                         :style="cell.isToday ? 'background: #009dde;' : ''"
                         x-text="cell.day"></div>
                    <template x-for="ev in getEventsForDate(cell.date).slice(0,3)" :key="ev.id">
                        <div class="text-xs px-1.5 py-0.5 rounded-md mb-0.5 truncate cursor-pointer hover:opacity-80 transition-opacity font-medium"
                             :style="'background:' + (ev.color || '#009dde') + '25; color:' + (ev.color || '#009dde')"
                             @click.stop="openDetail(ev)"
                             x-text="(ev.all_day ? '' : formatTime(ev.start_date) + ' ') + ev.title"></div>
                    </template>
                    <template x-if="getEventsForDate(cell.date).length > 3">
                        <div class="text-xs text-white/30 px-1" x-text="'+' + (getEventsForDate(cell.date).length - 3) + ' mehr'"></div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <!-- ===== WEEK VIEW ===== -->
    <div x-show="view === 'week'" class="glass-card overflow-hidden">
        <!-- Header row with day names -->
        <div class="grid border-b border-white/8" :style="'grid-template-columns: 60px repeat(7, 1fr)'">
            <div class="py-3 border-r border-white/8"></div>
            <template x-for="(day, idx) in weekDays" :key="idx">
                <div class="py-3 text-center border-r border-white/6 last:border-0"
                     :class="day.isToday ? 'bg-primary-500/5' : ''">
                    <div class="text-xs text-white/40 uppercase tracking-wider" x-text="['Mo','Di','Mi','Do','Fr','Sa','So'][idx]"></div>
                    <div class="text-xl font-bold mt-0.5 w-8 h-8 flex items-center justify-center rounded-full mx-auto"
                         :class="day.isToday ? 'text-white' : 'text-white/70'"
                         :style="day.isToday ? 'background: #009dde;' : ''"
                         x-text="new Date(day.date).getDate()"></div>
                </div>
            </template>
        </div>

        <!-- All-day events row -->
        <div x-show="weekDays.some(d => getEventsForDate(d.date).filter(e => e.all_day).length > 0)"
             class="grid border-b border-white/8" :style="'grid-template-columns: 60px repeat(7, 1fr)'">
            <div class="px-2 py-1 text-xs text-white/30 flex items-center border-r border-white/8">Ganzt.</div>
            <template x-for="(day, idx) in weekDays" :key="idx">
                <div class="px-1 py-1 min-h-7 border-r border-white/6 last:border-0">
                    <template x-for="ev in getEventsForDate(day.date).filter(e => e.all_day)" :key="ev.id">
                        <div class="text-xs px-1.5 py-0.5 rounded-md mb-0.5 truncate cursor-pointer hover:opacity-80"
                             :style="'background:' + (ev.color || '#009dde') + '30; color:' + (ev.color || '#009dde')"
                             @click="openDetail(ev)"
                             x-text="ev.title"></div>
                    </template>
                </div>
            </template>
        </div>

        <!-- Hourly grid -->
        <div class="overflow-y-auto" style="max-height: 600px;">
            <div class="grid" :style="'grid-template-columns: 60px repeat(7, 1fr)'">
                <!-- Hours column + day columns -->
                <template x-for="hour in hours" :key="hour">
                    <!-- Time label -->
                    <div class="px-2 py-1 text-xs text-white/25 border-b border-white/5 border-r border-white/8 h-14 flex items-start pt-1"
                         x-text="hour.toString().padStart(2,'0') + ':00'"></div>
                    <!-- Day cells for this hour -->
                    <template x-for="(day, dayIdx) in weekDays" :key="dayIdx">
                        <div class="border-b border-r border-white/5 last:border-r-0 h-14 relative cursor-pointer hover:bg-white/2 transition-colors"
                             :class="day.isToday ? 'bg-primary-500/3' : ''"
                             @click="openCreateOnDayTime(day.date, hour)">
                            <template x-for="ev in getEventsForDateHour(day.date, hour)" :key="ev.id">
                                <div class="absolute inset-x-0.5 top-0.5 px-1.5 py-0.5 rounded-lg text-xs font-medium truncate cursor-pointer hover:opacity-80 z-10"
                                     :style="'background:' + (ev.color || '#009dde') + '30; color:' + (ev.color || '#009dde') + '; border-left: 3px solid ' + (ev.color || '#009dde')"
                                     @click.stop="openDetail(ev)"
                                     x-text="formatTime(ev.start_date) + ' ' + ev.title"></div>
                            </template>
                            <!-- Current time indicator -->
                            <template x-if="day.isToday && hour === currentHour">
                                <div class="absolute left-0 right-0 z-20" :style="'top: ' + currentMinPct + '%; background: #ef4444;'" style="height: 2px;">
                                    <div class="w-2.5 h-2.5 rounded-full bg-red-400 -mt-1 -ml-1"></div>
                                </div>
                            </template>
                        </div>
                    </template>
                </template>
            </div>
        </div>
    </div>

    <!-- ===== DAY VIEW ===== -->
    <div x-show="view === 'day'">
        <!-- All-day events -->
        <template x-if="getEventsForDate(currentDay).filter(e => e.all_day).length > 0">
            <div class="glass-card p-3 mb-3 flex flex-wrap gap-2">
                <span class="text-xs text-white/40 self-center mr-2">Ganztägig:</span>
                <template x-for="ev in getEventsForDate(currentDay).filter(e => e.all_day)" :key="ev.id">
                    <div class="text-xs px-2.5 py-1 rounded-full cursor-pointer hover:opacity-80"
                         :style="'background:' + (ev.color || '#009dde') + '25; color:' + (ev.color || '#009dde')"
                         @click="openDetail(ev)"
                         x-text="ev.title"></div>
                </template>
            </div>
        </template>

        <!-- Hourly -->
        <div class="glass-card overflow-hidden">
            <div class="overflow-y-auto" style="max-height: 600px;">
                <template x-for="hour in hours" :key="hour">
                    <div class="flex border-b border-white/5 hover:bg-white/2 transition-colors group relative"
                         @click="openCreateOnDayTime(currentDay, hour)">
                        <div class="w-16 flex-shrink-0 px-3 py-3 text-xs text-white/25 border-r border-white/8"
                             x-text="hour.toString().padStart(2,'0') + ':00'"></div>
                        <div class="flex-1 min-h-14 relative px-2">
                            <template x-for="ev in getEventsForDateHour(currentDay, hour)" :key="ev.id">
                                <div class="my-0.5 px-2.5 py-1.5 rounded-xl text-xs font-medium cursor-pointer hover:opacity-80"
                                     :style="'background:' + (ev.color || '#009dde') + '25; color:' + (ev.color || '#009dde') + '; border-left: 3px solid ' + (ev.color || '#009dde')"
                                     @click.stop="openDetail(ev)">
                                    <span class="font-semibold" x-text="formatTime(ev.start_date)"></span>
                                    <span class="ml-1" x-text="ev.title"></span>
                                    <template x-if="ev.location">
                                        <span class="ml-1 text-white/50" x-text="'· 📍 ' + ev.location"></span>
                                    </template>
                                </div>
                            </template>
                        </div>
                        <!-- Current time indicator -->
                        <template x-if="currentDay === todayStr && hour === currentHour">
                            <div class="absolute left-16 right-0 z-20 flex items-center" :style="'top: ' + currentMinPct + '%;'">
                                <div class="w-2.5 h-2.5 rounded-full bg-red-400 flex-shrink-0"></div>
                                <div class="flex-1 h-0.5 bg-red-400 opacity-70"></div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
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
        events: [],
        view: 'month',
        year: new Date().getFullYear(),
        month: new Date().getMonth(),
        weekStart: null,   // Monday of current week
        currentDay: new Date().toISOString().split('T')[0],
        todayStr: new Date().toISOString().split('T')[0],
        hours: Array.from({length: 24}, (_, i) => i),
        currentHour: new Date().getHours(),
        currentMinPct: (new Date().getMinutes() / 60) * 100,
        modal: false,
        isNew: true,
        currentEvent: null,
        form: {},
        showSyncModal: false,
        syncCalendars: [],
        syncSaved: false,
        icalCopied: false,
        icalExportUrl: window.location.origin + '/api/calendar/export.ics',

        get periodTitle() {
            if (this.view === 'month') {
                return new Date(this.year, this.month, 1).toLocaleDateString('de-DE', {month:'long', year:'numeric'});
            } else if (this.view === 'week') {
                const end = new Date(this.weekStart);
                end.setDate(end.getDate() + 6);
                const opts = {day:'numeric', month:'short'};
                return new Date(this.weekStart).toLocaleDateString('de-DE', opts) + ' – ' + end.toLocaleDateString('de-DE', opts) + ' ' + end.getFullYear();
            } else {
                return new Date(this.currentDay).toLocaleDateString('de-DE', {weekday:'long', day:'numeric', month:'long', year:'numeric'});
            }
        },

        get weekDays() {
            const days = [];
            const today = this.todayStr;
            for (let i = 0; i < 7; i++) {
                const d = new Date(this.weekStart);
                d.setDate(d.getDate() + i);
                const dateStr = d.toISOString().split('T')[0];
                days.push({ date: dateStr, isToday: dateStr === today });
            }
            return days;
        },

        get calendarCells() {
            const first = new Date(this.year, this.month, 1);
            const last  = new Date(this.year, this.month + 1, 0);
            const today = this.todayStr;
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

        getEventsForDateHour(date, hour) {
            return this.events.filter(ev => {
                if (ev.all_day) return false;
                const start = ev.start_date || '';
                const evDate = start.split('T')[0];
                if (evDate !== date) return false;
                const evHour = parseInt((start.split('T')[1] || '00:00').split(':')[0]);
                return evHour === hour;
            });
        },

        getMonday(d) {
            const date = new Date(d);
            const day = date.getDay() || 7;
            date.setDate(date.getDate() - day + 1);
            return date.toISOString().split('T')[0];
        },

        async init() {
            this.weekStart = this.getMonday(new Date());
            this.updateCurrentTime();
            setInterval(() => this.updateCurrentTime(), 30000);
            await this.loadEvents();
            this.loadSyncSettings();
        },

        loadSyncSettings() {
            fetch('/api/calendar/sync-settings').then(r => r.json()).then(d => {
                if (d && Array.isArray(d.calendars)) this.syncCalendars = d.calendars;
            }).catch(() => {});
        },

        async saveSyncSettings() {
            const r = await fetch('/api/calendar/sync-settings', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ calendars: this.syncCalendars })
            });
            if (r.ok) {
                this.syncSaved = true;
                setTimeout(() => { this.syncSaved = false; }, 3000);
            }
        },

        copyIcalUrl() {
            navigator.clipboard.writeText(this.icalExportUrl).then(() => {
                this.icalCopied = true;
                setTimeout(() => { this.icalCopied = false; }, 2000);
            });
        },

        updateCurrentTime() {
            const now = new Date();
            this.currentHour = now.getHours();
            this.currentMinPct = (now.getMinutes() / 60) * 100;
        },

        async loadEvents() {
            let from, to;
            if (this.view === 'month') {
                from = new Date(this.year, this.month - 1, 1).toISOString().split('T')[0];
                to   = new Date(this.year, this.month + 2, 0).toISOString().split('T')[0];
            } else if (this.view === 'week') {
                from = this.weekStart;
                const toDate = new Date(this.weekStart);
                toDate.setDate(toDate.getDate() + 6);
                to = toDate.toISOString().split('T')[0];
            } else {
                from = this.currentDay;
                to   = this.currentDay;
            }
            const r = await fetch('/api/calendar/events?from=' + from + '&to=' + to);
            const d = await r.json();
            this.events = Array.isArray(d) ? d : [];
        },

        prev() {
            if (this.view === 'month') {
                if (this.month === 0) { this.month = 11; this.year--; } else this.month--;
            } else if (this.view === 'week') {
                const d = new Date(this.weekStart);
                d.setDate(d.getDate() - 7);
                this.weekStart = d.toISOString().split('T')[0];
            } else {
                const d = new Date(this.currentDay);
                d.setDate(d.getDate() - 1);
                this.currentDay = d.toISOString().split('T')[0];
            }
            this.loadEvents();
        },

        next() {
            if (this.view === 'month') {
                if (this.month === 11) { this.month = 0; this.year++; } else this.month++;
            } else if (this.view === 'week') {
                const d = new Date(this.weekStart);
                d.setDate(d.getDate() + 7);
                this.weekStart = d.toISOString().split('T')[0];
            } else {
                const d = new Date(this.currentDay);
                d.setDate(d.getDate() + 1);
                this.currentDay = d.toISOString().split('T')[0];
            }
            this.loadEvents();
        },

        goToday() {
            this.year = new Date().getFullYear();
            this.month = new Date().getMonth();
            this.weekStart = this.getMonday(new Date());
            this.currentDay = this.todayStr;
            this.loadEvents();
        },

        openCreate() {
            const today = new Date().toISOString().slice(0, 16);
            this.form = { title: '', description: '', all_day: false, start_date: today, end_date: today, category: '', color: '#009dde', location: '' };
            this.isNew = true; this.currentEvent = null; this.modal = true;
        },

        openCreateOnDay(date) {
            this.form = { title: '', description: '', all_day: true, start_date: date, end_date: date, category: '', color: '#009dde', location: '' };
            this.isNew = true; this.currentEvent = null; this.modal = true;
        },

        openCreateOnDayTime(date, hour) {
            const h = hour.toString().padStart(2, '0');
            const h2 = Math.min(hour + 1, 23).toString().padStart(2, '0');
            this.form = { title: '', description: '', all_day: false, start_date: date + 'T' + h + ':00', end_date: date + 'T' + h2 + ':00', category: '', color: '#009dde', location: '' };
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
            const url = this.isNew ? '/api/calendar/events' : '/api/calendar/events/' + this.currentEvent.id;
            const r = await fetch(url, { method, headers: {'Content-Type':'application/json'}, body: JSON.stringify(this.form) });
            if (r.ok) { this.modal = false; await this.loadEvents(); }
            else { const e = await r.json(); alert(e.detail || 'Fehler'); }
        },

        async deleteEvent() {
            if (!confirm('Event löschen?')) return;
            await fetch('/api/calendar/events/' + this.currentEvent.id, { method: 'DELETE' });
            this.modal = false; await this.loadEvents();
        },

        formatTime(dt) {
            if (!dt) return '';
            const t = dt.includes('T') ? dt.split('T')[1] : '';
            return t ? t.slice(0, 5) : '';
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

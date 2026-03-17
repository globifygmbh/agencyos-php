<?php $pageTitle = 'Aufgaben'; ?>
<?php require __DIR__ . '/_layout.php'; ?>

<div x-data="tasksApp()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center gap-3 mb-6 flex-wrap">
        <div>
            <h1 class="font-heading text-2xl font-bold text-white">📋 Aufgaben</h1>
            <p class="text-xs text-white/40 mt-0.5" x-text="tasks.length + ' Aufgaben'"></p>
        </div>

        <!-- View Toggle -->
        <div class="flex items-center gap-1 p-1 rounded-xl bg-white/5 border border-white/8 ml-auto">
            <button @click="view='kanban'" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                    :class="view==='kanban' ? 'bg-[#009dde] text-white' : 'text-white/40 hover:text-white'">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                Board
            </button>
            <button @click="view='list'" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                    :class="view==='list' ? 'bg-[#009dde] text-white' : 'text-white/40 hover:text-white'">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                Liste
            </button>
            <button @click="view='table'" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                    :class="view==='table' ? 'bg-[#009dde] text-white' : 'text-white/40 hover:text-white'">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 6h18M3 14h18M3 18h18"/></svg>
                Tabelle
            </button>
        </div>

        <button @click="openCreate()" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Neue Aufgabe
        </button>
    </div>

    <!-- Filters -->
    <div class="flex items-center gap-2 mb-5 flex-wrap">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="search" x-model.debounce.300ms="filterSearch" @input="applyFilters()" placeholder="Suche…" class="input-field pl-9 w-48">
        </div>
        <select x-model="filterStatus" @change="applyFilters()" class="input-field w-auto">
            <option value="">Alle Status</option>
            <template x-for="s in statuses" :key="s.id">
                <option :value="s.id" x-text="s.name"></option>
            </template>
        </select>
        <select x-model="filterPriority" @change="applyFilters()" class="input-field w-auto">
            <option value="">Alle Prioritäten</option>
            <option value="HIGH">🔴 Hoch</option>
            <option value="MEDIUM">🟡 Mittel</option>
            <option value="LOW">🟢 Niedrig</option>
        </select>
        <select x-model="filterAssignee" @change="applyFilters()" class="input-field w-auto">
            <option value="">Alle Mitarbeiter</option>
            <option value="me">Mir zugewiesen</option>
            <template x-for="u in users" :key="u.id">
                <option :value="u.id" x-text="u.first_name + ' ' + u.last_name"></option>
            </template>
        </select>
        <select x-model="filterProject" @change="applyFilters()" class="input-field w-auto">
            <option value="">Alle Projekte</option>
            <template x-for="p in projects" :key="p.id">
                <option :value="p.id" x-text="p.name"></option>
            </template>
        </select>
        <template x-if="tasks.length !== filteredTasks.length">
            <button @click="clearFilters()" class="text-xs text-white/40 hover:text-white px-2 py-1 rounded-lg hover:bg-white/8 transition-colors">
                ✕ Filter löschen
            </button>
        </template>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="flex items-center justify-center py-20">
        <svg class="w-6 h-6 animate-spin text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
    </div>

    <!-- KANBAN VIEW -->
    <div x-show="!loading && view === 'kanban'" class="flex gap-4 overflow-x-auto pb-4">
        <template x-for="col in statuses" :key="col.id">
            <div class="flex-shrink-0 w-72">
                <div class="flex items-center gap-2 mb-3 px-1">
                    <div class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="'background:' + col.color"></div>
                    <span class="text-sm font-semibold text-white font-heading" x-text="col.name"></span>
                    <span class="ml-auto text-xs text-white/40 bg-white/8 rounded-full px-2 py-0.5"
                          x-text="getTasksForColumn(col.id).length"></span>
                </div>

                <div class="space-y-2 min-h-12">
                    <template x-for="task in getTasksForColumn(col.id)" :key="task.id">
                        <div class="glass-card p-3.5 cursor-pointer transition-all hover:-translate-y-0.5 hover:border-white/20 relative overflow-hidden"
                             style="border: 1px solid rgba(255,255,255,0.08);"
                             @click="openDetail(task)">
                            <div class="absolute left-0 top-0 bottom-0 w-1 rounded-l-xl"
                                 :style="'background:' + priorityColor(task.priority)"></div>
                            <div class="pl-2">
                                <div class="text-sm font-medium text-white mb-1.5 line-clamp-2" x-text="task.title"></div>
                                <template x-if="task.description">
                                    <div class="text-xs text-white/35 mb-2 line-clamp-2" x-text="task.description"></div>
                                </template>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <template x-if="task.deadline">
                                        <span class="text-xs px-1.5 py-0.5 rounded-md"
                                              :class="isPastDeadline(task.deadline) ? 'bg-red-500/15 text-red-400' : 'bg-white/8 text-white/40'"
                                              x-text="formatDate(task.deadline)"></span>
                                    </template>
                                    <template x-if="task.estimated_hours">
                                        <span class="text-xs text-white/30" x-text="task.estimated_hours + 'h'"></span>
                                    </template>
                                    <template x-if="task.assignee">
                                        <div class="ml-auto w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                             :style="'background:' + (task.assignee.color || '#009dde')"
                                             :title="task.assignee.first_name + ' ' + task.assignee.last_name"
                                             x-text="(task.assignee.first_name || '?').charAt(0)"></div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <button @click="openCreateInStatus(col.id)"
                        class="w-full mt-2 text-xs text-white/25 hover:text-white/60 py-2.5 rounded-xl hover:bg-white/4 border border-dashed border-white/10 hover:border-white/25 transition-all">
                    + Aufgabe hinzufügen
                </button>
            </div>
        </template>
    </div>

    <!-- LIST VIEW -->
    <div x-show="!loading && view === 'list'">
        <template x-for="s in statuses" :key="s.id">
            <div x-show="getTasksForColumn(s.id).length > 0" class="mb-5">
                <div class="flex items-center gap-2 px-2 py-1.5 mb-1">
                    <div class="w-2 h-2 rounded-full" :style="'background:' + s.color"></div>
                    <span class="text-xs font-semibold text-white/50 uppercase tracking-widest" x-text="s.name"></span>
                    <span class="text-xs text-white/25" x-text="'(' + getTasksForColumn(s.id).length + ')'"></span>
                </div>
                <template x-for="task in getTasksForColumn(s.id)" :key="task.id">
                    <div class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/4 cursor-pointer transition-colors border-l-2 mb-0.5 group"
                         :style="'border-left-color:' + priorityColor(task.priority)"
                         @click="openDetail(task)">
                        <div class="w-4 h-4 rounded border-2 flex items-center justify-center flex-shrink-0"
                             :style="'border-color:' + s.color + '; ' + (s.is_done ? 'background:' + s.color : '')">
                            <template x-if="s.is_done">
                                <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </template>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-white truncate" :class="s.is_done ? 'line-through opacity-50' : ''" x-text="task.title"></div>
                            <template x-if="task.description">
                                <div class="text-xs text-white/35 truncate" x-text="task.description"></div>
                            </template>
                        </div>
                        <template x-if="task.deadline">
                            <span class="text-xs whitespace-nowrap"
                                  :class="isPastDeadline(task.deadline) ? 'text-red-400' : 'text-white/35'"
                                  x-text="formatDate(task.deadline)"></span>
                        </template>
                        <template x-if="task.assignee">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                 :style="'background:' + (task.assignee.color || '#009dde')"
                                 x-text="(task.assignee.first_name || '?').charAt(0)"></div>
                        </template>
                        <template x-if="task.estimated_hours">
                            <span class="text-xs text-white/25" x-text="task.estimated_hours + 'h'"></span>
                        </template>
                        <button @click.stop="openDetail(task)" class="opacity-0 group-hover:opacity-100 p-1.5 rounded-lg text-white/40 hover:text-[#009dde] hover:bg-[#009dde]/10 transition-all">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        </button>
                    </div>
                </template>
            </div>
        </template>
        <template x-if="!loading && filteredTasks.length === 0">
            <div class="text-center py-16 text-white/25">
                <svg class="w-10 h-10 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <p class="text-sm">Keine Aufgaben gefunden</p>
            </div>
        </template>
    </div>

    <!-- TABLE VIEW -->
    <div x-show="!loading && view === 'table'" class="glass-card overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/8 text-xs text-white/40 uppercase tracking-wider">
                    <th class="text-left py-3 px-4 font-medium">Aufgabe</th>
                    <th class="text-left py-3 px-4 font-medium">Status</th>
                    <th class="text-left py-3 px-4 font-medium">Priorität</th>
                    <th class="text-left py-3 px-4 font-medium">Zugewiesen</th>
                    <th class="text-left py-3 px-4 font-medium">Projekt</th>
                    <th class="text-left py-3 px-4 font-medium">Fällig</th>
                    <th class="text-left py-3 px-4 font-medium">Std.</th>
                    <th class="py-3 px-4"></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="task in filteredTasks" :key="task.id">
                    <tr class="border-b border-white/5 hover:bg-white/3 cursor-pointer transition-colors" @click="openDetail(task)">
                        <td class="py-3 px-4">
                            <div class="font-medium text-white max-w-xs truncate" x-text="task.title"></div>
                            <template x-if="task.description">
                                <div class="text-xs text-white/30 truncate" x-text="task.description"></div>
                            </template>
                        </td>
                        <td class="py-3 px-4">
                            <span class="text-xs px-2 py-0.5 rounded-full"
                                  :style="'background:' + (task.status?.color || '#666') + '25; color:' + (task.status?.color || '#aaa')"
                                  x-text="task.status?.name || '–'"></span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="text-xs" :style="'color:' + priorityColor(task.priority)"
                                  x-text="{'HIGH':'🔴 Hoch','MEDIUM':'🟡 Mittel','LOW':'🟢 Niedrig'}[(task.priority||'').toUpperCase()] || '–'"></span>
                        </td>
                        <td class="py-3 px-4">
                            <template x-if="task.assignee">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold"
                                         :style="'background:' + (task.assignee.color || '#009dde')"
                                         x-text="(task.assignee.first_name || '?').charAt(0)"></div>
                                    <span class="text-white/60 text-xs" x-text="task.assignee.first_name + ' ' + task.assignee.last_name"></span>
                                </div>
                            </template>
                            <template x-if="!task.assignee"><span class="text-white/25">–</span></template>
                        </td>
                        <td class="py-3 px-4 text-white/50 text-xs" x-text="task.project_name || '–'"></td>
                        <td class="py-3 px-4">
                            <template x-if="task.deadline">
                                <span class="text-xs" :class="isPastDeadline(task.deadline) ? 'text-red-400' : 'text-white/50'"
                                      x-text="formatDate(task.deadline)"></span>
                            </template>
                            <template x-if="!task.deadline"><span class="text-white/25">–</span></template>
                        </td>
                        <td class="py-3 px-4 text-white/40 text-xs font-mono" x-text="task.estimated_hours ? task.estimated_hours + 'h' : '–'"></td>
                        <td class="py-3 px-4">
                            <button @click.stop="openDetail(task)" class="p-1.5 rounded-lg text-white/25 hover:text-[#009dde] hover:bg-[#009dde]/10 transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </td>
                    </tr>
                </template>
                <template x-if="!loading && filteredTasks.length === 0">
                    <tr><td colspan="8" class="py-12 text-center text-white/30 text-sm">Keine Aufgaben gefunden</td></tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- TASK DETAIL SLIDE-IN PANEL -->
    <div x-show="panel.open" x-cloak
         class="fixed inset-0 z-50"
         style="background: rgba(0,0,0,0.6); backdrop-filter: blur(8px);"
         @click.self="panel.open = false">
        <div class="absolute inset-y-0 right-0 w-full max-w-2xl flex flex-col"
             style="background: #0c0c0e; border-left: 1px solid rgba(255,255,255,0.08);"
             @click.stop>

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-white/8 flex-shrink-0"
                 style="background: rgba(0,0,0,0.4); backdrop-filter: blur(20px);">
                <div class="flex items-center gap-3">
                    <div class="w-1 h-8 rounded-full" :style="'background:' + priorityColor(form.priority)"></div>
                    <h2 class="font-heading font-semibold text-white text-sm" x-text="panel.isNew ? 'Neue Aufgabe' : 'Aufgabe bearbeiten'"></h2>
                </div>
                <div class="flex items-center gap-1">
                    <template x-if="!panel.isNew && panel.task">
                        <button @click="archiveTask()" title="Archivieren"
                                class="p-2 rounded-xl text-white/30 hover:text-amber-400 hover:bg-amber-500/10 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8l1 12a2 2 0 002 2h8a2 2 0 002-2L19 8"/></svg>
                        </button>
                    </template>
                    <button @click="panel.open = false" class="p-2 rounded-xl text-white/40 hover:text-white hover:bg-white/8 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <!-- Tabs (only when editing) -->
            <template x-if="!panel.isNew">
                <div class="flex border-b border-white/8 flex-shrink-0 px-6">
                    <button @click="panelTab='details'" class="py-3 px-1 mr-6 text-xs font-medium border-b-2 transition-colors"
                            :class="panelTab==='details' ? 'text-[#009dde] border-[#009dde]' : 'text-white/40 border-transparent hover:text-white'">
                        Details
                    </button>
                    <button @click="panelTab='comments'; loadComments()" class="py-3 px-1 mr-6 text-xs font-medium border-b-2 transition-colors"
                            :class="panelTab==='comments' ? 'text-[#009dde] border-[#009dde]' : 'text-white/40 border-transparent hover:text-white'">
                        Kommentare
                        <template x-if="comments.length > 0">
                            <span class="ml-1 text-white/30" x-text="'(' + comments.length + ')'"></span>
                        </template>
                    </button>
                </div>
            </template>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto px-6 py-5">

                <!-- DETAILS TAB -->
                <div x-show="panelTab === 'details'" class="space-y-4">
                    <!-- Timer (edit only) -->
                    <template x-if="!panel.isNew && panel.task">
                        <div class="rounded-xl p-3.5 flex items-center gap-4"
                             style="background: linear-gradient(135deg, rgba(0,157,222,0.08), rgba(0,157,222,0.04)); border: 1px solid rgba(0,157,222,0.2);">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-0.5">
                                    <template x-if="activeTimerTaskId === panel.task.id">
                                        <span class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style="background:#009dde"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2" style="background:#009dde"></span>
                                        </span>
                                    </template>
                                    <span class="text-xs text-white/40" x-text="activeTimerTaskId === panel.task.id ? 'Zeit läuft…' : 'Zeiterfassung'"></span>
                                </div>
                                <div class="font-mono text-2xl font-bold"
                                     :style="activeTimerTaskId === panel.task.id ? 'color:#009dde' : 'color:rgba(255,255,255,0.8)'"
                                     x-text="activeTimerTaskId === panel.task.id ? timerDisplay : '00:00:00'"></div>
                            </div>
                            <button @click="toggleTimer(panel.task)"
                                    class="w-10 h-10 rounded-full flex items-center justify-center text-white transition-all hover:scale-110"
                                    :style="activeTimerTaskId === panel.task.id ? 'background: linear-gradient(135deg, #ff3b30, #cc2f26)' : 'background: linear-gradient(135deg, #34c759, #2da849)'">
                                <template x-if="activeTimerTaskId === panel.task.id">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M6 6h12v12H6z"/></svg>
                                </template>
                                <template x-if="activeTimerTaskId !== panel.task.id">
                                    <svg class="w-4 h-4 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                </template>
                            </button>
                        </div>
                    </template>

                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Titel *</label>
                        <input x-model="form.title" type="text" class="input-field" placeholder="Aufgabentitel">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-white/40 mb-1.5">Beschreibung</label>
                        <textarea x-model="form.description" rows="3" class="input-field resize-none" placeholder="Details zur Aufgabe…"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-white/40 mb-1.5">Status</label>
                            <select x-model="form.status_id" class="input-field">
                                <template x-for="s in statuses" :key="s.id">
                                    <option :value="s.id" x-text="s.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-white/40 mb-1.5">Priorität</label>
                            <select x-model="form.priority" class="input-field">
                                <option value="">–</option>
                                <option value="HIGH">🔴 Hoch</option>
                                <option value="MEDIUM">🟡 Mittel</option>
                                <option value="LOW">🟢 Niedrig</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-white/40 mb-1.5">Zugewiesen an</label>
                            <select x-model="form.assigned_to" class="input-field">
                                <option value="">–</option>
                                <template x-for="u in users" :key="u.id">
                                    <option :value="u.id" x-text="u.first_name + ' ' + u.last_name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-white/40 mb-1.5">Fälligkeitsdatum</label>
                            <input x-model="form.deadline" type="datetime-local" class="input-field">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-white/40 mb-1.5">Projekt</label>
                            <select x-model="form.project_id" class="input-field">
                                <option value="">–</option>
                                <template x-for="p in projects" :key="p.id">
                                    <option :value="p.id" x-text="p.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-white/40 mb-1.5">Geschätzte Stunden</label>
                            <input x-model="form.estimated_hours" type="number" step="0.5" min="0" class="input-field" placeholder="2.5">
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <input x-model="form.is_recurring" type="checkbox" id="isRecurring"
                               class="w-4 h-4 rounded bg-white/5 border-white/20 accent-[#009dde]">
                        <label for="isRecurring" class="text-sm text-white/60">Wiederkehrende Aufgabe</label>
                    </div>
                    <template x-if="form.is_recurring">
                        <div>
                            <label class="block text-xs font-medium text-white/40 mb-1.5">Wiederholung</label>
                            <select x-model="form.recurring_interval" class="input-field">
                                <option value="daily">Täglich</option>
                                <option value="weekly">Wöchentlich</option>
                                <option value="biweekly">Alle 2 Wochen</option>
                                <option value="monthly">Monatlich</option>
                            </select>
                        </div>
                    </template>
                </div>

                <!-- COMMENTS TAB -->
                <div x-show="panelTab === 'comments'" class="space-y-4">
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                             style="background: linear-gradient(135deg, #009dde, #0070a8);">
                            <?= strtoupper(substr($currentUser['first_name'] ?? $currentUser['username'] ?? '?', 0, 1)) ?>
                        </div>
                        <div class="flex-1">
                            <textarea x-model="newComment" rows="2" class="input-field resize-none text-sm"
                                      placeholder="Kommentar schreiben…"
                                      @keydown.meta.enter="addComment()"
                                      @keydown.ctrl.enter="addComment()"></textarea>
                            <div class="flex justify-between items-center mt-1.5">
                                <span class="text-xs text-white/25">⌘+Enter zum Senden</span>
                                <button @click="addComment()" class="btn-primary py-1.5 px-3 text-xs">Senden</button>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <template x-for="c in comments" :key="c.id">
                            <div class="flex gap-3">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0 mt-0.5"
                                     style="background: linear-gradient(135deg, #009dde, #0070a8);"
                                     x-text="(c.user_name || '?').charAt(0).toUpperCase()"></div>
                                <div class="flex-1">
                                    <div class="flex items-baseline gap-2 mb-0.5">
                                        <span class="text-xs font-semibold text-white" x-text="c.user_name || 'Unbekannt'"></span>
                                        <span class="text-xs text-white/25" x-text="formatDateTime(c.created_at)"></span>
                                    </div>
                                    <div class="text-sm text-white/70 leading-relaxed" x-text="c.content"></div>
                                </div>
                            </div>
                        </template>
                        <template x-if="comments.length === 0">
                            <div class="text-center py-8 text-white/25 text-sm">Noch keine Kommentare</div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-white/8 flex items-center gap-3 flex-shrink-0"
                 style="background: rgba(0,0,0,0.3);">
                <button @click="saveTask()" class="btn-primary" :disabled="!form.title?.trim()">
                    <span x-text="panel.isNew ? 'Erstellen' : 'Speichern'"></span>
                </button>
                <template x-if="!panel.isNew">
                    <button @click="deleteTask()"
                            class="px-4 py-2 rounded-xl text-sm text-red-400 hover:bg-red-500/10 transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Löschen
                    </button>
                </template>
                <button @click="panel.open = false" class="ml-auto text-sm text-white/40 hover:text-white transition-colors">Abbrechen</button>
            </div>
        </div>
    </div>
</div>

<script>
function tasksApp() {
    return {
        tasks: [], filteredTasks: [], statuses: [], users: [], projects: [],
        comments: [], newComment: '',
        loading: true,
        view: 'kanban',
        filterStatus: '', filterPriority: '', filterAssignee: '', filterProject: '', filterSearch: '',
        panel: { open: false, isNew: true, task: null },
        panelTab: 'details',
        form: {},

        // Timer
        activeTimerTaskId: null,
        timerSeconds: 0,
        timerInterval: null,
        timerEntryId: null,

        get timerDisplay() {
            const h = String(Math.floor(this.timerSeconds / 3600)).padStart(2,'0');
            const m = String(Math.floor((this.timerSeconds % 3600) / 60)).padStart(2,'0');
            const s = String(this.timerSeconds % 60).padStart(2,'0');
            return h + ':' + m + ':' + s;
        },

        async init() {
            await Promise.all([this.loadStatuses(), this.loadUsers(), this.loadProjects()]);
            await this.loadTasks();

            if (window.appLayoutData?.activeTimer) {
                const t = window.appLayoutData.activeTimer;
                this.activeTimerTaskId = t.task_id || null;
                this.timerEntryId = t.id;
                if (this.activeTimerTaskId) {
                    this.timerSeconds = Math.floor((Date.now() - new Date(t.start_time).getTime()) / 1000);
                    this.timerInterval = setInterval(() => this.timerSeconds++, 1000);
                }
            }

            const params = new URLSearchParams(window.location.search);
            if (params.get('task')) {
                const task = this.tasks.find(t => t.id === params.get('task'));
                if (task) this.openDetail(task);
            }

            window.addEventListener('openTaskModal', e => {
                this.openCreate();
                if (e.detail?.prefill) Object.assign(this.form, e.detail.prefill);
            });
        },

        async loadStatuses() {
            const r = await fetch('/api/task-statuses');
            const d = await r.json();
            this.statuses = Array.isArray(d) ? d : [];
        },
        async loadUsers() {
            const r = await fetch('/api/users');
            const d = await r.json();
            this.users = Array.isArray(d) ? d : (d.users || []);
        },
        async loadProjects() {
            const r = await fetch('/api/projects?limit=200');
            const d = await r.json();
            this.projects = Array.isArray(d) ? d : (d.projects || []);
        },
        async loadTasks() {
            this.loading = true;
            const r = await fetch('/api/tasks?limit=500');
            const d = await r.json();
            this.tasks = Array.isArray(d) ? d : (d.tasks || []);
            this.applyFilters();
            this.loading = false;
        },

        applyFilters() {
            let t = [...this.tasks];
            if (this.filterStatus)   t = t.filter(x => x.status_id === this.filterStatus);
            if (this.filterPriority) t = t.filter(x => (x.priority||'').toUpperCase() === this.filterPriority);
            if (this.filterProject)  t = t.filter(x => x.project_id === this.filterProject);
            if (this.filterAssignee === 'me') {
                const myId = window.CURRENT_USER?.id;
                t = t.filter(x => x.assigned_to === myId);
            } else if (this.filterAssignee) {
                t = t.filter(x => x.assigned_to === this.filterAssignee);
            }
            if (this.filterSearch) {
                const q = this.filterSearch.toLowerCase();
                t = t.filter(x => (x.title||'').toLowerCase().includes(q) || (x.description||'').toLowerCase().includes(q));
            }
            this.filteredTasks = t;
        },

        clearFilters() {
            this.filterStatus = ''; this.filterPriority = ''; this.filterAssignee = '';
            this.filterProject = ''; this.filterSearch = '';
            this.applyFilters();
        },

        getTasksForColumn(statusId) {
            return this.filteredTasks.filter(t => t.status_id === statusId);
        },

        openCreate() {
            this.form = {
                title: '', description: '',
                status_id: this.statuses[0]?.id || '',
                priority: 'MEDIUM',
                assigned_to: '', deadline: '', project_id: '',
                estimated_hours: '', is_recurring: false, recurring_interval: 'weekly'
            };
            this.panel = { open: true, isNew: true, task: null };
            this.panelTab = 'details';
        },

        openCreateInStatus(statusId) {
            this.openCreate();
            this.form.status_id = statusId;
        },

        async openDetail(task) {
            this.form = {
                ...task,
                priority: (task.priority || 'MEDIUM').toUpperCase(),
                deadline: task.deadline ? task.deadline.slice(0,16) : '',
                is_recurring: task.is_recurring == 1 || task.is_recurring === true,
                recurring_interval: task.recurring_config?.interval || 'weekly',
            };
            this.panel = { open: true, isNew: false, task };
            this.panelTab = 'details';
            await this.loadComments();
        },

        async loadComments() {
            if (!this.panel.task) return;
            const r = await fetch('/api/tasks/' + this.panel.task.id + '/comments');
            const d = await r.json();
            this.comments = Array.isArray(d) ? d : [];
        },

        async saveTask() {
            if (!this.form.title?.trim()) return alert('Titel ist erforderlich');
            const payload = {
                title: this.form.title,
                description: this.form.description || null,
                status_id: this.form.status_id || null,
                priority: (this.form.priority || 'MEDIUM').toUpperCase(),
                assigned_to: this.form.assigned_to || null,
                deadline: this.form.deadline || null,
                project_id: this.form.project_id || null,
                estimated_hours: this.form.estimated_hours ? parseFloat(this.form.estimated_hours) : null,
                is_recurring: this.form.is_recurring ? 1 : 0,
                recurring_config: this.form.is_recurring ? { interval: this.form.recurring_interval || 'weekly' } : null,
            };
            const method = this.panel.isNew ? 'POST' : 'PUT';
            const url = this.panel.isNew ? '/api/tasks' : '/api/tasks/' + this.panel.task.id;
            const r = await fetch(url, { method, headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            if (r.ok) {
                this.panel.open = false;
                await this.loadTasks();
            } else {
                const e = await r.json();
                alert(e.detail || 'Fehler beim Speichern');
            }
        },

        async deleteTask() {
            if (!confirm('Aufgabe wirklich löschen?')) return;
            await fetch('/api/tasks/' + this.panel.task.id, { method: 'DELETE' });
            this.panel.open = false;
            await this.loadTasks();
        },

        async archiveTask() {
            if (!confirm('Aufgabe archivieren?')) return;
            await fetch('/api/tasks/' + this.panel.task.id, {
                method: 'PUT', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ is_archived: 1 })
            });
            this.panel.open = false;
            await this.loadTasks();
        },

        async addComment() {
            if (!this.newComment.trim() || !this.panel.task) return;
            const r = await fetch('/api/tasks/' + this.panel.task.id + '/comments', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ content: this.newComment })
            });
            if (r.ok) {
                this.newComment = '';
                await this.loadComments();
            }
        },

        async toggleTimer(task) {
            if (this.activeTimerTaskId === task.id && this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
                if (this.timerEntryId) {
                    await fetch('/api/time/' + this.timerEntryId + '/stop', { method: 'POST' });
                    this.timerEntryId = null;
                }
                this.activeTimerTaskId = null;
                this.timerSeconds = 0;
            } else {
                if (this.timerInterval) {
                    clearInterval(this.timerInterval);
                    if (this.timerEntryId) await fetch('/api/time/' + this.timerEntryId + '/stop', { method: 'POST' });
                }
                const r = await fetch('/api/time/start', {
                    method: 'POST', headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({
                        description: task.title,
                        project_id: task.project_id || null,
                        start_time: new Date().toISOString()
                    })
                });
                if (r.ok) {
                    const entry = await r.json();
                    this.timerEntryId = entry.id;
                    this.activeTimerTaskId = task.id;
                    this.timerSeconds = 0;
                    this.timerInterval = setInterval(() => this.timerSeconds++, 1000);
                }
            }
        },

        priorityColor(p) {
            return { HIGH: '#ef4444', MEDIUM: '#eab308', LOW: '#22c55e' }[(p||'').toUpperCase()] || 'rgba(255,255,255,0.2)';
        },

        formatDate(d) {
            if (!d) return '';
            return new Date(d).toLocaleDateString('de-DE', {day:'2-digit', month:'2-digit', year:'2-digit'});
        },
        formatDateTime(d) {
            if (!d) return '';
            const dt = new Date(d);
            return dt.toLocaleDateString('de-DE', {day:'2-digit',month:'2-digit'}) + ' ' + dt.toLocaleTimeString('de-DE', {hour:'2-digit',minute:'2-digit'});
        },
        isPastDeadline(d) { return d && new Date(d) < new Date(); }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

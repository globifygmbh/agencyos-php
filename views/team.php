<?php $pageTitle = 'Team'; ?>
<?php require __DIR__ . '/_layout.php'; ?>

<div x-data="teamApp()" x-init="init()">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-heading text-2xl font-bold text-white">👥 Team</h1>
            <p class="text-xs text-white/40 mt-0.5" x-text="members.length + ' Mitglieder'"></p>
        </div>
        <div class="flex items-center gap-2">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" x-model="search" placeholder="Suche…" class="input-field pl-9 w-44">
            </div>
        </div>
    </div>

    <!-- Stats row -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="glass-card rounded-2xl p-4">
            <div class="text-xs text-white/40 mb-1">Team-Mitglieder</div>
            <div class="text-2xl font-bold text-white" x-text="members.length"></div>
        </div>
        <div class="glass-card rounded-2xl p-4">
            <div class="text-xs text-white/40 mb-1">Offene Aufgaben</div>
            <div class="text-2xl font-bold text-white" x-text="totalOpenTasks"></div>
        </div>
        <div class="glass-card rounded-2xl p-4">
            <div class="text-xs text-white/40 mb-1">Ø Auslastung</div>
            <div class="text-2xl font-bold text-white" x-text="avgWorkload + '%'"></div>
        </div>
        <div class="glass-card rounded-2xl p-4">
            <div class="text-xs text-white/40 mb-1">Im Urlaub (heute)</div>
            <div class="text-2xl font-bold text-amber-400" x-text="onVacationToday"></div>
        </div>
    </div>

    <!-- Team Grid -->
    <div x-show="loading" class="flex justify-center py-16">
        <svg class="w-6 h-6 animate-spin text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
    </div>

    <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <template x-for="m in filteredMembers" :key="m.id">
            <div class="glass-card rounded-2xl p-5 cursor-pointer hover:border-white/20 transition-all hover:-translate-y-0.5"
                 style="border: 1px solid rgba(255,255,255,0.08);"
                 @click="openMember(m)">

                <!-- Avatar + Status -->
                <div class="flex items-start justify-between mb-4">
                    <div class="relative">
                        <template x-if="m.profile_image">
                            <img :src="m.profile_image" class="w-14 h-14 rounded-2xl object-cover" alt="">
                        </template>
                        <template x-if="!m.profile_image">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white text-lg font-bold"
                                 :style="'background:' + (m.color || '#009dde')"
                                 x-text="((m.first_name || '') + (m.last_name || '')).charAt(0).toUpperCase()"></div>
                        </template>
                    </div>
                    <!-- Workload indicator -->
                    <div class="text-right">
                        <div class="text-xs font-bold mb-0.5"
                             :class="{
                                'text-red-400': (m.workload_pct || 0) >= 90,
                                'text-yellow-400': (m.workload_pct || 0) >= 60,
                                'text-green-400': (m.workload_pct || 0) < 60
                             }"
                             x-text="Math.round(m.workload_pct || 0) + '%'"></div>
                        <div class="text-xs text-white/30" x-text="(m.open_tasks || 0) + ' Tasks'"></div>
                    </div>
                </div>

                <div class="font-semibold text-white text-sm" x-text="(m.first_name || '') + ' ' + (m.last_name || '')"></div>
                <div class="text-xs text-white/50 mb-3" x-text="m.position || m.role || ''"></div>

                <!-- Workload bar -->
                <div class="h-1.5 bg-white/8 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all"
                         :style="'width:' + Math.min(m.workload_pct || 0, 100) + '%; background:' + ((m.workload_pct || 0) >= 90 ? '#ef4444' : (m.workload_pct || 0) >= 60 ? '#eab308' : '#22c55e')"></div>
                </div>

                <!-- Meta info -->
                <div class="flex items-center gap-3 mt-3 text-xs text-white/35">
                    <span x-text="(m.weekly_hours || 40) + 'h/W'"></span>
                    <template x-if="m.email">
                        <span class="truncate" x-text="m.email"></span>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <!-- Member Detail Modal -->
    <div x-show="selectedMember" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);"
         @click.self="selectedMember = null">
        <div class="glass-card rounded-2xl w-full max-w-lg shadow-2xl" @click.stop
             x-show="selectedMember">
            <!-- Header -->
            <div class="px-6 py-5 border-b border-white/8 flex items-center gap-4">
                <template x-if="selectedMember">
                    <div class="relative">
                        <template x-if="selectedMember.profile_image">
                            <img :src="selectedMember.profile_image" class="w-16 h-16 rounded-2xl object-cover">
                        </template>
                        <template x-if="!selectedMember.profile_image">
                            <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-white text-xl font-bold"
                                 :style="'background:' + (selectedMember.color || '#009dde')"
                                 x-text="((selectedMember.first_name || '') + (selectedMember.last_name || '')).charAt(0).toUpperCase()"></div>
                        </template>
                    </div>
                </template>
                <div class="flex-1" x-show="selectedMember">
                    <div class="font-heading font-semibold text-white text-lg" x-text="(selectedMember?.first_name || '') + ' ' + (selectedMember?.last_name || '')"></div>
                    <div class="text-sm text-white/50" x-text="selectedMember?.position || selectedMember?.role || ''"></div>
                    <div class="text-xs text-white/35 mt-0.5" x-text="selectedMember?.email || ''"></div>
                </div>
                <button @click="selectedMember = null" class="p-2 rounded-xl text-white/40 hover:text-white hover:bg-white/8 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Body -->
            <div class="px-6 py-4 space-y-5" x-show="selectedMember">
                <!-- Workload -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-white/50 uppercase tracking-wider">Auslastung</span>
                        <span class="text-sm font-bold"
                              :class="{
                                'text-red-400': (selectedMember?.workload_pct || 0) >= 90,
                                'text-yellow-400': (selectedMember?.workload_pct || 0) >= 60,
                                'text-green-400': (selectedMember?.workload_pct || 0) < 60
                              }"
                              x-text="Math.round(selectedMember?.workload_pct || 0) + '%'"></span>
                    </div>
                    <div class="h-2 bg-white/8 rounded-full overflow-hidden">
                        <div class="h-full rounded-full"
                             :style="'width:' + Math.min(selectedMember?.workload_pct || 0, 100) + '%; background:' + ((selectedMember?.workload_pct || 0) >= 90 ? '#ef4444' : (selectedMember?.workload_pct || 0) >= 60 ? '#eab308' : '#22c55e')"></div>
                    </div>
                    <div class="flex justify-between text-xs text-white/30 mt-1">
                        <span x-text="(selectedMember?.open_tasks || 0) + ' offene Aufgaben'"></span>
                        <span x-text="'Kapazität: ' + (selectedMember?.weekly_hours || 40) + 'h/Woche'"></span>
                    </div>
                </div>

                <!-- Stats grid -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-white/5 rounded-xl p-3 text-center">
                        <div class="text-lg font-bold text-white" x-text="selectedMember?.open_tasks || 0"></div>
                        <div class="text-xs text-white/40">Offen</div>
                    </div>
                    <div class="bg-white/5 rounded-xl p-3 text-center">
                        <div class="text-lg font-bold text-white" x-text="selectedMember?.weekly_hours || 40"></div>
                        <div class="text-xs text-white/40">Std/Wo</div>
                    </div>
                    <div class="bg-white/5 rounded-xl p-3 text-center">
                        <div class="text-lg font-bold text-white" x-text="(selectedMember?.vacation_days || 0) - (selectedMember?.vacation_days_used || 0)"></div>
                        <div class="text-xs text-white/40">Resturlaub</div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-3">
                    <a :href="'/chat'" class="btn-ghost text-sm flex-1 text-center py-2">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        Nachricht
                    </a>
                    <a :href="'/tasks?assigned_to=' + selectedMember?.id" class="btn-primary text-sm flex-1 text-center py-2">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        Aufgaben
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function teamApp() {
    return {
        members: [],
        loading: true,
        search: '',
        selectedMember: null,

        get filteredMembers() {
            if (!this.search) return this.members;
            const q = this.search.toLowerCase();
            return this.members.filter(m =>
                ((m.first_name || '') + ' ' + (m.last_name || '')).toLowerCase().includes(q) ||
                (m.email || '').toLowerCase().includes(q) ||
                (m.position || '').toLowerCase().includes(q)
            );
        },

        get totalOpenTasks() {
            return this.members.reduce((s, m) => s + (m.open_tasks || 0), 0);
        },

        get avgWorkload() {
            if (!this.members.length) return 0;
            return Math.round(this.members.reduce((s, m) => s + (m.workload_pct || 0), 0) / this.members.length);
        },

        get onVacationToday() {
            return 0; // Would need vacation API
        },

        async init() {
            await this.loadTeam();
        },

        async loadTeam() {
            this.loading = true;
            try {
                const [usersR, workloadR] = await Promise.all([
                    fetch('/api/users'),
                    fetch('/api/team/workload-overview')
                ]);
                const users = await usersR.json();
                const workload = workloadR.ok ? await workloadR.json() : [];

                const workloadMap = {};
                (Array.isArray(workload) ? workload : []).forEach(w => {
                    workloadMap[w.id] = w;
                });

                this.members = (Array.isArray(users) ? users : []).map(u => {
                    const w = workloadMap[u.id] || {};
                    const weeklyHours = u.weekly_hours || 40;
                    const openTasks = w.open_tasks || 0;
                    // Estimate ~2h per task for workload
                    const workloadPct = weeklyHours > 0 ? Math.min((openTasks * 2 / weeklyHours) * 100, 150) : 0;
                    return {
                        ...u,
                        open_tasks: openTasks,
                        workload_pct: Math.round(workloadPct),
                    };
                });
            } catch (e) {
                console.error(e);
            }
            this.loading = false;
        },

        openMember(m) {
            this.selectedMember = m;
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

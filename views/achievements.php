<?php
$pageTitle = 'Achievements';
require __DIR__ . '/_layout.php';
?>

<div x-data="achievementsApp()" x-init="init()">

    <!-- Level Card -->
    <div class="bg-gradient-to-br from-[#009dde]/20 to-purple-600/20 border border-[#009dde]/20 rounded-2xl p-6 mb-6" x-show="stats">
        <div class="flex items-center gap-6">
            <div class="text-center">
                <div class="text-5xl font-black" style="color: #009dde;" x-text="stats.level || 1"></div>
                <div class="text-xs text-white/40 uppercase tracking-wide mt-1">Level</div>
            </div>
            <div class="flex-1">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-white/70" x-text="(stats.title || 'Neuling')"></span>
                    <span class="text-sm text-white/40" x-text="(stats.points || 0) + ' Punkte'"></span>
                </div>
                <div class="h-3 bg-white/8 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-[#009dde] to-purple-500 rounded-full transition-all"
                         :style="'width:' + (stats.level_progress || 0) + '%'"></div>
                </div>
                <div class="flex justify-between text-xs text-white/50 mt-1">
                    <span x-text="'Level ' + (stats.level || 1)"></span>
                    <span x-text="'Level ' + ((stats.level || 1) + 1)"></span>
                </div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-yellow-400" x-text="stats.earned_achievements || 0"></div>
                <div class="text-xs text-white/40 mt-1">Achievements</div>
            </div>
        </div>
    </div>

    <!-- Leaderboard -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2">
            <!-- Achievements Grid -->
            <h3 class="text-sm font-heading font-semibold text-white/40 uppercase tracking-wide mb-4">Alle Achievements</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <template x-for="a in achievements" :key="a.id">
                    <div class="bg-white/5 border rounded-xl p-4 transition-all"
                         :class="a.earned ? 'border-[#009dde]/40 bg-[#009dde]/5' : 'border-white/8 opacity-60'">
                        <div class="flex items-start gap-3">
                            <div class="text-3xl" x-text="a.icon || '🏆'"></div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold"
                                     :class="a.earned ? 'text-white' : 'text-white/40'"
                                     x-text="a.title"></div>
                                <div class="text-xs text-white/50 mt-0.5" x-text="a.description"></div>
                                <div class="flex items-center gap-2 mt-2">
                                    <template x-if="!a.earned && a.progress !== undefined">
                                        <div class="flex-1">
                                            <div class="h-1 bg-white/8 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full transition-all"
                                                     style="background: #009dde;"
                                                     :style="'width:' + Math.min(100, a.progress) + '%; background: #009dde;'"></div>
                                            </div>
                                        </div>
                                    </template>
                                    <span class="text-xs text-yellow-400 ml-auto" x-text="'+' + (a.points || 0) + ' Punkte'"></span>
                                    <template x-if="a.earned">
                                        <span class="text-xs" style="color: #009dde;">✓</span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Leaderboard -->
        <div class="glass-card rounded-2xl p-5">
            <h3 class="text-sm font-heading font-semibold text-white mb-4">Rangliste</h3>
            <div class="space-y-3">
                <template x-for="(u, idx) in leaderboard" :key="u.id">
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                             :class="{
                                'bg-yellow-400/20 text-yellow-400': idx === 0,
                                'bg-gray-300/20 text-gray-300': idx === 1,
                                'bg-amber-600/20 text-amber-600': idx === 2,
                                'bg-white/8 text-white/50': idx > 2,
                             }"
                             x-text="idx + 1"></div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-white truncate" x-text="u.name || (u.first_name + ' ' + u.last_name)"></div>
                            <div class="text-xs text-white/50" x-text="'Level ' + (u.level || 1)"></div>
                        </div>
                        <div class="text-sm font-medium text-yellow-400" x-text="(u.points || 0) + ' P.'"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function achievementsApp() {
    return {
        achievements: [],
        leaderboard: [],
        stats: {},

        async init() {
            const [ar, lr, sr] = await Promise.all([
                fetch('/api/achievements').then(r => r.json()),
                fetch('/api/challenges/leaderboard').then(r => r.json()),
                fetch('/api/achievements/stats').then(r => r.json()),
            ]);
            this.achievements = Array.isArray(ar) ? ar : [];
            this.leaderboard  = Array.isArray(lr) ? lr : [];
            this.stats        = sr || {};
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

<?php
$pageTitle = 'Challenges';
require __DIR__ . '/_layout.php';
$isAdmin = in_array($currentUser['role'] ?? '', ['CHEF', 'admin', 'owner']);
?>

<div x-data="challengesApp()" x-init="init()">

    <!-- Header with points summary -->
    <div class="flex items-center gap-4 mb-6">
        <div class="flex-1">
            <!-- Level & Points Banner -->
            <div class="glass-card rounded-2xl p-5 flex items-center gap-6" x-show="stats.level">
                <div class="text-center flex-shrink-0">
                    <div class="text-5xl font-black font-heading" style="color: #009dde;" x-text="stats.level || 1"></div>
                    <div class="text-xs text-white/40 uppercase tracking-wide mt-1">Level</div>
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-white/80" x-text="levelTitle(stats.level || 1)"></span>
                        <span class="text-sm font-bold text-yellow-400" x-text="(stats.total_points || 0).toLocaleString('de-DE') + ' Punkte'"></span>
                    </div>
                    <div class="h-2.5 bg-white/8 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700"
                             style="background: linear-gradient(90deg, #009dde, #7c3aed);"
                             :style="'width:' + levelProgress() + '%'"></div>
                    </div>
                    <div class="flex justify-between text-xs text-white/40 mt-1.5">
                        <span x-text="levelThreshold(stats.level || 1).toLocaleString('de-DE') + ' P.'"></span>
                        <span x-text="levelThreshold((stats.level || 1) + 1).toLocaleString('de-DE') + ' P.'"></span>
                    </div>
                </div>
                <div class="text-center flex-shrink-0">
                    <div class="text-3xl font-bold" x-text="stats.streak_days || 0"></div>
                    <div class="text-xs text-white/40 mt-1">🔥 Streak</div>
                </div>
                <div class="text-center flex-shrink-0">
                    <div class="text-3xl font-bold text-yellow-400" x-text="stats.earned_achievements || 0"></div>
                    <div class="text-xs text-white/40 mt-1">🏆 Abzeichen</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-white/10 mb-6">
        <div class="flex gap-1">
            <button @click="tab = 'rewards'"
                    class="px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px"
                    :class="tab === 'rewards' ? 'text-[#009dde] border-[#009dde]' : 'text-white/40 border-transparent hover:text-white'">
                🎁 Belohnungen
            </button>
            <button @click="tab = 'achievements'"
                    class="px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px"
                    :class="tab === 'achievements' ? 'text-[#009dde] border-[#009dde]' : 'text-white/40 border-transparent hover:text-white'">
                🏆 Achievements
            </button>
            <button @click="tab = 'earn'"
                    class="px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px"
                    :class="tab === 'earn' ? 'text-[#009dde] border-[#009dde]' : 'text-white/40 border-transparent hover:text-white'">
                ⭐ Punkte sammeln
            </button>
            <button @click="tab = 'leaderboard'"
                    class="px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px"
                    :class="tab === 'leaderboard' ? 'text-[#009dde] border-[#009dde]' : 'text-white/40 border-transparent hover:text-white'">
                📊 Rangliste
            </button>
            <?php if ($isAdmin): ?>
            <button @click="tab = 'admin'"
                    class="px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px"
                    :class="tab === 'admin' ? 'text-[#009dde] border-[#009dde]' : 'text-white/40 border-transparent hover:text-white'">
                ⚙️ Admin
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== BELOHNUNGEN TAB ===== -->
    <div x-show="tab === 'rewards'">
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-white/50">Löse Punkte gegen Belohnungen ein</p>
            <div class="text-sm font-medium px-3 py-1.5 rounded-xl" style="background: rgba(255,204,0,0.1); color: #ffcc00;">
                <span x-text="(stats.total_points || 0).toLocaleString('de-DE')"></span> Punkte verfügbar
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="r in rewards" :key="r.id">
                <div class="glass-card rounded-2xl p-5 flex flex-col gap-3 transition-all hover:border-white/15"
                     :class="(stats.total_points || 0) >= r.points_cost ? '' : 'opacity-60'">
                    <div class="text-4xl" x-text="r.icon || '🎁'"></div>
                    <div>
                        <div class="font-heading font-semibold text-white text-base" x-text="r.name"></div>
                        <div class="text-xs text-white/50 mt-1 line-clamp-2" x-text="r.description || ''"></div>
                    </div>
                    <div class="flex items-center justify-between mt-auto pt-2 border-t border-white/8">
                        <div class="text-sm font-bold text-yellow-400" x-text="r.points_cost.toLocaleString('de-DE') + ' P.'"></div>
                        <div class="text-xs text-white/40" x-show="r.stock > 0" x-text="'Noch ' + r.stock + 'x'"></div>
                        <div class="text-xs text-white/40" x-show="r.stock === 0">Ausverkauft</div>
                    </div>
                    <button @click="redeemReward(r)"
                            :disabled="(stats.total_points || 0) < r.points_cost || r.stock === 0"
                            class="w-full py-2 rounded-xl text-sm font-semibold transition-all"
                            :class="(stats.total_points || 0) >= r.points_cost && r.stock !== 0
                                ? 'text-white hover:opacity-90 cursor-pointer'
                                : 'text-white/30 cursor-not-allowed bg-white/5'"
                            :style="(stats.total_points || 0) >= r.points_cost && r.stock !== 0
                                ? 'background: linear-gradient(135deg, #009dde, #0070a8);'
                                : ''">
                        Einlösen
                    </button>
                </div>
            </template>
            <template x-if="rewards.length === 0">
                <div class="col-span-3 text-center py-12 text-white/40">
                    <div class="text-4xl mb-3">🎁</div>
                    <p>Noch keine Belohnungen verfügbar</p>
                </div>
            </template>
        </div>

        <!-- My Redemptions -->
        <div class="mt-8" x-show="redemptions.length > 0">
            <h3 class="text-sm font-semibold text-white/40 uppercase tracking-wider mb-3">Meine Einlösungen</h3>
            <div class="space-y-2">
                <template x-for="red in redemptions" :key="red.id">
                    <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white/4">
                        <div class="flex-1 text-sm text-white/70" x-text="red.reward_name"></div>
                        <div class="text-xs text-yellow-400" x-text="'-' + red.points_spent + ' P.'"></div>
                        <span class="text-xs px-2 py-0.5 rounded-full"
                              :class="{
                                'bg-yellow-500/20 text-yellow-400': red.status === 'ausstehend',
                                'bg-blue-500/20 text-blue-400': red.status === 'bearbeitet',
                                'bg-green-500/20 text-green-400': red.status === 'abgeschlossen',
                              }"
                              x-text="{ausstehend:'Ausstehend',bearbeitet:'In Bearbeitung',abgeschlossen:'Abgeschlossen'}[red.status] || red.status"></span>
                        <div class="text-xs text-white/30" x-text="formatDate(red.redeemed_at)"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- ===== ACHIEVEMENTS TAB ===== -->
    <div x-show="tab === 'achievements'">
        <!-- Category filter -->
        <div class="flex gap-2 mb-4 flex-wrap">
            <button @click="achievFilter = ''"
                    class="px-3 py-1.5 rounded-xl text-xs font-medium transition-colors"
                    :class="achievFilter === '' ? 'text-white' : 'text-white/40 hover:text-white bg-white/5'"
                    :style="achievFilter === '' ? 'background: #009dde;' : ''">Alle</button>
            <template x-for="cat in achievCategories" :key="cat.key">
                <button @click="achievFilter = cat.key"
                        class="px-3 py-1.5 rounded-xl text-xs font-medium transition-colors"
                        :class="achievFilter === cat.key ? 'text-white' : 'text-white/40 hover:text-white bg-white/5'"
                        :style="achievFilter === cat.key ? 'background: #009dde;' : ''"
                        x-text="cat.label"></button>
            </template>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <template x-for="a in filteredAchievements" :key="a.id">
                <div class="glass-card rounded-2xl p-4 flex gap-4 transition-all"
                     :class="a.earned ? 'border-[#009dde]/30' : 'opacity-55'">
                    <div class="text-4xl flex-shrink-0 self-start mt-0.5" x-text="a.icon || '🏆'"></div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-sm" :class="a.earned ? 'text-white' : 'text-white/50'" x-text="a.name || a.title"></div>
                        <div class="text-xs text-white/40 mt-0.5 line-clamp-2" x-text="a.description"></div>

                        <!-- Progress bar for unearned -->
                        <template x-if="!a.earned && a.progress !== undefined">
                            <div class="mt-2">
                                <div class="h-1.5 bg-white/8 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all"
                                         style="background: #009dde;"
                                         :style="'width:' + Math.min(100, (a.progress / (a.requirement_value || 1)) * 100) + '%'"></div>
                                </div>
                                <div class="text-xs text-white/30 mt-1" x-text="a.progress + ' / ' + (a.requirement_value || '?')"></div>
                            </div>
                        </template>

                        <div class="flex items-center justify-between mt-2">
                            <span class="text-xs text-yellow-400" x-text="'+' + (a.points_reward || a.points || 0) + ' Punkte'"></span>
                            <template x-if="a.earned">
                                <span class="text-xs px-2 py-0.5 rounded-full" style="background: rgba(0,157,222,0.15); color: #009dde;">
                                    ✓ Erreicht
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- ===== PUNKTE SAMMELN TAB ===== -->
    <div x-show="tab === 'earn'">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- How to earn points -->
            <div>
                <h3 class="text-sm font-semibold text-white/40 uppercase tracking-wider mb-4">So sammelst du Punkte</h3>
                <div class="space-y-3">
                    <template x-for="rule in pointRules" :key="rule.id || rule.action_type">
                        <div class="flex items-center gap-4 px-4 py-3 rounded-xl bg-white/4 border border-white/8">
                            <div class="text-2xl flex-shrink-0" x-text="actionIcon(rule.action_type)"></div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-white" x-text="actionLabel(rule.action_type)"></div>
                                <div class="text-xs text-white/40 mt-0.5" x-text="rule.description || ''"></div>
                            </div>
                            <div class="text-sm font-bold text-yellow-400 flex-shrink-0" x-text="'+' + rule.points + ' P.'"></div>
                        </div>
                    </template>
                    <!-- Default rules if none loaded -->
                    <template x-if="pointRules.length === 0">
                        <template x-for="rule in defaultRules" :key="rule.key">
                            <div class="flex items-center gap-4 px-4 py-3 rounded-xl bg-white/4 border border-white/8">
                                <div class="text-2xl" x-text="rule.icon"></div>
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-white" x-text="rule.label"></div>
                                    <div class="text-xs text-white/40" x-text="rule.desc"></div>
                                </div>
                                <div class="text-sm font-bold text-yellow-400" x-text="'+' + rule.points + ' P.'"></div>
                            </div>
                        </template>
                    </template>
                </div>
            </div>

            <!-- Level overview -->
            <div>
                <h3 class="text-sm font-semibold text-white/40 uppercase tracking-wider mb-4">Level-Übersicht</h3>
                <div class="space-y-2">
                    <template x-for="lvl in levelList" :key="lvl.level">
                        <div class="flex items-center gap-4 px-4 py-3 rounded-xl transition-all"
                             :class="lvl.level === (stats.level || 1)
                                ? 'bg-[#009dde]/10 border border-[#009dde]/30'
                                : (stats.level || 1) > lvl.level ? 'bg-white/3 opacity-60' : 'bg-white/4 border border-white/8'">
                            <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-bold flex-shrink-0"
                                 :style="lvl.level === (stats.level || 1) ? 'background: #009dde; color: #fff;' : 'background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.5);'"
                                 x-text="lvl.level"></div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-white" x-text="lvl.title"></div>
                                <div class="text-xs text-white/40" x-text="'ab ' + lvl.threshold.toLocaleString('de-DE') + ' Punkten'"></div>
                            </div>
                            <template x-if="(stats.level || 1) > lvl.level">
                                <span class="text-xs text-green-400">✓</span>
                            </template>
                            <template x-if="lvl.level === (stats.level || 1)">
                                <span class="text-xs px-2 py-0.5 rounded-full text-[#009dde]" style="background: rgba(0,157,222,0.15);">Aktuell</span>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- My recent points history -->
        <div class="mt-8">
            <h3 class="text-sm font-semibold text-white/40 uppercase tracking-wider mb-4">Letzte Transaktionen</h3>
            <div class="space-y-2">
                <template x-for="tx in pointHistory" :key="tx.id">
                    <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white/4">
                        <div class="text-xl flex-shrink-0" x-text="actionIcon(tx.reason)"></div>
                        <div class="flex-1 text-sm text-white/70" x-text="actionLabel(tx.reason)"></div>
                        <div class="text-sm font-bold" :class="tx.points > 0 ? 'text-yellow-400' : 'text-red-400'"
                             x-text="(tx.points > 0 ? '+' : '') + tx.points + ' P.'"></div>
                        <div class="text-xs text-white/30" x-text="formatDate(tx.created_at)"></div>
                    </div>
                </template>
                <template x-if="pointHistory.length === 0">
                    <p class="text-center text-white/40 text-sm py-6">Noch keine Punkte-Aktivität</p>
                </template>
            </div>
        </div>
    </div>

    <!-- ===== RANGLISTE TAB ===== -->
    <div x-show="tab === 'leaderboard'">
        <!-- Top 3 podium -->
        <div class="flex items-end justify-center gap-4 mb-8" x-show="leaderboard.length >= 3">
            <!-- 2nd place -->
            <template x-if="leaderboard[1]">
                <div class="text-center flex-1 max-w-[140px]">
                    <div class="text-4xl mb-2">🥈</div>
                    <div class="w-16 h-16 rounded-2xl mx-auto mb-2 flex items-center justify-center text-white font-bold text-lg"
                         :style="'background: linear-gradient(135deg, ' + (leaderboard[1].color || '#6b7280') + '50, ' + (leaderboard[1].color || '#6b7280') + '20);'"
                         x-text="(leaderboard[1].first_name || '?').charAt(0)"></div>
                    <div class="text-sm font-medium text-white truncate" x-text="leaderboard[1].first_name + ' ' + (leaderboard[1].last_name?.charAt(0) || '') + '.'"></div>
                    <div class="text-xs text-white/50 mt-0.5" x-text="(leaderboard[1].total_points || 0).toLocaleString('de-DE') + ' P.'"></div>
                    <div class="h-16 bg-gray-400/20 rounded-t-xl mt-2"></div>
                </div>
            </template>
            <!-- 1st place -->
            <template x-if="leaderboard[0]">
                <div class="text-center flex-1 max-w-[140px]">
                    <div class="text-4xl mb-2">🥇</div>
                    <div class="w-20 h-20 rounded-2xl mx-auto mb-2 flex items-center justify-center text-white font-bold text-xl border-2"
                         style="border-color: #ffcc00;"
                         :style="'background: linear-gradient(135deg, ' + (leaderboard[0].color || '#009dde') + '80, ' + (leaderboard[0].color || '#009dde') + '30);'"
                         x-text="(leaderboard[0].first_name || '?').charAt(0)"></div>
                    <div class="text-sm font-semibold text-white truncate" x-text="leaderboard[0].first_name + ' ' + (leaderboard[0].last_name?.charAt(0) || '') + '.'"></div>
                    <div class="text-xs text-yellow-400 mt-0.5 font-medium" x-text="(leaderboard[0].total_points || 0).toLocaleString('de-DE') + ' P.'"></div>
                    <div class="h-24 rounded-t-xl mt-2" style="background: rgba(255,204,0,0.15);"></div>
                </div>
            </template>
            <!-- 3rd place -->
            <template x-if="leaderboard[2]">
                <div class="text-center flex-1 max-w-[140px]">
                    <div class="text-4xl mb-2">🥉</div>
                    <div class="w-14 h-14 rounded-2xl mx-auto mb-2 flex items-center justify-center text-white font-bold"
                         :style="'background: linear-gradient(135deg, ' + (leaderboard[2].color || '#6b7280') + '50, ' + (leaderboard[2].color || '#6b7280') + '20);'"
                         x-text="(leaderboard[2].first_name || '?').charAt(0)"></div>
                    <div class="text-sm font-medium text-white truncate" x-text="leaderboard[2].first_name + ' ' + (leaderboard[2].last_name?.charAt(0) || '') + '.'"></div>
                    <div class="text-xs text-white/50 mt-0.5" x-text="(leaderboard[2].total_points || 0).toLocaleString('de-DE') + ' P.'"></div>
                    <div class="h-10 bg-amber-600/20 rounded-t-xl mt-2"></div>
                </div>
            </template>
        </div>

        <!-- Full list -->
        <div class="glass-card rounded-2xl overflow-hidden">
            <div class="px-4 py-3 border-b border-white/8 text-xs font-semibold text-white/40 uppercase tracking-wider grid grid-cols-[40px_1fr_80px_80px_80px]">
                <div>#</div><div>Name</div><div class="text-center">Level</div><div class="text-right">Streak</div><div class="text-right">Punkte</div>
            </div>
            <template x-for="(u, idx) in leaderboard" :key="u.id">
                <div class="px-4 py-3.5 border-b border-white/5 last:border-0 grid grid-cols-[40px_1fr_80px_80px_80px] items-center hover:bg-white/3 transition-colors"
                     :class="u.id === '<?= htmlspecialchars($currentUser['id'] ?? '') ?>' ? 'bg-[#009dde]/5' : ''">
                    <div class="text-sm font-bold"
                         :class="{
                            'text-yellow-400': idx === 0,
                            'text-gray-300': idx === 1,
                            'text-amber-600': idx === 2,
                            'text-white/40': idx > 2,
                         }"
                         x-text="idx + 1"></div>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                             :style="'background: linear-gradient(135deg, ' + (u.color || '#009dde') + ', ' + (u.color || '#0070a8') + '70);'"
                             x-text="(u.first_name || '?').charAt(0)"></div>
                        <div>
                            <div class="text-sm font-medium text-white" x-text="u.first_name + ' ' + (u.last_name || '')"></div>
                            <div class="text-xs text-white/40" x-text="u.role || ''"></div>
                        </div>
                        <template x-if="u.id === '<?= htmlspecialchars($currentUser['id'] ?? '') ?>'">
                            <span class="text-xs px-1.5 py-0.5 rounded" style="background: rgba(0,157,222,0.15); color: #009dde;">Du</span>
                        </template>
                    </div>
                    <div class="text-center text-sm text-white/70" x-text="'Lvl ' + (u.level || 1)"></div>
                    <div class="text-right text-sm text-orange-400" x-text="(u.streak_days || 0) + '🔥'"></div>
                    <div class="text-right text-sm font-bold text-yellow-400" x-text="(u.total_points || 0).toLocaleString('de-DE')"></div>
                </div>
            </template>
        </div>
    </div>

    <!-- ===== ADMIN TAB ===== -->
    <?php if ($isAdmin): ?>
    <div x-show="tab === 'admin'">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Manage Rewards -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-white/40 uppercase tracking-wider">Belohnungen verwalten</h3>
                    <button @click="openRewardForm()"
                            class="btn-primary text-xs px-3 py-1.5">+ Belohnung</button>
                </div>
                <div class="space-y-2">
                    <template x-for="r in rewards" :key="r.id">
                        <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-white/4 border border-white/8">
                            <span x-text="r.icon || '🎁'" class="text-xl flex-shrink-0"></span>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm text-white truncate" x-text="r.name"></div>
                                <div class="text-xs text-white/40" x-text="r.points_cost + ' Punkte'"></div>
                            </div>
                            <button @click="deleteReward(r.id)" class="text-white/30 hover:text-red-400 transition-colors p-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Add reward form (inline) -->
                <div x-show="rewardForm" x-cloak class="mt-4 p-4 rounded-xl border border-white/10 bg-white/3 space-y-3">
                    <div class="flex gap-2">
                        <input x-model="newReward.icon" type="text" placeholder="🎁" class="input-field w-14 text-center text-lg px-2">
                        <input x-model="newReward.name" type="text" placeholder="Name der Belohnung" class="input-field flex-1">
                    </div>
                    <textarea x-model="newReward.description" rows="2" placeholder="Beschreibung..." class="input-field resize-none"></textarea>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-white/40 mb-1 block">Punktekosten</label>
                            <input x-model="newReward.points_cost" type="number" min="1" class="input-field">
                        </div>
                        <div>
                            <label class="text-xs text-white/40 mb-1 block">Lagerbestand (-1 = ∞)</label>
                            <input x-model="newReward.stock" type="number" min="-1" value="-1" class="input-field">
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button @click="saveReward()" class="btn-primary text-sm">Speichern</button>
                        <button @click="rewardForm = false" class="btn-ghost text-sm">Abbrechen</button>
                    </div>
                </div>
            </div>

            <!-- Manage Point Rules -->
            <div>
                <h3 class="text-sm font-semibold text-white/40 uppercase tracking-wider mb-4">Punkt-Regeln</h3>
                <div class="space-y-2">
                    <template x-for="rule in pointRules" :key="rule.id || rule.action_type">
                        <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-white/4 border border-white/8">
                            <span class="text-xl flex-shrink-0" x-text="actionIcon(rule.action_type)"></span>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm text-white" x-text="actionLabel(rule.action_type)"></div>
                            </div>
                            <input x-model.number="rule.points" type="number" min="0"
                                   class="w-16 text-right bg-white/5 border border-white/10 rounded-lg px-2 py-1 text-sm text-yellow-400 font-bold"
                                   @change="updatePointRule(rule)">
                            <span class="text-xs text-white/40">P.</span>
                        </div>
                    </template>
                </div>

                <!-- Pending Redemptions -->
                <div class="mt-6">
                    <h3 class="text-sm font-semibold text-white/40 uppercase tracking-wider mb-3">Offene Einlösungen</h3>
                    <div class="space-y-2">
                        <template x-for="red in allRedemptions.filter(r => r.status === 'ausstehend')" :key="red.id">
                            <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-yellow-500/5 border border-yellow-500/20">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm text-white" x-text="red.user_name + ': ' + red.reward_name"></div>
                                    <div class="text-xs text-white/40" x-text="formatDate(red.redeemed_at)"></div>
                                </div>
                                <button @click="processRedemption(red.id, 'bearbeitet')"
                                        class="text-xs px-2 py-1 rounded-lg bg-blue-500/20 text-blue-400">Bearbeiten</button>
                                <button @click="processRedemption(red.id, 'abgeschlossen')"
                                        class="text-xs px-2 py-1 rounded-lg bg-green-500/20 text-green-400">✓ Fertig</button>
                            </div>
                        </template>
                        <template x-if="allRedemptions.filter(r => r.status === 'ausstehend').length === 0">
                            <p class="text-center text-white/30 text-sm py-4">Keine offenen Einlösungen</p>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
function challengesApp() {
    return {
        tab: 'rewards',
        stats: {},
        rewards: [],
        redemptions: [],
        allRedemptions: [],
        achievements: [],
        leaderboard: [],
        pointRules: [],
        pointHistory: [],
        achievFilter: '',
        rewardForm: false,
        newReward: { icon: '🎁', name: '', description: '', points_cost: 100, stock: -1 },

        achievCategories: [
            { key: 'tasks', label: '✅ Tasks' },
            { key: 'time', label: '⏱️ Zeit' },
            { key: 'streak', label: '🔥 Streak' },
            { key: 'special', label: '⭐ Special' },
        ],

        defaultRules: [
            { key: 'task_completed', icon: '✅', label: 'Task erledigt', desc: 'Pro abgeschlossener Aufgabe', points: 10 },
            { key: 'daily_login', icon: '🌅', label: 'Täglicher Login', desc: 'Jeden Tag einloggen', points: 5 },
            { key: 'streak_bonus_7', icon: '🔥', label: '7-Tage Streak', desc: '7 Tage in Folge aktiv', points: 50 },
            { key: 'streak_bonus_30', icon: '💎', label: '30-Tage Streak', desc: '30 Tage in Folge aktiv', points: 200 },
            { key: 'time_logged_hour', icon: '⏱️', label: 'Zeit erfasst', desc: 'Pro erfasster Arbeitsstunde', points: 2 },
            { key: 'project_completed', icon: '🚀', label: 'Projekt abgeschlossen', desc: 'Pro abgeschlossenem Projekt', points: 100 },
        ],

        levelList: [
            { level: 1, title: 'Neuling', threshold: 0 },
            { level: 2, title: 'Aufsteiger', threshold: 100 },
            { level: 3, title: 'Profi', threshold: 300 },
            { level: 4, title: 'Experte', threshold: 600 },
            { level: 5, title: 'Meister', threshold: 1000 },
            { level: 6, title: 'Champion', threshold: 1500 },
            { level: 7, title: 'Legende', threshold: 2200 },
            { level: 8, title: 'Grandmaster', threshold: 3000 },
        ],

        get filteredAchievements() {
            if (!this.achievFilter) return this.achievements;
            return this.achievements.filter(a => (a.category || '') === this.achievFilter);
        },

        levelThreshold(level) {
            const found = this.levelList.find(l => l.level === level);
            if (found) return found.threshold;
            return 50 * level * (level + 1);
        },

        levelProgress() {
            const lvl = this.stats.level || 1;
            const cur = this.stats.total_points || 0;
            const from = this.levelThreshold(lvl);
            const to = this.levelThreshold(lvl + 1);
            if (to <= from) return 100;
            return Math.min(100, Math.round(((cur - from) / (to - from)) * 100));
        },

        levelTitle(level) {
            const found = this.levelList.find(l => l.level === level);
            return found ? found.title : 'Level ' + level;
        },

        actionIcon(type) {
            const icons = {
                task_completed: '✅', daily_login: '🌅', streak_bonus_7: '🔥',
                streak_bonus_30: '💎', time_logged_hour: '⏱️', project_completed: '🚀',
                vacation_approved: '🌴', reward_redeemed: '🎁', achievement_unlocked: '🏆',
            };
            return icons[type] || '⭐';
        },

        actionLabel(type) {
            const labels = {
                task_completed: 'Task erledigt', daily_login: 'Täglicher Login',
                streak_bonus_7: '7-Tage Streak Bonus', streak_bonus_30: '30-Tage Streak Bonus',
                time_logged_hour: 'Stunde erfasst', project_completed: 'Projekt abgeschlossen',
                vacation_approved: 'Urlaub genehmigt', reward_redeemed: 'Belohnung eingelöst',
                achievement_unlocked: 'Achievement freigeschaltet',
            };
            return labels[type] || type;
        },

        async init() {
            const [sr, rr, ach, lb, pr, ph, rd] = await Promise.all([
                fetch('/api/achievements/stats').then(r => r.json()).catch(() => ({})),
                fetch('/api/challenges/rewards').then(r => r.json()).catch(() => []),
                fetch('/api/achievements').then(r => r.json()).catch(() => []),
                fetch('/api/challenges/leaderboard').then(r => r.json()).catch(() => []),
                fetch('/api/challenges/point-rules').then(r => r.json()).catch(() => []),
                fetch('/api/challenges/my-points').then(r => r.json()).catch(() => ({})),
                fetch('/api/challenges/my-redemptions').then(r => r.json()).catch(() => []),
            ]);
            this.stats = sr || {};
            this.rewards = Array.isArray(rr) ? rr : [];
            this.achievements = Array.isArray(ach) ? ach : [];
            this.leaderboard = Array.isArray(lb) ? lb : [];
            this.pointRules = Array.isArray(pr) ? pr : [];
            this.pointHistory = Array.isArray(ph.history) ? ph.history : [];
            this.redemptions = Array.isArray(rd) ? rd : [];
            <?php if ($isAdmin): ?>
            fetch('/api/challenges/admin/redemptions').then(r => r.json()).then(d => {
                this.allRedemptions = Array.isArray(d) ? d : [];
            }).catch(() => {});
            <?php endif; ?>
        },

        async redeemReward(r) {
            if (!confirm('Möchtest du "' + r.name + '" für ' + r.points_cost + ' Punkte einlösen?')) return;
            const res = await fetch('/api/challenges/redeem/' + r.id, { method: 'POST' });
            const d = await res.json();
            if (res.ok) {
                this.stats.total_points = (this.stats.total_points || 0) - r.points_cost;
                if (r.stock > 0) r.stock--;
                if (d.redemption) this.redemptions.unshift(d.redemption);
                alert('✅ Erfolgreich eingelöst!');
            } else {
                alert(d.detail || d.message || 'Fehler beim Einlösen');
            }
        },

        openRewardForm() {
            this.newReward = { icon: '🎁', name: '', description: '', points_cost: 100, stock: -1 };
            this.rewardForm = true;
        },

        async saveReward() {
            if (!this.newReward.name) return alert('Name erforderlich');
            const r = await fetch('/api/challenges/rewards', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(this.newReward)
            });
            if (r.ok) {
                const d = await r.json();
                this.rewards.push(d);
                this.rewardForm = false;
            }
        },

        async deleteReward(id) {
            if (!confirm('Belohnung löschen?')) return;
            await fetch('/api/challenges/rewards/' + id, { method: 'DELETE' });
            this.rewards = this.rewards.filter(r => r.id !== id);
        },

        async updatePointRule(rule) {
            await fetch('/api/challenges/point-rules/' + rule.id, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ points: rule.points })
            }).catch(() => {});
        },

        async processRedemption(id, status) {
            await fetch('/api/challenges/redemptions/' + id, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status })
            });
            const i = this.allRedemptions.findIndex(r => r.id === id);
            if (i !== -1) this.allRedemptions[i].status = status;
        },

        formatDate(d) {
            if (!d) return '';
            return new Date(d).toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: '2-digit' });
        }
    };
}
</script>

<?php require __DIR__ . '/_layout_end.php'; ?>

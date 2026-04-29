<script setup>
import { ref, computed, onMounted } from "vue";
import { Head, router, Link } from "@inertiajs/vue3";
import MainLayout from "../../Layouts/MainLayout.vue";
import dayjs from "dayjs";
import StandingTable from "../../Components/StandingTable.vue";

const props = defineProps({
    team: { type: Object, required: true },
    recentGames: { type: Array, default: () => [] },
    upcomingGames: { type: Array, default: () => [] },
    squad: { type: Object, default: () => ({}) },
    standings: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    lastLineup: { type: Object, default: null },
    coachHistory: { type: Array, default: () => [] },
});

const activeTab = ref("overview");
const tabs = [
    { id: "overview", label: "Tổng quan" },
    { id: "standings", label: "BXH" },
    { id: "matches", label: "Trận đấu" },
    { id: "squad", label: "Đội hình" },
    { id: "stats", label: "Thống kê" },
];

const changeTab = (id) => {
    activeTab.value = id;
    const url = new URL(window.location);
    url.searchParams.set('tab', id);
    window.history.replaceState({}, '', url);
};

const formatDate = (dateStr) => dayjs(dateStr).format("DD/MM/YYYY");
const formatTime = (dateStr) => dayjs(dateStr).format("HH:mm");

const getMatchResultText = (game) => {
    if (game.home_score === null) return "-";
    const isHome = game.home_team_id === props.team.id;
    const teamScore = isHome ? game.home_score : game.away_score;
    const opponentScore = isHome ? game.away_score : game.home_score;

    if (teamScore > opponentScore) return "W";
    if (teamScore < opponentScore) return "L";
    return "D";
};

const getMatchResultClass = (game) => {
    const res = getMatchResultText(game);
    if (res === "W") return "bg-emerald-500 text-white";
    if (res === "L") return "bg-red-500 text-white";
    if (res === "D") return "bg-amber-500 text-white";
    return "bg-gray-100 dark:bg-gray-800 text-gray-400";
};

const nextMatch = computed(() => props.upcomingGames[0] || null);
const last5Games = computed(() => [...props.recentGames].slice(0, 5).reverse());

const viewMatch = (id) => router.visit(`/matches/${id}`);
const viewPlayer = (id) => router.visit(`/players/${id}`);

const getRating = (stat) => {
    return stat.detailed_stats?.[0]?.games?.rating || "N/A";
};

const getRatingClass = (rating) => {
    const val = parseFloat(rating);
    if (isNaN(val)) return "bg-gray-400";
    if (val >= 7.5) return "bg-emerald-500";
    if (val >= 6.5) return "bg-amber-500";
    return "bg-red-500";
};

const calcAge = (birthYear) => {
    if (!birthYear) return '-';
    return new Date().getFullYear() - birthYear;
};

const positionLabels = {
    Goalkeeper: "Thủ môn",
    Defender: "Hậu vệ",
    Midfielder: "Tiền vệ",
    Attacker: "Tiền đạo",
    Forward: "Tiền đạo",
    Centre: "Trung tâm",
};

const positionShort = {
    Goalkeeper: "TM",
    Defender: "HV",
    Midfielder: "TV",
    Attacker: "TĐ",
};

const showChart = ref(false);
onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const tabFromUrl = params.get('tab');
    if (tabFromUrl && tabs.find(t => t.id === tabFromUrl)) {
        activeTab.value = tabFromUrl;
    }

    setTimeout(() => {
        showChart.value = true;
    }, 100);
});

// Cấu trúc Pitch dựa trên grid [hàng, cột]
const getPitchPlayers = computed(() => {
    if (!props.lastLineup?.startXI) return [];
    return props.lastLineup.startXI.map(xi => ({
        ...xi,
        gridPos: xi.player.grid ? xi.player.grid.split(':').map(Number) : [1, 1]
    }));
});
</script>

<template>
    <Head :title="`${team.name} | AI Soccer`" />

    <MainLayout>
        <div class="py-6 space-y-6">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                <Link href="/" class="hover:text-emerald-500">Trang chủ</Link>
                <span>/</span>
                <span>{{ team.country || 'Đội bóng' }}</span>
                <span>/</span>
                <span class="text-gray-900 dark:text-gray-200">{{ team.name }}</span>
            </nav>

            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 mb-6 border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden">
                <div class="flex flex-col md:flex-row items-center gap-6 relative z-10">
                    <div class="w-20 h-20 md:w-24 md:h-24 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-700 flex items-center justify-center p-4 shadow-inner">
                        <img v-if="team.logo" :src="team.logo" class="w-full h-full object-contain filter drop-shadow-md" />
                    </div>

                    <div class="flex-1 text-center md:text-left">
                        <div class="flex items-center justify-center md:justify-start gap-3 mb-2">
                             <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ team.name }}</h1>
                             <div class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-[9px] font-bold uppercase tracking-widest text-gray-500">Anh</div>
                        </div>
                        
                        <div class="flex flex-wrap justify-center md:justify-start gap-4">
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button class="h-10 px-6 bg-gray-950 dark:bg-white text-white dark:text-gray-950 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all hover:scale-105 shadow-lg active:scale-95">
                            Theo dõi
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex bg-white dark:bg-gray-800 p-1.5 rounded-2xl border border-gray-100 dark:border-gray-700 mb-6 inline-flex overflow-x-auto no-scrollbar max-w-full shadow-sm">
                <button v-for="tab in tabs" :key="tab.id" @click="changeTab(tab.id)"
                    class="px-5 py-2.5 text-[9px] font-bold uppercase tracking-widest transition-all rounded-xl whitespace-nowrap"
                    :class="activeTab === tab.id ? 'bg-emerald-500 text-white shadow-md' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-200'">
                    {{ tab.label }}
                </button>
            </div>

            <!-- Content Area -->
            <div class="min-h-[600px]">
                <!-- OVERVIEW TAB -->
                <div v-if="activeTab === 'overview'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- Left Column -->
                    <div class="lg:col-span-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Phong độ -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col">
                                <h3 class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-6">Phong độ đội bóng</h3>
                                <div class="flex items-center justify-between gap-2 flex-1">
                                    <div v-for="(game, idx) in last5Games" :key="idx" class="flex flex-col items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-[9px] font-bold shadow-md"
                                             :class="getMatchResultClass(game)">
                                            {{ getMatchResultText(game) }}
                                        </div>
                                        <span class="text-[8px] font-bold text-gray-400 tabular-nums">{{ game.home_score }}-{{ game.away_score }}</span>
                                        <div class="w-8 h-8 flex items-center justify-center mt-1 border-t border-gray-50 dark:border-gray-700 pt-3 w-full">
                                             <img :src="game.home_team_id === team.id ? game.away_team?.logo : game.home_team?.logo" class="w-5 h-5 object-contain" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Next Match -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm transition-all relative flex flex-col">
                                <h3 class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-6">Trận đấu tiếp theo</h3>
                                
                                <div v-if="nextMatch" @click="viewMatch(nextMatch.id)" class="flex items-center justify-between gap-4 cursor-pointer group">
                                    <div class="flex flex-col items-center gap-2 flex-1">
                                        <div class="w-10 h-10 p-2 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-700">
                                             <img :src="nextMatch.home_team?.logo" class="w-full h-full object-contain" />
                                        </div>
                                        <span class="text-[9px] font-bold text-gray-900 dark:text-white text-center truncate uppercase tracking-tight w-full">{{ nextMatch.home_team?.name }}</span>
                                    </div>
                                    <div class="flex flex-col items-center">
                                        <span class="text-xl font-bold text-gray-950 dark:text-white tabular-nums">{{ formatTime(nextMatch.match_at) }}</span>
                                        <span class="text-[8px] font-bold text-gray-400 mt-1 uppercase tracking-widest">{{ formatDate(nextMatch.match_at) }}</span>
                                    </div>
                                    <div class="flex flex-col items-center gap-2 flex-1">
                                        <div class="w-10 h-10 p-2 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-700">
                                             <img :src="nextMatch.away_team?.logo" class="w-full h-full object-contain" />
                                        </div>
                                        <span class="text-[9px] font-bold text-gray-900 dark:text-white text-center truncate uppercase tracking-tight w-full">{{ nextMatch.away_team?.name }}</span>
                                    </div>
                                </div>

                                <div v-else class="flex-1 flex flex-col items-center justify-center py-4 border border-dashed border-gray-100 dark:border-gray-700 rounded-xl bg-gray-50/50 dark:bg-gray-900/30">
                                    <svg class="w-6 h-6 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Chưa có lịch thi đấu</span>
                                </div>
                            </div>
                        </div>

                        <!-- Coach History Chart -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                             <div class="mb-8">
                                  <h3 class="text-[10px] font-bold text-gray-900 dark:text-white uppercase tracking-widest">Coach win percentage</h3>
                                  <p class="text-[8px] font-bold text-gray-400 uppercase tracking-widest">Points per game</p>
                             </div>

                             <div v-if="coachHistory.length > 0" class="relative h-48 mb-16 px-4 bg-gray-50/50 dark:bg-gray-900/30 rounded-xl border border-gray-100 dark:border-gray-700">
                                  <div class="flex items-end justify-between h-full relative">
                                       <div v-for="(coach, idx) in coachHistory" :key="idx" 
                                            class="flex-1 flex flex-col items-center relative h-full">
                                            
                                            <!-- Vertical Line -->
                                            <div class="absolute bottom-0 w-px bg-gray-200 dark:bg-gray-700 h-full opacity-50"></div>
                                            
                                            <!-- Floating Data Boxes -->
                                            <div class="absolute transition-all duration-[1500ms] cubic-bezier(0.34, 1.56, 0.64, 1) flex flex-col items-center gap-0 z-10"
                                                 :style="{ bottom: showChart ? `${coach.winRate}%` : '0%', opacity: showChart ? 1 : 0 }">
                                                 <!-- Red Box (%) -->
                                                 <div class="bg-red-600 text-white px-2.5 py-1.5 rounded-t-lg text-[10px] font-bold min-w-[40px] text-center shadow-lg">
                                                      {{ coach.winRate }}%
                                                 </div>
                                                 <!-- Black Box (PPG) -->
                                                 <div class="bg-gray-900 dark:bg-black text-white px-2.5 py-1 rounded-b-lg text-[8px] font-bold min-w-[40px] text-center shadow-lg border-t border-white/5">
                                                      {{ coach.ppg }} Pts
                                                 </div>
                                            </div>

                                            <!-- Coach Avatar & Info -->
                                            <div class="absolute -bottom-16 flex flex-col items-center gap-1.5 w-full">
                                                 <div class="w-10 h-10 rounded-full bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-600 shadow-sm overflow-hidden p-0.5">
                                                      <img :src="coach.photo" class="w-full h-full object-cover rounded-full" />
                                                 </div>
                                                 <div class="flex flex-col items-center leading-none">
                                                      <span class="text-[9px] font-bold text-gray-900 dark:text-white uppercase tracking-tight text-center">{{ coach.name }}</span>
                                                      <span class="text-[8px] font-bold text-gray-400 text-center mt-0.5">{{ coach.season }}</span>
                                                 </div>
                                            </div>
                                       </div>
                                  </div>
                             </div>

                             <!-- Empty State -->
                             <div v-else class="h-48 flex flex-col items-center justify-center bg-gray-50/50 dark:bg-gray-900/30 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
                                  <svg class="w-8 h-8 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                  </svg>
                                  <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center px-6">Chưa có dữ liệu thống kê huấn luyện viên</span>
                             </div>
                        </div>

                        <!-- AI Summary -->
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                             <div class="flex items-center gap-2 mb-4">
                                  <div class="w-6 h-6 bg-emerald-500 rounded-lg flex items-center justify-center text-white text-[10px] font-bold">AI</div>
                                  <h3 class="text-[10px] font-bold text-gray-900 dark:text-white uppercase tracking-widest">Tóm tắt hàng ngày</h3>
                             </div>
                             <div class="space-y-4 text-[12px] text-gray-600 dark:text-gray-400 font-medium leading-relaxed">
                                  <p><span class="text-gray-900 dark:text-white font-bold">{{ team.name }}</span> đang có phong độ ổn định. Phân tích AI dự đoán khả năng thắng trận tới là <span class="text-emerald-500 font-bold">68%</span>.</p>
                             </div>
                        </div>

                        <!-- Mini Standings -->
                        <div v-if="standings.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm relative">
                             <div class="p-5 border-b border-gray-50 dark:border-gray-700 flex items-center justify-between">
                                  <h3 class="text-[9px] font-bold uppercase tracking-widest text-gray-400">BXH {{ nextMatch?.league?.name }}</h3>
                                  <button @click="activeTab = 'standings'" class="text-[9px] font-bold uppercase tracking-widest text-emerald-500">Toàn bộ</button>
                             </div>
                             <div class="no-scrollbar">
                                  <table class="w-full text-left">
                                       <thead>
                                            <tr class="text-[8px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50 dark:border-gray-700">
                                                 <th class="px-5 py-3 text-center">#</th>
                                                 <th class="px-5 py-3">Đội bóng</th>
                                                 <th class="px-5 py-3 text-center cursor-pointer group relative">
                                                      P
                                                      <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-bold rounded shadow-xl z-[100] pointer-events-none">
                                                           Số trận đã đấu
                                                           <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                                      </div>
                                                 </th>
                                                 <th class="px-5 py-3 text-center cursor-pointer group relative">
                                                      W
                                                      <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-bold rounded shadow-xl z-[100] pointer-events-none">
                                                           Thắng
                                                           <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                                      </div>
                                                 </th>
                                                 <th class="px-5 py-3 text-center cursor-pointer group relative">
                                                      D
                                                      <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-bold rounded shadow-xl z-[100] pointer-events-none">
                                                           Hòa
                                                           <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                                      </div>
                                                 </th>
                                                 <th class="px-5 py-3 text-center cursor-pointer group relative">
                                                      L
                                                      <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-bold rounded shadow-xl z-[100] pointer-events-none">
                                                           Thua
                                                           <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                                      </div>
                                                 </th>
                                                 <th class="px-5 py-3 text-center cursor-pointer group relative">
                                                      GD
                                                      <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-bold rounded shadow-xl z-[100] pointer-events-none">
                                                           Hiệu số bàn thắng bại
                                                           <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                                      </div>
                                                 </th>
                                                 <th class="px-5 py-3 text-center cursor-pointer group relative">
                                                      Pts
                                                      <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-bold rounded shadow-xl z-[100] pointer-events-none">
                                                           Tổng điểm hiện tại
                                                           <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                                      </div>
                                                 </th>
                                            </tr>
                                       </thead>
                                       <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                            <tr v-for="s in standings.slice(Math.max(0, standings.findIndex(x => x.team_id === team.id) - 1), Math.min(standings.length, standings.findIndex(x => x.team_id === team.id) + 2))" 
                                                :key="s.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-all group"
                                                :class="{ 'bg-emerald-500/5 dark:bg-emerald-500/10': s.team_id === team.id }">
                                                 <td class="px-5 py-3.5 text-[10px] font-bold text-gray-900 dark:text-white text-center">{{ s.rank }}</td>
                                                 <td class="px-5 py-3.5">
                                                      <div class="flex items-center gap-3">
                                                           <img :src="s.team?.logo" class="w-5 h-5 object-contain" />
                                                           <span class="text-[11px] font-bold text-gray-900 dark:text-white uppercase tracking-tight">{{ s.team?.name }}</span>
                                                      </div>
                                                 </td>
                                                 <td class="px-5 py-3.5 text-[10px] font-bold text-gray-500 text-center">{{ s.played }}</td>
                                                 <td class="px-5 py-3.5 text-[10px] font-bold text-gray-400 text-center">{{ s.win }}</td>
                                                 <td class="px-5 py-3.5 text-[10px] font-bold text-gray-400 text-center">{{ s.draw }}</td>
                                                 <td class="px-5 py-3.5 text-[10px] font-bold text-gray-400 text-center">{{ s.lose }}</td>
                                                 <td class="px-5 py-3.5 text-[10px] font-bold text-gray-400 text-center">{{ s.goals_for - s.goals_against }}</td>
                                                 <td class="px-5 py-3.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400 text-center">{{ s.points }}</td>
                                            </tr>
                                       </tbody>
                                  </table>
                             </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="lg:col-span-4 space-y-6">
                         <!-- Đội hình ra sân trận gần nhất (Pitch) -->
                         <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm relative flex flex-col">
                              <h3 class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-6">Đội hình ra sân gần nhất</h3>
                              <div v-if="lastLineup" class="aspect-[3/4] bg-emerald-900/10 dark:bg-emerald-500/10 rounded-2xl relative p-4 overflow-hidden">
                                   <!-- Pitch Lines -->
                                   <div class="absolute inset-2 border border-emerald-500/10 rounded-xl"></div>
                                   <div class="absolute inset-x-2 top-1/2 h-px bg-emerald-500/10"></div>
                                   
                                   <div v-for="xi in getPitchPlayers" :key="xi.player.id"
                                        class="absolute transform -translate-x-1/2 -translate-y-1/2 flex flex-col items-center gap-0.5"
                                        :style="{ left: `${xi.gridPos[1] * 20 - 10}%`, top: `${xi.gridPos[0] * 18}%` }">
                                        <div class="w-7 h-7 rounded-full bg-white dark:bg-gray-900 border-2 border-white dark:border-gray-800 shadow-xl overflow-hidden">
                                             <img v-if="xi.player.photo" :src="xi.player.photo" class="w-full h-full object-cover" />
                                             <div v-else class="w-full h-full flex items-center justify-center text-[8px] font-bold text-gray-400">{{ xi.player.number }}</div>
                                        </div>
                                        <div class="px-1 py-0.5 bg-gray-950/80 dark:bg-white/80 rounded text-[6px] font-bold text-white dark:text-gray-950 uppercase whitespace-nowrap shadow-sm">
                                             {{ xi.player.name.split(' ').pop() }}
                                        </div>
                                   </div>
                                   
                                   <div class="absolute bottom-2 right-4 text-[7px] font-bold text-gray-400 uppercase tracking-widest">{{ lastLineup.formation }}</div>
                              </div>
                              <div v-else class="aspect-[3/4] bg-gray-50 dark:bg-gray-900/50 rounded-2xl flex items-center justify-center text-[10px] font-bold text-gray-300 uppercase tracking-widest border border-dashed border-gray-200 dark:border-gray-800">
                                   Chưa có dữ liệu đội hình
                              </div>
                         </div>

                         <!-- HLV -->
                         <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
                              <h3 class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-5">Huấn luyện viên hiện tại</h3>
                              <div v-if="coachHistory.length > 0" class="flex items-center gap-4">
                                   <div class="w-14 h-14 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-700 overflow-hidden shadow-inner">
                                        <img :src="coachHistory[coachHistory.length-1].photo" class="w-full h-full object-cover" />
                                   </div>
                                   <div class="flex-1">
                                        <h4 class="text-[11px] font-bold text-gray-900 dark:text-white uppercase mb-1">{{ coachHistory[coachHistory.length-1].name }}</h4>
                                        <div class="flex items-center gap-2">
                                             <span class="text-lg font-bold text-emerald-500">{{ coachHistory[coachHistory.length-1].winRate }}%</span>
                                             <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">{{ coachHistory[coachHistory.length-1].ppg }} PPG</span>
                                        </div>
                                   </div>
                              </div>
                              <div v-else class="py-4 flex flex-col items-center justify-center border border-dashed border-gray-100 dark:border-gray-700 rounded-xl bg-gray-50/50 dark:bg-gray-900/30">
                                   <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Chưa có thông tin HLV</span>
                              </div>
                         </div>
                    </div>
                </div>

                <!-- STANDINGS TAB -->
                <div v-else-if="activeTab === 'standings'" class="space-y-6">
                    <StandingTable v-if="standings.length > 0" :standings="standings" :league-id="nextMatch?.league_id" />
                </div>

                <!-- SQUAD TAB (SINGLE TABLE) -->
                <div v-else-if="activeTab === 'squad'" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto no-scrollbar">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[9px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50 dark:border-gray-700">
                                    <th class="px-6 py-4">Cầu thủ</th>
                                    <th class="px-6 py-4 text-center">VT</th>
                                    <th class="px-6 py-4">Quốc gia</th>
                                    <th class="px-6 py-4 text-center">Số áo</th>
                                    <th class="px-6 py-4 text-center">Tuổi</th>
                                    <th class="px-6 py-4 text-center">Chiều cao</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                <tr v-for="p in squad" :key="p.id" @click="viewPlayer(p.id)" class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-all cursor-pointer group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-xl bg-gray-50 dark:bg-gray-900 overflow-hidden flex-shrink-0 border border-gray-100 dark:border-gray-700">
                                                <img :src="p.photo" class="w-full h-full object-cover" />
                                            </div>
                                            <span class="text-[11px] font-bold text-gray-950 dark:text-white uppercase tracking-tight group-hover:text-emerald-500 transition-colors">{{ p.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-[9px] font-bold text-gray-400 uppercase">{{ positionLabels[p.position] || p.position }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-bold text-gray-600 dark:text-gray-400">{{ p.nationality }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center text-[11px] font-bold text-gray-900 dark:text-white">
                                        {{ p.number || '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-[11px] font-bold text-gray-500">
                                        {{ calcAge(p.birth_year) }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-[10px] font-bold text-gray-400">
                                        {{ p.height ? p.height : '-' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

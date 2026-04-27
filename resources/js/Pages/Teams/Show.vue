<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import MainLayout from '../../Layouts/MainLayout.vue';
import dayjs from 'dayjs';

const props = defineProps({
  team: { type: Object, required: true },
  latestGames: { type: Array, default: () => [] },
  players: { type: Array, default: () => [] }
});

const activeTab = ref('form');
const tabs = [
  { id: 'form', label: 'Phong độ & Lịch đấu' },
  { id: 'squad', label: 'Đội hình' },
  { id: 'stats', label: 'Thống kê mùa giải' }
];

const getMatchResultText = (game) => {
    if (game.home_score === null) return '-';
    const isHome = game.home_team_id === props.team.id;
    const teamScore = isHome ? game.home_score : game.away_score;
    const opponentScore = isHome ? game.away_score : game.home_score;
    
    if (teamScore > opponentScore) return 'W';
    if (teamScore < opponentScore) return 'L';
    return 'D';
};

const getMatchResult = (game) => {
    if (game.home_score === null) return 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500';
    const res = getMatchResultText(game);
    if (res === 'W') return 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30';
    if (res === 'L') return 'bg-red-500 text-white shadow-lg shadow-red-500/30';
    return 'bg-amber-500 text-white shadow-lg shadow-amber-500/30';
};

const formatDate = (dateStr) => dayjs(dateStr).format('DD/MM/YYYY');
const viewMatch = (id) => router.visit(`/matches/${id}`);
const viewPlayer = (id) => router.visit(`/players/${id}`);
</script>

<template>
  <Head :title="`${team.name} | AI Soccer`" />
  
  <MainLayout>
    <div class="py-8">
      <!-- Team Header -->
      <div class="bg-white dark:bg-[#1a1d26] rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm p-10 md:p-14 mb-10 relative overflow-hidden group">
        <!-- Decoration Gradient -->
        <div class="absolute -top-12 -right-12 w-64 h-64 bg-blue-500/5 rounded-full blur-3xl group-hover:bg-blue-500/10 transition-all duration-700"></div>
        <div class="absolute -bottom-12 -left-12 w-64 h-64 bg-emerald-500/5 rounded-full blur-3xl group-hover:bg-emerald-500/10 transition-all duration-700"></div>
        
        <div class="flex flex-col md:flex-row items-center gap-10 relative z-10 text-center md:text-left">
          <div class="w-32 h-32 md:w-36 md:h-36 rounded-[2rem] bg-gray-50 dark:bg-[#0f1117] border border-gray-100 dark:border-gray-800 shadow-inner flex items-center justify-center p-6">
             <img v-if="team.logo_url" :src="team.logo_url" class="w-full h-full object-contain filter drop-shadow-lg" />
             <div v-else class="text-4xl font-black text-gray-300 dark:text-gray-700">{{ team.name.substring(0,2).toUpperCase() }}</div>
          </div>
          
          <div class="flex-1">
            <h1 class="text-4xl md:text-5xl font-black text-gray-950 dark:text-white mb-3 tracking-tight">{{ team.name }}</h1>
            <div class="flex flex-wrap justify-center md:justify-start gap-3">
              <span class="px-4 py-1.5 bg-gray-100 dark:bg-gray-800 rounded-xl text-[10px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                Câu lạc bộ chuyên nghiệp
              </span>
              <span class="px-4 py-1.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-xl text-[10px] font-black uppercase tracking-widest border border-blue-100 dark:border-blue-500/20">
                Football Club
              </span>
              <span class="px-4 py-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded-xl text-[10px] font-black uppercase tracking-widest border border-emerald-100 dark:border-emerald-500/20">
                ID: {{ team.id }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Detail Tabs -->
      <div class="flex bg-white dark:bg-[#1a1d26] p-1.5 rounded-2xl border border-gray-100 dark:border-gray-800 mb-8 inline-flex">
        <button v-for="tab in tabs" :key="tab.id" 
                @click="activeTab = tab.id"
                class="px-6 py-3 text-xs font-black uppercase tracking-widest transition-all rounded-xl whitespace-nowrap"
                :class="activeTab === tab.id ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-200'">
          {{ tab.label }}
        </button>
      </div>

      <!-- Tab Content Area -->
      <div class="min-h-[500px]">
        <!-- Form / Matches Tab -->
        <div v-if="activeTab === 'form'" class="grid gap-4">
          <div v-for="game in latestGames" :key="game.id" 
               @click="viewMatch(game.id)"
               class="bg-white dark:bg-[#1a1d26] p-6 rounded-3xl border border-gray-100 dark:border-gray-800 hover:border-blue-500/30 dark:hover:border-blue-500/30 transition-all cursor-pointer shadow-sm hover:shadow-xl flex flex-col md:flex-row items-center justify-between gap-6 group">
            
            <div class="hidden md:flex flex-col items-center min-w-[120px]">
                <span class="text-[9px] font-black uppercase text-blue-500 tracking-widest mb-1">{{ game.league?.name || 'V-League' }}</span>
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400 tracking-tight">{{ formatDate(game.match_datetime) }}</span>
            </div>

            <div class="flex-1 flex items-center justify-center gap-6">
                <!-- Home Team -->
                <div class="flex-1 text-right font-black text-gray-900 dark:text-gray-100 md:text-lg" :class="{ 'text-blue-500': game.home_team_id === team.id }">
                    {{ game.home_team?.name }}
                </div>

                <!-- Score / VS -->
                <div class="flex items-center gap-3 px-6 py-2.5 bg-gray-50 dark:bg-[#0f1117] rounded-2xl group-hover:bg-blue-50 dark:group-hover:bg-blue-900/10 transition-colors border border-gray-100 dark:border-gray-800">
                    <span class="text-2xl font-black text-gray-950 dark:text-white tabular-nums">{{ game.home_score ?? '-' }}</span>
                    <span class="text-gray-300 dark:text-gray-700 font-bold">:</span>
                    <span class="text-2xl font-black text-gray-950 dark:text-white tabular-nums">{{ game.away_score ?? '-' }}</span>
                </div>

                <!-- Away Team -->
                <div class="flex-1 text-left font-black text-gray-900 dark:text-gray-100 md:text-lg" :class="{ 'text-blue-500': game.away_team_id === team.id }">
                    {{ game.away_team?.name }}
                </div>
            </div>

            <div class="min-w-[80px] flex justify-center">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xs font-black transition-transform group-hover:scale-110" :class="getMatchResult(game)">
                    {{ getMatchResultText(game) }}
                </div>
            </div>
          </div>
          <div v-if="latestGames.length === 0" class="text-center py-20 bg-white dark:bg-[#1a1d26] rounded-3xl border border-dashed border-gray-200 dark:border-gray-800">
              <p class="text-gray-400 font-bold uppercase tracking-widest text-xs">Chưa có dữ liệu trận đấu</p>
          </div>
        </div>

        <!-- Squad Tab -->
        <div v-else-if="activeTab === 'squad'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="player in players" :key="player.id" 
                 @click="viewPlayer(player.id)"
                 class="bg-white dark:bg-[#1a1d26] p-6 rounded-3xl border border-gray-100 dark:border-gray-800 hover:border-blue-500 transition-all cursor-pointer shadow-sm group">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gray-50 dark:bg-[#0f1117] flex items-center justify-center font-black text-gray-300 dark:text-gray-700 group-hover:bg-blue-600 group-hover:text-white transition-all transform group-hover:rotate-6 shadow-inner">
                        <span class="text-xl">{{ player.name.substring(0,1) }}</span>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-bold text-gray-950 dark:text-white group-hover:text-blue-500 transition-colors">{{ player.name }}</h4>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-[9px] font-black uppercase text-gray-400 dark:text-gray-500 tracking-widest">{{ player.nationality || 'Cầu thủ' }}</span>
                            <span class="text-[10px] font-bold text-blue-500 bg-blue-50 dark:bg-blue-900/20 px-2 py-0.5 rounded-lg border border-blue-100 dark:border-blue-900/50">
                                {{ player.match_stats_count }} trận
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="players.length === 0" class="col-span-full text-center py-20 bg-white dark:bg-[#1a1d26] rounded-3xl border border-dashed border-gray-200 dark:border-gray-800">
              <p class="text-gray-400 font-bold uppercase tracking-widest text-xs">Chưa có dữ liệu cầu thủ</p>
            </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>

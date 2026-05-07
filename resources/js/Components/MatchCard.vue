<template>
  <div @click="router.visit(`/matches/${game.id}`)"
       class="group bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm border border-gray-100 dark:border-gray-700 rounded-2xl p-4 flex items-center justify-between hover:border-emerald-500/50 hover:shadow-lg hover:shadow-emerald-500/5 transition-all cursor-pointer">
    
    <div class="flex items-center gap-4 flex-1 min-w-0">
      <div class="w-1 h-8 bg-gray-100 dark:bg-gray-700 rounded-full group-hover:bg-emerald-500 transition-colors"></div>
      
      <div class="flex flex-col flex-1 min-w-0">
        <div class="flex items-center gap-2 sm:gap-6 justify-center sm:justify-start">
          <!-- Home Team -->
          <div class="flex items-center gap-3 flex-1 justify-end min-w-0">
            <span class="font-bold text-xs sm:text-sm text-right truncate text-gray-700 dark:text-gray-100">{{ game.home_team?.name }}</span>
            <div class="w-8 h-8 rounded-lg bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-700 flex items-center justify-center p-1 shrink-0 shadow-sm overflow-hidden">
              <img v-if="game.home_team?.logo_url" :src="game.home_team.logo_url" class="w-full h-full object-contain" />
              <span v-else class="text-[10px] font-bold text-gray-400">{{ game.home_team?.short_name?.substring(0,2) || 'N' }}</span>
            </div>
          </div>

          <!-- Score / Info -->
          <div class="flex flex-col items-center min-w-[50px] sm:min-w-[80px]">
            <div class="flex items-center gap-1 text-sm sm:text-base font-bold text-gray-900 dark:text-white tabular-nums">
              <span>{{ game.home_score ?? '-' }}</span>
              <span class="opacity-20">:</span>
              <span>{{ game.away_score ?? '-' }}</span>
            </div>
            <span class="text-[8px] font-bold text-emerald-600 bg-emerald-50 dark:bg-emerald-500/10 px-2 py-1 rounded-full mt-1 border border-emerald-100/50 flex items-center justify-center leading-none">
              {{ getMatchStatus(game) }}
            </span>
          </div>
          
          <!-- Away Team -->
          <div class="flex items-center gap-3 flex-1 min-w-0">
            <div class="w-8 h-8 rounded-lg bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-700 flex items-center justify-center p-1 shrink-0 shadow-sm overflow-hidden">
              <img v-if="game.away_team?.logo_url" :src="game.away_team.logo_url" class="w-full h-full object-contain" />
              <span v-else class="text-[10px] font-bold text-gray-400">{{ game.away_team?.short_name?.substring(0,2) || 'K' }}</span>
            </div>
            <span class="font-bold text-xs sm:text-sm truncate text-gray-700 dark:text-gray-100">{{ game.away_team?.name }}</span>
          </div>
        </div>
      </div>

      <!-- Notification Toggle -->
      <div @click.stop class="ml-4 shrink-0 relative z-10">
        <button @click="toggleFollow(game.id)"
                class="p-2.5 rounded-xl transition-all duration-300 border"
                :class="isFollowed(game.id) 
                    ? 'bg-emerald-50/50 dark:bg-emerald-500/10 text-emerald-500 border-emerald-500/40 shadow-lg shadow-emerald-500/5 scale-110' 
                    : 'bg-gray-50/30 dark:bg-gray-700/30 text-gray-400 border-gray-100 dark:border-gray-700 hover:text-emerald-500 hover:border-emerald-200'">
            <svg class="w-4 h-4" :class="{ 'animate-swing text-emerald-500': isFollowed(game.id) }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";

dayjs.extend(utc);

const props = defineProps({
  game: { type: Object, required: true }
});

const getMatchStatus = (game) => {
  const finishedStatuses = ['FT', 'AET', 'PEN', 'finished'];
  if (finishedStatuses.includes(game.status)) return 'KẾT THÚC';
  if (game.status === 'live' || game.status === '1H' || game.status === '2H' || game.status === 'HT') return 'TRỰC TIẾP';
  
  const matchDate = dayjs.utc(game.match_datetime || game.match_at).local();
  const isToday = matchDate.isSame(dayjs(), 'day');
  
  return isToday ? matchDate.format("HH:mm") : matchDate.format("DD/MM HH:mm");
};

// --- Logic Notification (Follow) ---
import { ref, onMounted } from 'vue';
const followedMatches = ref([]);

onMounted(() => {
    const stored = localStorage.getItem('followed_matches');
    if (stored) {
        followedMatches.value = JSON.parse(stored);
    }
});

const isFollowed = (id) => followedMatches.value.includes(id);

const toggleFollow = (id) => {
    if (isFollowed(id)) {
        followedMatches.value = followedMatches.value.filter(m => m !== id);
    } else {
        followedMatches.value.push(id);
    }
    localStorage.setItem('followed_matches', JSON.stringify(followedMatches.value));
    
    // Phát ra sự kiện global để Toast Notification có thể lắng nghe nếu cần
    window.dispatchEvent(new CustomEvent('followed-matches-updated', { detail: followedMatches.value }));
};
</script>

<style scoped>
@keyframes swing {
  0% { transform: rotate(0deg); }
  20% { transform: rotate(15deg); }
  40% { transform: rotate(-10deg); }
  60% { transform: rotate(5deg); }
  80% { transform: rotate(-5deg); }
  100% { transform: rotate(0deg); }
}
.animate-swing {
  animation: swing 0.5s ease-in-out infinite;
  transform-origin: top center;
}
</style>

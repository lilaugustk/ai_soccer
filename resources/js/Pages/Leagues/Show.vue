<template>
  <Head :title="`${league.name} | AI Soccer`" />
  
  <MainLayout>
    <div class="py-8 relative min-h-screen">
      <!-- AI Background Blobs -->
      <div class="absolute -top-24 -left-24 w-96 h-96 bg-emerald-500/10 dark:bg-emerald-500/5 rounded-full blur-[100px] -z-10 animate-pulse"></div>
      <div class="absolute bottom-24 -right-24 w-80 h-80 bg-blue-500/10 dark:bg-blue-500/5 rounded-full blur-[80px] -z-10"></div>

      <div class="flex flex-col lg:flex-row gap-8">
        <!-- Main Content -->
        <div class="flex-1 min-w-0">
          <!-- League Header Card -->
          <div class="relative overflow-hidden rounded-[2.5rem] bg-white dark:bg-gray-800/40 backdrop-blur-xl p-8 shadow-2xl shadow-gray-200/20 dark:shadow-none border border-gray-100 dark:border-gray-700/50 mb-10 group">
            <div class="absolute -right-20 -top-20 w-80 h-80 bg-emerald-500/5 rounded-full blur-[80px] group-hover:bg-emerald-500/10 transition-colors duration-700"></div>
            
            <div class="relative flex flex-col md:flex-row items-center md:items-start gap-8">
              <!-- Logo Container -->
              <div class="w-32 h-32 rounded-3xl bg-gray-50 dark:bg-gray-900/50 p-6 flex items-center justify-center border border-gray-100 dark:border-gray-700 shadow-inner group-hover:scale-105 group-hover:rotate-3 transition-all duration-500">
                <img :src="league.logo" class="w-full h-full object-contain drop-shadow-xl" />
              </div>
              
              <div class="text-center md:text-left flex-1">
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 mb-4">
                  <span v-if="league.type" class="px-4 py-1.5 rounded-2xl bg-emerald-500 text-white text-[10px] font-black uppercase tracking-wider shadow-lg shadow-emerald-500/20">
                    {{ league.type === 'League' ? 'Giải đấu' : (league.type === 'Cup' ? 'Cúp' : league.type) }}
                  </span>
                  <!-- Country Flag -->
                  <div class="flex items-center gap-2 px-4 py-1.5 rounded-2xl bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300">
                    <img :src="getFlagUrl(league.country_name)" class="w-4 h-3 object-cover rounded-[2px]" />
                    <span class="text-[10px] font-black uppercase tracking-wider">{{ league.country_name }}</span>
                  </div>
                  
                  <!-- Season Selector -->
                  <div class="relative season-dropdown">
                    <button @click="isSeasonOpen = !isSeasonOpen" 
                            class="px-4 py-1.5 rounded-2xl bg-blue-500/10 text-blue-500 text-[10px] font-black uppercase tracking-wider border border-blue-500/20 flex items-center gap-2 hover:bg-blue-500/20 transition-all">
                      Season {{ season }}
                      <svg class="w-3 h-3 transition-transform" :class="{'rotate-180': isSeasonOpen}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                      </svg>
                    </button>
                    
                    <transition enter-active-class="transition duration-200 ease-out" enter-from-class="transform scale-95 opacity-0 -translate-y-2" enter-to-class="transform scale-100 opacity-100 translate-y-0">
                      <div v-show="isSeasonOpen" class="absolute top-full left-0 mt-2 w-32 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl shadow-2xl py-2 z-50">
                        <button v-for="s in [2025, 2024, 2023, 2022]" :key="s"
                                @click="changeSeason(s)"
                                class="w-full text-left px-4 py-2 text-[10px] font-black uppercase tracking-wider hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition-colors"
                                :class="s == season ? 'text-emerald-500 bg-emerald-50/50' : 'text-gray-500'">
                          Season {{ s }}
                        </button>
                      </div>
                    </transition>
                  </div>
                </div>
                
                <h1 class="text-4xl font-black tracking-tight text-gray-900 dark:text-white uppercase leading-none mb-3">
                    {{ league.name }}
                </h1>
                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.1em]">
                    Dữ liệu phân tích mùa giải {{ season }} từ AI Soccer
                </p>
              </div>
            </div>
          </div>

          <!-- Tab Navigation -->
          <div class="flex items-center gap-2 mb-10 p-2 bg-white/50 dark:bg-gray-800/30 backdrop-blur-md rounded-[2rem] border border-gray-100 dark:border-gray-700 w-fit shadow-xl shadow-gray-200/10 dark:shadow-none">
            <button v-for="tab in tabs" :key="tab.id"
              @click="activeTab = tab.id"
              :class="activeTab === tab.id 
                ? 'bg-emerald-500 text-white shadow-xl shadow-emerald-500/20' 
                : 'text-gray-500 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-white dark:hover:bg-gray-700/50'"
              class="px-8 py-3.5 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all duration-300">
              {{ tab.name }}
            </button>
          </div>

          <!-- Tab Content with Transitions -->
          <transition mode="out-in" enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 translate-y-4" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-4">
            
            <!-- STANDINGS TAB -->
            <div v-if="activeTab === 'standings'" key="standings">
              <StandingTable :standings="standings" />
            </div>

            <!-- MATCHES TAB -->
            <div v-else-if="activeTab === 'matches'" key="matches" class="space-y-8">
              <!-- Round Filter -->
              <div v-if="availableRounds.length > 1" class="flex items-center gap-3 overflow-x-auto pb-2 no-scrollbar">
                <button @click="selectedRound = 'all'" 
                  :class="selectedRound === 'all' ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'bg-white dark:bg-gray-800 text-gray-500 border border-gray-100 dark:border-gray-700'"
                  class="px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-wider transition-all whitespace-nowrap">
                  Tất cả vòng
                </button>
                <button v-for="round in availableRounds" :key="round"
                  @click="selectedRound = round"
                  :class="selectedRound === round ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/20' : 'bg-white dark:bg-gray-800 text-gray-500 border border-gray-100 dark:border-gray-700'"
                  class="px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-wider transition-all whitespace-nowrap">
                  {{ round }}
                </button>
              </div>

              <div v-if="filteredMatches.length === 0" class="py-32 flex flex-col items-center justify-center text-center">
                 <div class="w-20 h-20 bg-gray-50 dark:bg-gray-800 rounded-[2rem] flex items-center justify-center mb-6 border border-gray-100 dark:border-gray-700">
                    <svg class="w-10 h-10 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                 </div>
                 <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-tight">Chưa có dữ liệu trận đấu</h3>
                 <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mt-2">Dữ liệu đang được đồng bộ cho mùa giải {{ season }}</p>
              </div>
              
              <div v-else class="grid grid-cols-1 gap-4">
                 <MatchCard v-for="match in filteredMatches" :key="match.id" :game="match" />
              </div>
            </div>

            <!-- SCORERS TAB -->
            <div v-else-if="activeTab === 'scorers'" key="scorers">
              <TopScorerList :scorers="topScorers" />
            </div>

          </transition>
        </div>

        <!-- RIGHT SIDEBAR -->
        <div class="w-full lg:w-80 space-y-8">
          <MiniStandingSidebar :standings="standings" @viewFull="activeTab = 'standings'" />
          
          <!-- League Stats Card -->
          <div class="relative overflow-hidden rounded-[2.5rem] bg-white dark:bg-gray-800/40 backdrop-blur-xl p-8 border border-gray-100 dark:border-gray-700/50 shadow-2xl shadow-gray-200/20 dark:shadow-none">
            <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-emerald-500/5 rounded-full blur-2xl"></div>
            
            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] mb-6 text-gray-400">Thống kê giải đấu</h4>
            
            <div class="space-y-6 relative z-10">
               <div class="flex items-center justify-between group">
                  <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Đội tham dự</span>
                  <span class="text-xl font-black text-gray-900 dark:text-white">{{ standings.length }}</span>
               </div>
               <div class="w-full h-px bg-gray-50 dark:bg-gray-700/50"></div>
               <div class="flex items-center justify-between">
                  <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Tổng trận đấu</span>
                  <span class="text-xl font-black text-gray-900 dark:text-white">{{ matches.length }}</span>
               </div>
               <div class="w-full h-px bg-gray-50 dark:bg-gray-700/50"></div>
               <div class="flex items-center justify-between">
                  <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Tình trạng</span>
                  <div class="flex items-center gap-2">
                     <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shadow-[0_0_10px_rgba(16,185,129,0.5)]"></div>
                     <span class="text-[10px] font-black uppercase tracking-wider text-emerald-500">Live Sync</span>
                  </div>
               </div>
            </div>

            <button class="w-full mt-10 py-4 bg-gray-50 dark:bg-gray-700/50 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 hover:text-emerald-500 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all border border-gray-100 dark:border-gray-700/50 text-gray-500">
               Xem chi tiết lịch sử
            </button>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import StandingTable from '@/Components/StandingTable.vue';
import TopScorerList from '@/Components/TopScorerList.vue';
import MiniStandingSidebar from '@/Components/MiniStandingSidebar.vue';
import MatchCard from '@/Components/MatchCard.vue';

const props = defineProps({
  league: Object,
  standings: Array,
  topScorers: Array,
  matches: Array,
  season: [Number, String]
});

const activeTab = ref('standings');
const isSeasonOpen = ref(false);

// Tự động tìm vòng đấu mới nhất để hiển thị mặc định
const getDefaultRound = () => {
    if (!props.matches || props.matches.length === 0) return 'all';
    // Lấy round của trận đấu đầu tiên (vì đã được sắp xếp desc trong Controller)
    return props.matches[0].round || 'all';
};

const selectedRound = ref(getDefaultRound());

const tabs = [
  { id: 'standings', name: 'Bảng xếp hạng' },
  { id: 'matches', name: 'Kết quả & Lịch thi đấu' },
  { id: 'scorers', name: 'Vua phá lưới' }
];

const changeSeason = (s) => {
  isSeasonOpen.value = false;
  router.get(`/leagues/${props.league.id}`, { season: s }, { preserveState: false });
};

const getFlagUrl = (countryName) => {
    if (!countryName || countryName === 'Quốc tế' || countryName === 'World') return 'https://flagcdn.com/w40/un.png';
    return `https://flagcdn.com/w40/${getCountryCode(countryName)}.png`;
};

const getCountryCode = (name) => {
    const map = {
        'England': 'gb-eng', 'Spain': 'es', 'Germany': 'de', 'Italy': 'it', 'France': 'fr',
        'Vietnam': 'vn', 'Japan': 'jp', 'South Korea': 'kr', 'Brazil': 'br', 'Argentina': 'ar',
        'Portugal': 'pt', 'Netherlands': 'nl', 'Belgium': 'be', 'Russia': 'ru', 'USA': 'us',
        'Scotland': 'gb-sct', 'Wales': 'gb-wls', 'Northern-Ireland': 'gb-nir',
        'Mexico': 'mx', 'Turkey': 'tr', 'Greece': 'gr', 'Austria': 'at', 'Switzerland': 'ch',
        'Ukraine': 'ua', 'Poland': 'pl', 'Saudi-Arabia': 'sa', 'China': 'cn', 'Australia': 'au'
    };
    return map[name] || name.toLowerCase().substring(0, 2);
};

// Lấy danh sách các vòng đấu duy nhất
const availableRounds = computed(() => {
  if (!props.matches) return [];
  const rounds = props.matches
    .map(m => m.round)
    .filter((v, i, a) => v && a.indexOf(v) === i);
  
  return rounds.sort((a, b) => {
    const numA = parseInt(a.match(/\d+/) || 0);
    const numB = parseInt(b.match(/\d+/) || 0);
    return numB - numA; 
  });
});

// Lọc trận đấu theo vòng và sắp xếp
const filteredMatches = computed(() => {
  let list = [];
  if (selectedRound.value === 'all') {
    list = [...props.matches];
    // Sắp xếp desc cho "Tất cả vòng" (mới nhất lên đầu)
    return list.sort((a, b) => new Date(b.match_at) - new Date(a.match_at));
  }
  
  list = props.matches.filter(m => m.round === selectedRound.value);
  // Sắp xếp asc cho một vòng đấu cụ thể (trình tự thời gian trong vòng)
  return list.sort((a, b) => new Date(a.match_at) - new Date(b.match_at));
});

const handleOutsideClick = (e) => {
  if (!e.target.closest('.season-dropdown')) {
    isSeasonOpen.value = false;
  }
};

onMounted(() => window.addEventListener('click', handleOutsideClick));
onUnmounted(() => window.removeEventListener('click', handleOutsideClick));
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>

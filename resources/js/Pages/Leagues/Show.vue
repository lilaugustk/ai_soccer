<template>
  <Head :title="`${league.name} - Kết quả, Lịch thi đấu, BXH | AI Soccer`" />
  
  <MainLayout>
    <div class="py-6 relative min-h-screen">
      <!-- AI Background Blobs -->
      <div class="absolute -top-24 -left-24 w-96 h-96 bg-emerald-500/5 dark:bg-emerald-500/5 rounded-full blur-[100px] -z-10 animate-pulse"></div>
      
      <div class="flex flex-col lg:flex-row gap-6">
        
        <!-- MAIN CONTENT -->
        <main class="flex-1 min-w-0 space-y-6">
          
          <!-- Breadcrumbs -->
          <nav class="flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
              <Link href="/" class="hover:text-emerald-500">Trang chủ</Link>
              <span>/</span>
              <span>{{ league.country_name }}</span>
              <span>/</span>
              <span class="text-gray-900 dark:text-gray-200">{{ league.name }}</span>
          </nav>

          <!-- Header -->
          <div class="bg-white dark:bg-gray-800/80 rounded-2xl border border-gray-100 dark:border-gray-700/50 p-6 shadow-sm overflow-hidden relative">
            <div class="flex flex-col md:flex-row items-center gap-6 relative z-10">
              <div class="w-24 h-24 p-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-700 flex items-center justify-center shadow-inner">
                <img :src="league.logo" :alt="league.name" class="w-full h-full object-contain" />
              </div>
              
              <div class="flex-1 text-center md:text-left space-y-3">
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-3">
                    <div class="flex items-center gap-1.5 px-3 py-1 bg-gray-100 dark:bg-gray-700/50 rounded-full text-[9px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-300">
                        <img :src="getFlagUrl(league.country_name)" class="w-3 h-2.5 object-cover rounded-sm" />
                        {{ league.country_name }}
                    </div>
                    <div class="px-3 py-1 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-full text-[9px] font-bold uppercase tracking-widest">
                        {{ league.type === 'League' ? 'Hạng đấu' : 'Giải đấu' }}
                    </div>
                </div>
                
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white uppercase tracking-tight">{{ league.name }}</h1>
                
                <div class="flex items-center justify-center md:justify-start gap-4">
                </div>
              </div>
            </div>
          </div>

          <!-- Flashscore Tabs -->
          <div class="border-b border-gray-100 dark:border-gray-700 relative">
              <div class="flex items-center justify-between gap-8">
                  <!-- Tabs with local overflow -->
                  <div class="flex gap-8 overflow-x-auto no-scrollbar">
                      <button v-for="tab in tabs" :key="tab.id"
                              @click="activeTab = tab.id"
                              :class="activeTab === tab.id ? 'text-emerald-500 border-b-2 border-emerald-500' : 'text-gray-500 hover:text-gray-800 dark:hover:text-gray-200'"
                              class="pb-4 px-1 text-[11px] font-bold uppercase tracking-widest transition-all whitespace-nowrap outline-none">
                          {{ tab.name }}
                      </button>
                  </div>

                  <!-- Season Dropdown -->
                  <div class="relative season-dropdown pb-4">
                        <button @click="isSeasonOpen = !isSeasonOpen" 
                                class="flex items-center gap-2 px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-lg text-[9px] font-bold uppercase tracking-widest transition-all hover:bg-gray-100 dark:hover:bg-gray-700">
                            Mùa giải: {{ season }}/{{ (parseInt(season) + 1).toString().slice(-2) }}
                            <svg :class="['w-2.5 h-2.5 transition-transform', isSeasonOpen ? 'rotate-180' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div v-show="isSeasonOpen" class="absolute top-full right-0 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl shadow-2xl py-2 z-50">
                            <button v-for="s in [2025, 2024, 2023, 2022]" :key="s" @click="changeSeason(s)" class="w-full text-center px-4 py-2 text-[11px] font-bold hover:bg-gray-50 dark:hover:bg-gray-700" :class="s == season ? 'text-emerald-500' : 'text-gray-500'">{{ s }} / {{ s + 1 }}</button>
                        </div>
                  </div>
              </div>
          </div>

          <!-- Content Area -->
          <div class="min-h-[400px]">
            <transition mode="out-in" enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-100 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
                
                <!-- STANDINGS -->
                <div v-if="activeTab === 'standings'" key="standings">
                  <StandingTable :standings="standings" :league-id="league.id" :season="season" />
                </div>

                <!-- MATCHES -->
                <div v-else-if="activeTab === 'matches'" key="matches" class="space-y-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
                            <button v-for="round in availableRounds" :key="round"
                                    @click="selectedRound = round"
                                    :class="selectedRound === round ? 'bg-emerald-500 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-500 hover:bg-gray-200'"
                                    class="px-3 py-1.5 rounded-lg text-[9px] font-bold uppercase tracking-wider transition-all whitespace-nowrap">
                                {{ round }}
                            </button>
                            <button @click="selectedRound = 'all'" :class="selectedRound === 'all' ? 'bg-emerald-500 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-500'" class="px-3 py-1.5 rounded-lg text-[9px] font-bold uppercase tracking-wider whitespace-nowrap">Tất cả</button>
                        </div>
                    </div>
                    <div v-if="filteredMatches.length === 0" class="py-20 text-center text-gray-400 text-[10px] font-bold uppercase tracking-widest">Không có dữ liệu trận đấu</div>
                    <div v-else class="grid grid-cols-1 gap-2">
                        <MatchCard v-for="match in filteredMatches" :key="match.id" :game="match" />
                    </div>
                </div>

                <!-- SCORERS -->
                <div v-else-if="activeTab === 'scorers'" key="scorers">
                    <TopScorerList :scorers="topScorers" />
                </div>
                
            </transition>
          </div>
        </main>

        <!-- RIGHT SIDEBAR (Ad/Info) -->
        <aside class="hidden xl:block w-72 shrink-0 space-y-6 pt-[38px]">
             <!-- Top Scorer Preview -->
             <div class="bg-white dark:bg-gray-800/80 rounded-2xl border border-gray-100 dark:border-gray-700/50 p-5 shadow-sm">
                <h3 class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-4">Vua phá lưới</h3>
                <div v-if="topScorers && topScorers.length > 0" class="space-y-3">
                    <div v-for="(scorer, idx) in topScorers.slice(0, 5)" :key="scorer.player_id" class="flex items-center gap-3">
                        <span class="text-[10px] font-bold text-gray-300 w-4">{{ idx + 1 }}</span>
                        <img :src="scorer.photo" class="w-8 h-8 rounded-full bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-700" />
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-bold text-gray-900 dark:text-white truncate">{{ scorer.player_name }}</p>
                            <p class="text-[9px] text-gray-400 truncate">{{ scorer.goals }} bàn</p>
                        </div>
                    </div>
                </div>
                <div v-else class="text-[9px] text-center text-gray-400 py-4 italic">Đang cập nhật...</div>
             </div>
        </aside>

      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import StandingTable from '@/Components/StandingTable.vue';
import TopScorerList from '@/Components/TopScorerList.vue';
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
const expandedCountry = ref(props.league.country_name);

const groupedLeagues = computed(() => usePage().props.sharedLeagues || []);

const sortedLeagues = computed(() => {
    if (!groupedLeagues.value) return [];
    const priorities = ['England', 'Spain', 'Italy', 'Germany', 'France', 'Vietnam', 'World'];
    return [...groupedLeagues.value].sort((a, b) => {
        const indexA = priorities.indexOf(a.country_name);
        const indexB = priorities.indexOf(b.country_name);
        if (indexA !== -1 && indexB !== -1) return indexA - indexB;
        if (indexA !== -1) return -1;
        if (indexB !== -1) return 1;
        return a.country_name.localeCompare(b.country_name);
    });
});

const toggleCountry = (name) => {
    expandedCountry.value = expandedCountry.value === name ? null : name;
};

const getDefaultRound = () => {
    if (!props.matches || props.matches.length === 0) return 'all';
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
    const mapping = {
        'England': 'gb-eng', 'Spain': 'es', 'Germany': 'de', 'Italy': 'it', 'France': 'fr',
        'Vietnam': 'vn', 'Brazil': 'br', 'Argentina': 'ar', 'Portugal': 'pt', 'Netherlands': 'nl'
    };
    const code = mapping[countryName] || countryName.toLowerCase().substring(0, 2);
    return `https://flagcdn.com/w40/${code}.png`;
};

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

const filteredMatches = computed(() => {
  let list = [];
  if (selectedRound.value === 'all') {
    list = [...props.matches];
    return list.sort((a, b) => new Date(b.match_at) - new Date(a.match_at));
  }
  list = props.matches.filter(m => m.round === selectedRound.value);
  return list.sort((a, b) => new Date(a.match_at) - new Date(b.match_at));
});

const handleOutsideClick = (e) => {
  if (!e.target.closest('.season-dropdown')) isSeasonOpen.value = false;
};

onMounted(() => window.addEventListener('click', handleOutsideClick));
onUnmounted(() => window.removeEventListener('click', handleOutsideClick));
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.custom-scrollbar::-webkit-scrollbar { width: 3px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); }
</style>

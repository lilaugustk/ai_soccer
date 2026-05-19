<template>
  <Head :title="`${league.name} - Kết quả, Lịch thi đấu, Bảng xếp hạng | AI Soccer`" />
  
  <MainLayout>
    <div class="pt-2 pb-8 relative min-h-screen">
      <!-- AI Background Blobs -->
      <div class="absolute -top-24 -left-24 w-96 h-96 bg-emerald-500/5 dark:bg-emerald-500/5 rounded-full blur-[100px] -z-10 animate-pulse"></div>
      
      <div class="flex flex-col lg:flex-row gap-6">
        
        <!-- MAIN CONTENT -->
        <main class="flex-1 min-w-0 space-y-6">
          
          <!-- Breadcrumbs -->
          <nav class="flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
              <Link href="/" class="hover:text-emerald-500">Trang chủ</Link>
              <span>/</span>
              <span>{{ league.country }}</span>
              <span>/</span>
              <span class="text-gray-900 dark:text-gray-200">{{ league.name }}</span>
          </nav>

          <!-- Header -->
          <div class="bg-white dark:bg-gray-800/80 rounded-2xl border border-gray-100 dark:border-gray-700/50 p-6 shadow-sm overflow-hidden relative">
            <div class="flex flex-col md:flex-row items-center gap-6 relative z-10">
              <div class="w-24 h-24 p-4 bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-700 flex items-center justify-center shadow-inner">
                <img :src="league.logo_url" :alt="league.name" class="w-full h-full object-contain" />
              </div>
              
              <div class="flex-1 text-center md:text-left space-y-3">
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-3">
                    <div class="flex items-center gap-1.5 px-3 py-1 bg-gray-100 dark:bg-gray-700/50 rounded-full text-[9px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-300">
                        <img :src="getFlagUrl(league.country)" class="w-3 h-2.5 object-cover rounded-sm" />
                        {{ league.country }}
                    </div>
                    <div class="px-3 py-1 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-full text-[9px] font-bold uppercase tracking-widest">
                        {{ league.type === 'League' ? 'Hạng đấu' : 'Giải đấu' }}
                    </div>
                    <div v-if="league.is_women" class="flex items-center gap-1 px-3 py-1 bg-pink-500/10 text-pink-600 dark:text-pink-400 rounded-full text-[9px] font-bold uppercase tracking-widest animate-fade-in shadow-sm border border-pink-500/10">
                        <svg class="w-3.5 h-3.5 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4a6 6 0 100 12 6 6 0 000-12zM12 16v6M9 19h6" />
                        </svg>
                        <span>Giải đấu Nữ</span>
                    </div>
                </div>
                
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white uppercase tracking-tight">{{ league.name }}</h1>
                
                <div v-if="season_start_date && season_end_date" class="flex flex-wrap items-center justify-center md:justify-start gap-1 text-xs font-semibold text-gray-500 dark:text-gray-400">
                    <span class="text-gray-800 dark:text-gray-200 bg-gray-105 rounded"> Thời gian diễn ra: {{ formatDate(season_start_date) }} đến {{ formatDate(season_end_date) }}</span>
                </div>
                
                <div class="flex items-center justify-center md:justify-start gap-4">
                </div>
              </div>
            </div>
          </div>

          <!-- Flashscore Tabs -->
          <div class="border-b border-gray-100 dark:border-gray-700 relative">
              <div class="flex items-center justify-between gap-8">
                  <!-- Tabs with local overflow -->
                  <div class="flex gap-8 overflow-x-auto no-scrollbar whitespace-nowrap">
                      <button v-for="tab in tabs" :key="tab.id"
                              @click="switchTab(tab.id)"
                              :class="activeTab === tab.id ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                              class="relative pb-4 px-1 text-[11px] font-bold uppercase transition-all whitespace-nowrap outline-none">
                          {{ tab.name }}
                          <!-- Active Underline Indicator -->
                          <div v-if="activeTab === tab.id" 
                               class="absolute bottom-0 left-0 right-0 h-0.5 bg-emerald-500 rounded-full animate-in fade-in slide-in-from-left-1">
                          </div>
                      </button>
                  </div>

                  <!-- Season Dropdown - Only show on Standings tab (Preserve height) -->
                  <div class="relative season-dropdown pb-4 transition-all duration-200" 
                       :class="activeTab === 'standings' ? 'opacity-100' : 'opacity-0 pointer-events-none'">
                        <button @click="isSeasonOpen = !isSeasonOpen" 
                                class="flex items-center gap-2 px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-lg text-[11px] font-bold uppercase transition-all hover:bg-gray-100 dark:hover:bg-gray-700">
                            Mùa giải: {{ formatSeason(season, league.country) }}
                            <svg :class="['w-2.5 h-2.5 transition-transform', isSeasonOpen ? 'rotate-180' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div v-show="isSeasonOpen" class="absolute top-full right-0 mt-1 min-w-full bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl shadow-2xl py-2 z-50 max-h-60 overflow-y-auto custom-scrollbar">
                            <button v-for="s in availableSeasons" :key="s" @click="changeSeason(s)" class="w-full text-center px-4 py-2 text-[11px] font-bold hover:bg-gray-50 dark:hover:bg-gray-700 whitespace-nowrap" :class="s == season ? 'text-emerald-500' : 'text-gray-500'">{{ formatSeason(s, league.country) }}</button>
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
                        <div ref="roundsScrollRef" 
                             @mousedown="onMouseDown"
                             @mouseleave="onMouseLeave"
                             @mouseup="onMouseUp"
                             @mousemove="onMouseMove"
                             class="flex items-center gap-2 overflow-x-auto custom-scrollbar pb-2 w-full min-w-0 whitespace-nowrap cursor-grab active:cursor-grabbing select-none">
                            <button @click="selectedRound = 'all'" 
                                    :class="selectedRound === 'all' ? 'bg-emerald-500 text-white border-emerald-500 ring-2 ring-emerald-500/20' : 'bg-white dark:bg-gray-800 text-gray-500 border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm'" 
                                    class="px-4 py-2 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all whitespace-nowrap shrink-0 border">
                                Tất cả
                            </button>
                            <button v-for="round in availableRounds" :key="round"
                                    @click="selectedRound = round"
                                    :class="selectedRound === round ? 'bg-emerald-500 text-white border-emerald-500 ring-2 ring-emerald-500/20' : 'bg-white dark:bg-gray-800 text-gray-500 border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm'"
                                    class="px-4 py-2 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all whitespace-nowrap shrink-0 border">
                                Vòng {{ round }}
                            </button>
                        </div>

                    <div v-if="filteredMatches.length === 0" class="py-20 text-center text-gray-400 text-[10px] font-bold uppercase tracking-widest">Không có dữ liệu trận đấu</div>
                    <div v-else class="grid grid-cols-1 gap-2 min-h-[400px] relative">
                        <MatchCard v-for="match in filteredMatches" :key="match.id" :game="match" />
                    </div>
                </div>

            </transition>
          </div>
        </main>

        <!-- RIGHT SIDEBAR (Matching Homepage) -->
        <LeagueSidebar />

      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import StandingTable from '@/Components/StandingTable.vue';
import LeagueSidebar from '@/Components/LeagueSidebar.vue';
import MatchCard from '@/Components/MatchCard.vue';

const props = defineProps({
  league: Object,
  standings: Array,
  matches: Array,
  season: [Number, String],
  season_start_date: String,
  season_end_date: String,
  availableSeasons: Array
});

const activeTab = ref(new URLSearchParams(window.location.search).get('tab') || 'standings');

const switchTab = (tabId) => {
    activeTab.value = tabId;
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tabId);
    window.history.replaceState({}, '', url);
};

const isSeasonOpen = ref(false);
const expandedCountry = ref(props.league.country);

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
  { id: 'matches', name: 'Kết quả & Lịch thi đấu' }
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
    .map(m => m.round_number)
    .filter((v, i, a) => v !== null && v !== undefined && a.indexOf(v) === i);
  
  return rounds.sort((a, b) => b - a); 
});

const filteredMatches = computed(() => {
  let list = [];
  if (selectedRound.value === 'all') {
    list = [...props.matches];
    return list.sort((a, b) => new Date(b.event_date || b.match_at) - new Date(a.event_date || a.match_at));
  }
  list = props.matches.filter(m => m.round_number === selectedRound.value);
  return list.sort((a, b) => new Date(a.event_date || a.match_at) - new Date(b.event_date || b.match_at));
});

const handleOutsideClick = (e) => {
  if (!e.target.closest('.season-dropdown')) isSeasonOpen.value = false;
};

// --- Drag-to-scroll logic ---
const roundsScrollRef = ref(null);
let isDown = false;
let startX;
let scrollLeft;

const onMouseDown = (e) => {
    isDown = true;
    roundsScrollRef.value.classList.add('cursor-grabbing');
    roundsScrollRef.value.classList.remove('cursor-grab');
    startX = e.pageX - roundsScrollRef.value.offsetLeft;
    scrollLeft = roundsScrollRef.value.scrollLeft;
};

const onMouseLeave = () => {
    isDown = false;
    roundsScrollRef.value.classList.remove('cursor-grabbing');
    roundsScrollRef.value.classList.add('cursor-grab');
};

const onMouseUp = () => {
    isDown = false;
    roundsScrollRef.value.classList.remove('cursor-grabbing');
    roundsScrollRef.value.classList.add('cursor-grab');
};

const onMouseMove = (e) => {
    if (!isDown) return;
    e.preventDefault();
    const x = e.pageX - roundsScrollRef.value.offsetLeft;
    const walk = (x - startX) * 1.5; 
    roundsScrollRef.value.scrollLeft = scrollLeft - walk;
};

onMounted(() => {
    window.addEventListener('click', handleOutsideClick);
});

onUnmounted(() => {
    window.removeEventListener('click', handleOutsideClick);
});
const formatSeason = (s, country = null) => {
    if (!s) return '—';
    const year = parseInt(s);
    if (isNaN(year)) return s;

    // Danh sách các quốc gia/giải đấu thường đá trong 1 năm dương lịch (Xuân-Thu)
    const singleYearCountries = [
        'Brazil', 'USA', 'Japan', 'South Korea', 'Norway', 'Sweden', 
        'Finland', 'China', 'Iceland', 'Estonia', 'Latvia', 'Lithuania',
        'Kazakhstan', 'Belarus', 'Republic of Ireland', 'Singapore'
    ];

    // Các giải đấu đặc biệt hoặc World Cup, Euro, Friendly cũng thường hiện 1 năm
    if (country && (singleYearCountries.includes(country) || ['World', 'Europe'].includes(country))) {
        return s.toString();
    }

    // Mặc định cho các giải Thu-Xuân (Châu Âu, Saudi, Việt Nam mới...)
    return `${year}-${year + 1}`;
};
const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    const parts = dateStr.split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dateStr;
};
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.custom-scrollbar::-webkit-scrollbar { width: 3px; height: 3px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(16, 185, 129, 0.1); border-radius: 20px; }
.custom-scrollbar:hover::-webkit-scrollbar-thumb { background: rgba(16, 185, 129, 0.3); }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.05); }
.dark .custom-scrollbar:hover::-webkit-scrollbar-thumb { background: rgba(16, 185, 129, 0.3); }

.dark .custom-scrollbar:hover::-webkit-scrollbar-thumb { background: rgba(16, 185, 129, 0.3); }
</style>

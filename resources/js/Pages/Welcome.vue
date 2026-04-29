<template>
  <Head title="Bảng tin bóng đá AI - Trực tiếp tỉ số & Phân tích" />
  
  <MainLayout>
    <div class="pt-2 pb-8 relative isolate">
      <!-- AI Background Blobs -->
      <div class="absolute -top-24 -left-24 w-96 h-96 bg-emerald-500/10 dark:bg-emerald-500/5 rounded-full blur-[100px] -z-10 animate-pulse"></div>
      <div class="absolute top-1/2 -right-24 w-80 h-80 bg-blue-500/10 dark:bg-blue-500/5 rounded-full blur-[80px] -z-10"></div>
      

      <div class="flex flex-col lg:flex-row gap-12">
        <!-- 2. Main Content: Full Schedule & Results -->
        <div class="flex-1 space-y-8 min-w-0">
            <!-- Header & Date/League Filters -->
            <div class="flex flex-col gap-6 mb-8 relative z-30">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold flex items-center gap-3 text-gray-900 dark:text-white uppercase tracking-tight">
                        <div class="w-1.5 h-6 bg-emerald-500 rounded-full shadow-[0_0_10px_rgba(16,185,129,0.3)]"></div>
                        Lịch Thi Đấu & Kết Quả
                    </h2>
                </div>

                <!-- Date Selector Area -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 relative">
                    <div class="flex items-center gap-2 bg-gray-100/50 dark:bg-gray-800/50 p-1.5 rounded-2xl border border-gray-200/50 dark:border-gray-700/50 backdrop-blur-sm shadow-sm">
                        <!-- Mini Calendar Toggle -->
                        <div class="relative">
                            <button @click="showDatePicker = !showDatePicker"
                                    class="p-2 rounded-xl bg-white dark:bg-gray-700 shadow-sm text-gray-700 dark:text-gray-200 hover:text-emerald-600 dark:hover:text-emerald-400 border border-transparent hover:border-emerald-200 dark:hover:border-emerald-500/30 transition-all">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </button>

                            <!-- Date Picker Dropdown -->
                             <div v-if="showDatePicker" class="absolute top-full left-0 mt-2 z-[60] bg-white/95 dark:bg-gray-800/95 backdrop-blur-xl rounded-[2rem] shadow-2xl border border-gray-100 dark:border-gray-700 p-4 w-72">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-[10px] font-bold uppercase text-gray-400 tracking-widest">Tháng {{ dayjs(props.filters?.date || today).format("M, [Năm] YYYY") }}</span>
                                    <div class="flex gap-1">
                                        <button @click="adjustMonth(-1)" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M15 19l-7-7 7-7" /></svg></button>
                                        <button @click="adjustMonth(1)" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9 5l7 7-7 7" /></svg></button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-7 gap-1 mb-4">
                                    <span v-for="d in ['CN','T2','T3','T4','T5','T6','T7']" :key="d" class="text-center text-[8px] font-bold text-gray-300">{{ d }}</span>
                                    <button v-for="day in calendarDays" :key="day.date" @click="changeDate(day.date); showDatePicker = false;"
                                            class="h-8 w-8 rounded-xl flex items-center justify-center text-[10px] font-bold transition-all"
                                            :class="[day.isCurrentMonth ? '' : 'opacity-20', (filters?.date || today) === day.date ? 'bg-emerald-500 text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300']">
                                        {{ day.dayNum }}
                                    </button>
                                </div>
                                <button @click="changeDate(today); showDatePicker = false;" class="w-full py-2 bg-gray-50 dark:bg-gray-700 rounded-xl text-[9px] font-bold uppercase tracking-widest text-emerald-600">Hôm nay</button>
                            </div>
                        </div>

                        <div class="h-5 w-px bg-gray-200 dark:bg-gray-700 mx-0.5"></div>

                        <!-- Date Slider -->
                        <div class="flex gap-1 overflow-x-auto no-scrollbar max-w-[200px] sm:max-w-[400px]">
                            <button v-for="btn in dateSlider" :key="btn.date" @click="changeDate(btn.date)"
                                    class="px-3 py-2 rounded-xl text-[10px] font-bold transition-all whitespace-nowrap min-w-[60px] text-center"
                                    :class="(filters?.date || today) === btn.date ? 'bg-emerald-600 text-white shadow-lg' : 'text-gray-400 hover:text-emerald-500'">
                                {{ btn.dayNum }}/{{ dayjs(btn.date).format('MM') }}
                            </button>
                        </div>
                    </div>

                    <!-- League Filter Slider -->
                    <div v-if="availableLeagues.length > 0" class="flex gap-1.5 overflow-x-auto no-scrollbar flex-1">
                        <button @click="changeLeague(null)"
                                class="px-4 py-2 rounded-xl text-[9px] font-bold uppercase tracking-widest transition-all border whitespace-nowrap"
                                :class="!filters.league_id ? 'bg-gray-950 dark:bg-white text-white dark:text-gray-950 border-gray-950 dark:border-white shadow-lg' : 'bg-white/50 dark:bg-gray-800/50 text-gray-400 border-gray-100 dark:border-gray-700'">
                            Tất cả
                        </button>
                        <button v-for="league in availableLeagues" :key="league.id" @click="changeLeague(league.id)"
                                class="px-4 py-2 rounded-xl text-[9px] font-bold uppercase tracking-widest transition-all border flex items-center gap-1.5 whitespace-nowrap"
                                :class="filters.league_id == league.id ? 'bg-emerald-500 text-white border-emerald-500 shadow-lg' : 'bg-white/50 dark:bg-gray-800/50 text-gray-400 border-gray-100 dark:border-gray-700'">
                            <img v-if="league.logo_url" :src="league.logo_url" class="w-3 h-3 object-contain" />
                            {{ league.name }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Matches List Grouped by League -->
            <div v-if="Object.keys(groupedGames).length > 0" class="space-y-10">
                <div v-for="(games, leagueName) in groupedGames" :key="leagueName">
                    <!-- League Title -->
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-1 h-4 bg-emerald-500 rounded-full"></div>
                        <h2 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] flex items-center gap-2">
                            {{ leagueName }}
                            <span class="text-[9px] font-bold px-1.5 py-0.5 bg-gray-100 dark:bg-gray-900 rounded-md">{{ games.length }}</span>
                        </h2>
                    </div>

                    <!-- Individual Match Cards -->
                    <div class="grid grid-cols-1 gap-3">
                        <MatchCard v-for="game in games" :key="game.id" :game="game" />
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="flex flex-col items-center justify-center py-24 text-center bg-white/30 dark:bg-gray-800/20 rounded-[3rem] border border-dashed border-gray-200 dark:border-gray-700">
                <div class="w-20 h-20 mb-6 relative">
                    <div class="absolute inset-0 bg-emerald-500/10 rounded-full animate-pulse"></div>
                    <div class="relative w-full h-full flex items-center justify-center">
                        <svg class="w-10 h-10 text-emerald-500/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-lg font-bold mb-2 uppercase tracking-tight text-gray-900 dark:text-white">Không có dữ liệu</h3>
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest max-w-xs mx-auto">Chưa có trận đấu nào được nạp cho ngày {{ dayjs(props.filters?.date || today).locale('vi').format('DD/MM') }}</p>
                <button @click="changeDate(today)" class="mt-8 px-6 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-[10px] font-bold uppercase tracking-widest rounded-xl hover:translate-y-[-2px] transition-all">VỀ HÔM NAY</button>
            </div>
        </div>

        <!-- 3. Sidebar: Top Scorers & Hot Leagues -->
        <div class="space-y-12 shrink-0 w-full lg:w-72">
            <!-- Countries & Leagues Accordion -->
            <div class="space-y-8">
                <!-- Pinned Leagues Section -->
                <div v-if="pinnedLeagues.length > 0">
                   <h2 class="text-lg font-bold mb-6 flex items-center gap-3 text-gray-900 dark:text-white uppercase tracking-tight">
                     <div class="w-1 h-5 bg-emerald-500 rounded-full shadow-[0_0_10px_rgba(16,185,129,0.3)]"></div>
                     Giải đấu của tôi
                   </h2>
                  <div class="space-y-1">
                     <div v-for="league in pinnedLeagues" :key="league.id" 
                          class="group/item flex items-center justify-between p-2 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition-all border border-transparent hover:border-emerald-100 dark:hover:border-emerald-500/20">
                        <Link :href="`/leagues/${league.id}`" class="flex items-center gap-3 min-w-0 flex-1">
                           <div class="w-6 h-6 rounded-md bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-700 flex items-center justify-center p-1 shrink-0">
                               <img v-if="league.logo_url" :src="league.logo_url" class="w-full h-full object-contain" />
                               <span v-else class="text-[8px] font-bold text-emerald-600">{{ league.name.substring(0,2).toUpperCase() }}</span>
                           </div>
                           <div class="flex flex-col min-w-0">
                              <span class="text-xs font-bold text-gray-700 dark:text-gray-200 truncate">{{ league.name }}</span>
                              <span class="text-[8px] font-bold text-gray-400 uppercase tracking-widest">{{ getLocalizedCountryName(league.country_name) }}</span>
                           </div>
                        </Link>
                        <button @click="togglePin(league.id)" class="p-1.5 text-gray-300 hover:text-emerald-500 transition-colors">
                           <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" /></svg>
                        </button>
                     </div>
                  </div>
               </div>

               <div>
                  <h2 class="text-lg font-bold mb-6 flex items-center gap-3 text-gray-900 dark:text-white uppercase tracking-tight">
                    <div class="w-1 h-5 bg-emerald-500 rounded-full shadow-[0_0_10px_rgba(16,185,129,0.3)]"></div>
                    Tất cả giải đấu
                  </h2>
                  
                  <div class="space-y-1 pr-1">
                     <div v-for="group in sortedLeagues" :key="group.country_name" class="group">
                        <!-- Country Header -->
                        <button @click="toggleCountry(group.country_name)"
                                :class="[
                                    'w-full flex items-center justify-between px-3 py-2.5 rounded-xl transition-all border outline-none',
                                    expandedCountry === group.country_name 
                                        ? 'bg-emerald-500 text-white border-emerald-400 shadow-lg shadow-emerald-500/10' 
                                        : 'bg-white/50 dark:bg-gray-800/50 border-gray-100 dark:border-gray-700 hover:border-emerald-300 dark:hover:border-emerald-500/50'
                                ]">
                            <div class="flex items-center gap-2 min-w-0">
                                <!-- Country Flag Icon -->
                                <div class="w-5 h-5 rounded-sm bg-white dark:bg-gray-900 flex items-center justify-center border border-gray-100 dark:border-gray-700 overflow-hidden shrink-0 shadow-sm relative">
                                    <img :src="getFlagUrl(group.country_name, group.country_code)" 
                                         class="w-full h-full object-cover"
                                         @error="(e) => (e.target.style.display = 'none')" />
                                    <span class="absolute inset-0 flex items-center justify-center text-[7px] font-bold pointer-events-none" 
                                          :class="expandedCountry === group.country_name ? 'text-white' : 'text-gray-400'"
                                          style="z-index: -1;">
                                        {{ group.country_code ? group.country_code.split(' ')[0].toUpperCase().substring(0, 2) : '??' }}
                                    </span>
                                </div>
                                <span class="text-[11px] font-bold truncate">{{ getLocalizedCountryName(group.country_name) }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span :class="['text-[8px] font-bold px-1 py-0.5 rounded', expandedCountry === group.country_name ? 'bg-white/20' : 'bg-gray-100 dark:bg-gray-700 text-gray-500']">
                                    {{ group.leagues.length }}
                                </span>
                                <svg :class="['w-2.5 h-2.5 transition-transform duration-300', expandedCountry === group.country_name ? 'rotate-180' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </button>

                        <!-- Leagues List (Accordion Content) -->
                        <div v-show="expandedCountry === group.country_name" class="mt-2 ml-4 space-y-1 overflow-hidden transition-all">
                            <div v-for="league in group.leagues" :key="league.id" class="group/item flex items-center justify-between">
                                <Link :href="`/leagues/${league.id}`"
                                      class="flex items-center gap-3 p-2 flex-1 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition-colors">
                                    <div class="w-6 h-6 rounded-md bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-700 flex items-center justify-center p-1 shrink-0">
                                        <img v-if="league.logo_url" :src="league.logo_url" class="w-full h-full object-contain" />
                                        <span v-else class="text-[8px] font-bold text-emerald-600">{{ league.name.substring(0,2).toUpperCase() }}</span>
                                    </div>
                                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400 group-hover/item:text-emerald-600 transition-colors truncate">{{ league.name }}</span>
                                </Link>
                                <button @click="togglePin(league.id)" 
                                        :class="[
                                            'p-1.5 transition-colors',
                                            pinnedLeagueIds.includes(league.id) ? 'text-emerald-500' : 'text-gray-200 hover:text-emerald-300'
                                        ]">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" /></svg>
                                </button>
                            </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '../Layouts/MainLayout.vue';
import MatchCard from '@/Components/MatchCard.vue';
import { getFullDisplay } from '../Constants/countries';
import dayjs from "dayjs";

const props = defineProps({
    groupedGames: { type: Object, default: () => ({}) },
    filters: {
        type: Object,
        default: () => ({
            date: dayjs().format("YYYY-MM-DD"),
            league_id: null,
        }),
    },
    availableLeagues: { type: Array, default: () => [] },
});

// Lấy Leagues từ Shared Props (Bền vững, không load lại khi đổi ngày)
const groupedLeagues = computed(() => usePage().props.sharedLeagues || []);

const pinnedLeagueIds = ref([]);

onMounted(() => {
    const saved = localStorage.getItem('pinnedLeagues');
    if (saved) {
        try {
            pinnedLeagueIds.value = JSON.parse(saved);
        } catch (e) {
            pinnedLeagueIds.value = [];
        }
    }
});

const togglePin = (leagueId) => {
    const index = pinnedLeagueIds.value.indexOf(leagueId);
    if (index > -1) {
        pinnedLeagueIds.value.splice(index, 1);
    } else {
        pinnedLeagueIds.value.push(leagueId);
    }
    localStorage.setItem('pinnedLeagues', JSON.stringify(pinnedLeagueIds.value));
};

const pinnedLeagues = computed(() => {
    if (!groupedLeagues.value) return [];
    const all = [];
    groupedLeagues.value.forEach(group => {
        group.leagues.forEach(l => {
            if (pinnedLeagueIds.value.includes(l.id)) {
                all.push({ ...l, country_name: group.country_name });
            }
        });
    });
    return all;
});

const showDatePicker = ref(false);
const currentMonthOffset = ref(0);
const today = dayjs().format("YYYY-MM-DD");
const expandedCountry = ref('England');

const toggleCountry = (name) => {
    expandedCountry.value = expandedCountry.value === name ? null : name;
};

const calendarDays = computed(() => {
    const baseDate = props.filters.date || today;
    const startOfMonth = dayjs(baseDate).add(currentMonthOffset.value, "month").startOf("month");
    const start = startOfMonth.startOf("week");
    const days = [];
    for (let i = 0; i < 42; i++) {
        const d = start.add(i, "day");
        days.push({
            date: d.format("YYYY-MM-DD"),
            dayNum: d.format("D"),
            isCurrentMonth: d.month() === startOfMonth.month(),
        });
    }
    return days;
});

const adjustMonth = (offset) => {
    currentMonthOffset.value += offset;
};

const dateSlider = computed(() => {
    const dates = [];
    const baseDate = props.filters.date || today;
    const start = dayjs(baseDate).subtract(3, "day");
    for (let i = 0; i < 7; i++) {
        const d = start.add(i, "day");
        dates.push({
            date: d.format("YYYY-MM-DD"),
            dayNum: d.format("DD"),
            month: d.format("MMM"),
        });
    }
    return dates;
});

const changeDate = (date) => {
    router.get('/', {
        date,
        league_id: props.filters.league_id,
    }, { preserveState: true });
};

const changeLeague = (leagueId) => {
    router.get('/', {
        date: props.filters.date,
        league_id: leagueId,
    }, { preserveState: true });
};

const getFlagUrl = (rawName, countryCode) => {
    if (!rawName && !countryCode) return '';
    
    const mapping = {
        'England': 'gb-eng', 'Scotland': 'gb-sct', 'Wales': 'gb-wls',
        'Germany': 'de', 'Spain': 'es', 'Italy': 'it', 'France': 'fr',
        'Vietnam': 'vn', 'Brazil': 'br', 'Argentina': 'ar', 'Portugal': 'pt',
        'Netherlands': 'nl', 'World': 'un'
    };
    
    if (mapping[rawName]) return `https://flagcdn.com/w80/${mapping[rawName]}.png`;
    if (countryCode) return `https://flagcdn.com/w80/${countryCode.toLowerCase()}.png`;
    
    const flagCode = rawName.toLowerCase().substring(0, 2);
    return `https://flagcdn.com/w80/${flagCode}.png`;
};

const getLocalizedCountryName = (rawName) => {
    // Nếu có hàm getFullDisplay thì dùng, không thì trả về tên gốc
    try {
        return getFullDisplay(rawName) || rawName;
    } catch (e) {
        return rawName;
    }
};

const sortedLeagues = computed(() => {
    if (!groupedLeagues.value || groupedLeagues.value.length === 0) return [];
    
    const priorities = ['England', 'Spain', 'Italy', 'Germany', 'France', 'Vietnam', 'World', 'Brazil'];
    
    return [...groupedLeagues.value].sort((a, b) => {
        const indexA = priorities.indexOf(a.country_name);
        const indexB = priorities.indexOf(b.country_name);
        
        if (indexA !== -1 && indexB !== -1) return indexA - indexB;
        if (indexA !== -1) return -1;
        if (indexB !== -1) return 1;
        
        return a.country_name.localeCompare(b.country_name);
    });
});
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
  background: #334155;
}
</style>

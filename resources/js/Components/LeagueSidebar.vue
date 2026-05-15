<template>
    <div class="space-y-6 shrink-0 w-full lg:w-72">
        <!-- Favorite Teams Section -->
        <div v-if="($page.props.globalFavoriteTeams || []).length > 0">
            <h2 class="text-lg font-bold mb-6 flex items-center gap-3 text-gray-900 dark:text-white uppercase tracking-tight">
                <div class="w-1 h-5 bg-rose-500 rounded-full shadow-[0_0_10px_rgba(244,63,94,0.3)]"></div>
                Đội bóng yêu thích
            </h2>
            <div class="grid grid-cols-2 gap-3">
                <div v-for="team in ($page.props.globalFavoriteTeams || [])" :key="team.id" 
                    class="relative flex flex-col items-center p-3 rounded-2xl bg-white/50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 hover:border-rose-300 dark:hover:border-rose-500/50 transition-all group shadow-sm">
                    <Link :href="`/teams/${team.id}`" class="flex flex-col items-center w-full">
                        <div class="w-10 h-10 mb-2 p-1 bg-white dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-700 flex items-center justify-center shrink-0 shadow-inner group-hover:scale-110 transition-transform">
                            <img v-if="team.logo_url" :src="team.logo_url" class="w-full h-full object-contain" />
                        </div>
                        <span class="text-[10px] font-bold text-gray-700 dark:text-gray-200 text-center truncate w-full uppercase tracking-tighter">{{ team.name }}</span>
                    </Link>
                    <button @click="toggleFavorite(team.id)" 
                            class="absolute top-1 right-1 p-2 text-gray-300 hover:text-rose-500 opacity-0 group-hover:opacity-100 transition-all bg-white/10 dark:bg-black/20 backdrop-blur-sm rounded-lg"
                            title="Bỏ yêu thích">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

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
                        <div class="w-8 h-8 rounded-lg bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-700 flex items-center justify-center p-0.5 shrink-0 shadow-sm">
                            <img v-if="league.logo_url" :src="league.logo_url" class="w-full h-full object-contain" />
                            <span v-else class="text-[10px] font-bold text-emerald-600">{{ league.name.substring(0,2).toUpperCase() }}</span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-200 truncate">{{ league.name }}</span>
                            <span class="text-[8px] font-bold text-gray-400 uppercase tracking-widest">{{ getLocalizedCountryName(league.country_name) }}</span>
                        </div>
                    </Link>
                    <button @click="togglePin(league.id)" 
                            class="p-2.5 text-emerald-500 bg-emerald-500/5 hover:bg-emerald-500/10 rounded-lg transition-all"
                            title="Bỏ ghim">
                        <svg class="w-4.5 h-4.5" fill="currentColor" viewBox="0 0 20 20"><path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" /></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- All Leagues Accordion -->
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
                            <div class="w-6 h-6 rounded-md bg-white dark:bg-gray-900 flex items-center justify-center border border-gray-100 dark:border-gray-700 overflow-hidden shrink-0 shadow-sm relative">
                                <img :src="getFlagUrl(group.country_name, group.country_code)" 
                                    class="w-full h-full object-cover"
                                    @error="(e) => (e.target.style.display = 'none')" />
                                <span class="absolute inset-0 flex items-center justify-center text-[8px] font-bold pointer-events-none" 
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
                                <div class="w-8 h-8 rounded-lg bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-700 flex items-center justify-center p-0.5 shrink-0 shadow-sm">
                                    <img v-if="league.logo_url" :src="league.logo_url" class="w-full h-full object-contain" />
                                    <span v-else class="text-[10px] font-bold text-emerald-600">{{ league.name.substring(0,2).toUpperCase() }}</span>
                                </div>
                                <span class="text-xs font-bold text-gray-600 dark:text-gray-400 group-hover/item:text-emerald-600 transition-colors truncate">{{ league.name }}</span>
                            </Link>
                            <button @click="togglePin(league.id)" 
                                    :class="[
                                        'p-2.5 rounded-lg transition-all',
                                        pinnedLeagueIds.includes(league.id) 
                                            ? 'text-emerald-500 bg-emerald-500/5 shadow-sm' 
                                            : 'text-gray-300 hover:text-emerald-400 hover:bg-emerald-500/5'
                                    ]"
                                    :title="pinnedLeagueIds.includes(league.id) ? 'Bỏ ghim' : 'Ghim giải đấu'">
                                <svg class="w-4.5 h-4.5" fill="currentColor" viewBox="0 0 20 20"><path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { getFullDisplay } from '../Constants/countries';

const page = usePage();
const props = defineProps({
    favoriteTeams: { type: Array, default: () => [] },
});

// Lấy đội bóng yêu thích từ prop hoặc shared props
const allFavoriteTeams = computed(() => {
    // Ưu tiên prop nếu có dữ liệu (cho khả năng ghi đè), nếu không dùng dữ liệu dùng chung
    if (props.favoriteTeams && props.favoriteTeams.length > 0) {
        return props.favoriteTeams;
    }
    return page.props.globalFavoriteTeams || [];
});

// Lấy Leagues từ Shared Props
const groupedLeagues = computed(() => {
    return page.props.sharedLeagues || [];
});

// Lấy thông tin user để lưu ghim riêng biệt cho từng tài khoản
const user = computed(() => page.props.auth?.user);
const storageKey = computed(() => user.value ? `pinnedLeagues_user_${user.value.id}` : 'pinnedLeagues_guest');

const pinnedLeagueIds = ref([]);
const expandedCountry = ref('England');

const loadPins = () => {
    if (typeof window === 'undefined') return;
    const saved = localStorage.getItem(storageKey.value);
    if (saved) {
        try {
            pinnedLeagueIds.value = JSON.parse(saved);
        } catch (e) {
            pinnedLeagueIds.value = [];
        }
    } else {
        pinnedLeagueIds.value = [];
    }
};

onMounted(() => {
    loadPins();
});

watch(() => storageKey.value, () => {
    loadPins();
});

const togglePin = (leagueId) => {
    const index = pinnedLeagueIds.value.indexOf(leagueId);
    if (index > -1) {
        pinnedLeagueIds.value.splice(index, 1);
    } else {
        pinnedLeagueIds.value.push(leagueId);
    }
    localStorage.setItem(storageKey.value, JSON.stringify(pinnedLeagueIds.value));
};

const toggleFavorite = (teamId) => {
    router.post(`/teams/${teamId}/favorite`, {}, {
        preserveScroll: true,
    });
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

const toggleCountry = (name) => {
    expandedCountry.value = expandedCountry.value === name ? null : name;
};

const getFlagUrl = (rawName, countryCode) => {
    if (!rawName && !countryCode) return '';
    
    const mapping = {
        'England': 'gb-eng', 'Scotland': 'gb-sct', 'Wales': 'gb-wls',
        'Germany': 'de', 'Spain': 'es', 'Italy': 'it', 'France': 'fr',
        'Vietnam': 'vn', 'Brazil': 'br', 'Argentina': 'ar', 'Portugal': 'pt',
        'Netherlands': 'nl', 'Belgium': 'be', 'Switzerland': 'ch', 'Turkey': 'tr',
        'Saudi Arabia': 'sa', 'USA': 'us', 'Mexico' : 'mx', 'Poland': 'pl',
        'Bulgaria': 'bg', 'Romania': 'ro', 'Greece': 'gr', 'Tunisia': 'tn',
        'Japan': 'jp', 'South Korea': 'kr', 'China': 'cn', 'Morocco': 'ma',
        'Norway': 'no', 'Finland': 'fi', 'Nigeria': 'ng', 'Sweden': 'se',
        'Africa': 'un', 'Europe': 'eu', 'International': 'un', 'World': 'un', 'South America': 'un'
    };
    
    if (mapping[rawName]) return `https://flagcdn.com/w80/${mapping[rawName]}.png`;
    if (countryCode) return `https://flagcdn.com/w80/${countryCode.toLowerCase()}.png`;
    
    const flagCode = rawName.toLowerCase().substring(0, 2);
    return `https://flagcdn.com/w80/${flagCode}.png`;
};

const getLocalizedCountryName = (rawName) => {
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

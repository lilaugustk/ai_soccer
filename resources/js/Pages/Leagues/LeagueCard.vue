<template>
  <Link :href="'/leagues/' + league.id" 
        class="group relative bg-white dark:bg-gray-800/40 backdrop-blur-xl p-5 rounded-[2rem] border border-gray-100 dark:border-gray-700/50 hover:border-emerald-500/50 hover:shadow-2xl hover:shadow-emerald-500/10 transition-all duration-500 flex flex-col items-center">
    
    <!-- League Logo Section -->
    <div class="relative mb-4">
        <div class="w-20 h-20 rounded-2xl bg-gray-50 dark:bg-gray-700/30 p-4 flex items-center justify-center group-hover:scale-110 transition-all duration-500 border border-gray-100/50 dark:border-gray-600/30 shadow-inner overflow-hidden">
           <img v-if="league.logo_url" :src="league.logo_url" class="w-full h-full object-contain relative z-10" />
           <div v-else class="text-xl font-bold text-gray-300 uppercase relative z-10">{{ league.name.substring(0, 2) }}</div>
        </div>

        <!-- Today Badge (Repositioned to not overlap) -->
        <div v-if="league.today_matches_count > 0" 
             class="absolute -bottom-2 -right-2 flex items-center gap-1 px-2 py-0.5 bg-emerald-500 text-white rounded-lg text-[7px] font-bold uppercase tracking-widest shadow-lg shadow-emerald-500/30 border border-emerald-400 z-20">
          <span class="w-1 h-1 rounded-full bg-white animate-pulse"></span>
          {{ league.today_matches_count }} TRẬN
        </div>
    </div>

    <!-- League Info -->
    <div class="text-center w-full space-y-1">
        <h3 class="text-xs font-bold text-gray-900 dark:text-white group-hover:text-emerald-500 transition-colors uppercase tracking-tight line-clamp-1">
            {{ league.name }}
        </h3>
        
        <div class="flex items-center justify-center gap-1.5">
            <span class="w-3.5 h-2.5 rounded-[1px] overflow-hidden bg-gray-100 dark:bg-gray-700 border border-gray-100 dark:border-gray-600 shrink-0">
                <img :src="getFlagUrl(league.country_name)" class="w-full h-full object-cover" @error="(e) => e.target.style.display='none'" />
            </span>
            <p class="text-[9px] font-medium text-gray-400 dark:text-gray-500 uppercase tracking-[0.1em] truncate">
                {{ league.country_name || 'Quốc tế' }}
            </p>
            <span v-if="league.is_women" class="shrink-0 text-[7px] font-extrabold uppercase bg-pink-500/10 text-pink-500 px-1.5 py-0.5 rounded border border-pink-400/20">NỮ</span>
        </div>
    </div>

    <!-- Subtle Footer Info -->
    <div class="mt-4 pt-3 border-t border-gray-50 dark:border-gray-700/30 w-full flex justify-between items-center px-1">
        <span class="text-[8px] font-bold text-gray-300 dark:text-gray-600 uppercase tracking-widest">ID: #{{ league.id }}</span>
        <svg class="w-3 h-3 text-gray-300 group-hover:text-emerald-500 group-hover:translate-x-1 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" />
        </svg>
    </div>
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  league: Object,
  isFeatured: Boolean
});

const getFlagUrl = (countryName) => {
    if (!countryName || countryName === 'Quốc tế' || countryName === 'World') return 'https://flagcdn.com/w40/un.png';
    return `https://flagcdn.com/w40/${getCountryCode(countryName)}.png`;
};

const getCountryCode = (name) => {
    const map = {
        'England': 'gb-eng', 'Spain': 'es', 'Germany': 'de', 'Italy': 'it', 'France': 'fr',
        'Vietnam': 'vn', 'Japan': 'jp', 'South Korea': 'kr', 'Brazil': 'br', 'Argentina': 'ar',
        'Portugal': 'pt', 'Netherlands': 'nl', 'Belgium': 'be', 'Russia': 'ru', 'USA': 'us',
        'Scotland': 'gb-sct', 'Wales': 'gb-wls', 'Northern-Ireland': 'gb-nir'
    };
    return map[name] || name.toLowerCase().substring(0, 2);
};
</script>

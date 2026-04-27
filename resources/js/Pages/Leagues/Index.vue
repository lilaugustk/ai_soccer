<template>
  <Head title="Hệ Thống Giải Đấu | AI Soccer" />
  
  <MainLayout>
    <div class="py-8 relative min-h-screen">
       <!-- AI Background Blobs -->
       <div class="absolute -top-24 -left-24 w-96 h-96 bg-emerald-500/10 dark:bg-emerald-500/5 rounded-full blur-[100px] -z-10 animate-pulse"></div>
       <div class="absolute bottom-24 -right-24 w-80 h-80 bg-blue-500/10 dark:bg-blue-500/5 rounded-full blur-[80px] -z-10"></div>

       <div class="flex flex-col lg:flex-row gap-8">
          
          <!-- LEFT SIDEBAR: Country Navigation -->
          <div class="w-full lg:w-64 shrink-0 space-y-6">
            <div class="sticky top-24">
                <div class="bg-white/70 dark:bg-gray-800/50 backdrop-blur-xl rounded-[2rem] border border-gray-100 dark:border-gray-700/50 p-6 shadow-xl shadow-gray-200/20 dark:shadow-none">
                    <h2 class="text-[10px] font-black text-emerald-500 uppercase tracking-[0.1em] mb-6 flex items-center gap-2">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 002 2 2 2 0 012 2v.5m.43 5.432a2 2 0 01-1.077 1.253L12 21l-1.353-1.353a2 2 0 01-1.253-1.077L9 18.216" />
                        </svg>
                        Quốc Gia & Khu Vực
                    </h2>
                    
                    <div class="space-y-1 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                        <button @click="selectedCountry = 'featured'" 
                                :class="[selectedCountry === 'featured' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/20' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50']"
                                class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all">
                            <span>Giải Đấu Tiêu Biểu</span>
                            <span class="text-[8px] opacity-60">{{ featuredLeagues.length }}</span>
                        </button>
                        
                        <div class="h-px bg-gray-50 dark:bg-gray-700/50 my-3"></div>
                        
                        <button v-for="group in groupedLeagues" :key="group.country"
                                @click="selectedCountry = group.country"
                                :class="[selectedCountry === group.country ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/20' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50']"
                                class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all group">
                            <div class="flex items-center gap-2">
                                <span class="w-4 h-3 rounded-[2px] overflow-hidden bg-gray-100 dark:bg-gray-700 flex items-center justify-center shrink-0 border border-gray-100 dark:border-gray-600">
                                    <img :src="getFlagUrl(group.country)" class="w-full h-full object-cover" @error="(e) => e.target.style.display='none'" />
                                </span>
                                <span class="truncate">{{ group.country }}</span>
                            </div>
                            <span class="text-[8px] opacity-60 group-hover:opacity-100">{{ group.leagues.length }}</span>
                        </button>
                    </div>
                </div>
            </div>
          </div>

          <!-- MAIN CONTENT -->
          <div class="flex-1 space-y-10">
             <!-- Header & Search -->
             <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                  <h1 class="text-3xl font-bold text-gray-900 dark:text-white uppercase tracking-tight leading-none">
                      Hệ Thống <span class="text-emerald-500">Giải Đấu</span>
                  </h1>
                  <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.1em] mt-3">Khám phá {{ allLeagues.length }}+ giải đấu hàng đầu thế giới</p>
                </div>

                <div class="relative group w-full md:w-96">
                   <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                      <svg class="h-4 w-4 text-gray-400 group-focus-within:text-emerald-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                      </svg>
                   </div>
                   <input v-model="searchQuery" 
                          type="text" 
                          placeholder="TÌM TÊN GIẢI ĐẤU HOẶC QUỐC GIA..."
                          class="block w-full pl-11 pr-4 py-3.5 bg-white dark:bg-gray-800/80 border border-gray-100 dark:border-gray-700 rounded-2xl text-[10px] font-bold placeholder-gray-400 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500/50 backdrop-blur-md transition-all uppercase tracking-[0.1em] shadow-xl shadow-gray-200/20 dark:shadow-none">
                </div>
             </div>

             <!-- Grid Layout -->
             <div class="space-y-12">
                <!-- If Search Query Exists -->
                <div v-if="searchQuery" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                   <LeagueCard v-for="league in searchResults" :key="league.id" :league="league" />
                </div>

                <!-- Featured Section (When no search and selectedCountry is featured) -->
                <div v-else-if="selectedCountry === 'featured' || !selectedCountry" class="space-y-8">
                   <div class="flex items-center gap-3">
                      <div class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)]"></div>
                      <h2 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-[0.1em]">Giải Đấu Tiêu Biểu</h2>
                   </div>
                   <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                      <LeagueCard v-for="league in featuredLeagues" :key="league.id" :league="league" is-featured />
                   </div>
                </div>

                <!-- Country Specific Section -->
                <div v-else class="space-y-8">
                   <div class="flex items-center gap-4 bg-white/50 dark:bg-gray-800/30 p-4 rounded-2xl border border-gray-100 dark:border-gray-700">
                      <div class="w-12 h-8 rounded-lg overflow-hidden border border-gray-100 dark:border-gray-600 shadow-sm">
                         <img :src="getFlagUrl(selectedCountry)" class="w-full h-full object-cover" />
                      </div>
                      <div>
                         <h2 class="text-lg font-bold text-gray-900 dark:text-white uppercase tracking-tight">{{ selectedCountry }}</h2>
                         <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">{{ getLeaguesInCountry(selectedCountry).length }} Giải Đấu</p>
                      </div>
                   </div>
                   <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                      <LeagueCard v-for="league in getLeaguesInCountry(selectedCountry)" :key="league.id" :league="league" />
                   </div>
                </div>

                <!-- Empty State -->
                <div v-if="searchQuery && searchResults.length === 0" class="py-32 flex flex-col items-center justify-center text-center">
                    <div class="w-20 h-20 bg-gray-50 dark:bg-gray-800/50 rounded-[2.5rem] flex items-center justify-center mb-6 border border-gray-100 dark:border-gray-700/50 shadow-inner">
                       <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                       </svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">Không tìm thấy giải đấu</h3>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mt-2">Vui lòng thử từ khóa khác hoặc lọc theo quốc gia</p>
                </div>
             </div>
          </div>
       </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import MainLayout from '../../Layouts/MainLayout.vue';
import LeagueCard from './LeagueCard.vue';

const props = defineProps({
  featuredLeagues: Array,
  groupedLeagues: Array,
  allLeagues: Array
});

const searchQuery = ref('');
const selectedCountry = ref('featured');

const searchResults = computed(() => {
  const query = searchQuery.value.toLowerCase().trim();
  if (!query) return [];
  return props.allLeagues.filter(l => 
    l.name.toLowerCase().includes(query) || 
    (l.country_name && l.country_name.toLowerCase().includes(query))
  ).slice(0, 50);
});

const getLeaguesInCountry = (country) => {
    const group = props.groupedLeagues.find(g => g.country === country);
    return group ? group.leagues : [];
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
</script>

<style>
.custom-scrollbar::-webkit-scrollbar {
  width: 3px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(16, 185, 129, 0.2);
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(16, 185, 129, 0.4);
}
</style>

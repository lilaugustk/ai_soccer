<template>
    <Head title="Câu Lạc Bộ | AI Soccer" />

    <MainLayout>
        <div class="py-8 relative min-h-screen">
            <!-- AI Background Blobs -->
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-emerald-500/10 dark:bg-emerald-500/5 rounded-full blur-[100px] -z-10 animate-pulse"></div>
            <div class="absolute bottom-24 -right-24 w-80 h-80 bg-blue-500/10 dark:bg-blue-500/5 rounded-full blur-[80px] -z-10"></div>

            <!-- Header & Search Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
                <div>
                  <h1 class="text-3xl font-bold text-gray-900 dark:text-white uppercase tracking-tight leading-none">
                      Hệ Thống <span class="text-emerald-500">Câu Lạc Bộ</span>
                  </h1>
                  <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.1em] mt-3">Phân tích dữ liệu từ {{ teams.total }}+ đội bóng</p>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto">
                    <!-- Search Bar -->
                    <div class="relative group w-full md:w-80">
                       <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                          <svg class="h-4 w-4 text-gray-400 group-focus-within:text-emerald-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                          </svg>
                       </div>
                       <input v-model="search" 
                              type="text" 
                              placeholder="TÌM ĐỘI BÓNG..."
                              class="block w-full pl-11 pr-4 py-3 bg-white dark:bg-gray-800/80 border border-gray-100 dark:border-gray-700 rounded-2xl text-[10px] font-bold placeholder-gray-400 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500/50 backdrop-blur-md transition-all uppercase tracking-[0.05em] shadow-xl shadow-gray-200/20 dark:shadow-none">
                    </div>

                    <!-- Country Filter -->
                    <div class="relative country-dropdown min-w-[200px]">
                        <button @click.stop="isCountryOpen = !isCountryOpen"
                                class="w-full flex items-center justify-between px-5 py-3 bg-white dark:bg-gray-800/80 border border-gray-100 dark:border-gray-700 rounded-2xl text-[10px] font-bold uppercase tracking-wider text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 transition-all shadow-xl shadow-gray-200/20 dark:shadow-none">
                            <span class="truncate">{{ getCountryLabel() }}</span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-300" :class="{ 'rotate-180': isCountryOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        
                        <transition enter-active-class="transition duration-200 ease-out" enter-from-class="transform scale-95 opacity-0 -translate-y-2" enter-to-class="transform scale-100 opacity-100 translate-y-0" leave-active-class="transition duration-150 ease-in" leave-from-class="transform scale-100 opacity-100 translate-y-0" leave-to-class="transform scale-95 opacity-0 -translate-y-2">
                            <div v-show="isCountryOpen" class="absolute top-full right-0 mt-3 w-full max-h-64 overflow-y-auto bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-2xl py-2 z-50 custom-scrollbar">
                                 <button @click="selectedCountry = 'all'; isCountryOpen = false" class="w-full text-left px-5 py-2.5 text-[10px] font-bold uppercase tracking-wider hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition-colors" :class="selectedCountry === 'all' ? 'text-emerald-500 bg-emerald-50/50' : 'text-gray-500 dark:text-gray-400'">Tất cả quốc gia</button>
                                 <button v-for="c in countries" :key="c.value" @click="selectedCountry = c.value; isCountryOpen = false" class="w-full text-left px-5 py-2.5 text-[10px] font-bold uppercase tracking-wider hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition-colors flex items-center justify-between group" :class="selectedCountry === c.value ? 'text-emerald-500 bg-emerald-50/50' : 'text-gray-500 dark:text-gray-400'">
                                    <span>{{ c.label }}</span>
                                    <span class="text-[8px] opacity-0 group-hover:opacity-100 transition-opacity">Chọn</span>
                                </button>
                            </div>
                        </transition>
                    </div>
                </div>
            </div>

            <!-- League Quick Filter -->
            <div class="mb-12">
                <div class="flex items-center gap-3 mb-6">
                  <div class="w-1.5 h-4 bg-emerald-500 rounded-full"></div>
                  <h2 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.1em]">Lọc theo giải đấu phổ biến</h2>
                </div>
                
                <div class="flex items-center gap-3 overflow-x-auto pb-4 no-scrollbar -mx-4 px-4 scroll-smooth">
                    <button @click="selectedLeague = 'all'" class="px-6 py-3 rounded-2xl text-[10px] font-bold uppercase tracking-wider transition-all whitespace-nowrap border" :class="selectedLeague === 'all' ? 'bg-emerald-500 border-emerald-500 text-white shadow-lg shadow-emerald-500/20' : 'bg-white dark:bg-gray-800/50 border-gray-100 dark:border-gray-700 text-gray-500 hover:border-emerald-500/50'">Tất cả</button>
                    <button v-for="league in leagues" :key="league.id" @click="selectedLeague = league.id" class="flex items-center gap-3 px-6 py-3 rounded-2xl text-[10px] font-bold uppercase tracking-wider transition-all whitespace-nowrap border group" :class="selectedLeague == league.id ? 'bg-emerald-500 border-emerald-500 text-white shadow-lg shadow-emerald-500/20' : 'bg-white dark:bg-gray-800/50 border-gray-100 dark:border-gray-700 text-gray-500 hover:border-emerald-500/50'">
                        <div class="w-5 h-5 rounded-lg bg-gray-50 dark:bg-gray-900/50 flex items-center justify-center p-0.5 group-hover:scale-110 transition-transform">
                            <img v-if="league.logo_url" :src="league.logo_url" class="w-full h-full object-contain" />
                            <span v-else class="text-[8px]">{{ league.name.substring(0,1) }}</span>
                        </div>
                        {{ league.name }}
                    </button>
                </div>
            </div>

            <!-- Teams Grid -->
            <div v-if="teams.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <Link v-for="team in teams.data" :key="team.id" :href="`/teams/${team.id}`" class="group relative bg-white dark:bg-gray-800/40 backdrop-blur-xl p-6 rounded-[2.5rem] border border-gray-100 dark:border-gray-700/50 hover:border-emerald-500/50 hover:shadow-2xl hover:shadow-emerald-500/10 transition-all duration-500 flex flex-col items-center">
                    <!-- Team Logo Section -->
                    <div class="w-20 h-20 rounded-[1.5rem] bg-gray-50 dark:bg-gray-700/30 p-4 flex items-center justify-center mb-6 group-hover:scale-110 group-hover:rotate-3 transition-all duration-500 border border-gray-100/50 dark:border-gray-600/30 shadow-inner relative overflow-hidden">
                       <img v-if="team.logo_url" :src="team.logo_url" :alt="team.name" class="w-full h-full object-contain relative z-10" />
                       <div v-else class="text-xl font-bold text-gray-300 uppercase relative z-10">{{ team.name.charAt(0) }}</div>
                       <div class="absolute inset-0 bg-emerald-500/0 group-hover:bg-emerald-500/5 transition-colors"></div>
                    </div>

                    <!-- Team Info -->
                    <div class="text-center w-full space-y-1 mb-6">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-emerald-500 transition-colors uppercase tracking-tight line-clamp-1 px-2">
                            {{ team.name }}
                        </h3>
                        <p class="text-[9px] font-medium text-gray-400 dark:text-gray-500 uppercase tracking-[0.1em] truncate">
                            {{ team.country || 'Câu lạc bộ' }}
                        </p>
                    </div>

                    <!-- Team Stats/ID Footer -->
                    <div class="mt-auto pt-4 border-t border-gray-50 dark:border-gray-700/50 w-full flex justify-between items-center px-1">
                        <span class="text-[8px] font-bold text-gray-300 dark:text-gray-600 uppercase tracking-widest">ID: #{{ team.id }}</span>
                        <div class="flex items-center gap-1.5">
                             <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
                             <span class="text-[8px] font-bold text-emerald-500 uppercase tracking-wider">Active</span>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-else class="py-32 flex flex-col items-center justify-center text-center">
                <div class="w-20 h-20 bg-gray-50 dark:bg-gray-800/50 rounded-[2.5rem] flex items-center justify-center mb-6 border border-gray-100 dark:border-gray-700">
                    <svg class="w-10 h-10 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-tight">KHÔNG TÌM THẤY ĐỘI BÓNG</h3>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-2">Vui lòng thử lại với tên khác hoặc lọc theo quốc gia khác</p>
            </div>

            <!-- Pagination -->
            <div v-if="teams.links.length > 3" class="mt-20 flex flex-wrap justify-center items-center gap-2">
                <template v-for="(link, index) in teams.links" :key="index">
                    <!-- Previous/Next Buttons -->
                    <Link 
                        v-if="link.label.includes('Previous') || link.label.includes('Next')"
                        :href="link.url || '#'"
                        v-html="link.label.replace('Previous', 'TRƯỚC').replace('Next', 'SAU')"
                        class="px-6 py-3 rounded-2xl border text-[9px] font-black tracking-[0.1em] transition-all flex items-center justify-center min-w-[120px]"
                        :class="[
                            !link.url ? 'opacity-20 cursor-not-allowed bg-transparent border-gray-100 dark:border-gray-800 text-gray-300' : 'bg-white dark:bg-gray-800/50 border-gray-100 dark:border-gray-700 text-gray-500 hover:border-emerald-500/50 hover:text-emerald-500 shadow-sm'
                        ]"
                    />
                    
                    <!-- Numbered Buttons -->
                    <Link 
                        v-else-if="link.label !== '...'"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="w-12 h-12 rounded-2xl border text-[11px] font-bold transition-all flex items-center justify-center"
                        :class="[
                            link.active ? 'bg-emerald-500 border-emerald-500 text-white shadow-xl shadow-emerald-500/30 rotate-3' : 'bg-white dark:bg-gray-800/50 border-gray-100 dark:border-gray-700 text-gray-500 hover:border-emerald-500/50 hover:text-emerald-500 shadow-sm'
                        ]"
                    />

                    <!-- Ellipsis -->
                    <span 
                        v-else
                        class="w-12 h-12 flex items-center justify-center text-gray-400 text-xs font-bold"
                    >
                        ...
                    </span>
                </template>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import MainLayout from '../../Layouts/MainLayout.vue';

const props = defineProps({
    teams: Object,
    leagues: Array,
    countries: Array,
    filters: Object
});

const search = ref(props.filters.search || '');
const selectedLeague = ref(props.filters.league_id || 'all');
const selectedCountry = ref(props.filters.country || 'all');

const isCountryOpen = ref(false);

// Combined Watch for all filters
watch([search, selectedLeague, selectedCountry], ([s, l, c]) => {
    router.get('/teams', { 
        search: s,
        league_id: l,
        country: c
    }, { preserveState: true, replace: true, preserveScroll: true });
});

const getCountryLabel = () => {
    if (selectedCountry.value === 'all') return 'Tất cả quốc gia';
    const found = props.countries.find(c => c.value === selectedCountry.value);
    return found ? found.label : selectedCountry.value;
};

const handleOutsideClick = (e) => {
    if (!e.target.closest('.country-dropdown')) {
        isCountryOpen.value = false;
    }
};

onMounted(() => window.addEventListener('click', handleOutsideClick));
onUnmounted(() => window.removeEventListener('click', handleOutsideClick));
</script>

<style>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(16, 185, 129, 0.2);
  border-radius: 10px;
}
</style>

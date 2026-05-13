<template>
  <Head title="Bảng tin bóng đá AI - Trực tiếp tỉ số & Phân tích" />
  
  <MainLayout>
    <div class="pt-2 pb-8 relative isolate">
      <!-- AI Background Blobs -->
      <div class="absolute -top-24 -left-24 w-96 h-96 bg-emerald-500/10 dark:bg-emerald-500/5 rounded-full blur-[100px] -z-10 animate-pulse"></div>
      <div class="absolute top-1/2 -right-24 w-80 h-80 bg-blue-500/10 dark:bg-blue-500/5 rounded-full blur-[80px] -z-10"></div>
      

      <div class="flex flex-col lg:flex-row gap-6">
        <!-- 2. Main Content: Full Schedule & Results -->
        <div class="flex-1 space-y-4 min-w-0">
            <!-- Header & Date/League Filters -->
            <div class="flex flex-col gap-6 mb-2 relative z-[40]">
                <GamesFilter 
                    title="Lịch thi đấu & Kết quả"
                    :tabs="mainTabs"
                    :activeTab="filters.status || 'ALL'"
                    @update:activeTab="val => changeStatus(val)"
                    :selectedDate="filters.date || today"
                    @update:selectedDate="val => changeDate(val)"
                    v-model:isLeagueIndexVisible="isLeagueIndexVisible"
                />
            </div>

            <!-- Row 2: League Filter Slider (Reusable Component) -->
            <TabSlider 
                v-if="availableLeagues.length > 0"
                v-model="filters.league_id"
                :items="availableLeagues"
                show-all
                all-label="TẤT CẢ"
                @change="changeLeague"
                class="z-10"
            />

            <!-- Match Navigation & List Container -->
            <div class="flex items-start transition-all duration-500" :class="isLeagueIndexVisible ? 'gap-8' : 'gap-0'">
                <!-- League Index (Table of Contents) - Only visible on larger screens -->
                <div v-if="Object.keys(groupedGames).length > 1" 
                     class="hidden xl:block sticky top-6 self-start transition-all duration-500 ease-in-out"
                     :class="isLeagueIndexVisible ? 'w-64 opacity-100 translate-x-0' : 'w-0 opacity-0 -translate-x-10 overflow-hidden'">
                    <div class="space-y-6 w-64">
                        <h3 class="flex items-center gap-3 text-lg font-bold text-gray-900 dark:text-white uppercase tracking-tight">
                            <div class="w-1.5 h-6 bg-emerald-500 rounded-full shadow-[0_0_10px_rgba(16,185,129,0.3)]"></div>
                            Mục lục
                        </h3>
                        <div class="space-y-1.5 max-h-[calc(100vh-250px)] overflow-y-auto no-scrollbar pr-1">
                            <button v-for="(games, leagueName) in groupedGames" :key="leagueName"
                                    @click="scrollToLeague(leagueName)"
                                    class="group/item w-full text-left p-2 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition-all flex items-center justify-between gap-3 border border-transparent hover:border-emerald-100 dark:hover:border-emerald-500/20">
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <!-- League Logo -->
                                    <div class="w-8 h-8 rounded-lg bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-700 flex items-center justify-center p-0.5 shrink-0 shadow-sm transition-transform group-hover/item:scale-110">
                                        <img v-if="games[0].league.logo_url" :src="games[0].league.logo_url" class="w-full h-full object-contain" />
                                        <span v-else class="text-[10px] font-bold text-emerald-600 uppercase">{{ games[0].league.name.substring(0,2) }}</span>
                                    </div>

                                    <div class="flex flex-col min-w-0">
                                        <span class="text-xs font-bold text-gray-700 dark:text-gray-200 group-hover/item:text-emerald-600 transition-colors">
                                            {{ leagueName.includes(' (') ? leagueName.split(' (')[0] : leagueName }}
                                        </span>
                                        <span v-if="leagueName.includes(' (')" class="text-[8px] font-bold text-gray-400 dark:text-white uppercase tracking-widest">
                                            {{ translateCountry(leagueName.split(' (')[1].replace(')', '')) }}
                                        </span>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold px-2 py-0.5 bg-gray-100 dark:bg-white/10 rounded-lg text-gray-400 dark:text-white group-hover/item:text-emerald-500 transition-all tabular-nums shrink-0 flex items-center justify-center min-w-[20px]">
                                    {{ games.length }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Matches List Grouped by League -->
                <div v-if="Object.keys(groupedGames).length > 0" class="flex-1 min-w-0 space-y-10">
                    <div v-for="(games, leagueName) in groupedGames" :key="leagueName" :id="'league-' + slugify(leagueName)" class="scroll-mt-24">
                        <!-- League Title -->
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-1 h-4 bg-emerald-500 rounded-full"></div>
                            <h2 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] flex items-center gap-2">
                                <span class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                                    <span class="text-gray-900 dark:text-white">{{ leagueName.includes(' (') ? leagueName.split(' (')[0] : leagueName }}</span>
                                    <span v-if="leagueName.includes(' (')" class="text-gray-400 dark:text-white font-medium">({{ translateCountry(leagueName.split(' (')[1].replace(')', '')) }})</span>
                                </span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 bg-gray-100 dark:bg-white/10 rounded-md text-gray-600 dark:text-white flex items-center justify-center min-w-[18px]">
                                    {{ games.length }}
                                </span>
                            </h2>
                        </div>

                        <!-- Individual Match Cards -->
                        <div class="grid grid-cols-1 gap-3">
                            <MatchCard v-for="game in games" :key="game.id" :game="game" />
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="flex-1 flex flex-col items-center justify-center py-24 text-center bg-white/30 dark:bg-gray-800/20 rounded-[3rem] border border-dashed border-gray-200 dark:border-gray-700">
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
        </div>

        <LeagueSidebar />
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import MainLayout from '../Layouts/MainLayout.vue';
import MatchCard from '@/Components/MatchCard.vue';
import LeagueSidebar from '@/Components/LeagueSidebar.vue';
import GamesFilter from '@/Components/GamesFilter.vue';
import TabSlider from '@/Components/TabSlider.vue';
import dayjs from "dayjs";

const props = defineProps({
    groupedGames: { type: Object, default: () => ({}) },
    filters: {
        type: Object,
        default: () => ({
            date: dayjs().format("YYYY-MM-DD"),
            league_id: null,
            status: 'ALL'
        }),
    },
    availableLeagues: { type: Array, default: () => [] },
});

const mainTabs = [
    { id: 'ALL', label: 'TẤT CẢ' },
    { id: 'LIVE', label: 'TRỰC TIẾP' },
    { id: 'FINISHED', label: 'KẾT THÚC' },
    { id: 'SCHEDULED', label: 'LỊCH THI ĐẤU' }
];

const today = dayjs().format('YYYY-MM-DD');
const isLeagueIndexVisible = ref(true);

// Lấy thông tin user để lưu ghim riêng biệt cho từng tài khoản
const user = computed(() => usePage().props.auth.user);

const translateCountry = (country) => {
    const map = {
        'England': 'Anh',
        'Spain': 'Tây Ban Nha',
        'Italy': 'Ý',
        'Germany': 'Đức',
        'France': 'Pháp',
        'Vietnam': 'Việt Nam',
        'World': 'Quốc tế',
        'Brazil': 'Brazil',
        'Argentina': 'Argentina',
        'Portugal': 'Bồ Đào Nha',
        'Netherlands': 'Hà Lan',
        'Denmark': 'Đan Mạch',
        'Czech Republic': 'Cộng hòa Séc',
        'Belgium': 'Bỉ',
        'Switzerland': 'Thụy Sĩ',
        'Austria': 'Áo',
        'Norway': 'Na Uy',
        'Sweden': 'Thụy Điển',
        'Poland': 'Ba Lan',
        'Turkey': 'Thổ Nhĩ Kỳ',
        'Ukraine': 'Ukraine',
        'Greece': 'Hy Lạp',
        'Russia': 'Nga',
        'Scotland': 'Scotland',
        'Wales': 'Wales',
        'Ireland': 'Ireland',
        'Northern Ireland': 'Bắc Ireland',
        'South Korea': 'Hàn Quốc',
        'Japan': 'Nhật Bản',
        'Saudi Arabia': 'Ả Rập Xê-út',
        'Australia': 'Australia',
        'USA': 'Hoa Kỳ',
        'Mexico': 'Mexico',
    };
    return map[country] || country;
};

const changeStatus = (status) => {
    router.get('/', {
        ...props.filters,
        status: status,
    }, { preserveState: true });
};

const changeDate = (date) => {
    router.get('/', {
        ...props.filters,
        date: date,
    }, { preserveState: true });
};

const changeLeague = (leagueId) => {
    router.get('/', {
        ...props.filters,
        league_id: leagueId,
    }, { preserveState: true });
};

const slugify = (text) => {
    return text.toString().toLowerCase()
        .replace(/\s+/g, '-')           // Replace spaces with -
        .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
        .replace(/\-\-+/g, '-')         // Replace multiple - with single -
        .replace(/^-+/, '')             // Trim - from start of text
        .replace(/-+$/, '');            // Trim - from end of text
};

const scrollToLeague = (leagueName) => {
    const element = document.getElementById('league-' + slugify(leagueName));
    if (element) {
        const offset = 100; // Khoảng cách từ đỉnh màn hình
        const bodyRect = document.body.getBoundingClientRect().top;
        const elementRect = element.getBoundingClientRect().top;
        const elementPosition = elementRect - bodyRect;
        const offsetPosition = elementPosition - offset;

        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
    }
};
let dashboardInterval = null;

onMounted(() => {
    // Polling mỗi 60 giây để cập nhật tỉ số các trận đang diễn ra
    dashboardInterval = setInterval(() => {
        const hasLiveMatches = Object.values(props.groupedGames).some(league => 
            league.some(match => match.status === 'live')
        );
        
        if (hasLiveMatches || props.filters.status === 'LIVE') {
            router.reload({ 
                preserveScroll: true, 
                preserveState: true,
                only: ['groupedGames'] // Chỉ lấy lại dữ liệu trận đấu để tối ưu
            });
        }
    }, 60000);
});

onUnmounted(() => {
    if (dashboardInterval) {
        clearInterval(dashboardInterval);
    }
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
.custom-scrollbar::-webkit-scrollbar { height: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(16, 185, 129, 0.2); border-radius: 10px; }
.custom-scrollbar:hover::-webkit-scrollbar-thumb { background: rgba(16, 185, 129, 0.4); }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(16, 185, 129, 0.2); }
.dark .custom-scrollbar:hover::-webkit-scrollbar-thumb { background: rgba(16, 185, 129, 0.4); }
</style>

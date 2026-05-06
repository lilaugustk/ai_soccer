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
            <div class="flex flex-col gap-6 mb-8 relative z-[40]">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold flex items-center gap-3 text-gray-900 dark:text-white uppercase tracking-tight">
                        <div class="w-1.5 h-6 bg-emerald-500 rounded-full shadow-[0_0_10px_rgba(16,185,129,0.3)]"></div>
                        Lịch Thi Đấu & Kết Quả
                    </h2>
                    
                    <!-- Toggle League Index Button -->
                    <button v-if="Object.keys(groupedGames).length > 1" 
                            @click="isLeagueIndexVisible = !isLeagueIndexVisible"
                            class="hidden xl:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-gray-800 text-[10px] font-bold uppercase tracking-widest text-gray-500 hover:text-emerald-500 transition-all border border-transparent hover:border-emerald-200 dark:hover:border-emerald-500/30">
                        <svg class="w-4 h-4 transition-transform duration-300" :class="{ 'rotate-180': !isLeagueIndexVisible }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                        </svg>
                        {{ isLeagueIndexVisible ? 'Ẩn mục lục' : 'Hiện mục lục' }}
                    </button>
                </div>

                <!-- Filter Area: Split into two rows -->
                <div class="flex flex-col gap-4 relative">
                    <!-- Row 1: Date Selector -->
                    <div class="flex items-center gap-2 bg-gray-100/50 dark:bg-gray-800/50 p-1.5 rounded-2xl border border-gray-200/50 dark:border-gray-700/50 backdrop-blur-sm shadow-sm w-fit max-w-full relative z-[21]">
                        <!-- Mini Calendar Toggle -->
                        <div class="relative date-picker-container">
                            <button @click="toggleDatePicker"
                                    class="p-2 rounded-xl bg-white dark:bg-gray-700 shadow-sm text-gray-700 dark:text-gray-200 hover:text-emerald-600 dark:hover:text-emerald-400 border border-transparent hover:border-emerald-200 dark:hover:border-emerald-500/30 transition-all">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </button>

                            <!-- Date Picker Dropdown -->
                             <div v-if="showDatePicker" 
                                  class="absolute top-full left-0 mt-3 z-[100] bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.15)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.3)] border border-gray-100 dark:border-gray-800 p-6 w-80 transform transition-all duration-300 ease-out">
                                <div class="flex items-center justify-between mb-6">
                                    <span class="text-xs font-black uppercase text-gray-900 dark:text-white tracking-[0.1em]">
                                        {{ dayjs(props.filters?.date || today).add(currentMonthOffset, 'month').format("MMMM [Năm] YYYY") }}
                                    </span>
                                    <div class="flex gap-2">
                                        <button @click.stop="adjustMonth(-1)" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition-colors text-gray-500 hover:text-emerald-500">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                                        </button>
                                        <button @click.stop="adjustMonth(1)" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition-colors text-gray-500 hover:text-emerald-500">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-7 gap-1.5 mb-6">
                                    <span v-for="d in ['CN','T2','T3','T4','T5','T6','T7']" :key="d" 
                                          class="text-center text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase pb-2">
                                        {{ d }}
                                    </span>
                                    <button v-for="day in calendarDays" :key="day.date" 
                                            @click="changeDate(day.date); showDatePicker = false;"
                                            class="h-9 w-9 rounded-xl flex items-center justify-center text-[11px] font-bold transition-all relative group"
                                            :class="[
                                                day.isCurrentMonth ? '' : 'opacity-20', 
                                                (filters?.date || today) === day.date 
                                                    ? 'bg-emerald-500 text-white shadow-[0_8px_20px_rgba(16,185,129,0.3)] scale-110 z-10' 
                                                    : 'hover:bg-emerald-50 dark:hover:bg-emerald-500/10 text-gray-700 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400'
                                            ]">
                                        {{ day.dayNum }}
                                        <div v-if="day.date === today && (filters?.date || today) !== day.date" 
                                             class="absolute bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 bg-emerald-500 rounded-full"></div>
                                    </button>
                                </div>
                                <button @click="changeDate(today); showDatePicker = false;" 
                                        class="w-full py-3 bg-gray-50 dark:bg-gray-800/50 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 rounded-2xl text-[10px] font-black uppercase tracking-[0.15em] text-emerald-600 dark:text-emerald-400 transition-all border border-transparent hover:border-emerald-100 dark:hover:border-emerald-500/20">
                                    Hôm nay
                                </button>
                            </div>
                        </div>

                        <div class="h-5 w-px bg-gray-200 dark:bg-gray-700 mx-0.5"></div>

                        <!-- Date Slider -->
                        <div ref="dateSliderRef" 
                             @mousedown="dateDrag.onMouseDown"
                             @mouseleave="dateDrag.onMouseLeave"
                             @mouseup="dateDrag.onMouseUp"
                             @mousemove="dateDrag.onMouseMove"
                             class="flex flex-nowrap gap-1 overflow-x-auto custom-scrollbar flex-1 pb-1 scroll-smooth cursor-grab active:cursor-grabbing select-none">
                            <button v-for="btn in dateSlider" :key="btn.date" 
                                    :ref="el => { if ((filters?.date || today) === btn.date) activeDateRef = el }"
                                    @click="changeDate(btn.date)"
                                    class="px-3 py-2 rounded-xl text-[10px] font-bold transition-all whitespace-nowrap min-w-[60px] text-center shrink-0"
                                    :class="(filters?.date || today) === btn.date ? 'bg-emerald-600 text-white shadow-lg' : 'text-gray-400 hover:text-emerald-500'">
                                {{ btn.dayNum }}/{{ dayjs(btn.date).format('MM') }}
                            </button>
                        </div>
                    </div>

                    <!-- Row 2: League Filter Slider -->
                    <div v-if="availableLeagues.length > 0" 
                         ref="leagueSliderRef"
                         @mousedown="leagueDrag.onMouseDown"
                         @mouseleave="leagueDrag.onMouseLeave"
                         @mouseup="leagueDrag.onMouseUp"
                         @mousemove="leagueDrag.onMouseMove"
                         class="flex flex-nowrap overflow-x-auto custom-scrollbar w-full pb-3 relative cursor-grab active:cursor-grabbing select-none z-10">
                        <!-- Sticky "All" Button -->
                        <div class="sticky left-0 z-10 pr-4 bg-gradient-to-r from-white dark:from-gray-900 via-white/95 dark:via-gray-900/95 to-transparent shrink-0">
                            <button @click="changeLeague(null)"
                                    class="px-4 py-2 rounded-xl text-[9px] font-bold uppercase tracking-widest transition-all border whitespace-nowrap"
                                    :class="!filters.league_id ? 'bg-gray-950 dark:bg-white text-white dark:text-gray-950 border-gray-950 dark:border-white shadow-lg' : 'bg-white/50 dark:bg-gray-800/50 text-gray-400 border-gray-100 dark:border-gray-700'">
                                Tất cả
                            </button>
                        </div>
                        
                        <!-- Scrolling Leagues -->
                        <div class="flex gap-1.5 flex-nowrap">
                            <button v-for="league in availableLeagues" :key="league.id" @click="changeLeague(league.id)"
                                    class="px-4 py-2 rounded-xl text-[9px] font-bold uppercase tracking-widest transition-all border flex items-center gap-1.5 whitespace-nowrap shrink-0"
                                    :class="filters.league_id == league.id ? 'bg-emerald-500 text-white border-emerald-500 shadow-lg' : 'bg-white/50 dark:bg-gray-800/50 text-gray-400 border-gray-100 dark:border-gray-700'">
                                <img v-if="league.logo_url" :src="league.logo_url" class="w-3 h-3 object-contain" />
                                {{ league.name }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

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
                                <span class="text-[10px] font-black px-2 py-0.5 bg-gray-100 dark:bg-white/10 rounded-lg text-gray-400 dark:text-white group-hover/item:text-emerald-500 transition-all tabular-nums shrink-0 flex items-center justify-center min-w-[20px]">
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
                                <span class="text-[9px] font-black px-1.5 py-0.5 bg-gray-100 dark:bg-white/10 rounded-md text-gray-600 dark:text-white flex items-center justify-center min-w-[18px]">
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

// Lấy thông tin user để lưu ghim riêng biệt cho từng tài khoản
const user = computed(() => usePage().props.auth.user);

const showDatePicker = ref(false);
const currentMonthOffset = ref(0);
const today = dayjs().format("YYYY-MM-DD");

// Refs cho việc ẩn/hiện mục lục giải đấu
const isLeagueIndexVisible = ref(true);

// Refs cho việc căn giữa ngày hiện tại
const dateSliderRef = ref(null);
const activeDateRef = ref(null);
const leagueSliderRef = ref(null);

// --- Drag-to-scroll logic factory ---
const setupDragScroll = (containerRef) => {
    let isDown = false;
    let startX;
    let scrollLeft;

    return {
        onMouseDown: (e) => {
            isDown = true;
            containerRef.value.classList.add('cursor-grabbing');
            startX = e.pageX - containerRef.value.offsetLeft;
            scrollLeft = containerRef.value.scrollLeft;
        },
        onMouseLeave: () => {
            isDown = false;
            containerRef.value.classList.remove('cursor-grabbing');
        },
        onMouseUp: () => {
            isDown = false;
            containerRef.value.classList.remove('cursor-grabbing');
        },
        onMouseMove: (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - containerRef.value.offsetLeft;
            const walk = (x - startX) * 2;
            containerRef.value.scrollLeft = scrollLeft - walk;
        }
    };
};

const dateDrag = setupDragScroll(dateSliderRef);
const leagueDrag = setupDragScroll(leagueSliderRef);

const scrollToActiveDate = (smooth = true) => {
    if (activeDateRef.value) {
        activeDateRef.value.scrollIntoView({
            behavior: smooth ? 'smooth' : 'auto',
            inline: 'center',
            block: 'nearest'
        });
    }
};

const toggleDatePicker = () => {
    showDatePicker.value = !showDatePicker.value;
    if (showDatePicker.value) {
        currentMonthOffset.value = 0;
    }
};

const closeDatePicker = (e) => {
    if (showDatePicker.value && !e.target.closest('.date-picker-container')) {
        showDatePicker.value = false;
    }
};

onMounted(() => {
    setTimeout(() => scrollToActiveDate(false), 100);
    window.addEventListener('click', closeDatePicker);
});

onUnmounted(() => {
    window.removeEventListener('click', closeDatePicker);
});

watch(() => props.filters.date, () => {
    setTimeout(() => scrollToActiveDate(true), 50);
});

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
    // Hiển thị 15 ngày trước và 15 ngày sau (tổng 31 ngày) để thanh cuộn trông ngắn và đẹp hơn
    const start = dayjs(baseDate).subtract(15, "day");
    for (let i = 0; i < 31; i++) {
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

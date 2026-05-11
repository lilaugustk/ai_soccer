<template>
    <div class="flex flex-col gap-2 relative z-[40]">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold flex items-center gap-3 text-gray-900 dark:text-white uppercase tracking-tight">
                <div class="w-1.5 h-6 bg-emerald-500 rounded-full shadow-[0_0_10px_rgba(16,185,129,0.3)]"></div>
                {{ title }}
            </h2>

            <!-- Toggle Menu Button -->
            <button @click="$emit('update:isLeagueIndexVisible', !isLeagueIndexVisible)"
                    class="hidden xl:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-gray-800 text-[10px] font-bold uppercase tracking-widest text-gray-500 hover:text-emerald-500 transition-all border border-transparent hover:border-emerald-200 dark:hover:border-emerald-500/30">
                <svg class="w-4 h-4 transition-transform duration-300" :class="{ 'rotate-180': !isLeagueIndexVisible }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
                {{ isLeagueIndexVisible ? 'Ẩn mục lục' : 'Hiện mục lục' }}
            </button>
        </div>

        <!-- Filter Area: Status & Date strictly in one row -->
        <div class="flex items-center justify-between gap-4 flex-nowrap relative pb-1 border-b border-gray-100 dark:border-gray-800 px-2">
            <!-- Status Filter Tabs (Synced Underline Style) -->
            <div class="flex items-center gap-6 shrink-0">
                <button v-for="tab in tabs" :key="tab.id"
                        @click="$emit('update:activeTab', tab.id)"
                        class="relative py-2 text-[11px] font-semibold tracking-widest transition-all duration-300 uppercase whitespace-nowrap"
                        :class="activeTab === tab.id 
                            ? 'text-emerald-600 dark:text-emerald-400' 
                            : 'text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'">
                    {{ tab.name || tab.label }}
                    <div v-if="activeTab === tab.id" 
                         class="absolute bottom-0 left-0 right-0 h-0.5 bg-emerald-500 rounded-full animate-in fade-in slide-in-from-left-1">
                    </div>
                </button>
            </div>

            <!-- Date Selector (Compact & Narrower) -->
            <div class="flex items-center gap-1.5 p-1 relative z-[21] min-w-0 ml-auto">
                <div class="h-4 w-px bg-gray-100 dark:bg-gray-800 shrink-0 mr-1.5"></div>
                <!-- Mini Calendar Toggle -->
                <div class="relative date-picker-container shrink-0">
                    <button @click="showDatePicker = !showDatePicker"
                            class="p-2.5 rounded-xl bg-white dark:bg-gray-700 shadow-sm text-gray-700 dark:text-gray-200 hover:text-emerald-600 transition-all">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </button>
                    <div v-if="showDatePicker" 
                          class="absolute top-full left-0 mt-3 z-[100] bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-2xl border border-gray-100 dark:border-gray-800 p-6 w-80 transform transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <span class="text-xs font-bold uppercase text-gray-900 dark:text-white tracking-[0.1em]">
                                {{ dayjs().add(currentMonthOffset, 'month').format("MMMM [Năm] YYYY") }}
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
                                  class="text-center text-[9px] font-bold text-gray-400 dark:text-gray-500 uppercase pb-2">
                                {{ d }}
                            </span>
                            <button v-for="day in calendarDays" :key="day.date" 
                                    @click="$emit('update:selectedDate', day.date); showDatePicker = false;"
                                    class="h-9 w-9 rounded-xl flex items-center justify-center text-[11px] font-bold transition-all relative group"
                                    :class="[
                                        day.isCurrentMonth ? '' : 'opacity-20', 
                                        selectedDate === day.date 
                                            ? 'bg-emerald-500 text-white shadow-[0_8px_20px_rgba(16,185,129,0.3)] scale-110 z-10' 
                                            : 'hover:bg-emerald-50 dark:hover:bg-emerald-500/10 text-gray-700 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400'
                                    ]">
                                {{ day.dayNum }}
                                <div v-if="day.date === today && selectedDate !== day.date" 
                                     class="absolute bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 bg-emerald-500 rounded-full"></div>
                            </button>
                        </div>
                        <button @click="$emit('update:selectedDate', today); showDatePicker = false;" 
                                class="w-full py-3 bg-gray-50 dark:bg-gray-800/50 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 rounded-2xl text-[10px] font-bold uppercase tracking-[0.15em] text-emerald-600 dark:text-emerald-400 transition-all border border-transparent hover:border-emerald-100 dark:hover:border-emerald-500/20">
                            Hôm nay
                        </button>
                    </div>
                </div>

                <div class="h-3 w-px bg-gray-200 dark:bg-gray-700 shrink-0"></div>

                <!-- Fixed 5-Day Selector -->
                <div class="flex flex-nowrap gap-1 flex-1 select-none">
                    <button v-for="btn in dateSlider" :key="btn.date" 
                            @click="$emit('update:selectedDate', btn.date)"
                            class="px-3 py-2 rounded-lg text-[11px] font-semibold transition-all whitespace-nowrap min-w-[42px] text-center shrink-0"
                            :class="selectedDate === btn.date ? 'bg-gray-950 dark:bg-white text-white dark:text-gray-900 shadow-sm' : 'text-gray-400 hover:text-emerald-500'">
                        {{ btn.dayNum }}/{{ dayjs(btn.date).format('MM') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import dayjs from 'dayjs';

const props = defineProps({
    title: { type: String, default: 'Lịch thi đấu & Kết quả' },
    tabs: { type: Array, required: true },
    activeTab: { type: String, required: true },
    selectedDate: { type: String, required: true },
    isLeagueIndexVisible: { type: Boolean, default: true }
});

const emit = defineEmits(['update:activeTab', 'update:selectedDate', 'update:isLeagueIndexVisible']);

const showDatePicker = ref(false);
const currentMonthOffset = ref(0);
const today = dayjs().format('YYYY-MM-DD');

const adjustMonth = (offset) => {
    currentMonthOffset.value += offset;
};

const calendarDays = computed(() => {
    const days = [];
    const baseMonth = dayjs().add(currentMonthOffset.value, 'month');
    const startOfMonth = baseMonth.startOf('month');
    const endOfMonth = baseMonth.endOf('month');
    
    const firstDayOfWeek = startOfMonth.day();
    for (let i = firstDayOfWeek - 1; i >= 0; i--) {
        const d = startOfMonth.subtract(i + 1, 'day');
        days.push({ date: d.format('YYYY-MM-DD'), dayNum: d.date(), isCurrentMonth: false });
    }
    
    for (let i = 1; i <= endOfMonth.date(); i++) {
        const d = startOfMonth.date(i);
        days.push({ date: d.format('YYYY-MM-DD'), dayNum: i, isCurrentMonth: true });
    }
    
    const lastDayOfWeek = endOfMonth.day();
    for (let i = 1; i < 7 - lastDayOfWeek; i++) {
        const d = endOfMonth.add(i, 'day');
        days.push({ date: d.format('YYYY-MM-DD'), dayNum: d.date(), isCurrentMonth: false });
    }
    
    return days;
});

const dateSlider = computed(() => {
    const dates = [];
    // Only 5 days: -2, -1, 0, +1, +2 from today
    const start = dayjs().subtract(2, 'day');
    for (let i = 0; i < 5; i++) {
        const d = start.add(i, 'day');
        dates.push({
            date: d.format('YYYY-MM-DD'),
            dayNum: d.format('DD'),
            dayName: d.format('ddd')
        });
    }
    return dates;
});
</script>

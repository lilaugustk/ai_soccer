<template>
    <Head title="Lịch Thi Đấu | AI Soccer" />

    <MainLayout>
        <div class="pt-2 pb-8">
            <!-- Header Section -->
            <div
                class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-12 relative z-30"
            >
                <div>
                    <h1
                        class="text-xl font-bold text-gray-900 dark:text-white uppercase tracking-tight"
                    >
                        Lịch Thi Đấu & Kết Quả
                    </h1>
                    <p
                        class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5 font-bold uppercase tracking-widest"
                    >
                        Dữ liệu bóng đá thời gian thực
                    </p>
                </div>

                <!-- Date Selector -->
                <div
                    class="flex flex-col md:flex-row items-stretch md:items-center gap-4 w-full md:w-auto relative"
                >
                    <div
                        class="flex items-center gap-2 bg-gray-100/50 dark:bg-gray-800/50 p-1.5 rounded-2xl border border-gray-200/50 dark:border-gray-700/50 backdrop-blur-sm shadow-sm"
                    >
                        <div class="relative group">
                            <button
                                @click="showDatePicker = !showDatePicker"
                                class="p-2 rounded-xl bg-white dark:bg-gray-700 shadow-sm text-gray-700 dark:text-gray-200 hover:text-emerald-600 dark:hover:text-emerald-400 border border-transparent hover:border-emerald-200 dark:hover:border-emerald-500/30 transition-all"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>
                            </button>

                            <!-- Custom Date Picker Dropdown -->
                            <div
                                v-if="showDatePicker"
                                class="absolute top-full left-0 mt-2 z-[60] bg-white/95 dark:bg-gray-800/95 backdrop-blur-xl rounded-[2rem] shadow-2xl border border-gray-100 dark:border-gray-700 p-4 w-72"
                            >
                                <div
                                    class="flex items-center justify-between mb-4"
                                >
                                    <span
                                        class="text-[10px] font-bold uppercase text-gray-400 tracking-widest"
                                        >Tháng {{
                                            dayjs(
                                                props.filters?.date || today,
                                            ).format("M, [Năm] YYYY")
                                        }}</span
                                    >
                                    <div class="flex gap-1">
                                        <button
                                            @click="adjustMonth(-1)"
                                            class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                        >
                                            <svg
                                                class="w-4 h-4"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path d="M15 19l-7-7 7-7" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="adjustMonth(1)"
                                            class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                        >
                                            <svg
                                                class="w-4 h-4"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <!-- Mini Calendar Grid -->
                                <div class="grid grid-cols-7 gap-1 mb-4">
                                    <span
                                        v-for="d in [
                                            'CN',
                                            'T2',
                                            'T3',
                                            'T4',
                                            'T5',
                                            'T6',
                                            'T7',
                                        ]"
                                        :key="d"
                                        class="text-center text-[8px] font-bold text-gray-300"
                                        >{{ d }}</span
                                    >
                                    <button
                                        v-for="day in calendarDays"
                                        :key="day.date"
                                        @click="
                                            changeDate(day.date);
                                            showDatePicker = false;
                                        "
                                        class="h-8 w-8 rounded-xl flex items-center justify-center text-[10px] font-bold transition-all"
                                        :class="[
                                            day.isCurrentMonth
                                                ? ''
                                                : 'opacity-20',
                                            (filters?.date || today) === day.date
                                                ? 'bg-emerald-500 text-white shadow-lg'
                                                : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300',
                                        ]"
                                    >
                                        {{ day.dayNum }}
                                    </button>
                                </div>
                                <button
                                    @click="
                                        changeDate(today);
                                        showDatePicker = false;
                                    "
                                    class="w-full py-2 bg-gray-50 dark:bg-gray-700 rounded-xl text-[9px] font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400"
                                >
                                    Hôm nay
                                </button>
                            </div>
                        </div>
                        <div
                            class="h-5 w-px bg-gray-200 dark:bg-gray-700 mx-0.5"
                        ></div>
                        <div
                            class="flex gap-1 overflow-x-auto no-scrollbar flex-1 md:flex-none max-w-full md:max-w-[280px]"
                        >
                            <button
                                v-for="btn in dateSlider"
                                :key="btn.date"
                                @click="changeDate(btn.date)"
                                class="px-4 py-2 rounded-xl text-[10px] font-bold transition-all whitespace-nowrap min-w-[70px] text-center"
                                :class="
                                    (filters?.date || today) === btn.date
                                        ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-500/20 dark:shadow-none'
                                        : 'text-gray-400 dark:text-gray-500 hover:text-emerald-500'
                                "
                            >
                                {{ btn.dayNum }}/{{ dayjs(btn.date).format('MM') }}
                            </button>
                        </div>
                    </div>

                    <!-- League Filter Slider -->
                    <div
                        v-if="availableLeagues.length > 0"
                        class="flex gap-1.5 overflow-x-auto no-scrollbar w-full md:max-w-md"
                    >
                        <button
                            @click="changeLeague(null)"
                            class="px-4 py-1.5 rounded-xl text-[9px] font-bold uppercase tracking-widest transition-all border whitespace-nowrap"
                            :class="
                                !filters.league_id
                                    ? 'bg-gray-950 dark:bg-white text-white dark:text-gray-950 border-gray-950 dark:border-white shadow-lg'
                                    : 'bg-white/50 dark:bg-gray-800/50 text-gray-400 border-gray-100 dark:border-gray-700 hover:border-emerald-200 dark:hover:border-emerald-500/30'
                            "
                        >
                            Tất cả
                        </button>
                        <button
                            v-for="league in availableLeagues"
                            :key="league.id"
                            @click="changeLeague(league.id)"
                            class="px-4 py-1.5 rounded-xl text-[9px] font-bold uppercase tracking-widest transition-all border flex items-center gap-1.5 whitespace-nowrap"
                            :class="
                                filters.league_id == league.id
                                    ? 'bg-emerald-500 text-white border-emerald-500 shadow-lg shadow-emerald-500/20'
                                    : 'bg-white/50 dark:bg-gray-800/50 text-gray-400 border-gray-100 dark:border-gray-700 hover:border-emerald-200 dark:hover:border-emerald-500/30'
                            "
                        >
                            <img
                                v-if="league.logo_url"
                                :src="league.logo_url"
                                class="w-3 h-3 object-contain"
                            />
                            {{ league.name }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Matches List -->
            <div v-if="Object.keys(groupedGames).length > 0" class="space-y-12">
                <div
                    v-for="(games, leagueName) in groupedGames"
                    :key="leagueName"
                >
                    <!-- League Title -->
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-1 h-4 bg-emerald-500 rounded-full"></div>
                        <h2
                            class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-[0.2em] flex items-center gap-2"
                        >
                            {{ leagueName }}
                            <span
                                class="text-[9px] font-bold px-1.5 py-0.5 bg-gray-100 dark:bg-gray-900 text-gray-400 rounded-md"
                                >{{ games.length }}</span
                            >
                        </h2>
                    </div>

                    <!-- Cards Grid -->
                    <div class="grid grid-cols-1 gap-4">
                        <div
                            v-for="game in games"
                            :key="game.id"
                            @click="viewMatch(game.id)"
                            class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-emerald-500/50 hover:shadow-xl hover:shadow-gray-100 dark:hover:shadow-none transition-all cursor-pointer overflow-hidden"
                        >
                            <div
                                class="flex items-center justify-between p-5 md:p-6"
                            >
                                <!-- Home Team -->
                                <div
                                    class="flex-1 flex items-center justify-end gap-4 text-right"
                                >
                                    <span
                                        class="font-bold text-gray-900 dark:text-gray-100 md:text-lg"
                                        >{{ game.home_team?.name }}</span
                                    >
                                    <div
                                        class="w-10 h-10 rounded-xl bg-gray-50 dark:bg-gray-700 flex items-center justify-center border border-gray-100 dark:border-gray-600 transition-transform group-hover:scale-110 overflow-hidden p-1.5"
                                    >
                                        <img
                                            v-if="game.home_team?.logo_url"
                                            :src="game.home_team?.logo_url"
                                            class="w-full h-full object-contain filter drop-shadow-sm"
                                        />
                                        <span
                                            v-else
                                            class="font-bold text-gray-400 dark:text-gray-500 text-[10px]"
                                            >{{
                                                game.home_team?.short_name ||
                                                "H"
                                            }}</span
                                        >
                                    </div>
                                </div>

                                <!-- Score / Info -->
                                <div
                                    class="px-4 md:px-8 flex flex-col items-center min-w-[100px] md:min-w-[140px]"
                                >
                                    <div
                                        class="flex items-baseline gap-2 text-2xl md:text-3xl font-bold text-gray-950 dark:text-white tabular-nums"
                                    >
                                        <span>{{
                                            game.home_score ?? "-"
                                        }}</span>
                                        <span
                                            class="text-gray-200 dark:text-gray-700 text-sm opacity-50 font-medium"
                                            >:</span
                                        >
                                        <span>{{
                                            game.away_score ?? "-"
                                        }}</span>
                                    </div>
                                    <div
                                        class="mt-1 px-2.5 py-0.5 rounded-lg text-[9px] font-bold uppercase tracking-[0.2em] bg-gray-50/50 dark:bg-gray-900/50 text-gray-400 border border-gray-100 dark:border-gray-700"
                                    >
                                        {{ getMatchStatus(game) }}
                                    </div>
                                </div>

                                <!-- Away Team -->
                                <div
                                    class="flex-1 flex items-center justify-start gap-4"
                                >
                                    <div
                                        class="w-10 h-10 rounded-xl bg-gray-50 dark:bg-gray-700 flex items-center justify-center border border-gray-100 dark:border-gray-600 transition-transform group-hover:scale-110 overflow-hidden p-1.5"
                                    >
                                        <img
                                            v-if="game.away_team?.logo_url"
                                            :src="game.away_team?.logo_url"
                                            class="w-full h-full object-contain filter drop-shadow-sm"
                                        />
                                        <span
                                            v-else
                                            class="font-bold text-gray-400 dark:text-gray-500 text-[10px]"
                                            >{{
                                                game.away_team?.short_name ||
                                                "A"
                                            }}</span
                                        >
                                    </div>
                                    <span
                                        class="font-bold text-gray-900 dark:text-gray-100 md:text-lg"
                                        >{{ game.away_team?.name }}</span
                                    >
                                </div>
                            </div>

                            <!-- Footer info (Desktop only) -->
                            <div
                                class="hidden md:flex bg-gray-50/50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 px-6 py-2.5 justify-between items-center opacity-0 group-hover:opacity-100 transition-opacity"
                            >
                                <span
                                    class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest"
                                >
                                    Match ID: {{ game.id }} • Vòng
                                    {{ game.round || "N/A" }}
                                </span>
                                <span
                                    class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest"
                                >
                                    Chi tiết trận đấu & Phân tích AI →
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="flex flex-col items-center justify-center py-32 text-center"
            >
                <div class="w-32 h-32 mb-8 relative">
                    <div
                        class="absolute inset-0 bg-emerald-500/10 rounded-full animate-pulse"
                    ></div>
                    <div
                        class="absolute inset-4 bg-emerald-500/10 rounded-full animate-pulse delay-75"
                    ></div>
                    <div
                        class="relative w-full h-full flex items-center justify-center"
                    >
                        <svg
                            class="w-16 h-16 text-emerald-500/40"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                    </div>
                </div>
                <h3
                    class="text-2xl font-bold mb-2 uppercase tracking-tight text-gray-900 dark:text-white"
                >
                    Không có dữ liệu
                </h3>
                <p
                    class="text-gray-500 dark:text-gray-400 font-medium max-w-xs mx-auto"
                >
                    Chưa có trận đấu nào được nạp cho ngày
                    {{ formatDate(props.filters?.date || today) }}.
                </p>
                <button
                    @click="changeDate(today)"
                    class="mt-8 px-8 py-3 bg-emerald-600 text-white font-bold rounded-2xl shadow-lg shadow-emerald-200 dark:shadow-none hover:translate-y-[-2px] transition-all"
                >
                    VỀ HÔM NAY
                </button>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { computed, ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import MainLayout from "../../Layouts/MainLayout.vue";
import dayjs from "dayjs";

const showDatePicker = ref(false);
const currentMonthOffset = ref(0);

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

const calendarDays = computed(() => {
    const baseDate =
        props.filters.date && props.filters.date !== ""
            ? props.filters.date
            : today;
    const startOfMonth = dayjs(baseDate)
        .add(currentMonthOffset.value, "month")
        .startOf("month");
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

const isDark = computed(() => {
    return (
        typeof document !== "undefined" &&
        document.documentElement.classList.contains("dark")
    );
});

const today = dayjs().format("YYYY-MM-DD");

const dateSlider = computed(() => {
    const dates = [];
    const baseDate =
        props.filters.date && props.filters.date !== ""
            ? props.filters.date
            : today;
    const start = dayjs(baseDate).subtract(3, "day");
    for (let i = 0; i < 7; i++) {
        const d = start.add(i, "day");
        dates.push({
            date: d.format("YYYY-MM-DD"),
            dayNum: d.format("DD"),
            dayName: d.format("ddd"),
            month: d.format("MMM"),
        });
    }
    return dates;
});

const changeDate = (date) => {
    router.get(
        "/matches",
        {
            date,
            league_id: props.filters.league_id,
        },
        { preserveState: true },
    );
};

const changeLeague = (leagueId) => {
    router.get(
        "/matches",
        {
            date: props.filters.date,
            league_id: leagueId,
        },
        { preserveState: true },
    );
};

const formatDate = (dateStr) => dayjs(dateStr).locale('vi').format("DD/MM");
const formatTime = (dateTime) => dayjs(dateTime).format("HH:mm");

const getMatchStatus = (game) => {
    if (game.status === "finished") return "KẾT THÚC";
    if (game.status === "live") return "TRỰC TIẾP";
    return formatTime(game.match_datetime);
};

const viewMatch = (id) => {
    router.visit(`/matches/${id}`);
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
</style>

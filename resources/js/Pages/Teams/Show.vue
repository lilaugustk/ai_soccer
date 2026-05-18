<script setup>
import { ref, computed, onMounted } from "vue";
import { Head, router, Link } from "@inertiajs/vue3";
import MainLayout from "../../Layouts/MainLayout.vue";
import dayjs from "dayjs";
import StandingTable from "../../Components/StandingTable.vue";
import MatchCard from "../../Components/MatchCard.vue";
import LeagueSidebar from "../../Components/LeagueSidebar.vue";

const props = defineProps({
    team: { type: Object, required: true },
    recentGames: { type: Array, default: () => [] },
    upcomingGames: { type: Array, default: () => [] },
    squad: { type: Object, default: () => ({}) },
    standings: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    lastLineup: { type: Object, default: null },
    coachHistory: { type: Array, default: () => [] },
    isFavorite: { type: Boolean, default: false },
    lastMatch: { type: Object, default: null },
    currentSeason: { type: Number, default: 2025 },
    availableSeasons: { type: Array, default: () => [] },
});

const activeTab = ref("overview");
const showPitchModal = ref(false);
const tabs = [
    { id: "overview", label: "Tổng quan" },
    { id: "standings", label: "Bảng xếp hạng" },
    { id: "matches", label: "Trận đấu" },
    { id: "squad", label: "Đội hình" },
];

const changeTab = (id) => {
    activeTab.value = id;
    const url = new URL(window.location);
    url.searchParams.set('tab', id);
    window.history.replaceState({}, '', url);
};

const isSeasonOpen = ref(false);
const changeSeason = (s) => {
    isSeasonOpen.value = false;
    router.visit(`/teams/${props.team.id}?season=${s}`, {
        preserveState: false,
        preserveScroll: true
    });
};

const formatDate = (dateStr) => dayjs(dateStr).format("DD/MM/YYYY");
const formatTime = (dateStr) => dayjs(dateStr).format("HH:mm");

const getMatchResultText = (game) => {
    if (game.home_score === null) return "-";
    const isHome = game.home_team_id === props.team.id;
    const teamScore = isHome ? game.home_score : game.away_score;
    const opponentScore = isHome ? game.away_score : game.home_score;

    if (teamScore > opponentScore) return "W";
    if (teamScore < opponentScore) return "L";
    return "D";
};

const getMatchResultClass = (game) => {
    const res = getMatchResultText(game);
    if (res === "W") return "bg-emerald-500 text-white";
    if (res === "L") return "bg-red-500 text-white";
    if (res === "D") return "bg-amber-500 text-white";
    return "bg-gray-100 dark:bg-gray-800 text-gray-400";
};

const nextMatch = computed(() => props.upcomingGames[0] || null);
const last5Games = computed(() => [...props.recentGames].slice(0, 5).reverse());

const viewMatch = (id) => router.visit(`/matches/${id}`);
const viewPlayer = (id) => router.visit(`/players/${id}`);

const toggleFavorite = () => {
    router.post(`/teams/${props.team.id}/favorite`, {}, {
        preserveScroll: true,
    });
};

const getRating = (stat) => {
    return stat.detailed_stats?.[0]?.games?.rating || "N/A";
};

const getRatingClass = (rating) => {
    const val = parseFloat(rating);
    if (isNaN(val)) return "bg-gray-400";
    if (val >= 7.5) return "bg-emerald-500";
    if (val >= 6.5) return "bg-amber-500";
    return "bg-red-500";
};

const calcAge = (birthDate) => {
    if (!birthDate) return '-';
    const d = dayjs(birthDate);
    if (!d.isValid()) return '-';
    return dayjs().diff(d, 'year');
};

const positionLabels = {
    Goalkeeper: "Thủ môn",
    Defender: "Hậu vệ",
    Midfielder: "Tiền vệ",
    Attacker: "Tiền đạo",
    Forward: "Tiền đạo",
    Centre: "Trung tâm",
};

const positionShort = {
    Goalkeeper: "TM",
    Defender: "HV",
    Midfielder: "TV",
    Attacker: "TĐ",
};

const countryTranslations = {
    "England": { name: "Anh", code: "gb-eng" },
    "France": { name: "Pháp", code: "fr" },
    "Germany": { name: "Đức", code: "de" },
    "Spain": { name: "Tây Ban Nha", code: "es" },
    "Italy": { name: "Ý", code: "it" },
    "Portugal": { name: "Bồ Đào Nha", code: "pt" },
    "Netherlands": { name: "Hà Lan", code: "nl" },
    "Belgium": { name: "Bỉ", code: "be" },
    "Brazil": { name: "Brazil", code: "br" },
    "Argentina": { name: "Argentina", code: "ar" },
    "Uruguay": { name: "Uruguay", code: "uy" },
    "Croatia": { name: "Croatia", code: "hr" },
    "Denmark": { name: "Đan Mạch", code: "dk" },
    "Switzerland": { name: "Thụy Sĩ", code: "ch" },
    "Poland": { name: "Ba Lan", code: "pl" },
    "Sweden": { name: "Thụy Điển", code: "se" },
    "Norway": { name: "Na Uy", code: "no" },
    "Austria": { name: "Áo", code: "at" },
    "Scotland": { name: "Scotland", code: "gb-sct" },
    "Wales": { name: "Wales", code: "gb-wls" },
    "Turkey": { name: "Thổ Nhĩ Kỳ", code: "tr" },
    "Türkiye": { name: "Thổ Nhĩ Kỳ", code: "tr" },
    "Slovenia": { name: "Slovenia", code: "si" },
    "Senegal": { name: "Senegal", code: "sn" },
    "Morocco": { name: "Ma-rốc", code: "ma" },
    "Japan": { name: "Nhật Bản", code: "jp" },
    "South Korea": { name: "Hàn Quốc", code: "kr" },
    "USA": { name: "Mỹ", code: "us" },
    "Cameroon": { name: "Cameroon", code: "cm" },
    "Ivory Coast": { name: "Bờ Biển Ngà", code: "ci" },
    "Côte d'Ivoire": { name: "Bờ Biển Ngà", code: "ci" },
    "Ghana": { name: "Ghana", code: "gh" },
    "Nigeria": { name: "Nigeria", code: "ng" },
    "Egypt": { name: "Ai Cập", code: "eg" },
    "Algeria": { name: "Algeria", code: "dz" },
    "Tunisia": { name: "Tunisia", code: "tn" },
    "Chile": { name: "Chile", code: "cl" },
    "Colombia": { name: "Colombia", code: "co" },
    "Peru": { name: "Peru", code: "pe" },
    "Mexico": { name: "Mexico", code: "mx" },
    "Canada": { name: "Canada", code: "ca" },
    "Australia": { name: "Úc", code: "au" },
    "Ukraine": { name: "Ukraine", code: "ua" },
    "Czech Republic": { name: "CH Séc", code: "cz" },
    "Serbia": { name: "Serbia", code: "rs" },
    "Slovakia": { name: "Slovakia", code: "sk" },
    "Hungary": { name: "Hungary", code: "hu" },
    "Romania": { name: "Romania", code: "ro" },
    "Greece": { name: "Hy Lạp", code: "gr" },
    "Ireland": { name: "Ireland", code: "ie" },
    "Northern Ireland": { name: "Bắc Ireland", code: "gb-nir" },
    "Finland": { name: "Phần Lan", code: "fi" },
    "Israel": { name: "Israel", code: "il" },
    "Russia": { name: "Nga", code: "ru" },
    "Ecuador": { name: "Ecuador", code: "ec" },
    "Paraguay": { name: "Paraguay", code: "py" },
    "Venezuela": { name: "Venezuela", code: "ve" },
    "Jamaica": { name: "Jamaica", code: "jm" },
    "Costa Rica": { name: "Costa Rica", code: "cr" },
    "Georgia": { name: "Georgia", code: "ge" },
    "Iceland": { name: "Iceland", code: "is" },
    "South Africa": { name: "Nam Phi", code: "za" },
};

const translateCountry = (name) => {
    return countryTranslations[name]?.name || name;
};

const getCountryFlag = (name) => {
    const code = countryTranslations[name]?.code;
    if (!code) return null;
    return `https://flagcdn.com/w40/${code.toLowerCase()}.png`;
};

const showChart = ref(false);
const activeTooltip = ref(null);
const showCoachDetails = ref(false);

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const tabFromUrl = params.get('tab');
    if (tabFromUrl && tabs.find(t => t.id === tabFromUrl)) {
        activeTab.value = tabFromUrl;
    }

    setTimeout(() => {
        showChart.value = true;
    }, 100);
});

// Logic xử lý đội hình cho sân cỏ
const getPlayerRating = (playerId) => {
    if (!props.lastMatch) return null;

    // Trường hợp 1: Dữ liệu từ Eloquent Model (TeamController)
    if (props.lastMatch.lineup?.teams) {
        for (const teamData of props.lastMatch.lineup.teams) {
            const p = teamData.players?.find(pl => (pl.player_id || pl.player?.id) == playerId);
            if (p?.ai_score) return (p.ai_score * 10).toFixed(1);
        }
    }

    // Trường hợp 2: Dữ liệu shimmed (GameController)
    if (props.lastMatch.players) {
        for (const teamData of props.lastMatch.players) {
            const playerData = teamData.players.find(p => p.player?.id == playerId);
            if (playerData?.statistics?.[0]?.games?.rating) {
                const r = parseFloat(playerData.statistics[0].games.rating);
                return r < 1 ? (r * 10).toFixed(1) : r.toFixed(1);
            }
        }
    }
    
    return null;
};

const formatSeason = (s, country = null) => {
    if (!s) return '—';
    const sStr = String(s);
    if (sStr.includes('-') && sStr.length > 7) { // Likely a full date YYYY-MM-DD
        return dayjs(s).format('DD.MM.YYYY');
    }
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

const formatValue = (val) => {
    if (!val) return '-';
    if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M €';
    if (val >= 1000) return (val / 1000).toFixed(0) + 'K €';
    return val + ' €';
};

const footLabels = {
    'left': 'Trái',
    'right': 'Phải',
    'both': 'Cả hai',
    'L': 'Trái',
    'R': 'Phải'
};
</script>

<template>
    <Head :title="`${team.name} | AI Soccer`" />

    <MainLayout>
        <div class="pt-2 pb-8 relative isolate">
            <!-- AI Background Blobs -->
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-emerald-500/5 dark:bg-emerald-500/5 rounded-full blur-[100px] -z-10 animate-pulse"></div>

            <div class="flex flex-col lg:flex-row gap-6">
                <!-- MAIN CONTENT -->
                <main class="flex-1 min-w-0 space-y-6">
                    <!-- Breadcrumbs -->
                    <nav class="flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-6">
                        <Link href="/" class="hover:text-emerald-500">Trang chủ</Link>
                        <span>/</span>
                        <span>{{ team.country || 'Đội bóng' }}</span>
                        <span>/</span>
                        <span class="text-gray-900 dark:text-gray-200">{{ team.name }}</span>
                    </nav>

            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 mb-6 border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden">
                <div class="flex flex-col md:flex-row items-center gap-6 relative z-10">
                    <div class="w-20 h-20 md:w-24 md:h-24 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-700 flex items-center justify-center p-4 shadow-inner">
                        <img v-if="team.logo_url" :src="team.logo_url" class="w-full h-full object-contain filter drop-shadow-md" />
                    </div>

                    <div class="flex-1 text-center md:text-left space-y-3">
                        <div class="flex flex-wrap items-center justify-center md:justify-start gap-3">
                            <div v-if="team.country" class="flex items-center gap-1.5 px-3 py-1 bg-gray-100 dark:bg-gray-700/50 rounded-full text-[9px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-300">
                                <img :src="getCountryFlag(team.country)" class="w-3 h-2.5 object-cover rounded-sm" v-if="getCountryFlag(team.country)" />
                                {{ translateCountry(team.country) }}
                            </div>
                            <div class="px-3 py-1 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-full text-[9px] font-bold uppercase tracking-widest">
                                Câu lạc bộ
                            </div>
                        </div>
                        
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white uppercase tracking-tight">{{ team.name }}</h1>
                    </div>

                    <div class="flex items-center gap-3">
                        <button 
                            @click="toggleFavorite"
                            class="h-9 px-5 flex items-center gap-2 rounded-lg text-[10px] font-bold uppercase tracking-widest transition-all hover:bg-emerald-500 hover:text-white border border-gray-100 dark:border-gray-700 shadow-sm"
                            :class="isFavorite ? 'bg-emerald-500 text-white border-emerald-500' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300'"
                        >
                            {{ isFavorite ? 'Đang theo dõi' : 'Theo dõi' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabs (Synced Underline Style) -->
            <div class="border-b border-gray-100 dark:border-gray-700 mb-6 relative">
                <div class="flex items-center justify-between gap-8">
                    <!-- Tabs with local overflow -->
                    <div class="flex gap-8 overflow-x-auto no-scrollbar whitespace-nowrap">
                        <button v-for="tab in tabs" :key="tab.id" @click="changeTab(tab.id)"
                            class="relative pb-4 px-1 text-[11px] font-bold uppercase tracking-[0.2em] transition-all whitespace-nowrap outline-none"
                            :class="activeTab === tab.id ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 hover:text-gray-900 dark:hover:text-white'">
                            {{ tab.label }}
                            <!-- Active Underline Indicator -->
                            <div v-if="activeTab === tab.id" 
                                 class="absolute bottom-0 left-0 right-0 h-0.5 bg-emerald-500 rounded-full animate-in fade-in slide-in-from-left-1">
                            </div>
                        </button>
                    </div>

                    <!-- Season Dropdown - Only show on Standings tab (Preserve height when hidden) -->
                    <div class="relative season-dropdown pb-4 transition-all duration-200" 
                         :class="activeTab === 'standings' ? 'opacity-100' : 'opacity-0 pointer-events-none'">
                        <button @click="isSeasonOpen = !isSeasonOpen" 
                                class="flex items-center gap-2 px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-lg text-[11px] font-bold uppercase tracking-[0.2em] transition-all hover:bg-gray-100 dark:hover:bg-gray-700">
                            Mùa giải: {{ formatSeason(currentSeason, team.country) }}
                            <svg :class="['w-2.5 h-2.5 transition-transform', isSeasonOpen ? 'rotate-180' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div v-show="isSeasonOpen" class="absolute top-full right-0 mt-1 min-w-full bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl shadow-2xl py-2 z-50 max-h-60 overflow-y-auto no-scrollbar">
                            <button v-for="s in availableSeasons" :key="s" @click="changeSeason(s)" class="w-full text-center px-4 py-2 text-[11px] font-bold hover:bg-gray-50 dark:hover:bg-gray-700 whitespace-nowrap" :class="s == currentSeason ? 'text-emerald-500' : 'text-gray-500'">{{ formatSeason(s, team.country) }}</button>
                        </div>
                    </div>
                </div>
            </div>

                    <!-- Content Area -->
                    <div class="min-h-[600px]">
                        <!-- OVERVIEW TAB -->
                    <div v-if="activeTab === 'overview'" class="space-y-6">
                        <!-- ROW 1: FORM & NEXT MATCH -->
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                            <!-- Phong độ -->
                            <div class="lg:col-span-4">
                                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col h-full">
                                    <h3 class="text-[9px] font-semibold uppercase tracking-widest text-gray-400 mb-6">Phong độ đội bóng</h3>
                                    <div class="flex items-center justify-between gap-2 flex-1">
                                        <div v-for="(game, idx) in last5Games" :key="idx" class="flex flex-col items-center gap-2">
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-[9px] font-semibold shadow-md"
                                                :class="getMatchResultClass(game)">
                                                {{ getMatchResultText(game) }}
                                            </div>
                                            <span class="text-[8px] font-semibold text-gray-400 tabular-nums">{{ game.home_score }}-{{ game.away_score }}</span>
                                            <div class="w-8 h-8 flex items-center justify-center mt-1 border-t border-gray-50 dark:border-gray-700 pt-3 w-full">
                                                <img :src="game.home_team_id === team.id ? game.away_team?.logo_url : game.home_team?.logo_url" class="w-5 h-5 object-contain" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Next Match -->
                            <div class="lg:col-span-8">
                                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm transition-all relative flex flex-col h-full justify-center">
                                    <h3 class="text-[9px] font-semibold uppercase tracking-widest text-gray-400 mb-6">Trận đấu tiếp theo</h3>
                                    
                                    <div v-if="nextMatch" @click="viewMatch(nextMatch.id)" class="flex items-center justify-between gap-8 cursor-pointer group px-4">
                                        <div class="flex flex-col items-center gap-3 flex-1">
                                            <div class="w-14 h-14 p-3 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-inner group-hover:scale-110 transition-transform">
                                                <img :src="nextMatch.home_team?.logo_url" class="w-full h-full object-contain filter drop-shadow-sm" />
                                            </div>
                                            <span class="text-[11px] font-semibold text-gray-900 dark:text-white text-center truncate uppercase tracking-widest w-full">{{ nextMatch.home_team?.name }}</span>
                                        </div>

                                        <div class="flex flex-col items-center px-8 border-x border-gray-50 dark:border-gray-700">
                                            <span class="text-2xl font-bold text-gray-950 dark:text-white tabular-nums tracking-tighter">{{ formatTime(nextMatch.match_at) }}</span>
                                            <span class="text-[10px] font-bold text-emerald-500 mt-2 uppercase tracking-[0.2em]">{{ formatDate(nextMatch.match_at) }}</span>
                                        </div>

                                        <div class="flex flex-col items-center gap-3 flex-1">
                                            <div class="w-14 h-14 p-3 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-inner group-hover:scale-110 transition-transform">
                                                <img :src="nextMatch.away_team?.logo_url" class="w-full h-full object-contain filter drop-shadow-sm" />
                                            </div>
                                            <span class="text-[11px] font-semibold text-gray-900 dark:text-white text-center truncate uppercase tracking-widest w-full">{{ nextMatch.away_team?.name }}</span>
                                        </div>
                                    </div>

                                    <div v-else class="flex-1 flex flex-col items-center justify-center py-8 border border-dashed border-gray-100 dark:border-gray-700 rounded-xl bg-gray-50/50 dark:bg-gray-900/30">
                                        <svg class="w-8 h-8 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Chưa có lịch thi đấu</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ROW 2: COACH CHART -->
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Coach History Chart -->
                            <div class="w-full">
                                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm relative h-full flex flex-col">
                                    <div class="mb-6">
                                        <h3 class="text-[9px] font-bold text-gray-400 uppercase tracking-[0.2em]">Tỷ lệ thắng của huấn luyện viên</h3>
                                    </div>

                                    <div v-if="coachHistory.length > 0" class="flex-1 relative h-60 mb-16 px-6 bg-gray-50/30 dark:bg-gray-900/20 rounded-2xl border border-gray-100/50 dark:border-gray-700/50">
                                        <div class="flex items-end justify-around h-full relative">
                                            <div v-for="(coach, idx) in coachHistory" :key="idx" 
                                                    class="flex-1 flex flex-col items-center relative h-full group/coach"
                                                    @mouseenter="activeTooltip = idx"
                                                    @mouseleave="activeTooltip = null">
                                                    
                                                    
                                                    <!-- Stacked Data Boxes -->
                                                    <div class="absolute transition-all duration-[1000ms] cubic-bezier(0.34, 1.56, 0.64, 1) flex flex-col items-center z-10"
                                                        :style="{ bottom: showChart ? `${(coach.winRate * 0.55) + 12}%` : '0%', opacity: showChart ? 1 : 0 }">
                                                        
                                                        <!-- Tooltip -->
                                                        <div v-if="activeTooltip === idx" 
                                                             class="absolute bottom-full mb-3 w-40 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-100 dark:border-gray-700 p-3 z-[100] animate-in fade-in zoom-in duration-200">
                                                            <div class="flex items-center gap-1.5 mb-1 justify-center">
                                                                <img v-if="coach.league.logo" :src="coach.league.logo" class="w-3.5 h-3.5 object-contain" />
                                                                <span class="text-[10px] font-bold text-gray-900 dark:text-white">{{ coach.league.name }}</span>
                                                            </div>
                                                            <div class="text-[8px] text-gray-400 font-bold mb-2 text-center uppercase tracking-widest">{{ coach.season }}</div>
                                                            <div class="flex justify-between gap-1">
                                                                <div class="flex-1 flex flex-col items-center p-1 rounded bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-500/10">
                                                                    <span class="text-[9px] font-bold text-emerald-600">{{ coach.wins }}</span>
                                                                    <span class="text-[6px] font-medium text-gray-400 uppercase">W</span>
                                                                </div>
                                                                <div class="flex-1 flex flex-col items-center p-1 rounded bg-amber-50 dark:bg-amber-500/10 border border-amber-500/10">
                                                                    <span class="text-[9px] font-bold text-amber-600">{{ coach.draws }}</span>
                                                                    <span class="text-[6px] font-medium text-gray-400 uppercase">D</span>
                                                                </div>
                                                                <div class="flex-1 flex flex-col items-center p-1 rounded bg-red-50 dark:bg-red-500/10 border border-red-500/10">
                                                                    <span class="text-[9px] font-bold text-red-600">{{ coach.losses }}</span>
                                                                    <span class="text-[6px] font-medium text-gray-400 uppercase">L</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Data Label Box -->
                                                        <div class="flex flex-col items-center">
                                                            <div class="bg-emerald-500 text-white px-2 py-1 rounded-md text-[10px] font-bold min-w-[42px] text-center shadow-lg">
                                                                {{ coach.winRate }}%
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Coach Avatar & Info -->
                                                    <div class="absolute -bottom-16 flex flex-col items-center gap-1.5 w-full">
                                                        <div class="w-10 h-10 rounded-full bg-white dark:bg-gray-800 border-2 border-white dark:border-gray-700 shadow-lg overflow-hidden group-hover/coach:scale-110 transition-transform duration-300">
                                                            <img v-if="coach.photo" :src="coach.photo" 
                                                                    @error="(e) => e.target.src = `https://ui-avatars.com/api/?name=${coach.name}&background=random&color=fff`"
                                                                    class="w-full h-full object-cover" />
                                                            <div v-else class="w-full h-full flex items-center justify-center text-xs font-bold text-gray-400 uppercase">{{ coach.name?.charAt(0) }}</div>
                                                        </div>
                                                        <div class="flex flex-col items-center leading-none">
                                                            <span class="text-[10px] font-bold text-gray-900 dark:text-white text-center">{{ coach.name.split(' ').pop() }}</span>
                                                            <span class="text-[9px] font-medium text-gray-400 mt-1">{{ coach.display_season }}</span>
                                                        </div>
                                                    </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Empty State -->
                                    <div v-else class="flex-1 flex flex-col items-center justify-center bg-gray-50/50 dark:bg-gray-900/30 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
                                        <svg class="w-8 h-8 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Chưa có dữ liệu lịch sử huấn luyện viên</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ROW 3: STANDINGS & CURRENT COACH -->
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                            <!-- Bảng xếp hạng -->
                            <div class="lg:col-span-8">
                                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm relative h-full">
                                    <div class="p-6 border-b border-gray-50 dark:border-gray-700 flex items-center justify-between">
                                        <h3 class="text-[9px] font-semibold uppercase tracking-widest text-gray-400">Bảng xếp hạng {{ nextMatch?.league?.name }}</h3>
                                        <button @click="activeTab = 'standings'" class="text-[9px] font-semibold uppercase tracking-widest text-emerald-500">Toàn bộ</button>
                                    </div>
                                    <div class="no-scrollbar">
                                        <table class="w-full text-left">
                                                <thead>
                                                    <tr class="text-[8px] font-semibold text-gray-400 uppercase tracking-widest border-b border-gray-50 dark:border-gray-700">
                                                        <th class="px-5 py-3 text-center">#</th>
                                                        <th class="px-5 py-3">Đội bóng</th>
                                                        <th class="px-5 py-3 text-center cursor-pointer group relative">
                                                            P
                                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-semibold rounded shadow-xl z-[100] pointer-events-none">
                                                                    Số trận đã đấu
                                                                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                                            </div>
                                                        </th>
                                                        <th class="px-5 py-3 text-center cursor-pointer group relative">
                                                            W
                                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-semibold rounded shadow-xl z-[100] pointer-events-none">
                                                                    Thắng
                                                                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                                            </div>
                                                        </th>
                                                        <th class="px-5 py-3 text-center cursor-pointer group relative">
                                                            D
                                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-semibold rounded shadow-xl z-[100] pointer-events-none">
                                                                    Hòa
                                                                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                                            </div>
                                                        </th>
                                                        <th class="px-5 py-3 text-center cursor-pointer group relative">
                                                            L
                                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-semibold rounded shadow-xl z-[100] pointer-events-none">
                                                                    Thua
                                                                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                                            </div>
                                                        </th>
                                                        <th class="px-5 py-3 text-center cursor-pointer group relative">
                                                            GD
                                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-semibold rounded shadow-xl z-[100] pointer-events-none">
                                                                    Hiệu số bàn thắng bại
                                                                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                                            </div>
                                                        </th>
                                                        <th class="px-5 py-3 text-center cursor-pointer group relative">
                                                            Pts
                                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-semibold rounded shadow-xl z-[100] pointer-events-none">
                                                                    Tổng điểm hiện tại
                                                                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                                            </div>
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                                    <tr v-for="s in standings.slice(Math.max(0, standings.findIndex(x => x.team_id === team.id) - 1), Math.min(standings.length, standings.findIndex(x => x.team_id === team.id) + 2))" :key="s.team_id" :class="{ 'bg-emerald-500/5 dark:bg-emerald-500/10': s.team_id === team.id }">
                                                        <td class="px-5 py-3.5 text-[10px] font-semibold text-gray-900 dark:text-white text-center">{{ s.position ?? s.rank }}</td>
                                                        <td class="px-5 py-3.5">
                                                                <div class="flex items-center gap-3">
                                                                    <img :src="s.team?.logo_url" class="w-5 h-5 object-contain" />
                                                                    <span class="text-[11px] font-semibold text-gray-900 dark:text-white uppercase tracking-tight">{{ s.team?.name }}</span>
                                                                </div>
                                                        </td>
                                                        <td class="px-5 py-3.5 text-[10px] font-semibold text-gray-500 text-center">{{ s.played }}</td>
                                                        <td class="px-5 py-3.5 text-[10px] font-semibold text-gray-400 text-center">{{ s.won ?? s.win }}</td>
                                                        <td class="px-5 py-3.5 text-[10px] font-semibold text-gray-400 text-center">{{ s.drawn ?? s.draw }}</td>
                                                        <td class="px-5 py-3.5 text-[10px] font-semibold text-gray-400 text-center">{{ s.lost ?? s.lose }}</td>
                                                        <td class="px-5 py-3.5 text-[10px] font-semibold text-gray-400 text-center">{{ (s.gf ?? s.goals_for ?? 0) - (s.ga ?? s.goals_against ?? 0) }}</td>
                                                        <td class="px-5 py-3.5 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 text-center">{{ s.pts ?? s.points }}</td>
                                                    </tr>
                                                </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Huấn luyện viên hiện tại -->
                            <div class="lg:col-span-4">
                                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm h-full">
                                    <h3 class="text-[9px] font-semibold uppercase tracking-widest text-gray-400 mb-5">Huấn luyện viên hiện tại</h3>
                                    <template v-if="coachHistory.length > 0">
                                        <div class="flex items-center gap-4">
                                            <div class="w-14 h-14 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-700 overflow-hidden shadow-inner flex items-center justify-center">
                                                    <img v-if="coachHistory[coachHistory.length-1].photo" 
                                                        :src="coachHistory[coachHistory.length-1].photo" 
                                                        @error="(e) => e.target.src = `https://ui-avatars.com/api/?name=${coachHistory[coachHistory.length-1].name}&background=random&color=fff`"
                                                        class="w-full h-full object-cover" />
                                                    <div v-else class="text-xl font-bold text-gray-300">{{ coachHistory[coachHistory.length-1].name?.charAt(0) }}</div>
                                            </div>
                                            <div class="flex-1">
                                                    <div class="flex items-center justify-between">
                                                        <h4 class="text-[11px] font-semibold text-gray-900 dark:text-white uppercase">{{ coachHistory[coachHistory.length-1].name }}</h4>
                                                        <button @click.stop="showCoachDetails = !showCoachDetails" 
                                                                class="w-8 h-8 flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-all duration-300 border border-gray-100 dark:border-gray-700">
                                                            <svg :class="['w-4 h-4 text-gray-500 transition-transform duration-500', showCoachDetails ? 'rotate-180' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                            </div>
                                        </div>

                                        <!-- Coach Detailed Stats Dropdown -->
                                        <div v-if="showCoachDetails" 
                                            class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700/50 space-y-4 animate-in fade-in slide-in-from-top-2 duration-300">
                                            <div class="grid grid-cols-4 gap-2">
                                                <!-- Main Stats -->
                                                <div class="flex flex-col items-center py-2 px-1 rounded-lg bg-gray-50/50 dark:bg-gray-900/30 border border-gray-100 dark:border-gray-800">
                                                    <span class="text-[10px] font-bold text-gray-900 dark:text-white">{{ coachHistory[coachHistory.length-1].matches }}</span>
                                                    <span class="text-[6px] font-medium text-gray-400 uppercase mt-0.5">Trận</span>
                                                </div>
                                                <div class="flex flex-col items-center py-2 px-1 rounded-lg bg-emerald-50/5 border border-emerald-500/10">
                                                    <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">{{ coachHistory[coachHistory.length-1].wins }}</span>
                                                    <span class="text-[6px] font-medium text-gray-400 uppercase mt-0.5">Thắng</span>
                                                </div>
                                                <div class="flex flex-col items-center py-2 px-1 rounded-lg bg-amber-50/5 border border-amber-500/10">
                                                    <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400">{{ coachHistory[coachHistory.length-1].draws }}</span>
                                                    <span class="text-[6px] font-medium text-gray-400 uppercase mt-0.5">Hòa</span>
                                                </div>
                                                <div class="flex flex-col items-center py-2 px-1 rounded-lg bg-red-50/5 border border-red-500/10">
                                                    <span class="text-[10px] font-bold text-red-600 dark:text-red-400">{{ coachHistory[coachHistory.length-1].losses }}</span>
                                                    <span class="text-[6px] font-medium text-gray-400 uppercase mt-0.5">Thua</span>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-2 gap-4">
                                                <!-- Tactical Info -->
                                                <div class="bg-gray-50/50 dark:bg-gray-900/30 p-3 rounded-xl border border-gray-100 dark:border-gray-800">
                                                    <div class="text-[8px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Chiến thuật</div>
                                                    <div class="text-[10px] font-semibold text-gray-900 dark:text-white uppercase">{{ coachHistory[coachHistory.length-1].tactical_profile || 'Chưa rõ' }}</div>
                                                </div>
                                                <div class="bg-gray-50/50 dark:bg-gray-900/30 p-3 rounded-xl border border-gray-100 dark:border-gray-800">
                                                    <div class="text-[8px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Sơ đồ ưu thích</div>
                                                    <div class="text-[10px] font-semibold text-gray-900 dark:text-white">{{ coachHistory[coachHistory.length-1].preferred_formation || 'Chưa rõ' }}</div>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-3 gap-2">
                                                <!-- Performance Metrics -->
                                                <div class="flex flex-col items-center p-2 rounded-lg bg-emerald-500/5 border border-emerald-500/10">
                                                    <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400">{{ coachHistory[coachHistory.length-1].avg_possession }}%</span>
                                                    <span class="text-[7px] font-medium text-gray-400 uppercase text-center mt-1">Kiểm soát</span>
                                                </div>
                                                <div class="flex flex-col items-center p-2 rounded-lg bg-blue-500/5 border border-blue-500/10">
                                                    <span class="text-[11px] font-bold text-blue-600 dark:text-blue-400">{{ coachHistory[coachHistory.length-1].clean_sheet_pct }}%</span>
                                                    <span class="text-[7px] font-medium text-gray-400 uppercase text-center mt-1">Sạch lưới</span>
                                                </div>
                                                <div class="flex flex-col items-center p-2 rounded-lg bg-amber-500/5 border border-amber-500/10">
                                                    <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400">{{ coachHistory[coachHistory.length-1].over_25_pct }}%</span>
                                                    <span class="text-[7px] font-medium text-gray-400 uppercase text-center mt-1">Over 2.5</span>
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-between px-2 pt-2 border-t border-dashed border-gray-100 dark:border-gray-700">
                                                <div class="flex flex-col">
                                                    <span class="text-[7px] font-medium text-gray-400 uppercase">Ghi bàn TB</span>
                                                    <span class="text-[10px] font-bold text-gray-900 dark:text-white">{{ coachHistory[coachHistory.length-1].avg_goals_scored }}</span>
                                                </div>
                                                <div class="flex flex-col items-end">
                                                    <span class="text-[7px] font-medium text-gray-400 uppercase">Thủng lưới TB</span>
                                                    <span class="text-[10px] font-bold text-gray-900 dark:text-white">{{ coachHistory[coachHistory.length-1].avg_goals_conceded }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <div v-else class="py-4 flex flex-col items-center justify-center border border-dashed border-gray-100 dark:border-gray-700 rounded-xl bg-gray-50/50 dark:bg-gray-900/30 h-full">
                                        <span class="text-[9px] font-semibold text-gray-400 uppercase tracking-widest">Chưa có thông tin HLV</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STANDINGS TAB -->
                    <div v-else-if="activeTab === 'standings'" class="space-y-6">
                        <StandingTable v-if="standings.length > 0" :standings="standings" :league-id="nextMatch?.league_id" />
                        <div v-else class="py-20 text-center text-gray-400 text-[10px] font-semibold uppercase tracking-widest border border-dashed border-gray-100 dark:border-gray-800 rounded-2xl">
                            Không có dữ liệu bảng xếp hạng
                        </div>
                    </div>

                    <!-- MATCHES TAB -->
                    <div v-else-if="activeTab === 'matches'" class="space-y-8">
                        <!-- Upcoming Games -->
                        <section v-if="upcomingGames.length > 0" class="space-y-4">
                            <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] px-2 flex items-center gap-3">
                                Lịch thi đấu sắp tới
                                <div class="h-px flex-1 bg-gray-100 dark:bg-gray-800"></div>
                            </h3>
                            <div class="grid grid-cols-1 gap-2">
                                <MatchCard v-for="match in upcomingGames" :key="match.id" :game="match" />
                            </div>
                        </section>

                        <!-- Recent Results -->
                        <section v-if="recentGames.length > 0" class="space-y-4">
                            <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] px-2 flex items-center gap-3">
                                Kết quả gần đây
                                <div class="h-px flex-1 bg-gray-100 dark:bg-gray-800"></div>
                            </h3>
                            <div class="grid grid-cols-1 gap-2">
                                <MatchCard v-for="match in recentGames" :key="match.id" :game="match" />
                            </div>
                        </section>

                        <div v-if="upcomingGames.length === 0 && recentGames.length === 0" class="py-20 text-center text-gray-400 text-[10px] font-bold uppercase tracking-widest border border-dashed border-gray-100 dark:border-gray-800 rounded-2xl">
                            Không có dữ liệu trận đấu
                        </div>
                    </div>

                    <!-- SQUAD TAB (SINGLE TABLE) -->
                    <div v-else-if="activeTab === 'squad'" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto no-scrollbar">
                            <table class="w-full text-left border-collapse table-auto">
                                <thead>
                                    <tr class="bg-gray-50/50 dark:bg-gray-800/50 text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest border-b border-gray-100 dark:border-gray-800 leading-4">
                                        <th class="px-4 py-3">Cầu thủ</th>
                                        <th class="px-4 py-3 text-center">VT</th>
                                        <th class="px-4 py-3">Quốc gia</th>
                                        <th class="px-4 py-3 text-center">Số áo</th>
                                        <th class="px-4 py-3 text-center">Tuổi</th>
                                        <th class="px-4 py-3 text-center">Cao</th>
                                        <th class="px-4 py-3 text-center">Chân</th>
                                        <th class="px-4 py-3 text-right">Giá trị</th>
                                        <th class="px-4 py-3 text-right whitespace-nowrap">Hết hạn</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                    <tr v-for="p in squad" :key="p.id" @click="viewPlayer(p.id)" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors cursor-pointer group leading-4">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-4 whitespace-nowrap">
                                                <div class="w-10 h-10 rounded-xl bg-gray-50 dark:bg-gray-900 overflow-hidden flex-shrink-0 border border-gray-100 dark:border-gray-800 shadow-inner">
                                                    <img :src="p.photo_url" class="w-full h-full object-cover" />
                                                </div>
                                                <span class="text-[11px] font-semibold text-gray-900 dark:text-gray-100 uppercase tracking-tight group-hover:text-emerald-500 transition-colors">{{ p.name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="text-[9px] font-medium text-gray-400 uppercase whitespace-nowrap">{{ p.specific_position || positionLabels[p.position] || p.position }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2.5">
                                                <div v-if="getCountryFlag(p.nationality)" class="w-5 h-3.5 rounded-sm overflow-hidden flex-shrink-0 shadow-sm border border-gray-100 dark:border-gray-700">
                                                    <img :src="getCountryFlag(p.nationality)" class="w-full h-full object-cover" />
                                                </div>
                                                <span class="text-[10px] font-medium text-gray-600 dark:text-gray-400 uppercase tracking-tight">{{ translateCountry(p.nationality) }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center text-[11px] font-medium text-gray-900 dark:text-white">
                                            {{ p.jersey_number || '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-center text-[11px] font-medium text-gray-500">
                                            {{ calcAge(p.date_of_birth) }}
                                        </td>
                                        <td class="px-4 py-3 text-center text-[10px] font-medium text-gray-400">
                                            {{ p.height_cm ? p.height_cm + 'cm' : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="text-[10px] font-medium text-gray-500 uppercase">{{ footLabels[p.preferred_foot] || '-' }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right text-[11px] font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                            {{ formatValue(p.market_value_eur) }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-[10px] font-medium text-gray-400 whitespace-nowrap uppercase">
                                            {{ p.contract_until ? dayjs(p.contract_until).format('MM/YYYY') : '-' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    </div> <!-- min-h-[600px] -->
                </main>

                <LeagueSidebar />
            </div> <!-- flex container -->
        </div> <!-- outer container -->
    </MainLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

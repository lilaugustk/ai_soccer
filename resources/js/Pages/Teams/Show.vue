<script setup>
import { ref, computed, onMounted } from "vue";
import { Head, router, Link } from "@inertiajs/vue3";
import MainLayout from "../../Layouts/MainLayout.vue";
import dayjs from "dayjs";
import StandingTable from "../../Components/StandingTable.vue";
import MatchCard from "../../Components/MatchCard.vue";

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
});

const activeTab = ref("overview");
const showPitchModal = ref(false);
const tabs = [
    { id: "overview", label: "Tổng quan" },
    { id: "standings", label: "BXH" },
    { id: "matches", label: "Trận đấu" },
    { id: "squad", label: "Đội hình" },
];

const changeTab = (id) => {
    activeTab.value = id;
    const url = new URL(window.location);
    url.searchParams.set('tab', id);
    window.history.replaceState({}, '', url);
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

const calcAge = (birthYear) => {
    if (!birthYear) return '-';
    return new Date().getFullYear() - birthYear;
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
    if (!props.lastMatch?.players) return null;
    for (const teamData of props.lastMatch.players) {
        const playerData = teamData.players.find(p => p.player?.id == playerId);
        if (playerData?.statistics?.[0]?.games?.rating) {
            return playerData.statistics[0].games.rating;
        }
    }
    return null;
};

const processedVerticalLineup = computed(() => {
    if (!props.lastLineup?.startXI) return [];
    
    const lineupXI = props.lastLineup.startXI;
    const rows = {};
    
    // Group players by grid row
    lineupXI.forEach((p) => {
        if (!p.player.grid) return;
        const [row, col] = p.player.grid.split(":").map(Number);
        if (!rows[row]) rows[row] = [];
        rows[row].push(p);
    });

    const processed = [];
    // Max rows from grid (usually 1 to 4 or 5)
    const maxRow = Math.max(...Object.keys(rows).map(Number));

    Object.entries(rows).forEach(([rowStr, playersInRow]) => {
        const rowNum = parseInt(rowStr);
        
        // Sort by column (usually 1, 2, 3...)
        playersInRow.sort((a, b) => {
            const colA = parseInt(a.player.grid.split(":")[1]);
            const colB = parseInt(b.player.grid.split(":")[1]);
            return colA - colB;
        });

        const count = playersInRow.length;
        playersInRow.forEach((p, index) => {
            // Mapping for vertical pitch:
            // Top: 90% (GK) to 10% (Attack)
            // Left: distributed across 10% to 90%
            
            // Formula for top: Higher grid row = Lower top % (Attack is top)
            // GK is grid row 1 -> top ~88%
            // Attack is grid row 4 -> top ~13%
            const top = 88 - (rowNum - 1) * (75 / (maxRow - 1 || 1));
            
            // Formula for left: centered distribution
            const left = 50 + (index - (count - 1) / 2) * (80 / count);

            processed.push({
                ...p.player,
                rating: getPlayerRating(p.player.id),
                style: {
                    top: `${top}%`,
                    left: `${left}%`
                },
            });
        });
    });
    return processed;
});
const formatSeason = (s, country = null) => {
    if (!s) return '—';
    if (s.includes('-') && s.length > 7) { // Likely a full date YYYY-MM-DD
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
</script>

<template>
    <Head :title="`${team.name} | AI Soccer`" />

    <MainLayout>
        <div class="pt-2 pb-6 space-y-6">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
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
                        <img v-if="team.logo" :src="team.logo" class="w-full h-full object-contain filter drop-shadow-md" />
                    </div>

                    <div class="flex-1 text-center md:text-left">
                        <div class="flex items-center justify-center md:justify-start gap-3 mb-2">
                             <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ team.name }}</h1>
                             <div class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-[9px] font-bold uppercase tracking-widest text-gray-500">Anh</div>
                        </div>
                        
                        <div class="flex flex-wrap justify-center md:justify-start gap-4">
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button 
                            @click="toggleFavorite"
                            class="h-10 px-6 flex items-center gap-2 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all hover:scale-105 shadow-lg active:scale-95"
                            :class="isFavorite ? 'bg-rose-500 text-white' : 'bg-gray-950 dark:bg-white text-white dark:text-gray-950'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-all" :class="isFavorite ? 'fill-current scale-110' : 'fill-none stroke-current'" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                            </svg>
                            {{ isFavorite ? 'Đã theo dõi' : 'Theo dõi' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabs (Flashscore Style) -->
            <div class="border-b border-gray-100 dark:border-gray-700 mb-6 relative">
                <div class="flex gap-8 overflow-x-auto no-scrollbar whitespace-nowrap">
                    <button v-for="tab in tabs" :key="tab.id" @click="changeTab(tab.id)"
                        class="pb-4 px-1 text-[11px] font-bold uppercase tracking-widest transition-all whitespace-nowrap outline-none"
                        :class="activeTab === tab.id ? 'text-emerald-500 border-b-2 border-emerald-500' : 'text-gray-500 hover:text-gray-800 dark:hover:text-gray-200'">
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <!-- Content Area -->
            <div class="min-h-[600px]">
                <!-- OVERVIEW TAB -->
                <div v-if="activeTab === 'overview'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- Left Column -->
                    <div class="lg:col-span-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Phong độ -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col">
                                <h3 class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-6">Phong độ đội bóng</h3>
                                <div class="flex items-center justify-between gap-2 flex-1">
                                    <div v-for="(game, idx) in last5Games" :key="idx" class="flex flex-col items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-[9px] font-bold shadow-md"
                                             :class="getMatchResultClass(game)">
                                            {{ getMatchResultText(game) }}
                                        </div>
                                        <span class="text-[8px] font-bold text-gray-400 tabular-nums">{{ game.home_score }}-{{ game.away_score }}</span>
                                        <div class="w-8 h-8 flex items-center justify-center mt-1 border-t border-gray-50 dark:border-gray-700 pt-3 w-full">
                                             <img :src="game.home_team_id === team.id ? game.away_team?.logo : game.home_team?.logo" class="w-5 h-5 object-contain" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Next Match -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm transition-all relative flex flex-col">
                                <h3 class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-6">Trận đấu tiếp theo</h3>
                                
                                <div v-if="nextMatch" @click="viewMatch(nextMatch.id)" class="flex items-center justify-between gap-4 cursor-pointer group">
                                    <div class="flex flex-col items-center gap-2 flex-1">
                                        <div class="w-10 h-10 p-2 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-700">
                                             <img :src="nextMatch.home_team?.logo" class="w-full h-full object-contain" />
                                        </div>
                                        <span class="text-[9px] font-bold text-gray-900 dark:text-white text-center truncate uppercase tracking-tight w-full">{{ nextMatch.home_team?.name }}</span>
                                    </div>
                                    <div class="flex flex-col items-center">
                                        <span class="text-xl font-bold text-gray-950 dark:text-white tabular-nums">{{ formatTime(nextMatch.match_at) }}</span>
                                        <span class="text-[8px] font-bold text-gray-400 mt-1 uppercase tracking-widest">{{ formatDate(nextMatch.match_at) }}</span>
                                    </div>
                                    <div class="flex flex-col items-center gap-2 flex-1">
                                        <div class="w-10 h-10 p-2 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-700">
                                             <img :src="nextMatch.away_team?.logo" class="w-full h-full object-contain" />
                                        </div>
                                        <span class="text-[9px] font-bold text-gray-900 dark:text-white text-center truncate uppercase tracking-tight w-full">{{ nextMatch.away_team?.name }}</span>
                                    </div>
                                </div>

                                <div v-else class="flex-1 flex flex-col items-center justify-center py-4 border border-dashed border-gray-100 dark:border-gray-700 rounded-xl bg-gray-50/50 dark:bg-gray-900/30">
                                    <svg class="w-6 h-6 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Chưa có lịch thi đấu</span>
                                </div>
                            </div>
                        </div>

                        <!-- Coach History Chart -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                             <div class="mb-8">
                                  <h3 class="text-[10px] font-bold text-gray-900 dark:text-white uppercase tracking-widest">Coach win percentage</h3>
                                  <p class="text-[8px] font-bold text-gray-400 uppercase tracking-widest">Points per game</p>
                             </div>

                             <div v-if="coachHistory.length > 0" class="relative h-48 mb-16 px-4 bg-gray-50/50 dark:bg-gray-900/30 rounded-xl border border-gray-100 dark:border-gray-700">
                                  <div class="flex items-end justify-between h-full relative">
                                       <div v-for="(coach, idx) in coachHistory" :key="idx" 
                                            class="flex-1 flex flex-col items-center relative h-full">
                                            
                                            <!-- Vertical Line -->
                                            <div class="absolute bottom-0 w-px bg-gray-200 dark:bg-gray-700 h-full opacity-50"></div>
                                            
                                            <!-- Floating Data Boxes -->
                                            <div class="absolute transition-all duration-[1500ms] cubic-bezier(0.34, 1.56, 0.64, 1) flex flex-col items-center gap-0 z-10"
                                                 :style="{ bottom: showChart ? `${coach.winRate}%` : '0%', opacity: showChart ? 1 : 0 }">
                                                 <!-- Red Box (%) -->
                                                 <div class="bg-red-600 text-white px-2.5 py-1.5 rounded-t-lg text-[10px] font-bold min-w-[40px] text-center shadow-lg">
                                                      {{ coach.winRate }}%
                                                 </div>
                                                 <!-- Black Box (PPG) -->
                                                 <div class="bg-gray-900 dark:bg-black text-white px-2.5 py-1 rounded-b-lg text-[8px] font-bold min-w-[40px] text-center shadow-lg border-t border-white/5">
                                                      {{ coach.ppg }} Pts
                                                 </div>
                                            </div>

                                            <!-- Coach Avatar & Info -->
                                            <div class="absolute -bottom-16 flex flex-col items-center gap-1.5 w-full">
                                                  <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-600 shadow-sm overflow-hidden p-0.5 flex items-center justify-center">
                                                       <img v-if="coach.photo" :src="coach.photo" 
                                                            @error="(e) => e.target.src = 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y'"
                                                            class="w-full h-full object-cover rounded-full" />
                                                       <div v-else class="text-[10px] font-bold text-gray-400">{{ coach.name?.charAt(0) }}</div>
                                                  </div>
                                                 <div class="flex flex-col items-center leading-none">
                                                      <span class="text-[9px] font-bold text-gray-900 dark:text-white uppercase tracking-tight text-center">{{ coach.name }}</span>
                                                      <span class="text-[8px] font-bold text-gray-400 text-center mt-0.5">{{ formatSeason(coach.season) }}</span>
                                                 </div>
                                            </div>
                                       </div>
                                  </div>
                             </div>

                             <!-- Empty State -->
                             <div v-else class="h-48 flex flex-col items-center justify-center bg-gray-50/50 dark:bg-gray-900/30 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
                                  <svg class="w-8 h-8 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                  </svg>
                                  <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center px-6">Chưa có dữ liệu thống kê huấn luyện viên</span>
                             </div>
                        </div>



                        <!-- Mini Standings -->
                        <div v-if="standings.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm relative">
                             <div class="p-5 border-b border-gray-50 dark:border-gray-700 flex items-center justify-between">
                                  <h3 class="text-[9px] font-bold uppercase tracking-widest text-gray-400">BXH {{ nextMatch?.league?.name }}</h3>
                                  <button @click="activeTab = 'standings'" class="text-[9px] font-bold uppercase tracking-widest text-emerald-500">Toàn bộ</button>
                             </div>
                             <div class="no-scrollbar">
                                  <table class="w-full text-left">
                                       <thead>
                                            <tr class="text-[8px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50 dark:border-gray-700">
                                                 <th class="px-5 py-3 text-center">#</th>
                                                 <th class="px-5 py-3">Đội bóng</th>
                                                 <th class="px-5 py-3 text-center cursor-pointer group relative">
                                                      P
                                                      <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-bold rounded shadow-xl z-[100] pointer-events-none">
                                                           Số trận đã đấu
                                                           <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                                      </div>
                                                 </th>
                                                 <th class="px-5 py-3 text-center cursor-pointer group relative">
                                                      W
                                                      <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-bold rounded shadow-xl z-[100] pointer-events-none">
                                                           Thắng
                                                           <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                                      </div>
                                                 </th>
                                                 <th class="px-5 py-3 text-center cursor-pointer group relative">
                                                      D
                                                      <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-bold rounded shadow-xl z-[100] pointer-events-none">
                                                           Hòa
                                                           <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                                      </div>
                                                 </th>
                                                 <th class="px-5 py-3 text-center cursor-pointer group relative">
                                                      L
                                                      <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-bold rounded shadow-xl z-[100] pointer-events-none">
                                                           Thua
                                                           <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                                      </div>
                                                 </th>
                                                 <th class="px-5 py-3 text-center cursor-pointer group relative">
                                                      GD
                                                      <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-bold rounded shadow-xl z-[100] pointer-events-none">
                                                           Hiệu số bàn thắng bại
                                                           <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                                      </div>
                                                 </th>
                                                 <th class="px-5 py-3 text-center cursor-pointer group relative">
                                                      Pts
                                                      <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-bold rounded shadow-xl z-[100] pointer-events-none">
                                                           Tổng điểm hiện tại
                                                           <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                                                      </div>
                                                 </th>
                                            </tr>
                                       </thead>
                                       <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                            <tr v-for="s in standings.slice(Math.max(0, standings.findIndex(x => x.team_id === team.id) - 1), Math.min(standings.length, standings.findIndex(x => x.team_id === team.id) + 2))" 
                                                :key="s.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-all group"
                                                :class="{ 'bg-emerald-500/5 dark:bg-emerald-500/10': s.team_id === team.id }">
                                                 <td class="px-5 py-3.5 text-[10px] font-bold text-gray-900 dark:text-white text-center">{{ s.rank }}</td>
                                                 <td class="px-5 py-3.5">
                                                      <div class="flex items-center gap-3">
                                                           <img :src="s.team?.logo" class="w-5 h-5 object-contain" />
                                                           <span class="text-[11px] font-bold text-gray-900 dark:text-white uppercase tracking-tight">{{ s.team?.name }}</span>
                                                      </div>
                                                 </td>
                                                 <td class="px-5 py-3.5 text-[10px] font-bold text-gray-500 text-center">{{ s.played }}</td>
                                                 <td class="px-5 py-3.5 text-[10px] font-bold text-gray-400 text-center">{{ s.win }}</td>
                                                 <td class="px-5 py-3.5 text-[10px] font-bold text-gray-400 text-center">{{ s.draw }}</td>
                                                 <td class="px-5 py-3.5 text-[10px] font-bold text-gray-400 text-center">{{ s.lose }}</td>
                                                 <td class="px-5 py-3.5 text-[10px] font-bold text-gray-400 text-center">{{ s.goals_for - s.goals_against }}</td>
                                                 <td class="px-5 py-3.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400 text-center">{{ s.points }}</td>
                                            </tr>
                                       </tbody>
                                  </table>
                             </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="lg:col-span-4 space-y-6">
                         <!-- Đội hình ra sân trận gần nhất (Vertical Grass Pitch) -->
                         <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm relative flex flex-col">
                              <div class="flex items-center justify-between mb-6">
                                   <h3 class="text-[9px] font-bold uppercase tracking-widest text-gray-400">Đội hình ra sân gần nhất</h3>
                                   <div v-if="lastLineup?.formation" class="px-2 py-0.5 bg-gray-50 dark:bg-gray-900 rounded text-[9px] font-bold text-gray-400 uppercase tracking-widest border border-gray-100 dark:border-gray-700">{{ lastLineup.formation }}</div>
                              </div>
                              
                              <div v-if="lastLineup" class="aspect-[3/4] bg-[#1a3326] rounded-2xl relative overflow-hidden border border-gray-100 dark:border-gray-700 shadow-inner">
                                   <!-- Expand Button -->
                                   <button @click="showPitchModal = true" 
                                        class="absolute top-3 right-3 z-50 p-2 bg-black/40 hover:bg-black/60 backdrop-blur-md rounded-xl border border-white/10 text-white transition-all shadow-xl active:scale-95 group"
                                        title="Phóng to đội hình">
                                        <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                        </svg>
                                   </button>
                                   <!-- Grass Pattern (Vertical Stripes) -->
                                   <div class="absolute inset-0 opacity-[0.08]"
                                        style="background-image: repeating-linear-gradient(0deg, transparent, transparent 10%, rgba(255, 255, 255, 0.05) 10%, rgba(255, 255, 255, 0.05) 20%);"></div>
                                   
                                   <!-- Pitch Markings -->
                                   <div class="absolute inset-4 border border-white/10 pointer-events-none"></div>
                                   <div class="absolute inset-x-4 top-1/2 h-px bg-white/10"></div>
                                   <div class="absolute top-1/2 left-1/2 w-20 h-20 border border-white/10 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                                   
                                   <!-- Goal Areas -->
                                   <div class="absolute top-4 left-1/2 -translate-x-1/2 w-24 h-12 border-x border-b border-white/10"></div>
                                   <div class="absolute bottom-4 left-1/2 -translate-x-1/2 w-24 h-12 border-x border-t border-white/10"></div>

                                   <!-- Players -->
                                   <div v-for="p in processedVerticalLineup" :key="p.id"
                                        class="absolute transform -translate-x-1/2 -translate-y-1/2 flex flex-col items-center gap-1 transition-all duration-500"
                                        :style="p.style">
                                        <div class="relative group cursor-pointer" @click="viewPlayer(p.id)">
                                             <div class="w-10 h-10 md:w-11 md:h-11 rounded-full border-2 border-white/20 bg-gray-900/50 shadow-xl overflow-hidden group-hover:scale-110 transition-transform">
                                                  <img v-if="p.id" :src="`https://media.api-sports.io/football/players/${p.id}.png`" class="w-full h-full object-cover" />
                                                  <div v-else class="w-full h-full flex items-center justify-center text-[10px] font-bold text-white">{{ p.number }}</div>
                                             </div>
                                             <!-- Player Rating -->
                                             <div v-if="p.rating" 
                                                  class="absolute -top-1 -right-2 w-6 h-4 rounded-md text-[8px] font-black flex items-center justify-center shadow-lg border border-white/30 z-10"
                                                  :class="getRatingClass(p.rating)">
                                                  {{ p.rating }}
                                             </div>
                                        </div>
                                        <div class="flex flex-col items-center leading-none">
                                             <div class="bg-black/40 px-2 py-0.5 rounded backdrop-blur-sm border border-white/5">
                                                  <span class="text-[9px] font-bold text-white tracking-tight truncate max-w-[65px] block">{{ p.name.split(' ').pop() }}</span>
                                             </div>
                                             <span class="text-[8px] font-bold text-white/60 uppercase tracking-widest mt-0.5">{{ p.number }}</span>
                                        </div>
                                   </div>
                              </div>
                              <div v-else class="aspect-[3/4] bg-gray-50 dark:bg-gray-900/50 rounded-2xl flex items-center justify-center text-[10px] font-bold text-gray-300 uppercase tracking-widest border border-dashed border-gray-200 dark:border-gray-800">
                                   Chưa có dữ liệu đội hình
                              </div>
                         </div>

                         <!-- Pitch Fullscreen Modal -->
                         <Teleport to="body">
                              <Transition name="modal-fade">
                                   <div v-if="showPitchModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                                        <div class="absolute inset-0 bg-black/80 backdrop-blur-md" @click="showPitchModal = false"></div>
                                        
                                        <div class="relative w-[95%] sm:max-w-2xl bg-[#1a3326] aspect-[3/4] rounded-[2rem] sm:rounded-[2.5rem] overflow-hidden shadow-2xl border-4 border-white/5">
                                             <!-- Close Button -->
                                             <button @click="showPitchModal = false" class="absolute top-4 right-4 sm:top-6 sm:right-6 z-[60] p-2 sm:p-3 bg-black/40 hover:bg-black/60 backdrop-blur-xl rounded-xl sm:rounded-2xl border border-white/10 text-white transition-all shadow-2xl active:scale-95">
                                                  <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                                  </svg>
                                             </button>

                                             <!-- Grass Pattern -->
                                             <div class="absolute inset-0 opacity-[0.1]"
                                                  style="background-image: repeating-linear-gradient(0deg, transparent, transparent 10%, rgba(255, 255, 255, 0.05) 10%, rgba(255, 255, 255, 0.05) 20%);"></div>
                                             
                                             <!-- Pitch Markings -->
                                             <div class="absolute inset-4 sm:inset-8 border-2 border-white/10 pointer-events-none"></div>
                                             <div class="absolute inset-x-4 sm:inset-x-8 top-1/2 h-px sm:h-0.5 bg-white/10"></div>
                                             <div class="absolute top-1/2 left-1/2 w-24 h-24 sm:w-40 sm:h-40 border-2 border-white/10 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                                             
                                             <!-- Goal Areas -->
                                             <div class="absolute top-4 sm:top-8 left-1/2 -translate-x-1/2 w-32 sm:w-48 h-16 sm:h-24 border-x-2 border-b-2 border-white/10"></div>
                                             <div class="absolute bottom-4 sm:bottom-8 left-1/2 -translate-x-1/2 w-32 sm:w-48 h-16 sm:h-24 border-x-2 border-t-2 border-white/10"></div>

                                             <!-- Formation Title -->
                                             <div class="absolute top-6 left-6 sm:top-10 sm:left-10 z-40">
                                                  <div class="px-3 py-1 sm:px-4 sm:py-2 bg-black/40 backdrop-blur-md rounded-xl sm:rounded-2xl border border-white/10 shadow-2xl">
                                                       <span class="text-[10px] sm:text-xs font-black text-white/40 uppercase tracking-[0.3em]">{{ lastLineup.formation }}</span>
                                                  </div>
                                             </div>

                                             <!-- Players (Larger version) -->
                                             <div v-for="p in processedVerticalLineup" :key="'modal-'+p.id"
                                                  class="absolute transform -translate-x-1/2 -translate-y-1/2 flex flex-col items-center gap-1.5 sm:gap-3 transition-all duration-700"
                                                  :style="p.style">
                                                  <div class="relative group cursor-pointer" @click="viewPlayer(p.id); showPitchModal = false">
                                                       <div class="w-12 h-12 sm:w-20 sm:h-20 rounded-full border-2 sm:border-4 border-white/20 bg-gray-900/50 shadow-2xl overflow-hidden group-hover:scale-110 transition-transform">
                                                            <img v-if="p.id" :src="`https://media.api-sports.io/football/players/${p.id}.png`" class="w-full h-full object-cover" />
                                                       </div>
                                                       <!-- Player Rating -->
                                                       <div v-if="p.rating" 
                                                            class="absolute -top-1 -right-2 sm:-right-3 w-8 h-5 sm:w-10 sm:h-7 rounded-lg sm:rounded-xl text-[9px] sm:text-[12px] font-black flex items-center justify-center shadow-2xl border border-white/30 sm:border-2 z-10"
                                                            :class="getRatingClass(p.rating)">
                                                            {{ p.rating }}
                                                       </div>
                                                  </div>
                                                  <div class="flex flex-col items-center">
                                                       <div class="bg-black/60 px-2 py-0.5 sm:px-4 sm:py-1.5 rounded-lg sm:rounded-xl backdrop-blur-md border border-white/10 group-hover:bg-emerald-500 transition-colors">
                                                            <span class="text-[10px] sm:text-sm font-bold text-white tracking-tight drop-shadow-xl">{{ p.name.split(' ').pop() }}</span>
                                                       </div>
                                                       <span class="text-[8px] sm:text-xs font-black text-white/50 uppercase tracking-widest mt-1 sm:mt-1.5">{{ p.number }}</span>
                                                  </div>
                                             </div>
                                        </div>
                                   </div>
                              </Transition>
                         </Teleport>

                         <!-- HLV -->
                         <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
                              <h3 class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-5">Huấn luyện viên hiện tại</h3>
                              <div v-if="coachHistory.length > 0" class="flex items-center gap-4">
                                   <div class="w-14 h-14 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-700 overflow-hidden shadow-inner">
                                        <img :src="coachHistory[coachHistory.length-1].photo" class="w-full h-full object-cover" />
                                   </div>
                                   <div class="flex-1">
                                        <h4 class="text-[11px] font-bold text-gray-900 dark:text-white uppercase mb-1">{{ coachHistory[coachHistory.length-1].name }}</h4>
                                        <div class="flex items-center gap-2">
                                             <span class="text-lg font-bold text-emerald-500">{{ coachHistory[coachHistory.length-1].winRate }}%</span>
                                             <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">{{ coachHistory[coachHistory.length-1].ppg }} PPG</span>
                                        </div>
                                   </div>
                              </div>
                              <div v-else class="py-4 flex flex-col items-center justify-center border border-dashed border-gray-100 dark:border-gray-700 rounded-xl bg-gray-50/50 dark:bg-gray-900/30">
                                   <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Chưa có thông tin HLV</span>
                              </div>
                         </div>
                    </div>
                </div>

                <!-- STANDINGS TAB -->
                <div v-else-if="activeTab === 'standings'" class="space-y-6">
                    <StandingTable v-if="standings.length > 0" :standings="standings" :league-id="nextMatch?.league_id" />
                    <div v-else class="py-20 text-center text-gray-400 text-[10px] font-bold uppercase tracking-widest border border-dashed border-gray-100 dark:border-gray-800 rounded-2xl">
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
                <div v-else-if="activeTab === 'squad'" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto no-scrollbar">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-[9px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50 dark:border-gray-700">
                                    <th class="px-6 py-4">Cầu thủ</th>
                                    <th class="px-6 py-4 text-center">VT</th>
                                    <th class="px-6 py-4">Quốc gia</th>
                                    <th class="px-6 py-4 text-center">Số áo</th>
                                    <th class="px-6 py-4 text-center">Tuổi</th>
                                    <th class="px-6 py-4 text-center">Chiều cao</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                <tr v-for="p in squad" :key="p.id" @click="viewPlayer(p.id)" class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-all cursor-pointer group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-xl bg-gray-50 dark:bg-gray-900 overflow-hidden flex-shrink-0 border border-gray-100 dark:border-gray-700">
                                                <img :src="p.photo" class="w-full h-full object-cover" />
                                            </div>
                                            <span class="text-[11px] font-bold text-gray-950 dark:text-white uppercase tracking-tight group-hover:text-emerald-500 transition-colors">{{ p.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-[9px] font-bold text-gray-400 uppercase">{{ positionLabels[p.position] || p.position }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2.5">
                                            <div v-if="getCountryFlag(p.nationality)" class="w-5 h-3.5 rounded-sm overflow-hidden flex-shrink-0 shadow-sm border border-gray-100 dark:border-gray-700">
                                                <img :src="getCountryFlag(p.nationality)" class="w-full h-full object-cover" />
                                            </div>
                                            <span class="text-[10px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-tight">{{ translateCountry(p.nationality) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center text-[11px] font-bold text-gray-900 dark:text-white">
                                        {{ p.number || '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-[11px] font-bold text-gray-500">
                                        {{ calcAge(p.birth_year) }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-[10px] font-bold text-gray-400">
                                        {{ p.height ? p.height : '-' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </MainLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

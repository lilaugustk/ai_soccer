<script setup>
import { ref, computed, onMounted } from "vue";
import { Head, router, Link } from "@inertiajs/vue3";
import MainLayout from "../../Layouts/MainLayout.vue";
import dayjs from "dayjs";
import LeagueSidebar from "../../Components/LeagueSidebar.vue";
import MatchCard from "../../Components/MatchCard.vue";

const props = defineProps({
    player: { type: Object, required: true },
    seasonStats: { type: Array, default: () => [] },
    latestStat: { type: Object, default: () => ({}) },
    matchHistory: { type: Array, default: () => [] },
    careerTotals: { type: Object, default: () => ({}) },
    availableSeasons: { type: Array, default: () => [] },
    currentSeason: { type: Number, default: 2025 },
    nationalTeam: { type: Object, default: null },
    transfers: { type: Array, default: () => [] },
});

// For backward compatibility or convenience
const team = computed(() => props.player.team || {});

const activeTab = ref("overview");
const tabs = [
    { id: "overview", label: "Tổng quan" },
    { id: "matches", label: "Trận đấu" },
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
    router.visit(`/players/${props.player.id}?season=${s}`, {
        preserveState: false,
        preserveScroll: true
    });
};

const formatDate = (dateStr) => dayjs(dateStr).format("DD/MM/YYYY");
const formatTime = (dateStr) => dayjs(dateStr).format("HH:mm");

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
    G: "Thủ môn",
    D: "Hậu vệ",
    M: "Tiền vệ",
    F: "Tiền đạo",
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

const formatSeason = (s) => {
    if (!s) return '—';
    const year = parseInt(s);
    if (isNaN(year)) return s;
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

const recentForm = computed(() => {
    // Only include matches where player actually played (rating > 0)
    return props.matchHistory
        .filter(m => parseFloat(m.rating) > 0)
        .slice(0, 5)
        .reverse();
});

const getRatingColor = (rating) => {
    const val = parseFloat(rating);
    if (val >= 7.5) return "#10b981"; // emerald-500
    if (val >= 6.5) return "#f59e0b"; // amber-500
    return "#ef4444"; // red-500
};

const getRatingTextColor = (rating) => {
    const val = parseFloat(rating);
    if (val >= 7.5) return "text-emerald-500";
    if (val >= 6.5) return "text-amber-500";
    return "text-red-500";
};

const performanceChartData = computed(() => {
    const data = recentForm.value;
    if (data.length === 0) return { points: [], pathD: "", areaD: "" };

    const points = data.map((stat, i) => {
        // Balanced padding (5% on each side)
        const x = 5 + (i / (data.length - 1)) * 90;
        // Scale rating (0-10) to Y (90% to 10%) to avoid vertical clipping
        const rating = parseFloat(stat.rating) || 0;
        const y = 90 - ((rating - 5) / 5) * 80; 
        return { x, y, stat };
    });

    // Build smooth SVG path
    let pathD = `M ${points[0].x} ${points[0].y} `;
    for (let i = 1; i < points.length; i++) {
        const p0 = points[i - 1];
        const p1 = points[i];
        const cp1x = p0.x + (p1.x - p0.x) / 2;
        const cp1y = p0.y;
        const cp2x = p0.x + (p1.x - p0.x) / 2;
        const cp2y = p1.y;
        pathD += `C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${p1.x} ${p1.y} `;
    }

    const areaD = `${pathD} L ${points[points.length - 1].x} 100 L ${points[0].x} 100 Z`;

    return { points, pathD, areaD };
});

const getMatchResult = (stat) => {
    if (!stat.match) return '-';
    const isHome = stat.team_id === stat.match.home_team_id;
    const teamScore = isHome ? stat.match.home_score : stat.match.away_score;
    const opponentScore = isHome ? stat.match.away_score : stat.match.home_score;
    
    if (teamScore > opponentScore) return 'W';
    if (teamScore < opponentScore) return 'L';
    return 'D';
};

const getMatchOpponent = (stat) => {
    if (!stat.match) return null;
    return stat.team_id === stat.match.home_team_id ? stat.match.away_team : stat.match.home_team;
};

const getRatingClass = (rating) => {
    const val = parseFloat(rating);
    if (isNaN(val) || val === 0) return "bg-gray-100 dark:bg-gray-800 text-gray-400";
    if (val >= 7.5) return "bg-emerald-500 text-white";
    if (val >= 6.5) return "bg-amber-500 text-white";
    return "bg-red-500 text-white";
};

const viewMatch = (id) => router.visit(`/matches/${id}`);

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const tabFromUrl = params.get('tab');
    if (tabFromUrl && tabs.find(t => t.id === tabFromUrl)) {
        activeTab.value = tabFromUrl;
    }
});
</script>

<template>
    <Head :title="`${player.name} | AI Soccer`" />

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
                        <Link v-if="team" :href="`/teams/${team.id}`" class="hover:text-emerald-500">{{ team.name }}</Link>
                        <span v-else>Cầu thủ</span>
                        <span>/</span>
                        <span class="text-gray-900 dark:text-gray-200">{{ player.name }}</span>
                    </nav>

                    <!-- Header -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 mb-6 border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden">
                        <div class="flex flex-col md:flex-row items-center gap-6 relative z-10">
                            <!-- Player Photo -->
                            <div class="w-20 h-20 md:w-24 md:h-24 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-700 overflow-hidden shadow-inner flex items-center justify-center p-1">
                                <img :src="player.photo_url" class="w-full h-full object-cover rounded-xl filter drop-shadow-md" />
                            </div>

                            <div class="flex-1 text-center md:text-left space-y-3">
                                <div class="flex flex-wrap items-center justify-center md:justify-start gap-3">
                                    <!-- Nationality -->
                                    <div v-if="player.nationality" class="flex items-center gap-1.5 px-3 py-1 bg-gray-100 dark:bg-gray-700/50 rounded-full text-[9px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-300">
                                        <img :src="getCountryFlag(player.nationality)" class="w-3 h-2.5 object-cover rounded-sm" v-if="getCountryFlag(player.nationality)" />
                                        {{ translateCountry(player.nationality) }}
                                    </div>
                                    <!-- Position -->
                                    <div class="px-3 py-1 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-full text-[9px] font-bold uppercase tracking-widest">
                                        {{ player.specific_position ? `${player.specific_position} (${positionLabels[player.position] || player.position})` : (positionLabels[player.position] || player.position) }}
                                    </div>
                                    <!-- Current Team -->
                                    <div v-if="team.name" class="px-3 py-1 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-full text-[9px] font-bold uppercase tracking-widest">
                                        {{ team.name }}
                                    </div>
                                </div>
                                
                                <h1 class="text-3xl font-bold text-gray-900 dark:text-white uppercase tracking-tight">{{ player.name }}</h1>
                                

                            </div>


                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="border-b border-gray-100 dark:border-gray-700 mb-6 relative">
                        <div class="flex items-center justify-between gap-8">
                            <div class="flex gap-8 overflow-x-auto no-scrollbar whitespace-nowrap">
                                <button v-for="tab in tabs" :key="tab.id" @click="changeTab(tab.id)"
                                    class="relative pb-4 px-1 text-[11px] font-bold uppercase tracking-[0.2em] transition-all whitespace-nowrap outline-none"
                                    :class="activeTab === tab.id ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 hover:text-gray-900 dark:hover:text-white'">
                                    {{ tab.label }}
                                    <div v-if="activeTab === tab.id" 
                                         class="absolute bottom-0 left-0 right-0 h-0.5 bg-emerald-500 rounded-full animate-in fade-in slide-in-from-left-1">
                                    </div>
                                </button>
                            </div>

                            <!-- Season Dropdown -->
                            <div class="relative season-dropdown pb-4 transition-all duration-200" 
                                 :class="activeTab === 'stats' ? 'opacity-100' : 'opacity-0 pointer-events-none'">
                                <button @click="isSeasonOpen = !isSeasonOpen" 
                                        class="flex items-center gap-2 px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-lg text-[11px] font-bold uppercase tracking-[0.2em] transition-all hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Mùa giải: {{ availableSeasons.find(s => s.id == currentSeason)?.name || currentSeason }}
                                    <svg :class="['w-2.5 h-2.5 transition-transform', isSeasonOpen ? 'rotate-180' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                                <div v-show="isSeasonOpen" class="absolute top-full right-0 mt-1 min-w-full bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl shadow-2xl py-2 z-50 max-h-60 overflow-y-auto no-scrollbar">
                                    <button v-for="s in availableSeasons" :key="s.id" @click="changeSeason(s.id)" class="w-full text-center px-4 py-2 text-[11px] font-bold hover:bg-gray-50 dark:hover:bg-gray-700 whitespace-nowrap" :class="s.id == currentSeason ? 'text-emerald-500' : 'text-gray-500'">{{ s.name }}</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Area -->
                    <div class="min-h-[600px]">
                        <!-- OVERVIEW TAB -->
                        <div v-if="activeTab === 'overview'" class="space-y-6">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                                <!-- Profile Card -->
                                <div class="lg:col-span-4">
                                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm h-full">
                                        <h3 class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-6">Thông tin chi tiết</h3>
                                        <div class="space-y-4">
                                            <div class="flex justify-between items-center py-2 border-b border-gray-50 dark:border-gray-700/50">
                                                <span class="text-[10px] font-medium text-gray-400 uppercase">Số áo</span>
                                                <span class="text-[10px] font-bold text-gray-900 dark:text-white">{{ player.jersey_number || '-' }}</span>
                                            </div>
                                            <div class="flex justify-between items-center py-2 border-b border-gray-50 dark:border-gray-700/50">
                                                <span class="text-[10px] font-medium text-gray-400 uppercase">Ngày sinh</span>
                                                <span class="text-[10px] font-bold text-gray-900 dark:text-white">{{ formatDate(player.date_of_birth) }} ({{ calcAge(player.date_of_birth) }} tuổi)</span>
                                            </div>
                                            <div class="flex justify-between items-center py-2 border-b border-gray-50 dark:border-gray-700/50">
                                                <span class="text-[10px] font-medium text-gray-400 uppercase">Chiều cao</span>
                                                <span class="text-[10px] font-bold text-gray-900 dark:text-white">{{ player.height_cm ? player.height_cm + ' cm' : '-' }}</span>
                                            </div>
                                            <div class="flex justify-between items-center py-2 border-b border-gray-50 dark:border-gray-700/50">
                                                <span class="text-[10px] font-medium text-gray-400 uppercase">Chân thuận</span>
                                                <span class="text-[10px] font-bold text-gray-900 dark:text-white">{{ footLabels[player.preferred_foot] || '-' }}</span>
                                            </div>
                                            <div v-if="team.name" class="flex justify-between items-center py-2 border-b border-gray-50 dark:border-gray-700/50">
                                                <span class="text-[10px] font-medium text-gray-400 uppercase">Câu lạc bộ</span>
                                                <span class="text-[10px] font-bold text-blue-500">{{ team.name }}</span>
                                            </div>
                                            <div class="flex justify-between items-center py-2 border-b border-gray-50 dark:border-gray-700/50">
                                                <span class="text-[10px] font-medium text-gray-400 uppercase">Giá trị</span>
                                                <span class="text-[10px] font-bold text-emerald-500">{{ formatValue(player.market_value_eur) }}</span>
                                            </div>
                                            <div class="flex justify-between items-center py-2 border-b border-gray-50 dark:border-gray-700/50">
                                                <span class="text-[10px] font-medium text-gray-400 uppercase">Hết hạn hợp đồng</span>
                                                <span class="text-[10px] font-bold text-gray-900 dark:text-white">{{ player.contract_until ? dayjs(player.contract_until).format('MM/YYYY') : '-' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Recent Form -->
                                <div class="lg:col-span-8">
                                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm h-full">
                                        <h3 class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-6 w-full">Phong độ gần đây</h3>
                                        
                                        <div v-if="recentForm.length > 0" class="relative pt-10 pb-6">
                                            <!-- Chart Background Grid -->
                                            <div class="absolute inset-x-0 top-0 bottom-0 pointer-events-none px-2">
                                                <div v-for="level in [10, 9, 8, 7, 6, 5]" :key="level" 
                                                     class="absolute w-full border-t border-gray-100/10 dark:border-gray-700/30 border-dashed"
                                                     :style="{ top: `${90 - ((level - 5) / 5) * 80}%` }">
                                                </div>
                                            </div>

                                            <div class="relative h-48 w-full">
                                                <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="w-full h-full overflow-visible">
                                                    <defs>
                                                        <linearGradient id="lineGradient" x1="0" y1="0" x2="0" y2="1">
                                                            <stop offset="0%" stop-color="#10b981" stop-opacity="0.2" />
                                                            <stop offset="100%" stop-color="#10b981" stop-opacity="0" />
                                                        </linearGradient>
                                                    </defs>

                                                    <!-- Area Fill -->
                                                    <path :d="performanceChartData.areaD" fill="url(#lineGradient)" />

                                                    <!-- Smooth Line -->
                                                    <path :d="performanceChartData.pathD" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                                                </svg>

                                                <!-- Data Nodes (Logos) -->
                                                <div class="absolute inset-0">
                                                    <div v-for="(node, idx) in performanceChartData.points" :key="idx"
                                                         @click="viewMatch(node.stat.match?.id)"
                                                         class="absolute transform -translate-x-1/2 -translate-y-1/2 group cursor-pointer z-20"
                                                         :style="{ left: `${node.x}%`, top: `${node.y}%` }">
                                                        
                                                        <!-- Logo Node -->
                                                        <div class="w-8 h-8 rounded-xl bg-white dark:bg-gray-800 border-2 shadow-lg flex items-center justify-center p-1.5 transition-all duration-300 group-hover:scale-125 group-hover:z-30"
                                                             :style="{ borderColor: getRatingColor(node.stat.rating) }">
                                                            <img v-if="getMatchOpponent(node.stat)?.logo_url" :src="getMatchOpponent(node.stat).logo_url" class="w-full h-full object-contain" />
                                                            <img v-else-if="getMatchOpponent(node.stat)?.name && getCountryFlag(getMatchOpponent(node.stat).name)" :src="getCountryFlag(getMatchOpponent(node.stat).name)" class="w-full h-full object-contain" />
                                                            <span v-else class="text-[8px] font-black">{{ getMatchOpponent(node.stat)?.name?.substring(0,2) }}</span>
                                                        </div>

                                                        <!-- Rating Tooltip (Always Visible) -->
                                                        <div class="absolute -top-6 left-1/2 -translate-x-1/2 bg-white dark:bg-gray-900 px-1.5 py-0.5 rounded-md border border-gray-100 dark:border-gray-700 shadow-sm whitespace-nowrap z-40">
                                                            <span class="text-[9px] font-black" :class="getRatingTextColor(node.stat.rating)">{{ Number(node.stat.rating).toFixed(1) }}</span>
                                                        </div>

                                                        <!-- Match Info Tooltip -->
                                                        <div class="absolute top-full mt-2 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[8px] px-2 py-1 rounded shadow-xl opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-40 pointer-events-none">
                                                            vs {{ getMatchOpponent(node.stat)?.name }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-else class="flex-1 flex flex-col items-center justify-center py-12">
                                            <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                            </svg>
                                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Đang cập nhật dữ liệu trận đấu</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Career Stats Table -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mt-6">
                                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                                    <h3 class="text-[9px] font-bold uppercase tracking-widest text-gray-400">Chỉ số sự nghiệp</h3>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left">
                                        <thead>
                                            <tr class="bg-gray-50/50 dark:bg-gray-900/30 text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700">
                                                <th class="px-6 py-4">Mùa giải</th>
                                                <th class="px-6 py-4">Đội bóng / Giải</th>
                                                <th class="px-6 py-4 text-center">Trận</th>
                                                <th class="px-6 py-4 text-center">Phút</th>
                                                <th class="px-6 py-4 text-center">Bàn</th>
                                                <th class="px-6 py-4 text-center">Kiến tạo</th>
                                                <th class="px-6 py-4 text-center">Điểm</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                            <tr v-for="s in seasonStats" :key="s.id" class="text-[11px] hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors">
                                                <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">{{ s.season ? (s.season.year ? s.season.year + '-' + (s.season.year + 1) : s.season.name) : formatSeason(s.season_id) }}</td>
                                                <td class="px-6 py-4">
                                                    <div class="flex flex-col">
                                                        <span class="font-bold text-gray-900 dark:text-white uppercase">{{ s.team?.name }}</span>
                                                        <span class="text-[9px] text-gray-400 uppercase tracking-widest">{{ s.league?.name }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-center font-semibold text-gray-600 dark:text-gray-300">{{ s.matches }}</td>
                                                <td class="px-6 py-4 text-center font-semibold text-gray-600 dark:text-gray-300">{{ s.minutes }}'</td>
                                                <td class="px-6 py-4 text-center font-bold text-gray-900 dark:text-white">{{ s.goals }}</td>
                                                <td class="px-6 py-4 text-center font-semibold text-gray-600 dark:text-gray-300">{{ s.assists }}</td>
                                                <td class="px-6 py-4 text-center">
                                                    <span class="px-2 py-1 rounded-md text-[10px] font-bold" :class="getRatingClass(s.avg_rating)">
                                                        {{ s.avg_rating }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Lịch sử chuyển nhượng -->
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mt-6">
                                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                                    <h3 class="text-[9px] font-bold uppercase tracking-widest text-gray-400">Lịch sử chuyển nhượng</h3>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div v-for="t in transfers" :key="t.id" class="bg-gray-50/30 dark:bg-gray-900/20 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-6">
                                        <div class="flex flex-col items-center min-w-[80px]">
                                            <span class="text-[10px] font-bold text-gray-400 uppercase">{{ formatDate(t.transfer_date) }}</span>
                                        </div>
                                        
                                        <div class="flex-1 flex items-center gap-8">
                                            <div class="flex flex-col items-end flex-1">
                                                <span class="text-[8px] font-bold text-gray-400 uppercase tracking-widest mb-1">Từ</span>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[11px] font-bold text-gray-900 dark:text-white uppercase">{{ t.from_team?.name || 'Không rõ' }}</span>
                                                    <img v-if="t.from_team?.logo_url" :src="t.from_team.logo_url" class="w-5 h-5 object-contain" />
                                                </div>
                                            </div>
                                            
                                            <div class="text-gray-300 dark:text-gray-700">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                            </div>
                                            
                                            <div class="flex flex-col items-start flex-1">
                                                <span class="text-[8px] font-bold text-gray-400 uppercase tracking-widest mb-1">Đến</span>
                                                <div class="flex items-center gap-2">
                                                    <img v-if="t.to_team?.logo_url" :src="t.to_team.logo_url" class="w-5 h-5 object-contain" />
                                                    <span class="text-[11px] font-bold text-gray-900 dark:text-white uppercase">{{ t.to_team?.name || 'Không rõ' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="flex flex-col items-end min-w-[100px]">
                                            <span class="text-[8px] font-bold text-gray-400 uppercase tracking-widest">Loại</span>
                                            <span class="text-[11px] font-bold text-gray-900 dark:text-white uppercase">{{ t.type || 'Chuyển nhượng' }}</span>
                                        </div>
                                    </div>
                                    
                                    <div v-if="transfers.length === 0" class="py-12 text-center text-gray-400 text-[10px] font-bold uppercase tracking-widest border border-dashed border-gray-100 dark:border-gray-800 rounded-2xl">
                                        Không có dữ liệu lịch sử chuyển nhượng
                                    </div>
                                </div>
                            </div>
                        </div>



                        <!-- MATCHES TAB -->
                        <div v-else-if="activeTab === 'matches'" class="space-y-3">
                            <div v-for="stat in matchHistory" :key="stat.id" @click="viewMatch(stat.match?.id)" class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm flex items-center gap-6 hover:border-emerald-500/50 transition-all cursor-pointer group">
                                <div class="flex flex-col items-center min-w-[60px]">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase">{{ formatDate(stat.match?.match_at || stat.match?.event_date) }}</span>
                                    <span class="text-[12px] font-bold text-gray-900 dark:text-white">{{ formatTime(stat.match?.match_at || stat.match?.event_date) }}</span>
                                </div>
                                
                                <div class="flex-1 flex items-center justify-center gap-4">
                                    <div class="flex items-center gap-3 flex-1 justify-end">
                                        <span class="text-[11px] font-bold text-gray-900 dark:text-white uppercase truncate text-right">{{ stat.match?.home_team?.name }}</span>
                                        <img :src="stat.match?.home_team?.logo_url" class="w-6 h-6 object-contain" />
                                    </div>
                                    
                                    <div class="px-3 py-1 bg-gray-50 dark:bg-gray-900 rounded-lg text-sm font-bold text-gray-950 dark:text-white tabular-nums border border-gray-100 dark:border-gray-800">
                                        {{ stat.match?.home_score }} - {{ stat.match?.away_score }}
                                    </div>
                                    
                                    <div class="flex items-center gap-3 flex-1">
                                        <img :src="stat.match?.away_team?.logo_url" class="w-6 h-6 object-contain" />
                                        <span class="text-[11px] font-bold text-gray-900 dark:text-white uppercase truncate">{{ stat.match?.away_team?.name }}</span>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-4 min-w-[120px] justify-end">
                                    <div v-if="stat.rating" class="flex flex-col items-end">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase">Điểm số</span>
                                        <span class="text-[11px] font-bold" :class="parseFloat(stat.rating) >= 7.5 ? 'text-emerald-500' : 'text-gray-900 dark:text-white'">{{ stat.rating }}</span>
                                    </div>
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-[10px] font-bold shadow-sm" :class="getMatchResult(stat) === 'W' ? 'bg-emerald-500 text-white' : getMatchResult(stat) === 'L' ? 'bg-red-500 text-white' : 'bg-amber-500 text-white'">
                                        {{ getMatchResult(stat) }}
                                    </div>
                                </div>
                            </div>
                            
                            <div v-if="matchHistory.length === 0" class="py-20 text-center text-gray-400 text-[10px] font-bold uppercase tracking-widest border border-dashed border-gray-100 dark:border-gray-800 rounded-2xl">
                                Không có dữ liệu trận đấu gần đây
                            </div>
                        </div>
                    </div>
                </main>

                <LeagueSidebar />
            </div>
        </div>
    </MainLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

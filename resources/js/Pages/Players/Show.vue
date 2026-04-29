<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import MainLayout from '../../Layouts/MainLayout.vue';
import dayjs from 'dayjs';

const props = defineProps({
    player:       { type: Object, required: true },
    seasonStats:  { type: Array, default: () => [] },
    latestStat:   { type: Object, default: null },
    matchHistory: { type: Array, default: () => [] },
    careerTotals: { type: Object, default: () => ({}) },
});

// ── Active tab ─────────────────────────────
const activeTab = ref('summary');
const tabs = [
    { id: 'summary',   label: 'Tổng quan' },
    { id: 'transfers', label: 'Chuyển nhượng' },
    { id: 'injuries',  label: 'Chấn thương' },
    { id: 'career',    label: 'Sự nghiệp' },
];

// ── Helpers ────────────────────────────────
const formatDate = (d) => d ? dayjs(d).format('DD.MM.YYYY') : '—';

const getRatingColor = (rating) => {
    const r = parseFloat(rating);
    if (!r) return 'bg-gray-500';
    if (r >= 7.5) return 'bg-emerald-500';
    if (r >= 6.5) return 'bg-amber-500';
    return 'bg-rose-500';
};

const matchResult = (stat) => {
    const g = stat.match;
    if (!g || g.home_score === null) return { label: '—', cls: 'bg-gray-200 text-gray-500 dark:bg-gray-700' };
    const myScore = g.home_team_id === stat.team_id ? g.home_score : g.away_score;
    const oppScore = g.home_team_id === stat.team_id ? g.away_score : g.home_score;
    if (myScore > oppScore) return { label: 'W', cls: 'bg-emerald-500 text-white' };
    if (myScore < oppScore) return { label: 'L', cls: 'bg-rose-500 text-white' };
    return { label: 'D', cls: 'bg-amber-500 text-white' };
};

const age = computed(() => {
    if (props.player.birth_year) return new Date().getFullYear() - props.player.birth_year;
    return null;
});

const getCountryCode = (name) => {
    if (!name) return 'un';
    const countries = {
        'England': 'gb-eng',
        'France': 'fr',
        'Brazil': 'br',
        'Germany': 'de',
        'Spain': 'es',
        'Portugal': 'pt',
        'Argentina': 'ar',
        'Italy': 'it',
        'Netherlands': 'nl',
        'Belgium': 'be',
        'Norway': 'no',
        'Sweden': 'se',
        'Denmark': 'dk',
        'USA': 'us',
        'Croatia': 'hr',
        'Scotland': 'gb-sct',
        'Wales': 'gb-wls',
        'Japan': 'jp',
        'South Korea': 'kr',
        'Egypt': 'eg',
        'Senegal': 'sn',
        'Morocco': 'ma'
    };
    return countries[name] || name.substring(0, 2).toLowerCase();
};

// Advanced Stats Breakdown
const advancedStats = computed(() => {
    if (!props.latestStat?.detailed_stats) return null;
    const s = props.latestStat.detailed_stats;
    return {
        rating: s.games?.rating ?? '—',
        attack: [
            { label: 'Sút / Trận', val: ((s.shots?.total ?? 0) / (props.latestStat.games || 1)).toFixed(1) },
            { label: 'Trúng đích', val: s.shots?.on ?? 0 },
            { label: 'Bàn thắng', val: s.goals?.total ?? 0 },
        ],
        passes: [
            { label: 'Chuyền / Trận', val: ((s.passes?.total ?? 0) / (props.latestStat.games || 1)).toFixed(0) },
            { label: 'Key Passes', val: s.passes?.key ?? 0 },
            { label: 'Chính xác', val: (s.passes?.accuracy ?? 0) + '%' },
        ],
        defense: [
            { label: 'Tắc bóng', val: s.tackles?.total ?? 0 },
            { label: 'Đánh chặn', val: s.tackles?.interceptions ?? 0 },
            { label: 'Thắng tranh chấp', val: s.duels?.won ?? 0 },
        ]
    };
});

// ── Radar Chart Logic (SVG) ────────────────
const isGoalkeeper = computed(() => props.player.position?.toLowerCase().includes('goalkeeper'));

const radarPoints = computed(() => {
    if (!props.latestStat?.detailed_stats) return '';
    const s = props.latestStat.detailed_stats;
    
    let points = [];
    
    if (isGoalkeeper.value) {
        // Goalkeeper specific logic
        points = [
            Math.min(100, (s.passes?.accuracy || 0)), // Chuyền dài/Chính xác
            Math.min(100, (s.tackles?.interceptions || 0) * 20), // Thủ môn quét (dựa trên đánh chặn)
            Math.max(0, 100 - (s.goals?.conceded || 0) * 20), // Bàn thua (càng ít càng cao)
            Math.min(100, (s.goals?.saves || 0) * 15), // Cứu thua
            Math.min(100, (s.duels?.total || 0) * 10), // Bắt bóng bổng/Tranh chấp
            Math.min(100, (props.careerTotals?.clean_sheets || 0) * 10), // Giữ sạch lưới (nếu có dữ liệu tổng)
        ];
    } else {
        // Outfield player logic
        points = [
            Math.min(100, (s.goals?.total || 0) * 10), // Bàn thắng
            Math.min(100, (s.shots?.total || 0) * 5), // Dứt điểm
            Math.min(100, (s.passes?.accuracy || 0)), // Lượt chạm
            Math.min(100, (s.passes?.key || 0) * 20), // Cơ hội tạo ra
            Math.min(100, (s.duels?.won || 0) / 2), // Không chiến
            Math.min(100, ((s.tackles?.total || 0) + (s.tackles?.interceptions || 0)) * 5), // Phòng ngự
        ];
    }
    
    const radius = 60;
    const center = 90;
    return points.map((val, i) => {
        // Back to vertices (starting from -90deg / Top)
        const angle = (Math.PI * 2 * i) / points.length - Math.PI / 2;
        const r = (val / 100) * radius;
        return {
            x: center + r * Math.cos(angle),
            y: center + r * Math.sin(angle)
        };
    });
});

const getLabelPosition = (i) => {
    const angle = (Math.PI * 2 * i) / 6 - Math.PI / 2;
    const r = 88; 
    const center = 90;
    return {
        left: `${center + r * Math.cos(angle)}px`,
        top: `${center + r * Math.sin(angle)}px`,
        transform: 'translate(-50%, -50%)'
    };
};

const getHexPath = (r) => {
    const size = 180;
    const center = size / 2;
    let p = [];
    for(let i=0; i<6; i++) {
        const a = (Math.PI * 2 * i) / 6 - Math.PI / 2;
        p.push(`${center + r * Math.cos(a)},${center + r * Math.sin(a)}`);
    }
    return p.join(' ');
};

const radarLabels = computed(() => {
    if (isGoalkeeper.value) {
        return [
            { name: 'Chuyền dài', val: (props.latestStat?.detailed_stats?.passes?.accuracy || 0) + '%' },
            { name: 'Quét', val: (props.latestStat?.detailed_stats?.tackles?.interceptions || 0) },
            { name: 'Bàn thua', val: (props.latestStat?.detailed_stats?.goals?.conceded || 0) },
            { name: 'Cứu thua %', val: (props.latestStat?.detailed_stats?.goals?.saves || 0) },
            { name: 'Bắt bổng', val: (props.latestStat?.detailed_stats?.duels?.total || 0) },
            { name: 'Sạch lưới', val: (props.careerTotals?.clean_sheets || 0) }
        ];
    }
    return [
        { name: 'Bàn thắng', val: (props.latestStat?.detailed_stats?.goals?.total || 0) },
        { name: 'Dứt điểm', val: (props.latestStat?.detailed_stats?.shots?.total || 0) },
        { name: 'Lượt chạm', val: (props.latestStat?.detailed_stats?.passes?.accuracy || 0) + '%' },
        { name: 'Cơ hội tạo ra', val: (props.latestStat?.detailed_stats?.passes?.key || 0) },
        { name: 'Không chiến', val: (props.latestStat?.detailed_stats?.duels?.won || 0) },
        { name: 'Phòng ngự', val: (props.latestStat?.detailed_stats?.tackles?.total || 0) }
    ];
});

// ── Mini Pitch Logic ───────────────────────
const positionCoords = computed(() => {
    const pos = props.player.position?.toLowerCase() || '';
    if (pos.includes('goalkeeper')) return { x: 50, y: 90 };
    if (pos.includes('defender')) return { x: 50, y: 75 };
    if (pos.includes('midfielder')) return { x: 50, y: 50 };
    if (pos.includes('attacker') || pos.includes('forward')) return { x: 50, y: 25 };
    return { x: 50, y: 50 };
});
</script>

<template>
    <Head :title="`${player.name} — Hồ sơ cầu thủ`" />

    <MainLayout>
        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen pb-12">
        <!-- Player Header (Compact & Refined) -->
        <div class="relative bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 text-gray-900 dark:text-white pt-8 pb-16 overflow-hidden">
            <!-- Background Decoration (Subtle) -->
            <div class="absolute inset-0 opacity-40 pointer-events-none">
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-emerald-500/5 rounded-full blur-[120px]"></div>
                <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-emerald-600/5 rounded-full blur-[120px]"></div>
            </div>

            <div class="max-w-6xl mx-auto px-4 sm:px-6 relative z-10">
                <div class="flex flex-col md:flex-row items-center gap-8">
                    <!-- Photo (Reduced Size & Sharpness Optimized) -->
                    <div class="relative">
                        <div class="w-32 h-32 rounded-2xl overflow-hidden border-4 border-white dark:border-gray-800 shadow-xl bg-gray-50 dark:bg-gray-800">
                            <img v-if="player.photo" :src="player.photo" 
                                class="w-full h-full object-cover brightness-[1.02] contrast-[1.05]" 
                                style="image-rendering: -webkit-optimize-contrast;"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-4xl font-bold text-gray-400">
                                {{ player.name.charAt(0) }}
                            </div>
                        </div>
                        <div v-if="advancedStats?.rating" :class="[getRatingColor(advancedStats.rating), 'absolute -bottom-2 -right-2 px-2 py-1 rounded-lg flex flex-col items-center justify-center border-2 border-white dark:border-gray-950 shadow-lg text-white z-10']">
                            <span class="text-[7px] font-bold uppercase opacity-80">Rating</span>
                            <span class="text-sm font-bold">{{ parseFloat(advancedStats.rating).toFixed(1) }}</span>
                        </div>
                    </div>

                    <!-- Info (Simplified) -->
                    <div class="flex-1 grid grid-cols-1 lg:grid-cols-3 gap-6 w-full">
                        <div class="flex flex-col justify-center text-center md:text-left">
                            <div class="flex items-center justify-center md:justify-start gap-2 mb-1.5">
                                <img v-if="player.team?.logo" :src="player.team?.logo" class="w-5 h-5 object-contain" />
                                <span class="text-emerald-500 uppercase text-[9px] font-bold tracking-widest">{{ player.team?.name }}</span>
                            </div>
                            <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-gray-900 dark:text-white mb-4">
                                {{ player.name }}
                            </h1>
                            <div class="flex gap-10 justify-center md:justify-start">
                                <div v-if="age" class="flex flex-col">
                                    <span class="text-gray-400 uppercase text-[9px] font-bold tracking-widest mb-1">Tuổi</span>
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">{{ age }}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-gray-400 uppercase text-[9px] font-bold tracking-widest mb-1">Quốc tịch</span>
                                    <div class="flex items-center gap-2">
                                        <div v-if="player.nationality" class="w-5 h-3.5 rounded-sm overflow-hidden shadow-sm border border-gray-100 flex-shrink-0">
                                            <img :src="`https://flagcdn.com/w40/${getCountryCode(player.nationality)}.png`" class="w-full h-full object-cover" :alt="player.nationality" />
                                        </div>
                                        <span class="text-lg font-bold text-gray-900 dark:text-white">{{ player.nationality || '—' }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-gray-400 uppercase text-[9px] font-bold tracking-widest mb-1">Thể hình</span>
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">{{ player.height || '—' }} / {{ player.weight || '—' }}</span>
                                </div>
                            </div>
                        </div>

                            <!-- Mini Pitch (Smaller) -->
                            <div class="flex flex-col items-center justify-center">
                                <div class="text-gray-400 uppercase text-[8px] font-bold tracking-widest mb-2">Vị trí</div>
                                <div class="relative w-20 h-28 bg-gray-100 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700/50 overflow-hidden">
                                    <div class="absolute inset-1.5 border border-gray-200 dark:border-gray-700/30 rounded-md"></div>
                                    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-10 h-4 border-b border-gray-200 dark:border-gray-700/30"></div>
                                    <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-10 h-4 border-t border-gray-700/30"></div>
                                    <div class="absolute top-1/2 left-0 right-0 h-px bg-gray-700/20"></div>
                                    <div 
                                        class="absolute w-3.5 h-3.5 bg-emerald-500 rounded-full border-2 border-white shadow-md flex items-center justify-center transition-all duration-1000"
                                        :style="{ left: positionCoords.x + '%', top: positionCoords.y + '%', transform: 'translate(-50%, -50%)' }"
                                    >
                                        <div class="w-1 h-1 bg-white rounded-full animate-ping"></div>
                                    </div>
                                </div>
                                <!-- Tên vị trí đã được đưa xuống dưới -->
                                <div class="mt-2 text-center text-[8px] font-bold text-emerald-500 uppercase tracking-widest">
                                    {{ player.position }}
                                </div>
                            </div>

                            <!-- Market Info -->
                            <div class="flex flex-col items-center lg:items-end justify-center">
                                <div class="text-gray-400 uppercase text-[9px] font-bold tracking-widest mb-3">Giá trị ước tính</div>
                                <div class="text-4xl md:text-5xl font-bold tracking-tighter text-gray-900 dark:text-white mb-1">€33.7M</div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Hết hạn: 30.06.2028</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Main ───────────────────────────── -->
            <div class="max-w-6xl mx-auto px-4 sm:px-6 -mt-8 relative z-20">
                <div class="space-y-8">
                        <!-- Tabs -->
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-1.5 shadow-xl flex gap-1">
                            <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
                                :class="['flex-1 py-2.5 px-4 rounded-xl text-xs font-bold uppercase tracking-widest transition-all', activeTab === tab.id ? 'bg-emerald-500 text-white shadow-md' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700']">
                                {{ tab.label }}
                            </button>
                        </div>

                        <!-- TAB: SUMMARY -->
                        <div v-if="activeTab === 'summary'" class="space-y-8">

                            <!-- Match History Table (SofaScore Style - Fixed Alignment) -->
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-3xl overflow-hidden shadow-sm">
                                <div class="px-6 py-4 border-b border-gray-50 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/20">
                                    <h2 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Trận đấu gần đây</h2>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left table-fixed min-w-[600px]">
                                        <thead>
                                            <tr class="border-b border-gray-50 dark:border-gray-700">
                                                <th class="w-24 px-6 py-3 text-[9px] font-bold text-gray-400 uppercase tracking-widest text-center">Ngày</th>
                                                <th class="px-2 py-3 text-[9px] font-bold text-gray-400 uppercase tracking-widest">Trận đấu</th>
                                                <th class="w-28 px-2 py-3 text-[9px] font-bold text-gray-400 uppercase tracking-widest text-center">Rating</th>
                                                <th class="w-12 px-2 py-3 text-center" title="Phút thi đấu">
                                                    <svg class="w-3.5 h-3.5 text-gray-400 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                </th>
                                                <th class="w-10 px-2 py-3 text-center" title="Bàn thắng">
                                                    <svg class="w-3.5 h-3.5 text-gray-400 mx-auto" viewBox="0 0 24 24" fill="currentColor"><path d="M12,2A10,10,0,1,0,22,12,10.011,10.011,0,0,0,12,2Zm0,18a8,8,0,1,1,8-8A8.009,8.009,0,0,1,12,20Z"/><path d="M12,6a6,6,0,1,0,6,6A6.007,6.007,0,0,0,12,6Zm0,10a4,4,0,1,1,4-4A4,4,0,0,1,12,16Z"/></svg>
                                                </th>
                                                <th class="w-10 px-2 py-3 text-[10px] font-bold text-gray-400 text-center uppercase" title="Kiến tạo">A</th>
                                                <th class="w-10 px-2 py-3 text-center" title="Thẻ vàng">
                                                    <div class="w-2.5 h-3.5 bg-yellow-400 rounded-sm mx-auto shadow-sm"></div>
                                                </th>
                                                <th class="w-10 px-2 py-3 text-center" title="Thẻ đỏ">
                                                    <div class="w-2.5 h-3.5 bg-red-500 rounded-sm mx-auto shadow-sm"></div>
                                                </th>
                                                <th class="w-14 px-4 py-3 text-center text-[9px] font-bold text-gray-400 uppercase tracking-widest pr-6" title="Kết quả">KQ</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                            <tr v-for="stat in matchHistory.slice(0, 10)" :key="stat.id" class="group hover:bg-emerald-50/30 dark:hover:bg-emerald-500/5 cursor-pointer transition-colors" @click="router.visit(`/matches/${stat.match_id}`)">
                                                <!-- Date -->
                                                <td class="px-6 py-4 text-center align-middle">
                                                    <div class="flex flex-col items-center justify-center h-full">
                                                        <span class="text-[10px] font-bold text-gray-400 tabular-nums">{{ dayjs(stat.match?.match_at).format('DD.MM.YY') }}</span>
                                                    </div>
                                                </td>
                                                <!-- Teams -->
                                                <td class="px-2 py-4">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex flex-col gap-1.5 overflow-hidden">
                                                            <div class="flex items-center gap-2">
                                                                <img :src="stat.match?.home_team?.logo" class="w-4 h-4 object-contain shrink-0" />
                                                                <span :class="['text-[11px] font-bold truncate', stat.match?.home_team_id === stat.team_id ? 'text-gray-900 dark:text-white' : 'text-gray-400']">{{ stat.match?.home_team?.name }}</span>
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                <img :src="stat.match?.away_team?.logo" class="w-4 h-4 object-contain shrink-0" />
                                                                <span :class="['text-[11px] font-bold truncate', stat.match?.away_team_id === stat.team_id ? 'text-gray-900 dark:text-white' : 'text-gray-400']">{{ stat.match?.away_team?.name }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="flex flex-col items-center gap-1.5 text-[11px] font-bold tabular-nums pr-4 text-gray-900 dark:text-white">
                                                            <span>{{ stat.match?.home_score }}</span>
                                                            <span>{{ stat.match?.away_score }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <!-- Rating -->
                                                <td class="px-2 py-4">
                                                    <div class="flex justify-center">
                                                        <div :class="[getRatingColor(stat.detailed_stats?.games?.rating), 'px-2 py-1 rounded-lg text-[11px] font-bold text-white flex items-center gap-1 shadow-sm min-w-[38px] justify-center']">
                                                            {{ stat.detailed_stats?.games?.rating ? parseFloat(stat.detailed_stats.games.rating).toFixed(1) : '-' }}
                                                            <span v-if="parseFloat(stat.detailed_stats?.games?.rating) >= 8" class="text-[8px]">✦</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <!-- Min -->
                                                <td class="px-2 py-4 text-center">
                                                    <span class="text-[10px] font-bold text-gray-500 tabular-nums">{{ stat.detailed_stats?.games?.minutes }}'</span>
                                                </td>
                                                <!-- G -->
                                                <td class="px-2 py-4 text-center">
                                                    <span class="text-[11px] font-bold" :class="stat.detailed_stats?.goals?.total > 0 ? 'text-emerald-500' : 'text-gray-300 dark:text-gray-600'">{{ stat.detailed_stats?.goals?.total || 0 }}</span>
                                                </td>
                                                <!-- A -->
                                                <td class="px-2 py-4 text-center">
                                                    <span class="text-[11px] font-bold" :class="stat.detailed_stats?.goals?.assists > 0 ? 'text-emerald-500' : 'text-gray-300 dark:text-gray-600'">{{ stat.detailed_stats?.goals?.assists || 0 }}</span>
                                                </td>
                                                <!-- YC -->
                                                <td class="px-2 py-4 text-center">
                                                    <span class="text-[11px] font-bold" :class="stat.detailed_stats?.cards?.yellow > 0 ? 'text-yellow-500' : 'text-gray-300 dark:text-gray-600'">{{ stat.detailed_stats?.cards?.yellow || 0 }}</span>
                                                </td>
                                                <!-- RC -->
                                                <td class="px-2 py-4 text-center">
                                                    <span class="text-[11px] font-bold" :class="stat.detailed_stats?.cards?.red > 0 ? 'text-red-500' : 'text-gray-300 dark:text-gray-600'">{{ stat.detailed_stats?.cards?.red || 0 }}</span>
                                                </td>
                                                <!-- Result -->
                                                <td class="px-4 py-4 pr-6">
                                                    <div :class="[matchResult(stat).cls, 'w-6 h-6 rounded-lg flex items-center justify-center text-[10px] font-black shadow-sm mx-auto']">
                                                        {{ matchResult(stat).label }}
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="p-4 border-t border-gray-50 dark:border-gray-700 text-center">
                                    <button class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest hover:underline flex items-center justify-center gap-2 mx-auto">
                                        Xem thêm trận đấu
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- TAB: TRANSFERS -->
                        <div v-if="activeTab === 'transfers'" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-3xl p-8">
                            <h2 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-6">Lịch sử chuyển nhượng</h2>
                            <div class="space-y-4">
                                <div v-for="(t, i) in player.transfers" :key="i" class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-700">
                                    <div class="flex items-center gap-4">
                                        <div class="text-[10px] font-bold text-gray-400">{{ dayjs(t.date).format('YYYY') }}</div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold">{{ t.teams?.out?.name }}</span>
                                        </div>
                                        <span class="text-gray-300 opacity-50">→</span>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-emerald-500">{{ t.teams?.in?.name }}</span>
                                        </div>
                                    </div>
                                    <div class="text-[9px] font-bold px-2 py-1 bg-white dark:bg-gray-800 rounded border border-gray-100 dark:border-gray-700 uppercase tracking-widest">{{ t.type }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB: CAREER -->
                        <div v-if="activeTab === 'career'" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-3xl overflow-hidden shadow-sm">
                             <table class="w-full text-left">
                                <thead class="bg-gray-50 dark:bg-gray-900/50">
                                    <tr>
                                        <th class="px-8 py-3.5 text-[9px] font-bold text-gray-400 uppercase tracking-widest">Mùa giải</th>
                                        <th class="px-8 py-3.5 text-[9px] font-bold text-gray-400 uppercase tracking-widest">CLB</th>
                                        <th class="px-4 py-3.5 text-[9px] font-bold text-gray-400 uppercase tracking-widest text-center">Trận</th>
                                        <th class="px-4 py-3.5 text-[9px] font-bold text-gray-400 uppercase tracking-widest text-center">G</th>
                                        <th class="px-4 py-3.5 text-[9px] font-bold text-gray-400 uppercase tracking-widest text-center">A</th>
                                        <th class="px-4 py-3.5 text-[9px] font-bold text-gray-400 uppercase tracking-widest text-center">Rating</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                    <tr v-for="stat in seasonStats" :key="stat.id" class="hover:bg-emerald-50/30 dark:hover:bg-emerald-500/5">
                                        <td class="px-8 py-4 text-xs font-bold tabular-nums">{{ stat.season }}</td>
                                        <td class="px-8 py-4">
                                            <div class="flex flex-col">
                                                <span class="text-xs font-bold">{{ stat.team?.name }}</span>
                                                <span class="text-[9px] font-bold text-gray-400 uppercase">{{ stat.league?.name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-center text-xs font-bold">{{ stat.games }}</td>
                                        <td class="px-4 py-4 text-center text-xs font-bold text-emerald-500">{{ stat.goals }}</td>
                                        <td class="px-4 py-4 text-center text-xs font-bold text-emerald-500">{{ stat.assists }}</td>
                                        <td class="px-4 py-4 text-center">
                                            <span v-if="stat.detailed_stats?.games?.rating" :class="[getRatingColor(stat.detailed_stats.games.rating), 'px-2 py-0.5 rounded text-[10px] font-bold text-white']">
                                                {{ parseFloat(stat.detailed_stats.games.rating).toFixed(1) }}
                                            </span>
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
.overflow-x-auto::-webkit-scrollbar {
    height: 4px;
}
.overflow-x-auto::-webkit-scrollbar-track {
    background: transparent;
}
.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.dark .overflow-x-auto::-webkit-scrollbar-thumb {
    background: #334155;
}
</style>

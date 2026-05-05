<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import MainLayout from '../../Layouts/MainLayout.vue';
import TransferValueChart from '../../Components/TransferValueChart.vue';
import dayjs from 'dayjs';

const props = defineProps({
    player:       { type: Object, required: true },
    seasonStats:  { type: Array, default: () => [] },
    latestStat:   { type: Object, default: null },
    matchHistory: { type: Array, default: () => [] },
    careerTotals: { type: Object, default: () => ({}) },
    availableSeasons: { type: Array, default: () => [] },
    currentSeason: { type: Number, default: 2024 },
});

// ── Active tab ─────────────────────────────
const activeTab = ref(new URLSearchParams(window.location.search).get('tab') || 'summary');
const tabs = [
    { id: 'summary',   label: 'Tổng quan' },
    { id: 'career',    label: 'Sự nghiệp' },
    { id: 'transfers', label: 'Chuyển nhượng' },
    { id: 'trophies',  label: 'Danh hiệu' },
    { id: 'injuries',  label: 'Chấn thương' },
];

const switchTab = (tabId) => {
    activeTab.value = tabId;
    // Update URL without full page reload
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tabId);
    window.history.replaceState({}, '', url);
};

// ── Helpers ────────────────────────────────
const translateCountry = (country) => {
    if (!country) return '—';
    const map = {
        'England': 'Anh',
        'Germany': 'Đức',
        'Norway': 'Na Uy',
        'Italy': 'Ý',
        'France': 'Pháp',
        'Spain': 'Tây Ban Nha',
        'World': 'Thế giới',
        'Europe': 'Châu Âu',
        'Austria': 'Áo',
        'Belgium': 'Bỉ',
        'Portugal': 'Bồ Đào Nha',
        'Netherlands': 'Hà Lan',
        'Brazil': 'Brazil',
        'Argentina': 'Argentina'
    };
    return map[country] || country;
};

const getCountryFlag = (country) => {
    if (!country) return null;
    const map = {
        'England': 'gb',
        'Germany': 'de',
        'Norway': 'no',
        'Italy': 'it',
        'France': 'fr',
        'Spain': 'es',
        'Austria': 'at',
        'Belgium': 'be',
        'Portugal': 'pt',
        'Netherlands': 'nl',
        'Brazil': 'br',
        'Argentina': 'ar',
        'World': 'un', // United Nations as proxy or global
        'Europe': 'eu'
    };
    const code = map[country];
    if (!code) return null;
    return `https://flagcdn.com/w40/${code.toLowerCase()}.png`;
};

const translatePlace = (place) => {
    if (!place) return '—';
    const p = place.toLowerCase();
    if (p.includes('winner')) return 'Vô địch';
    if (p.includes('2nd place') || p.includes('runner-up')) return 'Á quân';
    if (p.includes('3rd place')) return 'Hạng 3';
    return place;
};

const translateInjury = (type) => {
    if (!type) return '—';
    const map = {
        'Ankle Injury': 'Chấn thương mắt cá',
        'Knee Injury': 'Chấn thương đầu gối',
        'Muscle Injury': 'Chấn thương cơ',
        'Foot Injury': 'Chấn thương bàn chân',
        'Groin Injury': 'Chấn thương vùng bẹn',
        'Knock': 'Va chạm / Đau nhẹ',
        'Illness': 'Ốm / Bệnh',
        'Hip/Thigh Injury': 'Chấn thương Hông/Đùi',
        'Thigh Injury': 'Chấn thương đùi',
        'Hamstring': 'Chấn thương gân kheo',
        'Calf Injury': 'Chấn thương bắp chân',
        'Back Injury': 'Chấn thương lưng'
    };
    return map[type] || type;
};

const formatSeason = (s, country = null) => {
    if (!s) return '—';
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

const formatDate = (d) => d ? dayjs(d).format('DD.MM.YYYY') : '—';

const getRatingColor = (rating) => {
    const r = parseFloat(rating);
    if (!r) return 'bg-gray-500';
    if (r >= 8.0) return 'bg-purple-500';
    if (r >= 7.5) return 'bg-emerald-500';
    if (r >= 7.0) return 'bg-green-500';
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

const getAgeAtSeason = (season) => {
    if (!props.player.birth_date && !props.player.birth_year) return '—';
    const birthYear = props.player.birth_date ? dayjs(props.player.birth_date).year() : props.player.birth_year;
    return season - birthYear;
};

const getCountryCode = (name) => {
    if (!name) return 'un';
    const countries = {
        'England': 'gb-eng', 'France': 'fr', 'Brazil': 'br', 'Germany': 'de', 'Spain': 'es', 'Portugal': 'pt',
        'Argentina': 'ar', 'Italy': 'it', 'Netherlands': 'nl', 'Belgium': 'be', 'Norway': 'no', 'Sweden': 'se',
        'Denmark': 'dk', 'USA': 'us', 'Croatia': 'hr', 'Scotland': 'gb-sct', 'Wales': 'gb-wls', 'Japan': 'jp',
        'South Korea': 'kr', 'Egypt': 'eg', 'Senegal': 'sn', 'Morocco': 'ma'
    };
    return countries[name] || name.substring(0, 2).toLowerCase();
};

// ── Active Season Logic ────────────────────
const availableSeasons = computed(() => {
    // Prefer seasons from prop if provided (may include seasons without stats)
    if (props.availableSeasons && props.availableSeasons.length) {
        return [...new Set(props.availableSeasons)].sort((a, b) => b - a);
    }
    const seasons = props.seasonStats.map(s => s.season);
    return [...new Set(seasons)].sort((a, b) => b - a);
});

const activeSeason = ref(props.currentSeason);
const isSeasonOpen = ref(false);

const changeSeason = (s) => {
    isSeasonOpen.value = false;
    // Use direct path to ensure it works even if route() helper is missing
    router.visit(`/players/${props.player.id}?season=${s}`, {
        preserveState: false,
        preserveScroll: true
    });
};

const handleBack = () => {
    window.history.back();
};

const getCountryForSeason = (s) => {
    const stat = props.seasonStats.find(st => st.season === s);
    return stat?.league?.country_name || props.player.nationality || null;
};

const currentStat = computed(() => {
    return props.seasonStats.find(s => s.season === activeSeason.value);
});

const isSeasonDataMissing = computed(() => {
    return activeSeason.value && !currentStat.value;
});

const displayStat = computed(() => {
    return currentStat.value;
});

// Grouped Trophies by Team (Reference Style)
const trophiesByTeam = computed(() => {
    const trophies = props.player?.trophies;
    if (!Array.isArray(trophies) || trophies.length === 0) return [];
    
    // Create a map of season -> team info from seasonStats
    const seasonMap = {};
    const stats = Array.isArray(props.seasonStats) ? props.seasonStats : [];
    stats.forEach(s => {
        if (s && s.season && !seasonMap[s.season]) {
            seasonMap[s.season] = {
                id: s.team_id,
                name: s.detailed_stats?.team?.name || "Unknown",
                logo: s.detailed_stats?.team?.logo || null
            };
        }
    });

    const getTeamForSeason = (seasonStr) => {
        if (!seasonStr) return { name: "Other", logo: null };
        const year = parseInt(seasonStr);
        if (seasonMap[year]) return seasonMap[year];
        if (seasonMap[seasonStr]) return seasonMap[seasonStr];
        return { name: "Other", logo: null };
    };

    const teamGroups = {};
    
    trophies.forEach(t => {
        if (!t || !t.place || !t.place.toLowerCase().includes('winner')) return;
        
        const team = getTeamForSeason(t.season);
        const teamKey = team.name || "Other";
        
        if (!teamGroups[teamKey]) {
            teamGroups[teamKey] = {
                teamName: teamKey,
                teamLogo: team.logo,
                competitions: {}
            };
        }
        
        const compKey = t.league || "Unknown Competition";
        if (!teamGroups[teamKey].competitions[compKey]) {
            teamGroups[teamKey].competitions[compKey] = {
                name: compKey,
                seasons: []
            };
        }
        if (t.season) teamGroups[teamKey].competitions[compKey].seasons.push(t.season);
    });

    return Object.values(teamGroups).map(group => ({
        ...group,
        competitions: Object.values(group.competitions).map(c => ({
            ...c,
            count: c.seasons.length,
            seasonsDisplay: c.seasons.map(s => {
                if (typeof s !== 'string') return s;
                if (s.includes('/')) {
                    return s.split('/').map(v => v.slice(-2)).join('/');
                }
                return s.slice(-2);
            }).join(', ')
        }))
    })).sort((a, b) => {
        const currentTeamName = props.player?.current_team?.name;
        if (currentTeamName && a.teamName === currentTeamName) return -1;
        return 0;
    });
});

// Advanced Stats Breakdown
const advancedStats = computed(() => {
    const statToUse = displayStat.value;
    if (!statToUse?.detailed_stats) return null;
    const s = statToUse.detailed_stats;
    const games = statToUse.games || 1;
    return {
        rating: s.games?.rating ?? '—',
        groups: [
            {
                label: 'Dứt điểm (Shots)',
                icon: 'M4 19h16l-1-7h-6l-2 3h-4l-3 4zM15 12l1-4h3l1 4',
                color: 'text-emerald-500',
                stats: [
                    { label: 'Tổng cú sút', val: s.shots?.total ?? 0 },
                    { label: 'Sút trúng đích', val: s.shots?.on ?? 0 },
                    { label: 'Tỉ lệ chính xác', val: s.shots?.total ? Math.round((s.shots.on / s.shots.total) * 100) + '%' : '0%' }
                ]
            },
            {
                label: 'Bàn thắng (Goals)',
                icon: 'M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10zm0-2a8 8 0 100-16 8 8 0 000 16zM12 14l-4-4 4-4 4 4-4 4z',
                color: 'text-blue-600',
                stats: [
                    { label: 'Bàn thắng', val: s.goals?.total ?? 0 },
                    { label: 'Kiến tạo', val: s.goals?.assists ?? 0 },
                    { label: 'Bàn thua (GK)', val: s.goals?.conceded ?? 0 },
                    { label: 'Cứu thua (GK)', val: s.goals?.saves ?? 0 }
                ]
            },
            {
                label: 'Chuyền bóng (Passes)',
                icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
                color: 'text-indigo-500',
                stats: [
                    { label: 'Tổng đường chuyền', val: s.passes?.total ?? 0 },
                    { label: 'Đường chuyền dọn cỗ', val: s.passes?.key ?? 0 },
                    { label: 'Độ chính xác', val: (s.passes?.accuracy ?? 0) + '%' }
                ]
            },
            {
                label: 'Tắc bóng (Tackles)',
                icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                color: 'text-rose-500',
                stats: [
                    { label: 'Tắc bóng', val: s.tackles?.total ?? 0 },
                    { label: 'Đánh chặn', val: s.tackles?.interceptions ?? 0 },
                    { label: 'Cản cú sút', val: s.tackles?.blocks ?? 0 }
                ]
            },
            {
                label: 'Tranh chấp (Duels)',
                icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                color: 'text-amber-600',
                stats: [
                    { label: 'Tổng tranh chấp', val: s.duels?.total ?? 0 },
                    { label: 'Thắng tranh chấp', val: s.duels?.won ?? 0 },
                    { label: 'Tỉ lệ thắng', val: s.duels?.total ? Math.round((s.duels.won / s.duels.total) * 100) + '%' : '0%' }
                ]
            },
            {
                label: 'Rê bóng (Dribbles)',
                icon: 'M3 12c3-3 6 3 9 0s6-3 9 0M17 9l3 3-3 3',
                color: 'text-fuchsia-500',
                stats: [
                    { label: 'Số lần rê bóng', val: s.dribbles?.attempts ?? 0 },
                    { label: 'Rê bóng thành công', val: s.dribbles?.success ?? 0 },
                    { label: 'Bị vượt qua', val: s.dribbles?.past ?? 0 }
                ]
            },
            {
                label: 'Phạm lỗi (Fouls)',
                icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                color: 'text-orange-500',
                stats: [
                    { label: 'Bị phạm lỗi', val: s.fouls?.drawn ?? 0 },
                    { label: 'Phạm lỗi', val: s.fouls?.committed ?? 0 }
                ]
            },
            {
                label: 'Thẻ phạt (Cards)',
                icon: 'M5 4h5v7H5z M10 8h5v7h-5z',
                color: 'text-red-500',
                stats: [
                    { label: 'Thẻ vàng', val: s.cards?.yellow ?? 0 },
                    { label: 'Thẻ vàng 2', val: s.cards?.yellowred ?? 0 },
                    { label: 'Thẻ đỏ', val: s.cards?.red ?? 0 }
                ]
            },
            {
                label: 'Phạt đền (Penalty)',
                icon: 'M18 10h3v4h-3M5 6h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2zM11 6v4',
                color: 'text-emerald-600',
                stats: [
                    { label: 'Kiếm được', val: s.penalty?.won ?? 0 },
                    { label: 'Ghi bàn', val: s.penalty?.scored ?? 0 },
                    { label: 'Sút hỏng', val: s.penalty?.missed ?? 0 },
                    { label: 'Phạm lỗi penalty', val: s.penalty?.commited ?? 0 }
                ]
            },
            {
                label: 'Thay người (Subs)',
                icon: 'M7 16V4m0 12l-3-3m3 3l3-3M17 8V20m0-12l-3 3m3-3l3 3',
                color: 'text-gray-500',
                stats: [
                    { label: 'Vào sân', val: s.substitutes?.in ?? 0 },
                    { label: 'Rời sân', val: s.substitutes?.out ?? 0 },
                    { label: 'Trên ghế dự bị', val: s.substitutes?.bench ?? 0 }
                ]
            }
        ]
    };
});

// ── Radar Chart Logic (SVG) ────────────────
const isGoalkeeper = computed(() => props.player.position?.toLowerCase().includes('goalkeeper'));

const radarPoints = computed(() => {
    const statToUse = displayStat.value;
    if (!statToUse?.detailed_stats) return [];
    const s = statToUse.detailed_stats;
    const minutes = s.games?.minutes || 1;
    
    // Per 90 Helper
    const per90 = (val) => (val / minutes) * 90;
    
    // Percentile Helper: Maps a Per 90 value to a 0-100 score based on elite benchmarks
    const getPercentile = (val, elite) => Math.min(Math.max((val / elite) * 100, 5), 100);

    let values = [];
    if (isGoalkeeper.value) {
        values = [
            getPercentile(per90(s.goals?.saves || 0), 5.0),    // Saves/90 (Elite: 5)
            getPercentile(s.passes?.accuracy || 0, 90),       // Accuracy (Elite: 90%)
            getPercentile(per90(s.tackles?.interceptions || 0), 1.5), // Int/90
            getPercentile(per90(s.duels?.total || 0), 2.0),   // Duels/90
            getPercentile(100 - (s.goals?.conceded || 0) * 10, 100), // Conceded (Inverted)
            getPercentile(props.careerTotals?.clean_sheets || 0, 15)
        ];
    } else {
        // Elite Benchmarks (Per 90)
        const benchmarks = {
            forward: { goals: 0.7, shots: 3.5, passes: 85, key: 2.5, duels: 10, defense: 4 },
            midfielder: { goals: 0.3, shots: 2.0, passes: 92, key: 3.0, duels: 12, defense: 6 },
            defender: { goals: 0.1, shots: 1.0, passes: 88, key: 1.0, duels: 15, defense: 8 }
        };
        
        const pos = props.player.position?.toLowerCase() || 'midfielder';
        const b = pos.includes('forward') || pos.includes('attacker') ? benchmarks.forward : 
                  pos.includes('defender') ? benchmarks.defender : benchmarks.midfielder;

        values = [
            getPercentile(per90(s.goals?.total || 0), b.goals),
            getPercentile(per90(s.shots?.total || 0), b.shots),
            getPercentile(s.passes?.accuracy || 0, b.passes),
            getPercentile(per90(s.passes?.key || 0), b.key),
            getPercentile(per90(s.duels?.won || 0), b.duels),
            getPercentile(per90(s.tackles?.total || 0), b.defense)
        ];
    }

    const center = 90;
    const maxR = 60;
    
    return values.map((v, i) => {
        const angle = (Math.PI * 2 * i) / 6 - Math.PI / 2;
        const r = (v / 100) * maxR;
        return {
            x: center + r * Math.cos(angle),
            y: center + r * Math.sin(angle)
        };
    });
});

const radarLabels = computed(() => {
    const statToUse = displayStat.value;
    if (!statToUse?.detailed_stats) return [];
    const s = statToUse.detailed_stats;
    const minutes = s.games?.minutes || 1;
    const p90 = (val) => ((val / minutes) * 90).toFixed(2);

    if (isGoalkeeper.value) {
        return [
            { name: 'Cứu thua/90', val: p90(s.goals?.saves || 0) },
            { name: 'Chuyền %', val: (s.passes?.accuracy || 0) + '%' },
            { name: 'Cắt bóng/90', val: p90(s.tackles?.interceptions || 0) },
            { name: 'Tranh chấp/90', val: p90(s.duels?.total || 0) },
            { name: 'Bàn thua', val: s.goals?.conceded || 0 },
            { name: 'Sạch lưới', val: props.careerTotals?.clean_sheets || 0 }
        ];
    }
    return [
        { name: 'Bàn thắng/90', val: p90(s.goals?.total || 0) },
        { name: 'Dứt điểm/90', val: p90(s.shots?.total || 0) },
        { name: 'Chuyền %', val: (s.passes?.accuracy || 0) + '%' },
        { name: 'Cơ hội/90', val: p90(s.passes?.key || 0) },
        { name: 'Tranh chấp/90', val: p90(s.duels?.won || 0) },
        { name: 'Phòng ngự/90', val: p90(s.tackles?.total || 0) }
    ];
});

const getLabelPosition = (i) => {
    const angle = (Math.PI * 2 * i) / 6 - Math.PI / 2;
    const r = 92; // Increased distance to give more room
    const x = 50 + (r / 200 * 100) * Math.cos(angle);
    const y = 50 + (r / 200 * 100) * Math.sin(angle);
    
    return {
        left: `${x}%`,
        top: `${y}%`,
        transform: 'translate(-50%, -50%)',
        width: '80px', // Fixed width to prevent wrapping issues
        textAlign: 'center'
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
        <div class="pt-2 pb-12 relative min-h-screen">
            <!-- AI Background Blobs -->
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-emerald-500/5 dark:bg-emerald-500/5 rounded-full blur-[100px] -z-10 animate-pulse"></div>
            
            <div class="flex flex-col lg:flex-row gap-6 max-w-7xl mx-auto px-4 sm:px-6">
                <!-- MAIN CONTENT -->
                <main class="flex-1 min-w-0 space-y-6">
                    <!-- Back Button / Breadcrumb -->
                    <button
                        @click="handleBack"
                        class="inline-flex items-center gap-2 text-[11px] font-bold text-gray-400 hover:text-emerald-500 transition-colors group uppercase tracking-widest outline-none"
                    >
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                        </svg>
                        Quay lại
                    </button>

                    <!-- Player Header (Consistent with Leagues/Games) -->
                    <div class="bg-white dark:bg-gray-800/80 rounded-2xl border border-gray-100 dark:border-gray-700/50 p-6 shadow-sm overflow-hidden relative">
                        <div class="flex flex-col md:flex-row items-center gap-6 relative z-10">
                            <!-- Photo -->
                            <div class="relative w-24 h-24 md:w-28 md:h-28 shrink-0 mb-2 md:mb-0">
                                <div class="w-full h-full rounded-2xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-900 flex items-center justify-center p-1.5 shadow-sm">
                                    <div class="w-full h-full rounded-xl overflow-hidden bg-gray-50 dark:bg-gray-800">
                                        <img v-if="player.photo" :src="player.photo" class="w-full h-full object-cover" />
                                        <div v-else class="w-full h-full flex items-center justify-center text-4xl font-bold text-gray-200">
                                            {{ player.name.charAt(0) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-8 h-8 bg-white dark:bg-gray-800 rounded-full border border-gray-100 dark:border-gray-700 flex items-center justify-center shadow-md">
                                    <img v-if="displayStat?.team?.logo || player.team?.logo" :src="displayStat?.team?.logo || player.team?.logo" class="w-5 h-5 object-contain" />
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="flex-1 text-center md:text-left flex flex-col justify-center gap-2">
                                <div class="flex flex-wrap items-center justify-center md:justify-start gap-2.5">
                                    <div v-if="player.nationality" class="flex items-center gap-1.5 px-2.5 py-1 bg-gray-50 dark:bg-gray-800/50 rounded-full text-[9px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400 border border-gray-100 dark:border-gray-700">
                                        <img :src="`https://flagcdn.com/w40/${getCountryCode(player.nationality)}.png`" class="w-3 h-2.5 object-cover rounded-[2px]" />
                                        {{ player.nationality }}
                                    </div>
                                    <div class="px-2.5 py-1 bg-emerald-500/10 text-emerald-500 rounded-full text-[9px] font-bold uppercase tracking-widest border border-emerald-500/10">
                                        {{ displayStat?.detailed_stats?.games?.position || player.position || '—' }}
                                    </div>
                                    <div class="px-2.5 py-1 bg-gray-50 dark:bg-gray-800/50 rounded-full text-[9px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400 border border-gray-100 dark:border-gray-700">
                                        #{{ displayStat?.detailed_stats?.games?.number || player.number || '—' }}
                                    </div>
                                </div>
                                
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2 mb-0.5 justify-center md:justify-start">
                                        <span class="text-[9px] font-bold text-emerald-500 uppercase tracking-widest">{{ displayStat?.team?.name || player.team?.name || 'Tự do' }}</span>
                                    </div>
                                    <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">{{ player.name }}</h1>
                                </div>
                                
                                <div class="flex items-center justify-center md:justify-start gap-5 mt-1">
                                    <div class="flex flex-col text-left">
                                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Tuổi ({{ displayStat?.season || activeSeason }})</span>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ getAgeAtSeason(displayStat?.season || activeSeason) }}</span>
                                    </div>
                                    <div class="w-px h-6 bg-gray-100 dark:bg-gray-800"></div>
                                    <div class="flex flex-col text-left">
                                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Chiều cao</span>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ player.height || '—' }}</span>
                                    </div>
                                    <div class="w-px h-6 bg-gray-100 dark:bg-gray-800"></div>
                                    <div class="flex flex-col text-left">
                                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Cân nặng</span>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ player.weight || '—' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Current Season Stats -->
                            <div class="shrink-0 w-full md:w-64 bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700 hidden md:block">
                                <h3 class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-3">Mùa giải đang xem: {{ displayStat?.season }}</h3>
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex flex-col">
                                        <span class="text-3xl font-extrabold" :class="advancedStats?.rating >= 7.0 ? 'text-emerald-500' : 'text-gray-900 dark:text-white'">
                                            {{ advancedStats?.rating ? parseFloat(advancedStats.rating).toFixed(1) : '-' }}
                                        </span>
                                        <span class="text-[8px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">Đánh giá TB</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xl font-bold text-gray-900 dark:text-white">{{ currentStat?.games || 0 }}</span>
                                        <p class="text-[8px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">Trận đấu</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4 border-t border-gray-200 dark:border-gray-700 pt-3">
                                    <div>
                                        <div class="text-base font-bold text-gray-900 dark:text-white">{{ currentStat?.goals || 0 }}</div>
                                        <div class="text-[8px] font-bold text-gray-400 uppercase tracking-widest">Bàn thắng</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-base font-bold text-gray-900 dark:text-white">{{ currentStat?.assists || 0 }}</div>
                                        <div class="text-[8px] font-bold text-gray-400 uppercase tracking-widest">Kiến tạo</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Tabs & Season Dropdown -->
                    <div class="border-b border-gray-100 dark:border-gray-700 relative mb-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 sm:gap-8">
                            <!-- Tabs with local overflow -->
                            <div class="flex gap-4 sm:gap-8 overflow-x-auto no-scrollbar whitespace-nowrap">
                                <button v-for="tab in tabs" :key="tab.id"
                                        @click="switchTab(tab.id)"
                                        :class="activeTab === tab.id ? 'text-emerald-500 border-b-2 border-emerald-500' : 'text-gray-500 hover:text-gray-800 dark:hover:text-gray-200'"
                                        class="pb-4 px-1 text-[11px] font-bold uppercase tracking-widest transition-all whitespace-nowrap outline-none">
                                    {{ tab.label }}
                                </button>
                            </div>

                            <!-- Season Dropdown -->
                            <div class="relative season-dropdown pb-4 hidden sm:block" v-if="availableSeasons.length > 0">
                                <button @click="isSeasonOpen = !isSeasonOpen" 
                                        class="flex items-center gap-2 px-3 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-lg text-[9px] font-bold uppercase tracking-widest transition-all hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Mùa giải: {{ activeSeason }}
                                    <svg :class="['w-2.5 h-2.5 transition-transform', isSeasonOpen ? 'rotate-180' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                                <div v-show="isSeasonOpen" class="absolute top-full right-0 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl shadow-2xl py-2 z-50 max-h-64 overflow-y-auto no-scrollbar">
                                    <button v-for="s in availableSeasons" :key="s" @click="changeSeason(s)" class="w-full text-center px-4 py-2 text-[11px] font-bold hover:bg-gray-50 dark:hover:bg-gray-700" :class="s == activeSeason ? 'text-emerald-500' : 'text-gray-500'">{{ s }}</button>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- ── TAB: SUMMARY (OVERVIEW) ───────────────────────────── -->
                <div v-if="activeTab === 'summary'">
                    <!-- Alert: Season Data Missing -->
                    <div v-if="isSeasonDataMissing" class="mb-6 animate-in fade-in slide-in-from-top-4 duration-500">
                        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 rounded-2xl p-4 flex items-start gap-4 shadow-sm">
                            <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-bold text-amber-800 dark:text-amber-300">Dữ liệu mùa giải {{ activeSeason }} chưa có sẵn</h4>
                                <p class="text-xs text-amber-700/80 dark:text-amber-400/70 mt-1 leading-relaxed">
                                    Rất tiếc, hệ thống chưa có dữ liệu chi tiết cho mùa giải này.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="space-y-8">
                        <!-- 1. Detailed Stats Grid aligned with JSON Response -->
                        <div v-if="advancedStats?.groups" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                            <div v-for="(group, gIdx) in advancedStats.groups" :key="gIdx" 
                                 class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl p-5 shadow-sm transition-all hover:shadow-md flex flex-col">
                                <h4 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-4 flex items-center gap-2">
                                    {{ group.label }}
                                </h4>
                                <div class="space-y-3 mt-auto">
                                    <div v-for="(item, i) in group.stats" :key="i" class="flex justify-between items-center group/item">
                                        <span class="text-xs text-gray-500 dark:text-gray-400 font-medium group-hover/item:text-gray-700 dark:group-hover/item:text-gray-200 transition-colors">{{ item.label }}</span>
                                        <span class="text-xs font-bold text-gray-900 dark:text-white">{{ item.val }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Recent Matches -->
                        <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm">
                            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-900/30">
                                <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Trận đấu gần đây</h3>
                            </div>
                            <!-- ... existing table code ... -->
                            <div class="overflow-x-auto no-scrollbar">
                                <table class="w-full text-left min-w-[500px]">
                                    <thead>
                                        <tr class="border-b border-gray-100 dark:border-gray-700">
                                            <th class="px-5 py-3 text-[9px] font-bold text-gray-400 uppercase tracking-widest">Trận đấu</th>
                                            <th class="px-2 py-3 text-[9px] font-bold text-gray-400 uppercase tracking-widest text-center">KQ</th>
                                            <th class="px-2 py-3 text-[9px] font-bold text-gray-400 uppercase tracking-widest text-center">Min</th>
                                            <th class="px-2 py-3 text-[9px] font-bold text-gray-400 uppercase tracking-widest text-center">G</th>
                                            <th class="px-2 py-3 text-[9px] font-bold text-gray-400 uppercase tracking-widest text-center">A</th>
                                            <th class="px-5 py-3 text-[9px] font-bold text-gray-400 uppercase tracking-widest text-right">Rating</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        <tr v-for="stat in matchHistory.slice(0, 5)" :key="stat.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer transition-colors" @click="router.visit(`/matches/${stat.match_id}`)">
                                            <td class="px-5 py-3">
                                                <div class="flex items-center gap-3">
                                                    <span class="text-[9px] font-bold text-gray-400 w-10 shrink-0">{{ dayjs(stat.match?.match_at).format('DD.MM') }}</span>
                                                    <div class="flex items-center gap-2">
                                                        <span :class="['text-[11px] font-bold truncate w-24 text-right', stat.match?.home_team_id === stat.team_id ? 'text-gray-900 dark:text-white' : 'text-gray-500']">{{ stat.match?.home_team?.name }}</span>
                                                        <div class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 rounded text-[10px] font-black tracking-widest flex items-center justify-center text-gray-800 dark:text-gray-200">
                                                            {{ stat.match?.home_score }} - {{ stat.match?.away_score }}
                                                        </div>
                                                        <span :class="['text-[11px] font-bold truncate w-24', stat.match?.away_team_id === stat.team_id ? 'text-gray-900 dark:text-white' : 'text-gray-500']">{{ stat.match?.away_team?.name }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-2 py-3 text-center">
                                                <div :class="[matchResult(stat).cls, 'w-5 h-5 rounded flex items-center justify-center text-[9px] font-black shadow-sm mx-auto']">
                                                    {{ matchResult(stat).label }}
                                                </div>
                                            </td>
                                            <td class="px-2 py-3 text-center text-[11px] font-bold text-gray-500">{{ stat.detailed_stats?.games?.minutes }}'</td>
                                            <td class="px-2 py-3 text-center text-[11px] font-bold" :class="stat.detailed_stats?.goals?.total > 0 ? 'text-emerald-500' : 'text-gray-400'">{{ stat.detailed_stats?.goals?.total || 0 }}</td>
                                            <td class="px-2 py-3 text-center text-[11px] font-bold" :class="stat.detailed_stats?.goals?.assists > 0 ? 'text-emerald-500' : 'text-gray-400'">{{ stat.detailed_stats?.goals?.assists || 0 }}</td>
                                            <td class="px-5 py-3 text-right">
                                                <div class="flex justify-end">
                                                    <div :class="[getRatingColor(stat.detailed_stats?.games?.rating), 'px-2 py-1 rounded text-[11px] font-bold text-white shadow-sm min-w-[32px] text-center']">
                                                        {{ stat.detailed_stats?.games?.rating ? parseFloat(stat.detailed_stats.games.rating).toFixed(1) : '-' }}
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── TAB: CAREER ───────────────────────────── -->
                <div v-if="activeTab === 'career'">
                    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="text-left border-b border-gray-50 dark:border-gray-700/50 bg-gray-50/50 dark:bg-gray-900/30">
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Mùa giải / Giải đấu</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Câu lạc bộ</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Trận</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">G</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">A</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Rating</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                    <tr v-for="stat in seasonStats" :key="stat.id" 
                                        class="hover:bg-gray-50/50 dark:hover:bg-gray-700/10 transition-colors group">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <span class="text-xs font-black text-gray-900 dark:text-white tabular-nums">{{ formatSeason(stat.season, stat.league?.country_name) }}</span>
                                                <div class="flex items-center gap-2">
                                                    <img v-if="stat.league?.logo" :src="stat.league.logo" class="w-4 h-4 object-contain opacity-60" />
                                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest truncate max-w-[120px]">{{ stat.league?.name }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <img v-if="stat.team?.logo" :src="stat.team.logo" class="w-4 h-4 object-contain" />
                                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ stat.team?.name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center text-xs font-bold text-gray-500 tabular-nums">{{ stat.games }}</td>
                                        <td class="px-6 py-4 text-center text-xs font-black text-emerald-500 tabular-nums">{{ stat.goals }}</td>
                                        <td class="px-6 py-4 text-center text-xs font-black text-emerald-500 tabular-nums">{{ stat.assists }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <span v-if="stat.detailed_stats?.games?.rating" 
                                                  :class="[getRatingColor(stat.detailed_stats.games.rating), 'px-2 py-0.5 rounded text-[10px] font-black text-white shadow-sm inline-block min-w-[32px]']">
                                                {{ parseFloat(stat.detailed_stats.games.rating).toFixed(1) }}
                                            </span>
                                            <span v-else class="text-gray-300">—</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ── TAB: TRANSFERS ───────────────────────────── -->
                <div v-if="activeTab === 'transfers'">
                    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="text-left border-b border-gray-50 dark:border-gray-700/50 bg-gray-50/50 dark:bg-gray-900/30">
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Ngày</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Từ câu lạc bộ</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Đến câu lạc bộ</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Giá trị phí</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                    <tr v-for="(t, i) in player.transfers" :key="i" 
                                        class="hover:bg-gray-50/50 dark:hover:bg-gray-700/10 transition-colors group">
                                        <td class="px-6 py-4">
                                            <span class="text-xs font-black text-gray-900 dark:text-white tabular-nums">{{ dayjs(t.date).format('DD/MM/YYYY') }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <img v-if="t.teams?.out?.logo" :src="t.teams.out.logo" class="w-4 h-4 object-contain" />
                                                <span class="text-xs font-bold text-gray-500">{{ t.teams?.out?.name || '—' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <img v-if="t.teams?.in?.logo" :src="t.teams.in.logo" class="w-4 h-4 object-contain" />
                                                <span class="text-xs font-black text-emerald-600 dark:text-emerald-400">{{ t.teams?.in?.name || '—' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex flex-col items-end">
                                                <span class="text-xs font-black text-gray-900 dark:text-white">{{ t.fee }}</span>
                                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-tight">{{ 
                                                    t.type?.toLowerCase().includes('loan') ? 'Cho mượn' : 
                                                    t.type?.toLowerCase().includes('free') ? 'Tự do' : 
                                                    t.type?.toLowerCase().includes('n/a') ? '' : 
                                                    'Mua đứt' 
                                                }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div v-if="!player.transfers || player.transfers.length === 0" class="p-12 text-center text-sm font-medium text-gray-400">
                                Chưa có dữ liệu chuyển nhượng.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── TAB: TROPHIES ───────────────────────────── -->
                <div v-if="activeTab === 'trophies'">
                    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="text-left border-b border-gray-50 dark:border-gray-700/50 bg-gray-50/50 dark:bg-gray-900/30">
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Mùa giải</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Giải đấu</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Quốc gia</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Thứ hạng</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                    <tr v-for="(t, i) in [...(player.trophies || [])].sort((a,b) => String(b.season || '').localeCompare(String(a.season || '')))" :key="i" 
                                        class="hover:bg-gray-50/50 dark:hover:bg-gray-700/10 transition-colors group">
                                        <td class="px-6 py-4">
                                            <span class="text-xs font-black text-gray-900 dark:text-white">{{ t.season || '—' }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300 group-hover:text-emerald-500 transition-colors">{{ t.league }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <img v-if="getCountryFlag(t.country)" :src="getCountryFlag(t.country)" class="w-3 h-2.5 object-cover rounded-[1px] shadow-sm" :alt="t.country" />
                                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ translateCountry(t.country) }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span :class="[
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider',
                                                t.place?.toLowerCase().includes('winner')
                                                    ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400'
                                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'
                                            ]">
                                                {{ translatePlace(t.place) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div v-if="!Array.isArray(player.trophies) || player.trophies.length === 0" class="p-12 text-center text-sm font-medium text-gray-400">
                                Chưa có dữ liệu danh hiệu.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── TAB: INJURIES ───────────────────────────── -->
                <div v-if="activeTab === 'injuries'">
                    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="text-left border-b border-gray-50 dark:border-gray-700/50 bg-gray-50/50 dark:bg-gray-900/30">
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Mùa giải</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Loại chấn thương</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Thời gian</th>
                                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Tình trạng</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                    <tr v-for="(s, i) in [...(player.sidelined_history || [])].sort((a,b) => new Date(b.start) - new Date(a.start))" :key="i" 
                                        class="hover:bg-gray-50/50 dark:hover:bg-gray-700/10 transition-colors group">
                                        <td class="px-6 py-4">
                                            <span class="text-xs font-black text-gray-900 dark:text-white tabular-nums">{{ formatSeason(s.start) }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-2 h-2 rounded-full bg-rose-400 shadow-sm"></div>
                                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300 group-hover:text-rose-500 transition-colors">{{ translateInjury(s.type) }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ dayjs(s.start).format('DD/MM/YY') }} - {{ s.end ? dayjs(s.end).format('DD.MM.YY') : '?' }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span :class="[
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider',
                                                s.end ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400' : 'bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400'
                                            ]">
                                                {{ s.end ? 'Đã bình phục' : 'Đang điều trị' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div v-if="!player.sidelined_history || player.sidelined_history.length === 0" class="p-12 text-center text-sm font-medium text-gray-400">
                                Chưa có dữ liệu chấn thương.
                            </div>
                        </div>
                    </div>
                </div>
                </main>
            </div>
        </div>
    </MainLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>

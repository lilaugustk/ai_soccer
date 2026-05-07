<template>
    <Head title="FIFA World Cup 2026 - Lịch thi đấu & Kết quả" />
    <MainLayout>
        <div class="pt-2 pb-8 relative isolate">
            <!-- AI Background Blobs -->
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-emerald-500/10 dark:bg-emerald-500/5 rounded-full blur-[100px] -z-10 animate-pulse"></div>
            <div class="absolute top-1/2 -right-24 w-80 h-80 bg-blue-500/10 dark:bg-blue-500/5 rounded-full blur-[80px] -z-10"></div>



            <div class="flex flex-col lg:flex-row gap-6">
                <!-- Main Content -->
                <div class="flex-1 space-y-4 min-w-0">
                    <div class="flex flex-col gap-6 mb-2 relative z-[40]">
                        <!-- Reusable Page Header & Filter Component -->
                        <GamesFilter 
                            title="Lịch thi đấu & Kết quả"
                            :tabs="mainTabs"
                            v-model:activeTab="activeTab"
                            v-model:selectedDate="selectedDate"
                            v-model:isLeagueIndexVisible="isLeagueIndexVisible"
                        />
                    </div>

                    <!-- SUB-CONTENT AREA -->
                    <div class="min-h-[600px] animate-in fade-in duration-500">
                        <!-- MODE 1 & 2: DATE/GROUP (Blank for now) -->
                        <div v-if="activeTab === 'date' || activeTab === 'group'" class="min-h-[400px] flex items-center justify-center">
                            <div class="text-gray-300 dark:text-gray-700 font-bold uppercase tracking-[0.5em] text-[10px]">
                                Đang cập nhật nội dung...
                            </div>
                        </div>

                        <!-- MODE 3: KNOCKOUT BRACKET (VnExpress Style) -->
                        <div v-else-if="activeTab === 'knockout'" class="pb-20">
                            <div class="bg-white dark:bg-gray-800/50 rounded-[40px] border border-gray-100 dark:border-gray-800 shadow-2xl shadow-gray-200/50 dark:shadow-none overflow-hidden relative">
                                <!-- Navigation & Controls -->
                                <div class="relative pt-12 pb-6 px-12 border-b border-gray-50 dark:border-gray-800/50">
                                    <!-- Centered Nav -->
                                    <div class="flex justify-center gap-6 overflow-x-auto no-scrollbar">
                                        <button v-for="round in knockoutRounds" :key="round.id"
                                                @click="activeBracketRound = round.id"
                                                class="px-6 py-3 text-[12px] font-bold uppercase tracking-widest whitespace-nowrap transition-all relative group focus:outline-none"
                                                :class="activeBracketRound === round.id ? 'text-[#8b1d3d]' : 'text-gray-400 hover:text-gray-900 dark:hover:text-white'">
                                            {{ round.name }}
                                            <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#8b1d3d] transition-all duration-300"
                                                 :class="activeBracketRound === round.id ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0 group-hover:opacity-50 group-hover:scale-x-50'">
                                            </div>
                                        </button>
                                    </div>
                                               <!-- Bracket Content (Inner) -->
                                <div class="relative overflow-x-auto no-scrollbar" id="bracket-container">
                                    <div class="flex gap-8 min-w-max items-start justify-center p-4 w-full">
                                        <div v-for="round in visibleRounds" :key="'col-'+round.id" 
                                             class="flex flex-col gap-6 w-[280px] shrink-0 opacity-100">
                                            
                                            <div class="text-center border-b border-gray-100 dark:border-gray-800 pb-2 mb-2">
                                                <span class="text-[10px] font-bold text-[#8b1d3d] uppercase tracking-[0.2em]">{{ round.name }}</span>
                                            </div>

                                            <div class="flex flex-col gap-3">
                                                <div v-for="match in round.matches" :key="match.id" class="relative group/match z-10">
                                                    <div class="w-full bg-white dark:bg-gray-800 rounded-[20px] border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-all duration-300 group/card relative z-10 overflow-hidden">
                                                        <div class="px-3 py-1.5 bg-gray-50/30 dark:bg-gray-700/30 border-b border-gray-50 dark:border-gray-700 flex justify-between items-center">
                                                            <span class="text-[8px] font-bold text-[#8b1d3d] uppercase tracking-tighter">Trận {{ match.id }} • {{ match.time }} • {{ match.date }}</span>
                                                        </div>
                                                        <div class="p-3 space-y-2">
                                                            <div class="flex items-center justify-between gap-2">
                                                                <div class="flex items-center gap-2">
                                                                    <div class="w-5 h-5 rounded-full bg-gray-50 dark:bg-gray-700 border border-gray-100 dark:border-gray-600 flex items-center justify-center text-[10px] shadow-sm">🏳️</div>
                                                                    <span class="text-[11px] font-bold text-gray-800 dark:text-white uppercase tracking-tight">{{ match.home }}</span>
                                                                </div>
                                                                <span class="text-[10px] font-black text-gray-300">0</span>
                                                            </div>
                                                            <div class="flex items-center justify-between gap-2">
                                                                <div class="flex items-center gap-2">
                                                                    <div class="w-5 h-5 rounded-full bg-gray-50 dark:bg-gray-700 border border-gray-100 dark:border-gray-600 flex items-center justify-center text-[10px] shadow-sm">🏳️</div>
                                                                    <span class="text-[11px] font-bold text-gray-800 dark:text-white uppercase tracking-tight">{{ match.away }}</span>
                                                                </div>
                                                                <span class="text-[10px] font-black text-gray-300">0</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>

            <!-- Sidebar Content -->
            <LeagueSidebar />
        </div>
    </div>
</MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import LeagueSidebar from '@/Components/LeagueSidebar.vue';
import GamesFilter from '@/Components/GamesFilter.vue';
import dayjs from 'dayjs';
import axios from 'axios';

const props = defineProps({
    teams: [Array, Object],
    groups: [Array, Object],
    matches: [Array, Object],
    stadiums: [Array, Object],
    liveTest: Object
});

const mainTabs = [
    { id: 'date', name: 'Xem theo ngày' },
    { id: 'group', name: 'Xem theo bảng' },
    { id: 'knockout', name: 'Vòng loại trực tiếp' }
];

const activeTab = ref('date');
const activeBracketRound = ref('r32');
const selectedGroupId = ref('A');
const selectedDate = ref(dayjs().format('YYYY-MM-DD'));
const liveMatch = ref(props.liveTest);
const isLeagueIndexVisible = ref(true);

const knockoutRounds = [
    { id: 'r32', name: 'Vòng 32 đội', matches: [
        { id: 73, home: 'Nhì bảng A', away: 'Nhì bảng B', time: '02:00', date: '29/06' },
        { id: 74, home: 'Nhất bảng E', away: 'Hạng ba A/B/C/D/F', time: '03:30', date: '30/06' },
        { id: 75, home: 'Nhất bảng F', away: 'Nhì bảng C', time: '08:00', date: '30/06' },
        { id: 76, home: 'Nhất bảng C', away: 'Nhì bảng F', time: '00:00', date: '30/06' },
        { id: 77, home: 'Nhất bảng I', away: 'Hạng ba C/D/F/G/H', time: '04:00', date: '01/07' },
        { id: 78, home: 'Nhì bảng E', away: 'Nhì bảng I', time: '00:00', date: '01/07' },
        { id: 79, home: 'Nhất bảng A', away: 'Hạng ba C/E/F/H/I', time: '08:00', date: '01/07' },
        { id: 80, home: 'Nhất bảng L', away: 'Hạng ba E/H/I/J/K', time: '23:00', date: '01/07' },
        { id: 81, home: 'Nhất bảng D', away: 'Hạng ba B/E/F/I/J', time: '07:00', date: '02/07' },
        { id: 82, home: 'Nhất bảng G', away: 'Hạng ba A/E/H/I/J', time: '03:00', date: '02/07' },
        { id: 83, home: 'Nhì bảng K', away: 'Nhì bảng L', time: '06:00', date: '03/07' },
        { id: 84, home: 'Nhất bảng H', away: 'Nhì bảng J', time: '02:00', date: '03/07' },
        { id: 85, home: 'Nhất bảng B', away: 'Hạng ba E/F/G/I/J', time: '10:00', date: '03/07' },
        { id: 86, home: 'Nhất bảng J', away: 'Nhì bảng H', time: '05:00', date: '04/07' },
        { id: 87, home: 'Nhất bảng K', away: 'Hạng ba D/E/I/J/L', time: '08:30', date: '04/07' },
        { id: 88, home: 'Nhì bảng D', away: 'Nhì bảng G', time: '01:00', date: '04/07' }
    ] },
    { id: 'r16', name: 'Vòng 16 đội', matches: [
        { id: 89, home: 'Thắng trận 74', away: 'Thắng trận 77', time: '04:00', date: '05/07' },
        { id: 90, home: 'Thắng trận 73', away: 'Thắng trận 75', time: '00:00', date: '05/07' },
        { id: 91, home: 'Thắng trận 76', away: 'Thắng trận 78', time: '03:00', date: '06/07' },
        { id: 92, home: 'Thắng trận 79', away: 'Thắng trận 80', time: '07:00', date: '06/07' },
        { id: 93, home: 'Thắng trận 83', away: 'Thắng trận 84', time: '02:00', date: '07/07' },
        { id: 94, home: 'Thắng trận 81', away: 'Thắng trận 82', time: '07:00', date: '07/07' },
        { id: 95, home: 'Thắng trận 86', away: 'Thắng trận 88', time: '23:00', date: '07/07' },
        { id: 96, home: 'Thắng trận 85', away: 'Thắng trận 87', time: '03:00', date: '08/07' }
    ] },
    { id: 'qf', name: 'Tứ kết', matches: [
        { id: 97, home: 'Thắng trận 89', away: 'Thắng trận 90', time: '03:00', date: '10/07' },
        { id: 98, home: 'Thắng trận 93', away: 'Thắng trận 94', time: '02:00', date: '11/07' },
        { id: 99, home: 'Thắng trận 91', away: 'Thắng trận 92', time: '04:00', date: '12/07' },
        { id: 100, home: 'Thắng trận 95', away: 'Thắng trận 96', time: '08:00', date: '12/07' }
    ] },
    { id: 'sf', name: 'Bán kết', matches: [
        { id: 101, home: 'Thắng trận 97', away: 'Thắng trận 98', time: '02:00', date: '15/07' },
        { id: 102, home: 'Thắng trận 99', away: 'Thắng trận 100', time: '02:00', date: '16/07' }
    ] },
    { id: 'final', name: 'Chung kết / Hạng ba', matches: [
        { id: 104, home: 'Thắng trận 101', away: 'Thắng trận 102', time: '02:00', date: '20/07' },
        { id: 103, home: 'Thua trận 101', away: 'Thua trận 102', time: '04:00', date: '19/07' }
    ] }
];

const visibleRounds = computed(() => {
    const currentIndex = knockoutRounds.findIndex(r => r.id === activeBracketRound.value);
    const rounds = currentIndex === -1 ? knockoutRounds.slice(0, 2) : knockoutRounds.slice(currentIndex, currentIndex + 2);
    
    return rounds.map(round => {
        const pairs = [];
        for (let i = 0; i < round.matches.length; i += 2) {
            pairs.push(round.matches.slice(i, i + 2));
        }
        return { ...round, pairs };
    });
});

const scrollBracket = (direction) => {
    const container = document.getElementById('bracket-container');
    const scrollAmount = 350;
    if (container) {
        container.scrollBy({ left: direction === 'left' ? -scrollAmount : scrollAmount, behavior: 'smooth' });
    }
};

// Computed: Group matches by date, optionally filtered by team
const matchesByDate = computed(() => {
    if (!props.matches) return {};
    
    let allMatches = Array.isArray(props.matches) 
        ? props.matches 
        : Object.values(props.matches).flat();
        
    return allMatches.reduce((acc, match) => {
        if (!match || !match.kickoff_utc) return acc;
        const date = dayjs(match.kickoff_utc).format('YYYY-MM-DD');
        if (!acc[date]) acc[date] = [];
        acc[date].push(match);
        return acc;
    }, {});
});

// Computed: All unique dates with matches, sorted
const sortedDates = computed(() => {
    return matchesByDate.value ? Object.keys(matchesByDate.value).sort() : [];
});

// Watch to set default selected date
watch(sortedDates, (newDates) => {
    if (newDates.length > 0 && !selectedDate.value) {
        selectedDate.value = newDates[0];
    }
}, { immediate: true });

// Filter matches for a specific group
const getGroupMatches = (groupName) => {
    if (!props.matches) return [];
    
    const allMatches = Array.isArray(props.matches) 
        ? props.matches 
        : Object.values(props.matches).flat();
        
    return allMatches.filter(m => m && m.group_name === groupName);
};

// Update URL when tab changes
watch(activeTab, (newTab) => {
    if (typeof window !== 'undefined') {
        const url = new URL(window.location);
        url.searchParams.set('tab', newTab);
        window.history.replaceState({}, '', url);
    }
});

// Countdown Logic
const countdown = ref({ ngày: 0, giờ: 0, phút: 0, giây: 0 });
const targetDate = dayjs('2026-06-11T00:00:00Z');

const updateCountdown = () => {
    const now = dayjs();
    const diff = targetDate.diff(now);
    
    if (diff > 0) {
        countdown.value = {
            ngày: Math.floor(diff / (1000 * 60 * 60 * 24)),
            giờ: Math.floor((diff / (1000 * 60 * 60)) % 24),
            phút: Math.floor((diff / 1000 / 60) % 60),
            giây: Math.floor((diff / 1000) % 60),
        };
    }
};

// Helper: Transform WC Match to MatchCard expected format
const formatMatchData = (match) => {
    if (!match) return null;
    return {
        ...match,
        home_team: {
            name: match.home_team,
            logo_url: match.home_team_flag || `https://api.fifa.com/api/v3/picture/flags-sq-4/${match.home_team_code?.toUpperCase()}`,
            short_name: match.home_team_code
        },
        away_team: {
            name: match.away_team,
            logo_url: match.away_team_flag || `https://api.fifa.com/api/v3/picture/flags-sq-4/${match.away_team_code?.toUpperCase()}`,
            short_name: match.away_team_code
        },
        match_datetime: match.kickoff_utc || match.match_at
    };
};

const groupedMatches = computed(() => {
    if (!props.matches) return {};
    const allMatches = Array.isArray(props.matches) 
        ? props.matches 
        : Object.values(props.matches).flat();
        
    return allMatches.reduce((acc, match) => {
        if (!match) return acc;
        const round = match.round || 'Vòng bảng';
        if (!acc[round]) acc[round] = [];
        acc[round].push(formatMatchData(match));
        return acc;
    }, {});
});

const safeTeams = computed(() => Array.isArray(props.teams) ? props.teams : []);
const safeGroups = computed(() => Array.isArray(props.groups) ? props.groups : []);
const safeStadiums = computed(() => Array.isArray(props.stadiums) ? props.stadiums : []);

const formatRoundName = (round) => {
    const rounds = {
        'group': 'Vòng bảng',
        'R32': 'Vòng 32 đội',
        'R16': 'Vòng 16 đội',
        'QF': 'Tứ kết',
        'SF': 'Bán kết',
        '3rd': 'Tranh hạng ba',
        'final': 'Chung kết'
    };
    return rounds[round] || round;
};

// Polling live match data
let liveInterval = null;
onMounted(() => {
    updateCountdown();
    const countdownInterval = setInterval(updateCountdown, 1000);
    
    liveInterval = setInterval(async () => {
        try {
            const resp = await axios.get('/api/world-cup/live');
            liveMatch.value = resp.data;
        } catch (e) { console.error('Live sync failed', e); }
    }, 30000);

    onUnmounted(() => {
        clearInterval(countdownInterval);
        clearInterval(liveInterval);
    });
});
</script>

<style scoped>
.italic-shadow {
    text-shadow: 4px 4px 0 rgba(16, 185, 129, 0.2);
}

.animate-float {
    animation: float 4s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-15px); }
}

#bracket-container {
    scroll-snap-type: x mandatory;
    scroll-behavior: smooth;
}

#bracket-container > div > div {
    scroll-snap-align: center;
}


/* Custom Tab Scrollbar for Mobile */
.tabs-scroll {
    display: flex;
    overflow-x: auto;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
}

/* VnExpress Style refined borders */
.border-vn {
    border-color: rgba(0,0,0,0.05);
}

.dark .border-vn {
    border-color: rgba(255,255,255,0.05);
}
</style>

<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import MainLayout from '../../Layouts/MainLayout.vue';
import CustomSelect from '../../Components/CustomSelect.vue';
import { getFullDisplay, getCountryNames } from '../../Constants/countries';

const props = defineProps({
    players: Object,
    filters: Object,
    nationalities: Array,
});

const search      = ref(props.filters.search || '');
const nationality = ref(props.filters.nationality || '');
const position    = ref(props.filters.position || '');
const showFilters = ref(false);

const POSITIONS = [
    { value: '', label: 'Tất cả' },
    { value: 'GK', label: 'GK — Thủ môn' },
    { value: 'DF', label: 'DF — Hậu vệ' },
    { value: 'MF', label: 'MF — Tiền vệ' },
    { value: 'FW', label: 'FW — Tiền đạo' },
];

// Position badge colours
const posColor = (pos) => {
    const p = (pos || '').toUpperCase();
    if (p.includes('GK')) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400';
    if (p.includes('DF')) return 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-400';
    if (p.includes('MF')) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400';
    if (p.includes('FW')) return 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400';
    return 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400';
};

const flagCode = (nat) => nat ? nat.split(' ')[0].toLowerCase() : null;

// Debounce helper
let timer;
const query = () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get('/players', {
            search: search.value,
            nationality: nationality.value,
            position: position.value,
        }, { preserveState: true, replace: true });
    }, 300);
};

watch([search, nationality, position], query);

const clearFilters = () => {
    search.value = '';
    nationality.value = '';
    position.value = '';
};

const activeFilters = () => [search.value, nationality.value, position.value].filter(Boolean).length;
</script>

<template>
    <Head title="Cầu thủ — AI Soccer" />
    <MainLayout>
        <!-- ── Page Header ──────────────────────────── -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white">Cầu thủ</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ players.total.toLocaleString() }} vận động viên chuyên nghiệp
                </p>
            </div>

            <!-- Search bar -->
            <div class="flex gap-2">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Tìm tên cầu thủ…"
                        class="pl-9 pr-4 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 w-56 placeholder-gray-400"
                    />
                </div>
                <button
                    @click="showFilters = !showFilters"
                    class="relative px-4 py-2 rounded-xl border text-sm font-semibold transition-colors"
                    :class="activeFilters() ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:border-gray-300'"
                >
                    <svg class="w-4 h-4 inline -mt-px mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M7 10h10M11 16h2"/>
                    </svg>
                    Lọc
                    <span v-if="activeFilters()" class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full bg-emerald-500 text-white text-[10px] font-bold">{{ activeFilters() }}</span>
                </button>
            </div>
        </div>

        <!-- Filter panel -->
        <transition enter-active-class="transition duration-150 ease-out" enter-from-class="-translate-y-2 opacity-0" enter-to-class="translate-y-0 opacity-100" leave-active-class="transition duration-100 ease-in" leave-from-class="translate-y-0 opacity-100" leave-to-class="-translate-y-2 opacity-0">
            <div v-if="showFilters" class="mb-6 p-4 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex flex-wrap gap-4 items-end">
                <div class="min-w-[180px] flex-1">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Vị trí</label>
                    <CustomSelect
                        v-model="position"
                        :options="POSITIONS"
                        placeholder="Tất cả vị trí"
                    >
                        <template #option="{ option }">
                            <span v-if="option.value" class="w-2 h-2 rounded-full shrink-0" :class="posColor(option.value).split(' ')[0]"></span>
                        </template>
                    </CustomSelect>
                </div>
                <div class="min-w-[220px] flex-1">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Quốc tịch</label>
                    <CustomSelect
                        v-model="nationality"
                        :options="nationalities.map(n => ({ value: n, label: getFullDisplay(n) }))"
                        placeholder="Tất cả quốc gia"
                        searchable
                    >
                        <template #icon="{ option }">
                            <span v-if="option && flagCode(option.value)" :class="`fi fi-${flagCode(option.value)} shadow-sm rounded-sm`" style="width: 1.2em; height: 1em;"></span>
                        </template>
                        <template #option="{ option }">
                            <span v-if="flagCode(option.value)" :class="`fi fi-${flagCode(option.value)} shadow-sm rounded-sm`" style="width: 1.2em; height: 1em;"></span>
                        </template>
                    </CustomSelect>
                </div>
                <button v-if="activeFilters()" @click="clearFilters" class="text-xs font-bold text-rose-500 hover:text-rose-700 flex items-center gap-1 py-2">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Xóa bộ lọc
                </button>
            </div>
        </transition>

        <!-- ── Player Grid ──────────────────────────── -->
        <div v-if="players.data.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <Link
                v-for="player in players.data"
                :key="player.id"
                :href="`/players/${player.id}`"
                class="group block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl hover:border-emerald-400 dark:hover:border-emerald-500 hover:shadow-md transition-all overflow-hidden"
            >
                <!-- Card header accent -->
                <div class="h-1 w-full" :class="posColor(player.position).split(' ')[0].replace('bg-', 'bg-') + ' opacity-70'"></div>

                <div class="p-4">
                    <!-- Avatar row -->
                    <div class="flex items-center gap-3 mb-4">
                        <!-- Club logo as avatar background -->
                        <div class="relative w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center shrink-0 border border-gray-200 dark:border-gray-600 overflow-hidden">
                            <img v-if="player.team?.logo_url" :src="player.team.logo_url" class="w-8 h-8 object-contain opacity-30" />
                            <span class="absolute text-base font-bold text-gray-500 dark:text-gray-300">{{ player.name.charAt(0) }}</span>
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-gray-900 dark:text-white truncate group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">{{ player.name }}</div>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span v-if="flagCode(player.nationality)" :class="`fi fi-${flagCode(player.nationality)} text-sm`"></span>
                                <span class="text-xs text-gray-400 dark:text-gray-500 truncate" :title="getFullDisplay(player.nationality)">{{ player.nationality ? getCountryNames(player.nationality).vi : 'N/A' }}</span>
                            </div>
                        </div>
                        <span class="ml-auto text-[10px] font-bold uppercase px-2 py-0.5 rounded-lg shrink-0" :class="posColor(player.position)">
                            {{ player.position || '—' }}
                        </span>
                    </div>

                    <!-- Club row -->
                    <div class="flex items-center gap-2 py-3 border-t border-gray-100 dark:border-gray-700">
                        <img v-if="player.team?.logo_url" :src="player.team.logo_url" class="w-4 h-4 object-contain" />
                        <span class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ player.team?.name || 'Chưa cập nhật' }}</span>
                    </div>

                    <!-- Stats row from latest season -->
                    <div class="flex items-center justify-around border border-gray-100 dark:border-gray-700 rounded-xl py-2.5">
                        <div class="text-center">
                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">G</div>
                            <div class="text-sm font-bold text-gray-900 dark:text-white">{{ player.latest_season_stat?.goals ?? '—' }}</div>
                        </div>
                        <div class="w-px h-6 bg-gray-100 dark:bg-gray-700"></div>
                        <div class="text-center">
                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">A</div>
                            <div class="text-sm font-bold text-gray-900 dark:text-white">{{ player.latest_season_stat?.assists ?? '—' }}</div>
                        </div>
                        <div class="w-px h-6 bg-gray-100 dark:bg-gray-700"></div>
                        <div class="text-center">
                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Trận</div>
                            <div class="text-sm font-bold text-gray-900 dark:text-white">{{ player.latest_season_stat?.games ?? '—' }}</div>
                        </div>
                        <div class="w-px h-6 bg-gray-100 dark:bg-gray-700"></div>
                        <div class="text-center">
                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Phút</div>
                            <div class="text-sm font-bold text-gray-900 dark:text-white">{{ player.latest_season_stat?.minutes ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </Link>
        </div>

        <!-- Empty state -->
        <div v-else class="py-24 text-center border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-3xl">
            <svg class="mx-auto w-10 h-10 text-gray-300 dark:text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
            </svg>
            <p class="font-bold text-gray-500 dark:text-gray-400">Không tìm thấy cầu thủ phù hợp</p>
            <button @click="clearFilters" class="mt-4 text-sm font-bold text-emerald-600 hover:underline">Xóa bộ lọc</button>
        </div>

        <!-- ── Pagination ──────────────────────────── -->
        <div v-if="players.last_page > 1" class="mt-10 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm">
            <p class="text-gray-500 dark:text-gray-400">
                Hiển thị <span class="font-bold text-gray-900 dark:text-white">{{ players.from }}–{{ players.to }}</span> trong <span class="font-bold text-gray-900 dark:text-white">{{ players.total }}</span>
            </p>
            <div class="flex gap-1 flex-wrap justify-center">
                <Link
                    v-for="(link, i) in players.links"
                    :key="i"
                    :href="link.url || '#'"
                    v-html="link.label"
                    :class="[
                        'px-3 py-1.5 rounded-lg border font-medium text-xs transition-colors',
                        link.active
                            ? 'bg-emerald-500 border-emerald-500 text-white'
                            : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:border-emerald-400 hover:text-emerald-600',
                        !link.url ? 'opacity-40 pointer-events-none' : '',
                    ]"
                />
            </div>
        </div>
    </MainLayout>
</template>

<style scoped>
@import "https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.0.0/css/flag-icons.min.css";
</style>

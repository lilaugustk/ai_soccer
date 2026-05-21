<template>
    <Head :title="`${game.home_team?.name} vs ${game.away_team?.name}`" />
    <MainLayout>
        <div class="pt-2 pb-8 flex flex-col lg:flex-row gap-12">
            <div class="flex-1 min-w-0">
            <!-- Back Button -->
            <Link
                href="/matches"
                class="inline-flex items-center gap-2 text-[11px] font-bold text-gray-400 hover:text-emerald-500 transition-colors mb-4 group uppercase tracking-widest"
            >
                <svg
                    class="w-4 h-4 transition-transform group-hover:-translate-x-1"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2.5"
                        d="M15 19l-7-7 7-7"
                    />
                </svg>
                Danh sách trận đấu
            </Link>

            <!-- Scoreboard Header -->
            <div
                class="bg-white dark:bg-gray-800 rounded-[1.5rem] shadow-lg p-5 md:p-6 mb-6 border border-gray-100 dark:border-gray-700 relative overflow-hidden"
            >
                <div
                    class="flex flex-col md:flex-row items-center justify-between gap-6 relative z-10"
                >
                    <!-- Home Team -->
                    <div
                        class="flex-1 flex flex-col items-center md:items-end text-center md:text-right"
                    >
                        <div
                            class="w-16 h-16 md:w-20 md:h-20 mb-3 p-3 bg-white dark:bg-gray-700 rounded-2xl shadow-inner flex items-center justify-center border border-gray-100 dark:border-gray-600 transition-transform hover:scale-105 overflow-hidden"
                        >
                            <img
                                v-if="game.home_team?.logo_url"
                                :src="game.home_team?.logo_url"
                                class="w-full h-full object-contain filter drop-shadow-xl"
                            />
                            <div
                                v-else
                                class="text-2xl font-bold text-gray-200"
                            >
                                H
                            </div>
                        </div>
                        <h2
                            class="text-base md:text-lg font-semibold text-gray-950 dark:text-white mb-1 leading-tight"
                        >
                            {{ game.home_team?.name }}
                        </h2>
                        <span
                            class="text-[9px] font-semibold uppercase tracking-[0.2em] text-emerald-500 px-2 py-0.5 bg-emerald-50 dark:bg-emerald-500/10 rounded-full"
                            >Chủ nhà</span
                        >
                    </div>

                    <!-- Score & Info -->
                    <div class="flex flex-col items-center">
                        <div
                            class="px-2.5 py-1 bg-gray-950 dark:bg-white rounded-lg mb-2 flex items-center justify-center min-h-[20px]"
                        >
                            <span
                                class="text-[8px] font-bold uppercase tracking-widest text-white dark:text-gray-950 leading-none"
                            >
                                {{ getMatchStatus(game) }}
                            </span>
                        </div>
                        <div class="flex flex-col items-center">
                            <div
                                class="flex items-center gap-4 text-3xl md:text-4xl font-bold text-gray-950 dark:text-white tabular-nums"
                            >
                                <span>{{ (game.status === 'postponed' || game.status === 'cancelled') ? '-' : (game.home_score ?? "-") }}</span>
                                <span
                                    class="text-gray-200 dark:text-gray-700 opacity-50 text-xl"
                                    >:</span
                                >
                                <span>{{ (game.status === 'postponed' || game.status === 'cancelled') ? '-' : (game.away_score ?? "-") }}</span>
                            </div>
                            <div v-if="game.home_score_ht !== null && game.away_score_ht !== null && game.status !== 'postponed' && game.status !== 'cancelled'" class="mt-1 text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                                HT: {{ game.home_score_ht }} - {{ game.away_score_ht }}
                            </div>
                        </div>
                        <div class="mt-2 flex flex-col items-center">
                            <span
                                class="text-[8px] font-bold text-gray-400 uppercase tracking-widest"
                                >{{ formatDateTime(game.match_datetime) }}</span
                            >
                            <div
                                class="flex items-center gap-2 mt-1.5 px-2 py-0.5 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-100 dark:border-gray-600"
                            >
                                <img
                                    v-if="game.league?.logo_url"
                                    :src="game.league?.logo_url"
                                    class="w-3.5 h-3.5 object-contain"
                                />
                                <span
                                    class="text-[8px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-tighter"
                                    >{{ game.league?.name }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Away Team -->
                    <div
                        class="flex-1 flex flex-col items-center md:items-start text-center md:text-left"
                    >
                        <div
                            class="w-16 h-16 md:w-20 md:h-20 mb-3 p-3 bg-white dark:bg-gray-700 rounded-2xl shadow-inner flex items-center justify-center border border-gray-100 dark:border-gray-600 transition-transform hover:scale-105 overflow-hidden"
                        >
                            <img
                                v-if="game.away_team?.logo_url"
                                :src="game.away_team?.logo_url"
                                class="w-full h-full object-contain filter drop-shadow-xl"
                            />
                            <div
                                v-else
                                class="text-2xl font-bold text-gray-200"
                            >
                                A
                            </div>
                        </div>
                        <h2
                            class="text-base md:text-lg font-semibold text-gray-950 dark:text-white mb-1 leading-tight"
                        >
                            {{ game.away_team?.name }}
                        </h2>
                        <span
                            class="text-[9px] font-bold uppercase tracking-[0.2em] text-gray-400 px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded-full"
                            >Đội khách</span
                        >
                    </div>
                </div>

                <!-- Match Info Badges -->
                <div class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-700/50 flex flex-wrap justify-center gap-3 md:gap-6 relative z-10">
                    <div v-if="game.venue?.name" class="flex items-center gap-1.5 text-[10px] font-medium text-gray-500 dark:text-gray-400">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        <span>{{ game.venue.name }}{{ game.venue.city ? `, ${game.venue.city}` : '' }}</span>
                    </div>
                    <div v-if="game.referee" class="flex items-center gap-1.5 text-[10px] font-medium text-gray-500 dark:text-gray-400">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        <span>{{ game.referee }}</span>
                    </div>
                    <div v-if="game.attendance" class="flex items-center gap-1.5 text-[10px] font-medium text-gray-500 dark:text-gray-400">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        <span>{{ Number(game.attendance).toLocaleString() }}</span>
                    </div>
                    <div v-if="game.weather?.description" class="flex items-center gap-1.5 text-[10px] font-medium text-gray-500 dark:text-gray-400">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" /></svg>
                        <span class="capitalize">{{ game.weather.description }}</span>
                        <span v-if="game.weather.temp" class="ml-1">{{ game.weather.temp }}°C</span>
                    </div>
                </div>
            </div>


            <!-- Tabs Navigation (Synced Underline Style) -->
            <div class="flex items-center justify-center gap-8 border-b border-gray-100 dark:border-gray-800 mb-6 overflow-x-auto no-scrollbar">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="setActiveTab(tab.id)"
                    class="relative py-4 text-[11px] font-bold uppercase transition-all duration-300 whitespace-nowrap"
                    :class="
                        activeTab === tab.id
                            ? 'text-emerald-600 dark:text-emerald-400'
                            : 'text-gray-400 hover:text-gray-900 dark:hover:text-white'
                    "
                >
                    {{ tab.label }}
                    <!-- Active Underline Indicator -->
                    <div v-if="activeTab === tab.id" 
                         class="absolute bottom-0 left-0 right-0 h-0.5 bg-emerald-500 rounded-full animate-in fade-in slide-in-from-left-1">
                    </div>
                </button>
            </div>

            <!-- Tab Contents -->
            <div class="min-h-[500px] py-2 relative">
                <!-- Loading State (Skeleton) -->
                <div v-if="isLoadingTab" class="space-y-8 animate-pulse">
                    <!-- Scoreboard/Pitch or Stats skeleton -->
                    <div class="w-full aspect-[1.4/1] md:aspect-[2/1] bg-gray-100 dark:bg-gray-800/50 rounded-[2rem] border border-gray-200 dark:border-gray-700/50 flex items-center justify-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="w-10 h-10 rounded-full border-4 border-emerald-500/20 border-t-emerald-500 animate-spin"></div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-emerald-500">Đang tải dữ liệu...</span>
                        </div>
                    </div>
                    
                    <div class="grid lg:grid-cols-2 gap-8">
                        <div class="space-y-4">
                            <div class="h-10 bg-gray-100 dark:bg-gray-800/50 rounded-2xl w-full"></div>
                            <div class="bg-gray-50/30 dark:bg-gray-800/20 rounded-[2rem] h-96 border border-gray-100 dark:border-gray-700/50"></div>
                        </div>
                        <div class="space-y-4">
                            <div class="h-10 bg-gray-100 dark:bg-gray-800/50 rounded-2xl w-full"></div>
                            <div class="bg-gray-50/30 dark:bg-gray-800/20 rounded-[2rem] h-96 border border-gray-100 dark:border-gray-700/50"></div>
                        </div>
                    </div>
                </div>

                <!-- Tab Contents (Hidden when loading) -->
                <div v-else>
                    <!-- Lineups Tab -->
                    <div v-if="activeTab === 'lineups'" class="space-y-8">
                    <!-- Formation Pitch (Visual - Flashscore Horizontal Style) -->
                    <div class="relative">
                        <!-- Expand Button -->
                        <button
                            @click="showPitchModal = true"
                            class="absolute top-3 right-3 z-50 w-9 h-9 bg-black/50 hover:bg-black/70 backdrop-blur-sm border border-white/20 rounded-xl flex items-center justify-center transition-all hover:scale-110 shadow-xl group"
                            title="Xem toàn màn hình"
                        >
                            <svg class="w-4 h-4 text-white/80 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                            </svg>
                        </button>
                        <!-- Pitch Container -->
                        <div
                            class="relative w-full aspect-[1.4/1] md:aspect-[2/1] bg-gradient-to-br from-[#1e4d35] to-[#143a28] rounded-[2rem] overflow-hidden shadow-2xl border-4 border-[#2c5d44]"
                        >
                            <!-- Grass Background with Pattern -->
                            <div
                                class="absolute inset-0 overflow-hidden"
                            >
                                <div
                                    class="absolute inset-0 opacity-[0.15]"
                                    style="
                                        background-image: repeating-linear-gradient(
                                            90deg,
                                            transparent,
                                            transparent 10%,
                                            rgba(255, 255, 255, 0.1) 10%,
                                            rgba(255, 255, 255, 0.1) 20%
                                        );
                                    "
                                ></div>
                                <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(44,93,68,0.4),transparent)]"></div>
                            </div>

                            <!-- Pitch Markings -->
                            <div
                                class="absolute inset-[4%] border-2 border-white/15 pointer-events-none"
                            ></div>
                            <div
                                class="absolute inset-y-0 left-1/2 w-0.5 bg-white/15 -translate-x-1/2"
                            ></div>
                            <div
                                class="absolute top-1/2 left-1/2 w-24 h-24 md:w-32 md:h-32 border-2 border-white/15 rounded-full -translate-x-1/2 -translate-y-1/2"
                            ></div>
                            <div
                                class="absolute top-1/2 left-1/2 w-1.5 h-1.5 bg-white/30 rounded-full -translate-x-1/2 -translate-y-1/2"
                            ></div>

                            <!-- Penalty Areas -->
                            <div
                                class="absolute top-1/2 -translate-y-1/2 left-4 w-16 h-48 md:w-24 md:h-64 border-y border-r border-white/15"
                            ></div>
                            <div
                                class="absolute top-1/2 -translate-y-1/2 left-4 w-6 h-24 md:w-10 md:h-32 border-y border-r border-white/15"
                            ></div>

                            <div
                                class="absolute top-1/2 -translate-y-1/2 right-4 w-16 h-48 md:w-24 md:h-64 border-y border-l border-white/15"
                            ></div>
                            <div
                                class="absolute top-1/2 -translate-y-1/2 right-4 w-6 h-24 md:w-10 md:h-32 border-y border-l border-white/15"
                            ></div>







                            <!-- Players Layer -->
                            <div class="absolute inset-0 z-30">
                                <!-- Home Team -->
                                <div
                                    v-for="p in processedHomeLineup"
                                    :key="'home-' + p.id"
                                    class="absolute"
                                    :style="p.style"
                                >
                                    <div
                                        class="flex flex-col items-center gap-1.5 cursor-pointer group"
                                        @click="
                                            router.visit(`/players/${p.id}`)
                                        "
                                    >
                                        <div class="relative">
                                            <!-- Avatar with dynamic shadow -->
                                            <div
                                                class="w-11 h-11 md:w-13 md:h-13 rounded-full border-2 border-white/10 bg-slate-900/50 shadow-2xl overflow-hidden"
                                            >
                                                <img
                                                    v-if="p.id"
                                                    :src="`https://sports.bzzoiro.com/img/player/${p.id}/`"
                                                    class="w-full h-full object-cover rounded-full"
                                                />
                                            </div>
                                            <!-- Rating (Top Right) -->
                                            <div
                                                v-if="p.rating && parseFloat(p.rating) > 0"
                                                class="absolute -top-1.5 -right-3 w-7 h-5 rounded-lg text-[9px] font-bold flex items-center justify-center shadow-xl border-2 border-white/20 z-30"
                                                :class="getRatingClass(p.rating)"
                                            >
                                                {{ p.rating }}
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <div
                                                class="bg-black/50 px-2 py-0.5 rounded backdrop-blur-sm border border-white/10"
                                            >
                                                <span
                                                    class="text-[10px] md:text-[11px] font-bold text-white tracking-tight drop-shadow-lg truncate max-w-[80px] block"
                                                    >{{
                                                        p.name?.split(' ')?.pop() ?? '?'
                                                    }}</span
                                                >
                                            </div>

                                            <span v-if="p.isSubstitutedIn && lineupView === 'end'" class="text-[7px] font-bold text-emerald-400 uppercase tracking-tighter -mt-1 leading-none">Sub</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Away Team -->
                                <div
                                    v-for="p in processedAwayLineup"
                                    :key="'away-' + p.id"
                                    class="absolute"
                                    :style="p.style"
                                >
                                    <div
                                        class="flex flex-col items-center gap-1.5 cursor-pointer group"
                                        @click="
                                            router.visit(`/players/${p.id}`)
                                        "
                                    >
                                        <div class="relative">
                                            <div
                                                class="w-11 h-11 md:w-13 md:h-13 rounded-full border-2 border-white/10 bg-slate-900/50 shadow-2xl overflow-hidden"
                                            >
                                                <img
                                                    v-if="p.id"
                                                    :src="`https://sports.bzzoiro.com/img/player/${p.id}/`"
                                                    class="w-full h-full object-cover rounded-full"
                                                />
                                            </div>
                                            <!-- Rating (Top Left for Away) -->
                                            <div
                                                v-if="p.rating && parseFloat(p.rating) > 0"
                                                class="absolute -top-1.5 -left-2 w-7 h-5 rounded-lg text-[9px] font-bold flex items-center justify-center shadow-xl border-2 border-white/20 z-40"
                                                :class="
                                                    getRatingClass(p.rating)
                                                "
                                            >
                                                {{ p.rating }}
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <div
                                                class="bg-black/50 px-2 py-0.5 rounded backdrop-blur-sm border border-white/10"
                                            >
                                                <span
                                                    class="text-[10px] md:text-[11px] font-bold text-white tracking-tight drop-shadow-lg truncate max-w-[80px] block"
                                                    >{{
                                                        p.name?.split(' ')?.pop() ?? '?'
                                                    }}</span
                                                >
                                            </div>

                                            <span v-if="p.isSubstitutedIn && lineupView === 'end'" class="text-[7px] font-bold text-blue-400 uppercase tracking-tighter -mt-1 leading-none">Sub</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty Data Overlay -->
                            <div v-if="!processedHomeLineup.length && !processedAwayLineup.length" 
                                 class="absolute inset-0 z-50 flex flex-col items-center justify-center bg-black/40 backdrop-blur-[2px]">
                                <div class="p-8 bg-white dark:bg-gray-800 rounded-3xl shadow-2xl text-center max-w-sm mx-4 border border-gray-100 dark:border-gray-700">
                                    <div class="w-16 h-16 bg-emerald-500/10 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <h4 class="text-base font-bold text-gray-900 dark:text-white mb-2">Đội hình chưa khả dụng</h4>
                                    <p class="text-[11px] text-gray-400 font-medium leading-relaxed">Dữ liệu đội hình cho trận đấu này đang được hệ thống cập nhật tự động hoặc không được ban tổ chức cung cấp.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Predicted Lineup Banner -->
                    <div v-if="homeLineupData?.lineup_status === 'predicted' || awayLineupData?.lineup_status === 'predicted'"
                         class="flex items-start gap-3 px-4 py-3 rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 text-amber-700 dark:text-amber-400">
                        <div class="flex-1 min-w-0">
                            <p class="text-[11px] font-bold uppercase tracking-wider">Đội hình dự đoán</p>
                            <p class="text-[11px] font-medium mt-0.5 leading-relaxed">
                                Trận đấu chưa diễn ra. Đây là đội hình <strong>dự đoán bởi hệ thống AI</strong> dựa trên lịch sử thi đấu, không phải đội hình chính thức đã được xác nhận bởi ban huấn luyện.
                                <template v-if="homeLineupData?.confidence">
                                    Độ tin cậy: <strong>{{ (homeLineupData.confidence * 100).toFixed(0) }}%</strong> (chủ nhà) —
                                    <strong>{{ (awayLineupData?.confidence * 100).toFixed(0) }}%</strong> (khách).
                                </template>
                            </p>
                        </div>
                    </div>


                    <!-- Pitch Fullscreen Modal -->
                    <Teleport to="body">
                        <Transition name="modal-fade">
                        <div
                            v-if="showPitchModal"
                            class="fixed inset-0 z-[9999] flex items-center justify-center p-3"
                            @click.self="showPitchModal = false"
                        >
                            <!-- Backdrop -->
                            <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="showPitchModal = false"></div>

                            <!-- Modal Content -->
                            <div class="relative w-full max-w-7xl mx-auto">
                                <!-- Header -->
                                <div class="flex items-center justify-between mb-4 px-2">
                                    <div class="flex items-center gap-4">
                                        <div class="flex items-center gap-2">
                                            <img v-if="game.home_team?.logo_url" :src="game.home_team?.logo_url" class="w-7 h-7 object-contain" />
                                            <span class="text-white font-bold text-sm">{{ game.home_team?.name }}</span>
                                        </div>
                                        <span class="text-white/40 font-bold">vs</span>
                                        <div class="flex items-center gap-2">
                                            <img v-if="game.away_team?.logo_url" :src="game.away_team?.logo_url" class="w-7 h-7 object-contain" />
                                            <span class="text-white font-bold text-sm">{{ game.away_team?.name }}</span>
                                        </div>
                                    </div>
                                    <button
                                        @click="showPitchModal = false"
                                        class="w-9 h-9 bg-white/10 hover:bg-white/20 rounded-xl flex items-center justify-center transition-all border border-white/10"
                                    >
                                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Full Pitch -->
                                <div class="relative w-full aspect-[2/1] bg-gradient-to-br from-[#1e4d35] to-[#143a28] rounded-[2rem] overflow-hidden shadow-2xl border-4 border-[#2c5d44]">
                                    <!-- Grass Pattern -->
                                    <div class="absolute inset-0 overflow-hidden">
                                        <div class="absolute inset-0 opacity-[0.15]" style="background-image: repeating-linear-gradient(90deg, transparent, transparent 10%, rgba(255,255,255,0.1) 10%, rgba(255,255,255,0.1) 20%);"></div>
                                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(44,93,68,0.4),transparent)]"></div>
                                    </div>
                                    <!-- Pitch Markings -->
                                    <div class="absolute inset-[4%] border-2 border-white/15 pointer-events-none"></div>
                                    <div class="absolute inset-y-0 left-1/2 w-0.5 bg-white/15 -translate-x-1/2"></div>
                                    <div class="absolute top-1/2 left-1/2 w-32 h-32 border-2 border-white/15 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                                    <div class="absolute top-1/2 left-1/2 w-1.5 h-1.5 bg-white/30 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
                                    <!-- Penalty Areas -->
                                    <div class="absolute top-1/2 -translate-y-1/2 left-4 w-24 h-64 border-y border-r border-white/15"></div>
                                    <div class="absolute top-1/2 -translate-y-1/2 left-4 w-10 h-32 border-y border-r border-white/15"></div>
                                    <div class="absolute top-1/2 -translate-y-1/2 right-4 w-24 h-64 border-y border-l border-white/15"></div>
                                    <div class="absolute top-1/2 -translate-y-1/2 right-4 w-10 h-32 border-y border-l border-white/15"></div>


                                    <!-- Formation Labels -->
                                    <div class="absolute top-4 left-6 z-40">
                                        <div v-if="game.home_formation" class="px-2 py-1 bg-black/30 rounded border border-white/5">
                                            <span class="text-[11px] font-bold text-white/60 uppercase tracking-[0.2em]">{{ game.home_formation }}</span>
                                        </div>
                                    </div>
                                    <div class="absolute top-4 right-6 z-40">
                                        <div v-if="game.away_formation" class="px-2 py-1 bg-black/30 rounded border border-white/5">
                                            <span class="text-[11px] font-bold text-white/60 uppercase tracking-[0.2em]">{{ game.away_formation }}</span>
                                        </div>
                                    </div>

                                    <!-- Players -->
                                    <div class="absolute inset-0 z-30">
                                        <!-- Home -->
                                        <div v-for="p in processedHomeLineup" :key="'mhome-' + p.id" class="absolute" :style="p.style">
                                            <div class="flex flex-col items-center gap-1.5 cursor-pointer" @click="openPlayerStats(p); showPitchModal = false">
                                                <div class="relative">
                                                    <div class="w-14 h-14 rounded-full border-2 border-white/10 bg-slate-900/50 shadow-2xl overflow-hidden">
                                                        <img v-if="p.id" :src="`https://sports.bzzoiro.com/img/player/${p.id}/`" class="w-full h-full object-cover rounded-full" />
                                                    </div>
                                                    <div v-if="p.rating && parseFloat(p.rating) > 0" class="absolute -top-1.5 -right-3 w-8 h-5 rounded-lg text-[9px] font-black flex items-center justify-center shadow-xl border-2 border-white/20 z-30" :class="getRatingClass(p.rating)">{{ p.rating }}</div>
                                                </div>
                                                <div class="bg-black/50 px-2.5 py-1 rounded backdrop-blur-sm border border-white/10">
                                                    <span class="text-[11px] font-bold text-white tracking-tight drop-shadow-lg truncate max-w-[90px] block">{{ p.name?.split(' ')?.pop() ?? '?' }}</span>
                                                </div>
                                                <span class="text-[9px] font-bold text-white/75 uppercase tracking-widest drop-shadow-md">{{ p.number }}</span>
                                            </div>
                                        </div>
                                        <!-- Away -->
                                        <div v-for="p in processedAwayLineup" :key="'maway-' + p.id" class="absolute" :style="p.style">
                                            <div class="flex flex-col items-center gap-1.5 cursor-pointer" @click="openPlayerStats(p); showPitchModal = false">
                                                <div class="relative">
                                                    <div class="w-14 h-14 rounded-full border-2 border-white/10 bg-slate-900/50 shadow-2xl overflow-hidden">
                                                        <img v-if="p.id" :src="`https://sports.bzzoiro.com/img/player/${p.id}/`" class="w-full h-full object-cover rounded-full" />
                                                    </div>
                                                    <div v-if="p.rating && parseFloat(p.rating) > 0" class="absolute -top-1.5 -left-2 w-8 h-5 rounded-lg text-[9px] font-black flex items-center justify-center shadow-xl border-2 border-white/20 z-40" :class="getRatingClass(p.rating)">{{ p.rating }}</div>
                                                </div>
                                                <div class="bg-black/50 px-2.5 py-1 rounded backdrop-blur-sm border border-white/10">
                                                    <span class="text-[11px] font-bold text-white tracking-tight drop-shadow-lg truncate max-w-[90px] block">{{ p.name?.split(' ')?.pop() ?? '?' }}</span>
                                                </div>
                                                <span class="text-[9px] font-bold text-white/75 uppercase tracking-widest drop-shadow-md">{{ p.number }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </Transition>
                    </Teleport>

                    <!-- Player Stats Modal -->
                    <Teleport to="body">
                        <Transition name="modal-fade">
                            <div
                                v-if="showPlayerStatsModal && selectedPlayerStats"
                                class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                            >
                                <!-- Backdrop -->
                                <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-[4px]" @click="showPlayerStatsModal = false"></div>

                                <!-- Modal Card -->
                                <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-3xl overflow-hidden shadow-2xl border border-gray-100 dark:border-gray-800/80 transition-all duration-300">
                                    <!-- Header Profile Banner -->
                                    <div class="relative px-8 pt-8 pb-5 bg-white dark:bg-slate-900 border-b border-gray-100 dark:border-gray-800">
                                        <!-- Close Button -->
                                        <button
                                            @click="showPlayerStatsModal = false"
                                            class="absolute top-6 right-6 w-8 h-8 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700/60 text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 rounded-full flex items-center justify-center transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>

                                        <!-- Player Info -->
                                        <div class="flex items-center gap-4 pr-12">
                                            <div class="w-14 h-14 rounded-xl bg-gray-50 dark:bg-gray-800/80 border border-gray-100 dark:border-gray-700/40 overflow-hidden shadow-sm flex-shrink-0">
                                                <img :src="`https://sports.bzzoiro.com/img/player/${selectedPlayerStats.id}/`" class="w-full h-full object-cover" />
                                            </div>

                                            <div class="flex-1 min-w-0">
                                                <h3 class="text-base font-bold text-gray-900 dark:text-white uppercase truncate tracking-tight">{{ selectedPlayerStats.name }}</h3>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <span class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded text-[9px] font-bold uppercase tracking-wider">{{ selectedPlayerStats.pos }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Key Stats Overview Grid -->
                                    <div class="grid grid-cols-3 gap-2 px-8 py-4 bg-gray-50/50 dark:bg-gray-900/30 border-b border-gray-100 dark:border-gray-800">
                                        <div class="text-center py-2">
                                            <template v-if="selectedPlayerStats.pos?.toUpperCase() === 'GK' || selectedPlayerStats.pos?.toUpperCase() === 'GOALKEEPER'">
                                                <div class="text-[9px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Cứu thua</div>
                                                <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ selectedPlayerStats.saves }}</div>
                                            </template>
                                            <template v-else>
                                                <div class="text-[9px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Bàn thắng</div>
                                                <div class="text-xl font-bold text-gray-950 dark:text-white mt-1">{{ selectedPlayerStats.goals }}</div>
                                            </template>
                                        </div>
                                        <div class="text-center py-2 border-x border-gray-100 dark:border-gray-800">
                                            <div class="text-[9px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Kiến tạo</div>
                                            <div class="text-xl font-bold text-gray-950 dark:text-white mt-1">{{ selectedPlayerStats.assists }}</div>
                                        </div>
                                        <div class="text-center py-2">
                                            <div class="text-[9px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Phút thi đấu</div>
                                            <div class="text-xl font-bold text-gray-950 dark:text-white mt-1">{{ selectedPlayerStats.minutes_played }}'</div>
                                        </div>
                                    </div>

                                    <!-- Stats Body -->
                                    <div class="p-8 space-y-5 max-h-[48vh] overflow-y-auto no-scrollbar">
                                        <!-- Attacking & xG Section -->
                                        <div v-if="selectedPlayerStats.pos?.toUpperCase() !== 'GK' && selectedPlayerStats.pos?.toUpperCase() !== 'GOALKEEPER'" class="bg-gray-50/40 dark:bg-gray-800/20 rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden divide-y divide-gray-100 dark:divide-gray-800/40">
                                            <div class="flex items-center justify-between px-5 py-3">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Tổng số cú sút</span>
                                                <span class="text-xs font-semibold text-gray-900 dark:text-white">{{ selectedPlayerStats.total_shots }}</span>
                                            </div>
                                            <div class="flex items-center justify-between px-5 py-3">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Sút trúng đích</span>
                                                <span class="text-xs font-semibold text-gray-900 dark:text-white">{{ selectedPlayerStats.shots_on_target }}</span>
                                            </div>
                                            <div class="flex items-center justify-between px-5 py-3">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Bàn thắng kỳ vọng (xG)</span>
                                                <span class="text-xs font-semibold text-gray-900 dark:text-white">{{ selectedPlayerStats.expected_goals !== null ? selectedPlayerStats.expected_goals.toFixed(2) : '—' }}</span>
                                            </div>
                                            <div class="flex items-center justify-between px-5 py-3">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Kiến tạo kỳ vọng (xA)</span>
                                                <span class="text-xs font-semibold text-gray-900 dark:text-white">{{ selectedPlayerStats.expected_assists !== null ? selectedPlayerStats.expected_assists.toFixed(2) : '—' }}</span>
                                            </div>
                                        </div>

                                        <!-- Passing Section -->
                                        <div class="bg-gray-50/40 dark:bg-gray-800/20 rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden divide-y divide-gray-100 dark:divide-gray-800/40">
                                            <div class="flex items-center justify-between px-5 py-3">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Chuyền chính xác</span>
                                                <span class="text-xs font-semibold text-gray-900 dark:text-white">{{ selectedPlayerStats.accurate_pass }}/{{ selectedPlayerStats.total_pass }}</span>
                                            </div>
                                            <div class="flex items-center justify-between px-5 py-3">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Tỷ lệ chính xác</span>
                                                <span class="text-xs font-semibold text-gray-900 dark:text-white">
                                                    {{ selectedPlayerStats.total_pass > 0 ? ((selectedPlayerStats.accurate_pass / selectedPlayerStats.total_pass) * 100).toFixed(0) + '%' : '0%' }}
                                                </span>
                                            </div>
                                            <div class="flex items-center justify-between px-5 py-3">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Đường chuyền quyết định (Key Passes)</span>
                                                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ selectedPlayerStats.key_pass }}</span>
                                            </div>
                                        </div>

                                        <!-- Defending & Discipline Section -->
                                        <div class="bg-gray-50/40 dark:bg-gray-800/20 rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden divide-y divide-gray-100 dark:divide-gray-800/40">
                                            <div class="flex items-center justify-between px-5 py-3">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Tắc bóng thành công</span>
                                                <span class="text-xs font-semibold text-gray-900 dark:text-white">{{ selectedPlayerStats.total_tackle }}</span>
                                            </div>
                                            <div class="flex items-center justify-between px-5 py-3">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Cắt bóng</span>
                                                <span class="text-xs font-semibold text-gray-900 dark:text-white">{{ selectedPlayerStats.interception }}</span>
                                            </div>
                                            <div class="flex items-center justify-between px-5 py-3">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Thẻ phạt</span>
                                                <div class="flex items-center gap-2">
                                                    <span v-if="selectedPlayerStats.yellow_card > 0" class="px-1.5 py-0.5 bg-amber-500/10 text-amber-600 rounded text-[10px] font-bold">Thẻ vàng x{{ selectedPlayerStats.yellow_card }}</span>
                                                    <span v-if="selectedPlayerStats.red_card > 0" class="px-1.5 py-0.5 bg-red-500/10 text-red-500 rounded text-[10px] font-bold">Thẻ đỏ x{{ selectedPlayerStats.red_card }}</span>
                                                    <span v-if="selectedPlayerStats.yellow_card === 0 && selectedPlayerStats.red_card === 0" class="text-xs font-semibold text-gray-400">0</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Goalkeeping Section -->
                                        <div v-if="selectedPlayerStats.pos === 'GK' || selectedPlayerStats.saves > 0" class="bg-gray-50/40 dark:bg-gray-800/20 rounded-2xl border border-gray-100 dark:border-gray-800/60 overflow-hidden">
                                            <div class="flex items-center justify-between px-5 py-3">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Cản phá cứu thua (Saves)</span>
                                                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ selectedPlayerStats.saves }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Footer Actions -->
                                    <div class="px-8 py-5 bg-gray-50/50 dark:bg-gray-900/30 border-t border-gray-100 dark:border-gray-800 flex gap-4">
                                        <Link
                                            :href="`/players/${selectedPlayerStats.id}`"
                                            class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl flex items-center justify-center gap-1.5 transition-colors uppercase tracking-wider shadow-sm"
                                        >
                                            Xem hồ sơ chi tiết
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </Transition>
                    </Teleport>

                    <!-- Lineup Lists & Substitutes -->
                    <div v-if="processedHomeLineup.length > 0 || processedAwayLineup.length > 0" class="grid lg:grid-cols-2 gap-8">
                        <!-- Home Detailed -->
                        <div class="space-y-6">
                            <h4
                                class="px-6 py-3 bg-gray-50 dark:bg-gray-800/50 rounded-2xl text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500 dark:text-gray-400 border border-gray-100 dark:border-gray-700"
                            >
                                Đội hình: {{ game.home_team.name }}
                            </h4>
                            <div
                                class="bg-white dark:bg-gray-800 rounded-[2rem] border border-gray-100 dark:border-gray-700 overflow-hidden shadow-sm"
                            >
                                <!-- Starting XI -->
                                <div
                                    class="divide-y divide-gray-50 dark:divide-gray-700"
                                >
                                    <div
                                        v-for="p in processedHomeLineup"
                                        :key="p.id"
                                        class="px-6 py-4 flex items-center justify-between group hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors cursor-pointer"
                                        @click="openPlayerStats(p)"
                                    >
                                        <div class="flex items-center gap-4">
                                            <span
                                                class="w-6 text-xs font-bold text-slate-900 dark:text-slate-400 group-hover:text-emerald-500 transition-colors"
                                                >{{ p.number }}</span
                                            >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-gray-50 dark:bg-gray-700 overflow-hidden"
                                            >
                                                <img
                                                    v-if="p.id"
                                                    :src="`https://sports.bzzoiro.com/img/player/${p.id}/`"
                                                    class="w-full h-full object-cover"
                                                />
                                            </div>
                                            <div class="flex flex-col" @click.stop>
                                                <Link
                                                    :href="`/players/${p.id}`"
                                                    class="text-sm font-bold text-gray-950 dark:text-white hover:text-emerald-500 transition-colors"
                                                    >{{ p.name }}</Link
                                                >
                                                <span class="text-[9px] font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider">{{ p.pos }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <div
                                                v-if="p.is_captain"
                                                class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-[8px] font-bold text-slate-500 dark:text-gray-400"
                                            >
                                                C
                                            </div>
                                            <div
                                                v-if="p.rating && parseFloat(p.rating) > 0"
                                                class="w-8 h-6 flex items-center justify-center rounded-lg text-[10px] font-bold border border-white dark:border-gray-800 shadow-sm"
                                                :class="
                                                    getRatingClass(p.rating)
                                                "
                                            >
                                                {{ p.rating }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Substitutes -->
                                <div
                                    class="bg-gray-50/50 dark:bg-gray-900/20 px-6 py-4 border-t border-gray-100 dark:border-gray-700"
                                >
                                    <span
                                        class="text-[9px] font-bold uppercase tracking-widest text-slate-500 dark:text-gray-400"
                                        >Dự bị</span
                                    >
                                </div>
                                <div
                                    class="divide-y divide-gray-50 dark:divide-gray-700"
                                >
                                    <div
                                        v-for="p in homeSubstitutes"
                                        :key="p.id"
                                        class="px-6 py-4 flex items-center justify-between group hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors cursor-pointer"
                                        @click="openPlayerStats(p)"
                                    >
                                        <div
                                            class="flex items-center gap-4 opacity-70 group-hover:opacity-100 transition-opacity"
                                        >
                                            <span
                                                class="w-6 text-xs font-bold text-slate-900 dark:text-slate-400"
                                                >{{ p.number }}</span
                                            >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-gray-50 dark:bg-gray-700 overflow-hidden"
                                            >
                                                <img
                                                    v-if="p.id"
                                                    :src="`https://sports.bzzoiro.com/img/player/${p.id}/`"
                                                    class="w-full h-full object-cover"
                                                />
                                            </div>
                                            <div class="flex flex-col" @click.stop>
                                                <Link
                                                    :href="`/players/${p.id}`"
                                                    class="text-sm font-bold text-gray-950 dark:text-white hover:text-emerald-500 transition-colors"
                                                    >{{ p.name }}</Link
                                                >
                                                <span class="text-[9px] font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider">{{ p.pos }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <div
                                                v-if="p.rating && parseFloat(p.rating) > 0"
                                                class="w-8 h-6 flex items-center justify-center rounded-lg text-[10px] font-bold border border-white dark:border-gray-800 shadow-sm"
                                                :class="
                                                    getRatingClass(p.rating)
                                                "
                                            >
                                                {{ p.rating }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Coach -->
                                <div
                                    v-if="game.home_team?.coach"
                                    class="bg-gray-50/50 dark:bg-gray-900/20 px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between"
                                >
                                        <div>
                                            <span
                                                class="block text-[8px] font-bold uppercase text-slate-500 dark:text-gray-400"
                                                >Huấn luyện viên</span
                                            >
                                            <span
                                                class="text-sm font-bold text-gray-950 dark:text-white"
                                                >{{
                                                    game.home_team.coach
                                                }}</span
                                            >
                                        </div>
                                </div>
                            </div>
                        </div>

                        <!-- Away Detailed -->
                        <div class="space-y-6">
                            <h4
                                class="px-6 py-3 bg-gray-50 dark:bg-gray-800/50 rounded-2xl text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500 dark:text-gray-400 border border-gray-100 dark:border-gray-700 text-right"
                            >
                                Đội hình: {{ game.away_team.name }}
                            </h4>
                            <div
                                class="bg-white dark:bg-gray-800 rounded-[2rem] border border-gray-100 dark:border-gray-700 overflow-hidden shadow-sm"
                            >
                                <!-- Starting XI -->
                                <div
                                    class="divide-y divide-gray-50 dark:divide-gray-700"
                                >
                                    <div
                                        v-for="p in processedAwayLineup"
                                        :key="p.id"
                                        class="px-6 py-4 flex flex-row-reverse items-center justify-between group hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors cursor-pointer"
                                        @click="openPlayerStats(p)"
                                    >
                                        <div
                                            class="flex flex-row-reverse items-center gap-4"
                                        >
                                            <span
                                                class="w-6 text-xs font-bold text-slate-900 dark:text-slate-400 group-hover:text-blue-500 transition-colors text-right"
                                                >{{ p.number }}</span
                                            >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-gray-50 dark:bg-gray-700 overflow-hidden"
                                            >
                                                <img
                                                    v-if="p.id"
                                                    :src="`https://sports.bzzoiro.com/img/player/${p.id}/`"
                                                    class="w-full h-full object-cover"
                                                />
                                            </div>
                                            <div class="flex flex-col items-end" @click.stop>
                                                <Link
                                                    :href="`/players/${p.id}`"
                                                    class="text-sm font-bold text-gray-950 dark:text-white hover:text-blue-500 transition-colors text-right"
                                                    >{{ p.name }}</Link
                                                >
                                                <span class="text-[9px] font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider text-right">{{ p.pos }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <div
                                                v-if="p.rating && parseFloat(p.rating) > 0"
                                                class="w-8 h-6 flex items-center justify-center rounded-lg text-[10px] font-bold border border-white dark:border-gray-800 shadow-sm"
                                                :class="
                                                    getRatingClass(p.rating)
                                                "
                                            >
                                                {{ p.rating }}
                                            </div>
                                            <div
                                                v-if="p.is_captain"
                                                class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-[8px] font-bold text-slate-500 dark:text-gray-400"
                                            >
                                                C
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Substitutes -->
                                <div
                                    class="bg-gray-50/50 dark:bg-gray-900/20 px-6 py-4 border-t border-gray-100 dark:border-gray-700 text-right"
                                >
                                    <span
                                        class="text-[9px] font-bold uppercase tracking-widest text-slate-500 dark:text-gray-400"
                                        >Dự bị</span
                                    >
                                </div>
                                <div
                                    class="divide-y divide-gray-50 dark:divide-gray-700"
                                >
                                    <div
                                        v-for="p in awaySubstitutes"
                                        :key="p.id"
                                        class="px-6 py-4 flex flex-row-reverse items-center justify-between group hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors cursor-pointer"
                                        @click="openPlayerStats(p)"
                                    >
                                        <div
                                            class="flex flex-row-reverse items-center gap-4 opacity-70 group-hover:opacity-100 transition-opacity"
                                        >
                                            <span
                                                class="w-6 text-xs font-bold text-slate-900 dark:text-slate-400 text-right"
                                                >{{ p.number }}</span
                                            >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-gray-50 dark:bg-gray-700 overflow-hidden"
                                            >
                                                <img
                                                    v-if="p.id"
                                                    :src="`https://sports.bzzoiro.com/img/player/${p.id}/`"
                                                    class="w-full h-full object-cover"
                                                />
                                            </div>
                                            <div class="flex flex-col items-end" @click.stop>
                                                <Link
                                                    :href="`/players/${p.id}`"
                                                    class="text-sm font-bold text-gray-950 dark:text-white hover:text-blue-500 transition-colors text-right"
                                                    >{{ p.name }}</Link
                                                >
                                                <span class="text-[9px] font-bold text-slate-500 dark:text-gray-400 uppercase tracking-wider text-right">{{ p.pos }}</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <div
                                                v-if="p.rating && parseFloat(p.rating) > 0"
                                                class="w-8 h-6 flex items-center justify-center rounded-lg text-[10px] font-bold border border-white dark:border-gray-800 shadow-sm"
                                                :class="
                                                    getRatingClass(p.rating)
                                                "
                                            >
                                                {{ p.rating }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Coach -->
                                <div
                                    v-if="game.away_team?.coach"
                                    class="bg-gray-50/50 dark:bg-gray-900/20 px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex flex-row-reverse items-center justify-between"
                                >
                                        <div class="text-right">
                                            <span
                                                class="block text-[8px] font-bold uppercase text-slate-500 dark:text-gray-400"
                                                >Huấn luyện viên</span
                                            >
                                            <span
                                                class="text-sm font-bold text-gray-950 dark:text-white"
                                                >{{
                                                    game.away_team.coach
                                                }}</span
                                            >
                                        </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Missing Players (Injuries & Absences) -->
                    <!-- Missing Players (Injuries & Absences) -->
                    <div v-if="game.injuries && game.injuries.length > 0" class="grid lg:grid-cols-2 gap-8 pt-4">
                        <!-- Home Injuries -->
                        <div class="space-y-4">
                            <h5 class="px-6 text-[9px] font-bold uppercase tracking-widest text-red-500 flex items-center gap-2">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                Vắng mặt: {{ game.home_team.name }}
                            </h5>
                            <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                                <div v-if="game.injuries.filter(i => i.team.id === game.home_team.id).length > 0" class="divide-y divide-gray-50 dark:divide-gray-700">
                                    <div v-for="injury in game.injuries.filter(i => i.team.id === game.home_team.id)" :key="injury.player.id" class="px-6 py-4 flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-gray-50 dark:bg-gray-900 overflow-hidden border border-gray-100 dark:border-gray-800">
                                            <img :src="injury.player.photo" class="w-full h-full object-cover" />
                                        </div>
                                        <div class="flex-1">
                                            <div class="text-sm font-bold text-gray-900 dark:text-white">{{ injury.player.name }}</div>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span :class="[
                                                    'px-1.5 py-0.5 rounded text-[8px] font-bold uppercase tracking-wider',
                                                    injury.type === 'Questionable' ? 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
                                                ]">
                                                    {{ injury.type === 'Questionable' ? 'Bỏ ngỏ' : 'Vắng mặt' }}
                                                </span>
                                                <span class="text-[10px] text-slate-500 dark:text-gray-400 font-medium line-clamp-1">{{ injury.reason }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="p-10 text-center">
                                    <span class="text-xs font-medium text-slate-500 dark:text-gray-400">Không có dữ liệu vắng mặt</span>
                                </div>
                            </div>
                        </div>

                        <!-- Away Injuries -->
                        <div class="space-y-4">
                            <h5 class="px-6 text-[9px] font-bold uppercase tracking-widest text-red-500 flex items-center gap-2 lg:flex-row-reverse">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                Vắng mặt: {{ game.away_team.name }}
                            </h5>
                            <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                                <div v-if="game.injuries.filter(i => i.team.id === game.away_team.id).length > 0" class="divide-y divide-gray-50 dark:divide-gray-700">
                                    <div v-for="injury in game.injuries.filter(i => i.team.id === game.away_team.id)" :key="injury.player.id" class="px-6 py-4 flex flex-row-reverse items-center gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-gray-50 dark:bg-gray-900 overflow-hidden border border-gray-100 dark:border-gray-800">
                                            <img :src="injury.player.photo" class="w-full h-full object-cover" />
                                        </div>
                                        <div class="flex-1 text-right">
                                            <div class="text-sm font-bold text-gray-900 dark:text-white">{{ injury.player.name }}</div>
                                            <div class="flex flex-row-reverse items-center gap-1.5 mt-0.5">
                                                <span :class="[
                                                    'px-1.5 py-0.5 rounded text-[8px] font-bold uppercase tracking-wider',
                                                    injury.type === 'Questionable' ? 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
                                                ]">
                                                    {{ injury.type === 'Questionable' ? 'Bỏ ngỏ' : 'Vắng mặt' }}
                                                </span>
                                                <span class="text-[10px] text-slate-500 dark:text-gray-400 font-medium line-clamp-1">{{ injury.reason }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="p-10 text-center">
                                    <span class="text-xs font-medium text-slate-500 dark:text-gray-400">Không có dữ liệu vắng mặt</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Match Stats Tab -->
                <div v-else-if="activeTab === 'stats'" class="pb-4 space-y-6">
                    <!-- Momentum Chart (BSD v2 Exclusive) -->
                    <MomentumChart v-if="momentum && momentum.length" :momentum="momentum" class="mb-6" />

                    <!-- Shotmap & Heatmap (BSD v2 Exclusive) -->
                    <ShotMap v-if="shotmap && shotmap.length > 0" :shots="shotmap" :home-team="game.home_team" :away-team="game.away_team" />

                    <div v-if="matchStatsGroups.length" class="space-y-6">
                        <div
                            v-for="group in matchStatsGroups"
                            :key="group.group"
                            class="bg-white dark:bg-gray-800/50 rounded-[2rem] p-6 md:p-8 border border-gray-100 dark:border-white/5 shadow-sm dark:shadow-2xl space-y-8"
                        >
                            <div class="flex items-center justify-center gap-4">
                                <div
                                    class="h-px flex-1 bg-gray-200 dark:bg-white/10"
                                ></div>
                                <h4
                                    class="text-[12px] font-bold uppercase tracking-[0.25em] text-slate-900 dark:text-emerald-400 px-4"
                                >
                                    {{ group.group }}
                                </h4>
                                <div
                                    class="h-px flex-1 bg-gray-200 dark:bg-white/10"
                                ></div>
                            </div>

                            <div class="space-y-8">
                                <div
                                    v-for="stat in group.items"
                                    :key="stat.label"
                                    class="space-y-3"
                                >
                                    <div class="flex justify-between items-end">
                                        <div
                                            class="flex items-baseline gap-1.5 w-28"
                                        >
                                            <span
                                                class="text-sm font-bold text-slate-900 dark:text-white leading-none"
                                                >{{ stat.home }}</span
                                            >
                                            <span
                                                v-if="stat.homeDetail"
                                                class="text-[10px] font-bold text-slate-400 dark:text-white/30"
                                                >({{ stat.homeDetail }})</span
                                            >
                                        </div>

                                        <span
                                            class="text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-gray-500 text-center flex-1 pb-1"
                                        >
                                            {{ stat.label }}
                                        </span>

                                        <div
                                            class="flex justify-end items-baseline gap-1.5 w-28"
                                        >
                                            <span
                                                v-if="stat.awayDetail"
                                                class="text-[10px] font-bold text-slate-400 dark:text-white/30"
                                                >({{ stat.awayDetail }})</span
                                            >
                                            <span
                                                class="text-sm font-bold text-slate-900 dark:text-white leading-none"
                                                >{{ stat.away }}</span
                                            >
                                        </div>
                                    </div>

                                    <!-- Premium Progress Bar -->
                                    <div
                                        class="flex gap-2 items-center h-1 px-1"
                                    >
                                        <div
                                            class="flex-1 h-full bg-gray-100 dark:bg-white/5 rounded-full overflow-hidden"
                                        >
                                            <div
                                                class="h-full bg-emerald-500 transition-all duration-1000 float-right"
                                                :style="{
                                                    width:
                                                        stat.homePercent + '%',
                                                }"
                                            ></div>
                                        </div>
                                        <div
                                            class="flex-1 h-full bg-gray-100 dark:bg-white/5 rounded-full overflow-hidden"
                                        >
                                            <div
                                                class="h-full bg-slate-400 dark:bg-white transition-all duration-1000"
                                                :style="{
                                                    width:
                                                        stat.awayPercent + '%',
                                                }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        v-else
                        class="text-center py-24 bg-gray-50/50 dark:bg-gray-800/20 rounded-[2rem] border border-dashed border-gray-200 dark:border-gray-700 shadow-sm"
                    >
                        <div
                            class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-6 text-gray-400"
                        >
                            <svg
                                class="w-8 h-8"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                />
                            </svg>
                        </div>
                        <h4 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-widest mb-2">
                            {{ game.status === 'scheduled' ? 'Trận đấu chưa bắt đầu' : 'Thống kê chưa sẵn sàng' }}
                        </h4>
                        <p class="text-xs text-slate-400 dark:text-gray-500 font-medium max-w-sm mx-auto">
                            {{ game.status === 'scheduled' 
                                ? 'Các chỉ số thống kê chi tiết (sút bóng, kiểm soát bóng, phạt góc...) sẽ được cập nhật trực tiếp khi trận đấu diễn ra.' 
                                : 'Dữ liệu thống kê đang được hệ thống tự động xử lý và đồng bộ.' 
                            }}
                        </p>
                    </div>
                </div>

                <!-- H2H Tab -->
                <div v-else-if="activeTab === 'h2h'" class="pb-4 space-y-6">
                    <div
                        v-if="h2hMatches && h2hMatches.length"
                        class="space-y-8"
                    >
                        <!-- Premium H2H Summary Card -->
                        <!-- Compact H2H Summary Card -->
                        <div
                            class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-white/5 shadow-sm"
                        >
                            <div class="flex items-center justify-between mb-5">
                                <h3 class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Thống kê đối đầu</h3>
                                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-500">{{ h2hMatches.length }} trận gần nhất</span>
                            </div>

                            <div class="space-y-5">
                                <!-- Multi-segment Ratio Bar -->
                                <div class="flex h-2.5 rounded-full overflow-hidden bg-gray-100 dark:bg-white/5 shadow-inner">
                                    <div 
                                        class="bg-emerald-500 h-full transition-all duration-1000 ease-out"
                                        :style="{ width: (h2hStats.homeWins / h2hMatches.length) * 100 + '%' }"
                                    ></div>
                                    <div 
                                        class="bg-gray-300 dark:bg-gray-600 h-full transition-all duration-1000 ease-out"
                                        :style="{ width: (h2hStats.draws / h2hMatches.length) * 100 + '%' }"
                                    ></div>
                                    <div 
                                        class="bg-blue-500 h-full transition-all duration-1000 ease-out"
                                        :style="{ width: (h2hStats.awayWins / h2hMatches.length) * 100 + '%' }"
                                    ></div>
                                </div>

                                <!-- Details Row -->
                                <div class="grid grid-cols-3 items-center">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-black text-gray-900 dark:text-white tabular-nums">{{ h2hStats.homeWins }}</span>
                                        <span class="text-[8px] font-bold text-emerald-500 uppercase tracking-tighter truncate">{{ game.home_team?.name }} thắng</span>
                                    </div>
                                    
                                    <div class="flex flex-col items-center">
                                        <span class="text-sm font-black text-gray-900 dark:text-white tabular-nums">{{ h2hStats.draws }}</span>
                                        <span class="text-[8px] font-bold text-slate-400 uppercase tracking-tighter">Hòa</span>
                                    </div>

                                    <div class="flex flex-col items-end">
                                        <span class="text-sm font-black text-gray-900 dark:text-white tabular-nums">{{ h2hStats.awayWins }}</span>
                                        <span class="text-[8px] font-bold text-blue-500 uppercase tracking-tighter truncate text-right">{{ game.away_team?.name }} thắng</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Match History List -->
                        <div class="space-y-3">
                            <div
                                v-for="m in sortedH2H"
                                :key="m.fixture.id"
                                class="bg-white dark:bg-gray-800/50 rounded-2xl p-4 flex items-center justify-between border border-gray-50 dark:border-white/5 hover:border-emerald-500/30 transition-all group"
                            >
                                <div class="flex flex-col w-24">
                                    <span
                                        class="text-[10px] font-bold text-slate-400 tabular-nums"
                                    >
                                        {{
                                            m.fixture?.date && dayjs(m.fixture.date).isValid()
                                                ? dayjs(m.fixture.date).format("DD/MM/YYYY")
                                                : "N/A"
                                        }}
                                    </span>
                                    <span
                                        class="text-[9px] font-bold text-emerald-500 uppercase truncate"
                                    >
                                        {{ m.league?.name }}
                                    </span>
                                </div>

                                <div
                                    class="flex-1 flex items-center justify-center gap-4"
                                >
                                    <div
                                        class="flex items-center gap-3 flex-1 justify-end"
                                    >
                                        <span
                                            class="text-sm font-bold text-slate-900 dark:text-white truncate"
                                            :class="{
                                                'text-emerald-600':
                                                    m.goals?.home >
                                                    m.goals?.away,
                                            }"
                                        >
                                            {{ m.teams?.home?.name }}
                                        </span>
                                        <img
                                            v-if="m.teams?.home?.logo_url"
                                            :src="m.teams.home.logo_url"
                                            class="w-6 h-6 object-contain"
                                        />
                                    </div>

                                    <div
                                        class="flex items-center gap-1 px-3 py-1 bg-slate-900 text-white rounded-xl font-bold text-sm tabular-nums shadow-lg"
                                    >
                                        <span>{{ m.goals?.home }}</span>
                                        <span class="text-white/30">-</span>
                                        <span>{{ m.goals?.away }}</span>
                                    </div>

                                    <div
                                        class="flex items-center gap-3 flex-1 justify-start"
                                    >
                                        <img
                                            v-if="m.teams?.away?.logo_url"
                                            :src="m.teams.away.logo_url"
                                            class="w-6 h-6 object-contain"
                                        />
                                        <span
                                            class="text-sm font-bold text-slate-900 dark:text-white truncate"
                                            :class="{
                                                'text-emerald-600':
                                                    m.goals?.away >
                                                    m.goals?.home,
                                            }"
                                        >
                                            {{ m.teams?.away?.name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        v-else
                        class="text-center py-24 bg-gray-50/50 dark:bg-gray-800/20 rounded-3xl border border-dashed border-gray-200 dark:border-gray-700"
                    >
                        <div
                            class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-6 text-gray-400"
                        >
                            <svg
                                class="w-8 h-8"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                                />
                            </svg>
                        </div>
                        <p
                            class="text-sm font-bold text-slate-500 dark:text-gray-400 uppercase tracking-widest"
                        >
                            Chưa có dữ liệu lịch sử đối đầu giữa hai đội
                        </p>
                    </div>
                </div>

                <!-- Timeline Tab (Premium Style) -->
                <div v-else-if="activeTab === 'timeline'" class="pb-4">
                    <div
                        v-if="processedEvents.some(e => !e.isMarker)"
                        class="bg-white dark:bg-[#0a1921] rounded-[2rem] shadow-xl dark:shadow-2xl overflow-hidden min-h-[400px] border border-gray-100 dark:border-white/5"
                    >
                        <div v-for="(event, idx) in processedEvents" :key="idx">
                            <!-- Period Header (1st Half, 2nd Half) -->
                            <div
                                v-if="event.isMarker"
                                class="bg-gray-50 dark:bg-white/5 px-6 py-2.5 flex justify-between items-center border-b border-gray-100 dark:border-white/5 first:border-t-0"
                            >
                                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-gray-400">{{ event.label }}</span>
                                <span v-if="event.score" class="text-[11px] font-bold text-slate-900 dark:text-white tabular-nums tracking-wider">{{ event.score }}</span>
                            </div>

                            <!-- Regular Event Row -->
                            <div v-else class="px-6 py-4 border-b border-gray-50 dark:border-transparent last:border-0 transition-colors">
                                <div class="flex items-center w-full">
                                    <!-- HOME TEAM SIDE -->
                                    <div class="w-1/2 flex items-center gap-4">
                                        <template v-if="event.side === 'home'">
                                            <span class="text-[11px] font-bold text-slate-400 dark:text-white/40 w-8 tabular-nums">{{ event.displayTime }}'</span>
                                            
                                            <div class="flex items-center gap-3">
                                                <!-- Icon -->
                                                <!-- Icon with Tooltip -->
                                                <div class="relative group shrink-0">
                                                    <div v-html="getSofaIcon(event.type)"></div>
                                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-1 bg-slate-900 text-white text-[9px] font-black uppercase tracking-widest rounded shadow-xl opacity-0 group-hover:opacity-100 transition-all duration-200 transform translate-y-1 group-hover:translate-y-0 pointer-events-none z-50 whitespace-nowrap">
                                                        {{ translateDetail(event.type) }}
                                                        <div class="absolute top-full left-1/2 -translate-x-1/2 border-[4px] border-transparent border-t-slate-900"></div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Score (if goal) -->
                                                <div v-if="event.eventScore" class="px-2 py-0.5 bg-slate-900 dark:bg-white/10 rounded text-[10px] font-black text-white tabular-nums border border-slate-800 dark:border-white/10">
                                                    {{ event.eventScore }}
                                                </div>

                                                <!-- Player Info -->
                                                <div class="flex flex-wrap items-center gap-x-2">
                                                    <Link 
                                                        v-if="event.playerId" 
                                                        :href="'/players/' + event.playerId"
                                                        class="text-[13px] font-bold text-slate-900 dark:text-white hover:text-emerald-500 transition-colors"
                                                    >
                                                        {{ event.player }}
                                                    </Link>
                                                    <span v-else class="text-[13px] font-bold text-slate-900 dark:text-white">{{ event.player }}</span>
                                                    
                                                    <!-- Detail (Assist, Card reason, Out Player) -->
                                                    <span v-if="event.isSubstitution" class="text-[11px] font-medium text-slate-400 dark:text-white/40 truncate">
                                                        {{ event.playerOut }}
                                                    </span>
                                                    <span v-else-if="event.playerOut || event.detail" class="text-[11px] font-medium text-slate-400 dark:text-white/40 italic">
                                                        ({{ event.playerOut || translateDetail(event.detail) }})
                                                    </span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- AWAY TEAM SIDE -->
                                    <div class="w-1/2 flex flex-row-reverse items-center gap-4 text-right">
                                        <template v-if="event.side === 'away'">
                                            <span class="text-[11px] font-bold text-slate-400 dark:text-white/40 w-8 tabular-nums text-right">{{ event.displayTime }}'</span>
                                            
                                            <div class="flex flex-row-reverse items-center gap-3">
                                                <!-- Icon -->
                                                <!-- Icon with Tooltip -->
                                                <div class="relative group shrink-0">
                                                    <div v-html="getSofaIcon(event.type)"></div>
                                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-1 bg-slate-900 text-white text-[9px] font-black uppercase tracking-widest rounded shadow-xl opacity-0 group-hover:opacity-100 transition-all duration-200 transform translate-y-1 group-hover:translate-y-0 pointer-events-none z-50 whitespace-nowrap">
                                                        {{ translateDetail(event.type) }}
                                                        <div class="absolute top-full left-1/2 -translate-x-1/2 border-[4px] border-transparent border-t-slate-900"></div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Score (if goal) -->
                                                <div v-if="event.eventScore" class="px-2 py-0.5 bg-slate-900 dark:bg-white/10 rounded text-[10px] font-black text-white tabular-nums border border-slate-800 dark:border-white/10">
                                                    {{ event.eventScore }}
                                                </div>

                                                <!-- Player Info -->
                                                <div class="flex flex-row-reverse flex-wrap items-center gap-x-2">
                                                    <Link 
                                                        v-if="event.playerId" 
                                                        :href="'/players/' + event.playerId"
                                                        class="text-[13px] font-bold text-slate-900 dark:text-white hover:text-emerald-500 transition-colors"
                                                    >
                                                        {{ event.player }}
                                                    </Link>
                                                    <span v-else class="text-[13px] font-bold text-slate-900 dark:text-white">{{ event.player }}</span>
                                                    
                                                    <!-- Detail (Assist, Card reason, Out Player) -->
                                                    <span v-if="event.isSubstitution" class="text-[11px] font-medium text-slate-400 dark:text-white/40 truncate">
                                                        {{ event.playerOut }}
                                                    </span>
                                                    <span v-else-if="event.playerOut || event.detail" class="text-[11px] font-medium text-slate-400 dark:text-white/40 italic">
                                                        ({{ event.playerOut || translateDetail(event.detail) }})
                                                    </span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Match End Marker -->
                        <div class="bg-gray-50 dark:bg-black/40 px-6 py-6 border-t border-gray-100 dark:border-white/5 flex flex-col items-center gap-2">
                            <span class="text-[9px] font-bold uppercase tracking-widest text-slate-500 dark:text-gray-400">Kết thúc trận đấu</span>
                            <div class="flex items-center gap-6">
                                <span class="text-2xl font-black text-slate-900 dark:text-white tabular-nums tracking-tighter">
                                    {{ game.home_score }} <span class="text-slate-200 dark:text-white/20 mx-1">:</span> {{ game.away_score }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div
                        v-else
                        class="text-center py-24 bg-gray-50/50 dark:bg-gray-800/20 rounded-[2rem] border border-dashed border-gray-200 dark:border-gray-700 shadow-sm"
                    >
                        <div
                            class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-6 text-gray-400"
                        >
                            <svg
                                class="w-8 h-8"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <h4 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-widest mb-2">
                            {{ game.status === 'scheduled' ? 'Trận đấu chưa bắt đầu' : 'Chưa có diễn biến trận đấu' }}
                        </h4>
                        <p class="text-xs text-slate-400 dark:text-gray-500 font-medium max-w-sm mx-auto">
                            {{ game.status === 'scheduled' 
                                ? 'Diễn biến trận đấu (bàn thắng, thẻ phạt, thay người...) sẽ được cập nhật trực tiếp tại đây khi trận đấu bắt đầu.' 
                                : 'Diễn biến trận đấu hiện chưa có dữ liệu. Hệ thống đang tự động đồng bộ khi có diễn biến mới nhất.' 
                            }}
                        </p>
                    </div>
                </div>





                <!-- Standings Tab -->
                <div v-else-if="activeTab === 'standings'" class="pb-4">
                    <div v-if="isValidStandings" class="space-y-6">
                        <div class="flex items-center justify-between px-4">
                            <h3 class="text-[10px] font-bold uppercase tracking-widest text-slate-500">
                                Bảng xếp hạng {{ game.league?.name }}
                            </h3>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Mùa giải {{ formatSeason(game.season, game.league?.country_name) }}</span>
                        </div>
                        <StandingTable :standings="standings" :league-id="game.league?.id" :season="game.season" />
                    </div>
                    <div v-else class="text-center py-24 bg-gray-50/50 dark:bg-gray-800/20 rounded-[2rem] border border-dashed border-gray-200 dark:border-gray-700 shadow-sm">
                        <div
                            class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-900 flex items-center justify-center mx-auto mb-6 text-gray-400"
                        >
                            <svg
                                class="w-8 h-8"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                        <h4 class="text-sm font-bold text-slate-800 dark:text-white uppercase tracking-widest mb-2">Bảng xếp hạng chưa sẵn sàng</h4>
                        <p class="text-xs text-slate-400 dark:text-gray-500 font-medium max-w-sm mx-auto">Chưa có dữ liệu bảng xếp hạng cho mùa giải này hoặc giải đấu không sử dụng thể thức đấu vòng tròn tính điểm.</p>
                    </div>
                </div>

                <!-- Analysis Tab (API Predictions) -->
                <div v-else-if="activeTab === 'analysis'" class="pb-4 space-y-8">
                    <!-- Tactical AI Insights (Groq) -->
                    <div v-if="aiInsights" class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-white/5 shadow-sm relative overflow-hidden">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <h3 class="text-[10px] font-bold uppercase tracking-widest text-slate-500">AI Tactical Analysis</h3>
                            </div>
                        </div>

                        <div class="prose dark:prose-invert prose-sm max-w-none">
                            <div class="whitespace-pre-wrap text-slate-600 dark:text-slate-300 leading-relaxed font-medium text-[13px]" v-html="aiInsights.replace(/\n/g, '<br>')"></div>
                        </div>
                    </div>

                    <div v-if="prediction && hasPredictionData" class="space-y-8 animate-fade-in text-slate-900 dark:text-white">
                        <!-- AI Match Outcome Probability (1X2) -->
                        <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-white/5 shadow-sm relative overflow-hidden">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h3 class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Xác suất kết quả</h3>
                                </div>
                            </div>

                            <!-- Segmented progress bar -->
                            <div class="h-6 bg-slate-100 dark:bg-black/30 rounded-xl overflow-hidden flex mb-6 p-0.5 border border-slate-200/50 dark:border-white/5 shadow-inner">
                                <div 
                                    class="h-full bg-emerald-500 transition-all duration-1000 ease-out flex items-center justify-center text-[10px] font-bold text-white rounded-lg shadow-sm shrink-0"
                                    :style="{ width: prediction.predictions?.percent?.home }"
                                    v-if="prediction.predictions?.percent?.home && parseFloat(prediction.predictions.percent.home) > 0"
                                    :key="'home-' + prediction.predictions?.percent?.home"
                                >
                                    <span>{{ prediction.predictions.percent.home }}</span>
                                </div>
                                <div 
                                    class="h-full bg-slate-400 transition-all duration-1000 ease-out flex items-center justify-center text-[10px] font-bold text-white rounded-lg shadow-sm shrink-0 mx-0.5"
                                    :style="{ width: prediction.predictions?.percent?.draw }"
                                    v-if="prediction.predictions?.percent?.draw && parseFloat(prediction.predictions.percent.draw) > 0"
                                    :key="'draw-' + prediction.predictions?.percent?.draw"
                                >
                                    <span>{{ prediction.predictions.percent.draw }}</span>
                                </div>
                                <div 
                                    class="h-full bg-blue-500 transition-all duration-1000 ease-out flex items-center justify-center text-[10px] font-bold text-white rounded-lg shadow-sm shrink-0"
                                    :style="{ width: prediction.predictions?.percent?.away }"
                                    v-if="prediction.predictions?.percent?.away && parseFloat(prediction.predictions.percent.away) > 0"
                                    :key="'away-' + prediction.predictions?.percent?.away"
                                >
                                    <span>{{ prediction.predictions.percent.away }}</span>
                                </div>
                            </div>

                            <!-- Team Details underneath -->
                            <div class="grid grid-cols-3 gap-4">
                                <div class="flex items-center gap-3 bg-white dark:bg-gray-800/50 p-3 rounded-2xl border border-gray-50 dark:border-white/5 shadow-sm">
                                    <img v-if="game.home_team?.logo_url" :src="game.home_team.logo_url" class="w-7 h-7 object-contain shrink-0" />
                                    <div class="flex flex-col truncate">
                                        <span class="text-[9px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Chủ nhà</span>
                                        <span class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ game.home_team?.name }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-center justify-center bg-white dark:bg-gray-800/50 p-2 rounded-2xl border border-dashed border-gray-200 dark:border-white/5">
                                    <span class="text-[9px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest mb-0.5">Hòa</span>
                                    <span class="text-sm font-bold text-slate-900 dark:text-white">Xác suất {{ prediction.predictions?.percent?.draw || '0%' }}</span>
                                </div>
                                <div class="flex items-center justify-end gap-3 bg-white dark:bg-gray-800/50 p-3 rounded-2xl border border-gray-50 dark:border-white/5 shadow-sm">
                                    <div class="flex flex-col items-end truncate text-right">
                                        <span class="text-[9px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Khách</span>
                                        <span class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ game.away_team?.name }}</span>
                                    </div>
                                    <img v-if="game.away_team?.logo_url" :src="game.away_team.logo_url" class="w-7 h-7 object-contain shrink-0" />
                                </div>
                            </div>
                        </div>

                        <!-- Core AI Forecast Grid (Minimalist & consistent) -->
                        <div class="grid md:grid-cols-3 gap-6">
                            <!-- Card 1: Scoreboard Capsule for Most Likely Score & xG -->
                            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-white/5 shadow-sm flex flex-col justify-between">
                                <div class="flex items-center gap-2 mb-6">
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Tỉ số dự kiến</span>
                                </div>

                                <div class="my-auto py-2 flex flex-col items-center">
                                    <div class="flex items-center gap-4 mb-2">
                                        <img v-if="game.home_team?.logo_url" :src="game.home_team.logo_url" class="w-6 h-6 object-contain shrink-0" />
                                        <span class="text-2xl font-bold tracking-widest text-slate-900 dark:text-white tabular-nums bg-slate-100 dark:bg-gray-900/50 px-5 py-1.5 rounded-xl border border-slate-200/50 dark:border-white/5">
                                            {{ prediction?.most_likely_score || 'N/A' }}
                                        </span>
                                        <img v-if="game.away_team?.logo_url" :src="game.away_team.logo_url" class="w-6 h-6 object-contain shrink-0" />
                                    </div>
                                    <span class="text-[9px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-widest">Tỉ số khả thi nhất</span>
                                </div>

                                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-white/5">
                                    <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">
                                        <span>Bàn thắng kỳ vọng (xG)</span>
                                        <span class="tabular-nums font-bold text-slate-900 dark:text-white text-sm normal-case tracking-normal shrink-0">
                                            {{ prediction.expected_goals?.home || '0.00' }} - {{ prediction.expected_goals?.away || '0.00' }}
                                        </span>
                                    </div>
                                    <!-- Dynamic xG progress track -->
                                    <div class="flex gap-2 items-center h-1 px-1">
                                        <div class="flex-1 h-full bg-gray-100 dark:bg-white/5 rounded-full overflow-hidden">
                                            <div 
                                                class="h-full bg-emerald-500 transition-all duration-1000 float-right"
                                                :style="{ 
                                                    width: (prediction.expected_goals?.home || prediction.expected_goals?.away) 
                                                        ? (parseFloat(prediction.expected_goals?.home || 0) / (parseFloat(prediction.expected_goals?.home || 0) + parseFloat(prediction.expected_goals?.away || 0)) * 100) + '%'
                                                        : '50%'
                                                }"
                                            ></div>
                                        </div>
                                        <div class="flex-1 h-full bg-gray-100 dark:bg-white/5 rounded-full overflow-hidden">
                                            <div 
                                                class="h-full bg-blue-500 transition-all duration-1000"
                                                :style="{ 
                                                    width: (prediction.expected_goals?.home || prediction.expected_goals?.away) 
                                                        ? (parseFloat(prediction.expected_goals?.away || 0) / (parseFloat(prediction.expected_goals?.home || 0) + parseFloat(prediction.expected_goals?.away || 0)) * 100) + '%'
                                                        : '50%'
                                                }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card 2: AI Favorite Shield -->
                            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-white/5 shadow-sm flex flex-col justify-between">
                                <div class="flex items-center gap-2 mb-6">
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Đội cửa trên dự báo</span>
                                </div>

                                <div class="my-auto py-2 flex flex-col items-center text-center">
                                    <template v-if="prediction?.favorite === 'home'">
                                        <div class="w-12 h-12 bg-gray-50/50 dark:bg-gray-900 rounded-full border border-gray-100 dark:border-white/5 flex items-center justify-center mb-2 p-1.5 shadow-sm">
                                            <img v-if="game.home_team?.logo_url" :src="game.home_team.logo_url" class="w-full h-full object-contain shrink-0" />
                                        </div>
                                        <span class="text-sm font-bold text-slate-900 dark:text-white line-clamp-1">{{ game.home_team?.name }}</span>
                                    </template>
                                    <template v-else-if="prediction?.favorite === 'away'">
                                        <div class="w-12 h-12 bg-gray-50/50 dark:bg-gray-900 rounded-full border border-gray-100 dark:border-white/5 flex items-center justify-center mb-2 p-1.5 shadow-sm">
                                            <img v-if="game.away_team?.logo_url" :src="game.away_team.logo_url" class="w-full h-full object-contain shrink-0" />
                                        </div>
                                        <span class="text-sm font-bold text-slate-900 dark:text-white line-clamp-1">{{ game.away_team?.name }}</span>
                                    </template>
                                    <template v-else>
                                        <div class="w-10 h-10 rounded-full bg-gray-50/50 dark:bg-gray-900 flex items-center justify-center mb-2 text-slate-400">
                                            —
                                        </div>
                                        <span class="text-sm font-bold text-slate-900 dark:text-white">Cân bằng thế trận</span>
                                    </template>
                                </div>

                                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-white/5 flex items-center justify-between text-[10px] font-bold uppercase tracking-widest text-slate-500">
                                    <span>Xác suất chiến thắng</span>
                                    <span class="tabular-nums text-slate-900 dark:text-white text-sm font-bold normal-case tracking-normal shrink-0">
                                        {{ prediction?.favorite_prob || 'N/A' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Card 3: Goals Index (Minimalist linear progress bars instead of SVG circular gauges) -->
                            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-white/5 shadow-sm flex flex-col justify-between">
                                <div class="flex items-center gap-2 mb-6">
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Chỉ số bàn thắng</span>
                                </div>

                                <div class="space-y-3.5 my-auto">
                                    <!-- Goal Row 1: Over 1.5 -->
                                    <div class="space-y-1.5">
                                        <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-widest text-slate-500">
                                            <span>Tài 1.5 bàn</span>
                                            <span class="tabular-nums font-bold text-slate-900 dark:text-white text-sm normal-case tracking-normal shrink-0">{{ prediction.prob_over?.over_15 || '—' }}</span>
                                        </div>
                                        <div class="h-1 bg-gray-100 dark:bg-white/5 rounded-full overflow-hidden">
                                            <div 
                                                class="h-full bg-emerald-500 transition-all duration-1000"
                                                :style="{ width: prediction.prob_over?.over_15 }"
                                            ></div>
                                        </div>
                                    </div>

                                    <!-- Goal Row 2: Over 2.5 -->
                                    <div class="space-y-1.5">
                                        <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-widest text-slate-500">
                                            <span>Tài 2.5 bàn</span>
                                            <span class="tabular-nums font-bold text-slate-900 dark:text-white text-sm normal-case tracking-normal shrink-0">{{ prediction.prob_over?.over_25 || '—' }}</span>
                                        </div>
                                        <div class="h-1 bg-gray-100 dark:bg-white/5 rounded-full overflow-hidden">
                                            <div 
                                                class="h-full bg-emerald-500 transition-all duration-1000"
                                                :style="{ width: prediction.prob_over?.over_25 }"
                                            ></div>
                                        </div>
                                    </div>

                                    <!-- Goal Row 3: BTTS -->
                                                                         <div class="space-y-1.5">
                                        <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-widest text-slate-500">
                                            <span>Tài 3.5 bàn (Over 3.5)</span>
                                            <span class="tabular-nums font-bold text-slate-900 dark:text-white text-sm normal-case tracking-normal shrink-0">{{ prediction?.prob_btts || '—' }}</span>
                                        </div>
                                        <div class="h-1 bg-gray-100 dark:bg-white/5 rounded-full overflow-hidden">
                                            <div 
                                                class="h-full bg-emerald-500 transition-all duration-1000"
                                                :style="{ width: prediction.prob_over?.over_35 }"
                                            ></div>
                                        </div>
                                    </div>

                                    <!-- Goal Row 4: BTTS -->

  
                                    <div class="space-y-1.5">
                                        <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-widest text-slate-500">
                                            <span>Hai đội ghi bàn (BTTS)</span>
                                            <span class="tabular-nums font-bold text-slate-900 dark:text-white text-sm normal-case tracking-normal shrink-0">{{ prediction?.prob_btts || '—' }}</span>
                                        </div>
                                        <div class="h-1 bg-gray-100 dark:bg-white/5 rounded-full overflow-hidden">
                                            <div 
                                                class="h-full bg-emerald-500 transition-all duration-1000"
                                                :style="{ width: prediction?.prob_btts }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- AI Tactical Alerts / Betting Recommendations (Minimalist clean look) -->
                        <div 
                            v-if="prediction?.recommendations && Object.values(prediction.recommendations).some(Boolean)"
                            class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-white/5 shadow-sm"
                        >
                            <div class="mb-6">
                                <h3 class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Khuyến nghị chiến thuật AI</h3>
                                <p class="text-[9px] font-bold text-slate-400 dark:text-gray-500 uppercase tracking-wider mt-1">Đề xuất vị thế tối ưu dựa trên dữ liệu định lượng</p>
                            </div>

                            <div class="grid md:grid-cols-2 gap-4">
                                <div 
                                    v-if="prediction.recommendations?.bet_favorite" 
                                    class="bg-white dark:bg-gray-800/50 rounded-2xl p-4 border border-gray-50 dark:border-white/5 hover:border-emerald-500/30 transition-all group"
                                >
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <p class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Tín hiệu: Tin cậy Đội cửa trên</p>
                                    </div>
                                    <p class="text-[11px] font-medium text-slate-500 dark:text-gray-400 pl-3 leading-relaxed">Mô hình định lượng xác thực đội bóng cửa trên sở hữu sức mạnh áp đảo đáng kể ở trận cầu này.</p>
                                </div>
                                <div 
                                    v-if="prediction.recommendations?.winner" 
                                    class="bg-white dark:bg-gray-800/50 rounded-2xl p-4 border border-gray-50 dark:border-white/5 hover:border-emerald-500/30 transition-all group"
                                >
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <p class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Tín hiệu: Phân định Thắng Thua</p>
                                    </div>
                                    <p class="text-[11px] font-medium text-slate-500 dark:text-gray-400 pl-3 leading-relaxed">Xác suất hòa cực thấp, kịch bản hai đội đá thực dụng và quyết liệt hướng tới 3 điểm trọn vẹn.</p>
                                </div>

                                <div 
                                    v-if="prediction.recommendations?.over_15" 
                                    class="bg-white dark:bg-gray-800/50 rounded-2xl p-4 border border-gray-50 dark:border-white/5 hover:border-emerald-500/30 transition-all group"
                                >
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <p class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Tín hiệu: Tài 1.5 Bàn Thắng</p>
                                    </div>
                                    <p class="text-[11px] font-medium text-slate-500 dark:text-gray-400 pl-3 leading-relaxed">Dự toán kịch bản ghi nhận tối thiểu 2 bàn thắng sở hữu tỷ lệ bùng nổ vô cùng cao.</p>
                                </div>

                                <div 
                                    v-if="prediction.recommendations?.over_25" 
                                    class="bg-white dark:bg-gray-800/50 rounded-2xl p-4 border border-gray-50 dark:border-white/5 hover:border-emerald-500/30 transition-all group"
                                >
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <p class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Tín hiệu: Tài 2.5 Bàn Thắng (Mở)</p>
                                    </div>
                                    <p class="text-[11px] font-medium text-slate-500 dark:text-gray-400 pl-3 leading-relaxed">Lối đá cởi mở từ cả hai CLB được dự đoán sẽ tạo nên bữa tiệc tấn công sôi động nhiều bàn thắng.</p>
                                </div>

                                <div 
                                    v-if="prediction.recommendations?.over_35" 
                                    class="bg-white dark:bg-gray-800/50 rounded-2xl p-4 border border-gray-50 dark:border-white/5 hover:border-emerald-500/30 transition-all group"
                                >
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <p class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Tín hiệu: Tài 3.5 Bàn</p>
                                    </div>
                                    <p class="text-[11px] font-medium text-slate-500 dark:text-gray-400 pl-3 leading-relaxed">Kỳ vọng trận cầu rượt đuổi tỷ số kịch tính vượt mốc 4 bàn thắng có xác suất đáng lưu tâm.</p>
                                </div>

                                <div 
                                    v-if="prediction.recommendations?.btts" 
                                    class="bg-white dark:bg-gray-800/50 rounded-2xl p-4 border border-gray-50 dark:border-white/5 hover:border-emerald-500/30 transition-all group"
                                >
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <p class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Tín hiệu: Cả Hai Đội Cùng Ghi Bàn (BTTS)</p>
                                    </div>
                                    <p class="text-[11px] font-medium text-slate-500 dark:text-gray-400 pl-3 leading-relaxed">Hàng thủ mỏng manh và hàng công hiệu quả của cả hai bên tạo ra niềm tin đều sẽ lập công.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-20 text-center">
                        <div v-if="isRefreshing" class="flex flex-col items-center gap-3">
                            <div class="w-12 h-12 rounded-full border-4 border-emerald-500/20 border-t-emerald-500 animate-spin"></div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-emerald-500">Đang tự động cập nhật dữ liệu...</span>
                        </div>
                        <div v-else class="flex flex-col items-center">
                            <div class="w-16 h-16 rounded-full bg-gray-50 dark:bg-gray-900 flex items-center justify-center mx-auto mb-6 text-gray-400">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Phân tích AI chưa sẵn sàng</h3>
                            <p class="text-gray-400 text-sm mt-2 max-w-xs mx-auto leading-relaxed">
                                Chưa có dữ liệu dự báo phân tích AI cho trận đấu này từ hệ thống hoặc trận đấu đã diễn ra quá lâu.
                            </p>
                        </div>
                        </div>
                    </div>
                </div>
                </div> 
            </div>
            <LeagueSidebar />
        </div>

        <!-- Sync Background Toast — hiện khi job đang chạy ngầm -->
        <Teleport to="body">
            <Transition name="toast-slide">
                <div
                    v-if="syncToastVisible"
                    class="fixed bottom-5 right-5 z-[9999] flex items-center gap-2.5 px-4 py-2.5 bg-gray-950 dark:bg-white text-white dark:text-gray-950 rounded-2xl shadow-2xl border border-white/10 dark:border-gray-200"
                >
                    <div class="w-3.5 h-3.5 rounded-full border-2 border-emerald-400 border-t-transparent animate-spin flex-shrink-0"></div>
                    <span class="text-[10px] font-bold uppercase tracking-widest whitespace-nowrap">Đang cập nhật dữ liệu...</span>
                </div>
            </Transition>
        </Teleport>
    </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import MainLayout from "../../Layouts/MainLayout.vue";
import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import StandingTable from "../../Components/StandingTable.vue";
import LeagueSidebar from "../../Components/LeagueSidebar.vue";

import MomentumChart from "../../Components/MomentumChart.vue";
import ShotMap from "../../Components/ShotMap.vue";
import axios from "axios";

dayjs.extend(utc);

const formatDateTime = (val) => {
    if (!val) return "N/A";
    return dayjs.utc(val).local().format("DD/MM/YYYY HH:mm");
};

const props = defineProps({
    game:            { type: Object,  required: true },
    h2hMatches:      { type: Array,   default: () => [] },
    standings:       { type: Array,   default: () => [] },
    aiInsights:      { type: String,  default: null },
    momentum:        { type: Array,   default: () => [] },
    shotmap:         { type: Array,   default: () => [] },
    activeTab:       { type: String,  default: 'lineups' },
    // Flags từ server — cho biết có cần polling không
    syncDispatched:  { type: Boolean, default: false },
    isSyncing:       { type: Boolean, default: false },
    lastHydratedAt:  { type: String,  default: null },
});

const isRecentlyFinished = computed(() => {
    if (props.game.status !== 'finished' || !props.game.match_datetime) return false;
    const matchTime = dayjs.utc(props.game.match_datetime);
    const hoursSinceMatch = dayjs.utc().diff(matchTime, 'hour');
    return hoursSinceMatch >= 0 && hoursSinceMatch < 24;
});

const isMissingPlayerRatings = computed(() => {
    if (props.game.status !== 'finished') return false;
    if (!props.game.player_stats || props.game.player_stats.length === 0) return true;
    return props.game.player_stats.every(p => !p.rating || parseFloat(p.rating) <= 0);
});

const activeTab = ref(props.activeTab);
const isLoadingTab = ref(false);
const lineupView = ref("start"); // 'start' or 'end'
const showPitchModal = ref(false);
const selectedPlayerStats = ref(null);
const showPlayerStatsModal = ref(false);

const openPlayerStats = (player) => {
    if (!props.game.player_stats) return;
    const fullStat = props.game.player_stats.find(ps => ps.id == player.id);
    selectedPlayerStats.value = {
        id: player.id,
        name: player.name,
        number: player.number,
        pos: player.pos || 'Unknown',
        rating: player.rating || fullStat?.rating || '—',
        minutes_played: fullStat?.minutes_played ?? 0,
        goals: fullStat?.goals ?? 0,
        assists: fullStat?.assists ?? 0,
        expected_goals: fullStat?.expected_goals ?? null,
        expected_assists: fullStat?.expected_assists ?? null,
        total_shots: fullStat?.total_shots ?? 0,
        shots_on_target: fullStat?.shots_on_target ?? 0,
        total_pass: fullStat?.total_pass ?? 0,
        accurate_pass: fullStat?.accurate_pass ?? 0,
        key_pass: fullStat?.key_pass ?? 0,
        total_tackle: fullStat?.total_tackle ?? 0,
        interception: fullStat?.interception ?? 0,
        yellow_card: fullStat?.yellow_card ?? 0,
        red_card: fullStat?.red_card ?? 0,
        saves: fullStat?.saves ?? 0,
    };
    showPlayerStatsModal.value = true;
};

const tabs = [
    { id: 'lineups',   label: 'Đội hình' },
    { id: 'stats',     label: 'Thống kê' },
    { id: 'h2h',       label: 'Đối đầu' },
    { id: 'standings', label: 'Bảng xếp hạng' },
    { id: 'timeline',  label: 'Diễn biến' },
    { id: 'analysis',  label: 'Phân tích AI' },
];

watch(() => props.activeTab, (newVal) => {
    activeTab.value = newVal;
});

const setActiveTab = (tabId) => {
    if (tabId === activeTab.value) return;
    isLoadingTab.value = true;
    router.get(
        `/matches/${props.game.id}`,
        { tab: tabId },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['game', 'h2hMatches', 'standings', 'aiInsights', 'momentum', 'shotmap',
                   'activeTab', 'syncDispatched', 'isSyncing', 'lastHydratedAt'],
            onFinish: () => {
                isLoadingTab.value = false;
                activeTab.value = tabId;
            },
        }
    );
};

// --- SMART POLLING STATE ---
const isRefreshing = ref(false);
const isSyncingBg  = ref(props.isSyncing || props.syncDispatched);
const syncToastVisible = ref(false); // nhỏ toast "đang cập nhật"

// Polling interval ref — nhớ clear khi unmount / chuyển tab
let statusPollInterval  = null; // poll /api/matches/{id}/sync-status
let liveRefreshInterval = null; // refresh data khi trận live

/**
 * Dừng tất cả interval — gọi khi unmount hoặc không cần nữa
 */
const stopAllPolling = () => {
    if (statusPollInterval)  { clearInterval(statusPollInterval);  statusPollInterval  = null; }
    if (liveRefreshInterval) { clearInterval(liveRefreshInterval); liveRefreshInterval = null; }
};

/**
 * Reload Inertia partial — chỉ các trường cần thiết, không reload toàn trang
 */
const reloadMatchData = () => {
    if (!window.location.pathname.includes(`/matches/${props.game.id}`)) {
        stopAllPolling();
        return;
    }
    isRefreshing.value = true;
    router.reload({
        preserveScroll: true,
        preserveState: true,
        only: ['game', 'shotmap', 'momentum', 'aiInsights', 'h2hMatches', 'standings',
               'syncDispatched', 'isSyncing', 'lastHydratedAt'],
        onFinish: () => {
            isRefreshing.value = false;
            // Nếu server báo không còn sync, dừng poll trạng thái
            if (!props.syncDispatched && !props.isSyncing) {
                isSyncingBg.value = false;
                syncToastVisible.value = false;
                stopAllPolling();
            }
        },
    });
};

/**
 * Bắt đầu polling nhẹ vào /api/matches/{id}/sync-status.
 * Khi job xong (is_syncing = false) → reload data 1 lần rồi dừng.
 */
const startStatusPolling = () => {
    if (statusPollInterval) return; // đã chạy rồi

    let pollCount = 0;
    const MAX_POLLS = 30; // tối đa 2 phút (30 * 4s)
    let previousHydrated = props.lastHydratedAt;

    statusPollInterval = setInterval(async () => {
        // Nếu user rời trang — dừng ngay
        if (!window.location.pathname.includes(`/matches/${props.game.id}`)) {
            stopAllPolling();
            return;
        }

        pollCount++;
        if (pollCount > MAX_POLLS) {
            // Quá thời gian chờ, dừng polling
            clearInterval(statusPollInterval);
            statusPollInterval = null;
            isSyncingBg.value = false;
            syncToastVisible.value = false;
            return;
        }

        try {
            const { data } = await axios.get(`/api/matches/${props.game.id}/sync-status`);

            if (!data.is_syncing) {
                // Job đã xong
                clearInterval(statusPollInterval);
                statusPollInterval = null;

                // Reload nếu:
                // 1. last_hydrated thay đổi (có data mới), HOẶC
                // 2. Trang hiện tại vẫn thiếu data (lineups/stats rỗng)
                const currentlyMissingData = (
                    activeTab.value === 'lineups' && (!props.game.lineups || props.game.lineups.length === 0)
                ) || (
                    activeTab.value === 'stats' && (!props.game.statistics || props.game.statistics.length === 0)
                ) || (
                    activeTab.value === 'timeline' && (!props.game.events || props.game.events.length === 0)
                );

                if (data.last_hydrated && data.last_hydrated !== previousHydrated) {
                    reloadMatchData();
                } else if (currentlyMissingData && data.last_hydrated) {
                    // Có last_hydrated nhưng giá trị không đổi → vẫn reload vì trang chưa có data
                    reloadMatchData();
                } else {
                    isSyncingBg.value = false;
                    syncToastVisible.value = false;
                }
            }
        } catch (err) {
            console.warn('[SyncStatus] Poll failed:', err?.message);
        }
    }, 4000); // poll mỗi 4 giây
};

/**
 * Bắt đầu polling live — dùng cho trận đang chạy (30s/lần)
 */
const startLivePolling = () => {
    if (liveRefreshInterval) return;
    liveRefreshInterval = setInterval(() => {
        if (!window.location.pathname.includes(`/matches/${props.game.id}`)) {
            stopAllPolling();
            return;
        }
        reloadMatchData();
    }, 30000);
};

onMounted(() => {
    const liveStatuses = ['live', 'in_progress', 'halftime', '1st_half', '2nd_half', 'et', 'penalties'];
    const isLive = liveStatuses.includes(props.game.status?.toLowerCase());

    // 1. Nếu server dispatch job — bắt đầu polling trạng thái
    if (props.syncDispatched || props.isSyncing) {
        isSyncingBg.value  = true;
        syncToastVisible.value = true;
        startStatusPolling();
    }

    // 2. Nếu trận live — bắt đầu live polling (30s)
    if (isLive) {
        startLivePolling();
    }
});

onUnmounted(() => {
    // Dừng tất cả polling — không để "orphan" intervals chạy ngầm
    stopAllPolling();
});

// --- Legacy refreshMatchData (vẫn giữ cho compatibility với các nơi gọi thủ công) ---
const refreshMatchData = () => {
    isRefreshing.value = true;
    axios.post(`/matches/${props.game.id}/sync`)
        .then(() => reloadMatchData())
        .catch((err) => {
            console.error('Match sync failed:', err);
            isRefreshing.value = false;
        });
};

// Helper lấy rating của cầu thủ từ dữ liệu statistics
const getPlayerRating = (playerId) => {
    if (!props.game.player_stats) return null;
    const playerStat = props.game.player_stats.find(p => p.id == playerId);
    if (playerStat) {
        // console.log(`Found rating for player ${playerId}: ${playerStat.rating}`);
        return playerStat.rating;
    }
    return null;
};

const getRatingClass = (rating) => {
    const val = parseFloat(rating);
    if (isNaN(val)) return "bg-gray-200/20 text-gray-400";
    if (val >= 8.0) return "bg-emerald-500/80 text-white";
    if (val >= 7.0) return "bg-emerald-400/80 text-white";
    if (val >= 6.0) return "bg-amber-400/80 text-gray-900";
    return "bg-rose-400/80 text-white";
};

// Helper lấy các sự kiện của một cầu thủ
const getPlayerEvents = (playerId) => {
    const events = props.game.events;
    const eventsArray = Array.isArray(events) ? events : (events ? Object.values(events) : []);
    return eventsArray.filter(
        (e) => e.player?.id === playerId || e.assist?.id === playerId,
    );
};

// Điểm trung bình đội
const homeAverageRating = computed(() => {
    const ratings = processedHomeLineup.value
        .map((p) => parseFloat(p.rating))
        .filter((r) => !isNaN(r));
    if (!ratings.length) return "0.0";
    return (ratings.reduce((a, b) => a + b, 0) / ratings.length).toFixed(1);
});

const awayAverageRating = computed(() => {
    const ratings = processedAwayLineup.value
        .map((p) => parseFloat(p.rating))
        .filter((r) => !isNaN(r));
    if (!ratings.length) return "0.0";
    return (ratings.reduce((a, b) => a + b, 0) / ratings.length).toFixed(1);
});

const getLineupWithPositions = (lineupXI, formation = '4-3-3', isAway = false) => {
    if (!lineupXI) return [];

    const players = Array.isArray(lineupXI) ? lineupXI : Object.values(lineupXI);
    if (!players.length) return [];

    const horizontalStep = 8.5;

    // Phân tích formation string (ví dụ: "4-3-3" -> [1, 4, 3, 3])
    let rowSizes = [1]; // Luôn có 1 thủ môn ở hàng đầu
    if (formation && typeof formation === 'string') {
        const parts = formation.split('-').map(Number);
        if (parts.length > 0) {
            rowSizes = [1, ...parts];
        }
    }

    // Nếu tổng số người trong rowSizes không khớp với số lượng cầu thủ (thường là 11), tự điều chỉnh
    const totalInFormation = rowSizes.reduce((a, b) => a + b, 0);
    if (totalInFormation !== players.length) {
        // Fallback đơn giản nếu không khớp
        rowSizes = players.length === 11 ? [1, 4, 3, 3] : [1, Math.ceil((players.length - 1) / 3), Math.floor((players.length - 1) / 3), players.length - 1 - Math.ceil((players.length - 1) / 3) - Math.floor((players.length - 1) / 3)];
    }

    const processed = [];
    let rowIdx = 0;
    let colIdx = 0;

    players.forEach((p) => {
        // Chuyển sang hàng tiếp theo nếu hàng hiện tại đã đầy
        while (rowIdx < rowSizes.length && colIdx >= rowSizes[rowIdx]) {
            rowIdx++;
            colIdx = 0;
        }

        const rowNum = rowIdx + 1;
        const currentRowSize = rowSizes[rowIdx] ?? 1;
        
        // Tính toán vị trí Left (hàng ngang)
        const left = isAway
            ? 100 - ((rowNum - 1) * horizontalStep + 6)
            : (rowNum - 1) * horizontalStep + 6;
        
        // Tính toán vị trí Top (hàng dọc)
        const dynamicGap = currentRowSize > 4 ? 17 : currentRowSize > 3 ? 19 : 21;
        const top = isAway
            ? 50 + (colIdx - (currentRowSize - 1) / 2) * dynamicGap
            : 50 - (colIdx - (currentRowSize - 1) / 2) * dynamicGap;

        processed.push({
            ...(p.player || {}),
            name: p.player?.name ?? 'Unknown',
            rating: getPlayerRating(p.player?.id),
            events: getPlayerEvents(p.player?.id),
            style: { left: `${left}%`, top: `${top}%`, transform: "translate(-50%, -50%)" },
        });

        colIdx++;
    });

    return processed;
};

// Mapping Đội hình (API Football trả về mảng 2 đội)
const homeLineupData = computed(() => {
    const lineup = props.game.lineups?.find(
        (l) => l.team?.id == props.game.home_team?.id,
    );
    return lineup || null;
});

const awayLineupData = computed(() => {
    const lineup = props.game.lineups?.find(
        (l) => l.team?.id == props.game.away_team?.id,
    );
    return lineup || null;
});

const getCurrentXI = (lineupXI, teamId) => {
    if (!lineupXI) return [];
    if (lineupView.value === "start") return lineupXI;

    let currentXI = JSON.parse(JSON.stringify(lineupXI));

    // API-Football substitution event:
    // e.player = cầu thủ RA SÂN (đang trong startXI)
    // e.assist = cầu thủ VÀO SÂN (trong danh sách substitutes)
    const events = props.game.events;
    const substEvents = (Array.isArray(events) ? events : (events ? Object.values(events) : [])).filter(
        (e) => e.team?.id == teamId && e.type?.toLowerCase() === "subst"
    );

    substEvents.forEach((e) => {
        const playerOutId = e.player?.id;  // RA SÂN → tìm trong currentXI
        const playerInId  = e.assist?.id;  // VÀO SÂN → tìm trong substitutes

        const idx = currentXI.findIndex((p) => p.player?.id == playerOutId);
        if (idx === -1) return;

        const lineupData = props.game.lineups?.find((l) => l.team?.id == teamId);
        const subPlayer = (lineupData?.substitutes || []).find(
            (s) => s.player?.id == playerInId
        );

        if (subPlayer) {
            currentXI[idx] = {
                ...subPlayer,
                isSubstitutedIn: true,
                substituteMinute: e.time?.elapsed,
                replacedPlayerName: e.player?.name,
            };
        }
    });

    return currentXI;
};

const processedHomeLineup = computed(() => {
    const xi = getCurrentXI(
        homeLineupData.value?.startXI,
        props.game.home_team?.id,
    );
    return getLineupWithPositions(xi, homeLineupData.value?.formation, false);
});

const processedAwayLineup = computed(() => {
    const xi = getCurrentXI(
        awayLineupData.value?.startXI,
        props.game.away_team?.id,
    );
    return getLineupWithPositions(xi, awayLineupData.value?.formation, true);
});

const homeSubstitutes = computed(() => {
    const subs = homeLineupData.value?.substitutes || [];
    const subsArray = Array.isArray(subs) ? subs : Object.values(subs);
    return subsArray.map((p) => ({
        ...(p.player || {}),
        rating: getPlayerRating(p.player?.id),
    }));
});

const awaySubstitutes = computed(() => {
    const subs = awayLineupData.value?.substitutes || [];
    const subsArray = Array.isArray(subs) ? subs : Object.values(subs);
    return subsArray.map((p) => ({
        ...(p.player || {}),
        rating: getPlayerRating(p.player?.id),
    }));
});

// Mapping Thống kê
const matchStatsGroups = computed(() => {
    let stats = props.game.statistics || [];

    if (stats.length < 2) return [];

    const homeStats = stats[0].statistics;
    const awayStats = stats[1].statistics;

    const findVal = (list, type) => {
        const item = list.find((s) => s.type === type);
        return item ? item.value : 0;
    };

    const processStat = (label, type, suffix = "", detailsType = null) => {
        let homeRaw = String(findVal(homeStats, type) || 0).replace("%", "");
        let awayRaw = String(findVal(awayStats, type) || 0).replace("%", "");
        
        // Handle X/Y format for progress bar (take X)
        const getVal = (raw) => {
            if (typeof raw === 'string' && raw.includes('/')) {
                return parseFloat(raw.split('/')[0]) || 0;
            }
            return parseFloat(raw) || 0;
        };

        const homeVal = getVal(homeRaw);
        const awayVal = getVal(awayRaw);

        // Lấy chi tiết (ví dụ: cho Passes)
        let homeDetail = null;
        let awayDetail = null;
        if (detailsType) {
            homeDetail = findVal(homeStats, detailsType);
            awayDetail = findVal(awayStats, detailsType);
        }

        const total = homeVal + awayVal;

        return {
            label,
            home: homeRaw + suffix,
            away: awayRaw + suffix,
            homeDetail,
            awayDetail,
            homePercent: total > 0 ? (homeVal / total) * 100 : 50,
            awayPercent: total > 0 ? (awayVal / total) * 100 : 50,
        };
    };

    return [
        {
            group: "DỨT ĐIỂM",
            items: [
                processStat("Kỳ vọng bàn thắng (xG)", "Expected Goals"),
                processStat("Tổng cú sút", "Total Shots"),
                processStat("Sút trúng đích", "Shots on Goal"),
                processStat("Sút ra ngoài", "Shots off Goal"),
                processStat("Cú sút bị chặn", "Blocked Shots"),
            ].filter((i) => i.home != "0" || i.away != "0"),
        },
        {
            group: "TẤN CÔNG",
            items: [
                processStat("Kiểm soát bóng", "Ball Possession", "%"),
                processStat("Tấn công", "Attacks"),
                processStat("Tấn công nguy hiểm", "Dangerous Attacks"),
                processStat("Phạt góc", "Corner Kicks"),
                processStat("Việt vị", "Offsides"),
            ].filter((i) => i.home != "0" || i.away != "0"),
        },
        {
            group: "CHUYỀN BÓNG",
            items: [
                processStat("Độ chính xác chuyền", "Pass Accuracy"),
                processStat("Tổng số đường chuyền", "Total passes"),
                processStat("Tạt bóng", "Crosses"),
                processStat("Rê bóng", "Dribbles"),
                processStat("Chuyền dài", "Long Balls"),
            ].filter((i) => i.home != "0" || i.away != "0"),
        },
        {
            group: "PHÒNG NGỰ & THỦ MÔN",
            items: [
                processStat("Giải nguy", "Ball Safe"),
                processStat("Cứu thua", "Goalkeeper Saves"),
                processStat("Phạm lỗi", "Fouls"),
                processStat("Thẻ vàng", "Yellow Cards"),
                processStat("Thẻ đỏ", "Red Cards"),
            ].filter((i) => i.label === "Thẻ đỏ" || i.home != "0" || i.away != "0"),
        },
    ].filter((g) => g.items.length > 0);
});

// Phân tích AI
const prediction = computed(() => props.game.prediction);

const hasPredictionData = computed(() => {
    if (!prediction.value) return false;
    const homePercent = prediction.value.predictions?.percent?.home;
    const drawPercent = prediction.value.predictions?.percent?.draw;
    const awayPercent = prediction.value.predictions?.percent?.away;
    
    if (!homePercent || homePercent === '0%' || homePercent === '0.0%' || homePercent === 'N/A') {
        if (!drawPercent || drawPercent === '0%' || drawPercent === '0.0%' || drawPercent === 'N/A') {
            if (!awayPercent || awayPercent === '0%' || awayPercent === '0.0%' || awayPercent === 'N/A') {
                return false;
            }
        }
    }
    return true;
});

// Sắp xếp H2H theo thời gian mới nhất
const sortedH2H = computed(() => {
    const matches = [...(props.h2hMatches || [])];
    return matches.sort((a, b) => {
        const dateA = dayjs(a.fixture?.date);
        const dateB = dayjs(b.fixture?.date);
        return dateB.valueOf() - dateA.valueOf(); // Mới nhất lên đầu
    });
});

const isValidStandings = computed(() => {
    return props.standings && 
           props.standings.length > 0 && 
           props.standings.some(s => s.team?.name);
});

const h2hStats = computed(() => {
    let homeWins = 0;
    let awayWins = 0;
    let draws = 0;

    sortedH2H.value.forEach((m) => {
        if (m.goals.home > m.goals.away) {
            if (m.teams.home.id === props.game.home_team.id) homeWins++;
            else awayWins++;
        } else if (m.goals.home < m.goals.away) {
            if (m.teams.away.id === props.game.away_team.id) awayWins++;
            else homeWins++;
        } else {
            draws++;
        }
    });

    return { homeWins, awayWins, draws };
});

const translateDetail = (detail) => {
    if (!detail) return "";
    const lower = detail.toLowerCase().trim();
    if (lower === "goal" || lower.includes("normal goal")) return "Bàn thắng";
    if (lower.includes("yellow card") || lower === "yellow") return "Thẻ vàng";
    if (lower.includes("red card") || lower === "red") return "Thẻ đỏ";
    if (lower.includes("own goal")) return "Phản lưới nhà";
    if (lower.includes("penalty")) return "Phạt đền";
    if (lower.includes("missed penalty")) return "Hỏng phạt đền";
    if (lower.includes("var")) return "VAR";
    if (lower === "subst") return "Thay người";
    return detail;
};

const getSofaIcon = (type) => {
    if (type === "goal")
        return `<div class="w-6 h-6 flex items-center justify-center bg-gray-100 dark:bg-white/10 rounded-full shadow-inner">
            <svg class="w-4 h-4 text-slate-900 dark:text-white" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12,2C6.47,2,2,6.47,2,12s4.47,10,10,10s10-4.47,10-10S17.53,2,12,2z M12,4c1.23,0,2.39,0.3,3.42,0.82l-1.03,1.42 C14.12,6.6,13.62,6.8,13.1,6.8H10.9c-0.52,0-1.02-0.2-1.29-0.56L8.58,4.82C9.61,4.3,10.77,4,12,4z M5.14,7.44L6.37,9.1 c0.27,0.36,0.27,0.85,0,1.21l-1.23,1.66c-0.1,0.13-0.14,0.3-0.14,0.47v0.11C4.34,11.37,4,10.19,4,9c0-0.55,0.07-1.09,0.19-1.6 C4.47,7.31,4.82,7.35,5.14,7.44z M11.12,20.01l1.1-1.5c0.27-0.36,0.76-0.56,1.29-0.56h2.2c0.52,0,1.02,0.2,1.29,0.56l1.1,1.5 c-1.44,1.15-3.26,1.85-5.24,1.96C10.94,21.51,10.98,20.73,11.12,20.01z M19.81,11.6c-0.12-1.19-0.46-2.31-0.99-3.32 c0.33-0.1,0.67-0.13,1-0.13c0.32,0,0.63,0.03,0.94,0.1C20.88,9.26,20.93,10.43,19.95,11.44C19.86,11.53,19.83,11.57,19.81,11.6z M12,14.5l-2-1.5v-2l2-1.5l2,1.5v2L12,14.5z"/>
            </svg>
        </div>`;
    if (type === "card" || type === "yellow card")
        return `<div class="w-4 h-5 bg-amber-400 rounded-[3px] shadow-lg border border-white/20 dark:border-white/10 transform rotate-3"></div>`;
    if (type === "red card")
        return `<div class="w-4 h-5 bg-rose-600 rounded-[3px] shadow-lg border border-white/20 dark:border-white/10 transform rotate-3"></div>`;
    if (type === "subst")
        return `<div class="flex items-center justify-center w-6 h-6 bg-emerald-500/10 rounded-full">
            <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
            </svg>
        </div>`;
    return "";
};

// Mapping Diễn biến
const processedEvents = computed(() => {
    const rawEventsData = props.game.events || [];
    const rawEvents = Array.isArray(rawEventsData) ? rawEventsData : Object.values(rawEventsData);
    const events = [];

    // Header Hiệp 1
    events.push({ isMarker: true, label: "Bắt đầu hiệp 1", score: "0 - 0" });

    rawEvents.forEach((e) => {
        const minute = e.time?.elapsed;
        const extra = e.time?.extra;

        if (
            minute >= 46 &&
            !events.some((ev) => ev.label === "Bắt đầu hiệp 2")
        ) {
            events.push({ isMarker: true, label: "Bắt đầu hiệp 2" });
        }

        const type = e.type?.toLowerCase();
        const detail = (e.detail || "").toLowerCase();
        let finalType = type;
        if (type === "card") {
            if (detail.includes("yellow")) finalType = "yellow card";
            if (detail.includes("red")) finalType = "red card";
        }

        const side = e.team?.id === props.game.home_team?.id ? "home" : "away";

        const isSubstitution = type === "subst";
        
        // Handle name display for unknown players
        const playerName = isSubstitution 
            ? (e.subst?.playerIn?.name || "Cầu thủ vào sân")
            : (e.player?.name || "Cầu thủ không rõ");
            
        const playerOutName = isSubstitution
            ? (e.subst?.playerOut?.name || "Cầu thủ ra sân")
            : (e.assist?.name || "");

        // Only push if it's a valid event we want to show
        if (type && type !== 'unknown') {
            events.push({
                displayTime:
                    minute !== undefined && minute !== null
                        ? extra
                            ? `${minute}+${extra}`
                            : String(minute)
                        : "0",
                side: side,
                type: finalType,
                player: playerName,
                playerId: isSubstitution ? e.subst?.playerIn?.id : e.player?.id,
                playerOut: playerOutName,
                playerOutId: isSubstitution ? e.subst?.playerOut?.id : e.assist?.id,
                detail: e.detail,
                isSubstitution: isSubstitution,
                eventScore: type === "goal" ? `${e.comments || ""}` : null,
            });
        }
    });

    return events;
});

const getMatchStatus = (game) => {
    if (game.status === "finished") return "KẾT THÚC";
    if (game.status === "live") return "TRỰC TIẾP";
    if (game.status === "postponed") return "BỊ HOÃN";
    if (game.status === "cancelled") return "HỦY LỊCH";
    return "LỊCH THI ĐẤU";
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
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

/* Modal Transition */
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.25s ease, transform 0.25s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
    transform: scale(0.97);
}

/* Toast slide-up transition */
.toast-slide-enter-active,
.toast-slide-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}
.toast-slide-enter-from,
.toast-slide-leave-to {
    opacity: 0;
    transform: translateY(12px);
}
</style>
}

/* Toast slide-up transition */
.toast-slide-enter-active,
.toast-slide-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}
.toast-slide-enter-from,
.toast-slide-leave-to {
    opacity: 0;
    transform: translateY(12px);
}
</style>

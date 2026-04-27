<template>
    <Head :title="`${game.home_team?.name} vs ${game.away_team?.name}`" />
    <MainLayout>
        <div class="py-8">
            <!-- Back Button -->
            <Link
                href="/matches"
                class="inline-flex items-center gap-2 text-[11px] font-black text-gray-400 hover:text-emerald-500 transition-colors mb-4 group uppercase tracking-widest"
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

            <!-- Scoreboard Header (Premium) -->
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
                                class="text-2xl font-black text-gray-200"
                            >
                                H
                            </div>
                        </div>
                        <h2
                            class="text-base md:text-lg font-black text-gray-950 dark:text-white mb-1 leading-tight"
                        >
                            {{ game.home_team?.name }}
                        </h2>
                        <span
                            class="text-[9px] font-black uppercase tracking-[0.2em] text-emerald-500 px-2 py-0.5 bg-emerald-50 dark:bg-emerald-500/10 rounded-full"
                            >Chủ nhà</span
                        >
                    </div>

                    <!-- Score & Info -->
                    <div class="flex flex-col items-center">
                        <div
                            class="px-2.5 py-0.5 bg-gray-950 dark:bg-white rounded-lg mb-2"
                        >
                            <span
                                class="text-[8px] font-black uppercase tracking-widest text-white dark:text-gray-950"
                            >
                                {{ getMatchStatus(game) }}
                            </span>
                        </div>
                        <div
                            class="flex items-center gap-4 text-3xl md:text-4xl font-black text-gray-950 dark:text-white tabular-nums"
                        >
                            <span>{{ game.home_score ?? "-" }}</span>
                            <span
                                class="text-gray-200 dark:text-gray-700 opacity-50 text-xl"
                                >:</span
                            >
                            <span>{{ game.away_score ?? "-" }}</span>
                        </div>
                        <div class="mt-2 flex flex-col items-center">
                            <span
                                class="text-[8px] font-black text-gray-400 uppercase tracking-widest"
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
                                    class="text-[8px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-tighter"
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
                                class="text-2xl font-black text-gray-200"
                            >
                                A
                            </div>
                        </div>
                        <h2
                            class="text-base md:text-lg font-black text-gray-950 dark:text-white mb-1 leading-tight"
                        >
                            {{ game.away_team?.name }}
                        </h2>
                        <span
                            class="text-[9px] font-black uppercase tracking-[0.2em] text-gray-400 px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded-full"
                            >Đội khách</span
                        >
                    </div>
                </div>
            </div>

            <!-- Info Bar (Stadium, Referee, Attendance) -->
            <div class="grid grid-cols-3 gap-2 mb-6">
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-2.5 border border-gray-100 dark:border-gray-700 flex items-center gap-2.5 shadow-sm"
                >
                    <div
                        class="w-7 h-7 rounded-lg bg-gray-50 dark:bg-gray-700 flex items-center justify-center text-emerald-500"
                    >
                        <svg
                            class="w-3.5 h-3.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                            />
                        </svg>
                    </div>
                    <div>
                        <span
                            class="block text-[7px] font-black text-gray-400 uppercase tracking-widest leading-none mb-0.5"
                            >Sân vận động</span
                        >
                        <span
                            class="text-[10px] font-bold text-gray-900 dark:text-white line-clamp-1 truncate w-20 md:w-auto"
                            >{{ game.venue?.name
                            }}{{
                                game.venue?.city ? ", " + game.venue.city : ""
                            }}</span
                        >
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-2.5 border border-gray-100 dark:border-gray-700 flex items-center gap-2.5 shadow-sm"
                >
                    <div
                        class="w-7 h-7 rounded-lg bg-gray-50 dark:bg-gray-700 flex items-center justify-center text-blue-500"
                    >
                        <svg
                            class="w-3.5 h-3.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"
                            />
                        </svg>
                    </div>
                    <div>
                        <span
                            class="block text-[7px] font-black text-gray-400 uppercase tracking-widest leading-none mb-0.5"
                            >Trọng tài</span
                        >
                        <span
                            class="text-[10px] font-bold text-gray-900 dark:text-white line-clamp-1 truncate w-20 md:w-auto"
                            >{{ game.referee || "N/A" }}</span
                        >
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-2.5 border border-gray-100 dark:border-gray-700 flex items-center gap-2.5 shadow-sm"
                >
                    <div
                        class="w-7 h-7 rounded-lg bg-gray-50 dark:bg-gray-700 flex items-center justify-center text-purple-500"
                    >
                        <svg
                            class="w-3.5 h-3.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                    </div>
                    <div>
                        <span
                            class="block text-[7px] font-black text-gray-400 uppercase tracking-widest leading-none mb-0.5"
                            >Khán giả</span
                        >
                        <span
                            class="text-[10px] font-bold text-gray-900 dark:text-white"
                            >{{
                                game.attendance
                                    ? game.attendance.toLocaleString()
                                    : "N/A"
                            }}</span
                        >
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="flex flex-wrap items-center justify-center gap-3 mb-8">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="setActiveTab(tab.id)"
                    class="px-6 py-3 rounded-2xl text-[11px] font-black uppercase tracking-[0.2em] transition-all relative overflow-hidden group shadow-md"
                    :class="
                        activeTab === tab.id
                            ? 'bg-emerald-500 text-white shadow-emerald-500/25 scale-105'
                            : 'bg-white dark:bg-gray-800 text-gray-500 hover:text-gray-900 dark:hover:text-white border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
                    "
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- Tab Contents -->
            <div class="min-h-[500px] py-2">
                <!-- Lineups Tab -->
                <div v-if="activeTab === 'lineups'" class="space-y-8">
                    <!-- Formation Pitch (Visual - Flashscore Horizontal Style) -->
                    <div
                        class="relative bg-slate-950 rounded-[2.5rem] border border-slate-800 p-1.5 md:p-3 overflow-hidden shadow-2xl"
                    >
                        <!-- Pitch Container -->
                        <div
                            class="relative w-full aspect-[1.4/1] md:aspect-[2/1] bg-[#1a3326] rounded-2xl overflow-hidden"
                        >
                            <!-- Grass Background with Pattern -->
                            <div
                                class="absolute inset-0 bg-[#1a3326] overflow-hidden"
                            >
                                <div
                                    class="absolute inset-0 opacity-[0.08]"
                                    style="
                                        background-image: repeating-linear-gradient(
                                            90deg,
                                            transparent,
                                            transparent 10%,
                                            rgba(255, 255, 255, 0.05) 10%,
                                            rgba(255, 255, 255, 0.05) 20%
                                        );
                                    "
                                ></div>
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

                            <!-- Team Info Overlays -->
                            <div
                                class="absolute top-4 left-6 z-40 flex items-center gap-3"
                            >
                                <div
                                    class="px-2.5 py-1.5 rounded-lg bg-[#2c4238]/95 border border-white/10 flex items-center gap-2 shadow-xl backdrop-blur-md"
                                >
                                    <div
                                        class="w-5 h-5 rounded-md bg-yellow-500 flex items-center justify-center shadow-inner"
                                    >
                                        <span
                                            class="text-[10px] font-black text-slate-900"
                                            >Ø</span
                                        >
                                    </div>
                                    <span
                                        class="text-sm font-black text-white tabular-nums tracking-tight"
                                        >{{ homeAverageRating }}</span
                                    >
                                </div>
                                <div
                                    class="px-2 py-1 bg-black/30 rounded border border-white/5 backdrop-blur-sm"
                                >
                                    <span
                                        class="text-[10px] font-black text-white/50 uppercase tracking-[0.2em]"
                                        >{{ game.home_formation }}</span
                                    >
                                </div>
                            </div>

                            <div
                                class="absolute top-4 right-6 z-40 flex items-center gap-3 flex-row-reverse"
                            >
                                <div
                                    class="px-2.5 py-1.5 rounded-lg bg-[#2c4238]/95 border border-white/10 flex items-center gap-2 flex-row-reverse shadow-xl backdrop-blur-md"
                                >
                                    <div
                                        class="w-5 h-5 rounded-md bg-emerald-500 flex items-center justify-center shadow-inner"
                                    >
                                        <span
                                            class="text-[10px] font-black text-slate-900"
                                            >Ø</span
                                        >
                                    </div>
                                    <span
                                        class="text-sm font-black text-white tabular-nums tracking-tight"
                                        >{{ awayAverageRating }}</span
                                    >
                                </div>
                                <div
                                    class="px-2 py-1 bg-black/30 rounded border border-white/5 backdrop-blur-sm"
                                >
                                    <span
                                        class="text-[10px] font-black text-white/50 uppercase tracking-[0.2em] text-right"
                                        >{{ game.away_formation }}</span
                                    >
                                </div>
                            </div>

                            <!-- Watermark -->
                            <div
                                class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 opacity-[0.04] pointer-events-none"
                            >
                                <h2
                                    class="text-[12rem] font-black text-white tracking-tighter uppercase italic"
                                >
                                    AI SOCCER
                                </h2>
                            </div>

                            <!-- Players Layer -->
                            <div class="absolute inset-0 z-30">
                                <!-- Home Team -->
                                <div
                                    v-for="p in processedHomeLineup"
                                    :key="'home-' + p.id"
                                    class="absolute transition-all duration-700 hover:z-50"
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
                                                class="w-11 h-11 md:w-13 md:h-13 rounded-full border-2 border-white/10 bg-slate-900/50 shadow-2xl overflow-hidden group-hover:scale-110 transition-transform duration-300"
                                            >
                                                <img
                                                    v-if="p.id"
                                                    :src="`https://media.api-sports.io/football/players/${p.id}.png`"
                                                    class="w-full h-full object-cover rounded-full"
                                                />
                                            </div>
                                            <!-- Event Icons (Top Left) -->
                                            <div
                                                class="absolute top-0 -left-2 flex flex-col gap-1 z-40"
                                            >
                                                <div
                                                    v-for="(
                                                        ev, idx
                                                    ) in p.events"
                                                    :key="idx"
                                                    v-html="
                                                        getSofaIcon(ev.type)
                                                    "
                                                    class="scale-90 shadow-2xl"
                                                ></div>
                                            </div>
                                            <!-- Rating (Top Right) -->
                                            <div
                                                v-if="p.rating"
                                                class="absolute -top-1 -right-2 px-1.5 min-w-[24px] h-5 rounded-md text-[10px] font-black flex items-center justify-center shadow-2xl border border-white/20 z-40 transition-transform group-hover:scale-110"
                                                :class="
                                                    getRatingClass(p.rating)
                                                "
                                            >
                                                {{ p.rating }}
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <div
                                                class="bg-black/50 px-2 py-0.5 rounded backdrop-blur-sm border border-white/10 group-hover:bg-emerald-600/60 transition-colors"
                                            >
                                                <span
                                                    class="text-[10px] md:text-[11px] font-bold text-white tracking-tight drop-shadow-lg truncate max-w-[80px] block"
                                                    >{{
                                                        p.name.split(" ").pop()
                                                    }}</span
                                                >
                                            </div>
                                            <span
                                                class="text-[8px] font-black text-white/75 uppercase tracking-widest mt-0.5 drop-shadow-md"
                                                >{{ p.number }}</span
                                            >
                                        </div>
                                    </div>
                                </div>

                                <!-- Away Team -->
                                <div
                                    v-for="p in processedAwayLineup"
                                    :key="'away-' + p.id"
                                    class="absolute transition-all duration-700 hover:z-50"
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
                                                class="w-11 h-11 md:w-13 md:h-13 rounded-full border-2 border-white/10 bg-slate-900/50 shadow-2xl overflow-hidden group-hover:scale-110 transition-transform duration-300"
                                            >
                                                <img
                                                    v-if="p.id"
                                                    :src="`https://media.api-sports.io/football/players/${p.id}.png`"
                                                    class="w-full h-full object-cover rounded-full"
                                                />
                                            </div>
                                            <!-- Event Icons (Top Right for Away) -->
                                            <div
                                                class="absolute top-0 -right-2 flex flex-col gap-1 items-end z-40"
                                            >
                                                <div
                                                    v-for="(
                                                        ev, idx
                                                    ) in p.events"
                                                    :key="idx"
                                                    v-html="
                                                        getSofaIcon(ev.type)
                                                    "
                                                    class="scale-90 shadow-2xl"
                                                ></div>
                                            </div>
                                            <!-- Rating (Top Left for Away) -->
                                            <div
                                                v-if="p.rating"
                                                class="absolute -top-1 -left-2 px-1.5 min-w-[24px] h-5 rounded-md text-[10px] font-black flex items-center justify-center shadow-2xl border border-white/20 z-40 transition-transform group-hover:scale-110"
                                                :class="
                                                    getRatingClass(p.rating)
                                                "
                                            >
                                                {{ p.rating }}
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <div
                                                class="bg-black/50 px-2 py-0.5 rounded backdrop-blur-sm border border-white/10 group-hover:bg-blue-600/60 transition-colors"
                                            >
                                                <span
                                                    class="text-[10px] md:text-[11px] font-bold text-white tracking-tight drop-shadow-lg truncate max-w-[80px] block"
                                                    >{{
                                                        p.name.split(" ").pop()
                                                    }}</span
                                                >
                                            </div>
                                            <span
                                                class="text-[8px] font-black text-white/75 uppercase tracking-widest mt-0.5 drop-shadow-md"
                                                >{{ p.number }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lineup Lists & Substitutes -->
                    <div class="grid lg:grid-cols-2 gap-8">
                        <!-- Home Detailed -->
                        <div class="space-y-6">
                            <h4
                                class="px-6 py-3 bg-gray-50 dark:bg-gray-800/50 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 border border-gray-100 dark:border-gray-700"
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
                                        class="px-6 py-4 flex items-center justify-between group hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                                    >
                                        <div class="flex items-center gap-4">
                                            <span
                                                class="w-6 text-xs font-black text-gray-300 group-hover:text-emerald-500 transition-colors"
                                                >{{ p.number }}</span
                                            >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-gray-50 dark:bg-gray-700 overflow-hidden"
                                            >
                                                <img
                                                    v-if="p.id"
                                                    :src="`https://media.api-sports.io/football/players/${p.id}.png`"
                                                    class="w-full h-full object-cover"
                                                />
                                            </div>
                                            <Link
                                                :href="`/players/${p.id}`"
                                                class="text-sm font-bold text-gray-950 dark:text-white hover:text-emerald-500 transition-colors"
                                                >{{ p.name }}</Link
                                            >
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <div
                                                v-if="p.is_captain"
                                                class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-[8px] font-black text-gray-400"
                                            >
                                                C
                                            </div>
                                            <div
                                                v-if="p.rating"
                                                class="px-2 py-1 rounded-lg text-[10px] font-black border border-white dark:border-gray-800 shadow-sm"
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
                                        class="text-[9px] font-black uppercase tracking-widest text-gray-400"
                                        >Dự bị</span
                                    >
                                </div>
                                <div
                                    class="divide-y divide-gray-50 dark:divide-gray-700"
                                >
                                    <div
                                        v-for="p in homeSubstitutes"
                                        :key="p.id"
                                        class="px-6 py-4 flex items-center justify-between group hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                                    >
                                        <div
                                            class="flex items-center gap-4 opacity-70 group-hover:opacity-100 transition-opacity"
                                        >
                                            <span
                                                class="w-6 text-xs font-black text-gray-300"
                                                >{{ p.number }}</span
                                            >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-gray-50 dark:bg-gray-700 overflow-hidden"
                                            >
                                                <img
                                                    v-if="p.id"
                                                    :src="`https://media.api-sports.io/football/players/${p.id}.png`"
                                                    class="w-full h-full object-cover"
                                                />
                                            </div>
                                            <Link
                                                :href="`/players/${p.id}`"
                                                class="text-sm font-bold text-gray-950 dark:text-white hover:text-emerald-500 transition-colors"
                                                >{{ p.name }}</Link
                                            >
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <div
                                                v-if="p.rating"
                                                class="px-2 py-1 rounded-lg text-[10px] font-black border border-white dark:border-gray-800 shadow-sm"
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
                                    v-if="homeLineupData?.coach"
                                    class="bg-gray-50/50 dark:bg-gray-900/20 px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between"
                                >
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden"
                                        >
                                            <img
                                                :src="
                                                    homeLineupData.coach.photo
                                                "
                                                class="w-full h-full object-cover"
                                            />
                                        </div>
                                        <div>
                                            <span
                                                class="block text-[8px] font-black uppercase text-gray-400"
                                                >Huấn luyện viên</span
                                            >
                                            <span
                                                class="text-sm font-bold text-gray-950 dark:text-white"
                                                >{{
                                                    homeLineupData.coach.name
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Away Detailed -->
                        <div class="space-y-6">
                            <h4
                                class="px-6 py-3 bg-gray-50 dark:bg-gray-800/50 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 border border-gray-100 dark:border-gray-700 text-right"
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
                                        class="px-6 py-4 flex flex-row-reverse items-center justify-between group hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                                    >
                                        <div
                                            class="flex flex-row-reverse items-center gap-4"
                                        >
                                            <span
                                                class="w-6 text-xs font-black text-gray-300 group-hover:text-blue-500 transition-colors text-right"
                                                >{{ p.number }}</span
                                            >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-gray-50 dark:bg-gray-700 overflow-hidden"
                                            >
                                                <img
                                                    v-if="p.id"
                                                    :src="`https://media.api-sports.io/football/players/${p.id}.png`"
                                                    class="w-full h-full object-cover"
                                                />
                                            </div>
                                            <Link
                                                :href="`/players/${p.id}`"
                                                class="text-sm font-bold text-gray-950 dark:text-white hover:text-blue-500 transition-colors text-right"
                                                >{{ p.name }}</Link
                                            >
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <div
                                                v-if="p.rating"
                                                class="px-2 py-1 rounded-lg text-[10px] font-black border border-white dark:border-gray-800 shadow-sm"
                                                :class="
                                                    getRatingClass(p.rating)
                                                "
                                            >
                                                {{ p.rating }}
                                            </div>
                                            <div
                                                v-if="p.is_captain"
                                                class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-[8px] font-black text-gray-400"
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
                                        class="text-[9px] font-black uppercase tracking-widest text-gray-400"
                                        >Dự bị</span
                                    >
                                </div>
                                <div
                                    class="divide-y divide-gray-50 dark:divide-gray-700"
                                >
                                    <div
                                        v-for="p in awaySubstitutes"
                                        :key="p.id"
                                        class="px-6 py-4 flex flex-row-reverse items-center justify-between group hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                                    >
                                        <div
                                            class="flex flex-row-reverse items-center gap-4 opacity-70 group-hover:opacity-100 transition-opacity"
                                        >
                                            <span
                                                class="w-6 text-xs font-black text-gray-300 text-right"
                                                >{{ p.number }}</span
                                            >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-gray-50 dark:bg-gray-700 overflow-hidden"
                                            >
                                                <img
                                                    v-if="p.id"
                                                    :src="`https://media.api-sports.io/football/players/${p.id}.png`"
                                                    class="w-full h-full object-cover"
                                                />
                                            </div>
                                            <Link
                                                :href="`/players/${p.id}`"
                                                class="text-sm font-bold text-gray-950 dark:text-white hover:text-blue-500 transition-colors text-right"
                                                >{{ p.name }}</Link
                                            >
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <div
                                                v-if="p.rating"
                                                class="px-2 py-1 rounded-lg text-[10px] font-black border border-white dark:border-gray-800 shadow-sm"
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
                                    v-if="awayLineupData?.coach"
                                    class="bg-gray-50/50 dark:bg-gray-900/20 px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex flex-row-reverse items-center justify-between"
                                >
                                    <div
                                        class="flex flex-row-reverse items-center gap-4"
                                    >
                                        <div
                                            class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden"
                                        >
                                            <img
                                                :src="
                                                    awayLineupData.coach.photo
                                                "
                                                class="w-full h-full object-cover"
                                            />
                                        </div>
                                        <div class="text-right">
                                            <span
                                                class="block text-[8px] font-black uppercase text-gray-400"
                                                >Huấn luyện viên</span
                                            >
                                            <span
                                                class="text-sm font-bold text-gray-950 dark:text-white"
                                                >{{
                                                    awayLineupData.coach.name
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Missing Players (Injuries & Absences) -->
                    <div class="grid lg:grid-cols-2 gap-8 pt-4">
                        <div class="space-y-4">
                            <h5
                                class="px-6 text-[9px] font-black uppercase tracking-widest text-red-500 flex items-center gap-2"
                            >
                                <svg
                                    class="w-3 h-3"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="3"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                    />
                                </svg>
                                Chấn thương & Vắng mặt
                            </h5>
                            <div
                                class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-gray-700 text-center py-10"
                            >
                                <span class="text-xs font-medium text-gray-400"
                                    >Không có dữ liệu chấn thương cho trận đấu
                                    này</span
                                >
                            </div>
                        </div>
                        <div class="space-y-4">
                            <h5
                                class="px-6 text-[9px] font-black uppercase tracking-widest text-red-500 flex items-center gap-2 justify-end"
                            >
                                Chấn thương & Vắng mặt
                                <svg
                                    class="w-3 h-3"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="3"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                    />
                                </svg>
                            </h5>
                            <div
                                class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-gray-700 text-center py-10"
                            >
                                <span class="text-xs font-medium text-gray-400"
                                    >Không có dữ liệu chấn thương cho trận đấu
                                    này</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Match Stats Tab -->
                <div v-else-if="activeTab === 'stats'" class="py-4 space-y-6">
                    <!-- Sub Tabs (Flashscore style) -->
                    <div class="flex justify-center gap-1 mb-8">
                        <button
                            class="px-5 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider bg-red-600 text-white shadow-lg shadow-red-900/20"
                        >
                            Cả trận
                        </button>
                        <button
                            class="px-5 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                        >
                            Hiệp 1
                        </button>
                        <button
                            class="px-5 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                        >
                            Hiệp 2
                        </button>
                    </div>

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
                                    class="text-[12px] font-black uppercase tracking-[0.25em] text-slate-900 dark:text-emerald-400 px-4"
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
                                            class="flex flex-col items-start w-20"
                                        >
                                            <span
                                                class="text-xl font-black text-slate-900 dark:text-white leading-none"
                                                >{{ stat.home }}</span
                                            >
                                            <span
                                                v-if="stat.homeDetail"
                                                class="text-[9px] font-bold text-slate-400 dark:text-white/30 mt-1"
                                                >({{ stat.homeDetail }})</span
                                            >
                                        </div>

                                        <span
                                            class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 text-center flex-1 pb-1"
                                        >
                                            {{ stat.label }}
                                        </span>

                                        <div
                                            class="flex flex-col items-end w-20"
                                        >
                                            <span
                                                class="text-xl font-black text-slate-900 dark:text-white leading-none"
                                                >{{ stat.away }}</span
                                            >
                                            <span
                                                v-if="stat.awayDetail"
                                                class="text-[9px] font-bold text-slate-400 dark:text-white/30 mt-1"
                                                >({{ stat.awayDetail }})</span
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
                                                class="h-full bg-red-600 transition-all duration-1000 float-right"
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
                        class="text-center py-24 bg-gray-50/50 dark:bg-gray-800/20 rounded-3xl border border-dashed border-gray-200 dark:border-gray-700"
                    >
                        <p
                            class="text-sm font-black text-gray-400 uppercase tracking-widest"
                        >
                            Dữ liệu thống kê đang được xử lý...
                        </p>
                    </div>
                </div>

                <!-- H2H Tab -->
                <div v-else-if="activeTab === 'h2h'" class="py-4 space-y-6">
                    <div
                        v-if="h2hMatches && h2hMatches.length"
                        class="space-y-8"
                    >
                        <!-- Premium H2H Summary Card -->
                        <div
                            class="bg-white dark:bg-gray-800 rounded-[2.5rem] p-8 border border-gray-100 dark:border-white/5 shadow-sm"
                        >
                            <div class="flex items-center justify-between mb-8">
                                <h3
                                    class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-500"
                                >
                                    Thống kê đối đầu
                                </h3>
                                <span
                                    class="text-[10px] font-bold text-gray-400"
                                    >{{ h2hMatches.length }} trận gần nhất</span
                                >
                            </div>

                            <div class="flex items-center gap-4 h-12">
                                <!-- Home Wins Bar -->
                                <div class="relative flex-1 h-full group">
                                    <div
                                        class="absolute inset-0 bg-emerald-500/10 rounded-2xl"
                                    ></div>
                                    <div
                                        class="h-full bg-emerald-500 rounded-2xl flex items-center px-4 transition-all"
                                        :style="{
                                            width:
                                                Math.max(
                                                    20,
                                                    (h2hStats.homeWins /
                                                        h2hMatches.length) *
                                                        100,
                                                ) + '%',
                                        }"
                                    >
                                        <span
                                            class="text-white font-black text-lg"
                                            >{{ h2hStats.homeWins }}</span
                                        >
                                    </div>
                                    <span
                                        class="absolute -bottom-6 left-0 text-[9px] font-black uppercase text-emerald-600 tracking-tighter"
                                        >{{ game.home_team?.name }} thắng</span
                                    >
                                </div>

                                <!-- Draws Bar -->
                                <div class="w-20 h-full relative">
                                    <div
                                        class="absolute inset-0 bg-gray-100 dark:bg-white/5 rounded-2xl flex items-center justify-center"
                                    >
                                        <div class="flex flex-col items-center">
                                            <span
                                                class="text-gray-500 font-black text-lg leading-none"
                                                >{{ h2hStats.draws }}</span
                                            >
                                            <span
                                                class="text-[8px] font-bold text-gray-400 uppercase mt-0.5"
                                                >Hòa</span
                                            >
                                        </div>
                                    </div>
                                </div>

                                <!-- Away Wins Bar -->
                                <div
                                    class="relative flex-1 h-full group flex justify-end"
                                >
                                    <div
                                        class="absolute inset-0 bg-blue-500/10 rounded-2xl"
                                    ></div>
                                    <div
                                        class="h-full bg-blue-500 rounded-2xl flex items-center justify-end px-4 transition-all"
                                        :style="{
                                            width:
                                                Math.max(
                                                    20,
                                                    (h2hStats.awayWins /
                                                        h2hMatches.length) *
                                                        100,
                                                ) + '%',
                                        }"
                                    >
                                        <span
                                            class="text-white font-black text-lg"
                                            >{{ h2hStats.awayWins }}</span
                                        >
                                    </div>
                                    <span
                                        class="absolute -bottom-6 right-0 text-[9px] font-black uppercase text-blue-600 tracking-tighter text-right"
                                        >{{ game.away_team?.name }} thắng</span
                                    >
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
                                        class="text-[10px] font-black text-slate-400 tabular-nums"
                                    >
                                        {{
                                            m.fixture?.date
                                                ? dayjs(m.fixture.date).format(
                                                      "DD/MM/YYYY",
                                                  )
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
                                            class="text-sm font-black text-slate-900 dark:text-white truncate"
                                            :class="{
                                                'text-emerald-600':
                                                    m.goals?.home >
                                                    m.goals?.away,
                                            }"
                                        >
                                            {{ m.teams?.home?.name }}
                                        </span>
                                        <img
                                            v-if="m.teams?.home?.logo"
                                            :src="m.teams.home.logo"
                                            class="w-6 h-6 object-contain"
                                        />
                                    </div>

                                    <div
                                        class="flex items-center gap-1 px-3 py-1 bg-slate-900 text-white rounded-xl font-black text-sm tabular-nums shadow-lg"
                                    >
                                        <span>{{ m.goals?.home }}</span>
                                        <span class="text-white/30">-</span>
                                        <span>{{ m.goals?.away }}</span>
                                    </div>

                                    <div
                                        class="flex items-center gap-3 flex-1 justify-start"
                                    >
                                        <img
                                            v-if="m.teams?.away?.logo"
                                            :src="m.teams.away.logo"
                                            class="w-6 h-6 object-contain"
                                        />
                                        <span
                                            class="text-sm font-black text-slate-900 dark:text-white truncate"
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
                            class="text-sm font-black text-gray-400 uppercase tracking-widest"
                        >
                            Chưa có dữ liệu lịch sử đối đầu giữa hai đội
                        </p>
                    </div>
                </div>

                <!-- Timeline Tab (SofaScore Style) -->
                <div v-else-if="activeTab === 'timeline'" class="py-4">
                    <div
                        v-if="processedEvents.length"
                        class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden min-h-[400px]"
                    >
                        <div v-for="(event, idx) in processedEvents" :key="idx">
                            <!-- Period Header (1st Half, 2nd Half) -->
                            <div
                                v-if="event.isMarker"
                                class="bg-gray-50/50 dark:bg-gray-900/30 px-5 py-2 border-y border-gray-50 dark:border-gray-700 flex justify-between items-center group/header first:border-t-0"
                            >
                                <span
                                    class="text-[9px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 opacity-80"
                                    >{{ event.label }}</span
                                >
                                <span
                                    v-if="event.score"
                                    class="text-[10px] font-black text-gray-400 tracking-widest tabular-nums"
                                    >{{ event.score }}</span
                                >
                            </div>

                            <!-- Regular Event Row -->
                            <div
                                v-else
                                class="relative px-3 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700/20 transition-colors group/row"
                            >
                                <div
                                    class="flex items-center w-full min-h-[44px]"
                                >
                                    <!-- HOME TEAM COLUMN (Left Side) -->
                                    <div class="w-1/2 flex items-center pr-4">
                                        <template v-if="event.side === 'home'">
                                            <div
                                                class="w-10 flex-shrink-0 text-left"
                                            >
                                                <span
                                                    class="text-xs font-bold text-gray-950 dark:text-white tabular-nums opacity-60 group-hover/row:opacity-100"
                                                    >{{
                                                        event.displayTime
                                                    }}'</span
                                                >
                                                <div
                                                    v-if="event.eventScore"
                                                    class="text-[9px] font-black text-emerald-600 dark:text-emerald-400 opacity-90 leading-none mt-0.5"
                                                >
                                                    {{ event.eventScore }}
                                                </div>
                                            </div>
                                            <!-- Icon (Middle) -->
                                            <div
                                                class="w-10 flex-shrink-0 flex justify-center"
                                                v-html="getSofaIcon(event.type)"
                                            ></div>
                                            <!-- Player Info (Inner) -->
                                            <div class="flex flex-col">
                                                <!-- Tên thường -->
                                                <div
                                                    v-if="!event.isSubstitution"
                                                    class="flex items-center gap-2"
                                                >
                                                    <Link
                                                        v-if="event.playerId"
                                                        :href="
                                                            '/players/' +
                                                            event.playerId
                                                        "
                                                        class="text-[13px] font-bold text-gray-950 dark:text-white line-clamp-1 capitalize hover:text-emerald-500 transition-colors"
                                                    >
                                                        {{
                                                            event.player.toLowerCase()
                                                        }}
                                                    </Link>
                                                    <span
                                                        v-else
                                                        class="text-[13px] font-bold text-gray-950 dark:text-white line-clamp-1 capitalize"
                                                        >{{
                                                            event.player.toLowerCase()
                                                        }}</span
                                                    >
                                                </div>
                                                <span
                                                    v-if="
                                                        event.detail &&
                                                        !event.isSubstitution
                                                    "
                                                    class="text-[9px] font-medium text-gray-400 capitalize"
                                                    >{{ event.detail }}</span
                                                >
                                                <!-- Thay người -->
                                                <div
                                                    v-if="event.isSubstitution"
                                                    class="flex flex-col"
                                                >
                                                    <Link
                                                        v-if="event.playerId"
                                                        :href="
                                                            '/players/' +
                                                            event.playerId
                                                        "
                                                        class="text-[13px] font-bold text-emerald-600 dark:text-emerald-400 line-clamp-1 capitalize hover:underline"
                                                    >
                                                        ▲
                                                        {{
                                                            event.player.toLowerCase()
                                                        }}
                                                    </Link>
                                                    <span
                                                        v-else
                                                        class="text-[13px] font-bold text-emerald-600 dark:text-emerald-400 line-clamp-1 capitalize"
                                                        >▲
                                                        {{
                                                            event.player.toLowerCase()
                                                        }}</span
                                                    >

                                                    <Link
                                                        v-if="event.playerOutId"
                                                        :href="
                                                            '/players/' +
                                                            event.playerOutId
                                                        "
                                                        class="text-[11px] font-medium text-red-500 line-clamp-1 capitalize opacity-80 hover:underline"
                                                    >
                                                        ▼
                                                        {{
                                                            (
                                                                event.playerOut ||
                                                                ""
                                                            ).toLowerCase()
                                                        }}
                                                    </Link>
                                                    <span
                                                        v-else
                                                        class="text-[11px] font-medium text-red-500 line-clamp-1 capitalize opacity-80"
                                                        >▼
                                                        {{
                                                            (
                                                                event.playerOut ||
                                                                ""
                                                            ).toLowerCase()
                                                        }}</span
                                                    >
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- AWAY TEAM COLUMN (Right Side) -->
                                    <div
                                        class="w-1/2 flex items-center justify-end pl-4 text-right"
                                    >
                                        <template v-if="event.side === 'away'">
                                            <div
                                                class="flex flex-col items-end"
                                            >
                                                <!-- Tên thường -->
                                                <div
                                                    v-if="!event.isSubstitution"
                                                    class="flex items-center justify-end gap-2"
                                                >
                                                    <Link
                                                        v-if="event.playerId"
                                                        :href="
                                                            '/players/' +
                                                            event.playerId
                                                        "
                                                        class="text-[13px] font-bold text-gray-950 dark:text-white line-clamp-1 capitalize hover:text-blue-500 transition-colors"
                                                    >
                                                        {{
                                                            event.player.toLowerCase()
                                                        }}
                                                    </Link>
                                                    <span
                                                        v-else
                                                        class="text-[13px] font-bold text-gray-950 dark:text-white line-clamp-1 capitalize"
                                                        >{{
                                                            event.player.toLowerCase()
                                                        }}</span
                                                    >
                                                </div>
                                                <span
                                                    v-if="
                                                        event.detail &&
                                                        !event.isSubstitution
                                                    "
                                                    class="text-[9px] font-medium text-gray-400 capitalize"
                                                    >{{ event.detail }}</span
                                                >
                                                <!-- Thay người -->
                                                <div
                                                    v-if="event.isSubstitution"
                                                    class="flex flex-col items-end"
                                                >
                                                    <Link
                                                        v-if="event.playerId"
                                                        :href="
                                                            '/players/' +
                                                            event.playerId
                                                        "
                                                        class="text-[13px] font-bold text-emerald-600 dark:text-emerald-400 line-clamp-1 capitalize hover:underline"
                                                    >
                                                        {{
                                                            event.player.toLowerCase()
                                                        }}
                                                        ▲
                                                    </Link>
                                                    <span
                                                        v-else
                                                        class="text-[13px] font-bold text-emerald-600 dark:text-emerald-400 line-clamp-1 capitalize"
                                                        >{{
                                                            event.player.toLowerCase()
                                                        }}
                                                        ▲</span
                                                    >

                                                    <Link
                                                        v-if="event.playerOutId"
                                                        :href="
                                                            '/players/' +
                                                            event.playerOutId
                                                        "
                                                        class="text-[11px] font-medium text-red-400 line-clamp-1 capitalize opacity-80 hover:underline"
                                                    >
                                                        {{
                                                            (
                                                                event.playerOut ||
                                                                ""
                                                            ).toLowerCase()
                                                        }}
                                                        ▼
                                                    </Link>
                                                    <span
                                                        v-else
                                                        class="text-[11px] font-medium text-red-400 line-clamp-1 capitalize opacity-80"
                                                        >{{
                                                            (
                                                                event.playerOut ||
                                                                ""
                                                            ).toLowerCase()
                                                        }}
                                                        ▼</span
                                                    >
                                                </div>
                                            </div>
                                            <!-- Icon (Middle) -->
                                            <div
                                                class="w-10 flex-shrink-0 flex justify-center"
                                                v-html="getSofaIcon(event.type)"
                                            ></div>
                                            <!-- Minute (Far Right) -->
                                            <div
                                                class="w-10 flex-shrink-0 text-right"
                                            >
                                                <span
                                                    class="text-xs font-bold text-gray-950 dark:text-white tabular-nums opacity-60 group-hover/row:opacity-100"
                                                    >{{
                                                        event.displayTime
                                                    }}'</span
                                                >
                                                <div
                                                    v-if="event.eventScore"
                                                    class="text-[9px] font-black text-emerald-600 dark:text-emerald-400 opacity-90 leading-none mt-0.5"
                                                >
                                                    {{ event.eventScore }}
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Full Time Footer -->
                        <div
                            class="bg-gray-950 dark:bg-gray-900 px-6 py-5 flex items-center justify-between"
                        >
                            <span
                                class="text-[10px] font-black uppercase tracking-[0.4em] text-gray-400"
                                >Kết thúc trận đấu</span
                            >
                            <div class="flex items-center gap-4">
                                <span
                                    class="text-lg font-black text-white px-3 py-1 bg-gray-800 rounded-lg tabular-nums"
                                    >{{ game.home_score }} :
                                    {{ game.away_score }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="text-center py-20 bg-gray-50/50 dark:bg-gray-800/30 rounded-3xl border border-dashed border-gray-200 dark:border-gray-700"
                    >
                        <p
                            class="text-sm font-black text-gray-400 uppercase tracking-widest"
                        >
                            Diễn biến trận đấu đang được tải...
                        </p>
                    </div>
                </div>

                <!-- Analysis Tab (API Predictions) -->
                <div v-else-if="activeTab === 'analysis'" class="py-4">
                    <div v-if="prediction" class="space-y-6">
                        <!-- Expert Advice Card -->
                        <div
                            class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-3xl p-6 text-white shadow-lg shadow-emerald-500/20"
                        >
                            <div class="flex items-center gap-3 mb-4">
                                <div
                                    class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center"
                                >
                                    <svg
                                        class="w-6 h-6"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
                                        />
                                    </svg>
                                </div>
                                <h3
                                    class="text-xs font-black uppercase tracking-[0.2em]"
                                >
                                    Lời khuyên chuyên gia
                                </h3>
                            </div>
                            <p class="text-xl md:text-2xl font-black mb-2">
                                {{ prediction.predictions.advice }}
                            </p>
                            <div
                                class="flex items-center gap-2 text-[10px] font-bold text-white/80 uppercase tracking-widest"
                            >
                                <span>Dự đoán đội thắng: </span>
                                <span
                                    class="bg-white/20 px-2 py-0.5 rounded-full"
                                    >{{
                                        prediction.predictions.winner?.name ||
                                        "N/A"
                                    }}</span
                                >
                            </div>
                        </div>

                        <!-- Probability Chart -->
                        <div
                            class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm"
                        >
                            <h4
                                class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-6 text-center"
                            >
                                Xác suất kết quả (Win Probability)
                            </h4>
                            <div class="space-y-6">
                                <!-- Win/Draw/Loss Percentages -->
                                <div
                                    class="flex h-3 rounded-full overflow-hidden bg-gray-50 dark:bg-gray-700 shadow-inner"
                                >
                                    <div
                                        :style="{
                                            width: prediction.predictions
                                                .percent.home,
                                        }"
                                        class="bg-emerald-500 h-full relative group"
                                    >
                                        <div
                                            class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity"
                                        >
                                            Chủ nhà
                                        </div>
                                    </div>
                                    <div
                                        :style="{
                                            width: prediction.predictions
                                                .percent.draw,
                                        }"
                                        class="bg-gray-400 h-full relative group"
                                    >
                                        <div
                                            class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity"
                                        >
                                            Hòa
                                        </div>
                                    </div>
                                    <div
                                        :style="{
                                            width: prediction.predictions
                                                .percent.away,
                                        }"
                                        class="bg-blue-500 h-full relative group"
                                    >
                                        <div
                                            class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity"
                                        >
                                            Đội khách
                                        </div>
                                    </div>
                                </div>
                                <!-- Legend -->
                                <div class="grid grid-cols-3 text-center">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-2xl font-black text-emerald-500"
                                            >{{
                                                prediction.predictions.percent
                                                    .home
                                            }}</span
                                        >
                                        <span
                                            class="text-[8px] font-black uppercase text-gray-400 tracking-tighter"
                                            >{{ game.home_team.name }}</span
                                        >
                                    </div>
                                    <div class="flex flex-col">
                                        <span
                                            class="text-2xl font-black text-gray-400"
                                            >{{
                                                prediction.predictions.percent
                                                    .draw
                                            }}</span
                                        >
                                        <span
                                            class="text-[8px] font-black uppercase text-gray-400 tracking-tighter"
                                            >Hòa</span
                                        >
                                    </div>
                                    <div class="flex flex-col">
                                        <span
                                            class="text-2xl font-black text-blue-500"
                                            >{{
                                                prediction.predictions.percent
                                                    .away
                                            }}</span
                                        >
                                        <span
                                            class="text-[8px] font-black uppercase text-gray-400 tracking-tighter"
                                            >{{ game.away_team.name }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Comparison Stats (Attack/Defense) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div
                                class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-gray-100 dark:border-gray-700 flex flex-col items-center justify-center text-center"
                            >
                                <span
                                    class="text-[8px] font-black uppercase text-gray-400 mb-2"
                                    >Tài / Xỉu (Over/Under)</span
                                >
                                <span
                                    class="text-lg font-black text-gray-900 dark:text-white"
                                    >{{
                                        prediction.predictions.under_over ||
                                        "N/A"
                                    }}</span
                                >
                            </div>
                            <div
                                class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-gray-100 dark:border-gray-700 flex flex-col items-center justify-center text-center"
                            >
                                <span
                                    class="text-[8px] font-black uppercase text-gray-400 mb-2"
                                    >Bàn thắng kỳ vọng</span
                                >
                                <span
                                    class="text-lg font-black text-emerald-500"
                                    >{{
                                        prediction.predictions.goals.home || 0
                                    }}
                                    -
                                    {{
                                        prediction.predictions.goals.away || 0
                                    }}</span
                                >
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-20 text-center">
                        <div
                            class="w-16 h-16 rounded-full bg-gray-50 dark:bg-gray-900 flex items-center justify-center mx-auto mb-6 text-emerald-500"
                        >
                            <svg
                                class="w-8 h-8 animate-pulse"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <h3
                            class="text-lg font-bold text-gray-900 dark:text-white"
                        >
                            Dữ liệu phân tích đang được cập nhật...
                        </h3>
                        <p class="text-gray-400 text-sm mt-2 max-w-xs mx-auto">
                            Vui lòng chờ trong giây lát khi hệ thống tổng hợp
                            thông tin từ API.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import MainLayout from "../../Layouts/MainLayout.vue";
import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";

dayjs.extend(utc);

const formatDateTime = (val) => {
    if (!val) return "N/A";
    return dayjs.utc(val).local().format("DD/MM/YYYY HH:mm");
};

const props = defineProps({
    game: { type: Object, required: true },
    h2hMatches: { type: Array, default: () => [] },
});

const activeTab = ref("lineups");
const tabs = [
    { id: "lineups", label: "Đội hình" },
    { id: "stats", label: "Thống kê" },
    { id: "h2h", label: "Đối đầu" },
    { id: "timeline", label: "Diễn biến" },
    { id: "analysis", label: "Phân tích AI" },
];

const setActiveTab = (tabId) => {
    activeTab.value = tabId;
    window.location.hash = tabId;
};

onMounted(() => {
    const hash = window.location.hash.replace("#", "");
    if (hash && tabs.some((t) => t.id === hash)) {
        activeTab.value = hash;
    }
});

// Helper lấy rating của cầu thủ từ dữ liệu statistics
const getPlayerRating = (playerId) => {
    if (!props.game.players || !props.game.players.length) return null;
    for (const teamData of props.game.players) {
        if (!teamData.players) continue;
        const playerData = teamData.players.find(
            (p) => p.player.id == playerId,
        );
        if (playerData) {
            return playerData.statistics[0]?.games?.rating || null;
        }
    }
    return null;
};

const getRatingClass = (rating) => {
    const val = parseFloat(rating);
    if (isNaN(val)) return "bg-gray-100 text-gray-400";
    if (val >= 8.0) return "bg-emerald-500 text-white";
    if (val >= 7.0)
        return "bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400";
    if (val >= 6.0)
        return "bg-yellow-100 text-yellow-700 dark:bg-yellow-500/20 dark:text-yellow-400";
    return "bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400";
};

// Helper lấy các sự kiện của một cầu thủ
const getPlayerEvents = (playerId) => {
    return (props.game.events || []).filter(
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

const getLineupWithPositions = (lineupXI, isAway = false) => {
    if (!lineupXI) return [];

    // Nhóm cầu thủ theo hàng (row)
    const rows = {};
    lineupXI.forEach((p) => {
        if (!p.player.grid) return;
        const [row, col] = p.player.grid.split(":").map(Number);
        if (!rows[row]) rows[row] = [];
        rows[row].push(p);
    });

    const processed = [];
    const verticalGap = 21; // Dãn cách dọc rộng hơn
    const horizontalStep = 8.5; // Dãn cách ngang hẹp hơn để tránh va chạm ở giữa sân

    Object.keys(rows).forEach((rowNum) => {
        let playersInRow = rows[rowNum];

        // Sắp xếp theo cột để đảm bảo vị trí trái/phải
        playersInRow.sort((a, b) => {
            const colA = parseInt(a.player.grid.split(":")[1]);
            const colB = parseInt(b.player.grid.split(":")[1]);
            return isAway ? colB - colA : colA - colB;
        });

        const count = playersInRow.length;

        playersInRow.forEach((p, index) => {
            const [row, col] = p.player.grid.split(":").map(Number);

            // Tính Left (ngang)
            let left = 0;
            if (isAway) {
                left = 100 - ((row - 1) * horizontalStep + 6);
            } else {
                left = (row - 1) * horizontalStep + 6;
            }

            // Tính Top (dọc)
            const top = 50 + (index - (count - 1) / 2) * verticalGap;

            processed.push({
                ...p.player,
                rating: getPlayerRating(p.player.id),
                events: getPlayerEvents(p.player.id),
                style: {
                    left: `${left}%`,
                    top: `${top}%`,
                    transform: "translate(-50%, -50%)",
                },
            });
        });
    });

    return processed;
};

// Mapping Đội hình (API Football trả về mảng 2 đội)
const homeLineupData = computed(() => {
    const lineup = props.game.lineups?.find(
        (l) => l.team.id === props.game.home_team.id,
    );
    return lineup || null;
});

const awayLineupData = computed(() => {
    const lineup = props.game.lineups?.find(
        (l) => l.team.id === props.game.away_team.id,
    );
    return lineup || null;
});

const processedHomeLineup = computed(() => {
    return getLineupWithPositions(homeLineupData.value?.startXI, false);
});

const processedAwayLineup = computed(() => {
    return getLineupWithPositions(awayLineupData.value?.startXI, true);
});

const homeSubstitutes = computed(() => {
    return (homeLineupData.value?.substitutes || []).map((p) => ({
        ...p.player,
        rating: getPlayerRating(p.player.id),
    }));
});

const awaySubstitutes = computed(() => {
    return (awayLineupData.value?.substitutes || []).map((p) => ({
        ...p.player,
        rating: getPlayerRating(p.player.id),
    }));
});

// Mapping Thống kê
const matchStatsGroups = computed(() => {
    const stats = props.game.statistics || [];
    if (stats.length < 2) return [];

    const homeStats = stats[0].statistics;
    const awayStats = stats[1].statistics;

    const findVal = (list, type) => {
        const item = list.find((s) => s.type === type);
        return item ? item.value : 0;
    };

    const processStat = (label, type, suffix = "", detailsType = null) => {
        const homeRaw = String(findVal(homeStats, type) || 0).replace("%", "");
        const awayRaw = String(findVal(awayStats, type) || 0).replace("%", "");
        const homeVal = parseFloat(homeRaw) || 0;
        const awayVal = parseFloat(awayRaw) || 0;

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
                processStat("Kỳ vọng bàn thắng (xG)", "expected_goals"),
                processStat("Tổng cú sút", "Total Shots"),
                processStat("Sút trúng đích", "Shots on Goal"),
                processStat("Sút ra ngoài", "Shots off Goal"),
                processStat("Cú sút bị chặn", "Blocked Shots"),
                processStat("Sút trong vòng cấm", "Shots insidebox"),
                processStat("Sút ngoài vòng cấm", "Shots outsidebox"),
            ].filter((i) => i.home != "0" || i.away != "0"),
        },
        {
            group: "TẤN CÔNG",
            items: [
                processStat("Kiểm soát bóng", "Ball Possession", "%"),
                processStat("Phạt góc", "Corner Kicks"),
                processStat("Việt vị", "Offsides"),
            ].filter((i) => i.home != "0" || i.away != "0"),
        },
        {
            group: "CHUYỀN BÓNG",
            items: [
                processStat(
                    "Số đường chuyền",
                    "Passes %",
                    "%",
                    "Passes accurate",
                ),
                processStat("Tổng số đường chuyền", "Total passes"),
                processStat("Chuyền chính xác", "Passes accurate"),
            ].filter((i) => i.home != "0" || i.away != "0"),
        },
        {
            group: "PHÒNG NGỰ & THỦ MÔN",
            items: [
                processStat("Cứu thua", "Goalkeeper Saves"),
                processStat("Bàn thắng ngăn chặn", "goals_prevented"),
                processStat("Phạm lỗi", "Fouls"),
                processStat("Thẻ vàng", "Yellow Cards"),
                processStat("Thẻ đỏ", "Red Cards"),
            ].filter((i) => i.home != "0" || i.away != "0"),
        },
    ].filter((g) => g.items.length > 0);
});

// Sắp xếp H2H theo thời gian mới nhất
const sortedH2H = computed(() => {
    const matches = [...(props.h2hMatches || [])];
    return matches.sort((a, b) => {
        const dateA = new Date(a.fixture?.date || 0);
        const dateB = new Date(b.fixture?.date || 0);
        return dateB - dateA; // Mới nhất lên đầu
    });
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

const getSofaIcon = (type) => {
    if (type === "goal")
        return `<div class="w-5 h-5 bg-white rounded-full flex items-center justify-center shadow-lg border border-slate-900"><svg class="w-3.5 h-3.5 text-slate-900" viewBox="0 0 24 24" fill="currentColor"><path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4Z" /></svg></div>`;
    if (type === "card" || type === "yellow card")
        return `<div class="w-3.5 h-4.5 bg-yellow-400 rounded-sm shadow-md border border-white/20"></div>`;
    if (type === "red card")
        return `<div class="w-3.5 h-4.5 bg-red-500 rounded-sm shadow-md border border-white/20"></div>`;
    if (type === "subst")
        return `<div class="w-5 h-5 bg-slate-900/80 rounded-full flex items-center justify-center border border-white/10 shadow-lg"><svg class="w-3.5 h-3.5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4"><path d="M7 10l5-5 5 5M17 14l-5 5-5-5" /></svg></div>`;
    return "";
};

// Mapping Diễn biến
const processedEvents = computed(() => {
    const rawEvents = props.game.events || [];
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

        events.push({
            displayTime:
                minute !== undefined && minute !== null
                    ? extra
                        ? `${minute}+${extra}`
                        : String(minute)
                    : "0",
            side: side,
            type: finalType,
            player: e.player?.name || "Unknown",
            playerId: e.player?.id,
            playerOut: e.assist?.name,
            playerOutId: e.assist?.id,
            detail: e.detail,
            isSubstitution: type === "subst",
            eventScore: type === "goal" ? `${e.comments || ""}` : null,
        });
    });

    return events;
});

const getMatchStatus = (game) => {
    if (game.status === "finished") return "KẾT THÚC";
    if (game.status === "live") return "TRỰC TIẾP";
    return "LỊCH THI ĐẤU";
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

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
                        <!-- MODE 2: GROUP STAGE (Premium Standing & Schedule) -->
                        <div v-if="activeTab === 'group'" class="space-y-6">
                            <!-- Group Selector Tabs (Reusable Component) -->
                            <TabSlider 
                                v-model="selectedGroupId"
                                :items="groupList"
                                id-prefix="tab-stage-"
                                item-class="px-6"
                            />

                            <!-- Stage View Logic -->
                            <div v-if="['A','B','C','D','E','F','G','H','I','J','K','L'].includes(selectedGroupId)" class="space-y-10">
                                <!-- Top: Standing Table -->
                                <div class="space-y-4">
                                    <div class="bg-white dark:bg-gray-800 rounded-[32px] border border-gray-100 dark:border-gray-700 shadow-xl overflow-hidden">
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-left">
                                                <thead>
                                                    <tr class="bg-gray-50/50 dark:bg-gray-700/20 text-[10px] font-semibold text-gray-900 dark:text-white uppercase tracking-widest">
                                                        <th class="px-6 py-4">Hạng</th>
                                                        <th class="px-6 py-4">Đội bóng</th>
                                                        <th class="px-6 py-4 text-center">ST</th>
                                                        <th class="px-6 py-4 text-center">T</th>
                                                        <th class="px-6 py-4 text-center">H</th>
                                                        <th class="px-6 py-4 text-center">B</th>
                                                        <th class="px-6 py-4 text-center">HS</th>
                                                        <th class="px-6 py-4 text-center text-emerald-600">Điểm</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                                    <tr v-for="(team, idx) in currentGroupStandings" 
                                                        :key="team.name"
                                                        class="hover:bg-gray-50/50 dark:hover:bg-gray-700/20 transition-colors">
                                                        <td class="px-6 py-5">
                                                            <span :class="['w-6 h-6 flex items-center justify-center rounded-lg text-[10px] font-bold', 
                                                                idx < 2 ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white']">
                                                                {{ idx + 1 }}
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-5">
                                                            <div class="flex items-center gap-3">
                                                                <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 p-1 shadow-sm">
                                                                    <img :src="team.flag" class="w-full h-full object-contain" />
                                                                </div>
                                                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide truncate max-w-[120px]" :title="translateCountry(team.name)">{{ translateCountry(team.name) }}</span>
                                                            </div>
                                                        </td>
                                                        <td class="px-6 py-5 text-center text-xs font-medium text-gray-900 dark:text-white">{{ team.played }}</td>
                                                        <td class="px-6 py-5 text-center text-xs font-medium text-gray-900 dark:text-white">{{ team.won }}</td>
                                                        <td class="px-6 py-5 text-center text-xs font-medium text-gray-900 dark:text-white">{{ team.drawn }}</td>
                                                        <td class="px-6 py-5 text-center text-xs font-medium text-gray-900 dark:text-white">{{ team.lost }}</td>
                                                        <td class="px-6 py-5 text-center text-xs font-medium text-gray-900 dark:text-white">{{ team.gd }}</td>
                                                        <td class="px-6 py-5 text-center text-xs font-bold text-emerald-600">{{ team.pts }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bottom: Group Matches -->
                                <div class="space-y-6">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-1.5 h-6 bg-emerald-600 rounded-full shadow-[0_0_10px_rgba(16,185,129,0.3)]"></div>
                                            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 uppercase tracking-tight">Lịch thi đấu Bảng {{ selectedGroupId }}</h3>
                                        </div>
                                        <span class="text-[10px] font-bold text-gray-900 dark:text-white uppercase tracking-widest bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full border border-gray-100 dark:border-gray-700">{{ (groupMatches[selectedGroupId] || []).length }} Trận đấu</span>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        <div v-for="match in (groupMatches[selectedGroupId] || [])" :key="match.id"
                                             class="group relative bg-white dark:bg-gray-800 rounded-[24px] border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                                            
                                            <!-- Top Status Bar -->
                                            <div class="px-4 py-2 bg-gray-50/50 dark:bg-gray-700/20 border-b border-gray-50 dark:border-gray-700 flex justify-between items-center">
                                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">{{ match.date }} • {{ match.time }}</span>
                                                <span class="text-[9px] font-semibold text-emerald-600 uppercase tracking-tighter">Bảng {{ match.group_name }}</span>
                                            </div>

                                            <div class="p-5">
                                                <!-- Teams Grid -->
                                                <div class="grid grid-cols-7 items-center gap-2 mb-4">
                                                    <div class="col-span-3 flex flex-col items-center gap-2">
                                                        <div class="w-10 h-10 rounded-full bg-gray-50 dark:bg-gray-700 p-1.5 shadow-inner transition-transform group-hover:scale-110 duration-500">
                                                            <img :src="match.homeFlag" class="w-full h-full object-contain" />
                                                        </div>
                                                        <span class="text-[10px] font-bold text-gray-700 dark:text-gray-200 uppercase text-center truncate w-full">{{ translateCountry(match.home) }}</span>
                                                    </div>

                                                    <div class="col-span-1 flex flex-col items-center">
                                                        <div class="text-[10px] font-bold text-gray-300">VS</div>
                                                    </div>

                                                    <div class="col-span-3 flex flex-col items-center gap-2">
                                                        <div class="w-10 h-10 rounded-full bg-gray-50 dark:bg-gray-700 p-1.5 shadow-inner transition-transform group-hover:scale-110 duration-500">
                                                            <img :src="match.awayFlag" class="w-full h-full object-contain" />
                                                        </div>
                                                        <span class="text-[10px] font-bold text-gray-700 dark:text-gray-200 uppercase text-center truncate w-full">{{ translateCountry(match.away) }}</span>
                                                    </div>
                                                </div>

                                                <!-- Stadium Info -->
                                                <div class="flex items-center justify-center gap-2 pt-3 border-t border-gray-50 dark:border-gray-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest truncate">{{ match.stadium }}</span>
                                                </div>
                                            </div>

                                            <!-- Hover Glow Effect -->
                                            <div class="absolute inset-0 border-2 border-transparent group-hover:border-emerald-500/10 rounded-[24px] pointer-events-none transition-colors duration-300"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Knockout Round View -->
                            <div v-else class="space-y-10">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-1.5 h-6 bg-emerald-600 rounded-full"></div>
                                        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 uppercase tracking-tight">{{ groupList.find(s => s.id === selectedGroupId)?.name }}</h3>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div v-for="i in 3" :key="i"
                                         class="group relative bg-white dark:bg-gray-800 rounded-[24px] border border-gray-100 dark:border-gray-700 shadow-sm p-5 overflow-hidden">
                                        <div class="text-[9px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-tight mb-4 text-center">TBD - 00:00</div>
                                        <div class="flex items-center justify-between gap-4">
                                            <div class="flex flex-col items-center gap-2 flex-1 min-w-0">
                                                <div class="w-10 h-10 rounded-full bg-gray-50 dark:bg-gray-700 p-1.5 shadow-inner">
                                                    <div class="w-full h-full rounded-full bg-gray-200 dark:bg-gray-600 animate-pulse"></div>
                                                </div>
                                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">TBD</span>
                                            </div>
                                            <span class="text-lg font-bold text-gray-200 dark:bg-gray-700">VS</span>
                                            <div class="flex flex-col items-center gap-2 flex-1 min-w-0">
                                                <div class="w-10 h-10 rounded-full bg-gray-50 dark:bg-gray-700 p-1.5 shadow-inner">
                                                    <div class="w-full h-full rounded-full bg-gray-200 dark:bg-gray-600 animate-pulse"></div>
                                                </div>
                                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">TBD</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-10 text-center bg-gray-50/50 dark:bg-gray-800/50 rounded-[32px] border border-dashed border-gray-200 dark:border-gray-700">
                                    <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Xem sơ đồ đầy đủ trong tab "Vòng loại trực tiếp"</p>
                                    <button @click="activeTab = 'knockout'" class="mt-4 text-[10px] font-bold text-emerald-600 uppercase tracking-widest hover:underline">Mở Bracket Map</button>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="activeTab === 'stadiums'" class="animate-in fade-in slide-in-from-bottom-4 duration-700">
                            <StadiumMap :stadiums="safeStadiums" :matches="props.matches" />
                            
                            <div class="space-y-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-1.5 h-6 bg-emerald-600 rounded-full shadow-[0_0_10px_rgba(16,185,129,0.3)]"></div>
                                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 uppercase tracking-tight">Danh sách tất cả sân vận động</h3>
                                </div>
                                
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-7xl mx-auto">
                                    <div v-for="stadium in safeStadiums" :key="stadium.id" 
                                         class="group bg-white dark:bg-gray-800 rounded-[32px] border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-xl transition-all duration-500 overflow-hidden">
                                        <div class="aspect-[16/9] bg-gray-100 dark:bg-gray-700 relative overflow-hidden">
                                            <img :src="stadium.image || 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?q=80&w=2084&auto=format&fit=crop'" 
                                                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" />
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                                            <div class="absolute bottom-4 left-6 right-6 flex items-end justify-between gap-4">
                                                <div class="min-w-0 flex-1">
                                                    <h4 class="text-white font-semibold uppercase tracking-widest text-[13px] truncate" :title="stadium.name">{{ stadium.name }}</h4>
                                                    <p class="text-white/70 text-[10px] font-medium uppercase truncate" :title="stadium.city + ', ' + stadium.country">{{ stadium.city }}, {{ stadium.country }}</p>
                                                </div>
                                                <div class="shrink-0 text-right pb-1">
                                                    <div class="text-white font-semibold uppercase tracking-widest text-[13px]">
                                                        {{ stadium.capacity?.toLocaleString() || 'TBD' }}
                                                        <span class="text-[10px] text-white">Chỗ ngồi</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-6">

                                            <!-- Upcoming Matches in Card -->
                                            <div class="space-y-4 border-t border-gray-50 dark:border-gray-700/50">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-1 h-3 bg-emerald-600 rounded-full"></div>
                                                        <h5 class="text-[10px] font-bold text-gray-900 dark:text-white uppercase tracking-widest">Lịch thi đấu sắp tới</h5>
                                                    </div>
                                                    <span class="text-[9px] font-bold text-gray-400 uppercase">{{ getStadiumMatches(stadium.id).length }} trận</span>
                                                </div>

                                                <div v-if="getStadiumMatches(stadium.id).length > 0" class="space-y-2">
                                                    <div v-for="match in getStadiumMatches(stadium.id).slice(0, 3)" :key="match.id"
                                                         class="group/item flex items-center gap-3 p-3 bg-gray-50/50 dark:bg-gray-700/30 rounded-2xl border border-transparent hover:border-emerald-500/20 hover:bg-white dark:hover:bg-gray-800 transition-all duration-300">
                                                        <div class="flex flex-col items-center gap-1 w-10">
                                                            <div class="text-[9px] font-bold text-gray-900 dark:text-white">{{ match.time }}</div>
                                                            <div class="text-[8px] font-bold text-gray-400">{{ match.date }}</div>
                                                        </div>
                                                        <div class="h-6 w-px bg-gray-100 dark:bg-gray-700"></div>
                                                        <div class="flex-1 flex items-center justify-between gap-2">
                                                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                                                <img :src="match.homeFlag" class="w-4 h-4 object-contain shrink-0" />
                                                                <span class="text-[9px] font-semibold text-gray-700 dark:text-gray-200 uppercase truncate">{{ translateCountry(match.home) }}</span>
                                                            </div>
                                                            <span class="text-[8px] font-bold text-gray-300">VS</span>
                                                            <div class="flex items-center gap-2 min-w-0 flex-1 justify-end">
                                                                <span class="text-[9px] font-semibold text-gray-700 dark:text-gray-200 uppercase truncate text-right">{{ translateCountry(match.away) }}</span>
                                                                <img :src="match.awayFlag" class="w-4 h-4 object-contain shrink-0" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-else class="py-4 text-center">
                                                    <span class="text-[10px] font-medium text-gray-400 uppercase italic tracking-wide">Chưa có lịch thi đấu</span>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- MODE 1: DATE (Matches by Date) -->
                        <div v-else-if="activeTab === 'date'" class="space-y-8">
                            <div v-for="date in sortedDates" :key="date" class="space-y-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-px flex-1 bg-gray-100 dark:bg-gray-800"></div>
                                    <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-[0.3em]">{{ dayjs(date).format('dddd, DD/MM/YYYY') }}</span>
                                    <div class="h-px flex-1 bg-gray-100 dark:bg-gray-800"></div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div v-for="match in matchesByDate[date]" :key="match.id"
                                         class="group relative bg-white dark:bg-gray-800 rounded-[24px] border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                                        
                                        <!-- Top Status Bar -->
                                        <div class="px-4 py-2 bg-gray-50/50 dark:bg-gray-700/20 border-b border-gray-50 dark:border-gray-700 flex justify-between items-center">
                                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">{{ match.date }} • {{ match.time }}</span>
                                            <span class="text-[9px] font-semibold text-emerald-600 uppercase tracking-tighter">Bảng {{ match.group_name }}</span>
                                        </div>

                                        <div class="p-5">
                                            <!-- Teams Grid -->
                                            <div class="grid grid-cols-7 items-center gap-2 mb-4">
                                                <div class="col-span-3 flex flex-col items-center gap-2">
                                                    <div class="w-10 h-10 rounded-full bg-gray-50 dark:bg-gray-700 p-1.5 shadow-inner transition-transform group-hover:scale-110 duration-500">
                                                        <img :src="match.homeFlag" class="w-full h-full object-contain" />
                                                    </div>
                                                    <span class="text-[10px] font-bold text-gray-700 dark:text-gray-200 uppercase text-center truncate w-full">{{ translateCountry(match.home) }}</span>
                                                </div>

                                                <div class="col-span-1 flex flex-col items-center">
                                                    <div class="text-[10px] font-black text-gray-300">VS</div>
                                                </div>

                                                <div class="col-span-3 flex flex-col items-center gap-2">
                                                    <div class="w-10 h-10 rounded-full bg-gray-50 dark:bg-gray-700 p-1.5 shadow-inner transition-transform group-hover:scale-110 duration-500">
                                                        <img :src="match.awayFlag" class="w-full h-full object-contain" />
                                                    </div>
                                                    <span class="text-[10px] font-bold text-gray-700 dark:text-gray-200 uppercase text-center truncate w-full">{{ translateCountry(match.away) }}</span>
                                                </div>
                                            </div>

                                            <!-- Stadium Info -->
                                            <div class="flex items-center justify-center gap-2 pt-3 border-t border-gray-50 dark:border-gray-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest truncate">{{ match.stadium }}</span>
                                            </div>
                                        </div>

                                        <!-- Hover Glow Effect -->
                                        <div class="absolute inset-0 border-2 border-transparent group-hover:border-emerald-500/10 rounded-[24px] pointer-events-none transition-colors duration-300"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-if="sortedDates.length === 0" class="min-h-[400px] flex items-center justify-center">
                                <div class="text-gray-300 dark:text-gray-700 font-bold uppercase tracking-[0.5em] text-[10px]">
                                    Không có trận đấu nào được tìm thấy
                                </div>
                            </div>
                        </div>

                        <!-- MODE 3: KNOCKOUT BRACKET (Style Redesign) -->
                        <div v-else-if="activeTab === 'knockout'" class="pb-20">
                            <div class="bg-[#f8f9fa] dark:bg-gray-900/50 rounded-[32px] border border-gray-100 dark:border-gray-800 shadow-xl overflow-hidden relative">
                                <!-- Navigation & Headers -->
                                <div class="relative py-8 px-12 bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                                    <div class="flex items-center justify-between max-w-4xl mx-auto">
                                        <button @click="scrollBracket('left')" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                            </svg>
                                        </button>
                                        
                                        <div class="flex gap-16 md:gap-32">
                                            <div v-for="round in visibleRounds" :key="'header-'+round.id" class="text-center min-w-[200px]">
                                                <h3 class="text-emerald-600 dark:text-emerald-400 font-semibold text-sm md:text-base uppercase tracking-widest">{{ round.name }}</h3>
                                            </div>
                                        </div>

                                        <button @click="scrollBracket('right')" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Bracket Content -->
                                <div class="relative overflow-x-auto no-scrollbar scroll-smooth p-6 md:p-10" id="bracket-container">
                                    <div class="flex gap-0 min-w-max justify-center">
                                        <!-- Column for each round -->
                                        <div v-for="(round, rIdx) in visibleRounds" :key="'col-'+round.id" 
                                             class="flex flex-col w-[340px] shrink-0">
                                            
                                            <!-- First Round Column (Paired) -->
                                            <template v-if="round.isFirst">
                                                <div v-for="(pair, pIdx) in round.pairs" :key="'pair-'+pIdx" 
                                                     class="h-[280px] relative flex flex-col justify-center gap-6 px-8">
                                                    
                                                    <!-- Bracket Lines Connector -->
                                                    <div class="absolute -right-0 top-0 bottom-0 w-8 pointer-events-none">
                                                        <!-- Vertical line (centered between matches) -->
                                                        <div class="absolute top-[25%] bottom-[25%] right-0 w-[2px] bg-gray-400 dark:bg-gray-500 shadow-sm"></div>
                                                        <!-- Horizontal line from top match -->
                                                        <div class="absolute top-[25%] right-0 w-8 h-[2px] bg-gray-400 dark:bg-gray-500 shadow-sm"></div>
                                                        <!-- Horizontal line from bottom match -->
                                                        <div class="absolute bottom-[25%] right-0 w-8 h-[2px] bg-gray-400 dark:bg-gray-500 shadow-sm"></div>
                                                        <!-- Horizontal line out to next round -->
                                                        <div class="absolute top-1/2 right-[-32px] w-8 h-[2px] bg-gray-400 dark:bg-gray-500 shadow-sm"></div>

                                                        <!-- Special Case: Semi-final dashed lines to 3rd Place Match (SVG for precision) -->
                                                        <template v-if="round.id === 'semi'">
                                                            <svg class="absolute -right-0 top-0 w-8 h-[420px] pointer-events-none z-30" style="overflow: visible;">
                                                                <!-- Path from match 101 and 102 down to 103 -->
                                                                <path d="M 0 70 L 16 70 L 16 420 L 48 420" stroke="#9ca3af" stroke-width="2" fill="none" stroke-dasharray="4 4" />
                                                                <path d="M 0 210 L 16 210" stroke="#9ca3af" stroke-width="2" fill="none" stroke-dasharray="4 4" />
                                                            </svg>
                                                        </template>
                                                    </div>

                                                    <div v-for="match in pair" :key="match.id" class="relative z-10 h-[90px] flex items-center">
                                                        <!-- Match Card -->
                                                        <div class="w-full bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
                                                            <div class="px-3 py-1.5 bg-gray-50/50 dark:bg-gray-700/20 border-b border-gray-50 dark:border-gray-700 flex justify-between">
                                                                <span class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-tighter">Trận {{ match.id }} • {{ match.time }} • {{ match.date }}</span>
                                                            </div>
                                                            <div class="p-3 space-y-2">
                                                                <div v-for="team in ['home', 'away']" :key="team" class="flex items-center justify-between">
                                                                    <div class="flex items-center gap-2">
                                                                        <div class="w-6 h-6 rounded-full bg-gray-50 dark:bg-gray-700 flex items-center justify-center overflow-hidden border border-gray-100 dark:border-gray-600">
                                                                            <img v-if="match[team+'_flag']" :src="match[team+'_flag']" class="w-full h-full object-cover" />
                                                                            <span v-else class="text-[10px]">🏳️</span>
                                                                        </div>
                                                                        <span class="text-[11px] font-semibold text-gray-700 dark:text-gray-200 uppercase truncate max-w-[130px]">{{ translateCountry(match[team]) }}</span>
                                                                    </div>
                                                                    <span class="text-[11px] font-black text-gray-300">0</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

                                            <!-- Second Round Column -->
                                            <template v-else>
                                                <div v-for="match in round.matches" :key="match.id" 
                                                     class="h-[280px] relative flex items-center px-8">
                                                    <!-- Entry line from previous round (Hide for 103 as it has dashed line) -->
                                                    <div v-if="match.id !== 103" class="absolute left-0 top-1/2 w-8 h-[2px] bg-gray-400 dark:bg-gray-500 shadow-sm"></div>
                                                    
                                                    <!-- Match Card with Badge -->
                                                    <div class="relative w-full">
                                                        <div class="w-full bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
                                                            <div class="px-3 py-1.5 bg-gray-50/50 dark:bg-gray-700/20 border-b border-gray-50 dark:border-gray-700">
                                                                <span class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-tighter">Trận {{ match.id }} • {{ match.time }} • {{ match.date }}</span>
                                                            </div>
                                                            <div class="p-3 space-y-2">
                                                                <div v-for="team in ['home', 'away']" :key="team" class="flex items-center justify-between">
                                                                    <div class="flex items-center gap-2">
                                                                        <div class="w-6 h-6 rounded-full bg-gray-50 dark:bg-gray-700 flex items-center justify-center overflow-hidden border border-gray-100 dark:border-gray-600">
                                                                            <img v-if="match[team+'_flag']" :src="match[team+'_flag']" class="w-full h-full object-cover" />
                                                                            <span v-else class="text-[10px]">🏳️</span>
                                                                        </div>
                                                                        <span class="text-[11px] font-semibold text-gray-700 dark:text-gray-200 uppercase truncate max-w-[130px]">{{ translateCountry(match[team]) }}</span>
                                                                    </div>
                                                                    <span class="text-[11px] font-black text-gray-300">0</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Simplified Match Type Labels (Final / 3rd Place) -->
                                                        <div v-if="match.id === 104" class="absolute -right-3 top-1/2 -translate-y-1/2 translate-x-full">
                                                            <div class="flex items-center gap-1.5 px-2 py-0.5 border border-yellow-200 dark:border-yellow-900/50 bg-yellow-50/50 dark:bg-yellow-900/20 rounded-full">
                                                                <div class="w-1.5 h-1.5 rounded-full bg-yellow-500"></div>
                                                                <span class="text-[9px] font-bold text-yellow-700 dark:text-yellow-500 uppercase tracking-wider whitespace-nowrap">Chung kết</span>
                                                            </div>
                                                        </div>
                                                        <div v-if="match.id === 103" class="absolute -right-3 top-1/2 -translate-y-1/2 translate-x-full">
                                                            <div class="flex items-center gap-1.5 px-2 py-0.5 border border-orange-200 dark:border-orange-900/50 bg-orange-50/50 dark:bg-orange-900/20 rounded-full">
                                                                <div class="w-1.5 h-1.5 rounded-full bg-orange-500"></div>
                                                                <span class="text-[9px] font-bold text-orange-700 dark:text-orange-400 uppercase tracking-wider whitespace-nowrap">Hạng 3</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
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
import TabSlider from '@/Components/TabSlider.vue';
import StadiumMap from '@/Components/WorldCup/StadiumMap.vue';
import { getFullDisplay } from '@/Constants/countries';
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
    { id: 'knockout', name: 'Vòng loại trực tiếp' },
    { id: 'stadiums', name: 'Sân vận động' }
];

const activeTab = ref('date');
const activeBracketRound = ref('r32');
const selectedGroupId = ref('A');
const groupList = [
    ...['A','B','C','D','E','F','G','H','I','J','K','L'].map(id => ({ id, name: 'Bảng ' + id })),
    { id: 'r32', name: 'Vòng 1/16' },
    { id: 'r16', name: 'Vòng 1/8' },
    { id: 'qf', name: 'Tứ kết' },
    { id: 'sf', name: 'Bán kết' },
    { id: '3rd', name: 'Tranh hạng 3' },
    { id: 'f', name: 'Chung kết' }
];
const selectedDate = ref(dayjs().format('YYYY-MM-DD'));
const liveMatch = ref(props.liveTest);
const isLeagueIndexVisible = ref(true);

const translateCountry = (name) => {
    return getFullDisplay(name) || name;
};

const groupColors = {
    'A': 'from-emerald-500 to-green-600',
    'B': 'from-red-500 to-rose-600',
    'C': 'from-amber-500 to-orange-600',
    'D': 'from-blue-500 to-indigo-600',
    'E': 'from-purple-500 to-violet-600',
    'F': 'from-yellow-400 to-amber-500',
    'G': 'from-pink-500 to-fuchsia-600',
    'H': 'from-cyan-500 to-blue-600',
    'I': 'from-gray-700 to-slate-900',
    'J': 'from-sky-500 to-blue-600',
    'K': 'from-orange-500 to-red-600',
    'L': 'from-teal-500 to-emerald-600',
};

const dummyGroups = {
    'A': [
        { name: 'Mexico', flag: 'https://flagcdn.com/mx.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'South Africa', flag: 'https://flagcdn.com/za.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Korea Republic', flag: 'https://flagcdn.com/kr.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Czechia', flag: 'https://flagcdn.com/cz.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
    ],
    'B': [
        { name: 'Canada', flag: 'https://flagcdn.com/ca.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Bosnia and Herz.', flag: 'https://flagcdn.com/ba.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Qatar', flag: 'https://flagcdn.com/qa.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Switzerland', flag: 'https://flagcdn.com/ch.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
    ],
    'C': [
        { name: 'Brazil', flag: 'https://flagcdn.com/br.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Morocco', flag: 'https://flagcdn.com/ma.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Haiti', flag: 'https://flagcdn.com/ht.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Scotland', flag: 'https://flagcdn.com/gb-sct.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
    ],
    'D': [
        { name: 'USA', flag: 'https://flagcdn.com/us.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Paraguay', flag: 'https://flagcdn.com/py.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Australia', flag: 'https://flagcdn.com/au.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Türkiye', flag: 'https://flagcdn.com/tr.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
    ],
    'E': [
        { name: 'Germany', flag: 'https://flagcdn.com/de.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Curaçao', flag: 'https://flagcdn.com/cw.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Côte d\'Ivoire', flag: 'https://flagcdn.com/ci.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Ecuador', flag: 'https://flagcdn.com/ec.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
    ],
    'F': [
        { name: 'Netherlands', flag: 'https://flagcdn.com/nl.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Japan', flag: 'https://flagcdn.com/jp.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Sweden', flag: 'https://flagcdn.com/se.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Tunisia', flag: 'https://flagcdn.com/tn.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
    ],
    'G': [
        { name: 'Belgium', flag: 'https://flagcdn.com/be.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Egypt', flag: 'https://flagcdn.com/eg.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'IR Iran', flag: 'https://flagcdn.com/ir.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'New Zealand', flag: 'https://flagcdn.com/nz.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
    ],
    'H': [
        { name: 'Spain', flag: 'https://flagcdn.com/es.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Cabo Verde', flag: 'https://flagcdn.com/cv.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Saudi Arabia', flag: 'https://flagcdn.com/sa.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Uruguay', flag: 'https://flagcdn.com/uy.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
    ],
    'I': [
        { name: 'France', flag: 'https://flagcdn.com/fr.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Senegal', flag: 'https://flagcdn.com/sn.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Iraq', flag: 'https://flagcdn.com/iq.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Norway', flag: 'https://flagcdn.com/no.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
    ],
    'J': [
        { name: 'Argentina', flag: 'https://flagcdn.com/ar.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Algeria', flag: 'https://flagcdn.com/dz.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Austria', flag: 'https://flagcdn.com/at.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Jordan', flag: 'https://flagcdn.com/jo.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
    ],
    'K': [
        { name: 'Portugal', flag: 'https://flagcdn.com/pt.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Congo DR', flag: 'https://flagcdn.com/cd.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Uzbekistan', flag: 'https://flagcdn.com/uz.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Colombia', flag: 'https://flagcdn.com/co.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
    ],
    'L': [
        { name: 'England', flag: 'https://flagcdn.com/gb-eng.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Croatia', flag: 'https://flagcdn.com/hr.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Ghana', flag: 'https://flagcdn.com/gh.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
        { name: 'Panama', flag: 'https://flagcdn.com/pa.svg', played: 0, won: 0, drawn: 0, lost: 0, gd: 0, pts: 0 },
    ]
};

const groupMatches = computed(() => {
    const grouped = {};
    if (!props.matches) return grouped;
    
    const allMatches = Array.isArray(props.matches) 
        ? props.matches 
        : Object.values(props.matches).flat();

    allMatches.forEach(m => {
        if (!m || !m.group_name) return;
        const g = m.group_name.replace('Group ', '');
        if (!grouped[g]) grouped[g] = [];
        grouped[g].push(m);
    });
    return grouped;
});

const currentGroupStandings = computed(() => {
    if (props.groups && props.groups[selectedGroupId.value] && props.groups[selectedGroupId.value].length > 0) {
        return props.groups[selectedGroupId.value];
    }
    return dummyGroups[selectedGroupId.value] || [];
});

const knockoutRounds = [
    { id: 'r32', name: 'Vòng 32 đội', matches: [
        // Reordered to match tournament tree pairs (73+75 -> 90, 74+77 -> 89, etc.)
        { id: 73, home: 'Nhì bảng A', away: 'Nhì bảng B', time: '02:00', date: '29/06' },
        { id: 75, home: 'Nhất bảng F', away: 'Nhì bảng C', time: '08:00', date: '30/06' },
        { id: 74, home: 'Nhất bảng E', away: 'Hạng ba A/B/C/D/F', time: '03:30', date: '30/06' },
        { id: 77, home: 'Nhất bảng I', away: 'Hạng ba C/D/F/G/H', time: '04:00', date: '01/07' },
        { id: 76, home: 'Nhất bảng C', away: 'Nhì bảng F', time: '00:00', date: '30/06' },
        { id: 78, home: 'Nhì bảng E', away: 'Nhì bảng I', time: '00:00', date: '01/07' },
        { id: 79, home: 'Nhất bảng A', away: 'Hạng ba C/E/F/H/I', time: '08:00', date: '01/07' },
        { id: 80, home: 'Nhất bảng L', away: 'Hạng ba E/H/I/J/K', time: '23:00', date: '01/07' },
        { id: 81, home: 'Nhất bảng D', away: 'Hạng ba B/E/F/I/J', time: '07:00', date: '02/07' },
        { id: 82, home: 'Nhất bảng G', away: 'Hạng ba A/E/H/I/J', time: '03:00', date: '02/07' },
        { id: 83, home: 'Nhì bảng K', away: 'Nhì bảng L', time: '06:00', date: '03/07' },
        { id: 84, home: 'Nhất bảng H', away: 'Nhì bảng J', time: '02:00', date: '03/07' },
        { id: 85, home: 'Nhất bảng B', away: 'Hạng ba E/F/G/I/J', time: '10:00', date: '03/07' },
        { id: 87, home: 'Nhất bảng K', away: 'Hạng ba D/E/I/J/L', time: '08:30', date: '04/07' },
        { id: 86, home: 'Nhất bảng J', away: 'Nhì bảng H', time: '05:00', date: '04/07' },
        { id: 88, home: 'Nhì bảng D', away: 'Nhì bảng G', time: '01:00', date: '04/07' }
    ] },
    { id: 'r16', name: 'Vòng 16 đội', matches: [
        // Match IDs reordered to match R32 pairs
        { id: 90, home: 'Thắng trận 73', away: 'Thắng trận 75', time: '00:00', date: '05/07' },
        { id: 89, home: 'Thắng trận 74', away: 'Thắng trận 77', time: '04:00', date: '05/07' },
        { id: 91, home: 'Thắng trận 76', away: 'Thắng trận 78', time: '03:00', date: '06/07' },
        { id: 92, home: 'Thắng trận 79', away: 'Thắng trận 80', time: '07:00', date: '06/07' },
        { id: 94, home: 'Thắng trận 81', away: 'Thắng trận 82', time: '07:00', date: '07/07' },
        { id: 93, home: 'Thắng trận 83', away: 'Thắng trận 84', time: '02:00', date: '07/07' },
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
    const start = Math.max(0, Math.min(currentIndex, knockoutRounds.length - 2));
    const rounds = knockoutRounds.slice(start, start + 2);

    return rounds.map((round, idx) => {
        const pairs = [];
        if (idx === 0) {
            for (let i = 0; i < round.matches.length; i += 2) {
                pairs.push(round.matches.slice(i, i + 2));
            }
        }
        return { ...round, pairs, isFirst: idx === 0 };
    });
});

const scrollBracket = (direction) => {
    const currentIndex = knockoutRounds.findIndex(r => r.id === activeBracketRound.value);
    if (direction === 'left' && currentIndex > 0) {
        activeBracketRound.value = knockoutRounds[currentIndex - 1].id;
    } else if (direction === 'right' && currentIndex < knockoutRounds.length - 2) {
        activeBracketRound.value = knockoutRounds[currentIndex + 1].id;
    }
    
    // Also trigger native scroll for better feel
    const container = document.getElementById('bracket-container');
    if (container) {
        const scrollAmount = 350;
        container.scrollBy({ left: direction === 'left' ? -scrollAmount : scrollAmount, behavior: 'smooth' });
    }
};

// Bracket data for knockout stages
const bracketMatches = computed(() => {
    // This would normally come from props.matches
    return {
        'r32': [
            { id: 1, home_team: 'TBD', home_team_flag: 'https://flagcdn.com/un.svg', away_team: 'TBD', away_team_flag: 'https://flagcdn.com/un.svg', date: '2026-06-28T18:00:00Z' }
        ],
        // ... other rounds
    };
});

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

// Filter matches for a specific stadium
const getStadiumMatches = (stadiumId) => {
    if (!props.matches) return [];
    const allMatches = Array.isArray(props.matches) 
        ? props.matches 
        : Object.values(props.matches).flat();
    return allMatches.filter(m => m && m.stadium_id == stadiumId);
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
    // Read tab from URL for persistence
    if (typeof window !== 'undefined') {
        const params = new URLSearchParams(window.location.search);
        const urlTab = params.get('tab');
        if (urlTab && mainTabs.some(t => t.id === urlTab)) {
            activeTab.value = urlTab;
        }
    }

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

.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

/* Bracket line enhancements */
.group\/match::after {
    content: '';
    position: absolute;
    right: -48px;
    top: 50%;
    width: 48px;
    height: 2px;
    background: #e5e7eb;
    z-index: 0;
}

.dark .group\/match::after {
    background: #374151;
}

/* Hide line for the last column in view */
.flex-col:last-child .group\/match::after {
    display: none;
}
</style>

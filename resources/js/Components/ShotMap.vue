<template>
    <div class="shotmap-container bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-white/5 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Bản đồ cú sút (Shotmap)</h3>
            <div class="flex items-center gap-6">
                <!-- Home Team Legend -->
                <div class="flex items-center gap-4">
                    <span class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest">{{ homeTeam?.name }}</span>
                    <div class="flex items-center gap-2.5">
                        <div class="flex items-center gap-1.5">
                            <div class="w-2.5 h-2.5 rounded-full border border-emerald-500/60 bg-emerald-500/10"></div>
                            <span class="text-[9px] font-bold text-gray-500 uppercase tracking-widest">Sút</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)]"></div>
                            <span class="text-[9px] font-bold text-emerald-500 uppercase tracking-widest">Bàn thắng</span>
                        </div>
                    </div>
                </div>

                <!-- Away Team Legend -->
                <div class="flex items-center gap-4">
                    <span class="text-[10px] font-bold text-amber-500 uppercase tracking-widest">{{ awayTeam?.name }}</span>
                    <div class="flex items-center gap-2.5">
                        <div class="flex items-center gap-1.5">
                            <div class="w-2.5 h-2.5 rounded-full border border-amber-500/60 bg-amber-500/10"></div>
                            <span class="text-[9px] font-bold text-gray-500 uppercase tracking-widest">Sút</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-2.5 h-2.5 rounded-full bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.4)]"></div>
                            <span class="text-[9px] font-bold text-amber-500 uppercase tracking-widest">Bàn thắng</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative aspect-[105/68] w-full bg-gradient-to-br from-[#2d5a43] to-[#1f4532] rounded-xl shadow-inner border-2 border-[#3d6d54]">
            <!-- Grass Background with Pattern -->
            <div class="absolute inset-0 overflow-hidden rounded-[10px]">
                <div class="absolute inset-0 opacity-[0.12]" style="background-image: repeating-linear-gradient(90deg, transparent, transparent 10%, rgba(255, 255, 255, 0.08) 10%, rgba(255, 255, 255, 0.08) 20%);"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(44,93,68,0.3),transparent)]"></div>
            </div>

            <!-- Team Labels on Pitch -->
            <div class="absolute inset-y-0 left-4 flex items-center opacity-10 pointer-events-none">
                <span class="text-4xl font-black text-white uppercase -rotate-90 whitespace-nowrap">{{ homeTeam?.name }}</span>
            </div>
            <div class="absolute inset-y-0 right-4 flex items-center opacity-10 pointer-events-none">
                <span class="text-4xl font-black text-white uppercase rotate-90 whitespace-nowrap">{{ awayTeam?.name }}</span>
            </div>

            <!-- Pitch Markings (Lime Lines) -->
            <!-- Outer Boundary -->
            <div class="absolute inset-0 border-[1.5px] border-white/25"></div>
            
            <!-- Halfway Line -->
            <div class="absolute inset-y-0 left-1/2 w-[1.5px] bg-white/25"></div>
            
            <!-- Center Circle -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[17.4%] aspect-square border-[1.5px] border-white/25 rounded-full"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-1 h-1 bg-white/40 rounded-full"></div>
            
            <!-- Penalty Areas -->
            <div class="absolute top-[20.35%] left-0 w-[15.7%] h-[59.3%] border-y-[1.5px] border-r-[1.5px] border-white/25"></div>
            <div class="absolute top-[20.35%] right-0 w-[15.7%] h-[59.3%] border-y-[1.5px] border-l-[1.5px] border-white/25"></div>
            
            <!-- Goal Areas -->
            <div class="absolute top-[36.55%] left-0 w-[5.2%] h-[26.9%] border-y-[1.5px] border-r-[1.5px] border-white/25"></div>
            <div class="absolute top-[36.55%] right-0 w-[5.2%] h-[26.9%] border-y-[1.5px] border-l-[1.5px] border-white/25"></div>

            <!-- Penalty Spots -->
            <div class="absolute top-1/2 left-[10.4%] w-1 h-1 bg-white/40 rounded-full -translate-x-1/2 -translate-y-1/2 shadow-sm"></div>
            <div class="absolute top-1/2 right-[10.4%] w-1 h-1 bg-white/40 rounded-full translate-x-1/2 -translate-y-1/2 shadow-sm"></div>

            <!-- Goals -->
            <div class="absolute top-[43%] -left-1 w-1 h-[14%] bg-white/60 rounded-sm"></div>
            <div class="absolute top-[43%] -right-1 w-1 h-[14%] bg-white/60 rounded-sm"></div>

            <!-- Shots -->
            <div 
                v-for="shot in shots" 
                :key="shot.id"
                class="absolute w-3.5 h-3.5 -translate-x-1/2 -translate-y-1/2 cursor-pointer group hover:z-[100]"
                :style="{ left: shot.x + '%', top: shot.y + '%' }"
            >
                <div 
                    class="w-full h-full rounded-full border-[1.5px] transition-all duration-300 group-hover:scale-150 z-10"
                    :class="[
                        shot.team_id == homeTeam?.id 
                            ? (shot.type === 'goal' ? 'bg-emerald-500 border-emerald-400 shadow-[0_0_10px_rgba(16,185,129,0.4)]' : 'bg-emerald-500/10 border-emerald-500/60') 
                            : (shot.type === 'goal' ? 'bg-amber-500 border-amber-400 shadow-[0_0_10px_rgba(245,158,11,0.4)]' : 'bg-amber-500/10 border-amber-500/60')
                    ]"
                ></div>
                
                <!-- Tooltip -->
                <div class="opacity-0 group-hover:opacity-100 absolute bottom-full left-1/2 -translate-x-1/2 mb-4 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-slate-800 dark:text-white px-4 py-3 rounded-xl whitespace-nowrap z-[100] pointer-events-none shadow-xl transition-all duration-300 scale-95 group-hover:scale-100 origin-bottom w-56">
                    <!-- Team Header -->
                    <div class="flex items-center gap-2 mb-2.5 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <div class="w-5 h-5 bg-slate-100 dark:bg-white/5 rounded p-0.5 flex items-center justify-center shrink-0">
                            <img v-if="shot.team_logo" :src="shot.team_logo" class="w-full h-full object-contain" />
                        </div>
                        <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider truncate">{{ shot.team_name }}</span>
                    </div>

                    <!-- Player Info -->
                    <div class="mb-2">
                        <div class="text-sm font-bold text-slate-900 dark:text-white leading-tight truncate">{{ shot.player_name || 'Cầu thủ' }}</div>
                    </div>

                    <!-- Technical Specs -->
                    <div class="flex flex-col gap-1.5 pt-1.5 border-t border-slate-100 dark:border-slate-800 text-xs">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500 dark:text-slate-400">Expected Goals (xG)</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">{{ shot.xg ? shot.xg.toFixed(2) : '0.00' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500 dark:text-slate-400">Kết quả</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ shot.type_label || shot.type }}</span>
                        </div>
                        <div v-if="shot.body_part" class="flex items-center justify-between gap-4">
                            <span class="text-slate-500 dark:text-slate-400">Dứt điểm</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ shot.body_part }}</span>
                        </div>
                        <div v-if="shot.situation" class="flex items-center justify-between gap-4">
                            <span class="text-slate-500 dark:text-slate-400">Tình huống</span>
                            <span class="font-semibold text-amber-600 dark:text-amber-400">{{ shot.situation }}</span>
                        </div>
                    </div>

                    <!-- Arrow Pointer -->
                    <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-[1px] border-[6px] border-transparent border-t-white dark:border-t-slate-900"></div>
                </div>
            </div>
        </div>

        <div v-if="!shots.length" class="mt-4 text-center py-8">
            <span class="text-[10px] font-bold text-gray-400 uppercase italic">Chưa có dữ liệu cú sút</span>
        </div>
    </div>
</template>

<script setup>
defineProps({
    shots: {
        type: Array,
        default: () => []
    },
    homeTeam: {
        type: Object,
        default: null
    },
    awayTeam: {
        type: Object,
        default: null
    }
});
</script>

<style scoped>
.shotmap-container {
    user-select: none;
}
</style>

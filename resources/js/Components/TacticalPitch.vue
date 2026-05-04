<template>
    <div
        class="relative transition-all duration-500 overflow-hidden"
        :class="[
            isEnlarged ? 'p-0 shadow-none border-none bg-transparent h-full' : 'bg-white dark:bg-slate-950 rounded-[2.5rem] border border-gray-100 dark:border-slate-800 p-1.5 md:p-3 shadow-2xl'
        ]"
    >

        <!-- Zoom Button (only for normal mode) -->
        <button 
            v-if="showZoom"
            @click="$emit('zoom')"
            class="absolute bottom-4 right-4 z-[60] p-2.5 bg-black/40 hover:bg-black/60 backdrop-blur-md rounded-2xl border border-white/10 text-white transition-all shadow-xl group/zoom active:scale-95"
            title="Phóng to đội hình"
        >
            <svg class="w-4 h-4 md:w-5 md:h-5 transition-transform group-hover/zoom:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
            </svg>
        </button>

        <!-- Pitch Container -->
        <div
            class="relative w-full h-full bg-[#1a3326] rounded-2xl overflow-hidden shadow-inner aspect-[1.4/1] md:aspect-[2/1] max-w-full max-h-full mx-auto"
        >
            <!-- Grass Background with Pattern -->
            <div class="absolute inset-0 bg-[#1a3326] overflow-hidden">
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
            <div class="absolute inset-[4%] border-2 border-white/15 pointer-events-none"></div>
            <div class="absolute inset-y-0 left-1/2 w-0.5 bg-white/15 -translate-x-1/2"></div>
            <div class="absolute top-1/2 left-1/2 w-24 h-24 md:w-32 md:h-32 border-2 border-white/15 rounded-full -translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute top-1/2 left-1/2 w-1.5 h-1.5 bg-white/30 rounded-full -translate-x-1/2 -translate-y-1/2"></div>

            <!-- Penalty Areas -->
            <div class="absolute top-1/2 -translate-y-1/2 left-4 w-16 h-48 md:w-24 md:h-64 border-y border-r border-white/15"></div>
            <div class="absolute top-1/2 -translate-y-1/2 left-4 w-6 h-24 md:w-10 md:h-32 border-y border-r border-white/15"></div>
            <div class="absolute top-1/2 -translate-y-1/2 right-4 w-16 h-48 md:w-24 md:h-64 border-y border-l border-white/15"></div>
            <div class="absolute top-1/2 -translate-y-1/2 right-4 w-6 h-24 md:w-10 md:h-32 border-y border-l border-white/15"></div>

            <!-- Team Info Overlays -->
            <div 
                class="absolute left-1/2 -translate-x-1/2 z-40 transition-all duration-500"
                :class="isEnlarged ? 'top-8' : 'top-4'"
            >
                <div class="px-4 py-1 bg-black/40 backdrop-blur-md rounded-full border border-white/10 shadow-2xl flex items-center justify-center">
                    <span class="text-[10px] font-bold text-white uppercase tracking-[0.3em] text-center pl-[0.3em]">Đội hình xuất phát</span>
                </div>
            </div>

            <!-- Home Rating & Formation -->
            <div 
                class="absolute left-6 z-40 flex items-center gap-3 transition-all duration-500"
                :class="isEnlarged ? 'top-8 md:top-10' : 'top-4'"
            >
                <div class="px-2.5 py-1.5 rounded-lg bg-[#2c4238]/95 border border-white/10 flex items-center gap-2 shadow-xl backdrop-blur-md">
                    <div class="w-5 h-5 rounded-md flex items-center justify-center shadow-inner" :class="getRatingClass(homeAverageRating)">
                        <span class="text-[10px] font-bold">Ø</span>
                    </div>
                    <span class="text-sm font-bold text-white tabular-nums tracking-tight">{{ homeAverageRating }}</span>
                </div>
                <div v-if="game.home_formation" class="px-2 py-1 bg-black/30 rounded border border-white/5 backdrop-blur-sm">
                    <span class="text-[10px] font-bold text-white/50 uppercase tracking-[0.2em]">{{ game.home_formation }}</span>
                </div>
            </div>

            <!-- Away Rating & Formation -->
            <div 
                class="absolute right-6 z-40 flex items-center gap-3 flex-row-reverse transition-all duration-500"
                :class="isEnlarged ? 'top-8 md:top-10' : 'top-4'"
            >
                <div class="px-2.5 py-1.5 rounded-lg bg-[#2c4238]/95 border border-white/10 flex items-center gap-2 flex-row-reverse shadow-xl backdrop-blur-md">
                    <div class="w-5 h-5 rounded-md flex items-center justify-center shadow-inner" :class="getRatingClass(awayAverageRating)">
                        <span class="text-[10px] font-bold">Ø</span>
                    </div>
                    <span class="text-sm font-bold text-white tabular-nums tracking-tight">{{ awayAverageRating }}</span>
                </div>
                <div v-if="game.away_formation" class="px-2 py-1 bg-black/30 rounded border border-white/5 backdrop-blur-sm">
                    <span class="text-[10px] font-bold text-white/50 uppercase tracking-[0.2em] text-right">{{ game.away_formation }}</span>
                </div>
            </div>

            <!-- Players Layer -->
            <div class="absolute inset-0 z-30">
                <!-- Home Team -->
                <div v-for="p in processedHomeLineup" :key="'home-' + p.id" class="absolute transition-all duration-700" :style="p.style">
                    <div class="flex flex-col items-center gap-1.5 cursor-pointer group" @click="visitPlayer(p.id)">
                        <div class="relative">
                            <div 
                                class="rounded-full border-2 border-white/20 bg-white/10 dark:bg-slate-900/50 shadow-2xl overflow-hidden group-hover:scale-110 transition-transform"
                                :class="isEnlarged ? 'w-14 h-14 md:w-24 md:h-24' : 'w-10 h-10 md:w-11 md:h-11'"
                            >
                                <img v-if="p.id" :src="`https://media.api-sports.io/football/players/${p.id}.png`" class="w-full h-full object-cover rounded-full" />
                            </div>
                            <div 
                                v-if="p.rating" 
                                class="absolute -top-1 -right-2 font-black flex items-center justify-center shadow-xl border-2 border-white/30 z-30 rounded-lg" 
                                :class="[
                                    getRatingClass(p.rating),
                                    isEnlarged ? 'w-10 h-6 text-[12px]' : 'w-6 h-4 text-[8px]'
                                ]"
                            >
                                {{ p.rating }}
                            </div>
                        </div>
                        <div class="flex flex-col items-center">
                            <div class="bg-black/50 px-2 py-0.5 rounded backdrop-blur-sm border border-white/10 group-hover:bg-emerald-500 transition-colors">
                                <span 
                                    class="font-bold text-white tracking-tight drop-shadow-lg truncate block"
                                    :class="isEnlarged ? 'text-[11px] md:text-sm max-w-[120px]' : 'text-[9px] md:text-[10px] max-w-[70px]'"
                                >{{ p.name.split(" ").pop() }}</span>
                            </div>
                            <span 
                                class="font-bold text-white/75 uppercase tracking-widest mt-0.5 drop-shadow-md"
                                :class="isEnlarged ? 'text-[11px]' : 'text-[8px]'"
                            >{{ p.number }}</span>
                        </div>
                    </div>
                </div>

                <!-- Away Team -->
                <div v-for="p in processedAwayLineup" :key="'away-' + p.id" class="absolute transition-all duration-700" :style="p.style">
                    <div class="flex flex-col items-center gap-1.5 cursor-pointer group" @click="visitPlayer(p.id)">
                        <div class="relative">
                            <div 
                                class="rounded-full border-2 border-white/20 bg-white/10 dark:bg-slate-900/50 shadow-2xl overflow-hidden group-hover:scale-110 transition-transform"
                                :class="isEnlarged ? 'w-14 h-14 md:w-24 md:h-24' : 'w-10 h-10 md:w-11 md:h-11'"
                            >
                                <img v-if="p.id" :src="`https://media.api-sports.io/football/players/${p.id}.png`" class="w-full h-full object-cover rounded-full" />
                            </div>
                            <div 
                                v-if="p.rating" 
                                class="absolute -top-1 -left-2 font-black flex items-center justify-center shadow-xl border-2 border-white/30 z-40 rounded-lg" 
                                :class="[
                                    getRatingClass(p.rating),
                                    isEnlarged ? 'w-10 h-6 text-[12px]' : 'w-6 h-4 text-[8px]'
                                ]"
                            >
                                {{ p.rating }}
                            </div>
                        </div>
                        <div class="flex flex-col items-center">
                            <div class="bg-black/50 px-2 py-0.5 rounded backdrop-blur-sm border border-white/10 group-hover:bg-emerald-500 transition-colors">
                                <span 
                                    class="font-bold text-white tracking-tight drop-shadow-lg truncate block"
                                    :class="isEnlarged ? 'text-[11px] md:text-sm max-w-[120px]' : 'text-[9px] md:text-[10px] max-w-[70px]'"
                                >{{ p.name.split(" ").pop() }}</span>
                            </div>
                            <span 
                                class="font-bold text-white/75 uppercase tracking-widest mt-0.5 drop-shadow-md"
                                :class="isEnlarged ? 'text-[11px]' : 'text-[8px]'"
                            >{{ p.number }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { router } from "@inertiajs/vue3";

const props = defineProps({
    game: Object,
    homeAverageRating: [Number, String],
    awayAverageRating: [Number, String],
    processedHomeLineup: Array,
    processedAwayLineup: Array,
    showZoom: {
        type: Boolean,
        default: true
    },
    isEnlarged: {
        type: Boolean,
        default: false
    }
});

defineEmits(['zoom', 'close']);

const getRatingClass = (rating) => {
    const r = parseFloat(rating);
    if (r >= 8) return "bg-emerald-500 text-white";
    if (r >= 7) return "bg-green-500 text-white";
    if (r >= 6) return "bg-yellow-500 text-white";
    return "bg-red-500 text-white";
};

const visitPlayer = (id) => {
    router.visit(`/players/${id}`);
};
</script>

<style scoped>
img {
    image-rendering: -webkit-optimize-contrast;
    image-rendering: crisp-edges;
}
</style>

<template>
    <div class="shotmap-container bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-white/5 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Bản đồ cú sút (Shotmap)</h3>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5">
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50"></div>
                    <span class="text-[9px] font-bold text-gray-400 uppercase">Bàn thắng</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="w-2.5 h-2.5 rounded-full bg-gray-400"></div>
                    <span class="text-[9px] font-bold text-gray-400 uppercase">Khác</span>
                </div>
            </div>
        </div>

        <div class="relative aspect-[105/68] w-full bg-emerald-600/10 dark:bg-emerald-500/5 rounded-xl overflow-hidden border border-emerald-500/20">
            <!-- Pitch Markings (Simplified) -->
            <div class="absolute inset-0 border-2 border-white/20 m-4"></div>
            <div class="absolute inset-y-0 left-1/2 w-px bg-white/20"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-24 h-24 border border-white/20 rounded-full"></div>
            
            <!-- Penalty Areas -->
            <div class="absolute top-1/4 left-4 w-20 h-1/2 border border-white/20"></div>
            <div class="absolute top-1/4 right-4 w-20 h-1/2 border border-white/20"></div>

            <!-- Shots -->
            <div 
                v-for="shot in shots" 
                :key="shot.id"
                class="absolute w-3 h-3 -translate-x-1/2 -translate-y-1/2 cursor-pointer group"
                :style="{ left: shot.x + '%', top: shot.y + '%' }"
            >
                <div 
                    class="w-full h-full rounded-full border-2 border-white shadow-sm transition-transform group-hover:scale-150 z-10"
                    :class="shot.type === 'goal' ? 'bg-emerald-500' : 'bg-gray-400'"
                ></div>
                
                <!-- Tooltip -->
                <div class="opacity-0 group-hover:opacity-100 absolute bottom-full left-1/2 -translate-x-1/2 mb-2 bg-gray-900 text-white text-[9px] px-2 py-1 rounded whitespace-nowrap z-50 pointer-events-none">
                    <div class="font-bold">{{ shot.player_name || 'Cầu thủ' }}</div>
                    <div>xG: {{ shot.xg ? shot.xg.toFixed(2) : 'N/A' }}</div>
                    <div class="capitalize text-gray-400">{{ shot.type }}</div>
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
    }
});
</script>

<style scoped>
.shotmap-container {
    user-select: none;
}
</style>

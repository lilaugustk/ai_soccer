<template>
    <div class="momentum-container bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-white/5 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Biểu đồ thế trận (Momentum)</h3>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-1.5">
                    <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-tighter">Chủ nhà</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-tighter">Đội khách</span>
                </div>
            </div>
        </div>

        <div class="relative h-40 w-full mt-4 flex items-end gap-[2px]">
            <!-- Zero Line -->
            <div class="absolute top-1/2 left-0 right-0 h-px bg-gray-100 dark:bg-white/10 z-0"></div>

            <!-- Momentum Bars -->
            <div 
                v-for="(val, index) in momentum" 
                :key="index"
                class="flex-1 relative group"
                :style="{ height: '100%' }"
            >
                <!-- Home Momentum (Up) -->
                <div 
                    v-if="val > 0"
                    class="absolute bottom-1/2 left-0 right-0 bg-emerald-500/60 group-hover:bg-emerald-500 transition-all rounded-t-sm"
                    :style="{ height: (val) + '%' }"
                >
                    <div class="opacity-0 group-hover:opacity-100 absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[8px] px-1.5 py-0.5 rounded pointer-events-none whitespace-nowrap z-50">
                        P{{ index + 1 }}: +{{ val }}
                    </div>
                </div>

                <!-- Away Momentum (Down) -->
                <div 
                    v-if="val < 0"
                    class="absolute top-1/2 left-0 right-0 bg-blue-500/60 group-hover:bg-blue-500 transition-all rounded-b-sm"
                    :style="{ height: (Math.abs(val)) + '%' }"
                >
                    <div class="opacity-0 group-hover:opacity-100 absolute -bottom-8 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[8px] px-1.5 py-0.5 rounded pointer-events-none whitespace-nowrap z-50">
                        P{{ index + 1 }}: {{ val }}
                    </div>
                </div>
            </div>
        </div>
        
        <div class="flex justify-between mt-4">
            <span class="text-[8px] font-bold text-gray-400">0'</span>
            <span class="text-[8px] font-bold text-gray-400">45'</span>
            <span class="text-[8px] font-bold text-gray-400">90'</span>
        </div>
    </div>
</template>

<script setup>
defineProps({
    momentum: {
        type: Array,
        default: () => []
    }
});
</script>

<style scoped>
.momentum-container {
    user-select: none;
}
</style>

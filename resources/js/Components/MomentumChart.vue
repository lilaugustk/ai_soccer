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

        <div class="relative h-56 w-full mt-4 flex items-end gap-[2px]">
            <!-- Zero Line -->
            <div class="absolute top-1/2 left-0 right-0 h-px bg-gray-100 dark:bg-white/10 z-0"></div>

            <!-- Momentum Bars -->
            <div 
                v-for="(item, index) in normalizedMomentum" 
                :key="index"
                class="flex-1 relative group"
                :style="{ height: '100%' }"
            >
                <!-- Home Momentum (Up) -->
                <div 
                    v-if="item.value > 0"
                    class="absolute bottom-1/2 left-0 right-0 bg-emerald-500/60 group-hover:bg-emerald-500 transition-all rounded-t-sm"
                    :style="{ height: `calc(${(item.value / maxVal * 45)}% + 1px)` }"
                >
                    <div class="opacity-0 group-hover:opacity-100 absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[8px] px-1.5 py-0.5 rounded pointer-events-none whitespace-nowrap z-50">
                        Phút {{ item.minute }}: +{{ item.value }}
                    </div>
                </div>

                <!-- Away Momentum (Down) -->
                <div 
                    v-if="item.value < 0"
                    class="absolute top-1/2 left-0 right-0 bg-blue-500/60 group-hover:bg-blue-500 transition-all rounded-b-sm"
                    :style="{ height: `calc(${(Math.abs(item.value) / maxVal * 45)}% + 1px)` }"
                >
                    <div class="opacity-0 group-hover:opacity-100 absolute -bottom-8 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[8px] px-1.5 py-0.5 rounded pointer-events-none whitespace-nowrap z-50">
                        Phút {{ item.minute }}: {{ item.value }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    momentum: {
        type: Array,
        default: () => []
    }
});

// Normalize the momentum prop to always be an array of {minute, value} objects.
// This provides backward-compatibility if old number-only arrays are loaded from cache.
const normalizedMomentum = computed(() => {
    return props.momentum.map((item, index) => {
        if (typeof item === 'object' && item !== null) {
            return {
                minute: item.minute !== undefined ? item.minute : index + 1,
                value: item.value !== undefined ? Number(item.value) : 0
            };
        }
        return {
            minute: index + 1,
            value: Number(item) || 0
        };
    });
});

const maxVal = computed(() => {
    if (!normalizedMomentum.value.length) return 100;
    const max = Math.max(...normalizedMomentum.value.map(item => Math.abs(item.value)));
    return max > 0 ? max : 100;
});

const index45 = computed(() => {
    if (!normalizedMomentum.value.length) return -1;
    return normalizedMomentum.value.findIndex(item => item.minute === 45);
});

const index90 = computed(() => {
    if (!normalizedMomentum.value.length) return -1;
    return normalizedMomentum.value.findIndex(item => item.minute === 90);
});
</script>

<style scoped>
.momentum-container {
    user-select: none;
}
</style>

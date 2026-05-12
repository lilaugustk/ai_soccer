<template>
    <div class="heatmap-container bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-white/5 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Bản đồ nhiệt (Heatmap)</h3>
            <div class="flex items-center gap-2">
                <span class="text-[8px] font-bold text-gray-400 uppercase">Thấp</span>
                <div class="w-20 h-1.5 rounded-full bg-gradient-to-r from-emerald-500/20 via-yellow-500 to-red-500"></div>
                <span class="text-[8px] font-bold text-gray-400 uppercase">Cao</span>
            </div>
        </div>

        <div class="relative aspect-[105/68] w-full rounded-xl overflow-hidden border border-emerald-500/20">
            <!-- Pitch Background -->
            <div class="absolute inset-0 bg-emerald-950/10 dark:bg-emerald-500/5">
                 <div class="absolute inset-0 border-2 border-white/10 m-4"></div>
                 <div class="absolute inset-y-0 left-1/2 w-px bg-white/10"></div>
            </div>

            <!-- Heatmap Canvas -->
            <canvas ref="heatmapCanvas" class="absolute inset-0 w-full h-full opacity-80 mix-blend-multiply dark:mix-blend-screen"></canvas>
        </div>

        <div v-if="!points.length" class="mt-4 text-center py-8">
            <span class="text-[10px] font-bold text-gray-400 uppercase italic">Chưa có dữ liệu bản đồ nhiệt</span>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
    points: {
        type: Array,
        default: () => [] // Array of [x, y, value]
    }
});

const heatmapCanvas = ref(null);

const renderHeatmap = () => {
    const canvas = heatmapCanvas.value;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const { width, height } = canvas.getBoundingClientRect();
    canvas.width = width;
    canvas.height = height;

    ctx.clearRect(0, 0, width, height);

    if (!props.points.length) return;

    // Simple radial gradient approach
    props.points.forEach(point => {
        const [x, y, val] = point;
        const px = (x / 100) * width;
        const py = (y / 100) * height;
        const radius = 20 * (val / 100 + 0.5);

        const gradient = ctx.createRadialGradient(px, py, 0, px, py, radius);
        gradient.addColorStop(0, `rgba(255, 0, 0, ${val / 100})`);
        gradient.addColorStop(0.5, `rgba(255, 255, 0, ${val / 200})`);
        gradient.addColorStop(1, 'rgba(0, 255, 0, 0)');

        ctx.fillStyle = gradient;
        ctx.fillRect(px - radius, py - radius, radius * 2, radius * 2);
    });
};

onMounted(() => {
    renderHeatmap();
});

watch(() => props.points, () => {
    renderHeatmap();
}, { deep: true });
</script>

<style scoped>
.heatmap-container {
    user-select: none;
}
</style>

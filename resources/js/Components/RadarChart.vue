<template>
    <div class="relative w-full aspect-square max-w-[320px] mx-auto group">
        <svg viewBox="0 0 100 100" class="w-full h-full transform -rotate-90 overflow-visible">
            <!-- Background Polygons (Grid) -->
            <polygon
                v-for="i in 5"
                :key="'grid-' + i"
                :points="getPolygonPoints(i * 20)"
                class="fill-none stroke-gray-200 dark:stroke-white/10 stroke-[0.2]"
            />

            <!-- Axes -->
            <line
                v-for="(axis, index) in axes"
                :key="'axis-' + index"
                x1="50"
                y1="50"
                :x2="getPoint(100, index).x"
                :y2="getPoint(100, index).y"
                class="stroke-gray-200 dark:stroke-white/10 stroke-[0.2]"
            />

            <!-- Home Data Polygon -->
            <polygon
                :points="getDataPoints('home')"
                class="fill-emerald-500/10 stroke-emerald-500 stroke-[0.4] transition-all duration-1000"
            />
            
            <!-- Away Data Polygon -->
            <polygon
                :points="getDataPoints('away')"
                class="fill-blue-500/10 stroke-blue-500 stroke-[0.4] transition-all duration-1000"
            />

            <!-- Interactive Points -->
            <!-- Home Points -->
            <circle
                v-for="(p, index) in getDataArray('home')"
                :key="'h-point-' + index"
                :cx="p.x"
                :cy="p.y"
                :r="hoveredPoint?.index === index && hoveredPoint?.team === 'home' ? 2 : 1.2"
                class="fill-emerald-500 cursor-pointer transition-all duration-200"
                @mouseenter="hoveredPoint = { index, team: 'home', value: p.value, label: axes[index].label, x: p.x, y: p.y }"
                @mouseleave="hoveredPoint = null"
            />

            <!-- Away Points -->
            <circle
                v-for="(p, index) in getDataArray('away')"
                :key="'a-point-' + index"
                :cx="p.x"
                :cy="p.y"
                :r="hoveredPoint?.index === index && hoveredPoint?.team === 'away' ? 2 : 1.2"
                class="fill-blue-500 cursor-pointer transition-all duration-200"
                @mouseenter="hoveredPoint = { index, team: 'away', value: p.value, label: axes[index].label, x: p.x, y: p.y }"
                @mouseleave="hoveredPoint = null"
            />
        </svg>

        <!-- Tooltip -->
        <div 
            v-if="hoveredPoint"
            class="absolute z-50 pointer-events-none bg-gray-900/90 dark:bg-white text-white dark:text-gray-900 px-2 py-1 rounded text-[10px] font-bold shadow-xl border border-white/10 whitespace-nowrap transform -translate-x-1/2 -translate-y-full mb-2 transition-opacity duration-200"
            :style="{ left: `${hoveredPoint.x}%`, top: `${hoveredPoint.y}%`, marginTop: '-8px' }"
        >
            <div class="flex items-center gap-1.5">
                <div :class="['w-1.5 h-1.5 rounded-full', hoveredPoint.team === 'home' ? 'bg-emerald-400' : 'bg-blue-400']"></div>
                {{ hoveredPoint.label }}: {{ hoveredPoint.value }}%
            </div>
        </div>

        <!-- Labels -->
        <div 
            v-for="(axis, index) in axes" 
            :key="'label-' + index"
            class="absolute text-[7px] font-bold uppercase tracking-tighter text-slate-400 dark:text-slate-500 whitespace-nowrap transform -translate-x-1/2 -translate-y-1/2"
            :style="getLabelStyle(index)"
        >
            {{ axis.label }}
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    data: {
        type: Object,
        required: true
    },
    labels: {
        type: Object,
        default: () => ({
            total: 'Sức mạnh',
            form: 'Phong độ',
            att: 'Tấn công',
            def: 'Phòng ngự',
            poisson_distribution: 'Poisson',
            h2h: 'Đối đầu',
            goals: 'Bàn thắng'
        })
    }
});

const hoveredPoint = ref(null);

const axes = computed(() => {
    return Object.keys(props.labels).map(key => ({
        key,
        label: props.labels[key]
    }));
});

const parseVal = (val) => {
    if (!val) return 0;
    return parseFloat(val.toString().replace('%', '')) || 0;
};

const getPoint = (radius, index) => {
    const angle = (Math.PI * 2 * index) / axes.value.length;
    return {
        x: 50 + (radius / 2) * Math.cos(angle),
        y: 50 + (radius / 2) * Math.sin(angle)
    };
};

const getPolygonPoints = (radius) => {
    return axes.value.map((_, index) => {
        const p = getPoint(radius, index);
        return `${p.x},${p.y}`;
    }).join(' ');
};

const getDataArray = (team) => {
    return axes.value.map((axis, index) => {
        const value = parseVal(props.data[axis.key]?.[team]);
        const p = getPoint(value, index);
        return { ...p, value };
    });
};

const getDataPoints = (team) => {
    return getDataArray(team).map(p => `${p.x},${p.y}`).join(' ');
};

const getLabelStyle = (index) => {
    const angle = (Math.PI * 2 * index) / axes.value.length;
    const adjustedAngle = angle - Math.PI / 2;
    const radius = 58; 
    const x = 50 + radius * Math.cos(adjustedAngle);
    const y = 50 + radius * Math.sin(adjustedAngle);
    
    return {
        left: `${x}%`,
        top: `${y}%`
    };
};
</script>

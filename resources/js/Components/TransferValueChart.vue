<script setup>
import { computed } from 'vue';
import dayjs from 'dayjs';

const props = defineProps({
    transfers: {
        type: Array,
        default: () => []
    }
});

// Helper to extract value from string like "€ 60M" or "£ 50M"
const extractValue = (typeStr, previousVal = 0) => {
    if (!typeStr || typeStr === 'N/A') return previousVal || 1;
    const match = typeStr.match(/[\d.]+/);
    if (match) return parseFloat(match[0]);
    return previousVal || 1; // Free/Loan stays at previous or starts at 1
};

const chartData = computed(() => {
    if (!props.transfers || props.transfers.length === 0) return null;
    
    // Sort oldest to newest
    const sorted = [...props.transfers].sort((a, b) => new Date(a.date) - new Date(b.date));
    
    let currentVal = 0;
    const points = [];
    
    // We want to map dates to X coords. Let's find min and max year.
    let minDate = dayjs(sorted[0].date).subtract(6, 'month');
    let maxDate = dayjs().add(6, 'month');
    const totalDays = maxDate.diff(minDate, 'day');
    
    // Generate base points
    sorted.forEach((t, index) => {
        const d = dayjs(t.date);
        const daysSinceStart = d.diff(minDate, 'day');
        const x = Math.min(100, Math.max(0, (daysSinceStart / totalDays) * 100));
        
        // Extract value
        let val = extractValue(t.type, currentVal);
        
        // Logic: if it's a "Free" or "Loan" and we have a previous value, 
        // we assume the player's value didn't drop to zero, but maybe grew slightly or stayed same.
        if ((t.type.toLowerCase().includes('free') || t.type.toLowerCase().includes('loan')) && currentVal > 0) {
            val = currentVal; 
        }
        
        // Special case: if this is the first transfer and it's N/A, start very low
        if (index === 0 && (!t.type || t.type === 'N/A')) {
            val = 0.5; // 500k
        }

        currentVal = val;
        
        points.push({
            id: index,
            date: t.date,
            year: d.year(),
            x: x,
            val: currentVal,
            team: t.teams?.in,
            type: t.type
        });
    });
    
    // Add a final point for "today"
    const today = dayjs();
    const daysSinceStartToday = today.diff(minDate, 'day');
    points.push({
        id: 'now',
        date: today.format('YYYY-MM-DD'),
        year: today.year(),
        x: Math.min(100, (daysSinceStartToday / totalDays) * 100),
        val: currentVal, // flat until now
        team: sorted[sorted.length - 1].teams?.in,
        type: 'Current'
    });
    
    // Find max value for Y scaling
    const maxVal = Math.max(...points.map(p => p.val)) * 1.3 || 10;
    
    const smoothPoints = points.map(p => ({
        ...p,
        y: 100 - (p.val / maxVal * 100)
    }));
    
    // Build SVG Path using plateau curve
    let pathD = `M ${smoothPoints[0].x} ${smoothPoints[0].y} `;
    for (let i = 1; i < smoothPoints.length; i++) {
        const pPrev = smoothPoints[i-1];
        const p = smoothPoints[i];
        const c1X = pPrev.x + (p.x - pPrev.x) * 0.6;
        const c1Y = pPrev.y;
        const c2X = pPrev.x + (p.x - pPrev.x) * 0.8;
        const c2Y = p.y;
        pathD += `C ${c1X} ${c1Y}, ${c2X} ${c2Y}, ${p.x} ${p.y} `;
    }
    
    // Area path
    const areaD = `${pathD} L ${smoothPoints[smoothPoints.length-1].x} 100 L ${smoothPoints[0].x} 100 Z`;
    
    // Year labels - show every 2 years or so to avoid crowding
    const years = [];
    const startYear = minDate.year();
    const endYear = maxDate.year();
    for (let y = startYear; y <= endYear; y++) {
        const d = dayjs(`${y}-01-01`).diff(minDate, 'day');
        years.push({
            year: y,
            x: (d / totalDays) * 100
        });
    }
    
    return {
        points: points,
        maxVal,
        pathD,
        areaD,
        years,
        currentValue: points[points.length - 1].val.toFixed(1),
        highestValue: Math.max(...points.map(p => p.val)).toFixed(1)
    };
});
</script>

<template>
    <div v-if="chartData" class="bg-[#1a1d24] dark:bg-gray-900 rounded-2xl p-6 border border-gray-800 shadow-xl overflow-hidden text-white relative w-full mb-6">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-6 mb-8 relative z-10">
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-gray-200">Transfer value:</span>
                <span class="text-xl font-extrabold text-white">€{{ chartData.currentValue }}M</span>
                <button class="w-4 h-4 rounded-full bg-gray-700 text-[10px] flex items-center justify-center text-gray-300 ml-1">?</button>
            </div>
            <div class="w-px h-5 bg-gray-700 hidden sm:block"></div>
            <div class="text-sm text-gray-400">
                Highest: €{{ chartData.highestValue }}M
            </div>
        </div>

        <!-- Chart Area -->
        <div class="relative w-full h-[250px] sm:h-[300px]">
            <!-- Y Axis Lines -->
            <div class="absolute inset-0 flex flex-col justify-between pointer-events-none">
                <div v-for="i in 5" :key="i" class="w-full border-t border-gray-700/50 border-dashed h-0 flex items-center">
                    <span class="text-[10px] font-bold text-gray-500 bg-[#1a1d24] dark:bg-gray-900 pr-2 absolute -mt-2">
                        €{{ Math.round((chartData.maxVal / 4) * (5 - i)) }}M
                    </span>
                </div>
            </div>

            <!-- SVG Graph -->
            <div class="absolute inset-0 pl-10 pb-6">
                <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="w-full h-full overflow-visible">
                    <defs>
                        <linearGradient id="areaGradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.3" />
                            <stop offset="100%" stop-color="#3b82f6" stop-opacity="0.0" />
                        </linearGradient>
                    </defs>
                    
                    <!-- Fill Area -->
                    <path :d="chartData.areaD" fill="url(#areaGradient)" />
                    
                    <!-- Line -->
                    <path :d="chartData.pathD" fill="none" stroke="#3b82f6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" class="drop-shadow-md" />
                </svg>

                <!-- Transfer Nodes (Logos) -->
                <div class="absolute inset-0">
                    <template v-for="node in chartData.points" :key="node.id">
                        <div v-if="node.id !== 'now' && node.team?.logo"
                             class="absolute transform -translate-x-1/2 flex flex-col items-center group cursor-pointer transition-all hover:z-50"
                             :style="{ left: `${node.x}%`, top: `calc(${100 - (node.val / chartData.maxVal * 100)}% - 16px)` }">
                            
                            <!-- Logo -->
                            <div class="w-8 h-8 rounded-full bg-gray-800 border-2 border-gray-600 overflow-hidden p-1 shadow-lg transition-transform group-hover:scale-125 group-hover:border-blue-500">
                                <img :src="node.team?.logo" class="w-full h-full object-contain" />
                            </div>
                            
                            <!-- Tooltip -->
                            <div class="opacity-0 group-hover:opacity-100 absolute bottom-full mb-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-[10px] font-bold px-3 py-1.5 rounded-lg shadow-xl whitespace-nowrap pointer-events-none transition-opacity">
                                {{ node.team.name }}<br>
                                <span class="text-emerald-500">{{ node.type }}</span>
                                <div class="absolute -bottom-1 left-1/2 -translate-x-1/2 border-4 border-transparent border-t-white dark:border-t-gray-800"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- X Axis Years -->
            <div class="absolute bottom-0 left-10 right-0 flex pointer-events-none">
                <div v-for="year in chartData.years" :key="year.year"
                     class="absolute transform -translate-x-1/2 text-[10px] font-bold text-gray-500"
                     :style="{ left: `${year.x}%` }">
                    {{ year.year }}
                </div>
            </div>
        </div>

    </div>
</template>

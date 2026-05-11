<template>
    <div class="space-y-6">
        <!-- Map Container -->
        <div class="relative bg-white dark:bg-gray-800 rounded-[32px] border border-gray-100 dark:border-gray-700 shadow-xl overflow-hidden h-[500px] group">
            <div id="map" class="w-full h-full z-10"></div>
            
            <!-- Map Mode Toggle -->
            <div class="absolute bottom-6 right-6 z-[20] flex items-center bg-white/90 dark:bg-gray-900/90 backdrop-blur-xl rounded-2xl border border-gray-100 dark:border-gray-700 p-1 shadow-2xl transition-all duration-300"
                 :class="{ 'opacity-0 pointer-events-none translate-x-10': selectedStadium }">
                <button @click="mapMode = 'street'" 
                        :class="['px-4 py-2 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all duration-300', 
                                 mapMode === 'street' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-500/20' : 'text-gray-500 hover:text-emerald-600']">
                    Bản đồ
                </button>
                <button @click="mapMode = 'satellite'" 
                        :class="['px-4 py-2 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all duration-300', 
                                 mapMode === 'satellite' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-500/20' : 'text-gray-500 hover:text-emerald-600']">
                    Vệ tinh
                </button>
            </div>
            
            <!-- Country Legend -->
            <div class="absolute bottom-6 left-6 z-[20] bg-white/90 dark:bg-gray-900/90 backdrop-blur-xl rounded-2xl border border-gray-100 dark:border-gray-700 p-4 shadow-2xl space-y-2.5">
                <div v-for="stat in countryStats" :key="stat.name" class="flex items-center gap-3">
                    <div :class="[stat.color, stat.glow, 'w-2 h-2 rounded-full shadow-[0_0_8px]']"></div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-[9px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ stat.name === 'United States' ? 'Hoa Kỳ' : stat.name }} —</span>
                        <span class="text-[10px] font-bold text-gray-900 dark:text-white uppercase">{{ stat.count }} sân vận động</span>
                    </div>
                </div>
            </div>


            <!-- Stadium Detail Panel (Floating) -->
                <div v-if="selectedStadium" 
                     class="absolute top-6 bottom-6 right-6 w-[320px] lg:w-[380px] z-[9999] bg-white dark:bg-gray-900 rounded-[32px] border border-gray-100 dark:border-gray-700 shadow-2xl overflow-hidden flex flex-col">
                    <div class="relative aspect-[16/9]">
                        <img :src="selectedStadium.image || 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?q=80&w=2084&auto=format&fit=crop'" 
                             class="w-full h-full object-cover" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                        <button @click="selectedStadium = null" class="absolute top-4 right-4 p-2 bg-black/20 hover:bg-black/40 rounded-full text-white backdrop-blur-md transition-colors z-[10]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <div class="absolute bottom-4 left-6 right-6 flex items-end justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <h4 class="text-white font-semibold uppercase tracking-widest text-[13px] truncate">{{ selectedStadium.name }}</h4>
                                <p class="text-white/70 text-[10px] font-medium uppercase truncate">{{ selectedStadium.city }}, {{ selectedStadium.country }}</p>
                            </div>
                            <div class="shrink-0 text-right pb-1">
                                <div class="text-white font-semibold uppercase tracking-widest text-[13px]">
                                    {{ selectedStadium.capacity?.toLocaleString() || 'TBD' }}
                                    <span class="text-[10px] text-white/60 ml-1">CHỖ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">

                        <!-- Matches in this Stadium -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-2">
                                <div class="w-1 h-4 bg-emerald-600 rounded-full"></div>
                                <h5 class="text-[10px] font-bold text-gray-900 dark:text-white uppercase tracking-widest">Trận đấu diễn ra tại đây</h5>
                            </div>
                            
                            <div v-if="stadiumMatches.length > 0" class="space-y-3">
                                <div v-for="match in stadiumMatches" :key="match.id" 
                                     class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-2xl border border-gray-100 dark:border-gray-700/50 flex items-center justify-between">
                                    <div class="flex items-center gap-3 flex-1">
                                        <div class="flex flex-col items-center gap-1 w-8">
                                            <img :src="match.homeFlag" class="w-5 h-5 object-contain" />
                                            <span class="text-[8px] font-bold text-gray-400 uppercase">{{ match.home_code || match.home.substring(0,3) }}</span>
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <span class="text-[9px] font-bold text-gray-900 dark:text-white">{{ match.time }}</span>
                                            <span class="text-[8px] font-bold text-gray-400">{{ match.date }}</span>
                                        </div>
                                        <div class="flex flex-col items-center gap-1 w-8">
                                            <img :src="match.awayFlag" class="w-5 h-5 object-contain" />
                                            <span class="text-[8px] font-bold text-gray-400 uppercase">{{ match.away_code || match.away.substring(0,3) }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[8px] font-bold text-emerald-600 uppercase tracking-widest bg-emerald-50 dark:bg-emerald-500/10 px-2 py-0.5 rounded-full">{{ match.round }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-4">
                                <p class="text-[10px] font-bold text-gray-400 uppercase italic">Chưa có lịch thi đấu cụ thể</p>
                            </div>
                        </div>
                    </div>
                </div>
        </div>

        <!-- Stadium Quick List (Horizontal Scroll) -->
        <div class="relative group/list mt-8">
            <div ref="scrollContainer" 
                 @mousedown="startDragging"
                 @mouseleave="stopDragging"
                 @mouseup="stopDragging"
                 @mousemove="onDragging"
                 :class="[
                    'flex gap-4 overflow-x-auto no-scrollbar -mx-2 px-2 pb-4 select-none',
                    isDragging ? 'cursor-grabbing scroll-auto' : 'cursor-grab scroll-smooth'
                 ]">
                <button v-for="stadium in stadiums" :key="stadium.id"
                        @click="!isDragging && focusStadium(stadium)"
                        :class="[
                            'flex-shrink-0 px-4 py-3 rounded-2xl border transition-all duration-300 flex items-center gap-3',
                            selectedStadium?.id === stadium.id 
                                ? 'bg-emerald-600 border-emerald-600 text-white shadow-lg shadow-emerald-500/20' 
                                : 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-emerald-500/50'
                        ]">
                    <div class="w-8 h-8 rounded-xl bg-gray-100 dark:bg-gray-700/50 flex items-center justify-center overflow-hidden pointer-events-none">
                        <img :src="stadium.image || 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?q=80&w=2084&auto=format&fit=crop'" 
                             class="w-full h-full object-cover" />
                    </div>
                    <div class="text-left pointer-events-none">
                        <p class="text-[10px] font-semibold uppercase tracking-tight">{{ stadium.name }}</p>
                        <p class="text-[8px] font-bold opacity-70 uppercase">{{ stadium.city }}</p>
                    </div>
                </button>
            </div>

            <!-- Navigation Buttons -->
            <button @click="scroll('left')" class="absolute left-0 top-[40%] -translate-y-1/2 -translate-x-2 w-8 h-8 bg-white/90 dark:bg-gray-800/90 rounded-full border border-gray-100 dark:border-gray-700 shadow-lg flex items-center justify-center text-gray-400 hover:text-emerald-600 opacity-0 group-hover/list:opacity-100 transition-opacity z-20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button @click="scroll('right')" class="absolute right-0 top-[40%] -translate-y-1/2 translate-x-2 w-8 h-8 bg-white/90 dark:bg-gray-800/90 rounded-full border border-gray-100 dark:border-gray-700 shadow-lg flex items-center justify-center text-gray-400 hover:text-emerald-600 opacity-0 group-hover/list:opacity-100 transition-opacity z-20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';

const props = defineProps({
    stadiums: Array,
    matches: Array
});

const selectedStadium = ref(null);
const map = ref(null);
const markers = ref([]);
const scrollContainer = ref(null);
const isDragging = ref(false);
const startX = ref(0);
const scrollLeft = ref(0);
const mapMode = ref('street'); // 'street' or 'satellite'
const currentTileLayer = ref(null);



const startDragging = (e) => {
    isDragging.value = true;
    startX.value = e.pageX - scrollContainer.value.offsetLeft;
    scrollLeft.value = scrollContainer.value.scrollLeft;
};

const stopDragging = () => {
    isDragging.value = false;
};

const onDragging = (e) => {
    if (!isDragging.value) return;
    e.preventDefault();
    const x = e.pageX - scrollContainer.value.offsetLeft;
    const walk = (x - startX.value) * 2; // Scroll speed
    scrollContainer.value.scrollLeft = scrollLeft.value - walk;
};

const scroll = (direction) => {
    if (!scrollContainer.value) return;
    const scrollAmount = 300;
    scrollContainer.value.scrollBy({
        left: direction === 'left' ? -scrollAmount : scrollAmount,
        behavior: 'smooth'
    });
};

const stadiumMatches = computed(() => {
    if (!selectedStadium.value || !props.matches) return [];
    return props.matches.filter(m => m.stadium_id == selectedStadium.value.id);
});

const countryStats = computed(() => {
    const stats = {
        'United States': 0,
        'Mexico': 0,
        'Canada': 0
    };
    props.stadiums.forEach(s => {
        if (s.country === 'USA' || s.country === 'United States') stats['United States']++;
        else if (s.country === 'Mexico') stats['Mexico']++;
        else if (s.country === 'Canada') stats['Canada']++;
    });
    return [
        { name: 'United States', count: stats['United States'], color: 'bg-rose-500', glow: 'shadow-rose-500/50' },
        { name: 'Mexico', count: stats['Mexico'], color: 'bg-amber-500', glow: 'shadow-amber-500/50' },
        { name: 'Canada', count: stats['Canada'], color: 'bg-pink-500', glow: 'shadow-pink-500/50' }
    ];
});

const getNextMatchForStadium = (stadiumId) => {
    if (!props.matches) return null;
    const now = new Date();
    return props.matches
        .filter(m => m.stadium_id == stadiumId)
        .sort((a, b) => new Date(a.kickoff_utc) - new Date(b.kickoff_utc))
        .find(m => new Date(m.kickoff_utc) >= now) || null;
};

const loadLeaflet = () => {
    return new Promise((resolve, reject) => {
        if (window.L) {
            resolve();
            return;
        }

        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(link);

        const script = document.createElement('script');
        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload = resolve;
        script.onerror = () => {
            console.error('Failed to load Leaflet script');
            reject(new Error('Leaflet load failed'));
        };
        document.head.appendChild(script);
    });
};

const getCountryColor = (country) => {
    if (country === 'USA' || country === 'United States') return { tailwind: 'rose-500', hex: '#f43f5e' };
    if (country === 'Mexico') return { tailwind: 'amber-500', hex: '#f59e0b' };
    if (country === 'Canada') return { tailwind: 'pink-500', hex: '#ec4899' };
    return { tailwind: 'emerald-500', hex: '#10b981' };
};

const initMap = async () => {
    await loadLeaflet();
    
    // Initial center on North America
    map.value = L.map('map', {
        zoomControl: false,
        attributionControl: false
    }).setView([37.0902, -95.7129], 4);

    // Tile Layers
    updateTileLayer();


    // Add Markers
    props.stadiums.forEach(stadium => {
        if (!stadium.latitude || !stadium.longitude) return;

        const colors = getCountryColor(stadium.country);
        const customIcon = L.divIcon({
            className: 'custom-marker',
            html: `<div class="w-4 h-4 bg-${colors.tailwind} rounded-full border-2 border-white dark:border-gray-900 shadow-lg animate-pulse"></div>`,
            iconSize: [16, 16],
            iconAnchor: [8, 8]
        });

        const marker = L.marker([stadium.latitude, stadium.longitude], { icon: customIcon })
            .addTo(map.value)
            .on('click', () => {
                selectStadium(stadium, marker);
            });

        // Add Permanent Tooltip (Label)
        const nextMatch = getNextMatchForStadium(stadium.id);
        const nextMatchHtml = nextMatch 
            ? `<div class="next-match">
                <div class="match-row">
                    <div class="team team-left">
                        <span class="team-name">${nextMatch.home}</span>
                        <img src="${nextMatch.homeFlag}" class="mini-flag" onerror="this.src='https://flagcdn.com/un.svg'" />
                    </div>
                    <span class="vs">vs</span>
                    <div class="team team-right">
                        <img src="${nextMatch.awayFlag}" class="mini-flag" onerror="this.src='https://flagcdn.com/un.svg'" />
                        <span class="team-name">${nextMatch.away}</span>
                    </div>
                </div>
                <div class="match-time">${nextMatch.date} • ${nextMatch.time}</div>
               </div>`
            : `<div class="no-match-info">Chưa có lịch thi đấu kế tiếp</div>`;

        const tooltipDirection = stadium.latitude > 37 ? 'bottom' : 'top';
        const tooltipOffset = tooltipDirection === 'top' ? [0, -15] : [0, 15];

        marker.bindTooltip(`
            <div class="custom-stadium-tooltip" style="--country-color: ${colors.hex}">
                <div class="city" style="color: ${colors.hex}">${stadium.city?.toUpperCase()}</div>
                <div class="details">${stadium.name} - ${stadium.capacity?.toLocaleString()}</div>
                ${nextMatchHtml}
            </div>
        `, {
            permanent: false,
            direction: tooltipDirection,
            offset: tooltipOffset,
            className: 'stadium-label-tooltip'
        });
        
        markers.value.push({ id: stadium.id, country: stadium.country, marker });
    });
};

const selectStadium = (stadium, marker) => {
    selectedStadium.value = stadium;
    
    // Update marker icons
    markers.value.forEach(m => {
        const colors = getCountryColor(m.country);
        const icon = L.divIcon({
            className: 'custom-marker',
            html: `<div class="w-4 h-4 bg-${colors.tailwind} rounded-full border-2 border-white dark:border-gray-900 shadow-lg ${m.id === stadium.id ? '' : 'animate-pulse'}"></div>`,
            iconSize: [16, 16],
            iconAnchor: [8, 8]
        });
        m.marker.setIcon(icon);
    });

    const activeColors = getCountryColor(stadium.country);
    const activeIcon = L.divIcon({
        className: 'custom-marker-active',
        html: `<div class="w-6 h-6 bg-${activeColors.tailwind} rounded-full border-2 border-white dark:border-gray-900 shadow-xl flex items-center justify-center scale-125 transition-transform duration-300">
                <div class="w-2 h-2 bg-white rounded-full"></div>
              </div>`,
        iconSize: [24, 24],
        iconAnchor: [12, 12]
    });
    marker.setIcon(activeIcon);

    // Fly to position
    map.value.flyTo([stadium.latitude, stadium.longitude], 12, {
        duration: 1.5
    });
};

const focusStadium = (stadium) => {
    const markerObj = markers.value.find(m => m.id === stadium.id);
    if (markerObj) {
        selectStadium(stadium, markerObj.marker);
    }
};

const updateTileLayer = () => {
    if (!map.value) return;

    if (currentTileLayer.value) {
        map.value.removeLayer(currentTileLayer.value);
    }

    let tileUrl = '';
    let attribution = '';

    if (mapMode.value === 'satellite') {
        tileUrl = 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';
        attribution = 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EBP, and the GIS User Community';
    } else {
        const isDark = document.documentElement.classList.contains('dark');
        tileUrl = isDark 
            ? 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'
            : 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';
        attribution = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>';
    }

    currentTileLayer.value = L.tileLayer(tileUrl, {
        maxZoom: 19,
        attribution: attribution
    }).addTo(map.value);
};

watch(mapMode, () => {
    updateTileLayer();
});

onMounted(async () => {
    await initMap();
    // Add a small delay and invalidate size to fix rendering issues in tabs/animations
    setTimeout(() => {
        if (map.value) {
            map.value.invalidateSize();
        }
    }, 500);
});


// Watch for theme changes to update map tiles
watch(() => document.documentElement.classList.contains('dark'), () => {
    if (mapMode.value === 'street') {
        updateTileLayer();
    }
});


</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

#map {
    background: transparent;
}

:deep(.stadium-label-tooltip) {
    background: #ffffff !important; /* White background for light theme */
    border: 2px solid var(--country-color, #10b981);
    border-radius: 16px;
    padding: 16px 20px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    color: #1e293b;
    opacity: 1 !important;
}

:deep(.dark .stadium-label-tooltip) {
    background: #000000 !important; /* Pure black for dark theme */
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 10px 10px -5px rgba(0, 0, 0, 0.3);
    color: #ffffff;
}

:deep(.leaflet-tooltip-top.stadium-label-tooltip:before) {
    border-top-color: var(--country-color, #10b981);
}
:deep(.leaflet-tooltip-bottom.stadium-label-tooltip:before) {
    border-bottom-color: var(--country-color, #10b981);
}
:deep(.leaflet-tooltip-left.stadium-label-tooltip:before) {
    border-left-color: var(--country-color, #10b981);
}
:deep(.leaflet-tooltip-right.stadium-label-tooltip:before) {
    border-right-color: var(--country-color, #10b981);
}

:deep(.custom-stadium-tooltip) {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    min-width: 240px;
    font-family: system-ui, -apple-system, sans-serif;
}

:deep(.custom-stadium-tooltip .city) {
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.05em;
    color: #059669; /* Slightly darker emerald for better legibility on white */
    line-height: 1.2;
    margin-bottom: 4px;
    width: 100%;
}

:deep(.dark .custom-stadium-tooltip .city) {
    color: #10b981;
}

:deep(.custom-stadium-tooltip .details) {
    font-size: 12px;
    font-weight: 500;
    color: #64748b; /* slate-500 */
    white-space: nowrap;
    margin-bottom: 12px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    padding-bottom: 8px;
    width: 100%;
}

:deep(.dark .custom-stadium-tooltip .details) {
    color: rgba(255, 255, 255, 0.6);
    border-bottom-color: rgba(255, 255, 255, 0.1);
}

:deep(.custom-stadium-tooltip .next-match) {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    width: 100%;
}

:deep(.custom-stadium-tooltip .match-row) {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    width: 100%;
}

:deep(.custom-stadium-tooltip .team) {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
}

:deep(.custom-stadium-tooltip .team-left) {
    justify-content: flex-end;
}

:deep(.custom-stadium-tooltip .team-right) {
    justify-content: flex-start;
}

:deep(.custom-stadium-tooltip .mini-flag) {
    width: 22px;
    height: 15px;
    object-fit: cover;
    border-radius: 2px;
    border: 1px solid rgba(0, 0, 0, 0.1);
}

:deep(.custom-stadium-tooltip .team-name) {
    color: #1e293b;
    font-weight: 700;
    font-size: 13px;
}

:deep(.dark .custom-stadium-tooltip .team-name) {
    color: #ffffff;
}

:deep(.custom-stadium-tooltip .vs) {
    color: #94a3b8;
    font-weight: 900;
    font-size: 10px;
    text-transform: uppercase;
}

:deep(.custom-stadium-tooltip .match-time) {
    color: #d97706; /* amber-600 for better visibility on white */
    font-weight: 600;
    font-size: 11px;
    margin-top: 2px;
}

:deep(.custom-stadium-tooltip .no-match-info) {
    color: #94a3b8;
    font-size: 11px;
    font-style: italic;
    font-weight: 500;
}

:deep(.dark .custom-stadium-tooltip .match-time) {
    color: #fbbf24;
}

.slide-right-enter-active,
.slide-right-leave-active {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-right-enter-from {
    transform: translateX(100%);
    opacity: 0;
}

.slide-right-leave-to {
    transform: translateX(100%);
    opacity: 0;
}
</style>

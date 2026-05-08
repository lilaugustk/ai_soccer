<template>
    <div class="relative group/tabs py-2 overflow-hidden">
        <div ref="containerRef" 
             @mousedown="onMouseDown"
             @mouseleave="onMouseLeave"
             @mouseup="onMouseUp"
             @mousemove="onMouseMove"
             class="overflow-x-auto no-scrollbar flex gap-2 px-1 cursor-grab active:cursor-grabbing select-none"
             :class="containerClass">
            
            <!-- Optional "All" Button with Solid Sticky Background -->
            <div v-if="showAll" class="sticky left-0 z-20 pr-4 bg-gradient-to-r from-white dark:from-gray-900 via-white dark:via-gray-900 to-transparent shrink-0">
                <!-- Left Mask to prevent any leak through padding -->
                <div class="absolute inset-y-0 -left-20 w-20 bg-white dark:bg-gray-900"></div>
                
                <button @click="select(null)"
                        class="px-4 py-2 rounded-xl text-[11px] font-bold uppercase tracking-widest transition-all border whitespace-nowrap min-w-[80px]"
                        :class="!modelValue ? activeClass : inactiveClass">
                    {{ allLabel }}
                </button>
            </div>

            <button v-for="item in items" 
                    :key="item.id"
                    :id="idPrefix + item.id"
                    @click="select(item.id)"
                    class="px-4 py-2 rounded-xl text-[11px] font-bold uppercase tracking-widest transition-all border flex items-center gap-1.5 whitespace-nowrap shrink-0"
                    :class="[
                        modelValue === item.id ? activeClass + ' scale-105' : inactiveClass,
                        itemClass
                    ]">
                <img v-if="item.logo_url || item.logo" :src="item.logo_url || item.logo" class="w-3 h-3 object-contain" />
                {{ item.name || item.label || item.id }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue';

const props = defineProps({
    items: { type: Array, required: true },
    modelValue: { type: [String, Number], default: null },
    idPrefix: { type: String, default: 'tab-' },
    showAll: { type: Boolean, default: false },
    allLabel: { type: String, default: 'TẤT CẢ' },
    activeClass: { 
        type: String, 
        default: 'bg-gray-950 dark:bg-white text-white dark:text-gray-950 border-gray-950 dark:border-white shadow-lg' 
    },
    inactiveClass: { 
        type: String, 
        default: 'bg-white/50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300 border-gray-100 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-800 hover:text-gray-950 dark:hover:text-white' 
    },
    containerClass: { type: String, default: '' },
    itemClass: { type: String, default: '' }
});

const emit = defineEmits(['update:modelValue', 'change']);

const containerRef = ref(null);
const hasScroll = ref(false);

const select = (id) => {
    // Prevent selection if user was dragging (using a small threshold)
    if (dragDistance > 10) return;
    emit('update:modelValue', id);
    emit('change', id);
};

// Drag to scroll logic
let isDown = false;
let startX;
let scrollLeft;
let dragDistance = 0;

const onMouseDown = (e) => {
    if (!containerRef.value) return;
    isDown = true;
    dragDistance = 0;
    containerRef.value.classList.add('cursor-grabbing');
    startX = e.pageX - containerRef.value.offsetLeft;
    scrollLeft = containerRef.value.scrollLeft;
};

const onMouseLeave = () => {
    isDown = false;
    if (containerRef.value) containerRef.value.classList.remove('cursor-grabbing');
};

const onMouseUp = () => {
    isDown = false;
    if (containerRef.value) containerRef.value.classList.remove('cursor-grabbing');
};

const onMouseMove = (e) => {
    if (!isDown || !containerRef.value) return;
    e.preventDefault();
    const x = e.pageX - containerRef.value.offsetLeft;
    const walk = (x - startX) * 2;
    containerRef.value.scrollLeft = scrollLeft - walk;
    dragDistance += Math.abs(x - startX);
};

const scroll = (direction) => {
    if (!containerRef.value) return;
    const scrollAmount = 300;
    containerRef.value.scrollBy({
        left: direction === 'left' ? -scrollAmount : scrollAmount,
        behavior: 'smooth'
    });
};

const checkScroll = () => {
    if (!containerRef.value) return;
    hasScroll.value = containerRef.value.scrollWidth > containerRef.value.clientWidth;
};

// Auto-scroll to active item
const scrollIntoView = (id) => {
    if (!id || !containerRef.value) return;
    nextTick(() => {
        const activeItem = document.getElementById(props.idPrefix + id);
        if (activeItem && containerRef.value) {
            const containerWidth = containerRef.value.offsetWidth;
            const itemOffset = activeItem.offsetLeft;
            const itemWidth = activeItem.offsetWidth;
            
            containerRef.value.scrollTo({
                left: itemOffset - (containerWidth / 2) + (itemWidth / 2),
                behavior: 'smooth'
            });
        }
    });
};

watch(() => props.modelValue, (newVal) => {
    scrollIntoView(newVal);
});

onMounted(() => {
    checkScroll();
    window.addEventListener('resize', checkScroll);
    if (props.modelValue) {
        setTimeout(() => scrollIntoView(props.modelValue), 100);
    }
});

onUnmounted(() => {
    window.removeEventListener('resize', checkScroll);
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
</style>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: [String, Number],
    options: {
        type: Array, // Expected: [{ value: '', label: '' }, ...] or simple strings ['A', 'B']
        default: () => []
    },
    placeholder: {
        type: String,
        default: 'Chọn một giá trị'
    },
    searchable: {
        type: Boolean,
        default: false
    },
    iconClass: {
        type: Function,
        default: () => null
    }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchTerm = ref('');
const selectRef = ref(null);

// Normalize options to [{ value, label }]
const normalizedOptions = computed(() => {
    return props.options.map(opt => {
        if (typeof opt === 'object') return opt;
        return { value: opt, label: opt };
    });
});

const selectedOption = computed(() => {
    return normalizedOptions.value.find(o => o.value === props.modelValue);
});

const filteredOptions = computed(() => {
    if (!props.searchable || !searchTerm.value) return normalizedOptions.value;
    const s = searchTerm.value.toLowerCase();
    return normalizedOptions.value.filter(o => 
        String(o.label).toLowerCase().includes(s) || 
        String(o.value).toLowerCase().includes(s)
    );
});

const toggle = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        searchTerm.value = '';
    }
};

const select = (value) => {
    emit('update:modelValue', value);
    isOpen.value = false;
};

const handleClickOutside = (event) => {
    if (selectRef.value && !selectRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    window.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    window.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="relative" ref="selectRef">
        <!-- Trigger -->
        <button
            type="button"
            @click="toggle"
            class="w-full flex items-center justify-between gap-2 px-3.5 py-2.5 text-sm rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-all hover:border-gray-300 dark:hover:border-gray-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
            :class="{ 'ring-2 ring-emerald-500/20 border-emerald-500/50': isOpen }"
        >
            <div class="flex items-center gap-2 truncate">
                <!-- Slot for Icon (like Flag) -->
                <slot name="icon" :option="selectedOption"></slot>
                <span class="truncate font-medium" :class="!selectedOption ? 'text-gray-400' : ''">
                    {{ selectedOption ? selectedOption.label : placeholder }}
                </span>
            </div>
            
            <svg 
                class="w-4 h-4 text-gray-400 transition-transform duration-200" 
                :class="{ 'rotate-180': isOpen }"
                fill="none" viewBox="0 0 24 24" stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div 
                v-if="isOpen"
                class="absolute z-[60] mt-2 w-full min-w-[200px] bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-2xl overflow-hidden py-1.5"
            >
                <!-- Search Box -->
                <div v-if="searchable" class="px-2 pb-1.5 mb-1.5 border-b border-gray-50 dark:border-gray-700">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input
                            v-model="searchTerm"
                            type="text"
                            placeholder="Tìm kiếm..."
                            class="w-full pl-8 pr-3 py-2 text-xs bg-gray-50 dark:bg-gray-900 border-none rounded-xl focus:ring-1 focus:ring-emerald-500/30 text-gray-900 dark:text-gray-100"
                            @click.stop
                        />
                    </div>
                </div>

                <!-- Options List -->
                <div class="max-h-[280px] overflow-y-auto no-scrollbar">
                    <div
                        v-for="opt in filteredOptions"
                        :key="opt.value"
                        @click="select(opt.value)"
                        class="px-3.5 py-2.5 text-sm cursor-pointer flex items-center justify-between group transition-colors"
                        :class="modelValue === opt.value ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white'"
                    >
                        <div class="flex items-center gap-2.5 truncate">
                            <slot name="option" :option="opt"></slot>
                            <span class="truncate">{{ opt.label }}</span>
                        </div>

                        <!-- Checkmark for selected -->
                        <svg v-if="modelValue === opt.value" class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </div>

                    <!-- No results -->
                    <div v-if="filteredOptions.length === 0" class="px-4 py-8 text-center text-xs text-gray-400">
                        Không tìm thấy kết quả
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>

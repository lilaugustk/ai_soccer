<template>
  <div class="rounded-[2.5rem] border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/40 backdrop-blur-xl p-6 shadow-2xl shadow-gray-200/20 dark:shadow-none h-fit group">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Bảng xếp hạng Rút gọn</h3>
      <span class="text-[9px] font-bold text-emerald-500 bg-emerald-500/10 px-2.5 py-1 rounded-xl border border-emerald-500/20">TOP 5</span>
    </div>
    
    <div class="space-y-4">
      <div v-for="team in standings.slice(0, 5)" :key="team.team_id" 
           class="flex items-center gap-4 group/item">
        <span class="text-[10px] font-bold w-4 text-gray-300 group-hover/item:text-emerald-500 transition-colors">{{ team.rank }}</span>
        <div class="w-8 h-8 rounded-xl bg-gray-50 dark:bg-gray-900/50 p-1.5 border border-gray-100 dark:border-gray-700 group-hover/item:scale-110 group-hover/item:rotate-3 transition-all duration-300 shadow-sm">
          <img v-if="team.team?.logo_url" :src="team.team.logo_url" class="w-full h-full object-contain" />
          <span v-else class="text-[8px] font-bold text-gray-300">{{ team.team?.name?.charAt(0) }}</span>
        </div>
        <span class="text-xs font-bold text-gray-700 dark:text-gray-200 truncate flex-1 group-hover/item:text-emerald-500 transition-colors">{{ team.team?.name }}</span>
        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ team.points }}</span>
      </div>
    </div>
    
    <button @click="emit('viewFull')" class="w-full mt-6 py-4 text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-emerald-500 hover:bg-emerald-50/50 dark:hover:bg-emerald-500/5 rounded-2xl transition-all border-t border-gray-50 dark:border-gray-700/50 pt-6">
      Xem toàn bộ bảng
    </button>
  </div>
</template>

<script setup>
const props = defineProps({
  standings: { type: Array, required: true }
});
const emit = defineEmits(['viewFull']);
</script>

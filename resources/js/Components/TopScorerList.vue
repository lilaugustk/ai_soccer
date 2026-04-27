<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div v-for="(scorer, index) in scorers" :key="scorer.player_id" 
         class="group relative overflow-hidden rounded-[2.5rem] border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/40 backdrop-blur-xl p-6 hover:border-emerald-500/50 hover:shadow-2xl hover:shadow-emerald-500/10 transition-all duration-500">
      <div class="flex items-center gap-6">
        <!-- Rank Badge -->
        <div class="absolute -top-2 -right-2 w-12 h-12 rounded-bl-3xl bg-gray-50 dark:bg-gray-900/50 flex items-center justify-center text-xs font-black text-gray-300 group-hover:bg-emerald-500 group-hover:text-white transition-all duration-500 border-l border-b border-gray-100 dark:border-gray-700 shadow-sm">
          #{{ index + 1 }}
        </div>

        <!-- Player Photo -->
        <div class="relative w-20 h-20 rounded-2xl overflow-hidden bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 group-hover:scale-105 group-hover:rotate-2 transition-all duration-500 shadow-inner">
          <img v-if="scorer.photo" :src="scorer.photo" class="w-full h-full object-cover" />
          <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
            <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
          </div>
        </div>

        <!-- Player Info -->
        <div class="flex-1 min-w-0">
          <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-tight truncate pr-10 group-hover:text-emerald-500 transition-colors">{{ scorer.player_name }}</h3>
          <div class="flex items-center gap-2 mt-1.5">
            <div class="w-4 h-4 rounded-md bg-white dark:bg-gray-900 p-0.5 border border-gray-100 dark:border-gray-800 flex items-center justify-center overflow-hidden">
                <img v-if="scorer.team?.logo_url" :src="scorer.team.logo_url" class="w-full h-full object-contain" />
            </div>
            <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider truncate">{{ scorer.team?.name }}</span>
          </div>
          
          <!-- Stats -->
          <div class="flex gap-6 mt-4">
            <div class="flex flex-col">
              <span class="text-[9px] font-black uppercase tracking-widest text-gray-400 opacity-60">Bàn thắng</span>
              <span class="text-xl font-black text-emerald-600 dark:text-emerald-400 leading-tight">{{ scorer.goals }}</span>
            </div>
            <div class="w-px h-6 bg-gray-100 dark:bg-gray-700 self-end"></div>
            <div class="flex flex-col">
              <span class="text-[9px] font-black uppercase tracking-widest text-gray-400 opacity-60">Kiến tạo</span>
              <span class="text-xl font-black text-amber-500 leading-tight">{{ scorer.assists || 0 }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  scorers: { type: Array, required: true }
});
</script>

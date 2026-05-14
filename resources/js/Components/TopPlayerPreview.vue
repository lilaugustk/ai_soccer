<template>
  <div class="bg-white dark:bg-gray-800/80 rounded-2xl border border-gray-100 dark:border-gray-700/50 p-5 shadow-sm">
    <h3 class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-4">{{ title }}</h3>
    <div v-if="players && players.length > 0" class="space-y-3">
        <div v-for="(player, idx) in players.slice(0, 5)" :key="player.player_id" class="flex items-center gap-3">
            <span class="text-[10px] font-bold text-gray-300 w-4">{{ idx + 1 }}</span>
            <img :src="player.player?.photo_url" 
                 @error="(e) => e.target.src = 'https://www.sofascore.com/static/images/placeholders/player.png'"
                 class="w-8 h-8 rounded-full bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-700 object-cover" />
            <div class="min-w-0 flex-1">
                <p class="text-[11px] font-bold text-gray-900 dark:text-white truncate">{{ player.player?.name || 'Cầu thủ' }}</p>
                <p class="text-[9px] text-gray-400 truncate">{{ player[statKey] }} {{ statLabel }}</p>
            </div>
        </div>
    </div>
    <div v-else class="text-[9px] text-center text-gray-400 py-4 italic">Đang cập nhật...</div>
  </div>
</template>

<script setup>
defineProps({
    title: { type: String, required: true },
    players: { type: Array, required: true },
    statKey: { type: String, required: true },
    statLabel: { type: String, required: true }
});
</script>

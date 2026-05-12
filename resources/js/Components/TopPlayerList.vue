<template>
  <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm relative">
    <div class="no-scrollbar">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-gray-50/50 dark:bg-gray-800/50 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">
            <th class="px-4 py-3 w-12 text-center">#</th>
            <th class="px-4 py-3">Cầu thủ</th>
            <th class="px-4 py-3">Đội bóng</th>
            <th class="px-4 py-3 text-center cursor-pointer group relative text-emerald-600 dark:text-emerald-500">
                {{ statLabel }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
          <tr v-for="(player, idx) in players" :key="player.player_id" 
              class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors group">
            <td class="px-4 py-4 text-[11px] font-bold text-center text-gray-400">
              {{ idx + 1 }}
            </td>
            <td class="px-4 py-4">
              <div class="flex items-center gap-3">
                <div class="relative">
                    <img :src="player.player?.photo_url" class="w-8 h-8 rounded-full bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-gray-700" />
                    <div class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-white dark:bg-gray-800 rounded-full border border-gray-100 dark:border-gray-700 p-0.5">
                         <img :src="`https://media.api-sports.io/football/teams/${player.team_id}.png`" class="w-full h-full object-contain" />
                    </div>
                </div>
                <span class="text-[12px] font-bold text-gray-900 dark:text-gray-100 truncate">{{ player.player?.name || 'Cầu thủ' }}</span>
              </div>
            </td>
            <td class="px-4 py-4">
               <span class="text-[11px] text-gray-500 uppercase font-bold">{{ player.team?.name || 'Club' }}</span>
            </td>
            <td class="px-4 py-4 text-[12px] text-center font-bold text-emerald-600 dark:text-emerald-400">
              {{ player[statKey] || 0 }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  players: { type: Array, required: true },
  statKey: { type: String, default: 'goals' },
  statLabel: { type: String, default: 'Bàn thắng' }
});
</script>

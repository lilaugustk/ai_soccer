<template>
  <div class="overflow-hidden rounded-2xl border border-gray-100 dark:border-gray-800 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm shadow-sm">
    <div class="overflow-x-auto no-scrollbar">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="bg-gray-50/50 dark:bg-gray-800/50 text-[11px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">
            <th class="px-4 py-3 w-12 text-center">#</th>
            <th class="px-4 py-3">Đội bóng</th>
            <th class="px-3 py-3 text-center">ST</th>
            <th class="px-3 py-3 text-center hidden sm:table-cell">T</th>
            <th class="px-3 py-3 text-center hidden sm:table-cell">H</th>
            <th class="px-3 py-3 text-center hidden sm:table-cell">B</th>
            <th class="px-3 py-3 text-center hidden md:table-cell">BT/BB</th>
            <th class="px-3 py-3 text-center">HS</th>
            <th class="px-4 py-3 text-center text-emerald-600 dark:text-emerald-400">Điểm</th>
            <th class="px-4 py-3 text-center min-w-[120px]">Phong độ</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
          <tr v-for="team in standings" :key="team.team_id" 
              class="hover:bg-emerald-50/50 dark:hover:bg-emerald-500/5 transition-all duration-300 group border-b border-gray-50/50 dark:border-gray-800/50 last:border-0">
            <td class="px-4 py-5 text-sm font-black text-center">
              <span :class="getRankClass(team.rank)">{{ team.rank }}</span>
            </td>
            <td class="px-4 py-5">
              <div class="flex items-center gap-4">
                <div class="w-9 h-9 rounded-xl bg-white dark:bg-gray-800 flex items-center justify-center p-1.5 border border-gray-100 dark:border-gray-700 shadow-sm group-hover:scale-110 group-hover:rotate-2 transition-all duration-500">
                  <img v-if="team.team?.logo_url" :src="team.team.logo_url" class="w-full h-full object-contain" />
                  <span v-else class="text-[10px] font-black text-gray-300">{{ team.team?.name?.charAt(0) }}</span>
                </div>
                <span class="text-sm font-black text-gray-900 dark:text-gray-100 group-hover:text-emerald-500 transition-colors truncate max-w-[120px] sm:max-w-none">{{ team.team?.name }}</span>
              </div>
            </td>
            <td class="px-3 py-4 text-sm text-center font-medium">{{ team.played }}</td>
            <td class="px-3 py-4 text-sm text-center hidden sm:table-cell">{{ team.win }}</td>
            <td class="px-3 py-4 text-sm text-center hidden sm:table-cell">{{ team.draw }}</td>
            <td class="px-3 py-4 text-sm text-center hidden sm:table-cell">{{ team.lose }}</td>
            <td class="px-3 py-4 text-xs text-center text-gray-400 hidden md:table-cell italic">
              {{ team.goals_for }}-{{ team.goals_against }}
            </td>
            <td class="px-3 py-4 text-sm text-center font-bold" :class="team.goals_for - team.goals_against >= 0 ? 'text-emerald-500' : 'text-rose-500'">
              {{ (team.goals_for - team.goals_against) > 0 ? '+' : '' }}{{ team.goals_for - team.goals_against }}
            </td>
            <td class="px-4 py-4 text-sm text-center font-black text-emerald-600 dark:text-emerald-400">{{ team.points }}</td>
            <td class="px-4 py-4">
              <div class="flex justify-center gap-1">
                <span v-for="(res, idx) in parseForm(team.form)" :key="idx"
                      :class="getFormClass(res)"
                      class="w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-black text-white shadow-sm">
                  {{ res }}
                </span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  standings: { type: Array, required: true }
});

const parseForm = (formStr) => {
  if (!formStr) return [];
  // API Football form format is like "WWDLL"
  return formStr.split('').slice(-5); // Lấy 5 trận gần nhất
};

const getFormClass = (res) => {
  if (res === 'W') return 'bg-emerald-500 shadow-emerald-500/20';
  if (res === 'D') return 'bg-amber-500 shadow-amber-500/20';
  if (res === 'L') return 'bg-rose-500 shadow-rose-500/20';
  return 'bg-gray-300';
};

const getRankClass = (rank) => {
  const base = "inline-flex items-center justify-center w-6 h-6 rounded-md text-[11px] ";
  if (rank <= 4) return base + "bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 shadow-sm";
  if (rank >= 18) return base + "bg-rose-500/10 text-rose-600 border border-rose-500/20";
  return base + "text-gray-500";
};
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

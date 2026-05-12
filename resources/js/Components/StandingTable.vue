<template>
  <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm relative">
    <div class="no-scrollbar">
      <table class="w-full text-left border-collapse table-auto">
        <thead>
          <tr class="bg-gray-50/50 dark:bg-gray-800/50 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-800">
            <th class="pl-4 pr-2 py-3 w-10 text-center">#</th>
            <th class="px-2 py-3 min-w-[150px]">Đội bóng</th>
            <th class="px-2 py-3 text-center w-10 cursor-pointer group relative">
                ST
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-bold rounded shadow-xl z-[100] pointer-events-none">
                    Số trận đã đấu
                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                </div>
            </th>
            <th class="px-2 py-3 text-center w-10 cursor-pointer group relative">
                T
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-bold rounded shadow-xl z-[100] pointer-events-none">
                    Thắng
                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                </div>
            </th>
            <th class="px-2 py-3 text-center w-10 cursor-pointer group relative">
                H
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-bold rounded shadow-xl z-[100] pointer-events-none">
                    Hòa
                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                </div>
            </th>
            <th class="px-2 py-3 text-center w-10 cursor-pointer group relative">
                B
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-bold rounded shadow-xl z-[100] pointer-events-none">
                    Bại
                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                </div>
            </th>
            <th class="px-2 py-3 text-center w-16 hidden lg:table-cell cursor-pointer group relative">
                BT/BB
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-bold rounded shadow-xl z-[100] pointer-events-none">
                    Bàn thắng / Bàn bại
                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                </div>
            </th>
            <th class="px-2 py-3 text-center w-10 cursor-pointer group relative">
                HS
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-bold rounded shadow-xl z-[100] pointer-events-none">
                    Hiệu số bàn thắng bại
                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                </div>
            </th>
            <th class="px-2 py-3 text-center w-14 hidden xl:table-cell cursor-pointer group relative">
                xGF
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-bold rounded shadow-xl z-[100] pointer-events-none">
                    Bàn thắng kỳ vọng (xGF)
                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                </div>
            </th>
            <th class="px-2 py-3 text-center w-14 hidden xl:table-cell cursor-pointer group relative">
                xGA
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-bold rounded shadow-xl z-[100] pointer-events-none">
                    Bàn thua kỳ vọng (xGA)
                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                </div>
            </th>
            <th class="px-2 py-3 text-center w-14 hidden xl:table-cell cursor-pointer group relative">
                xGD
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-bold rounded shadow-xl z-[100] pointer-events-none">
                    Hiệu số xG (xGD)
                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                </div>
            </th>
            <th class="px-2 py-3 text-center w-12 text-emerald-600 dark:text-emerald-400 cursor-pointer group relative">
                Đ
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-max px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-[9px] font-bold rounded shadow-xl z-[100] pointer-events-none">
                    Tổng điểm hiện tại
                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700"></div>
                </div>
            </th>
            <th class="px-4 py-3 text-center min-w-[110px]">5 Trận</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
          <tr v-for="team in standings" :key="team.team_id" 
              class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors group">
            <td class="pl-4 pr-2 py-3 text-[11px] font-bold text-center relative">
              <!-- Sidebar Color with English Tooltip -->
              <div v-if="team.description" 
                   :class="getZoneColorClass(team.description)"
                   class="absolute left-0 top-1 bottom-1 w-1 rounded-r-full group/zone cursor-pointer">
                <!-- Mini Tooltip for Sidebar -->
                <div class="absolute left-full ml-2 top-1/2 -translate-y-1/2 hidden group-hover/zone:block bg-gray-900 text-white text-[9px] px-2 py-1 rounded shadow-xl whitespace-nowrap z-[120] font-medium border border-white/10">
                  {{ team.description }}
                </div>
              </div>
              {{ team.position ?? team.rank }}
            </td> 
            <td class="px-2 py-3">
              <Link :href="`/teams/${team.team_id}?season=${season}`" class="flex items-center gap-2 hover:text-emerald-500 transition-colors cursor-pointer group/team">
                <img v-if="team.team?.logo_url" :src="team.team.logo_url" class="w-5 h-5 object-contain" />
                <span class="text-[11px] font-bold text-gray-900 dark:text-gray-100 truncate max-w-[120px] lg:max-w-none">{{ team.team?.name }}</span>
              </Link>
            </td>
            <td class="px-2 py-3 text-[11px] text-center font-medium">{{ team.played || 0 }}</td>
            <td class="px-2 py-3 text-[11px] text-center">{{ team.won ?? team.win ?? 0 }}</td>
            <td class="px-2 py-3 text-[11px] text-center">{{ team.drawn ?? team.draw ?? 0 }}</td>
            <td class="px-2 py-3 text-[11px] text-center">{{ team.lost ?? team.lose ?? 0 }}</td>
            <td class="px-2 py-3 text-[10px] text-center text-gray-400 hidden lg:table-cell">
              {{ team.gf ?? team.goals_for ?? 0 }}:{{ team.ga ?? team.goals_against ?? 0 }}
            </td>
            <td class="px-2 py-3 text-[11px] text-center font-bold" :class="((team.gf ?? team.goals_for ?? 0) - (team.ga ?? team.goals_against ?? 0)) >= 0 ? 'text-emerald-500' : 'text-rose-500'">
              {{ (team.gf ?? team.goals_for ?? 0) - (team.ga ?? team.goals_against ?? 0) }}
            </td>
            <td class="px-2 py-3 text-[10px] text-center text-gray-500 hidden xl:table-cell font-medium">
              {{ team.xgf || 0 }}
            </td>
            <td class="px-2 py-3 text-[10px] text-center text-gray-500 hidden xl:table-cell font-medium">
              {{ team.xga || 0 }}
            </td>
            <td class="px-2 py-3 text-[10px] text-center hidden xl:table-cell font-bold" :class="(team.xgd || 0) >= 0 ? 'text-emerald-500/70' : 'text-rose-500/70'">
              {{ team.xgd || 0 }}
            </td>
            <td class="px-2 py-3 text-[11px] text-center font-bold text-emerald-600 dark:text-emerald-400">{{ team.pts ?? team.points ?? 0 }}</td>
            <td class="px-4 py-3">
              <div class="flex justify-center gap-0.5">
                <div v-for="(item, idx) in getDisplayForm(team)" :key="idx"
                      :class="getFormClass(item.res)"
                      class="group/form relative w-4 h-4 rounded-sm flex items-center justify-center text-[8px] font-bold text-white shadow-sm shrink-0 cursor-pointer">
                  {{ item.res }}
                  <!-- Tooltip phong độ -->
                  <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/form:block w-max px-3 py-2 bg-gray-950 text-white rounded-lg shadow-2xl z-[110] pointer-events-none min-w-[140px] border border-white/5">
                    <div class="flex flex-col gap-1.5">
                      <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-1.5 mb-0.5">
                        <span class="text-[9px] font-bold uppercase tracking-widest" :class="item.res === 'W' ? 'text-emerald-500' : (item.res === 'L' ? 'text-rose-500' : 'text-amber-500')">
                          {{ item.res === 'W' ? 'Thắng' : (item.res === 'D' ? 'Hòa' : 'Thua') }}
                        </span>
                        <span v-if="item.date" class="text-[8px] text-gray-500 font-bold tabular-nums">{{ item.date }}</span>
                      </div>
                      <div v-if="item.home" class="flex flex-col gap-1">
                        <div class="flex items-center justify-between gap-4">
                          <span class="text-[9px] font-bold text-gray-300 truncate max-w-[90px]">{{ item.home }}</span>
                          <span class="text-[10px] font-bold text-white tabular-nums">{{ item.score.split(' - ')[0] }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                          <span class="text-[9px] font-bold text-gray-300 truncate max-w-[90px]">{{ item.away }}</span>
                          <span class="text-[10px] font-bold text-white tabular-nums">{{ item.score.split(' - ')[1] }}</span>
                        </div>
                      </div>
                      <div v-else class="text-center py-1">
                        <span class="text-[8px] text-gray-500 font-bold italic">Chi tiết trận đấu chưa có sẵn</span>
                      </div>
                    </div>
                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-950"></div>
                  </div>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Dynamic Legend with Tooltip -->
    <div v-if="dynamicLegend.length > 0" class="p-3 border-t border-gray-50 dark:border-gray-800 bg-gray-50/30 dark:bg-gray-900/30">
        <div class="flex flex-wrap gap-x-6 gap-y-2">
            <div v-for="item in dynamicLegend" :key="item.original" 
                 class="flex items-center gap-2 group/legend relative cursor-pointer">
                <div class="w-2.5 h-2.5 rounded-[2px]" :class="item.color"></div>
                <span class="text-[9px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">{{ item.translated }}</span>
                
                <!-- English Tooltip -->
                <div class="absolute bottom-full left-0 mb-2 hidden group-hover/legend:block bg-gray-900 text-white text-[9px] px-2 py-1 rounded shadow-xl whitespace-nowrap z-[100] font-medium border border-white/10 uppercase tracking-wider">
                  {{ item.original }}
                  <div class="absolute top-full left-2 border-4 border-transparent border-t-gray-900"></div>
                </div>
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
  standings: { type: Array, required: true },
  leagueId: { type: [Number, String], default: null },
  season: { type: [Number, String], default: 2024 }
});

const translateDesc = (desc) => {
  if (!desc) return '';
  const d = desc.toLowerCase();
  if (d.includes('champions league')) return 'Cúp C1 / Champions League';
  if (d.includes('europa league')) return 'Cúp C2 / Europa League';
  if (d.includes('conference league')) return 'Cúp C3 / Conference League';
  if (d.includes('relegation play-off') || d.includes('relegation playoff')) return 'Play-off xuống hạng';
  if (d.includes('relegation') || d.includes('xuống hạng')) return 'Xuống hạng';
  if (d.includes('promotion')) return 'Thăng hạng';
  return desc;
};

const getZoneColorClass = (desc) => {
  if (!desc) return null;
  const d = desc.toLowerCase();
  if (d.includes('champions league')) return 'bg-blue-500';
  if (d.includes('europa league')) return 'bg-amber-500';
  if (d.includes('conference league')) return 'bg-emerald-500';
  if (d.includes('relegation play-off') || d.includes('relegation playoff')) return 'bg-orange-500';
  if (d.includes('relegation') || d.includes('xuống hạng')) return 'bg-rose-500';
  if (d.includes('play-off') || d.includes('promotion')) return 'bg-indigo-500';
  return 'bg-gray-300';
};

const dynamicLegend = computed(() => {
  const legends = [];
  const seen = new Set();
  props.standings.forEach(team => {
    if (team.description) {
      const translated = translateDesc(team.description);
      if (!seen.has(translated)) {
        seen.add(translated);
        legends.push({
          original: team.description,
          translated: translated,
          color: getZoneColorClass(team.description)
        });
      }
    }
  });
  const priority = (text) => {
    const t = text.toLowerCase();
    if (t.includes('champions league')) return 1;
    if (t.includes('europa league')) return 2;
    if (t.includes('conference league')) return 3;
    if (t.includes('play-off xuống hạng')) return 5;
    if (t.includes('xuống hạng')) return 6;
    return 4;
  };
  return legends.sort((a, b) => priority(a.translated) - priority(b.translated));
});

const getDisplayForm = (team) => {
  if (team.recent_matches && team.recent_matches.length > 0) {
    return team.recent_matches;
  }
  if (!team.form) return [];
  return team.form.split('').slice(-5).map(res => ({
    res: res,
    date: null, home: null, away: null, score: null
  }));
};

const getFormClass = (res) => {
  if (res === 'W') return 'bg-emerald-500';
  if (res === 'D') return 'bg-amber-500';
  if (res === 'L') return 'bg-rose-500';
  return 'bg-gray-300';
};
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

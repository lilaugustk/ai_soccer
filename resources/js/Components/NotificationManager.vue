<template>
  <div class="fixed bottom-6 right-6 z-[999] flex flex-col gap-3 pointer-events-none">
    <TransitionGroup name="toast">
      <div v-for="toast in activeToasts" :key="toast.id" 
           class="pointer-events-auto bg-white dark:bg-gray-900 border border-emerald-500/30 shadow-[0_20px_50px_rgba(0,0,0,0.2)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.4)] rounded-2xl p-4 w-80 backdrop-blur-xl flex gap-4 items-start animate-slide-in">
        <div class="w-10 h-10 rounded-full bg-emerald-500/10 flex items-center justify-center shrink-0">
          <svg v-if="toast.type === 'goal'" class="w-6 h-6 text-emerald-500 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
          <svg v-else class="w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <h4 class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1">{{ toast.title }}</h4>
          <p class="text-sm font-bold text-gray-800 dark:text-gray-100 leading-tight">{{ toast.message }}</p>
          <div class="mt-2 flex items-center justify-between">
            <span class="text-[9px] text-gray-400 font-bold uppercase">{{ toast.time }}</span>
            <button @click="removeToast(toast.id)" class="text-[9px] font-black text-gray-400 hover:text-rose-500 uppercase">Đóng</button>
          </div>
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import dayjs from 'dayjs';

const activeToasts = ref([]);
const followedMatchIds = ref([]);
const matchStates = ref({}); // Lưu trạng thái cũ để so sánh (tỉ số, phút...)

const removeToast = (id) => {
  activeToasts.value = activeToasts.value.filter(t => t.id !== id);
};

const addToast = (toast) => {
  const id = Date.now();
  activeToasts.value.unshift({ ...toast, id, time: dayjs().format('HH:mm') });
  
  // Tự động đóng sau 10 giây
  setTimeout(() => removeToast(id), 10000);
};

const checkUpdates = async () => {
  if (followedMatchIds.value.length === 0) return;

  try {
    // Gọi API lấy dữ liệu thô của các trận đang theo dõi
    // Chúng ta sẽ giả định backend có 1 endpoint lấy list matches theo ID
    // Nếu chưa có, ta tạm thời lấy từ trang hiện tại hoặc sync riêng
    const response = await axios.get('/api/matches/sync-states', {
      params: { ids: followedMatchIds.value.join(',') }
    });

    const matches = response.data;
    matches.forEach(match => {
      const prevState = matchStates.value[match.id];
      if (!prevState) {
        matchStates.value[match.id] = { ...match };
        return;
      }

      // 1. Kiểm tra bàn thắng
      if (match.home_score > prevState.home_score) {
        addToast({
          type: 'goal',
          title: 'VÀOOO! GHI BÀN',
          message: `${match.home_team.name} vừa ghi bàn nâng tỉ số lên ${match.home_score}-${match.away_score}`
        });
      } else if (match.away_score > prevState.away_score) {
        addToast({
          type: 'goal',
          title: 'VÀOOO! GHI BÀN',
          message: `${match.away_team.name} vừa ghi bàn nâng tỉ số lên ${match.home_score}-${match.away_score}`
        });
      }

      // 2. Kiểm tra bắt đầu trận (NS -> LIVE)
      if (prevState.status === 'NS' && match.status !== 'NS') {
        addToast({
          type: 'info',
          title: 'TRẬN ĐẤU BẮT ĐẦU',
          message: `Trận đấu giữa ${match.home_team.name} và ${match.away_team.name} đã chính thức bắt đầu!`
        });
      }

      // 3. Kiểm tra kết thúc trận (LIVE -> FT)
      const finishedStatuses = ['FT', 'AET', 'PEN'];
      if (!finishedStatuses.includes(prevState.status) && finishedStatuses.includes(match.status)) {
        addToast({
          type: 'info',
          title: 'KẾT THÚC TRẬN ĐẤU',
          message: `Trận đấu giữa ${match.home_team.name} và ${match.away_team.name} đã kết thúc với tỉ số ${match.home_score}-${match.away_score}`
        });
      }

      // Cập nhật lại state
      matchStates.value[match.id] = { ...match };
    });
  } catch (e) {
    console.error('Notification check failed', e);
  }
};

// Theo dõi countdown các trận sắp diễn ra
const checkCountdown = () => {
    // Logic này có thể chạy Local mà không cần gọi API liên tục
    // ...
};

onMounted(() => {
  const loadFollowed = () => {
    const stored = localStorage.getItem('followed_matches');
    followedMatchIds.value = stored ? JSON.parse(stored) : [];
  };

  loadFollowed();
  window.addEventListener('followed-matches-updated', (e) => {
    followedMatchIds.value = e.detail;
  });

  // Check định kỳ mỗi 30 giây
  const interval = setInterval(checkUpdates, 30000);
  onUnmounted(() => clearInterval(interval));
});
</script>

<style scoped>
.toast-enter-active, .toast-leave-active {
  transition: all 0.5s ease;
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(100px);
}
.toast-leave-to {
  opacity: 0;
  transform: scale(0.9);
}

@keyframes slide-in {
  from { transform: translateX(100%); }
  to { transform: translateX(0); }
}
.animate-slide-in {
  animation: slide-in 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes swing {
  0% { transform: rotate(0deg); }
  20% { transform: rotate(15deg); }
  40% { transform: rotate(-10deg); }
  60% { transform: rotate(5deg); }
  80% { transform: rotate(-5deg); }
  100% { transform: rotate(0deg); }
}
.animate-swing {
  animation: swing 0.5s ease-in-out infinite;
  transform-origin: top center;
}
</style>

<template>
  <div class="min-h-screen transition-colors duration-300 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <!-- Navbar -->
    <nav class="sticky top-0 z-50 transition-colors duration-300 bg-white/80 dark:bg-gray-800/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-700 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex justify-between h-14">
          <div class="flex items-center">
            <!-- Logo -->
            <div @click="router.visit('/')" class="flex-shrink-0 flex items-center gap-2 cursor-pointer">
              <div class="w-8 h-8 rounded bg-gradient-to-tr from-green-400 to-emerald-600 flex items-center justify-center text-white font-bold">
                AI
              </div>
              <span class="font-bold text-xl tracking-tight">Soccer<span class="text-emerald-500">.</span></span>
            </div>

            <!-- Desktop Menu -->
            <div class="hidden sm:ml-8 sm:flex sm:space-x-8">
              <Link href="/" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors"
                :class="$page.url === '/' || $page.url.startsWith('/matches') ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600'">
                Trang Chủ & Lịch Thi Đấu
              </Link>
              <Link href="/predictions" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors"
                :class="$page.url.startsWith('/predictions') ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-600'">
                AI Dự Đoán
              </Link>
            </div>
          </div>

          <!-- Right side: Search & Dark Mode Toggle -->
          <div class="flex items-center space-x-3">
            <div class="relative hidden sm:block h-9">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-3.5 w-3.5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>
              </div>
              <input type="text" v-model="searchQuery" placeholder="Tìm đội, cầu thủ..." 
                @input="handleSearch"
                @focus="showResults = true"
                class="block w-56 pl-9 pr-3 py-1.5 border rounded-xl leading-5 text-[13px] transition-all focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 bg-gray-50 dark:bg-gray-700 border-gray-100 dark:border-gray-600 text-gray-900 dark:text-white placeholder-gray-400"
              />
              
              <!-- Search Results Dropdown -->
              <div v-if="showResults && (isLoading || searchResults.teams.length || searchResults.players.length || searchResults.leagues.length || (searchQuery.length >= 2 && !isLoading))" 
                   class="absolute mt-2 w-80 right-0 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden z-[100] bg-white/95 dark:bg-gray-800/95 backdrop-blur-xl">
                
                <div class="max-h-[450px] overflow-y-auto p-2 space-y-1">
                  <!-- Loading State -->
                  <div v-if="isLoading" class="p-8 text-center">
                    <div class="inline-block w-6 h-6 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
                    <p class="mt-2 text-xs font-bold text-gray-400 uppercase tracking-widest">Đang tìm kiếm...</p>
                  </div>

                  <!-- No Results -->
                  <div v-else-if="searchQuery.length >= 2 && !searchResults.teams.length && !searchResults.players.length && !searchResults.leagues.length" 
                       class="p-8 text-center">
                    <p class="text-sm font-bold text-gray-500">Không tìm thấy kết quả nào</p>
                    <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-tighter">Thử tìm kiếm với từ khóa khác</p>
                  </div>

                  <template v-else>
                    <!-- Leagues -->
                    <div v-if="searchResults.leagues.length">
                      <span class="text-[9px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 px-3 py-2 block">Giải đấu</span>
                      <Link v-for="league in searchResults.leagues" :key="league.id" :href="`/leagues/${league.id}`" @click="showResults = false"
                            class="flex items-center gap-3 p-3 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-gray-50 dark:bg-gray-700 flex items-center justify-center p-1 border border-gray-100 dark:border-gray-600 group-hover:bg-white dark:group-hover:bg-gray-600 transition-colors overflow-hidden">
                          <img v-if="league.logo_url" :src="league.logo_url" class="w-full h-full object-contain" />
                          <span v-else class="text-[10px] font-bold uppercase text-gray-300">{{ league.name.substring(0,2) }}</span>
                        </div>
                        <span class="text-sm font-bold">{{ league.name }}</span>
                      </Link>
                    </div>

                    <!-- Teams -->
                    <div v-if="searchResults.teams.length">
                      <span class="text-[9px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 px-3 py-2 block border-t border-gray-50 dark:border-gray-700 mt-2 pt-4">Đội bóng</span>
                      <Link v-for="team in searchResults.teams" :key="team.id" :href="`/teams/${team.id}`" @click="showResults = false"
                            class="flex items-center gap-3 p-3 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-gray-50 dark:bg-gray-700 flex items-center justify-center p-1 border border-gray-100 dark:border-gray-600 group-hover:bg-white dark:group-hover:bg-gray-600 transition-colors overflow-hidden">
                          <img v-if="team.logo" :src="team.logo" class="w-full h-full object-contain" />
                          <span v-else class="text-[10px] font-bold uppercase text-gray-300">{{ team.code || team.name.substring(0,1) }}</span>
                        </div>
                        <span class="text-sm font-bold">{{ team.name }}</span>
                      </Link>
                    </div>

                    <!-- Players -->
                    <div v-if="searchResults.players.length">
                      <span class="text-[9px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 px-3 py-2 block border-t border-gray-50 dark:border-gray-700 mt-2 pt-4">Cầu thủ</span>
                      <Link v-for="player in searchResults.players" :key="player.id" :href="`/players/${player.id}`" @click="showResults = false"
                            class="flex items-center gap-3 p-3 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-gray-50 dark:bg-gray-700 flex items-center justify-center text-emerald-500/50 group-hover:bg-white dark:group-hover:bg-gray-600 transition-colors">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-sm font-bold">{{ player.name }}</span>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ player.nationality }}</span>
                        </div>
                      </Link>
                    </div>
                  </template>
                </div>
              </div>
            </div>


            <!-- Auth Menu -->
            <div class="flex items-center ml-2 border-l border-gray-100 dark:border-gray-700 pl-3">
              <template v-if="$page.props.auth.user">
                <!-- Notifications Bell -->
                <div class="relative mr-2">
                  <button @click="showNotifications = !showNotifications; if(showNotifications) fetchNotifications()" 
                          class="p-2 rounded-xl transition-all active:scale-95 relative"
                          :class="unreadCount > 0 ? 'bg-emerald-500/10 text-emerald-500' : 'bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600'">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 w-4 h-4 bg-rose-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center ring-2 ring-white dark:ring-gray-800 animate-bounce">
                      {{ unreadCount > 9 ? '9+' : unreadCount }}
                    </span>
                  </button>

                  <!-- Notifications Dropdown -->
                  <div v-if="showNotifications" class="absolute right-0 mt-3 w-80 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden z-[120]">
                    <div class="p-4 border-b border-gray-50 dark:border-gray-700 flex justify-between items-center">
                      <h3 class="text-sm font-bold">Thông báo</h3>
                      <button @click="markAllAsRead" v-if="unreadCount > 0" class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest hover:text-emerald-600">Đánh dấu đã đọc hết</button>
                    </div>
                    <div class="max-h-80 overflow-y-auto no-scrollbar">
                      <div v-if="notifications.length === 0" class="p-8 text-center">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Không có thông báo mới</p>
                      </div>
                      <div v-else v-for="n in notifications" :key="n.id" 
                           @click="markAsRead(n)"
                           class="p-4 border-b border-gray-50 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-500/10 transition-colors cursor-pointer relative"
                           :class="{ 'bg-emerald-50/50 dark:bg-emerald-500/5': !n.read_at }">
                        <div class="flex gap-3">
                          <div class="w-2 h-2 rounded-full bg-emerald-500 mt-1.5 shrink-0" v-if="!n.read_at"></div>
                          <div class="flex-1">
                            <p class="text-xs font-bold text-gray-900 dark:text-white">{{ n.data.message || 'Thông báo mới' }}</p>
                            <p class="text-[10px] text-gray-400 mt-1">{{ dayjs(n.created_at).fromNow() }}</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="relative group">
                  <button class="flex items-center gap-2 p-1 pr-3 rounded-full hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
                    <div class="w-8 h-8 rounded-full overflow-hidden flex items-center justify-center bg-emerald-500 text-white text-xs font-bold ring-2 ring-emerald-500/20">
                      <img v-if="$page.props.auth.user.avatar" :src="$page.props.auth.user.avatar" class="w-full h-full object-cover" />
                      <span v-else>{{ $page.props.auth.user.name.substring(0, 1).toUpperCase() }}</span>
                    </div>
                    <span class="text-xs font-bold hidden md:block">{{ $page.props.auth.user.name }}</span>
                    <svg class="w-3 h-3 text-gray-400 group-hover:text-gray-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>
                  <!-- Dropdown Menu -->
                  <div class="absolute right-0 mt-2 w-48 py-2 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-[110]">
                    <div class="px-4 py-2 border-b border-gray-50 dark:border-gray-700 mb-1">
                      <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Tài khoản</p>
                      <p class="text-xs font-medium truncate">{{ $page.props.auth.user.email }}</p>
                    </div>
                    <Link href="/profile" class="block px-4 py-2 text-xs font-medium hover:bg-emerald-50 dark:hover:bg-emerald-500/10 hover:text-emerald-600 transition-colors">
                      Hồ sơ của tôi
                    </Link>
                    <button @click="logout" class="w-full text-left px-4 py-2 text-xs font-medium text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-colors">
                      Đăng xuất
                    </button>
                  </div>
                </div>
              </template>
              <template v-else>
                <Link href="/login" class="px-5 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-[13px] font-bold rounded-xl shadow-lg shadow-emerald-500/20 transition-all active:scale-95 whitespace-nowrap">
                  Đăng nhập
                </Link>
              </template>
            </div>

            <!-- Theme Toggle Button -->
            <button @click="toggleDarkMode" class="p-2 rounded-xl border border-transparent focus:outline-none transition-all active:scale-95 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-600 dark:text-yellow-400">
              <!-- Sun Icon -->
              <svg v-if="isDarkMode" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
              <!-- Moon Icon -->
              <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content Area -->
    <main class="max-w-7xl mx-auto pt-6 pb-5 px-4 sm:px-6">
      <!-- Slot for Views -->
      <slot />
    </main>
    <!-- Simple Footer -->
    <footer class="border-t mt-12 py-8 bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700">
      <div class="max-w-7xl mx-auto px-4 text-center">
        <p class="text-sm text-gray-400 dark:text-gray-500">
          &copy; 2026 AI Soccer Predictions. All rights reserved. (FBref Data)
        </p>
      </div>
    </footer>

    <!-- Back to Top Button -->
    <transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="translate-y-10"
      enter-to-class="translate-y-0"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="translate-y-0"
      leave-to-class="translate-y-10"
    >
      <button v-show="showBackToTop" @click="scrollToTop"
              class="fixed bottom-8 right-8 z-[60] p-3 rounded-2xl bg-emerald-500 hover:bg-emerald-600 text-white shadow-2xl shadow-emerald-500/20 transition-all hover:scale-110 active:scale-95 group">
        <svg class="w-6 h-6 transition-transform group-hover:-translate-y-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
        </svg>
      </button>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import 'dayjs/locale/vi';

dayjs.extend(relativeTime);
dayjs.locale('vi');

// Dark theme switch logic
const isDarkMode = ref(false);
const searchQuery = ref('');
const showResults = ref(false);
const isLoading = ref(false);
const searchResults = ref({ teams: [], players: [], leagues: [] });
const debounceTimeout = ref(null);
const showBackToTop = ref(false);

// Notifications State
const notifications = ref([]);
const unreadCount = ref(0);
const showNotifications = ref(false);

const fetchNotifications = async () => {
  if (!usePage().props.auth.user) return;
  try {
    const resp = await axios.get('/api/notifications');
    notifications.value = resp.data.notifications;
    unreadCount.value = resp.data.unreadCount;
  } catch (e) {
    console.error('Failed to fetch notifications', e);
  }
};

const markAsRead = async (notification) => {
  if (notification.read_at) return;
  try {
    await axios.post(`/api/notifications/${notification.id}/read`);
    notification.read_at = new Date().toISOString();
    unreadCount.value = Math.max(0, unreadCount.value - 1);
  } catch (e) {
    console.error('Failed to mark as read', e);
  }
};

const markAllAsRead = async () => {
  try {
    await axios.post('/api/notifications/read-all');
    notifications.value.forEach(n => n.read_at = new Date().toISOString());
    unreadCount.value = 0;
  } catch (e) {
    console.error('Failed to mark all as read', e);
  }
};

const logout = () => {
  router.post('/logout');
};

const handleScroll = () => {
  showBackToTop.value = window.scrollY > 300;
};

const scrollToTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const toggleDarkMode = () => {
  isDarkMode.value = !isDarkMode.value;
  // Lưu vào localStorage
  localStorage.setItem('theme', isDarkMode.value ? 'dark' : 'light');
  
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }
};

const handleSearch = () => {
  if (debounceTimeout.value) clearTimeout(debounceTimeout.value);
  
  if (searchQuery.value.length < 2) {
    searchResults.value = { teams: [], players: [], leagues: [] };
    isLoading.value = false;
    return;
  }

  isLoading.value = true;
  showResults.value = true;

  debounceTimeout.value = setTimeout(async () => {
    try {
      const resp = await axios.get(`/api/search?q=${searchQuery.value}`);
      searchResults.value = resp.data;
    } catch (e) {
      console.error('Search failed', e);
    } finally {
      isLoading.value = false;
    }
  }, 300);
};

// Simple click outside handling
const closeSearch = (e) => {
  if (!e.target.closest('.relative')) {
    showResults.value = false;
    showNotifications.value = false;
  }
};

onMounted(() => {
  window.addEventListener('scroll', handleScroll);
  window.addEventListener('click', closeSearch);
  // Check localstorage
  if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    isDarkMode.value = true;
    document.documentElement.classList.add('dark');
  } else {
    isDarkMode.value = false;
    document.documentElement.classList.remove('dark');
  }
  
  if (usePage().props.auth.user) {
    fetchNotifications();
    // Refresh notifications every 2 minutes
    const interval = setInterval(fetchNotifications, 120000);
    onUnmounted(() => clearInterval(interval));
  }
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
  window.removeEventListener('click', closeSearch);
  if (debounceTimeout.value) clearTimeout(debounceTimeout.value);
});
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none; /* IE and Edge */
  scrollbar-width: none; /* Firefox */
}
</style>

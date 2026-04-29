<template>
  <Head title="AI Dự Đoán | AI Soccer" />
  
  <MainLayout>
    <div class="py-8">
      <!-- Header Section -->
      <div class="mb-12">
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
          AI Dự Đoán Phân Tích
        </h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1 font-medium">
          Sử dụng trí tuệ nhân tạo để phân tích xác suất từ hàng triệu điểm dữ liệu FBref.
        </p>
      </div>

      <!-- Quick Stats / Performance -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <div v-for="stat in performanceStats" :key="stat.label" 
             class="bg-white dark:bg-gray-800 p-6 rounded-[2rem] border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col items-center text-center">
          <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2">{{ stat.label }}</span>
          <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ stat.value }}</span>
          <div class="mt-2 w-12 h-1 bg-emerald-500 rounded-full"></div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="flex flex-col lg:flex-row gap-10">
        <!-- Hot Predictions List -->
        <div class="flex-1 space-y-8 min-w-0">
          <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-4">
            <h2 class="text-sm font-bold uppercase text-gray-400 dark:text-gray-500 tracking-widest flex items-center gap-2">
              <div class="w-1.5 h-4 bg-emerald-500 rounded-full"></div>
              Kèo HOT nhất hôm nay
            </h2>
            <button class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline">Tất cả dự đoán</button>
          </div>

          <div class="space-y-4">
            <div v-for="i in 3" :key="i" 
                 class="group bg-white dark:bg-gray-800 p-6 rounded-[2rem] border border-gray-100 dark:border-gray-700 hover:border-emerald-200 dark:hover:border-emerald-500 transition-all cursor-pointer shadow-sm hover:shadow-xl hover:shadow-gray-100 dark:hover:shadow-none">
              <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <!-- Match Info -->
                <div class="flex items-center gap-8 flex-1">
                  <div class="flex-1 text-right font-bold text-gray-900 dark:text-gray-100">Home Team {{ i }}</div>
                  <div class="text-gray-200 dark:text-gray-700 font-light text-xl">VS</div>
                  <div class="flex-1 text-left font-bold text-gray-900 dark:text-gray-100">Away Team {{ i }}</div>
                </div>

                <!-- AI Probability -->
                <div class="flex items-center gap-6 md:border-l md:pl-8 border-gray-100 dark:border-gray-700">
                  <div class="text-center">
                    <span class="block text-[10px] font-bold text-emerald-500 dark:text-emerald-400 uppercase tracking-tighter mb-1">Xác suất thắng</span>
                    <span class="text-3xl font-bold text-gray-900 dark:text-white">72%</span>
                  </div>
                  <button class="px-6 py-3 bg-emerald-600 text-white text-xs font-bold rounded-xl hover:bg-emerald-500 transition-colors uppercase tracking-widest shadow-md shadow-emerald-100 dark:shadow-none">
                    Phân tích
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar: Model Info -->
        <div class="space-y-8">
          <div class="bg-gray-900 dark:bg-gray-950 text-white p-8 rounded-[2rem] shadow-2xl relative overflow-hidden">
             <!-- Abstract Decoration -->
             <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500 opacity-20 rounded-full -mr-16 -mt-16 blur-2xl"></div>
             
             <h3 class="text-lg font-bold mb-6 uppercase tracking-tight">AI Neural Engine v2.0</h3>
             <ul class="space-y-4">
                <li v-for="item in modelFeatures" :key="item" class="flex gap-3 text-sm">
                   <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                   <span class="text-gray-400 dark:text-gray-500">{{ item }}</span>
                </li>
             </ul>
             
             <div class="mt-8 pt-8 border-t border-gray-800 dark:border-gray-900">
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest leading-relaxed">
                  Mô hình liên tục tự học qua mỗi vòng đấu để cải thiện độ chính xác của kết quả.
                </p>
             </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import MainLayout from '../../Layouts/MainLayout.vue';

const performanceStats = [
  { label: 'Tỷ lệ thắng AI', value: '84.2%' },
  { label: 'Số trận phân tích', value: '4,102' },
  { label: 'Độ chính xác tỷ số', value: '12.5%' }
];

const modelFeatures = [
  'Phân tích Monte Carlo (10,000 lần mô phỏng)',
  'Dữ liệu phong độ 10 trận gần nhất',
  'Tương quan đội hình & Chấn thương',
  'Lịch sử đối đầu trực tiếp'
];
</script>

<style scoped>
</style>

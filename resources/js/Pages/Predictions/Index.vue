<template>
  <Head title="AI Dự Đoán | AI Soccer" />
  
  <MainLayout>
    <div class="py-20 flex flex-col items-center justify-center text-center">
        <!-- Minimal Empty State -->
        <div class="w-16 h-16 mb-6 rounded-2xl bg-gray-50 dark:bg-gray-800 flex items-center justify-center">
            <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
        </div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white uppercase tracking-tight mb-2">Đang phát triển</h1>
        <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Tính năng AI Dự Đoán đang được cập nhật dữ liệu</p>
    </div>
  </MainLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import MainLayout from '../../Layouts/MainLayout.vue';
</script>

<style scoped>
</style>

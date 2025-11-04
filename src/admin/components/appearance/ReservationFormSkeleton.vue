<template>
  <div class="flex flex-col gap-6 animate-pulse">
    <div class="h-10 w-40 rounded-md bg-gray-300"></div>
    <div class="h-12 w-3/4 rounded-md mb-2 bg-gray-300"></div>
    <div class="p-3 rounded-lg shadow-md flex flex-wrap justify-start items-center gap-2" :style="{ backgroundColor: 'var(--admin-card-bg)' }">
      <div v-for="i in 7" :key="i" class="h-10 w-28 rounded-md bg-gray-300"></div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 items-stretch">
      <div class="lg:col-span-1 p-4 rounded-lg shadow-md flex flex-col" :style="{ backgroundColor: 'var(--admin-card-bg)' }">
        <div class="space-y-6 flex-grow">
          <div v-for="i in 4" :key="i" class="space-y-3">
            <div class="h-5 w-1/2 rounded bg-gray-300"></div>
            <div class="h-8 w-full rounded bg-gray-300"></div>
          </div>
        </div>
        <div class="mt-auto pt-4 border-t flex justify-end items-center gap-2" :style="{ borderColor: 'var(--admin-border-color)' }">
            <div class="h-8 w-20 rounded-md bg-gray-300"></div>
            <div class="h-8 w-20 rounded-md bg-gray-300"></div>
        </div>
      </div>
      <div class="lg:col-span-3 p-4 rounded-lg shadow-md flex flex-col" :style="{ backgroundColor: 'var(--admin-card-bg)' }">
        <div class="h-6 w-1/4 rounded mb-2 bg-gray-300"></div>
        <div class="flex-grow rounded-lg border" :style="{ borderColor: 'var(--admin-border-color)', backgroundColor: 'var(--admin-input-bg-color)' }">
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
// This component provides a skeleton loading state for the ReservationFormSettings page.
</script>

<template>
  <div class="flex flex-col min-w-0 break-words rounded-xl shadow-lg transform transition-all duration-300 hover:scale-105 hover:shadow-2xl cursor-pointer content-card" @click="redirect">
    <div class="p-2 flex-auto"> 
      <div class="flex items-center gap-x-4">
        
        <div v-if="icon" :class="['p-2 text-center inline-flex items-center justify-center w-10 h-10 shadow-md rounded-full', iconBgClass]"> 
          <i :class="[icon, 'text-white text-lg']"></i> 
        </div>
        
        <div class="relative w-auto">
          <p class="font-semibold text-xs" :style="{ color: 'var(--admin-card-text-color)' }">{{ title }}</p> 
          <h3 class="text-2xl font-bold" :style="{ color: 'var(--admin-text-color)' }">{{ value }}</h3> 
        </div>
      </div>
    </div>
    <div class="p-2 border-t rounded-b-xl" :style="{ backgroundColor: 'var(--admin-input-border-color)', borderColor: 'var(--admin-border-color)' }">
      <p class="text-xs" :style="{ color: 'var(--admin-card-text-color)' }">
        <slot name="footer"></slot>
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed, defineProps } from 'vue';
import { useRouter } from 'vue-router';

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number],
    required: true,
  },
  icon: {
    type: String,
    default: null,
  },
  iconColor: {
    type: String,
    default: 'blue',
  },
  routePath: {
    type: String,
    default: '',
  },
  redirectUrl: {
    type: String,
    default: '',
  },
  routeQuery: {
    type: Object,
    default: null,
  },
});

const iconBgClass = computed(() => {
  switch (props.iconColor) {
    case 'blue':
      return 'bg-blue-500';
    case 'green':
      return 'bg-green-500';
    case 'red':
      return 'bg-red-500';
    case 'yellow':
      return 'bg-yellow-500';
    case 'indigo':
      return 'bg-indigo-500';
    default:
      return 'bg-gray-500';
  }
});

const router = useRouter();

function redirect() {
  if (props.routePath) {
    // If routeQuery is passed as a prop, use it
    if (props.routeQuery) {
      router.push({ path: props.routePath, query: props.routeQuery });
    } else {
      router.push({ path: props.routePath });
    }
  } else if (props.redirectUrl) {
    window.location.href = props.redirectUrl;
  }
}
</script>

<style scoped>
/* No specific styles needed here, Tailwind CSS handles everything */
</style>


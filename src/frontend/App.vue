<template>  
    <router-view />
</template>

<script setup>
import '@/frontend/utils/cdn-tailwindcss.js'; 


tailwind.config ={
    // important: true,
    prefix: 'schedula-',
    corePlugins: {
        preflight: false,
    },
    contentt: [
        './**/*.{js,vue}',
    ]

}

</script>

<style scoped>
</style>
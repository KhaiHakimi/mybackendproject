<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    ferries: Array
});

const getImageUrl = (path) => {
    return path || 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80'; // Fallback
};
</script>

<template>
    <Head title="Our Fleet" />

    <GuestLayout :fullWidth="true">
        <template #header>
             <h2 class="font-extrabold text-2xl text-blue-900 leading-tight">
                Ferry Fleet & Services
            </h2>
        </template>
        
        <div class="py-12 bg-cream-50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center mb-16 animate-fade-in">
                 <div class="inline-block px-4 py-1.5 mb-6 text-[10px] font-black tracking-widest text-blue-900 uppercase rounded-full bg-blue-100 shadow-sm border border-blue-200">
                    Premium Marine Standards
                </div>
                <h1 class="text-5xl font-black text-blue-900 mb-6 tracking-tight sm:text-6xl">
                    Our Marine Fleet
                </h1>
                <p class="text-xl text-blue-800/60 max-w-2xl mx-auto font-medium leading-relaxed">
                    Explore our collection of modern, safe, and comfortable vessels serving the Malaysian coastline.
                </p>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                    <div v-for="ferry in ferries" :key="ferry.id" class="group bg-white rounded-[2.5rem] overflow-hidden shadow-xl hover:shadow-2xl transition-all duration-500 border border-blue-50 flex flex-col h-full hover:-translate-y-2">
                        <div class="relative h-72 bg-blue-50 overflow-hidden">
                            <img :src="getImageUrl(ferry.image_path)" :alt="ferry.name" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                            <div v-if="ferry.rating >= 4.5" class="absolute top-6 left-6 bg-white/90 backdrop-blur px-4 py-1.5 rounded-full text-[10px] font-black shadow-lg text-emerald-600 border border-emerald-50 uppercase tracking-widest">
                                Top Rated
                            </div>
                            <!-- Title overlay on image -->
                             <div class="absolute bottom-0 left-0 w-full bg-gradient-to-t from-blue-900/90 via-blue-900/40 to-transparent p-8">
                                <h3 class="text-3xl font-black text-white leading-tight">{{ ferry.name }}</h3>
                                <p class="text-blue-100/80 text-sm font-bold uppercase tracking-wider">{{ ferry.operator }}</p>
                            </div>
                        </div>
                        
                        <div class="p-8 flex-1 flex flex-col bg-white">
                            <div class="flex items-center mb-6">
                                <div class="flex text-yellow-400 text-sm">
                                    <span v-for="i in 5" :key="i">
                                        <svg v-if="i <= Math.round(ferry.rating || 0)" class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                        <svg v-else class="w-5 h-5 text-gray-200 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                    </span>
                                </div>
                                <span class="ml-3 text-sm text-blue-900/40 font-black uppercase tracking-tighter">{{ ferry.rating || 0 }} / 5.0</span>
                            </div>

                            <div class="flex items-center space-x-8 text-sm text-blue-900 font-bold mb-6">
                                <div class="flex items-center" v-if="ferry.length_ft">
                                    <div class="bg-blue-50 p-2 rounded-lg mr-3">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                    </div>
                                    {{ ferry.length_ft }}
                                </div>
                                <div class="flex items-center">
                                    <div class="bg-blue-50 p-2 rounded-lg mr-3">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    </div>
                                    {{ ferry.capacity }} Seats
                                </div>
                            </div>

                            <div class="mb-8">
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="amenity in ferry.amenities" :key="amenity" class="px-3 py-1.5 bg-blue-50 text-blue-700 text-[10px] font-black uppercase rounded-lg border border-blue-100">
                                        {{ amenity }}
                                    </span>
                                     <span v-if="!ferry.amenities || ferry.amenities.length === 0" class="text-xs text-blue-300 italic font-medium">No amenities listed</span>
                                </div>
                            </div>
                            
                            <div class="mt-auto flex items-center justify-between pt-6 border-t border-blue-50">
                                <div>
                                    <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest">Rate From</p>
                                    <p class="text-2xl font-black text-blue-900">RM {{ ferry.price || '0' }}</p>
                                </div>
                                <Link :href="route('ferries.public_show', ferry.id)" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-2xl font-black text-sm transition-all shadow-lg shadow-blue-100 transform active:scale-95">
                                    VIEW VESSEL
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
                
                 <div class="text-center mt-20 pb-20">
                     <button class="bg-yellow-400 hover:bg-yellow-500 text-blue-900 px-12 py-4 rounded-2xl font-black text-lg transition-all inline-flex items-center shadow-2xl shadow-yellow-100 transform hover:scale-105 active:scale-95">
                        EXPLORE FULL FLEET
                         <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                     </button>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
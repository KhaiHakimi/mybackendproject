<template>
    <Head :title="`Weather: ${port.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-extrabold leading-tight text-blue-900">
                    Weather & Risk Analysis: {{ port.name }}
                </h2>
                <Link :href="route('schedules.index')" class="text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to Schedules
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
                
                <div v-if="weather" class="grid grid-cols-1 md:grid-cols-2 gap-8 animate-fade-in-up">
                    <!-- Weather Details Card -->
                    <div class="bg-white p-8 rounded-3xl shadow-xl border border-blue-100 relative overflow-hidden">
                        <div class="relative z-10">
                            <h3 class="text-2xl font-bold mb-6 text-blue-900 flex items-center">
                                <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg>
                                Current Conditions
                            </h3>
                            <div class="grid grid-cols-1 gap-6">
                                <div class="flex justify-between items-center bg-blue-50/50 p-4 rounded-2xl border border-blue-50">
                                    <span class="text-blue-800 font-semibold">Wind Speed</span>
                                    <span class="text-2xl font-black text-blue-900">{{ weather.wind_speed }} <span class="text-sm font-normal">km/h</span></span>
                                </div>
                                <div class="flex justify-between items-center bg-blue-50/50 p-4 rounded-2xl border border-blue-50">
                                    <span class="text-blue-800 font-semibold">Wave Height</span>
                                    <span class="text-2xl font-black text-blue-900">{{ weather.wave_height }} <span class="text-sm font-normal">meters</span></span>
                                </div>
                                <div class="flex justify-between items-center bg-blue-50/50 p-4 rounded-2xl border border-blue-50">
                                    <span class="text-blue-800 font-semibold">Visibility</span>
                                    <span class="text-2xl font-black text-blue-900">{{ weather.visibility || 'N/A' }} <span class="text-sm font-normal">km</span></span>
                                </div>
                                <div class="flex justify-between items-center bg-blue-50/50 p-4 rounded-2xl border border-blue-50">
                                    <span class="text-blue-800 font-semibold">Precipitation</span>
                                    <span class="text-2xl font-black text-blue-900">{{ weather.precipitation || '0' }} <span class="text-sm font-normal">mm</span></span>
                                </div>
                            </div>
                            <div class="mt-8 pt-6 border-t border-blue-50 flex items-center justify-between text-blue-400">
                                <span class="text-xs font-bold uppercase tracking-widest">Last Updated</span>
                                <span class="text-sm font-medium">{{ new Date(weather.recorded_at).toLocaleString() }}</span>
                            </div>
                        </div>
                        <!-- Watermark Icon -->
                        <svg class="absolute -right-10 -top-10 w-48 h-48 text-blue-50/50 pointer-events-none" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71z"/></svg>
                    </div>

                    <!-- Risk Analysis Card -->
                    <div class="bg-white p-8 rounded-[3rem] shadow-xl border border-blue-100 flex flex-col items-center justify-center text-center relative overflow-hidden min-h-[450px]">
                        <div class="relative z-10 w-full">
                            <h3 class="text-2xl font-black text-blue-900 mb-2 uppercase tracking-tighter">AI Safety Verdict</h3>
                            
                            <div 
                                class="w-56 h-56 rounded-full flex flex-col items-center justify-center border-[16px] shadow-inner my-8 mx-auto transition-all duration-1000 relative"
                                :class="{
                                    'border-emerald-500 text-emerald-600 bg-emerald-50/20': risk_analysis.color === 'green',
                                    'border-yellow-400 text-yellow-600 bg-yellow-50/20': risk_analysis.color === 'yellow',
                                    'border-rose-500 text-rose-600 bg-rose-50/20': risk_analysis.color === 'red',
                                    'border-gray-200 text-gray-400': risk_analysis.color === 'gray'
                                }"
                            >
                                <div class="flex flex-col items-center justify-center">
                                    <span class="text-6xl font-black leading-none tracking-tighter">{{ weather.risk_score }}%</span>
                                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-900/30 mt-3">Risk Factor</span>
                                </div>
                            </div>
                            
                            <div 
                                class="text-3xl font-black px-10 py-3 rounded-2xl shadow-lg inline-block transform -rotate-1 transition-all"
                                :class="{
                                    'bg-emerald-600 text-white': risk_analysis.color === 'green',
                                    'bg-yellow-400 text-blue-900': risk_analysis.color === 'yellow',
                                    'bg-rose-600 text-white': risk_analysis.color === 'red',
                                }"
                            >
                                {{ risk_analysis.status.toUpperCase() }}
                            </div>
                            <p class="text-blue-800/60 mt-8 px-10 text-sm font-medium italic leading-relaxed">
                                "Our neural engine analyzed {{ port.name }}'s marine metrics and predicts a {{ weather.risk_score }}% probability of schedule disruptions."
                            </p>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-20 bg-white rounded-3xl border border-dashed border-blue-200 shadow-sm animate-pulse">
                    <svg class="w-20 h-20 mx-auto text-blue-100 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-2xl font-bold text-blue-900/30">Monitoring Station Offline</p>
                    <p class="text-blue-400 mt-2">No historical data found. Please trigger a manual fetch below.</p>
                </div>

                <!-- Simulation & Fetch Tool -->
                <div class="bg-blue-900/5 backdrop-blur-sm p-8 rounded-3xl border-2 border-dashed border-blue-200 animate-fade-in">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-6 mb-8">
                        <div>
                            <h3 class="text-xl font-bold text-blue-900">Control Center</h3>
                            <p class="text-blue-600 text-sm">Force update data or simulate marine environments</p>
                        </div>
                        <button @click="refreshWeather" type="button" class="w-full md:w-auto bg-white text-blue-600 border-2 border-blue-600 hover:bg-blue-600 hover:text-white px-8 py-3 rounded-xl font-black text-sm flex items-center justify-center gap-2 transition-all shadow-md active:scale-95">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            REFRESH LIVE SENSORS
                        </button>
                    </div>
                    <form @submit.prevent="submit" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-blue-900 ml-2">Wind Speed (km/h)</label>
                            <input v-model="form.wind_speed" type="number" step="0.1" placeholder="0.0" class="w-full bg-white border-blue-100 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-sm py-3 px-4" :class="{'border-rose-500': form.errors.wind_speed}">
                            <div v-if="form.errors.wind_speed" class="text-rose-600 text-[10px] font-bold mt-1 px-2">{{ form.errors.wind_speed }}</div>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-blue-900 ml-2">Wave Height (m)</label>
                            <input v-model="form.wave_height" type="number" step="0.1" placeholder="0.0" class="w-full bg-white border-blue-100 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-sm py-3 px-4" :class="{'border-rose-500': form.errors.wave_height}">
                            <div v-if="form.errors.wave_height" class="text-rose-600 text-[10px] font-bold mt-1 px-2">{{ form.errors.wave_height }}</div>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-blue-900 ml-2">Visibility (km)</label>
                            <input v-model="form.visibility" type="number" step="0.1" placeholder="10.0" class="w-full bg-white border-blue-100 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-sm py-3 px-4" :class="{'border-rose-500': form.errors.visibility}">
                             <div v-if="form.errors.visibility" class="text-rose-600 text-[10px] font-bold mt-1 px-2">{{ form.errors.visibility }}</div>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-blue-900 ml-2">Precipitation (mm)</label>
                            <input v-model="form.precipitation" type="number" step="0.1" placeholder="0.0" class="w-full bg-white border-blue-100 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-sm py-3 px-4" :class="{'border-rose-500': form.errors.precipitation}">
                             <div v-if="form.errors.precipitation" class="text-rose-600 text-[10px] font-bold mt-1 px-2">{{ form.errors.precipitation }}</div>
                        </div>
                        <button type="submit" class="sm:col-span-2 lg:col-span-4 bg-blue-600 text-white py-4 rounded-2xl font-black text-lg shadow-xl shadow-blue-200 hover:bg-blue-700 transition-all transform active:scale-[0.98]">
                            RE-SEED AI MODEL & ANALYZE
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    port: Object,
    weather: Object,
    risk_analysis: Object
});

const form = useForm({
    wind_speed: '',
    wave_height: '',
    visibility: '',
    precipitation: ''
});

const submit = () => {
    form.post(route('weather.store', props.port.id), {
        onSuccess: () => form.reset(),
        preserveScroll: true
    });
};

const refreshWeather = () => {
    if (confirm('Fetch real-time weather data for this location?')) {
        form.post(route('weather.refresh', props.port.id), {
            preserveScroll: true
        });
    }
};
</script>
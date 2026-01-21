<template>
    <Head :title="`Weather: ${port.name}`" />

    <GuestLayout fullWidth>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-extrabold leading-tight text-blue-900">
                    Weather & Risk Analysis: {{ port.name }}
                </h2>
                <Link
                    :href="route('dashboard')"
                    class="text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1 transition-colors"
                >
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"
                        ></path>
                    </svg>
                    Back to Dashboard
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
                <div
                    v-if="weather"
                    class="grid grid-cols-1 md:grid-cols-2 gap-8 animate-fade-in-up"
                >
                    <!-- Weather Details Card -->
                    <div
                        class="bg-white p-8 rounded-3xl shadow-xl border border-blue-100 relative overflow-hidden"
                    >
                        <div class="relative z-10">
                            <h3
                                class="text-2xl font-bold mb-6 text-blue-900 flex items-center"
                            >
                                <svg
                                    class="w-6 h-6 mr-2 text-blue-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"
                                    ></path>
                                </svg>
                                Current Conditions
                            </h3>
                            <div class="grid grid-cols-1 gap-6">
                                <div
                                    class="flex justify-between items-center bg-blue-50/50 p-4 rounded-2xl border border-blue-50"
                                >
                                    <span class="text-blue-800 font-semibold"
                                        >Wind Speed</span
                                    >
                                    <span
                                        class="text-2xl font-black text-blue-900"
                                        >{{ weather.wind_speed }}
                                        <span class="text-sm font-normal"
                                            >km/h</span
                                        ></span
                                    >
                                </div>
                                <div
                                    class="flex justify-between items-center bg-blue-50/50 p-4 rounded-2xl border border-blue-50"
                                >
                                    <span class="text-blue-800 font-semibold"
                                        >Wave Height</span
                                    >
                                    <span
                                        class="text-2xl font-black text-blue-900"
                                        >{{ weather.wave_height }}
                                        <span class="text-sm font-normal"
                                            >meters</span
                                        ></span
                                    >
                                </div>
                                <div
                                    class="flex justify-between items-center bg-blue-50/50 p-4 rounded-2xl border border-blue-50"
                                >
                                    <span class="text-blue-800 font-semibold"
                                        >Visibility</span
                                    >
                                    <span
                                        class="text-2xl font-black text-blue-900"
                                        >{{ weather.visibility || 'N/A' }}
                                        <span class="text-sm font-normal"
                                            >km</span
                                        ></span
                                    >
                                </div>
                                <div
                                    class="flex justify-between items-center bg-blue-50/50 p-4 rounded-2xl border border-blue-50"
                                >
                                    <span class="text-blue-800 font-semibold"
                                        >Precipitation</span
                                    >
                                    <span
                                        class="text-2xl font-black text-blue-900"
                                        >{{ weather.precipitation || '0' }}
                                        <span class="text-sm font-normal"
                                            >mm</span
                                        ></span
                                    >
                                </div>
                            </div>
                            <div
                                class="mt-8 pt-6 border-t border-blue-50 flex items-center justify-between text-blue-400"
                            >
                                <span
                                    class="text-xs font-bold uppercase tracking-widest"
                                    >Last Updated</span
                                >
                                <span class="text-sm font-medium">{{
                                    new Date(
                                        weather.recorded_at,
                                    ).toLocaleString('en-GB', {
                                        timeZone: 'Asia/Kuala_Lumpur',
                                    })
                                }}</span>
                            </div>
                        </div>
                        <!-- Watermark Icon -->
                        <svg
                            class="absolute -right-10 -top-10 w-48 h-48 text-blue-50/50 pointer-events-none"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71z"
                            />
                        </svg>
                    </div>

                    <!-- Risk Analysis Card -->
                    <div
                        class="bg-white p-8 rounded-[3rem] shadow-xl border border-blue-100 flex flex-col items-center justify-center text-center relative overflow-hidden min-h-[450px]"
                    >
                        <div class="relative z-10 w-full">
                            <h3
                                class="text-2xl font-black text-blue-900 mb-2 uppercase tracking-tighter"
                            >
                                AI Safety Verdict
                            </h3>

                            <div
                                class="w-56 h-56 rounded-full flex flex-col items-center justify-center border-[16px] shadow-inner my-8 mx-auto transition-all duration-1000 relative"
                                :class="{
                                    'border-emerald-500 text-emerald-600 bg-emerald-50/20':
                                        risk_analysis.color === 'green',
                                    'border-yellow-400 text-yellow-600 bg-yellow-50/20':
                                        risk_analysis.color === 'yellow',
                                    'border-rose-500 text-rose-600 bg-rose-50/20':
                                        risk_analysis.color === 'red',
                                    'border-gray-200 text-gray-400':
                                        risk_analysis.color === 'gray',
                                }"
                            >
                                <div
                                    class="flex flex-col items-center justify-center"
                                >
                                    <span
                                        class="text-6xl font-black leading-none tracking-tighter"
                                        >{{ weather.risk_score }}%</span
                                    >
                                    <span
                                        class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-900/30 mt-3"
                                        >Risk Factor</span
                                    >
                                </div>
                            </div>

                            <div
                                class="text-3xl font-black px-10 py-3 rounded-2xl shadow-lg inline-block transform -rotate-1 transition-all"
                                :class="{
                                    'bg-emerald-600 text-white':
                                        risk_analysis.color === 'green',
                                    'bg-yellow-400 text-blue-900':
                                        risk_analysis.color === 'yellow',
                                    'bg-rose-600 text-white':
                                        risk_analysis.color === 'red',
                                }"
                            >
                                {{ risk_analysis.status.toUpperCase() }}
                            </div>
                            <p
                                class="text-blue-800/60 mt-8 px-10 text-sm font-medium italic leading-relaxed"
                            >
                                "Our neural engine analyzed {{ port.name }}'s
                                marine metrics and predicts a
                                {{ weather.risk_score }}% probability of
                                schedule disruptions."
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="text-center py-20 bg-white rounded-3xl border border-dashed border-blue-200 shadow-sm animate-pulse"
                >
                    <svg
                        class="w-20 h-20 mx-auto text-blue-100 mb-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                        ></path>
                    </svg>
                    <p class="text-2xl font-bold text-blue-900/30">
                        Monitoring Station Offline
                    </p>
                    <p class="text-blue-400 mt-2 mb-6">
                        No historical data found. Connect to live marine
                        sensors.
                    </p>
                    <button
                        @click="refreshWeather"
                        type="button"
                        class="bg-blue-600 text-white px-8 py-3 rounded-full font-bold shadow-lg hover:bg-blue-700 transition transform hover:scale-105 flex items-center gap-2 mx-auto"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                        FETCH REAL LIVE DATA
                    </button>
                </div>

                <!-- Live Data Fetch (Public) -->
                <div
                    v-if="weather"
                    class="flex justify-center mt-8 animate-fade-in"
                >
                    <button
                        @click="refreshWeather"
                        type="button"
                        class="group bg-white text-blue-600 border-2 border-blue-600 hover:bg-blue-600 hover:text-white px-8 py-4 rounded-2xl font-black text-sm flex items-center justify-center gap-3 transition-all shadow-xl hover:shadow-2xl transform hover:-translate-y-1 active:scale-95"
                    >
                        <svg
                            class="w-6 h-6 group-hover:animate-spin"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            ></path>
                        </svg>
                        FETCH REAL-TIME DATA & RE-CALCULATE RISK
                    </button>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<script setup>
    import GuestLayout from '@/Layouts/GuestLayout.vue'
    import { Head, router, Link } from '@inertiajs/vue3'

    const props = defineProps({
        port: Object,
        weather: Object,
        risk_analysis: Object,
    })

    const refreshWeather = () => {
        if (confirm('Fetch real-time weather data for this location?')) {
            router.post(
                route('weather.refresh', props.port.id),
                {},
                {
                    preserveScroll: true,
                },
            )
        }
    }
</script>

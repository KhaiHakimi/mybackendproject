<template>
    <Head title="Ferry Schedules" />

    <GuestLayout :fullWidth="true">
        <template #header>
            <h2 class="text-2xl font-extrabold leading-tight text-blue-900">
                Marine Timetables
            </h2>
        </template>

        <div class="py-12 bg-cream-50 min-h-screen">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
                
                <!-- 1. Date Navigation -->
                <div class="flex items-center justify-between bg-white p-6 rounded-[2rem] shadow-xl border border-blue-50">
                    <button @click="changeDate(-1)" class="group flex items-center text-blue-600 hover:text-blue-900 font-black px-6 py-3 hover:bg-blue-50 rounded-2xl transition-all">
                        <svg class="w-6 h-6 mr-2 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7"></path></svg>
                        PREV
                    </button>
                    
                    <div class="text-center">
                        <div class="text-[10px] text-blue-400 font-black uppercase tracking-[0.2em] mb-1">Voyage Date</div>
                        <div class="text-3xl font-black text-blue-900 leading-none">{{ formatDateFull(currentDate) }}</div>
                         <input type="date" v-model="currentDate" @change="applyFilters" class="mt-4 block max-w-xs mx-auto text-sm border-blue-100 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 font-bold text-blue-900" />
                    </div>

                    <button @click="changeDate(1)" class="group flex items-center text-blue-600 hover:text-blue-900 font-black px-6 py-3 hover:bg-blue-50 rounded-2xl transition-all">
                        NEXT
                        <svg class="w-6 h-6 ml-2 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>

                <!-- 2. Filters Bar -->
                <div class="bg-white p-8 rounded-[2rem] shadow-xl border border-blue-50 relative overflow-hidden">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end relative z-10">
                        
                        <!-- Location / Near Me -->
                        <div>
                            <label class="block text-[10px] font-black text-blue-900 uppercase tracking-widest mb-2 ml-1 text-opacity-50">Discovery</label>
                            <button @click="locateUser" class="w-full h-[52px] inline-flex justify-center items-center px-6 py-2 bg-blue-600 border border-transparent rounded-2xl font-black text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none shadow-lg shadow-blue-100 transition transform active:scale-95">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                {{ userLocation ? 'Update Radar' : 'Find Jetties Near Me' }}
                            </button>
                        </div>

                         <!-- State Filter -->
                        <div>
                            <label class="block text-[10px] font-black text-blue-900 uppercase tracking-widest mb-2 ml-1 text-opacity-50">Region</label>
                            <select v-model="selectedState" @change="applyFilters" class="w-full border-blue-50 bg-blue-50/30 rounded-2xl shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm h-[52px] font-bold text-blue-900">
                                <option value="">All Regions</option>
                                <option v-for="state in states" :key="state" :value="state">{{ state }}</option>
                            </select>
                        </div>

                        <!-- Destination Filter -->
                         <div>
                            <label class="block text-[10px] font-black text-blue-900 uppercase tracking-widest mb-2 ml-1 text-opacity-50">Arrival Port</label>
                            <select v-model="selectedDestination" @change="applyFilters" class="w-full border-blue-50 bg-blue-50/30 rounded-2xl shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm h-[52px] font-bold text-blue-900">
                                <option value="">Any Destination</option>
                                <option v-for="port in ports" :key="port.id" :value="port.id">{{ port.name }}</option>
                            </select>
                        </div>

                        <!-- Time Filter -->
                         <div>
                            <label class="block text-[10px] font-black text-blue-900 uppercase tracking-widest mb-2 ml-1 text-opacity-50">Departure Time</label>
                            <select v-model="selectedTime" @change="applyFilters" class="w-full border-blue-50 bg-blue-50/30 rounded-2xl shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm h-[52px] font-bold text-blue-900">
                                <option value="">Any Time</option>
                                <option value="morning">Morning Wave</option>
                                <option value="afternoon">Afternoon Tide</option>
                                <option value="evening">Evening Star</option>
                            </select>
                        </div>
                    </div>

                    <!-- Active Filters / Clear -->
                    <div v-if="hasActiveFilters" class="mt-6 flex items-center justify-between text-sm pt-6 border-t border-blue-50 relative z-10">
                        <div class="text-blue-800/60 font-medium">
                            Monitoring <span class="font-black text-blue-900">{{ groupedSchedulesCount }}</span> active voyages.
                        </div>
                        <button @click="clearFilters" class="text-rose-600 hover:text-rose-800 font-black uppercase text-[10px] tracking-widest underline decoration-2 underline-offset-4">
                            Reset All Sensors
                        </button>
                    </div>
                    <!-- Background accent -->
                    <svg class="absolute right-0 top-0 w-64 h-64 text-blue-50/20 pointer-events-none transform translate-x-20 -translate-y-20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71z"/></svg>
                </div>

                <!-- Nearest Ports Information -->
                <div v-if="nearestPorts.length > 0" class="bg-emerald-50 border border-emerald-100 rounded-[2rem] p-8 shadow-inner animate-fade-in">
                    <h3 class="text-emerald-900 font-black mb-6 flex items-center uppercase tracking-widest text-xs">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        PROXIMITY ALERT: 3 Nearest Terminals Detected
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div v-for="port in nearestPorts" :key="port.id" class="bg-white p-5 rounded-2xl shadow-sm border border-emerald-100 flex justify-between items-center group hover:shadow-md transition-shadow">
                            <div>
                                <div class="font-black text-emerald-900 group-hover:text-emerald-600 transition-colors">{{ port.name }}</div>
                                <div class="text-[10px] font-bold text-emerald-600/50 uppercase mt-1">{{ parseFloat(port.distance).toFixed(1) }} KM DISTANCE</div>
                            </div>
                            <div class="bg-emerald-50 p-2 rounded-full">
                                <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Schedules & Map Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                    <!-- Schedule List -->
                    <div class="lg:col-span-2 space-y-10">
                        <!-- Timetable Groups -->
                        <div v-if="Object.keys(groupedByOperator).length > 0" class="space-y-10">
                            <div v-for="(schedules, operator) in groupedByOperator" :key="operator" class="bg-white rounded-[2.5rem] shadow-xl overflow-hidden border border-blue-50 transition-all duration-500 hover:shadow-2xl">
                                
                                <!-- Operator Header -->
                                <div class="px-8 py-6 bg-blue-900 flex items-center justify-between">
                                    <h3 class="text-xl font-black text-white flex items-center uppercase tracking-widest">
                                        <span class="bg-yellow-400 w-2 h-6 mr-4 rounded-full"></span>
                                        {{ operator }}
                                    </h3>
                                    <span class="text-[10px] text-blue-900 font-black bg-white px-4 py-1.5 rounded-full shadow-lg uppercase tracking-tighter">
                                        {{ schedules.length }} ACTIVE VOYAGES
                                    </span>
                                </div>

                                <!-- Schedule Table -->
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-blue-50">
                                        <thead class="bg-blue-50/30">
                                            <tr>
                                                <th scope="col" class="px-8 py-4 text-left text-[10px] font-black text-blue-900/40 uppercase tracking-widest">DEPARTURE</th>
                                                <th scope="col" class="px-8 py-4 text-left text-[10px] font-black text-blue-900/40 uppercase tracking-widest">VOYAGE PATH</th>
                                                <th scope="col" class="px-8 py-4 text-left text-[10px] font-black text-blue-900/40 uppercase tracking-widest">VESSEL</th>
                                                <th scope="col" class="px-8 py-4 text-left text-[10px] font-black text-blue-900/40 uppercase tracking-widest">TICKET TYPE</th>
                                                <th scope="col" class="px-8 py-4 text-right text-[10px] font-black text-blue-900/40 uppercase tracking-widest">FARE (RM)</th>
                                                <th scope="col" class="px-8 py-4 text-center text-[10px] font-black text-blue-900/40 uppercase tracking-widest">STATUS</th>
                                                <th scope="col" class="px-8 py-4 text-center text-[10px] font-black text-blue-900/40 uppercase tracking-widest">ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-blue-50">
                                            <tr 
                                                v-for="trip in schedules" 
                                                :key="trip.id" 
                                                class="group hover:bg-blue-50/50 transition-all duration-300 cursor-pointer"
                                                @mouseenter="highlightedRouteId = trip.id"
                                                @mouseleave="highlightedRouteId = null"
                                            >
                                                <td class="px-8 py-6 whitespace-nowrap text-2xl text-blue-900 font-black tracking-tighter">
                                                     {{ formatTime(trip.departure_time) }}
                                                </td>
                                                <td class="px-8 py-6 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <span class="font-black text-blue-900 text-sm">{{ trip.origin.name }}</span>
                                                        <div class="flex flex-col items-center mx-4">
                                                            <svg class="w-5 h-5 text-blue-300 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                                        </div>
                                                        <span class="font-black text-blue-900 text-sm">{{ trip.destination.name }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-8 py-6 whitespace-nowrap">
                                                    <div class="flex items-center text-blue-800/60 font-bold text-sm">
                                                        <svg class="w-4 h-4 mr-2 text-blue-400 group-hover:text-blue-600 transition-colors" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"></path></svg>
                                                        {{ trip.ferry.name }}
                                                    </div>
                                                </td>
                                                <td class="px-8 py-6 whitespace-nowrap">
                                                    <span 
                                                        class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-lg border"
                                                        :class="{
                                                            'bg-emerald-50 text-emerald-600 border-emerald-100': trip.ferry.ticket_type === 'Online Booking',
                                                            'bg-orange-50 text-orange-600 border-orange-100': trip.ferry.ticket_type === 'Walk-in / Counter',
                                                            'bg-blue-50 text-blue-600 border-blue-100': trip.ferry.ticket_type === 'Both Available'
                                                        }"
                                                    >
                                                        {{ trip.ferry.ticket_type }}
                                                    </span>
                                                </td>
                                                <td class="px-8 py-6 whitespace-nowrap text-right font-black text-blue-900 text-xl tracking-tighter">
                                                    {{ trip.price }}
                                                </td>
                                                <td class="px-8 py-6 whitespace-nowrap text-center">
                                                    <span 
                                                        class="px-4 py-1.5 inline-flex text-[10px] font-black rounded-full uppercase tracking-widest shadow-sm"
                                                        :class="getStatusColor(trip)"
                                                    >
                                                        {{ getStatus(trip) }}
                                                    </span>
                                                </td>
                                                <td class="px-8 py-6 whitespace-nowrap text-center">
                                                    <a 
                                                        v-if="trip.ferry.ticket_type !== 'Walk-in / Counter'"
                                                        :href="trip.ferry.booking_url || 'https://www.easybook.com'" 
                                                        target="_blank"
                                                        class="inline-flex items-center px-4 py-2 bg-yellow-400 border border-transparent rounded-xl font-black text-[10px] text-blue-900 uppercase tracking-widest hover:bg-yellow-300 focus:outline-none shadow-lg shadow-yellow-100 transition transform active:scale-95"
                                                    >
                                                        Book Now
                                                    </a>
                                                    <span v-else class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                                        Counter Only
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div v-else class="text-center py-32 bg-white rounded-[3rem] border-4 border-dashed border-blue-50 shadow-sm animate-fade-in">
                            <svg class="w-24 h-24 mx-auto text-blue-100 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <div class="text-3xl font-black text-blue-900/20 mb-4 uppercase tracking-tighter">No Active Voyages Found</div>
                            <p class="text-blue-400 font-bold">Try adjusting your radar filters or select another date.</p>
                        </div>
                    </div>

                    <!-- Map Column -->
                    <div class="lg:col-span-1">
                        <div class="bg-white p-2 rounded-[2.5rem] shadow-2xl border border-blue-50 sticky top-24 h-[650px] overflow-hidden group">
                            <FerryMap 
                                :markers="mapMarkers" 
                                :routes="mapRoutes" 
                                :highlightedRouteId="highlightedRouteId"
                                :center="mapCenter"
                                :zoom="mapZoom"
                            />
                            <!-- Map Overlay Label -->
                            <div class="absolute top-6 left-6 bg-blue-900/90 backdrop-blur text-white px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest shadow-xl border border-blue-800 pointer-events-none">
                                LIVE MARINE RADAR
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Footer Note -->
                <div class="bg-blue-900 p-10 rounded-[3rem] shadow-2xl mt-12 text-center relative overflow-hidden">
                    <div class="relative z-10">
                        <p class="font-black text-white text-2xl mb-4 uppercase tracking-tighter">Crucial Information</p>
                        <p class="text-blue-200 text-sm max-w-2xl mx-auto font-medium leading-relaxed">
                            Marine schedules are subject to tidal conditions and weather safety protocols. <br/>
                            <span class="text-yellow-400 font-black">All passengers must report to the terminal 45 minutes prior to departure.</span>
                        </p>
                    </div>
                    <!-- Background Wave -->
                    <svg class="absolute bottom-0 left-0 w-full text-white/5 pointer-events-none" viewBox="0 0 1440 320"><path fill="currentColor" d="M0,96L48,112C96,128,192,160,288,186.7C384,213,480,235,576,213.3C672,192,768,128,864,128C960,128,1056,192,1152,208C1248,224,1344,192,1392,176L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>
                </div>

                <!-- Admin Management (Only Show if Admin) -->
                <div v-if="$page.props.auth.user && $page.props.auth.user.is_admin" class="mt-20 bg-white overflow-hidden shadow-2xl rounded-[3rem] p-10 border-4 border-dashed border-blue-100">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="bg-blue-900 p-3 rounded-2xl">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                        </div>
                        <h2 class="text-2xl font-black text-blue-900 uppercase tracking-tighter">Command Center: Add Voyage</h2>
                    </div>
                    <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-blue-900 ml-2">Assign Vessel</label>
                            <select v-model="form.ferry_id" class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500">
                                <option value="" disabled>Select Ferry</option>
                                <option v-for="ferry in ferries" :value="ferry.id" :key="ferry.id">{{ ferry.name }}</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-blue-900 ml-2">Origin Port</label>
                            <select v-model="form.origin_port_id" class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500">
                                <option value="" disabled>Origin Port</option>
                                <option v-for="port in ports" :value="port.id" :key="port.id">{{ port.name }}</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-blue-900 ml-2">Destination Port</label>
                            <select v-model="form.destination_port_id" class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500">
                                <option value="" disabled>Destination Port</option>
                                <option v-for="port in ports" :value="port.id" :key="port.id">{{ port.name }}</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-blue-900 ml-2">Departure Data</label>
                            <input type="datetime-local" v-model="form.departure_time" class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500">
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-blue-900 ml-2">Arrival ETA</label>
                            <input type="datetime-local" v-model="form.arrival_time" class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500">
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black uppercase text-blue-900 ml-2">Fare (MYR)</label>
                            <input type="number" step="0.01" v-model="form.price" placeholder="0.00" class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500">
                        </div>

                        <button type="submit" class="bg-blue-900 text-white font-black py-4 px-8 rounded-2xl hover:bg-black md:col-span-2 lg:col-span-3 transition-all shadow-xl shadow-blue-100 transform active:scale-95 uppercase tracking-widest text-sm">
                            Publish Voyage to Timetable
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import FerryMap from '@/Components/Map.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// Define Props
const props = defineProps({
    schedules: Array,
    ferries: Array, // For Admin
    ports: Array,
    nearestPorts: {
        type: Array,
        default: () => []
    },
    userLocation: Object,
    filters: Object,
    initialDate: String
});

// Admin Form
const form = useForm({
    ferry_id: '',
    origin_port_id: '',
    destination_port_id: '',
    departure_time: '',
    arrival_time: '',
    price: ''
});

// Local State for Filters
const selectedState = ref(props.filters?.state || ''); 
const selectedDestination = ref(props.filters?.destination_port_id || '');
const selectedTime = ref(props.filters?.time_of_day || '');
const currentDate = ref(props.initialDate || new Date().toISOString().split('T')[0]);
const highlightedRouteId = ref(null);

// State Mapping
const stateMap = {
    'Perlis': ['Kuala Perlis Jetty'],
    'Kedah': ['Kuala Kedah Jetty', 'Kuah Jetty (Langkawi)', 'Tanjung Lembung Port'],
    'Penang': ['Sultan Abdul Halim Ferry Terminal', 'Swettenham Pier', 'Teluk Bahang', 'Teluk Kumbar'],
    'Perak': ['Lumut Jetty', 'Marina Island Jetty', 'Pulau Pangkor Jetty'],
    'Selangor': ['South Port Passenger Terminal', 'Pulau Ketam Jetty'],
    'Melaka': ['Melaka International Ferry Terminal'],
    'Johor': ['Mersing Jetty', 'Jeti Tanjung Emas Muar', 'Puteri Harbour International Ferry Terminal', 'Pasir Gudang Ferry Terminal', 'Kukup International Ferry Terminal'],
    'Pahang': ['Tanjung Gemok Jetty', 'Tioman Island Marina'],
    'Terengganu': ['Shahbandar Jetty', 'Merang Jetty', 'Kuala Besut Jetty', 'Redang Island Jetty', 'Perhentian Island Jetty', 'Marang Jetty', 'Kapas Island Jetty'],
};
const states = Object.keys(stateMap);

// Computed: Active Filters Check
const hasActiveFilters = computed(() => {
    return props.userLocation || selectedState.value || selectedDestination.value || selectedTime.value;
});

const groupedSchedulesCount = computed(() => {
    return Object.values(groupedByOperator.value).reduce((acc, curr) => acc + curr.length, 0);
});

// Filter logic reused for both grouping and map
const filteredSchedules = computed(() => {
    let schedules = props.schedules;
    if (selectedState.value) {
        const allowedPorts = stateMap[selectedState.value] || [];
        schedules = schedules.filter(s => 
            allowedPorts.includes(s.origin.name) || allowedPorts.includes(s.destination.name)
        );
    }
    return schedules;
});

// Group Schedules by "Ferry Operator"
const groupedByOperator = computed(() => {
    const groups = {};
    
    // Group by Operator
    filteredSchedules.value.forEach(schedule => {
        const key = schedule.ferry.operator || schedule.ferry.name;
        if (!groups[key]) {
            groups[key] = [];
        }
        groups[key].push(schedule);
    });

    // Sort trips within each group by date/time
    Object.keys(groups).forEach(key => {
        groups[key].sort((a, b) => new Date(a.departure_time) - new Date(b.departure_time));
    });

    return groups;
});

// Map Data Computeds
const mapMarkers = computed(() => {
    const markersMap = new window.Map();
    
    // If user has a location, add it
    if (props.userLocation) {
        markersMap.set('user', {
            id: 'user',
            lat: props.userLocation.lat,
            lng: props.userLocation.lng,
            title: 'Your Location',
            icon: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png'
        });
    }

    filteredSchedules.value.forEach(trip => {
        // Origin
        if (!markersMap.has(trip.origin.id)) {
            markersMap.set(trip.origin.id, {
                id: trip.origin.id,
                lat: trip.origin.latitude,
                lng: trip.origin.longitude,
                title: trip.origin.name,
                description: 'Departure Port'
            });
        }
        // Destination
        if (!markersMap.has(trip.destination.id)) {
            markersMap.set(trip.destination.id, {
                id: trip.destination.id,
                lat: trip.destination.latitude,
                lng: trip.destination.longitude,
                title: trip.destination.name,
                description: 'Arrival Port'
            });
        }
    });

    return Array.from(markersMap.values());
});

const mapRoutes = computed(() => {
    return filteredSchedules.value.map(trip => ({
        id: trip.id,
        path: [
            { lat: parseFloat(trip.origin.latitude), lng: parseFloat(trip.origin.longitude) },
            { lat: parseFloat(trip.destination.latitude), lng: parseFloat(trip.destination.longitude) }
        ],
        color: '#FF0000' // Default red route
    }));
});

const mapCenter = computed(() => {
    if (props.userLocation) return { lat: parseFloat(props.userLocation.lat), lng: parseFloat(props.userLocation.lng) };
    if (mapMarkers.value.length > 0) {
        return { lat: parseFloat(mapMarkers.value[0].lat), lng: parseFloat(mapMarkers.value[0].lng) };
    }
    return null; // Let Map component handle default
});

const mapZoom = computed(() => {
    return props.userLocation ? 10 : 7;
});

// Real-time Status Logic
const getStatus = (trip) => {
    const now = new Date();
    const departure = new Date(trip.departure_time);
    const arrival = new Date(trip.arrival_time);

    if (now < departure) {
        const diffMinutes = Math.floor((departure - now) / 60000);
        if (diffMinutes < 60) return `Departing in ${diffMinutes}m`;
        return 'Scheduled';
    } else if (now >= departure && now <= arrival) {
        return 'En Route';
    } else {
        return 'Arrived';
    }
};

const getStatusColor = (trip) => {
    const status = getStatus(trip);
    if (status === 'Scheduled') return 'bg-blue-100 text-blue-800';
    if (status.includes('Departing')) return 'bg-yellow-100 text-yellow-800';
    if (status === 'En Route') return 'bg-emerald-100 text-emerald-800 animate-pulse';
    return 'bg-gray-100 text-gray-800';
};


// Core Filtering Function (Calls Backend)
const applyFilters = () => {
    router.get(route('schedules.index'), {
        date: currentDate.value,
        destination_port_id: selectedDestination.value,
        time_of_day: selectedTime.value,
        state: selectedState.value,
        latitude: props.filters?.latitude,   // Preserve location if it was set
        longitude: props.filters?.longitude
    }, {
        preserveScroll: true,
        only: ['schedules', 'nearestPorts', 'userLocation', 'filters', 'initialDate']
    });
};

const changeDate = (days) => {
    const date = new Date(currentDate.value);
    date.setDate(date.getDate() + days);
    currentDate.value = date.toISOString().split('T')[0];
    applyFilters();
};

const clearFilters = () => {
    selectedState.value = '';
    selectedDestination.value = '';
    selectedTime.value = '';
    // Also clear location
    router.get(route('schedules.index'), {
         date: currentDate.value // Keep date? Or reset to today? Keep date seems friendlier.
    }, { preserveScroll: true });
};


const locateUser = () => {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                // When locating, we preserve other filters but update lat/long
                router.get(route('schedules.index'), {
                    ...props.filters,
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude
                }, {
                    preserveScroll: true
                });
            },
            (error) => {
                alert("Error getting location: " + error.message);
            }
        );
    } else {
        alert("Geolocation is not supported by this browser.");
    }
};

const submit = () => {
    form.post(route('schedules.store'), {
        onSuccess: () => form.reset()
    });
};

const formatTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
};

const formatDateFull = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};
</script>

<template>
    <Head title="Ferry Schedules" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold leading-tight text-gray-800">
                Ferry Schedules
            </h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
                
                <!-- Page Title / Info -->
                <div class="text-center mb-8">
                    <p class="text-gray-500 mt-2">Current schedules based on availability.</p>

                    <!-- Filter by State -->
                    <div class="mt-6">
                        <label for="state-filter" class="mr-2 text-gray-700 font-bold">Filter by State:</label>
                        <select id="state-filter" v-model="selectedState" class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">All States</option>
                            <option v-for="state in states" :key="state" :value="state">{{ state }}</option>
                        </select>
                    </div>
                    
                    <!-- Geolocation Status -->
                    <div class="mt-4 flex justify-center items-center space-x-4">
                        <button @click="locateUser" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Find Near Me
                        </button>
                        <button v-if="userLocation" @click="clearLocation" class="text-sm text-gray-600 underline hover:text-gray-800">
                            Show All Routes
                        </button>
                    </div>

                    <div v-if="nearestPort" class="mt-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded inline-block">
                        <span class="font-bold">Nearest Port:</span> {{ nearestPort.name }} ({{ parseFloat(nearestPort.distance).toFixed(1) }} km away)
                    </div>
                </div>

                <!-- Timetable Grid -->
                <div v-if="Object.keys(groupedSchedules).length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div v-for="(routeSchedules, routeKey) in groupedSchedules" :key="routeKey" class="bg-white rounded-xl shadow-lg overflow-hidden flex flex-col h-full border border-gray-100">
                        <!-- Dynamic Header Color based on index or hash could be cool, keeping simple for now -->
                        <div class="bg-gray-800 p-4 text-center">
                            <h3 class="text-white font-bold text-lg leading-tight">{{ routeKey }}</h3>
                        </div>
                        <div class="p-4 flex-grow space-y-3 bg-gray-50/30">
                            <div v-for="trip in routeSchedules" :key="trip.id" class="text-center py-2 border-b border-gray-100 last:border-0 hover:bg-white transition rounded">
                                <div class="text-xl font-bold text-gray-800">
                                    {{ formatTime(trip.departure_time) }}
                                </div>
                                <div class="text-xs text-gray-400 mb-1">
                                    {{ formatDate(trip.departure_time) }}
                                </div>
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mt-1">
                                    {{ trip.ferry.name }}
                                </div>
                                <div class="text-xs text-gray-400">
                                     RM {{ trip.price }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div v-else class="text-center py-12 text-gray-500">
                    <div class="text-xl mb-2">No schedules found for the selected criteria.</div>
                    <p class="text-sm">Try clearing the location filter or checking back later.</p>
                </div>


                <!-- Footer Note -->
                <div class="bg-white p-6 rounded-lg shadow mt-8 text-center border-t-4 border-gray-800">
                    <p class="font-bold text-gray-800 text-lg mb-2">Important Information</p>
                    <p class="text-sm text-gray-600">
                        <span class="font-bold text-red-500">[Time in Red]</span> = Closed / Sold Out &nbsp; | &nbsp; 
                        <span class="font-bold text-gray-800">[*]</span> = Extra Trip
                    </p>
                    <p class="text-xs text-gray-500 mt-2">
                        Ferry schedule is subject to change without prior notice. Please arrive 30 minutes before departure.
                    </p>
                    <a href="https://ticket.langkawiferryline.com" target="_blank" class="block mt-4 text-blue-600 font-bold hover:underline">
                        https://ticket.langkawiferryline.com
                    </a>
                </div>

                <!-- Admin Management (Only Show if Admin) -->
                <div v-if="$page.props.auth.user.is_admin" class="mt-12 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-2 border-dashed border-gray-300">
                    <h2 class="text-xl font-semibold mb-4 text-gray-500">Admin Management: Add New Schedule</h2>
                    <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <select v-model="form.ferry_id" class="p-2 border rounded">
                            <option value="" disabled>Select Ferry</option>
                            <option v-for="ferry in ferries" :value="ferry.id" :key="ferry.id">{{ ferry.name }}</option>
                        </select>

                        <select v-model="form.origin_port_id" class="p-2 border rounded">
                            <option value="" disabled>Origin Port</option>
                            <option v-for="port in ports" :value="port.id" :key="port.id">{{ port.name }}</option>
                        </select>

                        <select v-model="form.destination_port_id" class="p-2 border rounded">
                            <option value="" disabled>Destination Port</option>
                            <option v-for="port in ports" :value="port.id" :key="port.id">{{ port.name }}</option>
                        </select>

                        <input type="datetime-local" v-model="form.departure_time" class="p-2 border rounded">
                        <input type="datetime-local" v-model="form.arrival_time" class="p-2 border rounded">
                        <input type="number" step="0.01" v-model="form.price" placeholder="Price (MYR)" class="p-2 border rounded">

                        <button type="submit" class="bg-gray-800 text-white font-bold py-2 px-4 rounded hover:bg-gray-700 md:col-span-2 lg:col-span-3">
                            Add to Timetable
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    schedules: Array,
    ferries: Array,
    ports: Array,
    nearestPort: Object,
    userLocation: Object
});

const form = useForm({
    ferry_id: '',
    origin_port_id: '',
    destination_port_id: '',
    departure_time: '',
    arrival_time: '',
    price: ''
});

const selectedState = ref('');

// State to Port mapping
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

const submit = () => {
    form.post(route('schedules.store'), {
        onSuccess: () => form.reset()
    });
};

// Locate User Function
const locateUser = () => {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                router.get(route('schedules.index'), {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude
                }, {
                    preserveScroll: true,
                    only: ['schedules', 'nearestPort', 'userLocation']
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

const clearLocation = () => {
    selectedState.value = ''; // Clear state filter too
    router.get(route('schedules.index'), {}, { preserveScroll: true });
};

// Helper: Format date string to just Time (e.g. "07:30 AM")
const formatTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
};

// Helper: Format date string to just Date (e.g. "15 Dec")
const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString([], { day: 'numeric', month: 'short' });
};

// Group Schedules dynamically by "Origin -> Destination"
const groupedSchedules = computed(() => {
    const groups = {};
    
    // 1. Filter by State if selected
    let filteredSchedules = props.schedules;
    
    if (selectedState.value) {
        const allowedPorts = stateMap[selectedState.value] || [];
        filteredSchedules = filteredSchedules.filter(s => 
            allowedPorts.includes(s.origin.name) || allowedPorts.includes(s.destination.name)
        );
    }

    // 2. Group filtered schedules
    filteredSchedules.forEach(schedule => {
        const key = `${schedule.origin.name} to ${schedule.destination.name}`;
        if (!groups[key]) {
            groups[key] = [];
        }
        groups[key].push(schedule);
    });

    // Sort trips within each group by time
    Object.keys(groups).forEach(key => {
        groups[key].sort((a, b) => new Date(a.departure_time) - new Date(b.departure_time));
    });

    return groups;
});

// Watch for nearestPort changes to auto-select state? 
// For now, let's just let the nearestPort output from backend take precedence visually,
// bu allow user to override with the dropdown.
</script>

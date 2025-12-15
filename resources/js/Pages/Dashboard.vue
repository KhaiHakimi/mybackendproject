<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const props = defineProps({
    ports: Array
});

const userLocation = ref(null);
const selectedPort = ref(null);
const map = ref(null);

// ==========================================
// 1. INITIALIZE MAP SYSTEM
// ==========================================
// This function loads the Google Maps API script dynamically if it's not already loaded.
// It ensures we can render the map even if the user navigates away and comes back.
const initMap = () => {
    // Check if we have a saved location in browser storage from a previous session
    // This fixes the "reset" issue where refreshing or returning to the page lost your spot.
    const savedLoc = localStorage.getItem('user_location');
    if (savedLoc) {
        try {
            userLocation.value = JSON.parse(savedLoc);
            // We must recalculate distances immediately so the "Top 3" list appears correct
            calculateDistances();
        } catch (e) {
            console.error("Failed to parse saved location");
        }
    }

    // Load Google Maps Script
    if (!document.getElementById('google-maps-script')) {
        const script = document.createElement('script');
        script.id = 'google-maps-script';
        script.src = `https://maps.googleapis.com/maps/api/js?key=${import.meta.env.VITE_GOOGLE_MAPS_API_KEY}&callback=initMapCallback`;
        script.async = true;
        script.defer = true;
        window.initMapCallback = createMap;
        document.head.appendChild(script);
    } else if (window.google && window.google.maps) {
        createMap();
    }
};

// ==========================================
// 2. CREATE AND RENDER MAP
// ==========================================
// This logic draws the actual map on the screen, places markers, and handles interactions.
const createMap = () => {
    // Default center (Malaysia) if no user location
    const center = userLocation.value || { lat: 4.2105, lng: 101.9758 };

    // Create the map instance attached to the 'map' div
    map.value = new google.maps.Map(document.getElementById("map"), {
        center: center,
        zoom: userLocation.value ? 9 : 6, // Zoom in closer if we know where the user is
    });

    // Add User Marker (Blue Dot) if location is known
    if (userLocation.value) {
        new google.maps.Marker({
            position: userLocation.value,
            map: map.value,
            title: "Your Location",
            icon: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"
        });
    }

    // Add Port Markers (Ferry Terminals)
    const bounds = new google.maps.LatLngBounds();
    if (userLocation.value) bounds.extend(userLocation.value);

    // Identify the "Top 3" nearest ports to highlight them specifically
    const nearestPortsIds = userLocation.value && props.ports.length > 0 
        ? props.ports.slice(0, 3).map(p => p.id) 
        : [];

    // Create a single InfoWindow instance to reuse (saves memory)
    const infoWindow = new google.maps.InfoWindow();

    props.ports.forEach((port, index) => {
        if (port.latitude && port.longitude) {
            
            // Logic: Is this port special?
            const isTop3 = nearestPortsIds.includes(port.id);
            const isNearest = index === 0 && userLocation.value;

            // Create the marker
            const marker = new google.maps.Marker({
                position: { lat: parseFloat(port.latitude), lng: parseFloat(port.longitude) },
                map: map.value,
                title: port.name,
                // Color Logic:
                // Green = The absolute nearest one
                // Orange = Top 3 selection
                // Red = Further away
                icon: isNearest
                    ? "http://maps.google.com/mapfiles/ms/icons/green-dot.png" 
                    : (isTop3 ? "http://maps.google.com/mapfiles/ms/icons/orange-dot.png" : "http://maps.google.com/mapfiles/ms/icons/red-dot.png")
            });
            
            // Ensure the map view includes these important markers
            if (isTop3 && userLocation.value) {
                 bounds.extend(marker.getPosition());
            }

            // INTERACTION: Click marker to "Select" the port (Show Windy forecast)
            marker.addListener("click", () => {
                selectPort(port);
            });

            // INTERACTION: Hover displays name/distance
            marker.addListener("mouseover", () => {
                const contentString = `
                    <div style="padding: 5px; color: #333;">
                        <h3 style="margin: 0; font-weight: bold; font-size: 14px;">${port.name}</h3>
                        <p style="margin: 4px 0 0; font-size: 12px;">${port.location || 'Ferry Terminal'}</p>
                        ${isNearest ? '<span style="color: green; font-weight: bold; font-size: 10px;">NEAREST JETTY</span>' : ''}
                        ${userLocation.value ? `<p style="margin-top: 5px; font-weight: bold;">${port.distance.toFixed(1)} km away</p>` : ''}
                    </div>
                `;
                infoWindow.setContent(contentString);
                infoWindow.open(map.value, marker);
            });

            marker.addListener("mouseout", () => {
                infoWindow.close();
            });
        }
    });

    // Auto-zoom the map to fit the user and the nearest ports
    if (userLocation.value) {
        map.value.fitBounds(bounds);
    }
};

// Hook: Run this when component mounts (page loads)
onMounted(() => {
    initMap(); 
});

// ==========================================
// 3. GET USER GEOLOCATION
// ==========================================
// Uses the browser's built-in GPS/Location API
const getUserLocation = () => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                // Success: Update state
                userLocation.value = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                
                // Save to local storage so it persists if page refreshes
                localStorage.setItem('user_location', JSON.stringify(userLocation.value));

                // Recalculate distances to all ports
                calculateDistances();
                // Redraw map with new data
                createMap();
            },
            (error) => {
                alert('Error getting location: ' + error.message);
            }
        );
    } else {
        alert('Geolocation is not supported by this browser.');
    }
};

// ==========================================
// 4. DISTANCE CALCULATION
// ==========================================
// Computes distance from User to Each Port using simple math
const calculateDistances = () => {
    if (!userLocation.value) return;

    props.ports.forEach(port => {
        if (port.latitude && port.longitude) {
            // Calculate using Haversine algorithm
            port.distance = getDistanceFromLatLonInKm(
                userLocation.value.lat, userLocation.value.lng,
                port.latitude, port.longitude
            );
        }
    });
    
    // Sort the ports list: Closest first (index 0)
    props.ports.sort((a, b) => (a.distance || 9999) - (b.distance || 9999));
};

// ==========================================
// 5. PORT SELECTION
// ==========================================
// Triggers when user clicks a list item or map marker
const selectPort = (port) => {
    selectedPort.value = port; // Sets the "Active" port to show Windy forecast
    
    // Smooth scroll to the top so the user sees the forecast immediately
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

// Helper: Haversine Formula (Mathematics for Earth Distance)
function getDistanceFromLatLonInKm(lat1, lon1, lat2, lon2) {
  var R = 6371; // Radius of the earth in km
  var dLat = deg2rad(lat2-lat1); 
  var dLon = deg2rad(lon2-lon1); 
  var a = 
    Math.sin(dLat/2) * Math.sin(dLat/2) +
    Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
    Math.sin(dLon/2) * Math.sin(dLon/2)
    ; 
  var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
  var d = R * c; // Distance in km
  return d;
}

function deg2rad(deg) {
  return deg * (Math.PI/180)
}
// End Helper
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <section class="mb-8">
                            <h3 class="text-xl font-bold mb-4">Nearby Ferry Stations & Weather</h3>
                            <div class="bg-gray-200 h-96 rounded-lg relative flex items-center justify-center text-gray-500 overflow-hidden">
                                <!-- Placeholder for Google Maps -->
                                <div id="map" class="w-full h-full bg-gray-300"></div>
                                <div v-if="!userLocation" class="absolute inset-0 bg-black/50 flex items-center justify-center text-white z-10">
                                    <button @click="getUserLocation" class="bg-blue-600 px-4 py-2 rounded shadow hover:bg-blue-700">Enable Location Services</button>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">Map will show your location and nearby ferry ports. Select a port to see Windy forecast.</p>
                        </section>

                        <div v-if="selectedPort" class="mt-8 border-t pt-8">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-xl font-bold">Weather Forecast: {{ selectedPort.name }}</h3>
                                <button @click="selectedPort = null" class="text-blue-500 underline text-sm">Close Forecast</button>
                            </div>
                            
                            <!-- Windy Embed -->
                            <div class="w-full h-[500px] rounded-lg overflow-hidden border">
                                <iframe 
                                    width="100%" 
                                    height="100%" 
                                    :src="`https://embed.windy.com/embed2.html?lat=${selectedPort.latitude}&lon=${selectedPort.longitude}&detailLat=${selectedPort.latitude}&detailLon=${selectedPort.longitude}&width=650&height=450&zoom=10&level=surface&overlay=wind&product=ecmwf&menu=&message=true&marker=true&calendar=now&pressure=&type=map&location=coordinates&detail=true&metricWind=default&metricTemp=default&radarRange=-1`" 
                                    frameborder="0"
                                ></iframe>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <a :href="route('weather.show', selectedPort.id)" class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700">View Detailed AI Risk Analysis</a>
                            </div>
                        </div>

                        <section class="mt-8">
                            <h3 class="text-xl font-bold mb-4">Ferry Service Finder</h3>
                            
                            <!-- If Location is enabled, show TOP 3 NEAREST JETTIES -->
                            <div v-if="userLocation && ports.length > 0">
                                <h4 class="text-lg font-semibold text-gray-700 mb-4">Top 3 Nearest Terminals</h4>
                                <div class="space-y-4">
                                    <div 
                                        v-for="(port, index) in ports.slice(0, 3)" 
                                        :key="port.id"
                                        class="bg-white border-2 p-6 rounded-xl shadow-md flex flex-col md:flex-row justify-between items-center transition transform hover:scale-[1.01] cursor-pointer"
                                        :class="{
                                            'border-green-500 bg-green-50': index === 0,
                                            'border-orange-300': index > 0
                                        }"
                                        @click="selectPort(port)"
                                    >
                                        <div>
                                            <div class="flex items-center gap-2 mb-2">
                                                <span v-if="index === 0" class="bg-green-600 text-white text-xs font-bold px-2 py-1 rounded uppercase">Nearest</span>
                                                <span v-else class="bg-orange-500 text-white text-xs font-bold px-2 py-1 rounded uppercase">Near you</span>
                                                
                                                <h4 class="text-xl font-bold text-gray-800">{{ port.name }}</h4>
                                            </div>
                                            <p class="text-gray-600 font-medium">{{ port.location || 'Ferry Terminal' }}</p>
                                            <div class="flex items-center gap-2 mt-2 font-bold text-lg" :class="index === 0 ? 'text-green-700' : 'text-orange-600'">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                {{ port.distance.toFixed(2) }} km away
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4 md:mt-0 text-center">
                                            <button 
                                                type="button"
                                                class="font-bold py-2 px-6 rounded-lg shadow-sm transition"
                                                :class="index === 0 ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                                            >
                                                View Forecast
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-6 text-center">
                                    <p class="text-gray-500 text-sm">Showing the 3 closest jetties to your location. <button @click="userLocation = null" class="text-blue-500 underline">Show All Jetties</button></p>
                                </div>
                            </div>
                            <!-- If Location is disabled, show ALL JETTIES GRID -->
                            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div v-for="port in ports" :key="port.id" 
                                    @click="selectPort(port)"
                                    class="border p-4 rounded hover:bg-gray-50 cursor-pointer transition"
                                    :class="{'border-blue-500 ring-2 ring-blue-200': selectedPort?.id === port.id}"
                                >
                                    <h4 class="font-bold">{{ port.name }}</h4>
                                    <div class="text-sm text-gray-500" v-if="userLocation && port.distance">
                                        {{ port.distance.toFixed(1) }} km away
                                    </div>
                                    <button class="mt-2 text-blue-600 text-sm font-semibold">View Forecast & Services</button>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

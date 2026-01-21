<script setup>
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
    import Map from '@/Components/Map.vue'
    import { Head, router } from '@inertiajs/vue3'
    import { ref, onMounted, computed, watch } from 'vue'

    const props = defineProps({
        ports: Array,
        adminStats: Object, // Optional prop for admin statistics
        adminStats: Object, // Optional prop for admin statistics
        initialSystemLogs: Array, // Renamed to avoid confusion, though passing empty array is fine
    })

    const systemLogs = ref(props.initialSystemLogs || [])
    const allPorts = ref([...props.ports])

    watch(
        () => props.ports,
        (newPorts) => {
            allPorts.value = [...newPorts]
            calculateDistances()
        },
    )

    const userLocation = ref(null)
    const selectedOrigin = ref(null) // If set, this overrides userLocation for distance calcs
    const selectedPort = ref(null)

    const currentOrigin = computed(() => {
        if (selectedOrigin.value) return selectedOrigin.value
        return userLocation.value
    })

    // ==========================================
    // 1. INITIALIZE & RESTORE STATE
    // ==========================================
    onMounted(() => {
        // Check if we have a saved location in browser storage from a previous session
        const savedLoc = localStorage.getItem('user_location')
        if (savedLoc) {
            try {
                userLocation.value = JSON.parse(savedLoc)
                // We must recalculate distances immediately so the "Top 3" list appears correct
                calculateDistances()
            } catch (e) {
                console.error('Failed to parse saved location')
            }
        }
        // Fetch System Logs asynchronously for Admins
        if (props.adminStats) {
            window.axios
                .get(route('dashboard.logs'))
                .then((res) => {
                    systemLogs.value = res.data
                })
                .catch((err) => console.error('Failed to fetch logs', err))
        }

        // Automatically fetch latest weather and map data from the integrated APIs
        // Optimize: Only refresh if we haven't refreshed recently (e.g. last 5 minutes)
        const lastRefresh = localStorage.getItem('last_weather_refresh')
        const now = Date.now()
        const fiveMinutes = 5 * 60 * 1000

        if (
            window.axios &&
            (!lastRefresh || now - parseInt(lastRefresh) > fiveMinutes)
        ) {
            console.log('Fetching fresh weather data...')
            window.axios
                .post(route('weather.refresh_all'))
                .then((res) => {
                    console.log('Auto-fetch complete:', res.data.message)
                    localStorage.setItem('last_weather_refresh', now.toString())
                })
                .catch((err) => console.error('Auto-fetch failed:', err))
        } else {
            console.log('Skipping weather refresh (cached).')
        }
    })

    // ==========================================
    // 2. MAP DATA PREPARATION
    // ==========================================
    const mapMarkers = computed(() => {
        const markers = []

        // User Marker
        if (userLocation.value) {
            markers.push({
                id: 'user',
                lat: userLocation.value.lat,
                lng: userLocation.value.lng,
                title: 'Your Location',
                icon: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png',
            })
        }

        // Port Markers
        // Identify the "Top 3" nearest ports to highlight them specifically
        const nearestPortsIds =
            currentOrigin.value && peninsularPorts.value.length > 0
                ? peninsularPorts.value.slice(0, 3).map((p) => p.id)
                : []

        // Actually, markers depend on distance which is in allPorts.
        allPorts.value.forEach((port, index) => {
            if (port.latitude && port.longitude) {
                const isTop3 = nearestPortsIds.includes(port.id)
                // The nearest is the FIRST one in the filtered list
                const isNearest =
                    currentOrigin.value &&
                    peninsularPorts.value.length > 0 &&
                    peninsularPorts.value[0].id === port.id
                const isSelectedOrigin =
                    selectedOrigin.value && port.id === selectedOrigin.value.id

                markers.push({
                    id: port.id,
                    lat: port.latitude,
                    lng: port.longitude,
                    title: port.name,
                    description: `${port.location || 'Ferry Terminal'} ${currentOrigin.value ? '<br/>' + (port.distance ? port.distance.toFixed(1) : '') + ' km away' : ''}`,
                    icon: isSelectedOrigin
                        ? 'http://maps.google.com/mapfiles/ms/icons/purple-dot.png'
                        : isNearest
                          ? 'http://maps.google.com/mapfiles/ms/icons/green-dot.png'
                          : isTop3
                            ? 'http://maps.google.com/mapfiles/ms/icons/orange-dot.png'
                            : 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
                })
            }
        })

        return markers
    })

    const mapCenter = computed(() => {
        return currentOrigin.value || { lat: 4.2105, lng: 101.9758 }
    })

    const mapZoom = computed(() => {
        return currentOrigin.value ? 9 : 6
    })

    const mapRoutes = computed(() => {
        if (!currentOrigin.value || peninsularPorts.value.length === 0)
            return []

        // The ports are sorted by distance in calculateDistances(), so the first one is the nearest.
        const nearestPort = peninsularPorts.value[0]

        if (!nearestPort || !nearestPort.latitude || !nearestPort.longitude)
            return []

        return [
            {
                id: 'nearest-route',
                path: [
                    {
                        lat: currentOrigin.value.lat,
                        lng: currentOrigin.value.lng,
                    },
                    {
                        lat: parseFloat(nearestPort.latitude),
                        lng: parseFloat(nearestPort.longitude),
                    },
                ],
                color: '#10B981', // Emerald-500
                weight: 4,
            },
        ]
    })

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
                        lng: position.coords.longitude,
                    }

                    // Save to local storage so it persists if page refreshes
                    localStorage.setItem(
                        'user_location',
                        JSON.stringify(userLocation.value),
                    )

                    // Recalculate distances to all ports
                    calculateDistances()
                },
                (error) => {
                    alert('Error getting location: ' + error.message)
                },
            )
        } else {
            alert('Geolocation is not supported by this browser.')
        }
    }

    // ==========================================
    // 4. DISTANCE CALCULATION
    // ==========================================
    // Computes distance from User to Each Port using simple math
    const calculateDistances = () => {
        const origin = currentOrigin.value
        if (!origin) return

        allPorts.value.forEach((port) => {
            if (port.latitude && port.longitude) {
                // Calculate using Haversine algorithm
                port.distance = getDistanceFromLatLonInKm(
                    origin.lat,
                    origin.lng,
                    port.latitude,
                    port.longitude,
                )
            }
        })

        // Sort the ports list: Closest first (index 0)
        allPorts.value = [...allPorts.value].sort(
            (a, b) => (a.distance || 9999) - (b.distance || 9999),
        )
    }

    // ==========================================
    // 5. PENINSULAR FILTER (Computed)
    // ==========================================
    const peninsularPorts = computed(() => {
        return allPorts.value.filter((port) => {
            const loc = (port.location || '').toLowerCase()
            const isInternational =
                loc.includes('indonesia') || loc.includes('singapore')
            const isEastMalaysia = port.longitude && port.longitude > 109.0

            return !isInternational && !isEastMalaysia
        })
    })

    const displayPorts = computed(() => {
        if (!selectedOrigin.value) {
            return peninsularPorts.value.slice(0, 3)
        }

        const selected = allPorts.value.find(
            (p) => p.id === selectedOrigin.value.id,
        )
        if (!selected) {
            return peninsularPorts.value.slice(0, 3)
        }

        // The first port in peninsularPorts will be the selected one, so we skip it and take the next two.
        const nearestTwo = peninsularPorts.value
            .filter((p) => p.id !== selected.id)
            .slice(0, 2)

        return [selected, ...nearestTwo]
    })

    // ==========================================
    // 6. PORT SELECTION
    // ==========================================
    // Triggers when user clicks a list item or map marker
    const selectPort = (port) => {
        selectedPort.value = port // Sets the "Active" port to show Windy forecast

        // Smooth scroll to the top so the user sees the forecast immediately
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }

    const handleMapMarkerClick = (markerData) => {
        console.log('Marker clicked:', markerData)
        if (markerData.id === 'user') {
            selectedOrigin.value = null // Revert to user
        } else {
            // Find the port
            const port = allPorts.value.find((p) => p.id === markerData.id)
            if (port) {
                // Set this port as the new origin
                selectedOrigin.value = {
                    lat: parseFloat(port.latitude),
                    lng: parseFloat(port.longitude),
                    name: port.name,
                    type: 'port',
                    id: port.id,
                }
            }
        }
        // Re-calculate distances based on new origin
        calculateDistances()
    }

    // Helper: Haversine Formula (Mathematics for Earth Distance)
    function getDistanceFromLatLonInKm(lat1, lon1, lat2, lon2) {
        var R = 6371 // Radius of the earth in km
        var dLat = deg2rad(lat2 - lat1)
        var dLon = deg2rad(lon2 - lon1)
        var a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(deg2rad(lat1)) *
                Math.cos(deg2rad(lat2)) *
                Math.sin(dLon / 2) *
                Math.sin(dLon / 2)
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
        var d = R * c // Distance in km
        return d
    }

    function deg2rad(deg) {
        return deg * (Math.PI / 180)
    }
    // End Helper
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-extrabold leading-tight text-blue-900">
                Marine Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
                <!-- Telegram Activation Banner (If Not Linked) -->
                <div
                    v-if="
                        !$page.props.isTelegramLinked &&
                        $page.props.telegramCode
                    "
                    class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl shadow-xl p-6 text-white relative overflow-hidden animate-fade-in-down"
                >
                    <div
                        class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6"
                    >
                        <div>
                            <h3
                                class="text-2xl font-black uppercase tracking-wide flex items-center gap-2"
                            >
                                <svg
                                    class="w-8 h-8"
                                    fill="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"
                                    />
                                </svg>
                                Enable Risk Alerts
                            </h3>
                            <p class="text-blue-100 font-medium mt-1">
                                Connect your Telegram account to receive instant
                                High Risk warnings for your registered number.
                            </p>
                        </div>
                        <a
                            :href="`https://t.me/${$page.props.telegramBotName}?start=${$page.props.telegramCode}`"
                            target="_blank"
                            class="bg-white text-blue-600 font-black px-8 py-3 rounded-full hover:bg-blue-50 transition transform hover:scale-105 shadow-lg whitespace-nowrap"
                        >
                            Activate Now
                        </a>
                    </div>
                    <!-- Background Pattern -->
                    <svg
                        class="absolute top-0 right-0 h-full w-auto opacity-10"
                        fill="currentColor"
                        viewBox="0 0 100 100"
                    >
                        <circle cx="50" cy="50" r="50" />
                    </svg>
                </div>

                <!-- Admin Stats Widget (Only visible to Admins) -->
                <div
                    v-if="adminStats"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 animate-fade-in-down"
                >
                    <div
                        class="bg-blue-900 text-white p-6 rounded-2xl shadow-lg relative overflow-hidden"
                    >
                        <div class="relative z-10">
                            <div
                                class="text-[10px] font-black uppercase tracking-widest text-blue-300 mb-1"
                            >
                                Registered Users
                            </div>
                            <div class="text-3xl sm:text-4xl font-black">
                                {{ adminStats.total_users }}
                            </div>
                        </div>
                        <svg
                            class="absolute -right-2 -bottom-4 w-24 h-24 text-white/10"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"
                            ></path>
                        </svg>
                    </div>
                    <div
                        class="bg-blue-600 text-white p-6 rounded-2xl shadow-lg relative overflow-hidden"
                    >
                        <div class="relative z-10">
                            <div
                                class="text-[10px] font-black uppercase tracking-widest text-blue-200 mb-1"
                            >
                                Active Vessels
                            </div>
                            <div class="text-3xl sm:text-4xl font-black">
                                {{ adminStats.total_ferries }}
                            </div>
                        </div>
                        <svg
                            class="absolute -right-2 -bottom-4 w-24 h-24 text-white/10"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"
                            ></path>
                        </svg>
                    </div>
                    <div
                        class="bg-white p-6 rounded-2xl shadow-lg border border-blue-100 relative overflow-hidden"
                    >
                        <div class="relative z-10">
                            <div
                                class="text-[10px] font-black uppercase tracking-widest text-blue-400 mb-1"
                            >
                                Voyage Schedules
                            </div>
                            <div
                                class="text-3xl sm:text-4xl font-black text-blue-900"
                            >
                                {{ adminStats.total_schedules }}
                            </div>
                            <div
                                class="text-xs font-bold text-emerald-600 mt-1 flex items-center"
                            >
                                <span
                                    class="w-2 h-2 bg-emerald-500 rounded-full mr-1"
                                ></span>
                                {{ adminStats.active_voyages }} Active Now
                            </div>
                        </div>
                    </div>
                    <div
                        class="bg-white p-6 rounded-2xl shadow-lg border border-blue-100 relative overflow-hidden"
                    >
                        <div class="relative z-10">
                            <div
                                class="text-[10px] font-black uppercase tracking-widest text-blue-400 mb-1"
                            >
                                Total Reviews
                            </div>
                            <div
                                class="text-3xl sm:text-4xl font-black text-blue-900"
                            >
                                {{ adminStats.total_reviews }}
                            </div>
                        </div>
                        <svg
                            class="absolute -right-2 -bottom-4 w-24 h-24 text-blue-50"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            ></path>
                        </svg>
                    </div>
                </div>

                <!-- Admin Logs (Only visible to Admins) -->
                <div
                    v-if="adminStats && systemLogs.length > 0"
                    class="overflow-hidden bg-white shadow-xl sm:rounded-2xl border border-blue-100"
                >
                    <div class="p-8">
                        <h3
                            class="text-2xl font-bold text-blue-900 mb-6 flex items-center"
                        >
                            <svg
                                class="w-6 h-6 mr-2 text-rose-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l4 4a1 1 0 01.586 1.414V19a2 2 0 01-2 2z"
                                ></path>
                            </svg>
                            System Health Logs
                        </h3>
                        <div
                            class="bg-slate-900 rounded-xl p-6 h-64 overflow-y-auto font-mono text-xs shadow-inner custom-scrollbar"
                        >
                            <div
                                v-for="(log, index) in systemLogs"
                                :key="index"
                                class="mb-2 border-b border-slate-800 pb-2 last:border-0"
                            >
                                <div class="flex items-start gap-3">
                                    <span
                                        class="text-slate-500 whitespace-nowrap"
                                        >[{{ log.date }}]</span
                                    >
                                    <span
                                        class="font-bold uppercase tracking-wider px-2 py-0.5 rounded text-[10px]"
                                        :class="{
                                            'bg-rose-500/20 text-rose-400':
                                                log.level === 'ERROR',
                                            'bg-yellow-500/20 text-yellow-400':
                                                log.level === 'WARNING',
                                            'bg-blue-500/20 text-blue-400':
                                                log.level === 'INFO',
                                            'bg-gray-700 text-gray-300': ![
                                                'ERROR',
                                                'WARNING',
                                                'INFO',
                                            ].includes(log.level),
                                        }"
                                    >
                                        {{ log.level }}
                                    </span>
                                    <span class="text-slate-300 break-all">{{
                                        log.message
                                    }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map Section -->
                <div
                    class="overflow-hidden bg-white shadow-xl sm:rounded-2xl border border-blue-100"
                >
                    <div class="p-8">
                        <section class="mb-2">
                            <div class="flex items-center justify-between mb-6">
                                <h3
                                    class="text-2xl font-bold text-blue-900 flex items-center"
                                >
                                    <svg
                                        class="w-6 h-6 mr-2 text-blue-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"
                                        ></path>
                                    </svg>
                                    Interactive Route Map
                                </h3>
                                <button
                                    v-if="!userLocation"
                                    @click="getUserLocation"
                                    class="bg-blue-600 px-6 py-2 rounded-full text-white font-bold shadow-lg hover:bg-blue-700 transition transform hover:scale-105"
                                >
                                    Detect My Location
                                </button>
                            </div>

                            <div
                                class="bg-gray-100 h-[300px] sm:h-[450px] rounded-xl relative overflow-hidden border border-blue-50 shadow-inner"
                            >
                                <Map
                                    :markers="mapMarkers"
                                    :routes="mapRoutes"
                                    :center="mapCenter"
                                    :zoom="mapZoom"
                                    @marker-click="handleMapMarkerClick"
                                />
                            </div>
                            <p class="text-sm text-blue-600/60 mt-4 italic">
                                * Blue dot represents your location. Click on
                                any anchor icon to view real-time weather and
                                find nearest jetties to it.
                            </p>
                        </section>
                    </div>
                </div>

                <!-- Weather Forecast Section (Conditional) -->
                <div v-if="selectedPort" class="animate-fade-in-up">
                    <div
                        class="overflow-hidden bg-white shadow-xl sm:rounded-2xl border-l-8 border-blue-600"
                    >
                        <div class="p-8">
                            <div class="flex justify-between items-start mb-6">
                                <div>
                                    <h3
                                        class="text-2xl font-bold text-blue-900"
                                    >
                                        {{ selectedPort.name }}
                                    </h3>
                                    <p class="text-blue-600">
                                        {{ selectedPort.location }}
                                    </p>
                                </div>
                                <button
                                    @click="selectedPort = null"
                                    class="text-gray-400 hover:text-gray-600"
                                >
                                    <svg
                                        class="w-6 h-6"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        ></path>
                                    </svg>
                                </button>
                            </div>

                            <div
                                class="w-full h-[500px] rounded-xl overflow-hidden border border-blue-100 shadow-lg"
                            >
                                <iframe
                                    width="100%"
                                    height="100%"
                                    :src="`https://embed.windy.com/embed2.html?lat=${selectedPort.latitude}&lon=${selectedPort.longitude}&detailLat=${selectedPort.latitude}&detailLon=${selectedPort.longitude}&width=650&height=450&zoom=10&level=surface&overlay=wind&product=ecmwf&menu=&message=true&marker=true&calendar=now&pressure=&type=map&location=coordinates&detail=true&metricWind=default&metricTemp=default&radarRange=-1`"
                                    frameborder="0"
                                ></iframe>
                            </div>
                            <div class="mt-6 flex justify-end">
                                <a
                                    :href="
                                        route('weather.show', selectedPort.id)
                                    "
                                    class="bg-indigo-600 text-white px-8 py-3 rounded-full font-bold shadow-xl hover:bg-indigo-700 transition transform hover:scale-105"
                                >
                                    Analyze with FerryCast AI
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Port Finder Section -->
                <div
                    class="overflow-hidden bg-white shadow-xl sm:rounded-2xl border border-blue-100"
                >
                    <div class="p-8">
                        <h3
                            class="text-2xl font-bold text-blue-900 mb-8 flex items-center"
                        >
                            <svg
                                class="w-6 h-6 mr-2 text-blue-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                ></path>
                            </svg>
                            Ferry Service Finder
                            <span
                                v-if="selectedOrigin"
                                class="text-sm text-blue-500 ml-2"
                                >(Near {{ selectedOrigin.name }})</span
                            >
                        </h3>

                        <div v-if="selectedOrigin" class="mb-4">
                            <button
                                @click="handleMapMarkerClick({ id: 'user' })"
                                class="text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-lg inline-flex items-center"
                            >
                                <svg
                                    class="w-4 h-4 mr-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"
                                    />
                                </svg>
                                Reset to My Location
                            </button>
                        </div>

                        <!-- Top 3 Nearest -->
                        <div
                            v-if="currentOrigin && peninsularPorts.length > 0"
                            class="space-y-6"
                        >
                            <div
                                v-for="(port, index) in displayPorts"
                                :key="port.id"
                                class="group bg-white border-2 p-6 rounded-2xl flex flex-col md:flex-row justify-between items-center transition-all duration-300 hover:shadow-2xl cursor-pointer overflow-hidden relative"
                                :class="{
                                    'border-emerald-500 bg-emerald-50/30':
                                        index === 0,
                                    'border-blue-100 hover:border-blue-300':
                                        index > 0,
                                }"
                                @click="selectPort(port)"
                            >
                                <div class="relative z-10">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span
                                            v-if="index === 0"
                                            class="bg-emerald-600 text-white text-[10px] font-black px-2 py-1 rounded-md uppercase tracking-wider"
                                            >{{
                                                selectedOrigin
                                                    ? 'Selected Location'
                                                    : 'Closest'
                                            }}</span
                                        >
                                        <span
                                            v-else
                                            class="bg-blue-500 text-white text-[10px] font-black px-2 py-1 rounded-md uppercase tracking-wider"
                                            >Recommended</span
                                        >

                                        <h4
                                            class="text-xl font-bold text-blue-900"
                                        >
                                            {{ port.name }}
                                        </h4>
                                    </div>
                                    <p
                                        class="text-blue-800/60 font-medium mb-3"
                                    >
                                        {{ port.location || 'Ferry Terminal' }}
                                    </p>
                                    <div
                                        class="flex items-center gap-2 font-black text-2xl"
                                        :class="
                                            index === 0
                                                ? 'text-emerald-700'
                                                : 'text-blue-700'
                                        "
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-6 w-6"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                        </svg>
                                        {{ port.distance.toFixed(2) }}
                                        <span
                                            class="text-sm font-bold uppercase ml-1"
                                            >km away</span
                                        >
                                    </div>
                                </div>

                                <div
                                    class="mt-6 md:mt-0 relative z-10 w-full md:w-auto"
                                >
                                    <button
                                        type="button"
                                        class="w-full md:w-auto font-bold py-3 px-8 rounded-xl shadow-md transition transform group-hover:scale-105"
                                        :class="
                                            index === 0
                                                ? 'bg-emerald-600 text-white hover:bg-emerald-700'
                                                : 'bg-blue-600 text-white hover:bg-blue-700'
                                        "
                                    >
                                        Inspect Forecast
                                    </button>
                                </div>

                                <!-- Background Decorative Ship (Subtle) -->
                                <svg
                                    class="absolute -right-4 -bottom-4 w-32 h-32 text-black/5 pointer-events-none transform rotate-12"
                                    fill="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M3 14c0 1.3.8 2.4 2 2.8V20h14v-3.2c1.2-.4 2-1.5 2-2.8 0-1.7-1.3-3-3-3H6c-1.7 0-3 1.3-3 3zm2-1h14c.6 0 1 .4 1 1s-.4 1-1 1H5c-.6 0-1-.4-1-1s.4-1 1-1zM12 2L8.5 8h7L12 2zM6 9h12v2H6V9z"
                                    />
                                </svg>
                            </div>

                            <div class="pt-4 text-center">
                                <button
                                    @click="userLocation = null"
                                    class="text-blue-600 font-bold hover:underline flex items-center justify-center mx-auto"
                                >
                                    <svg
                                        class="w-4 h-4 mr-1"
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
                                    View All Terminals
                                </button>
                            </div>
                        </div>

                        <!-- All Jetties Grid -->
                        <div
                            v-else
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                        >
                            <div
                                v-for="port in allPorts"
                                :key="port.id"
                                @click="selectPort(port)"
                                class="group bg-white border border-blue-50 p-6 rounded-2xl hover:shadow-xl hover:border-blue-300 cursor-pointer transition-all duration-300 relative overflow-hidden"
                                :class="{
                                    'ring-2 ring-blue-500 border-blue-500':
                                        selectedPort?.id === port.id,
                                }"
                            >
                                <h4
                                    class="text-lg font-bold text-blue-900 mb-1 group-hover:text-blue-600 transition-colors"
                                >
                                    {{ port.name }}
                                </h4>
                                <div
                                    class="text-sm text-blue-800/50 mb-4"
                                    v-if="currentOrigin && port.distance"
                                >
                                    <span class="font-bold text-blue-700"
                                        >{{ port.distance.toFixed(1) }} km</span
                                    >
                                    from
                                    {{
                                        selectedOrigin
                                            ? selectedOrigin.name
                                            : 'you'
                                    }}
                                </div>
                                <div
                                    class="text-blue-600 text-xs font-black uppercase tracking-widest flex items-center"
                                >
                                    View Live Details
                                    <svg
                                        class="w-3 h-3 ml-1 transform group-hover:translate-x-1 transition-transform"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 5l7 7-7 7"
                                        ></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    markers: {
        type: Array,
        default: () => []
    },
    routes: {
        type: Array, // Array of { id, path: [{lat, lng}, {lat, lng}], color, weight }
        default: () => []
    },
    highlightedRouteId: {
        type: [String, Number],
        default: null
    },
    center: {
        type: Object, // { lat, lng }
        default: null
    },
    zoom: {
        type: Number,
        default: 7
    }
});

const mapContainer = ref(null);
const map = ref(null);
const googleMarkers = ref([]);
const googlePolylines = ref([]);

const initMap = () => {
    // Check if script is already loaded
    if (!window.google || !window.google.maps) {
        if (!document.getElementById('google-maps-script')) {
            const script = document.createElement('script');
            script.id = 'google-maps-script';
            script.src = `https://maps.googleapis.com/maps/api/js?key=${import.meta.env.VITE_GOOGLE_MAPS_API_KEY}&callback=initMapCallback`;
            script.async = true;
            script.defer = true;
            window.initMapCallback = () => {
                createMap();
            };
            document.head.appendChild(script);
        } else {
            // Script exists but maybe not fully loaded? Wait for callback or check interval
             const checkInterval = setInterval(() => {
                if (window.google && window.google.maps) {
                    clearInterval(checkInterval);
                    createMap();
                }
            }, 100);
        }
    } else {
        createMap();
    }
};

const createMap = () => {
    if (!mapContainer.value) return;

    const defaultCenter = { lat: 4.2105, lng: 101.9758 }; // Malaysia center

    map.value = new google.maps.Map(mapContainer.value, {
        center: props.center || defaultCenter,
        zoom: props.zoom,
        styles: [
            {
                featureType: "poi",
                elementType: "labels",
                stylers: [{ visibility: "off" }],
            },
            {
                featureType: "water",
                elementType: "geometry",
                stylers: [{ color: "#e9e9f2" }]
            }
        ]
    });

    renderMarkers();
    renderRoutes();
};

const renderMarkers = () => {
    if (!map.value) return;

    // Clear existing
    googleMarkers.value.forEach(m => m.setMap(null));
    googleMarkers.value = [];

    const bounds = new google.maps.LatLngBounds();

    props.markers.forEach(markerData => {
        const marker = new google.maps.Marker({
            position: { lat: parseFloat(markerData.lat), lng: parseFloat(markerData.lng) },
            map: map.value,
            title: markerData.title,
            icon: markerData.icon || "http://maps.google.com/mapfiles/ms/icons/red-dot.png"
        });

        const infoWindow = new google.maps.InfoWindow({
            content: `<div class="p-2"><strong>${markerData.title}</strong><br/>${markerData.description || ''}</div>`
        });

        marker.addListener("click", () => {
            infoWindow.open(map.value, marker);
        });

        googleMarkers.value.push(marker);
        bounds.extend(marker.getPosition());
    });

    if (props.markers.length > 0 && !props.center) {
        map.value.fitBounds(bounds);
        // Avoid zooming in too far if only one marker
        const listener = google.maps.event.addListener(map.value, "idle", () => { 
            if (map.value.getZoom() > 12) map.value.setZoom(12); 
            google.maps.event.removeListener(listener); 
        });
    } else if (props.center) {
        map.value.setCenter(props.center);
        map.value.setZoom(props.zoom);
    }
};

const renderRoutes = () => {
    if (!map.value) return;

    // Clear existing
    googlePolylines.value.forEach(p => p.setMap(null));
    googlePolylines.value = [];

    props.routes.forEach(routeData => {
        const polyline = new google.maps.Polyline({
            path: routeData.path,
            geodesic: true,
            strokeColor: routeData.color || '#FF0000',
            strokeOpacity: routeData.id === props.highlightedRouteId ? 1.0 : 0.4,
            strokeWeight: routeData.id === props.highlightedRouteId ? 4 : 2,
            map: map.value,
            zIndex: routeData.id === props.highlightedRouteId ? 10 : 1
        });

        googlePolylines.value.push(polyline);
    });
};

watch(() => props.markers, renderMarkers, { deep: true });
watch(() => props.routes, renderRoutes, { deep: true });
watch(() => props.center, () => {
    if(map.value && props.center) {
        map.value.panTo(props.center);
        map.value.setZoom(props.zoom);
    }
});
watch(() => props.highlightedRouteId, renderRoutes);

onMounted(() => {
    initMap();
});
</script>

<template>
    <div ref="mapContainer" class="w-full h-full rounded-xl overflow-hidden shadow-sm bg-gray-100"></div>
</template>

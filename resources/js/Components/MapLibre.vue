<script setup>
import { onMounted, ref, markRaw, watch } from 'vue';
import maplibregl from 'maplibre-gl';
import { Layer, ImageSource } from '@sakitam-gis/maplibre-wind';

const props = defineProps({
    markers: { type: Array, default: () => [] },
    routes: { type: Array, default: () => [] },
    weatherOverlay: { type: String, default: null } // 'waves', 'wind'
});

const mapContainer = ref(null);
const map = ref(null);
// We need to keep a reference to the library class for Markers/Popups


onMounted(() => {
    // Dynamic Import
    

    // 1. Initialize MapLibre
    // We use markRaw to prevent Vue from making the map reactive (performance killer)
    map.value = markRaw(new maplibregl.Map({
        container: mapContainer.value,
        // Use a free, open-source style (OSM Vector Tiles or Raster)
        // Here we use a standard Raster style for simplicity and zero-config
        style: {
            'version': 8,
            'sources': {
                'osm': {
                    'type': 'raster',
                    'tiles': ['https://a.tile.openstreetmap.org/{z}/{x}/{y}.png'],
                    'tileSize': 256,
                    'attribution': '&copy; OpenStreetMap Contributors',
                    'maxzoom': 19
                }
            },
            'layers': [
                {
                    'id': 'osm-tiles',
                    'type': 'raster',
                    'source': 'osm',
                    'minzoom': 0,
                    'maxzoom': 19
                }
            ]
        },
        center: [101.693207, 3.140853], // KL Coordinates
        zoom: 6
    }));

    // 2. Add Controls
    map.value.addControl(new maplibregl.NavigationControl(), 'top-right');

    // 3. Add Markers on Load
    map.value.on('load', () => {
        isMapLoaded = true; // UNLOCK
        updateMarkers();
        updateRoutes();
        updateWeatherLayer();
    });
});

// Flag to track readiness
let isMapLoaded = false;

// Helper: Add Markers (Reactive)
const updateMarkers = () => {
    if (!map.value || !isMapLoaded) return;
    
    // Clear existing logic would go here. For now, we append.
    props.markers.forEach(marker => {
         new maplibregl.Marker({ color: marker.color || 'red' })
            .setLngLat([marker.lng, marker.lat])
            .setPopup(new maplibregl.Popup().setHTML(marker.popup))
            .addTo(map.value);
    });
};

// Helper: Add Routes (Reactive)
const updateRoutes = () => {
    if (!map.value || !isMapLoaded) return;
    
    props.routes.forEach((route, index) => {
        const id = `route-${index}`;
        if (map.value.getSource(id)) return; 

        const geojson = {
            'type': 'Feature',
            'geometry': {
                'type': 'LineString',
                'coordinates': route.path.map(p => [p.lng, p.lat])
            }
        };

        map.value.addSource(id, { type: 'geojson', data: geojson });
        map.value.addLayer({
            'id': id,
            'type': 'line',
            'source': id,
            'layout': { 'line-join': 'round', 'line-cap': 'round' },
            'paint': {
                'line-color': route.color || '#3b82f6',
                'line-width': route.weight || 4
            }
        });
    });
};

// WATCHERS
watch(() => props.markers, updateMarkers, { deep: true });
watch(() => props.routes, updateRoutes, { deep: true });

// WEATHER CONFIG
// Map weather types to OWM Tile Layers or Vector Logic
const weatherConfig = {
    'wind': { type: 'vector', color: '#00ff00', endpoint: '/weather/wind-data' }, // Green for Wind
    'waves': { type: 'vector', color: '#00ffff', endpoint: '/weather/wave-data' }, // Cyan for Waves
    'clouds': { type: 'raster', layer: 'clouds_new' },
    'precipitation': { type: 'raster', layer: 'precipitation_new' },
    'temp': { type: 'raster', layer: 'temp_new' },
    'pressure': { type: 'raster', layer: 'pressure_new' }
};

// Main Weather Switcher
const updateWeatherLayer = () => {
    if (!map.value || !isMapLoaded) return;
    
    // 1. Remove Existing Weather Layers
    const layers = ['weather-circles', 'weather-raster'];
    const sources = ['weather-points', 'weather-raster-source'];
    
    layers.forEach(l => { if(map.value.getLayer(l)) map.value.removeLayer(l); });
    sources.forEach(s => { if(map.value.getSource(s)) map.value.removeSource(s); });

    if (!props.weatherOverlay) return; // None selected

    const config = weatherConfig[props.weatherOverlay];
    if (!config) {
        console.warn("Unknown weather type:", props.weatherOverlay);
        return;
    }

    // 2. Add New Layer
    if (config.type === 'vector') {
        fetchVectorData(config.endpoint, config.color);
    } else if (config.type === 'raster') {
        addRasterLayer(config.layer);
    }
};

// Vector Logic (Wind/Waves)
const fetchVectorData = async (endpoint, colorHex) => {
    try {
        const res = await fetch(endpoint); 
        const json = await res.json();
        if (Array.isArray(json) && json.length >= 2) {
            renderWindLayer(json, colorHex);
        }
    } catch (e) {
        console.error("Vector Fetch Error", e);
    }
};

let windLayer = null;

const renderWindLayer = (data, colorHex) => {
    if (!map.value) return;

    if (windLayer) {
        map.value.removeLayer('wind-layer');
        windLayer = null;
    }

    // Configure Color Scale based on input color
    // Simple monochromatic scale for now as requested
    const colorScale = [
        "rgb(36,104, 180)",
        "rgb(60,157, 194)",
        "rgb(128,205,193)",
        "rgb(151,218,168)",
        "rgb(198,231,181)",
        "rgb(238,247,217)",
        "rgb(255,238,159)",
        "rgb(252,217,125)",
        "rgb(255,182,100)",
        "rgb(252,150,75)",
        "rgb(250,112,52)",
        "rgb(245,64,32)",
        "rgb(237,45,28)",
        "rgb(220,24,32)",
        "rgb(180,0,35)"
    ];

    // Check Imports
    if (!Layer || !ImageSource) {
        console.warn("MapLibre Wind: Missing Classes", { Layer, ImageSource });
        return;
    }

    // Prepare Data (Client-Side Rasterization)
    // We convert the Float Arrays (U/V) into an RGBA Image (Canvas)
    // R: U-Component (Normalized)
    // G: V-Component (Normalized)
    // B: Unused
    // A: 255 (Full Opacity)
    
    // ... rest of logic remains the same ...
    const uGrid = data[0]; 
    const vGrid = data[1] || { data: new Array(uGrid.data.length).fill(0) }; // Safety
    
    const width = uGrid.header.nx;
    const height = uGrid.header.ny;
    const uData = uGrid.data;
    const vData = vGrid.data;
    
    // Determine bounds for normalization (Dynamic or Fixed)
    // Fixed is stable for animation: -50m/s to +50m/s
    const minVal = -50;
    const maxVal = 50;
    const range = maxVal - minVal;

    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');
    const imgData = ctx.createImageData(width, height);
    const d = imgData.data;

    for (let i = 0; i < uData.length; i++) {
        const u = uData[i];
        const v = vData[i];
        
        // Normalize to 0-255
        const r = Math.floor(((u - minVal) / range) * 255);
        const g = Math.floor(((v - minVal) / range) * 255);
        
        d[i * 4] = Math.max(0, Math.min(255, r));     // R
        d[i * 4 + 1] = Math.max(0, Math.min(255, g)); // G
        d[i * 4 + 2] = 0;                             // B
        d[i * 4 + 3] = 255;                           // A
    }
    ctx.putImageData(imgData, 0, 0);

    // Calculate Coordinates (Corners)
    // GRIB2: lo1 (start), la1 (top), dx (inc), dy (inc, neg for down)
    const lo1 = uGrid.header.lo1;
    const la1 = uGrid.header.la1;
    const dx = uGrid.header.dx;
    const dy = uGrid.header.dy;
    
    // Use (N-1) steps for the extent of the grid points
    const lo2 = lo1 + ((width - 1) * dx);
    const la2 = la1 + ((height - 1) * dy);
    
    // Clamp latitudes to prevent "Invalid LngLat" errors (must be -90 to 90)
    // Also Mercator projection gets weird at poles, so maybe clamp to 85?
    // MapLibre's LngLat throws if <-90 or >90.
    const safeLa1 = Math.max(-90, Math.min(90, la1));
    const safeLa2 = Math.max(-90, Math.min(90, la2));

    // MapLibre ImageSource Coordinates: [Top-Left, Top-Right, Bottom-Right, Bottom-Left]
    // [Lng, Lat]
    const coordinates = [
        [lo1, safeLa1],       // Top-Left
        [lo2, safeLa1],       // Top-Right
        [lo2, safeLa2],       // Bottom-Right
        [lo1, safeLa2]        // Bottom-Left
    ];

    // Create Source
    // Source typically takes (image, options)
    const source = new ImageSource(canvas, {
        coordinates: coordinates,
        wrapX: true,
        // Metadata describing the encoding
        min: minVal,
        max: maxVal,
    });

    // Create Layer
    const options = {
        windOptions: {
            colorScale: colorScale,
            frameRate: 60,
            maxAge: 40,
            globalAlpha: 0.9,
            velocityScale: 0.02, 
            paths: 2000,
        },
        renderFrom: 'rg', // We used R and G channels
        // We might need to handle the decoding logic if the library doesn't automatically look at source.min/max
    };

    try {
        console.log("Creating WindLayer with ImageSource", coordinates);
        windLayer = new Layer('wind-layer', source, options);
        map.value.addLayer(windLayer);
    } catch (err) {
        console.error("Failed to add WindLayer:", err);
    }
};

// Raster Logic (Clouds/Rain/Temp)
const addRasterLayer = (layerName) => {
    const apiKey = import.meta.env.VITE_OPENWEATHER_API_KEY || 'your_api_key'; // Ensure Key Exists
    const url = `https://tile.openweathermap.org/map/${layerName}/{z}/{x}/{y}.png?appid=${apiKey}`;
    
    map.value.addSource('weather-raster-source', {
        type: 'raster',
        tiles: [url],
        tileSize: 256
    });
    
    map.value.addLayer({
        id: 'weather-raster',
        type: 'raster',
        source: 'weather-raster-source',
        paint: { 'raster-opacity': 0.6 }
    });
};

// Update on Load
watch(() => props.weatherOverlay, updateWeatherLayer);





// Debugger for now
const checkMapStatus = () => {
    alert("MapLibre Active.\nCurrent Overlay: " + props.weatherOverlay);
};

</script>

<template>
    <div class="relative w-full h-full">
        <!-- Map Container -->
        <div ref="mapContainer" class="w-full h-full rounded-xl shadow-lg"></div>

        <!-- Debug Button -->
        <button 
            @click="checkMapStatus"
            class="absolute top-2 left-2 z-10 bg-blue-600 text-white px-3 py-1 rounded shadow text-xs font-bold hover:bg-blue-700"
        >
            CHECK MAP
        </button>
    </div>
</template>

<style>
/* MapLibre specific marker adjustments if needed */
.maplibregl-popup-content {
    font-family: 'Figtree', sans-serif;
    padding: 10px;
    border-radius: 8px;
}
</style>

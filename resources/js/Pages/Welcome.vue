<script setup>
    import { Head, Link } from '@inertiajs/vue3'
    import ApplicationLogo from '@/Components/ApplicationLogo.vue'
    import { ref, onMounted } from 'vue'

    const props = defineProps({
        canLogin: { type: Boolean },
        canRegister: { type: Boolean },
        laravelVersion: { type: String, required: true },
        phpVersion: { type: String, required: true },
        stats: { type: Object, default: () => ({}) },
    })

    // Animated counters
    const displayStats = ref({
        total_ferries: 0,
        active_routes: 0,
        total_ports: 0,
        total_bookings: 0,
    })

    const animateCounter = (key, target, duration = 1500) => {
        const start = 0
        const startTime = Date.now()
        const animate = () => {
            const elapsed = Date.now() - startTime
            const progress = Math.min(elapsed / duration, 1)
            // Ease out cubic
            const eased = 1 - Math.pow(1 - progress, 3)
            displayStats.value[key] = Math.round(start + (target - start) * eased)
            if (progress < 1) requestAnimationFrame(animate)
        }
        animate()
    }

    onMounted(() => {
        // Start counter animations with staggered delays
        setTimeout(() => animateCounter('total_ferries', props.stats.total_ferries || 0), 300)
        setTimeout(() => animateCounter('active_routes', props.stats.active_routes || 0), 500)
        setTimeout(() => animateCounter('total_ports', props.stats.total_ports || 0), 700)
        setTimeout(() => animateCounter('total_bookings', props.stats.total_bookings || 0), 900)
    })

    const features = [
        {
            icon: `<svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18v3" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 19l-2 2" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 19l2 2" /></svg>`,
            title: 'Live Weather Intelligence',
            desc: 'Real-time wind speed, wave height & visibility forecasts with AI-powered risk analysis for every route.',
        },
        {
            icon: `<svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>`,
            title: 'Interactive Route Maps',
            desc: 'Explore ferry routes on an interactive map with precise weather overlays and port details.',
        },
        {
            icon: `<svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" /></svg>`,
            title: 'Instant Booking',
            desc: 'Book ferry tickets online instantly with secure payments. No queues, no hassle, just easy travel.',
        },
    ]
</script>

<template>
    <Head title="Welcome to FerryCast — Smart Ferry Planning" />
    <div class="min-h-screen bg-gray-50 font-sans text-gray-900 selection:bg-blue-500 selection:text-white">
        
        <!-- Hero Section -->
        <div class="relative bg-white">
            <!-- Background Image -->
            <div class="absolute inset-0 z-0">
                <img class="w-full h-full object-cover" src="https://images.unsplash.com/photo-1590483864757-0b1a0395374e?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80" alt="Ferry on the water" />
                <!-- Gradient Overlay -->
                <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/30 to-black/60"></div>
            </div>

            <!-- Navigation -->
            <nav class="relative z-20 px-6 py-6 flex items-center justify-between max-w-7xl mx-auto border-b border-white/10">
                <Link href="/" class="flex items-center group">
                    <div class="transform group-hover:scale-105 transition-transform">
                        <ApplicationLogo class="text-white" />
                    </div>
                </Link>

                <div class="flex items-center gap-6">
                    <Link
                        href="/schedules"
                        class="text-white/90 hover:text-white font-semibold text-sm transition hidden sm:inline-block"
                    >
                        Schedules
                    </Link>
                    <Link
                        href="/our-fleet"
                        class="text-white/90 hover:text-white font-semibold text-sm transition hidden sm:inline-block"
                    >
                        Fleet
                    </Link>
                    <template v-if="canLogin">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="bg-white text-blue-900 font-bold px-6 py-2.5 rounded-full hover:bg-gray-100 transition shadow-lg text-sm"
                        >
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-white font-semibold text-sm hover:text-gray-200 transition"
                            >
                                Sign In
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="bg-blue-600 text-white font-bold text-sm px-6 py-2.5 rounded-full hover:bg-blue-700 shadow-lg shadow-blue-900/20 transition"
                            >
                                Get Started
                            </Link>
                        </template>
                    </template>
                </div>
            </nav>

            <!-- Hero Content -->
            <div class="relative z-10 px-6 pt-24 pb-32 max-w-7xl mx-auto flex flex-col justify-center min-h-[75vh]">
                <div class="max-w-3xl animate-fade-in-up">
                    <div class="inline-flex items-center bg-white/20 backdrop-blur-md rounded-full px-4 py-1.5 mb-6 border border-white/30">
                        <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                        <span class="text-xs font-bold text-white uppercase tracking-wider">Live operations active</span>
                    </div>

                    <h1 class="text-5xl sm:text-7xl font-extrabold tracking-tight mb-6 text-white leading-[1.1]">
                        Navigate Smarter,
                        <br />
                        <span class="text-blue-400">Sail Safer.</span>
                    </h1>

                    <p class="text-gray-100 text-lg sm:text-xl max-w-2xl mb-12 leading-relaxed font-medium">
                        The complete platform for Malaysian ferry travel. Book tickets, check live schedules, and travel with confidence using our AI-powered weather intelligence.
                    </p>

                    <!-- Mock Booking Widget -->
                    <div class="bg-white p-4 sm:p-6 rounded-3xl shadow-2xl max-w-4xl flex flex-col sm:flex-row gap-4 items-center animate-fade-in-up delay-200">
                        <div class="flex-1 w-full border border-gray-200 rounded-2xl px-5 py-3 flex flex-col justify-center hover:border-blue-500 transition cursor-text">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">From</span>
                            <div class="text-gray-900 font-semibold">Langkawi (Kuah)</div>
                        </div>
                        
                        <div class="hidden sm:flex items-center justify-center w-10 h-10 rounded-full bg-gray-50 border border-gray-200 text-gray-400 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                        </div>

                        <div class="flex-1 w-full border border-gray-200 rounded-2xl px-5 py-3 flex flex-col justify-center hover:border-blue-500 transition cursor-text">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">To</span>
                            <div class="text-gray-900 font-semibold">Kuala Perlis</div>
                        </div>

                        <Link href="/schedules" class="w-full sm:w-auto bg-blue-600 text-white font-bold px-8 py-5 rounded-2xl hover:bg-blue-700 transition shadow-lg flex items-center justify-center whitespace-nowrap shrink-0">
                            Search Ferries
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Section -->
        <div class="max-w-7xl mx-auto px-6 py-24">
            
            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-24 animate-fade-in-up delay-300">
                <div class="bg-white rounded-3xl p-8 text-center shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] hover:-translate-y-1 transition-all">
                    <div class="text-4xl font-extrabold text-blue-900 mb-2 tabular-nums">{{ displayStats.total_ferries }}</div>
                    <div class="text-xs font-bold uppercase tracking-widest text-gray-500">Vessels</div>
                </div>
                <div class="bg-white rounded-3xl p-8 text-center shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] hover:-translate-y-1 transition-all">
                    <div class="text-4xl font-extrabold text-blue-900 mb-2 tabular-nums">{{ displayStats.active_routes }}</div>
                    <div class="text-xs font-bold uppercase tracking-widest text-gray-500">Routes</div>
                </div>
                <div class="bg-white rounded-3xl p-8 text-center shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] hover:-translate-y-1 transition-all">
                    <div class="text-4xl font-extrabold text-blue-900 mb-2 tabular-nums">{{ displayStats.total_ports }}</div>
                    <div class="text-xs font-bold uppercase tracking-widest text-gray-500">Ports</div>
                </div>
                <div class="bg-white rounded-3xl p-8 text-center shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] hover:-translate-y-1 transition-all">
                    <div class="text-4xl font-extrabold text-blue-900 mb-2 tabular-nums">{{ displayStats.total_bookings }}</div>
                    <div class="text-xs font-bold uppercase tracking-widest text-gray-500">Bookings</div>
                </div>
            </div>

            <!-- Features -->
            <div class="text-center max-w-3xl mx-auto mb-16 animate-fade-in-up delay-400">
                <h2 class="text-3xl font-extrabold text-gray-900 mb-4">Everything you need for a smooth journey</h2>
                <p class="text-gray-500 text-lg">We combine real-time data with AI to ensure your ferry travel is safe, reliable, and entirely hassle-free.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 animate-fade-in-up delay-500">
                <div
                    v-for="(feature, index) in features"
                    :key="index"
                    class="bg-white rounded-3xl p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 hover:shadow-[0_8px_30px_rgb(0,0,0,0.12)] hover:-translate-y-1 transition-all duration-300"
                >
                    <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mb-6" v-html="feature.icon"></div>
                    <h3 class="text-gray-900 font-bold text-xl mb-3">{{ feature.title }}</h3>
                    <p class="text-gray-500 leading-relaxed">{{ feature.desc }}</p>
                </div>
            </div>
            
        </div>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-100 py-12">
            <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between">
                <div class="flex items-center mb-4 md:mb-0 transform hover:scale-105 transition">
                    <ApplicationLogo class="text-blue-900" />
                </div>
                <p class="text-gray-400 text-sm font-medium">
                    © 2025-2026 FerryCast. Built with Laravel v{{ laravelVersion }}.
                </p>
            </div>
        </footer>
    </div>
</template>

<style>
    @keyframes fadeInUp {
        from { opacity: 0; transform: translate3d(0, 40px, 0); }
        to { opacity: 1; transform: translate3d(0, 0, 0); }
    }
    .animate-fade-in-up { animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) both; }
    .delay-200 { animation-delay: 0.2s; }
    .delay-300 { animation-delay: 0.3s; }
    .delay-400 { animation-delay: 0.4s; }
    .delay-500 { animation-delay: 0.5s; }
</style>

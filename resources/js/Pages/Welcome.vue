<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="Welcome to FerryCast" />
    <div class="relative min-h-screen overflow-hidden font-sans text-white bg-gradient-to-b from-blue-600 to-blue-900">
        
        <!-- Animated Background Waves -->
        <div class="absolute bottom-0 left-0 w-full overflow-hidden leading-none rotate-180 z-0">
            <svg class="relative block w-[calc(100%+1.3px)] h-[120px] sm:h-[150px]" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="fill-white/10"></path>
                <path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".25" class="fill-white/20"></path>
                <path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" opacity=".5" class="fill-white/30"></path>
            </svg>
        </div>

        <div class="relative z-10 flex flex-col items-center justify-center min-h-screen px-4 text-center">
            
            <!-- Logo / Brand -->
            <div class="mb-8 animate-fade-in-down scale-150">
                 <ApplicationLogo />
            </div>

            <!-- Definition / Description -->
            <p class="max-w-2xl mb-12 text-lg font-light leading-relaxed text-blue-100 sm:text-2xl animate-fade-in-up delay-100">
                Seamlessly plan your sea voyages. <span class="font-bold text-white">FerryCast</span> delivers real-time schedules, weather forecasts, and smart route insights for the modern traveler.
            </p>

            <!-- Buttons -->
            <div class="flex flex-col w-full max-w-2xl mx-auto space-y-6 animate-fade-in-up delay-200">
                
                <!-- Main Action Row -->
                <div v-if="canLogin" class="flex flex-col sm:flex-row justify-center items-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="w-full sm:w-auto px-10 py-4 text-lg font-black text-blue-900 uppercase tracking-widest bg-white rounded-2xl shadow-xl hover:bg-blue-50 hover:scale-105 transition-all focus:outline-none focus:ring-4 focus:ring-blue-300"
                    >
                        Go to Dashboard
                    </Link>

                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="w-full sm:w-auto px-10 py-4 text-lg font-black text-white uppercase tracking-widest border-2 border-white rounded-2xl hover:bg-white hover:text-blue-900 shadow-lg transition-all active:scale-95"
                        >
                            Sign In
                        </Link>

                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="w-full sm:w-auto px-10 py-4 text-lg font-black text-blue-900 uppercase tracking-widest bg-yellow-400 rounded-2xl shadow-xl hover:bg-yellow-300 hover:scale-105 transition-all active:scale-95"
                        >
                            Join Now
                        </Link>
                    </template>
                </div>

                <!-- Public Access Links -->
                <div class="flex flex-row justify-center items-center gap-6 pt-4 border-t border-white/10">
                    <Link href="/schedules" class="text-blue-200 hover:text-white font-bold uppercase tracking-widest text-sm flex items-center gap-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        View Schedules
                    </Link>
                    <span class="text-blue-200/30">|</span>
                    <Link href="/our-fleet" class="text-blue-200 hover:text-white font-bold uppercase tracking-widest text-sm flex items-center gap-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        Browse Fleet
                    </Link>
                </div>
            </div>
            
             <footer class="absolute bottom-4 text-xs text-blue-200/50 animate-fade-in">
                Laravel v{{ laravelVersion }} (PHP v{{ phpVersion }})
            </footer>
        </div>
    </div>
</template>

<style>
.animate-fade-in-down {
    animation: fadeInDown 1s ease-out;
}
.animate-fade-in-up {
    animation: fadeInUp 1s ease-out backwards;
}
.animate-fade-in {
    animation: fadeIn 1.5s ease-out;
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translate3d(0, -30px, 0);
    }
    to {
        opacity: 1;
        transform: translate3d(0, 0, 0);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translate3d(0, 30px, 0);
    }
    to {
        opacity: 1;
        transform: translate3d(0, 0, 0);
    }
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.delay-100 {
    animation-delay: 0.15s;
}
.delay-200 {
    animation-delay: 0.3s;
}
</style>
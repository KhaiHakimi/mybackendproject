<script setup>
    import GuestLayout from '@/Layouts/GuestLayout.vue'
    import { Head, useForm, Link } from '@inertiajs/vue3'
    import { ref } from 'vue'

    const props = defineProps({
        ferry: Object,
        ratingDistribution: Object,
    })

    const form = useForm({
        rating: 5,
        comment: '',
    })

    const submitReview = () => {
        form.post(route('ferries.reviews.store', props.ferry.id), {
            onSuccess: () => form.reset(),
            preserveScroll: true,
        })
    }

    const getImageUrl = (path) => {
        return (
            path ||
            'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80'
        )
    }
    const formatDate = (dateString) => {
        const options = {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            timeZone: 'Asia/Kuala_Lumpur',
        }
        return new Date(dateString).toLocaleDateString('en-GB', options)
    }

    // Calculate percent for distribution bars
    const getPercent = (count) => {
        const total = props.ferry.reviews_count || 1
        return Math.round((count / total) * 100)
    }
</script>

<template>
    <Head :title="ferry.name" />

    <GuestLayout :fullWidth="true">
        <template #header>
            <div
                class="flex items-center space-x-3 text-sm font-bold uppercase tracking-widest text-blue-400"
            >
                <Link
                    :href="route('ferries.public_index')"
                    class="hover:text-blue-600 transition-colors"
                    >Fleet radar</Link
                >
                <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="3"
                        d="M9 5l7 7-7 7"
                    ></path>
                </svg>
                <span class="text-blue-900">{{ ferry.name }}</span>
            </div>
        </template>

        <div class="py-12 bg-cream-50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                    <!-- Left Column: Ferry Details -->
                    <div class="lg:col-span-2 space-y-10">
                        <!-- Hero Section -->
                        <div
                            class="bg-white rounded-[3rem] overflow-hidden shadow-2xl border border-blue-50 relative"
                        >
                            <div class="relative h-[500px]">
                                <img
                                    :src="getImageUrl(ferry.image_path)"
                                    class="w-full h-full object-cover"
                                />
                                <div
                                    class="absolute inset-0 bg-gradient-to-t from-blue-900 via-blue-900/20 to-transparent"
                                ></div>
                                <div
                                    class="absolute bottom-0 left-0 p-10 text-white w-full"
                                >
                                    <div
                                        class="flex flex-wrap items-end justify-between gap-6"
                                    >
                                        <div>
                                            <div
                                                class="bg-yellow-400 text-blue-900 text-[10px] font-black uppercase px-3 py-1 rounded-md inline-block mb-4 tracking-widest"
                                            >
                                                Active Vessel
                                            </div>
                                            <h1
                                                class="text-5xl font-black mb-2 tracking-tighter leading-none"
                                            >
                                                {{ ferry.name }}
                                            </h1>
                                            <p
                                                class="text-xl font-bold text-blue-100 opacity-80"
                                            >
                                                Vessel Operator:
                                                {{ ferry.operator }}
                                            </p>
                                        </div>
                                        <div
                                            class="bg-white/10 backdrop-blur-md p-6 rounded-3xl border border-white/20 text-center min-w-[160px]"
                                        >
                                            <div
                                                class="text-[10px] text-blue-200 font-black uppercase tracking-widest mb-1"
                                            >
                                                Fare Start
                                            </div>
                                            <div
                                                class="text-3xl font-black text-yellow-400 leading-none"
                                            >
                                                RM {{ ferry.price || '0' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-8 flex items-center gap-4">
                                        <a
                                            v-if="
                                                ferry.ticket_type !==
                                                'Walk-in / Counter'
                                            "
                                            :href="
                                                ferry.booking_url ||
                                                'https://www.easybook.com'
                                            "
                                            target="_blank"
                                            class="inline-flex items-center px-10 py-4 bg-yellow-400 text-blue-900 font-black rounded-2xl shadow-2xl hover:bg-yellow-300 transition-all transform hover:scale-105 active:scale-95 uppercase tracking-widest text-sm"
                                        >
                                            <svg
                                                class="w-5 h-5 mr-2"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="3"
                                                    d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 012-2h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5z"
                                                ></path>
                                            </svg>
                                            Book This Vessel Now
                                        </a>
                                        <div
                                            class="bg-white/10 backdrop-blur-sm px-6 py-3 rounded-2xl border border-white/20"
                                        >
                                            <p
                                                class="text-[10px] text-blue-200 font-black uppercase tracking-widest mb-1"
                                            >
                                                Booking Method
                                            </p>
                                            <p
                                                class="text-lg font-black text-white leading-none"
                                            >
                                                {{
                                                    ferry.ticket_type ||
                                                    'Walk-in / Counter'
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="p-10">
                                <div class="prose max-w-none text-blue-900/70">
                                    <h3
                                        class="text-2xl font-black text-blue-900 mb-6 uppercase tracking-tighter"
                                    >
                                        Vessel Specifications
                                    </h3>
                                    <p
                                        class="text-lg font-medium leading-relaxed italic border-l-4 border-blue-600 pl-6 py-2 bg-blue-50/30 rounded-r-2xl"
                                    >
                                        {{
                                            ferry.description ||
                                            'Modern marine transport offering high safety standards and passenger comfort for all voyages.'
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="mt-10 grid grid-cols-2 md:grid-cols-4 gap-8 pt-10 border-t border-blue-50"
                                >
                                    <div class="bg-blue-50/50 p-4 rounded-2xl">
                                        <div
                                            class="text-[10px] text-blue-400 font-black uppercase tracking-widest mb-2"
                                        >
                                            Length
                                        </div>
                                        <div
                                            class="text-xl font-black text-blue-900"
                                        >
                                            {{ ferry.length_ft || 'N/A' }}
                                        </div>
                                    </div>
                                    <div class="bg-blue-50/50 p-4 rounded-2xl">
                                        <div
                                            class="text-[10px] text-blue-400 font-black uppercase tracking-widest mb-2"
                                        >
                                            Capacity
                                        </div>
                                        <div
                                            class="text-xl font-black text-blue-900"
                                        >
                                            {{ ferry.capacity }}
                                            <span
                                                class="text-xs font-bold text-blue-400 uppercase"
                                                >Seats</span
                                            >
                                        </div>
                                    </div>
                                    <div class="bg-blue-50/50 p-4 rounded-2xl">
                                        <div
                                            class="text-[10px] text-blue-400 font-black uppercase tracking-widest mb-2"
                                        >
                                            Engines
                                        </div>
                                        <div
                                            class="text-xl font-black text-blue-900"
                                        >
                                            High-Speed
                                        </div>
                                    </div>
                                    <div
                                        class="bg-emerald-50 p-4 rounded-2xl border border-emerald-100"
                                    >
                                        <div
                                            class="text-[10px] text-emerald-400 font-black uppercase tracking-widest mb-2"
                                        >
                                            Status
                                        </div>
                                        <div
                                            class="text-xl font-black text-emerald-600"
                                        >
                                            IN SERVICE
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="mt-10 pt-10 border-t border-blue-50"
                                >
                                    <h3
                                        class="text-xl font-black text-blue-900 mb-6 uppercase tracking-tighter"
                                    >
                                        Onboard Amenities
                                    </h3>
                                    <div class="flex flex-wrap gap-4">
                                        <div
                                            v-for="amenity in ferry.amenities"
                                            :key="amenity"
                                            class="flex items-center px-6 py-3 bg-white rounded-2xl text-sm font-black text-blue-900 border-2 border-blue-50 shadow-sm hover:border-blue-200 transition-colors"
                                        >
                                            <div
                                                class="w-2 h-2 bg-blue-600 rounded-full mr-3"
                                            ></div>
                                            {{ amenity }}
                                        </div>
                                        <div
                                            v-if="!ferry.amenities?.length"
                                            class="text-sm font-bold text-blue-300 italic"
                                        >
                                            Standard marine safety equipment and
                                            comfortable seating provided.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Schedules Preview -->
                        <div
                            v-if="ferry.schedules && ferry.schedules.length > 0"
                            class="bg-blue-900 rounded-[3rem] p-10 shadow-2xl relative overflow-hidden"
                        >
                            <div
                                class="relative z-10 flex justify-between items-center mb-8"
                            >
                                <h3
                                    class="text-2xl font-black text-white uppercase tracking-tighter"
                                >
                                    Live Voyage Log
                                </h3>
                                <Link
                                    :href="route('schedules.index')"
                                    class="bg-white/10 hover:bg-white/20 text-white px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border border-white/20 transition-all"
                                >
                                    View Full Radar
                                </Link>
                            </div>
                            <div class="overflow-x-auto relative z-10">
                                <table
                                    class="min-w-full text-left border-separate border-spacing-y-3"
                                >
                                    <thead
                                        class="text-[10px] text-blue-300 font-black uppercase tracking-widest"
                                    >
                                        <tr>
                                            <th class="px-6 py-2">Dep. Date</th>
                                            <th class="px-6 py-2">Time</th>
                                            <th class="px-6 py-2">Course</th>
                                            <th class="px-6 py-2 text-right">
                                                Fare
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="trip in ferry.schedules.slice(
                                                0,
                                                5,
                                            )"
                                            :key="trip.id"
                                            class="bg-white/5 hover:bg-white/10 transition-colors rounded-2xl overflow-hidden group"
                                        >
                                            <td
                                                class="px-6 py-4 font-black text-blue-100 rounded-l-2xl"
                                            >
                                                {{
                                                    formatDate(
                                                        trip.departure_time,
                                                    )
                                                }}
                                            </td>
                                            <td
                                                class="px-6 py-4 font-black text-white text-xl tracking-tighter"
                                            >
                                                {{
                                                    new Date(
                                                        trip.departure_time,
                                                    ).toLocaleTimeString(
                                                        'en-GB',
                                                        {
                                                            hour: '2-digit',
                                                            minute: '2-digit',
                                                            timeZone:
                                                                'Asia/Kuala_Lumpur',
                                                        },
                                                    )
                                                }}
                                            </td>
                                            <td
                                                class="px-6 py-4 text-blue-200 font-bold"
                                            >
                                                <div class="flex items-center">
                                                    {{ trip.origin?.name }}
                                                    <svg
                                                        class="w-4 h-4 mx-3 text-blue-500"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="3"
                                                            d="M17 8l4 4m0 0l-4 4m4-4H3"
                                                        ></path>
                                                    </svg>
                                                    {{ trip.destination?.name }}
                                                </div>
                                            </td>
                                            <td
                                                class="px-6 py-4 text-right font-black text-yellow-400 text-xl tracking-tighter rounded-r-2xl"
                                            >
                                                RM {{ trip.price }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Background pattern -->
                            <svg
                                class="absolute right-0 bottom-0 w-64 h-64 text-white/5 pointer-events-none transform translate-x-20 translate-y-20"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71z"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- Right Column: Ratings & Reviews -->
                    <div class="space-y-10">
                        <!-- External Review CTA -->
                        <div
                            class="bg-white rounded-[2.5rem] p-10 shadow-2xl relative overflow-hidden transform hover:-rotate-1 transition-transform border border-blue-50"
                        >
                            <h3
                                class="text-xl font-black text-blue-900 mb-6 uppercase tracking-widest"
                            >
                                Feedback & Ratings
                            </h3>
                            <div class="relative z-10 text-center">
                                <p class="text-blue-900/70 font-bold mb-8">
                                    We value transparency. Our ratings and
                                    reviews are verified and synced directly
                                    from Google Maps to ensure authenticity.
                                </p>

                                <a
                                    v-if="ferry.google_place_id"
                                    :href="`https://search.google.com/local/writereview?placeid=${ferry.google_place_id}`"
                                    target="_blank"
                                    class="w-full inline-flex justify-center bg-blue-900 text-white font-black py-4 rounded-2xl hover:bg-[#4285F4] transition-all shadow-xl uppercase tracking-[0.2em] text-xs items-center gap-3"
                                >
                                    <svg
                                        class="w-5 h-5"
                                        viewBox="0 0 24 24"
                                        fill="currentColor"
                                    >
                                        <path
                                            d="M21.35 11.1h-9.17v2.73h6.51c-.33 3.81-3.5 5.44-6.5 5.44C8.36 19.27 5 16.25 5 12c0-4.1 3.2-7.27 7.2-7.27 3.09 0 4.9 1.97 4.9 1.97L19 4.72S16.64 2 12 2C6.48 2 2 6.48 2 12s4.48 10 10 10c4.59 0 9.13-3.23 9.53-9.14.05-.75-.17-.76-.17-.76z"
                                        />
                                    </svg>
                                    Write a Review on Google
                                </a>
                                <div
                                    v-else
                                    class="text-xs text-blue-300 font-bold uppercase tracking-widest mt-4"
                                >
                                    Integration Pending
                                </div>
                            </div>
                            <!-- Decorative background -->
                            <div
                                class="absolute -right-10 -bottom-10 w-40 h-40 bg-blue-50 rounded-full blur-3xl pointer-events-none"
                            ></div>
                        </div>

                        <!-- Recent Reviews List -->
                        <div class="space-y-6">
                            <h3
                                class="text-xl font-black text-blue-900 px-2 uppercase tracking-tighter"
                            >
                                Verified Voyages (Google Reviews)
                            </h3>
                            <div
                                v-if="ferry.reviews && ferry.reviews.length > 0"
                                class="space-y-4"
                            >
                                <div
                                    v-for="review in ferry.reviews"
                                    :key="review.id"
                                    class="bg-white p-8 rounded-3xl shadow-xl border border-blue-50 transform transition-all hover:scale-[1.02]"
                                >
                                    <div
                                        class="flex justify-between items-start mb-6"
                                    >
                                        <div class="flex items-center">
                                            <img
                                                v-if="review.user.avatar"
                                                :src="review.user.avatar"
                                                class="w-12 h-12 rounded-2xl shadow-lg mr-4 object-cover"
                                                referrerpolicy="no-referrer"
                                            />
                                            <div
                                                v-else
                                                class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-900 flex items-center justify-center font-black text-lg mr-4 shadow-lg"
                                            >
                                                {{ review.user.name.charAt(0) }}
                                            </div>
                                            <div>
                                                <div
                                                    class="text-base font-black text-blue-900 tracking-tight"
                                                >
                                                    {{ review.user.name }}
                                                </div>
                                                <div
                                                    class="text-[10px] font-black text-blue-300 uppercase tracking-widest flex items-center"
                                                >
                                                    {{
                                                        formatDate(
                                                            review.created_at,
                                                        )
                                                    }}
                                                    <span
                                                        v-if="
                                                            review.source ===
                                                            'Google Maps'
                                                        "
                                                        class="ml-2 px-2 py-0.5 bg-gray-100 text-gray-500 rounded-md text-[8px] flex items-center gap-1"
                                                    >
                                                        <svg
                                                            class="w-3 h-3"
                                                            viewBox="0 0 24 24"
                                                            fill="currentColor"
                                                        >
                                                            <path
                                                                d="M21.35 11.1h-9.17v2.73h6.51c-.33 3.81-3.5 5.44-6.5 5.44C8.36 19.27 5 16.25 5 12c0-4.1 3.2-7.27 7.2-7.27 3.09 0 4.9 1.97 4.9 1.97L19 4.72S16.64 2 12 2C6.48 2 2 6.48 2 12s4.48 10 10 10c4.59 0 9.13-3.23 9.53-9.14.05-.75-.17-.76-.17-.76z"
                                                            />
                                                        </svg>
                                                        GOOGLE
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            class="bg-yellow-50 px-3 py-1 rounded-lg border border-yellow-100 flex text-yellow-500 text-xs font-black items-center"
                                        >
                                            {{ review.rating }}
                                            <svg
                                                class="w-3 h-3 ml-1 fill-current"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"
                                                />
                                            </svg>
                                        </div>
                                    </div>
                                    <p
                                        class="text-blue-900/70 font-medium leading-relaxed italic border-l-2 border-blue-100 pl-4"
                                    >
                                        "{{ review.comment }}"
                                    </p>
                                </div>
                            </div>
                            <div
                                v-else
                                class="text-center py-16 text-blue-300 bg-white rounded-[2.5rem] border-4 border-dashed border-blue-50 font-bold italic"
                            >
                                No voyage logs recorded yet. <br />
                                Be the first to transmit your data!
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

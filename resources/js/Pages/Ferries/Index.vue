<script setup>
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
    import Modal from '@/Components/Modal.vue'
    import SecondaryButton from '@/Components/SecondaryButton.vue'
    import PrimaryButton from '@/Components/PrimaryButton.vue'
    import TextInput from '@/Components/TextInput.vue'
    import InputLabel from '@/Components/InputLabel.vue'
    import InputError from '@/Components/InputError.vue'
    import { Head, useForm, router } from '@inertiajs/vue3'
    import { ref } from 'vue'

    const props = defineProps({
        ferries: Array,
    })

    const showModal = ref(false)
    const isEditing = ref(false)
    const editId = ref(null)
    const previewImage = ref(null)

    const availableAmenities = [
        'Air Conditioning',
        'Indoor Seating',
        'Outdoor Deck',
        'Restrooms',
        'Snack Kiosk',
        'TV Entertainment',
        'Luggage Rack',
        'Life Jackets',
        'Prayer Room',
        'Wi-Fi',
    ]

    const form = useForm({
        name: '',
        capacity: '',
        operator: '',
        description: '',
        price: '',
        ticket_type: 'Online Booking',
        length_ft: '',
        rating: '',
        reviews_count: '',
        amenities: [],
        image: null,
    })

    const getImageUrl = (path) => {
        return (
            path ||
            'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80'
        )
    }

    const openModal = (ferry = null) => {
        form.clearErrors()

        if (ferry) {
            isEditing.value = true
            editId.value = ferry.id
            form.name = ferry.name
            form.capacity = ferry.capacity
            form.operator = ferry.operator
            form.description = ferry.description
            form.price = ferry.price
            form.ticket_type = ferry.ticket_type || 'Walk-in / Counter'
            form.length_ft = ferry.length_ft
            form.rating = ferry.rating
            form.reviews_count = ferry.reviews_count
            form.amenities = ferry.amenities || []
            form.image = null
            previewImage.value = ferry.image_path
        } else {
            isEditing.value = false
            editId.value = null
            form.reset()
            form.amenities = []
            previewImage.value = null
        }
        showModal.value = true
    }

    const closeModal = () => {
        showModal.value = false
        form.reset()
    }

    const submit = () => {
        if (isEditing.value) {
            form.transform((data) => ({
                ...data,
                _method: 'put',
            })).post(route('ferries.update', editId.value), {
                onSuccess: () => closeModal(),
            })
        } else {
            form.post(route('ferries.store'), {
                onSuccess: () => closeModal(),
            })
        }
    }

    const deleteFerry = (id) => {
        if (confirm('Are you sure you want to delete this ferry?')) {
            router.delete(route('ferries.destroy', id))
        }
    }

    const toggleAmenity = (amenity) => {
        const index = form.amenities.indexOf(amenity)
        if (index === -1) {
            form.amenities.push(amenity)
        } else {
            form.amenities.splice(index, 1)
        }
    }

    const handleImageChange = (e) => {
        const file = e.target.files[0]
        if (file) {
            form.image = file
            previewImage.value = URL.createObjectURL(file)
        }
    }
</script>

<template>
    <Head title="Manage Ferries" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col md:flex-row justify-between items-center gap-4"
            >
                <h2
                    class="text-2xl font-black text-blue-900 uppercase tracking-tighter"
                >
                    Vessel Fleet Management
                </h2>
                <button
                    @click="openModal()"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-black px-8 py-3 rounded-2xl shadow-xl shadow-blue-100 transition transform active:scale-95 uppercase tracking-widest text-xs"
                >
                    Commission New Vessel
                </button>
            </div>
        </template>

        <div class="py-12 bg-cream-50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10"
                >
                    <div
                        v-for="ferry in ferries"
                        :key="ferry.id"
                        class="group bg-white rounded-[2.5rem] overflow-hidden shadow-xl hover:shadow-2xl transition-all duration-500 border border-blue-50 flex flex-col h-full hover:-translate-y-2"
                    >
                        <div class="relative h-64 bg-blue-50 overflow-hidden">
                            <img
                                :src="getImageUrl(ferry.image_path)"
                                :alt="ferry.name"
                                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                            />
                            <!-- Overlay actions -->
                            <div
                                class="absolute inset-0 bg-blue-900/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center space-x-4 backdrop-blur-sm"
                            >
                                <button
                                    @click="openModal(ferry)"
                                    class="bg-white text-blue-900 p-4 rounded-2xl hover:bg-yellow-400 transition-all shadow-xl transform hover:scale-110"
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
                                            stroke-width="3"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 00 2 2h11a2 2 0 00 2-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                        ></path>
                                    </svg>
                                </button>
                                <button
                                    @click="deleteFerry(ferry.id)"
                                    class="bg-rose-600 text-white p-4 rounded-2xl hover:bg-rose-700 transition-all shadow-xl transform hover:scale-110"
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
                                            stroke-width="3"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        ></path>
                                    </svg>
                                </button>
                            </div>
                            <div
                                class="absolute bottom-0 left-0 w-full bg-gradient-to-t from-blue-900/90 to-transparent p-6 pointer-events-none"
                            >
                                <h3
                                    class="text-2xl font-black text-white leading-none tracking-tight"
                                >
                                    {{ ferry.name }}
                                </h3>
                            </div>
                        </div>

                        <div class="p-8 flex-1 flex flex-col bg-white">
                            <div class="flex items-center justify-between mb-6">
                                <div
                                    class="flex items-center text-yellow-400 text-sm"
                                >
                                    <span
                                        class="font-black text-blue-900 mr-2"
                                        >{{ ferry.rating }}</span
                                    >
                                    <svg
                                        class="w-4 h-4 fill-current"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"
                                        />
                                    </svg>
                                    <span
                                        class="text-blue-300 text-[10px] font-black uppercase ml-2 tracking-widest"
                                        >({{ ferry.reviews_count }} logs)</span
                                    >
                                </div>
                                <span
                                    class="bg-blue-50 text-blue-600 px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border border-blue-100"
                                    >{{ ferry.operator }}</span
                                >
                            </div>

                            <p
                                class="text-sm font-medium text-blue-900/60 mb-6 line-clamp-2 leading-relaxed italic border-l-2 border-blue-100 pl-4"
                            >
                                {{ ferry.description }}
                            </p>

                            <div class="grid grid-cols-2 gap-4 mb-8">
                                <div
                                    class="bg-blue-50/50 p-3 rounded-xl border border-blue-50"
                                >
                                    <p
                                        class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1"
                                    >
                                        Fare
                                    </p>
                                    <p
                                        class="text-lg font-black text-emerald-600 leading-none"
                                    >
                                        RM {{ ferry.price }}
                                    </p>
                                </div>
                                <div
                                    class="bg-blue-50/50 p-3 rounded-xl border border-blue-50"
                                >
                                    <p
                                        class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1"
                                    >
                                        Capacity
                                    </p>
                                    <p
                                        class="text-lg font-black text-blue-900 leading-none"
                                    >
                                        {{ ferry.capacity }}
                                        <span class="text-[10px] font-bold"
                                            >PAX</span
                                        >
                                    </p>
                                </div>
                            </div>

                            <div class="mt-auto">
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="amenity in ferry.amenities"
                                        :key="amenity"
                                        class="px-2 py-1 bg-white border border-blue-50 text-blue-400 text-[9px] font-black uppercase rounded-md tracking-tighter"
                                    >
                                        {{ amenity }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-10 bg-white">
                <div class="flex items-center gap-4 mb-8">
                    <div class="bg-blue-900 p-3 rounded-2xl">
                        <svg
                            class="w-6 h-6 text-white"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"
                            ></path>
                        </svg>
                    </div>
                    <h2
                        class="text-2xl font-black text-blue-900 uppercase tracking-tighter"
                    >
                        {{
                            isEditing
                                ? 'Update Vessel Profile'
                                : 'Commission New Vessel'
                        }}
                    </h2>
                </div>

                <form
                    @submit.prevent="submit"
                    class="grid grid-cols-1 md:grid-cols-2 gap-6"
                >
                    <div class="col-span-2 md:col-span-1 space-y-1">
                        <InputLabel
                            for="name"
                            value="Vessel Name"
                            class="text-[10px] font-black uppercase text-blue-900 ml-1"
                        />
                        <TextInput
                            id="name"
                            type="text"
                            class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500"
                            v-model="form.name"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="col-span-2 md:col-span-1 space-y-1">
                        <InputLabel
                            for="operator"
                            value="Operator Fleet"
                            class="text-[10px] font-black uppercase text-blue-900 ml-1"
                        />
                        <TextInput
                            id="operator"
                            type="text"
                            class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500"
                            v-model="form.operator"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.operator"
                        />
                    </div>

                    <div class="col-span-2 space-y-1">
                        <InputLabel
                            for="description"
                            value="Vessel Bio"
                            class="text-[10px] font-black uppercase text-blue-900 ml-1"
                        />
                        <textarea
                            id="description"
                            class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500 min-h-[100px]"
                            v-model="form.description"
                            rows="3"
                        ></textarea>
                        <InputError
                            class="mt-2"
                            :message="form.errors.description"
                        />
                    </div>

                    <div class="space-y-1">
                        <InputLabel
                            for="capacity"
                            value="Max Occupancy"
                            class="text-[10px] font-black uppercase text-blue-900 ml-1"
                        />
                        <TextInput
                            id="capacity"
                            type="number"
                            class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500"
                            v-model="form.capacity"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.capacity"
                        />
                    </div>

                    <div class="space-y-1">
                        <InputLabel
                            for="price"
                            value="Fare Rate (RM)"
                            class="text-[10px] font-black uppercase text-blue-900 ml-1"
                        />
                        <TextInput
                            id="price"
                            type="text"
                            class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500"
                            v-model="form.price"
                        />
                        <InputError class="mt-2" :message="form.errors.price" />
                    </div>

                    <div>
                        <InputLabel for="ticket_type" value="Ticket Method" />
                        <select
                            id="ticket_type"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            v-model="form.ticket_type"
                        >
                            <option value="Online Booking">
                                Online Booking
                            </option>
                            <option value="Walk-in / Counter">
                                Walk-in / Counter
                            </option>
                            <option value="Both Available">
                                Both Available
                            </option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="form.errors.ticket_type"
                        />
                    </div>

                    <div>
                        <InputLabel for="length" value="Length (e.g. 38ft)" />
                        <TextInput
                            id="length"
                            type="text"
                            class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500"
                            v-model="form.length_ft"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.length_ft"
                        />
                    </div>

                    <div class="space-y-1">
                        <InputLabel
                            for="rating"
                            value="Initial Rating"
                            class="text-[10px] font-black uppercase text-blue-900 ml-1"
                        />
                        <TextInput
                            id="rating"
                            type="number"
                            step="0.1"
                            max="5"
                            class="w-full bg-blue-50/50 border-blue-50 rounded-2xl py-3 px-4 font-bold text-blue-900 focus:ring-blue-500"
                            v-model="form.rating"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.rating"
                        />
                    </div>

                    <div
                        class="col-span-2 space-y-4 pt-4 border-t border-blue-50"
                    >
                        <InputLabel
                            value="Onboard Systems & Amenities"
                            class="text-[10px] font-black uppercase text-blue-900 ml-1 mb-4"
                        />
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <label
                                v-for="amenity in availableAmenities"
                                :key="amenity"
                                class="flex items-center group cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    :value="amenity"
                                    class="w-5 h-5 rounded-lg border-blue-100 text-blue-600 shadow-sm focus:ring-blue-500 transition-all group-hover:scale-110"
                                    v-model="form.amenities"
                                />
                                <span
                                    class="ml-3 text-xs font-black text-blue-900 group-hover:text-blue-600 transition-colors uppercase tracking-tighter"
                                    >{{ amenity }}</span
                                >
                            </label>
                        </div>
                    </div>

                    <div class="col-span-2 pt-6 border-t border-blue-50">
                        <InputLabel
                            for="image"
                            value="Visual Profile (Photo)"
                            class="text-[10px] font-black uppercase text-blue-900 ml-1 mb-4"
                        />
                        <div class="flex items-center gap-6">
                            <label
                                class="cursor-pointer bg-blue-900 text-white font-black px-6 py-3 rounded-2xl text-[10px] uppercase tracking-widest hover:bg-black transition-all"
                            >
                                Upload Image
                                <input
                                    type="file"
                                    @change="handleImageChange"
                                    class="hidden"
                                />
                            </label>
                            <div v-if="previewImage" class="relative group">
                                <img
                                    :src="
                                        previewImage.startsWith('data:')
                                            ? previewImage
                                            : getImageUrl(previewImage)
                                    "
                                    class="h-24 w-24 rounded-2xl object-cover bg-blue-50 border-4 border-white shadow-xl"
                                />
                                <div
                                    class="absolute inset-0 bg-blue-900/40 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"
                                ></div>
                            </div>
                        </div>
                        <InputError class="mt-2" :message="form.errors.image" />
                    </div>

                    <div
                        class="col-span-2 flex justify-end mt-10 gap-4 pt-10 border-t border-blue-50"
                    >
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-8 py-4 text-blue-400 font-black uppercase text-[10px] tracking-[0.2em] hover:text-blue-900 transition-all"
                        >
                            Abort
                        </button>
                        <button
                            type="submit"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-black px-12 py-4 rounded-2xl shadow-xl shadow-emerald-100 transition transform active:scale-95 uppercase tracking-widest text-xs"
                        >
                            {{
                                isEditing
                                    ? 'Execute Update'
                                    : 'Finalize Commissioning'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

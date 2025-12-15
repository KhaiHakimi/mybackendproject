<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

// Props passed from the Laravel Controller
defineProps({
    ferries: Array
});

const form = useForm({
    name: '',
    capacity: '',
    operator: ''
});

const submit = () => {
    form.post('/ferries'); // Hits the store method in controller
};
</script>

<template>
    <Head title="Ferries" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Ferry Management
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="mb-8">
                        <input v-model="form.name" type="text" placeholder="Ferry Name" class="border p-2 mr-2 rounded">
                        <input v-model="form.capacity" type="number" placeholder="Capacity" class="border p-2 mr-2 rounded">
                        <input v-model="form.operator" type="text" placeholder="Operator" class="border p-2 mr-2 rounded">
                        <button type="submit" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600 transition">Add Ferry</button>
                    </form>

                    <div class="grid gap-4">
                        <div v-for="ferry in ferries" :key="ferry.id" class="bg-gray-50 p-4 shadow rounded flex justify-between border">
                            <div>
                                <h3 class="font-bold text-lg">{{ ferry.name }}</h3>
                                <p class="text-gray-600">Operator: {{ ferry.operator || 'Unknown' }}</p>
                            </div>
                            <div>
                                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded">Capacity: {{ ferry.capacity }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
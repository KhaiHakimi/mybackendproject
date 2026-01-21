<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Account Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-extrabold leading-tight text-blue-900 uppercase tracking-tighter">
                Marine Account Settings
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
                <div class="bg-white p-8 shadow-xl sm:rounded-[2rem] border border-blue-50 relative overflow-hidden">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl relative z-10"
                    />
                    <svg class="absolute right-0 top-0 w-48 h-48 text-blue-50/20 pointer-events-none transform translate-x-10 -translate-y-10" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71z"/></svg>
                </div>

                <div class="bg-white p-8 shadow-xl sm:rounded-[2rem] border border-blue-50">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div class="bg-rose-50 p-8 shadow-xl sm:rounded-[2rem] border border-rose-100">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

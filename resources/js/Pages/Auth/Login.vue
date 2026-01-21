<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div class="mb-8 text-center">
            <h2 class="text-3xl font-bold text-blue-900">Welcome Back</h2>
            <p class="text-blue-600/60 mt-2">Log in to your FerryCast account</p>
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="animate-fade-in delay-100">
                <InputLabel for="email" value="Email" class="text-blue-900 font-semibold" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full border-blue-100 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-sm"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="you@example.com"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="animate-fade-in delay-200">
                <InputLabel for="password" value="Password" class="text-blue-900 font-semibold" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full border-blue-100 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-sm"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between animate-fade-in delay-300">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" class="text-blue-600 focus:ring-blue-500 rounded" />
                    <span class="ms-2 text-sm text-blue-800">Remember me</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-blue-600 hover:text-blue-800 transition-colors"
                >
                    Forgot password?
                </Link>
            </div>

            <div class="pt-2 animate-fade-in delay-300">
                <PrimaryButton
                    class="w-full justify-center py-4 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 transition-all transform hover:scale-[1.02] rounded-xl text-lg font-bold shadow-lg shadow-blue-200"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Log in
                </PrimaryButton>
            </div>

            <div class="mt-8 text-center border-t border-blue-50 pt-6 animate-fade-in delay-300">
                <p class="text-sm text-blue-800">
                    Don't have an account? 
                    <Link
                        :href="route('register')"
                        class="font-bold text-blue-600 hover:text-blue-800 underline underline-offset-4"
                    >
                        Sign up for free
                    </Link>
                </p>
            </div>
        </form>
    </GuestLayout>
</template>

<style scoped>
.delay-100 { animation-delay: 0.1s; }
.delay-200 { animation-delay: 0.2s; }
.delay-300 { animation-delay: 0.3s; }
</style>


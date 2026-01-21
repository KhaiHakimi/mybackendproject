<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <div class="mb-8 text-center">
            <h2 class="text-3xl font-bold text-blue-900">Join FerryCast</h2>
            <p class="text-blue-600/60 mt-2">Create an account to start your journey</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div class="animate-fade-in delay-75">
                <InputLabel for="name" value="Full Name" class="text-blue-900 font-semibold" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full border-blue-100 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-sm"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="John Doe"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4 animate-fade-in delay-100">
                <InputLabel for="email" value="Email Address" class="text-blue-900 font-semibold" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full border-blue-100 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-sm"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="john@example.com"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4 animate-fade-in delay-150">
                <InputLabel for="password" value="Password" class="text-blue-900 font-semibold" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full border-blue-100 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-sm"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 animate-fade-in delay-200">
                <InputLabel
                    for="password_confirmation"
                    value="Confirm Password"
                    class="text-blue-900 font-semibold"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full border-blue-100 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-sm"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="pt-4 animate-fade-in delay-300">
                <PrimaryButton
                    class="w-full justify-center py-4 bg-yellow-400 hover:bg-yellow-500 text-blue-900 active:bg-yellow-600 transition-all transform hover:scale-[1.02] rounded-xl text-lg font-bold shadow-lg shadow-yellow-100"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Create Account
                </PrimaryButton>
            </div>

            <div class="mt-8 text-center border-t border-blue-50 pt-6 animate-fade-in delay-300">
                <p class="text-sm text-blue-800">
                    Already have an account? 
                    <Link
                        :href="route('login')"
                        class="font-bold text-blue-600 hover:text-blue-800 underline underline-offset-4"
                    >
                        Sign in instead
                    </Link>
                </p>
            </div>
        </form>
    </GuestLayout>
</template>

<style scoped>
.delay-75 { animation-delay: 0.075s; }
.delay-100 { animation-delay: 0.1s; }
.delay-150 { animation-delay: 0.15s; }
.delay-200 { animation-delay: 0.2s; }
.delay-300 { animation-delay: 0.3s; }
</style>


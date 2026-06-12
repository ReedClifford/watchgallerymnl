<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const showPassword = ref(false);

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <section
            class="relative w-full overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-2xl shadow-[#0b3a56]/10 sm:rounded-[2rem]"
            aria-labelledby="login-heading"
        >
            <!-- Soft Background -->
            <div class="pointer-events-none absolute inset-0">
                <div
                    class="absolute inset-0 bg-[radial-gradient(circle_at_15%_0%,rgba(11,58,86,0.13),transparent_34%),radial-gradient(circle_at_95%_12%,rgba(15,23,42,0.08),transparent_34%),linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)]"
                />
            </div>

            <div class="relative z-10">
                <!-- Header -->
                <div
                    class="border-b border-slate-100 bg-gradient-to-br from-white via-[#f8fafc] to-[#eef8fb] px-5 py-6 text-center sm:px-8 sm:py-8"
                >
                    <div
                        class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br from-[#061725] via-[#0b3a56] to-[#071923] text-base font-black text-white shadow-xl shadow-[#0b3a56]/25 sm:h-16 sm:w-16 sm:text-lg"
                        aria-hidden="true"
                    >
                        WG
                    </div>

                    <p
                        class="mt-5 text-[10px] font-black uppercase tracking-[0.32em] text-[#0b3a56]"
                    >
                        Admin Portal
                    </p>

                    <h1
                        id="login-heading"
                        class="mt-2 text-2xl font-black tracking-[-0.045em] text-[#071923] sm:text-3xl"
                    >
                        Welcome back
                    </h1>

                    <p
                        class="mx-auto mt-2 max-w-sm text-sm leading-6 text-slate-500"
                    >
                        Sign in to manage watches, sales, transactions, and your
                        website inventory.
                    </p>
                </div>

                <!-- Form -->
                <div class="px-5 py-6 sm:px-8 sm:py-8">
                    <div
                        v-if="status"
                        role="status"
                        aria-live="polite"
                        class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold leading-6 text-emerald-700"
                    >
                        {{ status }}
                    </div>

                    <form class="space-y-5" @submit.prevent="submit" novalidate>
                        <!-- Email -->
                        <div>
                            <label
                                for="email"
                                class="mb-2 block text-xs font-black uppercase tracking-[0.2em] text-slate-500"
                            >
                                Email Address
                            </label>

                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                autofocus
                                autocomplete="username"
                                inputmode="email"
                                placeholder="Enter your email"
                                :aria-invalid="Boolean(form.errors.email)"
                                :aria-describedby="
                                    form.errors.email
                                        ? 'email-error'
                                        : undefined
                                "
                                class="block min-h-[48px] w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 text-base font-semibold text-[#071923] shadow-sm transition placeholder:text-slate-400 focus:border-[#0b3a56] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[#0b3a56]/12 sm:text-sm"
                                :class="
                                    form.errors.email
                                        ? 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-red-100'
                                        : ''
                                "
                            />

                            <p
                                v-if="form.errors.email"
                                id="email-error"
                                class="mt-2 text-sm font-semibold leading-5 text-red-600"
                            >
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Password -->
                        <div>
                            <div
                                class="mb-2 flex items-center justify-between gap-3"
                            >
                                <label
                                    for="password"
                                    class="block text-xs font-black uppercase tracking-[0.2em] text-slate-500"
                                >
                                    Password
                                </label>

                                <Link
                                    v-if="canResetPassword"
                                    :href="route('password.request')"
                                    class="rounded-lg text-xs font-black text-[#0b3a56] underline-offset-4 transition hover:text-[#071923] hover:underline focus:outline-none focus:ring-4 focus:ring-[#0b3a56]/12"
                                >
                                    Forgot password?
                                </Link>
                            </div>

                            <div class="relative">
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Enter your password"
                                    :aria-invalid="
                                        Boolean(form.errors.password)
                                    "
                                    :aria-describedby="
                                        form.errors.password
                                            ? 'password-error'
                                            : undefined
                                    "
                                    class="block min-h-[48px] w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 pr-20 text-base font-semibold text-[#071923] shadow-sm transition placeholder:text-slate-400 focus:border-[#0b3a56] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[#0b3a56]/12 sm:text-sm"
                                    :class="
                                        form.errors.password
                                            ? 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-red-100'
                                            : ''
                                    "
                                />

                                <button
                                    type="button"
                                    class="absolute inset-y-1 right-1 inline-flex min-w-[64px] items-center justify-center rounded-xl px-3 text-xs font-black text-slate-500 transition hover:bg-white hover:text-[#0b3a56] focus:outline-none focus:ring-4 focus:ring-[#0b3a56]/12"
                                    :aria-label="
                                        showPassword
                                            ? 'Hide password'
                                            : 'Show password'
                                    "
                                    @click="showPassword = !showPassword"
                                >
                                    {{ showPassword ? "Hide" : "Show" }}
                                </button>
                            </div>

                            <p
                                v-if="form.errors.password"
                                id="password-error"
                                class="mt-2 text-sm font-semibold leading-5 text-red-600"
                            >
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <!-- Remember Me -->
                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5"
                        >
                            <label
                                for="remember"
                                class="flex cursor-pointer items-center gap-3"
                            >
                                <input
                                    id="remember"
                                    v-model="form.remember"
                                    type="checkbox"
                                    class="h-5 w-5 rounded border-slate-300 text-[#0b3a56] focus:ring-4 focus:ring-[#0b3a56]/12"
                                />

                                <span class="text-sm font-bold text-slate-600">
                                    Remember me on this device
                                </span>
                            </label>
                        </div>

                        <!-- Submit -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="group inline-flex min-h-[52px] w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] px-5 py-4 text-sm font-black text-white shadow-xl shadow-[#0b3a56]/20 transition hover:brightness-110 focus:outline-none focus:ring-4 focus:ring-[#0b3a56]/20 disabled:cursor-not-allowed disabled:opacity-70 active:scale-[0.99]"
                        >
                            <span
                                v-if="form.processing"
                                class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"
                                aria-hidden="true"
                            />

                            <span>
                                {{
                                    form.processing ? "Signing in..." : "Log in"
                                }}
                            </span>

                            <span
                                v-if="!form.processing"
                                class="transition group-hover:translate-x-1"
                                aria-hidden="true"
                            >
                                →
                            </span>
                        </button>
                    </form>

                    <p
                        class="mt-6 text-center text-xs font-semibold leading-6 text-slate-400"
                    >
                        Authorized access only. Keep your credentials secure.
                    </p>
                </div>
            </div>
        </section>
    </GuestLayout>
</template>

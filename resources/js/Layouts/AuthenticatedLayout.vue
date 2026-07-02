<script setup>
import { ref } from "vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import { Link } from "@inertiajs/vue3";

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100 pb-24 sm:pb-0">
            <nav
                class="sticky top-0 z-50 border-b border-white/10 bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] shadow-xl shadow-slate-900/15"
            >
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div
                        class="flex h-[78px] items-center justify-between gap-3 sm:h-[90px]"
                    >
                        <div class="flex min-w-0 items-center gap-6">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link
                                    :href="route('dashboard')"
                                    class="group flex h-[70px] w-[150px] items-center overflow-visible sm:h-[82px] sm:w-[220px]"
                                    aria-label="Watch Gallery Manila Dashboard"
                                >
                                    <img
                                        src="/images/WGM.png"
                                        alt="Watch Gallery Manila"
                                        class="h-16 w-auto origin-left scale-[1.85] object-contain transition duration-300 group-hover:opacity-85 sm:h-20 sm:scale-[2.05] md:h-24"
                                    />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden items-center sm:flex">
                                <Link
                                    :href="route('dashboard')"
                                    class="rounded-full border border-transparent px-5 py-2.5 text-sm font-black text-white/75 transition hover:border-white/10 hover:bg-white/10 hover:text-white"
                                    :class="
                                        route().current('dashboard')
                                            ? 'border-white/15 bg-white/15 text-white shadow-inner shadow-white/5'
                                            : ''
                                    "
                                >
                                    Dashboard
                                </Link>
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center">
                            <!-- Settings Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-full border border-white/10 bg-white/10 px-4 py-2.5 text-sm font-bold leading-4 text-white/80 shadow-inner shadow-white/5 transition duration-150 ease-in-out hover:border-white/20 hover:bg-white/15 hover:text-white focus:outline-none"
                                            >
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
                                            Profile
                                        </DropdownLink>

                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center rounded-2xl border border-white/10 bg-white/10 p-2.5 text-white/80 transition duration-150 ease-in-out hover:border-white/20 hover:bg-white/15 hover:text-white focus:outline-none"
                                aria-label="Toggle navigation menu"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />

                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    class="h-px bg-gradient-to-r from-transparent via-white/30 to-transparent"
                />

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="border-t border-white/10 bg-[#071923] sm:hidden"
                >
                    <div class="space-y-1 px-4 pb-3 pt-3">
                        <Link
                            :href="route('dashboard')"
                            class="block rounded-2xl border border-transparent px-4 py-3 text-sm font-black text-white/75 transition hover:border-white/10 hover:bg-white/10 hover:text-white"
                            :class="
                                route().current('dashboard')
                                    ? 'border-white/15 bg-white/15 text-white'
                                    : ''
                            "
                        >
                            Dashboard
                        </Link>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="border-t border-white/10 pb-4 pt-4">
                        <div class="px-4">
                            <div class="text-base font-black text-white">
                                {{ $page.props.auth.user.name }}
                            </div>

                            <div
                                class="mt-0.5 text-sm font-medium text-white/55"
                            >
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1 px-4">
                            <Link
                                :href="route('profile.edit')"
                                class="block rounded-2xl border border-transparent px-4 py-3 text-sm font-bold text-white/70 transition hover:border-white/10 hover:bg-white/10 hover:text-white"
                            >
                                Profile
                            </Link>

                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="block w-full rounded-2xl border border-transparent px-4 py-3 text-left text-sm font-bold text-white/70 transition hover:border-white/10 hover:bg-white/10 hover:text-white"
                            >
                                Log Out
                            </Link>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header
                class="border-b border-slate-200 bg-white shadow-sm"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>

            <!-- Mobile Bottom Navigation -->
            <div
                class="fixed inset-x-0 bottom-0 z-50 border-t border-white/15 bg-gradient-to-r from-[#03111f] via-[#075985] to-[#0f172a] px-3 pb-[max(env(safe-area-inset-bottom),0.75rem)] pt-2 shadow-[0_-18px_45px_rgba(2,8,23,0.35)] backdrop-blur-xl sm:hidden"
            >
                <div
                    class="mx-auto grid max-w-md grid-cols-4 items-center rounded-[2rem] border border-white/10 bg-white/10 p-1.5 shadow-inner shadow-white/10"
                >
                    <!-- Dashboard -->
                    <Link
                        :href="route('dashboard')"
                        class="group flex flex-col items-center justify-center gap-1 rounded-[1.5rem] px-2 py-2 text-[10px] font-black uppercase tracking-[0.12em] text-cyan-100/70 transition duration-200 active:scale-95"
                        :class="
                            route().current('dashboard')
                                ? 'bg-white text-[#075985] shadow-lg shadow-cyan-950/20'
                                : 'hover:bg-white/10 hover:text-white'
                        "
                    >
                        <svg
                            class="h-6 w-6 transition duration-200"
                            :class="
                                route().current('dashboard')
                                    ? 'text-[#0284c7]'
                                    : 'text-cyan-200 group-hover:text-white'
                            "
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M3 10.5 12 3l9 7.5" />
                            <path d="M5 10v10h14V10" />
                            <path d="M9 20v-6h6v6" />
                        </svg>
                        <span>Home</span>
                    </Link>

                    <!-- Watches -->
                    <Link
                        v-if="route().has('admin.watches.index')"
                        :href="route('admin.watches.index')"
                        class="group flex flex-col items-center justify-center gap-1 rounded-[1.5rem] px-2 py-2 text-[10px] font-black uppercase tracking-[0.12em] text-cyan-100/70 transition duration-200 active:scale-95"
                        :class="
                            route().current('admin.watches.*')
                                ? 'bg-white text-[#075985] shadow-lg shadow-cyan-950/20'
                                : 'hover:bg-white/10 hover:text-white'
                        "
                    >
                        <svg
                            class="h-6 w-6 transition duration-200"
                            :class="
                                route().current('admin.watches.*')
                                    ? 'text-[#0284c7]'
                                    : 'text-sky-200 group-hover:text-white'
                            "
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <circle cx="12" cy="12" r="7" />
                            <path d="M12 8v4l2.5 2.5" />
                            <path d="M9 2h6" />
                            <path d="M9 22h6" />
                        </svg>
                        <span>Watches</span>
                    </Link>

                    <!-- Sales -->
                    <Link
                        v-if="route().has('admin.sales.index')"
                        :href="route('admin.sales.index')"
                        class="group flex flex-col items-center justify-center gap-1 rounded-[1.5rem] px-2 py-2 text-[10px] font-black uppercase tracking-[0.12em] text-cyan-100/70 transition duration-200 active:scale-95"
                        :class="
                            route().current('admin.sales.*')
                                ? 'bg-white text-[#075985] shadow-lg shadow-cyan-950/20'
                                : 'hover:bg-white/10 hover:text-white'
                        "
                    >
                        <svg
                            class="h-6 w-6 transition duration-200"
                            :class="
                                route().current('admin.sales.*')
                                    ? 'text-[#0284c7]'
                                    : 'text-blue-100 group-hover:text-white'
                            "
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M4 19V5" />
                            <path d="M4 19h16" />
                            <path d="M8 16v-5" />
                            <path d="M12 16V8" />
                            <path d="M16 16v-9" />
                        </svg>
                        <span>Sales</span>
                    </Link>

                    <!-- Profile -->
                    <Link
                        :href="route('profile.edit')"
                        class="group flex flex-col items-center justify-center gap-1 rounded-[1.5rem] px-2 py-2 text-[10px] font-black uppercase tracking-[0.12em] text-cyan-100/70 transition duration-200 active:scale-95"
                        :class="
                            route().current('profile.edit')
                                ? 'bg-white text-[#075985] shadow-lg shadow-cyan-950/20'
                                : 'hover:bg-white/10 hover:text-white'
                        "
                    >
                        <svg
                            class="h-6 w-6 transition duration-200"
                            :class="
                                route().current('profile.edit')
                                    ? 'text-[#0284c7]'
                                    : 'text-cyan-100 group-hover:text-white'
                            "
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <circle cx="12" cy="8" r="4" />
                            <path d="M5 21a7 7 0 0 1 14 0" />
                        </svg>
                        <span>Me</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

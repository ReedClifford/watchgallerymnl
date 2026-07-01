<script setup>
import { computed, ref } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

const sidebarOpen = ref(false);
const page = usePage();

const user = computed(() => page.props.auth?.user ?? null);

const navItems = [
    {
        label: "Dashboard",
        shortLabel: "Home",
        href: route("dashboard"),
        active: route().current("dashboard"),
        icon: "home",
    },
    {
        label: "Watch Inventory",
        shortLabel: "Inventory",
        href: route("admin.watches.index"),
        active: route().current("admin.watches.*"),
        icon: "inventory",
    },
    {
        label: "Transactions",
        shortLabel: "Sales",
        href: route("admin.transactions.index"),
        active: route().current("admin.transactions.*"),
        icon: "sales",
    },
    {
        label: "About Us",
        shortLabel: "About",
        href: route("admin.about-us.edit"),
        active: route().current("admin.about-us.*"),
        icon: "about",
    },
];

const iconPaths = {
    home: ["M3 10.5 12 3l9 7.5", "M5 10v10h14V10", "M9 20v-6h6v6"],
    inventory: ["M4 7l8-4 8 4-8 4-8-4Z", "M4 12l8 4 8-4", "M4 17l8 4 8-4"],
    sales: ["M4 19V5", "M8 17v-6", "M12 17V8", "M16 17v-4", "M20 17V6"],
    about: [
        "M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z",
        "M12 16v-4",
        "M12 8h.01",
    ],
};

const closeSidebar = () => {
    sidebarOpen.value = false;
};
</script>

<template>
    <div class="min-h-screen overflow-x-hidden bg-[#f8fafc] text-[#071923]">
        <!-- Soft White Base Background -->
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_12%_0%,rgba(11,58,86,0.12),transparent_32%),radial-gradient(circle_at_88%_8%,rgba(255,255,255,0.30),transparent_30%),linear-gradient(180deg,#ffffff_0%,#f8fafc_48%,#ffffff_100%)]"
            />
            <div
                class="absolute left-1/2 top-0 h-px w-[80vw] -translate-x-1/2 bg-gradient-to-r from-transparent via-[#0b3a56]/20 to-transparent"
            />
        </div>

        <!-- Mobile Overlay -->
        <Transition name="fade">
            <div
                v-if="sidebarOpen"
                class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden"
                @click="closeSidebar"
            />
        </Transition>

        <!-- Desktop Sidebar / Mobile Drawer -->
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-[82vw] max-w-80 flex-col overflow-hidden border-r border-white/10 bg-gradient-to-b from-[#061725] via-[#0b3a56] to-[#071923] shadow-2xl shadow-slate-950/35 transition-transform duration-300 lg:w-80 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <!-- Sidebar Glow -->
            <div class="pointer-events-none absolute inset-0">
                <div
                    class="absolute -left-20 top-0 h-72 w-72 rounded-full bg-white/10 blur-3xl"
                />
                <div
                    class="absolute right-0 top-28 h-80 w-80 rounded-full bg-white/10 blur-3xl"
                />
                <div
                    class="absolute bottom-0 left-12 h-72 w-72 rounded-full bg-[#0b3a56]/35 blur-3xl"
                />
            </div>

            <!-- Brand -->
            <div class="relative border-b border-white/10 px-5 py-5">
                <Link
                    :href="route('dashboard')"
                    class="group relative block overflow-hidden rounded-[2rem] border border-white/10 bg-white/10 px-4 py-5 shadow-xl shadow-black/20 backdrop-blur-xl transition hover:bg-white/[0.13] active:scale-[0.99]"
                    @click="closeSidebar"
                    aria-label="Watch Gallery Manila Dashboard"
                >
                    <div
                        class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-white/15 blur-2xl"
                    />

                    <div
                        class="relative flex min-h-[108px] items-center justify-center overflow-visible"
                    >
                        <img
                            src="/images/WGM.png"
                            alt="Watch Gallery Manila"
                            class="h-24 w-auto max-w-[245px] origin-center scale-[1.65] object-contain drop-shadow-[0_18px_35px_rgba(0,0,0,0.28)] transition duration-300 group-hover:scale-[1.72] group-hover:opacity-90 sm:h-28"
                        />
                    </div>

                    <p
                        class="relative mt-4 text-center text-xs leading-relaxed text-slate-300"
                    >
                        Admin inventory, transactions, and website content
                        management.
                    </p>
                </Link>
            </div>

            <!-- Navigation -->
            <nav class="relative flex-1 space-y-2 px-4 py-5">
                <Link
                    v-for="item in navItems"
                    :key="item.label"
                    :href="item.href"
                    class="group flex items-center gap-3 rounded-2xl px-4 py-3.5 text-sm font-semibold transition active:scale-[0.98]"
                    :class="
                        item.active
                            ? 'bg-white text-[#071923] shadow-lg shadow-black/20 ring-1 ring-white/20'
                            : 'text-slate-200 hover:bg-white/10 hover:text-white'
                    "
                    @click="closeSidebar"
                >
                    <span
                        class="grid h-9 w-9 place-items-center rounded-xl transition"
                        :class="
                            item.active
                                ? 'bg-[#071923]/10 text-[#071923]'
                                : 'bg-white/10 text-white/75 group-hover:bg-white/15 group-hover:text-white'
                        "
                    >
                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.25"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                v-for="path in iconPaths[item.icon]"
                                :key="path"
                                :d="path"
                            />
                        </svg>
                    </span>

                    <span>{{ item.label }}</span>

                    <span
                        v-if="item.active"
                        class="ml-auto h-2 w-2 rounded-full bg-[#071923]/50"
                    />
                </Link>
            </nav>

            <!-- User Card -->
            <div class="relative border-t border-white/10 p-4">
                <div
                    class="rounded-[1.75rem] border border-white/10 bg-white/10 p-4 shadow-xl shadow-black/10 backdrop-blur-xl"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="grid h-11 w-11 place-items-center rounded-2xl bg-white text-base font-black text-[#071923] shadow-lg shadow-black/20 ring-1 ring-white/20"
                        >
                            {{ user?.name?.charAt(0) || "A" }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-white">
                                {{ user?.name || "Admin" }}
                            </p>
                            <p class="text-xs text-slate-300">Administrator</p>
                        </div>
                    </div>

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="mt-4 w-full rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-bold text-slate-200 transition hover:border-white/20 hover:bg-white/10 hover:text-white active:scale-95"
                    >
                        Logout
                    </Link>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="relative z-10 pb-28 lg:pb-0 lg:pl-80">
            <!-- Topbar -->
            <header
                class="sticky top-0 z-30 border-b border-white/10 bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] shadow-xl shadow-slate-900/15 backdrop-blur-2xl"
            >
                <div
                    class="flex h-[72px] items-center justify-between gap-3 px-4 sm:h-20 sm:px-6 lg:px-8"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <button
                            type="button"
                            class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-white/10 bg-white/10 text-lg text-white shadow-lg shadow-black/20 transition hover:bg-white/15 active:scale-95 lg:hidden"
                            @click="sidebarOpen = true"
                            aria-label="Open admin menu"
                        >
                            ☰
                        </button>

                        <!-- Mobile / Topbar Logo -->
                        <Link
                            :href="route('dashboard')"
                            class="group flex h-[56px] w-[145px] shrink-0 items-center overflow-visible sm:h-[64px] sm:w-[190px] lg:hidden"
                            aria-label="Watch Gallery Manila Dashboard"
                        >
                            <img
                                src="/images/WGM.png"
                                alt="Watch Gallery Manila"
                                class="h-14 w-auto origin-left scale-[1.75] object-contain drop-shadow-[0_12px_24px_rgba(0,0,0,0.28)] transition duration-300 group-hover:opacity-90 sm:h-16 sm:scale-[1.95]"
                            />
                        </Link>

                        <!-- Desktop Topbar Text -->
                        <div class="hidden min-w-0 lg:block">
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.3em] text-white/75 sm:text-xs"
                            >
                                Admin Panel
                            </p>
                            <h2
                                class="truncate text-base font-black text-white sm:text-xl"
                            >
                                Watch Gallery Manila
                            </h2>
                        </div>
                    </div>

                    <div
                        class="hidden items-center gap-3 rounded-2xl border border-white/10 bg-white/10 px-4 py-2 shadow-inner shadow-white/5 backdrop-blur-xl sm:flex"
                    >
                        <div
                            class="grid h-10 w-10 place-items-center rounded-xl bg-white text-sm font-black text-[#071923] ring-1 ring-white/20"
                        >
                            {{ user?.name?.charAt(0) || "A" }}
                        </div>

                        <div class="max-w-52">
                            <p class="truncate text-sm font-bold text-white">
                                {{ user?.name || "Admin" }}
                            </p>
                            <p class="text-xs text-slate-300">Online</p>
                        </div>
                    </div>
                </div>

                <div
                    class="h-px bg-gradient-to-r from-transparent via-white/35 to-transparent"
                />
            </header>

            <!-- Page Header -->
            <section
                v-if="$slots.header"
                class="border-b border-slate-200 bg-white"
            >
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                    <div
                        class="admin-page-header overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-xl shadow-slate-900/5"
                    >
                        <div
                            class="h-1.5 bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923]"
                        />

                        <div
                            class="relative overflow-hidden bg-[radial-gradient(circle_at_12%_0%,rgba(11,58,86,0.10),transparent_30%),radial-gradient(circle_at_90%_10%,rgba(255,255,255,0.35),transparent_28%)] p-5"
                        >
                            <div
                                class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-white/40 blur-2xl"
                            />

                            <div class="relative">
                                <slot name="header" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <main class="relative">
                <slot />
            </main>
        </div>

        <!-- Mobile Bottom Navigation -->
        <nav
            class="fixed left-4 right-4 z-40 rounded-[1.55rem] border border-slate-200/80 bg-white p-1.5 shadow-[0_14px_34px_rgba(15,23,42,0.16)] ring-1 ring-white/80 backdrop-blur-2xl lg:hidden bottom-[calc(env(safe-area-inset-bottom)+0.75rem)]"
        >
            <div class="grid grid-cols-4 gap-1">
                <Link
                    v-for="item in navItems"
                    :key="item.shortLabel"
                    :href="item.href"
                    class="flex min-h-[56px] flex-col items-center justify-center rounded-[1.1rem] px-1 py-1.5 text-[10px] font-black text-[#071923] transition hover:bg-[#eef8fb] active:scale-95"
                    :class="
                        item.active
                            ? 'bg-[#eef8fb] ring-1 ring-[#0084ff]/10'
                            : 'bg-white'
                    "
                >
                    <span
                        class="mb-1 grid h-8 w-8 place-items-center rounded-full bg-white text-[#0084ff] transition"
                        :class="
                            item.active
                                ? 'shadow-sm ring-1 ring-[#0084ff]/15'
                                : 'ring-1 ring-slate-200/70'
                        "
                    >
                        <svg
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                            class="h-[21px] w-[21px]"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.35"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                v-for="path in iconPaths[item.icon]"
                                :key="path"
                                :d="path"
                            />
                        </svg>
                    </span>

                    <span
                        class="truncate text-[9.5px] font-black leading-none text-[#071923]"
                    >
                        {{ item.shortLabel }}
                    </span>
                </Link>
            </div>
        </nav>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/*
    This keeps older page headers readable even if the page itself still uses
    dark-theme classes like text-white and text-slate-400 inside the slot.
*/
.admin-page-header :deep(.text-white) {
    color: #071923 !important;
}

.admin-page-header :deep(.text-slate-400) {
    color: #64748b !important;
}

.admin-page-header :deep(.text-\[\#D6B18A\]) {
    color: #0b3a56 !important;
}
</style>

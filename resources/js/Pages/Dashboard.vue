<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    bestSellers: {
        type: Array,
        default: () => [],
    },
    latestSold: {
        type: Array,
        default: () => [],
    },
});

const formatMoney = (value) => {
    const numericValue = Number(value || 0);

    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        maximumFractionDigits: 0,
    }).format(numericValue);
};

const formatDate = (value) => {
    if (!value) return "No date";

    return new Intl.DateTimeFormat("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
    }).format(new Date(value));
};

const totalWatches = computed(() => Number(props.stats.total_watches || 0));
const availableWatches = computed(() =>
    Number(props.stats.available_watches || 0),
);
const reservedWatches = computed(() =>
    Number(props.stats.reserved_watches || 0),
);
const soldWatches = computed(() => Number(props.stats.sold_watches || 0));
const hiddenWatches = computed(() => Number(props.stats.hidden_watches || 0));
const totalSales = computed(() => Number(props.stats.total_sales || 0));
const totalProfit = computed(() => Number(props.stats.total_profit || 0));

const availableRate = computed(() => {
    if (!totalWatches.value) return 0;

    return Math.round((availableWatches.value / totalWatches.value) * 100);
});

const soldRate = computed(() => {
    if (!totalWatches.value) return 0;

    return Math.round((soldWatches.value / totalWatches.value) * 100);
});

const averageSoldPrice = computed(() => {
    if (!soldWatches.value) return 0;

    return totalSales.value / soldWatches.value;
});

const statCards = computed(() => [
    {
        label: "Total Watches",
        value: totalWatches.value,
        helper: "All inventory records",
        icon: "◉",
        tone: "text-[#071923]",
        card: "border-slate-200 bg-white shadow-[#0b3a56]/10",
        iconClass: "bg-[#eef8fb] text-[#0b3a56]",
        accent: "from-[#061725] via-[#0b3a56] to-[#071923]",
    },
    {
        label: "Available",
        value: availableWatches.value,
        helper: "Ready for sale",
        icon: "✓",
        tone: "text-[#0b3a56]",
        card: "border-[#0b3a56]/10 bg-[#eef8fb] shadow-[#0b3a56]/10",
        iconClass: "bg-white text-[#0b3a56]",
        accent: "from-[#061725] via-[#0b3a56] to-[#071923]",
    },
    {
        label: "Reserved",
        value: reservedWatches.value,
        helper: "Temporarily held",
        icon: "⌁",
        tone: "text-[#b98b63]",
        card: "border-[#d6b18a]/30 bg-[#fff8f0] shadow-[#b98b63]/10",
        iconClass: "bg-white text-[#b98b63]",
        accent: "from-[#f0d8b6] via-[#d6b18a] to-[#b98b63]",
    },
    {
        label: "Sold",
        value: soldWatches.value,
        helper: "Completed sales",
        icon: "◆",
        tone: "text-[#071923]",
        card: "border-slate-200 bg-white shadow-[#0b3a56]/10",
        iconClass: "bg-[#071923] text-white",
        accent: "from-[#061725] via-[#0b3a56] to-[#071923]",
    },
]);

const moneyCards = computed(() => [
    {
        label: "Total Sales",
        value: formatMoney(totalSales.value),
        helper: "All-time sold amount",
        icon: "₱",
        tone: "text-[#071923]",
        card: "border-slate-200 bg-white shadow-[#0b3a56]/10",
        iconClass: "bg-[#eef8fb] text-[#0b3a56]",
        accent: "from-[#061725] via-[#0b3a56] to-[#071923]",
    },
    {
        label: "Total Profit",
        value: formatMoney(totalProfit.value),
        helper: "Sold price minus capital",
        icon: "↗",
        tone: totalProfit.value >= 0 ? "text-[#0b3a56]" : "text-rose-600",
        card:
            totalProfit.value >= 0
                ? "border-[#0b3a56]/10 bg-[#eef8fb] shadow-[#0b3a56]/10"
                : "border-rose-200 bg-rose-50 shadow-rose-500/10",
        iconClass:
            totalProfit.value >= 0
                ? "bg-white text-[#0b3a56]"
                : "bg-white text-rose-600",
        accent:
            totalProfit.value >= 0
                ? "from-[#061725] via-[#0b3a56] to-[#071923]"
                : "from-rose-500 via-rose-600 to-rose-700",
    },
    {
        label: "Average Sold Price",
        value: formatMoney(averageSoldPrice.value),
        helper: "Based on completed sales",
        icon: "≈",
        tone: "text-[#b98b63]",
        card: "border-[#d6b18a]/30 bg-[#fff8f0] shadow-[#b98b63]/10",
        iconClass: "bg-white text-[#b98b63]",
        accent: "from-[#f0d8b6] via-[#d6b18a] to-[#b98b63]",
    },
]);

const topSeller = computed(() => props.bestSellers?.[0] ?? null);
</script>

<template>
    <Head title="Dashboard" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="text-lg font-black text-[#071923] sm:text-xl">
                    Dashboard
                </h2>
                <p class="text-sm text-slate-500">
                    Overview of Watch Gallery Manila inventory and sales
                    performance.
                </p>
            </div>
        </template>

        <div
            class="min-h-screen bg-[#eef3f7] px-4 py-5 text-[#071923] sm:px-6 sm:py-8 lg:px-8"
        >
            <div class="pointer-events-none fixed inset-0 overflow-hidden">
                <div
                    class="absolute inset-0 bg-[radial-gradient(circle_at_12%_0%,rgba(11,58,86,0.16),transparent_34%),radial-gradient(circle_at_90%_8%,rgba(214,177,138,0.18),transparent_30%),linear-gradient(180deg,#f8fbfd_0%,#eef3f7_46%,#f7f9fb_100%)]"
                />
            </div>

            <div class="relative mx-auto max-w-7xl">
                <!-- Hero -->
                <section
                    class="mb-5 overflow-hidden rounded-[2rem] border border-white/10 bg-gradient-to-br from-[#061725] via-[#0b3a56] to-[#071923] shadow-2xl shadow-[#0b3a56]/25"
                >
                    <div class="relative p-5 sm:p-6 lg:p-7">
                        <div
                            class="pointer-events-none absolute -right-14 -top-14 h-56 w-56 rounded-full bg-[#d6b18a]/20 blur-3xl"
                        />
                        <div
                            class="pointer-events-none absolute bottom-0 left-1/3 h-40 w-40 rounded-full bg-cyan-300/10 blur-3xl"
                        />
                        <div
                            class="pointer-events-none absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-[#d6b18a]/60 to-transparent"
                        />

                        <div
                            class="relative grid gap-6 lg:grid-cols-[1.15fr_.85fr] lg:items-center"
                        >
                            <div>
                                <div
                                    class="inline-flex items-center gap-2 rounded-full border border-[#d6b18a]/25 bg-[#d6b18a]/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.28em] text-[#f0d8b6]"
                                >
                                    Business Control Center
                                </div>

                                <h1
                                    class="mt-4 text-3xl font-black tracking-tight text-white sm:text-4xl"
                                >
                                    Watch Gallery Manila
                                </h1>

                                <p
                                    class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-300 sm:text-base"
                                >
                                    Track inventory movement, sales performance,
                                    profit, recent sold watches, and
                                    best-selling models from one premium control
                                    dashboard.
                                </p>

                                <div class="mt-6 flex flex-wrap gap-2">
                                    <span
                                        class="rounded-2xl border border-white/10 bg-white/10 px-4 py-2 text-xs font-bold text-slate-200 backdrop-blur"
                                    >
                                        {{ totalWatches }} total watches
                                    </span>

                                    <span
                                        class="rounded-2xl border border-white/10 bg-white/10 px-4 py-2 text-xs font-bold text-slate-200 backdrop-blur"
                                    >
                                        {{ soldWatches }} sold
                                    </span>

                                    <span
                                        class="rounded-2xl border border-[#d6b18a]/25 bg-[#d6b18a]/10 px-4 py-2 text-xs font-bold text-[#f0d8b6] backdrop-blur"
                                    >
                                        {{ formatMoney(totalSales) }} sales
                                    </span>
                                </div>

                                <div
                                    class="mt-6 grid grid-cols-2 gap-2 sm:flex"
                                >
                                    <Link
                                        :href="route('admin.watches.index')"
                                        class="rounded-2xl bg-gradient-to-r from-[#f0d8b6] via-[#d6b18a] to-[#b98b63] px-5 py-3 text-center text-sm font-black text-[#071923] shadow-lg shadow-black/20 ring-1 ring-white/20 transition hover:brightness-110 active:scale-95"
                                    >
                                        Manage Inventory
                                    </Link>

                                    <Link
                                        :href="
                                            route('admin.transactions.index')
                                        "
                                        class="rounded-2xl border border-white/10 bg-white/10 px-5 py-3 text-center text-sm font-black text-slate-200 shadow-lg shadow-black/10 backdrop-blur transition hover:bg-white/15 hover:text-white active:scale-95"
                                    >
                                        Transactions
                                    </Link>
                                </div>
                            </div>

                            <div
                                class="rounded-[1.75rem] border border-white/10 bg-white/10 p-4 shadow-xl shadow-black/20 backdrop-blur-xl"
                            >
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.25em] text-[#f0d8b6]"
                                >
                                    Financial Snapshot
                                </p>

                                <div class="mt-4 space-y-3">
                                    <div
                                        class="rounded-[1.5rem] border border-white/10 bg-white/10 p-4"
                                    >
                                        <div
                                            class="flex items-center justify-between gap-3"
                                        >
                                            <div>
                                                <p
                                                    class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-300"
                                                >
                                                    Total Sales
                                                </p>
                                                <p
                                                    class="mt-2 text-2xl font-black text-white"
                                                >
                                                    {{
                                                        formatMoney(totalSales)
                                                    }}
                                                </p>
                                            </div>

                                            <div
                                                class="grid h-12 w-12 place-items-center rounded-2xl bg-[#d6b18a]/15 text-xl font-black text-[#f0d8b6]"
                                            >
                                                ₱
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="rounded-[1.5rem] border border-white/10 bg-white/10 p-4"
                                    >
                                        <div
                                            class="flex items-center justify-between gap-3"
                                        >
                                            <div>
                                                <p
                                                    class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-300"
                                                >
                                                    Total Profit
                                                </p>
                                                <p
                                                    class="mt-2 text-2xl font-black"
                                                    :class="
                                                        totalProfit >= 0
                                                            ? 'text-[#f0d8b6]'
                                                            : 'text-rose-300'
                                                    "
                                                >
                                                    {{
                                                        formatMoney(totalProfit)
                                                    }}
                                                </p>
                                            </div>

                                            <div
                                                class="grid h-12 w-12 place-items-center rounded-2xl text-xl font-black"
                                                :class="
                                                    totalProfit >= 0
                                                        ? 'bg-[#d6b18a]/15 text-[#f0d8b6]'
                                                        : 'bg-rose-500/15 text-rose-300'
                                                "
                                            >
                                                ↗
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="rounded-[1.5rem] border border-white/10 bg-white/10 p-4"
                                    >
                                        <div
                                            class="flex items-center justify-between gap-3"
                                        >
                                            <div>
                                                <p
                                                    class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-300"
                                                >
                                                    Sold Rate
                                                </p>
                                                <p
                                                    class="mt-2 text-2xl font-black text-white"
                                                >
                                                    {{ soldRate }}%
                                                </p>
                                            </div>

                                            <div
                                                class="grid h-12 w-12 place-items-center rounded-2xl bg-white/10 text-xl font-black text-[#f0d8b6]"
                                            >
                                                ◆
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Inventory Stats -->
                <section class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <div
                        v-for="card in statCards"
                        :key="card.label"
                        class="relative overflow-hidden rounded-[1.75rem] border p-4 shadow-xl"
                        :class="card.card"
                    >
                        <div
                            class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r"
                            :class="card.accent"
                        />

                        <div
                            class="flex items-start justify-between gap-3 pt-1"
                        >
                            <div>
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400"
                                >
                                    {{ card.label }}
                                </p>

                                <p
                                    class="mt-3 text-3xl font-black"
                                    :class="card.tone"
                                >
                                    {{ card.value }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ card.helper }}
                                </p>
                            </div>

                            <div
                                class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl text-base font-black"
                                :class="card.iconClass"
                            >
                                {{ card.icon }}
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Money Stats -->
                <section class="mb-5 grid gap-3 lg:grid-cols-3">
                    <div
                        v-for="card in moneyCards"
                        :key="card.label"
                        class="relative overflow-hidden rounded-[2rem] border p-5 shadow-xl"
                        :class="card.card"
                    >
                        <div
                            class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r"
                            :class="card.accent"
                        />

                        <div
                            class="flex items-start justify-between gap-4 pt-1"
                        >
                            <div>
                                <p
                                    class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400"
                                >
                                    {{ card.label }}
                                </p>

                                <p
                                    class="mt-3 text-2xl font-black sm:text-3xl"
                                    :class="card.tone"
                                >
                                    {{ card.value }}
                                </p>

                                <p class="mt-2 text-sm text-slate-500">
                                    {{ card.helper }}
                                </p>
                            </div>

                            <div
                                class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl text-xl font-black"
                                :class="card.iconClass"
                            >
                                {{ card.icon }}
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Performance Summary -->
                <section class="mb-5 grid gap-3 lg:grid-cols-3">
                    <div
                        class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-xl shadow-[#0b3a56]/10"
                    >
                        <p
                            class="text-[10px] font-black uppercase tracking-[0.28em] text-[#0b3a56]"
                        >
                            Inventory Health
                        </p>

                        <div class="mt-4 space-y-4">
                            <div>
                                <div
                                    class="mb-2 flex items-center justify-between text-xs font-bold text-slate-500"
                                >
                                    <span>Available Stock</span>
                                    <span>{{ availableRate }}%</span>
                                </div>
                                <div
                                    class="h-2 overflow-hidden rounded-full bg-slate-100"
                                >
                                    <div
                                        class="h-full rounded-full bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923]"
                                        :style="{ width: `${availableRate}%` }"
                                    />
                                </div>
                            </div>

                            <div>
                                <div
                                    class="mb-2 flex items-center justify-between text-xs font-bold text-slate-500"
                                >
                                    <span>Sold Inventory</span>
                                    <span>{{ soldRate }}%</span>
                                </div>
                                <div
                                    class="h-2 overflow-hidden rounded-full bg-slate-100"
                                >
                                    <div
                                        class="h-full rounded-full bg-gradient-to-r from-[#f0d8b6] via-[#d6b18a] to-[#b98b63]"
                                        :style="{ width: `${soldRate}%` }"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-xl shadow-[#0b3a56]/10"
                    >
                        <p
                            class="text-[10px] font-black uppercase tracking-[0.28em] text-[#0b3a56]"
                        >
                            Top Performer
                        </p>

                        <div v-if="topSeller" class="mt-4">
                            <p class="text-lg font-black text-[#071923]">
                                {{ topSeller.brand }} {{ topSeller.model_name }}
                            </p>
                            <p class="mt-1 text-sm text-slate-500">
                                {{
                                    topSeller.reference_number || "No reference"
                                }}
                            </p>

                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <div class="rounded-2xl bg-[#eef8fb] p-3">
                                    <p class="text-[11px] text-slate-500">
                                        Sold
                                    </p>
                                    <p
                                        class="mt-1 text-lg font-black text-[#0b3a56]"
                                    >
                                        {{ topSeller.sold_count }}
                                    </p>
                                </div>

                                <div class="rounded-2xl bg-slate-50 p-3">
                                    <p class="text-[11px] text-slate-500">
                                        Sales
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-black text-[#071923]"
                                    >
                                        {{ formatMoney(topSeller.total_sales) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="mt-4 rounded-2xl border border-dashed border-slate-300 p-5 text-center"
                        >
                            <p class="text-sm font-bold text-slate-500">
                                No top seller yet.
                            </p>
                        </div>
                    </div>

                    <div
                        class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-xl shadow-[#0b3a56]/10"
                    >
                        <p
                            class="text-[10px] font-black uppercase tracking-[0.28em] text-[#0b3a56]"
                        >
                            Other Records
                        </p>

                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <div class="rounded-2xl bg-slate-50 p-3">
                                <p class="text-[11px] text-slate-500">Hidden</p>
                                <p
                                    class="mt-1 text-xl font-black text-slate-600"
                                >
                                    {{ hiddenWatches }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[#fff8f0] p-3">
                                <p class="text-[11px] text-slate-500">
                                    Reserved
                                </p>
                                <p
                                    class="mt-1 text-xl font-black text-[#b98b63]"
                                >
                                    {{ reservedWatches }}
                                </p>
                            </div>

                            <div
                                class="col-span-2 rounded-2xl bg-[#eef8fb] p-3"
                            >
                                <p class="text-[11px] text-slate-500">
                                    Average Sold Price
                                </p>
                                <p
                                    class="mt-1 text-xl font-black text-[#0b3a56]"
                                >
                                    {{ formatMoney(averageSoldPrice) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="grid gap-5 xl:grid-cols-[1.1fr_.9fr]">
                    <!-- Best Sellers -->
                    <section
                        class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-xl shadow-[#0b3a56]/10"
                    >
                        <div
                            class="bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] px-4 py-4 text-white sm:px-5"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <div>
                                    <p
                                        class="text-[10px] font-black uppercase tracking-[0.3em] text-[#f0d8b6]"
                                    >
                                        All Time
                                    </p>
                                    <h3
                                        class="mt-1 text-lg font-black text-white"
                                    >
                                        Best Selling Watches
                                    </h3>
                                </div>

                                <Link
                                    :href="
                                        route('admin.watches.index', {
                                            status: 'sold',
                                        })
                                    "
                                    class="rounded-2xl border border-white/10 bg-white/10 px-4 py-2 text-xs font-black text-slate-100 transition hover:bg-white/15"
                                >
                                    View Sold
                                </Link>
                            </div>
                        </div>

                        <div class="p-4 sm:p-5">
                            <div v-if="bestSellers.length" class="space-y-3">
                                <div
                                    v-for="(watch, index) in bestSellers"
                                    :key="`${watch.brand}-${watch.model_name}-${watch.reference_number}`"
                                    class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#0b3a56]/25 hover:bg-[#f8fafc]"
                                >
                                    <div
                                        class="flex items-start justify-between gap-3"
                                    >
                                        <div class="min-w-0">
                                            <div
                                                class="flex items-center gap-3"
                                            >
                                                <span
                                                    class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-[#f0d8b6] via-[#d6b18a] to-[#b98b63] text-xs font-black text-[#071923] shadow-lg shadow-[#b98b63]/10"
                                                >
                                                    {{ index + 1 }}
                                                </span>

                                                <div class="min-w-0">
                                                    <p
                                                        class="truncate text-sm font-black text-[#071923]"
                                                    >
                                                        {{ watch.brand }}
                                                        {{ watch.model_name }}
                                                    </p>
                                                    <p
                                                        class="text-xs text-slate-500"
                                                    >
                                                        {{
                                                            watch.reference_number ||
                                                            "No reference"
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="shrink-0 text-right">
                                            <p
                                                class="text-lg font-black text-[#0b3a56]"
                                            >
                                                {{ watch.sold_count }}
                                            </p>
                                            <p
                                                class="text-[11px] text-slate-500"
                                            >
                                                sold
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-4 grid grid-cols-2 gap-2">
                                        <div
                                            class="rounded-2xl bg-slate-50 p-3"
                                        >
                                            <p
                                                class="text-[11px] text-slate-500"
                                            >
                                                Sales
                                            </p>
                                            <p
                                                class="mt-1 text-sm font-black text-[#071923]"
                                            >
                                                {{
                                                    formatMoney(
                                                        watch.total_sales,
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-2xl bg-[#eef8fb] p-3"
                                        >
                                            <p
                                                class="text-[11px] text-slate-500"
                                            >
                                                Profit
                                            </p>
                                            <p
                                                class="mt-1 text-sm font-black"
                                                :class="
                                                    watch.total_profit >= 0
                                                        ? 'text-[#0b3a56]'
                                                        : 'text-rose-600'
                                                "
                                            >
                                                {{
                                                    formatMoney(
                                                        watch.total_profit,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-else
                                class="rounded-3xl border border-dashed border-slate-300 p-8 text-center"
                            >
                                <p class="font-black text-[#071923]">
                                    No sold watches yet.
                                </p>
                                <p class="mt-1 text-sm text-slate-500">
                                    Best sellers will appear once watches are
                                    marked as sold.
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- Latest Sold -->
                    <section
                        class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-xl shadow-[#0b3a56]/10"
                    >
                        <div
                            class="bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] px-4 py-4 text-white sm:px-5"
                        >
                            <p
                                class="text-[10px] font-black uppercase tracking-[0.3em] text-[#f0d8b6]"
                            >
                                Recent
                            </p>
                            <h3 class="mt-1 text-lg font-black text-white">
                                Latest Sold Watches
                            </h3>
                        </div>

                        <div class="p-4 sm:p-5">
                            <div v-if="latestSold.length" class="space-y-3">
                                <div
                                    v-for="watch in latestSold"
                                    :key="watch.id"
                                    class="flex gap-3 rounded-3xl border border-slate-200 bg-white p-3 shadow-sm transition hover:border-[#0b3a56]/25 hover:bg-[#f8fafc]"
                                >
                                    <div
                                        class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl bg-slate-100"
                                    >
                                        <img
                                            v-if="watch.image_url"
                                            :src="watch.image_url"
                                            :alt="watch.model_name"
                                            class="h-full w-full object-cover"
                                        />

                                        <div
                                            v-else
                                            class="grid h-full w-full place-items-center text-xs text-slate-400"
                                        >
                                            No Image
                                        </div>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="truncate text-sm font-black text-[#071923]"
                                        >
                                            {{ watch.brand }}
                                            {{ watch.model_name }}
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            {{
                                                watch.reference_number ||
                                                "No reference"
                                            }}
                                        </p>

                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <span
                                                class="rounded-full bg-[#eef8fb] px-2.5 py-1 text-[11px] font-bold text-[#0b3a56]"
                                            >
                                                {{
                                                    formatMoney(
                                                        watch.sold_price,
                                                    )
                                                }}
                                            </span>

                                            <span
                                                class="rounded-full px-2.5 py-1 text-[11px] font-bold"
                                                :class="
                                                    watch.profit >= 0
                                                        ? 'bg-[#fff8f0] text-[#b98b63]'
                                                        : 'bg-rose-50 text-rose-600'
                                                "
                                            >
                                                {{ formatMoney(watch.profit) }}
                                                profit
                                            </span>
                                        </div>

                                        <p class="mt-2 text-xs text-slate-500">
                                            {{ formatDate(watch.date_sold) }}
                                            <span v-if="watch.buyer_name">
                                                · {{ watch.buyer_name }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-else
                                class="rounded-3xl border border-dashed border-slate-300 p-8 text-center"
                            >
                                <p class="font-black text-[#071923]">
                                    No sales yet.
                                </p>
                                <p class="mt-1 text-sm text-slate-500">
                                    Latest sold watches will appear here.
                                </p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

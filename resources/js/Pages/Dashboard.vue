<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed } from "vue";
import VueApexCharts from "vue3-apexcharts";

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
    analytics: {
        type: Object,
        default: () => ({
            today_valid_visits: 0,
            today_unique_visitors: 0,
            today_engaged_visits: 0,
            average_duration_seconds: 0,
            total_valid_visits: 0,
            daily_visits: [],
            top_watches: [],
            device_breakdown: [],
        }),
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

const formatDuration = (seconds) => {
    const totalSeconds = Number(seconds || 0);

    if (!Number.isFinite(totalSeconds) || totalSeconds <= 0) {
        return "0s";
    }

    const minutes = Math.floor(totalSeconds / 60);
    const remainingSeconds = Math.round(totalSeconds % 60);

    if (minutes <= 0) {
        return `${remainingSeconds}s`;
    }

    if (minutes < 60) {
        return `${minutes}m ${remainingSeconds}s`;
    }

    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    return `${hours}h ${remainingMinutes}m`;
};

const totalWatches = computed(() => Number(props.stats.total_watches || 0));
const availableWatches = computed(() =>
    Number(props.stats.available_watches || 0),
);
const reservedWatches = computed(() =>
    Number(props.stats.reserved_watches || 0),
);
const soldWatches = computed(() => Number(props.stats.sold_watches || 0));
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
        tone: "text-[#071923]",
        card: "border-slate-200 bg-white shadow-[#0b3a56]/10",
        iconClass: "bg-slate-100 text-[#071923]",
        accent: "from-slate-200 via-slate-300 to-slate-400",
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
        tone: "text-[#071923]",
        card: "border-slate-200 bg-white shadow-[#0b3a56]/10",
        iconClass: "bg-slate-100 text-[#071923]",
        accent: "from-slate-200 via-slate-300 to-slate-400",
    },
]);

const analytics = computed(() => props.analytics || {});

const todayValidVisits = computed(() =>
    Number(analytics.value.today_valid_visits || 0),
);

const todayUniqueVisitors = computed(() =>
    Number(analytics.value.today_unique_visitors || 0),
);

const todayEngagedVisits = computed(() =>
    Number(analytics.value.today_engaged_visits || 0),
);

const averageDurationSeconds = computed(() =>
    Number(analytics.value.average_duration_seconds || 0),
);

const totalValidVisits = computed(() =>
    Number(analytics.value.total_valid_visits || 0),
);

const engagementRate = computed(() => {
    if (!todayValidVisits.value) return 0;

    return Math.round(
        (todayEngagedVisits.value / todayValidVisits.value) * 100,
    );
});

const uniqueVisitorRate = computed(() => {
    if (!todayValidVisits.value) return 0;

    return Math.round(
        (todayUniqueVisitors.value / todayValidVisits.value) * 100,
    );
});

const dailyVisits = computed(() => analytics.value.daily_visits || []);
const topWatches = computed(() => analytics.value.top_watches || []);
const deviceBreakdown = computed(() => analytics.value.device_breakdown || []);

const chartFontFamily =
    "Figtree, Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";

const truncateChartLabel = (value, limit = 28) => {
    const text = String(value || "").trim();

    if (text.length <= limit) {
        return text;
    }

    return `${text.slice(0, limit - 1)}…`;
};

const dailyVisitsChartSeries = computed(() => [
    {
        name: "Valid Visits",
        data: dailyVisits.value.map((day) => Number(day.visits || 0)),
    },
    {
        name: "Unique Visitors",
        data: dailyVisits.value.map((day) => Number(day.unique_visitors || 0)),
    },
]);

const dailyVisitsChartOptions = computed(() => ({
    chart: {
        type: "area",
        toolbar: { show: false },
        zoom: { enabled: false },
        fontFamily: chartFontFamily,
        parentHeightOffset: 0,
    },
    colors: ["#0b3a56", "#38bdf8"],
    dataLabels: { enabled: false },
    stroke: {
        curve: "smooth",
        width: 3,
    },
    fill: {
        type: "gradient",
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.32,
            opacityTo: 0.04,
            stops: [0, 90, 100],
        },
    },
    grid: {
        borderColor: "#e2e8f0",
        strokeDashArray: 4,
        padding: { left: 8, right: 18 },
    },
    markers: {
        size: 4,
        strokeWidth: 0,
        hover: { size: 6 },
    },
    xaxis: {
        categories: dailyVisits.value.map((day) => day.label || day.date),
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            style: {
                colors: "#64748b",
                fontSize: "11px",
                fontWeight: 800,
            },
        },
    },
    yaxis: {
        min: 0,
        forceNiceScale: true,
        labels: {
            formatter: (value) => Math.round(Number(value || 0)),
            style: {
                colors: "#64748b",
                fontSize: "11px",
                fontWeight: 800,
            },
        },
    },
    legend: {
        position: "top",
        horizontalAlign: "right",
        fontWeight: 900,
        markers: { radius: 12 },
    },
    tooltip: {
        theme: "light",
        y: {
            formatter: (value) => `${Math.round(Number(value || 0))} visits`,
        },
    },
    noData: {
        text: "No traffic data yet",
    },
    responsive: [
        {
            breakpoint: 640,
            options: {
                chart: { height: 245 },
                stroke: { width: 2 },
                markers: { size: 3 },
                legend: {
                    position: "bottom",
                    horizontalAlign: "center",
                    fontSize: "11px",
                },
                grid: {
                    padding: { left: 0, right: 6 },
                },
                xaxis: {
                    labels: {
                        rotate: -20,
                        style: { fontSize: "10px" },
                    },
                },
                yaxis: {
                    labels: {
                        style: { fontSize: "10px" },
                    },
                },
            },
        },
    ],
}));

const qualityChartSeries = computed(() => [
    engagementRate.value,
    uniqueVisitorRate.value,
]);

const qualityChartOptions = computed(() => ({
    chart: {
        type: "radialBar",
        toolbar: { show: false },
        fontFamily: chartFontFamily,
        parentHeightOffset: 0,
    },
    labels: ["Engaged visits", "Unique visits"],
    colors: ["#0b3a56", "#38bdf8"],
    stroke: { lineCap: "round" },
    plotOptions: {
        radialBar: {
            hollow: { size: "42%" },
            track: { background: "#e2e8f0" },
            dataLabels: {
                name: {
                    fontSize: "12px",
                    fontWeight: 900,
                    color: "#64748b",
                },
                value: {
                    fontSize: "22px",
                    fontWeight: 950,
                    color: "#071923",
                    formatter: (value) => `${Math.round(Number(value || 0))}%`,
                },
                total: {
                    show: true,
                    label: "Quality",
                    color: "#64748b",
                    formatter: () => `${engagementRate.value}%`,
                },
            },
        },
    },
    legend: {
        show: true,
        position: "bottom",
        fontWeight: 900,
        markers: { radius: 12 },
    },
    responsive: [
        {
            breakpoint: 640,
            options: {
                chart: { height: 225 },
                plotOptions: {
                    radialBar: {
                        hollow: { size: "36%" },
                        dataLabels: {
                            value: { fontSize: "18px" },
                            total: { show: true },
                        },
                    },
                },
                legend: { fontSize: "11px" },
            },
        },
    ],
}));

const deviceChartSeries = computed(() =>
    deviceBreakdown.value.map((device) => Number(device.visits || 0)),
);

const deviceChartOptions = computed(() => ({
    chart: {
        type: "donut",
        toolbar: { show: false },
        fontFamily: chartFontFamily,
        parentHeightOffset: 0,
    },
    labels: deviceBreakdown.value.map((device) =>
        String(device.device || "Unknown")
            .replace(/_/g, " ")
            .replace(/\b\w/g, (letter) => letter.toUpperCase()),
    ),
    colors: ["#0b3a56", "#38bdf8", "#071923", "#94a3b8"],
    stroke: {
        colors: ["#ffffff"],
        width: 4,
    },
    dataLabels: {
        enabled: true,
        formatter: (value) => `${Math.round(Number(value || 0))}%`,
        style: {
            fontSize: "12px",
            fontWeight: 900,
        },
    },
    plotOptions: {
        pie: {
            donut: {
                size: "68%",
                labels: {
                    show: true,
                    name: {
                        show: true,
                        fontWeight: 900,
                        color: "#64748b",
                    },
                    value: {
                        show: true,
                        fontWeight: 950,
                        color: "#071923",
                        formatter: (value) => `${value} visits`,
                    },
                    total: {
                        show: true,
                        label: "Today",
                        color: "#64748b",
                        formatter: () => `${todayValidVisits.value}`,
                    },
                },
            },
        },
    },
    legend: {
        position: "bottom",
        fontWeight: 900,
        markers: { radius: 12 },
    },
    tooltip: {
        y: {
            formatter: (value) => `${value} visits`,
        },
    },
    noData: {
        text: "No device data yet",
    },
    responsive: [
        {
            breakpoint: 640,
            options: {
                chart: { height: 230 },
                dataLabels: { enabled: false },
                legend: {
                    position: "bottom",
                    fontSize: "11px",
                },
                plotOptions: {
                    pie: { donut: { size: "72%" } },
                },
            },
        },
    ],
}));

const topWatchesChartSeries = computed(() => [
    {
        name: "Views",
        data: topWatches.value.map((watch) => Number(watch.visits || 0)),
    },
]);

const topWatchesChartOptions = computed(() => ({
    chart: {
        type: "bar",
        toolbar: { show: false },
        fontFamily: chartFontFamily,
        parentHeightOffset: 0,
    },
    colors: ["#0b3a56"],
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 8,
            barHeight: "58%",
        },
    },
    dataLabels: {
        enabled: true,
        formatter: (value) => Math.round(Number(value || 0)),
        style: {
            fontSize: "11px",
            fontWeight: 900,
        },
    },
    grid: {
        borderColor: "#e2e8f0",
        strokeDashArray: 4,
    },
    xaxis: {
        categories: topWatches.value.map((watch) =>
            truncateChartLabel(watch.page_title || watch.page_path, 34),
        ),
        labels: {
            formatter: (value) => Math.round(Number(value || 0)),
            style: {
                colors: "#64748b",
                fontSize: "11px",
                fontWeight: 800,
            },
        },
    },
    yaxis: {
        labels: {
            style: {
                colors: "#071923",
                fontSize: "11px",
                fontWeight: 900,
            },
        },
    },
    tooltip: {
        y: {
            formatter: (value) => `${value} views`,
        },
    },
    noData: {
        text: "No viewed watches yet",
    },
    responsive: [
        {
            breakpoint: 640,
            options: {
                chart: { height: 260 },
                plotOptions: {
                    bar: {
                        borderRadius: 6,
                        barHeight: "50%",
                    },
                },
                dataLabels: {
                    enabled: false,
                },
                xaxis: {
                    labels: {
                        style: { fontSize: "10px" },
                    },
                },
                yaxis: {
                    labels: {
                        maxWidth: 120,
                        style: { fontSize: "10px" },
                    },
                },
            },
        },
    ],
}));

const analyticsCards = computed(() => [
    {
        label: "Today Visits",
        value: todayValidVisits.value,
        helper: "Valid visits after 5 seconds",
        icon: "↗",
        tone: "text-[#071923]",
        card: "border-slate-200 bg-white shadow-[#0b3a56]/10",
        iconClass: "bg-[#eef8fb] text-[#0b3a56]",
        accent: "from-[#061725] via-[#0b3a56] to-[#071923]",
    },
    {
        label: "Unique Visitors",
        value: todayUniqueVisitors.value,
        helper: "Unique browser/device today",
        icon: "◎",
        tone: "text-[#0b3a56]",
        card: "border-[#0b3a56]/10 bg-[#eef8fb] shadow-[#0b3a56]/10",
        iconClass: "bg-white text-[#0b3a56]",
        accent: "from-[#061725] via-[#0b3a56] to-[#071923]",
    },
    {
        label: "Avg. Time",
        value: formatDuration(averageDurationSeconds.value),
        helper: "Average active page time",
        icon: "◷",
        tone: "text-[#071923]",
        card: "border-slate-200 bg-white shadow-[#0b3a56]/10",
        iconClass: "bg-slate-100 text-[#071923]",
        accent: "from-slate-200 via-slate-300 to-slate-400",
    },
    {
        label: "Engaged",
        value: todayEngagedVisits.value,
        helper: "10s+ or clicked/scrolled",
        icon: "◆",
        tone: "text-[#071923]",
        card: "border-slate-200 bg-white shadow-[#0b3a56]/10",
        iconClass: "bg-[#071923] text-white",
        accent: "from-[#061725] via-[#0b3a56] to-[#071923]",
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
                    class="absolute inset-0 bg-[radial-gradient(circle_at_12%_0%,rgba(11,58,86,0.16),transparent_34%),radial-gradient(circle_at_90%_8%,rgba(255,255,255,0.32),transparent_30%),linear-gradient(180deg,#f8fbfd_0%,#eef3f7_46%,#f7f9fb_100%)]"
                />
            </div>

            <div class="relative mx-auto max-w-7xl">
                <!-- Hero -->
                <section
                    class="mb-5 overflow-hidden rounded-[2rem] border border-white/10 bg-gradient-to-br from-[#061725] via-[#0b3a56] to-[#071923] shadow-2xl shadow-[#0b3a56]/25"
                >
                    <div class="relative p-5 sm:p-6 lg:p-7">
                        <div
                            class="pointer-events-none absolute -right-14 -top-14 h-56 w-56 rounded-full bg-white/10 blur-3xl"
                        />
                        <div
                            class="pointer-events-none absolute bottom-0 left-1/3 h-40 w-40 rounded-full bg-cyan-300/10 blur-3xl"
                        />
                        <div
                            class="pointer-events-none absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-white/35 to-transparent"
                        />

                        <div
                            class="relative grid gap-6 lg:grid-cols-[1.15fr_.85fr] lg:items-center"
                        >
                            <div>
                                <div
                                    class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.28em] text-white/80"
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
                                        class="rounded-2xl border border-white/10 bg-white/10 px-4 py-2 text-xs font-bold text-white backdrop-blur"
                                    >
                                        {{ formatMoney(totalSales) }} sales
                                    </span>
                                </div>

                                <div
                                    class="mt-6 grid grid-cols-2 gap-2 sm:flex"
                                >
                                    <Link
                                        :href="route('admin.watches.index')"
                                        class="rounded-2xl bg-white px-5 py-3 text-center text-sm font-black text-[#071923] shadow-lg shadow-black/20 ring-1 ring-white/20 transition hover:bg-white/90 active:scale-95"
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
                                    class="text-[10px] font-black uppercase tracking-[0.25em] text-white/80"
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
                                                class="grid h-12 w-12 place-items-center rounded-2xl bg-white/10 text-xl font-black text-white"
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
                                                            ? 'text-white'
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
                                                        ? 'bg-white/10 text-white'
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
                                                class="grid h-12 w-12 place-items-center rounded-2xl bg-white/10 text-xl font-black text-white"
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

                <!-- Mobile-friendly Dashboard Navigation -->
                <nav
                    class="sticky top-3 z-30 mb-5 overflow-x-auto rounded-[1.35rem] border border-white/80 bg-white/90 p-2 shadow-xl shadow-[#0b3a56]/10 backdrop-blur-2xl sm:top-4"
                >
                    <div class="flex min-w-max gap-2">
                        <a
                            href="#dashboard-analytics"
                            class="rounded-full bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] px-4 py-2.5 text-xs font-black text-white shadow-lg shadow-[#0b3a56]/15 active:scale-95"
                        >
                            Analytics
                        </a>
                        <a
                            href="#dashboard-inventory"
                            class="rounded-full border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-600 transition hover:border-[#0b3a56]/30 hover:bg-[#eef8fb] hover:text-[#0b3a56] active:scale-95"
                        >
                            Inventory
                        </a>
                        <a
                            href="#dashboard-sales"
                            class="rounded-full border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-600 transition hover:border-[#0b3a56]/30 hover:bg-[#eef8fb] hover:text-[#0b3a56] active:scale-95"
                        >
                            Sales
                        </a>
                        <a
                            href="#dashboard-performance"
                            class="rounded-full border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-600 transition hover:border-[#0b3a56]/30 hover:bg-[#eef8fb] hover:text-[#0b3a56] active:scale-95"
                        >
                            Performance
                        </a>
                    </div>
                </nav>

                <!-- Website Analytics: Top Overview -->
                <section
                    id="dashboard-analytics"
                    class="mb-5 scroll-mt-24 overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl shadow-[#0b3a56]/10"
                >
                    <div
                        class="relative overflow-hidden bg-gradient-to-br from-[#061725] via-[#0b3a56] to-[#071923] p-5 text-white sm:p-6 lg:p-7"
                    >
                        <div
                            class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-cyan-300/10 blur-3xl"
                        />
                        <div
                            class="pointer-events-none absolute bottom-0 left-10 h-40 w-40 rounded-full bg-white/10 blur-3xl"
                        />

                        <div
                            class="relative flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
                        >
                            <div>
                                <div
                                    class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.28em] text-white/75 backdrop-blur"
                                >
                                    Website Analytics
                                </div>

                                <h3
                                    class="mt-4 max-w-2xl text-2xl font-black tracking-tight text-white sm:text-3xl"
                                >
                                    Traffic, interest, and visitor behavior
                                </h3>

                                <p
                                    class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-300"
                                >
                                    Valid visits are counted after 5 seconds.
                                    Engaged visits mean the visitor stayed 10+
                                    seconds or interacted with the page.
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-2 sm:flex">
                                <div
                                    class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur"
                                >
                                    <p
                                        class="text-[10px] font-black uppercase tracking-[0.2em] text-white/55"
                                    >
                                        All-time valid
                                    </p>
                                    <p
                                        class="mt-1 text-xl font-black text-white"
                                    >
                                        {{ totalValidVisits }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur"
                                >
                                    <p
                                        class="text-[10px] font-black uppercase tracking-[0.2em] text-white/55"
                                    >
                                        Engaged rate
                                    </p>
                                    <p
                                        class="mt-1 text-xl font-black text-white"
                                    >
                                        {{ engagementRate }}%
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 sm:p-5 lg:p-6">
                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                            <div
                                v-for="card in analyticsCards"
                                :key="card.label"
                                class="relative overflow-hidden rounded-[1.5rem] border p-4 shadow-lg"
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
                                            class="mt-3 text-2xl font-black sm:text-3xl"
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
                        </div>

                        <div
                            class="mt-5 grid gap-5 xl:grid-cols-[1.15fr_.85fr]"
                        >
                            <!-- 7-day Traffic Chart -->
                            <section
                                class="rounded-[1.75rem] border border-slate-200 bg-[#f8fafc] p-4 shadow-sm sm:p-5"
                            >
                                <div
                                    class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between"
                                >
                                    <div>
                                        <p
                                            class="text-[10px] font-black uppercase tracking-[0.28em] text-[#0b3a56]"
                                        >
                                            Traffic Trend
                                        </p>
                                        <h4
                                            class="mt-1 text-lg font-black text-[#071923]"
                                        >
                                            Last 7 days valid visits
                                        </h4>
                                    </div>

                                    <span
                                        class="rounded-full bg-white px-3 py-1.5 text-[11px] font-black text-slate-500 shadow-sm"
                                    >
                                        7-day view
                                    </span>
                                </div>

                                <div
                                    class="mt-5 overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white p-2 sm:p-4"
                                >
                                    <VueApexCharts
                                        type="area"
                                        height="270"
                                        :options="dailyVisitsChartOptions"
                                        :series="dailyVisitsChartSeries"
                                    />
                                </div>
                            </section>

                            <!-- Quality + Device Charts -->
                            <section class="grid gap-5">
                                <div
                                    class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5"
                                >
                                    <p
                                        class="text-[10px] font-black uppercase tracking-[0.28em] text-[#0b3a56]"
                                    >
                                        Visit Quality
                                    </p>
                                    <h4
                                        class="mt-1 text-lg font-black text-[#071923]"
                                    >
                                        Engaged vs unique visits
                                    </h4>
                                    <p
                                        class="mt-1 text-xs font-semibold leading-relaxed text-slate-500"
                                    >
                                        Engaged rate shows how many valid visits
                                        stayed longer or interacted. Unique
                                        visits show how much of today's traffic
                                        came from different browsers.
                                    </p>

                                    <div
                                        class="mt-4 rounded-[1.5rem] border border-slate-100 bg-slate-50 p-2 sm:p-3"
                                    >
                                        <VueApexCharts
                                            type="radialBar"
                                            height="230"
                                            :options="qualityChartOptions"
                                            :series="qualityChartSeries"
                                        />
                                    </div>
                                </div>

                                <div
                                    class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5"
                                >
                                    <div
                                        class="flex items-center justify-between gap-3"
                                    >
                                        <div>
                                            <p
                                                class="text-[10px] font-black uppercase tracking-[0.28em] text-[#0b3a56]"
                                            >
                                                Devices
                                            </p>
                                            <h4
                                                class="mt-1 text-lg font-black text-[#071923]"
                                            >
                                                Mobile vs desktop
                                            </h4>
                                        </div>
                                    </div>

                                    <div
                                        class="mt-4 overflow-hidden rounded-[1.5rem] border border-slate-100 bg-slate-50 p-2 sm:p-3"
                                    >
                                        <VueApexCharts
                                            type="donut"
                                            height="230"
                                            :options="deviceChartOptions"
                                            :series="deviceChartSeries"
                                        />
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="mt-5">
                            <!-- Most Viewed Watches -->
                            <section
                                class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5"
                            >
                                <div
                                    class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <p
                                            class="text-[10px] font-black uppercase tracking-[0.28em] text-[#0b3a56]"
                                        >
                                            Product Interest
                                        </p>
                                        <h4
                                            class="mt-1 text-lg font-black text-[#071923]"
                                        >
                                            Most viewed watches
                                        </h4>
                                        <p
                                            class="mt-1 text-xs font-semibold text-slate-500"
                                        >
                                            Shows which watch detail pages get
                                            the most valid views.
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="mt-4 overflow-hidden rounded-[1.5rem] border border-slate-100 bg-slate-50 p-2 sm:p-3"
                                >
                                    <VueApexCharts
                                        type="bar"
                                        height="300"
                                        :options="topWatchesChartOptions"
                                        :series="topWatchesChartSeries"
                                    />
                                </div>
                            </section>
                        </div>
                    </div>
                </section>

                <!-- Inventory Stats -->
                <section
                    id="dashboard-inventory"
                    class="mb-5 grid scroll-mt-24 grid-cols-2 gap-3 lg:grid-cols-4"
                >
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
                <section
                    id="dashboard-sales"
                    class="mb-5 grid scroll-mt-24 gap-3 lg:grid-cols-3"
                >
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
                <section
                    id="dashboard-performance"
                    class="mb-5 grid scroll-mt-24 gap-3 lg:grid-cols-3"
                >
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
                                        class="h-full rounded-full bg-gradient-to-r from-slate-400 via-slate-500 to-slate-700"
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
                                <p class="text-[11px] text-slate-500">
                                    Reserved
                                </p>
                                <p
                                    class="mt-1 text-xl font-black text-[#071923]"
                                >
                                    {{ reservedWatches }}
                                </p>
                            </div>

                            <div class="rounded-2xl bg-[#eef8fb] p-3">
                                <p class="text-[11px] text-slate-500">Sold</p>
                                <p
                                    class="mt-1 text-xl font-black text-[#0b3a56]"
                                >
                                    {{ soldWatches }}
                                </p>
                            </div>

                            <div class="col-span-2 rounded-2xl bg-slate-50 p-3">
                                <p class="text-[11px] text-slate-500">
                                    Average Sold Price
                                </p>
                                <p
                                    class="mt-1 text-xl font-black text-[#071923]"
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
                                        class="text-[10px] font-black uppercase tracking-[0.3em] text-white/80"
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
                                                    class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-[#071923] text-xs font-black text-white shadow-lg shadow-[#0b3a56]/10"
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
                                class="text-[10px] font-black uppercase tracking-[0.3em] text-white/80"
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
                                                        ? 'bg-slate-100 text-slate-700'
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

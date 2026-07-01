<script setup>
import { computed, ref, watch } from "vue";
import { Head, Link } from "@inertiajs/vue3";

const navbarLogo = "/images/WGM.png";

const props = defineProps({
    watch: {
        type: Object,
        required: true,
    },
    otherWatches: {
        type: Array,
        default: () => [],
    },
});

const activeImageIndex = ref(0);
const otherWatchesStrip = ref(null);

const compactOtherWatches = computed(() => {
    return props.otherWatches.slice(0, 8);
});

const displayWatchName = computed(() => {
    return (
        props.watch.display_name ||
        props.watch.model_name ||
        props.watch.name ||
        props.watch.title ||
        props.watch.reference_number ||
        "Watch Details"
    );
});

const inclusionText = computed(() => {
    return String(
        props.watch.box_papers ||
            props.watch.box_and_papers ||
            props.watch.inclusions ||
            "",
    ).trim();
});

const watchDescription = computed(() => {
    return String(props.watch.description || "").trim();
});
const images = computed(() => {
    if (Array.isArray(props.watch.images) && props.watch.images.length) {
        return props.watch.images;
    }

    if (props.watch.image_url) {
        return [
            {
                id: "primary",
                image_url: props.watch.image_url,
                is_primary: true,
            },
        ];
    }

    return [];
});

const activeImage = computed(() => {
    return images.value[activeImageIndex.value] ?? null;
});

const actualWatchPrice = computed(() => {
    const candidates = [
        props.watch.actual_price,
        props.watch.discounted_price,
        props.watch.selling_price,
        props.watch.price,
        props.watch.display_price,
    ];

    return candidates.find((value) => Number(value) > 0) ?? null;
});

const suggestedSrp = computed(() => {
    const amount = Number(props.watch.suggested_srp);

    return Number.isFinite(amount) && amount > 0 ? amount : null;
});

const otherWatchPrice = (item) => {
    const candidates = [
        item.actual_price,
        item.display_price,
        item.discounted_price,
        item.selling_price,
        item.price,
    ];

    return candidates.find((value) => Number(value) > 0) ?? null;
};

const otherWatchSrp = (item) => {
    const amount = Number(item?.suggested_srp);

    return Number.isFinite(amount) && amount > 0 ? amount : null;
};

const conditionLabel = (condition) => {
    const normalized = String(condition || "")
        .trim()
        .toLowerCase()
        .replace(/[\s-]+/g, "_");

    const labels = {
        brand_new: "Brand New",
        pre_owned: "Pre-owned",
        preowned: "Pre-owned",
        used: "Pre-owned",
    };

    return labels[normalized] || condition || "Available";
};

const formatMoney = (value) => {
    const amount = Number(value);

    if (!Number.isFinite(amount) || amount <= 0) {
        return "Price unavailable";
    }

    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        maximumFractionDigits: 0,
    }).format(amount);
};

const nextImage = () => {
    if (!images.value.length) return;

    activeImageIndex.value = (activeImageIndex.value + 1) % images.value.length;
};

const previousImage = () => {
    if (!images.value.length) return;

    activeImageIndex.value =
        (activeImageIndex.value - 1 + images.value.length) %
        images.value.length;
};

const selectImage = (index) => {
    activeImageIndex.value = index;
};

const scrollOtherWatches = (direction = 1) => {
    if (!otherWatchesStrip.value) return;

    otherWatchesStrip.value.scrollBy({
        left:
            direction *
            Math.min(otherWatchesStrip.value.clientWidth * 0.82, 560),
        behavior: "smooth",
    });
};

watch(
    () => props.watch.id,
    () => {
        activeImageIndex.value = 0;

        requestAnimationFrame(() => {
            otherWatchesStrip.value?.scrollTo({
                left: 0,
                behavior: "smooth",
            });
        });
    },
);

const messengerLink = computed(() => {
    const text = `Hi Watch Gallery Manila, I would like to inquire about the ${props.watch.condition}  ${displayWatchName.value || ""}  with the price of ${formatMoney(actualWatchPrice.value)}. Thank you so much!`;

    return `https://m.me/watchgallerymanila?text=${encodeURIComponent(text)}`;
});

const specs = computed(() => {
    return [
        {
            label: "Brand",
            value: props.watch.brand,
        },
        {
            label: "Reference",
            value: props.watch.reference_number,
        },
        {
            label: "Condition",
            value: props.watch.condition,
        },
        {
            label: "Category",
            value: props.watch.category,
        },
        {
            label: "Movement",
            value: props.watch.movement,
        },
        {
            label: "Case Size",
            value: props.watch.case_size,
        },
        {
            label: "Case Material",
            value: props.watch.case_material,
        },
        {
            label: "Dial Color",
            value: props.watch.dial_color,
        },
        {
            label: "Crystal",
            value: props.watch.crystal,
        },
        {
            label: "Bracelet / Strap",
            value: props.watch.bracelet_or_strap,
        },
        {
            label: "Water Resistance",
            value: props.watch.water_resistance,
        },
        {
            label: "Warranty",
            value: props.watch.warranty_type,
        },
    ].filter((item) => item.value);
});
</script>

<template>
    <Head :title="`${watch.brand || ''} ${displayWatchName}`" />

    <div class="min-h-screen bg-[#f8fafc] pb-6 text-[#071923]">
        <!-- Header -->
        <header
            class="sticky top-0 z-50 border-b border-white/10 bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] shadow-xl shadow-slate-900/15"
        >
            <div
                class="mx-auto flex h-[72px] max-w-7xl items-center justify-between px-4 sm:h-[86px] sm:px-6 lg:px-8"
            >
                <!-- Logo Only -->
                <Link
                    href="/"
                    class="group flex shrink-0 items-center"
                    aria-label="Watch Gallery Manila Home"
                >
                    <img
                        :src="navbarLogo"
                        alt="Watch Gallery Manila"
                        class="h-16 w-auto origin-left scale-[1.85] object-contain transition duration-300 group-hover:opacity-85 sm:h-20 sm:scale-[2.05] md:h-24"
                    />
                </Link>

                <Link
                    href="/"
                    class="rounded-full border border-white/15 bg-white/10 px-3 py-2 text-[11px] font-black text-white shadow-lg shadow-black/10 backdrop-blur-xl transition hover:bg-white hover:text-[#071923] active:scale-95 sm:px-5 sm:py-2.5 sm:text-sm"
                >
                    ← Back
                    <span class="hidden sm:inline"> to Collection</span>
                </Link>
            </div>
        </header>

        <main class="relative">
            <!-- Background -->
            <div class="pointer-events-none fixed inset-0 overflow-hidden">
                <div
                    class="absolute inset-0 bg-[radial-gradient(circle_at_12%_0%,rgba(11,58,86,0.11),transparent_32%),radial-gradient(circle_at_90%_10%,rgba(15,23,42,0.07),transparent_30%),linear-gradient(180deg,#ffffff_0%,#f8fafc_48%,#ffffff_100%)]"
                />
            </div>

            <section
                class="relative z-10 mx-auto max-w-7xl px-3 py-4 sm:px-6 sm:py-8 lg:px-8"
            >
                <div
                    class="grid gap-4 lg:grid-cols-[1.05fr_0.95fr] lg:items-stretch"
                >
                    <!-- Image Carousel -->
                    <div
                        class="watch-detail-panel overflow-hidden rounded-[1.45rem] border border-slate-200 bg-white p-1.5 shadow-xl shadow-[#0b3a56]/8 sm:rounded-[2.5rem] sm:p-2 sm:shadow-2xl sm:shadow-[#0b3a56]/10 lg:flex lg:flex-col"
                    >
                        <div
                            class="relative aspect-[4/5] max-h-[58dvh] overflow-hidden rounded-[1.15rem] bg-slate-100 sm:max-h-none sm:rounded-[2rem] lg:flex-1 lg:aspect-auto lg:max-h-none"
                        >
                            <img
                                v-if="activeImage"
                                :src="activeImage.image_url"
                                :alt="displayWatchName"
                                class="h-full w-full object-cover"
                            />

                            <div
                                v-else
                                class="grid h-full w-full place-items-center text-sm text-slate-400"
                            >
                                No Image
                            </div>

                            <div
                                class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/18 via-transparent to-black/5"
                            />

                            <div
                                v-if="images.length > 1"
                                class="absolute inset-x-3 top-1/2 flex -translate-y-1/2 justify-between sm:inset-x-4"
                            >
                                <button
                                    type="button"
                                    @click="previousImage"
                                    class="grid h-9 w-9 place-items-center rounded-full border border-white/20 bg-black/15 text-xl font-black text-white shadow-lg shadow-black/15 backdrop-blur-xl transition hover:bg-white hover:text-[#071923] active:scale-95 sm:h-11 sm:w-11 sm:text-2xl"
                                    aria-label="Previous image"
                                >
                                    ‹
                                </button>

                                <button
                                    type="button"
                                    @click="nextImage"
                                    class="grid h-9 w-9 place-items-center rounded-full border border-white/20 bg-black/15 text-xl font-black text-white shadow-lg shadow-black/15 backdrop-blur-xl transition hover:bg-white hover:text-[#071923] active:scale-95 sm:h-11 sm:w-11 sm:text-2xl"
                                    aria-label="Next image"
                                >
                                    ›
                                </button>
                            </div>

                            <div
                                v-if="images.length > 1"
                                class="absolute bottom-3 left-1/2 flex -translate-x-1/2 items-center gap-1.5 rounded-full border border-white/15 bg-black/25 px-2.5 py-1.5 backdrop-blur-xl sm:bottom-4 sm:px-3 sm:py-2"
                            >
                                <button
                                    v-for="(_, index) in images"
                                    :key="`dot-${index}`"
                                    type="button"
                                    @click="selectImage(index)"
                                    class="h-1.5 rounded-full transition sm:h-2"
                                    :class="
                                        activeImageIndex === index
                                            ? 'w-5 bg-white sm:w-6'
                                            : 'w-1.5 bg-white/45 hover:bg-white/80 sm:w-2'
                                    "
                                    :aria-label="`Go to image ${index + 1}`"
                                />
                            </div>
                        </div>

                        <!-- Thumbnails -->
                        <div
                            v-if="images.length > 1"
                            class="mt-1.5 flex gap-1.5 overflow-x-auto pb-1 sm:mt-2 sm:gap-2"
                        >
                            <button
                                v-for="(image, index) in images"
                                :key="image.id || index"
                                type="button"
                                @click="selectImage(index)"
                                class="h-14 w-14 shrink-0 overflow-hidden rounded-xl border bg-slate-100 transition sm:h-24 sm:w-24 sm:rounded-2xl"
                                :class="
                                    activeImageIndex === index
                                        ? 'border-[#0b3a56] ring-2 ring-[#0b3a56]/20'
                                        : 'border-slate-200 opacity-70 hover:opacity-100'
                                "
                            >
                                <img
                                    :src="image.image_url"
                                    :alt="`${displayWatchName} image ${index + 1}`"
                                    class="h-full w-full object-cover"
                                />
                            </button>
                        </div>
                    </div>

                    <!-- Details -->
                    <div
                        class="watch-detail-panel overflow-hidden rounded-[1.45rem] border border-slate-200 bg-white shadow-xl shadow-[#0b3a56]/8 sm:rounded-[2.5rem] sm:shadow-2xl sm:shadow-[#0b3a56]/10 lg:flex lg:flex-col"
                    >
                        <!-- Main Info -->
                        <div
                            class="relative overflow-hidden bg-gradient-to-br from-[#061725] via-[#0b3a56] to-[#071923] p-4 text-white sm:p-6"
                        >
                            <div
                                class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_18%_0%,rgba(255,255,255,0.16),transparent_32%),radial-gradient(circle_at_100%_15%,rgba(255,255,255,0.08),transparent_28%)]"
                            />

                            <div class="relative z-10">
                                <!-- Status Row -->
                                <div
                                    class="mb-3 flex flex-wrap items-center gap-1.5"
                                >
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1.5 text-[8px] font-black uppercase tracking-[0.18em] text-emerald-100 backdrop-blur-xl sm:text-[9px]"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-emerald-300"
                                        ></span>
                                        Available
                                    </span>

                                    <span
                                        v-if="watch.is_featured"
                                        class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-[8px] font-black uppercase tracking-[0.18em] text-white/75 backdrop-blur-xl sm:text-[9px]"
                                    >
                                        Featured
                                    </span>

                                    <span
                                        v-if="watch.condition"
                                        class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-[8px] font-black uppercase tracking-[0.18em] text-white/75 backdrop-blur-xl sm:text-[9px]"
                                    >
                                        {{ watch.condition }}
                                    </span>
                                </div>

                                <!-- Main Product Card -->
                                <div
                                    class="overflow-hidden rounded-[1.45rem] border border-white/10 bg-white/[0.075] shadow-2xl shadow-black/15 backdrop-blur-xl"
                                >
                                    <!-- Product Header -->
                                    <div
                                        class="relative overflow-hidden px-4 py-4 sm:px-5 sm:py-5"
                                    >
                                        <div
                                            class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_0%_0%,rgba(255,255,255,0.12),transparent_34%),linear-gradient(135deg,rgba(255,255,255,0.06),transparent_45%)]"
                                        />

                                        <div class="relative z-10">
                                            <div
                                                class="flex items-start justify-between gap-3"
                                            >
                                                <div class="min-w-0 flex-1">
                                                    <p
                                                        class="text-[8px] font-black uppercase tracking-[0.3em] text-white/40 sm:text-[9px]"
                                                    >
                                                        {{
                                                            watch.brand ||
                                                            "Watch Gallery Manila"
                                                        }}
                                                    </p>

                                                    <h2
                                                        class="mt-2 text-[1.7rem] font-black leading-[0.96] tracking-[-0.06em] text-white sm:text-4xl"
                                                    >
                                                        {{
                                                            displayWatchName ||
                                                            watch.model_name ||
                                                            "Watch Details"
                                                        }}
                                                    </h2>

                                                    <div
                                                        class="mt-3 flex flex-wrap items-center gap-1.5"
                                                    >
                                                        <span
                                                            v-if="
                                                                watch.reference_number
                                                            "
                                                            class="inline-flex rounded-full border border-white/10 bg-white/[0.08] px-3 py-1.5 text-[9px] font-black uppercase tracking-[0.16em] text-white/55"
                                                        >
                                                            Ref.
                                                            {{
                                                                watch.reference_number
                                                            }}
                                                        </span>

                                                        <span
                                                            v-if="
                                                                watch.condition
                                                            "
                                                            class="inline-flex rounded-full border border-emerald-300/15 bg-emerald-300/10 px-3 py-1.5 text-[9px] font-black uppercase tracking-[0.16em] text-emerald-100"
                                                        >
                                                            {{
                                                                watch.condition
                                                            }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Price and Inclusions -->
                                    <div
                                        class="border-t border-white/10 p-3 sm:p-4"
                                    >
                                        <div
                                            class="grid gap-3"
                                            :class="
                                                inclusionText
                                                    ? 'sm:grid-cols-[1.05fr_0.95fr]'
                                                    : 'sm:grid-cols-1'
                                            "
                                        >
                                            <!-- Price Card -->
                                            <div
                                                class="relative overflow-hidden rounded-[1.25rem] border border-white/10 bg-white/[0.09] p-4 shadow-inner shadow-white/5"
                                            >
                                                <div
                                                    class="pointer-events-none absolute -right-10 -top-10 h-28 w-28 rounded-full bg-white/10 blur-2xl"
                                                />

                                                <div class="relative z-10">
                                                    <div
                                                        class="flex items-center gap-2"
                                                    >
                                                        <span
                                                            class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-white/10 bg-white/10 text-white/75"
                                                        >
                                                            ₱
                                                        </span>

                                                        <p
                                                            class="text-[8px] font-black uppercase tracking-[0.24em] text-white/40"
                                                        >
                                                            Listed Price
                                                        </p>
                                                    </div>

                                                    <div
                                                        class="mt-3 flex flex-wrap items-end gap-x-3 gap-y-1"
                                                    >
                                                        <p
                                                            class="text-[2.1rem] font-black leading-none tracking-[-0.065em] text-white sm:text-4xl"
                                                        >
                                                            {{
                                                                formatMoney(
                                                                    actualWatchPrice,
                                                                )
                                                            }}
                                                        </p>

                                                        <p
                                                            v-if="suggestedSrp"
                                                            class="mb-1 text-sm font-bold leading-none text-white/40 line-through decoration-white/40 decoration-1"
                                                        >
                                                            SRP
                                                            {{
                                                                formatMoney(
                                                                    suggestedSrp,
                                                                )
                                                            }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Inclusions Card -->
                                            <div
                                                v-if="inclusionText"
                                                class="relative overflow-hidden rounded-[1.25rem] border border-white/10 bg-white/[0.09] p-4 shadow-inner shadow-white/5"
                                            >
                                                <div
                                                    class="pointer-events-none absolute -left-10 -bottom-10 h-28 w-28 rounded-full bg-emerald-300/10 blur-2xl"
                                                />

                                                <div class="relative z-10">
                                                    <div
                                                        class="flex items-center gap-2"
                                                    >
                                                        <span
                                                            class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-emerald-300/15 bg-emerald-300/10 text-emerald-100"
                                                        >
                                                            <svg
                                                                viewBox="0 0 24 24"
                                                                aria-hidden="true"
                                                                class="h-4 w-4"
                                                                fill="none"
                                                                stroke="currentColor"
                                                                stroke-width="2.4"
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                            >
                                                                <path
                                                                    d="M20 6 9 17l-5-5"
                                                                />
                                                            </svg>
                                                        </span>

                                                        <p
                                                            class="text-[8px] font-black uppercase tracking-[0.24em] text-white/40"
                                                        >
                                                            Inclusions
                                                        </p>
                                                    </div>

                                                    <p
                                                        class="mt-3 text-base font-black leading-snug tracking-[-0.02em] text-white"
                                                    >
                                                        {{ inclusionText }}
                                                    </p>

                                                    <p
                                                        class="mt-1.5 text-[11px] font-semibold leading-relaxed text-white/45"
                                                    >
                                                        Included package details
                                                        for this watch.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- CTA -->
                                <div class="mt-3">
                                    <a
                                        :href="messengerLink"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="group relative inline-flex min-h-12 w-full items-center justify-center gap-2 overflow-hidden rounded-[1.1rem] border border-[#0084ff]/30 bg-gradient-to-r from-[#0084ff] via-[#0b78ff] to-[#006aff] px-5 py-3 text-sm font-black text-white shadow-lg shadow-[#0084ff]/25 transition hover:brightness-110 hover:shadow-[#0084ff]/35 active:scale-[0.98]"
                                    >
                                        <span
                                            class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_30%_0%,rgba(255,255,255,0.38),transparent_36%)] opacity-90"
                                        />

                                        <span
                                            class="relative z-10 grid h-7 w-7 place-items-center rounded-full bg-white/18 transition group-hover:bg-white/25"
                                        >
                                            <svg
                                                viewBox="0 0 24 24"
                                                aria-hidden="true"
                                                class="h-[17px] w-[17px]"
                                                fill="currentColor"
                                            >
                                                <path
                                                    d="M12 2.25c-5.37 0-9.75 4.03-9.75 9 0 2.82 1.41 5.32 3.62 6.97v3.16c0 .33.37.52.64.33l2.9-2.03c.82.23 1.69.36 2.59.36 5.37 0 9.75-4.03 9.75-9s-4.38-8.79-9.75-8.79Zm.98 12.14-2.48-2.65-4.84 2.65 5.31-5.64 2.54 2.65 4.77-2.65-5.3 5.64Z"
                                                />
                                            </svg>
                                        </span>

                                        <span class="relative z-10"
                                            >Inquire on Messenger</span
                                        >
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Compact Details -->
                        <div class="p-3.5 sm:p-7 lg:flex-1">
                            <div>
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <p
                                        class="text-[8px] font-black uppercase tracking-[0.24em] text-[#0b3a56] sm:text-[10px]"
                                    >
                                        Specifications
                                    </p>
                                </div>

                                <dl class="spec-compact-grid mt-3">
                                    <div
                                        v-for="item in specs"
                                        :key="item.label"
                                        class="spec-compact-card"
                                    >
                                        <dt class="spec-compact-label">
                                            {{ item.label }}
                                        </dt>

                                        <dd class="spec-compact-value">
                                            {{ item.value }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Watch Description from watches.description -->
                <section
                    v-if="watchDescription"
                    class="mt-4 overflow-hidden rounded-[1.15rem] border border-slate-200/80 bg-white/95 p-4 shadow-md shadow-[#0b3a56]/5 backdrop-blur-xl sm:mt-8 sm:rounded-[1.35rem] sm:p-6"
                >
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div>
                            <p
                                class="text-[8px] font-black uppercase tracking-[0.24em] text-[#0b3a56]/70 sm:text-[10px]"
                            >
                                Description
                            </p>

                            <h2
                                class="mt-1 text-lg font-black tracking-[-0.04em] text-[#071923] sm:text-2xl"
                            >
                                About this watch
                            </h2>
                        </div>
                    </div>

                    <p
                        class="mt-3 whitespace-pre-line text-sm font-medium leading-7 text-slate-600 sm:text-base sm:leading-8"
                    >
                        {{ watchDescription }}
                    </p>
                </section>

                <!-- Premium Bottom Watch Cards -->
                <section
                    v-if="compactOtherWatches.length"
                    class="mt-4 overflow-visible rounded-[1.15rem] border border-slate-200/80 bg-white/90 p-2.5 shadow-md shadow-[#0b3a56]/5 backdrop-blur-xl sm:mt-8 sm:rounded-[1.35rem] sm:p-4"
                >
                    <div
                        class="mb-2.5 flex items-center justify-between gap-3 sm:mb-3"
                    >
                        <div class="min-w-0">
                            <p
                                class="text-[7px] font-black uppercase tracking-[0.24em] text-[#0b3a56]/65 sm:text-[8px]"
                            >
                                More Watches
                            </p>

                            <h2
                                class="mt-0.5 text-[13px] font-black text-[#071923] sm:text-base"
                            >
                                Continue browsing
                            </h2>
                        </div>

                        <!-- Mobile Swipe Cue -->
                        <div
                            class="flex shrink-0 items-center gap-1.5 sm:hidden"
                        >
                            <span class="mobile-swipe-cue">
                                <span class="mobile-swipe-dot" />
                                Swipe
                                <span class="mobile-swipe-cue-arrow">→</span>
                            </span>
                        </div>

                        <!-- Desktop Arrows -->
                        <div
                            class="hidden shrink-0 items-center gap-1.5 sm:flex"
                        >
                            <button
                                type="button"
                                @click="scrollOtherWatches(-1)"
                                class="bottom-card-arrow"
                                aria-label="Previous watches"
                            >
                                ‹
                            </button>

                            <button
                                type="button"
                                @click="scrollOtherWatches(1)"
                                class="bottom-card-arrow"
                                aria-label="Next watches"
                            >
                                ›
                            </button>
                        </div>
                    </div>

                    <div class="relative">
                        <div
                            ref="otherWatchesStrip"
                            class="bottom-watch-strip flex snap-x snap-mandatory gap-2.5 overflow-x-auto px-1 pb-6 pt-2 scroll-smooth sm:gap-3 sm:pb-8"
                        >
                            <Link
                                v-for="item in compactOtherWatches"
                                :key="item.id"
                                :href="item.url || `/watches/${item.id}`"
                                class="bottom-watch-card group snap-start"
                            >
                                <div class="bottom-watch-media">
                                    <img
                                        v-if="item.image_url"
                                        :src="item.image_url"
                                        :alt="
                                            item.display_name || item.model_name
                                        "
                                        class="bottom-watch-image"
                                    />

                                    <div
                                        v-else
                                        class="grid h-full w-full place-items-center bg-slate-100 text-xs text-slate-400"
                                    >
                                        No Image
                                    </div>

                                    <div class="bottom-watch-badges">
                                        <span
                                            v-if="item.condition"
                                            class="bottom-watch-pill"
                                        >
                                            {{ conditionLabel(item.condition) }}
                                        </span>

                                        <span class="bottom-watch-pill">
                                            Available
                                        </span>

                                        <span
                                            v-if="item.is_in_demand"
                                            class="bottom-watch-demand"
                                        >
                                            In-Demand
                                        </span>
                                    </div>

                                    <div class="bottom-watch-view">
                                        <span>View details</span>
                                        <span aria-hidden="true">→</span>
                                    </div>
                                </div>

                                <div class="bottom-watch-body">
                                    <h3 class="bottom-watch-title">
                                        {{
                                            item.display_name ||
                                            item.model_name ||
                                            item.name ||
                                            item.title ||
                                            item.reference_number ||
                                            "Watch Details"
                                        }}
                                    </h3>

                                    <div class="bottom-watch-price-row">
                                        <p class="bottom-watch-price">
                                            {{
                                                formatMoney(
                                                    otherWatchPrice(item),
                                                )
                                            }}
                                        </p>

                                        <p
                                            v-if="otherWatchSrp(item)"
                                            class="bottom-watch-srp"
                                        >
                                            SRP
                                            {{
                                                formatMoney(otherWatchSrp(item))
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>
                </section>
            </section>
        </main>
    </div>
</template>

<style scoped>
.spec-compact-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.5rem;
}

.spec-compact-card {
    min-height: 3.4rem;
    border-radius: 0.95rem;
    border: 1px solid rgba(226, 232, 240, 0.95);
    background: rgba(248, 250, 252, 0.92);
    padding: 0.65rem 0.7rem;
    box-shadow:
        0 6px 16px rgba(15, 23, 42, 0.035),
        inset 0 1px 0 rgba(255, 255, 255, 0.85);
}

.spec-compact-label {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 0.47rem;
    font-weight: 950;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: #94a3b8;
}

.spec-compact-value {
    margin-top: 0.22rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 0.78rem;
    font-weight: 900;
    line-height: 1.15;
    color: #071923;
}

.bottom-watch-strip {
    scrollbar-width: none;
    scroll-padding-left: 0.25rem;
    -ms-overflow-style: none;
}

.bottom-watch-strip::-webkit-scrollbar {
    display: none;
}

.bottom-watch-card {
    position: relative;
    width: 188px;
    max-width: 188px;
    flex: 0 0 188px;
    scroll-snap-align: start;
    overflow: hidden;
    border-radius: 1.22rem;
    background: #ffffff;
    color: inherit;
    text-decoration: none;
    box-shadow: 0 18px 48px rgba(15, 23, 42, 0.1);
    outline: none;
    -webkit-tap-highlight-color: transparent;
    transition:
        transform 0.26s ease,
        box-shadow 0.26s ease;
}

.bottom-watch-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 26px 64px rgba(11, 58, 86, 0.17);
}

.bottom-watch-card:focus-visible {
    box-shadow:
        0 0 0 4px rgba(11, 58, 86, 0.15),
        0 26px 64px rgba(11, 58, 86, 0.17);
}

.bottom-watch-media {
    position: relative;
    aspect-ratio: 4 / 5;
    overflow: hidden;
    border-radius: 1.22rem 1.22rem 0 0;
    background: #f1f5f9;
}

.bottom-watch-image {
    height: 100%;
    width: 100%;
    object-fit: cover;
    transition: transform 0.72s ease;
}

.bottom-watch-card:hover .bottom-watch-image {
    transform: scale(1.045);
}

.bottom-watch-badges {
    position: absolute;
    left: 0.72rem;
    right: 0.72rem;
    top: 0.72rem;
    z-index: 20;
    display: flex;
    flex-wrap: wrap;
    gap: 0.42rem;
}

.bottom-watch-pill,
.bottom-watch-demand {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
    border-radius: 999px;
    padding: 0.42rem 0.62rem;
    color: #ffffff;
    font-size: 0.52rem;
    font-weight: 950;
    letter-spacing: 0.07em;
    line-height: 1;
    text-transform: uppercase;
    backdrop-filter: blur(18px) saturate(165%);
    -webkit-backdrop-filter: blur(18px) saturate(165%);
    box-shadow:
        0 10px 24px rgba(15, 23, 42, 0.16),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
}

.bottom-watch-pill {
    border: 1px solid rgba(255, 255, 255, 0.38);
    background: linear-gradient(
        135deg,
        rgba(11, 58, 86, 0.72),
        rgba(100, 116, 139, 0.46)
    );
}

.bottom-watch-demand {
    border: 1px solid rgba(254, 202, 202, 0.5);
    background: linear-gradient(
        135deg,
        rgba(220, 38, 38, 0.9),
        rgba(153, 27, 27, 0.68)
    );
}

.bottom-watch-view {
    position: absolute;
    left: 0.72rem;
    right: 0.72rem;
    bottom: 0.72rem;
    z-index: 22;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    border-radius: 0.82rem;
    background: rgba(255, 255, 255, 0.96);
    padding: 0.72rem 0.9rem;
    color: #071923;
    font-size: 0.72rem;
    font-weight: 950;
    letter-spacing: -0.01em;
    opacity: 0;
    box-shadow: 0 16px 34px rgba(15, 23, 42, 0.18);
    transform: translateY(10px);
    transition:
        opacity 0.24s ease,
        transform 0.24s ease,
        background 0.24s ease;
}

.bottom-watch-card:hover .bottom-watch-view,
.bottom-watch-card:focus-visible .bottom-watch-view {
    opacity: 1;
    transform: translateY(0);
}

.bottom-watch-body {
    display: flex;
    min-height: 6.65rem;
    flex-direction: column;
    justify-content: flex-end;
    padding: 1rem 1.05rem 1.1rem;
    background: #ffffff;
}

.bottom-watch-title {
    display: -webkit-box;
    overflow: hidden;
    min-height: 2.55rem;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    font-size: 1.02rem;
    font-weight: 800;
    line-height: 1.12;
    letter-spacing: -0.042em;
    color: #222222;
    transition: color 0.24s ease;
}

.bottom-watch-card:hover .bottom-watch-title {
    color: #222222;
}

.bottom-watch-price-row {
    margin-top: 0.75rem;
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 0.25rem 0.45rem;
}

.bottom-watch-price {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 1.14rem;
    font-weight: 900;
    line-height: 1;
    letter-spacing: -0.052em;
    color: #111827;
}

.bottom-watch-srp {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 0.64rem;
    font-weight: 800;
    line-height: 1;
    color: #a1a1aa;
    text-decoration-line: line-through;
    text-decoration-color: #a1a1aa;
    text-decoration-thickness: 1px;
}

.bottom-card-arrow {
    display: grid;
    height: 1.9rem;
    width: 1.9rem;
    place-items: center;
    border-radius: 999px;
    border: 1px solid rgba(226, 232, 240, 0.95);
    background: #ffffff;
    color: #071923;
    font-size: 0.95rem;
    font-weight: 950;
    box-shadow: 0 6px 14px rgba(15, 23, 42, 0.05);
    transition:
        border-color 0.22s ease,
        background 0.22s ease,
        transform 0.22s ease;
}

.bottom-card-arrow:hover {
    border-color: rgba(11, 58, 86, 0.22);
    background: #eef8fb;
    transform: translateY(-1px);
}

.mobile-swipe-cue {
    display: inline-flex;
    align-items: center;
    gap: 0.28rem;
    border-radius: 999px;
    border: 1px solid rgba(11, 58, 86, 0.12);
    background: rgba(238, 248, 251, 0.9);
    padding: 0.32rem 0.55rem;
    font-size: 0.52rem;
    font-weight: 950;
    line-height: 1;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #0b3a56;
    box-shadow: 0 8px 18px rgba(11, 58, 86, 0.08);
}

.mobile-swipe-dot {
    height: 0.32rem;
    width: 0.32rem;
    border-radius: 999px;
    background: #0b78ff;
    box-shadow: 0 0 0 4px rgba(0, 132, 255, 0.12);
}

.mobile-swipe-cue-arrow {
    display: inline-block;
    font-size: 0.7rem;
    animation: mobileSwipeCueArrow 1.25s ease-in-out infinite;
}

@keyframes mobileSwipeCueArrow {
    0%,
    100% {
        transform: translateX(0);
        opacity: 0.65;
    }

    50% {
        transform: translateX(3px);
        opacity: 1;
    }
}

@media (min-width: 640px) {
    .spec-compact-grid {
        gap: 0.65rem;
    }

    .spec-compact-card {
        min-height: 4.15rem;
        border-radius: 1.1rem;
        padding: 0.85rem 0.95rem;
    }

    .spec-compact-label {
        font-size: 0.58rem;
    }

    .spec-compact-value {
        margin-top: 0.32rem;
        font-size: 0.95rem;
    }

    .bottom-watch-card {
        width: 230px;
        max-width: 230px;
        flex-basis: 230px;
        border-radius: 1.45rem;
    }

    .bottom-watch-media {
        border-radius: 1.45rem 1.45rem 0 0;
    }

    .bottom-watch-badges {
        left: 0.9rem;
        right: 0.9rem;
        top: 0.9rem;
        gap: 0.5rem;
    }

    .bottom-watch-pill,
    .bottom-watch-demand {
        padding: 0.48rem 0.72rem;
        font-size: 0.6rem;
    }

    .bottom-watch-view {
        left: 0.9rem;
        right: 0.9rem;
        bottom: 0.9rem;
        font-size: 0.8rem;
    }

    .bottom-watch-body {
        min-height: 7.2rem;
        padding: 1.15rem 1.25rem 1.25rem;
    }

    .bottom-watch-title {
        min-height: 2.85rem;
        font-size: 1.17rem;
    }

    .bottom-watch-price {
        font-size: 1.32rem;
    }

    .bottom-watch-srp {
        font-size: 0.72rem;
    }
}

@media (max-width: 374px) {
    .spec-compact-card {
        padding: 0.58rem 0.62rem;
    }

    .spec-compact-value {
        font-size: 0.72rem;
    }

    .bottom-watch-card {
        width: 170px;
        max-width: 170px;
        flex-basis: 170px;
    }

    .bottom-watch-badges {
        left: 0.55rem;
        right: 0.55rem;
        top: 0.55rem;
        gap: 0.32rem;
    }

    .bottom-watch-pill,
    .bottom-watch-demand {
        padding: 0.32rem 0.48rem;
        font-size: 0.46rem;
    }

    .bottom-watch-body {
        min-height: 6.15rem;
        padding: 0.85rem 0.9rem 0.95rem;
    }

    .bottom-watch-title {
        min-height: 2.3rem;
        font-size: 0.92rem;
    }

    .bottom-watch-price {
        font-size: 1.02rem;
    }

    .bottom-watch-srp {
        font-size: 0.55rem;
    }
}
</style>

<script setup>
import { computed, nextTick, ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";

const props = defineProps({
    heroWatches: {
        type: Array,
        default: () => [],
    },
    watches: {
        type: [Object, Array],
        required: true,
    },
    transactions: {
        type: Array,
        default: () => [],
    },
    aboutUs: {
        type: Object,
        default: null,
    },
    filters: {
        type: Object,
        default: () => ({
            search: "",
            condition: "",
            gender: "",
            category: "", // legacy fallback for old URLs/controllers
        }),
    },
});
const transactionStrip = ref(null);
const isFiltering = ref(false);
const filterRequestToken = ref(0);
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
const scrollTransactions = (direction = 1) => {
    if (!transactionStrip.value) return;

    transactionStrip.value.scrollBy({
        left:
            direction *
            Math.min(transactionStrip.value.clientWidth * 0.85, 420),
        behavior: "smooth",
    });
};
const normalizeGender = (value) => {
    if (value === "mens") return "men";
    if (value === "womens") return "women";

    return value || "";
};

const search = ref(props.filters?.search ?? "");
const activeCondition = ref(props.filters?.condition ?? "");
const activeGender = ref(
    normalizeGender(props.filters?.gender ?? props.filters?.category ?? ""),
);

const shopOpened = ref(false);
const isShopTransitioning = ref(false);
const heroTransitionDirection = ref(null);
const aboutSlideIndex = ref(0);

const conditionFilters = [
    {
        label: "Brand New",
        value: "brand_new",
    },
    {
        label: "Pre-owned",
        value: "pre_owned",
    },
];

const genderFilters = [
    {
        label: "Unisex",
        value: "unisex",
    },
    {
        label: "Men",
        value: "men",
    },
    {
        label: "Women",
        value: "women",
    },
];

const hasActiveCollectionFilters = computed(() => {
    return Boolean(search.value || activeCondition.value || activeGender.value);
});

const watchesData = computed(() => {
    if (Array.isArray(props.watches?.data)) {
        return props.watches.data;
    }

    if (Array.isArray(props.watches)) {
        return props.watches;
    }

    return [];
});

const watchPaginationLinks = computed(() => {
    return Array.isArray(props.watches?.links) ? props.watches.links : [];
});

const collageWatches = computed(() => props.heroWatches?.slice(0, 5) ?? []);
const hasAboutUs = computed(() => Boolean(props.aboutUs));

const aboutPictures = computed(() => {
    return (props.aboutUs?.images ?? [])
        .filter((image) => image.image_url)
        .slice()
        .sort((a, b) => {
            if (a.is_primary && !b.is_primary) return -1;
            if (!a.is_primary && b.is_primary) return 1;

            return Number(a.sort_order || 0) - Number(b.sort_order || 0);
        })
        .map((image) => ({
            image_url: image.image_url,
            title: image.caption || "Watch Gallery Manila Showroom",
            subtitle: image.is_primary
                ? "Featured showroom photo"
                : "Showroom photo",
            is_primary: Boolean(image.is_primary),
        }))
        .slice(0, 12);
});

const currentAboutPicture = computed(
    () => aboutPictures.value[aboutSlideIndex.value] ?? null,
);

const hasOwnerProfile = computed(() => {
    return Boolean(
        props.aboutUs?.owner_image_url ||
        props.aboutUs?.owner_bio ||
        props.aboutUs?.dealer_name ||
        props.aboutUs?.dealer_message,
    );
});

const ownerInitials = computed(() => {
    const name = props.aboutUs?.dealer_name || "Watch Gallery Manila";

    return name
        .split(" ")
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0))
        .join("")
        .toUpperCase();
});

const availableCount = computed(
    () => props.watches?.total ?? watchesData.value.length,
);

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

const watchDisplayPrice = (watch) => {
    return (
        watch?.display_price ?? watch?.discounted_price ?? watch?.selling_price
    );
};

const hasSuggestedSrp = (watch) => {
    const amount = Number(watch?.suggested_srp);

    return Number.isFinite(amount) && amount > 0;
};

const formatDate = (value) => {
    if (!value) return "";

    return new Intl.DateTimeFormat("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
    }).format(new Date(value));
};

const scrollToElement = (id, offset = 86, behavior = "smooth") => {
    const target = document.getElementById(id);

    if (!target) return;

    const targetTop =
        target.getBoundingClientRect().top + window.scrollY - offset;

    window.scrollTo({
        top: targetTop,
        behavior,
    });
};

const filterPayload = () => {
    return {
        search: search.value?.trim() || undefined,
        condition: activeCondition.value || undefined,
        gender: activeGender.value || undefined,
    };
};

const applyFilters = () => {
    const requestToken = filterRequestToken.value + 1;

    filterRequestToken.value = requestToken;
    isFiltering.value = true;

    router.get(route("welcome"), filterPayload(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ["watches", "filters"],
        onFinish: () => {
            window.setTimeout(() => {
                if (filterRequestToken.value === requestToken) {
                    isFiltering.value = false;
                }
            }, 120);
        },
    });
};

const applySearch = () => {
    applyFilters();
};

const clearSearch = () => {
    search.value = "";
    applyFilters();
};

const setConditionFilter = (value) => {
    activeCondition.value = activeCondition.value === value ? "" : value;
    applyFilters();
};

const setGenderFilter = (value) => {
    activeGender.value = activeGender.value === value ? "" : value;
    applyFilters();
};

const clearCollectionFilters = () => {
    search.value = "";
    activeCondition.value = "";
    activeGender.value = "";
    applyFilters();
};

const scrollToShopTop = (behavior = "smooth") => {
    window.setTimeout(() => {
        requestAnimationFrame(() => {
            scrollToElement("shop-section", 78, behavior);
        });
    }, 80);
};

const goToWatchPage = (link) => {
    if (!link?.url || link.active || isFiltering.value) {
        return;
    }

    const requestToken = filterRequestToken.value + 1;

    filterRequestToken.value = requestToken;
    shopOpened.value = true;
    isFiltering.value = true;

    router.visit(link.url, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ["watches"],
        onSuccess: async () => {
            await nextTick();
            scrollToShopTop("smooth");
        },
        onFinish: () => {
            window.setTimeout(() => {
                if (filterRequestToken.value === requestToken) {
                    isFiltering.value = false;
                }
            }, 140);
        },
    });
};

const scrollToShop = async (event = null) => {
    event?.preventDefault?.();
    event?.stopPropagation?.();

    if (isShopTransitioning.value) return;

    heroTransitionDirection.value = "to-shop";
    isShopTransitioning.value = true;
    shopOpened.value = true;

    await nextTick();

    window.setTimeout(() => {
        scrollToElement("shop-section", 78, "smooth");
    }, 60);

    window.setTimeout(() => {
        isShopTransitioning.value = false;
        heroTransitionDirection.value = null;
    }, 460);
};

const showHeroAgain = async () => {
    if (isShopTransitioning.value) return;

    heroTransitionDirection.value = "to-hero";
    isShopTransitioning.value = true;
    shopOpened.value = false;

    await nextTick();

    window.setTimeout(() => {
        window.scrollTo({
            top: 0,
            behavior: "smooth",
        });
    }, 40);

    window.setTimeout(() => {
        isShopTransitioning.value = false;
        heroTransitionDirection.value = null;
    }, 560);
};

const scrollToAbout = () => {
    scrollToElement("about-section", 86);
};

const nextAboutSlide = () => {
    if (!aboutPictures.value.length) return;

    aboutSlideIndex.value =
        (aboutSlideIndex.value + 1) % aboutPictures.value.length;
};

const previousAboutSlide = () => {
    if (!aboutPictures.value.length) return;

    aboutSlideIndex.value =
        (aboutSlideIndex.value - 1 + aboutPictures.value.length) %
        aboutPictures.value.length;
};

const watchDetailsLink = (watch) => {
    const identifier = watch.slug || watch.id;

    if (watch.url) {
        return watch.url;
    }

    try {
        return route("public.watches.show", identifier);
    } catch (error) {
        return `/watches/${identifier}`;
    }
};

const watchCardLabel = (watch) => {
    return `View details for ${watch.brand || ""} ${watch.model_name || ""} ${
        watch.reference_number || ""
    }`;
};

const genderLabel = (gender) => {
    const labels = {
        unisex: "Unisex",
        men: "Men",
        women: "Women",
        mens: "Men",
        womens: "Women",
    };

    return labels[gender] || "";
};

const messengerLink = (watch = null) => {
    const text = watch
        ? `Hi Watch Gallery Manila, I would like to inquire about ${watch.brand} ${watch.model_name} ${watch.reference_number || ""}.`
        : "Hi Watch Gallery Manila, I would like to inquire about your available watches.";

    return `https://m.me/watchgallerymanila?text=${encodeURIComponent(text)}`;
};
</script>

<template>
    <Head title="Watch Gallery Manila" />

    <div
        class="min-h-screen overflow-x-hidden bg-[#f8fafc] pb-32 text-[#071923] sm:pb-0"
    >
        <!-- Soft Background Accents -->
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_12%_0%,rgba(11,58,86,0.13),transparent_32%),radial-gradient(circle_at_90%_10%,rgba(15,23,42,0.08),transparent_30%),linear-gradient(180deg,#ffffff_0%,#f8fafc_45%,#ffffff_100%)]"
            />
            <div
                class="absolute left-1/2 top-[74px] h-px w-[80vw] -translate-x-1/2 bg-gradient-to-r from-transparent via-[#0b3a56]/20 to-transparent"
            />
        </div>

        <!-- Header -->
        <header
            class="sticky top-0 z-50 border-b border-white/10 bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] shadow-xl shadow-slate-900/15 backdrop-blur-2xl"
        >
            <div
                class="mx-auto flex h-[64px] max-w-7xl items-center justify-between px-4 sm:h-[74px] sm:px-6 lg:px-8"
            >
                <button
                    type="button"
                    @click="showHeroAgain"
                    class="group text-left"
                >
                    <p
                        class="text-[9px] font-black uppercase tracking-[0.45em] text-white/60"
                    >
                        Watch Gallery
                    </p>

                    <h1
                        class="mt-0.5 text-base font-black tracking-wide text-white transition group-hover:text-white/80 sm:mt-1 sm:text-xl"
                    >
                        Manila
                    </h1>
                </button>

                <nav
                    class="hidden items-center gap-1 rounded-full border border-white/10 bg-white/10 p-1 text-sm font-bold text-slate-100 shadow-inner shadow-white/5 backdrop-blur-xl sm:flex"
                >
                    <button
                        type="button"
                        @click.prevent.stop="scrollToShop"
                        class="rounded-full px-5 py-2.5 transition hover:bg-white/15 hover:text-white"
                    >
                        Latest Collection
                    </button>

                    <button
                        v-if="hasAboutUs"
                        type="button"
                        @click="scrollToAbout"
                        class="rounded-full px-5 py-2.5 transition hover:bg-white/15 hover:text-white"
                    >
                        About Us
                    </button>

                    <a
                        href="#transactions"
                        class="rounded-full px-5 py-2.5 transition hover:bg-white/15 hover:text-white"
                    >
                        Sold Gallery
                    </a>

                    <a
                        href="https://m.me/watchgallerymanila"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="rounded-full border border-white/20 bg-white px-5 py-2.5 font-black text-[#071923] shadow-lg shadow-black/15 transition hover:bg-white/90 hover:shadow-black/20 active:scale-95"
                    >
                        Inquire
                    </a>
                </nav>

                <div class="flex items-center gap-2 sm:hidden">
                    <button
                        v-if="hasAboutUs"
                        type="button"
                        @click="scrollToAbout"
                        class="rounded-full border border-white/15 bg-white/10 px-3 py-2.5 text-xs font-black text-white shadow-lg shadow-black/10 backdrop-blur-xl active:scale-95"
                    >
                        About
                    </button>

                    <a
                        :href="messengerLink()"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="rounded-full border border-white/20 bg-white px-4 py-2.5 text-xs font-black text-[#071923] shadow-lg shadow-black/15 active:scale-95"
                    >
                        Inquire
                    </a>
                </div>
            </div>

            <div
                class="h-px bg-gradient-to-r from-transparent via-white/25 to-transparent"
            />
        </header>

        <main class="relative z-10">
            <!-- Hero Collage -->
            <section
                class="hero-panel"
                :class="[
                    shopOpened ? 'hero-closed' : 'hero-open',
                    isShopTransitioning && heroTransitionDirection === 'to-shop'
                        ? 'hero-to-shop'
                        : '',
                    isShopTransitioning && heroTransitionDirection === 'to-hero'
                        ? 'hero-to-hero'
                        : '',
                ]"
            >
                <div
                    class="mx-auto max-w-7xl px-4 py-5 sm:px-6 sm:py-8 lg:px-8"
                >
                    <div
                        class="mb-5 flex flex-col gap-4 sm:mb-7 sm:flex-row sm:items-end sm:justify-between"
                    >
                        <div>
                            <p
                                class="text-[10px] font-black uppercase tracking-[0.35em] text-[#0b3a56]"
                            >
                                Latest Available Watches
                            </p>

                            <h2
                                class="mt-2 max-w-3xl text-[1.7rem] font-black leading-[1.04] tracking-tight text-[#071923] sm:text-4xl lg:text-5xl"
                            >
                                Newest drops from our current collection.
                            </h2>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click.prevent.stop="scrollToShop"
                        class="group relative block w-full overflow-hidden rounded-[2rem] border border-[#0b3a56]/15 bg-white p-1 shadow-2xl shadow-slate-900/10 ring-1 ring-[#0b3a56]/5 transition active:scale-[0.99] sm:rounded-[2.5rem]"
                        :class="
                            isShopTransitioning &&
                            heroTransitionDirection === 'to-shop'
                                ? 'collage-button-transitioning'
                                : ''
                        "
                    >
                        <div v-if="collageWatches.length" class="collage-grid">
                            <!-- Main Watch -->
                            <article
                                v-if="collageWatches[0]"
                                class="collage-cell collage-main"
                            >
                                <img
                                    v-if="collageWatches[0].image_url"
                                    :src="collageWatches[0].image_url"
                                    :alt="collageWatches[0].model_name"
                                    class="collage-img"
                                />

                                <div
                                    v-else
                                    class="grid h-full w-full place-items-center bg-slate-100 text-sm text-slate-400"
                                >
                                    No Image
                                </div>

                                <div class="collage-gradient" />

                                <div
                                    class="absolute bottom-0 left-0 right-0 p-4 text-left sm:p-6"
                                >
                                    <p
                                        class="text-[10px] font-black uppercase tracking-[0.25em] text-white/70"
                                    >
                                        Latest Drop
                                    </p>

                                    <h3
                                        class="mt-1 line-clamp-1 text-xl font-black text-white sm:text-2xl"
                                    >
                                        {{ collageWatches[0].model_name }}
                                    </h3>

                                    <p
                                        class="mt-2 text-sm font-black text-white"
                                    >
                                        {{
                                            formatMoney(
                                                collageWatches[0].display_price,
                                            )
                                        }}
                                    </p>
                                </div>
                            </article>

                            <!-- Small Watches -->
                            <article
                                v-for="(watch, index) in collageWatches.slice(
                                    1,
                                    4,
                                )"
                                :key="watch.id"
                                class="collage-cell"
                                :class="`collage-${index + 2}`"
                            >
                                <img
                                    v-if="watch.image_url"
                                    :src="watch.image_url"
                                    :alt="watch.model_name"
                                    class="collage-img"
                                />

                                <div
                                    v-else
                                    class="grid h-full w-full place-items-center bg-slate-100 text-sm text-slate-400"
                                >
                                    No Image
                                </div>

                                <div class="collage-gradient-small" />

                                <div
                                    class="absolute bottom-0 left-0 right-0 p-3 text-left sm:p-4"
                                >
                                    <h3
                                        class="line-clamp-1 text-sm font-black text-white sm:text-base"
                                    >
                                        {{ watch.model_name }}
                                    </h3>

                                    <p
                                        class="mt-1 text-xs font-bold text-white/75"
                                    >
                                        {{ formatMoney(watch.display_price) }}
                                    </p>
                                </div>
                            </article>

                            <!-- More Card -->
                            <article class="collage-cell collage-more">
                                <template v-if="collageWatches[4]">
                                    <img
                                        v-if="collageWatches[4].image_url"
                                        :src="collageWatches[4].image_url"
                                        :alt="collageWatches[4].model_name"
                                        class="collage-img opacity-70"
                                    />

                                    <div
                                        v-else
                                        class="grid h-full w-full place-items-center bg-slate-100 text-sm text-slate-400"
                                    >
                                        No Image
                                    </div>

                                    <div
                                        class="absolute inset-0 grid place-items-center bg-[#071923]/62 text-center backdrop-blur-[1px]"
                                    >
                                        <div>
                                            <p
                                                class="text-4xl font-black text-white sm:text-5xl"
                                            >
                                                +{{
                                                    Math.max(
                                                        availableCount - 4,
                                                        0,
                                                    )
                                                }}
                                            </p>
                                            <p
                                                class="mt-1 text-xs font-black uppercase tracking-[0.25em] text-white/70"
                                            >
                                                More Watches
                                            </p>
                                        </div>
                                    </div>
                                </template>

                                <template v-else>
                                    <div
                                        class="grid h-full w-full place-items-center bg-[#eef8fb] p-5 text-center"
                                    >
                                        <div>
                                            <p
                                                class="text-3xl font-black text-[#0b3a56]"
                                            >
                                                Shop
                                            </p>
                                            <p
                                                class="mt-1 text-xs text-slate-500"
                                            >
                                                More available watches
                                            </p>
                                        </div>
                                    </div>
                                </template>
                            </article>
                        </div>

                        <div
                            v-else
                            class="grid h-[520px] place-items-center rounded-[1.75rem] bg-slate-50 text-center"
                        >
                            <div>
                                <p
                                    class="text-base font-black text-[#071923] sm:text-lg"
                                >
                                    No available watches yet.
                                </p>
                                <p class="mt-2 text-sm text-slate-500">
                                    Add watches from your admin inventory.
                                </p>
                            </div>
                        </div>

                        <!-- Center CTA -->
                        <div
                            class="pointer-events-none absolute inset-0 z-20 flex items-center justify-center"
                        >
                            <div class="shop-now-button">
                                <div class="shop-now-shine" />

                                <div
                                    class="relative z-10 flex items-center gap-4"
                                >
                                    <div class="text-left">
                                        <span class="shop-now-eyebrow">
                                            Latest Collection
                                        </span>

                                        <span class="shop-now-main">
                                            Shop Now
                                        </span>
                                    </div>

                                    <span class="shop-now-icon">→</span>
                                </div>
                            </div>
                        </div>

                        <div class="shop-transition-flash" />
                    </button>
                </div>
            </section>

            <!-- Shop Section: kept mounted using v-show so cards do not disappear -->
            <Transition name="shop-page">
                <section
                    v-show="shopOpened"
                    id="shop-section"
                    class="shop-section min-h-screen border-t border-slate-200 bg-white py-8 sm:py-12"
                    :class="shopOpened ? 'shop-section-opened' : ''"
                >
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <!-- Shop Toolbar -->
                        <!-- Shop Toolbar -->
                        <div
                            class="shop-toolbar-card mb-6 rounded-[2rem] border border-slate-200 bg-white p-4 shadow-xl shadow-[#0b3a56]/10 sm:p-5"
                        >
                            <div
                                class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
                            >
                                <div>
                                    <button
                                        v-if="shopOpened"
                                        type="button"
                                        @click="showHeroAgain"
                                        class="mb-3 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-500 transition hover:border-[#0b3a56]/30 hover:bg-[#eef8fb] hover:text-[#0b3a56]"
                                    >
                                        ← Back to collage
                                    </button>

                                    <p
                                        class="text-[10px] font-black uppercase tracking-[0.35em] text-[#0b3a56]"
                                    >
                                        Shop Watches
                                    </p>

                                    <h2
                                        class="mt-2 text-2xl font-black tracking-tight text-[#071923] sm:text-3xl"
                                    >
                                        Available Timepieces
                                    </h2>

                                    <p class="mt-2 text-sm text-slate-500">
                                        Browse all current stocks ready for
                                        inquiry.
                                    </p>
                                </div>

                                <div
                                    class="grid gap-2 sm:grid-cols-[1fr_auto] lg:w-[440px]"
                                >
                                    <input
                                        v-model="search"
                                        type="search"
                                        placeholder="Search model name or reference number..."
                                        class="w-full rounded-2xl border-slate-200 bg-slate-50 text-sm text-[#071923] placeholder:text-slate-400 focus:border-[#0b3a56] focus:ring-[#0b3a56]"
                                        @keyup.enter="applySearch"
                                    />

                                    <button
                                        type="button"
                                        :disabled="isFiltering"
                                        @click="applySearch"
                                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-[#0b3a56]/15 transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-70 active:scale-95"
                                    >
                                        <span
                                            v-if="isFiltering"
                                            class="mini-button-spinner"
                                        />
                                        <span>
                                            {{
                                                isFiltering
                                                    ? "Searching..."
                                                    : "Search"
                                            }}
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <Transition name="filter-loading-fade">
                                <div
                                    v-if="isFiltering"
                                    class="mt-4 overflow-hidden rounded-2xl border border-[#0b3a56]/10 bg-[#eef8fb] shadow-inner shadow-[#0b3a56]/5"
                                >
                                    <div
                                        class="flex items-center gap-3 px-4 py-3"
                                    >
                                        <span class="filter-spinner" />

                                        <div>
                                            <p
                                                class="text-xs font-black uppercase tracking-[0.2em] text-[#0b3a56]"
                                            >
                                                Updating collection
                                            </p>

                                            <p
                                                class="mt-0.5 text-xs text-slate-500"
                                            >
                                                Applying your selected filters.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="filter-progress-bar" />
                                </div>
                            </Transition>

                            <!-- Collection Filters -->
                            <div
                                class="mt-5 grid gap-4 border-t border-slate-100 pt-4 lg:grid-cols-2"
                            >
                                <div>
                                    <p
                                        class="mb-2 text-[10px] font-black uppercase tracking-[0.26em] text-slate-400"
                                    >
                                        Condition
                                    </p>

                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            :disabled="isFiltering"
                                            @click="setConditionFilter('')"
                                            class="collection-filter-chip"
                                            :class="
                                                activeCondition === ''
                                                    ? 'collection-filter-chip-active'
                                                    : ''
                                            "
                                        >
                                            All
                                        </button>

                                        <button
                                            v-for="filter in conditionFilters"
                                            :key="filter.value"
                                            type="button"
                                            :disabled="isFiltering"
                                            @click="
                                                setConditionFilter(filter.value)
                                            "
                                            class="collection-filter-chip"
                                            :class="
                                                activeCondition === filter.value
                                                    ? 'collection-filter-chip-active'
                                                    : ''
                                            "
                                        >
                                            {{ filter.label }}
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <p
                                        class="mb-2 text-[10px] font-black uppercase tracking-[0.26em] text-slate-400"
                                    >
                                        Gender
                                    </p>

                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            :disabled="isFiltering"
                                            @click="setGenderFilter('')"
                                            class="collection-filter-chip"
                                            :class="
                                                activeGender === ''
                                                    ? 'collection-filter-chip-active'
                                                    : ''
                                            "
                                        >
                                            All
                                        </button>

                                        <button
                                            v-for="filter in genderFilters"
                                            :key="filter.value"
                                            type="button"
                                            :disabled="isFiltering"
                                            @click="
                                                setGenderFilter(filter.value)
                                            "
                                            class="collection-filter-chip"
                                            :class="
                                                activeGender === filter.value
                                                    ? 'collection-filter-chip-active'
                                                    : ''
                                            "
                                        >
                                            {{ filter.label }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="hasActiveCollectionFilters"
                                class="mt-4 flex flex-wrap items-center gap-2"
                            >
                                <button
                                    v-if="search"
                                    type="button"
                                    :disabled="isFiltering"
                                    @click="clearSearch"
                                    class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-500 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60 active:scale-95"
                                >
                                    Clear search
                                </button>

                                <button
                                    type="button"
                                    :disabled="isFiltering"
                                    @click="clearCollectionFilters"
                                    class="rounded-2xl border border-[#0b3a56]/15 bg-[#eef8fb] px-4 py-2 text-xs font-black text-[#0b3a56] transition hover:border-[#0b3a56]/30 hover:bg-white disabled:cursor-not-allowed disabled:opacity-60 active:scale-95"
                                >
                                    Clear all filters
                                </button>
                            </div>
                        </div>

                        <!-- Full Image Watch Cards -->
                        <!-- Full Image Watch Cards -->
                        <div class="relative">
                            <TransitionGroup
                                v-if="watchesData.length"
                                name="watch-card"
                                tag="div"
                                class="grid grid-cols-2 gap-3 transition duration-300 sm:gap-5 lg:grid-cols-3 lg:gap-6"
                                :class="
                                    isFiltering
                                        ? 'pointer-events-none opacity-45 blur-[1px]'
                                        : ''
                                "
                            >
                                <Link
                                    v-for="watch in watchesData"
                                    :key="watch.id"
                                    :href="watchDetailsLink(watch)"
                                    :aria-label="watchCardLabel(watch)"
                                    class="shop-grid-card group relative block cursor-pointer overflow-hidden rounded-[1.35rem] border border-slate-200 bg-white shadow-xl shadow-[#0b3a56]/10 outline-none transition duration-300 hover:-translate-y-1 hover:border-[#0b3a56]/25 hover:shadow-2xl hover:shadow-[#0b3a56]/15 focus-visible:ring-4 focus-visible:ring-[#0b3a56]/20 sm:rounded-[2rem]"
                                >
                                    <div
                                        class="relative aspect-[4/5] overflow-hidden bg-slate-100"
                                    >
                                        <img
                                            v-if="watch.image_url"
                                            :src="watch.image_url"
                                            :alt="watch.model_name"
                                            class="h-full w-full object-cover transition duration-700 group-hover:scale-[1.045]"
                                        />

                                        <div
                                            v-else
                                            class="grid h-full w-full place-items-center text-sm text-slate-400"
                                        >
                                            No Image
                                        </div>

                                        <div class="watch-card-overlay" />

                                        <div
                                            class="absolute left-2.5 right-2.5 top-2.5 z-20 flex items-start justify-between gap-2 sm:left-4 sm:right-4 sm:top-4 sm:gap-3"
                                        >
                                            <div class="flex flex-wrap gap-2">
                                                <span
                                                    class="watch-badge-available"
                                                >
                                                    {{
                                                        conditionLabel(
                                                            watch.condition,
                                                        )
                                                    }}
                                                </span>

                                                <span
                                                    v-if="watch.is_featured"
                                                    class="watch-badge-drop"
                                                >
                                                    Featured
                                                </span>
                                            </div>
                                        </div>

                                        <div
                                            class="absolute inset-x-0 bottom-0 z-20 p-3 sm:p-5"
                                        >
                                            <div class="max-w-full">
                                                <h3
                                                    class="line-clamp-2 max-w-[96%] text-sm font-black leading-[1.08] tracking-[-0.025em] text-white drop-shadow-[0_3px_12px_rgba(0,0,0,0.45)] sm:text-xl"
                                                >
                                                    {{ watch.model_name }}
                                                </h3>

                                                <div
                                                    class="mt-2 flex flex-wrap items-baseline gap-x-2 gap-y-0.5"
                                                >
                                                    <p
                                                        class="line-clamp-1 text-[13px] font-black leading-none tracking-[-0.025em] text-white drop-shadow-[0_3px_12px_rgba(0,0,0,0.45)] sm:text-lg"
                                                    >
                                                        {{
                                                            formatMoney(
                                                                watchDisplayPrice(
                                                                    watch,
                                                                ),
                                                            )
                                                        }}
                                                    </p>

                                                    <p
                                                        v-if="
                                                            hasSuggestedSrp(
                                                                watch,
                                                            )
                                                        "
                                                        class="line-clamp-1 text-[10px] font-bold leading-none text-white/60 line-through decoration-white/60 decoration-1 drop-shadow-[0_3px_12px_rgba(0,0,0,0.45)] sm:text-xs"
                                                    >
                                                        SRP
                                                        {{
                                                            formatMoney(
                                                                watch.suggested_srp,
                                                            )
                                                        }}
                                                    </p>
                                                </div>

                                                <span class="watch-card-cta">
                                                    <span
                                                        >View More Details</span
                                                    >
                                                    <span
                                                        class="watch-card-cta-arrow"
                                                        >→</span
                                                    >
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </Link>
                            </TransitionGroup>

                            <div
                                v-else
                                class="rounded-[2rem] border border-dashed border-slate-300 bg-slate-50 p-10 text-center transition duration-300"
                                :class="
                                    isFiltering ? 'opacity-45 blur-[1px]' : ''
                                "
                            >
                                <p
                                    class="text-base font-black text-[#071923] sm:text-lg"
                                >
                                    No available watches found.
                                </p>
                                <p class="mt-2 text-sm text-slate-500">
                                    Try clearing your search or message us
                                    directly.
                                </p>

                                <a
                                    :href="messengerLink()"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="mt-6 inline-flex rounded-2xl bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] px-5 py-3 text-sm font-black text-white shadow-lg shadow-[#0b3a56]/15 transition hover:brightness-110"
                                >
                                    Message Us
                                </a>
                            </div>

                            <Transition name="filter-loading-fade">
                                <div
                                    v-if="isFiltering"
                                    class="filter-loading-overlay"
                                >
                                    <div class="filter-loading-card">
                                        <span
                                            class="filter-spinner filter-spinner-large"
                                        />

                                        <div>
                                            <p
                                                class="text-sm font-black text-[#071923]"
                                            >
                                                Finding matching watches
                                            </p>

                                            <p
                                                class="mt-1 text-xs text-slate-500"
                                            >
                                                Hold on while the collection
                                                refreshes.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                        <div
                            v-if="watchPaginationLinks.length > 3"
                            class="mt-8 flex flex-wrap justify-center gap-2"
                        >
                            <button
                                v-for="link in watchPaginationLinks"
                                :key="link.label"
                                type="button"
                                :disabled="
                                    !link.url || link.active || isFiltering
                                "
                                class="inline-flex min-h-10 min-w-10 items-center justify-center rounded-xl border px-4 py-2 text-sm font-black transition active:scale-95 disabled:cursor-not-allowed"
                                :class="[
                                    link.active
                                        ? 'border-[#071923] bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] text-white shadow-lg shadow-[#0b3a56]/15'
                                        : 'border-slate-200 bg-white text-slate-600 hover:border-[#0b3a56]/40 hover:bg-[#eef8fb] hover:text-[#0b3a56]',
                                    !link.url || isFiltering
                                        ? 'opacity-45'
                                        : '',
                                ]"
                                @click="goToWatchPage(link)"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </section>
            </Transition>

            <!-- Transactions + About Us -->
            <section id="transactions" class="bg-[#f8fafc] py-10 sm:py-14">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div
                        class="mb-5 flex flex-col gap-4 sm:mb-7 sm:flex-row sm:items-end sm:justify-between"
                    >
                        <div>
                            <p
                                class="text-[10px] font-black uppercase tracking-[0.35em] text-[#0b3a56]"
                            >
                                Client Handover
                            </p>

                            <h2
                                class="mt-2 text-2xl font-black tracking-tight text-[#071923] sm:text-4xl"
                            >
                                Recent Transactions
                            </h2>

                            <p
                                class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-500 sm:text-base"
                            >
                                A glimpse of successful purchases and happy
                                clients.
                            </p>
                        </div>

                        <div
                            v-if="transactions.length > 1"
                            class="hidden items-center gap-2 sm:flex"
                        >
                            <button
                                type="button"
                                @click="scrollTransactions(-1)"
                                class="transaction-arrow"
                                aria-label="Previous transactions"
                            >
                                ‹
                            </button>

                            <button
                                type="button"
                                @click="scrollTransactions(1)"
                                class="transaction-arrow"
                                aria-label="Next transactions"
                            >
                                ›
                            </button>
                        </div>
                    </div>

                    <div v-if="transactions.length" class="relative">
                        <div
                            class="pointer-events-none absolute -left-1 top-0 z-10 hidden h-full w-16 bg-gradient-to-r from-[#f8fafc] to-transparent sm:block"
                        />

                        <div
                            class="pointer-events-none absolute -right-1 top-0 z-10 hidden h-full w-16 bg-gradient-to-l from-[#f8fafc] to-transparent sm:block"
                        />

                        <div
                            ref="transactionStrip"
                            class="transaction-carousel flex snap-x gap-4 overflow-x-auto pb-4"
                        >
                            <article
                                v-for="transaction in transactions"
                                :key="transaction.id"
                                class="transaction-card group"
                            >
                                <div
                                    class="relative aspect-[4/5] overflow-hidden bg-slate-100"
                                >
                                    <img
                                        v-if="transaction.image_url"
                                        :src="transaction.image_url"
                                        :alt="transaction.title"
                                        class="h-full w-full object-cover transition duration-700 group-hover:scale-[1.04]"
                                    />

                                    <div
                                        v-else
                                        class="grid h-full w-full place-items-center text-sm text-slate-400"
                                    >
                                        No Image
                                    </div>

                                    <div class="transaction-card-overlay" />

                                    <div
                                        class="absolute left-4 right-4 top-4 z-20 flex items-center justify-between gap-2"
                                    ></div>

                                    <div
                                        class="absolute inset-x-0 bottom-0 z-20 p-4 sm:p-5"
                                    >
                                        <p
                                            class="text-[10px] font-black uppercase tracking-[0.24em] text-white/60"
                                        >
                                            Successful Purchase
                                        </p>

                                        <h3
                                            class="mt-1 line-clamp-2 text-xl font-black leading-tight tracking-[-0.03em] text-white drop-shadow-[0_3px_12px_rgba(0,0,0,0.45)]"
                                        >
                                            {{ transaction.title }}
                                        </h3>

                                        <p
                                            class="mt-2 line-clamp-2 text-sm font-medium leading-relaxed text-gray-100"
                                        >
                                            {{
                                                transaction.caption ||
                                                "Thank you for trusting Watch Gallery Manila."
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <p
                            v-if="transactions.length > 1"
                            class="mt-1 text-center text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400 sm:hidden"
                        >
                            Swipe to view more handovers
                        </p>
                    </div>

                    <div
                        v-else
                        class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm"
                    >
                        <p class="font-black text-[#071923]">
                            No transaction photos yet.
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Add visible transactions from the admin panel.
                        </p>
                    </div>

                    <!-- About Us Section -->
                    <section
                        v-if="hasAboutUs"
                        id="about-section"
                        class="about-luxury-section mt-10 overflow-hidden rounded-[2rem] border border-[#071923]/10 bg-[#071923] shadow-2xl shadow-[#0b3a56]/15 sm:mt-14 sm:rounded-[2.5rem]"
                    >
                        <div class="relative overflow-hidden">
                            <div
                                class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_18%_0%,rgba(255,255,255,0.16),transparent_34%),radial-gradient(circle_at_90%_18%,rgba(11,58,86,0.38),transparent_32%),linear-gradient(135deg,#061725_0%,#0b3a56_48%,#071923_100%)]"
                            />

                            <div
                                class="pointer-events-none absolute inset-x-8 top-0 h-px bg-gradient-to-r from-transparent via-white/35 to-transparent"
                            />

                            <div
                                class="relative grid gap-0 lg:grid-cols-[0.94fr_1.06fr]"
                            >
                                <!-- Content Panel -->
                                <div
                                    class="relative z-10 flex flex-col justify-between p-5 text-white sm:p-8 lg:p-10"
                                >
                                    <div>
                                        <div
                                            class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3.5 py-2 text-[10px] font-black uppercase tracking-[0.28em] text-white/70 backdrop-blur-xl"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full bg-[#f0d8b6]"
                                            />
                                            {{
                                                aboutUs.eyebrow ||
                                                "About Watch Gallery Manila"
                                            }}
                                        </div>

                                        <h2
                                            class="mt-5 max-w-xl text-3xl font-black leading-[0.98] tracking-[-0.055em] text-white sm:text-5xl"
                                        >
                                            {{
                                                aboutUs.title ||
                                                "Curated watches, trusted service."
                                            }}
                                        </h2>

                                        <p
                                            v-if="aboutUs.body"
                                            class="mt-5 max-w-xl whitespace-pre-line text-sm leading-7 text-white/70 sm:text-base sm:leading-8"
                                        >
                                            {{ aboutUs.body }}
                                        </p>

                                        <div
                                            class="mt-7 grid grid-cols-3 gap-2.5 sm:gap-3"
                                        ></div>

                                        <div
                                            v-if="hasOwnerProfile"
                                            class="mt-7 overflow-hidden rounded-[1.75rem] border border-white/10 bg-white/[0.08] shadow-2xl shadow-black/10 backdrop-blur-xl"
                                        >
                                            <div
                                                class="flex items-center gap-4 p-4 sm:p-5"
                                            >
                                                <div
                                                    class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl border border-white/10 bg-white/10 shadow-lg shadow-black/10 sm:h-24 sm:w-24"
                                                >
                                                    <img
                                                        v-if="
                                                            aboutUs.owner_image_url
                                                        "
                                                        :src="
                                                            aboutUs.owner_image_url
                                                        "
                                                        :alt="
                                                            aboutUs.dealer_name ||
                                                            'Owner photo'
                                                        "
                                                        class="h-full w-full object-cover"
                                                    />

                                                    <div
                                                        v-else
                                                        class="grid h-full place-items-center text-base font-black text-white"
                                                    >
                                                        {{ ownerInitials }}
                                                    </div>
                                                </div>

                                                <div class="min-w-0 flex-1">
                                                    <p
                                                        class="text-[10px] font-black uppercase tracking-[0.25em] text-[#f0d8b6]/80"
                                                    >
                                                        Meet The Owner
                                                    </p>

                                                    <p
                                                        v-if="
                                                            aboutUs.dealer_name
                                                        "
                                                        class="mt-1 line-clamp-1 text-xl font-black leading-tight text-white sm:text-2xl"
                                                    >
                                                        {{
                                                            aboutUs.dealer_name
                                                        }}
                                                    </p>

                                                    <p
                                                        v-if="aboutUs.owner_bio"
                                                        class="mt-2 line-clamp-3 whitespace-pre-line text-xs font-semibold leading-relaxed text-white/60 sm:text-sm"
                                                    >
                                                        {{ aboutUs.owner_bio }}
                                                    </p>
                                                </div>
                                            </div>

                                            <p
                                                v-if="aboutUs.dealer_message"
                                                class="border-t border-white/10 px-4 py-4 text-sm font-semibold leading-relaxed text-white/72 sm:px-5 sm:text-base"
                                            >
                                                {{ aboutUs.dealer_message }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-8 grid gap-2 sm:grid-cols-2">
                                        <a
                                            :href="messengerLink()"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center justify-center rounded-2xl bg-white px-5 py-3.5 text-sm font-black text-[#071923] shadow-xl shadow-black/15 transition hover:bg-white/90 active:scale-95"
                                        >
                                            Message Us
                                        </a>

                                        <button
                                            type="button"
                                            @click.prevent.stop="scrollToShop"
                                            class="inline-flex items-center justify-center rounded-2xl border border-white/15 bg-white/10 px-5 py-3.5 text-sm font-black text-white backdrop-blur-xl transition hover:bg-white/15 active:scale-95"
                                        >
                                            View Collection
                                        </button>
                                    </div>
                                </div>

                                <!-- Gallery Panel -->
                                <div class="relative z-10 p-3 sm:p-5 lg:p-6">
                                    <div
                                        class="relative min-h-[380px] overflow-hidden rounded-[1.75rem] border border-white/10 bg-white/10 shadow-2xl shadow-black/20 sm:min-h-[560px] sm:rounded-[2rem]"
                                    >
                                        <template v-if="currentAboutPicture">
                                            <Transition
                                                name="about-carousel"
                                                mode="out-in"
                                            >
                                                <img
                                                    :key="
                                                        currentAboutPicture.image_url
                                                    "
                                                    :src="
                                                        currentAboutPicture.image_url
                                                    "
                                                    :alt="
                                                        currentAboutPicture.title
                                                    "
                                                    class="absolute inset-0 h-full w-full object-cover"
                                                />
                                            </Transition>

                                            <div
                                                class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/88 via-black/26 to-transparent"
                                            />

                                            <div
                                                class="absolute left-4 right-4 top-4 flex items-center justify-between gap-3"
                                            >
                                                <div
                                                    class="rounded-full border border-white/15 bg-black/25 px-3 py-2 text-[10px] font-black uppercase tracking-[0.22em] text-white/75 backdrop-blur-xl"
                                                >
                                                    Showroom Gallery
                                                </div>

                                                <div
                                                    v-if="
                                                        aboutPictures.length > 1
                                                    "
                                                    class="rounded-full border border-white/15 bg-black/25 px-3 py-2 text-[10px] font-black text-white/80 backdrop-blur-xl"
                                                >
                                                    {{ aboutSlideIndex + 1 }} /
                                                    {{ aboutPictures.length }}
                                                </div>
                                            </div>

                                            <div
                                                v-if="aboutPictures.length > 1"
                                                class="absolute inset-x-4 top-1/2 flex -translate-y-1/2 justify-between"
                                            >
                                                <button
                                                    type="button"
                                                    @click="previousAboutSlide"
                                                    class="about-gallery-arrow"
                                                    aria-label="Previous showroom photo"
                                                >
                                                    ‹
                                                </button>

                                                <button
                                                    type="button"
                                                    @click="nextAboutSlide"
                                                    class="about-gallery-arrow"
                                                    aria-label="Next showroom photo"
                                                >
                                                    ›
                                                </button>
                                            </div>

                                            <div
                                                class="absolute bottom-0 left-0 right-0 p-4 sm:p-6"
                                            >
                                                <h3
                                                    class="line-clamp-2 text-2xl font-black leading-tight tracking-[-0.04em] text-white drop-shadow-[0_4px_16px_rgba(0,0,0,0.45)] sm:text-4xl"
                                                >
                                                    {{
                                                        currentAboutPicture.title
                                                    }}
                                                </h3>

                                                <p
                                                    class="mt-2 text-sm font-bold text-white/65"
                                                >
                                                    {{
                                                        currentAboutPicture.subtitle
                                                    }}
                                                </p>
                                            </div>
                                        </template>

                                        <div
                                            v-else
                                            class="grid h-full min-h-[380px] place-items-center p-8 text-center text-white sm:min-h-[560px]"
                                        >
                                            <div>
                                                <p
                                                    class="text-[10px] font-black uppercase tracking-[0.35em] text-white/55"
                                                >
                                                    Showroom Gallery
                                                </p>

                                                <p
                                                    class="mt-3 text-3xl font-black sm:text-4xl"
                                                >
                                                    Showroom Photos
                                                </p>

                                                <p
                                                    class="mt-3 text-sm text-white/65"
                                                >
                                                    Add showroom photos from the
                                                    admin About Us page.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        v-if="aboutPictures.length > 1"
                                        class="mt-3 flex gap-2 overflow-x-auto rounded-[1.35rem] border border-white/10 bg-white/10 p-2 backdrop-blur-xl"
                                    >
                                        <button
                                            v-for="(
                                                picture, index
                                            ) in aboutPictures"
                                            :key="`${picture.image_url}-strip-${index}`"
                                            type="button"
                                            @click="aboutSlideIndex = index"
                                            class="h-16 w-24 shrink-0 overflow-hidden rounded-2xl border bg-slate-100 transition sm:h-20 sm:w-32"
                                            :class="
                                                aboutSlideIndex === index
                                                    ? 'border-white ring-2 ring-white/30'
                                                    : 'border-white/10 opacity-60 hover:opacity-100'
                                            "
                                        >
                                            <img
                                                :src="picture.image_url"
                                                :alt="
                                                    picture.title ||
                                                    'Showroom photo'
                                                "
                                                class="h-full w-full object-cover"
                                            />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </section>
        </main>

        <!-- Mobile Sticky CTA -->
        <div
            class="fixed left-3 right-3 z-40 grid gap-2 rounded-[1.5rem] border border-white/70 bg-white/85 p-1.5 shadow-2xl shadow-slate-900/15 backdrop-blur-2xl sm:hidden bottom-[calc(env(safe-area-inset-bottom)+0.75rem)]"
            :class="hasAboutUs ? 'grid-cols-3' : 'grid-cols-2'"
        >
            <button
                type="button"
                @click.prevent.stop="scrollToShop"
                class="rounded-[1.15rem] border border-slate-200 bg-white px-2 py-3 text-[11px] font-black leading-tight text-[#071923] shadow-sm active:scale-95"
            >
                Latest
            </button>

            <button
                v-if="hasAboutUs"
                type="button"
                @click="scrollToAbout"
                class="rounded-[1.15rem] border border-slate-200 bg-white px-2 py-3 text-[11px] font-black leading-tight text-[#071923] shadow-sm active:scale-95"
            >
                About
            </button>

            <a
                :href="messengerLink()"
                target="_blank"
                rel="noopener noreferrer"
                class="rounded-[1.15rem] border border-slate-200 bg-white px-2 py-3 text-center text-[11px] font-black leading-tight text-[#071923] shadow-sm active:scale-95"
            >
                Inquire
            </a>
        </div>
    </div>
</template>

<style scoped>
.hero-panel {
    overflow: hidden;
    backface-visibility: hidden;
    contain: layout paint;
    will-change: max-height, opacity, transform;
    transform: translateZ(0);
    transition:
        max-height 0.42s cubic-bezier(0.22, 1, 0.36, 1),
        opacity 0.26s ease,
        transform 0.34s cubic-bezier(0.22, 1, 0.36, 1);
}

.hero-open {
    max-height: 1400px;
    opacity: 1;
    transform: translateY(0);
}

.hero-closed {
    max-height: 0;
    opacity: 0;
    transform: translateY(-12px) scale(0.992);
}

.hero-to-shop.hero-open {
    transform: translateY(-6px) scale(0.996);
}

.hero-to-hero.hero-open {
    animation: heroReturn 0.42s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.collage-grid {
    display: grid;
    will-change: transform, opacity;
    backface-visibility: hidden;
    transform: translateZ(0);
    height: min(760px, calc(100vh - 170px));
    min-height: 560px;
    grid-template-columns: 1.25fr 1.25fr 0.95fr 0.95fr;
    grid-template-rows: 1fr 1fr;
    grid-template-areas:
        "main main two three"
        "main main four more";
    gap: 5px;
    overflow: hidden;
    border-radius: 1.7rem;
    background: #ffffff;
}

.collage-cell {
    position: relative;
    overflow: hidden;
    background: #f1f5f9;
}

.collage-main {
    grid-area: main;
}

.collage-2 {
    grid-area: two;
}

.collage-3 {
    grid-area: three;
}

.collage-4 {
    grid-area: four;
}

.collage-more {
    grid-area: more;
}

.collage-img {
    height: 100%;
    width: 100%;
    object-fit: cover;
    transition: transform 0.7s ease;
}

.collage-cell:hover .collage-img,
.group:hover .collage-img {
    transform: scale(1.045);
}

.collage-gradient {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(to top, rgba(0, 0, 0, 0.82), transparent 48%),
        linear-gradient(to right, rgba(0, 0, 0, 0.2), transparent 40%);
}

.collage-gradient-small {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.76), transparent 58%);
}

.shop-now-button {
    position: relative;
    overflow: hidden;
    border-radius: 999px;
    border: 1px solid rgba(7, 25, 35, 0.08);
    background: rgba(255, 255, 255, 0.94);
    padding: 0.95rem 1rem 0.95rem 1.45rem;
    color: #071923;
    box-shadow:
        0 26px 90px rgba(15, 23, 42, 0.2),
        0 0 0 1px rgba(255, 255, 255, 0.8),
        inset 0 1px 0 rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(18px);
    transform: translateY(0);
    transition:
        transform 0.28s ease,
        border-color 0.28s ease,
        box-shadow 0.28s ease,
        background 0.28s ease;
}

.shop-now-shine {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(
            circle at 20% 0%,
            rgba(11, 58, 86, 0.16),
            transparent 34%
        ),
        linear-gradient(
            120deg,
            transparent 0%,
            rgba(11, 58, 86, 0.09) 45%,
            transparent 58%
        );
    opacity: 0.85;
    transition: opacity 0.28s ease;
}

.shop-now-eyebrow {
    display: block;
    font-size: 0.62rem;
    font-weight: 900;
    letter-spacing: 0.24em;
    text-transform: uppercase;
    color: #0b3a56;
}

.shop-now-main {
    display: block;
    margin-top: 0.08rem;
    font-size: 1.45rem;
    font-weight: 950;
    letter-spacing: -0.045em;
    color: #071923;
    line-height: 1;
}

.shop-now-icon {
    display: grid;
    height: 3rem;
    width: 3rem;
    place-items: center;
    border-radius: 999px;
    background: linear-gradient(135deg, #061725, #0b3a56, #071923);
    color: #ffffff;
    font-size: 1.35rem;
    font-weight: 950;
    box-shadow: 0 14px 36px rgba(11, 58, 86, 0.22);
    transition:
        transform 0.28s ease,
        filter 0.28s ease;
}

button:hover .shop-now-button {
    border-color: rgba(11, 58, 86, 0.2);
    box-shadow:
        0 30px 100px rgba(15, 23, 42, 0.26),
        0 0 34px rgba(11, 58, 86, 0.12),
        inset 0 1px 0 rgba(255, 255, 255, 1);
    transform: translateY(-3px) scale(1.015);
}

button:hover .shop-now-shine {
    opacity: 1;
}

button:hover .shop-now-icon {
    filter: brightness(1.12);
    transform: translateX(3px);
}

.collage-button-transitioning {
    pointer-events: none;
}

.collage-button-transitioning .collage-grid {
    animation: collageDepthExit 0.36s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.collage-button-transitioning .collage-cell {
    animation: none;
}

.collage-button-transitioning .collage-main {
    animation-delay: 0s;
}

.collage-button-transitioning .collage-2 {
    animation-delay: 0s;
}

.collage-button-transitioning .collage-3 {
    animation-delay: 0s;
}

.collage-button-transitioning .collage-4 {
    animation-delay: 0s;
}

.collage-button-transitioning .collage-more {
    animation-delay: 0s;
}

.collage-button-transitioning .shop-now-button {
    animation: shopButtonLaunch 0.36s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.collage-button-transitioning .shop-now-icon {
    animation: shopArrowLaunch 0.34s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.shop-transition-flash {
    pointer-events: none;
    position: absolute;
    inset: auto auto 50% 50%;
    z-index: 18;
    height: 16rem;
    width: 16rem;
    border-radius: 999px;
    background: radial-gradient(
        circle,
        rgba(255, 255, 255, 0.55) 0%,
        rgba(255, 255, 255, 0.24) 36%,
        rgba(11, 58, 86, 0.12) 58%,
        transparent 72%
    );
    opacity: 0;
    transform: translate(-50%, 50%) scale(0.1);
}

.collage-button-transitioning .shop-transition-flash {
    animation: shopFlash 0.34s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.shop-section {
    scroll-margin-top: 88px;
    will-change: transform, opacity;
    transform: translateZ(0);
}

#about-section {
    scroll-margin-top: 92px;
}

.transaction-strip,
.about-section-card * {
    scrollbar-width: thin;
    scrollbar-color: rgba(11, 58, 86, 0.28) transparent;
}

.transaction-strip::-webkit-scrollbar,
.about-section-card *::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}

.transaction-strip::-webkit-scrollbar-thumb,
.about-section-card *::-webkit-scrollbar-thumb {
    border-radius: 999px;
    background: rgba(11, 58, 86, 0.24);
}
.transaction-carousel {
    scroll-padding-left: 0.25rem;
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.transaction-carousel::-webkit-scrollbar {
    display: none;
}

.transaction-card {
    position: relative;
    width: 78vw;
    max-width: 330px;
    flex: 0 0 auto;
    scroll-snap-align: start;
    overflow: hidden;
    border-radius: 2rem;
    border: 1px solid rgba(226, 232, 240, 0.95);
    background: #ffffff;
    box-shadow:
        0 18px 46px rgba(15, 23, 42, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.8);
    transition:
        transform 0.28s ease,
        border-color 0.28s ease,
        box-shadow 0.28s ease;
}

.transaction-card:hover {
    transform: translateY(-4px);
    border-color: rgba(11, 58, 86, 0.22);
    box-shadow:
        0 24px 60px rgba(11, 58, 86, 0.13),
        inset 0 1px 0 rgba(255, 255, 255, 0.95);
}

.transaction-card-overlay {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(
            to top,
            rgba(0, 0, 0, 0.82) 0%,
            rgba(0, 0, 0, 0.48) 36%,
            rgba(0, 0, 0, 0.12) 62%,
            rgba(0, 0, 0, 0.04) 100%
        ),
        radial-gradient(
            circle at 50% 100%,
            rgba(11, 58, 86, 0.26),
            transparent 52%
        );
}

.transaction-date-pill,
.transaction-view-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.18);
    background: rgba(255, 255, 255, 0.16);
    padding: 0.55rem 0.8rem;
    color: rgba(255, 255, 255, 0.88);
    font-size: 0.62rem;
    font-weight: 950;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    backdrop-filter: blur(16px);
    box-shadow:
        0 10px 28px rgba(0, 0, 0, 0.16),
        inset 0 1px 0 rgba(255, 255, 255, 0.18);
}

.transaction-view-pill {
    background: rgba(255, 255, 255, 0.92);
    color: #071923;
}

.transaction-arrow {
    display: grid;
    height: 2.5rem;
    width: 2.5rem;
    place-items: center;
    border-radius: 999px;
    border: 1px solid rgba(226, 232, 240, 0.95);
    background: rgba(255, 255, 255, 0.92);
    color: #071923;
    font-size: 1.25rem;
    font-weight: 950;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    transition:
        transform 0.22s ease,
        border-color 0.22s ease,
        background 0.22s ease,
        box-shadow 0.22s ease;
}

.transaction-arrow:hover {
    transform: translateY(-1px);
    border-color: rgba(11, 58, 86, 0.22);
    background: #ffffff;
    box-shadow: 0 14px 32px rgba(11, 58, 86, 0.1);
}

@media (min-width: 640px) {
    .transaction-card {
        width: 320px;
    }
}

@media (min-width: 1024px) {
    .transaction-card {
        width: 340px;
        max-width: 340px;
    }
}

@media (max-width: 639px) {
    .transaction-card {
        width: 82vw;
        max-width: 310px;
        border-radius: 1.65rem;
    }

    .transaction-date-pill,
    .transaction-view-pill {
        padding: 0.48rem 0.68rem;
        font-size: 0.55rem;
        letter-spacing: 0.11em;
    }

    .watch-badge-available,
    .watch-badge-gender,
    .watch-badge-drop {
        padding: 0.28rem 0.42rem;
        font-size: 0.42rem;
        letter-spacing: 0.08em;
        box-shadow:
            0 5px 16px rgba(0, 0, 0, 0.12),
            inset 0 1px 0 rgba(255, 255, 255, 0.14);
    }

    .watch-card-cta {
        margin-top: 0.55rem;
        height: 1.95rem;
        border-radius: 0.7rem;
        font-size: 0.52rem;
        gap: 0.25rem;
        letter-spacing: -0.01em;
        box-shadow:
            0 10px 24px rgba(0, 0, 0, 0.18),
            inset 0 1px 0 rgba(255, 255, 255, 0.16);
    }

    .watch-card-cta-arrow {
        font-size: 0.65rem;
    }
}
.shop-page-enter-active,
.shop-page-leave-active {
    transition:
        opacity 0.28s ease,
        transform 0.34s cubic-bezier(0.22, 1, 0.36, 1);
}

.shop-page-enter-from,
.shop-page-leave-to {
    opacity: 0;
    transform: translateY(14px);
}

.shop-section-opened .shop-toolbar-card {
    animation: shopToolbarArrive 0.38s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.shop-section-opened .shop-grid-card {
    animation: shopCardArrive 0.34s cubic-bezier(0.22, 1, 0.36, 1) both;
    will-change: transform, opacity;
    backface-visibility: hidden;
    transform: translateZ(0);
}

.shop-section-opened .shop-grid-card:nth-child(1) {
    animation-delay: 0s;
}

.shop-section-opened .shop-grid-card:nth-child(2) {
    animation-delay: 0.025s;
}

.shop-section-opened .shop-grid-card:nth-child(3) {
    animation-delay: 0.05s;
}

.shop-section-opened .shop-grid-card:nth-child(4) {
    animation-delay: 0.075s;
}

.shop-section-opened .shop-grid-card:nth-child(5) {
    animation-delay: 0.1s;
}

.shop-section-opened .shop-grid-card:nth-child(6) {
    animation-delay: 0.125s;
}

.watch-card-enter-active,
.watch-card-leave-active {
    transition: all 0.35s ease;
}

.watch-card-enter-from,
.watch-card-leave-to {
    opacity: 0;
    transform: translateY(18px);
}

.watch-card-move {
    transition: transform 0.35s ease;
}

.collection-filter-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    border: 1px solid rgba(15, 23, 42, 0.08);
    background: rgba(248, 250, 252, 0.92);
    padding: 0.7rem 1rem;
    font-size: 0.76rem;
    font-weight: 900;
    color: #475569;
    box-shadow:
        0 8px 24px rgba(15, 23, 42, 0.04),
        inset 0 1px 0 rgba(255, 255, 255, 0.9);
    transition:
        transform 0.22s ease,
        border-color 0.22s ease,
        background 0.22s ease,
        color 0.22s ease,
        box-shadow 0.22s ease;
}

.collection-filter-chip:hover {
    transform: translateY(-1px);
    border-color: rgba(11, 58, 86, 0.18);
    background: #ffffff;
    color: #0b3a56;
    box-shadow:
        0 12px 28px rgba(11, 58, 86, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 1);
}

.collection-filter-chip-active {
    border-color: rgba(11, 58, 86, 0.28);
    background: linear-gradient(135deg, #061725, #0b3a56, #071923);
    color: #ffffff;
    box-shadow:
        0 14px 36px rgba(11, 58, 86, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.16);
}

.shop-grid-card::after {
    content: "";
    pointer-events: none;
    position: absolute;
    inset: 0;
    z-index: 24;
    border-radius: inherit;
    border: 1px solid rgba(255, 255, 255, 0);
    opacity: 0;
    transition:
        opacity 0.28s ease,
        border-color 0.28s ease,
        box-shadow 0.28s ease;
}

.shop-grid-card:hover::after {
    border-color: rgba(255, 255, 255, 0.42);
    opacity: 1;
    box-shadow:
        inset 0 0 0 1px rgba(255, 255, 255, 0.16),
        inset 0 -120px 160px rgba(255, 255, 255, 0.06);
}

.watch-card-overlay {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(
            to top,
            rgba(0, 0, 0, 0.82) 0%,
            rgba(0, 0, 0, 0.56) 30%,
            rgba(0, 0, 0, 0.16) 58%,
            rgba(0, 0, 0, 0.08) 100%
        ),
        radial-gradient(
            circle at 50% 92%,
            rgba(11, 58, 86, 0.24),
            transparent 46%
        );
}

.watch-badge-available,
.watch-badge-gender,
.watch-badge-drop {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    padding: 0.48rem 0.78rem;
    font-size: 0.62rem;
    font-weight: 900;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    backdrop-filter: blur(14px);
    box-shadow:
        0 8px 24px rgba(0, 0, 0, 0.14),
        inset 0 1px 0 rgba(255, 255, 255, 0.18);
}

.watch-badge-available {
    border: 1px solid rgba(255, 255, 255, 0.18);
    background: rgba(255, 255, 255, 0.14);
    color: rgba(255, 255, 255, 0.9);
}

.watch-badge-gender {
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: rgba(255, 255, 255, 0.9);
    color: #071923;
}

.watch-badge-drop {
    border: 1px solid rgba(147, 197, 253, 0.24);
    background: rgba(11, 58, 86, 0.38);
    color: #dbeafe;
}

.watch-card-hover-hint {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.18);
    background: rgba(255, 255, 255, 0.12);
    padding: 0.48rem 0.72rem;
    font-size: 0.68rem;
    font-weight: 900;
    color: rgba(255, 255, 255, 0.82);
    opacity: 0.82;
    backdrop-filter: blur(14px);
    box-shadow:
        0 8px 24px rgba(0, 0, 0, 0.14),
        inset 0 1px 0 rgba(255, 255, 255, 0.16);
    transition:
        opacity 0.24s ease,
        transform 0.24s ease,
        background 0.24s ease,
        color 0.24s ease;
}

.shop-grid-card:hover .watch-card-hover-hint {
    background: rgba(255, 255, 255, 0.96);
    color: #071923;
    opacity: 1;
    transform: translateX(2px);
}

.watch-card-cta {
    margin-top: 1rem;
    display: inline-flex;
    height: 2.9rem;
    width: 100%;
    align-items: center;
    justify-content: center;
    gap: 0.55rem;
    border-radius: 1rem;
    border: 1px solid rgba(255, 255, 255, 0.22);
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.22),
        rgba(255, 255, 255, 0.1)
    );
    color: #ffffff;
    font-size: 0.78rem;
    font-weight: 950;
    letter-spacing: -0.01em;
    box-shadow:
        0 16px 40px rgba(0, 0, 0, 0.22),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(18px);
    transition:
        transform 0.24s ease,
        border-color 0.24s ease,
        background 0.24s ease,
        color 0.24s ease,
        box-shadow 0.24s ease;
}

.shop-grid-card:hover .watch-card-cta {
    border-color: rgba(255, 255, 255, 0.92);
    background: rgba(255, 255, 255, 0.96);
    color: #071923;
    box-shadow:
        0 18px 48px rgba(0, 0, 0, 0.24),
        inset 0 1px 0 rgba(255, 255, 255, 1);
    transform: translateY(-1px);
}

.watch-card-cta-arrow {
    transition: transform 0.24s ease;
}

.shop-grid-card:hover .watch-card-cta-arrow {
    transform: translateX(3px);
}

@media (max-width: 639px) {
    .collection-filter-chip {
        padding: 0.62rem 0.86rem;
        font-size: 0.7rem;
    }

    .watch-card-hover-hint {
        padding: 0.42rem 0.62rem;
        font-size: 0.62rem;
    }

    .watch-card-cta {
        height: 2.75rem;
        font-size: 0.74rem;
    }
}

.about-carousel-enter-active,
.about-carousel-leave-active {
    transition:
        opacity 0.28s ease,
        transform 0.32s cubic-bezier(0.22, 1, 0.36, 1),
        filter 0.32s ease;
}

.about-carousel-enter-from {
    opacity: 0;
    transform: scale(1.025);
    filter: blur(4px);
}

.about-carousel-leave-to {
    opacity: 0;
    transform: scale(0.985);
    filter: blur(4px);
}

.about-luxury-section {
    isolation: isolate;
}

.about-mini-stat {
    border-radius: 1.25rem;
    border: 1px solid rgba(255, 255, 255, 0.11);
    background: rgba(255, 255, 255, 0.08);
    padding: 0.85rem 0.65rem;
    text-align: center;
    backdrop-filter: blur(18px);
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.1),
        0 14px 36px rgba(0, 0, 0, 0.08);
}

.about-mini-stat p:last-child {
    margin-top: 0.25rem;
    color: rgba(255, 255, 255, 0.54);
    font-size: 0.58rem;
    font-weight: 850;
    line-height: 1.15;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.about-gallery-arrow {
    display: grid;
    height: 2.75rem;
    width: 2.75rem;
    place-items: center;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.18);
    background: rgba(0, 0, 0, 0.22);
    color: #ffffff;
    font-size: 1.7rem;
    font-weight: 950;
    line-height: 1;
    box-shadow: 0 14px 36px rgba(0, 0, 0, 0.22);
    backdrop-filter: blur(18px);
    transition:
        transform 0.22s ease,
        background 0.22s ease,
        border-color 0.22s ease;
}

.about-gallery-arrow:hover {
    transform: translateY(-1px);
    border-color: rgba(255, 255, 255, 0.34);
    background: rgba(255, 255, 255, 0.18);
}

@media (max-width: 639px) {
    .about-mini-stat {
        border-radius: 1rem;
        padding: 0.75rem 0.45rem;
    }

    .about-mini-stat p:last-child {
        font-size: 0.52rem;
        letter-spacing: 0.08em;
    }

    .about-gallery-arrow {
        height: 2.45rem;
        width: 2.45rem;
        font-size: 1.45rem;
    }
}

@keyframes collageDepthExit {
    0% {
        transform: translateY(0) scale(1);
        opacity: 1;
    }

    100% {
        transform: translateY(-10px) scale(0.985);
        opacity: 0;
    }
}

@keyframes collageCellExit {
    0% {
        transform: translateY(0) scale(1);
        opacity: 1;
    }

    100% {
        transform: translateY(-8px) scale(0.995);
        opacity: 0;
    }
}

@keyframes shopButtonLaunch {
    0% {
        transform: translateY(-2px) scale(1);
        opacity: 1;
    }

    100% {
        transform: translateY(-14px) scale(0.94);
        opacity: 0;
    }
}

@keyframes shopArrowLaunch {
    0% {
        transform: translateX(2px);
    }

    100% {
        transform: translateX(16px);
    }
}

@keyframes shopFlash {
    0% {
        opacity: 0;
        transform: translate(-50%, 50%) scale(0.18);
    }

    45% {
        opacity: 0.55;
        transform: translate(-50%, 50%) scale(0.9);
    }

    100% {
        opacity: 0;
        transform: translate(-50%, 50%) scale(1.45);
    }
}

@keyframes shopToolbarArrive {
    0% {
        opacity: 0;
        transform: translateY(14px) scale(0.99);
    }

    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes shopCardArrive {
    0% {
        opacity: 0;
        transform: translateY(14px) scale(0.992);
    }

    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes heroReturn {
    0% {
        opacity: 0;
        transform: translateY(-10px) scale(0.992);
    }

    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media (max-width: 639px) {
    .collage-grid {
        height: min(455px, calc(100dvh - 160px));
        min-height: 420px;
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 1.35fr 1fr 1fr;
        grid-template-areas:
            "main main"
            "two three"
            "four more";
        border-radius: 1.55rem;
    }

    .shop-now-button {
        width: min(78%, 300px);
        padding: 0.86rem 1.1rem;
    }

    .shop-now-main {
        font-size: 1.22rem;
    }

    .shop-now-eyebrow {
        font-size: 0.56rem;
        letter-spacing: 0.18em;
    }

    .shop-now-icon {
        height: 2.55rem;
        width: 2.55rem;
    }
}

.filter-loading-fade-enter-active,
.filter-loading-fade-leave-active {
    transition:
        opacity 0.22s ease,
        transform 0.22s ease,
        filter 0.22s ease;
}

.filter-loading-fade-enter-from,
.filter-loading-fade-leave-to {
    opacity: 0;
    transform: translateY(6px);
    filter: blur(4px);
}

.filter-spinner,
.mini-button-spinner {
    display: inline-block;
    flex-shrink: 0;
    border-radius: 999px;
    border-style: solid;
    border-color: rgba(11, 58, 86, 0.2);
    border-top-color: #0b3a56;
    animation: filterSpin 0.72s linear infinite;
}

.filter-spinner {
    height: 1.2rem;
    width: 1.2rem;
    border-width: 3px;
}

.filter-spinner-large {
    height: 2.35rem;
    width: 2.35rem;
    border-width: 4px;
}

.mini-button-spinner {
    height: 1rem;
    width: 1rem;
    border-width: 2px;
    border-color: rgba(255, 255, 255, 0.35);
    border-top-color: #ffffff;
}

.filter-progress-bar {
    height: 3px;
    width: 100%;
    overflow: hidden;
    background: rgba(11, 58, 86, 0.08);
    position: relative;
}

.filter-progress-bar::after {
    content: "";
    position: absolute;
    inset-block: 0;
    left: -40%;
    width: 40%;
    border-radius: 999px;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(11, 58, 86, 0.72),
        transparent
    );
    animation: filterProgress 1s ease-in-out infinite;
}

.filter-loading-overlay {
    position: absolute;
    inset: -0.75rem;
    z-index: 30;
    display: grid;
    place-items: start center;
    padding-top: 5.5rem;
    border-radius: 2rem;
    background:
        radial-gradient(
            circle at 50% 0%,
            rgba(11, 58, 86, 0.08),
            transparent 45%
        ),
        rgba(255, 255, 255, 0.66);
    backdrop-filter: blur(7px);
}

.filter-loading-card {
    display: flex;
    align-items: center;
    gap: 0.95rem;
    border-radius: 1.5rem;
    border: 1px solid rgba(226, 232, 240, 0.9);
    background: rgba(255, 255, 255, 0.92);
    padding: 1rem 1.1rem;
    box-shadow:
        0 24px 70px rgba(15, 23, 42, 0.16),
        inset 0 1px 0 rgba(255, 255, 255, 0.95);
}

.collection-filter-chip:disabled {
    cursor: not-allowed;
    opacity: 0.64;
    transform: none;
}

@keyframes filterSpin {
    to {
        transform: rotate(360deg);
    }
}

@keyframes filterProgress {
    0% {
        left: -40%;
    }

    100% {
        left: 100%;
    }
}

@media (max-width: 639px) {
    .filter-loading-overlay {
        inset: -0.35rem;
        align-items: start;
        padding: 4.5rem 1rem 0;
        border-radius: 1.5rem;
    }

    .filter-loading-card {
        width: min(100%, 21rem);
        padding: 0.9rem 1rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .hero-panel,
    .collage-grid,
    .collage-cell,
    .shop-now-button,
    .shop-now-icon,
    .shop-toolbar-card,
    .shop-grid-card,
    .shop-transition-flash,
    .about-carousel-enter-active,
    .about-carousel-leave-active,
    .filter-loading-fade-enter-active,
    .filter-loading-fade-leave-active,
    .filter-spinner,
    .mini-button-spinner,
    .filter-progress-bar::after {
        animation: none !important;
        transition: none !important;
    }
}
</style>

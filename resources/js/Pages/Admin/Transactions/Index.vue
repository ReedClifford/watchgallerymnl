<script setup>
import { computed, ref } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";
import AdminLayout from "@/Layouts/AdminLayout.vue";

const props = defineProps({
    transactions: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({
            visibility: "all",
            search: "",
        }),
    },
    counts: {
        type: Object,
        default: () => ({
            all: 0,
            visible: 0,
            hidden: 0,
        }),
    },
});

const MAX_TRANSACTION_IMAGES = 10;

const showModal = ref(false);
const modalMode = ref("create");
const editingTransaction = ref(null);
const imagePreviews = ref([]);
const search = ref(props.filters?.search ?? "");

const swalTheme = {
    background: "#ffffff",
    color: "#071923",
    confirmButtonColor: "#0b3a56",
    cancelButtonColor: "#64748b",
};

const toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 2200,
    timerProgressBar: true,
    background: "#ffffff",
    color: "#071923",
    iconColor: "#0b3a56",
});

const defaultForm = () => ({
    _method: "",
    title: "",
    caption: "",
    transaction_date: new Date().toISOString().slice(0, 10),
    is_visible: true,
    images: [],
});

const form = useForm(defaultForm());

const transactionsData = computed(() => props.transactions?.data ?? []);

const filterOptions = computed(() => [
    {
        value: "all",
        label: "All",
        count: props.counts?.all ?? 0,
    },
    {
        value: "visible",
        label: "Visible",
        count: props.counts?.visible ?? 0,
    },
    {
        value: "hidden",
        label: "Hidden",
        count: props.counts?.hidden ?? 0,
    },
]);

const currentVisibilityFilter = computed(
    () => props.filters?.visibility ?? "all",
);

const modalTitle = computed(() => {
    return modalMode.value === "create"
        ? "Add Transaction"
        : "Edit Transaction";
});

const modalSubtitle = computed(() => {
    return modalMode.value === "create"
        ? "Upload a new client handover or sold watch proof."
        : "Update this transaction gallery post.";
});

const submitLabel = computed(() => {
    if (form.processing) {
        return modalMode.value === "create" ? "Saving..." : "Updating...";
    }

    return modalMode.value === "create"
        ? "Save Transaction"
        : "Update Transaction";
});

const totalTransactions = computed(() => props.counts?.all ?? 0);
const visibleCount = computed(() => props.counts?.visible ?? 0);
const hiddenCount = computed(() => props.counts?.hidden ?? 0);

const existingImageCount = computed(() => {
    return editingTransaction.value?.images?.length ?? 0;
});

const totalSelectedImages = computed(() => {
    return existingImageCount.value + imagePreviews.value.length;
});

const hasUnsavedChanges = computed(() => {
    return form.isDirty || imagePreviews.value.length > 0;
});

const previewCover = computed(() => {
    if (imagePreviews.value.length) {
        return imagePreviews.value[0]?.url ?? null;
    }

    if (editingTransaction.value?.image_url) {
        return editingTransaction.value.image_url;
    }

    return null;
});

const formatDate = (value) => {
    if (!value) return "No date";

    return new Intl.DateTimeFormat("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
    }).format(new Date(value));
};

const visibilityClass = (isVisible) => {
    return isVisible
        ? "bg-[#eef8fb] text-[#0b3a56] ring-[#0b3a56]/20"
        : "bg-slate-100 text-slate-500 ring-slate-300";
};

const photoCount = (transaction) => {
    return transaction.images_count ?? transaction.images?.length ?? 0;
};

const applyFilters = (visibility = currentVisibilityFilter.value) => {
    router.get(
        route("admin.transactions.index"),
        {
            visibility,
            search: search.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const resetFilters = () => {
    search.value = "";

    router.get(
        route("admin.transactions.index"),
        {
            visibility: "all",
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const clearImagePreviews = () => {
    imagePreviews.value.forEach((preview) => {
        if (preview?.url) {
            URL.revokeObjectURL(preview.url);
        }
    });

    imagePreviews.value = [];
};

const resetModalState = () => {
    editingTransaction.value = null;
    clearImagePreviews();

    form.defaults(defaultForm());
    form.reset();
    form.clearErrors();
};

const openCreateModal = () => {
    modalMode.value = "create";
    resetModalState();
    showModal.value = true;
};

const openEditModal = (transaction) => {
    modalMode.value = "edit";
    editingTransaction.value = transaction;
    clearImagePreviews();

    form.defaults({
        _method: "put",
        title: transaction.title ?? "",
        caption: transaction.caption ?? "",
        transaction_date: transaction.transaction_date ?? "",
        is_visible: Boolean(transaction.is_visible),
        images: [],
    });

    form.reset();
    form.clearErrors();

    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    resetModalState();
};

const requestCloseModal = async () => {
    if (!hasUnsavedChanges.value) {
        closeModal();
        return;
    }

    const result = await Swal.fire({
        title: "Discard changes?",
        text: "You have unsaved changes in this transaction form.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Discard",
        cancelButtonText: "Keep editing",
        reverseButtons: true,
        ...swalTheme,
    });

    if (result.isConfirmed) {
        closeModal();
    }
};

const handleImages = (event) => {
    const files = Array.from(event.target.files || []);
    const currentExistingCount =
        modalMode.value === "edit" ? existingImageCount.value : 0;

    if (currentExistingCount + files.length > MAX_TRANSACTION_IMAGES) {
        event.target.value = "";

        Swal.fire({
            title: "Maximum 10 photos only",
            text: `This transaction can only have up to ${MAX_TRANSACTION_IMAGES} photos.`,
            icon: "warning",
            confirmButtonText: "Okay",
            ...swalTheme,
        });

        return;
    }

    clearImagePreviews();

    form.images = files;

    imagePreviews.value = files.map((file) => ({
        file,
        name: file.name,
        size: file.size,
        url: URL.createObjectURL(file),
    }));
};

const removeSelectedImage = (index) => {
    const preview = imagePreviews.value[index];

    if (preview?.url) {
        URL.revokeObjectURL(preview.url);
    }

    imagePreviews.value.splice(index, 1);
    form.images = imagePreviews.value
        .map((previewItem) => previewItem.file)
        .filter(Boolean);
};

const validateForm = () => {
    if (!form.title) {
        Swal.fire({
            title: "Transaction title is required",
            text: "Please add a title before saving.",
            icon: "warning",
            confirmButtonText: "Okay",
            ...swalTheme,
        });

        return false;
    }

    if (totalSelectedImages.value > MAX_TRANSACTION_IMAGES) {
        Swal.fire({
            title: "Too many photos",
            text: `Please keep photos up to ${MAX_TRANSACTION_IMAGES} images only.`,
            icon: "warning",
            confirmButtonText: "Okay",
            ...swalTheme,
        });

        return false;
    }

    return true;
};

const submitForm = () => {
    if (!validateForm()) return;

    const options = {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            closeModal();

            toast.fire({
                icon: "success",
                title:
                    modalMode.value === "create"
                        ? "Transaction added successfully"
                        : "Transaction updated successfully",
            });
        },
        onError: () => {
            Swal.fire({
                title: "Please check the form",
                text: "Some fields need your attention before saving.",
                icon: "error",
                confirmButtonText: "Review form",
                ...swalTheme,
            });
        },
    };

    if (modalMode.value === "create") {
        form.post(route("admin.transactions.store"), options);
        return;
    }

    form.post(
        route("admin.transactions.update", editingTransaction.value.id),
        options,
    );
};

const deleteTransaction = async (transaction) => {
    const result = await Swal.fire({
        title: "Delete transaction?",
        html: `
            <div style="text-align:center">
                <strong>${transaction.title}</strong>
                <br>
                <span style="font-size:13px;color:#64748b">This action cannot be undone.</span>
            </div>
        `,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, delete it",
        cancelButtonText: "Cancel",
        reverseButtons: true,
        confirmButtonColor: "#e11d48",
        cancelButtonColor: "#64748b",
        background: "#ffffff",
        color: "#071923",
    });

    if (!result.isConfirmed) return;

    router.delete(route("admin.transactions.destroy", transaction.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.fire({
                icon: "success",
                title: "Transaction deleted",
            });
        },
        onError: () => {
            Swal.fire({
                title: "Delete failed",
                text: "Something went wrong while deleting the transaction.",
                icon: "error",
                confirmButtonText: "Okay",
                ...swalTheme,
            });
        },
    });
};
</script>

<template>
    <Head title="Transactions" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="text-lg font-black text-[#071923] sm:text-xl">
                    Transactions
                </h2>
                <p class="text-sm text-slate-500">
                    Manage client handovers, sold watch photos, and proof of
                    transactions.
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
                                    Client Gallery
                                </div>

                                <h1
                                    class="mt-4 text-3xl font-black tracking-tight text-white sm:text-4xl"
                                >
                                    Transaction Gallery
                                </h1>

                                <p
                                    class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-300 sm:text-base"
                                >
                                    Upload sold watch proof, client handover
                                    photos, and transaction highlights for your
                                    public gallery.
                                </p>

                                <div class="mt-6 flex flex-wrap gap-2">
                                    <span
                                        class="rounded-2xl border border-white/10 bg-white/10 px-4 py-2 text-xs font-bold text-slate-200 backdrop-blur"
                                    >
                                        {{ totalTransactions }} total
                                    </span>

                                    <span
                                        class="rounded-2xl border border-white/10 bg-white/10 px-4 py-2 text-xs font-bold text-slate-200 backdrop-blur"
                                    >
                                        {{ visibleCount }} visible
                                    </span>

                                    <span
                                        class="rounded-2xl border border-[#d6b18a]/25 bg-[#d6b18a]/10 px-4 py-2 text-xs font-bold text-[#f0d8b6] backdrop-blur"
                                    >
                                        {{ hiddenCount }} hidden
                                    </span>
                                </div>
                            </div>

                            <div
                                class="rounded-[1.75rem] border border-white/10 bg-white/10 p-4 shadow-xl shadow-black/20 backdrop-blur-xl"
                            >
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.25em] text-[#f0d8b6]"
                                >
                                    Quick Action
                                </p>

                                <p
                                    class="mt-2 text-sm leading-relaxed text-slate-300"
                                >
                                    Add a transaction post with photos, caption,
                                    date, and public visibility status.
                                </p>

                                <button
                                    type="button"
                                    @click="openCreateModal"
                                    class="mt-4 inline-flex w-full items-center justify-center rounded-2xl bg-gradient-to-r from-[#f0d8b6] via-[#d6b18a] to-[#b98b63] px-5 py-3.5 text-sm font-black text-[#071923] shadow-lg shadow-black/20 ring-1 ring-white/20 transition hover:brightness-110 active:scale-95"
                                >
                                    + Add Transaction
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Filters -->
                <section
                    class="sticky top-16 z-20 -mx-4 mb-5 border-y border-slate-200 bg-white/95 px-4 py-3 shadow-xl shadow-[#0b3a56]/10 backdrop-blur-2xl sm:static sm:mx-0 sm:rounded-[2rem] sm:border sm:bg-white"
                >
                    <div
                        class="flex gap-2 overflow-x-auto pb-2 sm:flex-wrap sm:overflow-visible sm:pb-0"
                    >
                        <button
                            v-for="filter in filterOptions"
                            :key="filter.value"
                            type="button"
                            @click="applyFilters(filter.value)"
                            class="shrink-0 rounded-2xl px-4 py-2.5 text-xs font-black transition active:scale-95"
                            :class="
                                currentVisibilityFilter === filter.value
                                    ? 'bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] text-white shadow-lg shadow-[#0b3a56]/15 ring-1 ring-white/20'
                                    : 'border border-slate-200 bg-white text-slate-600 hover:border-[#0b3a56]/30 hover:bg-[#eef8fb] hover:text-[#0b3a56]'
                            "
                        >
                            {{ filter.label }}
                            <span class="ml-1 opacity-75">
                                {{ filter.count }}
                            </span>
                        </button>
                    </div>

                    <div class="mt-3 grid grid-cols-[1fr_auto] gap-2">
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search title or caption..."
                            class="w-full rounded-2xl border-slate-200 bg-slate-50 text-sm text-[#071923] placeholder:text-slate-400 focus:border-[#0b3a56] focus:ring-[#0b3a56]"
                            @keyup.enter="applyFilters()"
                        />

                        <button
                            type="button"
                            @click="applyFilters()"
                            class="rounded-2xl bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-[#0b3a56]/15 transition hover:brightness-110 active:scale-95"
                        >
                            Search
                        </button>
                    </div>

                    <button
                        v-if="currentVisibilityFilter !== 'all' || search"
                        type="button"
                        @click="resetFilters"
                        class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-500 transition hover:bg-slate-50 active:scale-95 sm:w-auto"
                    >
                        Clear filters
                    </button>
                </section>

                <!-- Stats -->
                <section class="mb-5 grid grid-cols-3 gap-3">
                    <div
                        class="rounded-3xl border border-slate-200 bg-white p-4 shadow-xl shadow-[#0b3a56]/10"
                    >
                        <p
                            class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400"
                        >
                            Total
                        </p>
                        <p
                            class="mt-2 text-2xl font-black text-[#071923] sm:text-3xl"
                        >
                            {{ totalTransactions }}
                        </p>
                    </div>

                    <div
                        class="rounded-3xl border border-[#0b3a56]/10 bg-[#eef8fb] p-4 shadow-xl shadow-[#0b3a56]/10"
                    >
                        <p
                            class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500"
                        >
                            Visible
                        </p>
                        <p
                            class="mt-2 text-2xl font-black text-[#0b3a56] sm:text-3xl"
                        >
                            {{ visibleCount }}
                        </p>
                    </div>

                    <div
                        class="rounded-3xl border border-[#d6b18a]/30 bg-[#fff8f0] p-4 shadow-xl shadow-[#b98b63]/10"
                    >
                        <p
                            class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500"
                        >
                            Hidden
                        </p>
                        <p
                            class="mt-2 text-2xl font-black text-[#b98b63] sm:text-3xl"
                        >
                            {{ hiddenCount }}
                        </p>
                    </div>
                </section>

                <!-- Transaction Cards -->
                <section
                    v-if="transactionsData.length"
                    class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
                >
                    <article
                        v-for="transaction in transactionsData"
                        :key="transaction.id"
                        class="group overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-xl shadow-[#0b3a56]/10 ring-1 ring-white/80 transition duration-300 hover:-translate-y-1 hover:border-[#0b3a56]/30 hover:shadow-2xl hover:shadow-[#0b3a56]/15"
                    >
                        <div
                            class="h-1.5 bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923]"
                        />

                        <div
                            class="relative aspect-[4/3] overflow-hidden bg-slate-100"
                        >
                            <img
                                v-if="transaction.image_url"
                                :src="transaction.image_url"
                                :alt="transaction.title"
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                            />

                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center text-sm text-slate-400"
                            >
                                No Image
                            </div>

                            <div
                                class="absolute inset-x-0 bottom-0 h-28 bg-gradient-to-t from-black/85 via-black/35 to-transparent"
                            />

                            <div class="absolute left-4 top-4">
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-black ring-1 backdrop-blur"
                                    :class="
                                        visibilityClass(transaction.is_visible)
                                    "
                                >
                                    {{
                                        transaction.is_visible
                                            ? "Visible"
                                            : "Hidden"
                                    }}
                                </span>
                            </div>

                            <div
                                class="absolute right-4 top-4 rounded-full bg-white/90 px-3 py-1 text-xs font-black text-[#071923] shadow-lg shadow-black/10 ring-1 ring-white/40 backdrop-blur"
                            >
                                {{ photoCount(transaction) }} photo(s)
                            </div>

                            <div class="absolute bottom-4 left-4 right-4">
                                <p
                                    class="text-xs font-black uppercase tracking-[0.25em] text-[#f0d8b6]"
                                >
                                    {{
                                        formatDate(transaction.transaction_date)
                                    }}
                                </p>
                                <h3
                                    class="mt-1 line-clamp-2 text-lg font-black leading-tight text-white"
                                >
                                    {{ transaction.title }}
                                </h3>
                            </div>
                        </div>

                        <div class="p-4 sm:p-5">
                            <p
                                class="mb-4 line-clamp-3 text-sm leading-relaxed text-slate-500"
                            >
                                {{ transaction.caption || "No caption added." }}
                            </p>

                            <div
                                v-if="transaction.images?.length"
                                class="mb-4 flex gap-2 overflow-x-auto pb-1"
                            >
                                <img
                                    v-for="image in transaction.images.slice(
                                        0,
                                        5,
                                    )"
                                    :key="image.id"
                                    :src="image.image_url"
                                    class="h-14 w-14 shrink-0 rounded-xl border border-slate-200 object-cover"
                                />

                                <div
                                    v-if="transaction.images.length > 5"
                                    class="grid h-14 w-14 shrink-0 place-items-center rounded-xl border border-slate-200 bg-slate-50 text-xs font-black text-slate-500"
                                >
                                    +{{ transaction.images.length - 5 }}
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    @click="openEditModal(transaction)"
                                    class="rounded-2xl border border-[#0b3a56]/20 bg-[#eef8fb] px-4 py-3 text-sm font-black text-[#0b3a56] transition hover:border-[#0b3a56]/40 hover:bg-[#dff3f8] active:scale-95"
                                >
                                    Edit
                                </button>

                                <button
                                    type="button"
                                    @click="deleteTransaction(transaction)"
                                    class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-black text-rose-600 transition hover:border-rose-300 hover:bg-rose-100 active:scale-95"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </article>
                </section>

                <!-- Empty State -->
                <section
                    v-else
                    class="overflow-hidden rounded-[2rem] border border-white/10 bg-gradient-to-br from-[#061725] via-[#0b3a56] to-[#071923] p-8 text-center shadow-2xl shadow-[#0b3a56]/25 sm:p-12"
                >
                    <div
                        class="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-[#d6b18a]/15 text-2xl text-[#f0d8b6]"
                    >
                        ◆
                    </div>

                    <p class="mt-5 text-lg font-black text-white">
                        No transactions found.
                    </p>

                    <p class="mt-2 text-sm text-slate-300">
                        Add your first transaction photo set.
                    </p>

                    <button
                        type="button"
                        @click="openCreateModal"
                        class="mt-6 rounded-2xl bg-gradient-to-r from-[#f0d8b6] via-[#d6b18a] to-[#b98b63] px-5 py-3 text-sm font-black text-[#071923] shadow-lg shadow-black/20 ring-1 ring-white/20 transition hover:brightness-110 active:scale-95"
                    >
                        Add Transaction
                    </button>
                </section>

                <!-- Pagination -->
                <div
                    v-if="transactions.links && transactions.links.length > 3"
                    class="mt-8 flex flex-wrap justify-center gap-2"
                >
                    <Link
                        v-for="link in transactions.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        preserve-scroll
                        class="rounded-xl border px-4 py-2 text-sm transition"
                        :class="[
                            link.active
                                ? 'border-[#071923] bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] text-white'
                                : 'border-slate-200 bg-white text-slate-600 hover:border-[#0b3a56]/40 hover:bg-[#eef8fb] hover:text-[#0b3a56]',
                            !link.url ? 'pointer-events-none opacity-40' : '',
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>

            <!-- Mobile Floating Add -->
            <button
                type="button"
                @click="openCreateModal"
                class="fixed bottom-24 right-4 z-30 grid h-14 w-14 place-items-center rounded-full bg-gradient-to-br from-[#061725] via-[#0b3a56] to-[#071923] text-2xl font-black text-white shadow-2xl shadow-[#0b3a56]/20 ring-1 ring-white/10 transition active:scale-90 sm:hidden"
            >
                +
            </button>

            <!-- Modal -->
            <Teleport to="body">
                <Transition name="modal">
                    <div
                        v-if="showModal"
                        class="fixed inset-0 z-[9999] bg-white text-[#071923] sm:flex sm:items-center sm:justify-center sm:bg-slate-950/60 sm:px-4 sm:py-6 sm:backdrop-blur-md"
                    >
                        <div
                            class="flex h-[100dvh] w-full flex-col overflow-hidden bg-white shadow-2xl shadow-[#0b3a56]/25 sm:h-auto sm:max-h-[92vh] sm:max-w-5xl sm:rounded-[2rem] sm:border sm:border-slate-200"
                        >
                            <div
                                class="shrink-0 bg-gradient-to-br from-[#061725] via-[#0b3a56] to-[#071923] px-5 pb-5 pt-5 text-white sm:px-6"
                            >
                                <div
                                    class="mx-auto mb-4 h-1.5 w-12 rounded-full bg-white/20 sm:hidden"
                                />

                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <div class="min-w-0">
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <p
                                                class="text-[10px] font-black uppercase tracking-[0.3em] text-[#f0d8b6] sm:text-xs"
                                            >
                                                Watch Gallery Manila
                                            </p>

                                            <span
                                                class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide"
                                                :class="
                                                    modalMode === 'create'
                                                        ? 'bg-[#d6b18a]/15 text-[#f0d8b6]'
                                                        : 'bg-white/10 text-slate-200'
                                                "
                                            >
                                                {{
                                                    modalMode === "create"
                                                        ? "New Transaction"
                                                        : "Editing"
                                                }}
                                            </span>
                                        </div>

                                        <h2
                                            class="mt-2 truncate text-xl font-black text-white sm:text-2xl"
                                        >
                                            {{ modalTitle }}
                                        </h2>

                                        <p class="mt-1 text-sm text-slate-300">
                                            {{ modalSubtitle }}
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        @click="requestCloseModal"
                                        class="grid h-11 w-11 shrink-0 place-items-center rounded-full border border-white/10 bg-white/10 text-xl font-black text-white transition hover:bg-white/15 active:scale-95"
                                    >
                                        ×
                                    </button>
                                </div>
                            </div>

                            <form
                                @submit.prevent="submitForm"
                                class="flex min-h-0 flex-1 flex-col"
                            >
                                <div
                                    class="min-h-0 flex-1 overflow-y-auto bg-[#eef3f7] px-5 py-5 sm:px-6"
                                >
                                    <div
                                        class="grid gap-5 lg:grid-cols-[1fr_.9fr]"
                                    >
                                        <!-- Details -->
                                        <div class="space-y-5">
                                            <section
                                                class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-xl shadow-[#0b3a56]/10 sm:p-5"
                                            >
                                                <h3
                                                    class="text-base font-black text-[#071923]"
                                                >
                                                    Transaction Details
                                                </h3>

                                                <p
                                                    class="mt-1 text-sm text-slate-500"
                                                >
                                                    Add the public title, date,
                                                    and caption.
                                                </p>

                                                <div class="mt-5 space-y-4">
                                                    <div>
                                                        <label
                                                            class="mb-1.5 block text-sm font-bold text-slate-700"
                                                        >
                                                            Title
                                                            <span
                                                                class="text-[#0b3a56]"
                                                                >*</span
                                                            >
                                                        </label>

                                                        <input
                                                            v-model="form.title"
                                                            type="text"
                                                            placeholder="Sold Seiko 5 Sports SRPD53"
                                                            class="w-full rounded-2xl border-slate-200 bg-slate-50 text-[#071923] placeholder:text-slate-400 focus:border-[#0b3a56] focus:ring-[#0b3a56]"
                                                        />

                                                        <p
                                                            v-if="
                                                                form.errors
                                                                    .title
                                                            "
                                                            class="mt-1 text-sm text-rose-600"
                                                        >
                                                            {{
                                                                form.errors
                                                                    .title
                                                            }}
                                                        </p>
                                                    </div>

                                                    <div>
                                                        <label
                                                            class="mb-1.5 block text-sm font-bold text-slate-700"
                                                        >
                                                            Transaction Date
                                                        </label>

                                                        <input
                                                            v-model="
                                                                form.transaction_date
                                                            "
                                                            type="date"
                                                            class="w-full rounded-2xl border-slate-200 bg-slate-50 text-[#071923] focus:border-[#0b3a56] focus:ring-[#0b3a56]"
                                                        />
                                                    </div>

                                                    <div>
                                                        <label
                                                            class="mb-1.5 block text-sm font-bold text-slate-700"
                                                        >
                                                            Caption
                                                        </label>

                                                        <textarea
                                                            v-model="
                                                                form.caption
                                                            "
                                                            rows="5"
                                                            placeholder="Thank you for trusting Watch Gallery Manila..."
                                                            class="w-full rounded-2xl border-slate-200 bg-slate-50 text-[#071923] placeholder:text-slate-400 focus:border-[#0b3a56] focus:ring-[#0b3a56]"
                                                        />
                                                    </div>

                                                    <label
                                                        class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4"
                                                    >
                                                        <input
                                                            v-model="
                                                                form.is_visible
                                                            "
                                                            type="checkbox"
                                                            class="rounded border-slate-300 bg-white text-[#0b3a56] focus:ring-[#0b3a56]"
                                                        />

                                                        <div>
                                                            <p
                                                                class="text-sm font-black text-[#071923]"
                                                            >
                                                                Visible on
                                                                website
                                                            </p>
                                                            <p
                                                                class="text-xs text-slate-500"
                                                            >
                                                                Show this
                                                                transaction in
                                                                the public
                                                                gallery.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </div>
                                            </section>

                                            <section
                                                v-if="previewCover"
                                                class="hidden overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-xl shadow-[#0b3a56]/10 lg:block"
                                            >
                                                <div
                                                    class="relative aspect-[4/3]"
                                                >
                                                    <img
                                                        :src="previewCover"
                                                        alt="Preview cover"
                                                        class="h-full w-full object-cover"
                                                    />

                                                    <div
                                                        class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 to-transparent p-4"
                                                    >
                                                        <p
                                                            class="text-xs font-black uppercase tracking-[0.25em] text-[#f0d8b6]"
                                                        >
                                                            Preview
                                                        </p>
                                                        <p
                                                            class="mt-1 line-clamp-2 text-lg font-black text-white"
                                                        >
                                                            {{
                                                                form.title ||
                                                                "Transaction title"
                                                            }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </section>
                                        </div>

                                        <!-- Photos -->
                                        <div class="space-y-5">
                                            <section
                                                class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-xl shadow-[#0b3a56]/10 sm:p-5"
                                            >
                                                <div
                                                    class="mb-5 flex items-center justify-between gap-3"
                                                >
                                                    <div>
                                                        <h3
                                                            class="text-base font-black text-[#071923]"
                                                        >
                                                            Photos
                                                        </h3>
                                                        <p
                                                            class="mt-1 text-xs text-slate-500"
                                                        >
                                                            Maximum 10 photos.
                                                        </p>
                                                    </div>

                                                    <div
                                                        class="rounded-2xl bg-[#eef8fb] px-3 py-2 text-xs font-black text-[#0b3a56]"
                                                    >
                                                        {{
                                                            totalSelectedImages
                                                        }}
                                                        /
                                                        {{
                                                            MAX_TRANSACTION_IMAGES
                                                        }}
                                                    </div>
                                                </div>

                                                <label
                                                    class="flex cursor-pointer flex-col items-center justify-center rounded-[2rem] border border-dashed border-[#0b3a56]/30 bg-[#eef8fb]/50 px-5 py-8 text-center transition hover:bg-[#eef8fb]"
                                                >
                                                    <div
                                                        class="grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] text-2xl font-black text-white shadow-lg shadow-[#0b3a56]/15"
                                                    >
                                                        +
                                                    </div>

                                                    <p
                                                        class="mt-4 text-sm font-black text-[#071923]"
                                                    >
                                                        Tap to upload photos
                                                    </p>

                                                    <p
                                                        class="mt-1 text-xs text-slate-500"
                                                    >
                                                        JPG, PNG, WEBP up to 5MB
                                                        each.
                                                    </p>

                                                    <input
                                                        type="file"
                                                        multiple
                                                        accept="image/*"
                                                        @change="handleImages"
                                                        class="hidden"
                                                    />
                                                </label>

                                                <p
                                                    v-if="form.errors.images"
                                                    class="mt-2 text-sm text-rose-600"
                                                >
                                                    {{ form.errors.images }}
                                                </p>
                                            </section>

                                            <section
                                                v-if="
                                                    modalMode === 'edit' &&
                                                    editingTransaction?.images
                                                        ?.length
                                                "
                                                class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-xl shadow-[#0b3a56]/10 sm:p-5"
                                            >
                                                <p
                                                    class="mb-3 text-xs font-black uppercase tracking-[0.2em] text-slate-400"
                                                >
                                                    Existing Photos
                                                </p>

                                                <div
                                                    class="grid grid-cols-3 gap-2"
                                                >
                                                    <img
                                                        v-for="image in editingTransaction.images"
                                                        :key="image.id"
                                                        :src="image.image_url"
                                                        class="aspect-square rounded-2xl border border-slate-200 object-cover"
                                                    />
                                                </div>
                                            </section>

                                            <section
                                                v-if="imagePreviews.length"
                                                class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-xl shadow-[#0b3a56]/10 sm:p-5"
                                            >
                                                <p
                                                    class="mb-3 text-xs font-black uppercase tracking-[0.2em] text-slate-400"
                                                >
                                                    New Uploads
                                                </p>

                                                <div
                                                    class="grid grid-cols-3 gap-2"
                                                >
                                                    <div
                                                        v-for="(
                                                            preview, index
                                                        ) in imagePreviews"
                                                        :key="preview.url"
                                                        class="relative overflow-hidden rounded-2xl border border-slate-200 bg-slate-50"
                                                    >
                                                        <img
                                                            :src="preview.url"
                                                            :alt="preview.name"
                                                            class="aspect-square w-full object-cover"
                                                        />

                                                        <button
                                                            type="button"
                                                            @click="
                                                                removeSelectedImage(
                                                                    index,
                                                                )
                                                            "
                                                            class="absolute right-1 top-1 grid h-7 w-7 place-items-center rounded-full bg-white/90 text-sm font-black text-rose-600 shadow-lg shadow-black/10 backdrop-blur transition hover:bg-rose-600 hover:text-white"
                                                        >
                                                            ×
                                                        </button>
                                                    </div>
                                                </div>
                                            </section>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="shrink-0 border-t border-slate-200 bg-white px-5 pb-[calc(env(safe-area-inset-bottom)+16px)] pt-4 sm:px-6 sm:pb-4"
                                >
                                    <div
                                        class="grid grid-cols-2 gap-3 sm:flex sm:justify-end"
                                    >
                                        <button
                                            type="button"
                                            @click="requestCloseModal"
                                            class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-600 transition hover:bg-slate-50 hover:text-[#071923] active:scale-95"
                                        >
                                            Cancel
                                        </button>

                                        <button
                                            type="submit"
                                            :disabled="form.processing"
                                            class="rounded-2xl bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] px-6 py-3 text-sm font-black text-white shadow-lg shadow-[#0b3a56]/15 ring-1 ring-white/10 transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-60 active:scale-95"
                                        >
                                            {{ submitLabel }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </Transition>
            </Teleport>
        </div>
    </AdminLayout>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: all 0.25s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

@media (max-width: 639px) {
    .modal-enter-from,
    .modal-leave-to {
        transform: translateY(100%);
    }
}

@media (min-width: 640px) {
    .modal-enter-from,
    .modal-leave-to {
        transform: translateY(24px) scale(0.98);
    }
}
</style>

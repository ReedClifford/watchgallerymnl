<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, ref } from "vue";
import Swal from "sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";

const props = defineProps({
    aboutUs: {
        type: Object,
        required: true,
    },
});

const fileInput = ref(null);
const ownerFileInput = ref(null);

const newImagePreviews = ref([]);
const ownerImagePreview = ref(null);

const defaultPrimaryImageId =
    props.aboutUs?.images?.find((image) => image.is_primary)?.id ||
    props.aboutUs?.images?.[0]?.id ||
    null;

const form = useForm({
    eyebrow: props.aboutUs?.eyebrow || "About Watch Gallery Manila",
    title: props.aboutUs?.title || "Your trusted watch showroom experience",
    body: props.aboutUs?.body || "",
    dealer_name: props.aboutUs?.dealer_name || "Nel Miranda",
    dealer_message:
        props.aboutUs?.dealer_message ||
        "I'm Nel Miranda pala, your watch dealer. Hope to meet you soon!",
    owner_bio:
        props.aboutUs?.owner_bio ||
        "Your trusted watch dealer, helping clients find quality timepieces with clear details, smooth transactions, and reliable after-sales support.",
    owner_image: null,
    remove_owner_image: false,
    is_active: props.aboutUs?.is_active ?? true,
    images: [],
    remove_image_ids: [],

    // Primary gallery image controls
    primary_image_id: defaultPrimaryImageId,
    primary_new_image_index: null,
});

const existingImages = computed(() => props.aboutUs?.images || []);

const visibleExistingImages = computed(() => {
    return existingImages.value.filter(
        (image) => !form.remove_image_ids.includes(image.id),
    );
});

const activePrimaryExistingImage = computed(() => {
    if (form.primary_new_image_index !== null) {
        return null;
    }

    return (
        visibleExistingImages.value.find(
            (image) => Number(image.id) === Number(form.primary_image_id),
        ) || null
    );
});

const activePrimaryNewImage = computed(() => {
    if (form.primary_new_image_index === null) {
        return null;
    }

    return newImagePreviews.value[form.primary_new_image_index] || null;
});

const primaryPreviewImage = computed(() => {
    return (
        activePrimaryNewImage.value?.url ||
        activePrimaryExistingImage.value?.image_url ||
        visibleExistingImages.value[0]?.image_url ||
        newImagePreviews.value[0]?.url ||
        null
    );
});

const primaryStatusLabel = computed(() => {
    if (activePrimaryNewImage.value) {
        return "New upload selected as primary";
    }

    if (activePrimaryExistingImage.value) {
        return "Current photo selected as primary";
    }

    return "No primary image selected";
});

const currentOwnerImage = computed(() => {
    if (ownerImagePreview.value) {
        return ownerImagePreview.value.url;
    }

    if (form.remove_owner_image) {
        return null;
    }

    return props.aboutUs?.owner_image_url || null;
});

const isExistingImagePrimary = (image) => {
    return (
        form.primary_new_image_index === null &&
        Number(form.primary_image_id) === Number(image.id) &&
        !form.remove_image_ids.includes(image.id)
    );
};

const isNewImagePrimary = (index) => {
    return form.primary_new_image_index === index;
};

const assignFallbackPrimary = () => {
    const firstExisting = visibleExistingImages.value[0];

    if (firstExisting) {
        form.primary_image_id = firstExisting.id;
        form.primary_new_image_index = null;
        return;
    }

    if (newImagePreviews.value.length) {
        form.primary_image_id = null;
        form.primary_new_image_index = 0;
        return;
    }

    form.primary_image_id = null;
    form.primary_new_image_index = null;
};

const setExistingImageAsPrimary = (image) => {
    if (form.remove_image_ids.includes(image.id)) {
        return;
    }

    form.primary_image_id = image.id;
    form.primary_new_image_index = null;
};

const setNewImageAsPrimary = (index) => {
    if (!newImagePreviews.value[index]) {
        return;
    }

    form.primary_image_id = null;
    form.primary_new_image_index = index;
};

const handleImageUpload = (event) => {
    const files = Array.from(event.target.files || []);

    form.images = files;

    newImagePreviews.value.forEach((preview) => {
        URL.revokeObjectURL(preview.url);
    });

    newImagePreviews.value = files.map((file) => ({
        name: file.name,
        url: URL.createObjectURL(file),
    }));

    const currentExistingPrimaryStillVisible = visibleExistingImages.value.some(
        (image) => Number(image.id) === Number(form.primary_image_id),
    );

    if (!currentExistingPrimaryStillVisible && files.length) {
        form.primary_image_id = null;
        form.primary_new_image_index = 0;
    }

    if (!files.length && form.primary_new_image_index !== null) {
        form.primary_new_image_index = null;
        assignFallbackPrimary();
    }
};

const handleOwnerImageUpload = (event) => {
    const file = event.target.files?.[0] || null;

    if (ownerImagePreview.value) {
        URL.revokeObjectURL(ownerImagePreview.value.url);
    }

    if (!file) {
        form.owner_image = null;
        ownerImagePreview.value = null;
        return;
    }

    form.owner_image = file;
    form.remove_owner_image = false;

    ownerImagePreview.value = {
        name: file.name,
        url: URL.createObjectURL(file),
    };
};

const removeNewImage = (index) => {
    const updatedFiles = [...form.images];

    updatedFiles.splice(index, 1);

    URL.revokeObjectURL(newImagePreviews.value[index].url);

    newImagePreviews.value.splice(index, 1);
    form.images = updatedFiles;

    if (form.primary_new_image_index === index) {
        form.primary_new_image_index = null;
        assignFallbackPrimary();
    } else if (
        form.primary_new_image_index !== null &&
        form.primary_new_image_index > index
    ) {
        form.primary_new_image_index = form.primary_new_image_index - 1;
    }

    if (fileInput.value && !updatedFiles.length) {
        fileInput.value.value = "";
    }
};

const clearSelectedOwnerImage = () => {
    form.owner_image = null;

    if (ownerImagePreview.value) {
        URL.revokeObjectURL(ownerImagePreview.value.url);
        ownerImagePreview.value = null;
    }

    if (ownerFileInput.value) {
        ownerFileInput.value.value = "";
    }
};

const removeCurrentOwnerImage = () => {
    clearSelectedOwnerImage();
    form.remove_owner_image = true;
};

const undoRemoveOwnerImage = () => {
    form.remove_owner_image = false;
};

const markImageForRemoval = (id) => {
    if (!form.remove_image_ids.includes(id)) {
        form.remove_image_ids.push(id);
    }

    if (Number(form.primary_image_id) === Number(id)) {
        form.primary_image_id = null;
        form.primary_new_image_index = null;
        assignFallbackPrimary();
    }
};

const restoreImage = (id) => {
    form.remove_image_ids = form.remove_image_ids.filter(
        (imageId) => imageId !== id,
    );

    if (!form.primary_image_id && form.primary_new_image_index === null) {
        const restoredImage = existingImages.value.find(
            (image) => Number(image.id) === Number(id),
        );

        if (restoredImage) {
            setExistingImageAsPrimary(restoredImage);
        }
    }
};

const submit = () => {
    if (
        form.primary_image_id &&
        form.remove_image_ids.includes(form.primary_image_id)
    ) {
        assignFallbackPrimary();
    }

    form.post(route("admin.about-us.update"), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.images = [];
            form.remove_image_ids = [];
            form.owner_image = null;
            form.remove_owner_image = false;
            form.primary_new_image_index = null;

            newImagePreviews.value.forEach((preview) => {
                URL.revokeObjectURL(preview.url);
            });

            newImagePreviews.value = [];

            if (ownerImagePreview.value) {
                URL.revokeObjectURL(ownerImagePreview.value.url);
                ownerImagePreview.value = null;
            }

            if (fileInput.value) {
                fileInput.value.value = "";
            }

            if (ownerFileInput.value) {
                ownerFileInput.value.value = "";
            }

            Swal.fire({
                icon: "success",
                title: "Saved successfully",
                text: "Your showroom gallery, About Us section, and owner profile have been updated.",
                timer: 1800,
                showConfirmButton: false,
                background: "#ffffff",
                color: "#071923",
                iconColor: "#0b3a56",
            });
        },
    });
};

onBeforeUnmount(() => {
    newImagePreviews.value.forEach((preview) => {
        URL.revokeObjectURL(preview.url);
    });

    if (ownerImagePreview.value) {
        URL.revokeObjectURL(ownerImagePreview.value.url);
    }
});
</script>

<template>
    <Head title="Showroom Gallery Settings" />

    <AdminLayout>
        <template #header>
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p
                        class="text-[10px] font-black uppercase tracking-[0.35em] text-[#0b3a56]"
                    >
                        Website Content
                    </p>

                    <h1
                        class="mt-2 text-2xl font-black tracking-tight text-[#071923] sm:text-3xl"
                    >
                        Showroom Gallery & About Us
                    </h1>

                    <p class="mt-2 max-w-2xl text-sm text-slate-500">
                        Manage the public About Us modal, showroom carousel,
                        owner profile, and dealer message.
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-[#d6b18a]/35 bg-[#fff8f0] px-4 py-3"
                >
                    <p
                        class="text-[10px] font-black uppercase tracking-[0.25em] text-[#b98b63]"
                    >
                        Website Status
                    </p>

                    <p class="mt-1 text-sm font-black text-[#071923]">
                        {{ form.is_active ? "Visible on website" : "Hidden" }}
                    </p>
                </div>
            </div>
        </template>

        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[1fr_400px]">
                <form
                    @submit.prevent="submit"
                    class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-xl shadow-slate-900/5"
                >
                    <div
                        class="border-b border-slate-200 bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] px-5 py-4"
                    >
                        <p
                            class="text-[10px] font-black uppercase tracking-[0.35em] text-[#f0d8b6]"
                        >
                            Editable Content
                        </p>

                        <h2 class="mt-1 text-xl font-black text-white">
                            About Us, Owner Profile & Showroom Details
                        </h2>
                    </div>

                    <div class="grid gap-6 p-5 sm:p-6">
                        <!-- About Us Content -->
                        <div
                            class="rounded-[1.75rem] border border-slate-200 bg-white p-5"
                        >
                            <div class="mb-5">
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.3em] text-[#0b3a56]"
                                >
                                    About Us Section
                                </p>
                                <h3
                                    class="mt-1 text-lg font-black text-[#071923]"
                                >
                                    Public About Us Text
                                </h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    This appears beside the showroom carousel.
                                </p>
                            </div>

                            <div class="grid gap-5">
                                <div>
                                    <label
                                        class="text-sm font-black text-[#071923]"
                                        for="eyebrow"
                                    >
                                        Small Label
                                    </label>

                                    <input
                                        id="eyebrow"
                                        v-model="form.eyebrow"
                                        type="text"
                                        class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 text-sm text-[#071923] placeholder:text-slate-400 focus:border-[#0b3a56] focus:ring-[#0b3a56]"
                                        placeholder="About Watch Gallery Manila"
                                    />

                                    <p
                                        v-if="form.errors.eyebrow"
                                        class="mt-2 text-xs font-bold text-red-600"
                                    >
                                        {{ form.errors.eyebrow }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="text-sm font-black text-[#071923]"
                                        for="title"
                                    >
                                        Title
                                    </label>

                                    <input
                                        id="title"
                                        v-model="form.title"
                                        type="text"
                                        class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 text-sm text-[#071923] placeholder:text-slate-400 focus:border-[#0b3a56] focus:ring-[#0b3a56]"
                                        placeholder="Your trusted watch showroom experience"
                                    />

                                    <p
                                        v-if="form.errors.title"
                                        class="mt-2 text-xs font-bold text-red-600"
                                    >
                                        {{ form.errors.title }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="text-sm font-black text-[#071923]"
                                        for="body"
                                    >
                                        About Description
                                    </label>

                                    <textarea
                                        id="body"
                                        v-model="form.body"
                                        rows="6"
                                        class="mt-2 w-full resize-y rounded-2xl border-slate-200 bg-slate-50 text-sm leading-relaxed text-[#071923] placeholder:text-slate-400 focus:border-[#0b3a56] focus:ring-[#0b3a56]"
                                        placeholder="Write a short description about Watch Gallery Manila..."
                                    />

                                    <p
                                        v-if="form.errors.body"
                                        class="mt-2 text-xs font-bold text-red-600"
                                    >
                                        {{ form.errors.body }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="text-sm font-black text-[#071923]"
                                    >
                                        Website Visibility
                                    </label>

                                    <button
                                        type="button"
                                        @click="
                                            form.is_active = !form.is_active
                                        "
                                        class="mt-2 flex w-full items-center justify-between rounded-2xl border px-4 py-3 text-left transition active:scale-[0.99]"
                                        :class="
                                            form.is_active
                                                ? 'border-[#0b3a56]/25 bg-[#eef8fb] text-[#0b3a56]'
                                                : 'border-slate-200 bg-slate-50 text-slate-500'
                                        "
                                    >
                                        <span>
                                            <span
                                                class="block text-sm font-black"
                                            >
                                                {{
                                                    form.is_active
                                                        ? "Visible"
                                                        : "Hidden"
                                                }}
                                            </span>

                                            <span class="mt-0.5 block text-xs">
                                                {{
                                                    form.is_active
                                                        ? "About Us button will show publicly."
                                                        : "About Us button will be hidden."
                                                }}
                                            </span>
                                        </span>

                                        <span
                                            class="grid h-7 w-7 place-items-center rounded-full text-xs font-black"
                                            :class="
                                                form.is_active
                                                    ? 'bg-[#0b3a56] text-white'
                                                    : 'bg-slate-200 text-slate-500'
                                            "
                                        >
                                            {{ form.is_active ? "✓" : "×" }}
                                        </span>
                                    </button>

                                    <p
                                        v-if="form.errors.is_active"
                                        class="mt-2 text-xs font-bold text-red-600"
                                    >
                                        {{ form.errors.is_active }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Owner Profile -->
                        <div
                            class="overflow-hidden rounded-[1.75rem] border border-[#d6b18a]/35 bg-[#fff8f0]"
                        >
                            <div
                                class="border-b border-[#d6b18a]/25 bg-white/70 px-5 py-4"
                            >
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.3em] text-[#b98b63]"
                                >
                                    Owner Profile
                                </p>
                                <h3
                                    class="mt-1 text-lg font-black text-[#071923]"
                                >
                                    Owner Picture & Bio
                                </h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    This appears as a smaller profile card. It
                                    will not be part of the showroom carousel.
                                </p>
                            </div>

                            <div class="grid gap-5 p-5">
                                <div
                                    class="grid gap-5 sm:grid-cols-[180px_1fr]"
                                >
                                    <div>
                                        <div
                                            class="overflow-hidden rounded-[1.5rem] border border-[#d6b18a]/35 bg-white shadow-sm"
                                        >
                                            <div
                                                class="aspect-[4/5] bg-[#071923]"
                                            >
                                                <img
                                                    v-if="currentOwnerImage"
                                                    :src="currentOwnerImage"
                                                    alt="Owner photo"
                                                    class="h-full w-full object-cover"
                                                />

                                                <div
                                                    v-else
                                                    class="grid h-full place-items-center p-4 text-center text-white/70"
                                                >
                                                    <div>
                                                        <p
                                                            class="text-3xl font-black"
                                                        >
                                                            Owner
                                                        </p>
                                                        <p class="mt-2 text-xs">
                                                            Add photo
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="p-3">
                                                <input
                                                    ref="ownerFileInput"
                                                    type="file"
                                                    accept="image/*"
                                                    class="hidden"
                                                    @change="
                                                        handleOwnerImageUpload
                                                    "
                                                />

                                                <button
                                                    type="button"
                                                    @click="
                                                        ownerFileInput?.click()
                                                    "
                                                    class="w-full rounded-xl bg-[#071923] px-3 py-2 text-xs font-black text-white transition hover:brightness-110 active:scale-95"
                                                >
                                                    Upload Owner Photo
                                                </button>

                                                <button
                                                    v-if="ownerImagePreview"
                                                    type="button"
                                                    @click="
                                                        clearSelectedOwnerImage
                                                    "
                                                    class="mt-2 w-full rounded-xl bg-slate-100 px-3 py-2 text-xs font-black text-slate-600 transition hover:bg-slate-200 active:scale-95"
                                                >
                                                    Clear Selected
                                                </button>

                                                <button
                                                    v-if="
                                                        props.aboutUs
                                                            ?.owner_image_url &&
                                                        !form.remove_owner_image &&
                                                        !ownerImagePreview
                                                    "
                                                    type="button"
                                                    @click="
                                                        removeCurrentOwnerImage
                                                    "
                                                    class="mt-2 w-full rounded-xl bg-red-50 px-3 py-2 text-xs font-black text-red-600 transition hover:bg-red-100 active:scale-95"
                                                >
                                                    Remove Current
                                                </button>

                                                <button
                                                    v-if="
                                                        form.remove_owner_image
                                                    "
                                                    type="button"
                                                    @click="
                                                        undoRemoveOwnerImage
                                                    "
                                                    class="mt-2 w-full rounded-xl bg-slate-100 px-3 py-2 text-xs font-black text-slate-600 transition hover:bg-slate-200 active:scale-95"
                                                >
                                                    Undo Remove
                                                </button>
                                            </div>
                                        </div>

                                        <p
                                            v-if="form.errors.owner_image"
                                            class="mt-2 text-xs font-bold text-red-600"
                                        >
                                            {{ form.errors.owner_image }}
                                        </p>
                                    </div>

                                    <div class="grid gap-5">
                                        <div>
                                            <label
                                                class="text-sm font-black text-[#071923]"
                                                for="dealer_name"
                                            >
                                                Owner / Dealer Name
                                            </label>

                                            <input
                                                id="dealer_name"
                                                v-model="form.dealer_name"
                                                type="text"
                                                class="mt-2 w-full rounded-2xl border-slate-200 bg-white text-sm text-[#071923] placeholder:text-slate-400 focus:border-[#0b3a56] focus:ring-[#0b3a56]"
                                                placeholder="Nel Miranda"
                                            />

                                            <p
                                                v-if="form.errors.dealer_name"
                                                class="mt-2 text-xs font-bold text-red-600"
                                            >
                                                {{ form.errors.dealer_name }}
                                            </p>
                                        </div>

                                        <div>
                                            <label
                                                class="text-sm font-black text-[#071923]"
                                                for="owner_bio"
                                            >
                                                Owner Bio
                                            </label>

                                            <textarea
                                                id="owner_bio"
                                                v-model="form.owner_bio"
                                                rows="5"
                                                class="mt-2 w-full resize-y rounded-2xl border-slate-200 bg-white text-sm leading-relaxed text-[#071923] placeholder:text-slate-400 focus:border-[#0b3a56] focus:ring-[#0b3a56]"
                                                placeholder="Write something short about the owner..."
                                            />

                                            <p
                                                v-if="form.errors.owner_bio"
                                                class="mt-2 text-xs font-bold text-red-600"
                                            >
                                                {{ form.errors.owner_bio }}
                                            </p>
                                        </div>

                                        <div>
                                            <label
                                                class="text-sm font-black text-[#071923]"
                                                for="dealer_message"
                                            >
                                                Dealer Message
                                            </label>

                                            <textarea
                                                id="dealer_message"
                                                v-model="form.dealer_message"
                                                rows="4"
                                                class="mt-2 w-full resize-y rounded-2xl border-slate-200 bg-white text-sm leading-relaxed text-[#071923] placeholder:text-slate-400 focus:border-[#0b3a56] focus:ring-[#0b3a56]"
                                                placeholder="I'm Nel Miranda pala, your watch dealer. Hope to meet you soon!"
                                            />

                                            <p
                                                v-if="
                                                    form.errors.dealer_message
                                                "
                                                class="mt-2 text-xs font-bold text-red-600"
                                            >
                                                {{ form.errors.dealer_message }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Showroom Gallery -->
                        <div
                            class="overflow-hidden rounded-[1.75rem] border border-[#0b3a56]/15 bg-[#eef8fb]"
                        >
                            <div
                                class="border-b border-[#0b3a56]/10 bg-white/60 px-5 py-4"
                            >
                                <div
                                    class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                                >
                                    <div>
                                        <p
                                            class="text-[10px] font-black uppercase tracking-[0.3em] text-[#0b3a56]"
                                        >
                                            Showroom Gallery
                                        </p>
                                        <h3
                                            class="mt-1 text-lg font-black text-[#071923]"
                                        >
                                            Main Carousel Photos
                                        </h3>
                                        <p class="mt-1 text-sm text-slate-600">
                                            Choose one primary photo. This will
                                            appear first on the public About Us
                                            carousel.
                                        </p>
                                    </div>

                                    <div
                                        class="rounded-2xl border border-[#0b3a56]/10 bg-white px-4 py-3"
                                    >
                                        <p
                                            class="text-[10px] font-black uppercase tracking-[0.2em] text-[#0b3a56]"
                                        >
                                            Primary
                                        </p>
                                        <p
                                            class="mt-1 text-xs font-bold text-slate-500"
                                        >
                                            {{ primaryStatusLabel }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-5 p-5">
                                <div>
                                    <label
                                        class="text-sm font-black text-[#071923]"
                                    >
                                        Add Showroom Photos
                                    </label>

                                    <input
                                        ref="fileInput"
                                        type="file"
                                        multiple
                                        accept="image/*"
                                        class="mt-2 block w-full rounded-2xl border border-dashed border-slate-300 bg-white p-4 text-sm text-slate-500 file:mr-4 file:rounded-xl file:border-0 file:bg-[#071923] file:px-4 file:py-2 file:text-sm file:font-black file:text-white hover:bg-white"
                                        @change="handleImageUpload"
                                    />

                                    <p class="mt-2 text-xs text-slate-500">
                                        Recommended: upload showroom, display
                                        table, packaging, behind-the-scenes, and
                                        handover area photos.
                                    </p>

                                    <p
                                        v-if="form.errors.images"
                                        class="mt-2 text-xs font-bold text-red-600"
                                    >
                                        {{ form.errors.images }}
                                    </p>

                                    <p
                                        v-if="form.errors.primary_image_id"
                                        class="mt-2 text-xs font-bold text-red-600"
                                    >
                                        {{ form.errors.primary_image_id }}
                                    </p>

                                    <p
                                        v-if="
                                            form.errors.primary_new_image_index
                                        "
                                        class="mt-2 text-xs font-bold text-red-600"
                                    >
                                        {{
                                            form.errors.primary_new_image_index
                                        }}
                                    </p>
                                </div>

                                <div v-if="newImagePreviews.length">
                                    <div
                                        class="mb-3 flex items-center justify-between gap-3"
                                    >
                                        <h3
                                            class="text-sm font-black text-[#071923]"
                                        >
                                            New Showroom Photos to Upload
                                        </h3>

                                        <p
                                            class="text-xs font-bold text-slate-500"
                                        >
                                            {{ newImagePreviews.length }}
                                            selected
                                        </p>
                                    </div>

                                    <div
                                        class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
                                    >
                                        <div
                                            v-for="(
                                                preview, index
                                            ) in newImagePreviews"
                                            :key="preview.url"
                                            class="group overflow-hidden rounded-2xl border bg-white shadow-sm transition"
                                            :class="
                                                isNewImagePrimary(index)
                                                    ? 'border-[#0b3a56] ring-2 ring-[#0b3a56]/15'
                                                    : 'border-slate-200'
                                            "
                                        >
                                            <div class="relative">
                                                <img
                                                    :src="preview.url"
                                                    :alt="preview.name"
                                                    class="aspect-[4/3] w-full object-cover"
                                                />

                                                <div
                                                    v-if="
                                                        isNewImagePrimary(index)
                                                    "
                                                    class="absolute left-3 top-3 rounded-full bg-[#071923] px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.18em] text-white shadow-lg shadow-black/20"
                                                >
                                                    Primary
                                                </div>

                                                <div
                                                    v-else
                                                    class="absolute left-3 top-3 rounded-full bg-white/90 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.18em] text-[#071923] shadow-lg shadow-black/10 backdrop-blur"
                                                >
                                                    New
                                                </div>
                                            </div>

                                            <div class="p-3">
                                                <p
                                                    class="line-clamp-1 text-xs font-bold text-slate-500"
                                                >
                                                    {{ preview.name }}
                                                </p>

                                                <div
                                                    class="mt-3 grid grid-cols-2 gap-2"
                                                >
                                                    <button
                                                        type="button"
                                                        @click="
                                                            setNewImageAsPrimary(
                                                                index,
                                                            )
                                                        "
                                                        class="rounded-xl px-3 py-2 text-xs font-black transition active:scale-95"
                                                        :class="
                                                            isNewImagePrimary(
                                                                index,
                                                            )
                                                                ? 'bg-[#071923] text-white'
                                                                : 'bg-[#eef8fb] text-[#0b3a56] hover:bg-white'
                                                        "
                                                    >
                                                        {{
                                                            isNewImagePrimary(
                                                                index,
                                                            )
                                                                ? "Primary"
                                                                : "Set Primary"
                                                        }}
                                                    </button>

                                                    <button
                                                        type="button"
                                                        @click="
                                                            removeNewImage(
                                                                index,
                                                            )
                                                        "
                                                        class="rounded-xl bg-red-50 px-3 py-2 text-xs font-black text-red-600 transition hover:bg-red-100 active:scale-95"
                                                    >
                                                        Remove
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="existingImages.length">
                                    <div
                                        class="mb-3 flex items-center justify-between gap-3"
                                    >
                                        <h3
                                            class="text-sm font-black text-[#071923]"
                                        >
                                            Current Showroom Photos
                                        </h3>

                                        <p
                                            class="text-xs font-bold text-slate-500"
                                        >
                                            {{ visibleExistingImages.length }}
                                            active
                                        </p>
                                    </div>

                                    <div
                                        class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
                                    >
                                        <div
                                            v-for="image in existingImages"
                                            :key="image.id"
                                            class="group overflow-hidden rounded-2xl border bg-white shadow-sm transition"
                                            :class="[
                                                form.remove_image_ids.includes(
                                                    image.id,
                                                )
                                                    ? 'border-red-200 opacity-50'
                                                    : 'border-slate-200',
                                                isExistingImagePrimary(image)
                                                    ? 'ring-2 ring-[#0b3a56]/15'
                                                    : '',
                                            ]"
                                        >
                                            <div class="relative">
                                                <img
                                                    :src="image.image_url"
                                                    :alt="
                                                        image.caption ||
                                                        'Showroom photo'
                                                    "
                                                    class="aspect-[4/3] w-full object-cover"
                                                />

                                                <div
                                                    v-if="
                                                        isExistingImagePrimary(
                                                            image,
                                                        )
                                                    "
                                                    class="absolute left-3 top-3 rounded-full bg-[#071923] px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.18em] text-white shadow-lg shadow-black/20"
                                                >
                                                    Primary
                                                </div>

                                                <div
                                                    v-else-if="
                                                        form.remove_image_ids.includes(
                                                            image.id,
                                                        )
                                                    "
                                                    class="absolute left-3 top-3 rounded-full bg-red-600 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.18em] text-white shadow-lg shadow-black/20"
                                                >
                                                    Removing
                                                </div>
                                            </div>

                                            <div class="p-3">
                                                <p
                                                    class="line-clamp-1 text-xs font-bold text-slate-500"
                                                >
                                                    {{
                                                        image.caption ||
                                                        "Showroom photo"
                                                    }}
                                                </p>

                                                <div
                                                    class="mt-3 grid grid-cols-2 gap-2"
                                                >
                                                    <button
                                                        v-if="
                                                            !form.remove_image_ids.includes(
                                                                image.id,
                                                            )
                                                        "
                                                        type="button"
                                                        @click="
                                                            setExistingImageAsPrimary(
                                                                image,
                                                            )
                                                        "
                                                        class="rounded-xl px-3 py-2 text-xs font-black transition active:scale-95"
                                                        :class="
                                                            isExistingImagePrimary(
                                                                image,
                                                            )
                                                                ? 'bg-[#071923] text-white'
                                                                : 'bg-[#eef8fb] text-[#0b3a56] hover:bg-white'
                                                        "
                                                    >
                                                        {{
                                                            isExistingImagePrimary(
                                                                image,
                                                            )
                                                                ? "Primary"
                                                                : "Set Primary"
                                                        }}
                                                    </button>

                                                    <button
                                                        v-if="
                                                            !form.remove_image_ids.includes(
                                                                image.id,
                                                            )
                                                        "
                                                        type="button"
                                                        @click="
                                                            markImageForRemoval(
                                                                image.id,
                                                            )
                                                        "
                                                        class="rounded-xl bg-red-50 px-3 py-2 text-xs font-black text-red-600 transition hover:bg-red-100 active:scale-95"
                                                    >
                                                        Remove
                                                    </button>

                                                    <button
                                                        v-else
                                                        type="button"
                                                        @click="
                                                            restoreImage(
                                                                image.id,
                                                            )
                                                        "
                                                        class="col-span-2 rounded-xl bg-slate-100 px-3 py-2 text-xs font-black text-slate-600 transition hover:bg-slate-200 active:scale-95"
                                                    >
                                                        Undo Remove
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="
                                        !existingImages.length &&
                                        !newImagePreviews.length
                                    "
                                    class="rounded-2xl border border-dashed border-[#0b3a56]/20 bg-white/70 p-8 text-center"
                                >
                                    <p
                                        class="text-sm font-black text-[#071923]"
                                    >
                                        No showroom photos yet.
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Upload a photo and mark it as primary.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 p-5 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <p class="text-xs text-slate-500">
                            Public layout: the selected primary photo appears
                            first in the showroom carousel.
                        </p>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-2xl bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] px-6 py-3 text-sm font-black text-white shadow-lg shadow-[#0b3a56]/20 transition hover:brightness-110 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{
                                form.processing ? "Saving..." : "Save About Us"
                            }}
                        </button>
                    </div>
                </form>

                <!-- Preview Card -->
                <aside
                    class="h-fit overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-xl shadow-slate-900/5"
                >
                    <div
                        class="border-b border-slate-200 bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] px-5 py-4"
                    >
                        <p
                            class="text-[10px] font-black uppercase tracking-[0.35em] text-[#f0d8b6]"
                        >
                            Preview
                        </p>

                        <h2 class="mt-1 text-xl font-black text-white">
                            Public Modal Preview
                        </h2>
                    </div>

                    <div class="space-y-5 p-5">
                        <div
                            class="overflow-hidden rounded-[1.5rem] border border-slate-200 bg-slate-50"
                        >
                            <div class="relative aspect-[4/3] bg-[#071923]">
                                <img
                                    v-if="primaryPreviewImage"
                                    :src="primaryPreviewImage"
                                    alt="Showroom preview"
                                    class="h-full w-full object-cover"
                                />

                                <div
                                    v-else
                                    class="grid h-full place-items-center text-center text-white/70"
                                >
                                    <div>
                                        <p class="text-3xl font-black">
                                            Showroom Gallery
                                        </p>
                                        <p class="mt-2 text-xs">
                                            Add showroom photos
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-if="primaryPreviewImage"
                                    class="absolute left-3 top-3 rounded-full bg-white/90 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.18em] text-[#071923] shadow-lg shadow-black/10 backdrop-blur"
                                >
                                    Primary Preview
                                </div>
                            </div>

                            <div class="p-5">
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.35em] text-[#0b3a56]"
                                >
                                    {{
                                        form.eyebrow ||
                                        "About Watch Gallery Manila"
                                    }}
                                </p>

                                <h3
                                    class="mt-2 text-2xl font-black leading-tight text-[#071923]"
                                >
                                    {{
                                        form.title ||
                                        "Your trusted watch showroom experience"
                                    }}
                                </h3>

                                <p
                                    class="mt-3 line-clamp-5 whitespace-pre-line text-sm leading-relaxed text-slate-600"
                                >
                                    {{
                                        form.body ||
                                        "Your About Us description will appear here."
                                    }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="overflow-hidden rounded-[1.5rem] border border-[#d6b18a]/35 bg-[#fff8f0]"
                        >
                            <div
                                class="border-b border-[#d6b18a]/25 bg-white/60 px-4 py-3"
                            >
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.25em] text-[#b98b63]"
                                >
                                    Owner Card
                                </p>
                            </div>

                            <div class="p-4">
                                <div class="flex gap-4">
                                    <div
                                        class="h-24 w-20 shrink-0 overflow-hidden rounded-2xl bg-[#071923]"
                                    >
                                        <img
                                            v-if="currentOwnerImage"
                                            :src="currentOwnerImage"
                                            alt="Owner preview"
                                            class="h-full w-full object-cover"
                                        />

                                        <div
                                            v-else
                                            class="grid h-full place-items-center text-[10px] font-bold text-white/60"
                                        >
                                            Owner
                                        </div>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="text-lg font-black text-[#071923]"
                                        >
                                            {{
                                                form.dealer_name ||
                                                "Nel Miranda"
                                            }}
                                        </p>

                                        <p
                                            class="mt-1 line-clamp-4 whitespace-pre-line text-xs leading-relaxed text-slate-600"
                                        >
                                            {{
                                                form.owner_bio ||
                                                "Owner bio will appear here."
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="mt-4 rounded-2xl border border-[#d6b18a]/35 bg-white/60 p-4"
                                >
                                    <p
                                        class="whitespace-pre-line text-sm font-black leading-relaxed text-[#071923]"
                                    >
                                        {{
                                            form.dealer_message ||
                                            "Dealer message preview."
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </AdminLayout>
</template>

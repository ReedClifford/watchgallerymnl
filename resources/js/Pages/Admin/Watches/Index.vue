<script setup>
import { computed, ref } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";
import AdminLayout from "@/Layouts/AdminLayout.vue";

const props = defineProps({
    watches: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({
            status: "all",
            search: "",
        }),
    },
    counts: {
        type: Object,
        default: () => ({
            all: 0,
            available: 0,
            sold: 0,
            reserved: 0,
        }),
    },
});

const MAX_WATCH_IMAGES = 6;

const showModal = ref(false);
const modalMode = ref("create");
const editingWatch = ref(null);
const imagePreviews = ref([]);
const activeModalTab = ref("details");
const search = ref(props.filters?.search ?? "");
const galleryOrder = ref([]);
const primaryImageToken = ref("");
const uploadedImageKeyCounter = ref(0);

const tabOrder = ["details", "pricing", "photos"];

const tabs = [
    { key: "details", label: "Details", helper: "Watch information" },
    { key: "pricing", label: "Pricing", helper: "Price and status" },
    { key: "photos", label: "Photos", helper: "Watch images" },
];

const basicFields = [
    {
        key: "brand",
        label: "Brand",
        placeholder: "Seiko",
        required: true,
        type: "text",
    },
    {
        key: "model_name",
        label: "Model Name",
        placeholder: "Seiko 5 Sports",
        required: true,
        type: "text",
    },
    {
        key: "reference_number",
        label: "Reference Number",
        placeholder: "SRPD53",
        required: false,
        type: "text",
    },
    {
        key: "condition",
        label: "Condition",
        placeholder: "Brand New",
        required: false,
        type: "select",
    },
];

const conditionOptions = [
    {
        value: "Brand New",
        label: "Brand New",
    },
    {
        value: "Pre-owned",
        label: "Pre-owned",
    },
];

const normalizeConditionForForm = (value) => {
    const normalized = String(value || "")
        .trim()
        .toLowerCase()
        .replace(/[\s-]+/g, "_");

    if (["brand_new", "brandnew", "new"].includes(normalized)) {
        return "Brand New";
    }

    if (["pre_owned", "preowned", "used", "second_hand"].includes(normalized)) {
        return "Pre-owned";
    }

    return "Brand New";
};

const conditionDisplayLabel = (value) => {
    return conditionOptions.find(
        (option) => option.value === normalizeConditionForForm(value),
    )?.label;
};

const genderOptions = [
    {
        value: "unisex",
        label: "Unisex",
        description: "Default option for every new watch.",
    },
    {
        value: "men",
        label: "Men",
        description: "For men’s watch listings.",
    },
    {
        value: "women",
        label: "Women",
        description: "For women’s watch listings.",
    },
];

const specFields = [
    { key: "movement", label: "Movement", placeholder: "Automatic / Quartz" },
    { key: "case_size", label: "Case Size", placeholder: "40mm" },
    {
        key: "case_material",
        label: "Case Material",
        placeholder: "Stainless Steel",
    },
    { key: "dial_color", label: "Dial Color", placeholder: "Blue" },
    { key: "crystal", label: "Crystal", placeholder: "Hardlex" },
    {
        key: "bracelet_or_strap",
        label: "Bracelet / Strap",
        placeholder: "Stainless bracelet",
    },
    { key: "water_resistance", label: "Water Resistance", placeholder: "100m" },
    { key: "box_papers", label: "Box / Papers", placeholder: "Complete set" },
];

const pricingFields = [
    { key: "capital_price", label: "Capital Price", placeholder: "0.00" },
    {
        key: "suggested_srp",
        label: "Suggested SRP",
        placeholder: "Official / market SRP",
    },
    { key: "selling_price", label: "Selling Price", placeholder: "0.00" },
    {
        key: "discounted_price",
        label: "Discounted Price",
        placeholder: "Optional",
    },
];

const soldFields = [
    {
        key: "sold_price",
        label: "Sold Price",
        placeholder: "Final sold amount",
        type: "number",
    },
    { key: "date_sold", label: "Date Sold", placeholder: "", type: "date" },
    {
        key: "buyer_name",
        label: "Buyer Name",
        placeholder: "Client name",
        type: "text",
    },
];

const statusOptions = [
    {
        value: "available",
        label: "Available",
        description: "Shown as available stock",
    },
    {
        value: "reserved",
        label: "Reserved",
        description: "Temporarily held",
    },
    {
        value: "sold",
        label: "Sold",
        description: "Completed sale",
    },
];

const swalTheme = {
    background: "#ffffff",
    color: "#071923",
    confirmButtonColor: "#0b3a56",
    cancelButtonColor: "#64748b",
    customClass: {
        container: "wgm-swal-container",
        popup: "wgm-swal-popup",
    },
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
    customClass: {
        container: "wgm-swal-container",
        popup: "wgm-swal-popup",
    },
});

const defaultForm = () => ({
    _method: "",
    brand: "Seiko",
    model_name: "",
    reference_number: "",
    condition: "Brand New",
    gender: "unisex",
    description: "",
    movement: "",
    case_size: "",
    case_material: "",
    dial_color: "",
    crystal: "",
    bracelet_or_strap: "",
    water_resistance: "",
    box_papers: "",
    capital_price: "",
    suggested_srp: "",
    selling_price: "",
    discounted_price: "",
    status: "available",
    is_visible: true,
    is_in_demand: false,
    sold_price: "",
    date_sold: "",
    buyer_name: "",
    images: [],
    removed_image_ids: [],
    image_order: [],
    primary_image_id: "",
    primary_new_image_index: "",
});

const form = useForm(defaultForm());

const watchesData = computed(() => props.watches?.data ?? []);

const filterOptions = computed(() => [
    { value: "all", label: "All", count: props.counts?.all ?? 0 },
    {
        value: "available",
        label: "Available",
        count: props.counts?.available ?? 0,
    },
    {
        value: "reserved",
        label: "Reserved",
        count: props.counts?.reserved ?? 0,
    },
    {
        value: "sold",
        label: "Sold",
        count: props.counts?.sold ?? 0,
    },
]);

const currentStatusFilter = computed(() => props.filters?.status ?? "all");

const modalTitle = computed(() => {
    return modalMode.value === "create" ? "Add New Watch" : "Edit Watch";
});

const modalSubtitle = computed(() => {
    return modalMode.value === "create"
        ? "Create a new listing for Watch Gallery Manila."
        : "Update this watch listing and inventory details.";
});

const submitLabel = computed(() => {
    if (form.processing) {
        return modalMode.value === "create" ? "Saving..." : "Updating...";
    }

    return modalMode.value === "create" ? "Save Watch" : "Update Watch";
});

const activeStepIndex = computed(() => {
    return tabOrder.indexOf(activeModalTab.value);
});

const isFirstStep = computed(() => {
    return activeStepIndex.value === 0;
});

const isLastStep = computed(() => {
    return activeStepIndex.value === tabOrder.length - 1;
});

const primaryActionLabel = computed(() => {
    if (!isLastStep.value) {
        return "Next";
    }

    return submitLabel.value;
});

const stepText = computed(() => {
    if (activeModalTab.value === "details") {
        return "Step 1 of 3: Watch details";
    }

    if (activeModalTab.value === "pricing") {
        return "Step 2 of 3: Pricing and status";
    }

    return "Step 3 of 3: Photos";
});

const totalWatches = computed(() => props.counts?.all ?? 0);
const availableCount = computed(() => props.counts?.available ?? 0);
const reservedCount = computed(() => props.counts?.reserved ?? 0);
const soldCount = computed(() => props.counts?.sold ?? 0);

const visibleExistingImages = computed(() => {
    if (modalMode.value !== "edit" || !editingWatch.value?.images?.length) {
        return [];
    }

    const removedIds = new Set(form.removed_image_ids || []);

    return editingWatch.value.images.filter(
        (image) => !removedIds.has(image.id),
    );
});

const existingImageCount = computed(() => visibleExistingImages.value.length);

const newImageCount = computed(() => imagePreviews.value.length);

const totalSelectedImages = computed(() => {
    return existingImageCount.value + newImageCount.value;
});

const existingImageToken = (image) => `existing-${image.id}`;

const newImageToken = (preview) =>
    preview?.key || preview?.url || preview?.name;

const galleryItems = computed(() => {
    const existingItems = visibleExistingImages.value.map((image) => ({
        type: "existing",
        token: existingImageToken(image),
        id: image.id,
        url: image.image_url,
        name: `Existing photo ${image.id}`,
        isOriginalPrimary: Boolean(image.is_primary),
    }));

    const newItems = imagePreviews.value.map((preview, index) => ({
        type: "new",
        token: newImageToken(preview),
        key: preview.key,
        file: preview.file,
        url: preview.url,
        name: preview.name || `New upload ${index + 1}`,
        newIndex: index,
        isOriginalPrimary: false,
    }));

    const allItems = [...existingItems, ...newItems];
    const byToken = new Map(allItems.map((item) => [item.token, item]));
    const orderedItems = galleryOrder.value
        .map((token) => byToken.get(token))
        .filter(Boolean);
    const orderedTokens = new Set(orderedItems.map((item) => item.token));

    allItems.forEach((item) => {
        if (!orderedTokens.has(item.token)) {
            orderedItems.push(item);
        }
    });

    return orderedItems;
});

const hasGalleryItems = computed(() => galleryItems.value.length > 0);

const hasUnsavedChanges = computed(() => {
    return form.isDirty || imagePreviews.value.length > 0;
});

const formatMoney = (value) => {
    if (!value) return "No price";

    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        maximumFractionDigits: 0,
    }).format(value);
};

const statusClass = (status) => {
    const classes = {
        available: "bg-[#eef8fb] text-[#0b3a56] ring-[#0b3a56]/20",
        reserved: "bg-slate-100 text-slate-600 ring-slate-300",
        sold: "bg-[#071923] text-white ring-[#071923]/20",
    };

    return classes[status] || "bg-slate-100 text-slate-500 ring-slate-300";
};

const applyFilters = (status = currentStatusFilter.value) => {
    router.get(
        route("admin.watches.index"),
        {
            status,
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
        route("admin.watches.index"),
        { status: "all" },
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
    editingWatch.value = null;
    clearImagePreviews();
    galleryOrder.value = [];
    primaryImageToken.value = "";
    uploadedImageKeyCounter.value = 0;
    activeModalTab.value = "details";

    form.defaults(defaultForm());
    form.reset();
    form.clearErrors();
};

const openCreateModal = () => {
    modalMode.value = "create";
    resetModalState();
    showModal.value = true;
};

const openEditModal = (watch) => {
    modalMode.value = "edit";
    editingWatch.value = watch;
    clearImagePreviews();
    activeModalTab.value = "details";

    form.defaults({
        _method: "put",
        brand: watch.brand ?? "Seiko",
        model_name: watch.model_name ?? "",
        reference_number: watch.reference_number ?? "",
        condition: normalizeConditionForForm(watch.condition),
        gender: watch.gender ?? "unisex",
        description: watch.description ?? "",
        movement: watch.movement ?? "",
        case_size: watch.case_size ?? "",
        case_material: watch.case_material ?? "",
        dial_color: watch.dial_color ?? "",
        crystal: watch.crystal ?? "",
        bracelet_or_strap: watch.bracelet_or_strap ?? "",
        water_resistance: watch.water_resistance ?? "",
        box_papers: watch.box_papers ?? "",
        capital_price: watch.capital_price ?? "",
        suggested_srp: watch.suggested_srp ?? "",
        selling_price: watch.selling_price ?? "",
        discounted_price: watch.discounted_price ?? "",
        status:
            watch.status === "hidden"
                ? "available"
                : (watch.status ?? "available"),
        is_visible: Boolean(watch.is_visible),
        is_in_demand: Boolean(watch.is_in_demand),
        sold_price: watch.sold_price ?? "",
        date_sold: watch.date_sold ?? "",
        buyer_name: watch.buyer_name ?? "",
        images: [],
        removed_image_ids: [],
        image_order: [],
        primary_image_id: "",
        primary_new_image_index: "",
    });

    form.reset();
    galleryOrder.value = (watch.images || []).map((image) =>
        existingImageToken(image),
    );

    const savedPrimaryImage = (watch.images || []).find(
        (image) => image.is_primary,
    );

    primaryImageToken.value = savedPrimaryImage
        ? existingImageToken(savedPrimaryImage)
        : galleryOrder.value[0] || "";

    syncGalleryFormState();
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
        text: "You have unsaved changes in this watch form.",
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

const normalizeGalleryOrder = () => {
    const validTokens = [
        ...visibleExistingImages.value.map((image) =>
            existingImageToken(image),
        ),
        ...imagePreviews.value.map((preview) => newImageToken(preview)),
    ].filter(Boolean);

    const validTokenSet = new Set(validTokens);
    const normalizedOrder = galleryOrder.value.filter((token) =>
        validTokenSet.has(token),
    );

    validTokens.forEach((token) => {
        if (!normalizedOrder.includes(token)) {
            normalizedOrder.push(token);
        }
    });

    galleryOrder.value = normalizedOrder;

    if (
        !primaryImageToken.value ||
        !validTokenSet.has(primaryImageToken.value)
    ) {
        primaryImageToken.value = normalizedOrder[0] || "";
    }
};

const syncGalleryFormState = () => {
    normalizeGalleryOrder();

    const orderedItems = galleryItems.value;
    const orderedNewItems = orderedItems.filter((item) => item.type === "new");

    form.images = orderedNewItems.map((item) => item.file).filter(Boolean);

    form.image_order = orderedItems.map((item) => {
        if (item.type === "existing") {
            return `existing:${item.id}`;
        }

        const newIndex = orderedNewItems.findIndex(
            (newItem) => newItem.token === item.token,
        );

        return `new:${newIndex}`;
    });

    const primaryItem =
        orderedItems.find((item) => item.token === primaryImageToken.value) ||
        orderedItems[0] ||
        null;

    if (!primaryItem) {
        form.primary_image_id = "";
        form.primary_new_image_index = "";
        primaryImageToken.value = "";
        return;
    }

    primaryImageToken.value = primaryItem.token;

    if (primaryItem.type === "existing") {
        form.primary_image_id = primaryItem.id;
        form.primary_new_image_index = "";
        return;
    }

    form.primary_image_id = "";
    form.primary_new_image_index = orderedNewItems.findIndex(
        (item) => item.token === primaryItem.token,
    );
};

const setPrimaryImage = (token) => {
    primaryImageToken.value = token;
    syncGalleryFormState();
};

const moveGalleryItem = (token, direction) => {
    normalizeGalleryOrder();

    const currentIndex = galleryOrder.value.indexOf(token);
    const targetIndex = currentIndex + direction;

    if (
        currentIndex === -1 ||
        targetIndex < 0 ||
        targetIndex >= galleryOrder.value.length
    ) {
        return;
    }

    const updatedOrder = [...galleryOrder.value];
    [updatedOrder[currentIndex], updatedOrder[targetIndex]] = [
        updatedOrder[targetIndex],
        updatedOrder[currentIndex],
    ];

    galleryOrder.value = updatedOrder;
    syncGalleryFormState();
};

const moveGalleryItemToStart = (token) => {
    normalizeGalleryOrder();

    galleryOrder.value = [
        token,
        ...galleryOrder.value.filter((itemToken) => itemToken !== token),
    ];

    syncGalleryFormState();
};

const removeGalleryToken = (token) => {
    galleryOrder.value = galleryOrder.value.filter(
        (itemToken) => itemToken !== token,
    );

    if (primaryImageToken.value === token) {
        primaryImageToken.value = "";
    }
};

const handleImages = (event) => {
    const files = Array.from(event.target.files || []);

    if (!files.length) return;

    const remainingSlots =
        MAX_WATCH_IMAGES -
        existingImageCount.value -
        imagePreviews.value.length;

    if (files.length > remainingSlots) {
        event.target.value = "";

        Swal.fire({
            title: "Maximum 6 photos only",
            text:
                remainingSlots > 0
                    ? `You can still add ${remainingSlots} photo(s) only.`
                    : `This watch already has the maximum ${MAX_WATCH_IMAGES} photos.`,
            icon: "warning",
            confirmButtonText: "Okay",
            ...swalTheme,
        });

        return;
    }

    const newPreviews = files.map((file) => {
        uploadedImageKeyCounter.value += 1;

        return {
            key: `new-${Date.now()}-${uploadedImageKeyCounter.value}`,
            file,
            name: file.name,
            size: file.size,
            url: URL.createObjectURL(file),
        };
    });

    imagePreviews.value = [...imagePreviews.value, ...newPreviews];
    galleryOrder.value = [
        ...galleryOrder.value,
        ...newPreviews.map((preview) => newImageToken(preview)),
    ];

    if (!primaryImageToken.value && newPreviews.length) {
        primaryImageToken.value = newImageToken(newPreviews[0]);
    }

    syncGalleryFormState();
    event.target.value = "";
};

const removeSelectedImage = (previewOrIndex) => {
    const index =
        typeof previewOrIndex === "number"
            ? previewOrIndex
            : imagePreviews.value.findIndex(
                  (preview) =>
                      newImageToken(preview) === previewOrIndex?.token ||
                      newImageToken(preview) === newImageToken(previewOrIndex),
              );

    if (index < 0) return;

    const preview = imagePreviews.value[index];
    const token = newImageToken(preview);

    if (preview?.url) {
        URL.revokeObjectURL(preview.url);
    }

    imagePreviews.value.splice(index, 1);
    removeGalleryToken(token);
    syncGalleryFormState();
};

const removeExistingImage = async (image) => {
    const result = await Swal.fire({
        title: "Remove this photo?",
        text: "This photo will be deleted after you update the watch.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Remove photo",
        cancelButtonText: "Cancel",
        reverseButtons: true,
        confirmButtonColor: "#e11d48",
        ...swalTheme,
    });

    if (!result.isConfirmed) return;

    form.removed_image_ids = [
        ...new Set([...(form.removed_image_ids || []), image.id]),
    ];

    removeGalleryToken(existingImageToken(image));
    syncGalleryFormState();

    toast.fire({
        icon: "success",
        title: "Photo marked for removal",
    });
};

const setStatus = (status) => {
    form.status = status;

    if (status !== "sold") {
        form.sold_price = "";
        form.date_sold = "";
        form.buyer_name = "";
    }
};

const validateCurrentStep = () => {
    form.clearErrors();

    if (activeModalTab.value === "details") {
        if (!form.brand || !form.model_name) {
            Swal.fire({
                title: "Complete watch details",
                text: "Brand and model name are required before continuing.",
                icon: "warning",
                confirmButtonText: "Okay",
                ...swalTheme,
            });

            return false;
        }
    }

    if (activeModalTab.value === "photos") {
        if (totalSelectedImages.value > MAX_WATCH_IMAGES) {
            Swal.fire({
                title: "Too many photos",
                text: `Please keep watch photos up to ${MAX_WATCH_IMAGES} images only.`,
                icon: "warning",
                confirmButtonText: "Okay",
                ...swalTheme,
            });

            return false;
        }
    }

    return true;
};

const goToTab = (targetTab) => {
    const targetIndex = tabOrder.indexOf(targetTab);

    if (targetIndex === -1 || targetIndex === activeStepIndex.value) return;

    if (targetIndex < activeStepIndex.value) {
        activeModalTab.value = targetTab;
        return;
    }

    if (targetIndex === activeStepIndex.value + 1) {
        if (validateCurrentStep()) {
            activeModalTab.value = targetTab;
        }

        return;
    }

    Swal.fire({
        title: "Use Next to continue",
        text: "Please complete each step before jumping ahead.",
        icon: "info",
        confirmButtonText: "Okay",
        ...swalTheme,
    });
};

const goToNextStep = () => {
    if (!validateCurrentStep()) return;

    const nextIndex = activeStepIndex.value + 1;

    if (nextIndex < tabOrder.length) {
        activeModalTab.value = tabOrder[nextIndex];
    }
};

const goToPreviousStep = () => {
    const previousIndex = activeStepIndex.value - 1;

    if (previousIndex >= 0) {
        activeModalTab.value = tabOrder[previousIndex];
    }
};

const goToErrorTab = (errors) => {
    const errorKeys = Object.keys(errors || {});

    if (errorKeys.some((key) => key.includes("image"))) {
        activeModalTab.value = "photos";
        return;
    }

    if (
        errorKeys.some((key) =>
            [
                "capital_price",
                "suggested_srp",
                "selling_price",
                "discounted_price",
                "status",
                "is_visible",
                "is_in_demand",
                "sold_price",
                "date_sold",
                "buyer_name",
            ].includes(key),
        )
    ) {
        activeModalTab.value = "pricing";
        return;
    }

    activeModalTab.value = "details";
};

const submitForm = () => {
    syncGalleryFormState();

    const options = {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            closeModal();

            toast.fire({
                icon: "success",
                title:
                    modalMode.value === "create"
                        ? "Watch added successfully"
                        : "Watch updated successfully",
            });
        },
        onError: (errors) => {
            goToErrorTab(errors);

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
        form.post(route("admin.watches.store"), options);
        return;
    }

    form.post(route("admin.watches.update", editingWatch.value.id), options);
};

const handlePrimaryAction = () => {
    if (!isLastStep.value) {
        goToNextStep();
        return;
    }

    if (!validateCurrentStep()) return;

    submitForm();
};

const markAsSold = async (watch) => {
    const defaultPrice = watch.discounted_price || watch.selling_price || "";
    const today = new Date().toISOString().slice(0, 10);

    const result = await Swal.fire({
        title: "Mark as sold?",
        html: `
            <div style="text-align:left">
                <label style="display:block;margin-bottom:6px;font-size:13px;color:#475569;font-weight:800;">Sold Price</label>
                <input id="sold_price" type="number" step="0.01" value="${defaultPrice ?? ""}" placeholder="Final sold amount" style="width:100%;padding:12px 14px;border-radius:14px;border:1px solid #e2e8f0;background:#f8fafc;color:#071923;margin-bottom:14px;">

                <label style="display:block;margin-bottom:6px;font-size:13px;color:#475569;font-weight:800;">Date Sold</label>
                <input id="date_sold" type="date" value="${today}" style="width:100%;padding:12px 14px;border-radius:14px;border:1px solid #e2e8f0;background:#f8fafc;color:#071923;margin-bottom:14px;">

                <label style="display:block;margin-bottom:6px;font-size:13px;color:#475569;font-weight:800;">Buyer Name</label>
                <input id="buyer_name" type="text" placeholder="Optional client name" style="width:100%;padding:12px 14px;border-radius:14px;border:1px solid #e2e8f0;background:#f8fafc;color:#071923;">
            </div>
        `,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Mark as Sold",
        cancelButtonText: "Cancel",
        reverseButtons: true,
        focusConfirm: false,
        ...swalTheme,
        preConfirm: () => ({
            sold_price: document.getElementById("sold_price").value,
            date_sold: document.getElementById("date_sold").value,
            buyer_name: document.getElementById("buyer_name").value,
        }),
    });

    if (!result.isConfirmed) return;

    router.patch(route("admin.watches.mark-as-sold", watch.id), result.value, {
        preserveScroll: true,
        onSuccess: () => {
            toast.fire({
                icon: "success",
                title: "Watch marked as sold",
            });
        },
        onError: () => {
            Swal.fire({
                title: "Unable to mark as sold",
                text: "Please check the sold details and try again.",
                icon: "error",
                confirmButtonText: "Okay",
                ...swalTheme,
            });
        },
    });
};

const duplicateWatch = async (watch) => {
    const result = await Swal.fire({
        title: "Duplicate this watch?",
        html: `
            <div style="text-align:center">
                <strong>${watch.brand || ""} ${watch.model_name || ""}</strong>
                <br>
                <span style="font-size:13px;color:#64748b">This will create a new available listing with the same details, photos, primary image, and photo order.</span>
            </div>
        `,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Duplicate Watch",
        cancelButtonText: "Cancel",
        reverseButtons: true,
        ...swalTheme,
    });

    if (!result.isConfirmed) return;

    router.post(
        route("admin.watches.duplicate", watch.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.fire({
                    icon: "success",
                    title: "Watch duplicated",
                });
            },
            onError: () => {
                Swal.fire({
                    title: "Duplicate failed",
                    text: "Something went wrong while duplicating this watch.",
                    icon: "error",
                    confirmButtonText: "Okay",
                    ...swalTheme,
                });
            },
        },
    );
};

const deleteWatch = async (watch) => {
    const result = await Swal.fire({
        title: "Delete this watch?",
        html: `
            <div style="text-align:center">
                <strong>${watch.brand} ${watch.model_name}</strong>
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
        ...swalTheme,
    });

    if (!result.isConfirmed) return;

    router.delete(route("admin.watches.destroy", watch.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.fire({
                icon: "success",
                title: "Watch deleted",
            });
        },
        onError: () => {
            Swal.fire({
                title: "Delete failed",
                text: "Something went wrong while deleting the watch.",
                icon: "error",
                confirmButtonText: "Okay",
                ...swalTheme,
            });
        },
    });
};
</script>

<template>
    <Head title="Watch Inventory" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <h2 class="text-lg font-black text-[#071923] sm:text-xl">
                    Watch Inventory
                </h2>
                <p class="text-sm text-slate-500">
                    Add, edit, and manage Watch Gallery Manila listings.
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
                            class="relative grid gap-6 lg:grid-cols-[1.2fr_.8fr] lg:items-center"
                        >
                            <div>
                                <div
                                    class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.28em] text-white/80"
                                >
                                    Watch Gallery Manila
                                </div>

                                <h1
                                    class="mt-4 text-3xl font-black tracking-tight text-white sm:text-4xl"
                                >
                                    Admin Inventory
                                </h1>

                                <p
                                    class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-300 sm:text-base"
                                >
                                    Manage available, reserved, and sold watch
                                    listings with a cleaner premium control
                                    panel.
                                </p>

                                <div class="mt-6 flex flex-wrap gap-2">
                                    <span
                                        class="rounded-2xl border border-white/10 bg-white/10 px-4 py-2 text-xs font-bold text-slate-200 backdrop-blur"
                                    >
                                        {{ totalWatches }} total
                                    </span>

                                    <span
                                        class="rounded-2xl border border-white/10 bg-white/10 px-4 py-2 text-xs font-bold text-slate-200 backdrop-blur"
                                    >
                                        {{ availableCount }} available
                                    </span>

                                    <span
                                        class="rounded-2xl border border-white/10 bg-white/10 px-4 py-2 text-xs font-bold text-white backdrop-blur"
                                    >
                                        {{ soldCount }} sold
                                    </span>
                                </div>
                            </div>

                            <div
                                class="rounded-[1.75rem] border border-white/10 bg-white/10 p-4 shadow-xl shadow-black/20 backdrop-blur-xl"
                            >
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.25em] text-white/80"
                                >
                                    Quick Action
                                </p>

                                <p
                                    class="mt-2 text-sm leading-relaxed text-slate-300"
                                >
                                    Add a new watch listing with details,
                                    pricing, status, and photos.
                                </p>

                                <button
                                    type="button"
                                    @click="openCreateModal"
                                    class="mt-4 inline-flex w-full items-center justify-center rounded-2xl bg-white px-5 py-3.5 text-sm font-black text-[#071923] shadow-lg shadow-black/20 ring-1 ring-white/20 transition hover:bg-white/90 active:scale-95"
                                >
                                    + Add Watch
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
                                currentStatusFilter === filter.value
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
                            placeholder="Search model, brand, reference..."
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
                        v-if="currentStatusFilter !== 'all' || search"
                        type="button"
                        @click="resetFilters"
                        class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-500 transition hover:bg-slate-50 active:scale-95 sm:w-auto"
                    >
                        Clear filters
                    </button>
                </section>

                <!-- Stats -->
                <section class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
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
                            {{ totalWatches }}
                        </p>
                    </div>

                    <div
                        class="rounded-3xl border border-[#0b3a56]/10 bg-[#eef8fb] p-4 shadow-xl shadow-[#0b3a56]/10"
                    >
                        <p
                            class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500"
                        >
                            Available
                        </p>
                        <p
                            class="mt-2 text-2xl font-black text-[#0b3a56] sm:text-3xl"
                        >
                            {{ availableCount }}
                        </p>
                    </div>

                    <div
                        class="rounded-3xl border border-slate-200 bg-white p-4 shadow-xl shadow-[#0b3a56]/10"
                    >
                        <p
                            class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400"
                        >
                            Reserved
                        </p>
                        <p
                            class="mt-2 text-2xl font-black text-[#071923] sm:text-3xl"
                        >
                            {{ reservedCount }}
                        </p>
                    </div>

                    <div
                        class="rounded-3xl border border-slate-200 bg-white p-4 shadow-xl shadow-[#0b3a56]/10"
                    >
                        <p
                            class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400"
                        >
                            Sold
                        </p>
                        <p
                            class="mt-2 text-2xl font-black text-[#071923] sm:text-3xl"
                        >
                            {{ soldCount }}
                        </p>
                    </div>
                </section>

                <!-- Watch Cards -->
                <section
                    v-if="watchesData.length"
                    class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
                >
                    <article
                        v-for="watch in watchesData"
                        :key="watch.id"
                        class="group overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-xl shadow-[#0b3a56]/10 ring-1 ring-white/80 transition duration-300 hover:-translate-y-1 hover:border-[#0b3a56]/30 hover:shadow-2xl hover:shadow-[#0b3a56]/15"
                    >
                        <div
                            class="h-1.5 bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923]"
                        />

                        <div
                            class="relative aspect-[4/3] overflow-hidden bg-slate-100"
                        >
                            <img
                                v-if="watch.image_url"
                                :src="watch.image_url"
                                :alt="watch.model_name"
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
                                    class="rounded-full px-3 py-1 text-xs font-black capitalize ring-1 backdrop-blur"
                                    :class="statusClass(watch.status)"
                                >
                                    {{ watch.status }}
                                </span>
                            </div>

                            <div
                                v-if="watch.is_in_demand"
                                class="absolute right-4 top-4 rounded-full border border-white/20 bg-white px-3 py-1 text-xs font-black text-[#071923] shadow-lg shadow-black/10"
                            >
                                In-Demand
                            </div>

                            <div class="absolute bottom-4 left-4 right-4">
                                <p
                                    class="text-xs font-black uppercase tracking-[0.25em] text-white/80"
                                >
                                    {{ watch.brand }}
                                </p>
                                <h3
                                    class="mt-1 text-lg font-black leading-tight text-white"
                                >
                                    {{ watch.model_name }}
                                </h3>
                            </div>
                        </div>

                        <div class="p-4 sm:p-5">
                            <p class="mb-3 text-sm text-slate-500">
                                {{
                                    watch.reference_number ||
                                    "No reference number"
                                }}
                            </p>

                            <div
                                class="mb-4 rounded-3xl border border-slate-200 bg-slate-50 p-4"
                            >
                                <div
                                    class="flex items-end justify-between gap-3"
                                >
                                    <div>
                                        <div
                                            v-if="watch.suggested_srp"
                                            class="mb-2"
                                        >
                                            <p class="text-xs text-slate-500">
                                                Suggested SRP
                                            </p>
                                            <p
                                                class="mt-1 text-sm font-black text-slate-500 line-through decoration-slate-400"
                                            >
                                                {{
                                                    formatMoney(
                                                        watch.suggested_srp,
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <p class="text-xs text-slate-500">
                                            Selling Price
                                        </p>
                                        <p
                                            class="mt-1 text-xl font-black text-[#071923]"
                                        >
                                            {{
                                                formatMoney(
                                                    watch.discounted_price ||
                                                        watch.selling_price,
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <span
                                        class="rounded-full px-2.5 py-1 text-[11px] font-bold"
                                        :class="
                                            watch.is_visible
                                                ? 'bg-[#eef8fb] text-[#0b3a56]'
                                                : 'bg-slate-100 text-slate-500'
                                        "
                                    >
                                        {{
                                            watch.is_visible
                                                ? "Visible"
                                                : "Not Visible"
                                        }}
                                    </span>
                                </div>
                            </div>

                            <div class="mb-4 flex flex-wrap items-center gap-2">
                                <span
                                    class="rounded-full bg-[#eef8fb] px-3 py-1.5 text-xs font-semibold text-[#0b3a56]"
                                >
                                    {{ conditionDisplayLabel(watch.condition) }}
                                </span>

                                <span
                                    v-if="watch.gender"
                                    class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold capitalize text-slate-600"
                                >
                                    {{ watch.gender }}
                                </span>

                                <span
                                    v-if="watch.case_size"
                                    class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600"
                                >
                                    {{ watch.case_size }}
                                </span>

                                <span
                                    v-if="watch.movement"
                                    class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600"
                                >
                                    {{ watch.movement }}
                                </span>
                            </div>

                            <div class="space-y-2">
                                <button
                                    v-if="watch.status !== 'sold'"
                                    type="button"
                                    @click="markAsSold(watch)"
                                    class="w-full rounded-2xl bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] px-4 py-3 text-sm font-black text-white shadow-lg shadow-[#0b3a56]/15 ring-1 ring-white/20 transition hover:brightness-110 active:scale-95"
                                >
                                    Mark as Sold
                                </button>

                                <div
                                    class="grid grid-cols-2 gap-2 sm:grid-cols-3"
                                >
                                    <button
                                        type="button"
                                        @click="openEditModal(watch)"
                                        class="rounded-2xl border border-[#0b3a56]/20 bg-[#eef8fb] px-4 py-3 text-sm font-black text-[#0b3a56] transition hover:border-[#0b3a56]/40 hover:bg-[#dff3f8] active:scale-95"
                                    >
                                        Edit
                                    </button>

                                    <button
                                        type="button"
                                        @click="duplicateWatch(watch)"
                                        class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-600 transition hover:border-[#0b3a56]/30 hover:bg-[#eef8fb] hover:text-[#0b3a56] active:scale-95"
                                    >
                                        Duplicate
                                    </button>

                                    <button
                                        type="button"
                                        @click="deleteWatch(watch)"
                                        class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-black text-rose-600 transition hover:border-rose-300 hover:bg-rose-100 active:scale-95"
                                    >
                                        Delete
                                    </button>
                                </div>
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
                        class="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-white/10 text-2xl text-white/80"
                    >
                        ◉
                    </div>

                    <p class="mt-5 text-lg font-black text-white">
                        No watches found.
                    </p>

                    <p class="mt-2 text-sm text-slate-300">
                        Try changing your search or filter.
                    </p>

                    <button
                        type="button"
                        @click="resetFilters"
                        class="mt-6 rounded-2xl bg-white px-5 py-3 text-sm font-black text-[#071923] shadow-lg shadow-black/20 ring-1 ring-white/20 transition hover:bg-white/90 active:scale-95"
                    >
                        Clear Filters
                    </button>
                </section>

                <!-- Pagination -->
                <div
                    v-if="watches.links && watches.links.length > 3"
                    class="mt-8 flex flex-wrap justify-center gap-2"
                >
                    <Link
                        v-for="link in watches.links"
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
                                                class="text-[10px] font-black uppercase tracking-[0.3em] text-white/80 sm:text-xs"
                                            >
                                                Watch Gallery Manila
                                            </p>

                                            <span
                                                class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide"
                                                :class="
                                                    modalMode === 'create'
                                                        ? 'bg-white text-[#071923]'
                                                        : 'bg-white/10 text-slate-200'
                                                "
                                            >
                                                {{
                                                    modalMode === "create"
                                                        ? "New Listing"
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

                                <div
                                    class="mt-5 grid grid-cols-3 gap-2 rounded-2xl border border-white/10 bg-white/10 p-1.5 backdrop-blur-xl"
                                >
                                    <button
                                        v-for="tab in tabs"
                                        :key="tab.key"
                                        type="button"
                                        @click="goToTab(tab.key)"
                                        class="rounded-xl px-2 py-3 text-center transition active:scale-95"
                                        :class="
                                            activeModalTab === tab.key
                                                ? 'bg-white text-[#071923] shadow-lg shadow-black/20 ring-1 ring-white/20'
                                                : 'text-slate-300 hover:bg-white/10 hover:text-white'
                                        "
                                    >
                                        <span
                                            class="block text-xs font-black sm:text-sm"
                                        >
                                            {{ tab.label }}
                                        </span>
                                        <span
                                            class="hidden text-[10px] font-semibold opacity-70 sm:block"
                                        >
                                            {{ tab.helper }}
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <form
                                @submit.prevent="handlePrimaryAction"
                                class="flex min-h-0 flex-1 flex-col"
                            >
                                <div
                                    class="min-h-0 flex-1 overflow-y-auto bg-[#eef3f7] px-5 py-5 sm:px-6"
                                >
                                    <div
                                        v-show="activeModalTab === 'details'"
                                        class="space-y-5"
                                    >
                                        <section
                                            class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-xl shadow-[#0b3a56]/10 sm:p-5"
                                        >
                                            <div class="mb-5">
                                                <h3
                                                    class="text-base font-black text-[#071923]"
                                                >
                                                    Basic Information
                                                </h3>
                                                <p
                                                    class="mt-1 text-sm text-slate-500"
                                                >
                                                    Main watch details shown in
                                                    the catalog.
                                                </p>
                                            </div>

                                            <div
                                                class="grid gap-4 sm:grid-cols-2"
                                            >
                                                <div
                                                    v-for="field in basicFields"
                                                    :key="field.key"
                                                >
                                                    <label
                                                        class="mb-1.5 block text-sm font-bold text-slate-700"
                                                    >
                                                        {{ field.label }}
                                                        <span
                                                            v-if="
                                                                field.required
                                                            "
                                                            class="text-[#0b3a56]"
                                                        >
                                                            *
                                                        </span>
                                                    </label>

                                                    <select
                                                        v-if="
                                                            field.key ===
                                                            'condition'
                                                        "
                                                        v-model="
                                                            form[field.key]
                                                        "
                                                        class="w-full rounded-2xl border-slate-200 bg-slate-50 text-[#071923] focus:border-[#0b3a56] focus:ring-[#0b3a56]"
                                                    >
                                                        <option
                                                            v-for="option in conditionOptions"
                                                            :key="option.value"
                                                            :value="
                                                                option.value
                                                            "
                                                        >
                                                            {{ option.label }}
                                                        </option>
                                                    </select>

                                                    <input
                                                        v-else
                                                        v-model="
                                                            form[field.key]
                                                        "
                                                        :type="field.type"
                                                        :placeholder="
                                                            field.placeholder
                                                        "
                                                        class="w-full rounded-2xl border-slate-200 bg-slate-50 text-[#071923] placeholder:text-slate-400 focus:border-[#0b3a56] focus:ring-[#0b3a56]"
                                                    />

                                                    <p
                                                        v-if="
                                                            form.errors[
                                                                field.key
                                                            ]
                                                        "
                                                        class="mt-1 text-sm text-rose-600"
                                                    >
                                                        {{
                                                            form.errors[
                                                                field.key
                                                            ]
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </section>

                                        <section
                                            class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-xl shadow-[#0b3a56]/10 sm:p-5"
                                        >
                                            <div class="mb-5">
                                                <h3
                                                    class="text-base font-black text-[#071923]"
                                                >
                                                    Gender Category
                                                </h3>
                                                <p
                                                    class="mt-1 text-sm text-slate-500"
                                                >
                                                    New watches default to
                                                    Unisex unless you choose Men
                                                    or Women.
                                                </p>
                                            </div>

                                            <div
                                                class="grid gap-3 sm:grid-cols-3"
                                            >
                                                <button
                                                    v-for="gender in genderOptions"
                                                    :key="gender.value"
                                                    type="button"
                                                    @click="
                                                        form.gender =
                                                            gender.value
                                                    "
                                                    class="rounded-2xl border p-4 text-left transition active:scale-[0.98]"
                                                    :class="
                                                        form.gender ===
                                                        gender.value
                                                            ? 'border-[#0b3a56]/30 bg-[#eef8fb] ring-1 ring-[#0b3a56]/10'
                                                            : 'border-slate-200 bg-white hover:border-[#0b3a56]/30 hover:bg-slate-50'
                                                    "
                                                >
                                                    <div
                                                        class="flex items-center justify-between gap-3"
                                                    >
                                                        <p
                                                            class="font-black"
                                                            :class="
                                                                form.gender ===
                                                                gender.value
                                                                    ? 'text-[#0b3a56]'
                                                                    : 'text-[#071923]'
                                                            "
                                                        >
                                                            {{ gender.label }}
                                                        </p>

                                                        <span
                                                            class="grid h-6 w-6 place-items-center rounded-full border text-xs"
                                                            :class="
                                                                form.gender ===
                                                                gender.value
                                                                    ? 'border-[#0b3a56] bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] text-white'
                                                                    : 'border-slate-300 text-transparent'
                                                            "
                                                        >
                                                            ✓
                                                        </span>
                                                    </div>

                                                    <p
                                                        class="mt-1 text-xs text-slate-500"
                                                    >
                                                        {{ gender.description }}
                                                    </p>
                                                </button>
                                            </div>

                                            <p
                                                v-if="form.errors.gender"
                                                class="mt-2 text-sm text-rose-600"
                                            >
                                                {{ form.errors.gender }}
                                            </p>
                                        </section>

                                        <section
                                            class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-xl shadow-[#0b3a56]/10 sm:p-5"
                                        >
                                            <div class="mb-5">
                                                <h3
                                                    class="text-base font-black text-[#071923]"
                                                >
                                                    Specifications
                                                </h3>
                                                <p
                                                    class="mt-1 text-sm text-slate-500"
                                                >
                                                    Optional details for better
                                                    customer browsing.
                                                </p>
                                            </div>

                                            <div
                                                class="grid gap-4 sm:grid-cols-2"
                                            >
                                                <div
                                                    v-for="field in specFields"
                                                    :key="field.key"
                                                >
                                                    <label
                                                        class="mb-1.5 block text-sm font-bold text-slate-700"
                                                    >
                                                        {{ field.label }}
                                                    </label>

                                                    <input
                                                        v-model="
                                                            form[field.key]
                                                        "
                                                        type="text"
                                                        :placeholder="
                                                            field.placeholder
                                                        "
                                                        class="w-full rounded-2xl border-slate-200 bg-slate-50 text-[#071923] placeholder:text-slate-400 focus:border-[#0b3a56] focus:ring-[#0b3a56]"
                                                    />
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                <label
                                                    class="mb-1.5 block text-sm font-bold text-slate-700"
                                                >
                                                    Description
                                                </label>
                                                <textarea
                                                    v-model="form.description"
                                                    rows="4"
                                                    placeholder="Short selling description for this watch..."
                                                    class="w-full rounded-2xl border-slate-200 bg-slate-50 text-[#071923] placeholder:text-slate-400 focus:border-[#0b3a56] focus:ring-[#0b3a56]"
                                                />
                                            </div>
                                        </section>
                                    </div>

                                    <div
                                        v-show="activeModalTab === 'pricing'"
                                        class="space-y-5"
                                    >
                                        <section
                                            class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-xl shadow-[#0b3a56]/10 sm:p-5"
                                        >
                                            <div class="mb-5">
                                                <h3
                                                    class="text-base font-black text-[#071923]"
                                                >
                                                    Pricing
                                                </h3>
                                                <p
                                                    class="mt-1 text-sm text-slate-500"
                                                >
                                                    Add cost and public selling
                                                    price.
                                                </p>
                                            </div>

                                            <div
                                                class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
                                            >
                                                <div
                                                    v-for="field in pricingFields"
                                                    :key="field.key"
                                                >
                                                    <label
                                                        class="mb-1.5 block text-sm font-bold text-slate-700"
                                                    >
                                                        {{ field.label }}
                                                    </label>

                                                    <input
                                                        v-model="
                                                            form[field.key]
                                                        "
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        inputmode="decimal"
                                                        :placeholder="
                                                            field.placeholder
                                                        "
                                                        class="w-full rounded-2xl border-slate-200 bg-slate-50 text-[#071923] placeholder:text-slate-400 focus:border-[#0b3a56] focus:ring-[#0b3a56]"
                                                    />

                                                    <p
                                                        v-if="
                                                            form.errors[
                                                                field.key
                                                            ]
                                                        "
                                                        class="mt-1 text-sm text-rose-600"
                                                    >
                                                        {{
                                                            form.errors[
                                                                field.key
                                                            ]
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </section>

                                        <section
                                            class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-xl shadow-[#0b3a56]/10 sm:p-5"
                                        >
                                            <div class="mb-5">
                                                <h3
                                                    class="text-base font-black text-[#071923]"
                                                >
                                                    Inventory Status
                                                </h3>
                                                <p
                                                    class="mt-1 text-sm text-slate-500"
                                                >
                                                    Choose how this watch should
                                                    appear.
                                                </p>
                                            </div>

                                            <div
                                                class="grid gap-3 sm:grid-cols-3"
                                            >
                                                <button
                                                    v-for="status in statusOptions"
                                                    :key="status.value"
                                                    type="button"
                                                    @click="
                                                        setStatus(status.value)
                                                    "
                                                    class="rounded-2xl border p-4 text-left transition active:scale-[0.98]"
                                                    :class="
                                                        form.status ===
                                                        status.value
                                                            ? 'border-[#0b3a56]/30 bg-[#eef8fb] ring-1 ring-[#0b3a56]/10'
                                                            : 'border-slate-200 bg-white hover:border-[#0b3a56]/30 hover:bg-slate-50'
                                                    "
                                                >
                                                    <div
                                                        class="flex items-center justify-between gap-3"
                                                    >
                                                        <p
                                                            class="font-black"
                                                            :class="
                                                                form.status ===
                                                                status.value
                                                                    ? 'text-[#0b3a56]'
                                                                    : 'text-[#071923]'
                                                            "
                                                        >
                                                            {{ status.label }}
                                                        </p>

                                                        <span
                                                            class="grid h-6 w-6 place-items-center rounded-full border text-xs"
                                                            :class="
                                                                form.status ===
                                                                status.value
                                                                    ? 'border-[#0b3a56] bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] text-white'
                                                                    : 'border-slate-300 text-transparent'
                                                            "
                                                        >
                                                            ✓
                                                        </span>
                                                    </div>

                                                    <p
                                                        class="mt-1 text-xs text-slate-500"
                                                    >
                                                        {{ status.description }}
                                                    </p>
                                                </button>
                                            </div>

                                            <div
                                                class="mt-5 grid gap-3 sm:grid-cols-2"
                                            >
                                                <label
                                                    class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#0b3a56]/25 hover:bg-[#f8fafc]"
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
                                                            Visible on website
                                                        </p>
                                                        <p
                                                            class="text-xs text-slate-500"
                                                        >
                                                            Customers can see
                                                            this listing.
                                                        </p>
                                                    </div>
                                                </label>

                                                <label
                                                    class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-[#0b3a56]/25 hover:bg-[#f8fafc]"
                                                >
                                                    <input
                                                        v-model="
                                                            form.is_in_demand
                                                        "
                                                        type="checkbox"
                                                        class="rounded border-slate-300 bg-white text-[#0b3a56] focus:ring-[#0b3a56]"
                                                    />
                                                    <div>
                                                        <p
                                                            class="text-sm font-black text-[#071923]"
                                                        >
                                                            In-Demand watch
                                                        </p>
                                                        <p
                                                            class="text-xs text-slate-500"
                                                        >
                                                            Adds an in-demand
                                                            label to this
                                                            listing.
                                                        </p>
                                                    </div>
                                                </label>
                                            </div>
                                        </section>

                                        <section
                                            v-if="form.status === 'sold'"
                                            class="rounded-[2rem] border border-[#0b3a56]/10 bg-[#eef8fb] p-4 shadow-xl shadow-[#0b3a56]/10 sm:p-5"
                                        >
                                            <div class="mb-5">
                                                <h3
                                                    class="text-base font-black text-[#071923]"
                                                >
                                                    Sold Details
                                                </h3>
                                                <p
                                                    class="mt-1 text-sm text-slate-500"
                                                >
                                                    These details will be used
                                                    for sold records.
                                                </p>
                                            </div>

                                            <div
                                                class="grid gap-4 sm:grid-cols-3"
                                            >
                                                <div
                                                    v-for="field in soldFields"
                                                    :key="field.key"
                                                >
                                                    <label
                                                        class="mb-1.5 block text-sm font-bold text-slate-700"
                                                    >
                                                        {{ field.label }}
                                                    </label>

                                                    <input
                                                        v-model="
                                                            form[field.key]
                                                        "
                                                        :type="field.type"
                                                        :placeholder="
                                                            field.placeholder
                                                        "
                                                        :step="
                                                            field.type ===
                                                            'number'
                                                                ? '0.01'
                                                                : null
                                                        "
                                                        class="w-full rounded-2xl border-slate-200 bg-white text-[#071923] placeholder:text-slate-400 focus:border-[#0b3a56] focus:ring-[#0b3a56]"
                                                    />
                                                </div>
                                            </div>
                                        </section>
                                    </div>

                                    <div
                                        v-show="activeModalTab === 'photos'"
                                        class="space-y-5"
                                    >
                                        <section
                                            class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-xl shadow-[#0b3a56]/10 sm:p-5"
                                        >
                                            <div
                                                class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                                            >
                                                <div>
                                                    <h3
                                                        class="text-base font-black text-[#071923]"
                                                    >
                                                        Watch Photos
                                                    </h3>
                                                    <p
                                                        class="mt-1 text-sm text-slate-500"
                                                    >
                                                        Upload clear product
                                                        images.
                                                    </p>
                                                </div>

                                                <div
                                                    class="rounded-2xl bg-[#eef8fb] px-4 py-2 text-sm font-black text-[#0b3a56]"
                                                >
                                                    {{ totalSelectedImages }} /
                                                    {{ MAX_WATCH_IMAGES }}
                                                    photo(s)
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
                                                    Tap to upload watch photos
                                                </p>

                                                <p
                                                    class="mt-1 text-xs text-slate-500"
                                                >
                                                    JPG, PNG, WEBP up to 5MB
                                                    each. Maximum 6 photos.
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
                                            v-if="hasGalleryItems"
                                            class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-xl shadow-[#0b3a56]/10 sm:p-5"
                                        >
                                            <div
                                                class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                                            >
                                                <div>
                                                    <p
                                                        class="text-xs font-black uppercase tracking-[0.2em] text-slate-400"
                                                    >
                                                        Photo Arrangement
                                                    </p>

                                                    <p
                                                        class="mt-1 text-sm text-slate-500"
                                                    >
                                                        Choose the primary photo
                                                        and arrange the order
                                                        shown on the website.
                                                    </p>
                                                </div>

                                                <div
                                                    class="rounded-2xl bg-slate-50 px-4 py-2 text-xs font-black text-slate-500"
                                                >
                                                    First photo appears first
                                                </div>
                                            </div>

                                            <div
                                                class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6"
                                            >
                                                <article
                                                    v-for="(
                                                        item, index
                                                    ) in galleryItems"
                                                    :key="item.token"
                                                    class="overflow-hidden rounded-2xl border bg-slate-50 shadow-sm transition"
                                                    :class="
                                                        primaryImageToken ===
                                                        item.token
                                                            ? 'border-[#0b3a56] ring-2 ring-[#0b3a56]/15'
                                                            : 'border-slate-200'
                                                    "
                                                >
                                                    <div
                                                        class="relative aspect-square overflow-hidden bg-slate-100"
                                                    >
                                                        <img
                                                            :src="item.url"
                                                            :alt="item.name"
                                                            class="h-full w-full object-cover"
                                                        />

                                                        <div
                                                            class="absolute left-2 top-2 rounded-full bg-white/95 px-2.5 py-1 text-[10px] font-black text-[#071923] shadow-lg shadow-black/10"
                                                        >
                                                            #{{ index + 1 }}
                                                        </div>

                                                        <button
                                                            type="button"
                                                            @click="
                                                                item.type ===
                                                                'existing'
                                                                    ? removeExistingImage(
                                                                          item,
                                                                      )
                                                                    : removeSelectedImage(
                                                                          item,
                                                                      )
                                                            "
                                                            class="absolute right-2 top-2 grid h-7 w-7 place-items-center rounded-full bg-white/95 text-sm font-black text-rose-600 shadow-lg shadow-black/10 transition hover:bg-rose-600 hover:text-white"
                                                            aria-label="Remove photo"
                                                        >
                                                            ×
                                                        </button>

                                                        <div
                                                            class="absolute bottom-2 left-2 right-2 flex flex-wrap gap-1"
                                                        >
                                                            <span
                                                                class="rounded-full bg-black/55 px-2 py-1 text-[9px] font-black uppercase tracking-wide text-white backdrop-blur"
                                                            >
                                                                {{
                                                                    item.type ===
                                                                    "existing"
                                                                        ? "Saved"
                                                                        : "New"
                                                                }}
                                                            </span>

                                                            <span
                                                                v-if="
                                                                    primaryImageToken ===
                                                                    item.token
                                                                "
                                                                class="rounded-full bg-white px-2 py-1 text-[9px] font-black uppercase tracking-wide text-[#071923]"
                                                            >
                                                                Primary
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <div class="space-y-2 p-2">
                                                        <button
                                                            type="button"
                                                            @click="
                                                                setPrimaryImage(
                                                                    item.token,
                                                                )
                                                            "
                                                            class="w-full rounded-xl px-3 py-2 text-[11px] font-black transition active:scale-95"
                                                            :class="
                                                                primaryImageToken ===
                                                                item.token
                                                                    ? 'bg-[#071923] text-white'
                                                                    : 'border border-[#0b3a56]/15 bg-[#eef8fb] text-[#0b3a56] hover:border-[#0b3a56]/30'
                                                            "
                                                        >
                                                            {{
                                                                primaryImageToken ===
                                                                item.token
                                                                    ? "Primary"
                                                                    : "Set Primary"
                                                            }}
                                                        </button>

                                                        <div
                                                            class="grid grid-cols-3 gap-1"
                                                        >
                                                            <button
                                                                type="button"
                                                                :disabled="
                                                                    index === 0
                                                                "
                                                                @click="
                                                                    moveGalleryItem(
                                                                        item.token,
                                                                        -1,
                                                                    )
                                                                "
                                                                class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-black text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-35"
                                                                aria-label="Move photo left"
                                                            >
                                                                ↑
                                                            </button>

                                                            <button
                                                                type="button"
                                                                :disabled="
                                                                    index === 0
                                                                "
                                                                @click="
                                                                    moveGalleryItemToStart(
                                                                        item.token,
                                                                    )
                                                                "
                                                                class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[10px] font-black text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-35"
                                                            >
                                                                First
                                                            </button>

                                                            <button
                                                                type="button"
                                                                :disabled="
                                                                    index ===
                                                                    galleryItems.length -
                                                                        1
                                                                "
                                                                @click="
                                                                    moveGalleryItem(
                                                                        item.token,
                                                                        1,
                                                                    )
                                                                "
                                                                class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-black text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-35"
                                                                aria-label="Move photo right"
                                                            >
                                                                ↓
                                                            </button>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>

                                            <p
                                                class="mt-4 rounded-2xl bg-slate-50 px-4 py-3 text-xs font-semibold leading-relaxed text-slate-500"
                                            >
                                                Tip: The photo marked as Primary
                                                will be used as the main card
                                                image. Arrangement controls the
                                                image order on the detail page.
                                            </p>
                                        </section>
                                    </div>
                                </div>

                                <div
                                    class="shrink-0 border-t border-slate-200 bg-white px-5 pb-[calc(env(safe-area-inset-bottom)+16px)] pt-4 sm:px-6 sm:pb-4"
                                >
                                    <div
                                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div class="text-center sm:text-left">
                                            <p
                                                class="text-xs font-bold text-slate-500"
                                            >
                                                {{ stepText }}
                                            </p>

                                            <div
                                                class="mt-2 flex justify-center gap-1.5 sm:justify-start"
                                            >
                                                <span
                                                    v-for="tab in tabOrder"
                                                    :key="tab"
                                                    class="h-1.5 rounded-full transition-all"
                                                    :class="
                                                        activeModalTab === tab
                                                            ? 'w-8 bg-[#0b3a56]'
                                                            : 'w-3 bg-slate-200'
                                                    "
                                                />
                                            </div>
                                        </div>

                                        <div
                                            class="grid grid-cols-2 gap-3 sm:flex"
                                        >
                                            <button
                                                v-if="!isFirstStep"
                                                type="button"
                                                @click="goToPreviousStep"
                                                class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-600 transition hover:bg-slate-50 hover:text-[#071923] active:scale-95"
                                            >
                                                Back
                                            </button>

                                            <button
                                                v-else
                                                type="button"
                                                @click="requestCloseModal"
                                                class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-600 transition hover:bg-slate-50 hover:text-[#071923] active:scale-95"
                                            >
                                                Cancel
                                            </button>

                                            <button
                                                type="button"
                                                :disabled="form.processing"
                                                @click="handlePrimaryAction"
                                                class="rounded-2xl bg-gradient-to-r from-[#061725] via-[#0b3a56] to-[#071923] px-6 py-3 text-sm font-black text-white shadow-lg shadow-[#0b3a56]/15 ring-1 ring-white/10 transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-60 active:scale-95"
                                            >
                                                {{ primaryActionLabel }}
                                            </button>
                                        </div>
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

:global(.wgm-swal-container),
:global(.swal2-container) {
    z-index: 2147483647 !important;
}

:global(.wgm-swal-popup) {
    border-radius: 1.5rem !important;
    border: 1px solid rgba(226, 232, 240, 0.95) !important;
    box-shadow: 0 30px 90px rgba(15, 23, 42, 0.22) !important;
}
</style>

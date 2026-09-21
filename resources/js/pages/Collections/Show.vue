<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    BarChart3,
    BookOpen,
    Check,
    CheckCircle2,
    Edit3,
    Layers,
    PieChart,
    Plus,
    Settings,
    Share2,
    Trash2,
    User,
    UserPlus,
    Users,
    X,
} from '@lucide/vue';
import { ref, computed } from 'vue';

interface MangaVolume {
    id: number;
    number: string;
    title: string;
    isbn13: string | null;
    cover_url: string | null;
    is_owned: boolean;
    item_id: number | null;
    item_status: string | null;
    user: { id: number; name: string } | null;
}

interface OwnerSummary {
    user_id: number;
    name: string;
    count: number;
}

interface MangaGroup {
    manga: {
        id: number;
        external_id: string;
        title: string;
        cover_url: string | null;
        status: string | null;
        total_volumes: number | null;
    };
    volumes: MangaVolume[];
    owned_count: number;
    total_count: number;
    owners_summary: OwnerSummary[];
}

interface AvailableManga {
    id: number;
    title: string;
    cover_url: string | null;
    external_id: string;
    total_volumes: number | null;
}

interface Contributor {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    is_owner: boolean;
}

interface UserStatBreakdown {
    user_id: number;
    user_name: string;
    is_owner: boolean;
    total_owned: number;
    mangas_count: number;
    mangas: Record<string, { title: string; count: number }>;
}

interface CollectionData {
    id: number;
    name: string;
    type: 'personal' | 'shared';
    is_owner: boolean;
    owner: {
        id: number;
        name: string;
        email: string;
        avatar: string | null;
    };
    members: Array<{
        id: number;
        user_id: number;
        name: string;
        email: string;
        avatar: string | null;
        role: 'owner' | 'editor' | 'viewer';
        status: 'pending' | 'accepted' | 'declined';
    }>;
}

const props = defineProps<{
    collection: CollectionData;
    contributors: Contributor[];
    userBreakdown: UserStatBreakdown[];
    mangasGrouped: MangaGroup[];
    availableMangas: AvailableManga[];
    totalItemsCount: number;
}>();

const page = usePage();
const authUser = computed(() => page.props.auth?.user as { id: number; name: string } | null);

// State
const showSettingsModal = ref(false);
const showAddMangaModal = ref(false);
const showStatsModal = ref(false);
const selectedMangaId = ref<number | ''>('');

// Selected user to assign when clicking to check a volume (defaults to currently logged in user)
const selectedAssigneeId = ref<number>(authUser.value?.id ?? props.contributors[0]?.id ?? 1);

// Rename collection form
const renameForm = useForm({
    name: props.collection.name,
});

const submitRename = () => {
    renameForm.patch(`/collections/${props.collection.id}`, {
        onSuccess: () => {
            showSettingsModal.value = false;
        },
    });
};

// Invite member form
const memberForm = useForm({
    email: '',
    role: 'editor',
});

const submitInvite = () => {
    memberForm.post(`/collections/${props.collection.id}/members`, {
        onSuccess: () => {
            memberForm.reset();
        },
    });
};

const removeMember = (memberId: number) => {
    if (confirm('Retirer ce membre de la collection ?')) {
        useForm({}).delete(`/collections/${props.collection.id}/members/${memberId}`);
    }
};

// Add manga form
const addMangaForm = useForm({
    manga_id: null as number | null,
});

const submitAddManga = () => {
    if (!selectedMangaId.value) return;
    addMangaForm.manga_id = Number(selectedMangaId.value);
    addMangaForm.post(`/collections/${props.collection.id}/add-manga`, {
        onSuccess: () => {
            selectedMangaId.value = '';
            showAddMangaModal.value = false;
        },
    });
};

// Checkbox toggle volume possession with assigned contributor
const toggleVolume = (vol: MangaVolume) => {
    useForm({
        collection_id: props.collection.id,
        volume_id: vol.id,
        status: 'owned',
        user_id: selectedAssigneeId.value,
    }).post('/collection-items/toggle', {
        preserveScroll: true,
    });
};

const deleteCollection = () => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette collection ?')) {
        useForm({}).delete(`/collections/${props.collection.id}`);
    }
};

// User color badges helper
const getUserBadgeClass = (userId: number) => {
    const colors = [
        'bg-blue-500/10 text-blue-600 border-blue-500/20 dark:text-blue-400',
        'bg-rose-500/10 text-rose-600 border-rose-500/20 dark:text-rose-400',
        'bg-purple-500/10 text-purple-600 border-purple-500/20 dark:text-purple-400',
        'bg-amber-500/10 text-amber-600 border-amber-500/20 dark:text-amber-400',
        'bg-emerald-500/10 text-emerald-600 border-emerald-500/20 dark:text-emerald-400',
    ];
    return colors[userId % colors.length];
};
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: '/dashboard' }, { title: collection.name, href: `/collections/${collection.id}` }]">
        <Head :title="`${collection.name} - MangaShelf`" />

        <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">
            <!-- Header Banner -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-3xl border border-neutral-200/80 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/60">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wider text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">
                            {{ contributors.length > 1 ? 'Collection Partagée' : 'Collection' }}
                        </span>
                        <span v-if="contributors.length > 1" class="text-xs text-amber-600 dark:text-amber-400 font-semibold">
                            • {{ contributors.length }} personnes
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-neutral-900 dark:text-white">
                        {{ collection.name }}
                    </h1>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">
                        {{ mangasGrouped.length }} séries • {{ totalItemsCount }} tomes possédés au total
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-if="contributors.length > 1 || totalItemsCount > 0"
                        @click="showStatsModal = true"
                        class="inline-flex items-center gap-2 rounded-2xl border border-neutral-200 bg-white px-4 py-2.5 text-xs font-semibold text-neutral-700 shadow-sm transition hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-300 dark:hover:bg-neutral-800"
                    >
                        <PieChart class="size-4 text-amber-500" />
                        Stats & Propriétaires
                    </button>

                    <button
                        @click="showAddMangaModal = true"
                        class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-amber-500 to-orange-500 px-4 py-2.5 text-xs font-semibold text-white shadow-md shadow-orange-500/20 transition hover:from-amber-600 hover:to-orange-600"
                    >
                        <Plus class="size-4" />
                        Ajouter un manga
                    </button>

                    <button
                        @click="showSettingsModal = true"
                        class="inline-flex items-center gap-2 rounded-2xl border border-neutral-200 bg-white px-4 py-2.5 text-xs font-semibold text-neutral-700 shadow-sm transition hover:bg-neutral-50 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-300 dark:hover:bg-neutral-800"
                    >
                        <Settings class="size-4 text-neutral-400" />
                        Gérer / Inviter
                    </button>
                </div>
            </div>

            <!-- Ownership Stats Summary Strip (When multiple contributors or items) -->
            <div v-if="userBreakdown && userBreakdown.length > 0 && totalItemsCount > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="stat in userBreakdown"
                    :key="stat.user_id"
                    class="rounded-3xl border border-neutral-200/80 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/60 flex items-center justify-between gap-4"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex size-11 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold text-sm">
                            <User class="size-5" />
                        </div>
                        <div>
                            <p class="text-sm font-bold text-neutral-900 dark:text-white flex items-center gap-1.5">
                                {{ stat.user_name }}
                                <span v-if="stat.is_owner" class="text-[10px] text-amber-600 font-normal">(Créateur)</span>
                            </p>
                            <p class="text-xs text-neutral-500">
                                {{ stat.mangas_count }} séries suivies
                            </p>
                        </div>
                    </div>

                    <div class="text-right">
                        <span class="text-xl font-black text-neutral-900 dark:text-white">
                            {{ stat.total_owned }}
                        </span>
                        <span class="text-[11px] block text-neutral-400">tomes</span>
                    </div>
                </div>
            </div>

            <!-- Active Assignee Bar for Multi-Contributor Collections -->
            <div
                v-if="contributors.length > 1"
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-2xl bg-amber-500/10 p-3.5 px-5 border border-amber-500/20 dark:bg-amber-500/5"
            >
                <div class="flex items-center gap-2">
                    <User class="size-4 text-amber-600 dark:text-amber-400" />
                    <span class="text-xs font-semibold text-neutral-900 dark:text-white">
                        Qui possède les tomes que vous cochez ?
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        v-for="c in contributors"
                        :key="c.id"
                        @click="selectedAssigneeId = c.id"
                        :class="[
                            'rounded-xl px-3 py-1.5 text-xs font-bold transition flex items-center gap-1.5',
                            selectedAssigneeId === c.id
                                ? 'bg-amber-500 text-white shadow-sm'
                                : 'bg-white text-neutral-700 hover:bg-neutral-100 dark:bg-neutral-800 dark:text-neutral-300'
                        ]"
                    >
                        <span>{{ c.name }}</span>
                        <Check v-if="selectedAssigneeId === c.id" class="size-3" />
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="mangasGrouped.length === 0"
                class="rounded-3xl border border-dashed border-neutral-300 py-16 text-center dark:border-neutral-800 bg-white/50 dark:bg-neutral-900/30"
            >
                <BookOpen class="size-12 text-neutral-400 mx-auto mb-3 stroke-[1.5]" />
                <h3 class="text-lg font-bold text-neutral-800 dark:text-neutral-200">
                    Aucun manga dans cette collection
                </h3>
                <p class="text-xs text-neutral-500 mt-1 max-w-sm mx-auto">
                    Cliquez sur "Ajouter un manga" pour choisir une série et cocher vos tomes possédés.
                </p>
                <button
                    @click="showAddMangaModal = true"
                    class="mt-4 inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-semibold text-white shadow hover:bg-amber-600 transition"
                >
                    <Plus class="size-4" />
                    Ajouter un manga à la collection
                </button>
            </div>

            <!-- Manga Series List with Volume Checklists & Ownership Breakdowns -->
            <div v-else class="space-y-8">
                <div
                    v-for="group in mangasGrouped"
                    :key="group.manga.id"
                    class="rounded-3xl border border-neutral-200/80 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/60"
                >
                    <!-- Series Header with Progression & Owners Pill Breakdown -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-neutral-100 dark:border-neutral-800">
                        <div class="flex items-center gap-4">
                            <img
                                v-if="group.manga.cover_url"
                                :src="group.manga.cover_url"
                                :alt="group.manga.title"
                                class="size-16 rounded-2xl object-cover shadow-sm"
                            />
                            <div>
                                <Link
                                    :href="`/manga/${group.manga.external_id}`"
                                    class="text-lg font-bold text-neutral-900 hover:text-amber-600 dark:text-white dark:hover:text-amber-400 transition"
                                >
                                    {{ group.manga.title }}
                                </Link>

                                <div class="flex flex-wrap items-center gap-3 mt-1">
                                    <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                        {{ group.owned_count }} / {{ group.total_count }} tomes
                                    </span>

                                    <!-- Progress mini-bar -->
                                    <div class="w-24 sm:w-32 bg-neutral-100 h-2 rounded-full overflow-hidden dark:bg-neutral-800">
                                        <div
                                            class="bg-emerald-500 h-full rounded-full transition-all duration-500"
                                            :style="{ width: `${group.total_count > 0 ? (group.owned_count / group.total_count) * 100 : 0}%` }"
                                        ></div>
                                    </div>

                                    <!-- Owners breakdown badges for this manga -->
                                    <div v-if="group.owners_summary && group.owners_summary.length > 0" class="flex flex-wrap items-center gap-1.5 ml-2">
                                        <span
                                            v-for="own in group.owners_summary"
                                            :key="own.user_id"
                                            :class="['rounded-full px-2 py-0.5 text-[10px] font-bold border', getUserBadgeClass(own.user_id)]"
                                        >
                                            {{ own.name }} ({{ own.count }})
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <Link
                            :href="`/manga/${group.manga.external_id}`"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-600 hover:underline dark:text-amber-400 self-start sm:self-auto"
                        >
                            Fiche de la série →
                        </Link>
                    </div>

                    <!-- Volume Checklist Grid -->
                    <div class="pt-5">
                        <p class="text-xs text-neutral-400 font-medium mb-3">
                            Cliquez sur un tome pour marquer ou retirer sa possession :
                        </p>

                        <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 xl:grid-cols-10 gap-2.5">
                            <button
                                v-for="vol in group.volumes"
                                :key="vol.id"
                                @click="toggleVolume(vol)"
                                :class="[
                                    'group relative flex flex-col items-center justify-between rounded-xl p-2.5 border transition-all text-center select-none',
                                    vol.is_owned
                                        ? 'bg-emerald-500/10 border-emerald-500/40 text-emerald-900 dark:text-emerald-300 dark:bg-emerald-500/15'
                                        : 'bg-neutral-50/70 border-neutral-200/80 text-neutral-500 hover:bg-neutral-100 dark:bg-neutral-800/40 dark:border-neutral-800 dark:text-neutral-400 dark:hover:bg-neutral-800'
                                ]"
                            >
                                <!-- Checkbox Indicator -->
                                <div class="w-full flex items-center justify-between mb-1.5">
                                    <span class="text-[11px] font-extrabold font-mono">
                                        T.{{ vol.number }}
                                    </span>
                                    <div
                                        :class="[
                                            'size-4 rounded-md flex items-center justify-center border transition',
                                            vol.is_owned
                                                ? 'bg-emerald-500 border-emerald-500 text-white shadow-sm'
                                                : 'border-neutral-300 bg-white dark:border-neutral-700 dark:bg-neutral-800'
                                        ]"
                                    >
                                        <Check v-if="vol.is_owned" class="size-3 stroke-[3]" />
                                    </div>
                                </div>

                                <!-- Mini Cover -->
                                <div class="size-12 rounded-lg overflow-hidden bg-neutral-200 dark:bg-neutral-700 mb-1">
                                    <img
                                        v-if="vol.cover_url"
                                        :src="vol.cover_url"
                                        :alt="vol.title"
                                        class="h-full w-full object-cover"
                                        loading="lazy"
                                    />
                                    <div v-else class="flex h-full w-full items-center justify-center text-neutral-400">
                                        <BookOpen class="size-4" />
                                    </div>
                                </div>

                                <!-- Assigned Owner Tag if owned in shared collection -->
                                <span
                                    v-if="vol.is_owned && vol.user"
                                    class="text-[9px] font-bold truncate w-full text-emerald-700 dark:text-emerald-300"
                                    :title="`Possédé par ${vol.user.name}`"
                                >
                                    {{ vol.user.name }}
                                </span>
                                <span v-else class="text-[10px] font-medium truncate w-full text-neutral-400">
                                    {{ vol.is_owned ? 'Possédé' : 'Non possédé' }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Stats Modal (Who owns what) -->
            <div
                v-if="showStatsModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            >
                <div class="w-full max-w-2xl rounded-3xl border border-neutral-200 bg-white p-6 shadow-2xl dark:border-neutral-800 dark:bg-neutral-900 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between pb-4 border-b border-neutral-100 dark:border-neutral-800">
                        <h3 class="text-lg font-bold text-neutral-900 dark:text-white flex items-center gap-2">
                            <BarChart3 class="size-5 text-amber-500" />
                            Répartition des tomes par personne
                        </h3>
                        <button @click="showStatsModal = false" class="rounded-lg p-1 text-neutral-400 hover:text-neutral-600 dark:hover:text-white">
                            <X class="size-5" />
                        </button>
                    </div>

                    <div class="mt-5 space-y-6">
                        <div
                            v-for="stat in userBreakdown"
                            :key="stat.user_id"
                            class="rounded-2xl border border-neutral-200/80 p-5 bg-neutral-50/50 dark:border-neutral-800 dark:bg-neutral-800/40"
                        >
                            <div class="flex items-center justify-between pb-3 border-b border-neutral-200/60 dark:border-neutral-700/60">
                                <div>
                                    <h4 class="text-sm font-bold text-neutral-900 dark:text-white">
                                        {{ stat.user_name }}
                                    </h4>
                                    <p class="text-xs text-neutral-500">
                                        {{ stat.total_owned }} tomes répartis sur {{ stat.mangas_count }} séries
                                    </p>
                                </div>
                                <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ totalItemsCount > 0 ? Math.round((stat.total_owned / totalItemsCount) * 100) : 0 }}% de la collection
                                </span>
                            </div>

                            <!-- List of series owned by this user -->
                            <div v-if="stat.mangas && Object.keys(stat.mangas).length > 0" class="pt-3">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-neutral-400 mb-2">Détail des mangas possédés :</p>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="(item, mangaId) in stat.mangas"
                                        :key="mangaId"
                                        class="rounded-xl bg-white px-3 py-1.5 text-xs font-medium text-neutral-800 shadow-sm border border-neutral-200 dark:bg-neutral-800 dark:text-neutral-200 dark:border-neutral-700"
                                    >
                                        {{ item.title }} : <strong class="text-amber-600 dark:text-amber-400">{{ item.count }} tomes</strong>
                                    </span>
                                </div>
                            </div>
                            <div v-else class="pt-3 text-xs text-neutral-400">
                                Aucun tome assigné pour l'instant.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Manga Modal -->
            <div
                v-if="showAddMangaModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            >
                <div class="w-full max-w-md rounded-3xl border border-neutral-200 bg-white p-6 shadow-2xl dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="flex items-center justify-between pb-4 border-b border-neutral-100 dark:border-neutral-800">
                        <h3 class="text-lg font-bold text-neutral-900 dark:text-white flex items-center gap-2">
                            <Plus class="size-5 text-amber-500" />
                            Ajouter un manga à la collection
                        </h3>
                        <button @click="showAddMangaModal = false" class="rounded-lg p-1 text-neutral-400 hover:text-neutral-600 dark:hover:text-white">
                            <X class="size-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitAddManga" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">
                                Sélectionnez un manga :
                            </label>
                            <select
                                v-model="selectedMangaId"
                                required
                                class="w-full rounded-xl border border-neutral-200 bg-neutral-50 px-3.5 py-2.5 text-sm text-neutral-900 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white focus:ring-2 focus:ring-amber-500"
                            >
                                <option value="" disabled>-- Choisir une série --</option>
                                <option v-for="m in availableMangas" :key="m.id" :value="m.id">
                                    {{ m.title }} ({{ m.total_volumes ? `${m.total_volumes} tomes` : 'En cours' }})
                                </option>
                            </select>
                        </div>

                        <p class="text-xs text-neutral-500">
                            Astuce : Si le manga n'est pas encore dans la liste, cherchez-le d'abord dans le <Link href="/" class="text-amber-600 hover:underline">Catalogue</Link>.
                        </p>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button
                                type="button"
                                @click="showAddMangaModal = false"
                                class="rounded-xl px-4 py-2 text-xs font-semibold text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                :disabled="!selectedMangaId || addMangaForm.processing"
                                class="rounded-xl bg-amber-500 px-5 py-2 text-xs font-semibold text-white shadow-md hover:bg-amber-600 transition"
                            >
                                {{ addMangaForm.processing ? 'Ajout...' : 'Ajouter la série' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Settings & Member Management Modal -->
            <div
                v-if="showSettingsModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            >
                <div class="w-full max-w-lg rounded-3xl border border-neutral-200 bg-white p-6 shadow-2xl dark:border-neutral-800 dark:bg-neutral-900 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between pb-4 border-b border-neutral-100 dark:border-neutral-800">
                        <h3 class="text-lg font-bold text-neutral-900 dark:text-white flex items-center gap-2">
                            <Settings class="size-5 text-amber-500" />
                            Paramètres de la collection
                        </h3>
                        <button @click="showSettingsModal = false" class="rounded-lg p-1 text-neutral-400 hover:text-neutral-600 dark:hover:text-white">
                            <X class="size-5" />
                        </button>
                    </div>

                    <!-- 1. Rename Collection -->
                    <form @submit.prevent="submitRename" class="mt-4 space-y-3 pb-6 border-b border-neutral-100 dark:border-neutral-800">
                        <label class="block text-xs font-bold uppercase tracking-wider text-neutral-400">
                            Nom de la collection
                        </label>
                        <div class="flex gap-2">
                            <input
                                v-model="renameForm.name"
                                type="text"
                                required
                                class="flex-1 rounded-xl border border-neutral-200 bg-neutral-50 px-3 py-2 text-xs text-neutral-900 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            />
                            <button
                                type="submit"
                                :disabled="renameForm.processing"
                                class="rounded-xl bg-neutral-900 px-4 py-2 text-xs font-semibold text-white hover:bg-neutral-800 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-100"
                            >
                                Renommer
                            </button>
                        </div>
                    </form>

                    <!-- 2. Member Invitations -->
                    <div class="mt-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-400 flex items-center gap-1.5">
                                <Users class="size-4 text-amber-500" />
                                Partager avec d'autres personnes
                            </h4>
                        </div>

                        <form @submit.prevent="submitInvite" class="space-y-2">
                            <label class="block text-xs text-neutral-600 dark:text-neutral-400">
                                Entrez l'email du compte pour lui envoyer une demande :
                            </label>
                            <div class="flex gap-2">
                                <input
                                    v-model="memberForm.email"
                                    type="email"
                                    placeholder="ex: chloe@example.com"
                                    required
                                    class="flex-1 rounded-xl border border-neutral-200 bg-neutral-50 px-3 py-2 text-xs text-neutral-900 dark:border-neutral-700 dark:bg-neutral-800 dark:text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                                />
                                <button
                                    type="submit"
                                    :disabled="memberForm.processing"
                                    class="inline-flex items-center gap-1 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-white shadow hover:bg-amber-600 transition"
                                >
                                    <UserPlus class="size-3.5" />
                                    Inviter
                                </button>
                            </div>
                            <p v-if="memberForm.errors.email" class="text-xs text-red-500">
                                {{ memberForm.errors.email }}
                            </p>
                        </form>

                        <!-- Members list & Pending status -->
                        <div class="space-y-2 pt-2">
                            <p class="text-[11px] font-semibold text-neutral-400 uppercase">Membres & Demandes</p>

                            <!-- Creator -->
                            <div class="flex items-center justify-between rounded-xl bg-neutral-50 p-2.5 dark:bg-neutral-800/60">
                                <div>
                                    <p class="text-xs font-bold text-neutral-900 dark:text-white">
                                        {{ collection.owner.name }}
                                    </p>
                                    <p class="text-[10px] text-neutral-500">
                                        {{ collection.owner.email }} • <span class="font-bold text-amber-600">Créateur</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Invited Members -->
                            <div
                                v-for="member in collection.members"
                                :key="member.id"
                                class="flex items-center justify-between rounded-xl bg-neutral-50 p-2.5 dark:bg-neutral-800/60"
                            >
                                <div>
                                    <p class="text-xs font-bold text-neutral-900 dark:text-white">
                                        {{ member.name }}
                                    </p>
                                    <p class="text-[10px] text-neutral-500">
                                        {{ member.email }} •
                                        <span v-if="member.status === 'pending'" class="text-amber-500 font-semibold">En attente d'acceptation</span>
                                        <span v-else-if="member.status === 'accepted'" class="text-emerald-500 font-semibold">Membre actif</span>
                                    </p>
                                </div>
                                <button
                                    v-if="member.user_id !== collection.owner.id"
                                    @click="removeMember(member.id)"
                                    class="text-xs text-red-500 hover:underline"
                                >
                                    Retirer
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Delete Collection Button -->
                    <div v-if="collection.is_owner" class="mt-8 pt-6 border-t border-neutral-100 dark:border-neutral-800">
                        <button
                            type="button"
                            @click="deleteCollection"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-red-500/10 py-2.5 text-xs font-bold text-red-600 hover:bg-red-500/20 transition"
                        >
                            <Trash2 class="size-4" />
                            Supprimer définitivement la collection
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    BookOpen,
    CheckCircle2,
    Clock,
    FolderPlus,
    Grid,
    Heart,
    Layers,
    LayoutGrid,
    Library,
    List,
    Mail,
    Plus,
    Share2,
    Sparkles,
    Trash2,
    UserCheck,
    UserX,
    Users,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

interface CollectionSummary {
    id: number;
    name: string;
    type: 'personal' | 'shared';
    items_count: number;
    members_count: number;
    owner?: {
        id: number;
        name: string;
    };
}

interface PendingInvitation {
    id: number;
    role: string;
    collection: {
        id: number;
        name: string;
        owner_name: string;
    };
}

interface UserSeriesItem {
    id: number;
    external_id: string;
    title: string;
    author: string | null;
    cover_url: string | null;
    status: string | null;
    owned_count: number;
    total_volumes: number;
    percentage: number;
    collection_id: number;
}

interface StatsSummary {
    total_collections: number;
    total_items: number;
    total_owned: number;
    total_read: number;
    total_wishlist: number;
}

const props = defineProps<{
    ownedCollections: CollectionSummary[];
    sharedCollections: CollectionSummary[];
    pendingInvitations: PendingInvitation[];
    userSeries?: UserSeriesItem[];
    stats: StatsSummary;
}>();

const showCreateModal = ref(false);
const viewMode = ref<'series' | 'collections'>('series');

const createForm = useForm({
    name: '',
});

const submitCreate = () => {
    createForm.post('/collections', {
        onSuccess: () => {
            createForm.reset();
            showCreateModal.value = false;
        },
    });
};

const acceptInvitation = (invitationId: number) => {
    useForm({}).post(`/invitations/${invitationId}/accept`);
};

const declineInvitation = (invitationId: number) => {
    useForm({}).post(`/invitations/${invitationId}/decline`);
};

const seriesList = computed(() => props.userSeries || []);
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Ma Mangathèque', href: '/dashboard' }]">
        <Head title="Ma Mangathèque — MangaShelf" />

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 space-y-8">
            <!-- Pending Invitations Banner -->
            <div
                v-if="pendingInvitations && pendingInvitations.length > 0"
                class="rounded-2xl border border-amber-500/40 bg-amber-500/10 p-4 sm:p-5 backdrop-blur-sm"
            >
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex size-8 items-center justify-center rounded-xl bg-amber-500 text-neutral-950">
                        <Mail class="size-4" />
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-amber-400">
                            Invitations de partage en attente
                        </h3>
                        <p class="text-xs text-neutral-400">
                            D'autres collectionneurs souhaitent partager une bibliothèque avec vous.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                    <div
                        v-for="inv in pendingInvitations"
                        :key="inv.id"
                        class="flex items-center justify-between rounded-xl border border-neutral-800 bg-neutral-900/90 p-3 shadow-sm"
                    >
                        <div>
                            <p class="text-xs font-bold text-white">{{ inv.collection.name }}</p>
                            <p class="text-[11px] text-neutral-400">Invité par {{ inv.collection.owner_name }} ({{ inv.role }})</p>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button
                                @click="acceptInvitation(inv.id)"
                                class="inline-flex items-center gap-1 rounded-lg bg-emerald-500 px-2.5 py-1 text-xs font-semibold text-neutral-950 hover:bg-emerald-400 transition"
                            >
                                <UserCheck class="size-3.5" />
                                Accepter
                            </button>
                            <button
                                @click="declineInvitation(inv.id)"
                                class="inline-flex items-center gap-1 rounded-lg bg-neutral-800 px-2.5 py-1 text-xs font-semibold text-neutral-400 hover:bg-neutral-700 transition"
                            >
                                <UserX class="size-3.5" />
                                Refuser
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Welcome & Actions Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-3xl border border-neutral-800/80 bg-gradient-to-r from-amber-500/10 via-neutral-900/90 to-orange-500/10 p-6 sm:p-8 shadow-sm">
                <div class="space-y-1">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/20 px-3 py-1 text-xs font-bold text-amber-400">
                        <Sparkles class="size-3.5" />
                        Espace Membre
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                        Ma Mangathèque
                    </h1>
                    <p class="text-sm text-neutral-400">
                        Gérez vos tomes possédés, suivez la complétion de vos séries et partagez vos bibliothèques.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="showCreateModal = true"
                        class="inline-flex items-center gap-2 rounded-xl border border-neutral-700 bg-neutral-800/80 px-4 py-2.5 text-xs font-semibold text-neutral-200 shadow-sm transition hover:bg-neutral-700"
                    >
                        <FolderPlus class="size-4" />
                        Nouvelle Collection
                    </button>
                    <Link
                        href="/search"
                        class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold text-neutral-950 shadow-md shadow-amber-500/20 transition hover:bg-amber-400"
                    >
                        <Plus class="size-4" />
                        Ajouter un Manga
                    </Link>
                </div>
            </div>

            <!-- Compact Stats Strip (MangaCollec style) -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-2xl border border-neutral-800/80 bg-neutral-900/40 p-4 flex items-center gap-3.5">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <CheckCircle2 class="size-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-medium text-neutral-400">Tomes Possédés</p>
                        <p class="text-xl font-black text-white">{{ stats.total_owned }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-neutral-800/80 bg-neutral-900/40 p-4 flex items-center gap-3.5">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                        <BookOpen class="size-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-medium text-neutral-400">Tomes Lus</p>
                        <p class="text-xl font-black text-white">{{ stats.total_read }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-neutral-800/80 bg-neutral-900/40 p-4 flex items-center gap-3.5">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                        <Heart class="size-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-medium text-neutral-400">Liste d'Envies</p>
                        <p class="text-xl font-black text-white">{{ stats.total_wishlist }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-neutral-800/80 bg-neutral-900/40 p-4 flex items-center gap-3.5">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/20">
                        <Layers class="size-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-medium text-neutral-400">Total Enregistrés</p>
                        <p class="text-xl font-black text-white">{{ stats.total_items }}</p>
                    </div>
                </div>
            </div>

            <!-- View Mode Switch: Mes Séries (MangaCollec style) vs Mes Collections -->
            <div class="flex items-center justify-between border-b border-neutral-800 pb-4">
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                        :class="
                            viewMode === 'series'
                                ? 'bg-amber-500 text-neutral-950 font-bold'
                                : 'text-neutral-400 hover:text-white'
                        "
                        @click="viewMode = 'series'"
                    >
                        <LayoutGrid class="size-3.5" />
                        Mes Séries ({{ seriesList.length }})
                    </button>

                    <button
                        type="button"
                        class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                        :class="
                            viewMode === 'collections'
                                ? 'bg-amber-500 text-neutral-950 font-bold'
                                : 'text-neutral-400 hover:text-white'
                        "
                        @click="viewMode = 'collections'"
                    >
                        <Library class="size-3.5" />
                        Mes Dossiers & Partages ({{ ownedCollections.length + sharedCollections.length }})
                    </button>
                </div>
            </div>

            <!-- Series Grid Mode (MangaCollec style) -->
            <div v-if="viewMode === 'series'">
                <div
                    v-if="seriesList.length === 0"
                    class="flex flex-col items-center justify-center rounded-2xl border border-neutral-800/80 bg-neutral-900/30 p-12 text-center"
                >
                    <div class="flex size-14 items-center justify-center rounded-2xl bg-neutral-800/60 text-amber-500">
                        <BookOpen class="size-7" />
                    </div>
                    <h3 class="mt-4 text-base font-bold text-white">Votre mangathèque est vide</h3>
                    <p class="mt-1 text-xs text-neutral-400 max-w-sm">
                        Recherchez des mangas dans le catalogue et ajoutez-les pour suivre votre collection tome par tome.
                    </p>
                    <Link
                        href="/search"
                        class="mt-5 inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-neutral-950 hover:bg-amber-400 transition"
                    >
                        <Plus class="size-3.5" />
                        Explorer le catalogue
                    </Link>
                </div>

                <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                    <div
                        v-for="series in seriesList"
                        :key="series.id"
                        class="group flex flex-col overflow-hidden rounded-xl border border-neutral-800/80 bg-neutral-900/40 transition-all duration-300 hover:-translate-y-1 hover:border-amber-500/40 hover:shadow-xl hover:shadow-amber-500/5"
                    >
                        <!-- Cover with progression overlay -->
                        <Link :href="`/manga/${series.external_id}`" class="relative aspect-[2/3] w-full overflow-hidden bg-neutral-800">
                            <img
                                v-if="series.cover_url"
                                :src="series.cover_url"
                                :alt="series.title"
                                loading="lazy"
                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                            />
                            <div v-else class="flex h-full w-full items-center justify-center text-xs text-neutral-500">
                                Pas d'image
                            </div>

                            <!-- Progress Tag -->
                            <div class="absolute bottom-2 left-2 right-2 rounded-md bg-neutral-950/85 px-2 py-1 backdrop-blur-sm border border-neutral-800">
                                <div class="flex items-center justify-between text-[11px] font-bold">
                                    <span class="text-amber-400">{{ series.owned_count }} / {{ series.total_volumes || '?' }}</span>
                                    <span class="text-neutral-400">{{ series.percentage }}%</span>
                                </div>
                                <div class="mt-1 h-1 w-full overflow-hidden rounded-full bg-neutral-800">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :class="series.percentage === 100 ? 'bg-emerald-500' : 'bg-amber-500'"
                                        :style="{ width: `${series.percentage}%` }"
                                    />
                                </div>
                            </div>
                        </Link>

                        <!-- Series Title & Action -->
                        <div class="flex flex-1 flex-col p-3">
                            <Link
                                :href="`/manga/${series.external_id}`"
                                class="line-clamp-1 text-xs font-semibold text-neutral-200 hover:text-amber-400 transition"
                            >
                                {{ series.title }}
                            </Link>
                            <p v-if="series.author" class="mt-0.5 text-[11px] text-neutral-400 truncate">
                                {{ series.author }}
                            </p>

                            <div class="mt-auto pt-2.5">
                                <Link
                                    :href="`/collections/${series.collection_id}`"
                                    class="flex w-full items-center justify-center rounded-lg bg-neutral-800/80 py-1 text-[11px] font-medium text-neutral-300 hover:bg-amber-500 hover:text-neutral-950 transition"
                                >
                                    Gérer les tomes
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Collections Folders Mode -->
            <div v-else class="space-y-6">
                <!-- My Collections -->
                <div>
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-base font-bold tracking-tight text-white flex items-center gap-2">
                            <Library class="size-4 text-amber-500" />
                            Mes Bibliothèques
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="col in ownedCollections"
                            :key="col.id"
                            :href="`/collections/${col.id}`"
                            class="group flex flex-col justify-between rounded-2xl border border-neutral-800/80 bg-neutral-900/40 p-5 shadow-sm transition hover:-translate-y-1 hover:border-amber-500/40 hover:shadow-lg"
                        >
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span v-if="col.members_count > 0" class="rounded-full bg-amber-500/10 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-400 border border-amber-500/20">
                                        {{ col.members_count + 1 }} personnes
                                    </span>
                                    <span v-else class="rounded-full bg-neutral-800 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-neutral-400">
                                        Solo
                                    </span>
                                </div>
                                <h3 class="text-base font-bold text-white group-hover:text-amber-400 transition">
                                    {{ col.name }}
                                </h3>
                                <p class="text-xs text-neutral-400">
                                    {{ col.items_count }} tomes enregistrés
                                </p>
                            </div>
                            <div class="mt-4 flex items-center justify-between pt-4 border-t border-neutral-800 text-xs text-amber-400 font-semibold">
                                <span>Ouvrir & Gérer</span>
                                <span>→</span>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Shared Collections -->
                <div v-if="sharedCollections && sharedCollections.length > 0">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-base font-bold tracking-tight text-white flex items-center gap-2">
                            <Share2 class="size-4 text-amber-500" />
                            Collections Rejointes
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="col in sharedCollections"
                            :key="col.id"
                            :href="`/collections/${col.id}`"
                            class="group flex flex-col justify-between rounded-2xl border border-neutral-800/80 bg-neutral-900/40 p-5 shadow-sm transition hover:-translate-y-1 hover:border-amber-500/40 hover:shadow-lg"
                        >
                            <div class="space-y-2">
                                <span class="rounded-full bg-amber-500/10 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-400 border border-amber-500/20">
                                    Partagée par {{ col.owner?.name }}
                                </span>
                                <h3 class="text-base font-bold text-white group-hover:text-amber-400 transition">
                                    {{ col.name }}
                                </h3>
                                <p class="text-xs text-neutral-400">
                                    {{ col.items_count }} tomes enregistrés
                                </p>
                            </div>
                            <div class="mt-4 flex items-center justify-between pt-4 border-t border-neutral-800 text-xs text-amber-400 font-semibold">
                                <span>Accéder à la collection</span>
                                <span>→</span>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Create Modal -->
            <div
                v-if="showCreateModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
            >
                <div class="w-full max-w-md rounded-2xl border border-neutral-800 bg-neutral-900 p-6 shadow-2xl">
                    <h3 class="text-lg font-bold text-white">
                        Créer une nouvelle collection
                    </h3>
                    <p class="text-xs text-neutral-400 mt-1 mb-4">
                        Vous pourrez ajouter des mangas et inviter d'autres personnes à tout moment.
                    </p>

                    <form @submit.prevent="submitCreate" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-neutral-300 mb-1">
                                Nom de la collection :
                            </label>
                            <input
                                v-model="createForm.name"
                                type="text"
                                placeholder="ex: Mangas Salon, Shōnen, Collection commune..."
                                required
                                class="w-full rounded-xl border border-neutral-700 bg-neutral-800 px-3.5 py-2.5 text-sm text-white focus:ring-2 focus:ring-amber-500 focus:outline-none"
                            />
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button
                                type="button"
                                @click="showCreateModal = false"
                                class="rounded-xl px-4 py-2 text-xs font-semibold text-neutral-400 hover:bg-neutral-800"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                :disabled="createForm.processing"
                                class="rounded-xl bg-amber-500 px-5 py-2 text-xs font-bold text-neutral-950 shadow-md hover:bg-amber-400 transition"
                            >
                                {{ createForm.processing ? 'Création...' : 'Créer' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

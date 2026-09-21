<script setup lang="ts">
import { Head, Link, Deferred, useForm } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {
    ArrowLeft,
    BookOpen,
    Calendar,
    CheckCircle2,
    Clock,
    Heart,
    Layers,
    Library,
    Plus,
    Sparkles,
    Star,
    Tag,
} from '@lucide/vue';
import { ref, computed } from 'vue';
import BottomNav from '@/components/BottomNav.vue';

interface VolumeItem {
    id?: number;
    number: string;
    title: string;
    publisher: string | null;
    publication_date: string | null;
    isbn13: string | null;
    isbn10: string | null;
    cover_url: string | null;
    status: 'released' | 'upcoming';
    collection_status?: string | null;
}

interface MangaDetails {
    external_id: string;
    title: string;
    original_title: string | null;
    synopsis: string | null;
    author: string | null;
    genres?: string[];
    status: string | null;
    total_volumes: number | null;
    score?: number | null;
    cover_url: string | null;
}

interface UserCollectionOption {
    id: number;
    name: string;
    type: string;
}

const props = defineProps<{
    manga: MangaDetails;
    volumes?: VolumeItem[];
    userCollections?: UserCollectionOption[];
    defaultCollectionId?: number | null;
}>();

const activeFilter = ref<'all' | 'released' | 'upcoming'>('all');
const selectedCollectionId = ref<number | null>(props.defaultCollectionId ?? (props.userCollections?.[0]?.id ?? null));

const safeVolumes = computed(() => props.volumes ?? []);

const statusLabels: Record<string, string> = {
    publishing: 'En cours',
    releasing: 'En cours',
    finished: 'Terminé',
    completed: 'Terminé',
    upcoming: 'À paraître',
    not_yet_released: 'À paraître',
    on_hiatus: 'En pause',
    hiatus: 'En pause',
    discontinued: 'Abandonné',
    cancelled: 'Abandonné',
};

const formatStatus = (status: string | null | undefined): string => {
    if (!status) return 'En cours';
    const key = status.toLowerCase().trim();
    return statusLabels[key] || status;
};

const genreTranslations: Record<string, string> = {
    Action: 'Action',
    Adventure: 'Aventure',
    Comedy: 'Comédie',
    Drama: 'Drame',
    Fantasy: 'Fantastique',
    Horror: 'Horreur',
    Mystery: 'Mystère',
    Psychological: 'Psychologique',
    Romance: 'Romance',
    'Sci-Fi': 'Science-Fiction',
    'Slice of Life': 'Tranche de vie',
    Sports: 'Sport',
    Supernatural: 'Surnaturel',
    Thriller: 'Thriller',
    Historical: 'Historique',
    MartialArts: 'Arts Martiaux',
    'Martial Arts': 'Arts Martiaux',
    School: 'Vie Scolaire',
    Shounen: 'Shōnen',
    Seinen: 'Seinen',
    Shoujo: 'Shōjo',
    Josei: 'Josei',
};

const formatGenre = (genre: string): string => {
    return genreTranslations[genre] || genre;
};

const filteredVolumes = computed(() => {
    if (activeFilter.value === 'released') {
        return safeVolumes.value.filter(v => v.status === 'released');
    }
    if (activeFilter.value === 'upcoming') {
        return safeVolumes.value.filter(v => v.status === 'upcoming');
    }
    return safeVolumes.value;
});

const releasedCount = computed(() => safeVolumes.value.filter(v => v.status === 'released').length);
const upcomingCount = computed(() => safeVolumes.value.filter(v => v.status === 'upcoming').length);
const ownedCount = computed(() => safeVolumes.value.filter(v => v.collection_status === 'owned').length);
const totalAvailableCount = computed(() => safeVolumes.value.length || props.manga.total_volumes || 0);
const progressPercentage = computed(() => {
    if (totalAvailableCount.value === 0) return 0;
    return Math.min(100, Math.round((ownedCount.value / totalAvailableCount.value) * 100));
});

const toggleVolumeStatus = (vol: VolumeItem, targetStatus: string) => {
    if (!vol.id || !selectedCollectionId.value) return;

    useForm({
        collection_id: selectedCollectionId.value,
        volume_id: vol.id,
        status: targetStatus,
    }).post('/collection-items/toggle', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`${manga.title} - Détails & Tomes`">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] flex flex-col selection:bg-amber-500 selection:text-white">
        <!-- Header -->
        <header class="sticky top-0 z-50 w-full border-b border-[#19140015] bg-[#FDFDFC]/80 backdrop-blur-md dark:border-[#3E3E3A]/40 dark:bg-[#0a0a0a]/80">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link href="/" class="flex items-center gap-3 transition-opacity hover:opacity-90">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 shadow-md shadow-orange-500/20 text-white">
                        <AppLogoIcon class="size-6 fill-current" />
                    </div>
                    <div>
                        <span class="text-lg font-bold tracking-tight bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500 bg-clip-text text-transparent">
                            MangaShelf
                        </span>
                        <span class="block text-[10px] uppercase font-semibold tracking-widest text-neutral-400 dark:text-neutral-500 -mt-1">
                            Fiche Série & Tomes
                        </span>
                    </div>
                </Link>

                <nav class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-flex items-center gap-2 rounded-lg bg-neutral-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-all hover:bg-neutral-800 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-100"
                    >
                        <Library class="size-4" />
                        Mon Espace
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium text-neutral-700 transition hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800/60"
                        >
                            Connexion
                        </Link>
                        <Link
                            :href="register()"
                            class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-500 to-orange-500 px-4 py-2 text-sm font-medium text-white shadow-md shadow-orange-500/20 transition-all hover:from-amber-600 hover:to-orange-600"
                        >
                            <Sparkles class="size-4" />
                            S'inscrire
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Back button -->
            <div class="mb-6">
                <Link
                    href="/"
                    class="inline-flex items-center gap-2 text-sm font-medium text-neutral-600 hover:text-amber-600 dark:text-neutral-400 dark:hover:text-amber-400 transition-colors"
                >
                    <ArrowLeft class="size-4" />
                    Retour au catalogue
                </Link>
            </div>

            <!-- Manga Overview Hero Card -->
            <section class="mb-12 overflow-hidden rounded-3xl border border-neutral-200/80 bg-white p-6 sm:p-8 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/60">
                <div class="flex flex-col md:flex-row gap-8 items-start">
                    <!-- Cover -->
                    <div class="w-full md:w-72 shrink-0 aspect-[3/4] overflow-hidden rounded-2xl bg-neutral-100 shadow-lg dark:bg-neutral-800 relative">
                        <img
                            v-if="manga.cover_url"
                            :src="manga.cover_url"
                            :alt="manga.title"
                            class="h-full w-full object-cover"
                        />
                        <div v-else class="flex h-full w-full items-center justify-center text-neutral-400">
                            <BookOpen class="size-16 stroke-[1.2]" />
                        </div>

                        <span
                            v-if="manga.score"
                            class="absolute top-3 left-3 flex items-center gap-1 rounded-full bg-neutral-900/85 px-3 py-1 text-xs font-bold text-amber-400 backdrop-blur-md shadow-sm"
                        >
                            <Star class="size-3.5 fill-amber-400 text-amber-400" />
                            {{ manga.score }} / 10
                        </span>
                    </div>

                    <!-- Details -->
                    <div class="flex-1 flex flex-col">
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                {{ formatStatus(manga.status) }}
                            </span>
                            <span v-if="manga.total_volumes" class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-semibold text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300">
                                {{ manga.total_volumes }} tomes
                            </span>
                        </div>

                        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl text-neutral-900 dark:text-white">
                            {{ manga.title }}
                        </h1>
                        <p v-if="manga.original_title" class="text-sm font-medium text-neutral-400 mt-1">
                            {{ manga.original_title }}
                        </p>

                        <div class="mt-4 flex flex-wrap items-center gap-y-2 gap-x-6 text-sm text-neutral-600 dark:text-neutral-300">
                            <div>
                                <span class="text-neutral-400 dark:text-neutral-500 font-normal">Auteur(s) :</span>
                                <span class="font-semibold ml-1.5">{{ manga.author || 'Inconnu' }}</span>
                            </div>
                            <div v-if="manga.genres && manga.genres.length > 0">
                                <span class="text-neutral-400 dark:text-neutral-500 font-normal">Genres :</span>
                                <span class="font-medium ml-1.5">{{ manga.genres.map(formatGenre).join(', ') }}</span>
                            </div>
                        </div>

                        <!-- MangaCollec Style Collection Progression Bar -->
                        <div v-if="$page.props.auth.user && safeVolumes.length > 0" class="mt-4 rounded-xl border border-neutral-200/80 bg-neutral-50 p-3.5 dark:border-neutral-800 dark:bg-neutral-800/40">
                            <div class="flex items-center justify-between text-xs font-bold mb-1.5">
                                <span class="text-neutral-700 dark:text-neutral-300">Votre progression :</span>
                                <span class="text-amber-500">{{ ownedCount }} / {{ totalAvailableCount }} tomes ({{ progressPercentage }}%)</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="progressPercentage === 100 ? 'bg-emerald-500' : 'bg-gradient-to-r from-amber-500 to-orange-500'"
                                    :style="{ width: `${progressPercentage}%` }"
                                />
                            </div>
                        </div>

                        <!-- Synopsis -->
                        <div class="mt-6 border-t border-neutral-100 pt-6 dark:border-neutral-800">
                            <h2 class="text-sm font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500 mb-2">
                                Synopsis
                            </h2>
                            <p class="text-sm sm:text-base leading-relaxed text-neutral-700 dark:text-neutral-300 whitespace-pre-line">
                                {{ manga.synopsis || 'Aucun résumé disponible.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Volumes Section -->
            <section>
                <Deferred data="volumes">
                    <!-- Loading skeleton -->
                    <template #fallback>
                        <div class="mb-6">
                            <h2 class="text-2xl font-bold tracking-tight text-neutral-900 dark:text-white flex items-center gap-2">
                                <Layers class="size-6 text-amber-500" />
                                Éditions Françaises
                            </h2>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">
                                Chargement des tomes en cours…
                            </p>
                        </div>
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                            <div v-for="i in 12" :key="i" class="animate-pulse rounded-2xl border border-neutral-200/80 bg-white dark:border-neutral-800 dark:bg-neutral-900/60 overflow-hidden">
                                <div class="aspect-[3/4] bg-neutral-200 dark:bg-neutral-800"></div>
                                <div class="p-3.5 space-y-2">
                                    <div class="h-3 bg-neutral-200 dark:bg-neutral-800 rounded w-3/4"></div>
                                    <div class="h-2 bg-neutral-100 dark:bg-neutral-800/60 rounded w-1/2"></div>
                                    <div class="h-2 bg-neutral-100 dark:bg-neutral-800/60 rounded w-2/3"></div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Loaded content -->
                    <div>
                        <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-bold tracking-tight text-neutral-900 dark:text-white flex items-center gap-2">
                                    <Layers class="size-6 text-amber-500" />
                                    Éditions & Tomes
                                </h2>
                                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ safeVolumes.length }} tomes répertoriés
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <!-- Collection selector if user is logged in -->
                                <div v-if="$page.props.auth.user && userCollections && userCollections.length > 0" class="flex items-center gap-1.5 text-xs bg-neutral-100 p-1 rounded-xl dark:bg-neutral-800">
                                    <span class="text-neutral-400 pl-2">Ajouter vers :</span>
                                    <select
                                        v-model="selectedCollectionId"
                                        class="bg-white dark:bg-neutral-700 text-xs font-semibold rounded-lg px-2.5 py-1 border-none focus:ring-1 focus:ring-amber-500 cursor-pointer text-neutral-800 dark:text-white"
                                    >
                                        <option v-for="col in userCollections" :key="col.id" :value="col.id">
                                            {{ col.name }} ({{ col.type === 'personal' ? 'Perso' : 'Partagée' }})
                                        </option>
                                    </select>
                                </div>

                                <!-- Filter Tabs -->
                                <div v-if="safeVolumes.length > 0" class="inline-flex rounded-xl bg-neutral-100 p-1 dark:bg-neutral-800/80">
                                    <button
                                        @click="activeFilter = 'all'"
                                        :class="[
                                            'rounded-lg px-3.5 py-1.5 text-xs font-semibold transition-all',
                                            activeFilter === 'all'
                                                ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-700 dark:text-white'
                                                : 'text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white'
                                        ]"
                                    >
                                        Tous ({{ safeVolumes.length }})
                                    </button>
                                    <button
                                        @click="activeFilter = 'released'"
                                        :class="[
                                            'rounded-lg px-3.5 py-1.5 text-xs font-semibold transition-all',
                                            activeFilter === 'released'
                                                ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-700 dark:text-white'
                                                : 'text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white'
                                        ]"
                                    >
                                        Parus ({{ releasedCount }})
                                    </button>
                                    <button
                                        v-if="upcomingCount > 0"
                                        @click="activeFilter = 'upcoming'"
                                        :class="[
                                            'rounded-lg px-3.5 py-1.5 text-xs font-semibold transition-all',
                                            activeFilter === 'upcoming'
                                                ? 'bg-white text-neutral-900 shadow-sm dark:bg-neutral-700 dark:text-white'
                                                : 'text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white'
                                        ]"
                                    >
                                        À paraître ({{ upcomingCount }})
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Empty state -->
                        <div
                            v-if="safeVolumes.length === 0"
                            class="rounded-2xl border border-dashed border-neutral-300 py-16 text-center dark:border-neutral-800"
                        >
                            <BookOpen class="size-12 text-neutral-400 mx-auto mb-3 stroke-[1.5]" />
                            <h3 class="text-base font-semibold text-neutral-800 dark:text-neutral-200">
                                Aucun tome disponible.
                            </h3>
                        </div>

                        <!-- Volumes Grid -->
                        <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                            <div
                                v-for="vol in filteredVolumes"
                                :key="vol.number"
                                class="group flex flex-col overflow-hidden rounded-2xl border border-neutral-200/80 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-amber-500/40 dark:border-neutral-800 dark:bg-neutral-900/60"
                            >
                                <!-- Cover -->
                                <div class="relative aspect-[3/4] w-full overflow-hidden bg-neutral-100 dark:bg-neutral-800">
                                    <img
                                        v-if="vol.cover_url"
                                        :src="vol.cover_url"
                                        :alt="vol.title"
                                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                        loading="lazy"
                                    />
                                    <div v-else class="flex h-full w-full flex-col items-center justify-center gap-2 text-neutral-400">
                                        <BookOpen class="size-8 stroke-[1.2]" />
                                        <span class="text-2xl font-black text-neutral-300 dark:text-neutral-600">{{ vol.number }}</span>
                                    </div>

                                    <!-- Volume number badge -->
                                    <span class="absolute top-2 left-2 rounded-lg bg-neutral-900/85 px-2 py-0.5 text-xs font-bold text-white shadow-sm backdrop-blur-md">
                                        T.{{ vol.number }}
                                    </span>

                                    <!-- Collection Status Overlay Badge if logged in and collected -->
                                    <span
                                        v-if="vol.collection_status === 'owned'"
                                        class="absolute top-2 right-2 rounded-lg bg-emerald-500 px-2 py-0.5 text-[10px] font-bold text-white shadow backdrop-blur-md"
                                        title="Dans votre collection"
                                    >
                                        ✓ Possédé
                                    </span>
                                    <span
                                        v-else-if="vol.collection_status === 'wishlist'"
                                        class="absolute top-2 right-2 rounded-lg bg-amber-500 px-2 py-0.5 text-[10px] font-bold text-white shadow backdrop-blur-md"
                                        title="Dans votre liste d'envie"
                                    >
                                        ⭐ Souhaité
                                    </span>
                                    <span
                                        v-else-if="vol.status === 'upcoming'"
                                        class="absolute top-2 right-2 rounded-md bg-neutral-900/80 p-1 text-white shadow-sm backdrop-blur-md"
                                        title="À paraître"
                                    >
                                        <Clock class="size-3.5" />
                                    </span>
                                </div>

                                <!-- Info -->
                                <div class="flex flex-1 flex-col p-3.5">
                                    <h3 class="font-bold text-xs leading-snug line-clamp-2 text-neutral-900 dark:text-white" :title="vol.title">
                                        {{ vol.title }}
                                    </h3>

                                    <div class="mt-2 space-y-1 text-[11px] text-neutral-500 dark:text-neutral-400">
                                        <p v-if="vol.publisher" class="text-neutral-400 truncate">
                                            {{ vol.publisher }}
                                        </p>
                                        <p v-if="vol.publication_date" class="flex items-center gap-1">
                                            <Calendar class="size-3 text-neutral-400 shrink-0" />
                                            <span>{{ vol.publication_date }}</span>
                                        </p>
                                        <p v-if="vol.isbn13 || vol.isbn10" class="flex items-center gap-1 truncate font-mono text-[10px] text-neutral-400">
                                            <Tag class="size-2.5 text-neutral-400 shrink-0" />
                                            <span>{{ vol.isbn13 || vol.isbn10 }}</span>
                                        </p>
                                    </div>

                                    <!-- Quick Add Action Buttons for Logged In Users -->
                                    <div v-if="$page.props.auth.user && vol.id" class="mt-3 pt-2.5 border-t border-neutral-100 dark:border-neutral-800/80 flex items-center justify-between gap-1">
                                        <button
                                            @click="toggleVolumeStatus(vol, 'owned')"
                                            :class="[
                                                'flex-1 py-1 rounded-lg text-[10px] font-bold transition flex items-center justify-center gap-1',
                                                vol.collection_status === 'owned'
                                                    ? 'bg-emerald-500 text-white shadow-sm'
                                                    : 'bg-neutral-100 text-neutral-700 hover:bg-emerald-50 hover:text-emerald-600 dark:bg-neutral-800 dark:text-neutral-300'
                                            ]"
                                            title="Marquer comme possédé"
                                        >
                                            <CheckCircle2 class="size-3" />
                                            <span>{{ vol.collection_status === 'owned' ? 'Possédé' : 'Posséder' }}</span>
                                        </button>

                                        <button
                                            @click="toggleVolumeStatus(vol, 'wishlist')"
                                            :class="[
                                                'py-1 px-2 rounded-lg text-[10px] font-bold transition flex items-center justify-center',
                                                vol.collection_status === 'wishlist'
                                                    ? 'bg-amber-500 text-white shadow-sm'
                                                    : 'bg-neutral-100 text-neutral-700 hover:bg-amber-50 hover:text-amber-600 dark:bg-neutral-800 dark:text-neutral-300'
                                            ]"
                                            title="Ajouter aux souhaits"
                                        >
                                            <Heart class="size-3" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Deferred>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t border-[#19140015] py-8 text-center text-xs text-neutral-500 dark:border-[#3E3E3A]/40 dark:text-neutral-500">
            <p>© {{ new Date().getFullYear() }} MangaShelf.</p>
        </footer>

        <!-- Mobile Bottom Nav -->
        <BottomNav />
    </div>
</template>

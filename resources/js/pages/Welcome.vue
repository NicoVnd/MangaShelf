<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Calendar,
    ChevronRight,
    Compass,
    Library,
    Search as SearchIcon,
    Sparkles,
    Star,
    TrendingUp,
    X,
} from '@lucide/vue';
import { ref } from 'vue';
import BottomNav from '@/components/BottomNav.vue';
import { Button } from '@/components/ui/button';
import { dashboard, login, register, planning, search } from '@/routes';

interface MangaApiItem {
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

interface VolumeReleaseItem {
    id: number;
    number: string;
    title: string;
    publication_date: string | null;
    cover_url: string | null;
    manga: {
        external_id: string | null;
        title: string | null;
    };
}

const props = defineProps<{
    mangas: MangaApiItem[];
    recentReleases?: VolumeReleaseItem[];
    upcomingReleases?: VolumeReleaseItem[];
    filters: {
        search?: string;
    };
    auth?: {
        user?: { id: number; name: string } | null;
    };
}>();

const searchQuery = ref(props.filters.search || '');
const isSearching = ref(false);

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

function formatStatus(status: string | null | undefined): string {
    if (!status) return 'En cours';
    const key = status.toLowerCase().trim();
    return statusLabels[key] || status;
}

function handleSearch() {
    if (searchQuery.value && searchQuery.value.trim().length > 0) {
        router.get('/search', { q: searchQuery.value });
    }
}
</script>

<template>
    <Head title="Nouveautés & Catalogue Manga — MangaShelf">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-neutral-950 text-neutral-100 flex flex-col selection:bg-amber-500 selection:text-neutral-950">
        <!-- Navigation Header -->
        <header class="sticky top-0 z-50 w-full border-b border-neutral-800/80 bg-neutral-950/80 backdrop-blur-md">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <!-- Logo & Brand -->
                <Link href="/" class="flex items-center gap-2.5 transition-opacity hover:opacity-90">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-gradient-to-tr from-amber-500 to-orange-400 font-black text-neutral-950 shadow-lg shadow-amber-500/20">
                        MS
                    </span>
                    <div>
                        <span class="text-lg font-bold tracking-tight bg-gradient-to-r from-amber-400 via-orange-400 to-amber-200 bg-clip-text text-transparent">
                            MangaShelf
                        </span>
                    </div>
                </Link>

                <!-- Search Input for desktop -->
                <form @submit.prevent="handleSearch" class="hidden md:flex items-center relative max-w-md w-full mx-8">
                    <SearchIcon class="absolute left-3.5 size-4 text-neutral-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Rechercher un manga (ex: Jujutsu Kaisen, One Piece)..."
                        class="w-full pl-10 pr-9 py-2 rounded-full border border-neutral-800 bg-neutral-900/80 text-sm text-white placeholder-neutral-500 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                    />
                </form>

                <!-- Auth Navigation -->
                <nav class="flex items-center gap-2">
                    <Link
                        v-if="$page.props.auth?.user"
                        :href="dashboard()"
                        class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-neutral-950 shadow-sm transition-all hover:bg-amber-400"
                    >
                        <Library class="size-4" />
                        Ma Mangathèque
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="inline-flex items-center rounded-lg px-3.5 py-1.5 text-sm font-medium text-neutral-300 hover:text-white"
                        >
                            Connexion
                        </Link>
                        <Link
                            :href="register()"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-3.5 py-1.5 text-sm font-semibold text-neutral-950 hover:bg-amber-400 transition-all shadow-md shadow-amber-500/20"
                        >
                            Inscription
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 pb-24 md:pb-12">
            <!-- Mobile Search Bar -->
            <form @submit.prevent="handleSearch" class="mb-6 flex md:hidden items-center relative w-full">
                <SearchIcon class="absolute left-3.5 size-4 text-neutral-400" />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Rechercher un manga..."
                    class="w-full pl-10 pr-9 py-2.5 rounded-xl border border-neutral-800 bg-neutral-900 text-sm text-white focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                />
            </form>

            <!-- Hero Section (MangaCollec style) -->
            <section class="mb-10 relative overflow-hidden rounded-3xl border border-neutral-800/80 bg-gradient-to-br from-neutral-900 via-neutral-950 to-neutral-900 p-8 sm:p-10 shadow-2xl">
                <div class="relative z-10 max-w-2xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-400 backdrop-blur-sm mb-4">
                        <Compass class="size-3.5" />
                        Nouveautés & Planning
                    </div>
                    <h1 class="text-3xl font-black tracking-tight sm:text-4xl lg:text-5xl text-white">
                        Gérez et suivez votre collection manga
                    </h1>
                    <p class="mt-3 text-sm sm:text-base text-neutral-300 leading-relaxed">
                        Ne manquez plus aucune sortie, suivez vos séries en cours et partagez votre mangathèque avec vos amis.
                    </p>
                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <Link
                            :href="planning()"
                            class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-bold text-neutral-950 hover:bg-amber-400 transition-all shadow-lg shadow-amber-500/20"
                        >
                            <Calendar class="size-4" />
                            Voir le planning des sorties
                        </Link>
                        <Link
                            :href="search()"
                            class="inline-flex items-center gap-2 rounded-xl border border-neutral-700 bg-neutral-800/60 px-5 py-2.5 text-sm font-semibold text-neutral-200 hover:bg-neutral-800 transition-all"
                        >
                            <SearchIcon class="size-4" />
                            Rechercher une série
                        </Link>
                    </div>
                </div>
                <div class="absolute -right-12 -top-12 size-72 rounded-full bg-amber-500/10 blur-3xl pointer-events-none" />
                <div class="absolute right-32 -bottom-20 size-80 rounded-full bg-orange-500/10 blur-3xl pointer-events-none" />
            </section>

            <!-- Recent / Upcoming Releases Strip (MangaCollec style) -->
            <section v-if="upcomingReleases && upcomingReleases.length > 0" class="mb-12">
                <div class="mb-5 flex items-center justify-between border-b border-neutral-800 pb-3">
                    <div class="flex items-center gap-2">
                        <Calendar class="size-5 text-amber-400" />
                        <h2 class="text-xl font-bold tracking-tight text-white">
                            Prochaines Sorties
                        </h2>
                    </div>
                    <Link
                        :href="planning()"
                        class="flex items-center gap-1 text-xs font-semibold text-amber-400 hover:text-amber-300 transition-colors"
                    >
                        Tout le planning
                        <ChevronRight class="size-3.5" />
                    </Link>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                    <div
                        v-for="release in upcomingReleases"
                        :key="release.id"
                        class="group relative flex flex-col overflow-hidden rounded-xl border border-neutral-800/80 bg-neutral-900/40 transition-all duration-300 hover:-translate-y-1 hover:border-amber-500/40"
                    >
                        <div class="relative aspect-[2/3] w-full overflow-hidden bg-neutral-800">
                            <img
                                v-if="release.cover_url"
                                :src="release.cover_url"
                                :alt="release.title"
                                loading="lazy"
                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                            />
                            <div v-else class="flex h-full w-full items-center justify-center text-xs text-neutral-500">
                                Tome {{ release.number }}
                            </div>
                            <span class="absolute bottom-2 left-2 rounded-md bg-neutral-950/80 px-2 py-0.5 text-xs font-bold text-amber-400 backdrop-blur-sm border border-neutral-800">
                                Tome {{ release.number }}
                            </span>
                        </div>
                        <div class="flex flex-1 flex-col p-2.5">
                            <Link
                                v-if="release.manga?.external_id"
                                :href="`/manga/${release.manga.external_id}`"
                                class="line-clamp-1 text-xs font-semibold text-neutral-200 hover:text-amber-400 transition-colors"
                            >
                                {{ release.manga?.title || release.title }}
                            </Link>
                            <span v-if="release.publication_date" class="mt-1 text-[11px] text-neutral-400">
                                Sortie le {{ release.publication_date }}
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Manga Series Catalog / Top Mangas -->
            <section>
                <div class="mb-6 flex items-center justify-between border-b border-neutral-800 pb-3">
                    <div class="flex items-center gap-2">
                        <TrendingUp class="size-5 text-amber-400" />
                        <h2 class="text-xl font-bold tracking-tight text-white">
                            Séries Populaires & Découvertes
                        </h2>
                    </div>
                    <Link
                        :href="search()"
                        class="flex items-center gap-1 text-xs font-semibold text-amber-400 hover:text-amber-300"
                    >
                        Explorer tout le catalogue
                        <ChevronRight class="size-3.5" />
                    </Link>
                </div>

                <!-- Manga Grid -->
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-6">
                    <Link
                        v-for="manga in mangas"
                        :key="manga.external_id"
                        :href="`/manga/${manga.external_id}`"
                        class="group flex flex-col overflow-hidden rounded-xl border border-neutral-800/80 bg-neutral-900/40 transition-all duration-300 hover:-translate-y-1 hover:border-amber-500/40 hover:shadow-xl hover:shadow-amber-500/5"
                    >
                        <div class="relative aspect-[2/3] w-full overflow-hidden bg-neutral-800">
                            <img
                                v-if="manga.cover_url"
                                :src="manga.cover_url"
                                :alt="manga.title"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                loading="lazy"
                            />
                            <div v-else class="flex h-full w-full items-center justify-center text-neutral-400 text-xs">
                                Pas d'image
                            </div>

                            <span
                                v-if="manga.score"
                                class="absolute top-2 left-2 flex items-center gap-1 rounded-md bg-neutral-950/80 px-1.5 py-0.5 text-xs font-bold text-amber-400 backdrop-blur-sm"
                            >
                                <Star class="size-3 fill-amber-400 text-amber-400" />
                                {{ manga.score }}
                            </span>

                            <span
                                v-if="manga.total_volumes"
                                class="absolute bottom-2 left-2 rounded-md bg-neutral-950/80 px-1.5 py-0.5 text-[10px] font-semibold text-neutral-300 backdrop-blur-sm"
                            >
                                {{ manga.total_volumes }} tomes
                            </span>
                        </div>

                        <div class="flex flex-1 flex-col p-3">
                            <h3 class="line-clamp-2 text-xs font-semibold text-neutral-200 group-hover:text-amber-400 transition-colors">
                                {{ manga.title }}
                            </h3>
                            <p v-if="manga.author" class="mt-1 text-[11px] text-neutral-400 truncate">
                                {{ manga.author }}
                            </p>
                        </div>
                    </Link>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t border-neutral-800/80 py-8 text-center text-xs text-neutral-500">
            <p>© {{ new Date().getFullYear() }} MangaShelf. Votre mangathèque et gestionnaire de collection inspiré par les passionnés.</p>
        </footer>

        <!-- Mobile Bottom Nav -->
        <BottomNav />
    </div>
</template>

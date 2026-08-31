<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { BookOpen, Sparkles, Library, Search, Star, X } from '@lucide/vue';
import { ref } from 'vue';

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

const props = defineProps<{
    mangas: MangaApiItem[];
    filters: {
        search?: string;
    };
}>();

const searchQuery = ref(props.filters.search || '');
const isSearching = ref(false);

function handleSearch() {
    isSearching.value = true;
    router.get(
        '/',
        { search: searchQuery.value || undefined },
        {
            preserveState: true,
            replace: true,
            onFinish: () => {
                isSearching.value = false;
            },
        }
    );
}

function clearSearch() {
    searchQuery.value = '';
    handleSearch();
}
</script>

<template>
    <Head title="Catalogue Manga - MangaShelf">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] flex flex-col selection:bg-amber-500 selection:text-white">
        <!-- Navigation Header -->
        <header class="sticky top-0 z-50 w-full border-b border-[#19140015] bg-[#FDFDFC]/80 backdrop-blur-md dark:border-[#3E3E3A]/40 dark:bg-[#0a0a0a]/80">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <!-- Logo & Brand -->
                <Link href="/" class="flex items-center gap-3 transition-opacity hover:opacity-90">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 shadow-md shadow-orange-500/20 text-white">
                        <AppLogoIcon class="size-6 fill-current" />
                    </div>
                    <div>
                        <span class="text-lg font-bold tracking-tight bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500 bg-clip-text text-transparent">
                            MangaShelf
                        </span>
                        <span class="block text-[10px] uppercase font-semibold tracking-widest text-neutral-400 dark:text-neutral-500 -mt-1">
                            Live Jikan Catalog
                        </span>
                    </div>
                </Link>

                <!-- Search bar in header for tablet & desktop -->
                <form @submit.prevent="handleSearch" class="hidden md:flex items-center relative max-w-md w-full mx-8">
                    <Search class="absolute left-3.5 size-4 text-neutral-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Rechercher un manga (ex: Naruto, Jujutsu Kaisen)..."
                        class="w-full pl-10 pr-9 py-2 rounded-full border border-neutral-200 bg-neutral-50 text-sm focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/20 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:bg-neutral-950"
                    />
                    <button
                        v-if="searchQuery"
                        type="button"
                        @click="clearSearch"
                        class="absolute right-3 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200"
                    >
                        <X class="size-4" />
                    </button>
                </form>

                <!-- Auth Navigation -->
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
            <!-- Mobile Search Bar -->
            <form @submit.prevent="handleSearch" class="mb-6 flex md:hidden items-center relative w-full">
                <Search class="absolute left-3.5 size-4 text-neutral-400" />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Rechercher un manga (ex: Bleach, One Piece)..."
                    class="w-full pl-10 pr-9 py-2.5 rounded-xl border border-neutral-200 bg-white text-sm shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-100"
                />
                <button
                    v-if="searchQuery"
                    type="button"
                    @click="clearSearch"
                    class="absolute right-3 text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200"
                >
                    <X class="size-4" />
                </button>
            </form>

            <!-- Hero Banner -->
            <section class="mb-10 relative overflow-hidden rounded-3xl bg-gradient-to-br from-neutral-900 via-neutral-950 to-neutral-900 p-8 text-white shadow-2xl dark:border dark:border-neutral-800">
                <div class="relative z-10 max-w-2xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-medium text-amber-400 backdrop-blur-sm mb-4">
                        <Sparkles class="size-3.5" />
                        Catalogue en direct (Jikan / MyAnimeList API)
                    </div>
                    <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">
                        Explorez des millions de mangas en direct
                    </h1>
                    <p class="mt-4 text-sm sm:text-base text-neutral-300 leading-relaxed">
                        Retrouvez les fiches officielles, synopses, couvertures HD et tomes parus. Recherchez n'importe quel manga pour l'ajouter à vos collections.
                    </p>
                </div>
                <!-- Background visual glows -->
                <div class="absolute -right-12 -top-12 size-72 rounded-full bg-amber-500/10 blur-3xl pointer-events-none" />
                <div class="absolute right-32 -bottom-20 size-80 rounded-full bg-rose-500/10 blur-3xl pointer-events-none" />
            </section>

            <!-- Manga Series Catalog -->
            <section>
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-neutral-900 dark:text-white flex items-center gap-2">
                            <BookOpen class="size-6 text-amber-500" />
                            {{ filters.search ? `Résultats de recherche pour "${filters.search}"` : 'Séries Populaires en direct' }}
                        </h2>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">
                            {{ mangas.length }} titre{{ mangas.length > 1 ? 's' : '' }} chargé{{ mangas.length > 1 ? 's' : '' }} via Jikan API
                        </p>
                    </div>
                    <button
                        v-if="filters.search"
                        @click="clearSearch"
                        class="text-xs font-semibold text-amber-600 hover:text-amber-700 dark:text-amber-400"
                    >
                        Réinitialiser la recherche
                    </button>
                </div>

                <!-- Empty state -->
                <div
                    v-if="mangas.length === 0"
                    class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-neutral-300 py-16 text-center dark:border-neutral-800"
                >
                    <BookOpen class="size-12 text-neutral-400 mb-3 stroke-[1.5]" />
                    <h3 class="text-lg font-medium text-neutral-900 dark:text-white">Aucun manga trouvé</h3>
                    <p class="text-sm text-neutral-500 mt-1">Essayez un autre mot-clé ou vérifiez l'orthographe.</p>
                </div>

                <!-- Manga Grid -->
                <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <article
                        v-for="manga in mangas"
                        :key="manga.external_id"
                        class="group flex flex-col overflow-hidden rounded-2xl border border-neutral-200/80 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-amber-500/40 dark:border-neutral-800 dark:bg-neutral-900/60"
                    >
                        <!-- Cover Image with badge -->
                        <div class="relative aspect-[3/4] w-full overflow-hidden bg-neutral-100 dark:bg-neutral-800">
                            <img
                                v-if="manga.cover_url"
                                :src="manga.cover_url"
                                :alt="manga.title"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                loading="lazy"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center text-neutral-400"
                            >
                                <BookOpen class="size-16 stroke-[1.2]" />
                            </div>

                            <!-- Score badge -->
                            <span
                                v-if="manga.score"
                                class="absolute top-3 left-3 flex items-center gap-1 rounded-full bg-neutral-900/80 px-2.5 py-1 text-xs font-semibold text-amber-400 backdrop-blur-md shadow-sm dark:bg-neutral-800/90"
                            >
                                <Star class="size-3 fill-amber-400 text-amber-400" />
                                {{ manga.score }}
                            </span>

                            <!-- Status Badge -->
                            <span
                                v-if="manga.status"
                                class="absolute top-3 right-3 rounded-full bg-neutral-900/80 px-2.5 py-1 text-xs font-semibold text-white capitalize backdrop-blur-md shadow-sm dark:bg-neutral-800/90"
                            >
                                {{ manga.status }}
                            </span>

                            <!-- Author & Title overlay -->
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 via-black/45 to-transparent p-4 text-white">
                                <p v-if="manga.original_title" class="text-xs text-neutral-300 font-medium line-clamp-1">
                                    {{ manga.original_title }}
                                </p>
                                <h3 class="text-lg font-bold leading-tight line-clamp-1 drop-shadow-sm">
                                    {{ manga.title }}
                                </h3>
                            </div>
                        </div>

                        <!-- Info Body -->
                        <div class="flex flex-1 flex-col p-5">
                            <div class="mb-3 flex items-center justify-between text-xs text-neutral-500 dark:text-neutral-400">
                                <span class="font-medium truncate max-w-[160px]">
                                    {{ manga.author || 'Auteur non spécifié' }}
                                </span>
                                <span v-if="manga.total_volumes" class="font-semibold text-amber-600 dark:text-amber-400">
                                    {{ manga.total_volumes }} tomes
                                </span>
                                <span v-else class="text-neutral-400">
                                    En cours
                                </span>
                            </div>

                            <p
                                v-if="manga.synopsis"
                                class="text-xs text-neutral-600 line-clamp-3 leading-relaxed dark:text-neutral-300 mb-4"
                            >
                                {{ manga.synopsis }}
                            </p>

                            <!-- External ID tag & MyAnimeList ref -->
                            <div class="mt-auto pt-3 border-t border-neutral-100 dark:border-neutral-800/80 flex items-center justify-between text-xs text-neutral-500 dark:text-neutral-400">
                                <span class="text-[11px] text-neutral-400">
                                    MAL ID #{{ manga.external_id }}
                                </span>
                                <a
                                    :href="`https://myanimelist.net/manga/${manga.external_id}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-[11px] font-medium text-amber-600 hover:underline dark:text-amber-400"
                                >
                                    Fiche MAL ↗
                                </a>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t border-[#19140015] py-8 text-center text-xs text-neutral-500 dark:border-[#3E3E3A]/40 dark:text-neutral-500">
            <p>© {{ new Date().getFullYear() }} MangaShelf. Données propulsées en direct par l'API Jikan (MyAnimeList).</p>
        </footer>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BookOpen,
    Compass,
    Eye,
    Plus,
    Search as SearchIcon,
    Sparkles,
    Star,
    TrendingUp,
    X,
} from '@lucide/vue';
import { ref } from 'vue';
import BottomNav from '@/components/BottomNav.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppHeader from '@/components/AppHeader.vue';

interface MangaSearchItem {
    id: number;
    external_id: string;
    title: string;
    original_title?: string | null;
    author?: string | null;
    cover_url?: string | null;
    status?: string | null;
    total_volumes?: number | null;
    score?: number | null;
    genres?: string[];
}

const props = defineProps<{
    query: string;
    results: MangaSearchItem[];
    popular: MangaSearchItem[];
    auth?: {
        user?: { id: number; name: string } | null;
    };
}>();

const searchInput = ref(props.query || '');
const isTyping = ref(false);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

function handleInput() {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        executeSearch();
    }, 350);
}

function executeSearch() {
    router.get(
        '/search',
        { q: searchInput.value || undefined },
        { preserveState: true, replace: true }
    );
}

function clearSearch() {
    searchInput.value = '';
    executeSearch();
}
</script>

<template>
    <Head title="Recherche Manga — MangaShelf" />

    <div class="min-h-screen bg-neutral-950 text-neutral-100 selection:bg-amber-500 selection:text-neutral-950">
        <!-- Header -->
        <AppHeader v-if="auth?.user" />
        <header v-else class="sticky top-0 z-40 border-b border-neutral-800/80 bg-neutral-950/80 backdrop-blur-md">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">
                <Link href="/" class="flex items-center gap-2">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-gradient-to-tr from-amber-500 to-orange-400 font-black text-neutral-950 shadow-lg shadow-amber-500/20">
                        MS
                    </span>
                    <span class="text-lg font-bold tracking-tight text-white">MangaShelf</span>
                </Link>

                <div class="flex items-center gap-2">
                    <Button variant="ghost" size="sm" as-child class="text-neutral-300 hover:text-white">
                        <Link href="/login">Connexion</Link>
                    </Button>
                    <Button size="sm" as-child class="bg-amber-500 font-semibold text-neutral-950 hover:bg-amber-400">
                        <Link href="/register">Inscription</Link>
                    </Button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 pb-24 md:pb-12">
            <!-- Search Banner -->
            <div class="mx-auto max-w-2xl text-center mb-10">
                <div class="inline-flex items-center gap-1.5 rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-400 mb-3">
                    <SearchIcon class="size-3.5" />
                    Catalogue Manga & Recherche
                </div>
                <h1 class="text-3xl font-black tracking-tight text-white sm:text-4xl">
                    Trouvez vos mangas
                </h1>
                <p class="mt-2 text-sm text-neutral-400">
                    Recherchez parmi des milliers de titres, suivez vos séries et complétez votre collection.
                </p>

                <!-- Search Input with clear button -->
                <div class="relative mt-6">
                    <SearchIcon class="absolute left-4 top-1/2 -translate-y-1/2 size-5 text-neutral-400" />
                    <input
                        v-model="searchInput"
                        type="text"
                        placeholder="Rechercher par titre, auteur, titre original..."
                        class="w-full rounded-2xl border border-neutral-800 bg-neutral-900/90 py-3.5 pl-12 pr-12 text-sm text-white placeholder-neutral-500 shadow-xl shadow-black/40 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20 transition-all"
                        @input="handleInput"
                        @keydown.enter="executeSearch"
                    />
                    <button
                        v-if="searchInput"
                        type="button"
                        class="absolute right-4 top-1/2 -translate-y-1/2 rounded-lg p-1 text-neutral-400 hover:text-white hover:bg-neutral-800"
                        @click="clearSearch"
                    >
                        <X class="size-4" />
                    </button>
                </div>
            </div>

            <!-- Search Results Mode -->
            <div v-if="query && query.length >= 2">
                <div class="mb-6 flex items-center justify-between border-b border-neutral-800 pb-3">
                    <h2 class="text-lg font-bold text-white">
                        Résultats pour <span class="text-amber-400 font-extrabold">"{{ query }}"</span>
                    </h2>
                    <span class="text-xs font-medium text-neutral-400">
                        {{ results.length }} résultat{{ results.length > 1 ? 's' : '' }}
                    </span>
                </div>

                <div
                    v-if="results.length === 0"
                    class="flex flex-col items-center justify-center rounded-2xl border border-neutral-800/80 bg-neutral-900/30 p-12 text-center"
                >
                    <div class="flex size-14 items-center justify-center rounded-2xl bg-neutral-800/60 text-amber-500">
                        <SearchIcon class="size-7" />
                    </div>
                    <h3 class="mt-4 text-base font-bold text-white">Aucun résultat trouvé</h3>
                    <p class="mt-1 text-xs text-neutral-400 max-w-sm">
                        Vérifiez l'orthographe ou essayez avec le titre japonais/anglais.
                    </p>
                </div>

                <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-6">
                    <Link
                        v-for="item in results"
                        :key="item.id"
                        :href="`/manga/${item.external_id}`"
                        class="group flex flex-col overflow-hidden rounded-xl border border-neutral-800/80 bg-neutral-900/40 transition-all duration-300 hover:-translate-y-1 hover:border-amber-500/40 hover:shadow-xl hover:shadow-amber-500/5"
                    >
                        <div class="relative aspect-[2/3] w-full overflow-hidden bg-neutral-800">
                            <img
                                v-if="item.cover_url"
                                :src="item.cover_url"
                                :alt="item.title"
                                loading="lazy"
                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                            />
                            <div v-else class="flex h-full w-full items-center justify-center bg-neutral-800 text-neutral-500 text-xs">
                                Pas d'image
                            </div>

                            <div
                                v-if="item.score"
                                class="absolute top-2 right-2 flex items-center gap-1 rounded-md bg-neutral-950/80 px-1.5 py-0.5 text-xs font-bold text-amber-400 backdrop-blur-sm"
                            >
                                <Star class="size-3 fill-amber-400 text-amber-400" />
                                {{ item.score }}
                            </div>

                            <div
                                v-if="item.total_volumes"
                                class="absolute bottom-2 left-2 rounded-md bg-neutral-950/80 px-1.5 py-0.5 text-[10px] font-semibold text-neutral-300 backdrop-blur-sm"
                            >
                                {{ item.total_volumes }} vol.
                            </div>
                        </div>

                        <div class="flex flex-1 flex-col p-3">
                            <h3 class="line-clamp-2 text-xs font-semibold text-neutral-200 group-hover:text-amber-400 transition-colors">
                                {{ item.title }}
                            </h3>
                            <p v-if="item.author" class="mt-1 text-[11px] text-neutral-400 truncate">
                                {{ item.author }}
                            </p>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Popular / Suggestions Mode (Empty Query) -->
            <div v-else>
                <div class="mb-6 flex items-center gap-2 border-b border-neutral-800 pb-3">
                    <TrendingUp class="size-4 text-amber-400" />
                    <h2 class="text-base font-bold text-white">
                        Séries populaires & recommandées
                    </h2>
                </div>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-6">
                    <Link
                        v-for="item in popular"
                        :key="item.id"
                        :href="`/manga/${item.external_id}`"
                        class="group flex flex-col overflow-hidden rounded-xl border border-neutral-800/80 bg-neutral-900/40 transition-all duration-300 hover:-translate-y-1 hover:border-amber-500/40 hover:shadow-xl hover:shadow-amber-500/5"
                    >
                        <div class="relative aspect-[2/3] w-full overflow-hidden bg-neutral-800">
                            <img
                                v-if="item.cover_url"
                                :src="item.cover_url"
                                :alt="item.title"
                                loading="lazy"
                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                            />
                            <div v-else class="flex h-full w-full items-center justify-center bg-neutral-800 text-neutral-500 text-xs">
                                Pas d'image
                            </div>

                            <div
                                v-if="item.score"
                                class="absolute top-2 right-2 flex items-center gap-1 rounded-md bg-neutral-950/80 px-1.5 py-0.5 text-xs font-bold text-amber-400 backdrop-blur-sm"
                            >
                                <Star class="size-3 fill-amber-400 text-amber-400" />
                                {{ item.score }}
                            </div>

                            <div
                                v-if="item.total_volumes"
                                class="absolute bottom-2 left-2 rounded-md bg-neutral-950/80 px-1.5 py-0.5 text-[10px] font-semibold text-neutral-300 backdrop-blur-sm"
                            >
                                {{ item.total_volumes }} vol.
                            </div>
                        </div>

                        <div class="flex flex-1 flex-col p-3">
                            <h3 class="line-clamp-2 text-xs font-semibold text-neutral-200 group-hover:text-amber-400 transition-colors">
                                {{ item.title }}
                            </h3>
                            <p v-if="item.author" class="mt-1 text-[11px] text-neutral-400 truncate">
                                {{ item.author }}
                            </p>
                        </div>
                    </Link>
                </div>
            </div>
        </main>

        <!-- Bottom Navigation for Mobile -->
        <BottomNav />
    </div>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Calendar,
    ChevronRight,
    Compass,
    Eye,
    Filter,
    Library,
    Lock,
    Sparkles,
} from '@lucide/vue';
import { computed } from 'vue';
import BottomNav from '@/components/BottomNav.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AppHeader from '@/components/AppHeader.vue';

interface MangaRef {
    id: number | null;
    external_id: string | null;
    title: string | null;
}

interface VolumePlanningItem {
    id: number;
    number: string;
    title: string;
    publication_date: string | null;
    cover_url: string | null;
    isbn13: string | null;
    manga: MangaRef;
    publisher: string | null;
}

interface WeeklyGroup {
    week_start: string;
    week_label: string;
    volumes: VolumePlanningItem[];
}

const props = defineProps<{
    weeklyGroups: WeeklyGroup[];
    filter: 'all' | 'mine';
    isAuthenticated: boolean;
}>();

const hasVolumes = computed(() => {
    return props.weeklyGroups && props.weeklyGroups.some((g) => g.volumes.length > 0);
});

const totalVolumes = computed(() => {
    if (!props.weeklyGroups) return 0;
    return props.weeklyGroups.reduce((acc, g) => acc + g.volumes.length, 0);
});

function setFilter(newFilter: 'all' | 'mine') {
    router.get(
        '/planning',
        { filter: newFilter },
        { preserveState: true, replace: true }
    );
}
</script>

<template>
    <Head title="Planning des sorties Manga — MangaShelf" />

    <div class="min-h-screen bg-neutral-950 text-neutral-100 selection:bg-amber-500 selection:text-neutral-950">
        <!-- Global Header if authenticated, or public topbar -->
        <AppHeader v-if="isAuthenticated" />
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
            <!-- Header Section with title & filters -->
            <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-amber-500">
                        <Calendar class="size-5" />
                        <span class="text-xs font-bold uppercase tracking-wider">Planning des Sorties</span>
                    </div>
                    <h1 class="mt-1 text-2xl font-black tracking-tight text-white sm:text-3xl">
                        Prochaines Parutions
                    </h1>
                    <p class="mt-1 text-sm text-neutral-400">
                        {{ totalVolumes }} sortie{{ totalVolumes > 1 ? 's' : '' }} programmée{{ totalVolumes > 1 ? 's' : '' }} sur les 3 prochains mois
                    </p>
                </div>

                <!-- Filters: Toutes vs Mes séries -->
                <div class="flex items-center gap-2 rounded-xl border border-neutral-800 bg-neutral-900/60 p-1 backdrop-blur-sm self-start sm:self-auto">
                    <button
                        type="button"
                        class="flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs font-semibold transition-all"
                        :class="
                            filter === 'all'
                                ? 'bg-amber-500 text-neutral-950 shadow-md'
                                : 'text-neutral-400 hover:text-white'
                        "
                        @click="setFilter('all')"
                    >
                        <Compass class="size-3.5" />
                        Toutes les sorties
                    </button>

                    <button
                        v-if="isAuthenticated"
                        type="button"
                        class="flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs font-semibold transition-all"
                        :class="
                            filter === 'mine'
                                ? 'bg-amber-500 text-neutral-950 shadow-md'
                                : 'text-neutral-400 hover:text-white'
                        "
                        @click="setFilter('mine')"
                    >
                        <Library class="size-3.5" />
                        Mes séries suivies
                    </button>
                    <Link
                        v-else
                        href="/login"
                        class="flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-xs font-semibold text-neutral-500 hover:text-neutral-300"
                        title="Connectez-vous pour voir vos séries"
                    >
                        <Lock class="size-3.5" />
                        Mes séries (connexion requise)
                    </Link>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="!hasVolumes"
                class="flex flex-col items-center justify-center rounded-2xl border border-neutral-800/80 bg-neutral-900/30 p-12 text-center"
            >
                <div class="flex size-14 items-center justify-center rounded-2xl bg-neutral-800/60 text-amber-500 ring-1 ring-neutral-700/50">
                    <Calendar class="size-7" />
                </div>
                <h3 class="mt-4 text-lg font-bold text-white">Aucune sortie répertoriée pour cette période</h3>
                <p class="mt-1.5 max-w-sm text-sm text-neutral-400">
                    {{
                        filter === 'mine'
                            ? 'Aucun tome à paraître pour les séries présentes dans votre mangathèque.'
                            : 'Les dates de sortie françaises pour les prochaines semaines seront complétées au fur et à mesure.'
                    }}
                </p>
                <Button
                    v-if="filter === 'mine'"
                    variant="outline"
                    size="sm"
                    class="mt-4 border-neutral-700 text-neutral-300 hover:bg-neutral-800"
                    @click="setFilter('all')"
                >
                    Voir toutes les sorties
                </Button>
            </div>

            <!-- Weekly Releases Groups -->
            <div v-else class="space-y-10">
                <section
                    v-for="group in weeklyGroups"
                    :key="group.week_start"
                    class="space-y-4"
                >
                    <!-- Week Header -->
                    <div class="flex items-center gap-3 border-b border-neutral-800/80 pb-3">
                        <div class="flex size-7 items-center justify-center rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 text-xs font-bold">
                            {{ group.volumes.length }}
                        </div>
                        <h2 class="text-base font-bold text-neutral-200 capitalize">
                            Semaine du {{ group.week_label }}
                        </h2>
                    </div>

                    <!-- Volumes Grid -->
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-7">
                        <div
                            v-for="volume in group.volumes"
                            :key="volume.id"
                            class="group relative flex flex-col overflow-hidden rounded-xl border border-neutral-800/80 bg-neutral-900/40 transition-all duration-300 hover:-translate-y-1 hover:border-amber-500/40 hover:shadow-xl hover:shadow-amber-500/5"
                        >
                            <!-- Cover thumbnail -->
                            <div class="relative aspect-[2/3] w-full overflow-hidden bg-neutral-800">
                                <img
                                    v-if="volume.cover_url"
                                    :src="volume.cover_url"
                                    :alt="volume.title"
                                    loading="lazy"
                                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center bg-neutral-800 text-neutral-500 text-xs font-medium"
                                >
                                    Pas d'image
                                </div>

                                <!-- Volume number badge -->
                                <div class="absolute bottom-2 left-2 rounded-md bg-neutral-950/80 px-2 py-0.5 text-xs font-bold text-amber-400 backdrop-blur-sm border border-neutral-800">
                                    Tome {{ volume.number }}
                                </div>

                                <!-- Release date chip -->
                                <div
                                    v-if="volume.publication_date"
                                    class="absolute top-2 right-2 rounded-md bg-neutral-950/80 px-1.5 py-0.5 text-[10px] font-semibold text-neutral-300 backdrop-blur-sm"
                                >
                                    {{ volume.publication_date }}
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="flex flex-1 flex-col p-2.5">
                                <Link
                                    v-if="volume.manga?.external_id"
                                    :href="`/manga/${volume.manga.external_id}`"
                                    class="line-clamp-1 text-xs font-semibold text-neutral-200 hover:text-amber-400 transition-colors"
                                    :title="volume.manga?.title || volume.title"
                                >
                                    {{ volume.manga?.title || volume.title }}
                                </Link>
                                <span
                                    v-else
                                    class="line-clamp-1 text-xs font-semibold text-neutral-200"
                                >
                                    {{ volume.title }}
                                </span>

                                <span
                                    v-if="volume.publisher"
                                    class="mt-0.5 text-[11px] text-neutral-400 truncate"
                                >
                                    {{ volume.publisher }}
                                </span>

                                <div class="mt-auto pt-2">
                                    <Link
                                        v-if="volume.manga?.external_id"
                                        :href="`/manga/${volume.manga.external_id}`"
                                        class="flex w-full items-center justify-center gap-1 rounded-md bg-neutral-800/80 py-1 text-[11px] font-medium text-neutral-300 transition-colors hover:bg-amber-500 hover:text-neutral-950"
                                    >
                                        Fiche
                                        <ChevronRight class="size-3" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>

        <!-- Bottom Navigation for Mobile -->
        <BottomNav />
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Calendar, Compass, Library, Search } from '@lucide/vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard, home, planning, search } from '@/routes';

const { isCurrentUrl } = useCurrentUrl();

const navItems = [
    {
        title: 'Nouveautés',
        href: home(),
        icon: Compass,
    },
    {
        title: 'Collection',
        href: dashboard(),
        icon: Library,
    },
    {
        title: 'Planning',
        href: planning(),
        icon: Calendar,
    },
    {
        title: 'Recherche',
        href: search(),
        icon: Search,
    },
];
</script>

<template>
    <nav
        class="fixed bottom-0 left-0 right-0 z-50 flex h-16 items-center justify-around border-t border-border bg-background/80 px-2 backdrop-blur-md md:hidden safe-area-inset-bottom"
    >
        <Link
            v-for="item in navItems"
            :key="item.title"
            :href="item.href"
            class="flex flex-1 flex-col items-center justify-center gap-1 py-1 text-xs font-medium transition-colors"
            :class="
                isCurrentUrl(item.href)
                    ? 'text-amber-500 font-semibold dark:text-amber-400'
                    : 'text-muted-foreground hover:text-foreground'
            "
        >
            <component :is="item.icon" class="size-5" />
            <span class="text-[11px] leading-none">{{ item.title }}</span>
        </Link>
    </nav>
</template>

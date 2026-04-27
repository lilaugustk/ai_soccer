import { ref, watch } from 'vue';

export function useFavorites() {
    const favorites = ref({
        teams: [],
        leagues: []
    });

    // Load from localStorage on init
    if (typeof window !== 'undefined') {
        const stored = localStorage.getItem('ai_soccer_favorites');
        if (stored) {
            favorites.value = JSON.parse(stored);
        }
    }

    // Save on change
    watch(favorites, (newVal) => {
        if (typeof window !== 'undefined') {
            localStorage.setItem('ai_soccer_favorites', JSON.stringify(newVal));
        }
    }, { deep: true });

    const toggleFavorite = (type, id) => {
        const list = favorites.value[type];
        const index = list.indexOf(id);
        if (index === -1) {
            list.push(id);
        } else {
            list.splice(index, 1);
        }
    };

    const isFavorite = (type, id) => {
        return favorites.value[type].includes(id);
    };

    return {
        favorites,
        toggleFavorite,
        isFavorite
    };
}

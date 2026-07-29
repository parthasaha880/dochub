import { ref } from 'vue';

const isDark = ref(false);

export function useTheme() {
    const apply = (dark) => {
        isDark.value = dark;
        document.documentElement.classList.toggle('dark', dark);
        localStorage.setItem('edams_theme', dark ? 'dark' : 'light');
    };

    const initTheme = () => {
        const saved = localStorage.getItem('edams_theme');
        if (saved) {
            apply(saved === 'dark');
            return;
        }
        apply(window.matchMedia('(prefers-color-scheme: dark)').matches);
    };

    const toggleTheme = () => apply(!isDark.value);

    return { isDark, initTheme, toggleTheme, apply };
}

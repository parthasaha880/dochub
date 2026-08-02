import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const SIDE_KEY = 'edams_sidebar_side';
const COLLAPSED_KEY = 'edams_sidebar_collapsed';

const side = ref('left');
const collapsed = ref(false);
const mobileOpen = ref(false);
const isDesktop = ref(true);

function readMedia() {
    if (typeof window === 'undefined') return;
    isDesktop.value = window.matchMedia('(min-width: 768px)').matches;
    if (isDesktop.value) {
        mobileOpen.value = false;
    }
}

export function useSidebarLayout() {
    const isRight = computed(() => side.value === 'right');

    const initLayout = () => {
        const savedSide = localStorage.getItem(SIDE_KEY);
        if (savedSide === 'left' || savedSide === 'right') {
            side.value = savedSide;
        }
        collapsed.value = localStorage.getItem(COLLAPSED_KEY) === '1';
        readMedia();
    };

    const setSide = (value) => {
        side.value = value === 'right' ? 'right' : 'left';
        localStorage.setItem(SIDE_KEY, side.value);
    };

    const toggleSide = () => setSide(side.value === 'left' ? 'right' : 'left');

    const toggleCollapsed = () => {
        collapsed.value = !collapsed.value;
        localStorage.setItem(COLLAPSED_KEY, collapsed.value ? '1' : '0');
    };

    const openMobile = () => {
        mobileOpen.value = true;
    };

    const closeMobile = () => {
        mobileOpen.value = false;
    };

    const toggleMobile = () => {
        mobileOpen.value = !mobileOpen.value;
    };

    watch(isDesktop, (desktop) => {
        if (desktop) mobileOpen.value = false;
    });

    let mq = null;
    const onMediaChange = () => readMedia();

    onMounted(() => {
        initLayout();
        mq = window.matchMedia('(min-width: 768px)');
        if (mq.addEventListener) mq.addEventListener('change', onMediaChange);
        else mq.addListener(onMediaChange);
    });

    onUnmounted(() => {
        if (!mq) return;
        if (mq.removeEventListener) mq.removeEventListener('change', onMediaChange);
        else mq.removeListener(onMediaChange);
    });

    return {
        side,
        isRight,
        collapsed,
        mobileOpen,
        isDesktop,
        initLayout,
        setSide,
        toggleSide,
        toggleCollapsed,
        openMobile,
        closeMobile,
        toggleMobile,
    };
}

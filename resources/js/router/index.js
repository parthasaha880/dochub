import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/modules/auth/stores/auth';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/login',
            name: 'login',
            component: () => import('@/modules/auth/pages/LoginPage.vue'),
            meta: { guest: true },
        },
        {
            path: '/forgot-password',
            name: 'forgot-password',
            component: () => import('@/modules/auth/pages/ForgotPasswordPage.vue'),
            meta: { guest: true },
        },
        {
            path: '/reset-password',
            name: 'reset-password',
            component: () => import('@/modules/auth/pages/ResetPasswordPage.vue'),
            meta: { guest: true },
        },
        {
            path: '/',
            component: () => import('@/layouts/AppLayout.vue'),
            meta: { auth: true },
            children: [
                {
                    path: '',
                    redirect: { name: 'dashboard' },
                },
                {
                    path: 'dashboard',
                    name: 'dashboard',
                    component: () => import('@/modules/dashboard/pages/DashboardPage.vue'),
                },
                {
                    path: 'profile',
                    name: 'profile',
                    component: () => import('@/modules/auth/pages/ProfilePage.vue'),
                },
                {
                    path: 'otp-book',
                    name: 'otp-book',
                    component: () => import('@/modules/auth/pages/OtpBookPage.vue'),
                    meta: { permission: 'otp.view' },
                },
                {
                    path: 'security/sessions',
                    name: 'sessions',
                    component: () => import('@/modules/auth/pages/SessionsPage.vue'),
                },
                {
                    path: 'security/login-activity',
                    name: 'login-activity',
                    component: () => import('@/modules/auth/pages/LoginActivityPage.vue'),
                },
                {
                    path: 'email/verify',
                    name: 'verify-email',
                    component: () => import('@/modules/auth/pages/VerifyEmailPage.vue'),
                },
                {
                    path: 'organization',
                    name: 'organization',
                    component: () => import('@/modules/organization/pages/OrganizationPage.vue'),
                },
                {
                    path: 'users',
                    name: 'users',
                    component: () => import('@/modules/users/pages/UsersRolesPage.vue'),
                },
                {
                    path: 'documents',
                    name: 'documents',
                    component: () => import('@/modules/documents/pages/DocumentsPage.vue'),
                },
                {
                    path: 'archive',
                    name: 'archive',
                    component: () => import('@/modules/archive/pages/ArchivePage.vue'),
                    meta: { permission: 'archive.view' },
                },
                {
                    path: 'workflow',
                    name: 'workflow',
                    component: () => import('@/modules/workflow/pages/WorkflowPage.vue'),
                },
                {
                    path: 'search',
                    name: 'search',
                    component: () => import('@/modules/search/pages/SearchPage.vue'),
                },
                {
                    path: 'operations',
                    name: 'operations',
                    component: () => import('@/modules/operations/pages/OperationsPage.vue'),
                },
                {
                    path: 'manual',
                    name: 'manual',
                    component: () => import('@/modules/manual/pages/SoftwareManualPage.vue'),
                },
            ],
        },
        {
            path: '/share/:token',
            name: 'public-share',
            component: () => import('@/modules/sharing/pages/PublicSharePage.vue'),
        },
    ],
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (!auth.initialized) {
        await auth.fetchMe();
    }

    if (to.meta.auth && !auth.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    // Nested routes inherit parent meta; check matched records for auth
    const needsAuth = to.matched.some((r) => r.meta.auth);
    if (needsAuth && !auth.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guest && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }

    const permission = to.meta.permission || to.matched.find((r) => r.meta.permission)?.meta.permission;
    if (permission && !auth.hasPermission(permission)) {
        return { name: 'dashboard' };
    }

    return true;
});

export default router;

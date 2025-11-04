
import { createRouter, createWebHashHistory } from 'vue-router';

// Import all your top-level view components (pages)
// Using the 'Page' suffix as per your provided router config
import DashboardPage from '../pages/DashboardPage.vue';
import OnboardingWizard from '../pages/OnboardingWizard.vue';
import AppointmentsPage from '../pages/AppointmentsPage.vue';
import StaffPage from '../pages/StaffPage.vue';
import ServicesCategoriesPage from '../pages/ServicesCategoriesPage.vue'; 
import ClientsPage from '../pages/ClientsPage.vue';
import NotificationsPage from '../pages/NotificationsPage.vue';
import AppearancePage from '../pages/AppearancePage.vue';
import SettingsPage from '../pages/SettingsPage.vue';
import PaymentsPage from '../pages/PaymentsPage.vue';
import AgendaPage from '../pages/AgendaPage.vue';
import ServiceFormCustomization from '../components/appearance/ServiceFormCustomization.vue';
import ServiceFormReservation from '../components/appearance/ServiceFormReservation.vue';
import AppearanceHomePage from '../components/appearance/AppearanceHomePage.vue';
import GoProPage from '../pages/GoProPage.vue';

/**
 * Define the routes for your Vue.js application.
 * Each route maps a URL path to a specific Vue component (page).
 */
const routes = [
  {
    path: '/',
    name: 'Dashboard',
    component: DashboardPage,
    meta: { title: 'Dashboard' }
  },
  {
    path: '/onboarding',
    name: 'Onboarding',
    component: OnboardingWizard,
    meta: { title: 'Onboarding' }
  },
  {
    path: '/appointments',
    name: 'Appointments',
    component: AppointmentsPage,
    meta: { title: 'Appointments' }
  },
  {
    path: '/agenda',
    name: 'Agenda',
    component: AgendaPage,
    meta: { title: 'Agenda' }
  },
  {
    path: '/staff',
    name: 'Staff',
    component: StaffPage,
    meta: { title: 'Staff' }
  },
  {
    
    path: '/services-categories', 
    name: 'Services-Categories',
    component: ServicesCategoriesPage,
    meta: { title: 'Services & Categories' }
  },
  {
    path: '/clients',
    name: 'Clients',
    component: ClientsPage,
    meta: { title: 'Clients' }
  },
  {
    path: '/notifications',
    name: 'Notifications',
    component: NotificationsPage,
    meta: { title: 'Notifications' }
  },
  {
    path: '/appearance',
    name: 'Appearance',
    component: AppearancePage,
    meta: { title: 'Appearance' }
  },
  {
    path: '/settings',
    name: 'Settings',
    component: SettingsPage,
    meta: { title: 'Settings' }
  },
  {
    path: '/payments',
    name: 'Payments',
    component: PaymentsPage,
    meta: { title: 'Payments' }
  },


  {
    path: '/service-form-customization/:id?',
    name: 'ServiceFormCustomization',
    component: ServiceFormCustomization,
    meta: { title: 'Service Form Customization' },
    props: true
  },
  {
    path: '/ServiceFormReservation',
    name: 'ServiceFormReservation',
    component: ServiceFormReservation,
    meta: { title: 'Service Form Reservation' }
  },
  
  {
    path: '/appearance-home',
    name: 'AppearanceHome',
    component: AppearanceHomePage,
    meta: { title: 'Appearance Home' }
  },
  
  {
    path: '/go-pro',
    name: 'GoPro',
    component: GoProPage,
    meta: { title: 'Go Pro' }
  },
  
  // Catch-all route for any undefined paths (e.g., 404 page)
  {
    path: '/:pathMatch(.*)*', // Matches any path not explicitly defined above
    name: 'NotFound',
    component: { // Simple inline component for a 404 page
        template: `
            <div class="p-6 bg-white rounded-lg shadow-md">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">404 - Page Not Found</h2>
                <p class="text-gray-600">The page you are looking for does not exist within Schedula admin.</p>
            </div>
        `
    }
  }
];

/**
 * Create the Vue Router instance.
 *
 * We use `createWebHashHistory()` without a base argument for compatibility
 * with WordPress admin environments. It uses a hash (#) in the URL,
 * which avoids issues with server-side routing and permalinks.
 */
const router = createRouter({
  history: createWebHashHistory(), // Removed the base argument here
  routes,
});

router.beforeEach((to, from, next) => {
  // Use `schedulaData` which is localized from PHP.
  const isOnboardingComplete = window.schedulaData && window.schedulaData.onboardingComplete;

  if (isOnboardingComplete && to.name === 'Onboarding') {
    // If onboarding is done, don't let user go back to wizard. Redirect to dashboard.
    next({ name: 'Dashboard' });
  }  else if (!isOnboardingComplete && to.name !== 'Onboarding') {
    // If onboarding is not done, and user is trying to access any other page, redirect to wizard.
    next({ name: 'Onboarding' });
  } else {
    // Otherwise, allow navigation (to onboarding if not complete, or any page if complete).
    next();
  }
});

// Update document title on route change (this is a good addition!)
router.afterEach((to) => {
  document.title = `${to.meta.title} | Schedula`;
});

export default router;

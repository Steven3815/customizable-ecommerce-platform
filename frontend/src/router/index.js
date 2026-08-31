import { createRouter, createWebHistory } from 'vue-router'

import CustomerLayout from '../layouts/CustomerLayout.vue'
import StoreLayout from '../layouts/StoreLayout.vue'

import CustomerLogin from '../views/auth/CustomerLogin.vue'
import CustomerRegister from '../views/auth/CustomerRegister.vue'
import CustomerLogout from '../views/auth/CustomerLogout.vue'
import StoreLogin from '../views/auth/StoreLogin.vue'
import StoreRegister from '../views/auth/StoreRegister.vue'
import StoreLogout from '../views/auth/StoreLogout.vue'

import Home from '../views/customer/Home.vue'
import StoreDashboard from '../views/store/dashboard/Dashboard.vue'
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),

  routes: [
    // Customer
    {
      path: '/store-:storeId',
      component: CustomerLayout,
      children: [
        {
          path: '',
          name: 'CustomerHome',
          component: Home,
        },
      ],
    },

    {
      path: '/store-:storeId/login',
      name: 'CustomerLogin',
      component: CustomerLogin,
    },

    {
      path: '/store-:storeId/register',
      name: 'CustomerRegister',
      component: CustomerRegister,
    },

    {
      path: '/store-:storeId/logout',
      name: 'CustomerLogout',
      component: CustomerLogout,
    },

    // Store
    {
      path: '/store-:storeId/admin/dashboard',
      name: 'StoreDashboard',
      component: StoreDashboard,

    },

    {
      path: '/store/login',
      name: 'StoreLogin',
      component: StoreLogin,
    },

    {
      path: '/store/register',
      name: 'StoreRegister',
      component: StoreRegister,
    },

    {
      path: '/store-:storeId/admin/logout',
      name: 'StoreLogout',
      component: StoreLogout,
    },
  ],
})

export default router
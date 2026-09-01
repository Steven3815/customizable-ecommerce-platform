import { createRouter, createWebHistory } from 'vue-router'

import StoreLayout from '../layouts/StoreLayout.vue'

import Page404 from '@/views/error/Page404.vue'

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
    // Error
    {
      path: '/404',
      name: 'Page404',
      component: Page404,
    },

    // Customer
    {
      path: '/store-:storeId',
      name: 'CustomerHome',
      component: Home,
      beforeEnter: (to) => {
        if (!/^[1-9]\d*$/.test(to.params.storeId)) {
          return '/404'
        }
      },
    },

    {
      path: '/store-:storeId/login',
      name: 'CustomerLogin',
      component: CustomerLogin,
      beforeEnter: (to) => {
        if (!/^[1-9]\d*$/.test(to.params.storeId)) {
          return '/404'
        }
      },
    },

    {
      path: '/store-:storeId/register',
      name: 'CustomerRegister',
      component: CustomerRegister,
      beforeEnter: (to) => {
        if (!/^[1-9]\d*$/.test(to.params.storeId)) {
          return '/404'
        }
      },
    },

    {
      path: '/store-:storeId/logout',
      name: 'CustomerLogout',
      component: CustomerLogout,
      beforeEnter: (to) => {
        if (!/^[1-9]\d*$/.test(to.params.storeId)) {
          return '/404'
        }
      },
    },

    // Store
    {
      path: '/store/admin/dashboard',
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
      beforeEnter: (to) => {
        if (!/^[1-9]\d*$/.test(to.params.storeId)) {
          return '/404'
        }
      },
    }
  ],
})

export default router
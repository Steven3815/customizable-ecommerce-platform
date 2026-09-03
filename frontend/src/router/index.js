import { createRouter, createWebHistory } from 'vue-router'

import StoreLayout from '../layouts/StoreLayout.vue'

import Page404 from '@/views/error/Page404.vue'
import Page401 from '@/views/error/Page401.vue'

import CustomerLogin from '../views/auth/CustomerLogin.vue'
import CustomerRegister from '../views/auth/CustomerRegister.vue'
import CustomerLogout from '../views/auth/CustomerLogout.vue'
import StoreLogin from '../views/auth/StoreLogin.vue'
import StoreRegister from '../views/auth/StoreRegister.vue'
import StoreLogout from '../views/auth/StoreLogout.vue'

import Home from '../views/customer/Home.vue'
import StoreDashboard from '../views/store/dashboard/Dashboard.vue'
import HomepageSettings from '../views/store/homepage/Settings.vue'
import HomepageSettingsBanner from '../views/store/homepage/Banner.vue'
import HomepageSettingsSlider from '../views/store/homepage/Slider.vue'
import HomepageSettingsProduct from '../views/store/homepage/Product.vue'


import { checkStoreAuth } from '@/api/store.js'



const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),

  routes: [
    // Error
    {
      path: '/404',
      name: 'Page404',
      component: Page404,
    },
    {
      path: '/401',
      name: 'Page401',
      component: Page401,
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
      meta: {
        requiresStoreAuth: true
      }
    },
    {
      path: '/store/admin/homepage_settings/settings',
      name: 'HomepageSettings',
      component: HomepageSettings,
      meta: {
        requiresStoreAuth: true
      }
    },
    {
      path: '/store/admin/homepage_settings/banner',
      name: 'HomepageSettingsBanner',
      component: HomepageSettingsBanner,
      meta: {
        requiresStoreAuth: true
      }
    },
    {
      path: '/store/admin/homepage_settings/slider',
      name: 'HomepageSettingsSlider',
      component: HomepageSettingsSlider,
      meta: {
        requiresStoreAuth: true
      }
    },
    {
      path: '/store/admin/homepage_settings/product',
      name: 'HomepageSettingsProduct',
      component: HomepageSettingsProduct,
      meta: {
        requiresStoreAuth: true
      }
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
      path: '/store/logout',
      name: 'StoreLogout',
      component: StoreLogout,
    },

    // 檢查不存在頁面 
    {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: Page404,
    }
  ],
})

router.beforeEach(async (to) => {
  if (!to.meta.requiresStoreAuth) {
    return true
  }

  const isAuthenticated = await checkStoreAuth()

  if (!isAuthenticated) {
    return '/401'
  }

  return true
})

export default router
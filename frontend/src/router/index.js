import { createRouter, createWebHistory } from 'vue-router'

import StoreLayout from '../layouts/StoreLayout.vue'

import Page404 from '@/views/error/Page404.vue'
import Page401 from '@/views/error/Page401.vue'

import CustomerLogin from '../views/auth/CustomerLogin.vue'
import CustomerRegister from '../views/auth/CustomerRegister.vue'
import StoreLogin from '../views/auth/StoreLogin.vue'
import StoreRegister from '../views/auth/StoreRegister.vue'

import CustomerHome from '../views/customer/Home.vue'
import CustomerCart from '../views/customer/Cart.vue'

import StoreDashboard from '../views/store/dashboard/Dashboard.vue'
import StoreHomepageSettings from '../views/store/homepage/Settings.vue'
import StoreHomepageSettingsBanner from '../views/store/homepage/Banner.vue'
import StoreHomepageSettingsSlider from '../views/store/homepage/Slider.vue'
import StoreHomepageSettingsProduct from '../views/store/homepage/Product.vue'
import StoreHomepageSettingsFooter from '../views/store/homepage/Footer.vue'
import StoreOrderList from '../views/store/order/OrderList.vue'
import StoreOrderDetail from '../views/store/order/OrderDetail.vue'
import StoreProductList from '../views/store/product/ProductList.vue'
import StoreProductDetail from '../views/store/product/ProductDetail.vue'
import StoreCustomerList from '../views/store/customer/CustomerList.vue'
import StoreCustomerDetail from '../views/store/customer/CustomerDetail.vue'
import StoreSettings from '../views/store/settings/StoreSettings.vue'
import StoreProfile from '../views/store/profile/StoreProfile.vue'
import StoreCustomerServiceList from '../views/store/customer_service/CustomerServiceList.vue'
import StoreCustomerServiceDetail from '../views/store/customer_service/CustomerServiceDetail.vue'
import StoreRefundList from '../views/store/refund/RefundList.vue'
import StoreRefundDetail from '../views/store/refund/RefundDetail.vue'

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
      component: CustomerHome,
      beforeEnter: (to) => {
        if (!/^[1-9]\d*$/.test(to.params.storeId)) {
          return '/404'
        }
      },
    },

    {
      path: '/store-:storeId/cart',
      name: 'CustomerCart',
      component: CustomerCart,
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
      name: 'StoreHomepageSettings',
      component: StoreHomepageSettings,
      meta: {
        requiresStoreAuth: true
      }
    },
    {
      path: '/store/admin/homepage_settings/banner',
      name: 'StoreHomepageSettingsBanner',
      component: StoreHomepageSettingsBanner,
      meta: {
        requiresStoreAuth: true
      }
    },
    {
      path: '/store/admin/homepage_settings/slider',
      name: 'StoreHomepageSettingsSlider',
      component: StoreHomepageSettingsSlider,
      meta: {
        requiresStoreAuth: true
      }
    },
    {
      path: '/store/admin/homepage_settings/product',
      name: 'StoreHomepageSettingsProduct',
      component: StoreHomepageSettingsProduct,
      meta: {
        requiresStoreAuth: true
      }
    },
    {
      path: '/store/admin/homepage_settings/footer',
      name: 'StoreHomepageSettingsFooter',
      component: StoreHomepageSettingsFooter,
      meta: {
        requiresStoreAuth: true
      }
    },
    {
      path: '/store/admin/order_list',
      name: 'StoreOrderList',
      component: StoreOrderList,
      meta: {
        requiresStoreAuth: true
      }
    },
    {
      path: '/store/admin/order/:orderId',
      name: 'StoreOrderDetail',
      component: StoreOrderDetail
    },
    {
      path: '/store/admin/product_list',
      name: 'StoreProductList',
      component: StoreProductList,
      meta: {
        requiresStoreAuth: true
      }
    },
    {
      path: '/store/admin/product/:productId',
      name: 'StoreProductDetail',
      component: StoreProductDetail,
      meta: {
        requiresStoreAuth: true
      }
    },
    {
      path: '/store/admin/customer_list',
      name: 'StoreCustomerList',
      component: StoreCustomerList,
      meta: {
        requiresStoreAuth: true
      }
    },
    {
      path: '/store/admin/customer/:customerId',
      name: 'StoreCustomerDetail',
      component: StoreCustomerDetail,
      meta: {
        requiresStoreAuth: true
      }
    },
    {
      path: '/store/admin/settings',
      name: 'StoreSettings',
      component: StoreSettings,
      meta: {
        requiresStoreAuth: true
      }
    },
    {
      path: '/store/admin/profile',
      name: 'StoreProfile',
      component: StoreProfile,
      meta: {
        requiresStoreAuth: true
      }
    },
    {
      path: '/store/admin/customer_service_list',
      name: 'StoreCustomerServiceList',
      component: StoreCustomerServiceList,
      meta: {
        requiresStoreAuth: true
      }
    },
    {
      path: '/store/admin/customer_service/:serviceId',
      name: 'StoreCustomerServiceDetail',
      component: StoreCustomerServiceDetail,
      meta: {
        requiresStoreAuth: true
      }
    },
    {
      path: '/store/admin/refund_list',
      name: 'StoreRefundList',
      component: StoreRefundList,
      meta: {
        requiresStoreAuth: true
      }
    },
    {
      path: '/store/admin/refund/:refundId',
      name: 'StoreRefundDetail',
      component: StoreRefundDetail,
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
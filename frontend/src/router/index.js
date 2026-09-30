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
import CustomerCategoryProduct from '../views/customer/CategoryProduct.vue'
import CustomerProductSearch from '../views/customer/ProductSearch.vue'
import CustomerOrderDetail from '../views/customer/OrderDetail.vue'
import CustomerOrderList from '../views/customer/OrderList.vue'
import CustomerCheckout from '../views/customer/Checkout.vue'
import CustomerCreditCard from '../views/customer/CreditCard.vue'
import CustomerTransferPayment from '../views/customer/TransferPayment.vue'
import CustomerProfile from '../views/customer/Profile.vue'
import CustomerServiceList from '../views/customer/service/ServiceList.vue'
import CustomerServiceDetail from '../views/customer/service/ServiceDetail.vue'
import CustomerCreateService from '../views/customer/service/CreateService.vue'
import CustomerRefundList from '../views/customer/refund/RefundList.vue'
import CustomerCreateRefund from '../views/customer/refund/CreateRefund.vue'

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
      path: '/store-:storeId/category/:categoryId',
      name: 'CustomerCategoryProduct',
      component: CustomerCategoryProduct,
      beforeEnter: (to) => {
        if (
          !/^[1-9]\d*$/.test(to.params.storeId) ||
          !/^[1-9]\d*$/.test(to.params.categoryId)
        ) {
          return '/404'
        }
      },
    },
    {
      path: '/store-:storeId/product_search',
      name: 'CustomerProductSearch',
      component: CustomerProductSearch,
      beforeEnter: (to) => {
        if (
          !/^[1-9]\d*$/.test(to.params.storeId)
        ) {
          return '/404'
        }
      },
    },
    {
      path: '/store-:storeId/checkout',
      name: 'CustomerCheckout',
      component: CustomerCheckout,
      beforeEnter: (to) => {
        if (
          !/^[1-9]\d*$/.test(to.params.storeId)
        ) {
          return '/404'
        }
      },
    },
    {
      path: '/store-:storeId/order/:orderId/payment/credit_card',
      name: 'CustomerCreditCard',
      component: CustomerCreditCard,
      beforeEnter: (to) => {
        if (
          !/^[1-9]\d*$/.test(to.params.storeId) ||
          !/^[1-9]\d*$/.test(to.params.orderId)
        ) {
          return '/404'
        }
      },
    },
    {
      path: '/store-:storeId/order/:orderId/payment/transfer_payment',
      name: 'CustomerTransferPayment',
      component: CustomerTransferPayment,
      beforeEnter: (to) => {
        if (
          !/^[1-9]\d*$/.test(to.params.storeId) ||
          !/^[1-9]\d*$/.test(to.params.orderId)
        ) {
          return '/404'
        }
      },
    },

    {
      path: '/store-:storeId/order_list',
      name: 'CustomerOrderList',
      component: CustomerOrderList,
      beforeEnter: (to) => {
        if (
          !/^[1-9]\d*$/.test(to.params.storeId)
        ) {
          return '/404'
        }
      },
    },
    {
      path: '/store-:storeId/order/:orderId',
      name: 'CustomerOrderDetail',
      component: CustomerOrderDetail,
      beforeEnter: (to) => {
        if (
          !/^[1-9]\d*$/.test(to.params.storeId) ||
          !/^[1-9]\d*$/.test(to.params.orderId)
        ) {
          return '/404'
        }
      },
    },
    {
      path: '/store-:storeId/profile',
      name: 'CustomerProfile',
      component: CustomerProfile,
      beforeEnter: (to) => {
        if (
          !/^[1-9]\d*$/.test(to.params.storeId)
        ) {
          return '/404'
        }
      },
    },
    {
      path: '/store-:storeId/service_list',
      name: 'CustomerServiceList',
      component: CustomerServiceList,
      beforeEnter: (to) => {
        if (
          !/^[1-9]\d*$/.test(to.params.storeId)
        ) {
          return '/404'
        }
      },
    },
    {
      path: '/store-:storeId/service/:serviceId',
      name: 'CustomerServiceDetail',
      component: CustomerServiceDetail,
      beforeEnter: (to) => {
        if (
          !/^[1-9]\d*$/.test(to.params.storeId) ||
          !/^[1-9]\d*$/.test(to.params.serviceId)
        ) {
          return '/404'
        }
      },
    },
    {
      path: '/store-:storeId/service/create',
      name: 'CustomerCreateService',
      component: CustomerCreateService,
      beforeEnter: (to) => {
        if (
          !/^[1-9]\d*$/.test(to.params.storeId)
        ) {
          return '/404'
        }
      },
    },
    {
      path: '/store-:storeId/refund_list',
      name: 'CustomerRefundList',
      component: CustomerRefundList,
      beforeEnter: (to) => {
        if (
          !/^[1-9]\d*$/.test(to.params.storeId)
        ) {
          return '/404'
        }
      },
    },
    {
      path: '/store-:storeId/refund/create',
      name: 'CustomerCreateRefund',
      component: CustomerCreateRefund,
      beforeEnter: (to) => {
        if (
          !/^[1-9]\d*$/.test(to.params.storeId) ||
          !/^[1-9]\d*$/.test(to.query.orderId)
        ) {
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
      component: StoreOrderDetail,
      meta: {
        requiresStoreAuth: true
      }
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
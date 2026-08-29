import { createRouter, createWebHistory } from 'vue-router'
import CustomerLogin from '../views/auth/CustomerLogin.vue'
import CustomerRegister from '../views/auth/CustomerRegister.vue'
import StoreLogin from '../views/auth/StoreLogin.vue'
import StoreRegister from '../views/auth/StoreRegister.vue'
// 暫時加logout
import CustomerLogout from '../views/auth/CustomerLogout.vue'
import StoreLogout from '../views/auth/StoreLogout.vue'

import Home from '../views/customer/Home.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    /* 平台首頁
    { 
      path: '/',
      name: 'Homepage',
      component: HomePage
    },
    商家入口
    { 
      path: '/store',
      name: 'StoreHomepage',
      component: StoreHomepage
    },*/
    {
      path: '/store-:storeId/login',
      name: 'CustomerLogin',
      component: CustomerLogin
    },
    {
      path: '/store-:storeId/register',
      name: 'CustomerRegister',
      component: CustomerRegister
    },
    {
      path: '/store/login',
      name: 'StoreLogin',
      component: StoreLogin
    },
    {
      path: '/store/register',
      name: 'StoreRegister',
      component: StoreRegister
    },
    // 暫時加logout
    {
      path: '/store-:storeId/logout',
      name: 'CustomerLogout',
      component: CustomerLogout
    },
    {
      path: '/store-:storeId/admin/logout',
      name: 'StoreLogout',
      component: StoreLogout
    },
    /*{ 後臺主頁
      path: '/store-1/admin',
      name: '',
      component: 
    },*/
    {
      path: '/store-:storeId',
      name: 'Home',
      component: Home
    }
  ]
})

export default router

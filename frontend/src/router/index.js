import { createRouter, createWebHistory } from 'vue-router'
import CustomerLogin from '../views/CustomerLogin.vue'
import CustomerRegister from '../views/CustomerRegister.vue'
import StoreLogin from '../views/StoreLogin.vue'
import StoreRegister from '../views/StoreRegister.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/store-:storeId/login',
      name: 'CustomerLogin',
      component: CustomerLogin
    },
    {
      path: '//store-:storeId/register',
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
  ]
})

export default router

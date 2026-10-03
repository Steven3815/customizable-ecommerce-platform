import { ref } from 'vue'
import { defineStore } from 'pinia'

import {
  getCustomerLoginStatus,
  getCustomerCart
} from '../api/customer.js'

export const useCartStore = defineStore('cart', () => {
  const cartTotal = ref(0)

  const loadCartTotal = async (storeId) => {
    if (!storeId) {
      cartTotal.value = 0
      return
    }

    try {
      const loginStatus = await getCustomerLoginStatus()

      if (!loginStatus.loggedIn) {
        cartTotal.value = 0
        return
      }

      const cart = await getCustomerCart(storeId)

      if (!cart?.items?.length) {
        cartTotal.value = 0
        return
      }

      cartTotal.value = cart.items.reduce(
        (total, item) =>
          total + Number(item.price) * Number(item.quantity),
        0
      )
    } catch (error) {
      console.error('取得購物車金額失敗:', error)
      cartTotal.value = 0
    }
  }

  return {
    cartTotal,
    loadCartTotal
  }
})
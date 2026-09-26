<template>
  <COffcanvas
    placement="end"
    :scroll="true"
    :visible="visible"
    @close="close"
  >
    <COffcanvasHeader>
      <COffcanvasTitle>
        購物車
        <span v-if="cart">
          ({{ cart.items?.length || 0 }})
        </span>
      </COffcanvasTitle>

      <CCloseButton
        class="text-reset"
        @click="close"
      />
    </COffcanvasHeader>

    <COffcanvasBody>

      <!-- Loading -->
      <div
        v-if="loading"
        class="text-center py-5"
      >
        <CSpinner />
      </div>

      <!-- 未登入 -->
      <div
        v-else-if="!isLoggedIn"
        class="text-center py-5"
      >
        <p class="mb-3">
          請先登入
        </p>

        <CButton
          color="primary"
          @click="goToLogin"
        >
          前往登入
        </CButton>
      </div>

      <!-- Error -->
      <div
        v-else-if="error"
        class="text-center py-5"
      >
        <p class="text-danger mb-3">
          {{ error }}
        </p>

        <CButton
          color="primary"
          @click="open"
        >
          重新載入
        </CButton>
      </div>

      <!-- 購物車為空 -->
      <div
        v-else-if="!cart?.items?.length"
        class="text-center py-5"
      >
        <p class="text-body-secondary mb-3">
          購物車目前沒有商品
        </p>

        <CButton
          color="primary"
          @click="goToCart"
        >
          繼續購物
        </CButton>
      </div>

      <!-- 購物車商品 -->
      <div v-else>

        <div
          v-for="item in cart.items"
          :key="item.cart_item_id"
          class="cart-item d-flex gap-3 mb-4"
        >

          <!-- 商品圖片 -->
          <div class="cart-image">

            <img
              v-if="item.image_url"
              :src="item.image_url"
              :alt="item.product_name"
            >

            <div
              v-else
              class="cart-image-placeholder"
            >
              無圖片
            </div>

          </div>

          <!-- 商品資訊 -->
          <div class="flex-grow-1">

            <!-- 商品名稱 -->
            <div class="fw-semibold mb-1">
              {{ item.product_name }}
            </div>

            <!-- 規格 -->
            <div
              v-if="item.spec_name"
              class="small text-body-secondary mb-1"
            >
              {{ item.spec_name }}
            </div>

            <!-- 單價 -->
            <div class="small mb-1">
              NT$ {{ Number(item.price).toLocaleString() }}
            </div>

            <!-- 數量 -->
            <div class="small text-body-secondary">
              數量：{{ item.quantity }}
            </div>

          </div>

          <!-- 小計 -->
          <div class="fw-semibold text-nowrap">
            NT$
            {{
              (
                Number(item.price) * Number(item.quantity)
              ).toLocaleString()
            }}
          </div>

        </div>

        <hr>

        <!-- 商品小計 -->
        <div class="d-flex justify-content-between mb-2">

          <span>
            商品小計
          </span>

          <span>
            NT$ {{ cartSubtotal.toLocaleString() }}
          </span>

        </div>

        <!-- 總計 -->
        <div class="d-flex justify-content-between fw-semibold fs-5">

          <span>
            總計
          </span>

          <span>
            NT$ {{ cartSubtotal.toLocaleString() }}
          </span>

        </div>

        <!-- 前往購物車 -->
        <CButton
          color="primary"
          class="w-100 mt-4"
          @click="goToCart"
        >
          前往購物車
        </CButton>

      </div>

    </COffcanvasBody>
  </COffcanvas>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'

import {
  COffcanvas,
  COffcanvasHeader,
  COffcanvasTitle,
  COffcanvasBody,
  CCloseButton,
  CButton,
  CSpinner
} from '@coreui/vue'

import {
  getCustomerLoginStatus,
  getCustomerCart
} from '../../api/customer.js'

const router = useRouter()

const props = defineProps({
  storeId: {
    type: [String, Number],
    required: false,
    default: null
  }
})

const visible = ref(false)
const loading = ref(false)
const error = ref('')
const cart = ref(null)
const isLoggedIn = ref(false)

const cartSubtotal = computed(() => {

  if (!cart.value?.items) {
    return 0
  }

  return cart.value.items.reduce(
    (total, item) =>
      total + Number(item.price) * Number(item.quantity),
    0
  )

})

async function loadCart() {

  if (!props.storeId) {
    error.value = '找不到商店資料'
    return
  }

  try {

    cart.value = await getCustomerCart(props.storeId)

  } catch (err) {

    console.error('取得購物車資料失敗:', err)

    error.value = err.message || '取得購物車資料失敗'

  }

}

const open = async () => {

  visible.value = true
  loading.value = true
  error.value = ''
  cart.value = null
  isLoggedIn.value = false

  try {

    const loginStatus = await getCustomerLoginStatus()

    isLoggedIn.value = loginStatus.loggedIn

    if (!loginStatus.loggedIn) {
      return
    }

    if (!props.storeId) {
      error.value = '找不到商店資料'
      return
    }

    await loadCart()

  } catch (err) {

    console.error('取得登入狀態失敗:', err)

    error.value = err.message || '取得登入狀態失敗'

  } finally {

    loading.value = false

  }

}

const close = () => {
  visible.value = false
}

const goToLogin = () => {
  close()
  router.push(`/store-${props.storeId}/login`)
}

const goToCart = () => {
  close()
  router.push(`/store-${props.storeId}/cart`)
}

defineExpose({
  open,
  close
})
</script>

<style scoped>

.cart-item {
  min-width: 0;
}

.cart-image {
  width: 72px;
  height: 72px;
  flex-shrink: 0;
}

.cart-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 6px;
}

.cart-image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--cui-secondary-bg);
  color: var(--cui-secondary-color);
  border-radius: 6px;
  font-size: 12px;
}

</style>
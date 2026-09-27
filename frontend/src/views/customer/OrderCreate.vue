<template>
  <div>
    <Sidebar />

    <div class="wrapper d-flex flex-column min-vh-100">

      <Header
        :store="home?.store || {}"
      />

      <div class="body flex-grow-1">

        <CContainer class="px-4" lg>

          <!-- 標題 -->
          <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

              <h2 class="mt-2 mb-2">
                建立訂單
              </h2>

              <div class="mt-4">
                商品數：{{ productQuantity }} 件
              </div>

            </div>

            <CButton
              color="secondary"
              variant="outline"
              @click="goCart"
            >
              返回購物車
            </CButton>

          </div>

          <!-- 商品 -->
          <CCard class="mb-4">
            <CCardBody>

              <h4 class="mb-4">
                訂購商品
              </h4>

              <div
                v-for="item in selectedCartItems"
                :key="item.cart_item_id"
                class="d-flex border-bottom py-4"
              >

                <!-- 商品圖片 -->
                <div class="product-image ms-4 me-4">

                  <img
                    :src="item.image_url"
                    :alt="item.product_name"
                    class="img-fluid"
                  >

                </div>

                <!-- 商品資訊 -->
                <div class="flex-grow-1">

                  <h5 class="mb-3">
                    {{ item.product_name }}
                  </h5>

                  <div
                    v-if="item.spec_name"
                    class="text-body-secondary mb-2"
                  >
                    規格：{{ item.spec_name }}
                  </div>

                  <div class="mb-2">
                    單價：NT$ {{ Number(item.price).toLocaleString() }}
                  </div>

                  <div class="mb-2">
                    數量：{{ item.quantity }}
                  </div>

                  <div class="fw-bold">
                    小計：
                    NT$
                    {{
                      (
                        Number(item.price) *
                        Number(item.quantity)
                      ).toLocaleString()
                    }}
                  </div>

                </div>

              </div>

            </CCardBody>
          </CCard>

          <!-- 訂單摘要 -->
          <CCard class="mb-4">
            <CCardBody>

              <h4 class="mb-4">
                訂單摘要
              </h4>

              <div class="d-flex justify-content-between mb-3">

                <span>
                  商品金額
                </span>

                <span>
                  NT$ {{ productAmount.toLocaleString() }}
                </span>

              </div>

              <div class="d-flex justify-content-between mb-3">

                <span>
                  運費
                </span>

                <span>
                  NT$ {{ shippingFee.toLocaleString() }}
                </span>

              </div>

              <hr>

              <div class="d-flex justify-content-between">

                <span class="fw-bold">
                  總金額
                </span>

                <span class="fw-bold">
                  NT$ {{ totalAmount.toLocaleString() }}
                </span>

              </div>

            </CCardBody>
          </CCard>

          <!-- 收件資料 -->
          <CCard class="mb-4">
            <CCardBody>

              <h4 class="mb-4">
                收件資料
              </h4>

              <CRow class="mb-4">

                <CCol :md="6">

                  <div class="d-flex align-items-center">

                    <CFormLabel class="mb-0 me-3 text-nowrap">
                      收件人姓名
                    </CFormLabel>

                    <CFormInput
                      v-model="receiverName"
                      placeholder="請輸入收件人姓名"
                    />

                  </div>

                </CCol>

              </CRow>

              <CRow class="mb-4">

                <CCol :md="6">

                  <div class="d-flex align-items-center">

                    <CFormLabel class="mb-0 me-3 text-nowrap">
                      收件人電話
                    </CFormLabel>

                    <CFormInput
                      v-model="receiverPhone"
                      placeholder="請輸入收件人電話"
                    />

                  </div>

                </CCol>

              </CRow>

              <CRow class="mb-4">

                <CCol :md="6">

                  <div class="d-flex align-items-center">

                    <CFormLabel class="mb-0 me-3 text-nowrap">
                      收件地址
                    </CFormLabel>

                    <CFormInput
                      v-model="receiverAddress"
                      placeholder="請輸入收件地址"
                    />

                  </div>

                </CCol>

              </CRow>

              <CRow>

                <CCol :md="6">

                  <div class="d-flex align-items-center">

                    <CFormLabel class="mb-0 me-3 text-nowrap">
                      配送方式
                    </CFormLabel>

                    <CFormSelect
                      v-model="deliveryMethod"
                    >
                      <option value="">
                        請選擇配送方式
                      </option>

                      <option value="home_delivery">
                        宅配
                      </option>

                      <option value="convenience_store">
                        超商取貨
                      </option>

                      <option value="store_pickup">
                        門市自取
                      </option>

                    </CFormSelect>

                  </div>

                </CCol>

              </CRow>

            </CCardBody>
          </CCard>

          <!-- 建立訂單 -->
          <div class="d-flex justify-content-end mb-4">

            <CButton
              color="primary"
              :disabled="loading"
              @click="createOrder"
            >
              {{ loading ? '建立中...' : '建立訂單' }}
            </CButton>

          </div>

        </CContainer>

      </div>

      <Footer
        :footer="home?.footer || {}"
      />

      <Createdby />

    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import {
  CButton,
  CCard,
  CCardBody,
  CCol,
  CContainer,
  CFormInput,
  CFormLabel,
  CFormSelect,
  CRow
} from '@coreui/vue'

import Sidebar from '../../components/customer_homepage/Sidebar.vue'
import Header from '../../components/customer_homepage/Header.vue'
import Footer from '../../components/customer_homepage/Footer.vue'
import Createdby from '../../components/customer_homepage/Createdby.vue'

import {
  getCustomerHome,
  getCustomerCart,
  createCustomerOrder
} from '../../api/customer.js'

const route = useRoute()
const router = useRouter()

const storeId = route.params.storeId

const home = ref(null)
const cart = ref({
  items: []
})

const selectedItems = ref(
  JSON.parse(
    localStorage.getItem(`cart-selected-${storeId}`) || '[]'
  )
)

const receiverName = ref('')
const receiverPhone = ref('')
const receiverAddress = ref('')
const deliveryMethod = ref('')

const loading = ref(false)

const shippingFee = 60

const selectedCartItems = computed(() => {
  return cart.value.items.filter(
    item =>
      selectedItems.value.includes(
        item.cart_item_id
      )
  )
})

const productQuantity = computed(() => selectedCartItems.value.length)

const productAmount = computed(() => {
  return selectedCartItems.value.reduce((total, item) => total + Number(item.price) * Number(item.quantity), 0)
})

const totalAmount = computed(() => {
  return productAmount.value + shippingFee
})

function goCart() {
  router.push(`/store-${storeId}/cart`)
}

async function loadHome() {
  try {
    home.value = await getCustomerHome(storeId)
  } catch (error) {
    console.error('取得首頁資料失敗:', error)
  }
}

async function loadCart() {
  try {
    cart.value = await getCustomerCart(
      storeId,
      'created_at',
      'desc'
    )

    const cartItemIds = cart.value.items.map(
      item => item.cart_item_id
    )

    selectedItems.value = selectedItems.value.filter(
      id => cartItemIds.includes(id)
    )

  } catch (error) {
    console.error('取得購物車失敗:', error)
  }
}

async function createOrder() {

  if (!receiverName.value.trim()) {
    alert('請輸入收件人姓名')
    return
  }

  if (!receiverPhone.value.trim()) {
    alert('請輸入收件人電話')
    return
  }

  if (!receiverAddress.value.trim()) {
    alert('請輸入收件地址')
    return
  }

  if (!deliveryMethod.value) {
    alert('請選擇配送方式')
    return
  }

  if (selectedItems.value.length === 0) {
    alert('沒有選擇任何商品')
    return
  }

  loading.value = true

  try {

    const data = await createCustomerOrder(
      selectedItems.value,
      receiverName.value,
      receiverPhone.value,
      receiverAddress.value,
      deliveryMethod.value
    )

    localStorage.removeItem(
      `cart-selected-${storeId}`
    )

    alert('訂單建立成功')

    console.log('建立訂單成功:', data)

  } catch (error) {

    console.error('建立訂單失敗:', error)

    if (error.errorType === 'insufficient_stock') {
      alert(`${error.message}：目前剩餘 ${error.stock} 件，請至購物車調整數量`)
    } else {
      alert(error.message || '建立訂單失敗')
    }

  } finally {

    loading.value = false

  }
}

onMounted(async () => {
  await Promise.all([
    loadHome(),
    loadCart()
  ])
})
</script>

<style scoped>
.product-image {
  width: 150px;
  height: 150px;
  flex-shrink: 0;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 6px;
}

:deep(.form-control),
:deep(.form-select) {
  font-size: 14px;
}
</style>
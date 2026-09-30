<template>
  <div>
    <Sidebar />

    <div class="wrapper d-flex flex-column min-vh-100">
      <Header :store="home?.store || {}" />

      <div class="body flex-grow-1">
        <CContainer class="px-4" lg>
          <!-- 標題 -->
          <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h2 class="mb-2">
                  建立訂單
                </h2>

                <div class="mt-4">
                  商品數：{{ productQuantity }} 件
                </div>
              </div>

              <CButton
                color="secondary"
                @click="goBack"
              >
                返回上頁
              </CButton>
            </div>
          </div>

          <!-- 商品 -->
          <CCard class="mb-4">
            <CCardBody>
              <h4 class="mb-4">
                訂購商品
              </h4>

              <div
                v-for="item in selectedCartItems"
                :key="item.cart_item_id || item.order_item_id || item.product_id"
                class="d-flex border-bottom py-4"
              >
                <div class="product-image ms-4 me-4">
                  <img
                    :src="item.image_url"
                    :alt="item.product_name"
                    class="img-fluid"
                  >
                </div>

                <div class="flex-grow-1">
                  <h5 class="fw-bold mb-3">
                    {{ item.product_name }}
                  </h5>

                  <div
                    v-if="item.spec_name"
                    class="text-body-secondary mb-2"
                  >
                    規格：{{ item.spec_name }}
                  </div>

                  <div class="mb-2 d-flex">
                    <span>
                      單價：
                    </span>

                    <span class="product-amount">
                      $ {{ Number(item.price).toLocaleString() }}
                    </span>
                  </div>

                  <div class="mb-2 d-flex">
                    <span>
                      數量：
                    </span>

                    <span class="product-amount">
                      {{ item.quantity }}
                    </span>
                  </div>

                  <div class="fw-bold d-flex">
                    <span>
                      小計：
                    </span>

                    <span class="product-amount">
                      $
                      {{
                        (
                          Number(item.price) *
                          Number(item.quantity)
                        ).toLocaleString()
                      }}
                    </span>
                  </div>
                </div>
              </div>

              <div
                v-if="selectedCartItems.length === 0"
                class="text-body-secondary py-4"
              >
                沒有商品
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
                  $ {{ productAmount.toLocaleString() }}
                </span>
              </div>

              <div class="d-flex justify-content-between mb-3">
                <span>
                  運費
                </span>

                <span>
                  $ {{ shippingFee.toLocaleString() }}
                </span>
              </div>

              <hr>

              <div class="d-flex justify-content-between">
                <span class="fw-bold">
                  總金額
                </span>

                <span class="fw-bold">
                  $ {{ totalAmount.toLocaleString() }}
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

                    <CFormSelect v-model="deliveryMethod">
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
              @click="submitOrder"
            >
              {{
                loading
                  ? '處理中...'
                  : '前往付款'
              }}
            </CButton>
          </div>
        </CContainer>
      </div>

      <Footer :footer="home?.footer || {}" />
      <Createdby />
    </div>
  </div>
</template>

<script setup>
import {
  computed,
  onMounted,
  ref
} from 'vue'

import {
  useRoute,
  useRouter
} from 'vue-router'

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
  getCustomerOrder,
  createCustomerOrder,
  updateCustomerOrder
} from '../../api/customer.js'

const route = useRoute()
const router = useRouter()

const storeId = route.params.storeId
const orderId = route.query.order_id
const isEditMode = !!orderId

const home = ref(null)

const cart = ref({
  items: []
})

const orderItems = ref([])

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
const shippingFee = ref(60)

const selectedCartItems = computed(() => {
  if (isEditMode) {
    return orderItems.value
  }

  return cart.value.items.filter(
    item =>
      selectedItems.value.includes(
        item.cart_item_id
      )
  )
})

const productQuantity = computed(() => {
  return selectedCartItems.value.length
})

const productAmount = computed(() => {
  return selectedCartItems.value.reduce(
    (total, item) =>
      total +
      Number(item.price) *
      Number(item.quantity),
    0
  )
})

const totalAmount = computed(() => {
  return productAmount.value +
    Number(shippingFee.value)
})

function goBack() {
  router.back()
}

async function loadHome() {
  try {
    home.value =
      await getCustomerHome(storeId)
  } catch (error) {
    console.error(
      '取得首頁資料失敗:',
      error
    )
  }
}

async function loadCart() {
  if (isEditMode) {
    return
  }

  try {
    cart.value =
      await getCustomerCart(
        storeId,
        'created_at',
        'desc'
      )

    const cartItemIds =
      cart.value.items.map(
        item =>
          item.cart_item_id
      )

    selectedItems.value =
      selectedItems.value.filter(
        id =>
          cartItemIds.includes(id)
      )
  } catch (error) {
    console.error(
      '取得購物車失敗:',
      error
    )
  }
}

async function loadOrder() {
  if (!isEditMode) {
    return
  }

  try {
    const data =
      await getCustomerOrder(
        storeId,
        orderId
      )

    const order = data.order

    if (
      order.order_status !==
      'pending'
    ) {
      alert(
        '此訂單目前無法重新建立'
      )

      router.push(
        `/store-${storeId}/order_list`
      )

      return
    }

    receiverName.value =
      order.receiver_name || ''

    receiverPhone.value =
      order.receiver_phone || ''

    receiverAddress.value =
      order.receiver_address || ''

    deliveryMethod.value =
      order.delivery_method || ''

    shippingFee.value =
      Number(order.shipping_fee || 0)

    orderItems.value =
      order.items || []
  } catch (error) {
    console.error(
      '取得訂單資料失敗:',
      error
    )

    alert(
      error.message ||
      '取得訂單資料失敗'
    )

    router.push(
      `/store-${storeId}/order_list`
    )
  }
}

function validateOrder() {
  if (
    !receiverName.value.trim()
  ) {
    alert(
      '請輸入收件人姓名'
    )

    return false
  }

  if (
    !receiverPhone.value.trim()
  ) {
    alert(
      '請輸入收件人電話'
    )

    return false
  }

  if (
    !receiverAddress.value.trim()
  ) {
    alert(
      '請輸入收件地址'
    )

    return false
  }

  if (
    !deliveryMethod.value
  ) {
    alert(
      '請選擇配送方式'
    )

    return false
  }

  if (
    !isEditMode &&
    selectedItems.value.length === 0
  ) {
    alert(
      '沒有選擇任何商品'
    )

    return false
  }

  return true
}

async function submitOrder() {
  if (!validateOrder()) {
    return
  }

  loading.value = true

  try {
    if (isEditMode) {
      const data =
        await updateCustomerOrder(
          storeId,
          orderId,
          receiverName.value,
          receiverPhone.value,
          receiverAddress.value,
          deliveryMethod.value
        )

      console.log(
        '建立訂單成功:',
        data
      )

      router.push(
        `/store-${storeId}/order/${orderId}/payment`
      )

      return
    }

    const data =
      await createCustomerOrder(
        selectedItems.value,
        receiverName.value,
        receiverPhone.value,
        receiverAddress.value,
        deliveryMethod.value
      )

    console.log(
      '建立訂單成功:',
      data
    )

    localStorage.removeItem(
      `cart-selected-${storeId}`
    )

    alert(
      '訂單建立成功'
    )

    router.push(
      `/store-${storeId}/order/${data.order_id}/payment`
    )
  } catch (error) {
    console.error(
      '建立訂單失敗:',
      error
    )

    if (
      !isEditMode &&
      error.errorType ===
      'insufficient_stock'
    ) {
      alert(
        `${error.message}：目前剩餘 ${error.stock} 件，請至購物車調整數量`
      )
    } else {
      alert(
        error.message ||
        '建立訂單失敗'
      )
    }
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadHome()
  await loadCart()

  if (isEditMode) {
    await loadOrder()
  }
})
</script>

<style scoped>
.product-image {
  width: 100px;
  height: 100px;
  flex-shrink: 0;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 6px;
}

.product-amount {
  width: 120px;
  text-align: right;
  margin-left: 8px;
}

:deep(.form-control),
:deep(.form-select) {
  font-size: 14px;
}
</style>
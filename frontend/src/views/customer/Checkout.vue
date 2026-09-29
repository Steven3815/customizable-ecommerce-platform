<template>
  <div>
    <Sidebar />

    <div class="wrapper d-flex flex-column min-vh-100">
      <Header :store="home?.store || paymentMethodsData?.store || {}" />

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
                  商品數：{{ selectedCartItems.length }} 件
                </div>
              </div>

              <CButton
                color="secondary"
                @click="goBack"
              >
                返回上一頁
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

              <CRow>
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
            </CCardBody>
          </CCard>

          <!-- 配送方式 -->
          <CCard class="mb-4">
            <CCardBody>
              <h4 class="mb-4">
                配送方式
              </h4>

              <CFormSelect
                v-model="deliveryMethod"
                :options="[
                  {
                    label: '請選擇配送方式',
                    value: ''
                  },
                  ...(paymentMethodsData?.delivery_methods || []).map(
                    method => ({
                      label: method.name,
                      value: method.delivery_method
                    })
                  )
                ]"
              />

              <div
                v-if="!paymentMethodsData?.delivery_methods?.length"
                class="text-body-secondary mt-2"
              >
                目前沒有可用的配送方式
              </div>
            </CCardBody>
          </CCard>

          <!-- 付款方式 -->
          <CCard class="mb-4">
            <CCardBody>
              <h4 class="mb-4">
                付款方式
              </h4>

              <CFormSelect
                v-model="selectedPaymentMethod"
                :options="[
                  {
                    label: '請選擇付款方式',
                    value: ''
                  },
                  ...(paymentMethodsData?.payment_methods || []).map(
                    method => ({
                      label: method.name,
                      value: method.payment_method
                    })
                  )
                ]"
              />

              <div
                v-if="!paymentMethodsData?.payment_methods?.length"
                class="text-body-secondary mt-2"
              >
                目前沒有可用的付款方式
              </div>
            </CCardBody>
          </CCard>

          <!-- 建立訂單 -->
          <div class="d-flex justify-content-end mb-4">
            <CButton
              color="primary"
              :disabled="loading"
              @click="submitCheckout"
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

    <!-- 錯誤提示視窗 -->
    <CModal
      :visible="errorVisible"
      @close="errorVisible = false"
    >
      <CModalHeader class="border-0">
        <CModalTitle>
          提示
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        {{ errorMessage }}
      </CModalBody>

      <CModalFooter class="border-0">
        <CButton
          color="primary"
          @click="errorVisible = false"
        >
          確定
        </CButton>
      </CModalFooter>
    </CModal>

    <!-- 訂單成功 -->
    <CModal
      :visible="orderSuccessVisible"
      @close="orderSuccessVisible = false"
    >
      <CModalHeader class="border-0">
        <CModalTitle>
          訂單建立成功
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        可查看訂單
      </CModalBody>

      <CModalFooter class="border-0">
        <CButton
          color="primary"
          @click="goOrderDetail"
        >
          查看訂單
        </CButton>
      </CModalFooter>
    </CModal>

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
  CModal,
  CModalBody,
  CModalFooter,
  CModalHeader,
  CModalTitle,
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
  getCustomerPayment,
  getCustomerPaymentMethods,
  createCustomerOrder,
  updateCustomerOrder,
  createCustomerPayment
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

const paymentMethodsData = ref(null)
const paymentData = ref(null)

const selectedItems = ref(
  JSON.parse(
    localStorage.getItem(`cart-selected-${storeId}`) || '[]'
  )
)

const receiverName = ref('')
const receiverPhone = ref('')
const receiverAddress = ref('')
const deliveryMethod = ref('')
const selectedPaymentMethod = ref('')

const loading = ref(false)
const errorMessage = ref('')
const errorVisible = ref(false)

const orderSuccessVisible = ref(false)
const completedOrderId = ref(null)

const shippingFee = ref(60)

const selectedCartItems = computed(() => {
  if (isEditMode) {
    return orderItems.value
  }

  return cart.value.items.filter(
    item => selectedItems.value.map(Number).includes(Number(item.cart_item_id))
  )
})

// 計算商品金額
const productAmount = computed(() => {
  return selectedCartItems.value.reduce(
    (total, item) => total + Number(item.price) * Number(item.quantity),
    0
  )
})

// 計算總金額
const totalAmount = computed(() => {
  return productAmount.value + Number(shippingFee.value)
})

function showError(message) {
  errorMessage.value = message
  errorVisible.value = true
}

function goBack() {
  router.back()
}

function goOrderDetail() {
  orderSuccessVisible.value = false

  router.replace(
    `/store-${storeId}/order/${completedOrderId.value}`
  )
}

async function loadHome() {
  try {
    home.value = await getCustomerHome(storeId)
  } catch (error) {
    console.error(
      '取得首頁資料失敗:',
      error
    )
  }
}

async function loadPaymentMethods() {
  try {
    paymentMethodsData.value =
      await getCustomerPaymentMethods(storeId)

    const preferredPayment =
      paymentMethodsData.value?.payment_methods?.find(
        method => method.selected
      )

    const preferredDelivery =
      paymentMethodsData.value?.delivery_methods?.find(
        method => method.selected
      )

    if (!isEditMode) {
      selectedPaymentMethod.value =
        preferredPayment?.payment_method || ''

      deliveryMethod.value =
        preferredDelivery?.delivery_method || ''
    }
  } catch (error) {
    console.error(
      '取得付款與配送方式失敗:',
      error
    )

    if (error.status === 401) {
      router.replace('/401')
      return
    }

    if (error.status === 404) {
      router.replace('/404')
      return
    }

    showError(
      error.message ||
      '取得付款與配送方式失敗'
    )
  }
}

async function loadPayment() {
  if (!isEditMode) {
    return
  }

  try {
    paymentData.value =
      await getCustomerPayment(
        storeId,
        orderId
      )

    selectedPaymentMethod.value =
      paymentData.value?.payment?.payment_method ||
      paymentData.value?.customer?.preferred_payment ||
      ''
  } catch (error) {
    console.error(
      '取得付款資料失敗:',
      error
    )

    if (error.status === 401) {
      router.replace('/401')
      return
    }

    if (error.status === 404) {
      router.replace('/404')
      return
    }

    showError(
      error.message ||
      '取得付款資料失敗'
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
        item => Number(item.cart_item_id)
      )

    selectedItems.value =
      selectedItems.value.filter(
        id => cartItemIds.includes(Number(id))
      )
  } catch (error) {
    console.error(
      '取得購物車失敗:',
      error
    )

    showError(
      error.message ||
      '取得購物車資料失敗'
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

    if (order.order_status !== 'pending') {
      showError(
        '此訂單目前無法重新建立'
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

    showError(
      error.message ||
      '取得訂單資料失敗'
    )
  }
}

function validateCheckout() {
  if (!receiverName.value.trim()) {
    showError(
      '請輸入收件人姓名'
    )

    return false
  }

  if (!receiverPhone.value.trim()) {
    showError(
      '請輸入收件人電話'
    )

    return false
  }

  if (!receiverAddress.value.trim()) {
    showError(
      '請輸入收件地址'
    )

    return false
  }

  if (!deliveryMethod.value) {
    showError(
      '請選擇配送方式'
    )

    return false
  }

  if (!selectedPaymentMethod.value) {
    showError(
      '請選擇付款方式'
    )

    return false
  }

  if (
    !isEditMode &&
    selectedItems.value.length === 0
  ) {
    showError(
      '沒有選擇任何商品'
    )

    return false
  }

  return true
}

async function submitCheckout() {
  errorMessage.value = ''
  errorVisible.value = false

  if (!validateCheckout()) {
    return
  }

  loading.value = true

  try {
    let currentOrderId = orderId

    // 編輯既有訂單
    if (isEditMode) {
      await updateCustomerOrder(
        storeId,
        orderId,
        receiverName.value,
        receiverPhone.value,
        receiverAddress.value,
        deliveryMethod.value
      )
    }

    // 建立新訂單
    else {
      const orderData =
        await createCustomerOrder(
          selectedItems.value,
          receiverName.value,
          receiverPhone.value,
          receiverAddress.value,
          deliveryMethod.value
        )

      console.log(
        '建立訂單成功:',
        orderData
      )

      currentOrderId =
        orderData.order_id

      localStorage.removeItem(
        `cart-selected-${storeId}`
      )
    }

    // 建立付款
    const paymentDataResult =
      await createCustomerPayment(
        storeId,
        currentOrderId,
        selectedPaymentMethod.value
      )

    console.log(
      '建立付款成功:',
      paymentDataResult
    )

    const nextAction =
      paymentDataResult?.payment?.next_action ||
      paymentDataResult?.next_action

    // 信用卡
    if (nextAction === 'credit_card') {
      router.replace(
        `/store-${storeId}/order/${currentOrderId}/payment/credit_card`
      )

      return
    }

    // ATM / 郵局轉帳
    if (
      nextAction === 'bank_transfer' ||
      nextAction === 'post_office_transfer'
    ) {
      router.replace(
        `/store-${storeId}/order/${currentOrderId}/payment/transfer_payment`
      )

      return
    }

    // 貨到付款、店內付款
    if (nextAction === 'complete_order') {
      completedOrderId.value = currentOrderId
      orderSuccessVisible.value = true

      return
    }

    // 如果 API 沒有回傳 next_action
    completedOrderId.value = currentOrderId
    orderSuccessVisible.value = true
  } catch (error) {
    console.error(
      '建立訂單或付款失敗:',
      error
    )

    if (error.status === 401) {
      router.replace('/401')
      return
    }

    if (error.status === 404) {
      router.replace('/404')
      return
    }

    if (
      !isEditMode &&
      error.errorType === 'insufficient_stock'
    ) {
      showError(
        `${error.message}：目前剩餘 ${error.stock} 件，請至購物車調整數量`
      )

      return
    }

    showError(
      error.message ||
      '建立訂單或付款失敗'
    )
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  if (!/^[1-9]\d*$/.test(storeId)) {
    router.replace('/404')
    return
  }

  await loadHome()
  await loadPaymentMethods()
  await loadCart()

  if (isEditMode) {
    await loadOrder()
    await loadPayment()
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
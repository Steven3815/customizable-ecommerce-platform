<template>
  <div>
    <Sidebar />

    <div class="wrapper d-flex flex-column min-vh-100">
      <Header :store="home?.store || order?.store || {}" />

      <div class="body flex-grow-1">
        <CContainer class="px-4" lg>

          <!-- 標題 -->
          <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h2 class="mb-2">
                  信用卡付款
                </h2>
              </div>
            </div>
          </div>

          <!-- 交易金額 -->
          <CCard class="mb-4">
            <CCardBody>
              <h4 class="mb-4">
                交易金額
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

          <!-- 信用卡付款 -->
          <CCard class="mb-4">
            <CCardBody>
              <h4 class="mb-4">
                信用卡資訊
              </h4>

              <CRow class="mb-4">
                <CCol :md="6">
                  <div class="d-flex align-items-center">
                    <CFormLabel class="mb-0 me-3 text-nowrap">
                      信用卡號
                    </CFormLabel>

                    <CFormInput
                      v-model="cardNumber"
                      type="text"
                      inputmode="numeric"
                      autocomplete="cc-number"
                      maxlength="19"
                      placeholder="如：1234 5678 9012 3456"
                      :disabled="loading"
                    />
                  </div>
                </CCol>
              </CRow>

              <CRow class="mb-4">
                <CCol :md="6">
                  <div class="d-flex align-items-center">
                    <CFormLabel class="mb-0 me-3 text-nowrap">
                      有效期限
                    </CFormLabel>

                    <CFormInput
                      v-model="expiryDate"
                      type="text"
                      inputmode="numeric"
                      autocomplete="cc-exp"
                      maxlength="5"
                      placeholder="MM/YY"
                      :disabled="loading"
                    />
                  </div>
                </CCol>
              </CRow>

              <CRow>
                <CCol :md="6">
                  <div class="d-flex align-items-center">
                    <CFormLabel class="mb-0 me-3 text-nowrap">
                      手機號碼
                    </CFormLabel>

                    <CFormInput
                      v-model="phone"
                      type="tel"
                      autocomplete="tel"
                      placeholder="如：0912345678"
                      :disabled="loading"
                    />
                  </div>
                </CCol>
              </CRow>
            </CCardBody>
          </CCard>

          <!-- 付款 -->
          <div class="d-flex justify-content-end gap-2 mb-4">
            <CButton
              color="danger"
              :disabled="loading"
              @click="showCancelModal"
            >
              取消交易
            </CButton>

            <CButton
              color="primary"
              :disabled="loading"
              @click="submitPayment"
            >
              {{
                loading
                  ? '付款處理中...'
                  : '確認付款'
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

    <!-- 取消交易確認 -->
    <CModal
      :visible="cancelVisible"
      @close="cancelVisible = false"
    >
      <CModalHeader class="border-0">
        <CModalTitle>
          取消交易
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        確定要取消此次交易嗎？
      </CModalBody>

      <CModalFooter class="border-0">
        <CButton
          color="secondary"
          @click="cancelVisible = false"
        >
          返回
        </CButton>

        <CButton
          color="danger"
          @click="cancelPayment"
        >
          取消交易
        </CButton>
      </CModalFooter>
    </CModal>

    <!-- 付款成功 -->
    <CModal
      :visible="paymentSuccessVisible"
      @close="paymentSuccessVisible = false"
    >
      <CModalHeader class="border-0">
        <CModalTitle>
          付款成功
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        付款已完成，可查看訂單
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
  getCustomerOrder,
  payCustomerCreditCard
} from '../../api/customer.js'

const route = useRoute()
const router = useRouter()

const storeId = route.params.storeId
const orderId = route.params.orderId

const home = ref(null)
const order = ref(null)

const cardNumber = ref('')
const expiryDate = ref('')
const phone = ref('')

const loading = ref(false)

const errorMessage = ref('')
const errorVisible = ref(false)

const cancelVisible = ref(false)

const paymentSuccessVisible = ref(false)

const shippingFee = computed(() => {
  return Number(order.value?.shipping_fee || 0)
})

// 商品金額
const productAmount = computed(() => {
  return (order.value?.items || []).reduce(
    (total, item) =>
      total +
      Number(item.price) *
      Number(item.quantity),
    0
  )
})

// 總金額
const totalAmount = computed(() => {
  return Number(
    order.value?.total_amount ??
    productAmount.value + shippingFee.value
  )
})

function showError(message) {
  errorMessage.value = message
  errorVisible.value = true
}

function showCancelModal() {
  cancelVisible.value = true
}

function cancelPayment() {
  cancelVisible.value = false

  router.push(
    `/store-${storeId}/order_list`
  )
}

function goOrderDetail() {
  paymentSuccessVisible.value = false

  router.replace(
    `/store-${storeId}/order/${orderId}`
  )
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
      '取得首頁資料失敗'
    )
  }
}

async function loadOrder() {
  try {
    const data =
      await getCustomerOrder(
        storeId,
        orderId
      )

    order.value =
      data.order

    if (!order.value) {
      showError(
        '找不到訂單資料'
      )

      return
    }

    if (
      order.value.order_status !==
      'pending'
    ) {
      showError(
        '此訂單目前無法付款'
      )
    }
  } catch (error) {
    console.error(
      '取得訂單資料失敗:',
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
      '取得訂單資料失敗'
    )
  }
}

function validatePayment() {
  const cleanCardNumber =
    cardNumber.value.replace(/\s/g, '')

  if (!cleanCardNumber) {
    showError(
      '請輸入信用卡號'
    )

    return false
  }

  if (!/^\d{13,19}$/.test(cleanCardNumber)) {
    showError(
      '信用卡號格式錯誤'
    )

    return false
  }

  if (!expiryDate.value.trim()) {
    showError(
      '請輸入信用卡有效期限'
    )

    return false
  }

  if (
    !/^(0[1-9]|1[0-2])\/\d{2}$/.test(
      expiryDate.value.trim()
    )
  ) {
    showError(
      '有效期限格式錯誤，請使用 MM/YY'
    )

    return false
  }

  if (!phone.value.trim()) {
    showError(
      '請輸入手機號碼'
    )

    return false
  }

  return true
}

async function submitPayment() {
  errorMessage.value = ''
  errorVisible.value = false

  if (!validatePayment()) {
    return
  }

  if (!order.value) {
    showError(
      '訂單資料不存在'
    )

    return
  }

  if (
    order.value.order_status !==
    'pending'
  ) {
    showError(
      '此訂單目前無法付款'
    )

    return
  }

  loading.value = true

  try {
    const data =
      await payCustomerCreditCard(
        storeId,
        orderId,
        totalAmount.value,
        cardNumber.value.replace(/\s/g, ''),
        expiryDate.value.trim(),
        phone.value.trim()
      )

    console.log(
      '信用卡付款成功:',
      data
    )

    paymentSuccessVisible.value = true
  } catch (error) {
    console.error(
      '信用卡付款失敗:',
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
      '信用卡付款失敗'
    )
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  if (
    !/^[1-9]\d*$/.test(storeId) ||
    !/^[1-9]\d*$/.test(orderId)
  ) {
    router.replace('/404')
    return
  }

  await loadHome()
  await loadOrder()
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

:deep(.form-control) {
  font-size: 14px;
}
</style>
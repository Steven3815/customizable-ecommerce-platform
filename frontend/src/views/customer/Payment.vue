<template>
  <div>
    <Sidebar />

    <div class="wrapper d-flex flex-column min-vh-100">
      <Header :store="paymentData?.store || {}" />

      <div class="body flex-grow-1">
        <CContainer class="px-4" lg>
          <!-- 標題 -->
          <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h2 class="mb-2">
                  付款
                </h2>

                <div class="mt-4">
                  訂單編號：{{ paymentData?.order?.order_number || '' }}
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

          <!-- 訂單資訊 -->
          <CCard class="mb-4">
            <CCardBody>
              <h4 class="mb-4">
                訂單資訊
              </h4>

              <div class="d-flex justify-content-between mb-3">
                <span>
                  配送方式
                </span>

                <span>
                  {{
                    deliveryMethodNames[
                      paymentData?.order?.delivery_method
                    ] ||
                    paymentData?.order?.delivery_method ||
                    ''
                  }}
                </span>
              </div>

              <div class="d-flex justify-content-between mb-3">
                <span>
                  商品金額
                </span>

                <span>
                  $ {{ Number(paymentData?.order?.product_amount || 0).toLocaleString() }}
                </span>
              </div>

              <div class="d-flex justify-content-between mb-3">
                <span>
                  運費
                </span>

                <span>
                  $ {{ Number(paymentData?.order?.shipping_fee || 0).toLocaleString() }}
                </span>
              </div>

              <hr>

              <div class="d-flex justify-content-between">
                <span class="fw-bold">
                  總金額
                </span>

                <span class="fw-bold">
                  $ {{ Number(paymentData?.order?.total_amount || 0).toLocaleString() }}
                </span>
              </div>
            </CCardBody>
          </CCard>

          <!-- 付款方式 -->
          <CCard class="mb-4">
            <CCardBody>
              <h4 class="mb-4">
                付款方式
              </h4>

              <CFormCheck
                v-for="method in paymentData?.payment_methods || []"
                :key="method.payment_method_id"
                type="radio"
                name="paymentMethod"
                :id="`payment-${method.payment_method}`"
                :value="method.payment_method"
                v-model="selectedPaymentMethod"
                :label="
                  paymentMethodNames[method.payment_method] ||
                  method.payment_method
                "
                class="mb-3"
              />

              <div
                v-if="!paymentData?.payment_methods?.length"
                class="text-body-secondary"
              >
                目前沒有可用的付款方式
              </div>
            </CCardBody>
          </CCard>

          <!-- 付款狀態 -->
          <CCard
            v-if="paymentData?.payment"
            class="mb-4"
          >
            <CCardBody>
              <h4 class="mb-4">
                付款狀態
              </h4>

              <div class="d-flex justify-content-between mb-3">
                <span>
                  付款方式
                </span>

                <span>
                  {{
                    paymentMethodNames[
                      paymentData.payment.payment_method
                    ] ||
                    paymentData.payment.payment_method ||
                    ''
                  }}
                </span>
              </div>

              <div class="d-flex justify-content-between">
                <span>
                  付款狀態
                </span>

                <span>
                  {{ paymentStatusText }}
                </span>
              </div>
            </CCardBody>
          </CCard>

          <!-- 前往付款 -->
          <div class="d-flex justify-content-end mb-4">
            <CButton
              color="primary"
              :disabled="
                loading ||
                !selectedPaymentMethod ||
                paymentData?.payment?.payment_status === 'paid' ||
                paymentData?.payment?.payment_status === 'processing'
              "
              @click="submitPayment"
            >
              {{
                loading
                  ? '處理中...'
                  : '確認付款方式'
              }}
            </CButton>
          </div>

          <!-- 錯誤 -->
          <CAlert
            v-if="errorMessage"
            color="danger"
            class="mb-4"
          >
            {{ errorMessage }}
          </CAlert>
        </CContainer>
      </div>

      <Footer :footer="paymentData?.footer || {}" />
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
  CAlert,
  CButton,
  CCard,
  CCardBody,
  CContainer,
  CFormCheck
} from '@coreui/vue'

import Sidebar from '../../components/customer_homepage/Sidebar.vue'
import Header from '../../components/customer_homepage/Header.vue'
import Footer from '../../components/customer_homepage/Footer.vue'
import Createdby from '../../components/customer_homepage/Createdby.vue'

import {
  getCustomerPayment,
  createCustomerPayment
} from '../../api/customer.js'

const route = useRoute()
const router = useRouter()

const storeId = route.params.storeId
const orderId = route.params.orderId

const paymentData = ref(null)
const selectedPaymentMethod = ref('')
const loading = ref(false)
const errorMessage = ref('')

const paymentMethodNames = {
  credit_card: '信用卡',
  atm: 'ATM 轉帳',
  post_office: '郵局轉帳',
  cash_on_delivery: '貨到付款',
  in_store: '店內付款'
}

const deliveryMethodNames = {
  home_delivery: '宅配',
  convenience_store: '超商取貨',
  store_pickup: '門市自取'
}

const paymentStatusText = computed(() => {
  if (!paymentData.value?.payment) {
    return '尚未建立付款'
  }

  const status =
    paymentData.value.payment.payment_status

  const confirmStatus =
    paymentData.value.payment.payment_confirm_status

  if (
    status === 'paid' &&
    confirmStatus === 'confirmed'
  ) {
    return '已付款'
  }

  if (
    status === 'processing' &&
    confirmStatus === 'waiting'
  ) {
    return '等待商店確認'
  }

  if (
    status === 'pending' &&
    confirmStatus === 'waiting'
  ) {
    return '待付款'
  }

  return status || '未知'
})

function goBack() {
  router.back()
}

async function loadPayment() {
  errorMessage.value = ''

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

    if (
      error.status === 401
    ) {
      router.push('/401')
      return
    }

    if (
      error.status === 404
    ) {
      router.push('/404')
      return
    }

    errorMessage.value =
      error.message ||
      '取得付款資料失敗'
  }
}

async function submitPayment() {
  if (!selectedPaymentMethod.value) {
    errorMessage.value =
      '請選擇付款方式'

    return
  }

  loading.value = true
  errorMessage.value = ''

  try {
    const data =
      await createCustomerPayment(
        storeId,
        orderId,
        selectedPaymentMethod.value
      )

    console.log(
      '建立付款成功:',
      data
    )

    const nextAction =
      data?.payment?.next_action ||
      data?.next_action

    if (
      nextAction ===
      'credit_card'
    ) {
      router.push(
        `/store-${storeId}/order/${orderId}/payment/credit-card`
      )

      return
    }

    if (
      nextAction ===
      'bank_transfer'
    ) {
      router.push(
        `/store-${storeId}/order/${orderId}/payment/bank-transfer`
      )

      return
    }

    if (
      nextAction ===
      'post_office_transfer'
    ) {
      router.push(
        `/store-${storeId}/order/${orderId}/payment/post-office-transfer`
      )

      return
    }

    if (
      nextAction ===
      'complete_order'
    ) {
      router.push(
        `/store-${storeId}/order/${orderId}`
      )

      return
    }

    await loadPayment()
  } catch (error) {
    console.error(
      '建立付款失敗:',
      error
    )

    if (
      error.status === 401
    ) {
      router.push('/401')
      return
    }

    if (
      error.status === 404
    ) {
      router.push('/404')
      return
    }

    errorMessage.value =
      error.message ||
      '建立付款失敗'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  if (
    !/^[1-9]\d*$/.test(storeId) ||
    !/^[1-9]\d*$/.test(orderId)
  ) {
    router.push('/404')
    return
  }

  await loadPayment()
})
</script>
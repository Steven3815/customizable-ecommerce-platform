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
                  轉帳付款
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

          <!-- 轉帳資訊 -->
          <CCard class="mb-4">
            <CCardBody>
              <h4 class="mb-4">
                {{ paymentMethodTitle }}
              </h4>

              <!-- ATM -->
              <template
                v-if="payment?.payment?.payment_method === 'atm'"
              >
                <CRow class="mb-4">
                  <CCol :md="6">
                    <div class="d-flex align-items-center">
                      <CFormLabel class="mb-0 me-3 text-nowrap">
                        銀行名稱
                      </CFormLabel>

                      <CFormInput
                        :value="payment.payment_account?.bank_name || ''"
                        readonly
                      />
                    </div>
                  </CCol>
                </CRow>

                <CRow>
                  <CCol :md="6">
                    <div class="d-flex align-items-center">
                      <CFormLabel class="mb-0 me-3 text-nowrap">
                        店家銀行帳號
                      </CFormLabel>

                      <CFormInput
                        :value="payment.payment_account?.bank_number || ''"
                        readonly
                      />
                    </div>
                  </CCol>
                </CRow>
              </template>

              <!-- 郵局 -->
              <template
                v-else-if="payment?.payment?.payment_method === 'post_office'"
              >
                <CRow>
                  <CCol :md="6">
                    <div class="d-flex align-items-center">
                      <CFormLabel class="mb-0 me-3 text-nowrap">
                        店家郵局帳號
                      </CFormLabel>

                      <CFormInput
                        :value="payment.payment_account?.post_office_number || ''"
                        readonly
                      />
                    </div>
                  </CCol>
                </CRow>
              </template>
            </CCardBody>
          </CCard>

          <!-- 付款證明 -->
          <CCard class="mb-4">
            <CCardBody>
              <h4 class="mb-4">
                付款證明
              </h4>

              <CRow>
                <CCol :md="6">
                  <div class="d-flex align-items-center">
                    <CFormLabel class="mb-0 me-3 text-nowrap">
                      上傳圖片
                    </CFormLabel>

                    <CFormInput
                      :key="paymentProofInputKey"
                      type="file"
                      accept="image/jpeg,image/png,image/webp"
                      :disabled="loading"
                      @change="handleFileChange"
                    />
                  </div>
                </CCol>
              </CRow>

              <div class="text-body-secondary mt-3">
                付款證明為選填，完成轉帳後可上傳轉帳截圖
              </div>

              <div
                v-if="paymentProofImage"
                class="text-body-secondary mt-2"
              >
                已選擇：{{ paymentProofImage.name }}
              </div>

              <!-- 圖片預覽 -->
              <div
                v-if="paymentProofPreview"
                class="mt-4"
              >
                <CFormLabel>
                  圖片預覽
                </CFormLabel>

                <div class="payment-proof-preview mt-2">
                  <CImage
                    :src="paymentProofPreview"
                    fluid
                    class="payment-proof-preview-image"
                  />
                </div>

                <div class="mt-2">
                  <CButton
                    color="danger"
                    variant="outline"
                    :disabled="loading"
                    @click="removePaymentProof"
                  >
                    移除圖片
                  </CButton>
                </div>
              </div>
            </CCardBody>
          </CCard>

          <!-- 付款 -->
          <div class="d-flex justify-content-end gap-2 mb-4">
            <CButton
              color="danger"
              :disabled="loading"
              @click="showCancelModal"
            >
              取消付款
            </CButton>

            <CButton
              color="primary"
              :disabled="loading"
              @click="submitPayment"
            >
              {{
                loading
                  ? '處理中...'
                  : '我已完成轉帳'
              }}
            </CButton>
          </div>

        </CContainer>
      </div>

      <!-- Footer -->
      <Footer
        :footer="home?.footer || {}"
      />

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

    <!-- 取消付款確認 -->
    <CModal
      :visible="cancelVisible"
      @close="cancelVisible = false"
    >
      <CModalHeader class="border-0">
        <CModalTitle>
          取消付款
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        確定要取消此次付款嗎？
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
          取消付款
        </CButton>
      </CModalFooter>
    </CModal>

    <!-- 轉帳確認已送出 -->
    <CModal
      :visible="paymentSuccessVisible"
      @close="paymentSuccessVisible = false"
    >
      <CModalHeader class="border-0">
        <CModalTitle>
          轉帳確認已送出
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        已送出轉帳確認，等待商家確認付款
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
  onBeforeUnmount,
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
  CImage,
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
  getCustomerTransferPayment,
  submitCustomerTransferPayment
} from '../../api/customer.js'

import { useCartStore } from '../../stores/cart.js'

const route = useRoute()
const router = useRouter()

const storeId = route.params.storeId
const orderId = route.params.orderId

const cartStore = useCartStore()

const home = ref(null)
const payment = ref(null)

const paymentProofImage = ref(null)
const paymentProofPreview = ref(null)
const paymentProofInputKey = ref(0)

const loading = ref(false)

const errorMessage = ref('')
const errorVisible = ref(false)

const cancelVisible = ref(false)

const paymentSuccessVisible = ref(false)

const shippingFee = computed(() => {
  return Number(
    payment.value?.order?.shipping_fee || 0
  )
})

// 商品金額
const productAmount = computed(() => {
  return Number(
    payment.value?.order?.product_amount || 0
  )
})

// 總金額
const totalAmount = computed(() => {
  return Number(
    payment.value?.order?.total_amount ||
    productAmount.value + shippingFee.value
  )
})

// 付款方式標題
const paymentMethodTitle = computed(() => {
  if (
    payment.value?.payment?.payment_method ===
    'atm'
  ) {
    return 'ATM 轉帳資訊'
  }

  if (
    payment.value?.payment?.payment_method ===
    'post_office'
  ) {
    return '郵局轉帳資訊'
  }

  return '轉帳資訊'
})

// 取得首頁資料
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

// 更新購物車金額
async function loadCartTotal() {
  await cartStore.loadCartTotal(storeId)
}

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

// 取得轉帳付款資訊
async function loadPayment() {
  try {
    const data =
      await getCustomerTransferPayment(
        orderId
      )

    payment.value = data

    if (!payment.value?.payment) {
      showError(
        '找不到付款資料'
      )

      return
    }

    if (
      payment.value.payment.payment_status !==
      'pending'
    ) {
      showError(
        '此訂單目前無法付款'
      )
    }

  } catch (error) {
    console.error(
      '取得轉帳付款資訊失敗:',
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
      '取得轉帳付款資訊失敗'
    )
  }
}

// 選擇付款證明
function handleFileChange(event) {
  const file =
    event.target.files?.[0] ||
    null

  if (!file) {
    return
  }

  if (paymentProofPreview.value) {
    URL.revokeObjectURL(
      paymentProofPreview.value
    )
  }

  paymentProofImage.value = file

  paymentProofPreview.value =
    URL.createObjectURL(file)
}

// 移除付款證明
function removePaymentProof() {
  if (paymentProofPreview.value) {
    URL.revokeObjectURL(
      paymentProofPreview.value
    )
  }

  paymentProofImage.value = null
  paymentProofPreview.value = null

  paymentProofInputKey.value++
}

// 提交轉帳確認
async function submitPayment() {
  errorMessage.value = ''
  errorVisible.value = false

  if (!payment.value) {
    showError(
      '付款資料不存在'
    )

    return
  }

  if (
    payment.value.payment?.payment_status !==
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
      await submitCustomerTransferPayment(
        orderId,
        paymentProofImage.value
      )

    console.log(
      '轉帳確認成功:',
      data
    )

    await loadCartTotal()

    paymentSuccessVisible.value = true

  } catch (error) {
    console.error(
      '轉帳確認失敗:',
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
      '轉帳確認失敗'
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
  await loadPayment()
  await loadCartTotal()
})

onBeforeUnmount(() => {
  if (paymentProofPreview.value) {
    URL.revokeObjectURL(
      paymentProofPreview.value
    )
  }
})
</script>

<style scoped>
:deep(.form-control) {
  font-size: 14px;
}

.payment-proof-preview {
  width: 100%;
  max-width: 600px;
  max-height: 600px;
  overflow: hidden;
  border-radius: 4px;
}

.payment-proof-preview-image {
  width: 100%;
  max-height: 600px;
  object-fit: contain;
  display: block;
}
</style>
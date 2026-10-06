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
              <h2 class="mb-0">
                申請退款
              </h2>

              <CButton
                color="secondary"
                @click="goBack"
              >
                返回上頁
              </CButton>
            </div>
          </div>

          <!-- 載入訂單中 -->
          <div
            v-if="loadingOrder"
            class="text-center py-5"
          >
            載入中...
          </div>

          <!-- 退款申請 -->
          <CCard
            v-else-if="order && canRefund"
            class="mb-4"
          >
            <CCardBody>
              <h4 class="mb-4">
                訂單編號: {{ order.order_number }}
              </h4>

              <!-- 退款原因 -->
              <CRow class="mb-4">
                <CCol :md="6">
                  <CFormLabel>
                    退款原因
                  </CFormLabel>

                  <CFormSelect
                    v-model="refundReason"
                    :options="refundReasonOptions"
                  />
                </CCol>
              </CRow>

              <!-- 退款說明 -->
              <CRow class="mb-4">
                <CCol>
                  <CFormLabel>
                    退款說明
                  </CFormLabel>

                  <CFormTextarea
                    v-model="refundDescription"
                    rows="8"
                    maxlength="1000"
                    placeholder="請詳細說明退款原因"
                  />

                  <div class="text-body-secondary mt-2">
                    {{ refundDescription.length }} / 1000
                  </div>
                </CCol>
              </CRow>

              <!-- 圖片 -->
              <CRow>
                <CCol :md="6">
                  <CFormLabel>
                    退款圖片
                  </CFormLabel>

                  <CFormInput
                    :key="imageInputKey"
                    type="file"
                    accept="image/*"
                    @change="handleImageChange"
                  />

                  <div class="text-body-secondary mt-2">
                    圖片為選填
                  </div>

                  <!-- 圖片預覽 -->
                  <div
                    v-if="imagePreview"
                    class="mt-3"
                  >
                    <img
                      :src="imagePreview"
                      alt="退款圖片預覽"
                      class="image-preview"
                    >

                    <div class="mt-4">
                      <CButton
                        color="danger"
                        variant="outline"
                        @click="clearImage"
                      >
                        清除圖片
                      </CButton>
                    </div>
                  </div>
                </CCol>
              </CRow>

            </CCardBody>
          </CCard>

          <!-- 無法申請退款 -->
          <CCard
            v-else-if="order && !canRefund"
            class="mb-4"
          >
            <CCardBody>
              <h4 class="mb-4">
                訂單編號: {{ order.order_number }}
              </h4>

              <div class="text-danger">
                {{ refundUnavailableMessage }}
              </div>
            </CCardBody>
          </CCard>

          <!-- 建立退款申請 -->
          <div
            v-if="order && canRefund"
            class="d-flex justify-content-end mb-4"
          >
            <CButton
              color="primary"
              :disabled="loading || loadingOrder"
              @click="submitRefund"
            >
              {{
                loading
                  ? '處理中...'
                  : '送出申請'
              }}
            </CButton>
          </div>

        </CContainer>
      </div>

      <Footer :footer="home?.footer || {}" />
      <Createdby />
    </div>

    <!-- 未登入 -->
    <LoginRequireModal
      :visible="showLoginModal"
      :store-id="storeId"
      @close="showLoginModal = false"
    />

    <!-- 錯誤提示 -->
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

    <!-- 建立成功 -->
    <CModal
      :visible="refundSuccessVisible"
      @close="refundSuccessVisible = false"
    >
      <CModalHeader class="border-0">
        <CModalTitle>
          退款申請成功
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        您的退款申請已送出，請等待商家處理。
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

    <!-- 無法申請退款 -->
    <CModal
      :visible="refundUnavailableVisible"
      @close="closeRefundUnavailableModal"
    >
      <CModalHeader class="border-0">
        <CModalTitle>
          無法申請退款
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        {{ refundUnavailableMessage }}
      </CModalBody>

      <CModalFooter class="border-0">
        <CButton
          color="primary"
          @click="closeRefundUnavailableModal"
        >
          確定
        </CButton>
      </CModalFooter>
    </CModal>

  </div>
</template>

<script setup>
import {
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
  CFormTextarea,
  CModal,
  CModalBody,
  CModalFooter,
  CModalHeader,
  CModalTitle,
  CRow
} from '@coreui/vue'

import Sidebar from '../../../components/customer_homepage/Sidebar.vue'
import Header from '../../../components/customer_homepage/Header.vue'
import Footer from '../../../components/customer_homepage/Footer.vue'
import Createdby from '../../../components/customer_homepage/Createdby.vue'
import LoginRequireModal from '../../../components/customer/LoginRequireModal.vue'

import {
  getCustomerHome,
  getCustomerLoginStatus,
  getCustomerOrder,
  getCustomerRefunds,
  createCustomerRefund
} from '../../../api/customer.js'

const route = useRoute()
const router = useRouter()

const storeId = route.params.storeId
const orderId = Number(route.query.orderId)

const home = ref(null)
const order = ref(null)

const refundReason = ref('')
const refundDescription = ref('')

const refundImage = ref(null)
const imagePreview = ref('')
const imageInputKey = ref(0)

const loadingOrder = ref(true)
const loading = ref(false)

const refundId = ref(null)

const errorMessage = ref('')
const errorVisible = ref(false)

const refundSuccessVisible = ref(false)

const canRefund = ref(true)

// 退款功能是否開啟
const refundEnable = ref(false)

const refundUnavailableVisible = ref(false)
const refundUnavailableMessage = ref('')

const showLoginModal = ref(false)

const refundReasonOptions = [
  {
    label: '請選擇退款原因',
    value: ''
  },
  {
    label: '商品瑕疵',
    value: 'product_defect'
  },
  {
    label: '商品與描述不符',
    value: 'product_not_as_described'
  },
  {
    label: '商品損壞',
    value: 'product_damaged'
  },
  {
    label: '商品缺少或錯誤',
    value: 'product_missing_or_wrong'
  },
  {
    label: '其他',
    value: 'other'
  }
]

function showError(message) {
  errorMessage.value = message
  errorVisible.value = true
}

function showRefundUnavailable(message) {
  canRefund.value = false
  refundUnavailableMessage.value = message
  refundUnavailableVisible.value = true
}

// 返回上頁
function goBack() {
  router.back()
}

// 關閉無法申請退款 Modal
function closeRefundUnavailableModal() {
  refundUnavailableVisible.value = false
}

function goOrderDetail() {
  refundSuccessVisible.value = false

  router.replace(
    `/store-${storeId}/order/${orderId}`
  )
}

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

    if (error.status === 401) {
      router.replace('/401')
      return
    }

    router.replace('/404')
  }
}

// 檢查登入狀態
async function checkLoginStatus() {
  try {
    const data =
      await getCustomerLoginStatus()

    if (data.loggedIn !== true) {
      showLoginModal.value = true
      loadingOrder.value = false
      return false
    }

    return true

  } catch (error) {
    console.error(
      '取得登入狀態失敗:',
      error
    )

    showLoginModal.value = true
    loadingOrder.value = false

    return false
  }
}

// 取得訂單
async function loadOrder() {
  loadingOrder.value = true

  const loggedIn =
    await checkLoginStatus()

  if (!loggedIn) {
    return
  }

  try {
    // 先確認退款功能是否開啟
    const refundData =
      await getCustomerRefunds(storeId)

    refundEnable.value =
      Number(refundData.refund_enable) === 1

    if (!refundEnable.value) {
      showRefundUnavailable(
        '目前無開啟退款功能'
      )

      return
    }

    const data =
      await getCustomerOrder(
        storeId,
        orderId
      )

    order.value =
      data.order || null

    if (!order.value) {
      router.replace('/404')
      return
    }

    // 確認訂單已送達
    if (
      order.value.delivery_status !==
      'completed'
    ) {
      showRefundUnavailable(
        '訂單尚未送達，無法申請退款'
      )

      return
    }

  } catch (error) {
    console.error(
      '取得訂單失敗:',
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
      '取得訂單失敗'
    )

  } finally {
    loadingOrder.value = false
  }
}

// 選擇圖片
function handleImageChange(event) {
  const file = event.target.files[0]

  refundImage.value = file || null

  if (imagePreview.value) {
    URL.revokeObjectURL(imagePreview.value)
    imagePreview.value = ''
  }

  if (file) {
    imagePreview.value =
      URL.createObjectURL(file)
  }
}

// 清除圖片
function clearImage() {
  refundImage.value = null

  if (imagePreview.value) {
    URL.revokeObjectURL(imagePreview.value)
    imagePreview.value = ''
  }

  imageInputKey.value++
}

// 驗證退款資料
function validateRefund() {
  // 再次確認退款功能
  if (!refundEnable.value) {
    showRefundUnavailable(
      '目前無開啟退款功能'
    )

    return false
  }

  if (!refundReason.value) {
    showError(
      '請選擇退款原因'
    )

    return false
  }

  if (!refundDescription.value.trim()) {
    showError(
      '請輸入退款說明'
    )

    return false
  }

  if (refundDescription.value.trim().length > 1000) {
    showError(
      '退款說明不可超過 1000 個字'
    )

    return false
  }

  return true
}

// 建立退款申請
async function submitRefund() {
  errorMessage.value = ''
  errorVisible.value = false

  // 退款功能未開啟時禁止建立
  if (!refundEnable.value || !canRefund.value) {
    showRefundUnavailable(
      '目前無開啟退款功能'
    )

    return
  }

  if (!validateRefund()) {
    return
  }

  loading.value = true

  try {
    const data =
      await createCustomerRefund(
        orderId,
        refundReason.value,
        refundDescription.value.trim(),
        refundImage.value
      )

    refundId.value =
      data.refund.refund_id

    console.log(
      '建立退款申請成功:',
      data
    )

    refundSuccessVisible.value = true

  } catch (error) {
    console.error(
      '建立退款申請失敗:',
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

    if (error.status === 403) {
      showError(
        error.message ||
        '目前無法申請退款'
      )

      return
    }

    if (error.status === 409) {
      showError(
        error.message ||
        '目前無法申請退款'
      )

      return
    }

    showError(
      error.message ||
      '建立退款申請失敗'
    )

  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  if (
    !/^[1-9]\d*$/.test(storeId) ||
    !Number.isInteger(orderId) ||
    orderId <= 0
  ) {
    router.replace('/404')
    return
  }

  await loadHome()

  if (errorVisible.value) {
    return
  }

  await loadOrder()
})
</script>

<style scoped>
.image-preview {
  width: 200px;
  max-height: 200px;
  object-fit: contain;
  border-radius: 6px;
}

:deep(.form-control),
:deep(.form-select) {
  font-size: 14px;
}
</style>
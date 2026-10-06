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
                建立客服
              </h2>

              <CButton
                color="secondary"
                @click="goBack"
              >
                返回上頁
              </CButton>
            </div>
          </div>

          <!-- 載入中 -->
          <div
            v-if="checkingService"
            class="text-center py-5"
          >
            載入中...
          </div>

          <!-- 客服案件 -->
          <CCard
            v-else-if="canCreateService"
            class="mb-4"
          >
            <CCardBody>
              <h4 class="mb-4">
                客服案件
              </h4>

              <!-- 問題類型 -->
              <CRow class="mb-4">
                <CCol :md="6">
                  <div class="d-flex align-items-center">
                    <CFormLabel class="mb-0 me-3 text-nowrap">
                      問題類型
                    </CFormLabel>

                    <CFormSelect
                      v-model="problemType"
                      :options="problemTypeOptions"
                    />
                  </div>
                </CCol>
              </CRow>

              <!-- 關聯訂單 -->
              <CRow class="mb-4">
                <CCol :md="6">
                  <div class="d-flex align-items-center">
                    <CFormLabel class="mb-0 me-3 text-nowrap">
                      關聯訂單
                    </CFormLabel>

                    <CFormSelect
                      v-model="orderId"
                      :options="orderOptions"
                    />
                  </div>
                </CCol>
              </CRow>

              <!-- 問題描述 -->
              <CRow class="mb-4">
                <CCol>
                  <CFormLabel>
                    問題描述
                  </CFormLabel>

                  <CFormTextarea
                    v-model="description"
                    rows="8"
                    maxlength="1000"
                    placeholder="請詳細描述您遇到的問題"
                  />

                  <div class="text-body-secondary mt-2">
                    {{ description.length }} / 1000
                  </div>
                </CCol>
              </CRow>

              <!-- 圖片 -->
              <CRow>
                <CCol :md="6">
                  <CFormLabel>
                    問題圖片
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
                      alt="客服圖片預覽"
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

          <!-- 無法建立客服 -->
          <CCard
            v-else
            class="mb-4"
          >
            <CCardBody>
              <h4 class="mb-4">
                無法建立客服
              </h4>

              <div class="text-body-secondary">
                {{ serviceUnavailableMessage }}
              </div>
            </CCardBody>
          </CCard>

          <!-- 建立客服案件 -->
          <div
            v-if="canCreateService"
            class="d-flex justify-content-end mb-4"
          >
            <CButton
              color="primary"
              :disabled="loading || checkingService"
              @click="submitService"
            >
              {{
                loading
                  ? '處理中...'
                  : '建立客服案件'
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

    <!-- 建立成功 -->
    <CModal
      :visible="serviceSuccessVisible"
      @close="serviceSuccessVisible = false"
    >
      <CModalHeader class="border-0">
        <CModalTitle>
          客服案件建立成功
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        您的客服案件已建立，請等待客服人員處理
      </CModalBody>

      <CModalFooter class="border-0">
        <CButton
          color="primary"
          @click="goServiceDetail"
        >
          查看客服案件
        </CButton>
      </CModalFooter>
    </CModal>

    <!-- 無法建立客服 -->
    <CModal
      :visible="serviceUnavailableVisible"
      @close="closeServiceUnavailableModal"
    >
      <CModalHeader class="border-0">
        <CModalTitle>
          無法建立客服
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        {{ serviceUnavailableMessage }}
      </CModalBody>

      <CModalFooter class="border-0">
        <CButton
          color="primary"
          @click="closeServiceUnavailableModal"
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
  getCustomerOrders,
  getCustomerServices,
  createCustomerService
} from '../../../api/customer.js'

const route = useRoute()
const router = useRouter()

const storeId = route.params.storeId

const home = ref(null)
const orders = ref([])

const problemType = ref('')
const orderId = ref('')
const description = ref('')

const serviceImage = ref(null)
const imagePreview = ref('')
const imageInputKey = ref(0)

const serviceId = ref(null)

const loading = ref(false)
const checkingService = ref(true)

const errorMessage = ref('')
const errorVisible = ref(false)

const serviceSuccessVisible = ref(false)

const canCreateService = ref(true)

// 客服功能是否開啟
const customerServiceEnable = ref(false)

const serviceUnavailableVisible = ref(false)
const serviceUnavailableMessage = ref('')

const showLoginModal = ref(false)

const problemTypeOptions = [
  {
    label: '請選擇問題類型',
    value: ''
  },
  {
    label: '商品問題',
    value: 'product'
  },
  {
    label: '訂單問題',
    value: 'order'
  },
  {
    label: '付款問題',
    value: 'payment'
  },
  {
    label: '配送問題',
    value: 'delivery'
  },
  {
    label: '退款問題',
    value: 'refund'
  },
  {
    label: '其他問題',
    value: 'other'
  }
]

const orderOptions = ref([
  {
    label: '無',
    value: ''
  }
])

function showError(message) {
  errorMessage.value = message
  errorVisible.value = true
}

function showServiceUnavailable(message) {
  canCreateService.value = false
  serviceUnavailableMessage.value = message
  serviceUnavailableVisible.value = true
}

function goBack() {
  router.back()
}

// 關閉無法建立客服 Modal
function closeServiceUnavailableModal() {
  serviceUnavailableVisible.value = false
}

function goServiceDetail() {
  serviceSuccessVisible.value = false

  router.replace(
    `/store-${storeId}/service/${serviceId.value}`
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
      checkingService.value = false
      return false
    }

    return true

  } catch (error) {
    console.error(
      '取得登入狀態失敗:',
      error
    )

    showLoginModal.value = true
    checkingService.value = false

    return false
  }
}

// 檢查客服功能與是否已有處理中的客服案件
async function checkPendingService() {
  checkingService.value = true

  try {
    const data =
      await getCustomerServices(
        storeId,
        'pending'
      )

    // 判斷店家是否開啟客服功能
    customerServiceEnable.value =
      Number(data.customer_service_enable) === 1

    // 未開啟客服功能
    if (!customerServiceEnable.value) {
      showServiceUnavailable(
        '目前無開啟客服功能'
      )

      return
    }

    // 檢查是否已有處理中的客服案件
    const pendingServices =
      data.services || []

    if (pendingServices.length > 0) {
      showServiceUnavailable(
        '您目前已有處理中的客服案件，請等待案件處理完成後再建立新的案件'
      )
    }

  } catch (error) {
    console.error(
      '檢查客服案件失敗:',
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
      '檢查客服案件失敗'
    )

  } finally {
    checkingService.value = false
  }
}

// 取得訂單
async function loadOrders() {
  try {
    const data =
      await getCustomerOrders(
        storeId,
        'all',
        'all',
        'all',
        'all',
        'all'
      )

    orders.value =
      data.orders || []

    orderOptions.value = [
      {
        label: '無',
        value: ''
      },
      ...orders.value.map(order => ({
        label: `訂單 #${order.order_number}`,
        value: String(order.order_id)
      }))
    ]

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
  }
}

// 選擇圖片
function handleImageChange(event) {
  const file = event.target.files[0]

  serviceImage.value = file || null

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
  serviceImage.value = null

  if (imagePreview.value) {
    URL.revokeObjectURL(imagePreview.value)
    imagePreview.value = ''
  }

  imageInputKey.value++
}

function validateService() {
  // 再次確認客服功能是否開啟
  if (!customerServiceEnable.value) {
    showServiceUnavailable(
      '目前無開啟客服功能'
    )

    return false
  }

  // 確認目前可以建立客服
  if (!canCreateService.value) {
    showServiceUnavailable(
      '您目前已有處理中的客服案件，請等待案件處理完成後再建立新的案件'
    )

    return false
  }

  if (!problemType.value) {
    showError(
      '請選擇問題類型'
    )

    return false
  }

  if (!description.value.trim()) {
    showError(
      '請輸入問題描述'
    )

    return false
  }

  if (description.value.trim().length > 1000) {
    showError(
      '問題描述不可超過 1000 個字'
    )

    return false
  }

  return true
}

async function submitService() {
  errorMessage.value = ''
  errorVisible.value = false

  if (!validateService()) {
    return
  }

  loading.value = true

  try {
    const formData = new FormData()

    formData.append(
      'store_id',
      storeId
    )

    formData.append(
      'problem_type',
      problemType.value
    )

    formData.append(
      'description',
      description.value.trim()
    )

    if (orderId.value) {
      formData.append(
        'order_id',
        orderId.value
      )
    }

    if (serviceImage.value) {
      formData.append(
        'service_image',
        serviceImage.value
      )
    }

    const data =
      await createCustomerService(
        formData
      )

    serviceId.value =
      data.service.service_id

    console.log(
      '建立客服案件成功:',
      data
    )

    serviceSuccessVisible.value = true

  } catch (error) {
    console.error(
      '建立客服案件失敗:',
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

    if (error.status === 409) {
      showServiceUnavailable(
        '您目前已有處理中的客服案件，請等待案件處理完成後再建立新的案件'
      )

      return
    }

    showError(
      error.message ||
      '建立客服案件失敗'
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

  if (errorVisible.value) {
    return
  }

  const loggedIn =
    await checkLoginStatus()

  if (!loggedIn) {
    return
  }

  await checkPendingService()

  if (errorVisible.value) {
    return
  }

  // 只有客服功能開啟且沒有 pending 案件才取得訂單
  if (!canCreateService.value) {
    return
  }

  await loadOrders()
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
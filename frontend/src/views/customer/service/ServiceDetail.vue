<template>
  <div>
    <Sidebar />

    <div class="wrapper d-flex flex-column min-vh-100">
      <Header :store="home?.store || {}" />

      <div class="body flex-grow-1">
        <CContainer class="px-4" lg>

          <!-- 標題 -->
          <div class="page-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h2 class="mb-2">
                  客服案件
                </h2>

                <!-- 客服功能未開啟 -->
                <div
                  v-if="!customerServiceEnable"
                  class="text-danger"
                >
                  目前無開啟客服功能
                </div>
              </div>

              <CButton
                color="secondary"
                @click="goBack"
              >
                返回客服列表
              </CButton>
            </div>
          </div>

          <!-- 載入中 -->
          <div
            v-if="loading"
            class="text-center py-5"
          >
            載入中...
          </div>

          <!-- 錯誤 -->
          <CAlert
            v-else-if="error"
            color="danger"
          >
            {{ error }}
          </CAlert>

          <template v-else-if="service">

            <!-- 客服案件資訊 -->
            <CCard class="service-card mb-4">
              <CCardBody>

                <div class="section-title">
                  <h5 class="mb-0">
                    案件資訊
                  </h5>
                </div>

                <div class="service-info-list">

                  <!-- 案件編號 -->
                  <div class="service-info-item">
                    <span class="service-info-label">
                      案件編號
                    </span>

                    <span class="service-info-value service-number">
                      CS{{ String(service.service_id).padStart(6, '0') }}
                    </span>
                  </div>

                  <!-- 案件狀態 -->
                  <div class="service-info-item">
                    <span class="service-info-label">
                      案件狀態
                    </span>

                    <span
                      class="service-info-value"
                      :class="{
                        'text-danger fw-bold':
                          service.status === 'pending',
                        'text-success fw-bold':
                          service.status === 'resolved'
                      }"
                    >
                      {{ getServiceStatusText(service.status) }}
                    </span>
                  </div>

                  <!-- 問題類型 -->
                  <div class="service-info-item">
                    <span class="service-info-label">
                      問題類型
                    </span>

                    <span class="service-info-value">
                      {{ getProblemTypeText(service.problem_type) }}
                    </span>
                  </div>

                  <!-- 建立時間 -->
                  <div class="service-info-item">
                    <span class="service-info-label">
                      建立時間
                    </span>

                    <span class="service-info-value">
                      {{ service.created_at }}
                    </span>
                  </div>

                  <hr>

                  <!-- 問題描述 -->
                  <div class="service-info-item">
                    <span class="service-info-label">
                      問題描述
                    </span>

                    <span class="service-info-value service-description">
                      {{ service.description }}
                    </span>
                  </div>

                  <!-- 附件圖片 -->
                  <div
                    v-if="service.image_url"
                    class="service-info-item"
                  >
                    <span class="service-info-label">
                      附件圖片
                    </span>

                    <div class="service-info-value">
                      <div class="service-image">
                        <img
                          :src="getImageUrl(service.image_url)"
                          alt="客服案件附件"
                        >
                      </div>
                    </div>
                  </div>

                </div>

                <!-- 刪除案件 -->
                <div
                  v-if="service.status === 'pending'"
                  class="service-action"
                >
                  <CButton
                    color="danger"
                    @click="showDeleteModal = true"
                  >
                    刪除案件
                  </CButton>
                </div>

              </CCardBody>
            </CCard>

            <!-- 訂單資訊 -->
            <CCard
              v-if="service.order"
              class="service-card mb-4"
            >
              <CCardBody>

                <div class="section-title">
                  <h5 class="mb-0">
                    關聯訂單
                  </h5>
                </div>

                <div class="service-info-list">

                  <!-- 訂單編號 -->
                  <div class="service-info-item">
                    <span class="service-info-label">
                      訂單編號
                    </span>

                    <span class="service-info-value service-number">
                      {{ service.order.order_number }}
                    </span>
                  </div>

                  <!-- 訂單金額 -->
                  <div class="service-info-item">
                    <span class="service-info-label">
                      訂單金額
                    </span>

                    <span class="service-info-value">
                      $ {{ Number(service.order.total_amount).toLocaleString() }}
                    </span>
                  </div>

                  <!-- 配送方式 -->
                  <div class="service-info-item">
                    <span class="service-info-label">
                      配送方式
                    </span>

                    <span class="service-info-value">
                      {{ getDeliveryMethodText(service.order.delivery_method) }}
                    </span>
                  </div>

                  <!-- 配送狀態 -->
                  <div class="service-info-item">
                    <span class="service-info-label">
                      配送狀態
                    </span>

                    <span class="service-info-value">
                      {{ getDeliveryStatusText(service.order.delivery_status) }}
                    </span>
                  </div>

                  <!-- 預計出貨日期 -->
                  <div
                    v-if="service.order.estimated_ship_date"
                    class="service-info-item"
                  >
                    <span class="service-info-label">
                      預計出貨日期
                    </span>

                    <span class="service-info-value">
                      {{ service.order.estimated_ship_date }}
                    </span>
                  </div>

                  <!-- 預計送達日期 -->
                  <div
                    v-if="service.order.estimated_arrival_date"
                    class="service-info-item"
                  >
                    <span class="service-info-label">
                      預計送達日期
                    </span>

                    <span class="service-info-value">
                      {{ service.order.estimated_arrival_date }}
                    </span>
                  </div>

                </div>

              </CCardBody>
            </CCard>

            <!-- 商家回覆 -->
            <CCard class="service-card mb-4">
              <CCardBody>

                <div class="section-title">
                  <h5 class="mb-0">
                    商家回覆
                  </h5>
                </div>

                <div
                  v-if="service.admin_reply"
                  class="service-reply"
                >
                  {{ service.admin_reply }}
                </div>

                <div
                  v-else
                  class="text-body-secondary"
                >
                  尚無商家回覆
                </div>

              </CCardBody>
            </CCard>

          </template>
        </CContainer>
      </div>

      <!-- Footer -->
      <Footer
        :footer="home?.footer || {}"
        class="mt-auto"
      />

      <Createdby />
    </div>

    <!-- 未登入 -->
    <LoginRequireModal
      :visible="showLoginModal"
      :store-id="storeId"
      @close="showLoginModal = false"
    />

    <!-- 刪除確認 Modal -->
    <CModal
      :visible="showDeleteModal"
      alignment="center"
      @close="showDeleteModal = false"
    >
      <CModalHeader>
        <CModalTitle>
          刪除客服案件
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        確定要刪除此客服案件嗎？
        <br>
        刪除後將無法繼續處理此案件
      </CModalBody>

      <CModalFooter>
        <CButton
          color="secondary"
          @click="showDeleteModal = false"
        >
          取消
        </CButton>

        <CButton
          color="danger"
          :disabled="deleting"
          @click="deleteService"
        >
          {{ deleting ? '刪除中...' : '確認刪除' }}
        </CButton>
      </CModalFooter>
    </CModal>

  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import {
  CAlert,
  CButton,
  CCard,
  CCardBody,
  CContainer,
  CModal,
  CModalBody,
  CModalFooter,
  CModalHeader,
  CModalTitle
} from '@coreui/vue'

import Sidebar from '../../../components/customer_homepage/Sidebar.vue'
import Header from '../../../components/customer_homepage/Header.vue'
import Footer from '../../../components/customer_homepage/Footer.vue'
import Createdby from '../../../components/customer_homepage/Createdby.vue'
import LoginRequireModal from '../../../components/customer/LoginRequireModal.vue'

import {
  getCustomerHome,
  getCustomerLoginStatus,
  getCustomerService,
  deleteCustomerService
} from '../../../api/customer.js'

const route = useRoute()
const router = useRouter()

const storeId = Number(route.params.storeId)
const serviceId = Number(route.params.serviceId)

const loading = ref(true)
const error = ref('')
const service = ref(null)
const home = ref(null)

const showLoginModal = ref(false)

// 客服功能是否開啟
const customerServiceEnable = ref(false)

const showDeleteModal = ref(false)
const deleting = ref(false)

/* 載入首頁資料 */
const loadHome = async () => {
  try {
    home.value = await getCustomerHome(storeId)
  } catch (err) {
    console.error(
      '取得首頁資料失敗:',
      err
    )
  }
}

/* 檢查登入狀態 */
const checkLoginStatus = async () => {
  try {
    const data = await getCustomerLoginStatus()

    if (data.loggedIn !== true) {
      showLoginModal.value = true
      loading.value = false
      return false
    }

    return true

  } catch (err) {
    console.error(
      '取得登入狀態失敗:',
      err
    )

    showLoginModal.value = true
    loading.value = false

    return false
  }
}

/* 載入客服案件 */
const loadService = async () => {
  loading.value = true
  error.value = ''

  const loggedIn = await checkLoginStatus()

  if (!loggedIn) {
    return
  }

  try {
    const data = await getCustomerService(
      storeId,
      serviceId
    )

    // 判斷店家是否開啟客服功能
    customerServiceEnable.value =
      Number(data.customer_service_enable) === 1

    service.value = data
  } catch (err) {
    console.error(
      '取得客服案件失敗:',
      err
    )

    if (err.status === 401) {
      router.replace('/401')
      return
    }

    if (err.status === 404) {
      router.replace('/404')
      return
    }

    error.value =
      err.message || '取得客服案件失敗'
  } finally {
    loading.value = false
  }
}

const deleteService = async () => {
  if (deleting.value) {
    return
  }

  deleting.value = true

  try {
    await deleteCustomerService(
      storeId,
      serviceId
    )

    showDeleteModal.value = false

    router.replace(
      `/store-${storeId}/service_list`
    )
  } catch (err) {
    console.error(
      '取消客服案件失敗:',
      err
    )

    showDeleteModal.value = false

    error.value =
      err.message || '取消客服案件失敗'
  } finally {
    deleting.value = false
  }
}

const goBack = () => {
  router.push(
    `/store-${storeId}/service_list`
  )
}

const getServiceStatusText = (status) => {
  const map = {
    pending: '處理中',
    processing: '處理中',
    resolved: '已處理',
    closed: '已結案'
  }

  return map[status] || status
}

const getProblemTypeText = (type) => {
  const map = {
    product: '商品問題',
    order: '訂單問題',
    payment: '付款問題',
    delivery: '配送問題',
    refund: '退款問題',
    other: '其他問題'
  }

  return map[type] || type
}

const getDeliveryMethodText = (method) => {
  const map = {
    home_delivery: '宅配',
    convenience_store: '超商取貨',
    store_pickup: '門市取貨'
  }

  return map[method] || method
}

const getDeliveryStatusText = (status) => {
  const map = {
    pending: '待出貨',
    shipping: '配送中',
    completed: '已送達'
  }

  return map[status] || status
}

// 圖片網址
const getImageUrl = (url) => {
  if (!url) {
    return null
  }

  if (
    url.startsWith('http://') ||
    url.startsWith('https://')
  ) {
    return url
  }

  return `http://localhost/ecommerce-platform/backend${url}`
}

onMounted(async () => {
  await loadHome()
  await loadService()
})
</script>

<style scoped>
.page-header {
  padding-top: 8px;
}

.service-card {
  border: 1px solid var(--cui-border-color);
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.section-title {
  padding-bottom: 12px;
  margin-bottom: 20px;
  border-bottom: 1px solid var(--cui-border-color);
}

.service-info-list {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.service-info-item {
  display: flex;
  align-items: flex-start;
  gap: 20px;
  padding-top: 2px;
  padding-bottom: 2px;
  line-height: 1.6;
}

.service-info-label {
  width: 110px;
  flex-shrink: 0;
  color: var(--cui-secondary-color);
}

.service-info-value {
  flex: 1;
}

.service-number {
  font-weight: 600;
  letter-spacing: 0.3px;
}

.service-description {
  white-space: pre-wrap;
  word-break: break-word;
}

.service-action {
  display: flex;
  justify-content: flex-end;
  margin-top: 24px;
}

.service-image {
  width: 100%;
  max-width: 500px;
  max-height: 600px;
  overflow: hidden;
  border-radius: 8px;
}

.service-image img {
  width: 100%;
  max-height: 600px;
  object-fit: contain;
  display: block;
  border: 1px solid var(--cui-border-color);
  border-radius: 8px;
}

.service-reply {
  padding: 16px;
  background-color: var(--cui-tertiary-bg);
  border-radius: 8px;
  white-space: pre-wrap;
  word-break: break-word;
  line-height: 1.7;
}

@media (max-width: 576px) {
  .page-header .d-flex {
    align-items: flex-start !important;
    gap: 16px;
  }

  .service-info-item {
    flex-direction: column;
    gap: 4px;
  }

  .service-info-label {
    width: auto;
  }

  .amount-value {
    width: auto;
    text-align: left;
  }

  .service-action {
    justify-content: flex-start;
  }
}
</style>
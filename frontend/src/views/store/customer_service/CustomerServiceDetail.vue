<script setup>
import { ref, onMounted } from 'vue'

import {
  CContainer,
  CCard,
  CCardBody,
  CButton,
  CModal,
  CModalHeader,
  CModalTitle,
  CModalBody,
  CModalFooter
} from '@coreui/vue'

import AppFooter from '../../../components/store/AppFooter.vue'
import AppHeader from '../../../components/store/AppHeader.vue'
import AppSidebar from '../../../components/store/AppSidebar.vue'

import { useRoute, useRouter } from 'vue-router'

import {
  getCustomerService,
  replyCustomerService
} from '../../../api/store'

const route = useRoute()
const router = useRouter()

const service = ref(null)

const loading = ref(true)
const replying = ref(false)

const adminReply = ref('')

const showReplyConfirmModal = ref(false)

const error = ref('')

const showErrorModal = ref(false)
const errorModalMessage = ref('')

// 顯示錯誤 Modal
function showError(message) {
  errorModalMessage.value = message
  showErrorModal.value = true
}

// 關閉錯誤 Modal
function closeErrorModal() {
  showErrorModal.value = false
  errorModalMessage.value = ''
}

// 取得客服案件詳細資料
async function loadCustomerService() {
  loading.value = true
  error.value = ''

  try {
    const serviceId = route.params.serviceId

    const data = await getCustomerService(serviceId)

    service.value = data.service
    adminReply.value = data.service.admin_reply || ''
  } catch (err) {
    console.error('取得客服案件詳細資料失敗:', err)

    if (err.status === 401) {
      error.value = '登入狀態已失效，請重新登入'
    } else if (err.status === 403) {
      error.value = '目前無權限查看此客服案件'
    } else if (err.status === 404) {
      error.value = '找不到此客服案件'
    } else {
      error.value = err.message || '取得客服案件詳細資料失敗'
    }

    showError(error.value)
  } finally {
    loading.value = false
  }
}

// 返回客服案件列表
function goBack() {
  router.push({
    name: 'StoreCustomerServiceList'
  })
}

// 開啟回覆確認 Modal
function openReplyConfirmModal() {
  if (!adminReply.value.trim()) {
    showError('請填寫客服回覆')
    return
  }

  if (adminReply.value.trim().length > 300) {
    showError('客服回覆不可超過 300 字')
    return
  }

  showReplyConfirmModal.value = true
}

// 關閉回覆確認 Modal
function closeReplyConfirmModal() {
  showReplyConfirmModal.value = false
}

// 確認回覆
async function confirmReply() {
  if (!service.value || replying.value) {
    return
  }

  if (!adminReply.value.trim()) {
    closeReplyConfirmModal()
    showError('請填寫客服回覆')
    return
  }

  if (adminReply.value.trim().length > 300) {
    closeReplyConfirmModal()
    showError('客服回覆不可超過 300 字')
    return
  }

  replying.value = true

  try {
    const data = await replyCustomerService(
      service.value.service_id,
      adminReply.value.trim()
    )

    service.value.status = data.status
    service.value.admin_reply = data.admin_reply
    service.value.updated_at = new Date().toISOString()

    adminReply.value = data.admin_reply

    closeReplyConfirmModal()
  } catch (err) {
    console.error('回覆客服案件失敗:', err)

    if (err.status === 401) {
      showError('登入狀態已失效，請重新登入')
    } else if (err.status === 403) {
      showError('目前無權限回覆此客服案件')
    } else if (err.status === 404) {
      showError('找不到此客服案件')
    } else if (err.status === 409) {
      showError('此客服案件已處理，無法再次回覆')
    } else {
      showError(err.message || '回覆客服案件失敗')
    }
  } finally {
    replying.value = false
  }
}

function getServiceStatusText(status) {
  const statusMap = {
    pending: '待處理',
    resolved: '已處理'
  }

  return statusMap[status] || status
}

function getDeliveryStatusText(status) {
  const statusMap = {
    pending: '待出貨',
    shipping: '配送中',
    completed: '已完成'
  }

  return statusMap[status] || status
}

function getDeliveryMethodText(method) {
  const methodMap = {
    home_delivery: '宅配'
  }

  return methodMap[method] || method
}

function formatAmount(amount) {
  return `$${Number(amount || 0).toLocaleString()}`
}

function formatDate(date) {
  if (!date) {
    return '-'
  }

  return date.replace('T', ' ').slice(0, 19)
}

onMounted(() => {
  loadCustomerService()
})
</script>

<template>
  <div>
    <AppSidebar />

    <div class="wrapper d-flex flex-column min-vh-100">
      <AppHeader />

      <div class="body flex-grow-1">
        <CContainer class="px-4" lg>

          <!-- 載入中 -->
          <div
            v-if="loading"
            class="text-center py-5"
          >
            載入中...
          </div>

          <!-- 客服案件不存在 -->
          <div
            v-else-if="!service"
            class="text-center py-5"
          >
            <p class="text-body-secondary mb-3">
              找不到客服案件資料
            </p>

            <CButton
              color="primary"
              @click="goBack"
            >
              返回客服列表
            </CButton>
          </div>

          <div v-else>

            <!-- 標題 + 返回 -->
            <div class="position-relative mb-4">
              <h2 class="mt-2 mb-3">
                客服詳細
              </h2>

              <CButton
                color="secondary"
                class="position-absolute top-0 end-0"
                @click="goBack"
              >
                返回客服列表
              </CButton>
            </div>

            <!-- 客服案件資訊 -->
            <CCard class="mb-4">
              <CCardBody>
                <h4 class="mb-4">
                  客服資訊
                </h4>

                <div class="mb-3">
                  <strong>案件編號</strong>

                  <div class="mt-1">
                    {{ service.service_id }}
                  </div>
                </div>

                <div class="mb-3">
                  <strong>問題類型</strong>

                  <div class="mt-1">
                    {{ service.problem_type || '-' }}
                  </div>
                </div>

                <div class="mb-3">
                  <strong>案件狀態</strong>

                  <div
                    class="mt-1"
                    :class="{ 
                      'text-danger fw-bold': service.status === 'pending',
                      'text-success fw-bold': service.status === 'resolved'
                    }"
                  >
                    {{ getServiceStatusText(service.status) }}
                  </div>
                </div>

                <div class="mb-3">
                  <strong>建立時間</strong>

                  <div class="mt-1">
                    {{ formatDate(service.created_at) }}
                  </div>
                </div>

                <div>
                  <strong>最後更新時間</strong>

                  <div class="mt-1">
                    {{ formatDate(service.updated_at) }}
                  </div>
                </div>
              </CCardBody>
            </CCard>

            <!-- 顧客資訊 -->
            <CCard class="mb-4">
              <CCardBody>
                <h4 class="mb-4">
                  顧客資訊
                </h4>

                <div class="mb-3">
                  <strong>姓名</strong>

                  <div class="mt-1">
                    {{ service.customer?.name || '-' }}
                  </div>
                </div>

                <div>
                  <strong>Email</strong>

                  <div class="mt-1">
                    {{ service.customer?.email || '-' }}
                  </div>
                </div>
              </CCardBody>
            </CCard>

            <!-- 訂單資訊 -->
            <CCard class="mb-4">
              <CCardBody>
                <h4 class="mb-4">
                  訂單資訊
                </h4>

                <template v-if="service.order">

                  <div class="mb-3">
                    <strong>訂單編號</strong>

                    <div class="mt-1">
                      {{ service.order.order_number }}
                    </div>
                  </div>

                  <div class="mb-3">
                    <strong>訂單總金額</strong>

                    <div class="mt-1">
                      {{ formatAmount(service.order.total_amount) }}
                    </div>
                  </div>

                  <div class="mb-3">
                    <strong>配送方式</strong>

                    <div class="mt-1">
                      {{ getDeliveryMethodText(service.order.delivery_method) }}
                    </div>
                  </div>

                  <div>
                    <strong>配送狀態</strong>

                    <div class="mt-1">
                      {{ getDeliveryStatusText(service.order.delivery_status) }}
                    </div>
                  </div>

                </template>

                <div
                  v-else
                  class="text-body-secondary"
                >
                  此客服案件沒有關聯訂單
                </div>
              </CCardBody>
            </CCard>

            <!-- 問題內容 -->
            <CCard class="mb-4">
              <CCardBody>
                <h4 class="mb-4">
                  問題內容
                </h4>

                <div class="mb-3">
                  <strong>問題說明</strong>

                  <div class="mt-1">
                    {{ service.description || '-' }}
                  </div>
                </div>

                <div>
                  <strong>問題圖片</strong>

                  <div class="mt-2">
                    <img
                      v-if="service.image_url"
                      :src="service.image_url"
                      alt="客服問題圖片"
                      class="service-image"
                    >

                    <div
                      v-else
                      class="text-body-secondary"
                    >
                      尚無問題圖片
                    </div>
                  </div>
                </div>
              </CCardBody>
            </CCard>

            <!-- 客服回覆 -->
            <CCard class="mb-4">
              <CCardBody>
                <h4 class="mb-4">
                  客服回覆
                </h4>

                <!-- 待處理 -->
                <template v-if="service.status === 'pending'">

                  <div class="mb-3">
                    <strong>回覆內容</strong>

                    <textarea
                      v-model="adminReply"
                      class="form-control mt-1"
                      rows="5"
                      maxlength="300"
                      placeholder="請輸入客服回覆"
                    ></textarea>
                  </div>

                  <div class="text-body-secondary mb-3">
                    {{ adminReply.length }} / 300
                  </div>

                  <CButton
                    color="primary"
                    :disabled="replying"
                    @click="openReplyConfirmModal"
                  >
                    {{ replying ? '處理中...' : '送出回覆' }}
                  </CButton>

                </template>

                <!-- 已處理 -->
                <template v-else>

                  <div class="mb-3">
                    <strong>管理員回覆</strong>

                    <div class="mt-1">
                      {{ service.admin_reply || '-' }}
                    </div>
                  </div>

                  <div>
                    <strong>處理時間</strong>

                    <div class="mt-1">
                      {{ formatDate(service.updated_at) }}
                    </div>
                  </div>

                </template>
              </CCardBody>
            </CCard>

          </div>
        </CContainer>
      </div>

      <AppFooter />
    </div>

    <!-- 錯誤提示 -->
    <CModal
      :visible="showErrorModal"
      @close="closeErrorModal"
    >
      <CModalHeader>
        <CModalTitle>
          提示
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        {{ errorModalMessage }}
      </CModalBody>

      <CModalFooter class="border-0">
        <CButton
          color="primary"
          @click="closeErrorModal"
        >
          確定
        </CButton>
      </CModalFooter>
    </CModal>

    <!-- 回覆確認提示 -->
    <CModal
      :visible="showReplyConfirmModal"
      @close="closeReplyConfirmModal"
    >
      <CModalHeader>
        <CModalTitle>
          確認回覆
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        是否確認送出客服回覆？送出後將無法修改。
      </CModalBody>

      <CModalFooter class="border-0">
        <CButton
          color="secondary"
          :disabled="replying"
          @click="closeReplyConfirmModal"
        >
          取消
        </CButton>

        <CButton
          color="primary"
          :disabled="replying"
          @click="confirmReply"
        >
          {{ replying ? '處理中...' : '確認' }}
        </CButton>
      </CModalFooter>
    </CModal>

  </div>
</template>

<style scoped>
.service-image {
  max-width: 500px;
  max-height: 500px;
  width: auto;
  height: auto;
  object-fit: contain;
  border: 1px solid var(--cui-border-color);
  border-radius: 4px;
}
</style>
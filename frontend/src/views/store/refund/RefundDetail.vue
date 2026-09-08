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
  CModalFooter,
  CTable,
  CTableHead,
  CTableRow,
  CTableHeaderCell,
  CTableBody,
  CTableDataCell
} from '@coreui/vue'

import AppFooter from '../../../components/store/AppFooter.vue'
import AppHeader from '../../../components/store/AppHeader.vue'
import AppSidebar from '../../../components/store/AppSidebar.vue'

import { useRoute, useRouter } from 'vue-router'

import {
  getRefund,
  updateRefund
} from '../../../api/store'

const route = useRoute()
const router = useRouter()

const refund = ref(null)

const loading = ref(true)
const processing = ref(false)

const refundStatus = ref('')
const adminReply = ref('')

const showProcessConfirmModal = ref(false)

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

// 取得退款詳細資料
async function loadRefund() {
  loading.value = true
  error.value = ''

  try {
    const refundId = route.params.refundId

    const data = await getRefund(refundId)

    refund.value = data.refund
    refundStatus.value = data.refund.refund_status
    adminReply.value = data.refund.admin_reply || ''
  } catch (err) {
    console.error('取得退款詳細資料失敗:', err)

    if (err.status === 401) {
      error.value = '登入狀態已失效，請重新登入'
    } else if (err.status === 403) {
      error.value = '目前無權限查看此退款案件'
    } else if (err.status === 404) {
      error.value = '找不到此退款案件'
    } else {
      error.value = err.message || '取得退款詳細資料失敗'
    }

    showError(error.value)
  } finally {
    loading.value = false
  }
}

// 返回退款列表
function goBack() {
  router.push({
    name: 'StoreRefundList'
  })
}

// 開啟處理確認 Modal
function openProcessConfirmModal(status) {
  if (!refund.value || processing.value) {
    return
  }

  refundStatus.value = status
  showProcessConfirmModal.value = true
}

// 關閉處理確認 Modal
function closeProcessConfirmModal() {
  showProcessConfirmModal.value = false
}

// 確認處理退款
async function confirmProcess() {
  if (!refund.value || processing.value) {
    return
  }

  if (
    refundStatus.value !== 'approved' &&
    refundStatus.value !== 'rejected'
  ) {
    closeProcessConfirmModal()
    showError('退款處理狀態錯誤')
    return
  }

  if (adminReply.value.trim().length > 1000) {
    closeProcessConfirmModal()
    showError('管理員回覆不可超過 1000 字')
    return
  }

  processing.value = true

  try {
    const data = await updateRefund(
      refund.value.refund_id,
      refundStatus.value,
      adminReply.value.trim() || null
    )

    refund.value.refund_status = data.refund_status
    refund.value.admin_reply = data.admin_reply
    refund.value.processed_at = new Date().toISOString()

    refundStatus.value = data.refund_status
    adminReply.value = data.admin_reply || ''

    closeProcessConfirmModal()
  } catch (err) {
    console.error('處理退款失敗:', err)

    if (err.status === 401) {
      showError('登入狀態已失效，請重新登入')
    } else if (err.status === 403) {
      showError('目前無權限處理此退款')
    } else if (err.status === 404) {
      showError('找不到此退款案件')
    } else if (err.status === 409) {
      showError('此退款案件已處理，無法再次處理')
    } else {
      showError(err.message || '處理退款失敗')
    }
  } finally {
    processing.value = false
  }
}

// 退款狀態
function getRefundStatusText(status) {
  const statusMap = {
    pending: '待處理',
    approved: '已核准',
    rejected: '已拒絕'
  }

  return statusMap[status] || status
}

// 付款時間
function formatDate(date) {
  if (!date) {
    return '-'
  }

  return date.replace('T', ' ').slice(0, 19)
}

// 金額
function formatAmount(amount) {
  return `$${Number(amount || 0).toLocaleString()}`
}

// 付款狀態
function getPaymentStatusText(status) {
  const statusMap = {
    pending: '待付款',
    processing: '處理中',
    paid: '已付款',
    failed: '付款失敗'
  }

  return statusMap[status] || status
}

onMounted(() => {
  loadRefund()
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

          <!-- 退款不存在 -->
          <div
            v-else-if="!refund"
            class="text-center py-5"
          >
            <p class="text-body-secondary mb-3">
              找不到退款資料
            </p>

            <CButton
              color="primary"
              @click="goBack"
            >
              返回退款列表
            </CButton>
          </div>

          <div v-else>

            <!-- 標題 + 返回 -->
            <div class="position-relative mb-4">
              <h2 class="mt-2 mb-3">
                退款詳細
              </h2>

              <CButton
                color="secondary"
                class="position-absolute top-0 end-0"
                @click="goBack"
              >
                返回退款列表
              </CButton>
            </div>

            <!-- 退款資訊 -->
            <CCard class="mb-4">
              <CCardBody>

                <h4 class="mb-4">
                  退款資訊
                </h4>

                <div class="mb-3">
                  <strong>退款編號</strong>

                  <div class="mt-1">
                    {{ refund.refund_id }}
                  </div>
                </div>

                <div class="mb-3">
                  <strong>退款狀態</strong>

                  <div
                    class="mt-1"
                    :class="{
                      'text-danger fw-bold': refund.refund_status === 'pending',
                      'text-success fw-bold': refund.refund_status === 'approved',
                      'text-secondary fw-bold': refund.refund_status === 'rejected'
                    }"
                  >
                    {{ getRefundStatusText(refund.refund_status) }}
                  </div>
                </div>

                <div class="mb-3">
                  <strong>退款原因</strong>

                  <div class="mt-1">
                    {{ refund.refund_reason || '-' }}
                  </div>
                </div>

                <div class="mb-3">
                  <strong>退款說明</strong>

                  <div class="mt-1">
                    {{ refund.refund_description || '-' }}
                  </div>
                </div>

                <div class="mb-4">
                  <strong>退款圖片</strong>

                  <div class="mt-2">
                    <img
                      v-if="refund.refund_image_url"
                      :src="refund.refund_image_url"
                      alt="退款圖片"
                      class="refund-image"
                    >

                    <div
                      v-else
                      class="text-body-secondary"
                    >
                      尚無退款圖片
                    </div>
                  </div>
                </div>

                <div class="mb-3">
                  <strong>申請時間</strong>

                  <div class="mt-1">
                    {{ formatDate(refund.requested_at) }}
                  </div>
                </div>

                <div>
                  <strong>處理時間</strong>

                  <div class="mt-1">
                    {{ formatDate(refund.processed_at) }}
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
                    {{ refund.customer_name || '-' }}
                  </div>
                </div>

                <div>
                  <strong>Email</strong>

                  <div class="mt-1">
                    {{ refund.email || '-' }}
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

                <div class="mb-3">
                  <strong>訂單編號</strong>

                  <div class="mt-1">
                    {{ refund.order_number || '-' }}
                  </div>
                </div>

                <div class="mb-3">
                  <strong>商品金額</strong>

                  <div class="mt-1">
                    {{ formatAmount(refund.product_amount) }}
                  </div>
                </div>

                <div class="mb-3">
                  <strong>運費</strong>

                  <div class="mt-1">
                    {{ formatAmount(refund.shipping_fee) }}
                  </div>
                </div>
                
                <div class="mb-3">
                  <strong>訂單總金額</strong>

                  <div class="mt-1">
                    {{ formatAmount(refund.total_amount) }}
                  </div>
                </div>

                <div class="mb-3">
                  <strong>付款時間</strong>

                  <div class="mt-1">
                    {{ formatDate(refund.payment?.paid_at) }}
                  </div>
                </div>

                <div class="mt-4">
                  <strong>商品明細</strong>

                  <div
                    v-if="refund.items && refund.items.length > 0"
                    class="mt-2 table-responsive"
                  >
                    <CTable hover class="text-center">

                      <CTableHead>
                        <CTableRow>

                          <CTableHeaderCell>
                            商品
                          </CTableHeaderCell>

                          <CTableHeaderCell>
                            規格
                          </CTableHeaderCell>

                          <CTableHeaderCell>
                            單價
                          </CTableHeaderCell>

                          <CTableHeaderCell>
                            數量
                          </CTableHeaderCell>

                          <CTableHeaderCell>
                            小計
                          </CTableHeaderCell>

                        </CTableRow>
                      </CTableHead>

                      <CTableBody>

                        <CTableRow
                          v-for="item in refund.items"
                          :key="item.order_item_id"
                        >

                          <CTableDataCell>
                            {{ item.product_name || '-' }}
                          </CTableDataCell>

                          <CTableDataCell>
                            {{ item.spec_name || '-' }}
                          </CTableDataCell>

                          <CTableDataCell>
                            {{ formatAmount(item.price) }}
                          </CTableDataCell>

                          <CTableDataCell>
                            {{ item.quantity }}
                          </CTableDataCell>

                          <CTableDataCell>
                            {{ formatAmount(item.subtotal) }}
                          </CTableDataCell>

                        </CTableRow>

                      </CTableBody>

                    </CTable>
                  </div>

                  <div
                    v-else
                    class="text-body-secondary"
                  >
                    此訂單沒有商品明細
                  </div>
                </div>

              </CCardBody>
            </CCard>

            <!-- 退款處理 -->
            <CCard class="mb-4">
              <CCardBody>

                <h4 class="mb-4">
                  退款處理
                </h4>

                <!-- 待處理 -->
                <template v-if="refund.refund_status === 'pending'">

                  <div class="mb-3">
                    <strong>管理員回覆</strong>

                    <textarea
                      v-model="adminReply"
                      class="form-control mt-1"
                      rows="5"
                      maxlength="1000"
                      placeholder="請輸入管理員回覆（選填）"
                    ></textarea>
                  </div>

                  <div class="text-body-secondary mb-3">
                    {{ adminReply.length }} / 1000
                  </div>

                  <div class="d-flex gap-2">

                    <CButton
                      color="success"
                      :disabled="processing"
                      @click="openProcessConfirmModal('approved')"
                    >
                      核准退款
                    </CButton>

                    <CButton
                      color="danger"
                      :disabled="processing"
                      @click="openProcessConfirmModal('rejected')"
                    >
                      拒絕退款
                    </CButton>

                  </div>

                </template>

                <!-- 已處理 -->
                <template v-else>

                  <div class="mb-3">
                    <strong>處理結果</strong>

                    <div
                      class="mt-1 fw-bold"
                      :class="{
                        'text-success': refund.refund_status === 'approved',
                        'text-danger': refund.refund_status === 'rejected'
                      }"
                    >
                      {{ getRefundStatusText(refund.refund_status) }}
                    </div>
                  </div>

                  <div class="mb-3">
                    <strong>管理員回覆</strong>

                    <div class="mt-1">
                      {{ refund.admin_reply || '-' }}
                    </div>
                  </div>

                  <div>
                    <strong>處理時間</strong>

                    <div class="mt-1">
                      {{ formatDate(refund.processed_at) }}
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

    <!-- 退款處理確認 -->
    <CModal
      :visible="showProcessConfirmModal"
      @close="closeProcessConfirmModal"
    >
      <CModalHeader>
        <CModalTitle>
          確認退款處理
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        是否確認將此退款案件處理為
        「{{ getRefundStatusText(refundStatus) }}」？
        <br>
        處理後將無法修改。
      </CModalBody>

      <CModalFooter class="border-0">

        <CButton
          color="secondary"
          :disabled="processing"
          @click="closeProcessConfirmModal"
        >
          取消
        </CButton>

        <CButton
          :color="refundStatus === 'approved' ? 'success' : 'danger'"
          :disabled="processing"
          @click="confirmProcess"
        >
          {{ processing ? '處理中...' : '確認' }}
        </CButton>

      </CModalFooter>
    </CModal>

  </div>
</template>

<style scoped>
.refund-image {
  max-width: 500px;
  max-height: 500px;
  width: auto;
  height: auto;
  object-fit: contain;
  border: 1px solid var(--cui-border-color);
  border-radius: 4px;
}

:deep(.form-control) {
  font-size: 14px;
}
</style>
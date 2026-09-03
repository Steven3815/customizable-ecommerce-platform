<script setup>
import { ref, onMounted, watch } from 'vue'

import {
  CContainer,
  CCard,
  CCardBody,
  CRow,
  CCol,
  CFormLabel,
  CFormInput,
  CFormSelect,
  CButton,
  CTable,
  CTableHead,
  CTableRow,
  CTableHeaderCell,
  CTableBody,
  CTableDataCell,
  CModal,
  CModalHeader,
  CModalTitle,
  CModalBody,
  CModalFooter,
  CPagination,
  CPaginationItem
} from '@coreui/vue'

import AppFooter from '../../../components/store/AppFooter.vue'
import AppHeader from '../../../components/store/AppHeader.vue'
import AppSidebar from '../../../components/store/AppSidebar.vue'

import {
  getStoreOrders
} from '@/api/store.js'

const orders = ref([])

const search = ref('')
const status = ref('all')
const refundStatus = ref('all')
const paymentConfirmStatus = ref('all')
const sort = ref('newest')

const page = ref(1)
const totalPages = ref(0)
const total = ref(0)

const loading = ref(true)
const searching = ref(false)
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

// 取得訂單
async function loadOrders() {
  loading.value = true
  error.value = ''

  try {
    const data = await getStoreOrders({
      search: search.value.trim(),
      status: status.value,
      refundStatus: refundStatus.value,
      paymentConfirmStatus: paymentConfirmStatus.value,
      sort: sort.value,
      page: page.value
    })

    orders.value = data.orders || []
    totalPages.value = data.total_pages || 0
    total.value = data.total || 0

  } catch (e) {
    console.error('取得訂單資料失敗:', e)

    orders.value = []
    totalPages.value = 0
    total.value = 0

    if (e.status === 403) {
      error.value = '您沒有權限存取此頁面'
    } else if (e.status === 400) {
      error.value = e.message || '查詢條件錯誤'
    } else {
      error.value = '取得訂單資料失敗'
    }
  } finally {
    loading.value = false
  }
}

// 監聽篩選與排序
watch(
  [
    status,
    refundStatus,
    paymentConfirmStatus,
    sort
  ],
  () => {
    page.value = 1
    loadOrders()
  }
)

// 搜尋訂單
async function searchOrders() {
  if (searching.value) {
    return
  }

  page.value = 1
  searching.value = true

  try {
    await loadOrders()
  } finally {
    searching.value = false
  }
}

// 切換頁面
function changePage(newPage) {
  if (
    newPage < 1 ||
    newPage > totalPages.value ||
    loading.value
  ) {
    return
  }

  page.value = newPage
  loadOrders()
}

// 配送狀態
function getDeliveryStatus(status) {
  const statusMap = {
    pending: '待處理',
    shipping: '配送中',
    completed: '已完成'
  }

  return statusMap[status] || status
}

// 付款確認狀態
function getPaymentStatus(status) {
  const statusMap = {
    waiting: '待確認',
    confirmed: '已確認',
    rejected: '已拒絕'
  }

  return statusMap[status] || status
}

// 退款狀態
function getRefundStatus(status) {
  const statusMap = {
    none: '無退款',
    pending: '退款申請中',
    approved: '退款已核准',
    rejected: '退款已拒絕'
  }

  return statusMap[status] || status
}

// 格式化金額
function formatAmount(amount) {
  return `$ ${Number(amount).toLocaleString()}`
}

// 格式化日期
function formatDate(date) {
  if (!date) {
    return ''
  }

  return new Date(date).toLocaleString('zh-TW', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  loadOrders()
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
          <div v-if="loading">
            載入中...
          </div>

          <!-- 頁面載入錯誤 -->
          <div v-else-if="error">
            <p class="text-danger">
              {{ error }}
            </p>
          </div>

          <!-- 頁面內容 -->
          <div v-else>

            <div class="position-relative mb-4">
              <h2 class="mt-2 mb-3">
                訂單管理
              </h2>
            </div>

            <!-- 訂單搜尋 -->
            <CCard class="mb-4">
              <CCardBody>
                <h4 class="mb-4">
                  訂單搜尋
                </h4>

                <CRow class="g-3">

                  <CCol :md="4">
                    <CFormLabel>
                      搜尋
                    </CFormLabel>

                    <CFormInput
                      v-model="search"
                      placeholder="訂單編號、客戶姓名或電話"
                      @keyup.enter="searchOrders"
                    />
                  </CCol>

                  <CCol :md="2">
                    <CFormLabel>
                      配送狀態
                    </CFormLabel>

                    <CFormSelect
                      v-model="status"
                    >
                      <option value="all">
                        全部
                      </option>

                      <option value="pending">
                        待處理
                      </option>

                      <option value="shipping">
                        配送中
                      </option>

                      <option value="completed">
                        已完成
                      </option>
                    </CFormSelect>
                  </CCol>

                  <CCol :md="2">
                    <CFormLabel>
                      付款確認
                    </CFormLabel>

                    <CFormSelect
                      v-model="paymentConfirmStatus"
                    >
                      <option value="all">
                        全部
                      </option>

                      <option value="waiting">
                        待確認
                      </option>

                      <option value="confirmed">
                        已確認
                      </option>

                      <option value="rejected">
                        已拒絕
                      </option>
                    </CFormSelect>
                  </CCol>
                  <CCol :md="2">
                    <CFormLabel>
                      退款狀態
                    </CFormLabel>

                    <CFormSelect
                      v-model="refundStatus"
                    >
                      <option value="all">
                        全部
                      </option>

                      <option value="none">
                        無退款
                      </option>

                      <option value="pending">
                        退款申請中
                      </option>

                      <option value="approved">
                        退款已核准
                      </option>

                      <option value="rejected">
                        退款已拒絕
                      </option>
                    </CFormSelect>
                  </CCol>

                  <CCol :md="2">
                    <CFormLabel>
                      排序
                    </CFormLabel>

                    <CFormSelect
                      v-model="sort"
                    >
                      <option value="newest">
                        最新訂單
                      </option>

                      <option value="oldest">
                        最舊訂單
                      </option>

                      <option value="price_high">
                        金額高到低
                      </option>

                      <option value="price_low">
                        金額低到高
                      </option>
                    </CFormSelect>
                  </CCol>

                </CRow>

                <div class="mt-4">
                  <CButton
                    color="primary"
                    :disabled="searching"
                    @click="searchOrders"
                  >
                    {{ searching ? '搜尋中...' : '搜尋' }}
                  </CButton>
                </div>

              </CCardBody>
            </CCard>

            <!-- 訂單列表 -->
            <CCard class="mb-4">
              <CCardBody>

                <div
                  class="d-flex justify-content-between align-items-center mb-4"
                >
                  <h4 class="mb-0">
                    訂單列表
                  </h4>

                  <span class="text-body-secondary">
                    第 {{ page }} 頁 / 共 {{ totalPages }} 頁
                  </span>

                  <span class="text-body-secondary">
                    共 {{ total }} 筆訂單
                  </span>
                </div>

                <div class="table-responsive">
                  <CTable hover>
                    <CTableHead>
                      <CTableRow>

                        <CTableHeaderCell>
                          訂單編號
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          客戶
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          電話
                        </CTableHeaderCell>

                        <CTableHeaderCell style="text-align: right;" class="pe-4">
                          訂單金額
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          配送狀態
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          付款確認
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          退款狀態
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          建立時間
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          操作
                        </CTableHeaderCell>

                      </CTableRow>
                    </CTableHead>

                    <CTableBody>

                      <CTableRow
                        v-for="order in orders"
                        :key="order.order_id"
                      >

                        <CTableDataCell>
                          {{ order.order_number }}
                        </CTableDataCell>

                        <CTableDataCell>
                          {{ order.customer.name }}
                        </CTableDataCell>

                        <CTableDataCell>
                          {{ order.customer.phone }}
                        </CTableDataCell>

                        <CTableDataCell style="text-align: right;" class="pe-4">
                          {{ formatAmount(order.total_amount) }}
                        </CTableDataCell>

                        <CTableDataCell>
                          {{ getDeliveryStatus(order.delivery_status) }}
                        </CTableDataCell>

                        <CTableDataCell>
                          {{ getPaymentStatus(order.payment_confirm_status) }}
                        </CTableDataCell>

                        <CTableDataCell>
                          {{ getRefundStatus(order.refund_status) }}
                        </CTableDataCell>

                        <CTableDataCell>
                          {{ formatDate(order.created_at) }}
                        </CTableDataCell>

                      </CTableRow>

                      <CTableRow v-if="orders.length === 0">
                        <CTableDataCell
                          colspan="8"
                          class="text-center text-body-secondary"
                        >
                          目前沒有訂單
                        </CTableDataCell>
                      </CTableRow>

                    </CTableBody>
                  </CTable>
                </div>

                <!-- 分頁 -->
                <div
                  v-if="totalPages > 1"
                  class="d-flex justify-content-center mt-4"
                  style="cursor: pointer;"
                >
                  <CPagination>

                    <CPaginationItem
                      :disabled="page === 1"
                      @click="changePage(page - 1)"
                      style="cursor: pointer;"
                    >
                      上一頁
                    </CPaginationItem>

                    <CPaginationItem
                      v-for="pageNumber in totalPages"
                      :key="pageNumber"
                      :active="pageNumber === page"
                      @click="changePage(pageNumber)"
                    >
                      {{ pageNumber }}
                    </CPaginationItem>

                    <CPaginationItem
                      :disabled="page === totalPages"
                      @click="changePage(page + 1)"
                      style="cursor: pointer;"
                    >
                      下一頁
                    </CPaginationItem>

                  </CPagination>
                </div>

              </CCardBody>
            </CCard>

          </div>

        </CContainer>
      </div>

      <AppFooter />
    </div>

    <!-- 錯誤提示 Modal -->
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

  </div>
</template>

<style scoped>
:deep(.form-control),
:deep(.form-select) {
  font-size: 14px;
}
</style>
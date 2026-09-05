<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'

import {
  CContainer,
  CCard,
  CCardBody,
  CButton,
  CTable,
  CTableHead,
  CTableRow,
  CTableHeaderCell,
  CTableBody,
  CTableDataCell,
  CFormSelect,
  CPagination,
  CPaginationItem,
  CModal,
  CModalHeader,
  CModalTitle,
  CModalBody,
  CModalFooter
} from '@coreui/vue'

import AppFooter from '../../../components/store/AppFooter.vue'
import AppHeader from '../../../components/store/AppHeader.vue'
import AppSidebar from '../../../components/store/AppSidebar.vue'

import {
  getCustomer
} from '@/api/store.js'

const route = useRoute()
const router = useRouter()

const customer = ref(null)
const orders = ref([])

const page = ref(1)
const totalPages = ref(0)
const totalOrders = ref(0)

const sort = ref('created_at_desc')

const loading = ref(true)
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

// 取得客戶詳細資料
async function loadCustomer() {
  loading.value = true
  error.value = ''

  try {
    const customerId = route.params.customerId

    const data = await getCustomer({
      customerId,
      page: page.value,
      sort: sort.value
    })

    customer.value = data.customer || null

    orders.value = data.purchase_records?.orders || []
    totalOrders.value = data.purchase_records?.total_orders || 0
    totalPages.value = data.purchase_records?.total_pages || 0

  } catch (e) {
    console.error('取得客戶詳細資料失敗:', e)

    customer.value = null
    orders.value = []
    totalOrders.value = 0
    totalPages.value = 0

    if (e.status === 403) {
      error.value = '您沒有權限存取此頁面'
    } else if (e.status === 400) {
      error.value = e.message || '查詢條件錯誤'
    } else if (e.status === 401) {
      error.value = '登入狀態已失效，請重新登入'
    } else if (e.status === 404) {
      error.value = '找不到此客戶'
    } else {
      error.value = '取得客戶詳細資料失敗'
    }

    showError(error.value)
  } finally {
    loading.value = false
  }
}

// 監聽排序
watch(sort, () => {
  page.value = 1
  loadCustomer()
})

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
  loadCustomer()
}

// 返回客戶列表
function goBack() {
  router.push({
    name: 'CustomerList'
  })
}

// 格式化金額
function formatAmount(amount) {
  return `$${Number(amount || 0).toLocaleString()}`
}

// 格式化日期
function formatDate(date) {
  if (!date) {
    return '-'
  }

  return date.replace('T', ' ').slice(0, 19)
}

// 退款狀態
function getRefundStatusText(status) {
  const statusMap = {
    pending: '待處理',
    approved: '已核准',
    rejected: '已拒絕',
    completed: '已完成'
  }

  return statusMap[status] || status || '-'
}

function getRefundStatusClass(status) {
  return status === 'pending' ? 'text-danger' : ''
}

// 配送狀態
function getDeliveryStatusText(status) {
  const statusMap = {
    pending: '待處理',
    shipping: '配送中',
    completed: '已完成'
  }

  return statusMap[status] || status || '-'
}

function getDeliveryStatusClass(status) {
  return status === 'pending' ? 'text-danger' : ''
}

onMounted(() => {
  loadCustomer()
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

          <!-- 客戶不存在 -->
          <div
            v-else-if="!customer"
            class="text-center py-5"
          >
            <p class="text-body-secondary mb-3">
              找不到客戶資料
            </p>

            <CButton
              color="primary"
              @click="goBack"
            >
              返回客戶列表
            </CButton>
          </div>

          <div v-else>

            <!-- 標題 + 返回 -->
            <div class="position-relative mb-4">

              <h2 class="mt-2 mb-3">
                客戶詳細
              </h2>

              <CButton
                color="secondary"
                class="position-absolute top-0 end-0"
                @click="goBack"
              >
                返回客戶列表
              </CButton>

            </div>

            <!-- 客戶資料 -->
            <CCard class="mb-4">
              <CCardBody>

                <h4 class="mb-4">
                  客戶資料
                </h4>

                <div class="mb-3">
                  <strong>客戶名稱</strong>

                  <div class="mt-1">
                    {{ customer.name || '-' }}
                  </div>
                </div>

                <div class="mb-3">
                  <strong>Email</strong>

                  <div class="mt-1">
                    {{ customer.email || '-' }}
                  </div>
                </div>

                <div class="mb-3">
                  <strong>電話</strong>

                  <div class="mt-1">
                    {{ customer.phone || '-' }}
                  </div>
                </div>

                <div class="mb-3">
                  <strong>訂單數量</strong>

                  <div class="mt-1">
                    {{ customer.order_count }}
                  </div>
                </div>

                <div class="mb-3">
                  <strong>消費總額</strong>

                  <div class="mt-1">
                    {{ formatAmount(customer.total_spending) }}
                  </div>
                </div>

                <div>
                  <strong>註冊時間</strong>

                  <div class="mt-1">
                    {{ formatDate(customer.created_at) }}
                  </div>
                </div>

              </CCardBody>
            </CCard>

            <!-- 購買記錄 -->
            <CCard class="mb-4">
              <CCardBody>

                <div
                  class="d-flex justify-content-between align-items-center mb-4"
                >
                  <h4 class="mb-0">
                    購買記錄
                  </h4>

                  <span class="text-body-secondary">
                    共 {{ totalOrders }} 筆訂單
                  </span>
                </div>

                <!-- 排序 -->
                <div class="mb-4">

                  <div class="mb-2">
                    <strong>排序</strong>
                  </div>

                  <CFormSelect
                    v-model="sort"
                    style="max-width: 220px;"
                  >
                    <option value="created_at_desc">
                      下單時間：最新
                    </option>

                    <option value="created_at_asc">
                      下單時間：最舊
                    </option>

                    <option value="amount_desc">
                      消費金額：高到低
                    </option>

                    <option value="amount_asc">
                      消費金額：低到高
                    </option>
                  </CFormSelect>

                </div>

                <!-- 購買記錄列表 -->
                <div class="table-responsive">

                  <CTable
                    hover
                    class="text-center align-middle"
                  >

                    <CTableHead>
                      <CTableRow>

                        <CTableHeaderCell>
                          訂單編號
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          下單時間
                        </CTableHeaderCell>

                        <CTableHeaderCell
                          style="text-align: right;"
                          class="pe-4"
                        >
                          消費金額
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          配送狀態
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          退款狀態
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
                          {{ formatDate(order.created_at) }}
                        </CTableDataCell>

                        <CTableDataCell
                          style="text-align: right;"
                          class="pe-4"
                        >
                          {{ formatAmount(order.total_amount) }}
                        </CTableDataCell>

                        <CTableDataCell>
                          <span :class="getDeliveryStatusClass(order.delivery_status)">
                            {{ getDeliveryStatusText(order.delivery_status) }}
                          </span>
                        </CTableDataCell>

                        <CTableDataCell>
                          <span :class="getRefundStatusClass(order.refund_status)">
                            {{ getRefundStatusText(order.refund_status) }}
                          </span>
                        </CTableDataCell>

                        <CTableDataCell>
                          <RouterLink
                            :to="`/store/admin/order/${order.order_id}`"
                            class="btn btn-primary btn-sm"
                          >
                            查看訂單
                          </RouterLink>
                        </CTableDataCell>

                      </CTableRow>

                      <CTableRow v-if="orders.length === 0">
                        <CTableDataCell
                          colspan="6"
                          class="text-center text-body-secondary"
                        >
                          目前無購買記錄
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

    <!-- 錯誤提示 -->
    <CModal
      :visible="showErrorModal"
      @close="closeErrorModal"
    >
      <CModalHeader class="border-0">
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
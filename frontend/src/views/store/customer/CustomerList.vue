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
  CPaginationItem,
  CCollapse
} from '@coreui/vue'

import AppFooter from '../../../components/store/AppFooter.vue'
import AppHeader from '../../../components/store/AppHeader.vue'
import AppSidebar from '../../../components/store/AppSidebar.vue'

import {
  getCustomers
} from '@/api/store.js'

const customers = ref([])

const keyword = ref('')
const searchKeyword = ref('')

const orderCountSort = ref('')
const totalSpendingSort = ref('')
const createdAtSort = ref('desc')

const page = ref(1)
const totalPages = ref(0)
const total = ref(0)

const loading = ref(true)
const searching = ref(false)
const error = ref('')

const showDescription = ref(false)

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

// 取得客戶
async function loadCustomers() {
  loading.value = true
  error.value = ''

  try {
    const data = await getCustomers({
      page: page.value,
      search: searchKeyword.value || null,
      order_count_sort: orderCountSort.value || null,
      total_spending_sort: totalSpendingSort.value || null,
      created_at_sort: createdAtSort.value || null
    })

    customers.value = data.customers || []
    totalPages.value = data.total_pages || 0
    total.value = data.total_customers || 0

  } catch (e) {
    console.error('取得客戶資料失敗:', e)

    customers.value = []
    totalPages.value = 0
    total.value = 0

    if (e.status === 403) {
      error.value = '您沒有權限存取此頁面'
    } else if (e.status === 400) {
      error.value = e.message || '查詢條件錯誤'
    } else if (e.status === 401) {
      error.value = '登入狀態已失效，請重新登入'
    } else {
      error.value = '取得客戶資料失敗'
    }
  } finally {
    loading.value = false
  }
}

// 監聽排序
watch(
  [
    orderCountSort,
    totalSpendingSort,
    createdAtSort
  ],
  () => {
    page.value = 1
    loadCustomers()
  }
)

// 搜尋客戶
async function searchCustomers() {
  if (searching.value) {
    return
  }

  searchKeyword.value = keyword.value.trim()
  page.value = 1
  searching.value = true

  try {
    await loadCustomers()
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
  loadCustomers()
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
  loadCustomers()
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

            <!-- 頁面標題 -->
            <div class="position-relative mb-4">
              <h2 class="mt-2 mb-3">
                客戶管理
              </h2>

              <CButton
                color="link"
                class="text-decoration-none position-absolute top-0 end-0 pe-2"
                @click="
                  showDescription =
                    !showDescription
                "
              >
                <CIcon
                  :icon="
                    showDescription
                      ? 'cilChevronTop'
                      : 'cilChevronBottom'
                  "
                  class="me-1"
                />
                說明
              </CButton>

              <CCollapse
                :visible="showDescription"
              >
                <small class="d-block text-body-secondary">
                  客戶清單只顯示有在目前 Store 下過訂單的客戶。
                </small>
              </CCollapse>
            </div>

            <!-- 客戶搜尋 -->
            <CCard class="mb-4">
              <CCardBody>

                <h4 class="mb-4">
                  客戶搜尋
                </h4>

                <CRow class="g-3">

                  <!-- 搜尋 -->
                  <CCol :md="3">
                    <CFormLabel>
                      客戶搜尋
                    </CFormLabel>

                    <CFormInput
                      v-model="keyword"
                      placeholder="姓名、Email 或電話"
                      @keyup.enter="searchCustomers"
                    />
                  </CCol>

                  <!-- 訂單數量排序 -->
                  <CCol :md="3">
                    <CFormLabel>
                      訂單數量
                    </CFormLabel>

                    <CFormSelect
                      v-model="orderCountSort"
                    >
                      <option value="">
                        全部
                      </option>

                      <option value="desc">
                        高到低
                      </option>

                      <option value="asc">
                        低到高
                      </option>
                    </CFormSelect>
                  </CCol>

                  <!-- 消費總額排序 -->
                  <CCol :md="3">
                    <CFormLabel>
                      消費總額
                    </CFormLabel>

                    <CFormSelect
                      v-model="totalSpendingSort"
                    >
                      <option value="">
                        全部
                      </option>

                      <option value="desc">
                        高到低
                      </option>

                      <option value="asc">
                        低到高
                      </option>
                    </CFormSelect>
                  </CCol>

                  <!-- 註冊時間排序 -->
                  <CCol :md="3">
                    <CFormLabel>
                      註冊時間
                    </CFormLabel>

                    <CFormSelect
                      v-model="createdAtSort"
                    >
                      <option value="">
                        全部
                      </option>

                      <option value="desc">
                        最新
                      </option>

                      <option value="asc">
                        最舊
                      </option>
                    </CFormSelect>
                  </CCol>

                </CRow>

                <!-- 搜尋按鈕 -->
                <div class="mt-4">
                  <CButton
                    color="primary"
                    :disabled="searching"
                    @click="searchCustomers"
                  >
                    {{ searching ? '搜尋中...' : '搜尋' }}
                  </CButton>
                </div>

              </CCardBody>
            </CCard>

            <!-- 客戶列表 -->
            <CCard class="mb-4">
              <CCardBody>

                <div
                  class="d-flex justify-content-between align-items-center mb-4"
                >
                  <h4 class="mb-0">
                    客戶列表
                  </h4>

                  <span class="text-body-secondary">
                    第 {{ page }} 頁 / 共 {{ totalPages }} 頁
                  </span>

                  <span class="text-body-secondary">
                    共 {{ total }} 位客戶
                  </span>
                </div>

                <div class="table-responsive">
                  <CTable hover class="text-center align-middle">

                    <CTableHead>
                      <CTableRow>

                        <CTableHeaderCell>
                          客戶名稱
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          Email
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          電話
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          訂單數量
                        </CTableHeaderCell>

                        <CTableHeaderCell
                          style="text-align: right;"
                          class="pe-4"
                        >
                          消費總額
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          註冊時間
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          操作
                        </CTableHeaderCell>

                      </CTableRow>
                    </CTableHead>

                    <CTableBody>

                      <CTableRow
                        v-for="customer in customers"
                        :key="customer.customer_id"
                      >

                        <!-- 客戶名稱 -->
                        <CTableDataCell>
                          {{ customer.name || '-' }}
                        </CTableDataCell>

                        <!-- Email -->
                        <CTableDataCell>
                          {{ customer.email || '-' }}
                        </CTableDataCell>

                        <!-- 電話 -->
                        <CTableDataCell>
                          {{ customer.phone || '-' }}
                        </CTableDataCell>

                        <!-- 訂單數量 -->
                        <CTableDataCell>
                          {{ customer.order_count }}
                        </CTableDataCell>

                        <!-- 消費總額 -->
                        <CTableDataCell
                          style="text-align: right;"
                          class="pe-4"
                        >
                          {{ formatAmount(customer.total_spending) }}
                        </CTableDataCell>

                        <!-- 註冊時間 -->
                        <CTableDataCell>
                          {{ formatDate(customer.created_at) }}
                        </CTableDataCell>

                        <!-- 操作 -->
                        <CTableDataCell>
                          <CButton
                            color="primary"
                            size="sm"
                          >
                            查看
                          </CButton>
                        </CTableDataCell>

                      </CTableRow>

                      <!-- 沒有客戶 -->
                      <CTableRow v-if="customers.length === 0">
                        <CTableDataCell
                          colspan="7"
                          class="text-center text-body-secondary"
                        >
                          目前無客戶
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
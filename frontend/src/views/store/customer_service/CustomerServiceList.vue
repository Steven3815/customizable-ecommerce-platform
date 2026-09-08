<script setup>
import { ref, onMounted, watch } from 'vue'

import {
  CContainer,
  CCard,
  CCardBody,
  CRow,
  CCol,
  CFormLabel,
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

import { getCustomerServices } from '@/api/store.js'

const services = ref([])

const status = ref('all')
const sort = ref('newest')

const page = ref(1)
const totalPages = ref(0)
const total = ref(0)

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

// 取得客服案件
async function loadServices() {
  loading.value = true
  error.value = ''

  try {
    const data = await getCustomerServices(
      page.value,
      status.value,
      sort.value
    )

    services.value = data.services || []
    totalPages.value = data.total_pages || 0
    total.value = data.total || 0

  } catch (e) {
    console.error('取得客服案件資料失敗:', e)

    services.value = []
    totalPages.value = 0
    total.value = 0

    if (e.status === 403) {
      error.value = '您沒有權限存取此頁面'
    } else if (e.status === 400) {
      error.value = e.message || '查詢條件錯誤'
    } else {
      error.value = '取得客服案件資料失敗'
    }
  } finally {
    loading.value = false
  }
}

// 監聽客服狀態與建立時間排序
watch(
  [status, sort],
  () => {
    page.value = 1
    loadServices()
  }
)

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
  loadServices()
}

// 客服狀態
function getServiceStatus(status) {
  const statusMap = {
    pending: '待處理',
    resolved: '已處理'
  }

  return statusMap[status] || status
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
  loadServices()
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
                客服管理
              </h2>
            </div>

            <!-- 客服搜尋 -->
            <CCard class="mb-4">
              <CCardBody>

                <h4 class="mb-4">
                  客服搜尋
                </h4>

                <CRow class="g-3">

                  <CCol :md="3">
                    <CFormLabel>
                      案件狀態
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

                      <option value="resolved">
                        已處理
                      </option>
                    </CFormSelect>
                  </CCol>

                  <CCol :md="3">
                    <CFormLabel>
                      建立時間
                    </CFormLabel>

                    <CFormSelect
                      v-model="sort"
                    >
                      <option value="newest">
                        最新案件
                      </option>

                      <option value="oldest">
                        最舊案件
                      </option>
                    </CFormSelect>
                  </CCol>

                </CRow>

              </CCardBody>
            </CCard>

            <!-- 客服案件列表 -->
            <CCard class="mb-4">
              <CCardBody>

                <div
                  class="d-flex justify-content-between align-items-center mb-4"
                >
                  <h4 class="mb-0">
                    客服案件列表
                  </h4>

                  <span class="text-body-secondary me-5">
                    第 {{ page }} 頁 / 共 {{ totalPages }} 頁
                  </span>

                  <span class="text-body-secondary">
                    共 {{ total }} 筆案件
                  </span>
                </div>

                <div class="table-responsive">
                  <CTable hover class="text-center">
                    <CTableHead>
                      <CTableRow>

                        <CTableHeaderCell>
                          案件編號
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          客戶
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          訂單編號
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          問題類型
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          狀態
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
                        v-for="service in services"
                        :key="service.service_id"
                      >

                        <CTableDataCell>
                          {{ service.service_id }}
                        </CTableDataCell>

                        <CTableDataCell>
                          {{ service.customer_name }}
                        </CTableDataCell>

                        <CTableDataCell>
                          {{ service.order_number || '-' }}
                        </CTableDataCell>

                        <CTableDataCell>
                          {{ service.problem_type }}
                        </CTableDataCell>

                        <CTableDataCell>
                          <span
                            :class="{
                              'text-danger': service.status === 'pending'
                            }"
                          >
                            {{ getServiceStatus(service.status) }}
                          </span>
                        </CTableDataCell>

                        <CTableDataCell>
                          {{ formatDate(service.created_at) }}
                        </CTableDataCell>

                        <CTableDataCell>
                          <CButton
                            color="primary"
                            size="sm"
                          >
                            查看
                          </CButton>
                        </CTableDataCell>

                      </CTableRow>

                      <CTableRow v-if="services.length === 0">
                        <CTableDataCell
                          colspan="7"
                          class="text-center text-body-secondary"
                        >
                          目前沒有客服案件
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
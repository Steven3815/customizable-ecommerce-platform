<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'

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
  getProducts
} from '@/api/store.js'

const router = useRouter()

const products = ref([])
const categories = ref([])

const keyword = ref('')
const searchKeyword = ref('')
const categoryId = ref('')
const status = ref('all')
const stockStatus = ref('all')

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

// 取得商品
async function loadProducts() {
  loading.value = true
  error.value = ''

  try {
    const data = await getProducts({
      keyword: searchKeyword.value || null,
      category_id: categoryId.value || null,
      status: status.value === 'all'
        ? null
        : status.value,
      stock_status: stockStatus.value === 'all'
        ? null
        : stockStatus.value,
      page: page.value
    })

    products.value = data.products || []
    categories.value = data.categories || []

    totalPages.value = data.pagination?.total_pages || 0
    total.value = data.pagination?.total || 0

  } catch (e) {
    console.error('取得商品資料失敗:', e)

    products.value = []
    totalPages.value = 0
    total.value = 0

    if (e.status === 403) {
      error.value = '您沒有權限存取此頁面'
    } else if (e.status === 400) {
      error.value = e.message || '查詢條件錯誤'
    } else {
      error.value = '取得商品資料失敗'
    }
  } finally {
    loading.value = false
  }
}

// 監聽篩選
watch(
  [
    categoryId,
    status,
    stockStatus
  ],
  () => {
    page.value = 1
    loadProducts()
  }
)

// 搜尋商品
async function searchProducts() {
  if (searching.value) {
    return
  }

  searchKeyword.value = keyword.value.trim()
  page.value = 1
  searching.value = true

  try {
    await loadProducts()
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
  loadProducts()
}

// 商品狀態
function getProductStatus(status) {
  const statusMap = {
    active: '上架',
    hidden: '下架'
  }

  return statusMap[status] || status
}

// 庫存狀態
function getStockStatus(status) {
  const statusMap = {
    in_stock: '庫存正常',
    low_stock: '庫存不足',
    out_of_stock: '缺貨'
  }

  return statusMap[status] || status
}

// 格式化金額
function formatAmount(amount) {
  return `$ ${Number(amount).toLocaleString()}`
}

// 商品詳細
function goToProductDetail(productId) {
  router.push({
    name: 'StoreProductDetail',
    params: {
      productId
    }
  })
}

onMounted(() => {
  loadProducts()
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
                商品管理
              </h2>
            </div>

            <!-- 商品搜尋 -->
            <CCard class="mb-4">
              <CCardBody>
                <h4 class="mb-4">
                  商品搜尋
                </h4>

                <CRow class="g-3">

                  <CCol :md="3">
                    <CFormLabel>
                      商品名稱
                    </CFormLabel>

                    <CFormInput
                      v-model="keyword"
                      placeholder="請輸入商品名稱"
                    />
                  </CCol>

                  <CCol :md="3">
                    <CFormLabel>
                      商品分類
                    </CFormLabel>

                    <CFormSelect
                      v-model="categoryId"
                    >
                      <option value="">
                        全部
                      </option>

                      <option
                        v-for="category in categories"
                        :key="category.category_id"
                        :value="category.category_id"
                      >
                        {{ category.category_name }}
                      </option>
                    </CFormSelect>
                  </CCol>

                  <CCol :md="3">
                    <CFormLabel>
                      商品狀態
                    </CFormLabel>

                    <CFormSelect
                      v-model="status"
                    >
                      <option value="all">
                        全部
                      </option>

                      <option value="active">
                        上架
                      </option>

                      <option value="hidden">
                        下架
                      </option>
                    </CFormSelect>
                  </CCol>

                  <CCol :md="3">
                    <CFormLabel>
                      庫存狀態
                    </CFormLabel>

                    <CFormSelect
                      v-model="stockStatus"
                    >
                      <option value="all">
                        全部
                      </option>

                      <option value="in_stock">
                        庫存正常
                      </option>

                      <option value="low_stock">
                        庫存不足
                      </option>

                      <option value="out_of_stock">
                        缺貨
                      </option>
                    </CFormSelect>
                  </CCol>

                </CRow>

                <div class="mt-4">
                  <CButton
                    color="primary"
                    :disabled="searching"
                    @click="searchProducts"
                  >
                    {{ searching ? '搜尋中...' : '搜尋' }}
                  </CButton>
                </div>

              </CCardBody>
            </CCard>

            <!-- 商品列表 -->
            <CCard class="mb-4">
              <CCardBody>

                <div
                  class="d-flex justify-content-between align-items-center mb-4"
                >
                  <h4 class="mb-0">
                    商品列表
                  </h4>

                  <span class="text-body-secondary">
                    第 {{ page }} 頁 / 共 {{ totalPages }} 頁
                  </span>

                  <span class="text-body-secondary">
                    共 {{ total }} 筆商品
                  </span>

                </div>

                <div class="table-responsive">
                  <CTable hover class="text-center align-middle">
                    <CTableHead>
                      <CTableRow>

                        <CTableHeaderCell>
                          商品名稱
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          商品分類
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          規格名
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          規格值
                        </CTableHeaderCell>

                        <CTableHeaderCell
                          style="text-align: right;"
                          class="pe-4"
                        >
                          價格
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          庫存
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          庫存狀態
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          商品狀態
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          操作
                        </CTableHeaderCell>

                      </CTableRow>
                    </CTableHead>

                    <CTableBody>

                      <CTableRow
                        v-for="product in products"
                        :key="
                          product.spec_id ||
                          product.product_id
                        "
                      >

                        <CTableDataCell>
                          {{ product.product_name }}
                        </CTableDataCell>

                        <CTableDataCell>
                          {{ product.category_name || '-' }}
                        </CTableDataCell>

                        <CTableDataCell>
                          {{ product.spec_name || '-' }}
                        </CTableDataCell>

                        <CTableDataCell>
                          {{ product.spec_value || '-' }}
                        </CTableDataCell>

                        <CTableDataCell
                          style="text-align: right;"
                          class="pe-4"
                        >
                          {{ formatAmount(product.price) }}
                        </CTableDataCell>

                        <CTableDataCell>
                          {{ product.stock }}
                        </CTableDataCell>

                        <CTableDataCell>
                          <span
                            :class="{
                              'text-danger':
                                product.stock_status === 'low_stock' ||
                                product.stock_status === 'out_of_stock'
                            }"
                          >
                            {{ getStockStatus(product.stock_status) }}
                          </span>
                        </CTableDataCell>

                        <CTableDataCell>
                          {{ getProductStatus(product.status) }}
                        </CTableDataCell>

                        <CTableDataCell>
                          <CButton
                            color="primary"
                            size="sm"
                            @click="
                              goToProductDetail(
                                product.product_id
                              )
                            "
                          >
                            編輯
                          </CButton>
                        </CTableDataCell>

                      </CTableRow>

                      <CTableRow v-if="products.length === 0">
                        <CTableDataCell
                          colspan="9"
                          class="text-center text-body-secondary"
                        >
                          目前沒有商品
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
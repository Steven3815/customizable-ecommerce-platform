<script setup>
import { ref, onMounted, watch } from 'vue'

import {
  CContainer,
  CCard,
  CCardBody,
  CRow,
  CCol,
  CFormSelect,
  CFormInput,
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
  CCollapse
} from '@coreui/vue'

import AppFooter from '../../../components/store/AppFooter.vue'
import AppHeader from '../../../components/store/AppHeader.vue'
import AppSidebar from '../../../components/store/AppSidebar.vue'

import {
  getHomepageSettings,
  getHomepageProducts,
  createHomepageProduct,
  updateHomepageProduct,
  deleteHomepageProduct,
  reorderHomepageProducts
} from '@/api/store.js'

const categories = ref([])
const categoryId = ref(null)
const products = ref([])

const loading = ref(true)
const saving = ref(false)
const error = ref('')

// 說明區塊
const showDescription = ref(false)

// 新增商品 Modal
const showAddProductModal = ref(false)
const newProductName = ref('')
const addingProduct = ref(false)
const productError = ref('')

// 錯誤提示 Modal
const showErrorModal = ref(false)
const errorModalMessage = ref('')

// 取得首頁設定 用來取得所有商品類別
async function loadCategories() {
  try {
    const data = await getHomepageSettings()

    categories.value = data.categories || []

    if (categories.value.length > 0) {
      categoryId.value =
        categories.value[0].category_id
    } else {
      categoryId.value = null
    }
  } catch (e) {
    console.error('取得商品類別失敗:', e)

    if (e.status === 403) {
      error.value = '您沒有權限存取此頁面'
    } else if (e.status === 404) {
      error.value = '找不到首頁設定'
    } else {
      error.value = e.message || '取得商品類別失敗'
    }
  }
}

// 取得商品
async function loadProducts() {
  if (!categoryId.value) {
    products.value = []
    return
  }

  try {
    const data = await getHomepageProducts(
      categoryId.value
    )

    products.value =
      data.category?.products || []
  } catch (e) {
    console.error('取得商品資料失敗:', e)

    products.value = []

    if (e.status === 403) {
      error.value = '您沒有權限存取此頁面'
    } else if (e.status === 404) {
      error.value = '找不到商品類別'
    } else {
      error.value = e.message || '取得商品資料失敗'
    }
  }
}

// 監聽商品類別
watch(categoryId, async () => {
  if (!categoryId.value) {
    products.value = []
    return
  }

  error.value = ''

  await loadProducts()
})

// 載入頁面
async function loadPage() {
  loading.value = true
  error.value = ''

  try {
    await loadCategories()
  } catch (e) {
    console.error('載入首頁商品管理失敗:', e)

    error.value = e.message || '載入首頁商品管理失敗'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadPage()
})

// 取消修改
function cancelChanges() {
  window.location.reload()
}

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

// 開啟新增商品 Modal
function addProduct() {
  newProductName.value = ''
  productError.value = ''
  showAddProductModal.value = true
}

// 關閉新增商品 Modal
function closeAddProductModal() {
  if (addingProduct.value) {
    return
  }

  showAddProductModal.value = false
  newProductName.value = ''
  productError.value = ''
}

// 新增商品
async function createNewProduct() {
  const name =
    newProductName.value.trim()

  productError.value = ''

  if (!name) {
    productError.value =
      '商品名稱不可為空白'
    return
  }

  if (!categoryId.value) {
    productError.value =
      '請先選擇商品類別'
    return
  }

  if (addingProduct.value) {
    return
  }

  addingProduct.value = true

  try {
    const formData = new FormData()

    formData.append(
      'category_id',
      categoryId.value
    )

    formData.append(
      'product_name',
      name
    )

    await createHomepageProduct(
      formData
    )

    closeAddProductModal()

    await loadProducts()
  } catch (e) {
    console.error('新增商品失敗:', e)

    if (e.status === 403) {
      productError.value =
        '您沒有權限執行此操作'
    } else if (e.status === 404) {
      productError.value =
        '找不到商品類別'
    } else if (e.status === 409) {
      productError.value =
        '商品名稱已存在'
    } else {
      productError.value =
        e.message || '新增商品失敗'
    }
  } finally {
    addingProduct.value = false
  }
}

// 商品上移 / 下移
async function moveProduct(
  index,
  direction
) {
  const newIndex =
    index + direction

  if (
    newIndex < 0 ||
    newIndex >= products.value.length
  ) {
    return
  }

  if (
    !categoryId.value ||
    saving.value
  ) {
    return
  }

  const oldProducts =
    [...products.value]

  const newProducts =
    [...products.value]

  const temp =
    newProducts[index]

  newProducts[index] =
    newProducts[newIndex]

  newProducts[newIndex] =
    temp

  products.value =
    newProducts

  try {
    const productIds =
      products.value.map(
        product =>
          product.product_id
      )

    await reorderHomepageProducts(
      categoryId.value,
      productIds
    )
  } catch (e) {
    console.error('重新排列商品失敗:', e)

    products.value =
      oldProducts

    if (e.status === 403) {
      showError('您沒有權限執行此操作')
    } else if (e.status === 404) {
      showError('找不到商品')
    } else {
      showError(e.message || '重新排列商品失敗')
    }
  }
}

// 儲存商品名稱
async function saveProducts() {
  if (saving.value) {
    return
  }

  if (!categoryId.value) {
    return
  }

  const hasEmptyProduct =
    products.value.some(
      product =>
        !product.product_name.trim()
    )

  if (hasEmptyProduct) {
    showError('商品名稱不可為空白')
    return
  }

  saving.value = true
  error.value = ''

  try {
    for (const product of products.value) {
      await updateHomepageProduct(
        product.product_id,
        product.product_name.trim()
      )
    }

    await loadProducts()

    error.value = ''

    showError('儲存成功')
  } catch (e) {
    console.error('儲存商品設定失敗:', e)

    if (e.status === 403) {
      showError('您沒有權限執行此操作')
    } else if (e.status === 404) {
      showError('找不到商品')
    } else if (e.status === 409) {
      showError('商品名稱已存在')
    } else {
      showError(e.message || '儲存商品設定失敗')
    }
  } finally {
    saving.value = false
  }
}

// 刪除商品
async function removeProduct(
  productId
) {
  if (
    !confirm(
      '確定要刪除此商品嗎？'
    )
  ) {
    return
  }

  try {
    await deleteHomepageProduct(
      productId
    )

    await loadProducts()
  } catch (e) {
    console.error('刪除商品失敗:', e)

    if (e.status === 403) {
      showError('您沒有權限執行此操作')
    } else if (e.status === 404) {
      showError('找不到商品')
    } else {
      showError(e.message || '刪除商品失敗')
    }
  }
}
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

            <!-- 標題 + 說明 -->
            <div class="position-relative mb-4">
              <h2 class="mt-2 mb-3">
                首頁商品管理
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
                  管理網站首頁顯示的商品。
                  可以選擇商品類別、編輯商品。
                </small>
              </CCollapse>
            </div>

            <!-- 商品類別 -->
            <CCard class="mb-4">
              <CCardBody>
                <h4 class="mb-4">
                  商品類別
                </h4>

                <CRow>
                  <CCol :md="6">
                    <CFormSelect
                      v-model="categoryId"
                    >
                      <option
                        v-for="category in categories"
                        :key="category.category_id"
                        :value="category.category_id"
                      >
                        {{ category.category_name }}
                      </option>
                    </CFormSelect>
                  </CCol>
                </CRow>

                <div
                  v-if="categories.length === 0"
                  class="text-body-secondary mt-3"
                >
                  目前沒有商品類別
                </div>

                <small class="d-block mt-3">
                  <b>說明：</b>
                  選擇商品類別後，可管理該類別的首頁商品。
                </small>
              </CCardBody>
            </CCard>

            <!-- 商品管理 -->
            <CCard class="mb-4">
              <CCardBody>

                <!-- 標題 + 新增 -->
                <div
                  class="d-flex justify-content-between align-items-center mb-4"
                >
                  <h4 class="mb-0">
                    商品
                  </h4>

                  <CButton
                    color="primary"
                    :disabled="
                      !categoryId ||
                      saving
                    "
                    @click="addProduct"
                  >
                    新增商品
                  </CButton>
                </div>

                <!-- 商品表格 -->
                <CTable hover>
                  <CTableHead>
                    <CTableRow>
                      <CTableHeaderCell
                        class="text-center"
                      >
                        順序
                      </CTableHeaderCell>

                      <CTableHeaderCell>
                        商品名稱
                      </CTableHeaderCell>

                      <CTableHeaderCell
                        class="text-center"
                      >
                        操作
                      </CTableHeaderCell>
                    </CTableRow>
                  </CTableHead>

                  <CTableBody>
                    <CTableRow
                      v-for="(product, index) in products"
                      :key="product.product_id"
                    >

                      <!-- 順序 -->
                      <CTableDataCell
                        class="text-center align-middle"
                      >
                        {{ index + 1 }}
                      </CTableDataCell>

                      <!-- 商品名稱 -->
                      <CTableDataCell>
                        <CFormInput
                          v-model="product.product_name"
                          :disabled="saving"
                        />
                      </CTableDataCell>

                      <!-- 操作 -->
                      <CTableDataCell
                        class="text-center align-middle"
                      >

                        <!-- 上移 -->
                        <CButton
                          color="secondary"
                          size="sm"
                          class="me-2"
                          :disabled="
                            index === 0 ||
                            saving
                          "
                          @click="
                            moveProduct(
                              index,
                              -1
                            )
                          "
                        >
                          上移
                        </CButton>

                        <!-- 下移 -->
                        <CButton
                          color="secondary"
                          size="sm"
                          class="me-2"
                          :disabled="
                            index === products.length - 1 ||
                            saving
                          "
                          @click="
                            moveProduct(
                              index,
                              1
                            )
                          "
                        >
                          下移
                        </CButton>

                        <!-- 刪除 -->
                        <CButton
                          color="danger"
                          size="sm"
                          :disabled="saving"
                          @click="
                            removeProduct(
                              product.product_id
                            )
                          "
                        >
                          刪除
                        </CButton>

                      </CTableDataCell>
                    </CTableRow>

                    <!-- 沒有商品 -->
                    <CTableRow
                      v-if="
                        products.length === 0
                      "
                    >
                      <CTableDataCell
                        colspan="3"
                        class="text-center text-body-secondary"
                      >
                        目前沒有商品
                      </CTableDataCell>
                    </CTableRow>
                  </CTableBody>
                </CTable>

                <small>
                  <b>說明：</b>
                  商品會按照目前的順序顯示於首頁。
                  可使用上移與下移調整商品順序。
                </small>

                <!-- 儲存 / 取消修改 -->
                <div
                  class="d-flex justify-content-end mt-4"
                >
                  <CButton
                    color="secondary"
                    class="me-2"
                    :disabled="saving"
                    @click="cancelChanges"
                  >
                    取消修改
                  </CButton>

                  <CButton
                    color="primary"
                    :disabled="
                      saving ||
                      products.length === 0
                    "
                    @click="saveProducts"
                  >
                    {{
                      saving
                        ? '儲存中...'
                        : '儲存設定'
                    }}
                  </CButton>
                </div>

              </CCardBody>
            </CCard>

          </div>
        </CContainer>
      </div>

      <AppFooter />
    </div>

    <!-- 新增商品 Modal -->
    <CModal
      :visible="showAddProductModal"
      @close="closeAddProductModal"
    >
      <CModalHeader>
        <CModalTitle>
          新增商品
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        <CFormInput
          v-model="newProductName"
          label="商品名稱"
          placeholder="請輸入商品名稱"
          :disabled="addingProduct"
          @keyup.enter="createNewProduct"
        />

        <div
          v-if="productError"
          class="text-danger mt-2"
        >
          {{ productError }}
        </div>
      </CModalBody>

      <CModalFooter>
        <CButton
          color="secondary"
          :disabled="addingProduct"
          @click="closeAddProductModal"
        >
          取消
        </CButton>

        <CButton
          color="primary"
          :disabled="addingProduct"
          @click="createNewProduct"
        >
          {{
            addingProduct
              ? '新增中...'
              : '新增'
          }}
        </CButton>
      </CModalFooter>
    </CModal>

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
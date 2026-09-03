<script setup>
import { ref, onMounted } from 'vue'

import {
  CContainer,
  CCard,
  CCardBody,
  CRow,
  CCol,
  CFormCheck,
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
  CModalFooter
} from '@coreui/vue'

import AppFooter from '../../../components/store/AppFooter.vue'
import AppHeader from '../../../components/store/AppHeader.vue'
import AppSidebar from '../../../components/store/AppSidebar.vue'

import {
  getHomepageSettings,
  updateHomepageSettings,
  createCategory,
  reorderCategories,
  deleteCategories
} from '@/api/store.js'

const website = ref(null)
const product = ref(null)
const categories = ref([])
const footer = ref(null)

const loading = ref(true)
const saving = ref(false)
const error = ref('')
const showDescription = ref(false)

// 新增分類 Modal
const showAddCategoryModal = ref(false)
const newCategoryName = ref('')
const addingCategory = ref(false)
const categoryError = ref('')

// 錯誤提示 Modal
const showErrorModal = ref(false)
const errorModalMessage = ref('')

// 取得首頁設定
async function loadSettings() {
  try {
    const data = await getHomepageSettings()

    website.value = data.website
    product.value = data.product
    categories.value = data.categories
    footer.value = data.footer
  } catch (e) {
    console.error('取得首頁設定失敗:', e)

    if (e.status === 403) {
      error.value = '您沒有權限存取此頁面'
    } else if (e.status === 404) {
      error.value = '找不到首頁設定'
    } else {
      error.value = '取得首頁設定失敗'
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadSettings()
})

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

// 儲存首頁設定
async function saveSettings() {
  if (saving.value) {
    return
  }

  const hasEmptyCategory = categories.value.some(
    category => !category.category_name.trim()
  )

  if (hasEmptyCategory) {
    showError('商品類別名稱不可為空白')
    return
  }

  saving.value = true
  error.value = ''

  try {
    const payload = {
      intro_section_enable: website.value.intro_section_enable,
      banner_section_enable: website.value.banner_section_enable,
      display_limit: product.value.display_limit,

      categories: categories.value.map(category => ({
        category_id: category.category_id,
        category_name: category.category_name.trim()
      })),

      footer: {
        contact_phone_enable: footer.value.contact_phone_enable,
        address_enable: footer.value.address_enable,
        email_enable: footer.value.email_enable,
        service_phone_enable: footer.value.service_phone_enable
      }
    }

    const data = await updateHomepageSettings(payload)

    await loadSettings()

    error.value = ''

    showError('儲存成功' || data.message)
  } catch (e) {
    console.error('儲存首頁設定失敗:', e)

    if (e.status === 403) {
      showError('您沒有權限執行此操作')
    } else if (e.status === 404) {
      showError('找不到首頁設定')
    } else {
      showError(e.message || '儲存首頁設定失敗')
    }
  } finally {
    saving.value = false
  }
}

// 開啟新增分類 Modal
function addCategory() {
  newCategoryName.value = ''
  categoryError.value = ''
  showAddCategoryModal.value = true
}

// 關閉新增分類 Modal
function closeAddCategoryModal() {
  if (addingCategory.value) {
    return
  }

  showAddCategoryModal.value = false
  newCategoryName.value = ''
  categoryError.value = ''
}

// 新增分類
async function createNewCategory() {
  const name = newCategoryName.value.trim()

  categoryError.value = ''

  if (!name) {
    categoryError.value = '商品類別名稱不可為空白'
    return
  }

  if (addingCategory.value) {
    return
  }

  addingCategory.value = true

  try {
    const data = await createCategory(name)

    categories.value.push(data.category)

    closeAddCategoryModal()
  } catch (e) {
    console.error('新增分類失敗:', e)

    if (e.status === 403) {
      categoryError.value = '您沒有權限執行此操作'
    } else if (e.status === 409) {
      categoryError.value = '此分類名稱已存在'
    } else if (e.status === 404) {
      categoryError.value = '找不到商店'
    } else {
      categoryError.value = e.message || '新增分類失敗'
    }
  } finally {
    addingCategory.value = false
  }
}

// 分類上移 / 下移
async function moveCategory(index, direction) {
  const newIndex = index + direction

  if (
    newIndex < 0 ||
    newIndex >= categories.value.length
  ) {
    return
  }

  const oldCategories = [...categories.value]
  const newCategories = [...categories.value]

  const temp = newCategories[index]
  newCategories[index] = newCategories[newIndex]
  newCategories[newIndex] = temp

  categories.value = newCategories

  try {
    const categoryIds = categories.value.map(
      category => category.category_id
    )

    await reorderCategories(categoryIds)
  } catch (e) {
    console.error('重新排列分類失敗:', e)

    categories.value = oldCategories

    if (e.status === 403) {
      showError('您沒有權限執行此操作')
    } else if (e.status === 404) {
      showError('找不到分類')
    } else {
      showError(e.message || '重新排列分類失敗')
    }
  }
}

// 刪除分類
async function removeCategory(categoryId) {
  if (!confirm('確定要刪除此分類嗎？')) {
    return
  }

  try {
    const data = await deleteCategories([categoryId])

    categories.value = data.categories

    error.value = ''
  } catch (e) {
    console.error('刪除分類失敗:', e)

    if (e.status === 403) {
      showError('您沒有權限執行此操作')
    } else if (e.status === 404) {
      showError('找不到分類')
    } else {
      showError(e.message || '刪除分類失敗')
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
            <div class="position-relative mb-4">
              <h2 class="mt-2 mb-3">
                網站首頁管理
              </h2>
              <div class="mb-3">
                <CButton
                  color="link"
                  class="text-decoration-none position-absolute top-0 end-0 pe-2"
                  @click="showDescription = !showDescription"
                >
                  <CIcon
                    :icon="showDescription ? 'cilChevronTop' : 'cilChevronBottom'"
                    class="me-1"
                  />
                  說明
                </CButton>

                <CCollapse :visible="showDescription">
                  <small class="d-block mt-2">
                    用於設定首頁各區塊的顯示與隱藏，以及調整首頁內容與版面配置。
                  </small>
                </CCollapse>
              </div>
            </div>

            <!-- 首頁區塊 -->
            <CCard class="mb-4">
              <CCardBody>
                <h4 class="mb-4">
                  首頁區塊
                </h4>

                <div>
                  <CFormCheck
                    v-model="website.banner_section_enable"
                    label="橫幅區塊"
                    :true-value="1"
                    :false-value="0"
                  />
                  
                  <CFormCheck class="mb-4"
                    v-model="website.intro_section_enable"
                    label="輪播區塊"
                    :true-value="1"
                    :false-value="0"
                  />
                  <small><b>說明:</b> 開啟後，首頁將顯示對應區塊</small> <br>
                  <small><b>橫幅區塊:</b> 主要的橫幅圖片，用於呈現活動、優惠或重要資訊</small><br>
                  <small><b>輪播區塊:</b> 以輪播方式顯示多張圖片，可用於展示活動、商品或宣傳內容</small>
                </div>
              </CCardBody>
            </CCard>

            <!-- 商品設定 -->
            <CCard class="mb-4">
              <CCardBody>
                <h4 class="mb-4">
                  首頁商品區設定
                </h4>

                <CRow>
                  <CCol :md="4">
                    <CFormSelect class="mb-4"
                      v-model="product.display_limit"
                      label="每列顯示商品數量"
                    >
                      <option :value="4">
                        4 個
                      </option>

                      <option :value="5">
                        5 個
                      </option>

                      <option :value="6">
                        6 個
                      </option>  
                    </CFormSelect>
                  </CCol>
                  <CCol :md="12">
                    <small>
                      <b>說明：</b>
                      此設定會影響商品在首頁上的排列方式，可依首頁版面需求選擇
                    </small>
                  </CCol>
                </CRow>
              </CCardBody>
            </CCard>

            <!-- 商品類別 -->
            <CCard class="mb-4">
              <CCardBody>
                <div
                  class="d-flex justify-content-between align-items-center mb-4"
                >
                  <h4 class="mb-0">
                    商品類別
                  </h4>

                  <CButton
                    color="primary"
                    @click="addCategory"
                  >
                    新增類別
                  </CButton>
                </div>

                <CTable hover>
                  <CTableHead>
                    <CTableRow>
                      <CTableHeaderCell class="text-center">
                        順序
                      </CTableHeaderCell>

                      <CTableHeaderCell>
                        類別名稱
                      </CTableHeaderCell>

                      <CTableHeaderCell class="text-center">
                        操作
                      </CTableHeaderCell>
                    </CTableRow>
                  </CTableHead>

                  <CTableBody>
                    <CTableRow
                      v-for="(category, index) in categories"
                      :key="category.category_id"
                    >
                      <CTableDataCell class="text-center align-middle">
                        {{ index + 1 }}
                      </CTableDataCell>

                      <CTableDataCell>
                        <CFormInput
                          v-model="category.category_name"
                        />
                      </CTableDataCell>

                      <CTableDataCell class="text-center align-middle">
                        <CButton
                          color="secondary"
                          size="sm"
                          class="me-2"
                          :disabled="index === 0"
                          @click="moveCategory(index, -1)"
                        >
                          上移
                        </CButton>

                        <CButton
                          color="secondary"
                          size="sm"
                          class="me-2"
                          :disabled="index === categories.length - 1"
                          @click="moveCategory(index, 1)"
                        >
                          下移
                        </CButton>

                        <CButton
                          color="danger"
                          size="sm"
                          @click="removeCategory(category.category_id)"
                        >
                          刪除
                        </CButton>
                      </CTableDataCell>
                    </CTableRow>

                    <CTableRow v-if="categories.length === 0">
                      <CTableDataCell
                        colspan="3"
                        class="text-center text-body-secondary"
                      >
                        目前沒有商品類別
                      </CTableDataCell>
                    </CTableRow>
                    
                  </CTableBody>
                </CTable>
                <small><b>說明:</b> 用於將商品依照不同類型進行分類，方便顧客瀏覽與尋找商品</small>
              </CCardBody>
            </CCard>

            <!-- Footer -->
            <CCard class="mb-4">
              <CCardBody>
                <h4 class="mb-4">
                  頁尾版面設定
                </h4>

                <div>
                  <CFormCheck
                    v-model="footer.contact_phone_enable"
                    label="聯絡電話"
                    :true-value="1"
                    :false-value="0"
                  />

                  <CFormCheck
                    v-model="footer.address_enable"
                    label="地址"
                    :true-value="1"
                    :false-value="0"
                  />

                  <CFormCheck
                    v-model="footer.email_enable"
                    label="Email"
                    :true-value="1"
                    :false-value="0"
                  />

                  <CFormCheck
                    v-model="footer.service_phone_enable"
                    label="客服電話"
                    :true-value="1"
                    :false-value="0"
                  />
                </div>
                <small>
                  <b>說明：</b> 開啟後，頁尾可設定對應的商家資訊
                </small>
              </CCardBody>
              
            </CCard>

            <!-- 儲存 -->
            <div class="d-flex justify-content-end mb-5">
              <CButton
                color="primary"
                :disabled="saving"
                @click="saveSettings"
              >
                {{ saving ? '儲存中...' : '儲存設定' }}
              </CButton>
            </div>
          </div>

        </CContainer>
      </div>

      <AppFooter />
    </div>

    <!-- 新增分類 Modal -->
    <CModal
      :visible="showAddCategoryModal"
      @close="closeAddCategoryModal"
    >
      <CModalHeader>
        <CModalTitle>
          新增商品類別
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        <CFormInput
          v-model="newCategoryName"
          label="類別名稱"
          placeholder="請輸入類別名稱"
          :disabled="addingCategory"
          @keyup.enter="createNewCategory"
        />

        <div
          v-if="categoryError"
          class="text-danger mt-2"
        >
          {{ categoryError }}
        </div>
      </CModalBody>

      <CModalFooter>
        <CButton
          color="secondary"
          :disabled="addingCategory"
          @click="closeAddCategoryModal"
        >
          取消
        </CButton>

        <CButton
          color="primary"
          :disabled="addingCategory"
          @click="createNewCategory"
        >
          {{ addingCategory ? '新增中...' : '新增' }}
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
<script setup>
import { ref, onMounted } from 'vue'

import {
  CContainer,
  CCard,
  CCardBody,
  CRow,
  CCol,
  CFormLabel,
  CFormInput,
  CButton,
  CModal,
  CModalHeader,
  CModalTitle,
  CModalBody,
  CModalFooter,
  CCollapse,
  CTable,
  CTableHead,
  CTableRow,
  CTableHeaderCell,
  CTableBody,
  CTableDataCell
} from '@coreui/vue'

import AppSidebar from '../../../components/store/AppSidebar.vue'
import AppHeader from '../../../components/store/AppHeader.vue'
import AppFooter from '../../../components/store/AppFooter.vue'

import {
  getHomepageSlider,
  createHomepageSlider,
  updateHomepageSlider,
  deleteHomepageSlider,
  reorderHomepageSliders
} from '../../../api/store'

const sliderImages = ref([])
const introSectionEnable = ref(false)

const loading = ref(true)
const saving = ref(false)

const showAddForm = ref(false)
const showDescription = ref(false)

const selectedImage = ref(null)
const imagePreview = ref(null)

const title = ref('')
const editingImageId = ref(null)

const showDeleteModal = ref(false)
const deletingImage = ref(null)

const showErrorModal = ref(false)
const errorMessage = ref('')

function showError(message) {
  errorMessage.value = message
  showErrorModal.value = true
}

function getImageUrl(url) {
  if (!url) {
    return null
  }

  if (url.startsWith('blob:')) {
    return url
  }

  if (url.startsWith('http://') || url.startsWith('https://')) {
    return url
  }

  return `http://localhost/ecommerce-platform/backend${url}`
}

async function loadSlider() {
  loading.value = true

  try {
    const data = await getHomepageSlider()

    introSectionEnable.value =
      data.intro_section_enable === true ||
      data.intro_section_enable === 1

    sliderImages.value = data.slider_images || []
  } catch (error) {
    console.error('取得首頁輪播失敗:', error)

    if (error.status === 403) {
      showError('您沒有權限查看首頁輪播')
    } else if (error.status === 404) {
      showError('找不到首頁輪播資料')
    } else {
      showError(error.message || '取得首頁輪播失敗')
    }
  } finally {
    loading.value = false
  }
}

function startAdd() {
  editingImageId.value = null
  title.value = ''

  clearSelectedImage()

  showAddForm.value = true
}

function startEdit(image) {
  editingImageId.value = image.image_id
  title.value = image.title || ''

  clearSelectedImage()

  showAddForm.value = true

  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  })
}

function cancelEdit() {
  editingImageId.value = null
  title.value = ''

  clearSelectedImage()

  showAddForm.value = false
}

function handleImageChange(event) {
  const file = event.target.files?.[0]

  if (!file) {
    return
  }

  selectedImage.value = file

  if (imagePreview.value) {
    URL.revokeObjectURL(imagePreview.value)
  }

  imagePreview.value = URL.createObjectURL(file)
}

function clearSelectedImage() {
  if (imagePreview.value) {
    URL.revokeObjectURL(imagePreview.value)
  }

  selectedImage.value = null
  imagePreview.value = null
}

async function saveSlider() {
  if (!editingImageId.value && !selectedImage.value) {
    showError('請選擇輪播圖片')
    return
  }

  if (title.value.length > 200) {
    showError('標題最多 200 個字元')
    return
  }

  saving.value = true

  try {
    const formData = new FormData()

    if (editingImageId.value) {
      formData.append('image_id', editingImageId.value)
    }

    if (selectedImage.value) {
      formData.append('image', selectedImage.value)
    }

    formData.append('title', title.value.trim())

    if (editingImageId.value) {
      await updateHomepageSlider(formData)
    } else {
      await createHomepageSlider(formData)
    }

    await loadSlider()

    cancelEdit()
  } catch (error) {
    console.error('儲存首頁輪播失敗:', error)

    if (error.status === 403) {
      showError('您沒有權限執行此操作')
    } else if (error.status === 404) {
      showError('找不到輪播圖片')
    } else {
      showError(error.message || '儲存首頁輪播失敗')
    }
  } finally {
    saving.value = false
  }
}

function confirmDelete(image) {
  deletingImage.value = image
  showDeleteModal.value = true
}

async function deleteSlider() {
  if (!deletingImage.value) {
    return
  }

  saving.value = true

  try {
    await deleteHomepageSlider(deletingImage.value.image_id)

    await loadSlider()

    showDeleteModal.value = false
    deletingImage.value = null
  } catch (error) {
    console.error('刪除首頁輪播失敗:', error)

    if (error.status === 403) {
      showError('您沒有權限執行此操作')
    } else if (error.status === 404) {
      showError('找不到輪播圖片')
    } else {
      showError(error.message || '刪除首頁輪播失敗')
    }
  } finally {
    saving.value = false
  }
}

async function moveSlider(index, direction) {
  const newIndex = index + direction

  if (
    newIndex < 0 ||
    newIndex >= sliderImages.value.length
  ) {
    return
  }

  const oldImages = [...sliderImages.value]
  const newImages = [...sliderImages.value]

  const temp = newImages[index]
  newImages[index] = newImages[newIndex]
  newImages[newIndex] = temp

  sliderImages.value = newImages

  try {
    const imageIds = sliderImages.value.map(
      image => image.image_id
    )

    await reorderHomepageSliders(imageIds)
  } catch (error) {
    console.error('重新排列輪播失敗:', error)

    sliderImages.value = oldImages

    if (error.status === 403) {
      showError('您沒有權限執行此操作')
    } else if (error.status === 404) {
      showError('找不到輪播圖片')
    } else {
      showError(error.message || '重新排列輪播失敗')
    }
  }
}

onMounted(() => {
  loadSlider()
})
</script>

<template>
  <div>
    <AppSidebar />

    <div class="wrapper d-flex flex-column min-vh-100">
      <AppHeader />

      <div class="body flex-grow-1">
        <CContainer class="px-4" lg>

          <!-- 頁面標題 -->
          <div class="position-relative mb-4">
            <h2 class="mt-2 mb-3">
              首頁輪播
            </h2>

            <CButton
              color="link"
              class="text-decoration-none position-absolute top-0 end-0 pe-2"
              @click="showDescription = !showDescription"
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

            <CCollapse :visible="showDescription">
              <small class="d-block text-body-secondary">
                管理網站首頁的輪播圖片
                最多可以設定 5 張圖片，
                並可以調整圖片順序、標題及刪除圖片
              </small>
            </CCollapse>
          </div>

          <!-- 輪播區塊狀態 -->
          <CCard class="mb-4">
            <CCardBody>
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h4 class="mb-1">
                    輪播區塊
                  </h4>

                  <small class="text-body-secondary">
                    首頁目前的輪播區塊狀態
                  </small>
                </div>

                <span
                  v-if="introSectionEnable"
                  class="text-success"
                >
                  已啟用
                </span>

                <span
                  v-else
                  class="text-danger"
                >
                  未啟用
                </span>
              </div>
            </CCardBody>
          </CCard>

          <!-- 輪播圖片 -->
          <CCard class="mb-4">
            <CCardBody>

              <!-- 標題 + 新增 -->
              <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">
                  輪播圖片
                </h4>

                <CButton
                  v-if="!showAddForm"
                  color="primary"
                  :disabled="sliderImages.length >= 5"
                  @click="startAdd"
                >
                  新增輪播
                </CButton>
              </div>

              <!-- 新增 / 編輯表單 -->
              <div
                v-if="showAddForm"
                class="mb-4"
              >
                <div class="border rounded p-4">
                  <h6 class="mb-4">
                    {{
                      editingImageId
                        ? '編輯輪播'
                        : '新增輪播'
                    }}
                  </h6>

                  <CRow class="mb-4">

                    <!-- 圖片 -->
                    <CCol md="8">
                      <CFormLabel>
                        輪播圖片
                      </CFormLabel>

                      <CFormInput
                        type="file"
                        accept="image/*"
                        @change="handleImageChange"
                      />

                      <small class="d-block mt-2 text-body-secondary">
                        建議圖片比例為
                        1920 × 600，
                        系統會保留原始圖片尺寸
                      </small>

                      <!-- 新圖片預覽 -->
                      <div
                        v-if="imagePreview"
                        class="slider-preview mt-3"
                      >
                        <img
                          :src="imagePreview"
                          alt="圖片預覽"
                          class="slider-preview-image"
                        >
                      </div>

                      <!-- 編輯時的原圖片 -->
                      <div
                        v-else-if="
                          editingImageId &&
                          sliderImages.find(
                            image =>
                              image.image_id ===
                              editingImageId
                          )
                        "
                        class="slider-preview mt-3"
                      >
                        <img
                          :src="
                            getImageUrl(
                              sliderImages.find(
                                image =>
                                  image.image_id ===
                                  editingImageId
                              ).image_url
                            )
                          "
                          alt="目前圖片"
                          class="slider-preview-image"
                        >
                      </div>
                    </CCol>

                    <!-- 標題 -->
                    <CCol md="4">
                      <CFormLabel>
                        標題
                      </CFormLabel>

                      <CFormInput
                        v-model="title"
                        type="text"
                        maxlength="200"
                        placeholder="輸入輪播標題"
                      />

                      <small class="d-block mt-2 text-body-secondary">
                        選填，最多 200 個字元
                      </small>
                    </CCol>
                  </CRow>

                  <!-- 表單操作 -->
                  <div class="d-flex gap-2">
                    <CButton
                      color="primary"
                      :disabled="saving"
                      @click="saveSlider"
                    >
                      {{
                        saving
                          ? '儲存中...'
                          : editingImageId
                            ? '儲存修改'
                            : '新增'
                      }}
                    </CButton>

                    <CButton
                      color="secondary"
                      :disabled="saving"
                      @click="cancelEdit"
                    >
                      取消
                    </CButton>
                  </div>
                </div>
              </div>

              <!-- 最多 5 張提示 -->
              <small
                v-if="
                  sliderImages.length >= 5 &&
                  !showAddForm
                "
                class="d-block mb-3 text-body-secondary"
              >
                輪播最多只能設定 5 張圖片
              </small>

              <!-- 載入中 -->
              <div
                v-if="loading"
                class="text-center py-5 text-body-secondary"
              >
                載入中...
              </div>

              <!-- 沒有圖片 -->
              <div
                v-else-if="sliderImages.length === 0"
                class="text-center py-5 text-body-secondary"
              >
                尚未設定輪播圖片
              </div>

              <!-- 圖片列表 -->
              <CTable
                v-else
                align="middle"
                responsive
              >
                <CTableHead>
                  <CTableRow>
                    <CTableHeaderCell class="text-center">
                      順序
                    </CTableHeaderCell>

                    <CTableHeaderCell>
                      圖片
                    </CTableHeaderCell>

                    <CTableHeaderCell>
                      標題
                    </CTableHeaderCell>

                    <CTableHeaderCell class="text-center">
                      操作
                    </CTableHeaderCell>
                  </CTableRow>
                </CTableHead>

                <CTableBody>
                  <CTableRow
                    v-for="(image, index) in sliderImages"
                    :key="image.image_id"
                  >
                    <!-- 順序 -->
                    <CTableDataCell class="text-center">
                      {{ index + 1 }}
                    </CTableDataCell>

                    <!-- 圖片 -->
                    <CTableDataCell>
                      <div class="slider-table-image">
                        <img
                          :src="getImageUrl(image.image_url)"
                          :alt="image.title || '輪播'"
                          class="slider-table-image-content"
                        >
                      </div>
                    </CTableDataCell>

                    <!-- 標題 -->
                    <CTableDataCell>
                      {{ image.title || '未設定標題' }}
                    </CTableDataCell>

                    <!-- 操作 -->
                    <CTableDataCell class="text-center">
                      <CButton
                        color="secondary"
                        size="sm"
                        class="me-2"
                        :disabled="index === 0"
                        @click="moveSlider(index, -1)"
                      >
                        上移
                      </CButton>

                      <CButton
                        color="secondary"
                        size="sm"
                        class="me-2"
                        :disabled="index === sliderImages.length - 1"
                        @click="moveSlider(index, 1)"
                      >
                        下移
                      </CButton>

                      <CButton
                        color="primary"
                        size="sm"
                        class="me-2"
                        @click="startEdit(image)"
                      >
                        編輯
                      </CButton>

                      <CButton
                        color="danger"
                        size="sm"
                        @click="confirmDelete(image)"
                      >
                        刪除
                      </CButton>
                    </CTableDataCell>
                  </CTableRow>
                </CTableBody>
              </CTable>

            </CCardBody>
          </CCard>

        </CContainer>
      </div>

      <AppFooter />
    </div>

    <!-- 刪除確認 -->
    <CModal
      :visible="showDeleteModal"
      @close="showDeleteModal = false"
    >
      <CModalHeader class="border-0">
        <CModalTitle>
          刪除輪播
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        確定要刪除這張輪播圖片嗎？
      </CModalBody>

      <CModalFooter class="border-0">
        <CButton
          color="secondary"
          :disabled="saving"
          @click="showDeleteModal = false"
        >
          取消
        </CButton>

        <CButton
          color="danger"
          :disabled="saving"
          @click="deleteSlider"
        >
          {{
            saving
              ? '刪除中...'
              : '確定刪除'
          }}
        </CButton>
      </CModalFooter>
    </CModal>

    <!-- 錯誤訊息 -->
    <CModal
      :visible="showErrorModal"
      @close="showErrorModal = false"
    >
      <CModalHeader class="border-0">
        <CModalTitle>
          提示
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        {{ errorMessage }}
      </CModalBody>

      <CModalFooter class="border-0">
        <CButton
          color="primary"
          @click="showErrorModal = false"
        >
          確定
        </CButton>
      </CModalFooter>
    </CModal>

  </div>
</template>

<style scoped>
.slider-preview {
  width: 100%;
  max-width: 960px;
  aspect-ratio: 1920 / 600;
  overflow: hidden;
  border-radius: 4px;
}

.slider-preview-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.slider-table-image {
  width: 240px;
  aspect-ratio: 1920 / 600;
  overflow: hidden;
  border-radius: 4px;
}

.slider-table-image-content {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
</style>
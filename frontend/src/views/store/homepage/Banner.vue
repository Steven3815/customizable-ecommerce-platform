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
  CFormTextarea,
  CFormSelect,
  CButton,
  CImage,
  CModal,
  CModalHeader,
  CModalTitle,
  CModalBody,
  CModalFooter,
  CCollapse,
} from '@coreui/vue'

import AppFooter from '../../../components/store/AppFooter.vue'
import AppHeader from '../../../components/store/AppHeader.vue'
import AppSidebar from '../../../components/store/AppSidebar.vue'

import {
  getHomepageBanner,
  createHomepageBanner,
  updateHomepageBanner,
  deleteHomepageBanner,
  deleteHomepageBannerImage
} from '@/api/store.js'

const banner = ref(null)
const defaultBanners = ref([])
const bannerSectionEnable = ref(false)

const loading = ref(true)
const saving = ref(false)

const imageSource = ref('default')
const defaultBannerId = ref('')
const selectedImage = ref(null)

const title = ref('')
const description = ref('')

const imagePreview = ref(null)

const showDescription = ref(false)

const showErrorModal = ref(false)
const errorMessage = ref('')

const showDeleteModal = ref(false)

// 圖片網址
function getImageUrl(url) {
  if (!url) {
    return null
  }

  if (url.startsWith('blob:')) {
    return url
  }

  if (
    url.startsWith('http://') ||
    url.startsWith('https://')
  ) {
    return url
  }

  return `http://localhost/ecommerce-platform/backend${url}`
}

function showError(message) {
  errorMessage.value = message
  showErrorModal.value = true
}

function closeErrorModal() {
  showErrorModal.value = false
  errorMessage.value = ''
}

// 更新預設 Banner 預覽
function updateDefaultBannerPreview() {
  if (!defaultBannerId.value) {
    imagePreview.value = null
    return
  }

  const selectedBanner = defaultBanners.value.find(
    item =>
      String(item.default_banner_id) ===
      String(defaultBannerId.value)
  )

  if (!selectedBanner) {
    imagePreview.value = null
    return
  }

  imagePreview.value =
    getImageUrl(selectedBanner.image_url)
}

// 監聽預設 Banner 選擇
watch(defaultBannerId, () => {
  if (imageSource.value === 'default') {
    updateDefaultBannerPreview()
  }
})

// 取得首頁橫幅
async function loadBanner() {
  try {
    const data = await getHomepageBanner()

    bannerSectionEnable.value =
      data.banner_section_enable === true ||
      data.banner_section_enable === 1

    banner.value = data.banner
    defaultBanners.value =
      data.default_banners || []

    if (banner.value) {
      title.value =
        banner.value.title || ''

      description.value =
        banner.value.description || ''

      if (
        banner.value.image_source === 'default'
      ) {
        imageSource.value = 'default'

        defaultBannerId.value =
          banner.value.default_banner_id || ''
      }

      else {
        imageSource.value = 'upload'
        defaultBannerId.value = ''

        imagePreview.value =
          getImageUrl(
            banner.value.image_url
          )
      }

    } else {
      imageSource.value = 'default'
      defaultBannerId.value = ''
      imagePreview.value = null
    }

  } catch (e) {
    console.error('取得首頁橫幅資料失敗:', e)

    if (e.status === 403) {
      showError(`您沒有權限存取此頁面：${e.message || 'Forbidden'}`)
    } else if (e.status === 404) {
      showError(`找不到橫幅資料：${e.message || 'Not Found'}`)
    } else {
      showError(`取得橫幅資料失敗：${e.message || '未知錯誤'}`)
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadBanner()
})

function cancelChanges() {
  window.location.reload()
}

// 選擇圖片
function handleImageChange(event) {
  const file = event.target.files[0]

  if (!file) {
    return
  }

  selectedImage.value = file

  imagePreview.value =
    URL.createObjectURL(file)
}

// 切換圖片來源
function changeImageSource(source) {
  imageSource.value = source

  selectedImage.value = null

  if (source === 'default') {
    defaultBannerId.value = ''
    imagePreview.value = null
    return
  }

  defaultBannerId.value = ''

  if (
    banner.value &&
    banner.value.image_source === 'upload' &&
    banner.value.image_url
  ) {
    imagePreview.value =
      getImageUrl(
        banner.value.image_url
      )
  } else {
    imagePreview.value = null
  }
}

// 儲存首頁橫幅
async function saveBanner() {
  if (saving.value) {
    return
  }

  if (
    imageSource.value === 'default' &&
    !defaultBannerId.value
  ) {
    showError('請選擇預設橫幅')
    return
  }

  if (
    imageSource.value === 'upload' &&
    !selectedImage.value &&
    !banner.value
  ) {
    showError('請上傳橫幅圖片')
    return
  }

  if (
    imageSource.value === 'upload' &&
    !selectedImage.value &&
    banner.value &&
    banner.value.image_source !== 'upload'
  ) {
    showError('請上傳橫幅圖片')
    return
  }

  saving.value = true

  try {
    const isUpdate = !!banner.value
    const formData = new FormData()

    if (banner.value) {
      formData.append(
        'banner_id',
        banner.value.banner_id
      )
    }

    formData.append(
      'image_source',
      imageSource.value
    )

    if (imageSource.value === 'default') {
      formData.append(
        'default_banner_id',
        defaultBannerId.value
      )
    }

    if (
      imageSource.value === 'upload' &&
      selectedImage.value
    ) {
      formData.append(
        'image',
        selectedImage.value
      )
    }

    formData.append(
      'title',
      title.value
    )

    formData.append(
      'description',
      description.value
    )

    let data

    if (isUpdate) {
      data = await updateHomepageBanner(formData)
    } else {
      data = await createHomepageBanner(formData)
    }

    banner.value = data.banner
    selectedImage.value = null

    if (
      data.banner.image_source === 'default'
    ) {
      imageSource.value = 'default'

      defaultBannerId.value =
        data.banner.default_banner_id || ''

    } else {
      imageSource.value = 'upload'
      defaultBannerId.value = ''

      imagePreview.value =
        getImageUrl(
          data.banner.image_url
        )
    }

    showError((isUpdate ? '橫幅更新成功' : '橫幅新增成功' || data.message))

  } catch (e) {
    console.error('儲存首頁橫幅失敗:', e)

    if (e.status === 400) {
      showError(`橫幅資料格式錯誤：${e.message || 'Bad Request'}`)
    } else if (e.status === 403) {
      showError(`您沒有權限執行此操作：${e.message || 'Forbidden'}`)
    } else if (e.status === 404) {
      showError(`找不到橫幅：${e.message || 'Not Found'}`)
    } else if (e.status === 409) {
      showError(`橫幅已存在：${e.message || 'Conflict'}`)
    } else {
      showError(`儲存橫幅失敗：${e.message || '未知錯誤'}`)
    }
  } finally {
    saving.value = false
  }
}

// 刪除橫幅圖片
async function removeBannerImage() {
  if (
    saving.value ||
    !banner.value ||
    !banner.value.image_url
  ) {
    return
  }

  if (imageSource.value !== 'upload') {
    return
  }

  if (
    banner.value.default_banner_id !== null
  ) {
    return
  }

  saving.value = true

  try {
    const data = await deleteHomepageBannerImage(
      banner.value.banner_id
    )

    imagePreview.value = null
    selectedImage.value = null

    banner.value.image_url = null
    banner.value.image_source = 'upload'
    banner.value.default_banner_id = null

    imageSource.value = 'upload'
    defaultBannerId.value = ''

    showError('橫幅圖片刪除成功' || data.message)

  } catch (e) {
    console.error('刪除橫幅圖片失敗:', e)

    if (e.status === 400) {
      showError(`無法刪除橫幅圖片：${e.message || 'Bad Request'}`)
    } else if (e.status === 403) {
      showError(`您沒有權限執行此操作：${e.message || 'Forbidden'}`)
    } else if (e.status === 404) {
      showError(`找不到橫幅圖片：${e.message || 'Not Found'}`)
    } else {
      showError(`刪除橫幅圖片失敗：${e.message || '未知錯誤'}`)
    }
  } finally {
    saving.value = false
  }
}

// 開啟刪除橫幅確認
function openDeleteModal() {
  showDeleteModal.value = true
}

function closeDeleteModal() {
  showDeleteModal.value = false
}

// 刪除首頁橫幅
async function removeBanner() {
  if (saving.value) {
    return
  }

  saving.value = true

  try {
    const data = await deleteHomepageBanner()

    banner.value = null
    imagePreview.value = null
    selectedImage.value = null

    title.value = ''
    description.value = ''
    defaultBannerId.value = ''
    imageSource.value = 'default'

    showDeleteModal.value = false

    showError('橫幅刪除成功' || data.message)

  } catch (e) {
    console.error('刪除首頁橫幅失敗:', e)

    showDeleteModal.value = false

    if (e.status === 403) {
      showError(`您沒有權限執行此操作：${e.message || 'Forbidden'}`)
    } else if (e.status === 404) {
      showError(`找不到橫幅：${e.message || 'Not Found'}`)
    } else {
      showError(`刪除橫幅失敗：${e.message || '未知錯誤'}`)
    }
  } finally {
    saving.value = false
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
          <div v-if="loading">
            載入中...
          </div>

          <div v-else>
            <!-- 標題 -->
            <div class="position-relative mb-4">
              <h2 class="mt-2 mb-3">
                首頁橫幅
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
                  用於設定首頁顯示的主要橫幅圖片，可選擇預設橫幅或上傳自訂圖片
                </small>
              </CCollapse>
            </div>

            <!-- 橫幅區塊狀態 -->
            <CCard class="mb-4">
              <CCardBody>
                <div
                  class="d-flex justify-content-between align-items-center"
                >
                  <div>
                    <h4 class="mb-1">
                      橫幅區塊
                    </h4>

                    <small class="text-body-secondary">
                      首頁目前的橫幅區塊狀態
                    </small>
                  </div>

                  <span
                    v-if="bannerSectionEnable"
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

            <!-- 橫幅圖片 -->
            <CCard class="mb-4">
              <CCardBody>
                <h4 class="mb-4">
                  橫幅圖片
                </h4>

                <CRow class="mb-4">
                  <CCol :md="6">
                    <CFormLabel>
                      圖片來源
                    </CFormLabel>

                    <CFormSelect
                      v-model="imageSource"
                      @change="
                        changeImageSource(
                          imageSource
                        )
                      "
                    >
                      <option value="default">
                        預設橫幅
                      </option>

                      <option value="upload">
                        上傳圖片
                      </option>
                    </CFormSelect>
                  </CCol>
                </CRow>

                <!-- 預設橫幅 -->
                <CRow
                  v-if="
                    imageSource === 'default'
                  "
                  class="mb-4"
                >
                  <CCol :md="6">
                    <CFormLabel>
                      選擇預設橫幅
                    </CFormLabel>

                    <CFormSelect
                      v-model="defaultBannerId"
                    >
                      <option value="">
                        請選擇
                      </option>

                      <option
                        v-for="item in defaultBanners"
                        :key="
                          item.default_banner_id
                        "
                        :value="
                          item.default_banner_id
                        "
                      >
                        {{ item.name }}
                      </option>
                    </CFormSelect>
                  </CCol>
                </CRow>

                <!-- 上傳圖片 -->
                <CRow
                  v-if="
                    imageSource === 'upload'
                  "
                  class="mb-4"
                >
                  <CCol :md="6">
                    <CFormLabel>
                      上傳橫幅
                    </CFormLabel>

                    <CFormInput
                      type="file"
                      accept="image/jpeg,image/png,image/webp"
                      @change="
                        handleImageChange
                      "
                    />

                    <small class="text-body-secondary">
                      建議圖片比例為
                      1920 × 600（16:5）
                    </small>
                  </CCol>
                </CRow>

                <!-- 圖片預覽 -->
                <div v-if="imagePreview">
                  <CFormLabel>
                    圖片預覽
                  </CFormLabel>

                  <div class="banner-preview mt-2">
                    <CImage
                      :src="imagePreview"
                      fluid
                      class="banner-preview-image"
                    />
                  </div>

                  <!-- 刪除圖片 -->
                  <div
                    v-if="
                      banner &&
                      imageSource === 'upload' &&
                      banner.image_source === 'upload' &&
                      banner.image_url
                    "
                    class="mt-2"
                  >
                    <CButton
                      color="danger"
                      variant="outline"
                      :disabled="saving"
                      @click="removeBannerImage"
                    >
                      刪除圖片
                    </CButton>
                  </div>
                </div>
              </CCardBody>
            </CCard>

            <!-- 橫幅內容 -->
            <CCard class="mb-4">
              <CCardBody>
                <h4 class="mb-4">
                  橫幅內容
                </h4>

                <CRow>
                  <CCol :md="6">
                    <CFormLabel>
                      標題
                    </CFormLabel>

                    <CFormInput
                      v-model="title"
                      maxlength="20"
                      placeholder="最多 20 字"
                    />
                  </CCol>
                </CRow>

                <CRow class="mt-3">
                  <CCol :md="8">
                    <CFormLabel>
                      描述
                    </CFormLabel>

                    <CFormTextarea
                      v-model="description"
                      rows="4"
                      maxlength="80"
                      placeholder="最多 80 字"
                    />
                  </CCol>
                </CRow>
              </CCardBody>
            </CCard>

            <!-- 操作 -->
            <div
              class="d-flex justify-content-end mb-5"
            >
              <CButton
                v-if="
                  banner &&
                  imageSource === 'upload' &&
                  banner.image_source === 'upload' &&
                  banner.image_url
                "
                color="danger"
                class="me-2"
                :disabled="saving"
                @click="openDeleteModal"
              >
                刪除橫幅
              </CButton>

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
                :disabled="saving"
                @click="saveBanner"
              >
                {{
                  saving
                    ? '儲存中...'
                    : '儲存設定'
                }}
              </CButton>
            </div>
          </div>
        </CContainer>
      </div>

      <AppFooter />
    </div>

    <!-- 錯誤 / 成功 Modal -->
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
        {{ errorMessage }}
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

    <!-- 刪除橫幅確認 -->
    <CModal
      :visible="showDeleteModal"
      @close="closeDeleteModal"
    >
      <CModalHeader class="border-0">
        <CModalTitle>
          刪除橫幅
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        確定要刪除此首頁橫幅嗎？
      </CModalBody>

      <CModalFooter class="border-0">
        <CButton
          color="secondary"
          :disabled="saving"
          @click="closeDeleteModal"
        >
          取消
        </CButton>

        <CButton
          color="danger"
          :disabled="saving"
          @click="removeBanner"
        >
          {{
            saving
              ? '刪除中...'
              : '確定刪除'
          }}
        </CButton>
      </CModalFooter>
    </CModal>
  </div>
</template>

<style scoped>
.banner-preview {
  width: 100%;
  max-width: 960px;
  aspect-ratio: 1920 / 600;
  overflow: hidden;
  border-radius: 4px;
}

.banner-preview-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
</style>
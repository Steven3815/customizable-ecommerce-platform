<script setup>
import {
  ref,
  onMounted,
  watch
} from 'vue'

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

import AppSidebar from '../../../components/store/AppSidebar.vue'
import AppHeader from '../../../components/store/AppHeader.vue'
import AppFooter from '../../../components/store/AppFooter.vue'

import {
  useRoute,
  useRouter
} from 'vue-router'

import {
  getProduct,
  updateProduct,
  deleteProductImage,
  deleteProduct,
  reorderProductImages
} from '../../../api/store'

const route = useRoute()
const router = useRouter()

const product = ref(null)

const productName = ref('')
const description = ref('')
const price = ref('')
const hasSpec = ref(false)
const stock = ref('')
const specName = ref('')
const status = ref('active')

const specs = ref([])
const images = ref([])

const selectedImage = ref(null)
const imagePreview = ref(null)

const loading = ref(true)
const saving = ref(false)
const deleting = ref(false)
const reorderingImage = ref(false)

const showErrorModal = ref(false)
const errorMessage = ref('')

const showDescription = ref(false)

const showDeleteModal = ref(false)

const showDeleteImageModal = ref(false)
const deletingImage = ref(null)

// 統一轉換是否使用規格
function normalizeHasSpec(value) {
  return (
    value === true ||
    value === 1 ||
    value === '1' ||
    value === 'true'
  )
}

// 確保 hasSpec 永遠是 Boolean
watch(hasSpec, (value) => {
  const normalizedValue = normalizeHasSpec(value)

  if (value !== normalizedValue) {
    hasSpec.value = normalizedValue
  }
})

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

  if (
    url.startsWith('http://') ||
    url.startsWith('https://')
  ) {
    return url
  }

  return `http://localhost/ecommerce-platform/backend${url}`
}

// 取得商品資料
async function loadProduct() {
  loading.value = true

  clearSelectedImage()

  // 清除尚未儲存的新圖片
  images.value.forEach((image) => {
    if (
      image.isNew &&
      image.image_url?.startsWith('blob:')
    ) {
      URL.revokeObjectURL(image.image_url)
    }
  })

  try {
    const productId = route.params.productId
    const data = await getProduct(productId)

    product.value = data.product
    productName.value = data.product.product_name || ''
    description.value = data.product.description || ''
    price.value = data.product.price ?? ''
    hasSpec.value = normalizeHasSpec(data.product.has_spec)
    stock.value = data.product.stock ?? ''
    specName.value = data.product.spec_name || ''
    status.value = data.product.status || 'active'
    specs.value = data.product.specs || []
    images.value = (data.product.images || []).map((image) => ({
      ...image,
      file: null,
      isNew: false
    }))
  } catch (error) {
    console.error('取得商品詳細資料失敗:', error)

    if (error.status === 401) {
      showError('登入狀態已失效，請重新登入')
    } else if (error.status === 403) {
      showError('您沒有權限查看此商品')
    } else if (error.status === 404) {
      showError('找不到此商品')
    } else {
      showError(error.message || '取得商品詳細資料失敗')
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadProduct()
})

function goBack() {
  router.push({
    name: 'ProductList'
  })
}

// 新增規格
function addSpec() {
  if (specs.value.length >= 10) {
    showError('最多只能新增 10 項商品規格')
    return
  }

  specs.value.push({
    spec_id: null,
    spec_name: '',
    stock: '',
    status: 'active'
  })
}

// 撤銷尚未儲存的規格
function removeUnsavedSpec() {
  const index = specs.value
    .map(spec => spec.spec_id)
    .lastIndexOf(null)

  if (index === -1) {
    return
  }

  specs.value.splice(index, 1)
}

// 圖片選擇
function handleImageChange(event) {
  const file = event.target.files?.[0]

  if (!file) {
    return
  }

  if (imagePreview.value) {
    URL.revokeObjectURL(imagePreview.value)
  }

  selectedImage.value = file
  imagePreview.value = URL.createObjectURL(file)

  event.target.value = ''
}

// 確認選擇圖片
function confirmSelectedImage() {
  if (!selectedImage.value) {
    return
  }

  images.value.push({
    image_id: null,
    image_url: imagePreview.value,
    file: selectedImage.value,
    isNew: true
  })

  selectedImage.value = null
  imagePreview.value = null
}

// 取消選擇圖片
function cancelSelectedImage() {
  clearSelectedImage()
}

// 清除目前待確認圖片
function clearSelectedImage() {
  if (imagePreview.value) {
    URL.revokeObjectURL(imagePreview.value)
  }

  selectedImage.value = null
  imagePreview.value = null
}

// 移除新加入但尚未儲存的圖片
function removeNewImage(image) {
  const index = images.value.indexOf(image)

  if (index === -1) {
    return
  }

  if (image.image_url?.startsWith('blob:')) {
    URL.revokeObjectURL(image.image_url)
  }

  images.value.splice(index, 1)
}

// 儲存商品
async function saveProduct() {
  if (!product.value || saving.value) {
    return
  }

  if (!productName.value.trim()) {
    showError('請輸入商品名稱')
    return
  }

  if (
    price.value === '' ||
    Number(price.value) < 0
  ) {
    showError('請輸入有效的商品價格')
    return
  }

  if (!hasSpec.value) {
    if (
      stock.value === '' ||
      Number(stock.value) < 0 ||
      !Number.isInteger(Number(stock.value))
    ) {
      showError('請輸入有效的商品庫存')
      return
    }
  }

  if (hasSpec.value) {
    if (!specName.value.trim()) {
      showError('請輸入規格名稱')
      return
    }

    if (!specs.value.length) {
      showError('請至少新增一項商品規格')
      return
    }

    for (const spec of specs.value) {
      if (!spec.spec_name.trim()) {
        showError('請輸入完整的規格名稱')
        return
      }

      if (
        spec.stock === '' ||
        Number(spec.stock) < 0 ||
        !Number.isInteger(Number(spec.stock))
      ) {
        showError('請輸入有效的規格庫存')
        return
      }

      if (
        spec.status !== 'active' &&
        spec.status !== 'inactive'
      ) {
        showError('規格狀態錯誤')
        return
      }
    }
  }

  saving.value = true

  try {
    const formData = new FormData()

    console.log('送出的商品狀態:', status.value)

    formData.append('product_id', product.value.product_id)
    formData.append('product_name', productName.value.trim())
    formData.append('description', description.value)
    formData.append('price', price.value)
    formData.append('has_spec', hasSpec.value ? 1 : 0)
    formData.append('status', status.value)

    if (hasSpec.value) {
      formData.append('spec_name', specName.value.trim())

      const productSpecs = specs.value.map((spec) => {
        const specData = {
          spec_name: spec.spec_name.trim(),
          stock: Number(spec.stock),
          status: spec.status
        }

        if (spec.spec_id) {
          specData.spec_id = spec.spec_id
        }

        return specData
      })

      formData.append('specs', JSON.stringify(productSpecs))
    } else {
      formData.append('stock', stock.value)
    }

    // 只上傳新加入的圖片
    images.value
      .filter(image => image.isNew && image.file)
      .forEach((image) => {
        formData.append('images[]', image.file)
      })

    const data = await updateProduct(formData)

    product.value = data.product
    productName.value = data.product.product_name || ''
    description.value = data.product.description || ''
    price.value = data.product.price ?? ''
    hasSpec.value = normalizeHasSpec(data.product.has_spec)
    stock.value = data.product.stock ?? ''
    specName.value = data.product.spec_name || ''
    status.value = data.product.status || 'active'
    specs.value = data.product.specs || []
    images.value = (data.product.images || []).map((image) => ({
      ...image,
      file: null,
      isNew: false
    }))

    clearSelectedImage()

    showError('商品更新成功')
  } catch (error) {
    console.error('更新商品資料失敗:', error)

    if (error.status === 400) {
      showError(error.message || '商品資料格式錯誤')
    } else if (error.status === 401) {
      showError('登入狀態已失效，請重新登入')
    } else if (error.status === 403) {
      showError('您沒有權限更新此商品')
    } else if (error.status === 404) {
      showError('找不到此商品')
    } else if (error.status === 409) {
      showError(error.message || '商品資料發生衝突')
    } else {
      showError(error.message || '更新商品失敗')
    }
  } finally {
    saving.value = false
  }
}

// 開啟刪除圖片確認
function confirmDeleteImage(image) {
  if (images.value.length <= 1) {
    showError('商品至少需要保留 1 張圖片')
    return
  }

  deletingImage.value = image
  showDeleteImageModal.value = true
}

function closeDeleteImageModal() {
  showDeleteImageModal.value = false
  deletingImage.value = null
}

// 刪除商品圖片
async function removeProductImage() {
  if (!deletingImage.value) {
    return
  }

  const image = deletingImage.value

  // 尚未儲存的新圖片只需要從前端移除
  if (image.isNew) {
    removeNewImage(image)

    showDeleteImageModal.value = false
    deletingImage.value = null

    return
  }

  saving.value = true

  try {
    const data = await deleteProductImage(image.image_id)

    images.value = (data.images || []).map((image) => ({
      ...image,
      file: null,
      isNew: false
    }))

    showDeleteImageModal.value = false
    deletingImage.value = null
  } catch (error) {
    console.error('刪除商品圖片失敗:', error)

    if (error.status === 400) {
      showError(error.message || '無法刪除商品圖片')
    } else if (error.status === 401) {
      showError('登入狀態已失效，請重新登入')
    } else if (error.status === 403) {
      showError('您沒有權限刪除此商品圖片')
    } else if (error.status === 404) {
      showError('找不到商品圖片')
    } else if (error.status === 409) {
      showError(error.message || '無法刪除商品圖片')
    } else {
      showError(error.message || '刪除商品圖片失敗')
    }
  } finally {
    saving.value = false
  }
}

// 移動商品圖片
async function moveImage(index, direction) {
  if (reorderingImage.value || !product.value) {
    return
  }

  const newIndex = index + direction

  if (
    newIndex < 0 ||
    newIndex >= images.value.length
  ) {
    return
  }

  const currentImage = images.value[index]
  const targetImage = images.value[newIndex]

  // 新圖片尚未儲存，先只調整前端表格順序
  if (
    currentImage.isNew ||
    targetImage.isNew
  ) {
    const temp = images.value[index]

    images.value[index] = images.value[newIndex]
    images.value[newIndex] = temp

    return
  }

  const oldImages = [...images.value]
  const newImages = [...images.value]
  const temp = newImages[index]

  newImages[index] = newImages[newIndex]
  newImages[newIndex] = temp

  images.value = newImages
  reorderingImage.value = true

  try {
    const imageIds = images.value.map(image => image.image_id)

    await reorderProductImages(
      product.value.product_id,
      imageIds
    )
  } catch (error) {
    console.error('重新排列商品圖片失敗:', error)

    images.value = oldImages

    if (error.status === 400) {
      showError(error.message || '圖片排序資料錯誤')
    } else if (error.status === 401) {
      showError('登入狀態已失效，請重新登入')
    } else if (error.status === 403) {
      showError('您沒有權限修改商品圖片順序')
    } else if (error.status === 404) {
      showError('找不到此商品')
    } else if (error.status === 409) {
      showError(error.message || '商品圖片順序無法更新')
    } else {
      showError(error.message || '重新排列商品圖片失敗')
    }
  } finally {
    reorderingImage.value = false
  }
}

// 開啟刪除商品確認
function openDeleteModal() {
  showDeleteModal.value = true
}

function closeDeleteModal() {
  showDeleteModal.value = false
}

// 刪除商品
async function removeProduct() {
  if (
    saving.value ||
    deleting.value ||
    !product.value
  ) {
    return
  }

  deleting.value = true

  try {
    await deleteProduct(product.value.product_id)

    showDeleteModal.value = false

    router.push({
      name: 'ProductList'
    })
  } catch (error) {
    console.error('刪除商品失敗:', error)

    showDeleteModal.value = false

    if (error.status === 400) {
      showError(error.message || '商品資料錯誤')
    } else if (error.status === 401) {
      showError('登入狀態已失效，請重新登入')
    } else if (error.status === 403) {
      showError('您沒有權限刪除此商品')
    } else if (error.status === 404) {
      showError('找不到此商品')
    } else if (error.status === 409) {
      showError(error.message || '商品目前無法刪除')
    } else {
      showError(error.message || '刪除商品失敗')
    }
  } finally {
    deleting.value = false
  }
}

// 取消修改
function cancelChanges() {
  clearSelectedImage()

  images.value.forEach((image) => {
    if (
      image.isNew &&
      image.image_url?.startsWith('blob:')
    ) {
      URL.revokeObjectURL(image.image_url)
    }
  })

  loadProduct()
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
          <div
            v-if="loading"
            class="text-center py-5 text-body-secondary"
          >
            載入中...
          </div>

          <!-- 找不到商品 -->
          <div
            v-else-if="!product"
            class="text-center py-5"
          >
            <p class="text-body-secondary mb-3">
              找不到商品資料
            </p>

            <CButton
              color="primary"
              @click="goBack"
            >
              返回商品列表
            </CButton>
          </div>

          <div v-else>

            <!-- 頁面標題 -->
            <div class="position-relative mb-4">
              <h2 class="mt-2 mb-2">
                商品詳細
              </h2>

              <CButton
                color="link"
                class="text-decoration-none p-0"
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
                <small class="d-block mt-2 text-body-secondary">
                  <strong>商品狀態：</strong>
                  上架後商品會顯示於商店，顧客可以瀏覽及購買；下架後會對顧客隱藏
                  <br>

                  <strong>是否啟用規格：</strong>
                  不使用規格時，直接填價格及庫存；使用規格時，可設定規格名稱及各規格的庫存
                  <br>

                  <strong>商品分類：</strong>
                  恕不開放更改商品類別，建議直接至對應類別新增商品
                  <br>

                </small>
              </CCollapse>

              <CButton
                color="secondary"
                class="position-absolute top-0 end-0"
                @click="goBack"
              >
                返回商品列表
              </CButton>
            </div>

            <!-- 商品資訊 -->
            <CCard class="mb-4">
              <CCardBody>

                <h5 class="mb-4">
                  商品資訊
                </h5>

                <!-- 商品狀態 -->
                <CRow class="mb-4">
                  <CCol :md="4">
                    <CFormLabel>
                      商品狀態
                    </CFormLabel>

                    <CFormSelect v-model="status">
                      <option value="active">
                        上架
                      </option>

                      <option value="hidden">
                        下架
                      </option>
                    </CFormSelect>
                  </CCol>
                </CRow>

                <!-- 商品名稱 -->
                <CRow class="mb-4">
                  <CCol :md="4">
                    <CFormLabel>
                      商品名稱
                    </CFormLabel>

                    <CFormInput
                      v-model="productName"
                      type="text"
                      maxlength="200"
                      placeholder="請輸入商品名稱"
                    />
                  </CCol>
                </CRow>

                <!-- 商品分類 -->
                <CRow class="mb-4">
                  <CCol :md="4">
                    <CFormLabel>
                      商品分類
                    </CFormLabel>

                    <div class="text-body-secondary">
                      {{ product.category.category_name || '-' }}
                    </div>
                  </CCol>
                </CRow>

                <!-- 商品描述 -->
                <CRow class="mb-4">
                  <CCol :md="4">
                    <CFormLabel>
                      商品描述
                    </CFormLabel>

                    <CFormTextarea
                      v-model="description"
                      rows="5"
                      maxlength="5000"
                      placeholder="請輸入商品描述"
                    />
                  </CCol>
                </CRow>

                <!-- 商品價格 -->
                <CRow>
                  <CCol :md="4">
                    <CFormLabel>
                      商品價格
                    </CFormLabel>

                    <CFormInput
                      v-model="price"
                      type="number"
                      min="0"
                      placeholder="請輸入商品價格"
                    />
                  </CCol>
                </CRow>
                <small class="text-body-secondary">
                  <br>
                  <strong>更多操作：</strong>
                  可至「首頁商品管理」新增商品
                </small>
                 
              </CCardBody>
            </CCard>

            <!-- 商品規格 -->
            <CCard class="mb-4">
              <CCardBody>

                <h5 class="mb-4">
                  商品規格
                </h5>

                <!-- 是否啟用規格 -->
                <CRow class="mb-4">
                  <CCol :md="4">
                    <CFormLabel>
                      是否啟用規格
                    </CFormLabel>

                    <CFormSelect
                      :value="hasSpec ? 'true' : 'false'"
                      @change="hasSpec = $event.target.value === 'true'"
                    >
                      <option value="false">
                        不使用規格
                      </option>

                      <option value="true">
                        使用規格
                      </option>
                    </CFormSelect>
                  </CCol>
                </CRow>

                <!-- 有規格 -->
                <template v-if="hasSpec">

                  <!-- 規格名稱 -->
                  <CRow class="mb-4">
                    <CCol :md="4">
                      <CFormLabel>
                        規格名稱
                      </CFormLabel>

                      <CFormInput
                        v-model="specName"
                        type="text"
                        maxlength="100"
                        placeholder="例如：尺寸"
                      />
                    </CCol>
                  </CRow>

                  <!-- 規格列表標題 -->
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <CFormLabel class="mb-0">
                      規格列表
                    </CFormLabel>

                    <div>
                      <CButton
                        color="secondary"
                        size="sm"
                        class="me-2"
                        :disabled="saving || !specs.some(spec => !spec.spec_id)"
                        @click="removeUnsavedSpec"
                      >
                        撤銷規格
                      </CButton>

                      <CButton
                        color="primary"
                        size="sm"
                        :disabled="saving || specs.length >= 10"
                        @click="addSpec"
                      >
                        新增規格
                      </CButton>
                    </div>
                  </div>

                  <CTable
                    bordered
                    hover
                    responsive
                    class="text-center align-middle"
                  >
                    <CTableHead>
                      <CTableRow>
                        <CTableHeaderCell>
                          規格
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          庫存
                        </CTableHeaderCell>

                        <CTableHeaderCell>
                          狀態
                        </CTableHeaderCell>
                      </CTableRow>
                    </CTableHead>

                    <CTableBody>

                      <CTableRow
                        v-for="(spec, index) in specs"
                        :key="spec.spec_id || `new-${index}`"
                      >

                        <!-- 規格 -->
                        <CTableDataCell>
                          <CFormInput
                            v-model="spec.spec_name"
                            type="text"
                            maxlength="100"
                            placeholder="請輸入規格 例: 大/中/小"
                          />
                        </CTableDataCell>

                        <!-- 庫存 -->
                        <CTableDataCell>
                          <CFormInput
                            v-model="spec.stock"
                            type="number"
                            min="0"
                            placeholder="請輸入庫存"
                          />
                        </CTableDataCell>

                        <!-- 狀態 -->
                        <CTableDataCell>
                          <CFormSelect v-model="spec.status">
                            <option value="active">
                              啟用
                            </option>

                            <option value="inactive">
                              停用
                            </option>
                          </CFormSelect>
                        </CTableDataCell>

                      </CTableRow>

                      <CTableRow v-if="!specs.length">
                        <CTableDataCell
                          colspan="3"
                          class="text-center text-body-secondary"
                        >
                          尚無商品規格，請點擊「新增規格」
                        </CTableDataCell>
                      </CTableRow>

                    </CTableBody>
                  </CTable>

                </template>

                <!-- 無規格 -->
                <CRow v-else>
                  <CCol :md="4">
                    <CFormLabel>
                      庫存
                    </CFormLabel>

                    <CFormInput
                      v-model="stock"
                      type="number"
                      min="0"
                      placeholder="請輸入商品庫存"
                    />
                  </CCol>
                </CRow>

              </CCardBody>
            </CCard>

            <!-- 商品圖片 -->
            <CCard class="mb-4">
              <CCardBody>

                <h5 class="mb-4">
                  商品圖片
                </h5>

                <!-- 圖片表格 -->
                <div
                  v-if="images.length"
                  class="mb-4"
                >
                  <CTable
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

                        <CTableHeaderCell class="text-center">
                          操作
                        </CTableHeaderCell>
                      </CTableRow>
                    </CTableHead>

                    <CTableBody>
                      <CTableRow
                        v-for="(image, index) in images"
                        :key="image.image_id || `new-${index}`"
                      >

                        <!-- 順序 -->
                        <CTableDataCell class="text-center">
                          {{ index + 1 }}
                        </CTableDataCell>

                        <!-- 圖片 -->
                        <CTableDataCell>
                          <div class="product-table-image">
                            <img
                              :src="getImageUrl(image.image_url)"
                              alt="商品圖片"
                              class="product-table-image-content"
                            >
                          </div>
                        </CTableDataCell>

                        <!-- 操作 -->
                        <CTableDataCell class="text-center">

                          <CButton
                            color="secondary"
                            size="sm"
                            class="me-2"
                            :disabled="index === 0 || reorderingImage || saving"
                            @click="moveImage(index, -1)"
                          >
                            上移
                          </CButton>

                          <CButton
                            color="secondary"
                            size="sm"
                            class="me-2"
                            :disabled="index === images.length - 1 || reorderingImage || saving"
                            @click="moveImage(index, 1)"
                          >
                            下移
                          </CButton>

                          <CButton
                            color="danger"
                            size="sm"
                            :disabled="saving"
                            @click="confirmDeleteImage(image)"
                          >
                            刪除
                          </CButton>

                        </CTableDataCell>

                      </CTableRow>
                    </CTableBody>
                  </CTable>
                </div>

                <!-- 沒有圖片 -->
                <div
                  v-else
                  class="text-body-secondary mb-4"
                >
                  尚未設定商品圖片
                </div>

                <!-- 新增商品圖片 -->
                <div>
                  <CFormLabel>
                    新增商品圖片
                  </CFormLabel>

                  <CFormInput
                    type="file"
                    accept="image/*"
                    @change="handleImageChange"
                  />

                  <small class="d-block mt-2 text-body-secondary">
                    建議圖片比例為 1200 × 800（3 : 2）
                  </small>

                  <!-- 待確認圖片 -->
                  <div
                    v-if="imagePreview"
                    class="product-preview-section mt-4"
                  >
                    <CFormLabel>
                      圖片預覽
                    </CFormLabel>

                    <div class="product-preview-list mt-2">
                      <div class="product-preview-item">

                        <img
                          :src="imagePreview"
                          alt="圖片預覽"
                          class="product-preview-image"
                        >

                      </div>
                    </div>

                    <div class="mt-3">
                      <CButton
                        color="primary"
                        class="me-2"
                        :disabled="saving"
                        @click="confirmSelectedImage"
                      >
                        確認選擇
                      </CButton>

                      <CButton
                        color="secondary"
                        :disabled="saving"
                        @click="cancelSelectedImage"
                      >
                        取消選擇
                      </CButton>
                    </div>
                  </div>
                </div>

              </CCardBody>
            </CCard>

            <!-- 操作 -->
            <div class="d-flex justify-content-end mb-5">

              <CButton
                color="danger"
                class="me-2"
                :disabled="saving || deleting"
                @click="openDeleteModal"
              >
                刪除商品
              </CButton>

              <CButton
                color="secondary"
                class="me-2"
                :disabled="saving || deleting"
                @click="cancelChanges"
              >
                取消修改
              </CButton>

              <CButton
                color="primary"
                :disabled="saving || deleting"
                @click="saveProduct"
              >
                {{ saving ? '儲存中...' : '儲存設定' }}
              </CButton>

            </div>

          </div>

        </CContainer>
      </div>

      <AppFooter />
    </div>

    <!-- 刪除商品圖片 -->
    <CModal
      :visible="showDeleteImageModal"
      @close="closeDeleteImageModal"
    >
      <CModalHeader class="border-0">
        <CModalTitle>
          刪除商品圖片
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        確定要刪除這張商品圖片嗎？
      </CModalBody>

      <CModalFooter class="border-0">
        <CButton
          color="secondary"
          :disabled="saving"
          @click="closeDeleteImageModal"
        >
          取消
        </CButton>

        <CButton
          color="danger"
          :disabled="saving"
          @click="removeProductImage"
        >
          {{ saving ? '刪除中...' : '確定刪除' }}
        </CButton>
      </CModalFooter>
    </CModal>

    <!-- 刪除商品 -->
    <CModal
      :visible="showDeleteModal"
      @close="closeDeleteModal"
    >
      <CModalHeader class="border-0">
        <CModalTitle>
          刪除商品
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        確定要刪除此商品嗎？刪除後將無法恢復。
      </CModalBody>

      <CModalFooter class="border-0">
        <CButton
          color="secondary"
          :disabled="deleting"
          @click="closeDeleteModal"
        >
          取消
        </CButton>

        <CButton
          color="danger"
          :disabled="deleting"
          @click="removeProduct"
        >
          {{ deleting ? '刪除中...' : '確定刪除' }}
        </CButton>
      </CModalFooter>
    </CModal>

    <!-- 錯誤 / 提示 -->
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
:deep(.form-control),
:deep(.form-select) {
  font-size: 14px;
}

/* 隱藏 number 輸入框上下箭頭 */
:deep(input[type='number']::-webkit-inner-spin-button),
:deep(input[type='number']::-webkit-outer-spin-button) {
  -webkit-appearance: none;
  margin: 0;
}

:deep(input[type='number']) {
  -moz-appearance: textfield;
  appearance: none;
}

:deep(.table) {
  font-size: 14px;
}

/* 商品圖片列表 */
.product-table-image {
  width: 240px;
  aspect-ratio: 3 / 2;
  overflow: hidden;
  border-radius: 4px;
}

.product-table-image-content {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

/* 商品圖片預覽 */
.product-preview-list {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
}

.product-preview-item {
  width: 180px;
}

.product-preview-image {
  width: 180px;
  aspect-ratio: 3 / 2;
  object-fit: cover;
  border: 1px solid var(--cui-border-color);
  border-radius: 4px;
  display: block;
}
</style>
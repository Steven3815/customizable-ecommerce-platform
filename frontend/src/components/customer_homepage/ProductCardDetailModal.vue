<template>
  <div>
    <CModal
      v-if="isLoggedIn"
      :visible="visible"
      @close="closeModal"
      alignment="center"
      class="product-modal"
    >
      <CModalHeader class="border-0">
      </CModalHeader>

      <CModalBody>
        <div v-if="addedMessage" class="added-message">
          已加入 {{ addedQuantity }} 件
        </div>

        <template v-else>
          <!-- 載入商品資料 -->
          <div
            v-if="loading"
            class="text-center py-4"
          >
            載入中...
          </div>

          <!-- 已登入且商品資料取得成功 -->
          <div
            v-else-if="product"
            class="product-detail"
          >
            <!-- 商品圖片 -->
            <div
              v-if="product.images?.length && product.images[0].image_url"
              class="product-image-wrapper"
            >
              <CButton
                v-if="product.images.length > 1"
                color="light"
                class="image-prev"
                @click="previousImage"
              >
                ‹
              </CButton>

              <img
                :src="product.images[currentImageIndex].image_url"
                :alt="product.product_name"
                class="modal-product-image"
              />

              <CButton
                v-if="product.images.length > 1"
                color="light"
                class="image-next"
                @click="nextImage"
              >
                ›
              </CButton>
            </div>

            <div
              v-else
              class="image-placeholder"
            >
              無照片
            </div>

            <!-- 商品名稱 -->
            <h4 class="product-name">
              {{ product.product_name }}
            </h4>

            <!-- 商品描述 -->
            <p
              v-if="product.description"
              :ref="setDescriptionRef"
              :class="[
                'product-description',
                descriptionExpanded ? 'expanded' : ''
              ]"
            >
              {{ product.description }}
            </p>

            <!-- 展開 / 收合 -->
            <CButton
              v-if="
                product.description &&
                descriptionOverflow
              "
              color="secondary"
              variant="ghost"
              size="sm"
              class="description-button"
              @click="toggleDescription"
            >
              <CIcon
                :icon="
                  descriptionExpanded
                    ? 'cilChevronTop'
                    : 'cilChevronBottom'
                "
                class="me-1"
              />
              {{
                descriptionExpanded
                  ? '收合'
                  : '展開'
              }}
            </CButton>

            <!-- 無規格商品 -->
            <template v-if="!product.has_spec">
              <!-- 價格 -->
              <CRow class="align-items-center mt-4 mb-3">
                <CCol :md="3">
                  <div class="form-label mb-0">
                    價格
                  </div>
                </CCol>

                <CCol :md="9">
                  <div
                    v-if="product.price !== null"
                    class="modal-price"
                  >
                    ${{ Number(product.price).toFixed(2) }}
                  </div>

                  <div
                    v-else
                    class="stock text-start"
                  >
                    尚未設定價格
                  </div>
                </CCol>
              </CRow>

              <!-- 庫存 -->
              <CRow class="align-items-center mb-3">
                <CCol :md="3">
                  <div class="form-label mb-0">
                    庫存
                  </div>
                </CCol>

                <CCol :md="9">
                  <div
                    v-if="Number(product.stock) > 0"
                    class="stock text-start"
                  >
                    {{ Number(product.stock) }} 件
                  </div>

                  <div
                    v-else
                    class="stock out-of-stock text-start"
                  >
                    無庫存
                  </div>
                </CCol>
              </CRow>
            </template>

            <!-- 有規格商品 -->
            <template v-else>
              <!-- 規格 -->
              <CRow class="align-items-center mt-4 mb-3">
                <CCol :md="3">
                  <div class="form-label mb-0">
                    規格
                  </div>
                </CCol>

                <CCol :md="9">
                  <CFormSelect
                    v-model="selectedSpecId"
                    class="spec-select"
                  >
                    <option :value="null">
                      請選擇規格
                    </option>

                    <option
                      v-for="spec in activeSpecs"
                      :key="spec.spec_id"
                      :value="spec.spec_id"
                      :disabled="Number(spec.stock) <= 0"
                    >
                      {{ spec.spec_name }}
                      -
                      ${{ Number(spec.price).toFixed(2) }}

                      <template v-if="Number(spec.stock) <= 0">
                        （無庫存）
                      </template>

                      <template v-else-if="Number(spec.stock) <= 5">
                        （現在庫存：{{ Number(spec.stock) }}）
                      </template>
                    </option>
                  </CFormSelect>
                </CCol>
              </CRow>

              <!-- 選擇規格後的價格 -->
              <CRow
                v-if="selectedSpec"
                class="align-items-center mb-3"
              >
                <CCol :md="3">
                  <div class="form-label mb-0">
                    價格
                  </div>
                </CCol>

                <CCol :md="9">
                  <div class="modal-price">
                    ${{ Number(selectedSpec.price).toFixed(2) }}
                  </div>
                </CCol>
              </CRow>

              <!-- 選擇規格後的庫存 -->
              <CRow
                v-if="selectedSpec"
                class="align-items-center mb-3"
              >
                <CCol :md="3">
                  <div class="form-label mb-0">
                    庫存
                  </div>
                </CCol>

                <CCol :md="9">
                  <div
                    v-if="Number(selectedSpec.stock) > 0"
                    class="stock text-start"
                  >
                    {{ Number(selectedSpec.stock) }} 件
                  </div>

                  <div
                    v-else
                    class="stock out-of-stock text-start"
                  >
                    無庫存
                  </div>
                </CCol>
              </CRow>
            </template>

            <!-- 數量 -->
            <CRow class="align-items-center mt-4 mb-4">
              <CCol :md="3">
                <div class="form-label mb-0">
                  數量
                </div>
              </CCol>

              <CCol :md="9">
                <CInputGroup class="quantity-input">
                  <CButton
                    color="light"
                    @click="decreaseQuantity"
                  >
                    −
                  </CButton>

                  <CFormInput
                    v-model.number="quantity"
                    type="number"
                    min="1"
                    class="text-center"
                  />

                  <CButton
                    color="light"
                    @click="increaseQuantity"
                  >
                    +
                  </CButton>
                </CInputGroup>
              </CCol>
            </CRow>
          </div>

          <!-- 已登入但商品資料取得失敗 -->
          <div
            v-else
            class="text-center py-4"
          >
            無法取得商品資料
          </div>
        </template>
      </CModalBody>

      <CModalFooter class="border-0">
        <CButton
          color="secondary"
          @click="closeModal"
        >
          {{ addedMessage ? '確認' : '關閉' }}
        </CButton>

        <CButton
          v-if="!addedMessage && product"
          color="primary"
          @click="addToCart"
        >
          加入購物車
        </CButton>
      </CModalFooter>
    </CModal>

    <!-- 未登入 -->
    <LoginRequireModal
      :visible="visible && !loading && !isLoggedIn"
      :store-id="props.storeId"
      @close="closeModal"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

import {
  CModal,
  CModalHeader,
  CModalBody,
  CModalFooter,
  CFormSelect,
  CFormInput,
  CInputGroup,
  CButton,
  CRow,
  CCol
} from '@coreui/vue'

import {
  getCustomerProduct,
  addCustomerCart,
  getCustomerLoginStatus
} from '../../api/customer.js'

import LoginRequireModal from '../customer/LoginRequireModal.vue'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  productId: {
    type: Number,
    default: null
  },
  storeId: {
    type: [Number, String],
    required: true
  }
})

const emit = defineEmits(['close'])

const product = ref(null)
const loading = ref(false)
const isLoggedIn = ref(false)
const selectedSpecId = ref(null)
const quantity = ref(1)
const addedMessage = ref(false)
const addedQuantity = ref(0)
const currentImageIndex = ref(0)

const descriptionExpanded = ref(false)
const descriptionRef = ref(null)
const descriptionOverflow = ref(false)

// 取得可使用的規格
// PHP 已經只回傳 status = active 的規格
const activeSpecs = computed(() => {
  if (!product.value?.has_spec) {
    return []
  }

  return (product.value.specs || []).filter(
    spec => spec.price !== null
  )
})

// 目前選擇的規格
const selectedSpec = computed(() => {
  if (!product.value?.has_spec) {
    return null
  }

  return activeSpecs.value.find(
    spec => Number(spec.spec_id) === Number(selectedSpecId.value)
  )
})

// 商品描述 DOM
function setDescriptionRef(el) {
  if (el) {
    descriptionRef.value = el
  }
}

// 檢查商品描述是否超過一行
function checkDescriptionOverflow() {
  if (!descriptionRef.value) {
    descriptionOverflow.value = false
    return
  }

  const lineHeight = parseFloat(
    getComputedStyle(descriptionRef.value).lineHeight
  )

  descriptionOverflow.value =
    descriptionRef.value.scrollHeight > lineHeight + 1
}

// 展開 / 收合商品描述
function toggleDescription() {
  descriptionExpanded.value =
    !descriptionExpanded.value
}

// 取得登入狀態
const checkLoginStatus = async () => {
  try {
    const data = await getCustomerLoginStatus()

    isLoggedIn.value = data.loggedIn === true
  } catch (error) {
    console.error(
      '取得登入狀態失敗:',
      error
    )

    isLoggedIn.value = false
  }
}

// 開啟 Modal 時
watch(
  () => props.visible,
  async (visible) => {
    if (!visible) {
      return
    }

    // 重置資料
    product.value = null
    isLoggedIn.value = false
    selectedSpecId.value = null
    quantity.value = 1
    addedMessage.value = false
    addedQuantity.value = 0
    currentImageIndex.value = 0

    descriptionExpanded.value = false
    descriptionOverflow.value = false
    descriptionRef.value = null

    loading.value = true

    try {
      // 先確認登入狀態
      await checkLoginStatus()

      // 未登入，不取得商品資料
      if (!isLoggedIn.value) {
        return
      }

      // 已登入才取得商品資料
      console.log('取得商品資料:', {
        productId: props.productId,
        storeId: props.storeId
      })

      const data = await getCustomerProduct(
        props.productId,
        props.storeId
      )

      console.log(
        '商品 API 回傳:',
        data
      )

      console.log(
        '商品詳細:',
        data.product
      )

      product.value = data.product

      // 等待 DOM 渲染完成後檢查描述高度
      setTimeout(() => {
        checkDescriptionOverflow()
      }, 0)
    } catch (error) {
      console.error(
        '取得商品資料失敗:',
        error
      )
    } finally {
      loading.value = false
    }
  }
)

// 關閉 Modal
const closeModal = () => {
  emit('close')
}

// 上一張圖片
const previousImage = () => {
  if (!product.value?.images?.length) {
    return
  }

  currentImageIndex.value =
    (currentImageIndex.value - 1 + product.value.images.length) %
    product.value.images.length
}

// 下一張圖片
const nextImage = () => {
  if (!product.value?.images?.length) {
    return
  }

  currentImageIndex.value =
    (currentImageIndex.value + 1) %
    product.value.images.length
}

// 減少數量
const decreaseQuantity = () => {
  if (quantity.value > 1) {
    quantity.value--
  }
}

// 增加數量
const increaseQuantity = () => {
  if (!product.value) {
    return
  }

  if (product.value.has_spec && !selectedSpec.value) {
    return
  }

  quantity.value++
}

// 加入購物車
const addToCart = async () => {
  if (!isLoggedIn.value || !product.value) {
    return
  }

  try {
    const data = await addCustomerCart(
      product.value.product_id,
      quantity.value,
      props.storeId,
      selectedSpecId.value
    )

    console.log(
      '加入購物車成功:',
      data
    )

    addedQuantity.value = quantity.value
    addedMessage.value = true
  } catch (error) {
    console.error(
      '加入購物車失敗:',
      error
    )
  }
}
</script>

<style scoped>
.product-modal :deep(.modal-dialog) {
  max-width: 500px;
  width: 90%;
}

.product-detail {
  text-align: center;
}

.product-image-wrapper {
  position: relative;
  width: calc(50% - 24px);
  aspect-ratio: 1 / 1;
  margin: 12px auto 1.5rem;
  overflow: visible;
}

.modal-product-image {
  width: 100%;
  height: 100%;
  aspect-ratio: 1 / 1;
  object-fit: cover;
  display: block;
  border-radius: 4px;
}

.image-prev,
.image-next {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 30px;
  height: 40px;
  padding: 0;
  border: none;
  background: transparent !important;
  color: #6c757d;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  line-height: 1;
  z-index: 2;
}

.image-prev {
  left: -80px;
}

.image-next {
  right: -80px;
}

.image-prev:hover,
.image-next:hover {
  color: #495057;
  background: transparent !important;
}

.image-placeholder {
  width: calc(50% - 24px);
  aspect-ratio: 1 / 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--cui-tertiary-bg);
  color: var(--cui-secondary-color);
  margin: 12px auto 1.5rem;
  border-radius: 4px;
}

.product-name {
  margin-bottom: 0.75rem;
}

.product-description {
  text-align: left;
  line-height: 1.6;
  color: var(--cui-secondary-color);
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 0.25rem;
  overflow-wrap: break-word;
  word-break: break-word;
}

.product-description.expanded {
  display: block;
  -webkit-line-clamp: unset;
  overflow: visible;
}

.description-button {
  padding: 0;
  margin-bottom: 1.25rem;
}

.modal-price {
  text-align: left;
  font-size: 1.1rem;
  font-weight: 600;
}

.stock {
  color: var(--cui-secondary-color);
  text-align: left;
}

.out-of-stock {
  color: var(--cui-danger);
  font-weight: 600;
}

.spec-select {
  text-align: left;
}

.quantity-input {
  width: 140px;
  margin: 0;
}

.added-message {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 120px;
  font-size: 1.2rem;
  font-weight: 600;
}

:deep(input[type='number']::-webkit-inner-spin-button),
:deep(input[type='number']::-webkit-outer-spin-button) {
  -webkit-appearance: none;
  margin: 0;
}

:deep(input[type='number']) {
  -moz-appearance: textfield;
  appearance: textfield;
}

@media (max-width: 575px) {
  .product-modal :deep(.modal-dialog) {
    width: 95%;
  }

  .product-image-wrapper {
    width: calc(50% - 24px);
    aspect-ratio: 1 / 1;
  }

  .image-placeholder {
    width: calc(50% - 24px);
    aspect-ratio: 1 / 1;
  }
}
</style>
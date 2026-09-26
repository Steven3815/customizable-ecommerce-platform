<template>
  <div>
    <div>
      <Sidebar />

      <div class="wrapper d-flex flex-column min-vh-100">

        <Header
          :store="home?.store || {}"
        />

        <div class="body flex-grow-1">

          <CContainer class="px-4" lg>

            <!-- 標題 + 排序 -->
            <div class="mb-4">

              <h2 class="mt-2 mb-4">
                購物車
              </h2>

              <!-- 排序 -->
              <div>

                <div class="mb-2">
                  <strong>排序</strong>
                </div>

                <CFormSelect
                  v-model="sortValue"
                  :options="sortOptions"
                  style="max-width: 220px;"
                />

              </div>

            </div>

            <!-- Loading -->
            <div
              v-if="loading"
              class="text-center py-5"
            >
              載入中...
            </div>

            <!-- Error -->
            <CCard
              v-else-if="error"
              class="border-0"
            >
              <CCardBody class="text-center py-5">

                <p class="text-danger mb-3">
                  {{ error }}
                </p>

                <CButton
                  color="primary"
                  @click="loadCart"
                >
                  重新載入
                </CButton>

              </CCardBody>
            </CCard>

            <!-- Empty -->
            <CCard
              v-else-if="!cart?.items?.length"
              class="border-0"
            >
              <CCardBody class="text-center py-5">

                <h4 class="mb-3">
                  購物車目前沒有商品
                </h4>

                <CButton
                  color="primary"
                  @click="continueShopping"
                >
                  繼續購物
                </CButton>

              </CCardBody>
            </CCard>

            <!-- Cart -->
            <CRow v-else>

              <!-- Cart Items -->
              <CCol :lg="8">

                <!-- 全選 -->
                <div class="mb-3">

                  <CFormCheck
                    v-model="allSelected"
                    label="全選"
                    class="cart-select-all"
                  />

                </div>

                <!-- Cart Item -->
                <CCard
                  v-for="item in cart.items"
                  :key="item.cart_item_id"
                  class="mb-3"
                >

                  <CCardBody>

                    <CRow class="align-items-center">

                      <!-- Checkbox -->
                      <CCol
                        :xs="1"
                        :sm="1"
                        :md="1"
                      >

                        <CFormCheck
                          v-model="selectedItems"
                          :value="item.cart_item_id"
                          class="cart-checkbox"
                        />

                      </CCol>

                      <!-- Image -->
                      <CCol
                        :xs="3"
                        :sm="3"
                        :md="2"
                      >

                        <img
                          :src="item.image_url"
                          :alt="item.product_name"
                          class="cart-image"
                        >

                      </CCol>

                      <!-- Product -->
                      <CCol
                        :xs="8"
                        :sm="5"
                        :md="4"
                      >

                        <h5 class="mb-2">
                          {{ item.product_name }}
                        </h5>

                        <!-- 商品描述 -->
                        <p
                          v-if="item.description"
                          :ref="el => setDescriptionRef(item.cart_item_id, el)"
                          :class="[
                            'text-body-secondary',
                            'cart-description',
                            expandedDescriptions[item.cart_item_id]
                              ? 'expanded'
                              : ''
                          ]"
                        >
                          {{ item.description }}
                        </p>

                        <!-- 展開 / 收合 -->
                        <CButton
                          v-if="
                            item.description &&
                            descriptionOverflow[item.cart_item_id]
                          "
                          color="secondary"
                          variant="ghost"
                          size="sm"
                          class="description-button"
                          @click="toggleDescription(item.cart_item_id)"
                        >

                          <CIcon
                            :icon="
                              expandedDescriptions[item.cart_item_id]
                                ? 'cilChevronTop'
                                : 'cilChevronBottom'
                            "
                            class="me-1"
                          />

                          {{
                            expandedDescriptions[item.cart_item_id]
                              ? '收合'
                              : '展開'
                          }}

                        </CButton>

                        <!-- 規格 -->
                        <p
                          v-if="item.spec_name"
                          class="text-body-secondary mb-2"
                        >
                          {{ item.spec_name }}
                        </p>

                        <!-- 單價 -->
                        <p class="mb-0">
                          NT$ {{ Number(item.price).toLocaleString() }}
                        </p>

                      </CCol>

                      <!-- Quantity -->
                      <CCol
                        :sm="4"
                        :md="3"
                        class="mt-3 mt-sm-0"
                      >

                        <CInputGroup class="quantity-input">

                          <CButton
                            color="light"
                            :disabled="Number(item.quantity) <= 1"
                            @click="decreaseQuantity(item)"
                          >
                            −
                          </CButton>

                          <CFormInput
                            v-model.number="item.quantity"
                            type="number"
                            min="1"
                            class="text-center quantity-value"
                            @change="updateQuantity(item)"
                          />

                          <CButton
                            color="light"
                            @click="increaseQuantity(item)"
                          >
                            +
                          </CButton>

                        </CInputGroup>

                      </CCol>

                      <!-- Subtotal -->
                      <CCol
                        :md="2"
                        class="text-md-end mt-3 mt-md-0"
                      >

                        <div class="fw-bold mb-2">
                          NT$ {{ Number(item.subtotal).toLocaleString() }}
                        </div>

                        <CButton
                          color="danger"
                          size="sm"
                          @click="removeItem(item)"
                        >
                          移除
                        </CButton>

                      </CCol>

                    </CRow>

                  </CCardBody>

                </CCard>

              </CCol>

              <!-- Summary -->
              <CCol :lg="4">

                <CCard>

                  <CCardBody>

                    <h4 class="mb-4">
                      訂單摘要
                    </h4>

                    <div class="d-flex justify-content-between mb-3">

                      <span>
                        商品數量
                      </span>

                      <span>
                        {{ selectedItems.length }} 件
                      </span>

                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-4">

                      <span class="fw-bold">
                        總金額
                      </span>

                      <span class="fw-bold fs-5">
                        NT$ {{ selectedTotalAmount.toLocaleString() }}
                      </span>

                    </div>

                    <CButton
                      color="primary"
                      class="w-100 mb-2"
                      :disabled="selectedItems.length === 0"
                      @click="goCheckout"
                    >
                      前往結帳
                    </CButton>

                    <CButton
                      color="secondary"
                      variant="outline"
                      class="w-100"
                      @click="continueShopping"
                    >
                      繼續購物
                    </CButton>

                  </CCardBody>

                </CCard>

              </CCol>

            </CRow>

          </CContainer>

        </div>

        <Footer
          :footer="home?.footer || {}"
        />

        <Createdby />

      </div>
    </div>

    <!-- 未登入 -->
    <LoginRequireModal
      :visible="showLoginModal"
      :store-id="storeId"
      @close="showLoginModal = false"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import {
  CContainer,
  CRow,
  CCol,
  CCard,
  CCardBody,
  CButton,
  CFormSelect,
  CFormCheck,
  CFormInput,
  CInputGroup
} from '@coreui/vue'

import {
  getCustomerHome,
  getCustomerCart,
  updateCustomerCart,
  deleteCustomerCart,
  getCustomerLoginStatus
} from '../../api/customer.js'

import Header from '../../components/customer_homepage/Header.vue'
import Sidebar from '../../components/customer_homepage/Sidebar.vue'
import Footer from '../../components/customer_homepage/Footer.vue'
import Createdby from '../../components/customer_homepage/Createdby.vue'
import LoginRequireModal from '../../components/customer/LoginRequireModal.vue'

const route = useRoute()
const router = useRouter()

const home = ref(null)

const cart = ref(null)
const loading = ref(true)
const error = ref('')

const storeId = route.params.storeId

const showLoginModal = ref(false)

const selectedItems = ref(
  JSON.parse(
    localStorage.getItem(`cart-selected-${storeId}`) || '[]'
  )
)

const expandedDescriptions = ref({})
const descriptionRefs = ref({})
const descriptionOverflow = ref({})

const sortBy = ref('created_at')
const sortOrder = ref('desc')

const sortOptions = [
  {
    label: '最新加入',
    value: 'created_at-desc'
  },
  {
    label: '價格：低到高',
    value: 'price-asc'
  },
  {
    label: '價格：高到低',
    value: 'price-desc'
  }
]

const sortValue = computed({
  get() {
    return `${sortBy.value}-${sortOrder.value}`
  },

  set(value) {
    const [by, order] = value.split('-')

    sortBy.value = by
    sortOrder.value = order

    loadCart()
  }
})

// 全選
const allSelected = computed({
  get() {
    if (!cart.value?.items?.length) {
      return false
    }

    return cart.value.items.every(
      item =>
        selectedItems.value.includes(
          item.cart_item_id
        )
    )
  },

  set(value) {
    if (!cart.value?.items?.length) {
      return
    }

    if (value) {
      selectedItems.value =
        cart.value.items.map(
          item => item.cart_item_id
        )
    } else {
      selectedItems.value = []
    }
  }
})

// 計算選取商品總金額
const selectedTotalAmount = computed(() => {
  if (!cart.value?.items) {
    return 0
  }

  return cart.value.items
    .filter(item =>
      selectedItems.value.includes(item.cart_item_id)
    )
    .reduce(
      (total, item) =>
        total + Number(item.price) * Number(item.quantity),
      0
    )
})

// 儲存選取商品
watch(
  selectedItems,
  value => {
    localStorage.setItem(
      `cart-selected-${storeId}`,
      JSON.stringify(value)
    )
  },
  { deep: true }
)

// 設定商品描述 DOM
function setDescriptionRef(cartItemId, el) {
  if (el) {
    descriptionRefs.value[cartItemId] = el
  }
}

// 檢查商品描述是否超過一行
function checkDescriptionOverflow() {
  Object.entries(descriptionRefs.value).forEach(
    ([cartItemId, el]) => {
      const lineHeight = parseFloat(
        getComputedStyle(el).lineHeight
      )

      descriptionOverflow.value[cartItemId] =
        el.scrollHeight > lineHeight + 1
    }
  )
}

// 展開 / 收合商品描述
function toggleDescription(cartItemId) {
  expandedDescriptions.value[cartItemId] =
    !expandedDescriptions.value[cartItemId]
}

// 取得首頁資料
async function loadHome() {
  try {
    home.value = await getCustomerHome(storeId)
  } catch (err) {
    console.error(
      '取得首頁資料失敗:',
      err
    )

    router.push('/404')
  }
}

// 檢查登入狀態
async function checkLoginStatus() {
  try {
    const data = await getCustomerLoginStatus()

    if (data.loggedIn !== true) {
      showLoginModal.value = true
      loading.value = false
      return false
    }

    return true
  } catch (err) {
    console.error(
      '取得登入狀態失敗:',
      err
    )

    showLoginModal.value = true
    loading.value = false
    return false
  }
}

// 取得購物車
async function loadCart() {
  loading.value = true
  error.value = ''

  const loggedIn = await checkLoginStatus()

  if (!loggedIn) {
    return
  }

  try {
    cart.value = await getCustomerCart(
      storeId,
      sortBy.value,
      sortOrder.value
    )

    const cartItemIds = cart.value.items.map(
      item => item.cart_item_id
    )

    // 移除購物車中已不存在的商品
    selectedItems.value =
      selectedItems.value.filter(
        cartItemId =>
          cartItemIds.includes(cartItemId)
      )

    descriptionRefs.value = {}
    descriptionOverflow.value = {}

    setTimeout(() => {
      checkDescriptionOverflow()
    }, 0)
  } catch (err) {
    console.error(
      '取得購物車資料失敗:',
      err
    )

    error.value =
      err.message ||
      '取得購物車資料失敗'
  } finally {
    loading.value = false
  }
}

// 增加數量
async function increaseQuantity(item) {
  const newQuantity =
    Number(item.quantity) + 1

  try {
    await updateCustomerCart(
      item.cart_item_id,
      newQuantity,
      storeId
    )

    item.quantity = newQuantity

    item.subtotal =
      Number(item.price) * newQuantity
  } catch (err) {
    error.value =
      err.message ||
      '更新購物車數量失敗'
  }
}

// 減少數量
async function decreaseQuantity(item) {
  if (Number(item.quantity) <= 1) {
    return
  }

  const newQuantity =
    Number(item.quantity) - 1

  try {
    await updateCustomerCart(
      item.cart_item_id,
      newQuantity,
      storeId
    )

    item.quantity = newQuantity

    item.subtotal =
      Number(item.price) * newQuantity
  } catch (err) {
    error.value =
      err.message ||
      '更新購物車數量失敗'
  }
}

// 手動修改數量
async function updateQuantity(item) {
  let newQuantity =
    Number(item.quantity)

  if (
    !Number.isInteger(newQuantity) ||
    newQuantity < 1
  ) {
    newQuantity = 1
  }

  try {
    await updateCustomerCart(
      item.cart_item_id,
      newQuantity,
      storeId
    )

    item.quantity = newQuantity

    item.subtotal =
      Number(item.price) * newQuantity
  } catch (err) {
    error.value =
      err.message ||
      '更新購物車數量失敗'
  }
}

// 移除商品
async function removeItem(item) {
  try {
    await deleteCustomerCart(
      item.cart_item_id,
      storeId
    )

    selectedItems.value =
      selectedItems.value.filter(
        cartItemId =>
          cartItemId !== item.cart_item_id
      )

    await loadCart()
  } catch (err) {
    error.value =
      err.message ||
      '刪除購物車商品失敗'
  }
}

// 繼續購物
function continueShopping() {
  router.push(`/store-${storeId}`)
}

// 前往結帳
function goCheckout() {
  router.push(`/store-${storeId}/checkout`)
}

onMounted(() => {
  loadHome()
  loadCart()
})
</script>

<style scoped>
.cart-image {
  width: 100%;
  aspect-ratio: 1 / 1;
  object-fit: cover;
  display: block;
  border-radius: 8px;
}

.cart-checkbox {
  margin: 0;
}

.cart-select-all {
  margin-bottom: 0;
}

.cart-description {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 0.25rem;
}

.cart-description.expanded {
  display: block;
  -webkit-line-clamp: unset;
}

.description-button {
  padding: 0;
  margin-bottom: 0.5rem;
}

.quantity-input {
  width: 140px;
  margin: 0;
}

.quantity-input :deep(.form-control) {
  height: 38px;
  padding: 0;
  text-align: center;
  line-height: 38px;
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

:deep(.form-control),
:deep(.form-select) {
  font-size: 14px;
}

@media (max-width: 767px) {
  .quantity-input {
    width: 140px;
  }
}
</style>
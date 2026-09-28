<template>
  <div>
    <div>
      <Sidebar />

      <div class="wrapper flex-column d-flex min-vh-100">
        <Header :store="home?.store || {}" />

        <div class="body flex-grow-1">
          <CContainer class="px-4" lg>
            <!-- 標題 -->
            <div class="mb-4">
              <h2 class="mt-2 mb-4">
                訂單總覽
              </h2>

              <!-- 篩選 + 排序 -->
              <div class="row g-3">
                <!-- 訂單狀態 -->
                <div class="col-md-3">
                  <div class="mb-2">
                    <strong>訂單狀態</strong>
                  </div>

                  <CFormSelect
                    v-model="orderStatus"
                    :options="orderStatusOptions"
                    @change="changeFilter"
                  />
                </div>

                <!-- 付款狀態 -->
                <div class="col-md-3">
                  <div class="mb-2">
                    <strong>付款狀態</strong>
                  </div>

                  <CFormSelect
                    v-model="paymentStatus"
                    :options="paymentStatusOptions"
                    @change="changeFilter"
                  />
                </div>

                <!-- 付款確認狀態 -->
                <div class="col-md-3">
                  <div class="mb-2">
                    <strong>付款確認</strong>
                  </div>

                  <CFormSelect
                    v-model="paymentConfirmStatus"
                    :options="paymentConfirmStatusOptions"
                    @change="changeFilter"
                  />
                </div>

                <!-- 配送狀態 -->
                <div class="col-md-3">
                  <div class="mb-2">
                    <strong>配送狀態</strong>
                  </div>

                  <CFormSelect
                    v-model="deliveryStatus"
                    :options="deliveryStatusOptions"
                    @change="changeFilter"
                  />
                </div>

                <!-- 退款狀態 -->
                <div v-if="refundEnable" class="col-md-3">
                  <div class="mb-2">
                    <strong>退款狀態</strong>
                  </div>

                  <CFormSelect
                    v-model="refundStatus"
                    :options="refundStatusOptions"
                    @change="changeFilter"
                  />
                </div>

                <!-- 排序 -->
                <div class="col-md-3">
                  <div class="mb-2">
                    <strong>排序</strong>
                  </div>

                  <CFormSelect
                    v-model="sortValue"
                    :options="sortOptions"
                  />
                </div>
              </div>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="text-center py-5">
              載入中...
            </div>

            <!-- Error -->
            <CCard v-else-if="error" class="border-0">
              <CCardBody class="text-center py-5">
                <p class="text-danger mb-3">
                  {{ error }}
                </p>

                <CButton
                  color="primary"
                  @click="loadOrders"
                >
                  重新載入
                </CButton>
              </CCardBody>
            </CCard>

            <!-- Empty -->
            <CCard v-else-if="!orders.length" class="border-0">
              <CCardBody class="text-center py-5">
                <h4 class="mb-3">
                  目前沒有符合條件的訂單
                </h4>

                <CButton
                  color="primary"
                  @click="continueShopping"
                >
                  繼續購物
                </CButton>
              </CCardBody>
            </CCard>

            <!-- Orders -->
            <div v-else>
              <CCard
                v-for="order in orders"
                :key="order.order_id"
                class="mb-4"
              >
                <CCardBody>
                  <!-- 訂單資訊 -->
                  <div class="d-flex justify-content-between align-items-start mb-3">
                    <h5 class="mb-0">
                      <strong>
                        訂單編號： {{ order.order_number }}
                      </strong>
                    </h5>

                    <!-- 查看訂單 -->
                    <CButton
                      color="primary"
                      variant="outline"
                      @click="goOrderDetail(order.order_id)"
                    >
                      查看訂單
                    </CButton>
                  </div>

                  <div class="mb-2">
                    <strong>訂單日期：</strong>
                    {{ order.order_date }}
                  </div>

                  <div class="mb-3">
                    <strong>訂單狀態：</strong>

                    <span
                      :class="{
                        'text-danger fw-bold': order.order_status === 'pending'
                      }"
                    >
                      {{ getOrderStatusText(order.order_status) }}
                    </span>
                  </div>

                  <hr>

                  <!-- 商品資訊 -->
                  <div class="d-flex align-items-center mb-3">
                    <h5 class="mb-0">
                      商品資訊
                    </h5>

                    <CButton
                      color="link"
                      class="text-decoration-none text-secondary p-0 ms-2"
                      @click="showProducts[order.order_id] = !showProducts[order.order_id]"
                    >
                      <CIcon
                        :icon="showProducts[order.order_id] ? 'cilChevronTop' : 'cilChevronBottom'"
                        class="me-1"
                      />

                      {{ showProducts[order.order_id] ? '收合' : '展開詳細' }}
                    </CButton>
                  </div>

                  <CCollapse :visible="showProducts[order.order_id]">
                    <div
                      v-for="item in order.items"
                      :key="item.order_item_id"
                      class="mb-3"
                    >
                      <div class="row align-items-center">
                        <!-- 商品 -->
                        <div class="col-md-2">
                          <div class="fw-bold mb-2">
                            {{ item.product_name }}
                          </div>

                          <div
                            v-if="item.spec_name"
                            class="text-body-secondary"
                          >
                            規格：{{ item.spec_name }}
                          </div>
                        </div>

                        <!-- 單價 -->
                        <div class="col-md-3 text-end text-body-secondary">
                          $ {{ Number(item.price).toLocaleString() }} × {{ item.quantity }}
                        </div>

                        <!-- 小計 -->
                        <div class="col-md-3 text-end fw-bold">
                          $ {{ Number(item.subtotal).toLocaleString() }}
                        </div>
                      </div>
                    </div>
                  </CCollapse>

                  <hr>

                  <!-- 訂單摘要 -->
                  <h5 class="mb-3">
                    訂單摘要
                  </h5>

                  <!-- 已完成訂單才顯示付款、配送、退款狀態 -->
                  <template v-if="order.order_status === 'confirmed'">
                    <div class="mb-2">
                      <strong>付款狀態：</strong>
                      {{ getPaymentText(order.payment?.payment_status) }}
                    </div>

                    <div class="mb-2">
                      <strong>付款確認：</strong>
                      {{ getPaymentConfirmText(order.payment?.payment_confirm_status) }}
                    </div>

                    <div class="mb-2">
                      <strong>配送狀態：</strong>
                      {{ getDeliveryText(order.delivery_status) }}
                    </div>

                    <div v-if="refundEnable" class="mb-2">
                      <strong>退款狀態：</strong>
                      {{ getRefundText(order.refund?.refund_status) }}
                    </div>
                  </template>

                  <!-- 商品金額 -->
                  <div class="row mb-2">
                    <div class="col-md-5">
                      <strong>商品金額：</strong>
                    </div>

                    <div class="col-md-3 text-end">
                      $ {{ getProductAmount(order.items).toLocaleString() }}
                    </div>
                  </div>

                  <!-- 運費 -->
                  <div class="row mb-3">
                    <div class="col-md-5">
                      <strong>運費：</strong>
                    </div>

                    <div class="col-md-3 text-end">
                      $ {{ Number(order.shipping_fee).toLocaleString() }}
                    </div>
                  </div>

                  <hr>

                  <!-- 總金額 -->
                  <div class="row mb-0 fw-bold">
                    <div class="col-md-5">
                      <strong>總金額：</strong>
                    </div>

                    <div class="col-md-3 text-end">
                      <strong>
                        $ {{ Number(order.total_amount).toLocaleString() }}
                      </strong>
                    </div>
                  </div>

                  <!-- Pending 訂單操作 -->
                  <div
                    v-if="order.order_status === 'pending'"
                    class="d-flex justify-content-end gap-2 mt-3"
                  >
                    <CButton
                      color="danger"
                      @click="openCancelModal(order)"
                    >
                      取消訂單
                    </CButton>

                    <CButton
                      color="primary"
                      class="me-2"
                      @click="goOrderEdit(order.order_id)"
                    >
                      前往完成訂單
                    </CButton>
                  </div>
                </CCardBody>
              </CCard>
            </div>
          </CContainer>
        </div>

        <Footer :footer="home?.footer || {}" />

        <Createdby />
      </div>
    </div>

    <!-- 未登入 -->
    <LoginRequireModal
      :visible="showLoginModal"
      :store-id="storeId"
      @close="showLoginModal = false"
    />

    <!-- 取消訂單確認 -->
    <CModal
      :visible="showCancelModal"
      @close="closeCancelModal"
    >
      <CModalHeader class="border-0">
        <CModalTitle>
          取消訂單
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        確定要取消此訂單嗎？
      </CModalBody>

      <CModalFooter class="border-0">
        <CButton
          color="secondary"
          :disabled="cancelling"
          @click="closeCancelModal"
        >
          返回
        </CButton>

        <CButton
          color="danger"
          :disabled="cancelling"
          @click="cancelOrder"
        >
          {{ cancelling ? '取消中...' : '取消訂單' }}
        </CButton>
      </CModalFooter>
    </CModal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import {
  CContainer,
  CCard,
  CCardBody,
  CButton,
  CFormSelect,
  CCollapse,
  CModal,
  CModalHeader,
  CModalTitle,
  CModalBody,
  CModalFooter
} from '@coreui/vue'

import {
  getCustomerHome,
  getCustomerLoginStatus,
  getCustomerOrders,
  deleteCustomerOrder
} from '../../api/customer.js'

import Header from '../../components/customer_homepage/Header.vue'
import Sidebar from '../../components/customer_homepage/Sidebar.vue'
import Footer from '../../components/customer_homepage/Footer.vue'
import Createdby from '../../components/customer_homepage/Createdby.vue'
import LoginRequireModal from '../../components/customer/LoginRequireModal.vue'

const route = useRoute()
const router = useRouter()

const home = ref(null)
const orders = ref([])
const loading = ref(true)
const cancelling = ref(false)
const error = ref('')
const storeId = route.params.storeId
const showLoginModal = ref(false)
const showCancelModal = ref(false)
const selectedOrder = ref(null)
const showProducts = ref({})
const sortBy = ref('order_date')
const sortOrder = ref('desc')
const orderStatus = ref('all')
const paymentStatus = ref('all')
const paymentConfirmStatus = ref('all')
const deliveryStatus = ref('all')
const refundStatus = ref('all')
const refundEnable = ref(false)

const sortOptions = [
  {
    label: '最新訂單',
    value: 'order_date-desc'
  },
  {
    label: '最舊訂單',
    value: 'order_date-asc'
  }
]

const orderStatusOptions = [
  {
    label: '全部',
    value: 'all'
  },
  {
    label: '未完成',
    value: 'pending'
  },
  {
    label: '已完成',
    value: 'confirmed'
  }
]

const paymentStatusOptions = [
  {
    label: '全部',
    value: 'all'
  },
  {
    label: '待付款',
    value: 'pending'
  },
  {
    label: '處理中',
    value: 'processing'
  },
  {
    label: '已付款',
    value: 'paid'
  },
  {
    label: '付款失敗',
    value: 'failed'
  }
]

const paymentConfirmStatusOptions = [
  {
    label: '全部',
    value: 'all'
  },
  {
    label: '等待確認',
    value: 'waiting'
  },
  {
    label: '已確認',
    value: 'confirmed'
  },
  {
    label: '已拒絕',
    value: 'rejected'
  }
]

const deliveryStatusOptions = [
  {
    label: '全部',
    value: 'all'
  },
  {
    label: '待出貨',
    value: 'pending'
  },
  {
    label: '配送中',
    value: 'shipping'
  },
  {
    label: '已完成',
    value: 'completed'
  }
]

const refundStatusOptions = [
  {
    label: '全部',
    value: 'all'
  },
  {
    label: '處理中',
    value: 'pending'
  },
  {
    label: '已核准',
    value: 'approved'
  },
  {
    label: '已拒絕',
    value: 'rejected'
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
    sortOrders()
  }
})

// 取得首頁資料
async function loadHome() {
  try {
    home.value = await getCustomerHome(storeId)
  } catch (err) {
    console.error('取得首頁資料失敗:', err)
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
    console.error('取得登入狀態失敗:', err)
    showLoginModal.value = true
    loading.value = false
    return false
  }
}

// 取得訂單
async function loadOrders() {
  loading.value = true
  error.value = ''

  const loggedIn = await checkLoginStatus()

  if (!loggedIn) {
    return
  }

  try {
    const data = await getCustomerOrders(
      storeId,
      orderStatus.value,
      paymentStatus.value,
      paymentConfirmStatus.value,
      deliveryStatus.value,
      refundStatus.value
    )

    refundEnable.value = data.refund_enable === true
    orders.value = data.orders || []

    // 商品資訊預設收合
    showProducts.value = {}

    orders.value.forEach(order => {
      showProducts.value[order.order_id] = false
    })

    sortOrders()
  } catch (err) {
    console.error('取得訂單資料失敗:', err)
    error.value = err.message || '取得訂單資料失敗'
  } finally {
    loading.value = false
  }
}

// 排序
function sortOrders() {
  if (sortOrder.value === 'asc') {
    orders.value.sort(
      (a, b) =>
        new Date(a.order_date) -
        new Date(b.order_date)
    )
  } else {
    orders.value.sort(
      (a, b) =>
        new Date(b.order_date) -
        new Date(a.order_date)
    )
  }
}

// 篩選變更
function changeFilter() {
  loadOrders()
}

// 取得商品金額
function getProductAmount(items) {
  return items.reduce(
    (total, item) => total + Number(item.subtotal || 0),
    0
  )
}

// 訂單狀態文字
function getOrderStatusText(status) {
  const statusMap = {
    pending: '未完成',
    confirmed: '已完成',
    cancelled: '已取消'
  }

  return statusMap[status] || '未知'
}

// 付款狀態文字
function getPaymentText(status) {
  const statusMap = {
    pending: '待付款',
    processing: '處理中',
    paid: '已付款',
    failed: '付款失敗'
  }

  return statusMap[status] || '未知'
}

// 付款確認文字
function getPaymentConfirmText(status) {
  const statusMap = {
    waiting: '等待確認',
    confirmed: '已確認',
    rejected: '已拒絕'
  }

  return statusMap[status] || '未知'
}

// 配送狀態文字
function getDeliveryText(status) {
  const statusMap = {
    pending: '待出貨',
    shipping: '配送中',
    completed: '已完成'
  }

  return statusMap[status] || '未知'
}

// 退款狀態文字
function getRefundText(status) {
  const statusMap = {
    pending: '處理中',
    approved: '已核准',
    rejected: '已拒絕'
  }

  return statusMap[status] || '未知'
}

// 前往訂單詳細頁
function goOrderDetail(orderId) {
  router.push(`/store-${storeId}/order/${orderId}`)
}

// 前往完成訂單
function goOrderEdit(orderId) {
  router.push(`/store-${storeId}/create_order?order_id=${orderId}`)
}

// 開啟取消訂單 Modal
function openCancelModal(order) {
  selectedOrder.value = order
  showCancelModal.value = true
}

// 關閉取消訂單 Modal
function closeCancelModal() {
  if (cancelling.value) {
    return
  }

  showCancelModal.value = false
  selectedOrder.value = null
}

// 取消訂單
async function cancelOrder() {
  if (cancelling.value || !selectedOrder.value) {
    return
  }

  cancelling.value = true
  error.value = ''

  try {
    await deleteCustomerOrder(
      storeId,
      selectedOrder.value.order_number
    )

    showCancelModal.value = false
    selectedOrder.value = null

    await loadOrders()
  } catch (err) {
    console.error('取消訂單失敗:', err)
    error.value = err.message || '取消訂單失敗'
  } finally {
    cancelling.value = false
  }
}

// 繼續購物
function continueShopping() {
  router.push(`/store-${storeId}`)
}

onMounted(() => {
  loadHome()
  loadOrders()
})
</script>
<script setup>
import { ref, onMounted } from 'vue'

import {
  CContainer,
  CCard,
  CCardBody,
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

import { useRoute, useRouter } from 'vue-router'

import {
  getOrder,
  updateOrderDelivery,
  confirmOrderPayment
} from '../../../api/store'

const route = useRoute()
const router = useRouter()

const order = ref(null)
const refundEnable = ref(false)

const loading = ref(true)
const updatingDelivery = ref(false)
const confirmingPayment = ref(false)

const paymentNote = ref('')

const showPaymentConfirmModal = ref(false)
const paymentConfirmAction = ref('')

const showDeliveryConfirmModal = ref(false)
const deliveryConfirmStatus = ref('')

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

// 取得訂單
async function loadOrder() {
  loading.value = true
  error.value = ''

  try {
    const orderId = route.params.orderId

    const data = await getOrder(orderId)

    order.value = data.order
    refundEnable.value = data.refund_enable
    paymentNote.value = data.order.payment?.payment_note || ''
  } catch (err) {
    console.error('取得訂單詳細資料失敗:', err)

    if (err.status === 401) {
      error.value = '登入狀態已失效，請重新登入'
    } else if (err.status === 403) {
      error.value = '目前無權限查看此訂單'
    } else if (err.status === 404) {
      error.value = '找不到此訂單'
    } else {
      error.value = err.message || '取得訂單詳細資料失敗'
    }

    showError(error.value)
  } finally {
    loading.value = false
  }
}

function goBack() {
  router.push({
    name: 'OrderList'
  })
}

function openDeliveryConfirmModal(status) {
  deliveryConfirmStatus.value = status
  showDeliveryConfirmModal.value = true
}

function closeDeliveryConfirmModal() {
  showDeliveryConfirmModal.value = false
  deliveryConfirmStatus.value = ''
}

async function confirmDeliveryStatus() {
  const status = deliveryConfirmStatus.value

  if (!status) {
    return
  }

  closeDeliveryConfirmModal()
  await changeDeliveryStatus(status)
}

async function changeDeliveryStatus(status) {
  if (!order.value || updatingDelivery.value) {
    return
  }

  updatingDelivery.value = true

  try {
    const data = await updateOrderDelivery({
      order_id: order.value.order_id,
      delivery_status: status
    })

    order.value.delivery.delivery_status = data.delivery_status
    order.value.updated_at = new Date().toISOString()
  } catch (err) {
    console.error('更新訂單配送狀態失敗:', err)

    if (err.status === 401) {
      showError('登入狀態已失效，請重新登入')
    } else if (err.status === 403) {
      showError('目前無權限更新此訂單')
    } else if (err.status === 404) {
      showError('找不到此訂單')
    } else if (err.status === 409) {
      showError('此訂單配送狀態無法進行此變更')
    } else {
      showError(err.message || '更新訂單配送狀態失敗')
    }
  } finally {
    updatingDelivery.value = false
  }
}

function openPaymentConfirmModal(action) {
  paymentConfirmAction.value = action
  showPaymentConfirmModal.value = true
}

function closePaymentConfirmModal() {
  showPaymentConfirmModal.value = false
  paymentConfirmAction.value = ''
}

async function confirmPayment(paymentConfirmStatus) {
  if (!order.value?.payment || confirmingPayment.value) {
    return
  }

  if (!paymentNote.value.trim()) {
    closePaymentConfirmModal()
    showError('請填寫付款備註')
    return
  }

  confirmingPayment.value = true

  try {
    const data = await confirmOrderPayment({
      order_id: order.value.order_id,
      payment_confirm_status: paymentConfirmStatus,
      payment_note: paymentNote.value.trim()
    })

    order.value.payment.payment_status = data.payment.payment_status
    order.value.payment.payment_confirm_status = data.payment.payment_confirm_status
    order.value.payment.payment_note = data.payment.payment_note
    order.value.payment.paid_at = data.payment.paid_at
    order.value.payment.confirmed_at = data.payment.confirmed_at
    order.value.updated_at = new Date().toISOString()

    closePaymentConfirmModal()
  } catch (err) {
    console.error('更新訂單付款狀態失敗:', err)

    if (err.status === 401) {
      showError('登入狀態已失效，請重新登入')
    } else if (err.status === 403) {
      showError('目前無權限更新此訂單付款')
    } else if (err.status === 404) {
      showError('找不到付款資料')
    } else if (err.status === 409) {
      showError('此付款目前無法進行此變更')
    } else {
      showError(err.message || '更新訂單付款狀態失敗')
    }
  } finally {
    confirmingPayment.value = false
  }
}

function getDeliveryStatusText(status) {
  const statusMap = {
    pending: '待出貨',
    shipping: '配送中',
    completed: '已完成'
  }

  return statusMap[status] || status
}

function getPaymentStatusText(status) {
  const statusMap = {
    pending: '待付款',
    processing: '待確認付款',
    paid: '已付款',
    failed: '付款失敗'
  }

  return statusMap[status] || status
}

function getPaymentConfirmStatusText(status) {
  const statusMap = {
    waiting: '待確認',
    confirmed: '已確認',
    rejected: '已拒絕'
  }

  return statusMap[status] || status
}

function getPaymentMethodText(method) {
  const methodMap = {
    credit_card: '信用卡',
    atm: 'ATM 轉帳',
    post_office: '郵局轉帳',
    linepay: 'LINE Pay'
  }

  return methodMap[method] || method
}

function getDeliveryMethodText(method) {
  const methodMap = {
    home_delivery: '宅配'
  }

  return methodMap[method] || method
}

function getRefundStatusText(status) {
  const statusMap = {
    pending: '待處理',
    approved: '已核准',
    rejected: '已拒絕'
  }

  return statusMap[status] || status
}

function formatAmount(amount) {
  return `$${Number(amount || 0).toLocaleString()}`
}

function formatDate(date) {
  if (!date) {
    return '-'
  }

  return date.replace('T', ' ').slice(0, 19)
}

function getItemSubtotal(item) {
  return Number(item.quantity || 0) * Number(item.price || 0)
}

function canConfirmPayment() {
  if (!order.value?.payment) {
    return false
  }

  return (
    (
      order.value.payment.payment_method === 'atm' ||
      order.value.payment.payment_method === 'post_office'
    ) &&
    order.value.payment.payment_status === 'processing' &&
    order.value.payment.payment_confirm_status === 'waiting'
  )
}

onMounted(() => {
  loadOrder()
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
          <div
            v-if="loading"
            class="text-center py-5"
          >
            載入中...
          </div>

          <!-- 訂單不存在 -->
          <div
            v-else-if="!order"
            class="text-center py-5"
          >
            <p class="text-body-secondary mb-3">
              找不到訂單資料
            </p>

            <CButton
              color="primary"
              @click="goBack"
            >
              返回訂單列表
            </CButton>
          </div>

          <div v-else>

            <!-- 標題 + 返回 -->
            <div class="position-relative mb-4">
              <h2 class="mt-2 mb-3">
                訂單詳細
              </h2>

              <CButton
                color="secondary"
                class="position-absolute top-0 end-0"
                @click="goBack"
              >
                返回訂單列表
              </CButton>
            </div>

            <!-- 訂單資訊 -->
            <CCard class="mb-4">
              <CCardBody>
                <h4 class="mb-4">
                  訂單資訊
                </h4>

                <div class="mb-3">
                  <strong>訂單編號</strong>

                  <div class="mt-1">
                    {{ order.order_number }}
                  </div>
                </div>

                <div class="mb-3">
                  <strong>下單時間</strong>

                  <div class="mt-1">
                    {{ formatDate(order.order_date) }}
                  </div>
                </div>

                <div class="mb-3">
                  <strong>最後更新時間</strong>

                  <div class="mt-1">
                    {{ formatDate(order.updated_at) }}
                  </div>
                </div>

                <hr>

                <h4 class="mb-3">
                  顧客資訊
                </h4>

                <div class="mb-3">
                  <strong>姓名</strong>

                  <div class="mt-1">
                    {{ order.customer?.name || '-' }}
                  </div>
                </div>

                <div class="mb-3">
                  <strong>Email</strong>

                  <div class="mt-1">
                    {{ order.customer?.email || '-' }}
                  </div>
                </div>

                <div class="mb-3">
                  <strong>電話</strong>

                  <div class="mt-1">
                    {{ order.customer?.phone || '-' }}
                  </div>
                </div>

                <hr>

                <h4 class="mb-3">
                  收件資訊
                </h4>

                <div class="mb-3">
                  <strong>收件人姓名</strong>

                  <div class="mt-1">
                    {{ order.receiver?.name || '-' }}
                  </div>
                </div>

                <div class="mb-3">
                  <strong>收件人電話</strong>

                  <div class="mt-1">
                    {{ order.receiver?.phone || '-' }}
                  </div>
                </div>

                <div class="mb-3">
                  <strong>收件地址</strong>

                  <div class="mt-1">
                    {{ order.receiver?.address || '-' }}
                  </div>
                </div>

                <hr>

                <h4 class="mb-3">
                  訂單金額
                </h4>

                <div class="mb-3">
                  <strong>商品金額</strong>

                  <div class="mt-1">
                    {{ formatAmount(order.amount?.product_amount) }}
                  </div>
                </div>

                <div class="mb-3">
                  <strong>運費</strong>

                  <div class="mt-1">
                    {{ formatAmount(order.amount?.shipping_fee) }}
                  </div>
                </div>

                <div>
                  <strong>訂單總金額</strong>

                  <div class="mt-1 fw-bold">
                    {{ formatAmount(order.amount?.total_amount) }}
                  </div>
                </div>
              </CCardBody>
            </CCard>

            <!-- 商品明細 -->
            <CCard class="mb-4">
              <CCardBody>
                <h4 class="mb-4">
                  商品明細
                </h4>

                <CTable
                  bordered
                  hover
                  responsive
                >
                  <CTableHead>
                    <CTableRow>
                      <CTableHeaderCell>
                        商品名稱
                      </CTableHeaderCell>

                      <CTableHeaderCell>
                        規格
                      </CTableHeaderCell>

                      <CTableHeaderCell>
                        單價
                      </CTableHeaderCell>

                      <CTableHeaderCell>
                        數量
                      </CTableHeaderCell>

                      <CTableHeaderCell>
                        小計
                      </CTableHeaderCell>
                    </CTableRow>
                  </CTableHead>

                  <CTableBody>
                    <CTableRow
                      v-for="item in order.items"
                      :key="item.order_item_id"
                    >
                      <CTableDataCell>
                        {{ item.product_name }}
                      </CTableDataCell>

                      <CTableDataCell>
                        {{ item.spec_name || '-' }}
                      </CTableDataCell>

                      <CTableDataCell>
                        {{ formatAmount(item.price) }}
                      </CTableDataCell>

                      <CTableDataCell>
                        {{ item.quantity }}
                      </CTableDataCell>

                      <CTableDataCell>
                        {{ formatAmount(getItemSubtotal(item)) }}
                      </CTableDataCell>
                    </CTableRow>

                    <CTableRow v-if="!order.items?.length">
                      <CTableDataCell
                        colspan="5"
                        class="text-center text-body-secondary"
                      >
                        沒有商品明細
                      </CTableDataCell>
                    </CTableRow>
                  </CTableBody>
                </CTable>
              </CCardBody>
            </CCard>

            <!-- 付款資訊 -->
            <CCard class="mb-4">
              <CCardBody>
                <h4 class="mb-4">
                  付款資訊
                </h4>

                <template v-if="order.payment">

                  <div class="mb-3">
                    <strong>付款方式</strong>

                    <div class="mt-1">
                      {{ getPaymentMethodText(order.payment.payment_method) }}
                    </div>
                  </div>

                  <div class="mb-3">
                    <strong>付款狀態</strong>

                    <div class="mt-1">
                      {{ getPaymentStatusText(order.payment.payment_status) }}
                    </div>
                  </div>

                  <div class="mb-3">
                    <strong>付款金額</strong>

                    <div class="mt-1">
                      {{ formatAmount(order.payment.amount) }}
                    </div>
                  </div>

                  <div class="mb-3">
                    <strong>付款確認狀態</strong>

                    <div
                      class="mt-1"
                      :class="{ 'text-danger fw-bold': order.payment.payment_confirm_status === 'waiting' }"
                    >
                      {{ getPaymentConfirmStatusText(order.payment.payment_confirm_status) }}
                    </div>
                  </div>

                  <!-- 待確認 -->
                  <template v-if="canConfirmPayment()">

                    <hr>

                    <h4 class="mb-3">
                      付款確認
                    </h4>

                    <div class="mb-3">
                      <strong>付款時間</strong>

                      <div class="mt-1">
                        {{ formatDate(order.payment.paid_at) }}
                      </div>
                    </div>

                    <div class="mb-3">
                      <strong>付款備註</strong>

                      <textarea
                        v-model="paymentNote"
                        class="form-control mt-1"
                        rows="4"
                        placeholder="請輸入付款確認或拒絕原因"
                      ></textarea>
                    </div>

                    <div class="mb-3">
                      <strong>付款證明</strong>

                      <div class="mt-2">
                        <img
                          v-if="order.payment.payment_proof_image"
                          :src="order.payment.payment_proof_image"
                          alt="付款證明"
                          class="payment-proof-image"
                        >

                        <div
                          v-else
                          class="text-body-secondary"
                        >
                          尚無付款證明
                        </div>
                      </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                      <CButton
                        color="success"
                        :disabled="confirmingPayment"
                        @click="openPaymentConfirmModal('confirmed')"
                      >
                        確認收到付款
                      </CButton>

                      <CButton
                        color="danger"
                        :disabled="confirmingPayment"
                        @click="openPaymentConfirmModal('rejected')"
                      >
                        拒絕付款
                      </CButton>
                    </div>

                  </template>

                  <!-- 已確認或已拒絕 -->
                  <div
                    v-if="
                      order.payment.payment_confirm_status === 'confirmed' ||
                      order.payment.payment_confirm_status === 'rejected'
                    "
                    class="mb-3"
                  >
                    <strong>付款備註</strong>

                    <div class="mt-1">
                      {{ order.payment.payment_note || '-' }}
                    </div>
                  </div>

                  <!-- 已確認 -->
                  <div
                    v-if="order.payment.payment_confirm_status === 'confirmed'"
                  >
                    <strong>確認時間</strong>

                    <div class="mt-1">
                      {{ formatDate(order.payment.confirmed_at) }}
                    </div>
                  </div>

                </template>

                <div
                  v-else
                  class="text-body-secondary"
                >
                  尚無付款資料
                </div>
              </CCardBody>
            </CCard>

            <!-- 配送資訊 -->
            <CCard class="mb-4">
              <CCardBody>
                <h4 class="mb-4">
                  配送資訊
                </h4>

                <div class="mb-3">
                  <strong>配送方式</strong>

                  <div class="mt-1">
                    {{ getDeliveryMethodText(order.delivery?.delivery_method) }}
                  </div>
                </div>

                <div class="mb-3">
                  <strong>配送狀態</strong>

                  <div
                    class="mt-1"
                    :class="{ 'text-danger fw-bold': order.delivery?.delivery_status === 'pending' }"
                  >
                    {{ getDeliveryStatusText(order.delivery?.delivery_status) }}
                  </div>
                </div>

                <div
                  v-if="order.delivery?.delivery_status === 'pending'"
                  class="mb-4"
                >
                  <strong>預計出貨日期</strong>

                  <div class="mt-1">
                    {{ order.delivery.estimated_ship_date || '-' }}
                  </div>
                </div>

                <div
                  v-else-if="order.delivery?.delivery_status === 'shipping'"
                  class="mb-4"
                >
                  <strong>預計到貨日期</strong>

                  <div class="mt-1">
                    {{ order.delivery.estimated_arrival_date || '-' }}
                  </div>
                </div>

                <div
                  v-if="order.delivery?.delivery_status !== 'completed'"
                >

                  <div
                    v-if="order.delivery?.delivery_status === 'pending'"
                  >
                    <CButton
                      color="primary"
                      :disabled="updatingDelivery"
                      @click="openDeliveryConfirmModal('shipping')"
                    >
                      {{ updatingDelivery ? '更新中...' : '開始配送' }}
                    </CButton>
                  </div>

                  <div
                    v-else-if="order.delivery?.delivery_status === 'shipping'"
                  >
                    <CButton
                      color="success"
                      :disabled="updatingDelivery"
                      @click="openDeliveryConfirmModal('completed')"
                    >
                      {{ updatingDelivery ? '更新中...' : '完成配送' }}
                    </CButton>
                  </div>

                </div>
              </CCardBody>
            </CCard>

            <!-- 退款資訊 -->
            <CCard class="mb-4">
              <CCardBody>
                <h4 class="mb-4">
                  退款資訊
                </h4>

                <div
                  v-if="!refundEnable"
                  class="text-body-secondary"
                >
                  目前無開啟退款功能; 若需開啟，可至「商店設定｣
                </div>

                <template v-else-if="order.refund">

                  <div class="mb-3">
                    <strong>退款狀態</strong>

                    <div
                      class="mt-1"
                      :class="{ 'text-danger fw-bold': order.refund.refund_status === 'pending' }"
                    >
                      {{ getRefundStatusText(order.refund.refund_status) }}
                    </div>
                  </div>

                  <div class="mb-3">
                    <strong>退款原因</strong>

                    <div class="mt-1">
                      {{ order.refund.refund_reason || '-' }}
                    </div>
                  </div>

                  <div class="mb-3">
                    <strong>退款說明</strong>

                    <div class="mt-1">
                      {{ order.refund.refund_description || '-' }}
                    </div>
                  </div>

                  <div class="mb-3">
                    <strong>管理員回覆</strong>

                    <div class="mt-1">
                      {{ order.refund.admin_reply || '-' }}
                    </div>
                  </div>

                  <div class="mb-3">
                    <strong>申請時間</strong>

                    <div class="mt-1">
                      {{ formatDate(order.refund.requested_at) }}
                    </div>
                  </div>

                  <div>
                    <strong>處理時間</strong>

                    <div class="mt-1">
                      {{ formatDate(order.refund.processed_at) }}
                    </div>
                  </div>

                </template>

                <div
                  v-else
                  class="text-body-secondary"
                >
                  此訂單目前沒有退款申請
                </div>
              </CCardBody>
            </CCard>

          </div>
        </CContainer>
      </div>

      <AppFooter />
    </div>

    <!-- 錯誤提示 -->
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

    <!-- 付款確認提示 -->
    <CModal
      :visible="showPaymentConfirmModal"
      @close="closePaymentConfirmModal"
    >
      <CModalHeader>
        <CModalTitle>
          {{ paymentConfirmAction === 'confirmed' ? '確認付款' : '拒絕付款' }}
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        {{
          paymentConfirmAction === 'confirmed'
            ? '是否確認付款？確認後將無法撤銷'
            : '是否拒絕付款？拒絕後將無法撤銷'
        }}
      </CModalBody>

      <CModalFooter class="border-0">
        <CButton
          color="secondary"
          :disabled="confirmingPayment"
          @click="closePaymentConfirmModal"
        >
          取消
        </CButton>

        <CButton
          :color="paymentConfirmAction === 'confirmed' ? 'success' : 'danger'"
          :disabled="confirmingPayment"
          @click="confirmPayment(paymentConfirmAction)"
        >
          {{ confirmingPayment ? '處理中...' : '確認' }}
        </CButton>
      </CModalFooter>
    </CModal>

    <!-- 配送確認提示 -->
    <CModal
      :visible="showDeliveryConfirmModal"
      @close="closeDeliveryConfirmModal"
    >
      <CModalHeader>
        <CModalTitle>
          {{ deliveryConfirmStatus === 'shipping' ? '開始配送' : '完成配送' }}
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        {{
          deliveryConfirmStatus === 'shipping'
            ? '是否開始配送？點按後將不可更改'
            : '是否完成配送？點按後將不可更改'
        }}
      </CModalBody>

      <CModalFooter class="border-0">
        <CButton
          color="secondary"
          :disabled="updatingDelivery"
          @click="closeDeliveryConfirmModal"
        >
          取消
        </CButton>

        <CButton
          :color="deliveryConfirmStatus === 'shipping' ? 'primary' : 'success'"
          :disabled="updatingDelivery"
          @click="confirmDeliveryStatus"
        >
          {{ updatingDelivery ? '更新中...' : '確認' }}
        </CButton>
      </CModalFooter>
    </CModal>

  </div>
</template>

<style scoped>
:deep(.table) {
  font-size: 14px;
}

.payment-proof-image {
  max-width: 500px;
  max-height: 500px;
  width: auto;
  height: auto;
  object-fit: contain;
  border: 1px solid var(--cui-border-color);
  border-radius: 4px;
}
</style>
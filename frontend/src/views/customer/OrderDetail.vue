<template>
  <div>
    <Sidebar />

    <div class="wrapper d-flex flex-column min-vh-100">
      <Header :store="order?.store || {}" />

      <div class="body flex-grow-1">
        <CContainer class="px-4" lg>
          <!-- 標題 -->
          <div class="page-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h2 class="mb-2">
                  訂單詳情
                </h2>

                <p class="text-body-secondary mb-0">
                  查看您的訂單與付款資訊
                </p>
              </div>

              <CButton
                color="secondary"
                variant="outline"
                @click="goBack"
              >
                返回訂單
              </CButton>
            </div>
          </div>

          <!-- 載入中 -->
          <div
            v-if="loading"
            class="text-center py-5"
          >
            載入中...
          </div>

          <!-- 錯誤 -->
          <CAlert
            v-else-if="error"
            color="danger"
          >
            {{ error }}
          </CAlert>

          <template v-else-if="order">
            <!-- 訂單資訊 -->
            <CCard class="order-card mb-4">
              <CCardBody>
                <div class="section-title">
                  <h5 class="mb-0">
                    訂單資訊
                  </h5>
                </div>

                <div class="order-info-list">
                  <div class="order-info-item">
                    <span class="order-info-label">
                      訂單編號
                    </span>

                    <span class="order-info-value order-number">
                      {{ order.order_number }}
                    </span>
                  </div>

                  <div class="order-info-item">
                    <span class="order-info-label">
                      訂單狀態
                    </span>

                    <span
                      class="order-info-value"
                      :class="{
                        'text-danger fw-bold':
                          order.order_status === 'pending'
                      }"
                    >
                      {{ getOrderStatusText(order.order_status) }}
                    </span>
                  </div>

                  <div class="order-info-item">
                    <span class="order-info-label">
                      訂單日期
                    </span>

                    <span class="order-info-value">
                      {{ order.order_date }}
                    </span>
                  </div>

                  <hr>

                  <div class="order-info-item">
                    <span class="order-info-label">
                      商品金額
                    </span>

                    <span class="order-info-value">
                      <span class="amount-value">
                        $ {{ Number(order.product_amount).toLocaleString() }}
                      </span>
                    </span>
                  </div>

                  <div class="order-info-item">
                    <span class="order-info-label">
                      運費
                    </span>

                    <span class="order-info-value">
                      <span class="amount-value">
                        $ {{ Number(order.shipping_fee).toLocaleString() }}
                      </span>
                    </span>
                  </div>

                  <div class="order-info-item total-amount-row">
                    <span class="order-info-label">
                      訂單總金額
                    </span>

                    <span class="order-info-value fw-semibold">
                      <span class="amount-value total-amount">
                        $ {{ Number(order.total_amount).toLocaleString() }}
                      </span>
                    </span>
                  </div>
                </div>

                <!-- Pending 訂單操作 -->
                <div
                  v-if="order.order_status === 'pending'"
                  class="d-flex gap-2 mt-4 justify-content-end"
                >
                  <CButton
                    color="danger"
                    variant="outline"
                    @click="openCancelModal"
                  >
                    取消訂單
                  </CButton>

                  <CButton
                    color="primary"
                    @click="goOrderEdit"
                  >
                    前往完成訂單
                  </CButton>
                </div>
              </CCardBody>
            </CCard>

            <!-- 收件資訊 -->
            <CCard
              v-if="order.order_status !== 'pending'"
              class="order-card mb-4"
            >
              <CCardBody>
                <div class="section-title">
                  <h5 class="mb-0">
                    收件資訊
                  </h5>
                </div>

                <div class="order-info-list">
                  <div class="order-info-item">
                    <span class="order-info-label">
                      收件人
                    </span>

                    <span class="order-info-value">
                      {{ order.receiver_name }}
                    </span>
                  </div>

                  <div class="order-info-item">
                    <span class="order-info-label">
                      聯絡電話
                    </span>

                    <span class="order-info-value">
                      {{ order.receiver_phone }}
                    </span>
                  </div>

                  <div class="order-info-item">
                    <span class="order-info-label">
                      收件地址
                    </span>

                    <span class="order-info-value">
                      {{ order.receiver_address }}
                    </span>
                  </div>
                </div>
              </CCardBody>
            </CCard>

            <!-- 商品資訊 -->
            <CCard class="order-card mb-4">
              <CCardBody>
                <div class="section-title">
                  <h5 class="mb-0">
                    商品資訊
                  </h5>
                </div>

                <div
                  v-for="item in order.items"
                  :key="item.order_item_id"
                  class="product-item border-bottom mb-3 pb-3"
                >
                  <div class="row align-items-center">

                    <!-- 商品圖片 -->
                    <div class="col-md-2 col-sm-3 mb-3 mb-sm-0">
                      <div class="product-image">
                        <img
                          :src="item.image_url"
                          :alt="item.product_name"
                        >
                      </div>
                    </div>

                    <!-- 商品資訊 -->
                    <div class="col-md-4 col-sm-5 mb-3 mb-sm-0">
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

                    <!-- 單價 × 數量 -->
                    <div class="col-md-3 col-sm-4 text-sm-end mb-3 mb-sm-0">
                      <span class="text-body-secondary">
                        $ {{ Number(item.price).toLocaleString() }} × {{ item.quantity }}
                      </span>
                    </div>

                    <!-- 小計 -->
                    <div class="col-md-3 text-md-end fw-bold">
                      $ {{ Number(item.subtotal).toLocaleString() }}
                    </div>

                  </div>
                </div>

                <div
                  v-if="order.items.length === 0"
                  class="text-body-secondary py-4"
                >
                  沒有商品
                </div>
              </CCardBody>
            </CCard>

            <!-- 配送資訊 -->
            <CCard
              v-if="order.order_status !== 'pending'"
              class="order-card mb-4"
            >
              <CCardBody>
                <div class="section-title">
                  <h5 class="mb-0">
                    配送資訊
                  </h5>
                </div>

                <div class="order-info-list">
                  <div class="order-info-item">
                    <span class="order-info-label">
                      配送方式
                    </span>

                    <span class="order-info-value">
                      {{ getDeliveryMethodText(order.delivery_method) }}
                    </span>
                  </div>

                  <div class="order-info-item">
                    <span class="order-info-label">
                      配送狀態
                    </span>

                    <span class="order-info-value">
                      {{ getDeliveryStatusText(order.delivery_status) }}
                    </span>
                  </div>

                  <!-- 配送中才顯示預計出貨日期 -->
                  <div
                    v-if="order.delivery_status === 'shipping'"
                    class="order-info-item"
                  >
                    <span class="order-info-label">
                      預計出貨日期
                    </span>

                    <span class="order-info-value">
                      {{ order.estimated_ship_date }}
                    </span>
                  </div>

                  <!-- 已送達才顯示預計送達日期 -->
                  <div
                    v-if="order.delivery_status === 'completed'"
                    class="order-info-item"
                  >
                    <span class="order-info-label">
                      預計送達日期
                    </span>

                    <span class="order-info-value">
                      {{ order.estimated_arrival_date }}
                    </span>
                  </div>
                </div>
              </CCardBody>
            </CCard>

            <!-- 付款資訊 -->
            <CCard
              v-if="
                order.order_status !== 'pending'
                && order.payment
              "
              class="order-card mb-4"
            >
              <CCardBody>
                <div class="section-title">
                  <h5 class="mb-0">
                    付款資訊
                  </h5>
                </div>

                <div class="order-info-list">
                  <!-- 付款方式 -->
                  <div class="order-info-item">
                    <span class="order-info-label">
                      付款方式
                    </span>

                    <span class="order-info-value">
                      {{ getPaymentMethodText(order.payment.payment_method) }}
                    </span>
                  </div>

                  <!-- 付款狀態 -->
                  <div class="order-info-item">
                    <span class="order-info-label">
                      付款狀態
                    </span>

                    <span class="order-info-value">
                      {{ getPaymentStatusText(order.payment.payment_status) }}
                    </span>
                  </div>

                  <!-- 已付款才顯示付款詳細資訊 -->
                  <template
                    v-if="
                      order.payment.payment_status === 'paid'
                    "
                  >
                    <!-- 付款金額 -->
                    <div class="order-info-item">
                      <span class="order-info-label">
                        付款金額
                      </span>

                      <span class="order-info-value fw-semibold">
                        <span class="amount-value">
                          $ {{ Number(order.payment.amount).toLocaleString() }}
                        </span>
                      </span>
                    </div>

                    <!-- 付款確認狀態 -->
                    <div class="order-info-item">
                      <span class="order-info-label">
                        付款確認
                      </span>

                      <span class="order-info-value">
                        {{ getPaymentConfirmText(order.payment.payment_confirm_status) }}
                      </span>
                    </div>

                    <!-- 付款備註 -->
                    <div
                      v-if="order.payment.payment_note"
                      class="order-info-item"
                    >
                      <span class="order-info-label">
                        付款備註
                      </span>

                      <span class="order-info-value">
                        {{ order.payment.payment_note }}
                      </span>
                    </div>

                    <!-- 付款時間 -->
                    <div
                      v-if="order.payment.paid_at"
                      class="order-info-item"
                    >
                      <span class="order-info-label">
                        付款時間
                      </span>

                      <span class="order-info-value">
                        {{ order.payment.paid_at }}
                      </span>
                    </div>

                    <!-- 已確認才顯示確認時間 -->
                    <div
                      v-if="
                        order.payment.payment_confirm_status === 'confirmed'
                        && order.payment.confirmed_at
                      "
                      class="order-info-item"
                    >
                      <span class="order-info-label">
                        確認時間
                      </span>

                      <span class="order-info-value">
                        {{ order.payment.confirmed_at }}
                      </span>
                    </div>
                  </template>
                </div>
              </CCardBody>
            </CCard>

            <!-- 退款資訊 -->
            <CCard
              v-if="
                order.order_status !== 'pending'
                && order.payment?.payment_status !== 'pending'
              "
              class="order-card mb-4"
            >
              <CCardBody>
                <div class="section-title">
                  <h5 class="mb-0">
                    退款資訊
                  </h5>
                </div>

                <!-- 店家關閉退款功能 -->
                <div
                  v-if="!refundEnable"
                  class="text-body-secondary"
                >
                  目前無開啟退款功能
                </div>

                <!-- 有退款資料 -->
                <template v-else-if="order.refund">
                  <div class="order-info-list">
                    <div class="order-info-item">
                      <span class="order-info-label">
                        退款狀態
                      </span>

                      <span class="order-info-value">
                        {{ getRefundText(order.refund.refund_status) }}
                      </span>
                    </div>

                    <div
                      v-if="order.refund.refund_reason"
                      class="order-info-item"
                    >
                      <span class="order-info-label">
                        退款原因
                      </span>

                      <span class="order-info-value">
                        {{ order.refund.refund_reason }}
                      </span>
                    </div>

                    <div
                      v-if="order.refund.refund_description"
                      class="order-info-item"
                    >
                      <span class="order-info-label">
                        退款說明
                      </span>

                      <span class="order-info-value">
                        {{ order.refund.refund_description }}
                      </span>
                    </div>

                    <div
                      v-if="order.refund.admin_reply"
                      class="order-info-item"
                    >
                      <span class="order-info-label">
                        商家回覆
                      </span>

                      <span class="order-info-value">
                        {{ order.refund.admin_reply }}
                      </span>
                    </div>
                  </div>
                </template>

                <!-- 尚無退款資料 -->
                <div
                  v-else
                  class="text-body-secondary"
                >
                  尚無退款資料
                </div>
              </CCardBody>
            </CCard>
          </template>
        </CContainer>
      </div>

      <!-- Footer -->
      <Footer
        :footer="order?.footer || {}"
        class="mt-auto"
      />

      <Createdby />
    </div>

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
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import {
  CAlert,
  CButton,
  CCard,
  CCardBody,
  CContainer,
  CModal,
  CModalHeader,
  CModalTitle,
  CModalBody,
  CModalFooter
} from '@coreui/vue'

import Sidebar from '../../components/customer_homepage/Sidebar.vue'
import Header from '../../components/customer_homepage/Header.vue'
import Footer from '../../components/customer_homepage/Footer.vue'
import Createdby from '../../components/customer_homepage/Createdby.vue'

import {
  getCustomerOrder,
  deleteCustomerOrder
} from '../../api/customer.js'

const route = useRoute()
const router = useRouter()

const storeId = Number(route.params.storeId)
const orderId = Number(route.params.orderId)

const loading = ref(true)
const cancelling = ref(false)
const error = ref('')
const order = ref(null)
const refundEnable = ref(false)
const showCancelModal = ref(false)

const loadOrder = async () => {
  loading.value = true
  error.value = ''

  try {
    const data = await getCustomerOrder(
      storeId,
      orderId
    )

    refundEnable.value =
      data.refund_enable === true

    order.value =
      data.order
  } catch (err) {
    console.error(
      '取得訂單詳情失敗:',
      err
    )

    error.value =
      err.message || '取得訂單詳情失敗'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push(
    `/store-${storeId}/order_list`
  )
}

const goOrderEdit = () => {
  router.push(
    `/store-${storeId}/checkout?order_id=${orderId}`
  )
}

// 開啟取消訂單 Modal
const openCancelModal = () => {
  showCancelModal.value = true
}

// 關閉取消訂單 Modal
const closeCancelModal = () => {
  if (cancelling.value) {
    return
  }

  showCancelModal.value = false
}

// 取消訂單
const cancelOrder = async () => {
  if (
    cancelling.value ||
    !order.value
  ) {
    return
  }

  cancelling.value = true
  error.value = ''

  try {
    await deleteCustomerOrder(
      storeId,
      order.value.order_number
    )

    showCancelModal.value = false

    router.push(
      `/store-${storeId}/order_list`
    )
  } catch (err) {
    console.error(
      '取消訂單失敗:',
      err
    )

    showCancelModal.value = false

    error.value =
      err.message || '取消訂單失敗'
  } finally {
    cancelling.value = false
  }
}

const getOrderStatusText = (status) => {
  const map = {
    pending: '未完成',
    confirmed: '已完成',
    cancelled: '已取消'
  }

  return map[status] || status
}

const getPaymentMethodText = (method) => {
  const map = {
    credit_card: '信用卡',
    atm: 'ATM',
    post_office: '郵局',
    cash_on_delivery: '貨到付款',
    in_store: '店內付款'
  }

  return map[method] || method
}

const getPaymentStatusText = (status) => {
  const map = {
    pending: '待付款',
    processing: '處理中',
    paid: '已付款',
    failed: '付款失敗'
  }

  return map[status] || status
}

const getPaymentConfirmText = (status) => {
  const map = {
    waiting: '等待確認',
    confirmed: '已確認',
    rejected: '已拒絕'
  }

  return map[status] || status
}

const getDeliveryMethodText = (method) => {
  const map = {
    home_delivery: '宅配',
    convenience_store: '超商取貨',
    store_pickup: '門市取貨'
  }

  return map[method] || method
}

const getDeliveryStatusText = (status) => {
  const map = {
    pending: '待出貨',
    shipping: '配送中',
    completed: '已送達'
  }

  return map[status] || status
}

const getRefundText = (status) => {
  const map = {
    pending: '申請中',
    approved: '已核准',
    rejected: '已拒絕'
  }

  return map[status] || status
}

onMounted(() => {
  loadOrder()
})
</script>

<style scoped>
.page-header {
  padding-top: 8px;
}

.order-card {
  border: 1px solid var(--cui-border-color);
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.section-title {
  padding-bottom: 12px;
  margin-bottom: 20px;
  border-bottom: 1px solid var(--cui-border-color);
}

.order-info-list {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.order-info-item {
  display: flex;
  align-items: flex-start;
  gap: 20px;
  padding-top: 2px;
  padding-bottom: 2px;
  line-height: 1.6;
}

.order-info-label {
  width: 110px;
  flex-shrink: 0;
  color: var(--cui-secondary-color);
}

.order-info-value {
  flex: 1;
}

.order-number {
  font-weight: 600;
  letter-spacing: 0.3px;
}

.total-amount-row {
  padding-top: 6px;
  padding-bottom: 6px;
}

.amount-value {
  display: block;
  width: 120px;
  text-align: right;
}

.total-amount {
  font-weight: 700;
}

.product-item:last-child {
  border-bottom: 0 !important;
}

.product-image {
  width: 100px;
  height: 100px;
  flex-shrink: 0;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 8px;
}

:deep(.form-control),
:deep(.form-select) {
  font-size: 14px;
}

@media (max-width: 576px) {
  .page-header .d-flex {
    align-items: flex-start !important;
    gap: 16px;
  }

  .order-info-item {
    flex-direction: column;
    gap: 4px;
  }

  .order-info-label {
    width: auto;
  }

  .amount-value {
    width: auto;
    text-align: left;
  }

  .product-image {
    margin-left: 0 !important;
    margin-right: 0 !important;
  }
}
</style>
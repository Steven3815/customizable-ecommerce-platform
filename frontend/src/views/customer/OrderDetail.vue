<template>
  <div>

    <Sidebar />

    <div class="wrapper d-flex flex-column min-vh-100">

      <Header
        :store="order?.store || {}"
      />

      <div class="body flex-grow-1">

        <CContainer class="px-4" lg>

          <!-- 標題 -->
          <div class="mb-4">

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
            <CCard class="mb-4">
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

                    <span class="order-info-value">
                      {{ order.order_number }}
                    </span>

                  </div>

                  <div class="order-info-item">

                    <span class="order-info-label">
                      訂單狀態
                    </span>

                    <span
                      class="order-info-value"
                      :class="{ 'text-danger fw-bold': order.order_status === 'pending' }"
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

                  <div class="order-info-item">

                    <span class="order-info-label">
                      訂單總金額
                    </span>

                    <span class="order-info-value fw-semibold">
                      <span class="amount-value">
                        $ {{ Number(order.total_amount).toLocaleString() }}
                      </span>
                    </span>

                  </div>

                </div>

                <CButton
                  v-if="order.order_status === 'pending'"
                  color="primary"
                  class="mt-3"
                  @click="goOrderEdit"
                >
                  前往完成訂單
                </CButton>

              </CCardBody>
            </CCard>

            <!-- 收件資訊 -->
            <CCard
              v-if="order.order_status !== 'pending'"
              class="mb-4"
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
            <CCard class="mb-4">
              <CCardBody>

                <div class="section-title">
                  <h5 class="mb-0">
                    商品資訊
                  </h5>
                </div>

                <div
                  v-for="item in order.items"
                  :key="item.order_item_id"
                  class="d-flex border-bottom py-4"
                >

                  <div class="product-image ms-4 me-4">

                    <img
                      :src="item.image_url"
                      :alt="item.product_name"
                      class="img-fluid"
                    >

                  </div>

                  <div class="flex-grow-1">

                    <h5 class="fw-bold mb-3">
                      {{ item.product_name }}
                    </h5>

                    <div
                      v-if="item.spec_name"
                      class="text-body-secondary mb-2"
                    >
                      規格：{{ item.spec_name }}
                    </div>

                    <div class="mb-2 d-flex">

                      <span>
                        單價：
                      </span>

                      <span class="product-amount">
                        $ {{ Number(item.price).toLocaleString() }}
                      </span>

                    </div>

                    <div class="mb-2 d-flex">

                      <span>
                        數量：
                      </span>

                      <span class="product-amount">
                        {{ item.quantity }}
                      </span>

                    </div>

                    <div class="fw-bold d-flex">

                      <span>
                        小計：
                      </span>

                      <span class="product-amount">
                        $ {{ Number(item.subtotal).toLocaleString() }}
                      </span>

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
              class="mb-4"
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
              v-if="order.order_status !== 'pending' && order.payment"
              class="mb-4"
            >
              <CCardBody>

                <div class="section-title">
                  <h5 class="mb-0">
                    付款資訊
                  </h5>
                </div>

                <div class="order-info-list">

                  <div class="order-info-item">

                    <span class="order-info-label">
                      付款方式
                    </span>

                    <span class="order-info-value">
                      {{ getPaymentMethodText(order.payment.payment_method) }}
                    </span>

                  </div>

                  <div class="order-info-item">

                    <span class="order-info-label">
                      付款狀態
                    </span>

                    <span class="order-info-value">
                      {{ getPaymentStatusText(order.payment.payment_status) }}
                    </span>

                  </div>

                  <template
                    v-if="order.payment.payment_status !== 'pending'"
                  >

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
              v-if="order.order_status !== 'pending'"
              class="mb-4"
            >
              <CCardBody>

                <div class="section-title">
                  <h5 class="mb-0">
                    退款資訊
                  </h5>
                </div>

                <div
                  v-if="!refundEnable"
                  class="text-body-secondary"
                >
                  目前無開啟退款功能
                </div>

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

  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import Sidebar from '../../components/customer_homepage/Sidebar.vue'
import Header from '../../components/customer_homepage/Header.vue'
import Footer from '../../components/customer_homepage/Footer.vue'
import Createdby from '../../components/customer_homepage/Createdby.vue'

import {
  getCustomerOrder
} from '../../api/customer.js'

const route = useRoute()
const router = useRouter()

const storeId = Number(route.params.storeId)
const orderId = Number(route.params.orderId)

const loading = ref(true)
const error = ref('')
const order = ref(null)
const refundEnable = ref(false)

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
    `/store-${storeId}/create_order?order_id=${orderId}`
  )

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
.product-image {
  width: 100px;
  height: 100px;
  flex-shrink: 0;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 6px;
}

.section-title {
  padding-bottom: 12px;
  margin-bottom: 18px;
  border-bottom: 1px solid var(--cui-border-color);
}

.order-info-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.order-info-item {
  display: flex;
  align-items: flex-start;
  gap: 20px;
}

.order-info-label {
  width: 110px;
  flex-shrink: 0;
  color: var(--cui-secondary-color);
}

.order-info-value {
  flex: 1;
}

.amount-value {
  display: block;
  width: 120px;
  text-align: right;
}

.product-amount {
  width: 120px;
  text-align: right;
  margin-left: 8px;
}

:deep(.form-control),
:deep(.form-select) {
  font-size: 14px;
}

@media (max-width: 576px) {

  .order-info-item {
    flex-direction: column;
    gap: 4px;
  }

  .order-info-label {
    width: auto;
  }

}
</style>
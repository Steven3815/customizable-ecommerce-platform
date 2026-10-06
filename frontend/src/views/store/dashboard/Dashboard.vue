```vue
<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

import {
  CContainer,
  CRow,
  CCol,
  CCard,
  CCardBody,
  CTable,
  CTableHead,
  CTableRow,
  CTableHeaderCell,
  CTableBody,
  CTableDataCell,
  CButton,
  CBadge
} from '@coreui/vue'

import AppFooter from '../../../components/store/AppFooter.vue'
import AppHeader from '../../../components/store/AppHeader.vue'
import AppSidebar from '../../../components/store/AppSidebar.vue'

import { getStoreDashboard } from '@/api/store.js'

const router = useRouter()

const summary = ref(null)
const recentOrders = ref([])
const notifications = ref(null)
const store = ref(null)

// 前往訂單詳細
function goToOrderDetail(orderId) {
  router.push({
    name: 'StoreOrderDetail',
    params: {
      orderId
    }
  })
}

// 配送狀態
function getDeliveryStatus(status) {
  return {
    pending: '待出貨',
    shipping: '配送中',
    completed: '已完成'
  }[status] || status
}

// 配送狀態顏色
function getDeliveryColor(status) {
  return {
    pending: 'warning',
    shipping: 'info',
    completed: 'success'
  }[status] || 'secondary'
}

onMounted(async () => {
  try {
    const data = await getStoreDashboard()

    store.value = data.store
    summary.value = data.summary
    recentOrders.value = data.recent_orders
    notifications.value = data.notifications
  } catch (e) {
    console.error('取得 Dashboard 資料失敗:', e)
  }
})
</script>

<template>
  <div>
    <AppSidebar />

    <div class="wrapper d-flex flex-column min-vh-100">
      <AppHeader />

      <div class="body flex-grow-1 dashboard-page">
        <CContainer
          class="px-4 py-4"
          lg
        >
          <!-- Dashboard Header -->
          <div
            v-if="store && summary"
            class="dashboard-header mb-4"
          >
            <div>
              <div class="dashboard-label">
                STORE DASHBOARD
              </div>

              <h2 class="dashboard-title">
                儀表板
              </h2>

              <p class="dashboard-subtitle mb-0">
                歡迎回來，以下是目前商店的營運概況
              </p>
            </div>

            <!-- Store Info -->
            <div class="store-info">
              <div class="store-icon">
                <CIcon icon="cilHome" />
              </div>

              <div>
                <div class="store-name">
                  {{ store.store_name }}
                </div>

                <RouterLink
                  :to="`/store-${store.store_id}`"
                  class="store-link"
                >
                  {{ store.store_url }}

                  <CIcon
                    icon="cilExternalLink"
                    class="ms-1"
                  />
                </RouterLink>
              </div>
            </div>
          </div>

          <!-- Main Content -->
          <div v-if="store && summary">
            <!-- Store Overview -->
            <CCard class="overview-card border-0 mb-4">
              <CCardBody>
                <CRow class="align-items-center">
                  <CCol
                    md="8"
                    class="mb-3 mb-md-0"
                  >
                    <div class="overview-content">
                      <div class="overview-icon">
                        <CIcon icon="cilPeople" />
                      </div>

                      <div>
                        <div class="overview-label">
                          商店會員
                        </div>

                        <div class="overview-value">
                          {{ summary.member_count }}

                          <span>
                            位
                          </span>
                        </div>
                      </div>
                    </div>
                  </CCol>

                  <CCol
                    md="4"
                    class="text-md-end"
                  >
                    <div class="overview-status">
                      <span class="status-dot"></span>
                      商店正常運作中
                    </div>
                  </CCol>
                </CRow>
              </CCardBody>
            </CCard>

            <!-- Summary Cards -->
            <CRow class="g-4 mb-4">
              <!-- 今日訂單 -->
              <CCol md="4">
                <CCard class="summary-card border-0 h-100">
                  <CCardBody class="summary-card-body">
                    <div class="summary-card-top">
                      <div class="summary-icon order-icon">
                        <CIcon icon="cilCart" />
                      </div>

                      <span class="summary-period">
                        TODAY
                      </span>
                    </div>

                    <div class="summary-content">
                      <div class="summary-label">
                        今日訂單
                      </div>

                      <div class="summary-value">
                        {{ summary.today_orders }}

                        <span>
                          份
                        </span>
                      </div>

                      <div class="summary-description">
                        今日新增訂單數量
                      </div>
                    </div>
                  </CCardBody>
                </CCard>
              </CCol>

              <!-- 今日營收 -->
              <CCol md="4">
                <CCard class="summary-card border-0 h-100">
                  <CCardBody class="summary-card-body">
                    <div class="summary-card-top">
                      <div class="summary-icon revenue-icon">
                        <CIcon icon="cilDollar" />
                      </div>

                      <span class="summary-period">
                        TODAY
                      </span>
                    </div>

                    <div class="summary-content">
                      <div class="summary-label">
                        今日營收
                      </div>

                      <div class="summary-value">
                        ${{ summary.today_revenue }}
                      </div>

                      <div class="summary-description">
                        今日累計營業收入
                      </div>
                    </div>
                  </CCardBody>
                </CCard>
              </CCol>

              <!-- 本月營收 -->
              <CCol md="4">
                <CCard class="summary-card border-0 h-100">
                  <CCardBody class="summary-card-body">
                    <div class="summary-card-top">
                      <div class="summary-icon monthly-icon">
                        <CIcon icon="cilChartPie" />
                      </div>

                      <span class="summary-period">
                        MONTH
                      </span>
                    </div>

                    <div class="summary-content">
                      <div class="summary-label">
                        本月營收
                      </div>

                      <div class="summary-value">
                        ${{ summary.monthly_revenue }}
                      </div>

                      <div class="summary-description">
                        本月累計營業收入
                      </div>
                    </div>
                  </CCardBody>
                </CCard>
              </CCol>
            </CRow>

            <!-- Recent Orders -->
            <CRow class="mb-4">
              <CCol>
                <CCard class="dashboard-card border-0">
                  <CCardBody class="p-0">
                    <!-- Section Header -->
                    <div class="card-section-header">
                      <div>
                        <h4>
                          最近訂單
                        </h4>

                      </div>

                      <CIcon
                        icon="cilList"
                        class="section-header-icon"
                      />
                    </div>

                    <!-- Order Table -->
                    <div
                      v-if="recentOrders.length"
                      class="table-responsive"
                    >
                      <CTable
                        hover
                        align="middle"
                        class="order-table mb-0"
                      >
                        <CTableHead>
                          <CTableRow>
                            <CTableHeaderCell class="text-center">
                              訂單編號
                            </CTableHeaderCell>

                            <CTableHeaderCell class="text-center">
                              訂購時間
                            </CTableHeaderCell>

                            <CTableHeaderCell class="text-center">
                              會員
                            </CTableHeaderCell>

                            <CTableHeaderCell class="text-center">
                              訂單
                            </CTableHeaderCell>

                            <CTableHeaderCell class="text-center">
                              配送狀態
                            </CTableHeaderCell>

                            <CTableHeaderCell class="text-center">
                              操作
                            </CTableHeaderCell>
                          </CTableRow>
                        </CTableHead>

                        <CTableBody>
                          <CTableRow
                            v-for="order in recentOrders"
                            :key="order.order_number"
                          >
                            <!-- 訂單編號 -->
                            <CTableDataCell class="text-center">
                              <span class="order-number">
                                {{ order.order_number }}
                              </span>
                            </CTableDataCell>

                            <!-- 訂購時間 -->
                            <CTableDataCell class="text-center">
                              <span class="order-date">
                                {{ order.created_at }}
                              </span>
                            </CTableDataCell>

                            <!-- 會員 -->
                            <CTableDataCell class="text-center">
                              {{ order.customer_name }}
                            </CTableDataCell>

                            <!-- 訂單金額 -->
                            <CTableDataCell class="text-center">
                              ${{ order.total_amount }}
                            </CTableDataCell>

                            <!-- 配送狀態 -->
                            <CTableDataCell class="text-center">
                              <CBadge
                                :color="getDeliveryColor(order.delivery_status)"
                                class="status-badge"
                              >
                                {{ getDeliveryStatus(order.delivery_status) }}
                              </CBadge>
                            </CTableDataCell>

                            <!-- 操作 -->
                            <CTableDataCell class="text-center">
                              <CButton
                                color="light"
                                size="sm"
                                class="view-button"
                                @click="goToOrderDetail(order.order_id)"
                              >
                                <span>
                                  查看
                                </span>
                              </CButton>
                            </CTableDataCell>
                          </CTableRow>
                        </CTableBody>
                      </CTable>
                    </div>

                    <!-- Empty Orders -->
                    <div
                      v-else
                      class="empty-orders"
                    >
                      <div class="empty-icon">
                        <CIcon icon="cilCart" />
                      </div>

                      <h5>
                        目前沒有訂單
                      </h5>

                      <p>
                        商店有新的訂單後會顯示在這裡
                      </p>
                    </div>
                  </CCardBody>
                </CCard>
              </CCol>
            </CRow>

            <!-- Notifications -->
            <CRow class="mb-4">
              <CCol>
                <CCard
                  v-if="notifications"
                  class="dashboard-card border-0"
                >
                  <CCardBody class="p-0">
                    <!-- Section Header -->
                    <div class="card-section-header notification-header">
                      <div>
                        <h4>
                          待處理事項
                        </h4>

                        <p>
                          需要你注意的項目
                        </p>
                      </div>

                      <CIcon
                        icon="cilBell"
                        class="section-header-icon"
                      />
                    </div>

                    <!-- Notification Cards -->
                    <div class="notification-grid">
                      <!-- 待確認收款 -->
                      <div class="notification-card">
                        <div class="notification-icon payment-icon">
                          <CIcon icon="cilDollar" />
                        </div>

                        <div class="notification-info">
                          <span>
                            待確認收款
                          </span>

                          <strong>
                            {{ notifications.pending_payment_confirm }}
                          </strong>
                        </div>
                      </div>

                      <!-- 待出貨 -->
                      <div class="notification-card">
                        <div class="notification-icon shipment-icon">
                          <CIcon icon="cilBasket" />
                        </div>

                        <div class="notification-info">
                          <span>
                            待出貨
                          </span>

                          <strong>
                            {{ notifications.pending_shipment }}
                          </strong>
                        </div>
                      </div>

                      <!-- 待處理退款 -->
                      <div class="notification-card">
                        <div class="notification-icon refund-icon">
                          <CIcon icon="cilDollar" />
                        </div>

                        <div class="notification-info">
                          <span>
                            待處理退款
                          </span>

                          <strong>
                            {{ notifications.pending_refund }}
                          </strong>
                        </div>
                      </div>

                      <!-- 庫存預警 -->
                      <div class="notification-card">
                        <div class="notification-icon stock-icon">
                          <CIcon icon="cilBug" />
                        </div>

                        <div class="notification-info">
                          <span>
                            庫存預警
                          </span>

                          <strong>
                            {{ notifications.stock_alert }}
                          </strong>
                        </div>
                      </div>

                      <!-- 缺貨 -->
                      <div class="notification-card">
                        <div class="notification-icon out-stock-icon">
                          <CIcon icon="cilBan" />
                        </div>

                        <div class="notification-info">
                          <span>
                            缺貨
                          </span>

                          <strong>
                            {{ notifications.out_of_stock }}
                          </strong>
                        </div>
                      </div>
                    </div>
                  </CCardBody>
                </CCard>
              </CCol>
            </CRow>
          </div>

          <!-- Loading -->
          <div
            v-else
            class="loading-container"
          >
            <div class="loading-spinner">
              <CIcon
                icon="cilCloudDownload"
                size="xl"
              />
            </div>

            <p>
              載入中...
            </p>
          </div>

          <router-view />
        </CContainer>
      </div>

      <AppFooter />
    </div>
  </div>
</template>

<style scoped>
/* ========================================
   Dashboard
======================================== */

.dashboard-page {
  background: #f8f9fa;
}

/* ========================================
   Header
======================================== */

.dashboard-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 30px;
}

.dashboard-label {
  margin-bottom: 8px;
  color: var(--cui-primary);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 1.5px;
}

.dashboard-title {
  margin: 0;
  font-size: 32px;
  font-weight: 700;
  letter-spacing: -0.5px;
}

.dashboard-subtitle {
  margin-top: 8px;
  color: #6c757d;
}

.store-info {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border: 1px solid #e9ecef;
  border-radius: 12px;
  background: #ffffff;
}

.store-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background: rgba(50, 31, 219, 0.1);
  color: var(--cui-primary);
  font-size: 18px;
}

.store-name {
  margin-bottom: 3px;
  font-weight: 600;
}

.store-link {
  color: #6c757d;
  font-size: 13px;
  text-decoration: none;
}

.store-link:hover {
  color: var(--cui-primary);
}

/* ========================================
   Overview
======================================== */

.overview-card {
  border-radius: 14px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
}

.overview-content {
  display: flex;
  align-items: center;
  gap: 15px;
}

.overview-icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  background: rgba(50, 31, 219, 0.1);
  color: var(--cui-primary);
  font-size: 21px;
}

.overview-label {
  color: #6c757d;
  font-size: 14px;
}

.overview-value {
  margin-top: 2px;
  font-size: 24px;
  font-weight: 700;
}

.overview-value span {
  margin-left: 3px;
  color: #6c757d;
  font-size: 14px;
  font-weight: 400;
}

.overview-status {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  color: #198754;
  font-size: 14px;
  font-weight: 500;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #198754;
}

/* ========================================
   Summary Cards
======================================== */

.summary-card {
  border-radius: 14px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.summary-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
}

/* Summary Card 內容 */

.summary-card-body {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

/* 上方 Icon + TODAY */

.summary-card-top {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

/* Icon */

.summary-icon {
  width: 46px;
  height: 46px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 11px;
  font-size: 20px;
}

.order-icon {
  background: rgba(50, 31, 219, 0.1);
  color: var(--cui-primary);
}

.revenue-icon {
  background: rgba(25, 135, 84, 0.1);
  color: #198754;
}

.monthly-icon {
  background: rgba(13, 110, 253, 0.1);
  color: #0d6efd;
}

.summary-period {
  color: #adb5bd;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 1px;
}

/* 中間主要內容 */

.summary-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
}

/* 標題 */

.summary-label {
  font-weight: 700;
  margin-top: 22px;
  color: #6c757d;
  font-size: 18px;
  text-align: center;
}

/* 數字 */

.summary-value {
  margin-top: 5px;
  font-size: 28px;
  font-weight: 700;
  line-height: 1.2;
  text-align: center;
}

.summary-value span {
  margin-left: 3px;
  color: #6c757d;
  font-size: 14px;
  font-weight: 400;
}

/* 說明 */

.summary-description {
  margin-top: 10px;
  color: #adb5bd;
  font-size: 12px;
  text-align: center;
}

/* ========================================
   Dashboard Card
======================================== */

.dashboard-card {
  overflow: hidden;
  border-radius: 14px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
}

.card-section-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 24px 24px 18px;
  border-bottom: 1px solid #f1f3f5;
}

.card-section-header h4 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.card-section-header p {
  margin: 5px 0 0;
  color: #adb5bd;
  font-size: 13px;
}

.section-header-icon {
  color: #adb5bd;
  font-size: 19px;
}

/* ========================================
   Orders Table
======================================== */

.order-table {
  font-size: 13px;
}

.order-table :deep(thead th) {
  padding: 13px 16px;
  border-bottom: 1px solid #e9ecef;
  background: #f8f9fa;
  color: #6c757d;
  font-size: 12px;
  font-weight: 600;
  text-align: center;
  white-space: nowrap;
}

.order-table :deep(tbody td) {
  padding: 15px 16px;
  border-bottom: 1px solid #f1f3f5;
  text-align: center;
  white-space: nowrap;
}

.order-table :deep(tbody tr:last-child td) {
  border-bottom: 0;
}

.order-number {
  color: #212529;
  font-weight: 400;
}

.order-date {
  color: #6c757d;
  font-size: 14px;
}

.view-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  min-width: 50px;
  color: #495057;
  border: 1px solid #e9ecef;
  background: #ffffff;
  text-align: center;
}

.view-button:hover {
  color: var(--cui-primary);
  border-color: rgba(50, 31, 219, 0.2);
  background: rgba(50, 31, 219, 0.05);
}

.status-badge {
  min-width: 50px;
  padding: 5px 9px;
  font-weight: 500;
}

/* ========================================
   Empty Orders
======================================== */

.empty-orders {
  padding: 60px 20px;
  text-align: center;
}

.empty-icon {
  width: 56px;
  height: 56px;
  margin: 0 auto 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #f1f3f5;
  color: #adb5bd;
  font-size: 22px;
}

.empty-orders h5 {
  margin-bottom: 7px;
  font-size: 16px;
}

.empty-orders p {
  margin: 0;
  color: #adb5bd;
  font-size: 13px;
}

/* ========================================
   Notifications
======================================== */

.notification-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 14px;
  padding: 20px 24px 24px;
}

.notification-card {
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 16px 14px;
  border: 1px solid #e9ecef;
  border-radius: 12px;
  background: #ffffff;
  text-align: center;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.notification-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
}

.notification-icon {
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  font-size: 16px;
}

.payment-icon {
  background: rgba(25, 135, 84, 0.1);
  color: #198754;
}

.shipment-icon {
  background: rgba(13, 110, 253, 0.1);
  color: #0d6efd;
}

.refund-icon {
  background: rgba(111, 66, 193, 0.1);
  color: #6f42c1;
}

.stock-icon {
  background: rgba(255, 193, 7, 0.12);
  color: #997404;
}

.out-stock-icon {
  background: rgba(220, 53, 69, 0.1);
  color: #dc3545;
}

.notification-info {
  width: 100%;
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 3px;
  text-align: center;
}

.notification-info span {
  width: 100%;
  overflow: hidden;
  color: #6c757d;
  font-size: 13px;
  font-weight: 500;
  text-align: center;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.notification-info strong {
  color: #212529;
  font-size: 22px;
  font-weight: 600;
  line-height: 1.1;
  text-align: center;
}

/* ========================================
   Loading
======================================== */

.loading-container {
  min-height: 400px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #6c757d;
}

.loading-spinner {
  margin-bottom: 15px;
  color: var(--cui-primary);
}

.loading-container p {
  margin: 0;
}

/* ========================================
   Responsive
======================================== */

@media (max-width: 1199.98px) {
  .notification-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 991.98px) {
  .dashboard-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .store-info {
    width: 100%;
  }
}

@media (max-width: 767.98px) {
  .dashboard-page {
    padding-top: 5px;
  }

  .dashboard-title {
    font-size: 28px;
  }

  .dashboard-subtitle {
    font-size: 14px;
  }

  .store-info {
    padding: 11px 13px;
  }

  .overview-status {
    margin-top: 5px;
  }

  .card-section-header {
    padding: 20px 18px 15px;
  }

  .notification-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    padding: 16px 18px 18px;
  }

  .notification-card {
    gap: 8px;
    padding: 13px 11px;
  }

  .notification-icon {
    width: 36px;
    height: 36px;
    font-size: 14px;
  }

  .notification-info span {
    font-size: 12px;
  }

  .notification-info strong {
    font-size: 19px;
    text-align: center;
  }

  .order-table {
    min-width: 760px;
  }
}
</style>
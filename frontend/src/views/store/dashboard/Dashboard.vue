<script setup>
import { ref, onMounted } from 'vue'
import { CContainer, CRow, CCol, CCard, CCardBody } from '@coreui/vue'
import AppFooter from '../../../components/store/AppFooter.vue'
import AppHeader from '../../../components/store/AppHeader.vue'
import AppSidebar from '../../../components/store/AppSidebar.vue'

import { getStoreDashboard } from '@/api/store.js'

const summary = ref(null)
const recentOrders = ref([])
const notifications = ref(null)
const store = ref(null)

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

      <div class="body flex-grow-1">
        <CContainer class="px-4" lg>

          <div v-if="store && summary">
            <h2  class="mt-2 mb-4">Dashboard</h2>

            <div>
              <p>商店名稱：{{ store.store_name }}</p>
              <span>商店網址：</span>
              <RouterLink :to="`/store-${store.store_id}`">
                {{ store.store_url }}
              </RouterLink>
              <p  class="mt-3">會員數：{{ summary.member_count }}</p>
            </div>

            <CRow>
              <CCol :md="4">
                <CCard>
                  <CCardBody class="text-center">
                    <h4 class="mt-2">今日訂單</h4>
                    <h3>{{ summary.today_orders }}份</h3>
                  </CCardBody>
                </CCard>
              </CCol>

              <CCol :md="4">
                <CCard>
                  <CCardBody class="text-center">
                    <h4 class="mt-2">今日營收</h4>
                    <h3>${{ summary.today_revenue }}</h3>
                  </CCardBody>
                </CCard>
              </CCol>

              <CCol :md="4">
                <CCard>
                  <CCardBody class="text-center">
                    <h4 class="mt-2">本月營收</h4>
                    <h3>${{ summary.monthly_revenue }}</h3>
                  </CCardBody>
                </CCard>
              </CCol>
            </CRow>

            <div class="mt-5">
              <h4>最近訂單</h4>

            <CTable v-if="recentOrders.length">
              <CTableHead>
                <CTableRow>
                  <CTableHeaderCell class="text-center px-1">訂單編號</CTableHeaderCell>
                  <CTableHeaderCell class="text-center" style="max-width:200px;">訂購時間</CTableHeaderCell>
                  <CTableHeaderCell class="text-center">會員</CTableHeaderCell>
                  <CTableHeaderCell style="text-align: right;">訂單金額</CTableHeaderCell>
                  <CTableHeaderCell class="text-center">配送狀態</CTableHeaderCell>
                  <CTableHeaderCell class="text-center">操作</CTableHeaderCell>
                </CTableRow>
              </CTableHead>

              <CTableBody>
                <CTableRow
                  v-for="order in recentOrders"
                  :key="order.order_number"
                >
                  <CTableDataCell class="text-center">{{ order.order_number }}</CTableDataCell>
                  <CTableDataCell class="text-center">{{ order.created_at }}</CTableDataCell>
                  <CTableDataCell class="text-center">{{ order.customer_name }}</CTableDataCell>
                  <CTableDataCell style="text-align: right;">${{ order.total_amount }}</CTableDataCell>
                  <CTableDataCell class="text-center">
                    {{
                      {
                        pending: '待出貨',
                        shipping: '配送中',
                        completed: '已完成'
                      }[order.delivery_status]
                    }}
                  </CTableDataCell>
                </CTableRow>
              </CTableBody>
            </CTable>

            <p v-else>目前沒有訂單</p>
            </div>

            <div v-if="notifications" class="mt-4">
              <h4 class="mb-4">待處理事項</h4>
              <p>待確認收款：{{ notifications.pending_payment_confirm }}</p>
              <p>待出貨：{{ notifications.pending_shipment }}</p>
              <p>待處理退款：{{ notifications.pending_refund }}</p>
              <p>庫存預警：{{ notifications.stock_alert }}</p>
              <p>缺貨：{{ notifications.out_of_stock }}</p>
            </div>

            
          </div>

          <div v-else>
            載入中...
          </div>

          <router-view />
        </CContainer>
      </div>

      <AppFooter />
    </div>
  </div>
</template>
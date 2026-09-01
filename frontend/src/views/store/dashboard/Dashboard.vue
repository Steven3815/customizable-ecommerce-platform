<script setup>
import { ref, onMounted } from 'vue'
import {useRouter } from 'vue-router'
import { CContainer, CRow, CCol, CCard, CCardBody } from '@coreui/vue'
import AppFooter from '../../../components/store/AppFooter.vue'
import AppHeader from '../../../components/store/AppHeader.vue'
import AppSidebar from '../../../components/store/AppSidebar.vue'

import { getStore, getStoreDashboard } from '@/api/store.js'

const router = useRouter()
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
              <p>商店網址：</p>
              <RouterLink :to="`/store-${store.store_id}`">
                {{ store.store_url }}
              </RouterLink>
              <p>會員數：{{ summary.member_count }}</p>
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

              <table v-if="recentOrders.length" class="table">
                <thead>
                  <tr>
                    <th>訂單編號</th>
                    <th>訂購時間</th>
                    <th style="min-width: 120px;">會員</th>
                    <th style="min-width: 120px;">訂單金額</th>
                    <th>配送狀態</th>
                  </tr>
                </thead>

                <tbody>
                  <tr
                    v-for="order in recentOrders"
                    :key="order.order_number"
                  >
                    <td>{{ order.order_number }}</td>
                    <td>{{ order.created_at }}</td>
                    <td>{{ order.customer_name }}</td>
                    <td>${{ order.total_amount }}</td>
                    <td>
                      {{
                        {
                          pending: '待出貨',
                          shipping: '配送中',
                          completed: '已完成'
                        }[order.delivery_status]
                      }}
                    </td>
                  </tr>
                </tbody>
              </table>

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
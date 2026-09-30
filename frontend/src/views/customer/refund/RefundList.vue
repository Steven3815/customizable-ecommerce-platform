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
              <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mt-2 mb-0">
                  退款申請
                </h2>
              </div>

              <!-- 篩選 -->
              <div class="row g-3">
                <div class="col-md-3">
                  <div class="mb-2">
                    <strong>退款狀態</strong>
                  </div>

                  <CFormSelect
                    v-model="status"
                    :options="statusOptions"
                    @change="changeFilter"
                  />
                </div>
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
                  @click="loadRefunds"
                >
                  重新載入
                </CButton>
              </CCardBody>
            </CCard>

            <!-- Empty -->
            <CCard
              v-else-if="!filteredRefunds.length"
              class="border-0"
            >
              <CCardBody class="text-center py-5">
                <h4 class="mb-3">
                  目前沒有符合條件的退款申請
                </h4>
              </CCardBody>
            </CCard>

            <!-- Refunds -->
            <div v-else>
              <CCard
                v-for="refund in filteredRefunds"
                :key="refund.refund_id"
                class="mb-4"
              >
                <CCardBody>

                  <!-- 訂單編號 -->
                  <div class="mb-3">
                    <h5 class="mb-0">
                      <strong>
                        訂單編號：{{ refund.order.order_number }}
                      </strong>
                    </h5>
                  </div>

                  <div class="refund-info">

                    <!-- 退款原因 -->
                    <div class="mb-3">
                      <span>
                        退款原因：
                      </span>

                      {{ getRefundReasonText(refund.refund_reason) }}
                    </div>

                    <!-- 退款金額 -->
                    <div class="mb-3">
                      <span>
                        訂單金額：
                      </span>

                      NT$ {{ refund.order.total_amount }}
                    </div>

                    <!-- 申請時間 -->
                    <div class="mb-3">
                      <span>
                        申請時間：
                      </span>

                      {{ refund.requested_at }}
                    </div>

                    <!-- 退款狀態 -->
                    <div>
                      <span>
                        退款狀態：
                      </span>

                      <span
                        :class="{
                          'text-danger fw-bold':
                            refund.refund_status === 'pending',
                          'text-success fw-bold':
                            refund.refund_status === 'approved',
                          'text-secondary fw-bold':
                            refund.refund_status === 'rejected'
                        }"
                      >
                        {{ getRefundStatusText(refund.refund_status) }}
                      </span>
                    </div>

                  </div>

                  <!-- 按鈕區塊 -->
                  <div class="refund-actions">
                    <CButton
                      color="primary"
                      @click="goOrderDetail(refund.order.order_id)"
                    >
                      查看訂單
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
  </div>
</template>

<script setup>
import {
  computed,
  onMounted,
  ref
} from 'vue'

import {
  useRoute,
  useRouter
} from 'vue-router'

import {
  CButton,
  CCard,
  CCardBody,
  CContainer,
  CFormSelect
} from '@coreui/vue'

import {
  getCustomerHome,
  getCustomerRefunds
} from '../../../api/customer.js'

import Header from '../../../components/customer_homepage/Header.vue'
import Sidebar from '../../../components/customer_homepage/Sidebar.vue'
import Footer from '../../../components/customer_homepage/Footer.vue'
import Createdby from '../../../components/customer_homepage/Createdby.vue'

const route = useRoute()
const router = useRouter()

const home = ref(null)
const refunds = ref([])

const loading = ref(true)
const error = ref('')

const storeId = route.params.storeId

const status = ref('all')

const statusOptions = [
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

const refundReasonOptions = [
  {
    label: '請選擇退款原因',
    value: ''
  },
  {
    label: '商品瑕疵',
    value: 'product_defect'
  },
  {
    label: '商品與描述不符',
    value: 'product_not_as_described'
  },
  {
    label: '商品損壞',
    value: 'product_damaged'
  },
  {
    label: '商品缺少或錯誤',
    value: 'product_missing_or_wrong'
  },
  {
    label: '其他',
    value: 'other'
  }
]

// 取得首頁資料
async function loadHome() {
  try {
    home.value =
      await getCustomerHome(storeId)
  } catch (err) {
    console.error(
      '取得首頁資料失敗:',
      err
    )

    router.push('/404')
  }
}

// 取得退款資料
async function loadRefunds() {
  loading.value = true
  error.value = ''

  try {
    const data =
      await getCustomerRefunds(storeId)

    refunds.value =
      data.refunds || []

  } catch (err) {
    console.error(
      '取得退款列表失敗:',
      err
    )

    if (err.status === 401) {
      router.replace('/401')
      return
    }

    if (err.status === 404) {
      router.replace('/404')
      return
    }

    error.value =
      err.message ||
      '取得退款列表失敗'

  } finally {
    loading.value = false
  }
}

// 篩選退款
const filteredRefunds = computed(() => {
  if (status.value === 'all') {
    return refunds.value
  }

  return refunds.value.filter(
    refund =>
      refund.refund_status === status.value
  )
})

// 篩選變更
function changeFilter(event) {
  status.value = event.target.value
}

// 退款原因文字
function getRefundReasonText(reason) {
  const option =
    refundReasonOptions.find(
      item => item.value === reason
    )

  return option?.label || reason || '未知'
}

// 退款狀態文字
function getRefundStatusText(status) {
  const statusMap = {
    pending: '處理中',
    approved: '已核准',
    rejected: '已拒絕'
  }

  return statusMap[status] || '未知'
}

// 前往訂單詳細頁
function goOrderDetail(orderId) {
  router.push(
    `/store-${storeId}/order/${orderId}`
  )
}

onMounted(() => {
  loadHome()
  loadRefunds()
})
</script>

<style scoped>
.refund-info {
  line-height: 1.6;
}

.refund-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 24px;
}

:deep(.form-control),
:deep(.form-select) {
  font-size: 14px;
}
</style>
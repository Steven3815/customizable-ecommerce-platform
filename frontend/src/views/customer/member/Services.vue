<template>
  <div>
    <div>
      <Sidebar />

      <div class="wrapper flex-column d-flex min-vh-100">
        <Header />

        <div class="body flex-grow-1">
          <CContainer class="px-4" lg>

            <!-- 標題 -->
            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mt-2 mb-0">
                  客服案件
                </h2>
              </div>

              <!-- 篩選 -->
              <div class="row g-3">
                <div class="col-md-3">
                  <div class="mb-2">
                    <strong>案件狀態</strong>
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
                  @click="loadServices"
                >
                  重新載入
                </CButton>
              </CCardBody>
            </CCard>

            <!-- Empty -->
            <CCard
              v-else-if="!services.length"
              class="border-0"
            >
              <CCardBody class="text-center py-5">
                <h4 class="mb-3">
                  目前沒有符合條件的客服案件
                </h4>
              </CCardBody>
            </CCard>

            <!-- Services -->
            <div v-else>
              <CCard
                v-for="service in services"
                :key="service.service_id"
                class="mb-4"
              >
                <CCardBody>

                  <!-- Store + 案件編號 -->
                  <div class="mb-3">
                    <h5 class="mb-2">
                      <strong>
                        {{ service.store.store_name }}
                      </strong>
                    </h5>

                    <div class="text-body-secondary">
                      編號：
                      CS{{ String(service.service_id).padStart(6, '0') }}
                    </div>
                  </div>

                  <div class="service-info">

                    <!-- 問題類型 -->
                    <div class="mb-3">
                      <span>
                        問題類型：
                      </span>

                      {{ getProblemTypeText(service.problem_type) }}
                    </div>

                    <!-- 建立時間 -->
                    <div class="mb-3">
                      <span>
                        建立時間：
                      </span>

                      {{ service.created_at }}
                    </div>

                    <!-- 案件狀態 -->
                    <div>
                      <span>
                        案件狀態：
                      </span>

                      <span
                        :class="{
                          'text-danger fw-bold':
                            service.status === 'pending',
                          'text-success fw-bold':
                            service.status === 'resolved',
                          'text-secondary fw-bold':
                            service.status === 'cancelled'
                        }"
                      >
                        {{ getStatusText(service.status) }}
                      </span>
                    </div>

                  </div>

                  <!-- 按鈕區塊 -->
                  <div class="service-actions">

                    <!-- 刪除案件 -->
                    <CButton
                      v-if="service.status === 'pending'"
                      color="danger"
                      @click="openDeleteModal(service)"
                    >
                      刪除案件
                    </CButton>

                    <!-- 查看案件 -->
                    <CButton
                      color="primary"
                      @click="goServiceDetail(service)"
                    >
                      查看案件
                    </CButton>

                  </div>

                </CCardBody>
              </CCard>
            </div>

          </CContainer>
        </div>

        <Createdby />
      </div>
    </div>

    <!-- 刪除案件確認 Modal -->
    <CModal
      :visible="showDeleteModal"
      alignment="center"
      @close="closeDeleteModal"
    >
      <CModalHeader>
        <CModalTitle>
          刪除客服案件
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        <p class="mb-2">
          確定要刪除此客服案件嗎？
        </p>
      </CModalBody>

      <CModalFooter>
        <CButton
          color="secondary"
          :disabled="deleting"
          @click="closeDeleteModal"
        >
          取消
        </CButton>

        <CButton
          color="danger"
          :disabled="deleting"
          @click="deleteService"
        >
          {{ deleting ? '刪除中...' : '確認刪除' }}
        </CButton>
      </CModalFooter>
    </CModal>

  </div>
</template>

<script setup>
import {
  onMounted,
  ref
} from 'vue'

import {
  useRouter
} from 'vue-router'

import {
  CButton,
  CCard,
  CCardBody,
  CContainer,
  CFormSelect,
  CModal,
  CModalBody,
  CModalFooter,
  CModalHeader,
  CModalTitle
} from '@coreui/vue'

import {
  getMemberServices,
  deleteCustomerService
} from '../../../api/customer.js'

import Header from '../../../components/customer_member/Header.vue'
import Sidebar from '../../../components/customer_member/Sidebar.vue'
import Createdby from '../../../components/customer_homepage/Createdby.vue'

const router = useRouter()

const services = ref([])

const loading = ref(true)
const error = ref('')

const status = ref('all')

const showDeleteModal = ref(false)
const deleting = ref(false)
const selectedService = ref(null)

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
    label: '已解決',
    value: 'resolved'
  }
]

// 取得客服案件
async function loadServices() {
  loading.value = true
  error.value = ''

  try {
    const data = await getMemberServices(
      status.value
    )

    services.value = data.services || []

  } catch (err) {
    console.error(
      '取得會員中心客服案件失敗:',
      err
    )

    if (err.status === 401) {
      router.replace('/401')
      return
    }

    error.value =
      err.message ||
      '取得客服案件失敗'

  } finally {
    loading.value = false
  }
}

// 篩選變更
function changeFilter(event) {
  status.value = event.target.value
  loadServices()
}

// 問題類型文字
function getProblemTypeText(type) {
  const typeMap = {
    product: '商品問題',
    order: '訂單問題',
    payment: '付款問題',
    delivery: '配送問題',
    refund: '退款問題',
    other: '其他問題'
  }

  return typeMap[type] || type || '未知'
}

// 案件狀態文字
function getStatusText(status) {
  const statusMap = {
    pending: '處理中',
    resolved: '已解決',
    cancelled: '已取消'
  }

  return statusMap[status] || '未知'
}

// 前往客服案件詳細頁
function goServiceDetail(service) {
  router.push(
    `/store-${service.store.store_id}/service/${service.service_id}`
  )
}

// 開啟刪除確認 Modal
function openDeleteModal(service) {
  selectedService.value = service
  showDeleteModal.value = true
}

// 關閉刪除確認 Modal
function closeDeleteModal() {
  if (deleting.value) {
    return
  }

  showDeleteModal.value = false
  selectedService.value = null
}

// 刪除客服案件
async function deleteService() {
  if (
    deleting.value ||
    !selectedService.value
  ) {
    return
  }

  deleting.value = true

  try {
    await deleteCustomerService(
      selectedService.value.store.store_id,
      selectedService.value.service_id
    )

    showDeleteModal.value = false
    selectedService.value = null

    await loadServices()

  } catch (err) {
    console.error(
      '刪除客服案件失敗:',
      err
    )

    error.value =
      err.message ||
      '刪除客服案件失敗'

    showDeleteModal.value = false
    selectedService.value = null

  } finally {
    deleting.value = false
  }
}

onMounted(() => {
  loadServices()
})
</script>

<style scoped>
.service-info {
  line-height: 1.6;
}

.service-actions {
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
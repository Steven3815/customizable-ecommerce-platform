<script setup>
import { ref, onMounted } from 'vue'

import {
  CContainer,
  CCard,
  CCardBody,
  CRow,
  CCol,
  CFormLabel,
  CFormCheck,
  CFormInput,
  CFormSelect,
  CButton,
  CModal,
  CModalHeader,
  CModalTitle,
  CModalBody,
  CModalFooter
} from '@coreui/vue'

import AppFooter from '../../../components/store/AppFooter.vue'
import AppHeader from '../../../components/store/AppHeader.vue'
import AppSidebar from '../../../components/store/AppSidebar.vue'

import {
  getStoreSettings,
  updateStoreSettings
} from '@/api/store.js'

const storeSettings = ref({
  store_id: null,
  store_status: 'open',
  store_mode: 'shopping',
  refund_enable: false,
  refund_days_limit: 0,
  shipping_days: 0,
  delivery_days: 0,
  stock_alert_enable: false,
  stock_alert_threshold: null,
  spec_stock_alert_threshold: null,
  customer_service_enable: false
})

const paymentMethods = ref([])
const deliveryMethods = ref([])

const loading = ref(true)
const saving = ref(false)

const showDescription = ref(false)

const errorVisible = ref(false)
const errorMessage = ref('')

const paymentMethodLabels = {
  credit_card: '信用卡',
  atm: 'ATM 轉帳',
  post_office: '郵局轉帳',
  cash_on_delivery: '貨到付款',
  in_store: '到店取貨'
}

const deliveryMethodLabels = {
  home_delivery: '宅配',
  convenience_store: '超商取貨',
  store_pickup: '門市取貨'
}

function showError(message) {
  errorMessage.value = message
  errorVisible.value = true
}

async function loadSettings() {
  loading.value = true

  try {
    const data = await getStoreSettings()

    storeSettings.value = data.store_settings

    paymentMethods.value = data.payment_methods.map(payment => ({
      ...payment,
      account: payment.account || {}
    }))

    deliveryMethods.value = data.delivery_methods
  } catch (error) {
    if (error.status === 403) {
      showError('您沒有權限查看商店設定')
    } else if (error.status === 404) {
      showError('找不到商店設定')
    } else {
      showError('取得商店設定失敗')
    }
  } finally {
    loading.value = false
  }
}

function validateSettings() {
  if (storeSettings.value.refund_enable) {
    if (
      storeSettings.value.refund_days_limit === '' ||
      storeSettings.value.refund_days_limit === null ||
      storeSettings.value.refund_days_limit === undefined
    ) {
      showError('請填寫退貨期限')
      return false
    }

    if (Number(storeSettings.value.refund_days_limit) < 0) {
      showError('退貨期限不可小於 0')
      return false
    }
  }

  if (Number(storeSettings.value.shipping_days) < 0) {
    showError('出貨天數不可小於 0')
    return false
  }

  if (Number(storeSettings.value.delivery_days) < 0) {
    showError('配送天數不可小於 0')
    return false
  }

  if (storeSettings.value.stock_alert_enable) {
    if (
      storeSettings.value.stock_alert_threshold === '' ||
      storeSettings.value.stock_alert_threshold === null ||
      storeSettings.value.stock_alert_threshold === undefined
    ) {
      showError('請填寫商品庫存警戒值')
      return false
    }

    if (Number(storeSettings.value.stock_alert_threshold) < 0) {
      showError('商品庫存警戒值不可小於 0')
      return false
    }

    if (
      storeSettings.value.spec_stock_alert_threshold === '' ||
      storeSettings.value.spec_stock_alert_threshold === null ||
      storeSettings.value.spec_stock_alert_threshold === undefined
    ) {
      showError('請填寫規格庫存警戒值')
      return false
    }

    if (Number(storeSettings.value.spec_stock_alert_threshold) < 0) {
      showError('規格庫存警戒值不可小於 0')
      return false
    }
  }

  for (const payment of paymentMethods.value) {
    if (!payment.enabled) {
      continue
    }

    if (payment.payment_method === 'atm') {
      if (!payment.account.bank_name || !payment.account.bank_number) {
        showError('請填寫 ATM 轉帳銀行資料')
        return false
      }
    }

    if (payment.payment_method === 'post_office') {
      if (!payment.account.post_office_number) {
        showError('請填寫郵局轉帳帳號')
        return false
      }
    }
  }

  return true
}

async function saveSettings() {
  if (saving.value) {
    return
  }

  if (!validateSettings()) {
    return
  }

  saving.value = true

  try {
    const paymentMethodsPayload = paymentMethods.value.map(payment => {
      const item = {
        store_payment_id: payment.store_payment_id,
        enabled: payment.enabled
      }

      if (payment.payment_method === 'atm' && payment.enabled) {
        item.bank_name = payment.account.bank_name.trim()
        item.bank_number = payment.account.bank_number.trim()
      }

      if (payment.payment_method === 'post_office' && payment.enabled) {
        item.post_office_number = payment.account.post_office_number.trim()
      }

      return item
    })

    const deliveryMethodsPayload = deliveryMethods.value.map(delivery => ({
      store_delivery_id: delivery.store_delivery_id,
      enabled: delivery.enabled
    }))

    const payload = {
      store_status: storeSettings.value.store_status,
      store_mode: storeSettings.value.store_mode,
      refund: {
        enabled: storeSettings.value.refund_enable,
        days_limit: Number(storeSettings.value.refund_days_limit)
      },
      shipping: {
        shipping_days: Number(storeSettings.value.shipping_days),
        delivery_days: Number(storeSettings.value.delivery_days)
      },
      stock_alert: {
        enabled: storeSettings.value.stock_alert_enable,
        threshold: storeSettings.value.stock_alert_threshold === ''
          ? null
          : storeSettings.value.stock_alert_threshold === null
            ? null
            : Number(storeSettings.value.stock_alert_threshold),
        spec_threshold: storeSettings.value.spec_stock_alert_threshold === ''
          ? null
          : storeSettings.value.spec_stock_alert_threshold === null
            ? null
            : Number(storeSettings.value.spec_stock_alert_threshold)
      },
      customer_service: {
        enabled: storeSettings.value.customer_service_enable
      },
      payment_methods: paymentMethodsPayload,
      delivery_methods: deliveryMethodsPayload
    }

    const data = await updateStoreSettings(payload)

    showError('儲存成功' || data.message)

    await loadSettings()
  } catch (error) {
    if (error.status === 400) {
      showError(error.message || '設定資料格式錯誤')
    } else if (error.status === 403) {
      showError('您沒有權限修改商店設定')
    } else if (error.status === 409) {
      showError('目前商店模式無法切換')
    } else {
      showError('更新商店設定失敗')
    }
  } finally {
    saving.value = false
  }
}

function cancelChanges() {
  window.location.reload()
}

onMounted(loadSettings)
</script>

<template>
  <div>
    <AppSidebar />

    <div class="wrapper d-flex flex-column min-vh-100">
      <AppHeader />

      <div class="body flex-grow-1">
        <CContainer class="px-4" lg>

          <!-- 標題 -->
          <div class="d-flex justify-content-between align-items-center mt-2 mb-4">
            <h2 class="mb-0">
              商店設定
            </h2>

            <CFormCheck
              v-model="showDescription"
              :label="showDescription ? '隱藏說明' : '顯示說明'"
            />
          </div>

          <div
            v-if="loading"
            class="text-center py-5 text-body-secondary"
          >
            載入中...
          </div>

          <template v-else>

            <!-- 商店基本設定 -->
            <CCard class="mb-4">
              <CCardBody>

                <h4 class="mb-4">
                  商店基本設定
                </h4>

                <CRow class="mb-4">
                  <CCol :md="3">
                    <div class="d-flex align-items-center">
                      <CFormLabel class="mb-0 me-3 text-nowrap">
                        商店狀態
                      </CFormLabel>

                      <CFormSelect v-model="storeSettings.store_status">
                        <option value="open">
                          營業中
                        </option>

                        <option value="closed">
                          暫停營業
                        </option>
                      </CFormSelect>
                    </div>
                  </CCol>
                </CRow>

                <CRow>
                  <CCol :md="3">
                    <div class="d-flex align-items-center">
                      <CFormLabel class="mb-0 me-3 text-nowrap">
                        商店模式
                      </CFormLabel>

                      <CFormSelect
                        v-if="storeSettings.store_mode === 'showcase'"
                        v-model="storeSettings.store_mode"
                      >
                        <option value="showcase">
                          展示模式
                        </option>

                        <option value="shopping">
                          購物模式
                        </option>
                      </CFormSelect>

                      <CFormInput
                        v-else
                        value="購物模式"
                        readonly
                      />
                    </div>
                  </CCol>
                </CRow>

                <small
                  v-if="showDescription"
                  class="text-body-secondary"
                >
                  <br>
                  <strong>說明：</strong>
                  暫停營業時，顧客仍可瀏覽商品，但無法下單 (適合休假、整理庫存)
                </small>

              </CCardBody>
            </CCard>

            <!-- 付款方式 -->
            <CCard class="mb-4">
              <CCardBody>

                <h4 class="mb-4">
                  付款方式
                </h4>

                <div
                  v-for="payment in paymentMethods"
                  :key="payment.store_payment_id"
                  class="mb-4"
                >

                  <CFormCheck
                    v-model="payment.enabled"
                    :label="paymentMethodLabels[payment.payment_method]"
                  />

                  <!-- ATM 轉帳 -->
                  <div
                    v-if="payment.enabled && payment.payment_method === 'atm'"
                    class="ms-4 mt-3"
                  >
                    <CRow>

                      <CCol :md="3">
                        <div class="d-flex align-items-center">
                          <CFormLabel class="mb-0 me-3 text-nowrap">
                            銀行名稱:
                          </CFormLabel>

                          <CFormInput
                            v-model="payment.account.bank_name"
                            placeholder="請輸入銀行名稱"
                          />
                        </div>
                      </CCol>

                    </CRow>

                    <CRow class="mt-4">
                      <CCol :md="3">
                        <div class="d-flex align-items-center">
                          <CFormLabel class="mb-0 me-3 text-nowrap">
                            銀行帳號:
                          </CFormLabel>

                          <CFormInput
                            v-model="payment.account.bank_number"
                            placeholder="請輸入銀行帳號"
                          />
                        </div>
                      </CCol>
                    </CRow>
                  </div>

                  <!-- 郵局轉帳 -->
                  <div
                    v-if="payment.enabled && payment.payment_method === 'post_office'"
                    class="ms-4 mt-3"
                  >
                    <CRow>
                      <CCol :md="3">
                        <div class="d-flex align-items-center">
                          <CFormLabel class="mb-0 me-3 text-nowrap">
                            郵局帳號:
                          </CFormLabel>

                          <CFormInput
                            v-model="payment.account.post_office_number"
                            placeholder="請輸入郵局帳號"
                          />
                        </div>
                      </CCol>
                    </CRow>
                  </div>

                </div>

                <small
                  v-if="showDescription"
                  class="text-body-secondary"
                >
                  <strong>說明：</strong>
                  選擇可供顧客使用的付款方式
                </small>

              </CCardBody>
            </CCard>

            <!-- 配送方式 -->
            <CCard class="mb-4">
              <CCardBody>

                <h4 class="mb-4">
                  配送方式
                </h4>

                <div
                  v-for="delivery in deliveryMethods"
                  :key="delivery.store_delivery_id"
                  class="mb-3"
                >
                  <CFormCheck
                    v-model="delivery.enabled"
                    :label="deliveryMethodLabels[delivery.delivery_method]"
                  />
                </div>

                <small
                  v-if="showDescription"
                  class="text-body-secondary"
                >
                  <strong>說明：</strong>
                  選擇可供顧客使用的配送方式
                </small>

              </CCardBody>
            </CCard>

            <!-- 退貨功能 -->
            <CCard class="mb-4">
              <CCardBody>

                <h4 class="mb-4">
                  退貨功能
                </h4>

                <CFormCheck
                  v-model="storeSettings.refund_enable"
                  label="啟用退貨功能"
                />

                <CRow
                  v-if="storeSettings.refund_enable"
                  class="mt-4"
                >
                  <CCol :md="2">
                    <div class="d-flex align-items-center">
                      <CFormLabel class="mb-0 me-3 text-nowrap">
                        退貨期限
                      </CFormLabel>

                      <CFormInput
                        v-model="storeSettings.refund_days_limit"
                        type="number"
                        min="0"
                      />

                      <span class="ms-2 text-nowrap">
                        天
                      </span>
                    </div>
                  </CCol>
                </CRow>

                <small
                  v-if="showDescription"
                  class="text-body-secondary"
                >
                  <br>
                  <strong>說明：</strong>
                  可選擇是否開放顧客退貨 (退貨期限建議7天)
                </small>

              </CCardBody>
            </CCard>

            <!-- 庫存預警 -->
            <CCard class="mb-4">
              <CCardBody>

                <h4 class="mb-4">
                  庫存預警
                </h4>

                <CFormCheck
                  v-model="storeSettings.stock_alert_enable"
                  label="啟用庫存預警"
                />

                <CRow
                  v-if="storeSettings.stock_alert_enable"
                  class="mt-4"
                >
                  <CCol :md="2">
                    <div class="d-flex align-items-center">
                      <CFormLabel class="mb-0 me-3 text-nowrap">
                        商品預警值
                      </CFormLabel>

                      <CFormInput
                        v-model="storeSettings.stock_alert_threshold"
                        type="number"
                        min="0"
                      />

                      <span class="ms-2 text-nowrap">
                        件
                      </span>
                    </div>
                  </CCol>
                </CRow>

                <CRow
                  v-if="storeSettings.stock_alert_enable"
                  class="mt-4"
                >
                  <CCol :md="2">
                    <div class="d-flex align-items-center">
                      <CFormLabel class="mb-0 me-3 text-nowrap">
                        規格預警值
                      </CFormLabel>

                      <CFormInput
                        v-model="storeSettings.spec_stock_alert_threshold"
                        type="number"
                        min="0"
                      />

                      <span class="ms-2 text-nowrap">
                        件
                      </span>
                    </div>
                  </CCol>
                </CRow>

                <small
                  v-if="showDescription"
                  class="text-body-secondary"
                >
                  <br>
                  <strong>說明：</strong>
                  可選擇是否開啟預警 (開啟後若低於預警值，系統會自動標註商品)
                </small>

              </CCardBody>
            </CCard>

            <!-- 客服設定 -->
            <CCard class="mb-4">
              <CCardBody>

                <h4 class="mb-4">
                  客服設定
                </h4>

                <CFormCheck
                  v-model="storeSettings.customer_service_enable"
                  label="啟用客服功能"
                />

                <small
                  v-if="showDescription"
                  class="text-body-secondary"
                >
                  <strong>說明：</strong>
                  可選擇是否開啟客服功能 (開啟後顧客可透過客服功能與商店聯繫)
                </small>

              </CCardBody>
            </CCard>

            <!-- 操作 -->
            <div class="d-flex justify-content-end mb-5">

              <CButton
                color="secondary"
                class="me-2"
                :disabled="saving"
                @click="cancelChanges"
              >
                取消修改
              </CButton>

              <CButton
                color="primary"
                :disabled="saving"
                @click="saveSettings"
              >
                {{ saving ? '儲存中...' : '儲存設定' }}
              </CButton>

            </div>

          </template>

        </CContainer>
      </div>

      <AppFooter />
    </div>

    <!-- 提示視窗 -->
    <CModal
      :visible="errorVisible"
      @close="errorVisible = false"
    >
      <CModalHeader class="border-0">
        <CModalTitle>
          提示
        </CModalTitle>
      </CModalHeader>

      <CModalBody>
        {{ errorMessage }}
      </CModalBody>

      <CModalFooter class="border-0">
        <CButton
          color="primary"
          @click="errorVisible = false"
        >
          確定
        </CButton>
      </CModalFooter>
    </CModal>

  </div>
</template>

<style scoped>
:deep(.form-control),
:deep(.form-select) {
  font-size: 14px;
}

:deep(input[type='number']::-webkit-inner-spin-button),
:deep(input[type='number']::-webkit-outer-spin-button) {
  -webkit-appearance: none;
  margin: 0;
}

:deep(input[type='number']) {
  -moz-appearance: textfield;
  appearance: none;
}
</style>
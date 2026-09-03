<script setup>
import { ref, onMounted } from 'vue'

import {
  CContainer,
  CCard,
  CCardBody,
  CRow,
  CCol,
  CFormInput,
  CFormCheck,
  CButton,
  CModal,
  CModalHeader,
  CModalTitle,
  CModalBody,
  CModalFooter,
  CCollapse,
} from '@coreui/vue'

import AppFooter from '../../../components/store/AppFooter.vue'
import AppHeader from '../../../components/store/AppHeader.vue'
import AppSidebar from '../../../components/store/AppSidebar.vue'

import {
  getFooterSettings,
  updateFooterSettings
} from '@/api/store.js'

const contactPhone = ref('')
const contactPhoneEnable = ref(false)

const address = ref('')
const addressEnable = ref(false)

const email = ref('')
const emailEnable = ref(false)

const servicePhone = ref('')
const servicePhoneEnable = ref(false)

const loading = ref(true)
const saving = ref(false)

const showDescription = ref(false)

const showErrorModal = ref(false)
const errorMessage = ref('')

function showError(message) {
  errorMessage.value = message
  showErrorModal.value = true
}

function closeErrorModal() {
  showErrorModal.value = false
  errorMessage.value = ''
}

// 取得頁尾設定
async function loadFooter() {
  try {
    const data = await getFooterSettings()

    const footer = data.footer

    contactPhone.value =
      footer.contact_phone || ''

    contactPhoneEnable.value =
      footer.contact_phone_enable === true ||
      footer.contact_phone_enable === 1

    address.value =
      footer.address || ''

    addressEnable.value =
      footer.address_enable === true ||
      footer.address_enable === 1

    email.value =
      footer.email || ''

    emailEnable.value =
      footer.email_enable === true ||
      footer.email_enable === 1

    servicePhone.value =
      footer.service_phone || ''

    servicePhoneEnable.value =
      footer.service_phone_enable === true ||
      footer.service_phone_enable === 1

  } catch (e) {
    console.error(
      '取得頁尾設定失敗:',
      e
    )

    if (e.status === 403) {
      showError('您沒有權限存取此頁面：' + (e.message || 'Forbidden'))
    } else if (e.status === 404) {
      showError('找不到頁尾設定：' + (e.message || 'Not Found'))
    } else {
      showError('取得頁尾設定失敗：' + (e.message || '未知錯誤'))
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadFooter()
})

function cancelChanges() {
  window.location.reload()
}

// 儲存頁尾設定
async function saveFooter() {
  if (saving.value) {
    return
  }

  saving.value = true

  try {
    const formData = new FormData()

    formData.append(
      'contact_phone',
      contactPhone.value
    )

    formData.append(
      'contact_phone_enable',
      contactPhoneEnable.value ? 1 : 0
    )

    formData.append(
      'address',
      address.value
    )

    formData.append(
      'address_enable',
      addressEnable.value ? 1 : 0
    )

    formData.append(
      'email',
      email.value
    )

    formData.append(
      'email_enable',
      emailEnable.value ? 1 : 0
    )

    formData.append(
      'service_phone',
      servicePhone.value
    )

    formData.append(
      'service_phone_enable',
      servicePhoneEnable.value ? 1 : 0
    )

    const data =
      await updateFooterSettings(formData)

    const footer = data.footer

    contactPhone.value =
      footer.contact_phone || ''

    contactPhoneEnable.value =
      footer.contact_phone_enable === true ||
      footer.contact_phone_enable === 1

    address.value =
      footer.address || ''

    addressEnable.value =
      footer.address_enable === true ||
      footer.address_enable === 1

    email.value =
      footer.email || ''

    emailEnable.value =
      footer.email_enable === true ||
      footer.email_enable === 1

    servicePhone.value =
      footer.service_phone || ''

    servicePhoneEnable.value =
      footer.service_phone_enable === true ||
      footer.service_phone_enable === 1

    showError('頁尾設定更新成功' || data.message)

  } catch (e) {
    console.error(
      '更新頁尾設定失敗:',
      e
    )

    if (e.status === 400) {
      showError('頁尾資料格式錯誤：' + (e.message || 'Bad Request'))
    } else if (e.status === 403) {
      showError('您沒有權限執行此操作：' + (e.message || 'Forbidden'))
    } else if (e.status === 404) {
      showError('找不到頁尾設定：' + (e.message || 'Not Found'))
    } else {
      showError('更新頁尾設定失敗：' + (e.message || '未知錯誤'))
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div>
    <AppSidebar />

    <div class="wrapper d-flex flex-column min-vh-100">
      <AppHeader />

      <div class="body flex-grow-1">
        <CContainer class="px-4" lg>

          <div v-if="loading">
            載入中...
          </div>

          <div v-else>

            <!-- 標題 -->
            <div class="position-relative mb-4">
              <h2 class="mt-2 mb-3">
                頁尾設定
              </h2>

              <CButton
                color="link"
                class="text-decoration-none position-absolute top-0 end-0 pe-2"
                @click="
                  showDescription =
                    !showDescription
                "
              >
                <CIcon
                  :icon="
                    showDescription
                      ? 'cilChevronTop'
                      : 'cilChevronBottom'
                  "
                  class="me-1"
                />
                說明
              </CButton>

              <CCollapse
                :visible="showDescription"
              >
                <small class="d-block text-body-secondary">
                  管理網站頁尾顯示的聯絡資訊與服務電話。
                </small>
              </CCollapse>
            </div>

            <!-- 聯絡電話 -->
            <CCard class="mb-4">
              <CCardBody>
                <CRow class="align-items-center">

                  <CCol :md="2">
                    <CFormCheck
                      v-model="contactPhoneEnable"
                      label="聯絡電話"
                    />
                  </CCol>

                  <CCol
                    v-if="contactPhoneEnable"
                    :md="5"
                  >
                    <CFormInput
                      v-model="contactPhone"
                      maxlength="30"
                      placeholder="請輸入聯絡電話"
                    />
                  </CCol>

                </CRow>
              </CCardBody>
            </CCard>

            <!-- 地址 -->
            <CCard class="mb-4">
              <CCardBody>
                <CRow class="align-items-center">

                  <CCol :md="2">
                    <CFormCheck
                      v-model="addressEnable"
                      label="地址"
                    />
                  </CCol>

                  <CCol
                    v-if="addressEnable"
                    :md="5"
                  >
                    <CFormInput
                      v-model="address"
                      maxlength="200"
                      placeholder="請輸入地址"
                    />
                  </CCol>

                </CRow>
              </CCardBody>
            </CCard>

            <!-- Email -->
            <CCard class="mb-4">
              <CCardBody>
                <CRow class="align-items-center">

                  <CCol :md="2">
                    <CFormCheck
                      v-model="emailEnable"
                      label="Email"
                    />
                  </CCol>

                  <CCol
                    v-if="emailEnable"
                    :md="5"
                  >
                    <CFormInput
                      v-model="email"
                      type="email"
                      maxlength="200"
                      placeholder="請輸入 Email"
                    />
                  </CCol>

                </CRow>
              </CCardBody>
            </CCard>

            <!-- 客服電話 -->
            <CCard class="mb-4">
              <CCardBody>
                <CRow class="align-items-center">

                  <CCol :md="2">
                    <CFormCheck
                      v-model="servicePhoneEnable"
                      label="客服電話"
                    />
                  </CCol>

                  <CCol
                    v-if="servicePhoneEnable"
                    :md="5"
                  >
                    <CFormInput
                      v-model="servicePhone"
                      maxlength="30"
                      placeholder="請輸入客服電話"
                    />
                  </CCol>

                </CRow>
              </CCardBody>
            </CCard>

            <!-- 操作 -->
            <div
              class="d-flex justify-content-end mb-5"
            >
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
              @click="saveFooter"
            >
              {{
                saving
                  ? '儲存中...'
                  : '儲存設定'
              }}
            </CButton>
            </div>

          </div>
        </CContainer>
      </div>

      <AppFooter />
    </div>

    <!-- 錯誤 / 成功 Modal -->
    <CModal
      :visible="showErrorModal"
      @close="closeErrorModal"
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
          @click="closeErrorModal"
        >
          確定
        </CButton>
      </CModalFooter>
    </CModal>
  </div>
</template>
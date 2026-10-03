```vue
<template>
  <div>
    <Sidebar />

    <div class="wrapper d-flex flex-column min-vh-100">
      <Header :store="home?.store || {}" />

      <div class="body flex-grow-1">
        <CContainer class="px-4" lg>

          <!-- 標題 -->
          <div class="mb-4">
            <h2 class="mb-0">
              會員資料
            </h2>
          </div>

          <div
            v-if="loading"
            class="text-center py-5 text-body-secondary"
          >
            載入中...
          </div>

          <template v-else>

            <!-- 會員基本資料 -->
            <CCard class="mb-4">
              <CCardBody>

                <!-- 姓名 -->
                <CRow class="mb-4">
                  <CCol :md="6">
                    <div class="d-flex align-items-center">
                      <CFormLabel class="mb-0 me-3 text-nowrap">
                        姓名
                      </CFormLabel>

                      <CFormInput
                        v-model="profile.name"
                        placeholder="請輸入姓名"
                      />
                    </div>
                  </CCol>
                </CRow>

                <!-- Email -->
                <CRow class="mb-4">
                  <CCol :md="6">
                    <div class="d-flex align-items-center">
                      <CFormLabel class="mb-0 me-3 text-nowrap">
                        Email
                      </CFormLabel>

                      <CFormInput
                        :value="profile.email"
                        readonly
                      />
                    </div>
                  </CCol>
                </CRow>

                <!-- 電話 -->
                <CRow class="mb-4">
                  <CCol :md="6">
                    <div class="d-flex align-items-center">
                      <CFormLabel class="mb-0 me-3 text-nowrap">
                        聯絡電話
                      </CFormLabel>

                      <CFormInput
                        v-model="profile.phone"
                        placeholder="請輸入聯絡電話"
                      />
                    </div>
                  </CCol>
                </CRow>

                <!-- 地址 -->
                <CRow class="mb-4">
                  <CCol :md="6">
                    <div class="d-flex align-items-center">
                      <CFormLabel class="mb-0 me-3 text-nowrap">
                        預設收件地址
                      </CFormLabel>

                      <CFormInput
                        v-model="profile.address"
                        placeholder="請輸入聯絡地址"
                      />
                    </div>
                  </CCol>
                </CRow>

                <!-- 預設付款方式 -->
                <CRow class="mb-4">
                  <CCol :md="6">
                    <div class="d-flex align-items-center">
                      <CFormLabel class="mb-0 me-3 text-nowrap">
                        預設付款
                      </CFormLabel>

                      <CFormSelect
                        v-model="profile.preferred_payment"
                      >
                        <option value="">
                          未設定
                        </option>

                        <option value="credit_card">
                          信用卡
                        </option>

                        <option value="atm">
                          ATM轉帳
                        </option>

                        <option value="post_office">
                          郵局轉帳
                        </option>

                        <option value="cash_on_delivery">
                          貨到付款
                        </option>

                        <option value="in_store">
                          店內付款
                        </option>
                      </CFormSelect>
                    </div>
                  </CCol>
                </CRow>

                <!-- 預設配送方式 -->
                <CRow>
                  <CCol :md="6">
                    <div class="d-flex align-items-center">
                      <CFormLabel class="mb-0 me-3 text-nowrap">
                        預設配送
                      </CFormLabel>

                      <CFormSelect
                        v-model="profile.preferred_delivery"
                      >
                        <option value="">
                          未設定
                        </option>

                        <option value="home_delivery">
                          宅配
                        </option>

                        <option value="convenience_store">
                          超商取貨
                        </option>

                        <option value="store_pickup">
                          門市自取
                        </option>
                      </CFormSelect>
                    </div>
                  </CCol>
                </CRow>

                <small class="text-body-secondary">
                  <br>
                  <strong>說明：</strong>
                  Email無法於此頁面修改
                </small>

                <!-- 操作 -->
                <div class="d-flex justify-content-end mt-4">

                  <CButton
                    color="secondary"
                    class="me-2"
                    :disabled="savingProfile"
                    @click="cancelProfileChanges"
                  >
                    取消修改
                  </CButton>

                  <CButton
                    color="primary"
                    :disabled="savingProfile"
                    @click="saveProfile"
                  >
                    {{ savingProfile ? '儲存中...' : '儲存資料' }}
                  </CButton>

                </div>

              </CCardBody>
            </CCard>

            <!-- 修改密碼 -->
            <CCard class="mb-4">
              <CCardBody>

                <h4 class="mb-4">
                  修改密碼
                </h4>

                <!-- 目前密碼 -->
                <CRow class="mb-4">
                  <CCol :md="6">
                    <div class="d-flex align-items-center">
                      <CFormLabel class="mb-0 me-3 text-nowrap">
                        目前密碼
                      </CFormLabel>

                      <CFormInput
                        v-model="passwordForm.current_password"
                        type="password"
                        placeholder="請輸入目前密碼"
                      />
                    </div>
                  </CCol>
                </CRow>

                <!-- 新密碼 -->
                <CRow class="mb-4">
                  <CCol :md="6">
                    <div class="d-flex align-items-center">
                      <CFormLabel class="mb-0 me-3 text-nowrap">
                        新密碼
                      </CFormLabel>

                      <CFormInput
                        v-model="passwordForm.new_password"
                        type="password"
                        placeholder="至少 8 碼"
                      />
                    </div>
                  </CCol>
                </CRow>

                <!-- 確認新密碼 -->
                <CRow>
                  <CCol :md="6">
                    <div class="d-flex align-items-center">
                      <CFormLabel class="mb-0 me-3 text-nowrap">
                        確認新密碼
                      </CFormLabel>

                      <CFormInput
                        v-model="passwordForm.confirm_password"
                        type="password"
                        placeholder="請再次輸入新密碼"
                      />
                    </div>
                  </CCol>
                </CRow>

                <!-- 操作 -->
                <div class="d-flex justify-content-end mt-4">

                  <CButton
                    color="secondary"
                    class="me-2"
                    :disabled="savingPassword"
                    @click="clearPasswordForm"
                  >
                    清除
                  </CButton>

                  <CButton
                    color="primary"
                    :disabled="savingPassword"
                    @click="savePassword"
                  >
                    {{ savingPassword ? '修改中...' : '修改密碼' }}
                  </CButton>

                </div>

              </CCardBody>
            </CCard>

          </template>

        </CContainer>
      </div>

      <Footer :footer="home?.footer || {}" />
      <Createdby />
    </div>

    <!-- 錯誤提示視窗 -->
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

<script setup>
import {
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
  CCol,
  CContainer,
  CFormInput,
  CFormLabel,
  CFormSelect,
  CModal,
  CModalBody,
  CModalFooter,
  CModalHeader,
  CModalTitle,
  CRow
} from '@coreui/vue'

import Sidebar from '../../components/customer_homepage/Sidebar.vue'
import Header from '../../components/customer_homepage/Header.vue'
import Footer from '../../components/customer_homepage/Footer.vue'
import Createdby from '../../components/customer_homepage/Createdby.vue'

import {
  getCustomerHome,
  getCustomerProfile,
  updateCustomerProfile,
  changeCustomerPassword
} from '../../api/customer.js'

const route = useRoute()
const router = useRouter()

const storeId = route.params.storeId

const home = ref(null)

const profile = ref({
  name: '',
  email: '',
  phone: '',
  address: '',
  preferred_payment: '',
  preferred_delivery: '',
  created_at: '',
  updated_at: ''
})

const passwordForm = ref({
  current_password: '',
  new_password: '',
  confirm_password: ''
})

const loading = ref(true)
const savingProfile = ref(false)
const savingPassword = ref(false)

const errorVisible = ref(false)
const errorMessage = ref('')

function showError(message) {
  errorMessage.value = message
  errorVisible.value = true
}

async function loadHome() {
  try {
    home.value = await getCustomerHome(storeId)
  } catch (error) {
    console.error(
      '取得首頁資料失敗:',
      error
    )

    if (error.status === 401) {
      router.replace('/401')
      return
    }

    if (error.status === 404) {
      router.replace('/404')
      return
    }

    showError(
      error.message ||
      '取得首頁資料失敗'
    )
  }
}

async function loadProfile() {
  loading.value = true

  try {
    const data = await getCustomerProfile()

    profile.value = data.customer
  } catch (error) {
    console.error(
      '取得會員資料失敗:',
      error
    )

    if (error.status === 401) {
      router.replace('/401')
      return
    }

    if (error.status === 404) {
      router.replace('/404')
      return
    }

    showError(
      error.message ||
      '取得會員資料失敗'
    )
  } finally {
    loading.value = false
  }
}

function validateProfile() {
  const name = profile.value.name || ''
  const phone = profile.value.phone || ''
  const address = profile.value.address || ''

  if (!name.trim()) {
    showError('請輸入姓名')
    return false
  }

  if (name.trim().length > 100) {
    showError('姓名不可超過 100 字')
    return false
  }

  if (phone.trim().length > 30) {
    showError('電話不可超過 30 字')
    return false
  }

  if (address.trim().length > 255) {
    showError('地址不可超過 255 字')
    return false
  }

  return true
}

async function saveProfile() {
  if (savingProfile.value) {
    return
  }

  if (!validateProfile()) {
    return
  }

  savingProfile.value = true

  try {
    const data = await updateCustomerProfile({
      name: profile.value.name.trim(),
      phone: profile.value.phone.trim(),
      address: profile.value.address.trim(),
      preferred_payment: profile.value.preferred_payment,
      preferred_delivery: profile.value.preferred_delivery
    })

    showError('儲存成功' || data.message)

    profile.value = {
      ...profile.value,
      ...data.customer
    }
  } catch (error) {
    console.error(
      '更新會員資料失敗:',
      error
    )

    if (error.status === 401) {
      router.replace('/401')
      return
    }

    if (error.status === 400) {
      showError(
        error.message ||
        '會員資料格式錯誤'
      )
    } else if (error.status === 403) {
      showError('您沒有權限修改會員資料')
    } else if (error.status === 404) {
      showError('找不到會員資料')
    } else if (error.status === 409) {
      showError('Email 與密碼不可修改')
    } else {
      showError('更新會員資料失敗')
    }
  } finally {
    savingProfile.value = false
  }
}

function validatePassword() {
  if (!passwordForm.value.current_password) {
    showError('請輸入目前密碼')
    return false
  }

  if (!passwordForm.value.new_password) {
    showError('請輸入新密碼')
    return false
  }

  if (passwordForm.value.new_password.length < 8) {
    showError('新密碼至少需要 8 碼')
    return false
  }

  if (!passwordForm.value.confirm_password) {
    showError('請再次輸入新密碼')
    return false
  }

  if (
    passwordForm.value.new_password !==
    passwordForm.value.confirm_password
  ) {
    showError('兩次輸入的新密碼不一致')
    return false
  }

  return true
}

async function savePassword() {
  if (savingPassword.value) {
    return
  }

  if (!validatePassword()) {
    return
  }

  savingPassword.value = true

  try {
    const data = await changeCustomerPassword({
      current_password: passwordForm.value.current_password,
      new_password: passwordForm.value.new_password
    })

    showError('密碼修改成功' || data.message)

    passwordForm.value = {
      current_password: '',
      new_password: '',
      confirm_password: ''
    }
  } catch (error) {
    console.error(
      '修改密碼失敗:',
      error
    )

    if (error.status === 401) {
      showError('目前密碼不正確')
    } else if (error.status === 400) {
      showError(
        error.message ||
        '密碼資料格式錯誤'
      )
    } else if (error.status === 403) {
      showError('您沒有權限修改密碼')
    } else if (error.status === 404) {
      showError('找不到會員資料')
    } else if (error.status === 409) {
      showError('新密碼不可與目前密碼相同')
    } else {
      showError('修改密碼失敗')
    }
  } finally {
    savingPassword.value = false
  }
}

function cancelProfileChanges() {
  loadProfile()
}

function clearPasswordForm() {
  passwordForm.value = {
    current_password: '',
    new_password: '',
    confirm_password: ''
  }
}

onMounted(async () => {
  if (!/^[1-9]\d*$/.test(storeId)) {
    router.replace('/404')
    return
  }

  await loadHome()
  await loadProfile()
})
</script>

<style scoped>
:deep(.form-control),
:deep(.form-select) {
  font-size: 14px;
}
</style>
```

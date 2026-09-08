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
  getProfile,
  updateProfile,
  changeStorePassword
} from '@/api/store.js'

const profile = ref({
  store_id: null,
  store_name: '',
  store_url: '',
  owner_name: '',
  email: '',
  phone: '',
  status: ''
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

async function loadProfile() {
  loading.value = true

  try {
    const data = await getProfile()

    profile.value = data.store
  } catch (error) {
    if (error.status === 403) {
      showError('您沒有權限查看商家資料')
    } else if (error.status === 404) {
      showError('找不到商家資料')
    } else {
      showError('取得商家資料失敗')
    }
  } finally {
    loading.value = false
  }
}

function validateProfile() {
  if (!profile.value.store_name.trim()) {
    showError('請輸入商店名稱')
    return false
  }

  if (profile.value.store_name.trim().length > 100) {
    showError('商店名稱不可超過 100 字')
    return false
  }

  if (!profile.value.owner_name.trim()) {
    showError('請輸入負責人姓名')
    return false
  }

  if (profile.value.owner_name.trim().length > 100) {
    showError('負責人姓名不可超過 100 字')
    return false
  }

  if (profile.value.phone.trim().length > 30) {
    showError('電話不可超過 30 字')
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
    const data = await updateProfile({
      store_name: profile.value.store_name.trim(),
      owner_name: profile.value.owner_name.trim(),
      phone: profile.value.phone.trim()
    })

    showError('儲存成功' || data.message)

    profile.value = data.store
  } catch (error) {
    if (error.status === 400) {
      showError('商家資料格式錯誤')
    } else if (error.status === 403) {
      showError('您沒有權限修改商家資料')
    } else if (error.status === 404) {
      showError('找不到商家資料')
    } else if (error.status === 409) {
      showError('Email 不可修改')
    } else {
      showError('更新商家資料失敗')
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
    const data = await changeStorePassword({
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
    if (error.status === 400) {
      showError(error.message || '密碼資料格式錯誤')
    } else if (error.status === 401) {
      showError('目前密碼不正確')
    } else if (error.status === 403) {
      showError('您沒有權限修改密碼')
    } else if (error.status === 404) {
      showError('找不到商家資料')
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

onMounted(loadProfile)
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
              商家資料
            </h2>
          </div>

          <div
            v-if="loading"
            class="text-center py-5 text-body-secondary"
          >
            載入中...
          </div>

          <template v-else>

            <!-- 商家基本資料 -->
            <CCard class="mb-4">
              <CCardBody>

                <h4 class="mb-4">
                  商家基本資料
                </h4>

                <!-- 商家帳號狀態 -->
                <CRow class="mb-4">
                  <CCol :md="6">
                    <div class="profile-field">
                      <CFormLabel class="profile-label">
                        商家帳號狀態
                      </CFormLabel>

                      <CFormInput
                        v-if="profile.status === 'active'"
                        value="啟用"
                        readonly
                      />

                      <CFormInput
                        v-else-if="profile.status === 'inactive'"
                        value="停用"
                        readonly
                      />

                      <CFormInput
                        v-else
                        :value="profile.status"
                        readonly
                      />
                    </div>
                  </CCol>
                </CRow>

                <!-- 商店名稱 -->
                <CRow class="mb-4">
                  <CCol :md="6">
                    <div class="profile-field">
                      <CFormLabel class="profile-label">
                        商店名稱
                      </CFormLabel>

                      <CFormInput
                        v-model="profile.store_name"
                        placeholder="請輸入商店名稱"
                      />
                    </div>
                  </CCol>
                </CRow>

                <!-- 商店網址 -->
                <CRow class="mb-4">
                  <CCol :md="6">
                    <div class="profile-field">
                      <CFormLabel class="profile-label">
                        商店網址
                      </CFormLabel>

                      <CFormInput
                        :value="profile.store_url"
                        readonly
                      />
                    </div>
                  </CCol>
                </CRow>

                <!-- 負責人姓名 -->
                <CRow class="mb-4">
                  <CCol :md="6">
                    <div class="profile-field">
                      <CFormLabel class="profile-label">
                        負責人姓名
                      </CFormLabel>

                      <CFormInput
                        v-model="profile.owner_name"
                        placeholder="請輸入負責人姓名"
                      />
                    </div>
                  </CCol>
                </CRow>

                <!-- Email -->
                <CRow class="mb-4">
                  <CCol :md="6">
                    <div class="profile-field">
                      <CFormLabel class="profile-label">
                        Email
                      </CFormLabel>

                      <CFormInput
                        :value="profile.email"
                        readonly
                      />
                    </div>
                  </CCol>
                </CRow>

                <!-- 聯絡電話 -->
                <CRow>
                  <CCol :md="6">
                    <div class="profile-field">
                      <CFormLabel class="profile-label">
                        聯絡電話
                      </CFormLabel>

                      <CFormInput
                        v-model="profile.phone"
                        placeholder="請輸入聯絡電話"
                      />
                    </div>
                  </CCol>
                </CRow>

                <small class="text-body-secondary">
                  <br>
                  <strong>說明：</strong>
                  可修改商店名稱、負責人姓名與聯絡電話
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
                    <div class="profile-field">
                      <CFormLabel class="profile-label">
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
                    <div class="profile-field">
                      <CFormLabel class="profile-label">
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
                    <div class="profile-field">
                      <CFormLabel class="profile-label">
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
.profile-field {
  display: flex;
  align-items: center;
}

.profile-label {
  width: 90px;
  flex-shrink: 0;
  margin-bottom: 0;
  margin-right: 1rem;
  white-space: nowrap;
}

:deep(.form-control) {
  font-size: 14px;
}
</style>
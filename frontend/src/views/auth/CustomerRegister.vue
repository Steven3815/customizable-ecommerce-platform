<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'

import { customerRegister } from '../../api/auth.js'
import { getStore } from '../../api/store.js'

import { eye } from '@/assets/icons/eye'

const name = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')

const showPassword = ref(false)
const showConfirmPassword = ref(false)

const confirmSuccess = ref(null)
const success = ref('')
const error = ref('')
const store = ref(null)

const route = useRoute()
const router = useRouter()
const storeId = route.params.storeId

onMounted(async () => {
  try {
    const data = await getStore(storeId)
    store.value = data.store
  } catch (e) {
    console.error('取得商店資料失敗:', e)
    router.push('/404')
  }
})

async function register() {
  error.value = ''

  if (password.value !== confirmPassword.value) {
    confirmSuccess.value = false
    return
  }

  confirmSuccess.value = true

  try {
    const data = await customerRegister(
      storeId,
      name.value,
      email.value,
      password.value
    )

    console.log(data)

    success.value = data.message
  } catch (e) {
    error.value = e.message
  }
}
</script>

<template>
  <div
    v-if="store"
    class="bg-body-tertiary min-vh-100 d-flex flex-row align-items-center"
  >
    <CContainer>
      <CRow class="justify-content-center">
        <CCol :md="8" :lg="6" :xl="5">

          <div class="d-flex flex-column gap-4 text-center">

            <!-- Register Card -->
            <CCard class="p-4 mt-4 mb-4">
              <CCardBody class="d-flex flex-column gap-4">

                <h2 class="h3 text-center mb-0">
                  客戶註冊
                </h2>

                <!-- Register Form -->
                <CForm
                  v-if="!success"
                  class="row gy-3 text-start"
                  @submit.prevent="register"
                >

                  <!-- Name -->
                  <CCol :xs="12">
                    <CFormLabel for="name">
                      姓名
                    </CFormLabel>

                    <CFormInput
                      id="name"
                      v-model="name"
                      type="text"
                      placeholder="請輸入姓名"
                      autocomplete="name"
                      required
                    />
                  </CCol>

                  <!-- Email -->
                  <CCol :xs="12">
                    <CFormLabel for="email">
                      Email
                    </CFormLabel>

                    <CFormInput
                      id="email"
                      v-model="email"
                      type="email"
                      placeholder="your@email.com"
                      autocomplete="email"
                      required
                    />
                  </CCol>

                  <!-- Password -->
                  <CCol :xs="12">
                    <CFormLabel for="password">
                      密碼
                    </CFormLabel>

                    <CInputGroup>
                      <CFormInput
                        id="password"
                        v-model="password"
                        :type="showPassword ? 'text' : 'password'"
                        placeholder="請輸入密碼"
                        autocomplete="new-password"
                        required
                      />

                      <CInputGroupText class="password-eye">
                        <CTooltip
                          :content="showPassword ? '隱藏密碼' : '顯示密碼'"
                        >
                          <template #toggler="{ id, on }">
                            <CButton
                              type="button"
                              color="link"
                              class="p-0 link-secondary"
                              :aria-label="
                                showPassword ? '隱藏密碼' : '顯示密碼'
                              "
                              :aria-describedby="id"
                              v-on="on"
                              @click="showPassword = !showPassword"
                            >
                              <CIcon :icon="eye" size="sm" />
                            </CButton>
                          </template>
                        </CTooltip>
                      </CInputGroupText>
                    </CInputGroup>
                  </CCol>

                  <!-- Confirm Password -->
                  <CCol :xs="12">
                    <CFormLabel for="confirmPassword">
                      確認密碼
                    </CFormLabel>

                    <CInputGroup>
                      <CFormInput
                        id="confirmPassword"
                        v-model="confirmPassword"
                        :type="
                          showConfirmPassword ? 'text' : 'password'
                        "
                        placeholder="請再次輸入密碼"
                        autocomplete="new-password"
                        required
                      />

                      <CInputGroupText class="password-eye">
                        <CTooltip
                          :content="
                            showConfirmPassword
                              ? '隱藏密碼'
                              : '顯示密碼'
                          "
                        >
                          <template #toggler="{ id, on }">
                            <CButton
                              type="button"
                              color="link"
                              class="p-0 link-secondary"
                              :aria-label="
                                showConfirmPassword
                                  ? '隱藏密碼'
                                  : '顯示密碼'
                              "
                              :aria-describedby="id"
                              v-on="on"
                              @click="
                                showConfirmPassword =
                                  !showConfirmPassword
                              "
                            >
                              <CIcon :icon="eye" size="sm" />
                            </CButton>
                          </template>
                        </CTooltip>
                      </CInputGroupText>
                    </CInputGroup>

                    <div
                      v-if="confirmSuccess === false"
                      class="text-error small mt-1"
                    >
                      密碼輸入不一致
                    </div>
                  </CCol>

                  <!-- Error -->
                  <CCol
                    v-if="error"
                    :xs="12"
                  >
                    <div class="text-error">
                      {{ error }}
                    </div>
                  </CCol>

                  <!-- Submit -->
                  <CCol :xs="12">
                    <CButton
                      color="primary"
                      type="submit"
                      class="w-100"
                    >
                      註冊
                    </CButton>
                  </CCol>

                </CForm>

                <!-- Success -->
                <div
                  v-else
                  class="text-center"
                >
                  <p class="text-success mb-4">
                    恭喜！{{ success }}
                  </p>

                  <CButton
                    color="primary"
                    class="w-100 mb-2"
                    :href="`/store-${storeId}/login`"
                  >
                    前往登入
                  </CButton>
                </div>

                <!-- Login -->
                <div class="text-body-secondary">
                  已經有帳號？
                  <RouterLink :to="`/store-${storeId}/login`">
                    登入
                  </RouterLink>
                </div>

              </CCardBody>
            </CCard>

          </div>

        </CCol>
      </CRow>
    </CContainer>
  </div>

  <!-- Loading -->
  <div
    v-else
    class="bg-body-tertiary min-vh-100 d-flex flex-row align-items-center justify-content-center"
  >
    <p class="text-body-secondary mb-0">
      載入商店資料中...
    </p>
  </div>
</template>

<style scoped>
.password-eye .btn {
  color: #6c757d !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  padding: 0 !important;
  margin: 0 !important;
}

.password-eye .btn:hover,
.password-eye .btn:focus,
.password-eye .btn:active {
  color: #222 !important;
}

:deep(.form-control) {
  font-size: 14px;
}

.text-error {
  color: rgb(255, 73, 73);
}
</style>
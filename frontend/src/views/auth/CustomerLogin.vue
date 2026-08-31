<template>
  <div
    v-if="store"
    class="bg-body-tertiary min-vh-100 d-flex flex-row align-items-center"
  >
    <CContainer>
      <CRow class="justify-content-center">
        <CCol :md="8" :lg="6" :xl="5">
          <div class="d-flex flex-column gap-4">

            <!-- Login Card -->
            <CCard class="p-4">
              <CCardBody class="d-flex flex-column gap-5">

                <h2 class="h3 text-center mb-0">
                  客戶登入
                </h2>

                <CForm
                  @submit.prevent="login"
                  class="row gy-3"
                >

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
                        placeholder="輸入密碼"
                        autocomplete="current-password"
                        required
                      />

                      <CInputGroupText class="password-eye">
                        <CButton
                          type="button"
                          color="link"
                          class="p-0 link-secondary"
                          @click="showPassword = !showPassword"
                        >
                          <CIcon :icon="eye" size="sm" />
                        </CButton>
                      </CInputGroupText>
                    </CInputGroup>
                  </CCol>

                  <!-- Error -->
                  <CCol
                    v-if="error"
                    :xs="12"
                  >
                    <p class="text-error mb-0">
                      {{ error }}
                    </p>
                  </CCol>

                  <!-- Login -->
                  <CCol :xs="12">
                    <CButton
                      color="primary"
                      type="submit"
                      class="w-100"
                    >
                      登入
                    </CButton>
                  </CCol>

                </CForm>

                <!-- Register -->
                <div class="text-center text-body-secondary">
                  還沒有帳號？
                  <RouterLink :to="`/store-${storeId}/register`">
                    點此註冊
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

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'

import { customerLogin } from '../../api/auth.js'
import { getStore } from '../../api/store.js'

import { eye } from '@/assets/icons/eye'

const email = ref('')
const password = ref('')
const error = ref('')
const store = ref(null)
const showPassword = ref(false)

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

async function login() {
  error.value = ''

  try {
    const data = await customerLogin(
      storeId,
      email.value,
      password.value
    )

    console.log(data)

    // 登入成功
    router.push(`/store-${storeId}`)
  } catch (e) {
    error.value = e.message
  }
}
</script>

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
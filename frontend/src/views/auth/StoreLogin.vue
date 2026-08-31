<template>
  <div class="bg-body-tertiary min-vh-100 d-flex flex-row align-items-center">
    <CContainer>
      <CRow class="justify-content-center">
        <CCol :md="8" :lg="6" :xl="5">
          <div class="d-flex flex-column gap-4">

            <!-- Login Card -->
            <CCard class="p-4">
              <CCardBody class="d-flex flex-column gap-5">

                <h2 class="h3 text-center mb-0">
                  商家登入
                </h2>

                <CForm @submit.prevent="login" class="row gy-3">

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

                      <CInputGroupText>
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
                  <CCol v-if="error" :xs="12">
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
                  還沒有商家帳號？
                  <RouterLink to="/store/register">
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
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, RouterLink } from 'vue-router'

import { storeLogin } from '../../api/auth.js'

import { eye } from '@/assets/icons/eye'

const email = ref('')
const password = ref('')
const error = ref('')
const showPassword = ref(false)

const router = useRouter()

async function login() {
  error.value = ''

  try {
    const data = await storeLogin(
      email.value,
      password.value
    )

    console.log(data)

    const storeId = data.store.store_id

    // 登入成功
    router.push(`/store-${storeId}/admin/dashboard`)
  } catch (e) {
    error.value = e.message
  }
}
</script>

<style scoped>
:deep(.form-control) {
    font-size: 14px;
}
.text-error{
    color: rgb(255, 73, 73);
}
</style>
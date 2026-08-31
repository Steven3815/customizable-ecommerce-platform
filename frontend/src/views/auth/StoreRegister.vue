<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'

import { storeRegister } from '../../api/auth.js'

import { eye } from '@/assets/icons/eye'

const storeName = ref('')
const ownerName = ref('')
const phone = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const mode = ref('')

const showPassword = ref(false)
const showConfirmPassword = ref(false)

const confirmSuccess = ref(null)
const success = ref('')
const error = ref('')
const newStoreId = ref(null)

async function register() {
  error.value = ''

  if (password.value !== confirmPassword.value) {
    confirmSuccess.value = false
    return
  }

  confirmSuccess.value = true

  try {
    const data = await storeRegister(
      storeName.value,
      ownerName.value,
      email.value,
      password.value,
      phone.value,
      mode.value
    )

    console.log(data)

    success.value = data.message
    newStoreId.value = data.store.store_id

  } catch (e) {
    error.value = e.message
  }
}
</script>

<template>
  <div class="bg-body-tertiary min-vh-100 d-flex flex-row align-items-center">
    <CContainer>
      <CRow class="justify-content-center">
        <CCol :md="8" :lg="6" :xl="5">

          <div class="d-flex flex-column gap-4 text-center">

            <!-- Register Card -->
            <CCard class="p-4 mt-4 mb-4">
              <CCardBody class="d-flex flex-column gap-4">

                <h2 class="h3 text-center mb-0">
                  商家註冊
                </h2>

                <!-- Register Form -->
                <CForm
                  v-if="!success"
                  class="row gy-3 text-start"
                  @submit.prevent="register"
                >

                  <!-- Store Name -->
                  <CCol :xs="12">
                    <CFormLabel for="storeName">
                      商家名稱
                    </CFormLabel>

                    <CFormInput
                      id="storeName"
                      v-model="storeName"
                      type="text"
                      placeholder="請輸入商家名稱"
                      required
                    />
                  </CCol>

                  <!-- Owner Name -->
                  <CCol :xs="12">
                    <CFormLabel for="ownerName">
                      負責人姓名
                    </CFormLabel>

                    <CFormInput
                      id="ownerName"
                      v-model="ownerName"
                      type="text"
                      placeholder="請輸入負責人姓名"
                      required
                    />
                  </CCol>

                  <!-- Phone -->
                  <CCol :xs="12">
                    <CFormLabel for="phone">
                      連絡電話
                    </CFormLabel>

                    <CFormInput
                      id="phone"
                      v-model="phone"
                      type="tel"
                      placeholder="請輸入連絡電話"
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
                              :aria-label="showPassword ? '隱藏密碼' : '顯示密碼'"
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
                        :type="showConfirmPassword ? 'text' : 'password'"
                        placeholder="請再次輸入密碼"
                        autocomplete="new-password"
                        required
                      />

                      <CInputGroupText class="password-eye">
                        <CTooltip
                          :content="showConfirmPassword ? '隱藏密碼' : '顯示密碼'"
                        >
                          <template #toggler="{ id, on }">
                            <CButton
                              type="button"
                              color="link"
                              class="p-0 link-secondary"
                              :aria-label="showConfirmPassword ? '隱藏密碼' : '顯示密碼'"
                              :aria-describedby="id"
                              v-on="on"
                              @click="showConfirmPassword = !showConfirmPassword"
                            >
                              <CIcon :icon="eye" size="sm"/>
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

                  <!-- Store Mode -->
                  <CCol :xs="12">
                    <CFormLabel for="mode" class="mt-3">
                      商店模式
                    </CFormLabel>

                    <CFormSelect
                      id="mode"
                      v-model="mode"
                      required
                    >
                      <option
                        value=""
                        disabled
                      >
                        請選擇商店模式
                      </option>

                      <option value="shopping">
                        購物模式
                      </option>

                      <option value="showcase">
                        展示模式
                      </option>
                    </CFormSelect>

                    <div class="text-body-secondary small mt-4">
                      <div>
                        <strong>購物模式：</strong>
                        商店可以正常販售商品，顧客可以瀏覽商品、加入購物車並進行結帳。
                      </div>

                      <div class="mt-1">
                        <strong>展示模式：</strong>
                        商店僅供展示，顧客可以瀏覽商店與商品，但無法登入、註冊、加入購物車或進行購買。
                      </div>

                      <div class="mt-1">
                        <strong>注意：</strong>
                        一旦選擇「購物模式」，之後將無法切換為「展示模式」。
                      </div>
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
                    :href="`/store-${newStoreId}/login`"
                  >
                    前往登入
                  </CButton>
                </div>

            <!-- Login -->
            <div class="text-body-secondary">
              已經有商家帳號？
              <RouterLink to="/store/login">
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
:deep(.form-select) {
    font-size: 14px;
}
.text-error{
    color: rgb(255, 73, 73);
}
</style>
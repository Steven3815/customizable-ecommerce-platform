<template>
  <div>
    <Sidebar />

    <div class="wrapper d-flex flex-column min-vh-100">
      <Header />

      <div class="body flex-grow-1">
        <CContainer class="px-4" lg>

          <!-- 標題 -->
          <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
              <h2 class="mb-1">
                會員中心
              </h2>

              <p class="text-body-secondary mb-0">
                集中管理您在多家商店的訂單、退款與客服
              </p>
            </div>

            <!-- 返回上一間商店 -->
            <CButton
              v-if="lastStoreId"
              color="primary"
              @click="goBackToStore"
            >
              返回商店
            </CButton>
          </div>

          <!-- 功能 -->
          <CRow class="g-4">

            <!-- 訂單管理 -->
            <CCol :xs="12" :md="6">
              <CCard
                class="h-100"
                role="button"
                @click="router.push('/customer/orders')"
              >
                <CCardBody>
                  <CIcon
                    icon="cilList"
                    size="xl"
                    class="mb-3"
                  />

                  <h5 class="mb-2">
                    訂單管理
                  </h5>

                  <p class="text-body-secondary mb-0">
                    查看與管理您的訂單
                  </p>
                </CCardBody>
              </CCard>
            </CCol>

            <!-- 退款 -->
            <CCol :xs="12" :md="6">
              <CCard
                class="h-100"
                role="button"
                @click="router.push('/customer/refunds')"
              >
                <CCardBody>
                  <CIcon
                    icon="cilDollar"
                    size="xl"
                    class="mb-3"
                  />

                  <h5 class="mb-2">
                    退款
                  </h5>

                  <p class="text-body-secondary mb-0">
                    查看您的退款紀錄
                  </p>
                </CCardBody>
              </CCard>
            </CCol>

            <!-- 客服 -->
            <CCol :xs="12" :md="6">
              <CCard
                class="h-100"
                role="button"
                @click="router.push('/customer/services')"
              >
                <CCardBody>
                  <CIcon
                    icon="cilCommentSquare"
                    size="xl"
                    class="mb-3"
                  />

                  <h5 class="mb-2">
                    客服
                  </h5>

                  <p class="text-body-secondary mb-0">
                    查看客服服務紀錄
                  </p>
                </CCardBody>
              </CCard>
            </CCol>

          </CRow>

        </CContainer>
      </div>

      <Createdby />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

import Header from '../../../components/customer_member/Header.vue'
import Sidebar from '../../../components/customer_member/Sidebar.vue'
import Createdby from '../../../components/customer_homepage/Createdby.vue'

const router = useRouter()

const lastStoreId = ref(null)

onMounted(() => {
  lastStoreId.value = localStorage.getItem('lastStoreId')
})

function goBackToStore() {
  if (!lastStoreId.value) {
    return
  }

  router.push(`/store-${lastStoreId.value}`)
}
</script>
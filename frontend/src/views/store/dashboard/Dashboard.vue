<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { CContainer } from '@coreui/vue'
import AppFooter from '../../../components/store/AppFooter.vue'
import AppHeader from '../../../components/store/AppHeader.vue'
import AppSidebar from '../../../components/store/AppSidebar.vue'

import { getStore } from '@/api/store.js'

const route = useRoute()
const router = useRouter()

const store = ref(null)
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
</script>

<template>
  <div>
    <AppSidebar />

    <div class="wrapper d-flex flex-column min-vh-100">
      <AppHeader />

      <div class="body flex-grow-1">
        <CContainer class="px-4" lg>
          <router-view />
        </CContainer>
      </div>

      <AppFooter />
    </div>
  </div>
</template>
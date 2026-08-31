<script setup>
import { CContainer } from '@coreui/vue'
import '@/assets/CoreUI/style.scss'
import '@/assets/CoreUI/simplebar.scss'

import AppFooter from '@/components/store/AppFooter.vue'
import AppHeader from '@/components/store/AppHeader.vue'
import AppSidebar from '@/components/store/AppSidebar.vue'


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

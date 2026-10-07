<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import AppHeaderDropdownAccnt from './HeaderDropdownAccnt.vue'
import { useSidebarStore } from '../../stores/sidebar.js'

const headerClassNames = ref('mb-4 p-0')

const sidebar = useSidebarStore()
const router = useRouter()

onMounted(() => {
  document.addEventListener('scroll', () => {
    if (document.documentElement.scrollTop > 0) {
      headerClassNames.value = 'mb-4 p-0 shadow-sm'
    } else {
      headerClassNames.value = 'mb-4 p-0'
    }
  })
})
</script>

<template>
  <CHeader
    position="sticky"
    :class="headerClassNames"
  >
    <CContainer
      class="border-bottom px-4 position-relative"
      fluid
    >
      <CHeaderToggler
        @click="sidebar.toggleVisible()"
        style="margin-inline-start: -14px"
      >
        <CIcon
          icon="cil-menu"
          size="lg"
        />
      </CHeaderToggler>

      <CHeaderBrand class="member-name-wrapper">
        <div class="member-name">
          會員中心
        </div>
      </CHeaderBrand>

      <CHeaderNav class="ms-auto">
        <CHeaderNav>
          <li class="nav-item py-1">
            <div class="vr h-100 mx-2 text-body text-opacity-75"></div>
          </li>

          <AppHeaderDropdownAccnt />
        </CHeaderNav>
      </CHeaderNav>
    </CContainer>
  </CHeader>
</template>

<style scoped>
.member-name-wrapper {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
}

.member-name {
  font-size: 1.1rem;
  font-weight: 600;
  white-space: nowrap;
}
</style>
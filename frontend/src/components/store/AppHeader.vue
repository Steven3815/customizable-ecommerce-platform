<script setup>
import { onMounted, ref } from 'vue'
import { useColorModes } from '@coreui/vue'

import AppBreadcrumb from './AppBreadcrumb.vue'
import AppHeaderDropdownAccnt from './AppHeaderDropdownAccnt.vue'
import { useSidebarStore } from '../../stores/sidebar.js'

const headerClassNames = ref('mb-4 p-0')
const searchModalVisible = ref(false)
const { colorMode, setColorMode } = useColorModes('coreui-free-vue-admin-template-theme')
const sidebar = useSidebarStore()

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
  <CHeader position="sticky" :class="headerClassNames">
    <CContainer class="border-bottom px-4" fluid>
      <CHeaderToggler @click="sidebar.toggleVisible()" style="margin-inline-start: -14px">
        <CIcon icon="cil-menu" size="lg" />
      </CHeaderToggler>
      <CSearchButton
        class="ms-2"
        @trigger="searchModalVisible = true"
        aria-label="Open search dialog"
        aria-controls="headerSearchModal"
      />
      <CModal
        id="headerSearchModal"
        :visible="searchModalVisible"
        @close="
          () => {
            searchModalVisible = false
          }
        "
        aria-labelledby="headerSearchModalTitle"
      >
        <CModalHeader
          dismiss
          @close="
            () => {
              searchModalVisible = false
            }
          "
        >
          <CModalTitle id="headerSearchModalTitle" class="w-100">
            <CFormInput type="search" placeholder="Search" aria-label="Search" />
          </CModalTitle>
        </CModalHeader>
        <CModalBody>
          <p class="text-body-secondary small mb-2">Recent searches</p>
          <CListGroup flush>
            <CListGroupItem as="a" href="#">CoreUI components overview</CListGroupItem>
            <CListGroupItem as="a" href="#">Modal dialog examples</CListGroupItem>
            <CListGroupItem as="a" href="#">Sidebar navigation customization</CListGroupItem>
          </CListGroup>
        </CModalBody>
      </CModal>
      <CHeaderNav class="ms-auto">
        <CNavItem>
          <CNavLink href="#">
            <CIcon icon="cil-bell" size="lg" />
          </CNavLink>
        </CNavItem>
        <CNavItem>
          <CNavLink href="#">
            <CIcon icon="cil-envelope-open" size="lg" />
          </CNavLink>
        </CNavItem>
      </CHeaderNav>
      <CHeaderNav>
        <li class="nav-item py-1">
          <div class="vr h-100 mx-2 text-body text-opacity-75"></div>
        </li>
        <AppHeaderDropdownAccnt />
      </CHeaderNav>
    </CContainer>
    <!--
    <CContainer class="px-4" fluid>
      <AppBreadcrumb />
    </CContainer>
    -->
  </CHeader>
</template>

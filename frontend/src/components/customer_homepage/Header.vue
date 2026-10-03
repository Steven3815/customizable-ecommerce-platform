<script setup>
import { onMounted, ref, watch } from 'vue'
import { useColorModes } from '@coreui/vue'

import AppHeaderDropdownAccnt from './HeaderDropdownAccnt.vue'
import CartOffcanvas from './CartOffcanvas.vue'
import { useSidebarStore } from '../../stores/sidebar.js'
import { useCartStore } from '../../stores/cart.js'

const headerClassNames = ref('mb-4 p-0')
const cartOffcanvas = ref(null)

const { colorMode, setColorMode } = useColorModes(
  'coreui-free-vue-admin-template-theme'
)

const sidebar = useSidebarStore()
const cartStore = useCartStore()

const props = defineProps({
  store: {
    type: Object,
    default: () => ({
      store_name: '',
      store_id: null
    })
  }
})

const openCart = async () => {
  await cartOffcanvas.value?.open()

  await cartStore.loadCartTotal(
    props.store.store_id
  )
}

watch(
  () => props.store.store_id,
  (storeId) => {
    if (storeId) {
      cartStore.loadCartTotal(storeId)
    }
  },
  {
    immediate: true
  }
)

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

      <!-- Store Name -->
      <CHeaderBrand class="store-name-wrapper">
        <div class="store-name">
          {{ props.store.store_name }}
        </div>
      </CHeaderBrand>

      <CHeaderNav class="ms-auto">
        <CNavItem>
          <CNavLink href="#">
            <CIcon
              icon="cil-bell"
              size="lg"
            />
          </CNavLink>
        </CNavItem>

        <CNavItem>
          <CNavLink href="#">
            <CIcon
              icon="cil-envelope-open"
              size="lg"
            />
          </CNavLink>
        </CNavItem>
      </CHeaderNav>

      <CHeaderNav>
        <li class="nav-item py-1">
          <div class="vr h-100 mx-2 text-body text-opacity-75"></div>
        </li>

        <AppHeaderDropdownAccnt />
      </CHeaderNav>

      <div class="cart-container">

        <!-- 手機版 -->
        <CButton
          class="header-icon d-lg-none"
          @click="openCart"
        >
          <CIcon icon="cil-basket" />
        </CButton>

        <!-- 桌面版 -->
        <CButton
          class="cart-button d-none d-lg-flex"
          @click="openCart"
        >
          <CIcon
            icon="cil-basket"
            size="sm"
          />

          <strong>
            ${{ cartStore.cartTotal.toLocaleString() }}
          </strong>
        </CButton>

      </div>
    </CContainer>

    <CartOffcanvas
      ref="cartOffcanvas"
      :store-id="props.store.store_id"
    />
  </CHeader>
</template>

<style scoped>
.store-name-wrapper {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
}

.store-name {
  font-size: 1.1rem;
  font-weight: 600;
  white-space: nowrap;
}

.cart-container {
  margin-left: 0.5rem;
  width: 120px;
  flex-shrink: 0;
}

.cart-button {
  width: 120px;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  white-space: nowrap;
}

.cart-button strong {
  font-size: 1.1rem;
  min-width: 0;
}

.header-icon {
  width: 40px;
  height: 40px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
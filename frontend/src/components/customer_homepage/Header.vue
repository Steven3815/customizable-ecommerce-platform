<script setup>
import { onMounted, ref, watch } from 'vue'
import { useColorModes } from '@coreui/vue'
import { useRouter } from 'vue-router'

import AppHeaderDropdownAccnt from './HeaderDropdownAccnt.vue'
import CartOffcanvas from './CartOffcanvas.vue'
import { checkCustomerLogin } from '../../api/auth.js'
import { useSidebarStore } from '../../stores/sidebar.js'
import { useCartStore } from '../../stores/cart.js'

const headerClassNames = ref('mb-4 p-0')
const cartOffcanvas = ref(null)
const isLoggedIn = ref(false)

const { colorMode, setColorMode } = useColorModes(
  'coreui-free-vue-admin-template-theme'
)

const sidebar = useSidebarStore()
const cartStore = useCartStore()
const router = useRouter()

const props = defineProps({
  store: {
    type: Object,
    default: () => ({
      store_name: '',
      store_id: null
    })
  }
})

const checkLogin = async () => {
  try {
    const result = await checkCustomerLogin()

    isLoggedIn.value = result

    if (isLoggedIn.value && props.store.store_id) {
      await cartStore.loadCartTotal(
        props.store.store_id
      )
    }
  } catch (error) {
    console.error('Header login error:', error)
    isLoggedIn.value = false
  }
}

const goLogin = () => {
  router.push(
    `/store-${props.store.store_id}/login`
  )
}

const openCart = async () => {
  await cartOffcanvas.value?.open()

  await cartStore.loadCartTotal(
    props.store.store_id
  )
}

watch(
  () => props.store.store_id,
  async (storeId) => {
    if (storeId && isLoggedIn.value) {
      await cartStore.loadCartTotal(storeId)
    }
  },
  {
    immediate: true
  }
)

onMounted(() => {
  checkLogin()

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

      <CHeaderBrand class="store-name-wrapper">
        <div class="store-name">
          {{ props.store.store_name }}
        </div>
      </CHeaderBrand>

      <template v-if="isLoggedIn">
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
          <CButton
            class="header-icon d-lg-none"
            @click="openCart"
          >
            <CIcon icon="cil-basket" />
          </CButton>

          <CButton
            class="cart-button d-none d-lg-flex"
            @click="openCart"
          >
            <CIcon
              icon="cil-basket"
              size="sm"
            />

            <strong>
              {{ cartStore.cartTotal === null ? '價格暫不可用' : `$${cartStore.cartTotal.toLocaleString()}` }}
            </strong>
          </CButton>
        </div>
      </template>

      <CHeaderNav
        v-else
        class="ms-auto me-4"
      >
        <CButton
          color="primary"
          @click="goLogin"
        >
          登入
        </CButton>
      </CHeaderNav>
    </CContainer>

    <CartOffcanvas
      v-if="isLoggedIn"
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

```vue
<template>
  <div>
    <!-- Header -->
    <CHeader>
      <CContainer fluid>

        <!-- Logo -->
        <CHeaderBrand>
          <a v-if="storeLogo" href="#">
            <img
              :src="storeLogo"
              alt="logo"
              class="img-fluid"
            >
          </a>

          <div
            v-else
            class="logo-placeholder"
          ></div>
        </CHeaderBrand>

        <!-- Search -->
        <div class="search-container d-none d-lg-block">
          <CInputGroup>

            <!-- Category -->
            <CFormSelect
              v-model="selectedCategory"
              class="category-select"
            >
              <option value="">
                All Categories
              </option>

              <option value="groceries">
                Groceries
              </option>

              <option value="drinks">
                Drinks
              </option>

              <option value="chocolates">
                Chocolates
              </option>
            </CFormSelect>

            <!-- Keyword -->
            <CFormInput
              v-model="searchKeyword"
              type="search"
              placeholder="Search for more than 20,000 products"
            />

            <!-- Search Button -->
            <CButton
              color="light"
              @click="search"
            >
              <CIcon
                icon="cil-magnifying-glass"
              />
            </CButton>

          </CInputGroup>
        </div>

        <!-- Right Actions -->
        <div class="header-actions">

          <!-- Account -->
          <CButton
            color="light"
            shape="rounded-circle"
            class="header-icon"
          >
            <CIcon
              icon="cil-user"
            />
          </CButton>

          <!-- Mobile Cart -->
          <CButton
            color="light"
            shape="rounded-circle"
            class="header-icon d-lg-none"
            @click="openCart"
          >
            <CIcon
              icon="cil-basket"
            />
          </CButton>

          <!-- Mobile Search -->
          <CButton
            color="light"
            shape="rounded-circle"
            class="header-icon d-lg-none"
            @click="openSearch"
          >
            <CIcon
              icon="cil-magnifying-glass"
            />
          </CButton>

          <!-- Desktop Cart -->
          <div class="cart-container d-none d-lg-block">

            <CButton
              color="light"
              variant="ghost"
              class="cart-button"
              @click="openCart"
            >

              <span>
                Your Cart
              </span>

              <strong>
                $1290.00
              </strong>

            </CButton>

          </div>

        </div>

      </CContainer>
    </CHeader>

    <!-- Cart Offcanvas -->
    <CartOffcanvas
      ref="cartOffcanvas"
    />

    <!-- Search Offcanvas -->
    <SearchOffcanvas
      ref="searchOffcanvas"
    />

  </div>
</template>


<script setup>
import { ref } from 'vue'

import CartOffcanvas from './CartOffcanvas.vue'
import SearchOffcanvas from './SearchOffcanvas.vue'


/* -------------------------
   Store Logo
------------------------- */

const storeLogo = null


/* -------------------------
   Search
------------------------- */

const searchKeyword = ref('')
const selectedCategory = ref('')


const search = () => {
  console.log('Search:', {
    keyword: searchKeyword.value,
    category: selectedCategory.value,
  })
}


/* -------------------------
   Offcanvas
------------------------- */

const cartOffcanvas = ref(null)
const searchOffcanvas = ref(null)


const openCart = () => {
  cartOffcanvas.value?.open()
}


const openSearch = () => {
  searchOffcanvas.value?.open()
}
</script>


<style scoped>

/* Logo */

.logo-placeholder {
  width: 150px;
  height: 50px;
}


/* Search */

.search-container {
  width: 45%;
}

.category-select {
  max-width: 180px;
}


/* Header Actions */

.header-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-left: auto;
}


/* Header Icons */

.header-icon {
  width: 40px;
  height: 40px;
  padding: 0;

  display: flex;
  align-items: center;
  justify-content: center;
}


/* Desktop Cart */

.cart-container {
  margin-left: 0.5rem;
}

.cart-button {
  display: flex;
  flex-direction: column;
  align-items: flex-end;

  gap: 0.25rem;

  line-height: 1.2;
}

.cart-button strong {
  font-size: 1.1rem;
}

</style>
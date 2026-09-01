<template>
  <CHeader>
    <CContainer fluid>

      <!-- Mobile Menu Button -->
      <CButton
        class="d-lg-none p-0 border-0"
        color="light"
        @click="visible = true"
        aria-label="Open menu"
      >
        <CIcon
          icon="cilMenu"
          size="lg"
        />
      </CButton>


      <!-- Desktop Navigation -->
      <CNavbar
        expand="lg"
        class="d-none d-lg-flex w-100"
      >
        <CNavbarNav class="align-items-center gap-3">

          <!-- Categories -->
          <CNavItem>
            <CFormSelect
              v-model="selectedCategory"
              class="category-select"
            >
              <option value="">
                Shop by Departments
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
          </CNavItem>

          <CNavItem>
            <CNavLink href="#women">
              Women
            </CNavLink>
          </CNavItem>

          <CNavItem>
            <CNavLink href="#men">
              Men
            </CNavLink>
          </CNavItem>

          <CNavItem>
            <CNavLink href="#kids">
              Kids
            </CNavLink>
          </CNavItem>

          <CNavItem>
            <CNavLink href="#accessories">
              Accessories
            </CNavLink>
          </CNavItem>

          <CNavItem>
            <CNavLink href="#brand">
              Brand
            </CNavLink>
          </CNavItem>

          <CNavItem>
            <CNavLink href="#sale">
              Sale
            </CNavLink>
          </CNavItem>

          <CNavItem>
            <CNavLink href="#blog">
              Blog
            </CNavLink>
          </CNavItem>

        </CNavbarNav>
      </CNavbar>


      <!-- Mobile Offcanvas -->
      <COffcanvas
        placement="start"
        :visible="visible"
        @visible-change="(value) => visible = value"
      >

        <COffcanvasHeader class="border-bottom">

          <COffcanvasTitle>
            Menu
          </COffcanvasTitle>

          <CCloseButton
            @click="visible = false"
          />

        </COffcanvasHeader>


        <COffcanvasBody>

          <!-- Categories -->
          <CFormSelect
            v-model="selectedCategory"
            class="mb-4"
          >
            <option value="">
              Shop by Departments
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


          <!-- Mobile Navigation -->
          <CNav class="flex-column">

            <CNavItem>
              <CNavLink
                href="#women"
                @click="visible = false"
              >
                Women
              </CNavLink>
            </CNavItem>

            <CNavItem>
              <CNavLink
                href="#men"
                @click="visible = false"
              >
                Men
              </CNavLink>
            </CNavItem>

            <CNavItem>
              <CNavLink
                href="#kids"
                @click="visible = false"
              >
                Kids
              </CNavLink>
            </CNavItem>

            <CNavItem>
              <CNavLink
                href="#accessories"
                @click="visible = false"
              >
                Accessories
              </CNavLink>
            </CNavItem>

            <CNavItem>
              <CNavLink
                href="#brand"
                @click="visible = false"
              >
                Brand
              </CNavLink>
            </CNavItem>

            <CNavItem>
              <CNavLink
                href="#sale"
                @click="visible = false"
              >
                Sale
              </CNavLink>
            </CNavItem>

            <CNavItem>
              <CNavLink
                href="#blog"
                @click="visible = false"
              >
                Blog
              </CNavLink>
            </CNavItem>

          </CNav>

        </COffcanvasBody>

      </COffcanvas>

    </CContainer>
  </CHeader>
</template>


<script setup>
import { ref } from 'vue'

const visible = ref(false)
const selectedCategory = ref('')
</script>


<style scoped>
.category-select {
  width: 200px;
}
</style>
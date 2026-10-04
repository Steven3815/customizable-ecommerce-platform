<template>
  <div>
    <Sidebar />

    <div class="wrapper d-flex flex-column min-vh-100">
      <Header
        :store="store"
      />

      <div class="body flex-grow-1">
        <section class="py-5">
          <CContainer fluid>

            <!-- Search Header -->
            <CRow>
              <CCol :md="12">
                <div class="category-header">
                  <div class="category-title-wrapper">
                    <h1>
                      「{{ keyword }}」相關商品
                    </h1>
                  </div>
                </div>
              </CCol>
            </CRow>

            <!-- Products -->
            <CRow>
              <CCol :md="12">
                <div
                  v-if="products.length > 0"
                  class="products-carousel"
                  :class="`display-limit-${displayLimit}`"
                >
                  <div class="products-wrapper">

                    <div
                      v-for="product in products"
                      :key="product.product_id"
                      class="product-item"
                    >
                      <ProductCard
                        :product-id="product.product_id"
                        :store-id="route.params.storeId"
                        :name="product.product_name"
                        :image="product.main_image"
                        :price="product.display_price"
                        @view-product="openProduct"
                      />
                    </div>

                  </div>
                </div>
              </CCol>
            </CRow>

            <!-- Loading -->
            <div
              v-if="loading"
              class="text-center py-5"
            >
              載入中...
            </div>

            <!-- No Products -->
            <div
              v-else-if="products.length === 0"
              class="text-center py-5"
            >
              找不到符合的商品
            </div>

          </CContainer>
        </section>
      </div>

      <Footer
        :footer="footer"
      />

      <Createdby />
    </div>

    <!-- Product Detail Modal -->
    <ProductCardDetailModal
      :visible="showProductModal"
      :product-id="selectedProductId"
      :store-id="route.params.storeId"
      @close="closeProductModal"
    />

  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import {
  CContainer,
  CRow,
  CCol
} from '@coreui/vue'

import { getCustomerProducts } from '../../api/customer.js'

import ProductCard from '../../components/customer_homepage/ProductCard.vue'
import ProductCardDetailModal from '../../components/customer_homepage/ProductCardDetailModal.vue'
import Header from '../../components/customer_homepage/Header.vue'
import Sidebar from '../../components/customer_homepage/Sidebar.vue'
import Footer from '../../components/customer_homepage/Footer.vue'
import Createdby from '../../components/customer_homepage/Createdby.vue'

const route = useRoute()
const router = useRouter()

const products = ref([])
const keyword = ref('')

const store = ref({})
const footer = ref({})

const displayLimit = ref(4)

const loading = ref(false)

const showProductModal = ref(false)
const selectedProductId = ref(null)

const openProduct = (product) => {
  selectedProductId.value = product.productId
  showProductModal.value = true
}

const closeProductModal = () => {
  showProductModal.value = false
}

onMounted(async () => {
  const storeId = route.params.storeId

  keyword.value = route.query.keyword || ''

  if (!keyword.value.trim()) {
    router.push('/404')
    return
  }

  loading.value = true

  try {
    const data = await getCustomerProducts(
      storeId,
      null,
      'asc',
      keyword.value
    )

    console.log(
      'Customer Search Products:',
      data
    )

    // 只顯示有設定顯示價格的商品
    products.value = (data.products || []).filter(
      product => product.display_price !== null
    )

    displayLimit.value =
      Number(data.display_limit) || 4

    store.value = {
      store_id: data.store_id,
      store_name: data.store_name
    }

    footer.value = data.footer || {}

  } catch (error) {
    console.error(
      '搜尋商品失敗:',
      error
    )

    if (
      error.status === 403 ||
      error.status === 404
    ) {
      router.push('/404')
    }

  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.category-title-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.category-title-wrapper h1 {
  margin: 0;

  font-size: 2rem;
  font-weight: bold;
  letter-spacing: 0.08em;

  color: #3b4f66;

  cursor: default;
  position: relative;
  display: inline-block;
}

.category-title-wrapper h1::before,
.category-title-wrapper h1::after {
  content: '';
  display: inline-block;
  width: 8rem;
  height: 1px;
  background-color: var(--cui-border-color);
  vertical-align: middle;
  margin: 0 15px;
}

.category-header {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;

  margin-bottom: 3rem;
}

/* Products */

.products-carousel {
  width: 100%;
}

.products-wrapper {
  display: grid;
  gap: 20px;
}

.products-carousel.display-limit-4 .products-wrapper {
  grid-template-columns:
    repeat(4, minmax(0, 1fr));
}

.products-carousel.display-limit-5 .products-wrapper {
  grid-template-columns:
    repeat(5, minmax(0, 1fr));
}

.products-carousel.display-limit-6 .products-wrapper {
  grid-template-columns:
    repeat(6, minmax(0, 1fr));
}

.product-item {
  min-width: 0;
}

/* Tablet */

@media (max-width: 991px) {
  .products-wrapper {
    grid-template-columns:
      repeat(3, minmax(0, 1fr)) !important;
  }
}

@media (max-width: 767px) {
  .products-wrapper {
    grid-template-columns:
      repeat(2, minmax(0, 1fr)) !important;
  }
}

@media (max-width: 575px) {
  .products-wrapper {
    grid-template-columns:
      1fr !important;
  }
}
</style>
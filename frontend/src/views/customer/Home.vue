<template>
  <div>

    <Sidebar />

    <div class="wrapper d-flex flex-column min-vh-100">

      <Header
        :store="home?.store || {}"
      />

      <div class="body flex-grow-1">

        <Banner
          v-if="home?.website_setting?.banner_section_enable"
          :banners="home?.banners || []"
        />

        <Slider
          v-if="home?.website_setting?.intro_section_enable"
          :sliders="home?.sliders || []"
        />

        <section
          v-for="category in categories.filter(category => category.products.some(product => product.display_price !== null))"
          :key="category.category_id"
          class="py-5"
        >
          <CContainer fluid>

            <!-- Category Header -->
            <CRow>
              <CCol :md="12">

                <div class="category-header">

                  <div class="category-title-wrapper">

                    <h1>
                      {{ category.category_name }}
                    </h1>

                  </div>

                  <div class="category-action">
                    <CButton
                      color="link"
                      class="text-decoration-none"
                    >
                      查看類別商品 →
                    </CButton>
                  </div>

                </div>

              </CCol>
            </CRow>

            <!-- Products -->
            <CRow>
              <CCol :md="12">

                <div class="products-carousel swiper">

                  <div class="swiper-wrapper">

                    <div
                      v-for="product in category.products.filter(product => product.display_price !== null)"
                      :key="product.product_id"
                      class="swiper-slide"
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

          </CContainer>
        </section>

      </div>

      <Footer
        :footer="home?.footer || {}"
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
import { onMounted, ref, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import {
  CContainer,
  CRow,
  CCol,
  CButton
} from '@coreui/vue'

import Swiper from 'swiper'

import { getCustomerHome } from '../../api/customer.js'

import ProductCard from '../../components/customer_homepage/ProductCard.vue'
import ProductCardDetailModal from '../../components/customer_homepage/ProductCardDetailModal.vue'
import Header from '../../components/customer_homepage/Header.vue'
import Banner from '../../components/customer_homepage/Banner.vue'
import Slider from '../../components/customer_homepage/Slider.vue'
import Sidebar from '../../components/customer_homepage/Sidebar.vue'
import Footer from '../../components/customer_homepage/Footer.vue'
import Createdby from '../../components/customer_homepage/Createdby.vue'

const route = useRoute()
const router = useRouter()

const home = ref(null)
const categories = ref([])

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

  try {
    home.value = await getCustomerHome(storeId)

    categories.value = home.value.categories

    console.log('Customer Home:', home.value)

    await nextTick()

    const swiperElements = document.querySelectorAll(
      '.products-carousel'
    )

    swiperElements.forEach((element) => {
      new Swiper(element, {
        slidesPerView: 'auto',
        spaceBetween: 20,

        breakpoints: {
          576: {
            slidesPerView: 'auto'
          },

          768: {
            slidesPerView: 'auto'
          },

          992: {
            slidesPerView: 'auto'
          },

          1200: {
            slidesPerView: 'auto'
          }
        }
      })
    })

  } catch (error) {
    console.error('取得首頁資料失敗:', error)

    router.push('/404')
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

.category-action {
  position: absolute;
  right: 0;
}

.products-carousel {
  width: 100%;
  overflow: hidden;
}

.products-carousel :deep(.swiper-slide) {
  width: calc((100% - 60px) / 4);
}

@media (max-width: 991px) {
  .products-carousel :deep(.swiper-slide) {
    width: calc((100% - 40px) / 3);
  }
}

@media (max-width: 767px) {
  .products-carousel :deep(.swiper-slide) {
    width: calc((100% - 20px) / 2);
  }
}

@media (max-width: 575px) {
  .products-carousel :deep(.swiper-slide) {
    width: 100%;
  }
}

</style>
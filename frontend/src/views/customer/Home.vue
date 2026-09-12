<template>
  <div>
    <Sidebar />

    <div class="wrapper d-flex flex-column min-vh-100">

      <Header />

      <div class="body flex-grow-1">
        <Banner />
        <Slider />

        <section class="py-5">
          <CContainer fluid>
            <CRow>
              <CCol :md="12">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                  <h2 class="mb-0">
                    A類商品
                  </h2>

                  <div class="d-flex align-items-center gap-2">
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

            <CRow>
              <CCol :md="12">
                <div
                  ref="swiperElement"
                  class="products-carousel swiper"
                >
                  <div class="swiper-wrapper">
                    <ProductCard
                      v-for="product in products"
                      :key="product.productId"
                      :product-id="product.productId"
                      :name="product.name"
                      :image="product.image"
                      :price="product.price"
                    />
                  </div>
                </div>
              </CCol>
            </CRow>
          </CContainer>
        </section>
      </div>

      <Footer />
      <Createdby />
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import {
  CContainer,
  CRow,
  CCol,
  CButton
} from '@coreui/vue'

import Swiper from 'swiper'

import { getStore } from '../../api/store.js'

import ProductCard from '../../components/customer_homepage/ProductCard.vue'
import Header from '../../components/customer_homepage/Header.vue'
import Banner from '../../components/customer_homepage/Banner.vue'
import Slider from '../../components/customer_homepage/Slider.vue'
import Sidebar from '../../components/customer_homepage/Sidebar.vue'
import Footer from '../../components/customer_homepage/Footer.vue'
import Createdby from '../../components/customer_homepage/Createdby.vue'

const swiperElement = ref(null)
const swiper = ref(null)

const route = useRoute()
const router = useRouter()

const store = ref(null)

const products = [
  {
    productId: 1,
    name: '商品 1',
    image: '',
    price: 100
  },
  {
    productId: 2,
    name: '商品 2',
    image: '',
    price: 150
  },
  {
    productId: 3,
    name: '商品 3',
    image: '',
    price: 200
  },
  {
    productId: 4,
    name: '商品 4',
    image: '',
    price: 250
  }
]

onMounted(async() => {
  const storeId = route.params.storeId

  try {
    store.value = await getStore(storeId)

    console.log('Store:', store.value)
  } catch (error) {
    console.error('取得商店資料失敗:', error)

    router.push('/404')
  }

  swiper.value = new Swiper(swiperElement.value, {
    slidesPerView: 1,
    spaceBetween: 20,
    breakpoints: {
      576: {
        slidesPerView: 2
      },
      768: {
        slidesPerView: 3
      },
      992: {
        slidesPerView: 4
      },
      1200: {
        slidesPerView: 4
      }
    }
  })
})
</script>

<style scoped>
.products-carousel {
  width: 100%;
  overflow: hidden;
}
</style>
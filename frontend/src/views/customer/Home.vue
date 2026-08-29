<template>
  <div>
    <CartOffcanvas />
    <IconSymbols />
    <Header />
    <SearchOffcanvas />
    <Navbar />
    <Banner />
    <Slider />
    <section class="py-5 overflow-hidden">
      <div class="container-fluid">

        <div class="row">
          <div class="col-md-12">
            <div class="section-header d-flex flex-wrap justify-content-between my-5">

              <h2 class="section-title">
                Best Selling Products
              </h2>

              <div class="d-flex align-items-center">

                <a href="#" class="btn-link text-decoration-none">
                  View All Categories →
                </a>

                <div class="swiper-buttons">

                  <button
                    type="button"
                    class="swiper-prev products-carousel-prev btn btn-primary"
                    @click="swiper?.slidePrev()"
                  >
                    ❮
                  </button>

                  <button
                    type="button"
                    class="swiper-next products-carousel-next btn btn-primary"
                    @click="swiper?.slideNext()"
                  >
                    ❯
                  </button>

                </div>
              </div>
            </div>
          </div>
        </div>


        <div class="row">
          <div class="col-md-12">
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
          </div>
        </div>

      </div>
    </section>
    <Footer />
  </div>
</template>


<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import Swiper from 'swiper'

import { getStore } from '../../api/store.js'

import CartOffcanvas from '../../components/homepage/CartOffcanvas.vue'
import ProductCard from '../../components/homepage/ProductCard.vue'
import IconSymbols from '../../components/homepage/IconSymbols.vue'
import Header from '../../components/homepage/Header.vue'
import Banner from '../../components/homepage/Banner.vue'
import Slider from '../../components/homepage/Slider.vue'
import SearchOffcanvas from '../../components/homepage/SearchOffcanvas.vue'
import Navbar from '../../components/homepage/StoreNavbar.vue'
import Footer from '../../components/homepage/Footer.vue'

const swiperElement = ref(null)
const swiper = ref(null)

const route = useRoute()

const store = ref(null)

// 模擬 Store 尚未設定商品圖片
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
  },
  {
    productId: 5,
    name: '商品 5',
    image: '',
    price: 300
  },
  {
    productId: 6,
    name: '商品 6',
    image: '',
    price: 350
  }
]


onMounted(async() => {
  
  const storeId = route.params.storeId

  try {
    store.value = await getStore(storeId)

    console.log('Store:', store.value)

  } catch (error) {
    console.error('取得商店資料失敗:', error)
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
<template>
  <section class="py-3">
    <div class="container-fluid">

      <div
        ref="swiperElement"
        class="swiper slider-swiper"
      >

        <div class="swiper-wrapper">

          <div
            v-for="slider in sliders"
            :key="slider.id"
            class="swiper-slide"
          >

            <div v-if="slider.image" class="slider-image">
              <img
                :src="slider.image"
                :alt="slider.title"
              >
            </div>

            <div v-else class="slider-placeholder">
              No Slider Image
            </div>

          </div>

        </div>

        <div class="swiper-pagination"></div>

      </div>

    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import Swiper from 'swiper'
import { Pagination, Autoplay } from 'swiper/modules'

const swiperElement = ref(null)

const sliders = [
  {
    id: 1,
    title: 'Slider 1',
    image: ''
  },
  {
    id: 2,
    title: 'Slider 2',
    image: ''
  },
  {
    id: 3,
    title: 'Slider 3',
    image: ''
  }
]

onMounted(() => {
  new Swiper(swiperElement.value, {
    modules: [Pagination, Autoplay],

    slidesPerView: 1,

    spaceBetween: 0,

    loop: true,

    autoplay: {
      delay: 3000
    },

    pagination: {
      el: '.swiper-pagination',
      clickable: true
    }
  })
})
</script>

<style scoped>
.slider-image {
  width: 100%;
  aspect-ratio: 16 / 5;
}

.slider-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.slider-placeholder {
  width: 100%;
  aspect-ratio: 16 / 5;

  background-color: #f1f1f1;

  display: flex;
  align-items: center;
  justify-content: center;

  color: #999;
}
</style>
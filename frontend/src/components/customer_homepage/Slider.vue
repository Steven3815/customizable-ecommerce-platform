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
            :key="slider.image_id"
            class="swiper-slide"
          >

            <div
              v-if="slider.image_url"
            >
              <div class="slider-title mt-4">
                {{ slider.title }}
              </div>

              <div class="slider-image">
                <img
                  :src="slider.image_url"
                  :alt="slider.title"
                >
              </div>
            </div>

            <div
              v-else
              class="slider-placeholder"
            >
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

defineProps({
  sliders: {
    type: Array,
    default: () => []
  }
})

onMounted(() => {
  if (!swiperElement.value) {
    return
  }

  new Swiper(swiperElement.value, {
    modules: [Pagination, Autoplay],

    slidesPerView: 1,

    spaceBetween: 0,

    loop: true,

    autoplay: {
      delay: 3000
    },

    pagination: {
      el: swiperElement.value.querySelector('.swiper-pagination'),
      clickable: true
    }
  })
})
</script>

<style scoped>
.slider-title {
  text-align: center;
  margin-bottom: 1rem;
  font-size: 1.5rem;
  color: var(--cui-secondary-color);
  letter-spacing: 0.03em;
  cursor: default;
}

.slider-image {
  width: 100%;
  aspect-ratio: 16 / 5;
  overflow: hidden;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}

.slider-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
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
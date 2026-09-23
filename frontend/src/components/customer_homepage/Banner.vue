<template>
  <section class="py-3">
    <div class="container-fluid">

      <div
        v-if="banners.length > 0"
      >
        <div class="banner-description">
          {{ banners[0].description }}
        </div>

        <div class="banner-image">
          <img
            :src="banners[0].image_url"
            :alt="banners[0].title"
          >
        </div>
      </div>

      <div
        v-else
        class="banner-placeholder"
      >
        No Banner Image
      </div>

    </div>
  </section>
</template>

<script setup>
defineProps({
  banners: {
    type: Array,
    default: () => []
  }
})
</script>

<style scoped>
.banner-description {
  text-align: center;
  margin-bottom: 1rem;
  font-size: 1.5rem;
  color: var(--cui-secondary-color);
  letter-spacing: 0.03em;
  cursor: default;
}

.banner-image {
  width: 100%;
  aspect-ratio: 16 / 5;
  overflow: hidden;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}

.banner-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.banner-placeholder {
  width: 100%;
  aspect-ratio: 16 / 5;

  background-color: #f1f1f1;

  display: flex;
  align-items: center;
  justify-content: center;

  color: #999;
}
</style>
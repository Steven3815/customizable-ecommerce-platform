<template>
  <section class="py-3">
    <div class="container-fluid">

      <div v-if="banner.image" class="banner-image">
        <img
          :src="banner.image"
          :alt="banner.title"
        >
      </div>

      <div v-else class="banner-placeholder">
        No Banner Image
      </div>

    </div>
  </section>
</template>

<script setup>
const banner = {
  title: 'Banner',
  image: ''
}
</script>

<style scoped>
.banner-image {
  width: 100%;
  aspect-ratio: 16 / 5;
}

.banner-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
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
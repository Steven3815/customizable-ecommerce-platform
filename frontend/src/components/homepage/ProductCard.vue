<template>
  <div class="product-item swiper-slide">

    <!-- Wishlist -->
    <a href="#" class="btn-wishlist">
      <svg width="24" height="24" viewBox="0 0 24 24">
        <use xlink:href="#heart"></use>
      </svg>
    </a>

    <!-- Product Image -->
  <figure class="product-image">
    <a href="#" title="Product Title">
      <img
        v-if="image"
        :src="image"
        :alt="name"
        class="tab-image"
      >

      <div v-else class="image-placeholder">
        No Image
      </div>
    </a>
  </figure>

    <!-- Product Name -->
    <h3>{{ name }}</h3>

    <!-- Product Price -->
    <span class="price">
      ${{ Number(price).toFixed(2) }}
    </span>

    <!-- Quantity + Add to Cart -->
    <div class="d-flex align-items-center justify-content-between">

      <div class="input-group product-qty">

        <!-- Minus -->
        <span class="input-group-btn">
          <button
            type="button"
            class="quantity-left-minus btn btn-danger btn-number"
            data-type="minus"
          >
            <svg width="16" height="16" viewBox="0 0 24 24">
              <use xlink:href="#minus"></use>
            </svg>
          </button>
        </span>

        <!-- Quantity -->
        <input
          type="text"
          :id="`quantity-${productId}`"
          name="quantity"
          class="form-control input-number"
          value="1"
        >

        <!-- Plus -->
        <span class="input-group-btn">
          <button
            type="button"
            class="quantity-right-plus btn btn-success btn-number"
            data-type="plus"
          >
            <svg width="16" height="16" viewBox="0 0 24 24">
              <use xlink:href="#plus"></use>
            </svg>
          </button>
        </span>

      </div>

      <!-- Add to Cart -->
      <a href="#" class="nav-link">
        Add to Cart
        <iconify-icon icon="uil:shopping-cart"></iconify-icon>
      </a>

    </div>

  </div>
</template>

<script setup>

defineProps({
  productId: {
    type: Number,
    default: null
  },

  name: {
    type: String,
    required: true
  },

  image: {
    type: String,
    required: true
  },

  price: {
    type: [Number, String],
    required: true
  }

})

</script>

<style>
  .product-image {
  width: 100%;
  aspect-ratio: 1 / 1;
  margin: 0;
  }

  .product-image a {
    display: block;
    width: 100%;
    height: 100%;
  }

  .product-image .tab-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
  }

  .image-placeholder {
    width: 100%;
    height: 100%;
    background-color: #f1f1f1;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 14px;
  }
</style>
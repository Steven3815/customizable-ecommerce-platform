```vue
<template>
  <CCol>
    <CCard class="h-100">

      <!-- Product Image -->
      <CCardImage
        v-if="image"
        :src="image"
        :alt="name"
        class="product-image"
      />

      <div v-else class="image-placeholder">
        No Image
      </div>

      <CCardBody class="d-flex flex-column">

        <!-- Product Name -->
        <CCardTitle>
          {{ name }}
        </CCardTitle>

        <!-- Product Price -->
        <div class="price mb-3">
          ${{ Number(price).toFixed(2) }}
        </div>

        <!-- Quantity -->
        <CInputGroup class="mb-3">

          <CButton
            color="light"
            @click="decreaseQuantity"
          >
            −
          </CButton>

          <CFormInput
            v-model.number="quantity"
            type="number"
            min="1"
            class="text-center"
          />

          <CButton
            color="light"
            @click="increaseQuantity"
          >
            +
          </CButton>

        </CInputGroup>

        <!-- Add to Cart -->
        <CButton
          color="primary"
          class="w-100 mt-auto"
          @click="addToCart"
        >
          Add to Cart
        </CButton>

      </CCardBody>

    </CCard>
  </CCol>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
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

const quantity = ref(1)

const decreaseQuantity = () => {
  if (quantity.value > 1) {
    quantity.value--
  }
}

const increaseQuantity = () => {
  quantity.value++
}

const addToCart = () => {
  console.log('Add to cart:', {
    productId: props.productId,
    quantity: quantity.value
  })
}
</script>

<style scoped>
.product-image {
  width: 100%;
  aspect-ratio: 1 / 1;
  object-fit: contain;
}

.image-placeholder {
  width: 100%;
  aspect-ratio: 1 / 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--cui-tertiary-bg);
  color: var(--cui-secondary-color);
  font-size: 14px;
}

.price {
  font-size: 1.1rem;
  font-weight: 600;
}
</style>
```

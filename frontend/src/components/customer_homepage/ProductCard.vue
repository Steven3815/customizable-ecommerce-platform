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

      <div
        v-else
        class="image-placeholder"
      >
        No Image
      </div>

      <CCardBody class="d-flex flex-column">

        <CCardTitle>
          {{ name }}
        </CCardTitle>

        <div class="price mb-3">
          ${{ Number(price).toFixed(2) }}
        </div>

        <div class="d-flex gap-2 mt-auto">

          <CInputGroup class="quantity-input">

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

          <CButton
            color="primary"
            class="cart-btn"
            @click="addToCart"
          >
            購買
          </CButton>

        </div>

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
  width: calc(100% - 24px);
  aspect-ratio: 1 / 1;
  object-fit: cover;
  display: block;
  margin: 12px auto 0;
  border-radius: 4px;
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
  font-size: 1rem;
  font-weight: 600;
  margin-top: 0.5rem;
}

.card-title {
  margin-top: 0.5rem;
  font-size: 1.2rem;
}

:deep(input[type='number']::-webkit-inner-spin-button),
:deep(input[type='number']::-webkit-outer-spin-button) {
  -webkit-appearance: none;
  margin: 0;
}

:deep(input[type='number']) {
  -moz-appearance: textfield;
  appearance: textfield;
}

h2,
.card-title,
.price {
  cursor: default;
  text-align: center;
}

.quantity-input {
  width: 60%;
  margin-right: 3%;
}

.cart-btn {
  width: 30%;
}
</style>
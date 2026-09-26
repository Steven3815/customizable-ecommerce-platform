<template>
  <CCol>
    <CCard class="h-100">

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

        <CButton
          color="primary"
          class="mt-auto"
          @click="openProduct"
        >
          查看商品
        </CButton>

      </CCardBody>

    </CCard>
  </CCol>
</template>

<script setup>
import {
  CCol,
  CCard,
  CCardImage,
  CCardBody,
  CCardTitle,
  CButton
} from '@coreui/vue'

const props = defineProps({
  productId: {
    type: Number,
    default: null
  },

  storeId: {
    type: [Number, String],
    required: true
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

const emit = defineEmits([
  'view-product'
])

const openProduct = () => {
  emit(
    'view-product',
    {
      productId: props.productId,
      storeId: props.storeId
    }
  )
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

h2,
.card-title,
.price {
  cursor: default;
  text-align: center;
}
</style>
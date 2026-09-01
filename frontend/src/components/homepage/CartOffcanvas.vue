```vue
<template>
  <COffcanvas
    placement="end"
    :scroll="true"
    :visible="visible"
    @close="close"
  >
    <COffcanvasHeader>
      <COffcanvasTitle>
        購物車
      </COffcanvasTitle>

      <CCloseButton
        class="text-reset"
        @click="close"
      />
    </COffcanvasHeader>

    <COffcanvasBody>

      <!-- Cart Header -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
          <span class="text-primary">
            Your cart
          </span>
        </h4>

        <CBadge
          color="primary"
          shape="rounded-pill"
        >
          3
        </CBadge>
      </div>

      <!-- Cart Items -->
      <CListGroup class="mb-3">

        <CListGroupItem
          class="d-flex justify-content-between align-items-center"
        >
          <div>
            <h6 class="mb-1">
              Growers cider
            </h6>

            <small class="text-body-secondary">
              Brief description
            </small>
          </div>

          <span>
            $12
          </span>
        </CListGroupItem>

        <CListGroupItem
          class="d-flex justify-content-between align-items-center"
        >
          <div>
            <h6 class="mb-1">
              Fresh grapes
            </h6>

            <small class="text-body-secondary">
              Brief description
            </small>
          </div>

          <span>
            $8
          </span>
        </CListGroupItem>

        <CListGroupItem
          class="d-flex justify-content-between align-items-center"
        >
          <div>
            <h6 class="mb-1">
              Heinz tomato ketchup
            </h6>

            <small class="text-body-secondary">
              Brief description
            </small>
          </div>

          <span>
            $5
          </span>
        </CListGroupItem>

        <!-- Total -->
        <CListGroupItem
          class="d-flex justify-content-between align-items-center"
        >
          <span>
            Total (USD)
          </span>

          <strong>
            $25
          </strong>
        </CListGroupItem>

      </CListGroup>

      <!-- Checkout -->
      <CButton
        color="primary"
        size="lg"
        class="w-100"
      >
        繼續結帳
      </CButton>

    </COffcanvasBody>
  </COffcanvas>
</template>

<script setup>
import { ref } from 'vue'

const visible = ref(false)

const open = () => {
  visible.value = true
}

const close = () => {
  visible.value = false
}

defineExpose({
  open,
  close,
})
</script>
```

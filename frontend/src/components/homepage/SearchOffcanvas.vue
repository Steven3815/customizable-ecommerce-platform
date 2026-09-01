<template>
  <COffcanvas
    placement="end"
    :visible="visible"
    @close="visible = false"
    scroll
  >

    <!-- Header -->
    <COffcanvasHeader>

      <COffcanvasTitle>
        Search
      </COffcanvasTitle>

      <CCloseButton
        class="text-reset"
        @click="visible = false"
      />

    </COffcanvasHeader>


    <!-- Body -->
    <COffcanvasBody>

      <CForm @submit.prevent="search">

        <CInputGroup>

          <CFormInput
            v-model="keyword"
            type="search"
            placeholder="What are you looking for?"
            aria-label="What are you looking for?"
          />

          <CButton
            type="submit"
            color="primary"
          >
            Search
          </CButton>

        </CInputGroup>

      </CForm>

    </COffcanvasBody>

  </COffcanvas>
</template>


<script setup>
import { ref } from 'vue'

const visible = ref(false)

const keyword = ref('')

const search = () => {
  console.log('Search:', keyword.value)
}
</script>
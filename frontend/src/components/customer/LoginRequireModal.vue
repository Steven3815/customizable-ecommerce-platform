<template>
  <CModal
    :visible="visible"
    @close="closeModal"
    alignment="center"
  >
    <CModalHeader>

    </CModalHeader>

    <CModalBody>
      <div class="login-modal text-center py-4">
        <div class="mb-3">
          請先登入後再進行操作
        </div>

        <CButton
          color="primary"
          @click="goToLogin"
        >
          登入
        </CButton>
      </div>
    </CModalBody>
  </CModal>
</template>

<script setup>
import {
  CModal,
  CModalHeader,
  CModalBody,
  CButton
} from '@coreui/vue'

import { useRouter } from 'vue-router'

const router = useRouter()

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  storeId: {
    type: [Number, String],
    required: true
  }
})

const emit = defineEmits(['close'])

function closeModal() {
  emit('close')
}

function goToLogin() {
  closeModal()

  router.push(`/store-${props.storeId}/login`)
}
</script>

<style scoped>
.login-modal {
  min-height: 200px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.close-button {
  margin-left: auto;
  font-size: 1.5rem;
  line-height: 1;
  padding: 0.25rem 0.5rem;
}
</style>
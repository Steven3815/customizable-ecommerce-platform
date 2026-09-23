<template>
  <div>

    <CFooter class="px-4 py-3">
      <CContainer fluid>

        <CRow>

          <CCol :lg="6" :md="6" class="mb-4">

            <h5 class="fw-semibold mb-3">
              Customer Service
            </h5>

            <CListGroup flush>

              <CListGroupItem
                v-if="footer.email_enable"
                class="border-0 px-0 py-1"
              >
                {{ footer.email }}
              </CListGroupItem>

              <CListGroupItem
                v-if="footer.contact_phone_enable"
                class="border-0 px-0 py-1"
              >
                {{ footer.contact_phone }}
              </CListGroupItem>

              <CListGroupItem
                v-if="footer.service_phone_enable"
                class="border-0 px-0 py-1"
              >
                {{ footer.service_phone }}
              </CListGroupItem>

              <CListGroupItem
                v-if="footer.address_enable"
                class="border-0 px-0 py-1"
              >
                {{ footer.address }}
              </CListGroupItem>

            </CListGroup>

          </CCol>

        </CRow>

      </CContainer>
    </CFooter>

  </div>
</template>

<script setup>
defineProps({
  footer: {
    type: Object,
    default: () => ({
      contact_phone: null,
      address: null,
      email: null,
      service_phone: null,
      contact_phone_enable: false,
      address_enable: false,
      email_enable: false,
      service_phone_enable: false
    })
  }
})
</script>

<style scoped>
:deep(.list-group-item) {
  background-color: transparent;
}
</style>
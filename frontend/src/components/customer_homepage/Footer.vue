<template>
  <div>

    <CFooter class="px-4 py-5">
      <CContainer fluid>

        <CRow>

          <CCol :lg="6" :md="6" class="mb-4">

            <h5 class="fw-semibold mb-3">
              Ultras
            </h5>

            <CListGroup flush>

              <CListGroupItem class="border-0 px-0 py-1">
                <a href="#" class="footer-link">
                  About us
                </a>
              </CListGroupItem>

              <CListGroupItem class="border-0 px-0 py-1">
                <a href="#" class="footer-link">
                  Conditions
                </a>
              </CListGroupItem>

              <CListGroupItem class="border-0 px-0 py-1">
                <a href="#" class="footer-link">
                  Our Journals
                </a>
              </CListGroupItem>

              <CListGroupItem class="border-0 px-0 py-1">
                <a href="#" class="footer-link">
                  Careers
                </a>
              </CListGroupItem>

              <CListGroupItem class="border-0 px-0 py-1">
                <a href="#" class="footer-link">
                  Affiliate Programme
                </a>
              </CListGroupItem>

              <CListGroupItem class="border-0 px-0 py-1">
                <a href="#" class="footer-link">
                  Ultras Press
                </a>
              </CListGroupItem>

            </CListGroup>

          </CCol>

          <CCol :lg="6" :md="6" class="mb-4">

            <h5 class="fw-semibold mb-3">
              Customer Service
            </h5>

            <CListGroup flush>

              <CListGroupItem class="border-0 px-0 py-1">
                <a href="#" class="footer-link">
                  FAQ
                </a>
              </CListGroupItem>

              <CListGroupItem class="border-0 px-0 py-1">
                <a href="#" class="footer-link">
                  Contact
                </a>
              </CListGroupItem>

              <CListGroupItem class="border-0 px-0 py-1">
                <a href="#" class="footer-link">
                  Privacy Policy
                </a>
              </CListGroupItem>

              <CListGroupItem class="border-0 px-0 py-1">
                <a href="#" class="footer-link">
                  Returns & Refunds
                </a>
              </CListGroupItem>

              <CListGroupItem class="border-0 px-0 py-1">
                <a href="#" class="footer-link">
                  Cookie Guidelines
                </a>
              </CListGroupItem>

              <CListGroupItem class="border-0 px-0 py-1">
                <a href="#" class="footer-link">
                  Delivery Information
                </a>
              </CListGroupItem>

            </CListGroup>

          </CCol>

        </CRow>

      </CContainer>
    </CFooter>
  </div>
</template>


<script setup>
import {
  cibFacebook,
  cibTwitter,
  cibYoutube,
  cibInstagram
} from '@coreui/icons'

const icons = {
  cibFacebook,
  cibTwitter,
  cibYoutube,
  cibInstagram
}
</script>


<style scoped>
.footer-link {
  color: var(--cui-body-color);
  text-decoration: none;
}

.footer-link:hover {
  color: var(--cui-body-color);
}

:deep(.list-group-item) {
  background-color: transparent;
}
</style>
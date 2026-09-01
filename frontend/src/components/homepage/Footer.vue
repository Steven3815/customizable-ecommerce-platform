<template>
  <div>

    <CFooter class="px-4 py-5">
      <CContainer fluid>

        <!-- Footer Content -->
        <CRow>

          <!-- Ultras -->
          <CCol :lg="4" :md="6" class="mb-4">

            <h5 class="mb-3">
              Ultras
            </h5>

            <CListGroup flush>

              <CListGroupItem class="border-0 px-0">
                <a href="#" class="footer-link">
                  About us
                </a>
              </CListGroupItem>

              <CListGroupItem class="border-0 px-0">
                <a href="#" class="footer-link">
                  Conditions
                </a>
              </CListGroupItem>

              <CListGroupItem class="border-0 px-0">
                <a href="#" class="footer-link">
                  Our Journals
                </a>
              </CListGroupItem>

              <CListGroupItem class="border-0 px-0">
                <a href="#" class="footer-link">
                  Careers
                </a>
              </CListGroupItem>

              <CListGroupItem class="border-0 px-0">
                <a href="#" class="footer-link">
                  Affiliate Programme
                </a>
              </CListGroupItem>

              <CListGroupItem class="border-0 px-0">
                <a href="#" class="footer-link">
                  Ultras Press
                </a>
              </CListGroupItem>

            </CListGroup>

          </CCol>


          <!-- Customer Service -->
          <CCol :lg="4" :md="6" class="mb-4">

            <h5 class="mb-3">
              Customer Service
            </h5>

            <CListGroup flush>

              <CListGroupItem class="border-0 px-0">
                <a href="#" class="footer-link">
                  FAQ
                </a>
              </CListGroupItem>

              <CListGroupItem class="border-0 px-0">
                <a href="#" class="footer-link">
                  Contact
                </a>
              </CListGroupItem>

              <CListGroupItem class="border-0 px-0">
                <a href="#" class="footer-link">
                  Privacy Policy
                </a>
              </CListGroupItem>

              <CListGroupItem class="border-0 px-0">
                <a href="#" class="footer-link">
                  Returns & Refunds
                </a>
              </CListGroupItem>

              <CListGroupItem class="border-0 px-0">
                <a href="#" class="footer-link">
                  Cookie Guidelines
                </a>
              </CListGroupItem>

              <CListGroupItem class="border-0 px-0">
                <a href="#" class="footer-link">
                  Delivery Information
                </a>
              </CListGroupItem>

            </CListGroup>

          </CCol>


          <!-- Social Media -->
          <CCol :lg="4" :md="6" class="mb-4">

            <h5 class="mb-3">
              Follow Us
            </h5>

            <div class="d-flex gap-2">

              <!-- Facebook -->
              <CButton
                color="light"
                variant="outline"
                shape="rounded-circle"
              >
                <CIcon
                  :icon="icons.cibFacebook"
                  size="sm"
                />
              </CButton>

              <!-- Twitter -->
              <CButton
                color="light"
                variant="outline"
                shape="rounded-circle"
              >
                <CIcon
                  :icon="icons.cibTwitter"
                  size="sm"
                />
              </CButton>

              <!-- YouTube -->
              <CButton
                color="light"
                variant="outline"
                shape="rounded-circle"
              >
                <CIcon
                  :icon="icons.cibYoutube"
                  size="sm"
                />
              </CButton>

              <!-- Instagram -->
              <CButton
                color="light"
                variant="outline"
                shape="rounded-circle"
              >
                <CIcon
                  :icon="icons.cibInstagram"
                  size="sm"
                />
              </CButton>

            </div>

          </CCol>

        </CRow>


        <!-- Footer Bottom -->
        <CRow class="mt-4 pt-3 border-top">

          <CCol>
            <p class="mb-0">
              © 2026 Store. All rights reserved.
            </p>
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
  text-decoration: underline;
}
</style>
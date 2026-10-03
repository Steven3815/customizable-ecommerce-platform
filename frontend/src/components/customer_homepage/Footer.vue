<template>
  <div>
    <CFooter
      v-if="hasFooterContent"
      class="footer px-4 py-4"
    >
      <CContainer fluid>

        <CRow>
          <CCol :lg="12">

            <!-- 標題 -->
            <h5 class="footer-title">
              客服中心
            </h5>

            <div class="footer-divider"></div>

            <!-- 聯絡資訊 -->
            <CRow class="footer-contact g-4">

              <!-- Email -->
              <CCol
                v-if="footer.email_enable && footer.email"
                :lg="3"
                :md="6"
              >
                <div class="footer-item">

                  <div class="footer-icon">
                    <CIcon icon="cilEnvelopeClosed" />
                  </div>

                  <div class="footer-content">
                    <div class="footer-label">
                      電子郵件
                    </div>

                    <div class="footer-value">
                      {{ footer.email }}
                    </div>
                  </div>

                </div>
              </CCol>

              <!-- 聯絡電話 -->
              <CCol
                v-if="
                  footer.contact_phone_enable &&
                  footer.contact_phone
                "
                :lg="3"
                :md="6"
              >
                <div class="footer-item">

                  <div class="footer-icon">
                    <CIcon
                      icon="cilPhone"
                      class="phone-icon"
                    />
                  </div>

                  <div class="footer-content">
                    <div class="footer-label">
                      聯絡電話
                    </div>

                    <div class="footer-value">
                      {{ footer.contact_phone }}
                    </div>
                  </div>

                </div>
              </CCol>

              <!-- 客服電話 -->
              <CCol
                v-if="
                  footer.service_phone_enable &&
                  footer.service_phone
                "
                :lg="3"
                :md="6"
              >
                <div class="footer-item">

                  <div class="footer-icon">
                    <CIcon
                      icon="cilPhone"
                      class="phone-icon"
                    />
                  </div>

                  <div class="footer-content">
                    <div class="footer-label">
                      客服專線
                    </div>

                    <div class="footer-value">
                      {{ footer.service_phone }}
                    </div>
                  </div>

                </div>
              </CCol>

              <!-- 地址 -->
              <CCol
                v-if="
                  footer.address_enable &&
                  footer.address
                "
                :lg="3"
                :md="6"
              >
                <div class="footer-item">

                  <div class="footer-icon">
                    <CIcon icon="cilLocationPin" />
                  </div>

                  <div class="footer-content">
                    <div class="footer-label">
                      地址
                    </div>

                    <div class="footer-value">
                      {{ footer.address }}
                    </div>
                  </div>

                </div>
              </CCol>

            </CRow>

          </CCol>
        </CRow>

      </CContainer>
    </CFooter>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
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

const hasFooterContent = computed(() => {
  return (
    (props.footer.email_enable && props.footer.email) ||
    (
      props.footer.contact_phone_enable &&
      props.footer.contact_phone
    ) ||
    (
      props.footer.service_phone_enable &&
      props.footer.service_phone
    ) ||
    (
      props.footer.address_enable &&
      props.footer.address
    )
  )
})
</script>

<style scoped>
.footer {
  margin-top: 40px;
  background-color: var(--cui-tertiary-bg);
  border-top: 1px solid var(--cui-border-color);
  color: var(--cui-body-color);
}

/* 標題 */
.footer-title {
  margin-bottom: 0;
  font-size: 18px;
  font-weight: 600;
  letter-spacing: 0.3px;
}

/* 分隔線 */
.footer-divider {
  width: 40px;
  height: 3px;
  margin-top: 10px;
  margin-bottom: 24px;
  background-color: var(--cui-primary);
  border-radius: 3px;
}

/* 聯絡資訊 */
.footer-contact {
  margin-top: 0;
}

/* 每一個資訊區塊 */
.footer-item {
  display: flex;
  align-items: center;
  gap: 14px;
  min-height: 40px;
  height: 100%;
  transition: opacity 0.2s ease;
}

.footer-item:hover {
  opacity: 0.75;
}

/* Icon */
.footer-icon {
  width: 36px;
  height: 36px;
  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  color: var(--cui-primary);
  background-color: var(--cui-primary-bg-subtle);
  border-radius: 8px;
}

/* 電話 Icon 左右翻轉 */
.phone-icon {
  transform: scaleX(-1);
}

/* 文字區塊 */
.footer-content {
  min-width: 0;
}

/* 小標題 */
.footer-label {
  margin-bottom: 2px;
  color: var(--cui-secondary-color);
  font-size: 12px;
  line-height: 1.4;
}

/* 內容 */
.footer-value {
  font-size: 14px;
  line-height: 1.5;
  word-break: break-word;
}

/* 平板 */
@media (max-width: 991.98px) {
  .footer-item {
    align-items: center;
  }
}

/* 手機 */
@media (max-width: 576px) {
  .footer {
    padding-top: 28px !important;
    padding-bottom: 28px !important;
  }

  .footer-contact {
    row-gap: 14px !important;
  }

  .footer-item {
    align-items: flex-start;
  }

  .footer-icon {
    margin-top: 2px;
  }
}
</style>
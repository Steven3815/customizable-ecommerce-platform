<template>
  <div class="landing-page">

    <!-- Navbar -->
    <CHeader
      position="sticky"
      class="border-bottom bg-white"
    >
      <CContainer class="d-flex align-items-center justify-content-between">

        <!-- Logo -->
        <div class="d-flex align-items-center">
          <div class="brand-logo me-2">
            E
          </div>

          <span class="fw-semibold fs-5">
            客製化電商平台
          </span>
        </div>

        <!-- Navigation -->
        <div class="d-flex align-items-center gap-3">
          <RouterLink
            to="/store/login"
            class="btn btn-primary"
          >
            登入
          </RouterLink>
        </div>

      </CContainer>
    </CHeader>


    <!-- Hero -->
    <section class="hero-section">
      <CContainer>

        <CRow class="align-items-center min-vh-75">

          <!-- Hero Text -->
          <CCol
            lg="6"
            class="mb-5 mb-lg-0"
          >

            <div class="hero-badge mb-4">
              <span></span>
              多商店客製化電商平台
            </div>

            <h1 class="hero-title mb-4">
              打造屬於你的
              <br class="hero-title-break">

              <span class="text-primary">
                線上商店
              </span>
            </h1>

            <p class="hero-description mb-4">
              提供中小型商家簡單、彈性的電商管理工具，
              從商品管理、庫存、訂單到付款，
              一站完成線上商店的營運。
            </p>

            <div class="d-flex flex-wrap gap-3">

              <RouterLink
                to="/store/login"
                class="btn btn-primary btn-lg px-4"
              >
                開始使用

                <CIcon
                  icon="cilArrowRight"
                  class="ms-2"
                />
              </RouterLink>

              <CButton
                color="secondary"
                variant="outline"
                size="lg"
                class="px-4"
                @click="scrollToFeatures"
              >
                了解更多
              </CButton>

            </div>

          </CCol>


          <!-- Hero Image -->
          <CCol lg="6">

            <div class="landing-image-wrapper hero-image-wrapper">

              <img
                src="/landing/store-dashboard.png"
                alt="Store 後台 Dashboard"
                class="landing-image"
              >

            </div>

          </CCol>

        </CRow>

      </CContainer>
    </section>


    <!-- Features -->
    <section
      ref="featuresSection"
      class="section bg-light"
    >

      <CContainer>

        <div class="section-heading text-center">

          <div class="section-label">
            PLATFORM FEATURES
          </div>

          <h2>
            一站式電商管理
          </h2>

          <p>
            從商品建立到訂單完成，
            提供完整的線上商店管理功能。
          </p>

        </div>


        <CRow class="g-4">

          <!-- Feature 1 -->
          <CCol
            md="6"
            lg="3"
          >

            <CCard class="feature-card h-100 border-0">

              <CCardBody>

                <div class="feature-icon">
                  <CIcon icon="cilBasket" />
                </div>

                <h4>
                  商品管理
                </h4>

                <p>
                  建立商品、分類與商品規格，
                  讓商家能夠快速管理商品資訊。
                </p>

              </CCardBody>

            </CCard>

          </CCol>


          <!-- Feature 2 -->
          <CCol
            md="6"
            lg="3"
          >

            <CCard class="feature-card h-100 border-0">

              <CCardBody>

                <div class="feature-icon">
                  <CIcon icon="cilStorage" />
                </div>

                <h4>
                  庫存管理
                </h4>

                <p>
                  即時管理商品與規格庫存，
                  降低缺貨與庫存管理的負擔。
                </p>

              </CCardBody>

            </CCard>

          </CCol>


          <!-- Feature 3 -->
          <CCol
            md="6"
            lg="3"
          >

            <CCard class="feature-card h-100 border-0">

              <CCardBody>

                <div class="feature-icon">
                  <CIcon icon="cilCart" />
                </div>

                <h4>
                  訂單管理
                </h4>

                <p>
                  集中管理顧客訂單，
                  掌握付款、出貨與配送狀態。
                </p>

              </CCardBody>

            </CCard>

          </CCol>


          <!-- Feature 4 -->
          <CCol
            md="6"
            lg="3"
          >

            <CCard class="feature-card h-100 border-0">

              <CCardBody>

                <div class="feature-icon">
                  <CIcon icon="cilCreditCard" />
                </div>

                <h4>
                  付款管理
                </h4>

                <p>
                  支援多種付款方式，
                  提供完整的線上購物流程。
                </p>

              </CCardBody>

            </CCard>

          </CCol>

        </CRow>

      </CContainer>

    </section>


    <!-- Customer Store Preview 1 -->
    <section class="section">

      <CContainer>

        <CRow class="align-items-center">

          <!-- Image -->
          <CCol
            lg="6"
            class="mb-5 mb-lg-0"
          >

            <div class="landing-image-wrapper">

              <img
                src="/landing/customer-home-1.png"
                alt="顧客視角商店首頁"
                class="landing-image"
              >

            </div>

          </CCol>


          <!-- Text -->
          <CCol lg="6">

            <div class="section-label">
              CUSTOMER STORE
            </div>

            <h2 class="mb-4">
              每個商家，
              <br>
              都能擁有自己的商店
            </h2>

            <p class="text-medium-emphasis mb-4">
              平台採用多商店架構，
              不同商家可以擁有獨立的商品、
              訂單與商店設定。
            </p>


            <div class="feature-list">

              <div class="feature-list-item">

                <div class="check-icon">
                  <CIcon icon="cilCheck" />
                </div>

                <span>
                  獨立商店管理
                </span>

              </div>


              <div class="feature-list-item">

                <div class="check-icon">
                  <CIcon icon="cilCheck" />
                </div>

                <span>
                  自訂商店設定
                </span>

              </div>


              <div class="feature-list-item">

                <div class="check-icon">
                  <CIcon icon="cilCheck" />
                </div>

                <span>
                  商品與訂單分開管理
                </span>

              </div>

            </div>

          </CCol>

        </CRow>

      </CContainer>

    </section>


    <!-- Customer Store Preview 2 -->
    <section class="section bg-light">

      <CContainer>

        <CRow class="align-items-center">

          <!-- Text -->
          <CCol
            lg="5"
            class="order-2 order-lg-1"
          >

            <div class="section-label">
              CUSTOMER EXPERIENCE
            </div>

            <h2 class="mb-4">
              簡單直覺的
              <br>
              顧客購物體驗
            </h2>

            <p class="text-medium-emphasis mb-4">
              顧客可以直接瀏覽商店商品，
              查看商品資訊、選擇商品規格，
              並透過購物車完成訂單。
            </p>


            <div class="feature-list">

              <div class="feature-list-item">

                <div class="check-icon">
                  <CIcon icon="cilCheck" />
                </div>

                <span>
                  商品與分類瀏覽
                </span>

              </div>


              <div class="feature-list-item">

                <div class="check-icon">
                  <CIcon icon="cilCheck" />
                </div>

                <span>
                  商品規格與庫存
                </span>

              </div>


              <div class="feature-list-item">

                <div class="check-icon">
                  <CIcon icon="cilCheck" />
                </div>

                <span>
                  購物車與訂單流程
                </span>

              </div>

            </div>

          </CCol>


          <!-- Image -->
          <CCol
            lg="7"
            class="order-1 order-lg-2 mb-5 mb-lg-0"
          >

            <div class="landing-image-wrapper">

              <img
                src="/landing/customer-home-2.png"
                alt="顧客視角購物頁面"
                class="landing-image"
              >

            </div>

          </CCol>

        </CRow>

      </CContainer>

    </section>


    <!-- Process -->
    <section
      ref="processSection"
      class="section"
    >

      <CContainer>

        <div class="section-heading text-center">

          <div class="section-label">
            HOW IT WORKS
          </div>

          <h2>
            簡單四步驟
          </h2>

          <p>
            從建立商店到完成訂單，
            快速開始你的電商服務。
          </p>

        </div>


        <CRow class="process-row">

          <CCol
            md="3"
            class="process-item"
          >

            <div class="process-number">
              01
            </div>

            <h4>
              建立商店
            </h4>

            <p>
              建立你的商店並完成基本設定。
            </p>

          </CCol>


          <CCol
            md="3"
            class="process-item"
          >

            <div class="process-number">
              02
            </div>

            <h4>
              上架商品
            </h4>

            <p>
              建立商品、分類與商品規格。
            </p>

          </CCol>


          <CCol
            md="3"
            class="process-item"
          >

            <div class="process-number">
              03
            </div>

            <h4>
              顧客購物
            </h4>

            <p>
              顧客瀏覽商品並加入購物車。
            </p>

          </CCol>


          <CCol
            md="3"
            class="process-item"
          >

            <div class="process-number">
              04
            </div>

            <h4>
              完成訂單
            </h4>

            <p>
              處理付款、出貨與配送。
            </p>

          </CCol>

        </CRow>

      </CContainer>

    </section>


    <!-- CTA -->
    <section class="cta-section">

      <CContainer>

        <div class="cta-box text-center">

          <div class="section-label">
            GET STARTED
          </div>

          <h2>
            準備好建立你的線上商店了嗎？
          </h2>

          <p>
            開始使用客製化電商平台，
            建立屬於你的線上商店。
          </p>

          <RouterLink
            to="/store/login"
            class="btn btn-primary btn-lg px-5"
          >
            開始使用

            <CIcon
              icon="cilArrowRight"
              class="ms-2"
            />
          </RouterLink>

        </div>

      </CContainer>

    </section>


    <!-- Footer -->
    <footer class="footer">

      <CContainer>

        <div
          class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3"
        >

          <div>

            <strong>
              客製化電商平台
            </strong>

            <div class="text-medium-emphasis small mt-1">
              為中小型商家打造的電商管理平台
            </div>

          </div>


          <div class="text-medium-emphasis small">
            Created by Steven-Ye
          </div>

        </div>

      </CContainer>

    </footer>

  </div>
</template>


<script setup>
import { ref } from 'vue'

const featuresSection = ref(null)
const processSection = ref(null)

const scrollToFeatures = () => {
  featuresSection.value?.scrollIntoView({
    behavior: 'smooth'
  })
}
</script>


<style scoped>
.landing-page {
  background: #ffffff;
  color: #212529;
}


/* Navbar */

.brand-logo {
  width: 34px;
  height: 34px;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 8px;

  background: var(--cui-primary);
  color: #ffffff;

  font-weight: 700;
}


/* Hero */

.hero-section {
  background: linear-gradient(
    180deg,
    #ffffff 0%,
    #f8f9fa 100%
  );
}

.min-vh-75 {
  min-height: 75vh;
}

.hero-badge {
  display: inline-flex;
  align-items: center;

  gap: 8px;

  padding: 7px 14px;

  border-radius: 20px;

  background: #f1f3f5;
  color: #6c757d;

  font-size: 14px;
}

.hero-badge span {
  width: 7px;
  height: 7px;

  border-radius: 50%;

  background: var(--cui-primary);
}

.hero-title {
  font-size: clamp(42px, 5vw, 68px);

  font-weight: 700;
  line-height: 1.15;

  letter-spacing: -1.5px;
}

.hero-title-break {
  display: block;
}

.hero-description {
  max-width: 560px;

  color: #6c757d;

  font-size: 18px;
  line-height: 1.8;
}


/* Landing Images */

.landing-image-wrapper {
  width: 100%;

  padding: 20px;

  border-radius: 18px;

  background: #f8f9fa;

  box-shadow:
    0 15px 40px rgba(0, 0, 0, 0.06);
}

.hero-image-wrapper {
  padding: 15px;

  background: #ffffff;

  box-shadow:
    0 20px 60px rgba(0, 0, 0, 0.1);
}

.landing-image {
  display: block;

  width: 100%;
  height: auto;

  border-radius: 10px;
}


/* Sections */

.section {
  padding: 100px 0;
}

.section-heading {
  max-width: 650px;

  margin: 0 auto 55px;
}

.section-label {
  margin-bottom: 12px;

  color: var(--cui-primary);

  font-size: 12px;
  font-weight: 700;

  letter-spacing: 1.5px;
}

.section-heading h2,
.section h2 {
  font-size: 38px;

  font-weight: 700;

  line-height: 1.3;
}

.section-heading p {
  margin-top: 15px;

  color: #6c757d;
}


/* Feature */

.feature-card {
  border-radius: 12px;

  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.feature-card:hover {
  transform: translateY(-5px);

  box-shadow:
    0 12px 35px rgba(0, 0, 0, 0.08);
}

.feature-icon {
  width: 48px;
  height: 48px;

  margin-bottom: 22px;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 10px;

  background: rgba(50, 31, 219, 0.1);

  color: var(--cui-primary);

  font-size: 20px;
}

.feature-card h4 {
  margin-bottom: 12px;

  font-size: 19px;
  font-weight: 600;
}

.feature-card p {
  margin-bottom: 0;

  color: #6c757d;

  line-height: 1.7;
}


/* Feature List */

.feature-list {
  display: flex;

  flex-direction: column;

  gap: 18px;
}

.feature-list-item {
  display: flex;
  align-items: center;

  gap: 12px;
}

.check-icon {
  width: 28px;
  height: 28px;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 50%;

  background: rgba(50, 31, 219, 0.1);

  color: var(--cui-primary);

  font-size: 14px;
}


/* Process */

.process-row {
  position: relative;
}

.process-item {
  text-align: center;
}

.process-number {
  width: 58px;
  height: 58px;

  margin: 0 auto 22px;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 50%;

  background: #ffffff;

  color: var(--cui-primary);

  font-weight: 700;

  box-shadow:
    0 5px 20px rgba(0, 0, 0, 0.08);
}

.process-item h4 {
  font-size: 18px;
  font-weight: 600;
}

.process-item p {
  margin-top: 10px;

  color: #6c757d;

  line-height: 1.6;
}


/* CTA */

.cta-section {
  padding: 100px 0;
}

.cta-box {
  padding: 80px 30px;

  border-radius: 18px;

  background: #f8f9fa;
}

.cta-box h2 {
  margin-bottom: 15px;

  font-size: 38px;
  font-weight: 700;
}

.cta-box p {
  margin-bottom: 30px;

  color: #6c757d;
}


/* Footer */

.footer {
  padding: 35px 0;

  border-top: 1px solid #e9ecef;

  background: #ffffff;
}


/* Responsive */

@media (max-width: 991.98px) {
  .hero-image-wrapper {
    margin-top: 20px;
  }
}

@media (max-width: 767.98px) {

  .hero-section {
    padding-top: 30px;
  }

  .hero-title {
    font-size: 42px;
  }

  .hero-title-break {
    display: none;
  }

  .hero-description {
    font-size: 16px;
  }

  .landing-image-wrapper {
    padding: 12px;
  }

  .hero-image-wrapper {
    padding: 10px;
  }

  .section {
    padding: 70px 0;
  }

  .section-heading h2,
  .section h2,
  .cta-box h2 {
    font-size: 30px;
  }

  .cta-section {
    padding: 70px 0;
  }
}
</style>
import { createApp } from 'vue'

import App from './App.vue'
import router from './router'

import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'
import 'swiper/css'

import './assets/normalize.css'
import './assets/vendor.css'
import './assets/style.css'

import { createPinia } from 'pinia'
import CoreuiVue from '@coreui/vue'
import CIcon from '@coreui/icons-vue'
import { iconsSet as icons } from '@/assets/icons'

import './assets/CoreUI/style.scss'
import './assets/CoreUI/simplebar.scss'

const app = createApp(App)

app.use(router)
app.use(CoreuiVue)
app.use(createPinia())

app.provide('icons', icons)

app.component('CIcon', CIcon)

app.mount('#app')
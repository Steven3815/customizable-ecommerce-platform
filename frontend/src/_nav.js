/**
 * _nav.js - Sidebar Navigation Configuration
 *
 * This file defines the structure and content of the sidebar navigation menu.
 * The navigation is rendered by AppSidebar component using CoreUI nav components.
 *
 * Navigation item types:
 * - CNavItem: Single navigation link
 * - CNavGroup: Expandable group of navigation items
 * - CNavTitle: Section title/divider
 *
 * Each item can have:
 * - component: CoreUI component type ('CNavItem', 'CNavGroup', 'CNavTitle')
 * - name: Display text
 * - to: Vue Router path (for CNavItem)
 * - icon: CoreUI icon name (from @coreui/icons)
 * - badge: Optional badge with color and text
 * - items: Array of child items (for CNavGroup)
 * - href: External link URL
 * - external: Boolean for external links
 *
 * @type {Array<Object>}
 */
export default [
  {
    component: 'CNavItem',
    name: '儀表板',
    to: '/store/admin/dashboard',
    icon: 'cil-speedometer',
  },

  {
    component: 'CNavGroup',
    name: '首頁',
    icon: 'cil-laptop',
    items: [
      {
        component: 'CNavItem',
        name: '首頁設定',
        to: '/store/admin/homepage_settings/settings',
        icon: 'cil-settings',
      },
      {
        component: 'CNavItem',
        name: '橫幅',
        to: '/store/admin/homepage_settings/banner',
        icon: 'cil-grid',
      },
      {
        component: 'CNavItem',
        name: '輪播',
        to: '/store/admin/homepage_settings/slider',
        icon: 'cil-layers',
      },
      {
        component: 'CNavItem',
        name: '商品',
        to: '/store/admin/homepage_settings/product',
        icon: 'cil-basket',
      },
      {
        component: 'CNavItem',
        name: '頁尾',
        to: '/store/admin/homepage_settings/footer',
        icon: 'cil-notes',
      },
    ],
  },
  
  {
    component: 'CNavTitle',
    name: '管理',
  },

  {
    component: 'CNavItem',
    name: '商品',
    to: '/store/admin/product_list',
    icon: 'cil-basket',
  },
  {
    component: 'CNavItem',
    name: '訂單',
    to: '/store/admin/order_list',
    icon: 'cil-list',
  },
  {
    component: 'CNavItem',
    name: '客戶',
    to: '/store/admin/customer_list',
    icon: 'cil-people',
  },
  {
    component: 'CNavItem',
    name: '退款',
    to: '/store/admin/refund_list',
    icon: 'cil-dollar',
  },
  {
    component: 'CNavItem',
    name: '客服',
    to: '/store/admin/customer_service_list',
    icon: 'cil-comment-square',
  },
  {
    component: 'CNavTitle',
    name: '系統',
  },
  {
    component: 'CNavItem',
    name: '商家資料',
    to: '/store/admin/profile',
    icon: 'cil-user',
  },
  {
    component: 'CNavItem',
    name: '設定',
    to: '/store/admin/settings',
    icon: 'cil-settings',
  },
]
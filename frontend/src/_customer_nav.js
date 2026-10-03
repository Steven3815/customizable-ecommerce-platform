/**
 * _customer_nav.js - Customer Sidebar Navigation Configuration
 */

export default [
  {
    component: 'CNavItem',
    name: '首頁',
    to: '/store-:storeId',
    icon: 'cilHome',
  },

  {
    component: 'CNavItem',
    name: '訂單管理',
    to: '/store-:storeId/order_list',
    icon: 'cilList',
  },

  {
    component: 'CNavItem',
    name: '購物車',
    to: '/store-:storeId/cart',
    icon: 'cilBasket',
  },

  {
    component: 'CNavItem',
    name: '退款',
    to: '/store-:storeId/refund_list',
    icon: 'cilDollar',
  },

  {
    component: 'CNavItem',
    name: '客服',
    to: '/store-:storeId/service_list',
    icon: 'cilCommentSquare',
  },

  {
    component: 'CNavItem',
    name: '個人資料',
    to: '/store-:storeId/profile',
    icon: 'cilUser',
  },
]
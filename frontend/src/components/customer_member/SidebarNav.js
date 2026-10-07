import { defineComponent, h, resolveComponent } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import {
  CSidebarNav,
  CNavItem,
} from '@coreui/vue'

const nav = [
  {
    name: '會員中心',
    to: '/customer',
    icon: 'cilHome',
  },
  {
    name: '訂單管理',
    to: '/customer/orders',
    icon: 'cilList',
  },
  {
    name: '退款',
    to: '/customer/refunds',
    icon: 'cilDollar',
  },
  {
    name: '客服',
    to: '/customer/services',
    icon: 'cilCommentSquare',
  },
]

const SidebarNav = defineComponent({
  name: 'MemberSidebarNav',

  setup() {
    const route = useRoute()

    const renderItem = (item) => {
      return h(
        RouterLink,
        {
          to: item.to,
          custom: true,
        },
        {
          default: (props) =>
            h(
              CNavItem,
              {
                active: route.path === item.to,
                as: 'div',
                href: props.href,
                onClick: () => props.navigate(),
              },
              {
                default: () => [
                  h(resolveComponent('CIcon'), {
                    customClassName: 'nav-icon',
                    name: item.icon,
                  }),
                  item.name,
                ],
              },
            ),
        },
      )
    }

    return () =>
      h(
        CSidebarNav,
        {},
        {
          default: () => nav.map((item) => renderItem(item)),
        },
      )
  },
})

export { SidebarNav }
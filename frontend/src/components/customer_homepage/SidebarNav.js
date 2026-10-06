import { defineComponent, h, onMounted, ref, resolveComponent } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import { CBadge, CSidebarNav, CNavItem, CNavGroup, CNavTitle } from '@coreui/vue'
import nav from '@/_customer_nav.js'

import simplebar from 'simplebar-vue'
import 'simplebar-vue/dist/simplebar.min.css'

const normalizePath = (path) =>
  decodeURI(path)
    .replace(/#.*$/, '')
    .replace(/(index)?\.(html)$/, '')

const isActiveLink = (route, link) => {
  if (link === undefined) {
    return false
  }

  if (route.hash === link) {
    return true
  }

  const currentPath = normalizePath(route.path)
  const targetPath = normalizePath(link)

  return currentPath === targetPath
}

// List / Detail / Create / Checkout / Payment 共用 Sidebar active 狀態
const activeRouteGroups = {
  '/store-:storeId/order_list': [
    'CustomerOrderList',
    'CustomerOrderDetail',
    'CustomerCheckout',
    'CustomerCreditCard',
    'CustomerTransferPayment',
  ],

  '/store-:storeId/refund_list': [
    'CustomerRefundList',
    'CustomerCreateRefund',
  ],

  '/store-:storeId/service_list': [
    'CustomerServiceList',
    'CustomerServiceDetail',
    'CustomerCreateService',
  ],
}

const isActiveItem = (route, item) => {
  // 將 :storeId 替換成目前商店的 storeId
  const navPath = item.to
    ? item.to.replace(':storeId', route.params.storeId)
    : item.to

  // 一般頁面直接比對實際網址
  if (isActiveLink(route, navPath)) {
    return true
  }

  // 有子選單時，檢查子項目
  if (item.items) {
    return item.items.some((child) =>
      isActiveItem(route, child)
    )
  }

  // List / Detail / Create / Checkout / Payment route name 判斷
  if (item.to && activeRouteGroups[item.to]) {
    return activeRouteGroups[item.to].includes(route.name)
  }

  return false
}

const SidebarNav = defineComponent({
  name: 'SidebarNav',
  components: {
    CNavItem,
    CNavGroup,
    CNavTitle,
  },
  setup() {
    const route = useRoute()
    const firstRender = ref(true)

    onMounted(() => {
      firstRender.value = false
    })

    // 根據目前網址取得 storeId
    const getStoreId = () => {
      return route.params.storeId
    }

    // 將 :storeId 替換成目前商店的 storeId
    const getNavPath = (path) => {
      if (!path) {
        return path
      }

      const storeId = getStoreId()

      if (!storeId) {
        return path
      }

      return path.replace(':storeId', storeId)
    }

    const renderItem = (item) => {
      if (item.items) {
        return h(
          CNavGroup,
          {
            as: 'div',
            compact: true,
            ...(firstRender.value && {
              visible: item.items.some((child) =>
                isActiveItem(route, child)
              ),
            }),
          },
          {
            togglerContent: () => [
              h(resolveComponent('CIcon'), {
                customClassName: 'nav-icon',
                name: item.icon,
              }),
              item.name,
            ],
            default: () => item.items.map((child) => renderItem(child)),
          },
        )
      }

      if (item.href) {
        return h(
          resolveComponent(item.component),
          {
            href: item.href,
            target: '_blank',
            rel: 'noopener noreferrer',
          },
          {
            default: () => [
              item.icon
                ? h(resolveComponent('CIcon'), {
                    customClassName: 'nav-icon',
                    name: item.icon,
                  })
                : h(
                    'span',
                    { class: 'nav-icon' },
                    h('span', { class: 'nav-icon-bullet' }),
                  ),
              item.name,
              item.external &&
                h(resolveComponent('CIcon'), {
                  class: 'ms-2',
                  name: 'cilExternalLink',
                  size: 'sm',
                }),
              item.badge &&
                h(
                  CBadge,
                  {
                    class: 'ms-auto',
                    color: item.badge.color,
                    size: 'sm',
                  },
                  {
                    default: () => item.badge.text,
                  },
                ),
            ],
          },
        )
      }

      const navPath = getNavPath(item.to)

      return navPath
        ? h(
            RouterLink,
            {
              to: navPath,
              custom: true,
            },
            {
              default: (props) =>
                h(
                  resolveComponent(item.component),
                  {
                    active: isActiveItem(route, item),
                    as: 'div',
                    href: props.href,
                    onClick: () => props.navigate(),
                  },
                  {
                    default: () => [
                      item.icon
                        ? h(resolveComponent('CIcon'), {
                            customClassName: 'nav-icon',
                            name: item.icon,
                          })
                        : h(
                            'span',
                            { class: 'nav-icon' },
                            h('span', { class: 'nav-icon-bullet' }),
                          ),
                      item.name,
                      item.badge &&
                        h(
                          CBadge,
                          {
                            class: 'ms-auto',
                            color: item.badge.color,
                            size: 'sm',
                          },
                          {
                            default: () => item.badge.text,
                          },
                        ),
                    ],
                  },
                ),
            },
          )
        : h(
            resolveComponent(item.component),
            {
              as: 'div',
            },
            {
              default: () => item.name,
            },
          )
    }

    return () =>
      h(
        CSidebarNav,
        {
          as: simplebar,
        },
        {
          default: () => nav.map((item) => renderItem(item)),
        },
      )
  },
})

export { SidebarNav }
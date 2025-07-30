import { createRouter, createWebHistory } from 'vue-router'
import Dashboard from '@/views/Dashboard.vue'
import Products from '@/views/Products.vue'
import Orders from '@/views/Orders.vue'
import Customers from '@/views/Customers.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'dashboard',
      component: Dashboard
    },
    {
      path: '/products',
      name: 'products', 
      component: Products
    },
    {
      path: '/orders',
      name: 'orders',
      component: Orders
    },
    {
      path: '/customers',
      name: 'customers',
      component: Customers
    }
  ]
})

export default router
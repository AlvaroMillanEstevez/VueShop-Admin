<template>
  <div class="p-4">
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="spinner"></div>
    </div>

    <div v-else>
      <div v-if="error" class="text-red-500 text-center">{{ error }}</div>
      
      <!-- Estadísticas -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white shadow rounded p-4 stat-card">
          <p class="text-gray-500 text-sm">Ingresos Totales</p>
          <p class="text-lg font-semibold">€{{ formatNumber(stats.total_revenue?.current || 0) }}</p>
        </div>
        <div class="bg-white shadow rounded p-4 stat-card">
          <p class="text-gray-500 text-sm">Pedidos Totales</p>
          <p class="text-lg font-semibold">{{ stats.total_orders?.current || 0 }}</p>
        </div>
        <div class="bg-white shadow rounded p-4 stat-card">
          <p class="text-gray-500 text-sm">Clientes Activos</p>
          <p class="text-lg font-semibold">{{ stats.active_customers || 0 }}</p>
        </div>
        <div class="bg-white shadow rounded p-4 stat-card">
          <p class="text-gray-500 text-sm">Productos en Stock</p>
          <p class="text-lg font-semibold">{{ stats.products_in_stock || 0 }}</p>
        </div>
      </div>

      <!-- Controles del gráfico -->
      <div class="flex flex-col sm:flex-row gap-4 items-center mb-4">
        <select v-model="chartType" @change="updateChart" class="border rounded px-3 py-1">
          <option value="line">Línea</option>
          <option value="bar">Barras</option>
          <option value="doughnut">Circular</option>
        </select>
        <select v-model="dataToShow" @change="updateChart" class="border rounded px-3 py-1">
          <option value="both">Ingresos y Pedidos</option>
          <option value="revenue">Solo Ingresos</option>
          <option value="orders">Solo Pedidos</option>
          <option value="products">Top Productos</option>
        </select>
        <select v-model="chartPeriod" @change="loadChartData" class="border rounded px-3 py-1">
          <option value="7">Últimos 7 días</option>
          <option value="30">Últimos 30 días</option>
          <option value="90">Últimos 90 días</option>
        </select>
      </div>

      <!-- Gráfico -->
      <div class="bg-white rounded shadow p-4 mb-6">
        <div class="relative h-[400px]">
          <canvas ref="salesChart"></canvas>
        </div>
      </div>

      <!-- Pedidos recientes -->
      <div class="bg-white shadow rounded p-4">
        <h2 class="text-lg font-semibold mb-4">Pedidos Recientes</h2>
        <div class="overflow-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="order in recentOrders" :key="order.id">
                <td class="px-4 py-2">{{ order.id }}</td>
                <td class="px-4 py-2">{{ order.customer_name }}</td>
                <td class="px-4 py-2">{{ formatDate(order.date) }}</td>
                <td class="px-4 py-2">€{{ formatNumber(order.total) }}</td>
                <td class="px-4 py-2">
                  <span :class="getStatusClasses(order.status)" class="text-xs font-medium px-2 py-1 rounded">
                    {{ translateStatus(order.status) }}
                  </span>
                </td>
              </tr>
              <tr v-if="recentOrders.length === 0">
                <td colspan="5" class="text-center py-4 text-gray-400">No hay pedidos recientes.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick, computed, onUnmounted, watch } from 'vue'
import { dashboardApi } from '@/services/api'
import { Chart, registerables } from 'chart.js'
import type { ChartTypeRegistry } from 'chart.js'

// Registrar todos los componentes de Chart.js
Chart.register(...registerables)

// Estado reactivo
const loading = ref(true)
const error = ref<string | null>(null)
const stats = ref<any>(null)
const salesData = ref<any[]>([])
const topProducts = ref<any[]>([])
const recentOrders = ref<any[]>([])
const salesChart = ref<HTMLCanvasElement | null>(null)
let chartInstance: Chart | null = null

// Controles del gráfico
const chartType = ref<'line' | 'bar' | 'doughnut'>('line')
const dataToShow = ref<'both' | 'revenue' | 'orders' | 'products'>('both')
const chartPeriod = ref(30)

// Computed
const ordersDiff = computed(() => {
  return (stats.value?.total_orders?.current || 0) - (stats.value?.total_orders?.previous || 0)
})

// Funciones de utilidad
const formatNumber = (num: number): string => {
  return new Intl.NumberFormat('es-ES', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(num)
}

const formatDate = (dateString: string): string => {
  if (!dateString) return '-'
  return new Intl.DateTimeFormat('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(new Date(dateString))
}

const translateStatus = (status: string): string => {
  const translations: Record<string, string> = {
    pending: 'Pendiente',
    processing: 'Procesando',
    shipped: 'Enviado',
    delivered: 'Entregado',
    cancelled: 'Cancelado'
  }
  return translations[status] || status
}

const getStatusClasses = (status: string): string => {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    shipped: 'bg-green-100 text-green-800',
    delivered: 'bg-emerald-100 text-emerald-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

// Crear el gráfico
const createChart = async () => {
  await nextTick()
  
  if (!salesChart.value || salesData.value.length === 0) {
    console.log('No hay datos para el gráfico o el canvas no está listo')
    return
  }

  // Destruir el gráfico anterior si existe
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }

  const ctx = salesChart.value.getContext('2d')
  if (!ctx) {
    console.error('No se pudo obtener el contexto del canvas')
    return
  }

  // Preparar los datos según la selección
  const labels = salesData.value.map(item => {
    const date = new Date(item.date)
    return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
  })

  let datasets: any[] = []
  
  // Configurar datasets según la selección
  if (dataToShow.value === 'both' && chartType.value !== 'doughnut') {
    datasets = [
      {
        label: 'Ingresos (€)',
        data: salesData.value.map(item => item.revenue),
        borderColor: 'rgb(34, 197, 94)', // Verde
        backgroundColor: 'rgba(34, 197, 94, 0.1)',
        yAxisID: 'y',
        tension: 0.4,
        fill: true,
        pointRadius: 4,
        pointHoverRadius: 6,
        borderWidth: 2
      },
      {
        label: 'Pedidos',
        data: salesData.value.map(item => item.orders),
        borderColor: 'rgb(168, 85, 247)', // Púrpura
        backgroundColor: 'rgba(168, 85, 247, 0.1)',
        yAxisID: 'y1',
        tension: 0.4,
        fill: true,
        pointRadius: 4,
        pointHoverRadius: 6,
        borderWidth: 2
      }
    ]
  } else if (dataToShow.value === 'revenue' || (dataToShow.value === 'both' && chartType.value === 'doughnut')) {
    datasets = [{
      label: 'Ingresos (€)',
      data: chartType.value === 'doughnut' 
        ? salesData.value.slice(-7).map(item => item.revenue) // Últimos 7 días para gráfico circular
        : salesData.value.map(item => item.revenue),
      borderColor: 'rgb(34, 197, 94)',
      backgroundColor: chartType.value === 'doughnut'
        ? ['#22c55e', '#3b82f6', '#a855f7', '#f59e0b', '#ef4444', '#6366f1', '#ec4899']
        : 'rgba(34, 197, 94, 0.1)',
      borderWidth: 2
    }]
  } else if (dataToShow.value === 'orders') {
    datasets = [{
      label: 'Pedidos',
      data: chartType.value === 'doughnut'
        ? salesData.value.slice(-7).map(item => item.orders)
        : salesData.value.map(item => item.orders),
      borderColor: 'rgb(168, 85, 247)',
      backgroundColor: chartType.value === 'doughnut'
        ? ['#a855f7', '#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#6366f1', '#ec4899']
        : 'rgba(168, 85, 247, 0.1)',
      borderWidth: 2
    }]
  } else if (dataToShow.value === 'products') {
    // Para productos, usamos los top productos
    const productLabels = topProducts.value.map(p => p.name)
    const productData = topProducts.value.map(p => p.total_sold)
    
    datasets = [{
      label: 'Productos Vendidos',
      data: productData,
      backgroundColor: ['#3b82f6', '#22c55e', '#a855f7', '#f59e0b', '#ef4444'],
      borderWidth: 2
    }]
    
    // Actualizar labels para productos
    if (chartType.value === 'doughnut' || chartType.value === 'bar') {
      labels.length = 0
      labels.push(...productLabels)
    }
  }

  // Configuración específica para gráfico circular
  if (chartType.value === 'doughnut') {
    labels.length = 0
    if (dataToShow.value === 'products') {
      labels.push(...topProducts.value.map(p => p.name))
    } else {
      labels.push(...salesData.value.slice(-7).map(item => {
        const date = new Date(item.date)
        return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
      }))
    }
  }

  try {
    // Crear el gráfico
    chartInstance = new Chart(ctx, {
      type: chartType.value as string as keyof ChartTypeRegistry,
      data: {
        labels: labels,
        datasets: datasets
      },
      options: getChartOptions()
    })
    
    console.log('Gráfico creado exitosamente')
  } catch (error) {
    console.error('Error al crear el gráfico:', error)
  }
}

// Obtener opciones del gráfico según el tipo
const getChartOptions = () => {
  const baseOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: chartType.value === 'doughnut' ? 'right' as const : 'top' as const,
        labels: {
          usePointStyle: true,
          padding: 20,
          font: {
            size: 12
          }
        }
      },
      tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.8)',
        padding: 12,
        cornerRadius: 8,
        titleFont: {
          size: 14,
          weight: 'bold' as const
        },
        bodyFont: {
          size: 13
        },
        callbacks: {
          label: function(context: any) {
            let label = context.dataset.label || '';
            if (label) {
              label += ': ';
            }
            if (context.parsed.y !== null) {
              if (label.includes('Ingresos')) {
                label += new Intl.NumberFormat('es-ES', {
                  style: 'currency',
                  currency: 'EUR'
                }).format(context.parsed.y);
              } else {
                label += context.parsed.y;
              }
            } else if (context.parsed !== null) {
              // Para gráficos circulares
              if (context.dataset.label?.includes('Ingresos')) {
                label += new Intl.NumberFormat('es-ES', {
                  style: 'currency',
                  currency: 'EUR'
                }).format(context.parsed);
              } else {
                label += context.parsed;
              }
            }
            return label;
          }
        }
      }
    }
  }

  // Opciones específicas para gráficos de líneas y barras
  if (chartType.value !== 'doughnut') {
    return {
      ...baseOptions,
      interaction: {
        mode: 'index' as const,
        intersect: false,
      },
      scales: {
        x: {
          grid: {
            display: false
          },
          ticks: {
            font: {
              size: 11
            },
            maxRotation: 45,
            minRotation: chartType.value === 'bar' ? 0 : 45
          }
        },
        y: {
          type: 'linear' as const,
          display: true,
          position: 'left' as const,
          beginAtZero: true,
          title: {
            display: true,
            text: dataToShow.value === 'orders' ? 'Cantidad' : 'Ingresos (€)',
            font: {
              size: 12
            }
          },
          ticks: {
            callback: function(value: any) {
              if (dataToShow.value === 'revenue' || dataToShow.value === 'both') {
                return '€' + value.toLocaleString('es-ES');
              }
              return value;
            },
            font: {
              size: 11
            }
          }
        },
        ...(dataToShow.value === 'both' ? {
          y1: {
            type: 'linear' as const,
            display: true,
            position: 'right' as const,
            beginAtZero: true,
            title: {
              display: true,
              text: 'Número de pedidos',
              font: {
                size: 12
              }
            },
            grid: {
              drawOnChartArea: false,
            },
            ticks: {
              font: {
                size: 11
              },
              stepSize: 1
            }
          }
        } : {})
      }
    }
  }

  return baseOptions
}

// Actualizar el gráfico cuando cambien los controles
const updateChart = () => {
  createChart()
}

// Cargar datos del gráfico con período específico
const loadChartData = async () => {
  try {
    const chartData = await dashboardApi.getSalesChart(chartPeriod.value)
    salesData.value = chartData
    await createChart()
  } catch (error) {
    console.error('Error loading chart data:', error)
  }
}

// Cargar datos del dashboard
const loadDashboardData = async () => {
  try {
    loading.value = true
    error.value = null

    // Probar conexión primero
    await dashboardApi.testConnection()

    // Cargar todos los datos
    const [statsData, salesChartData, topProductsData, recentOrdersData] = await Promise.all([
      dashboardApi.getStats(),
      dashboardApi.getSalesChart(chartPeriod.value),
      dashboardApi.getTopProducts(),
      dashboardApi.getRecentOrders()
    ])

    stats.value = statsData
    salesData.value = salesChartData
    topProducts.value = topProductsData
    recentOrders.value = recentOrdersData

    // Crear el gráfico después de cargar los datos
    await nextTick()
    
    // Usar setTimeout para asegurar que el canvas esté listo
    setTimeout(() => {
      createChart()
    }, 100)

    console.log('Datos cargados correctamente')

  } catch (err: any) {
    console.error('Error loading dashboard:', err)
    error.value = 'Error al cargar los datos del dashboard. Por favor, verifica que el servidor Laravel esté ejecutándose.'
  } finally {
    loading.value = false
  }
}

// Lifecycle hooks
onMounted(() => {
  loadDashboardData()
})

// Watcher para recrear el gráfico cuando cambien los datos
watch(salesData, (newData) => {
  if (newData && newData.length > 0) {
    setTimeout(() => {
      createChart()
    }, 100)
  }
}, { deep: true })

onUnmounted(() => {
  // Limpiar el gráfico al desmontar el componente
  if (chartInstance) {
    chartInstance.destroy()
  }
})
</script>

<style scoped>
.spinner {
  border: 2px solid #f3f4f6;
  border-top: 2px solid #3b82f6;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.stat-card {
  transition: all 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
}

/* Responsive table */
@media (max-width: 640px) {
  table {
    font-size: 0.875rem;
  }
}
</style>
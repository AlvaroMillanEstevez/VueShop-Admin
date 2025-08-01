<template>
  <div class="p-6">
    <!-- Header -->
    <div class="mb-8 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Customers</h1>
        <p class="text-gray-600">Manage all customers in the system</p>
      </div>
      <button 
        v-if="canCreate"
        @click="showCreateModal = true"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2"
      >
        <PlusIcon />
        <span>New Customer</span>
      </button>
    </div>

    <!-- Error Display -->
    <ErrorAlert v-if="error" :message="error" @retry="loadCustomers" />

    <!-- Access Denied -->
    <AccessDenied v-if="!hasAccess" />

    <template v-else>
      <!-- Filters -->
      <div class="mb-6 flex flex-col sm:flex-row gap-4">
        <SearchInput 
          v-model="searchQuery" 
          @input="debounceSearch"
          placeholder="Search customers by name, email, or phone..."
        />
        <FilterSelect 
          v-model="orderFilter"
          @change="loadCustomers()"
          :options="[
            { value: '', label: 'All Customers' },
            { value: 'true', label: 'With Orders' },
            { value: 'false', label: 'Without Orders' }
          ]"
        />
      </div>

      <!-- Loading State -->
      <LoadingSpinner v-if="loading" />

      <!-- Customers Table -->
      <CustomersTable 
        v-else-if="!error"
        :customers="customers"
        :pagination="pagination"
        @view="handleView"
        @edit="handleEdit"
        @delete="handleDelete"
        @page-change="changePage"
      />

      <!-- Empty State -->
      <CustomersEmptyState 
        v-if="!loading && !error && customers.length === 0"
        :has-search="!!searchQuery"
        :can-create="!!canCreate"
        @create="showCreateModal = true"
      />
    </template>

    <!-- Modals -->
    <CustomerModal 
      v-if="showModal"
      :customer="selectedCustomer"
      :is-editing="isEditingLocal"
      :saving="saving"
      :form="customerForm"
      @close="closeModal"
      @save="handleSave"
      @edit="startEdit"
    />

    <CreateCustomerModal
      v-if="showCreateModal"
      :saving="saving"
      :form="customerForm"
      @close="closeCreateModal"
      @create="handleCreate"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useCustomers, type Customer } from '@/composables/useCustomers'
import PlusIcon from '@/components/icons/PlusIcon.vue'
import ErrorAlert from '@/components/ui/ErrorAlert.vue'
import AccessDenied from '@/components/ui/AccessDenied.vue'
import SearchInput from '@/components/ui/SearchInput.vue'
import FilterSelect from '@/components/ui/FilterSelect.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import CustomersTable from '@/components/customers/CustomersTable.vue'
import CustomersEmptyState from '@/components/customers/CustomersEmptyState.vue'
import CustomerModal from '@/components/customers/CustomerModal.vue'
import CreateCustomerModal from '@/components/customers/CreateCustomerModal.vue'

// Composable
const {
  loading,
  saving,
  customers,
  pagination,
  error,
  searchQuery,
  orderFilter,
  customerForm,
  hasAccess,
  canCreate,
  loadCustomers,
  getCustomer,
  createCustomer,
  updateCustomer,
  deleteCustomer,
  debounceSearch,
  changePage,
  resetForm,
  populateForm
} = useCustomers()

// Local state
const showModal = ref(false)
const showCreateModal = ref(false)
const selectedCustomer = ref<Customer | null>(null)
const isEditingLocal = ref(false) // Cambiado de isEditing a isEditingLocal

// Event handlers
const handleView = async (customer: Customer) => {
  const detailedCustomer = await getCustomer(customer.id)
  selectedCustomer.value = detailedCustomer || customer
  isEditingLocal.value = false
  showModal.value = true
}

const handleEdit = (customer: Customer) => {
  selectedCustomer.value = customer
  populateForm(customer)
  isEditingLocal.value = true
  showModal.value = true
}

const startEdit = (customer: Customer) => {
  populateForm(customer)
  isEditingLocal.value = true
}

const handleSave = async () => {
  if (!selectedCustomer.value) return
  
  const success = await updateCustomer(selectedCustomer.value.id, customerForm)
  if (success) {
    selectedCustomer.value = { ...selectedCustomer.value, ...customerForm }
    isEditingLocal.value = false
  }
}

const handleCreate = async () => {
  const success = await createCustomer()
  if (success) {
    closeCreateModal()
  }
}

const handleDelete = async (customer: Customer) => {
  if (!confirm(`Are you sure you want to delete customer "${customer.name}"? This action cannot be undone.`)) {
    return
  }
  
  const success = await deleteCustomer(customer.id)
  if (!success && error.value) {
    alert(error.value)
  }
}

const closeModal = () => {
  showModal.value = false
  selectedCustomer.value = null
  isEditingLocal.value = false
}

const closeCreateModal = () => {
  showCreateModal.value = false
  resetForm()
}

// Initialize
onMounted(() => {
  if (hasAccess.value) {
    loadCustomers()
  }
})
</script>
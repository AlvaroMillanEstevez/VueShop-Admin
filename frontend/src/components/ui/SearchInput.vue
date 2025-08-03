<template>
  <div class="flex-1">
    <input 
      :value="modelValue"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      type="text" 
      :placeholder="placeholder"
      class="search-input"
    >
  </div>
</template>

<script setup lang="ts">
interface Props {
  modelValue: string
  placeholder?: string
}

defineProps<Props>()
defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>
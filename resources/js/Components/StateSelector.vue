<template>
  <div>
    <label v-if="label" :for="id" class="block text-sm font-medium text-gray-700">
      {{ label }}
    </label>
    <select
      :id="id"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      :disabled="disabled || loading"
      :class="[
        'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm',
        disabled || loading ? 'bg-gray-100 cursor-not-allowed' : '',
        error ? 'border-red-500' : ''
      ]"
    >
      <option value="">
        {{ loading ? 'Loading states...' : placeholder }}
      </option>
      <option
        v-for="state in states"
        :key="state.id"
        :value="state.id"
      >
        {{ state.name }}
      </option>
    </select>
    <div v-if="error" class="mt-2 text-sm text-red-600">
      {{ error }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  label: {
    type: String,
    default: 'State'
  },
  placeholder: {
    type: String,
    default: 'Select State'
  },
  id: {
    type: String,
    default: 'state_id'
  },
  disabled: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: ''
  },
  autoLoad: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update:modelValue', 'change', 'loaded'])

const states = ref([])
const loading = ref(false)

const loadStates = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/states')
    states.value = response.data
    emit('loaded', states.value)
  } catch (error) {
    console.error('Error loading states:', error)
    emit('loaded', [])
  } finally {
    loading.value = false
  }
}

// Watch for changes in modelValue to emit change event
watch(() => props.modelValue, (newValue, oldValue) => {
  if (newValue !== oldValue) {
    const selectedState = states.value.find(state => state.id == newValue)
    emit('change', newValue, selectedState)
  }
})

// Auto-load states on mount if enabled
onMounted(() => {
  if (props.autoLoad) {
    loadStates()
  }
})

// Expose methods for parent components
defineExpose({
  loadStates,
  states,
  loading
})
</script>
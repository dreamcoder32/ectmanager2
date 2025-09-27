<template>
  <div>
    <label v-if="label" :for="id" class="block text-sm font-medium text-gray-700">
      {{ label }}
    </label>
    <select
      :id="id"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      :disabled="disabled || loading || !stateId"
      :class="[
        'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm',
        disabled || loading || !stateId ? 'bg-gray-100 cursor-not-allowed' : '',
        error ? 'border-red-500' : ''
      ]"
    >
      <option value="">
        {{ getPlaceholderText() }}
      </option>
      <option
        v-for="city in cities"
        :key="city.id"
        :value="city.id"
      >
        {{ city.name }}
      </option>
    </select>
    <div v-if="error" class="mt-2 text-sm text-red-600">
      {{ error }}
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  stateId: {
    type: [String, Number],
    default: ''
  },
  label: {
    type: String,
    default: 'City'
  },
  placeholder: {
    type: String,
    default: 'Select City'
  },
  noStateText: {
    type: String,
    default: 'Select state first'
  },
  id: {
    type: String,
    default: 'city_id'
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

const cities = ref([])
const loading = ref(false)

const getPlaceholderText = () => {
  if (loading.value) return 'Loading cities...'
  if (!props.stateId) return props.noStateText
  return props.placeholder
}

const loadCities = async (stateId) => {
  if (!stateId) {
    cities.value = []
    emit('loaded', [])
    return
  }

  loading.value = true
  try {
    const response = await axios.get(`/api/states/${stateId}/cities`)
    cities.value = response.data
    emit('loaded', cities.value)
  } catch (error) {
    console.error('Error loading cities:', error)
    cities.value = []
    emit('loaded', [])
  } finally {
    loading.value = false
  }
}

// Watch for changes in stateId to load cities
watch(() => props.stateId, (newStateId, oldStateId) => {
  if (newStateId !== oldStateId) {
    // Clear current selection when state changes
    if (props.modelValue) {
      emit('update:modelValue', '')
    }
    
    if (props.autoLoad) {
      loadCities(newStateId)
    }
  }
}, { immediate: true })

// Watch for changes in modelValue to emit change event
watch(() => props.modelValue, (newValue, oldValue) => {
  if (newValue !== oldValue) {
    const selectedCity = cities.value.find(city => city.id == newValue)
    emit('change', newValue, selectedCity)
  }
})

// Expose methods for parent components
defineExpose({
  loadCities,
  cities,
  loading
})
</script>
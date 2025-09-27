<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <StateSelector
      v-model="selectedState"
      :label="stateLabel"
      :placeholder="statePlaceholder"
      :id="stateId"
      :disabled="disabled"
      :error="stateError"
      @change="onStateChange"
      @loaded="onStatesLoaded"
    />
    
    <CitySelector
      v-model="selectedCity"
      :state-id="selectedState"
      :label="cityLabel"
      :placeholder="cityPlaceholder"
      :no-state-text="noStateText"
      :id="cityId"
      :disabled="disabled"
      :error="cityError"
      @change="onCityChange"
      @loaded="onCitiesLoaded"
    />
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import StateSelector from './StateSelector.vue'
import CitySelector from './CitySelector.vue'

const props = defineProps({
  stateValue: {
    type: [String, Number],
    default: ''
  },
  cityValue: {
    type: [String, Number],
    default: ''
  },
  stateLabel: {
    type: String,
    default: 'State'
  },
  cityLabel: {
    type: String,
    default: 'City'
  },
  statePlaceholder: {
    type: String,
    default: 'Select State'
  },
  cityPlaceholder: {
    type: String,
    default: 'Select City'
  },
  noStateText: {
    type: String,
    default: 'Select state first'
  },
  stateId: {
    type: String,
    default: 'state_id'
  },
  cityId: {
    type: String,
    default: 'city_id'
  },
  disabled: {
    type: Boolean,
    default: false
  },
  stateError: {
    type: String,
    default: ''
  },
  cityError: {
    type: String,
    default: ''
  }
})

const emit = defineEmits([
  'update:stateValue',
  'update:cityValue',
  'stateChange',
  'cityChange',
  'statesLoaded',
  'citiesLoaded'
])

const selectedState = ref(props.stateValue)
const selectedCity = ref(props.cityValue)

// Watch for external prop changes
watch(() => props.stateValue, (newValue) => {
  selectedState.value = newValue
})

watch(() => props.cityValue, (newValue) => {
  selectedCity.value = newValue
})

// Watch for internal changes and emit updates
watch(selectedState, (newValue) => {
  emit('update:stateValue', newValue)
})

watch(selectedCity, (newValue) => {
  emit('update:cityValue', newValue)
})

const onStateChange = (stateId, stateData) => {
  selectedState.value = stateId
  // Clear city selection when state changes
  selectedCity.value = ''
  emit('stateChange', stateId, stateData)
}

const onCityChange = (cityId, cityData) => {
  selectedCity.value = cityId
  emit('cityChange', cityId, cityData)
}

const onStatesLoaded = (states) => {
  emit('statesLoaded', states)
}

const onCitiesLoaded = (cities) => {
  emit('citiesLoaded', cities)
}

// Computed properties for easy access
const state = computed(() => selectedState.value)
const city = computed(() => selectedCity.value)

// Expose values and methods for parent components
defineExpose({
  state,
  city,
  selectedState,
  selectedCity,
  clearSelection: () => {
    selectedState.value = ''
    selectedCity.value = ''
  }
})
</script>
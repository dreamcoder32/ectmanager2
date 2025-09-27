<template>
  <div class="min-h-screen d-flex align-center justify-center" 
       style="background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);">
    
    <!-- Background decorative elements -->
    <div class="position-absolute w-100 h-100 overflow-hidden">
      <div class="position-absolute" 
           style="top: 10%; left: 10%; width: 200px; height: 200px; 
                  background: rgba(255,255,255,0.1); 
                  border-radius: 50%; 
                  filter: blur(40px);"></div>
      <div class="position-absolute" 
           style="bottom: 20%; right: 15%; width: 150px; height: 150px; 
                  background: rgba(255,255,255,0.08); 
                  border-radius: 50%; 
                  filter: blur(30px);"></div>
    </div>

    <v-card class="mx-auto position-relative" 
            max-width="450px" 
            elevation="24"
            style="backdrop-filter: blur(10px); 
                   background: rgba(255,255,255,0.95);
                   border-radius: 20px;
                   border: 1px solid rgba(255,255,255,0.2);">
      
      <!-- Header with gradient -->
      <div class="text-center pa-8 pb-6" 
           style="background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
                  border-radius: 20px 20px 0 0;
                  position: relative;
                  overflow: hidden;">
        
        <!-- Header decorative element -->
        <div class="position-absolute" 
             style="top: -50px; right: -50px; width: 100px; height: 100px; 
                    background: rgba(255,255,255,0.1); 
                    border-radius: 50%;"></div>
        
        <div class="d-flex align-center justify-center mb-4">
          <v-icon size="48" color="white" class="mr-3">mdi-truck-delivery</v-icon>
          <div>
            <h1 class="text-h4 font-weight-bold text-white mb-1">DeliveryPro</h1>
            <p class="text-body-2 text-white opacity-90 mb-0">{{ $t('auth.welcome_back') }}</p>
          </div>
        </div>
        
        <p class="text-body-1 text-white opacity-80 mb-0">{{ $t('auth.sign_in_account') }}</p>
      </div>
      
      <v-card-text class="pa-8">
        <v-form @submit.prevent="submit" ref="formRef">
          <v-text-field
            v-model="form.email"
            :label="$t('auth.email')"
            type="email"
            variant="outlined"
            :error-messages="form.errors.email"
            prepend-inner-icon="mdi-email"
            required
            class="mb-4"
            color="primary"
            style="--v-field-border-radius: 12px;"
          />
          
          <v-text-field
            v-model="form.password"
            :label="$t('auth.password')"
            :type="showPassword ? 'text' : 'password'"
            variant="outlined"
            :error-messages="form.errors.password"
            prepend-inner-icon="mdi-lock"
            :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
            @click:append-inner="showPassword = !showPassword"
            required
            class="mb-4"
            color="primary"
            style="--v-field-border-radius: 12px;"
          />
          
          <div class="d-flex align-center justify-space-between mb-6">
            <v-checkbox
              v-model="form.remember"
              :label="$t('auth.remember')"
              color="primary"
              density="compact"
              hide-details
            />
            
            <v-btn
              variant="text"
              color="primary"
              size="small"
              class="text-decoration-none"
            >
              {{ $t('auth.forgot_password') }}
            </v-btn>
          </div>
          
          <v-btn
            type="submit"
            block
            size="large"
            :loading="form.processing"
            :disabled="form.processing"
            class="mb-4 text-white font-weight-bold"
            style="background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
                   border-radius: 12px;
                   text-transform: none;
                   letter-spacing: 0.5px;
                   box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);"
            elevation="0"
          >
            <v-icon left class="mr-2">mdi-login</v-icon>
            {{ $t('auth.login') }}
          </v-btn>
        </v-form>
        
        <!-- Footer -->
        <div class="text-center mt-6">
          <p class="text-body-2 text-grey-darken-1 mb-0">
            © 2024 DeliveryPro. Professional delivery management system.
          </p>
        </div>
      </v-card-text>
    </v-card>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const showPassword = ref(false)
const formRef = ref(null)

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  })
}
</script>

<style scoped>
/* Custom animations */
.v-card {
  animation: slideUp 0.6s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Hover effects */
.v-btn:hover {
  transform: translateY(-2px);
  transition: all 0.3s ease;
}

/* Focus states */
.v-text-field:focus-within {
  transform: translateY(-2px);
  transition: all 0.3s ease;
}
</style>

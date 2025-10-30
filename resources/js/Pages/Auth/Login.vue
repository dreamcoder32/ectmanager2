<template>
  <div class="min-h-screen d-flex align-center justify-center" 
       style="background: linear-gradient(135deg, #1a1d29 0%, #2c3142 100%);">
    
    <!-- Background decorative elements -->
    <div class="position-absolute w-100 h-100 overflow-hidden">
      <div class="position-absolute" 
           style="top: 10%; left: 10%; width: 250px; height: 250px; 
                  background: radial-gradient(circle, rgba(102, 126, 234, 0.15) 0%, transparent 70%); 
                  border-radius: 50%; 
                  filter: blur(40px);"></div>
      <div class="position-absolute" 
           style="bottom: 20%; right: 15%; width: 200px; height: 200px; 
                  background: radial-gradient(circle, rgba(118, 75, 162, 0.12) 0%, transparent 70%); 
                  border-radius: 50%; 
                  filter: blur(30px);"></div>
      <div class="position-absolute" 
           style="top: 50%; left: 50%; width: 300px; height: 300px; 
                  background: radial-gradient(circle, rgba(102, 126, 234, 0.08) 0%, transparent 70%); 
                  border-radius: 50%; 
                  filter: blur(60px);
                  transform: translate(-50%, -50%);"></div>
    </div>

    <v-card class="mx-auto position-relative" 
            max-width="480px" 
            elevation="24"
            style="backdrop-filter: blur(20px); 
                   background: rgba(42, 45, 60, 0.95);
                   border-radius: 24px;
                   border: 1px solid rgba(255,255,255,0.08);
                   box-shadow: 0 20px 60px rgba(0,0,0,0.5);">
      
      <!-- Header with gradient -->
      <div class="text-center pa-8 pb-6" 
           style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                  border-radius: 24px 24px 0 0;
                  position: relative;
                  overflow: hidden;">
        
        <!-- Header decorative elements -->
        <div class="position-absolute" 
             style="top: -50px; right: -50px; width: 120px; height: 120px; 
                    background: rgba(255,255,255,0.15); 
                    border-radius: 50%;"></div>
        <div class="position-absolute" 
             style="bottom: -30px; left: -30px; width: 80px; height: 80px; 
                    background: rgba(255,255,255,0.1); 
                    border-radius: 50%;"></div>
        
        <div class="d-flex align-center justify-center mb-4" style="position: relative; z-index: 1;">
          <v-avatar size="56" 
                    class="mr-3"
                    style="background: rgba(255,255,255,0.2);
                           box-shadow: 0 8px 24px rgba(0,0,0,0.2);">
            <v-icon size="32" color="white">mdi-truck-delivery</v-icon>
          </v-avatar>
          <div class="text-left">
            <h1 class="text-h4 font-weight-bold text-white mb-1">ECTManager</h1>
            <p class="text-body-2 text-white" style="opacity: 0.95; font-weight: 500;">{{ $t('auth.welcome_back') }}</p>
          </div>
        </div>
        
        <p class="text-body-1 text-white mb-0" style="opacity: 0.9; position: relative; z-index: 1;">{{ $t('auth.sign_in_account') }}</p>
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
            class="mb-4 login-input"
            color="#667eea"
            base-color="rgba(255,255,255,0.5)"
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
            class="mb-4 login-input"
            color="#667eea"
            base-color="rgba(255,255,255,0.5)"
            style="--v-field-border-radius: 12px;"
          />
          
          <div class="d-flex align-center justify-space-between mb-6">
            <v-checkbox
              v-model="form.remember"
              :label="$t('auth.remember')"
              color="#667eea"
              density="compact"
              hide-details
              class="text-grey-lighten-1"
            />
            
            <v-btn
              variant="text"
              size="small"
              class="text-decoration-none"
              style="color: #667eea; text-transform: none;"
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
            class="mb-4 text-white font-weight-bold login-button"
            style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                   border-radius: 12px;
                   text-transform: none;
                   letter-spacing: 0.5px;
                   box-shadow: 0 8px 32px rgba(102, 126, 234, 0.4);"
            elevation="0"
          >
            <v-icon left class="mr-2">mdi-login</v-icon>
            {{ $t('auth.login') }}
          </v-btn>
        </v-form>
        
        <!-- Footer -->
        <div class="text-center mt-6">
          <p class="text-body-2 text-grey-lighten-1 mb-2">
            <v-icon size="20" color="#667eea" class="mr-1">mdi-truck-delivery</v-icon>
            Professional Delivery Management System
          </p>
          <p class="text-caption" style="color: rgba(255,255,255,0.5); font-weight: 500;">
            © 2025 ECTManager. All rights reserved.
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
  animation: slideUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Login input styling */
:deep(.login-input .v-field) {
  background: rgba(255, 255, 255, 0.05);
  border-color: rgba(255, 255, 255, 0.15);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

:deep(.login-input .v-field:hover) {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(102, 126, 234, 0.4);
  transform: translateY(-2px);
}

:deep(.login-input .v-field--focused) {
  background: rgba(255, 255, 255, 0.1);
  border-color: #667eea;
  box-shadow: 0 4px 20px rgba(102, 126, 234, 0.2);
}

:deep(.login-input .v-label) {
  color: rgba(255, 255, 255, 0.7);
}

:deep(.login-input .v-field__input) {
  color: white;
}

:deep(.login-input .v-icon) {
  color: rgba(255, 255, 255, 0.6);
}

/* Login button hover effects */
.login-button {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.login-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 40px rgba(102, 126, 234, 0.5) !important;
}

.login-button:active {
  transform: translateY(0);
}

/* Checkbox styling */
:deep(.v-checkbox .v-label) {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.9rem;
}

/* Background decorative animation */
@keyframes float {
  0%, 100% {
    transform: translate(0, 0);
  }
  50% {
    transform: translate(20px, 20px);
  }
}

.position-absolute > div:first-child {
  animation: float 8s ease-in-out infinite;
}

.position-absolute > div:nth-child(2) {
  animation: float 10s ease-in-out infinite reverse;
}

.position-absolute > div:nth-child(3) {
  animation: float 12s ease-in-out infinite;
}
</style>


<template>
  <v-menu>
    <template v-slot:activator="{ props }">
      <v-btn
        icon
        v-bind="props"
        class="language-switcher-btn"
        style="background: rgba(102, 126, 234, 0.1);
               border: 1px solid rgba(102, 126, 234, 0.2);
               border-radius: 8px;
               transition: all 0.3s ease;"
      >
        <v-icon color="primary" size="20">mdi-translate</v-icon>
      </v-btn>
    </template>
    
    <v-list 
      style="border-radius: 12px; 
             box-shadow: 0 8px 32px rgba(0,0,0,0.1);
             border: 1px solid rgba(0,0,0,0.05);
             min-width: 160px;"
    >
      <v-list-item
        v-for="language in languages"
        :key="language.code"
        @click="changeLanguage(language.code)"
        :class="{ 'v-list-item--active': currentLocale === language.code }"
        style="border-radius: 8px; 
               margin: 4px;
               transition: all 0.2s ease;"
      >
        <v-list-item-avatar class="mr-3">
          <v-icon :color="currentLocale === language.code ? 'primary' : 'grey'" size="20">
            {{ language.icon }}
          </v-icon>
        </v-list-item-avatar>
        <v-list-item-title 
          :class="{ 'text-primary font-weight-bold': currentLocale === language.code }"
        >
          {{ language.name }}
        </v-list-item-title>
        <v-list-item-subtitle class="text-caption">
          {{ language.nativeName }}
        </v-list-item-subtitle>
        <template v-slot:append v-if="currentLocale === language.code">
          <v-icon color="primary" size="16">mdi-check</v-icon>
        </template>
      </v-list-item>
    </v-list>
  </v-menu>
</template>

<script>
import { useI18n } from 'vue-i18n'

export default {
  name: 'LanguageSwitcher',
  
  setup() {
    const { locale, t } = useI18n()
    return { locale, t }
  },
  
  data() {
    return {
      languages: [
        {
          code: 'en',
          name: 'English',
          nativeName: 'English',
          icon: 'mdi-flag'
        },
        {
          code: 'fr',
          name: 'French',
          nativeName: 'Français',
          icon: 'mdi-flag'
        },
        {
          code: 'ar',
          name: 'Arabic',
          nativeName: 'العربية',
          icon: 'mdi-flag'
        }
      ]
    }
  },
  
  computed: {
    currentLocale() {
      return this.locale
    }
  },
  
  methods: {
    changeLanguage(newLocale) {
      console.log('Language change clicked:', newLocale)
      console.log('Current locale:', this.locale)
      
      if (newLocale !== this.locale) {
        console.log('Changing language to:', newLocale)
        this.locale = newLocale
        
        // Store the selected language in localStorage for persistence
        localStorage.setItem('selectedLanguage', newLocale)
        
        // Emit event to parent components if needed
        this.$emit('language-changed', newLocale)
        
        // Optional: Show a success message (if toast is available)
        console.log(this.t('common.languageChanged'))
      } else {
        console.log('Language already selected:', newLocale)
      }
    }
  },
  
  mounted() {
    // Load saved language preference on component mount
    const savedLanguage = localStorage.getItem('selectedLanguage')
    if (savedLanguage && savedLanguage !== this.locale) {
      this.changeLanguage(savedLanguage)
    }
  }
}
</script>

<style scoped>
.language-switcher-btn:hover {
  background: rgba(102, 126, 234, 0.15) !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.v-list-item:hover {
  background: rgba(102, 126, 234, 0.05) !important;
}

.v-list-item--active {
  background: rgba(102, 126, 234, 0.1) !important;
}

.v-list-item--active:hover {
  background: rgba(102, 126, 234, 0.15) !important;
}
</style>
import { createVuetify } from 'vuetify'
import { aliases, mdi } from 'vuetify/iconsets/mdi'
import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'

const vuetify = createVuetify({
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        colors: {
          primary: '#1976D2',
          secondary: '#424242',
          accent: '#82B1FF',
          error: '#FF5252',
          info: '#2196F3',
          success: '#4CAF50',
          warning: '#FFC107',
          background: '#FAFAFA',
          surface: '#FFFFFF',
        },
      },
      dark: {
        colors: {
          primary: '#2196F3',
          secondary: '#424242',
          accent: '#FF4081',
          error: '#FF5252',
          info: '#2196F3',
          success: '#4CAF50',
          warning: '#FB8C00',
          background: '#121212',
          surface: '#1E1E1E',
        },
      },
    },
  },
  icons: {
    defaultSet: 'mdi',
    aliases,
    sets: {
      mdi,
    },
  },
  defaults: {
    VCard: {
      elevation: 2,
    },
    VBtn: {
      elevation: 2,
    },
    VSelect: {
      variant: 'outlined',
      density: 'comfortable',
      hideDetails: 'auto',
      menuProps: {
        offsetY: true,
        maxHeight: 300,
        transition: 'scale-transition',
        contentClass: 'custom-dropdown-menu'
      }
    },
    VAutocomplete: {
      variant: 'outlined',
      density: 'comfortable',
      hideDetails: 'auto',
      menuProps: {
        offsetY: true,
        maxHeight: 300,
        transition: 'scale-transition',
        contentClass: 'custom-dropdown-menu'
      }
    },
    VMenu: {
      transition: 'scale-transition',
      contentClass: 'custom-dropdown-menu'
    },
    VList: {
      density: 'comfortable'
    }
  },
})

export default vuetify
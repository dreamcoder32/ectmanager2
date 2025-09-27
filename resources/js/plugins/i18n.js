import { createI18n } from 'vue-i18n'

const messages = {
  en: {
    navigation: {
      dashboard: 'Dashboard',
      parcels: 'Parcels',
      reports: 'Reports',
      settings: 'Settings',
      profile: 'Profile',
      logout: 'Log Out'
    },
    dashboard: {
      title: 'Dashboard',
      recent_parcels: 'Recent Parcels',
      total_parcels: 'Total Parcels',
      pending_parcels: 'Pending Parcels',
      delivered_parcels: 'Delivered Parcels',
      revenue: 'Revenue'
    },
    parcels: {
      title: 'Parcels',
      create: 'Create Parcel',
      edit: 'Edit Parcel',
      show: 'View Parcel',
      tracking_number: 'Tracking Number',
      sender_name: 'Sender Name',
      receiver_name: 'Receiver Name',
      receiver_phone: 'Receiver Phone',
      state: 'State',
      city: 'City',
      status: 'Status',
      actions: 'Actions',
      search: 'Search parcels...',
      status_pending: 'Pending',
      status_picked_up: 'Picked Up',
      status_in_transit: 'In Transit',
      status_out_for_delivery: 'Out for Delivery',
      status_delivered: 'Delivered',
      status_returned: 'Returned',
      status_cancelled: 'Cancelled'
    },
    common: {
      save: 'Save',
      cancel: 'Cancel',
      delete: 'Delete',
      edit: 'Edit',
      view: 'View',
      search: 'Search',
      filter: 'Filter',
      export: 'Export',
      import: 'Import',
      clear: 'Clear',
      version: 'Version',
      languageChanged: 'Language changed successfully'
    },
    auth: {
      login: 'Login',
      email: 'Email',
      password: 'Password',
      remember: 'Remember me',
      forgot_password: 'Forgot your password?',
      welcome_back: 'Welcome back',
      sign_in_account: 'Sign in to your account'
    },
    financial_dashboard: {
      title: 'Financial Dashboard',
      total_revenue: 'Total Revenue',
      monthly_revenue: 'Monthly Revenue',
      pending_payments: 'Pending Payments',
      completed_payments: 'Completed Payments'
    }
  },
  fr: {
    navigation: {
      dashboard: 'Tableau de bord',
      parcels: 'Colis',
      reports: 'Rapports',
      settings: 'Paramètres',
      profile: 'Profil',
      logout: 'Se déconnecter'
    },
    dashboard: {
      title: 'Tableau de bord',
      recent_parcels: 'Colis récents',
      total_parcels: 'Total des colis',
      pending_parcels: 'Colis en attente',
      delivered_parcels: 'Colis livrés',
      revenue: 'Revenus'
    },
    parcels: {
      title: 'Colis',
      create: 'Créer un colis',
      edit: 'Modifier le colis',
      show: 'Voir le colis',
      tracking_number: 'Numéro de suivi',
      sender_name: 'Nom de l\'expéditeur',
      receiver_name: 'Nom du destinataire',
      receiver_phone: 'Téléphone du destinataire',
      state: 'État',
      city: 'Ville',
      status: 'Statut',
      actions: 'Actions',
      search: 'Rechercher des colis...',
      status_pending: 'En attente',
      status_picked_up: 'Collecté',
      status_in_transit: 'En transit',
      status_out_for_delivery: 'En cours de livraison',
      status_delivered: 'Livré',
      status_returned: 'Retourné',
      status_cancelled: 'Annulé'
    },
    common: {
      save: 'Enregistrer',
      cancel: 'Annuler',
      delete: 'Supprimer',
      edit: 'Modifier',
      view: 'Voir',
      search: 'Rechercher',
      filter: 'Filtrer',
      export: 'Exporter',
      import: 'Importer',
      clear: 'Effacer',
      version: 'Version',
      languageChanged: 'Langue changée avec succès'
    },
    auth: {
      login: 'Connexion',
      email: 'Email',
      password: 'Mot de passe',
      remember: 'Se souvenir de moi',
      forgot_password: 'Mot de passe oublié?',
      welcome_back: 'Bon retour',
      sign_in_account: 'Connectez-vous à votre compte'
    },
    financial_dashboard: {
      title: 'Tableau de bord financier',
      total_revenue: 'Revenus totaux',
      monthly_revenue: 'Revenus mensuels',
      pending_payments: 'Paiements en attente',
      completed_payments: 'Paiements terminés'
    }
  },
  ar: {
    navigation: {
      dashboard: 'لوحة التحكم',
      parcels: 'الطرود',
      reports: 'التقارير',
      settings: 'الإعدادات',
      profile: 'الملف الشخصي',
      logout: 'تسجيل الخروج'
    },
    dashboard: {
      title: 'لوحة التحكم',
      recent_parcels: 'الطرود الأخيرة',
      total_parcels: 'إجمالي الطرود',
      pending_parcels: 'الطرود المعلقة',
      delivered_parcels: 'الطرود المسلمة',
      revenue: 'الإيرادات'
    },
    parcels: {
      title: 'الطرود',
      create: 'إنشاء طرد',
      edit: 'تعديل الطرد',
      show: 'عرض الطرد',
      tracking_number: 'رقم التتبع',
      sender_name: 'اسم المرسل',
      receiver_name: 'اسم المستلم',
      receiver_phone: 'هاتف المستلم',
      state: 'الولاية',
      city: 'المدينة',
      status: 'الحالة',
      actions: 'الإجراءات',
      search: 'البحث في الطرود...',
      status_pending: 'معلق',
      status_picked_up: 'تم الاستلام',
      status_in_transit: 'في الطريق',
      status_out_for_delivery: 'خارج للتسليم',
      status_delivered: 'تم التسليم',
      status_returned: 'مُرجع',
      status_cancelled: 'ملغي'
    },
    common: {
      save: 'حفظ',
      cancel: 'إلغاء',
      delete: 'حذف',
      edit: 'تعديل',
      view: 'عرض',
      search: 'بحث',
      filter: 'تصفية',
      export: 'تصدير',
      import: 'استيراد',
      clear: 'مسح',
      version: 'الإصدار',
      languageChanged: 'تم تغيير اللغة بنجاح'
    },
    auth: {
      login: 'تسجيل الدخول',
      email: 'البريد الإلكتروني',
      password: 'كلمة المرور',
      remember: 'تذكرني',
      forgot_password: 'نسيت كلمة المرور؟',
      welcome_back: 'مرحباً بعودتك',
      sign_in_account: 'سجل الدخول إلى حسابك'
    },
    financial_dashboard: {
      title: 'لوحة التحكم المالية',
      total_revenue: 'إجمالي الإيرادات',
      monthly_revenue: 'الإيرادات الشهرية',
      pending_payments: 'المدفوعات المعلقة',
      completed_payments: 'المدفوعات المكتملة'
    }
  }
}

const i18n = createI18n({
  locale: 'en',
  fallbackLocale: 'en',
  messages
})

export default i18n
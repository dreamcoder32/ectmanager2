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
    time: {
      just_now: 'just now',
      minutes_ago: '{count} minute ago | {count} minutes ago',
      hours_ago: '{count} hour ago | {count} hours ago',
      days_ago: '{count} day ago | {count} days ago',
      weeks_ago: '{count} week ago | {count} weeks ago',
      months_ago: '{count} month ago | {count} months ago',
      years_ago: '{count} year ago | {count} years ago'
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
    },
    stopdesk_payment: {
      barcode_scanner: 'Barcode Scanner',
      scan_or_type: 'Scan or type tracking number',
      search: 'SEARCH',
      pending_payments: 'Pending Payments',
      no_parcels_queue: 'No parcels in queue',
      scan_barcode_add: 'Scan a barcode to add parcels',
      recipient_name: 'Recipient Name',
      recipient_phone: 'Recipient Phone',
      recipient_address: 'Recipient Address',
      company: 'Company',
      state: 'State',
      city: 'City',
      cancel: 'Cancel',
      add_to_queue: 'Add to Queue',
      amount_given: 'Amount Given',
      change: 'Change',
      remove: 'Remove',
      confirm_payment: 'Confirm Payment',
      recent_collections: 'Recent Collections',
      no_recent_collections: 'No recent collections'
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
    time: {
      just_now: 'à l\'instant',
      minutes_ago: 'il y a {count} minute | il y a {count} minutes',
      hours_ago: 'il y a {count} heure | il y a {count} heures',
      days_ago: 'il y a {count} jour | il y a {count} jours',
      weeks_ago: 'il y a {count} semaine | il y a {count} semaines',
      months_ago: 'il y a {count} mois | il y a {count} mois',
      years_ago: 'il y a {count} an | il y a {count} ans'
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
    },
    stopdesk_payment: {
      barcode_scanner: 'Scanner de codes-barres',
      scan_or_type: 'Scanner ou saisir le numéro de suivi',
      search: 'RECHERCHER',
      pending_payments: 'Paiements en attente',
      no_parcels_queue: 'Aucun colis en file d\'attente',
      scan_barcode_add: 'Scanner un code-barres pour ajouter des colis',
      recipient_name: 'Nom du destinataire',
      recipient_phone: 'Téléphone du destinataire',
      recipient_address: 'Adresse du destinataire',
      company: 'Entreprise',
      state: 'État',
      city: 'Ville',
      cancel: 'Annuler',
      add_to_queue: 'Ajouter à la file',
      amount_given: 'Montant donné',
      change: 'Monnaie',
      remove: 'Supprimer',
      confirm_payment: 'Confirmer le paiement',
      recent_collections: 'Collections récentes',
      no_recent_collections: 'Aucune collection récente'
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
    time: {
      just_now: 'الآن',
      minutes_ago: 'منذ {count} دقيقة | منذ {count} دقائق',
      hours_ago: 'منذ {count} ساعة | منذ {count} ساعات',
      days_ago: 'منذ {count} يوم | منذ {count} أيام',
      weeks_ago: 'منذ {count} أسبوع | منذ {count} أسابيع',
      months_ago: 'منذ {count} شهر | منذ {count} أشهر',
      years_ago: 'منذ {count} سنة | منذ {count} سنوات'
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
    },
    stopdesk_payment: {
      barcode_scanner: 'ماسح الباركود',
      scan_or_type: 'امسح أو اكتب رقم التتبع',
      search: 'بحث',
      pending_payments: 'المدفوعات المعلقة',
      no_parcels_queue: 'لا توجد طرود في الطابور',
      scan_barcode_add: 'امسح الباركود لإضافة الطرود',
      recipient_name: 'اسم المستلم',
      recipient_phone: 'هاتف المستلم',
      recipient_address: 'عنوان المستلم',
      company: 'الشركة',
      state: 'الولاية',
      city: 'المدينة',
      cancel: 'إلغاء',
      add_to_queue: 'إضافة إلى الطابور',
      amount_given: 'المبلغ المدفوع',
      change: 'الباقي',
      remove: 'إزالة',
      confirm_payment: 'تأكيد الدفع',
      recent_collections: 'التحصيلات الأخيرة',
      no_recent_collections: 'لا توجد تحصيلات أخيرة'
    }
  }
}

const i18n = createI18n({
  locale: 'en',
  fallbackLocale: 'en',
  messages
})

export default i18n
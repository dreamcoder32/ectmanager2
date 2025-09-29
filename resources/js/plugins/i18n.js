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
      languageChanged: 'Language changed successfully',
      currency:'Da'
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
      title: 'Stop Desk Payment',
      barcode_scanner: 'Barcode Scanner',
      scan_or_type: 'Scan or type tracking number',
      search: 'SEARCH',
      pending_payments: 'Pending Payments',
      no_parcels_queue: 'No parcels in queue',
      scan_barcode_add: 'Scan a barcode to add parcels',
      manual_parcel_entry: 'Manual Parcel Entry',
      tracking_number: 'Tracking Number',
      cod_amount: 'COD Amount (DA)',
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
      no_recent_collections: 'No recent collections',
      total_collections: 'Total Collections',
      money_case: 'Money Case',
      select_money_case: 'Select Money Case for All Collections',
      case_active: 'Case Active',
      no_case_selected: 'No Case Selected'
    },
    expenses: {
      title: 'Expense Management',
      create_new: 'Create New Expense',
      status: {
        pending: 'Pending',
        approved: 'Approved',
        paid: 'Paid',
        rejected: 'Rejected'
      },
      filter_by_status: 'Filter by Status',
      filter_by_category: 'Filter by Category',
      search_placeholder: 'Search expenses...',
      clear_filters: 'Clear Filters',
      table: {
        title: 'Title',
        amount: 'Amount',
        category: 'Category',
        status: 'Status',
        money_case: 'Money Case',
        created_by: 'Created By',
        date: 'Date'
      },
      no_category: 'No category',
      no_case_assigned: 'No case assigned',
      approve: 'Approve',
      mark_as_paid: 'Mark as Paid',
      reject: 'Reject',
      payment_method: 'Payment Method',
      payment_date: 'Payment Date',
      delete_confirmation: 'Are you sure you want to delete this expense? This action cannot be undone.',
      payment_methods: {
        cash: 'Cash',
        bank_transfer: 'Bank Transfer',
        check: 'Check',
        card: 'Card'
      }
    }
  },
  fr: {
    navigation: {
      dashboard: 'Tableau de bord',
      parcels: 'Colis',
            expenses: 'Depenses',

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
      languageChanged: 'Langue changée avec succès',
            currency:'Da'

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
      title: 'Paiement au Bureau d\'Arrêt',
      barcode_scanner: 'Scanner de Code-barres',
      scan_or_type: 'Scanner ou taper le numéro de suivi',
      search: 'RECHERCHER',
      pending_payments: 'Paiements en Attente',
      no_parcels_queue: 'Aucun colis en file d\'attente',
      scan_barcode_add: 'Scanner un code-barres pour ajouter des colis',
      manual_parcel_entry: 'Saisie Manuelle de Colis',
      tracking_number: 'Numéro de Suivi',
      cod_amount: 'Montant COD (DA)',
      recipient_name: 'Nom du Destinataire',
      recipient_phone: 'Téléphone du Destinataire',
      recipient_address: 'Adresse du Destinataire',
      company: 'Entreprise',
      state: 'État',
      city: 'Ville',
      cancel: 'Annuler',
      add_to_queue: 'Ajouter à la File',
      amount_given: 'Montant Donné',
      change: 'Monnaie',
      remove: 'Supprimer',
      confirm_payment: 'Confirmer le Paiement',
      recent_collections: 'Collections Récentes',
      no_recent_collections: 'Aucune collection récente',
      total_collections: 'Total des Collections',
      money_case: 'Caisse',
      select_money_case: 'Sélectionner une Caisse pour Toutes les Collections',
      case_active: 'Caisse Active',
      no_case_selected: 'Aucune Caisse Sélectionnée'
    },
    expenses: {
      title: 'Gestion des Dépenses',
      create_new: 'Créer une Nouvelle Dépense',
      status: {
        pending: 'En Attente',
        approved: 'Approuvé',
        paid: 'Payé',
        rejected: 'Rejeté'
      },
      filter_by_status: 'Filtrer par Statut',
      filter_by_category: 'Filtrer par Catégorie',
      search_placeholder: 'Rechercher des dépenses...',
      clear_filters: 'Effacer les Filtres',
      table: {
        title: 'Titre',
        amount: 'Montant',
        category: 'Catégorie',
        status: 'Statut',
        money_case: 'Caisse',
        created_by: 'Créé par',
        date: 'Date'
      },
      no_category: 'Aucune catégorie',
      no_case_assigned: 'Aucune caisse assignée',
      approve: 'Approuver',
      mark_as_paid: 'Marquer comme Payé',
      reject: 'Rejeter',
      payment_method: 'Méthode de Paiement',
      payment_date: 'Date de Paiement',
      delete_confirmation: 'Êtes-vous sûr de vouloir supprimer cette dépense? Cette action ne peut pas être annulée.',
      payment_methods: {
        cash: 'Espèces',
        bank_transfer: 'Virement Bancaire',
        check: 'Chèque',
        card: 'Carte'
      }
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
      languageChanged: 'تم تغيير اللغة بنجاح',
            currency:'دج'

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
      title: 'دفع مكتب التوقف',
      barcode_scanner: 'ماسح الباركود',
      scan_or_type: 'امسح أو اكتب رقم التتبع',
      search: 'بحث',
      pending_payments: 'المدفوعات المعلقة',
      no_parcels_queue: 'لا توجد طرود في الطابور',
      scan_barcode_add: 'امسح الباركود لإضافة الطرود',
      manual_parcel_entry: 'إدخال الطرد يدوياً',
      tracking_number: 'رقم التتبع',
      cod_amount: 'مبلغ الدفع عند الاستلام (دج)',
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
      no_recent_collections: 'لا توجد تحصيلات أخيرة',
      total_collections: 'إجمالي التحصيلات',
      money_case: 'صندوق المال',
      select_money_case: 'اختر صندوق المال لجميع التحصيلات',
      case_active: 'الصندوق نشط',
      no_case_selected: 'لم يتم اختيار صندوق'
    },
    expenses: {
      title: 'إدارة المصروفات',
      create_new: 'إنشاء مصروف جديد',
      status: {
        pending: 'معلق',
        approved: 'موافق عليه',
        paid: 'مدفوع',
        rejected: 'مرفوض'
      },
      filter_by_status: 'تصفية حسب الحالة',
      filter_by_category: 'تصفية حسب الفئة',
      search_placeholder: 'البحث في المصروفات...',
      clear_filters: 'مسح المرشحات',
      table: {
        title: 'العنوان',
        amount: 'المبلغ',
        category: 'الفئة',
        status: 'الحالة',
        money_case: 'صندوق المال',
        created_by: 'أنشأ بواسطة',
        date: 'التاريخ'
      },
      no_category: 'لا توجد فئة',
      no_case_assigned: 'لم يتم تعيين صندوق',
      approve: 'موافقة',
      mark_as_paid: 'تحديد كمدفوع',
      reject: 'رفض',
      payment_method: 'طريقة الدفع',
      payment_date: 'تاريخ الدفع',
      delete_confirmation: 'هل أنت متأكد من أنك تريد حذف هذا المصروف؟ لا يمكن التراجع عن هذا الإجراء.',
      payment_methods: {
        cash: 'نقداً',
        bank_transfer: 'تحويل بنكي',
        check: 'شيك',
        card: 'بطاقة'
      }
    }
  }
}

const i18n = createI18n({
  locale: 'en',
  fallbackLocale: 'en',
  messages
})

export default i18n
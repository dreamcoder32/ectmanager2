<template>
  <div class="ecotrack-status d-flex align-center">
    <v-fade-transition mode="out-in">
        <div v-if="loading" class="d-flex align-center text-caption text-grey">
             <v-progress-circular indeterminate size="12" width="2" color="primary" class="mr-2"></v-progress-circular>
             Checking EcoTrack...
        </div>
        <div v-else-if="status" class="d-flex align-center cursor-pointer" @click.stop="verify" v-tooltip:top="'Click to refresh'">
             <v-icon size="14" :color="statusColor" class="mr-1">
                {{ statusIcon }}
             </v-icon>
             <span class="text-caption font-weight-medium" :class="statusTextClass">
                {{ status }}
             </span>
             <span v-if="lastUpdated" class="text-caption text-grey ml-1" style="font-size: 0.7rem;">
                ({{ lastUpdatedFromNow }})
             </span>
        </div>
        <div v-else class="d-flex align-center cursor-pointer hover-opacity" @click.stop="verify">
             <v-icon size="14" color="grey" class="mr-1">mdi-refresh</v-icon>
             <span class="text-caption text-grey underline-dashed">
                Check EcoStatus
             </span>
        </div>
    </v-fade-transition>
  </div>
</template>

<script>
import { formatDistanceToNow } from 'date-fns'
import axios from 'axios'

export default {
  name: 'EcoTrackStatusBadge',
  props: {
    parcel: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      loading: false,
      status: this.parcel.ecotrack_status,
      updatedAt: this.parcel.ecotrack_status_updated_at
    }
  },
  computed: {
    lastUpdated() {
        return this.updatedAt;
    },
    lastUpdatedFromNow() {
        if (!this.updatedAt) return '';
        try {
            return formatDistanceToNow(new Date(this.updatedAt), { addSuffix: true });
        } catch (e) {
            return '';
        }
    },
    statusColor() {
        if (!this.status) return 'grey';
        const s = this.status.toLowerCase();
        if (s.includes('livré') || s.includes('delivered')) return 'success';
        if (s.includes('retour') || s.includes('returned')) return 'error';
        if (s.includes('livraison') || s.includes('out')) return 'info';
        return 'warning';
    },
    statusTextClass() {
        if (!this.status) return 'text-grey';
        const s = this.status.toLowerCase();
        if (s.includes('livré') || s.includes('delivered')) return 'text-success';
        if (s.includes('retour') || s.includes('returned')) return 'text-error';
        if (s.includes('livraison') || s.includes('out')) return 'text-info';
        return 'text-warning';
    },
    statusIcon() {
        if (!this.status) return 'mdi-help-circle-outline';
        const s = this.status.toLowerCase();
        if (s.includes('livré') || s.includes('delivered')) return 'mdi-check-circle';
        if (s.includes('retour') || s.includes('returned')) return 'mdi-close-circle';
        if (s.includes('livraison') || s.includes('out')) return 'mdi-truck-delivery';
        return 'mdi-clock-outline';
    }
  },
  mounted() {
    // If status is missing and parcel is not final status, maybe auto-check?
    // User requested "automated". But we must be careful with rate limits.
    // Let's only auto-check if it's completely missing or older than 24h?
    // For now, let's stick to "Check EcoStatus" button or what is already in DB.
    // However, if the user sees "Check EcoStatus", they might click it.
    // To make it automated, we can try to fetch if status is null.
    
    if (!this.status && this.parcel.tracking_number) {
        // Debounce or random delay to prevent thunder herd if many items
        // Since this is client side, let's just wait a bit.
        // Or actually, maybe only fetch if visible?
        // Let's try to fetch immediately for now if status is null.
        // this.verify(); 
    }
  },
  watch: {
    'parcel.ecotrack_status'(val) {
        this.status = val;
    },
    'parcel.ecotrack_status_updated_at'(val) {
        this.updatedAt = val;
    }
  },
  methods: {
    async verify() {
        if (this.loading) return;
        this.loading = true;
        
        try {
            const response = await axios.post(`/parcels/${this.parcel.id}/verify-ecotrack`);
            this.status = response.data.ecotrack_status;
            this.updatedAt = response.data.ecotrack_status_updated_at;
            
            // Emit up just in case parent needs to know
            this.$emit('status-updated', { 
                status: this.status, 
                updated_at: this.updatedAt 
            });
            
        } catch (error) {
            console.error('Failed to verify status', error);
        } finally {
            this.loading = false;
        }
    }
  }
}
</script>

<style scoped>
.underline-dashed {
    border-bottom: 1px dashed #9e9e9e;
}
.hover-opacity:hover {
    opacity: 0.8;
}
</style>

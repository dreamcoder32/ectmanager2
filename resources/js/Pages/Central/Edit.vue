<template>
    <CentralLayout title="Edit Tenant">
        <v-card class="elevation-1 rounded-lg mx-auto" max-width="600">
            <v-card-title class="py-4 px-6">
                <span class="text-h5 font-weight-bold">Edit Tenant: {{ tenant.id }}</span>
            </v-card-title>
            <v-divider></v-divider>
            <v-card-text class="pa-6">
                <v-form @submit.prevent="submit">
                    <v-text-field
                        v-model="tenant.id"
                        label="Tenant ID"
                        variant="outlined"
                        class="mb-4"
                        disabled
                    ></v-text-field>

                    <v-select
                        v-model="form.plan"
                        label="Plan"
                        :items="['Free', 'Basic', 'Premium']"
                        variant="outlined"
                        class="mb-4"
                        :error-messages="form.errors.plan"
                    ></v-select>

                    <v-select
                        v-model="form.billing_status"
                        label="Billing Status"
                        :items="['Active', 'Inactive', 'Suspended']"
                        variant="outlined"
                        class="mb-6"
                        :error-messages="form.errors.billing_status"
                    ></v-select>

                    <v-btn
                        type="submit"
                        color="primary"
                        block
                        size="large"
                        :loading="form.processing"
                    >
                        Update Tenant
                    </v-btn>
                </v-form>
            </v-card-text>
        </v-card>
    </CentralLayout>
</template>

<script>
import CentralLayout from "@/Layouts/CentralLayout.vue";
import { useForm } from "@inertiajs/vue3";

export default {
    components: {
        CentralLayout,
    },
    props: {
        tenant: Object,
    },
    setup(props) {
        const form = useForm({
            plan: props.tenant.plan || "Free",
            billing_status: props.tenant.billing_status || "Active",
        });

        const submit = () => {
            form.put(`/tenants/${props.tenant.id}`);
        };

        return { form, submit };
    },
};
</script>

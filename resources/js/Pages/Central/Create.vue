<template>
    <CentralLayout title="Create Tenant">
        <v-card class="elevation-1 rounded-lg mx-auto" max-width="600">
            <v-card-title class="py-4 px-6">
                <span class="text-h5 font-weight-bold">Create New Tenant</span>
            </v-card-title>
            <v-divider></v-divider>
            <v-card-text class="pa-6">
                <v-form @submit.prevent="submit">
                    <v-text-field
                        v-model="form.id"
                        label="Tenant ID"
                        hint="Unique identifier (e.g., company-name)"
                        persistent-hint
                        variant="outlined"
                        class="mb-4"
                        :error-messages="form.errors.id"
                    ></v-text-field>

                    <v-text-field
                        v-model="form.domain"
                        label="Domain"
                        hint="Full domain (e.g., company.app.com)"
                        persistent-hint
                        variant="outlined"
                        class="mb-4"
                        :error-messages="form.errors.domain"
                    ></v-text-field>

                    <v-text-field
                        v-model="form.email"
                        label="Admin Email"
                        variant="outlined"
                        class="mb-4"
                        :error-messages="form.errors.email"
                    ></v-text-field>

                    <v-text-field
                        v-model="form.password"
                        label="Admin Password"
                        type="password"
                        variant="outlined"
                        class="mb-4"
                        :error-messages="form.errors.password"
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
                        Create Tenant
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
    setup() {
        const form = useForm({
            id: "",
            domain: "",
            email: "",
            password: "",
            plan: "Free",
            billing_status: "Active",
        });

        const submit = () => {
            form.post("/tenants");
        };

        return { form, submit };
    },
};
</script>

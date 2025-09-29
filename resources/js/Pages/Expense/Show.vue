<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Expense Details
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Header with Actions -->
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">{{ expense.title }}</h3>
                                <p class="text-gray-600 mt-1" v-if="expense.description">
                                    {{ expense.description }}
                                </p>
                            </div>
                            <div class="flex space-x-2">
                                <Link
                                    :href="route('expenses.edit', expense.id)"
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                                    v-if="expense.status === 'pending'"
                                >
                                    Edit
                                </Link>
                                <Link
                                    :href="route('expenses.index')"
                                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                                >
                                    Back to List
                                </Link>
                            </div>
                        </div>

                        <!-- Main Information Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <!-- Basic Information -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h4>
                                <div class="space-y-3">
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Amount:</span>
                                        <p class="text-lg font-bold text-gray-900">
                                            {{ formatCurrency(expense.amount, expense.currency) }}
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Category:</span>
                                        <p class="text-gray-900 capitalize">{{ expense.category.replace('_', ' ') }}</p>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Expense Date:</span>
                                        <p class="text-gray-900">{{ formatDate(expense.expense_date) }}</p>
                                    </div>
                                    <div v-if="expense.payment_method">
                                        <span class="text-sm font-medium text-gray-500">Payment Method:</span>
                                        <p class="text-gray-900 capitalize">{{ expense.payment_method.replace('_', ' ') }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Status and Tracking -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="text-lg font-semibold text-gray-900 mb-4">Status & Tracking</h4>
                                <div class="space-y-3">
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Status:</span>
                                        <div class="mt-1">
                                            <span :class="getStatusClass(expense.status)" class="px-3 py-1 text-sm font-semibold rounded-full">
                                                {{ expense.status }}
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Created:</span>
                                        <p class="text-gray-900">{{ formatDateTime(expense.created_at) }}</p>
                                    </div>
                                    <div v-if="expense.created_by">
                                        <span class="text-sm font-medium text-gray-500">Created By:</span>
                                        <p class="text-gray-900">{{ expense.created_by.name }}</p>
                                    </div>
                                    <div v-if="expense.approved_by">
                                        <span class="text-sm font-medium text-gray-500">Approved By:</span>
                                        <p class="text-gray-900">{{ expense.approved_by.name }}</p>
                                        <p class="text-sm text-gray-500">{{ formatDateTime(expense.approved_at) }}</p>
                                    </div>
                                    <div v-if="expense.paid_by">
                                        <span class="text-sm font-medium text-gray-500">Paid By:</span>
                                        <p class="text-gray-900">{{ expense.paid_by.name }}</p>
                                        <p class="text-sm text-gray-500">{{ formatDateTime(expense.paid_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Money Case Information -->
                        <div v-if="expense.money_case" class="bg-blue-50 p-4 rounded-lg mb-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-3">Money Case Assignment</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Case Name:</span>
                                    <p class="text-gray-900 font-medium">{{ expense.money_case.name }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Current Balance:</span>
                                    <p class="text-gray-900 font-medium">{{ formatCurrency(expense.money_case.balance) }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Status:</span>
                                    <span :class="getCaseStatusClass(expense.money_case.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                                        {{ expense.money_case.status }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="expense.money_case.description" class="mt-3">
                                <span class="text-sm font-medium text-gray-500">Description:</span>
                                <p class="text-gray-900">{{ expense.money_case.description }}</p>
                            </div>
                        </div>

                        <!-- Related Information -->
                        <div v-if="expense.salary_payment || expense.commission_payment" class="bg-yellow-50 p-4 rounded-lg mb-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-3">Related Payment</h4>
                            <div v-if="expense.salary_payment">
                                <p class="text-gray-900">
                                    <span class="font-medium">Salary Payment:</span>
                                    {{ expense.salary_payment.employee_name }} - {{ formatCurrency(expense.salary_payment.amount) }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    Period: {{ formatDate(expense.salary_payment.period_start) }} to {{ formatDate(expense.salary_payment.period_end) }}
                                </p>
                            </div>
                            <div v-if="expense.commission_payment">
                                <p class="text-gray-900">
                                    <span class="font-medium">Commission Payment:</span>
                                    {{ expense.commission_payment.employee_name }} - {{ formatCurrency(expense.commission_payment.amount) }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    Commission Rate: {{ expense.commission_payment.commission_rate }}%
                                </p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div v-if="expense.status === 'pending' || expense.status === 'approved'" class="flex justify-center space-x-4 pt-6 border-t">
                            <button
                                v-if="expense.status === 'pending'"
                                @click="approveExpense"
                                class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-6 rounded"
                            >
                                Approve Expense
                            </button>
                            <button
                                v-if="expense.status === 'approved'"
                                @click="showPaymentDialog"
                                class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded"
                            >
                                Mark as Paid
                            </button>
                            <button
                                v-if="expense.status === 'pending'"
                                @click="rejectExpense"
                                class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-6 rounded"
                            >
                                Reject Expense
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Dialog -->
        <div v-if="showPaymentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Mark Expense as Paid</h3>
                    <form @submit.prevent="markAsPaid">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                            <select v-model="paymentForm.payment_method" class="w-full border border-gray-300 rounded-md px-3 py-2" required>
                                <option value="">Select payment method</option>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="check">Check</option>
                                <option value="card">Card</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Date</label>
                            <input
                                type="date"
                                v-model="paymentForm.payment_date"
                                class="w-full border border-gray-300 rounded-md px-3 py-2"
                                required
                            />
                        </div>
                        <div class="flex justify-end space-x-2">
                            <button
                                type="button"
                                @click="closePaymentDialog"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600"
                            >
                                Mark as Paid
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router } from '@inertiajs/vue3';

export default {
    components: {
        AuthenticatedLayout,
        Link,
    },
    props: {
        expense: Object,
    },
    data() {
        return {
            showPaymentModal: false,
            paymentForm: {
                payment_method: '',
                payment_date: new Date().toISOString().split('T')[0],
            },
        };
    },
    methods: {
        formatCurrency(amount, currency = 'USD') {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency,
            }).format(amount);
        },
        formatDate(date) {
            return new Date(date).toLocaleDateString();
        },
        formatDateTime(datetime) {
            return new Date(datetime).toLocaleString();
        },
        getStatusClass(status) {
            const classes = {
                pending: 'bg-yellow-100 text-yellow-800',
                approved: 'bg-blue-100 text-blue-800',
                paid: 'bg-green-100 text-green-800',
                rejected: 'bg-red-100 text-red-800',
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },
        getCaseStatusClass(status) {
            const classes = {
                active: 'bg-green-100 text-green-800',
                inactive: 'bg-gray-100 text-gray-800',
                closed: 'bg-red-100 text-red-800',
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },
        approveExpense() {
            if (confirm('Are you sure you want to approve this expense?')) {
                router.post(route('expenses.approve', this.expense.id));
            }
        },
        rejectExpense() {
            if (confirm('Are you sure you want to reject this expense?')) {
                router.post(route('expenses.reject', this.expense.id));
            }
        },
        showPaymentDialog() {
            this.showPaymentModal = true;
        },
        closePaymentDialog() {
            this.showPaymentModal = false;
            this.paymentForm = {
                payment_method: '',
                payment_date: new Date().toISOString().split('T')[0],
            };
        },
        markAsPaid() {
            router.post(route('expenses.mark-as-paid', this.expense.id), this.paymentForm, {
                onSuccess: () => {
                    this.closePaymentDialog();
                },
            });
        },
    },
};
</script>
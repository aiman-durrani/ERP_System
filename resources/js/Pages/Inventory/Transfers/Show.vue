<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    transfer: Object,
});

const getStatusClass = (status) => {
    switch (status) {
        case 'Pending':   return 'bg-orange-100 text-orange-700 border-orange-200';
        case 'Completed': return 'bg-green-100 text-green-700 border-green-200';
        case 'Cancelled': return 'bg-gray-100 text-gray-600 border-gray-200';
        default:          return 'bg-gray-100 text-gray-600 border-gray-200';
    }
};

const completeTransfer = () => {
    if (confirm('Verify that items have been physically moved and complete this transfer?')) {
        router.post(route('inventory.transfers.complete', props.transfer.id));
    }
};

const deleteTransfer = () => {
    if (confirm('Are you sure you want to delete this transfer request? This action cannot be undone.')) {
        router.delete(route('inventory.transfers.destroy', props.transfer.id), {
            onSuccess: () => router.visit(route('inventory.transfers.index')),
        });
    }
};

const totalItems = () => props.transfer.items.reduce((sum, i) => sum + Number(i.quantity), 0);
</script>

<template>
    <Head :title="`Transfer ${transfer.transfer_number}`" />

    <AuthenticatedLayout>
        <div class="space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('inventory.transfers.index')"
                        class="flex items-center justify-center w-8 h-8 rounded-lg bg-white border border-gray-200 text-gray-500 hover:text-[#1C0D82] hover:border-[#1C0D82] transition-all shadow-sm"
                    >
                        <i class="pi pi-arrow-left text-xs"></i>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-2xl font-bold text-[#1C0D82]">{{ transfer.transfer_number }}</h2>
                            <span :class="['px-2 py-0.5 rounded border text-[10px] font-black uppercase tracking-wide', getStatusClass(transfer.status)]">
                                {{ transfer.status }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 font-medium">Inter-Warehouse Transfer Details</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2">
                    <button
                        v-if="transfer.status === 'Pending'"
                        @click="completeTransfer"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all font-semibold shadow-md active:scale-95 uppercase tracking-widest text-xs"
                    >
                        <i class="pi pi-check mr-2"></i>
                        Complete Transfer
                    </button>
                    <button
                        v-if="transfer.status !== 'Completed'"
                        @click="deleteTransfer"
                        class="inline-flex items-center px-4 py-2 bg-white border border-red-200 text-red-500 rounded-lg hover:bg-red-50 transition-all font-semibold shadow-sm active:scale-95 uppercase tracking-widest text-xs"
                    >
                        <i class="pi pi-trash mr-2"></i>
                        Delete
                    </button>
                </div>
            </div>

            <!-- Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- From Warehouse -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center flex-shrink-0">
                        <i class="pi pi-arrow-up-right text-red-500 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Source (FROM)</p>
                        <p class="text-sm font-black text-gray-900 mt-0.5">{{ transfer.from_warehouse.name }}</p>
                    </div>
                </div>

                <!-- To Warehouse -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center flex-shrink-0">
                        <i class="pi pi-arrow-down-left text-green-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Destination (TO)</p>
                        <p class="text-sm font-black text-gray-900 mt-0.5">{{ transfer.to_warehouse.name }}</p>
                    </div>
                </div>

                <!-- Transfer Date -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                        <i class="pi pi-calendar text-[#1C0D82] text-sm"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Transfer Date</p>
                        <p class="text-sm font-black text-gray-900 mt-0.5">{{ transfer.transfer_date }}</p>
                    </div>
                </div>

                <!-- Created By -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-start gap-4">
                    <div class="w-10 h-10 rounded-xl bg-yellow-50 flex items-center justify-center flex-shrink-0">
                        <i class="pi pi-user text-[#EAB308] text-sm"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Initiated By</p>
                        <p class="text-sm font-black text-gray-900 mt-0.5">{{ transfer.creator?.name ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-black text-[#1C0D82] uppercase tracking-wider">Transfer Line Items</h3>
                        <p class="text-xs text-gray-400 font-medium mt-0.5">{{ transfer.items.length }} item(s) · {{ totalItems() }} total units</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Status:</span>
                        <span :class="['px-2 py-0.5 rounded border text-[10px] font-black uppercase tracking-wide', getStatusClass(transfer.status)]">
                            {{ transfer.status }}
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">#</th>
                                <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Item</th>
                                <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Item Code</th>
                                <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest text-right">Qty Transferred</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="(line, index) in transfer.items" :key="line.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="text-xs font-black text-gray-400">{{ index + 1 }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-black text-gray-900">{{ line.item.name }}</p>
                                    <p class="text-[10px] text-gray-400 font-medium mt-0.5">{{ line.item.unit ?? '' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs font-mono font-bold text-[#1C0D82] bg-indigo-50 px-2 py-0.5 rounded">
                                        {{ line.item.item_code }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-sm font-black text-gray-900">{{ line.quantity }}</span>
                                </td>
                            </tr>
                            <tr v-if="transfer.items.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-gray-400 italic">No items in this transfer.</td>
                            </tr>
                        </tbody>
                        <!-- Totals Row -->
                        <tfoot>
                            <tr class="bg-gray-50 border-t border-gray-100">
                                <td colspan="3" class="px-6 py-3 text-xs font-black text-gray-500 uppercase tracking-widest text-right">Total Units</td>
                                <td class="px-6 py-3 text-right">
                                    <span class="text-sm font-black text-[#1C0D82]">{{ totalItems() }}</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Notes -->
            <div v-if="transfer.notes" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Movement Notes</p>
                <p class="text-sm text-gray-700 leading-relaxed">{{ transfer.notes }}</p>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

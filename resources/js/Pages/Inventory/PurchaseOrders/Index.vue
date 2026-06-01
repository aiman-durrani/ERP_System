<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    purchaseOrders: Array,
    suppliers: Array,
    warehouses: Array,
    items: Array,
});

const showCreateModal = ref(false);
const showReceiveModal = ref(false);
const showDetailsModal = ref(false);
const selectedPO = ref(null);

const openDetails = (po) => {
    selectedPO.value = po;
    showDetailsModal.value = true;
};

const form = useForm({
    supplier_id: '',
    warehouse_id: '',
    order_date: new Date().toISOString().substr(0, 10),
    expected_delivery_date: '',
    notes: '',
    items: [
        { inventory_item_id: '', quantity: 1, unit_cost: 0 }
    ],
});

const receiveForm = useForm({
    items: {}
});

const addItem = () => {
    form.items.push({ inventory_item_id: '', quantity: 1, unit_cost: 0 });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const submitCreate = () => {
    form.post(route('inventory.purchase-orders.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        }
    });
};

const openReceive = (po) => {
    selectedPO.value = po;
    const itemsData = {};
    po.items.forEach(item => {
        itemsData[item.id] = { received_quantity: item.quantity - item.received_quantity };
    });
    receiveForm.items = itemsData;
    showReceiveModal.value = true;
};

const submitReceive = () => {
    receiveForm.post(route('inventory.purchase-orders.receive', selectedPO.value.id), {
        onSuccess: () => {
            showReceiveModal.value = false;
        }
    });
};

const approvePO = (id) => {
    if (confirm('Approve this purchase order?')) {
        router.post(route('inventory.purchase-orders.approve', id));
    }
};

const getStatusClass = (status) => {
    switch (status) {
        case 'Pending': return 'bg-orange-100 text-orange-700';
        case 'Approved': return 'bg-blue-100 text-blue-700';
        case 'Received': return 'bg-green-100 text-green-700';
        case 'Cancelled': return 'bg-gray-100 text-gray-700';
        default: return 'bg-gray-100 text-gray-700';
    }
};
</script>

<template>
    <Head title="Purchase Orders" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-[#1C0D82]">Purchase Order Management</h2>
                    <p class="text-sm text-gray-500 font-medium">Create and track orders with suppliers.</p>
                </div>
                <button @click="showCreateModal = true" class="inline-flex items-center px-4 py-2 bg-[#1C0D82] text-white rounded-lg hover:bg-blue-900 transition-all font-semibold shadow-md active:scale-95 uppercase tracking-widest text-xs">
                    <i class="pi pi-plus mr-2"></i>
                    Raise New PO
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">PO Number</th>
                                <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Supplier</th>
                                <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Order Date</th>
                                <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest text-right">Total Amount</th>
                                <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest text-center">Status</th>
                                <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="po in purchaseOrders" :key="po.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="text-sm font-black text-[#1C0D82]">{{ po.po_number }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-bold text-gray-900 leading-none">{{ po.supplier.name }}</p>
                                    <p class="text-[10px] text-gray-500 font-bold uppercase mt-1">Wh: {{ po.warehouse.name }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-xs font-medium text-gray-700">{{ po.order_date }}</p>
                                    <p v-if="po.expected_delivery_date" class="text-[9px] text-orange-500 font-black tracking-tighter uppercase mt-0.5">Exp: {{ po.expected_delivery_date }}</p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-sm font-black text-gray-900">${{ po.total_amount }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span :class="['px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-tight', getStatusClass(po.status)]">
                                        {{ po.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button v-if="po.status === 'Pending'" @click="approvePO(po.id)" class="text-[10px] font-black text-blue-600 hover:bg-blue-50 px-2 py-1 rounded transition-colors uppercase tracking-widest border border-blue-100">Approve</button>
                                    <button v-if="po.status === 'Approved'" @click="openReceive(po)" class="text-[10px] font-black text-green-600 hover:bg-green-50 px-2 py-1 rounded transition-colors uppercase tracking-widest border border-green-100">Receive</button>
                                    <button @click="openDetails(po)" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-[#1C0D82] transition-colors border border-transparent hover:border-gray-100 group" title="View Details">
                                        <i class="pi pi-eye text-lg group-hover:scale-110 transition-transform"></i>
                                    </button>
                                </div>
                                </td>
                            </tr>
                            <tr v-if="purchaseOrders.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400 italic">No purchase orders raised yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create PO Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl w-full max-w-4xl shadow-2xl overflow-hidden" @click.stop>
                 <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="font-black text-[#1C0D82] uppercase tracking-wider">Raise New Purchase Order</h3>
                    <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600"><i class="pi pi-times"></i></button>
                </div>
                <form @submit.prevent="submitCreate" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Supplier</label>
                            <select v-model="form.supplier_id" class="w-full h-10 px-3 rounded-lg border border-gray-200 focus:border-[#1C0D82] focus:ring-0 text-xs transition-all bg-white">
                                <option value="" disabled>Choose Supplier</option>
                                <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Target Warehouse</label>
                            <select v-model="form.warehouse_id" class="w-full h-10 px-3 rounded-lg border border-gray-200 focus:border-[#1C0D82] focus:ring-0 text-xs transition-all bg-white">
                                <option value="" disabled>Choose Warehouse</option>
                                <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Order Date</label>
                            <input v-model="form.order_date" type="date" class="w-full h-10 px-3 rounded-lg border border-gray-200 focus:border-[#1C0D82] focus:ring-0 text-xs transition-all">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Expected Delivery</label>
                            <input v-model="form.expected_delivery_date" type="date" class="w-full h-10 px-3 rounded-lg border border-gray-200 focus:border-[#1C0D82] focus:ring-0 text-xs transition-all">
                        </div>
                    </div>

                    <!-- Items List -->
                    <div class="mt-6 border border-gray-100 rounded-xl overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-[10px] font-black text-gray-400 uppercase">Item</th>
                                    <th class="px-4 py-2 text-[10px] font-black text-gray-400 uppercase w-32">Quantity</th>
                                    <th class="px-4 py-2 text-[10px] font-black text-gray-400 uppercase w-32">Unit Cost</th>
                                    <th class="px-4 py-2 text-[10px] font-black text-gray-400 uppercase w-32 text-right">Total</th>
                                    <th class="px-4 py-2 text-[10px] font-black text-gray-400 uppercase w-16"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="(item, index) in form.items" :key="index">
                                    <td class="px-4 py-3">
                                        <select v-model="item.inventory_item_id" class="w-full h-9 px-2 rounded-lg border border-gray-200 focus:border-[#1C0D82] focus:ring-0 text-xs bg-white">
                                            <option value="" disabled>Select internal item...</option>
                                            <option v-for="itm in items" :key="itm.id" :value="itm.id">[{{ itm.item_code }}] {{ itm.name }}</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input v-model="item.quantity" type="number" step="0.01" class="w-full h-9 px-2 rounded-lg border border-gray-200 focus:border-[#1C0D82] focus:ring-0 text-xs">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input v-model="item.unit_cost" type="number" step="0.01" class="w-full h-9 px-2 rounded-lg border border-gray-200 focus:border-[#1C0D82] focus:ring-0 text-xs">
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-xs font-black text-gray-700">${{ (item.quantity * item.unit_cost).toFixed(2) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button type="button" @click="removeItem(index)" v-if="form.items.length > 1" class="text-red-400 hover:text-red-600"><i class="pi pi-trash"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="px-4 py-3 bg-gray-50/50 flex justify-between items-center">
                            <button type="button" @click="addItem" class="text-xs font-black text-[#1C0D82] hover:underline uppercase tracking-widest"><i class="pi pi-plus mr-1"></i> Add Row</button>
                            <div class="text-right">
                                <p class="text-[10px] text-gray-400 uppercase font-black">Estimated Subtotal</p>
                                <p class="text-xl font-black text-[#1C0D82]">${{ form.items.reduce((acc, item) => acc + (item.quantity * item.unit_cost), 0).toFixed(2) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Internal Remarks / Notes</label>
                        <textarea v-model="form.notes" rows="2" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#1C0D82] focus:ring-0 text-xs transition-all shadow-sm"></textarea>
                    </div>

                    <div class="pt-4 flex justify-end gap-3 border-t border-gray-100">
                        <button type="button" @click="showCreateModal = false" class="px-6 py-2 rounded-lg border border-gray-200 text-gray-600 text-xs font-bold hover:bg-gray-50 transition-colors uppercase tracking-widest">Discard</button>
                        <button type="submit" :disabled="form.processing" class="px-8 py-2 rounded-lg bg-[#EAB308] text-white text-xs font-black hover:bg-yellow-600 transition-all uppercase tracking-widest shadow-lg shadow-yellow-200 active:scale-95 disabled:opacity-70">
                            Confirm PO
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Receive Modal -->
        <div v-if="showReceiveModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden" @click.stop>
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="font-black text-[#1C0D82] uppercase tracking-wider">Receive Inventory: {{ selectedPO.po_number }}</h3>
                    <button @click="showReceiveModal = false" class="text-gray-400 hover:text-gray-600"><i class="pi pi-times"></i></button>
                </div>
                <form @submit.prevent="submitReceive" class="p-6 space-y-4">
                    <p class="text-xs text-gray-500 font-medium bg-blue-50 p-3 rounded-lg border border-blue-100 italic">Verify the actual physical quantity received before confirming. This will update real-time stock and create movement logs.</p>
                    
                    <div class="border border-gray-100 rounded-xl overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-[10px] font-black text-gray-400 uppercase">Item</th>
                                    <th class="px-4 py-2 text-[10px] font-black text-gray-400 uppercase text-center w-24">Ordered</th>
                                    <th class="px-4 py-2 text-[10px] font-black text-gray-400 uppercase text-center w-24">Received Prev.</th>
                                    <th class="px-4 py-2 text-[10px] font-black text-[#1C0D82] uppercase text-center w-32 border-l border-indigo-100 bg-indigo-50/30">Now Receiving</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="item in selectedPO.items" :key="item.id">
                                    <td class="px-4 py-3">
                                        <p class="text-xs font-black text-gray-900 leading-none">{{ item.item.name }}</p>
                                        <p class="text-[9px] text-gray-500 font-bold uppercase mt-1">{{ item.item.item_code }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-center text-xs font-bold text-gray-600">{{ item.quantity }}</td>
                                    <td class="px-4 py-3 text-center text-xs font-bold text-gray-400">{{ item.received_quantity }}</td>
                                    <td class="px-4 py-3 border-l border-indigo-100 bg-indigo-50/30">
                                        <input v-model="receiveForm.items[item.id].received_quantity" type="number" step="0.01" class="w-full h-9 px-2 rounded-lg border border-indigo-200 focus:border-[#1C0D82] focus:ring-0 text-xs text-center font-black">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pt-4 flex justify-end gap-3 border-t border-gray-100">
                        <button type="button" @click="showReceiveModal = false" class="px-6 py-2 rounded-lg border border-gray-200 text-gray-600 text-xs font-bold hover:bg-gray-50 transition-colors uppercase tracking-widest">Cancel</button>
                        <button type="submit" :disabled="receiveForm.processing" class="px-8 py-2 rounded-lg bg-[#1C0D82] text-white text-xs font-black hover:bg-blue-900 transition-all uppercase tracking-widest shadow-lg active:scale-95 disabled:opacity-70">
                            Confirm Receipt
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- PO Details Modal -->
        <div v-if="showDetailsModal && selectedPO" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl w-full max-w-4xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]" @click.stop>
                <!-- Modal Header -->
                <div class="px-8 py-6 border-b border-gray-100 flex justify-between items-center bg-[#1C0D82] text-white">
                    <div>
                        <h3 class="font-black text-xl uppercase tracking-[0.2em]">Purchase Order Details</h3>
                        <p class="text-indigo-200 text-xs font-bold uppercase tracking-widest">Record # {{ selectedPO.po_number }}</p>
                    </div>
                    <button @click="showDetailsModal = false" class="text-white/70 hover:text-white transition-colors">
                        <i class="pi pi-times text-xl"></i>
                    </button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="p-8 overflow-y-auto space-y-8 flex-1">
                    <!-- PO Summary Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                            <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">Status</p>
                            <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest inline-block shadow-sm', getStatusClass(selectedPO.status)]">
                                {{ selectedPO.status }}
                            </span>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 text-center">
                             <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">Order Date</p>
                             <p class="text-sm font-black text-gray-900">{{ selectedPO.order_date }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 text-center">
                             <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">Expected Delivery</p>
                             <p class="text-sm font-black text-gray-900">{{ selectedPO.expected_delivery_date || 'N/A' }}</p>
                        </div>
                        <div class="p-4 bg-[#EAB308]/5 rounded-xl border border-[#EAB308]/20 text-right">
                             <p class="text-[10px] text-[#EAB308] font-black uppercase tracking-widest mb-1">Total Value</p>
                             <p class="text-lg font-black text-[#1C0D82]">${{ selectedPO.total_amount }}</p>
                        </div>
                    </div>

                    <!-- Stakeholders Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-4 border-t border-gray-100">
                        <div>
                            <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                                <i class="pi pi-user-plus text-[#1C0D82]"></i> Supplier Information
                            </h4>
                            <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100">
                                <p class="text-lg font-black text-gray-900 leading-none mb-1">{{ selectedPO.supplier?.name }}</p>
                                <p class="text-xs text-gray-500 font-medium italic">{{ selectedPO.supplier?.email || 'No contact email provided' }}</p>
                                <div class="mt-4 pt-4 border-t border-gray-200/50 flex items-center gap-2 text-gray-400">
                                    <i class="pi pi-phone text-xs"></i>
                                    <span class="text-[10px] font-bold uppercase">{{ selectedPO.supplier?.phone || '--' }}</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-4 flex items-center gap-2 text-right justify-end">
                                Shipping Destination <i class="pi pi-truck text-[#1C0D82]"></i>
                            </h4>
                            <div class="bg-gray-50/50 p-4 rounded-2xl border border-gray-100 text-right">
                                <p class="text-lg font-black text-gray-900 leading-none mb-1">{{ selectedPO.warehouse?.name }}</p>
                                <p class="text-xs text-gray-500 font-medium italic">{{ selectedPO.warehouse?.location }}</p>
                                <div class="mt-4 pt-4 border-t border-gray-200/50 flex items-center gap-2 text-gray-400 justify-end">
                                    <span class="text-[10px] font-bold uppercase">Inventory Hub</span>
                                    <i class="pi pi-building text-xs"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Line Items -->
                    <div class="space-y-4 pt-4 border-t border-gray-100">
                        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Itemized Particulars</h4>
                        <div class="border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
                            <table class="w-full text-left">
                                <thead class="bg-gray-50 text-gray-400 text-[10px] font-black uppercase tracking-widest">
                                    <tr>
                                        <th class="px-6 py-4">Article Description</th>
                                        <th class="px-6 py-4 text-center">Unit Price</th>
                                        <th class="px-6 py-4 text-center">Ordered Qty</th>
                                        <th class="px-6 py-4 text-center">Received Qty</th>
                                        <th class="px-6 py-4 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr v-for="item in selectedPO.items" :key="item.id" class="text-xs">
                                        <td class="px-6 py-4">
                                            <p class="font-black text-gray-900">{{ item.item.name }}</p>
                                            <p class="text-[9px] text-gray-400 font-bold uppercase">{{ item.item.item_code }}</p>
                                        </td>
                                        <td class="px-6 py-4 text-center text-gray-500 font-bold">${{ item.unit_cost }}</td>
                                        <td class="px-6 py-4 text-center font-black text-gray-700 underline decoration-indigo-200 underline-offset-4">{{ item.quantity }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <span :class="['px-2 py-0.5 rounded font-black text-[10px]', item.received_quantity >= item.quantity ? 'bg-green-50 text-green-700' : 'bg-orange-50 text-orange-700']">
                                                {{ item.received_quantity }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right font-black text-[#1C0D82]">${{ (item.unit_cost * item.quantity).toFixed(2) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-gray-50/50">
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Aggregate Total</td>
                                        <td class="px-6 py-4 text-right text-base font-black text-[#1C0D82]">${{ selectedPO.total_amount }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Notes Section -->
                    <div v-if="selectedPO.notes" class="bg-orange-50 rounded-2xl p-6 border border-orange-100 flex gap-4">
                        <i class="pi pi-info-circle text-orange-400 text-xl mt-1"></i>
                        <div>
                            <h5 class="text-[10px] font-black text-[#1C0D82] uppercase tracking-widest mb-1">Administrative Remarks</h5>
                            <p class="text-xs text-gray-600 font-medium leading-relaxed italic">{{ selectedPO.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="px-8 py-6 border-t border-gray-100 bg-gray-50 flex justify-between items-center">
                    <p class="text-[9px] text-gray-400 font-bold uppercase italic tracking-widest">Digital Audit Log: {{ selectedPO.created_at ? new Date(selectedPO.created_at).toLocaleString() : 'N/A' }}</p>
                    <button @click="showDetailsModal = false" class="px-8 py-2.5 rounded-xl bg-gray-900 text-white text-[10px] font-black uppercase tracking-widest hover:bg-black transition-all shadow-lg active:scale-95">
                        Close View
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

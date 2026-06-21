<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    forecastData: Array,
    hiringContext: Object,
});

const localForecastData = ref([...props.forecastData]);

watch(() => props.forecastData, (newData) => {
    localForecastData.value = [...newData];
}, { deep: true });

const alertMessage = ref(null);
const alertType = ref(null); // 'success' or 'error'
const loadingItems = ref({}); // { item_id: true/false }

const selectedForecast = ref(null);
const showModal = ref(false);

const runForecast = async (item) => {
    const itemId = item.item_id;
    loadingItems.value[itemId] = true;
    alertMessage.value = null;
    alertType.value = null;
    
    try {
        const response = await axios.post('/inventory/forecast/run', {
            inventory_item_id: item.id || item.item_id
        });
        
        if (response.data.success) {
            const data = response.data.data;
            
            // Update the row forecast data dynamically
            const targetItem = localForecastData.value.find(i => i.item_id === itemId);
            let uom = '';
            if (targetItem) {
                targetItem.status = data.status;
                
                // Keep the original units/UOM styling in place
                const match = targetItem.suggested_reorder_qty ? targetItem.suggested_reorder_qty.toString().match(/[a-zA-Z\s]+$/) : null;
                uom = match ? match[0] : '';
                targetItem.suggested_reorder_qty = data.suggested_reorder_quantity + uom;
                
                if (data.suggested_reorder_quantity > 0) {
                    targetItem.suggested_order_date = new Date().toISOString().split('T')[0];
                } else {
                    targetItem.suggested_order_date = 'Stock Sufficient';
                }
            }

            // Map status for display (Healthy / Low / Critical)
            let mappedStatus = 'Healthy';
            if (data.status === 'Critical') {
                mappedStatus = 'Critical';
            } else if (data.status === 'Low' || data.status === 'Reorder Required') {
                mappedStatus = 'Low';
            }

            selectedForecast.value = {
                item: item,
                itemName: item.item_name,
                currentStock: item.current_stock,
                predictedDemand: data.predicted_demand,
                recommendedStock: data.recommended_stock,
                suggestedReorderQuantity: data.suggested_reorder_quantity,
                suggestedOrderDate: data.suggested_reorder_quantity > 0 ? new Date().toISOString().split('T')[0] : 'Stock Sufficient',
                mappedStatus: mappedStatus,
                originalStatus: data.status,
                uom: uom || ''
            };
            showModal.value = true;
        } else {
            alertMessage.value = response.data.message || 'Forecast run failed.';
            alertType.value = 'error';
        }
    } catch (error) {
        console.error(error);
        alertMessage.value = error.response?.data?.message || 'An error occurred while running the AI forecast.';
        alertType.value = 'error';
    } finally {
        loadingItems.value[itemId] = false;
    }
};

const runAgain = async (item) => {
    await runForecast(item);
};
</script>

<template>

    <Head title="AI Demand Forecast" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-[#1C0D82]">AI Inventory Demand Forecast</h2>
                    <p class="text-gray-500 text-sm font-medium">Predicting stock requirements based on historical usage
                        and HR hiring data.</p>
                </div>
                <div class="flex gap-4">
                    <div class="bg-indigo-50 px-4 py-2 rounded-lg border border-indigo-100 flex items-center gap-3">
                        <div class="w-8 h-8 bg-[#1C0D82] text-white rounded flex items-center justify-center">
                            <i class="pi pi-user-plus text-xs"></i>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-500 uppercase font-black leading-none">Hiring Plan</p>
                            <p class="text-lg font-black text-[#1C0D82] leading-none">{{ hiringContext.openPositions }}
                                Open Positions</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Banner -->
            <div v-if="alertMessage" :class="[
                'p-4 rounded-xl border flex items-start gap-3 transition-all duration-300',
                alertType === 'success' ? 'bg-green-50 border-green-100 text-green-800' : 'bg-red-50 border-red-100 text-red-800'
            ]">
                <div :class="[
                    'w-6 h-6 rounded-full flex items-center justify-center shrink-0 mt-0.5',
                    alertType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                ]">
                    <i :class="alertType === 'success' ? 'pi pi-check-circle' : 'pi pi-exclamation-circle'"></i>
                </div>
                <div class="flex-1">
                    <p class="text-xs font-bold uppercase tracking-wider leading-none mb-1">
                        {{ alertType === 'success' ? 'AI Prediction Complete' : 'AI Forecast Error' }}
                    </p>
                    <p class="text-sm font-medium">{{ alertMessage }}</p>
                </div>
                <button @click="alertMessage = null" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="pi pi-times text-xs"></i>
                </button>
            </div>

            <!-- Forecast Table -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-6 py-4 text-xs font-black text-gray-500 uppercase tracking-wider">Item
                                    Details</th>
                                <th class="px-6 py-4 text-xs font-black text-gray-500 uppercase tracking-wider">Current
                                    Stock</th>
                                <th
                                    class="px-6 py-4 text-xs font-black text-gray-500 uppercase tracking-wider text-center">
                                    Daily Usage</th>
                                <th
                                    class="px-6 py-4 text-xs font-black text-gray-500 uppercase tracking-wider text-center">
                                    Days Until Empty</th>
                                <th
                                    class="px-6 py-4 text-xs font-black text-orange-600 uppercase tracking-wider text-center bg-orange-50/20">
                                    HR Linked Demand</th>
                                <th
                                    class="px-6 py-4 text-xs font-black text-indigo-600 uppercase tracking-wider text-center bg-indigo-50/30">
                                    Suggested Reorder</th>
                                <th
                                    class="px-6 py-4 text-xs font-black text-gray-500 uppercase tracking-wider text-center">
                                    Suggested Order Date</th>
                                <th
                                    class="px-6 py-4 text-xs font-black text-gray-500 uppercase tracking-wider text-right">
                                    Status</th>
                                <th
                                    class="px-6 py-4 text-xs font-black text-gray-500 uppercase tracking-wider text-right">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="item in localForecastData" :key="item.item_id"
                                class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-black text-gray-900 leading-none">{{ item.item_name }}</p>
                                    <p class="text-[10px] text-gray-500 font-bold uppercase mt-1">{{ item.category }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <p
                                        class="text-xs font-black text-[#1C0D82] bg-indigo-50 px-2 py-0.5 rounded inline-block">
                                        {{ item.current_stock }}</p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-xs font-bold text-gray-600">{{ item.daily_usage }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        :class="['text-xs font-black', parseInt(item.days_until_empty) <= 15 ? 'text-red-600' : 'text-gray-900']">
                                        {{ item.days_until_empty }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center bg-orange-50/10">
                                    <span class="text-xs font-black text-orange-600">{{ item.hr_linked_demand > 0 ? '+'
                                        : '' }}{{ item.hr_linked_demand }} units</span>
                                </td>
                                <td class="px-6 py-4 text-center bg-indigo-50/10">
                                    <span v-if="parseFloat(item.suggested_reorder_qty) > 0"
                                        class="text-xs font-black text-indigo-700">
                                        {{ item.suggested_reorder_qty }}
                                    </span>
                                    <span v-else
                                        class="text-[10px] text-gray-400 font-bold italic uppercase">Good</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div v-if="item.suggested_order_date !== 'Stock Sufficient'"
                                        class="flex flex-col items-center">
                                        <span class="text-xs font-black text-red-600">{{ item.suggested_order_date
                                            }}</span>
                                        <span
                                            class="text-[9px] text-gray-400 font-bold uppercase tracking-tighter">Recommended</span>
                                    </div>
                                    <span v-else
                                        class="text-[10px] text-green-600 font-bold uppercase italic tracking-widest leading-none">Healthy</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span :class="[
                                        'px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-tight',
                                        item.status === 'Critical' ? 'bg-red-100 text-red-700' :
                                            (item.status === 'Low' ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700')
                                    ]">
                                        {{ item.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button 
                                        @click="runForecast(item)"
                                        :disabled="loadingItems[item.item_id]"
                                        :class="[
                                            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all duration-200 border flex items-center gap-1.5 ml-auto',
                                            loadingItems[item.item_id] 
                                                ? 'bg-gray-50 border-gray-200 text-gray-400 cursor-not-allowed'
                                                : 'bg-white border-indigo-200 text-indigo-700 hover:bg-indigo-50 active:scale-95 shadow-sm'
                                        ]"
                                    >
                                        <i v-if="loadingItems[item.item_id]" class="pi pi-spin pi-spinner text-[10px]"></i>
                                        <i v-else class="pi pi-bolt text-[10px]"></i>
                                        {{ loadingItems[item.item_id] ? 'Running AI...' : 'Run AI Forecast' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Insights Section -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-[#1C0D82] p-6 rounded-xl text-white shadow-lg overflow-hidden relative">
                    <div class="relative z-10 flex flex-col h-full justify-between">
                        <div class="w-10 h-10 bg-white/20 rounded flex items-center justify-center mb-4 text-white">
                            <i class="pi pi-info-circle text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-black text-lg mb-2">Algorithm Insight</h3>
                            <p class="text-xs text-blue-100 font-medium leading-relaxed">Our AI analyzes historical
                                usage patterns and cross-references them with active recruitment cycles from your HR
                                module to predict near-term demand peaks.</p>
                        </div>
                    </div>
                    <i class="pi pi-bolt absolute -right-4 -bottom-4 text-8xl text-white/5 rotate-12"></i>
                </div>
                <div class="md:col-span-2 bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                    <h3 class="font-black text-[#1C0D82] mb-4 uppercase text-xs tracking-widest">Efficiency Tips</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                            <p class="text-xs font-black text-gray-900 mb-1 leading-tight">Consolidate Orders</p>
                            <p class="text-[10px] text-gray-500 font-medium">You have 5 items reaching reorder points.
                                Consolidate into a single PO for supplier rating improvement.</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                            <p class="text-xs font-black text-gray-900 mb-1 leading-tight">Asset Collection</p>
                            <p class="text-[10px] text-gray-500 font-medium">3 employees are exiting next week. Monitor
                                return of high-value assets to reduce procurement costs.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Demand Forecast Modal -->
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div v-if="showModal && selectedForecast" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showModal = false">
                    <!-- Modal Card -->
                    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden transform border border-gray-100 flex flex-col my-8">
                        
                        <!-- Modal Header -->
                        <div class="bg-gradient-to-r from-[#1C0D82] to-[#4F46E5] px-6 py-5 flex items-center justify-between text-white shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center border border-white/20">
                                    <i class="pi pi-bolt text-lg text-yellow-300"></i>
                                </div>
                                <div class="text-left">
                                    <h3 class="text-lg font-black leading-none">AI Demand Forecast Report</h3>
                                    <p class="text-indigo-200 text-[10px] uppercase font-bold tracking-widest mt-1">Inventory Intelligence</p>
                                </div>
                            </div>
                            <button @click="showModal = false" class="text-white/80 hover:text-white transition-colors p-1.5 hover:bg-white/10 rounded-lg">
                                <i class="pi pi-times text-sm"></i>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-6 space-y-4 overflow-y-auto max-h-[calc(100vh-16rem)]">
                            <!-- Item Summary & Status Card -->
                            <div class="bg-slate-50 border border-slate-100 p-5 rounded-2xl flex justify-between items-center shadow-sm">
                                <div class="text-left">
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Item Details</p>
                                    <h4 class="text-base font-black text-slate-800 mt-1">{{ selectedForecast.itemName }}</h4>
                                </div>
                                <div class="flex flex-col items-end">
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">Status</p>
                                    <span :class="[
                                        'px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider shadow-sm border',
                                        selectedForecast.mappedStatus === 'Critical' 
                                            ? 'bg-red-50 text-red-700 border-red-200' 
                                            : (selectedForecast.mappedStatus === 'Low' ? 'bg-orange-50 text-orange-700 border-orange-200' : 'bg-green-50 text-green-700 border-green-200')
                                    ]">
                                        {{ selectedForecast.mappedStatus }}
                                    </span>
                                </div>
                            </div>

                            <!-- Metrics Stack -->
                            <div class="space-y-3">
                                <!-- Current Stock -->
                                <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between hover:border-indigo-100 transition-all">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-500">
                                            <i class="pi pi-box text-sm"></i>
                                        </div>
                                        <div class="text-left">
                                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Current Stock</p>
                                            <p class="text-sm font-black text-slate-800 mt-0.5">{{ selectedForecast.currentStock }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Predicted Demand -->
                                <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between hover:border-indigo-100 transition-all">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600">
                                            <i class="pi pi-chart-line text-sm"></i>
                                        </div>
                                        <div class="text-left">
                                            <p class="text-[10px] text-indigo-500 font-bold uppercase tracking-wider">Predicted Demand</p>
                                            <p class="text-sm font-black text-indigo-900 mt-0.5">{{ selectedForecast.predictedDemand }}{{ selectedForecast.uom }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Recommended Stock Level -->
                                <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between hover:border-indigo-100 transition-all">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600">
                                            <i class="pi pi-check text-sm"></i>
                                        </div>
                                        <div class="text-left">
                                            <p class="text-[10px] text-indigo-500 font-bold uppercase tracking-wider">Recommended Stock Level</p>
                                            <p class="text-sm font-black text-indigo-900 mt-0.5">{{ selectedForecast.recommendedStock }}{{ selectedForecast.uom }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Suggested Reorder Quantity -->
                                <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between hover:border-indigo-100 transition-all">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-500" :class="selectedForecast.suggestedReorderQuantity > 0 ? 'bg-indigo-50 text-indigo-600' : ''">
                                            <i class="pi pi-shopping-cart text-sm"></i>
                                        </div>
                                        <div class="text-left">
                                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Suggested Reorder Quantity</p>
                                            <p class="text-sm font-black mt-0.5" :class="selectedForecast.suggestedReorderQuantity > 0 ? 'text-indigo-700' : 'text-slate-500'">
                                                {{ selectedForecast.suggestedReorderQuantity }}{{ selectedForecast.uom }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Suggested Order Date -->
                                <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex items-center justify-between hover:border-indigo-100 transition-all">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center" :class="selectedForecast.suggestedOrderDate === 'Stock Sufficient' ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600'">
                                            <i class="pi pi-calendar text-sm"></i>
                                        </div>
                                        <div class="text-left">
                                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Suggested Order Date</p>
                                            <p class="text-sm font-black mt-0.5" :class="selectedForecast.suggestedOrderDate === 'Stock Sufficient' ? 'text-green-600' : 'text-red-600'">
                                                {{ selectedForecast.suggestedOrderDate }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="bg-slate-50 border-t border-slate-100 px-6 py-4 flex items-center justify-end gap-3">
                            <button @click="showModal = false" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-100 active:scale-95 transition-all">
                                Close
                            </button>
                            <button 
                                @click="runAgain(selectedForecast.item)" 
                                :disabled="loadingItems[selectedForecast.item.item_id]"
                                class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-[#1C0D82] hover:bg-[#3221b3] active:scale-95 transition-all disabled:opacity-50 disabled:pointer-events-none flex items-center gap-2 shadow-md shadow-indigo-100"
                            >
                                <i v-if="loadingItems[selectedForecast.item.item_id]" class="pi pi-spin pi-spinner text-xs"></i>
                                <i v-else class="pi pi-refresh text-xs"></i>
                                Run Again
                            </button>
                        </div>

                    </div>
                </div>
            </Transition>
        </div>
    </AuthenticatedLayout>
</template>

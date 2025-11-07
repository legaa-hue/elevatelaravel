<template>
    <Transition name="slide-up">
        <div v-if="showManager" class="fixed inset-x-0 bottom-0 z-50 bg-white shadow-2xl border-t-2 border-gray-200 max-h-[70vh] flex flex-col">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 border-b bg-gradient-to-r from-blue-50 to-indigo-50">
                <div class="flex items-center space-x-3">
                    <span class="material-icons text-blue-600 animate-pulse">sync</span>
                    <div>
                        <h3 class="font-bold text-gray-900">Offline Sync Manager</h3>
                        <p class="text-xs text-gray-600">
                            {{ pendingActionsCount }} {{ pendingActionsCount === 1 ? 'action' : 'actions' }} pending
                        </p>
                    </div>
                </div>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 transition">
                    <span class="material-icons">close</span>
                </button>
            </div>

            <!-- Progress Bar (when syncing) -->
            <div v-if="isSyncing" class="px-4 py-3 bg-blue-50 border-b">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-blue-900">
                        Syncing {{ syncProgress.current }} / {{ syncProgress.total }}
                    </span>
                    <span class="text-xs text-blue-700">
                        {{ Math.round((syncProgress.current / syncProgress.total) * 100) }}%
                    </span>
                </div>
                <div class="w-full bg-blue-200 rounded-full h-2 overflow-hidden">
                    <div
                        class="bg-blue-600 h-2 transition-all duration-300 ease-out"
                        :style="{ width: `${(syncProgress.current / syncProgress.total) * 100}%` }">
                    </div>
                </div>
            </div>

            <!-- Actions List -->
            <div class="flex-1 overflow-y-auto p-4 space-y-2">
                <div v-if="pendingActionsList.length === 0" class="text-center py-8 text-gray-500">
                    <span class="material-icons text-5xl mb-2">cloud_done</span>
                    <p class="font-medium">All changes synced!</p>
                </div>

                <div
                    v-for="action in pendingActionsList"
                    :key="action.id"
                    class="bg-white border border-gray-200 rounded-lg p-3 hover:shadow-md transition">
                    <div class="flex items-start space-x-3">
                        <span class="material-icons text-gray-400 text-sm mt-0.5">{{ action.icon }}</span>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 text-sm truncate">{{ action.label }}</p>
                            <p class="text-xs text-gray-500">
                                {{ formatTimestamp(action.timestamp) }}
                            </p>
                        </div>
                        <div class="flex space-x-1">
                            <button
                                v-if="isOnline && !isSyncing"
                                @click="handleRetry(action.id)"
                                :disabled="retrying === action.id"
                                class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-medium transition disabled:opacity-50">
                                <span v-if="retrying === action.id" class="material-icons text-xs animate-spin">sync</span>
                                <span v-else>Retry</span>
                            </button>
                            <button
                                @click="handleDelete(action.id)"
                                :disabled="deleting === action.id || isSyncing"
                                class="px-2 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-medium transition disabled:opacity-50">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="flex items-center justify-between p-4 border-t bg-gray-50">
                <div class="text-xs text-gray-600">
                    <span v-if="lastSyncTime">
                        Last synced: {{ formatLastSync(lastSyncTime) }}
                    </span>
                    <span v-else>Not synced yet</span>
                </div>
                <button
                    v-if="isOnline && pendingActionsCount > 0"
                    @click="$emit('sync-all')"
                    :disabled="isSyncing"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium text-sm transition disabled:opacity-50 flex items-center space-x-2">
                    <span class="material-icons text-sm" :class="{ 'animate-spin': isSyncing }">sync</span>
                    <span>{{ isSyncing ? 'Syncing...' : 'Sync All Now' }}</span>
                </button>
                <div v-else-if="!isOnline" class="text-sm text-orange-600 font-medium flex items-center space-x-1">
                    <span class="material-icons text-sm">cloud_off</span>
                    <span>Offline - Will sync when online</span>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useOfflineSync } from '../composables/useOfflineSync';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    }
});

defineEmits(['close', 'sync-all']);

const {
    isOnline,
    isSyncing,
    pendingActionsCount,
    lastSyncTime,
    syncProgress,
    getPendingActionsList,
    retryAction,
    deleteAction
} = useOfflineSync();

const showManager = computed(() => props.show);
const pendingActionsList = ref([]);
const retrying = ref(null);
const deleting = ref(null);

// Load pending actions when manager is opened
watch(() => props.show, async (newValue) => {
    if (newValue) {
        await loadPendingActions();
    }
});

const loadPendingActions = async () => {
    try {
        pendingActionsList.value = await getPendingActionsList();
    } catch (error) {
        console.error('Failed to load pending actions:', error);
    }
};

const handleRetry = async (actionId) => {
    retrying.value = actionId;
    try {
        await retryAction(actionId);
        await loadPendingActions();
    } catch (error) {
        console.error('Failed to retry action:', error);
        alert(`Failed to retry: ${error.message}`);
    } finally {
        retrying.value = null;
    }
};

const handleDelete = async (actionId) => {
    if (!confirm('Are you sure you want to delete this pending action? This cannot be undone.')) {
        return;
    }

    deleting.value = actionId;
    try {
        await deleteAction(actionId);
        await loadPendingActions();
    } catch (error) {
        console.error('Failed to delete action:', error);
        alert(`Failed to delete: ${error.message}`);
    } finally {
        deleting.value = null;
    }
};

const formatTimestamp = (timestamp) => {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;

    // Less than 1 minute
    if (diff < 60000) return 'Just now';

    // Less than 1 hour
    if (diff < 3600000) {
        const mins = Math.floor(diff / 60000);
        return `${mins} ${mins === 1 ? 'minute' : 'minutes'} ago`;
    }

    // Less than 1 day
    if (diff < 86400000) {
        const hours = Math.floor(diff / 3600000);
        return `${hours} ${hours === 1 ? 'hour' : 'hours'} ago`;
    }

    // Show full date
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
};

const formatLastSync = (timestamp) => {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;

    if (diff < 60000) return 'just now';
    if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`;
    if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`;
    return date.toLocaleDateString();
};

onMounted(() => {
    if (props.show) {
        loadPendingActions();
    }
});
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
    transition: transform 0.3s ease-out, opacity 0.3s ease-out;
}

.slide-up-enter-from {
    transform: translateY(100%);
    opacity: 0;
}

.slide-up-leave-to {
    transform: translateY(100%);
    opacity: 0;
}
</style>

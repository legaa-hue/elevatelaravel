<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

// Props
const props = defineProps({
    events: {
        type: Array,
        default: () => []
    }
});

// Modal state
const showModal = ref(false);
const isEditing = ref(false);
const currentEvent = ref(null);
const processing = ref(false);

// Form data
const form = ref({
    title: '',
    date: '',
    time: '',
    description: '',
    category: 'general',
    color: '#800000',
    target_audience: 'both'
});

// Local events state
const localEvents = ref([...props.events]);
const filterCategory = ref('all');
const searchQuery = ref('');

// Watch for props changes and update local events
watch(() => props.events, (newEvents) => {
    localEvents.value = [...newEvents];
    updateCalendarEvents();
}, { deep: true });

// Categories
const categories = [
    { value: 'general', label: 'General', color: '#6B7280' },
    { value: 'meeting', label: 'Meeting', color: '#800000' },
    { value: 'academic', label: 'Academic Event', color: '#10B981' },
    { value: 'deadline', label: 'Deadline', color: '#F59E0B' },
    { value: 'maintenance', label: 'Maintenance', color: '#EF4444' },
    { value: 'urgent', label: 'Urgent Notice', color: '#DC2626' },
];

// Calendar options
const calendarOptions = ref({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: window.innerWidth < 768 ? 'timeGridDay' : 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: window.innerWidth < 768 ? 'dayGridMonth,timeGridWeek,timeGridDay' : 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    editable: true,
    selectable: true,
    selectMirror: true,
    dayMaxEvents: true,
    weekends: true,
    events: [],
    eventClick: handleEventClick,
    dateClick: handleDateClick,
    eventDrop: handleEventDrop,
    eventResize: handleEventResize,
    height: 'auto',
    contentHeight: window.innerWidth < 768 ? 400 : 'auto',
    aspectRatio: window.innerWidth < 768 ? 1 : 1.8,
});

// Computed filtered events
const filteredEvents = computed(() => {
    let filtered = localEvents.value;
    
    if (filterCategory.value !== 'all') {
        filtered = filtered.filter(e => e.category === filterCategory.value);
    }
    
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(e =>
            e.title.toLowerCase().includes(query) ||
            e.description.toLowerCase().includes(query)
        );
    }
    
    return filtered.sort((a, b) => new Date(b.date) - new Date(a.date));
});

// Update calendar events
const updateCalendarEvents = () => {
    calendarOptions.value.events = localEvents.value.map(e => {
        // If time exists, create a datetime, otherwise just use the date for all-day event
        const eventStart = e.time ? `${e.date}T${e.time}` : e.date;
        
        return {
            id: e.id,
            title: e.title,
            start: eventStart,
            allDay: !e.time, // Mark as all-day if no time specified
            backgroundColor: e.backgroundColor,
            borderColor: e.borderColor,
            textColor: '#000000',
            extendedProps: {
                description: e.description,
                category: e.category,
                color: e.color,
            }
        };
    });
};

onMounted(() => {
    updateCalendarEvents();
});

// Handle event click
function handleEventClick(info) {
    const event = localEvents.value.find(e => e.id == info.event.id);
    if (event) {
        currentEvent.value = event;
        form.value = { ...event };
        isEditing.value = true;
        showModal.value = true;
    }
}

// Handle date click
function handleDateClick(info) {
    form.value = {
        title: '',
        date: info.dateStr,
        time: '',
        description: '',
        category: 'general',
        color: '#800000'
    };
    isEditing.value = false;
    currentEvent.value = null;
    showModal.value = true;
}

// Handle event drop (drag and drop)
function handleEventDrop(info) {
    const event = localEvents.value.find(e => e.id == info.event.id);
    if (event) {
        const newDate = info.event.startStr.split('T')[0];
        processing.value = true;
        
        // Update via API
        router.patch(route('admin.events.updateDate', event.id), {
            date: newDate
        }, {
            preserveScroll: true,
            onSuccess: () => {
                processing.value = false;
            },
            onError: () => {
                info.revert(); // Revert if error
                processing.value = false;
                alert('Failed to update event date');
            }
        });
    }
}

// Handle event resize
function handleEventResize(info) {
    const event = localEvents.value.find(e => e.id == info.event.id);
    if (event) {
        const newDate = info.event.startStr.split('T')[0];
        event.date = newDate;
        updateCalendarEvents();
    }
}

// Open create modal
function openCreateModal() {
    form.value = {
        title: '',
        date: new Date().toISOString().split('T')[0],
        time: '',
        description: '',
        category: 'general',
        color: '#800000'
    };
    isEditing.value = false;
    currentEvent.value = null;
    showModal.value = true;
}

// Save event
function saveEvent() {
    if (!form.value.title || !form.value.date || !form.value.description) {
        alert('Please fill in all required fields');
        return;
    }

    processing.value = true;
    const category = categories.find(c => c.value === form.value.category);
    const eventData = {
        title: form.value.title,
        date: form.value.date,
        time: form.value.time || null,
        description: form.value.description,
        category: form.value.category,
        color: category.color,
        target_audience: form.value.target_audience,
    };

    if (isEditing.value && currentEvent.value) {
        // Update existing event
        router.put(route('admin.events.update', currentEvent.value.id), eventData, {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                processing.value = false;
            },
            onError: () => {
                processing.value = false;
                alert('Failed to update event');
            }
        });
    } else {
        // Create new event
        router.post(route('admin.events.store'), eventData, {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                processing.value = false;
            },
            onError: () => {
                processing.value = false;
                alert('Failed to create event');
            }
        });
    }
}

// Delete event
function deleteEvent(eventId) {
    if (confirm('Are you sure you want to delete this event?')) {
        processing.value = true;
        router.delete(route('admin.events.destroy', eventId), {
            preserveScroll: true,
            onSuccess: () => {
                processing.value = false;
            },
            onError: () => {
                processing.value = false;
                alert('Failed to delete event');
            }
        });
    }
}

// Edit event
function editEvent(event) {
    currentEvent.value = event;
    form.value = { ...event };
    isEditing.value = true;
    showModal.value = true;
}

// Close modal
function closeModal() {
    showModal.value = false;
    isEditing.value = false;
    currentEvent.value = null;
}

// Get category badge color
function getCategoryBadgeColor(category) {
    const cat = categories.find(c => c.value === category);
    return cat ? cat.color : '#6B7280';
}

// Format date
function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
}

// Format time
function formatTime(time) {
    if (!time) return 'All Day';
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}
</script>

<template>
    <Head title="Calendar" />

    <AdminLayout>
        <div class="mb-4 md:mb-6 p-4 md:p-0">
            <h1 class="text-xl md:text-2xl font-bold text-gray-900">📅 Events & Announcements Calendar</h1>
            <p class="text-xs md:text-sm text-gray-600 mt-1">Manage and view all system events and announcements</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6 p-4 md:p-0">
            <!-- Left Section - Calendar View -->
            <div class="lg:col-span-2 bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
                <div class="h-2 bg-red-900"></div>
                <div class="p-3 md:p-6">
                    <FullCalendar :options="calendarOptions" class="elevategs-calendar" />
                </div>
            </div>

            <!-- Right Section - Announcements Panel -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
                <div class="h-2 bg-yellow-500"></div>
                <div class="p-4 md:p-6">
                    <!-- Header with Create Button -->
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base md:text-lg font-semibold text-gray-900">Announcements</h2>
                        <button
                            @click="openCreateModal"
                            class="flex items-center space-x-1 md:space-x-2 px-3 md:px-4 py-2 bg-red-900 text-white rounded-lg hover:bg-red-800 transition-colors shadow-sm"
                        >
                            <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="text-sm md:text-base font-medium">Create</span>
                        </button>
                    </div>

                    <!-- Filter and Search -->
                    <div class="space-y-3 mb-4">
                        <div>
                            <label class="text-xs font-semibold text-gray-900 mb-1 block">Filter by Category:</label>
                            <select
                                v-model="filterCategory"
                                class="w-full rounded-md border-gray-300 bg-white text-gray-900 text-sm focus:border-red-900 focus:ring-red-900"
                            >
                                <option value="all">All Categories</option>
                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                                    {{ cat.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-900 mb-1 block">Search:</label>
                            <input
                                type="text"
                                v-model="searchQuery"
                                placeholder="Search events..."
                                class="w-full rounded-md border-gray-300 bg-white text-gray-900 text-sm focus:border-red-900 focus:ring-red-900"
                            />
                        </div>
                    </div>

                    <!-- Color Legend -->
                    <div class="mb-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <h3 class="text-xs font-semibold text-gray-900 mb-2">Color Legend:</h3>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div v-for="cat in categories" :key="cat.value" class="flex items-center space-x-2">
                                <div class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: cat.color }"></div>
                                <span class="text-gray-900 truncate">{{ cat.label }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Announcements List -->
                    <div class="space-y-3 max-h-[400px] md:max-h-[600px] overflow-y-auto">
                        <div
                            v-for="event in filteredEvents"
                            :key="event.id"
                            class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200"
                        >
                            <div class="h-1" :style="{ backgroundColor: getCategoryBadgeColor(event.category) }"></div>
                            <div class="p-3 md:p-4">
                                <div class="flex items-start justify-between mb-2 gap-2">
                                    <h3 class="text-sm font-semibold text-gray-900 flex-1">{{ event.title }}</h3>
                                    <span
                                        class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap flex-shrink-0"
                                        :style="{
                                            backgroundColor: `${getCategoryBadgeColor(event.category)}20`,
                                            color: getCategoryBadgeColor(event.category)
                                        }"
                                    >
                                        {{ categories.find(c => c.value === event.category)?.label }}
                                    </span>
                                </div>
                                <div class="flex items-center flex-wrap gap-1 md:gap-2 text-xs text-gray-600 mb-2">
                                    <div class="flex items-center space-x-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span class="font-medium">{{ formatDate(event.date) }}</span>
                                    </div>
                                    <span class="text-gray-400">•</span>
                                    <span>{{ formatTime(event.time) }}</span>
                                </div>
                                <p class="text-xs text-gray-700 mb-3 line-clamp-2">{{ event.description }}</p>
                                <div class="flex items-center space-x-2">
                                    <button
                                        @click="editEvent(event)"
                                        class="flex-1 px-3 py-2 text-xs font-medium text-red-900 bg-red-50 rounded-lg hover:bg-red-100 transition-colors"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        @click="deleteEvent(event.id)"
                                        class="flex-1 px-3 py-2 text-xs font-medium text-gray-900 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="filteredEvents.length === 0" class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-semibold text-gray-900">No events found</h3>
                            <p class="mt-1 text-xs text-gray-600">Try adjusting your filters or create a new event.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="closeModal"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full mx-4">
                    <div class="h-2 bg-red-900"></div>
                    <div class="bg-white px-4 md:px-6 pt-5 pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-base md:text-lg font-semibold text-gray-900">
                                {{ isEditing ? 'Edit Event' : 'Create New Event' }}
                            </h3>
                            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form @submit.prevent="saveEvent" class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-900 mb-1">Title *</label>
                                <input
                                    type="text"
                                    v-model="form.title"
                                    required
                                    class="w-full rounded-md border-gray-300 bg-white text-gray-900 focus:border-red-900 focus:ring-red-900"
                                    placeholder="Event title"
                                />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-900 mb-1">Date *</label>
                                    <input
                                        type="date"
                                        v-model="form.date"
                                        required
                                        class="w-full rounded-md border-gray-300 bg-white text-gray-900 focus:border-red-900 focus:ring-red-900"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-900 mb-1">Time</label>
                                    <input
                                        type="time"
                                        v-model="form.time"
                                        class="w-full rounded-md border-gray-300 bg-white text-gray-900 focus:border-red-900 focus:ring-red-900"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-900 mb-1">Category *</label>
                                <select
                                    v-model="form.category"
                                    required
                                    class="w-full rounded-md border-gray-300 bg-white text-gray-900 focus:border-red-900 focus:ring-red-900"
                                >
                                    <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                                        {{ cat.label }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-900 mb-1">Send Announcement To *</label>
                                <select
                                    v-model="form.target_audience"
                                    required
                                    class="w-full rounded-md border-gray-300 bg-white text-gray-900 focus:border-red-900 focus:ring-red-900"
                                >
                                    <option value="both">Teachers & Students (Both)</option>
                                    <option value="teachers">Teachers Only</option>
                                    <option value="students">Students Only</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Choose who will see this announcement</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-900 mb-1">Description *</label>
                                <textarea
                                    v-model="form.description"
                                    required
                                    rows="3"
                                    class="w-full rounded-md border-gray-300 bg-white text-gray-900 focus:border-red-900 focus:ring-red-900"
                                    placeholder="Event description"
                                ></textarea>
                            </div>

                            <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-3 pt-4">
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    class="w-full sm:flex-1 px-4 py-2.5 bg-red-900 text-white rounded-lg hover:bg-red-800 transition-colors font-medium text-sm md:text-base disabled:opacity-50"
                                >
                                    {{ processing ? 'Saving...' : (isEditing ? 'Update Event' : 'Create Event') }}
                                </button>
                                <button
                                    type="button"
                                    @click="closeModal"
                                    :disabled="processing"
                                    class="w-full sm:flex-1 px-4 py-2.5 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition-colors font-medium text-sm md:text-base disabled:opacity-50"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* FullCalendar Custom Styling for ElevateGS */
:deep(.elevategs-calendar) {
    --fc-border-color: #E5E7EB;
    --fc-button-bg-color: #800000;
    --fc-button-border-color: #800000;
    --fc-button-hover-bg-color: #991B1B;
    --fc-button-hover-border-color: #991B1B;
    --fc-button-active-bg-color: #7F1D1D;
    --fc-button-active-border-color: #7F1D1D;
    --fc-today-bg-color: #FEF3C7;
}

:deep(.fc .fc-button) {
    font-weight: 600;
    text-transform: capitalize;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
}

:deep(.fc .fc-button:disabled) {
    opacity: 0.5;
}

:deep(.fc-theme-standard .fc-scrollgrid) {
    border-color: #E5E7EB;
}

:deep(.fc .fc-col-header-cell) {
    background-color: #F9FAFB;
    font-weight: 700;
    color: #111827;
    padding: 0.75rem 0.5rem;
}

:deep(.fc .fc-daygrid-day-number) {
    color: #111827;
    font-weight: 600;
    padding: 0.5rem;
}

:deep(.fc .fc-daygrid-day.fc-day-today) {
    background-color: #FEF3C7 !important;
}

:deep(.fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number) {
    color: #92400E;
    font-weight: 700;
}

:deep(.fc-event) {
    border-radius: 0.375rem;
    padding: 2px 4px;
    font-weight: 500;
    font-size: 0.75rem;
}

:deep(.fc-event-title) {
    color: #000000;
}

:deep(.fc-daygrid-event-dot) {
    border-width: 4px;
}

:deep(.fc .fc-toolbar-title) {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    :deep(.fc .fc-toolbar) {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    :deep(.fc .fc-toolbar-chunk) {
        display: flex;
        justify-content: center;
    }
    
    :deep(.fc .fc-button) {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }
    
    :deep(.fc .fc-toolbar-title) {
        font-size: 1.125rem;
    }
    
    :deep(.fc .fc-col-header-cell) {
        padding: 0.5rem 0.25rem;
        font-size: 0.75rem;
    }
    
    :deep(.fc .fc-daygrid-day-number) {
        padding: 0.25rem;
        font-size: 0.875rem;
    }
    
    :deep(.fc-event) {
        font-size: 0.625rem;
        padding: 1px 2px;
    }
    
    :deep(.fc .fc-daygrid-day-frame) {
        min-height: 60px;
    }
}

@media (max-width: 480px) {
    :deep(.fc .fc-button) {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    :deep(.fc .fc-toolbar-title) {
        font-size: 1rem;
    }
    
    :deep(.fc .fc-col-header-cell-cushion) {
        padding: 0.25rem;
    }
}

/* Scrollbar styling */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #F3F4F6;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #9CA3AF;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #6B7280;
}
</style>

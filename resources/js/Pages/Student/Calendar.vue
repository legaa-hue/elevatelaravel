<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
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
    editable: false,
    selectable: false,
    selectMirror: false,
    dayMaxEvents: true,
    weekends: true,
    events: [],
    eventClick: handleEventClick,
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
    
    return filtered;
});

// Calendar ref
const calendar = ref(null);

// Update calendar events
function updateCalendarEvents() {
    if (calendar.value) {
        const calendarApi = calendar.value.getApi();
        calendarApi.removeAllEvents();
        calendarApi.addEventSource(formatEventsForCalendar(filteredEvents.value));
    }
}

// Format events for FullCalendar
function formatEventsForCalendar(events) {
    return events.map(event => ({
        id: event.id,
        title: event.title,
        start: event.time ? `${event.date}T${event.time}` : event.date,
        allDay: !event.time,
        backgroundColor: event.color,
        borderColor: event.color,
        extendedProps: {
            description: event.description,
            category: event.category,
            created_by: event.created_by
        }
    }));
}

// Modal state for viewing event details
const showEventModal = ref(false);
const selectedEvent = ref(null);

// Handle event click
function handleEventClick(info) {
    const event = localEvents.value.find(e => e.id === parseInt(info.event.id));
    if (event) {
        selectedEvent.value = event;
        showEventModal.value = true;
    }
}

// Close event modal
function closeEventModal() {
    showEventModal.value = false;
    selectedEvent.value = null;
}

// Get category label
function getCategoryLabel(category) {
    return categories.find(c => c.value === category)?.label || category;
}

// Format date
const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

// Format time
const formatTime = (timeString) => {
    if (!timeString) return 'All Day';
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
};

// Initialize calendar
onMounted(() => {
    updateCalendarEvents();
    
    // Update calendar view on resize
    window.addEventListener('resize', () => {
        if (calendar.value) {
            const calendarApi = calendar.value.getApi();
            if (window.innerWidth < 768) {
                calendarApi.changeView('timeGridDay');
            } else {
                calendarApi.changeView('dayGridMonth');
            }
        }
    });
});

// Watch filter changes
watch([filterCategory, searchQuery], () => {
    updateCalendarEvents();
});
</script>

<template>
    <Head title="Calendar - Events & Announcements" />

    <StudentLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <div class="bg-gradient-to-r from-red-900 to-red-700 rounded-lg shadow-lg p-6 md:p-8 text-white">
                <div class="flex items-center gap-3 mb-2">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h1 class="text-2xl md:text-3xl font-bold">Events & Announcements Calendar</h1>
                </div>
                <p class="text-red-100">View course deadlines and announcements</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Calendar -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow-md p-4 md:p-6">
                    <FullCalendar ref="calendar" :options="calendarOptions" />
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Announcements -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Announcements</h2>

                        <!-- Filter -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Category:</label>
                            <select 
                                v-model="filterCategory"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent"
                            >
                                <option value="all">All Categories</option>
                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                                    {{ cat.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Search -->
                        <div class="mb-4">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search events..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent"
                            />
                        </div>

                        <!-- Color Legend -->
                        <div class="space-y-2">
                            <p class="text-sm font-medium text-gray-700 mb-2">Color Legend:</p>
                            <div v-for="cat in categories" :key="cat.value" class="flex items-center gap-2">
                                <div class="w-4 h-4 rounded" :style="{ backgroundColor: cat.color }"></div>
                                <span class="text-sm text-gray-600">{{ cat.label }}</span>
                            </div>
                        </div>

                        <!-- Events List -->
                        <div v-if="filteredEvents.length > 0" class="mt-6 space-y-2 max-h-64 overflow-y-auto">
                            <p class="text-xs font-medium text-gray-500 mb-2">{{ filteredEvents.length }} event(s) found</p>
                            <div
                                v-for="event in filteredEvents"
                                :key="event.id"
                                class="p-3 rounded-lg border-l-4 cursor-pointer hover:bg-gray-50 transition"
                                :style="{ borderColor: event.color }"
                                @click="selectedEvent = event; showEventModal = true"
                            >
                                <h4 class="font-semibold text-sm text-gray-900">{{ event.title }}</h4>
                                <p class="text-xs text-gray-600 mt-1">{{ formatDate(event.date) }} • {{ formatTime(event.time) }}</p>
                                <span class="inline-block mt-1 px-2 py-0.5 text-xs rounded" :style="{ backgroundColor: event.color, color: 'white' }">
                                    {{ getCategoryLabel(event.category) }}
                                </span>
                            </div>
                        </div>

                        <div v-else class="mt-6 text-center py-8 text-gray-500 text-sm">
                            No events found
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Event Details Modal -->
        <div v-if="showEventModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="closeEventModal"></div>
            <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
                <button @click="closeEventModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div v-if="selectedEvent">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">{{ selectedEvent.title }}</h2>
                    
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>{{ formatDate(selectedEvent.date) }}</span>
                        </div>

                        <div class="flex items-center gap-2 text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ formatTime(selectedEvent.time) }}</span>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 rounded text-sm font-medium text-white" :style="{ backgroundColor: selectedEvent.color }">
                                {{ getCategoryLabel(selectedEvent.category) }}
                            </span>
                        </div>

                        <div v-if="selectedEvent.description" class="pt-3 border-t border-gray-200">
                            <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ selectedEvent.description }}</p>
                        </div>

                        <div v-if="selectedEvent.created_by" class="pt-3 border-t border-gray-200">
                            <p class="text-xs text-gray-500">Created by: {{ selectedEvent.created_by }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import { useOfflineSync } from '@/composables/useOfflineSync';
import { useTeacherOffline } from '@/composables/useTeacherOffline';

// Props
const props = defineProps({
    events: {
        type: Array,
        default: () => []
    },
    courses: {
        type: Array,
        default: () => []
    }
});

// Offline composables
const { isOnline } = useOfflineSync();
const { createEventOffline, cacheEvents, getCachedEvents } = useTeacherOffline();

// Page props for flash messages
const page = usePage();
const showSuccessMessage = ref(false);
const successMessage = ref('');
const isFromCache = ref(false);

// Watch for flash messages
watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        successMessage.value = flash.success;
        showSuccessMessage.value = true;
        setTimeout(() => {
            showSuccessMessage.value = false;
        }, 5000);
    }
}, { deep: true, immediate: true });

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
    is_deadline: false,
    color: '#800000',
    target_audience: 'both',
    course_id: null,
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
        const eventStart = e.time ? `${e.date}T${e.time}` : e.date;
        const bgColor = e.color + '20';
        
        return {
            id: e.id,
            title: e.is_deadline ? '📌 ' + e.title : e.title,
            start: eventStart,
            allDay: !e.time,
            backgroundColor: bgColor,
            borderColor: e.color,
            textColor: '#000000',
            extendedProps: {
                description: e.description,
                category: e.category,
                is_deadline: e.is_deadline,
                color: e.color,
                course_name: e.course_name,
                created_by: e.created_by,
                is_own: e.is_own,
            }
        };
    });
};

onMounted(async () => {
    // Cache events when online
    if (isOnline.value && props.events?.length > 0) {
        await cacheEvents(props.events);
        isFromCache.value = false;
    } else if (!isOnline.value) {
        // Load from cache when offline
        const cached = await getCachedEvents();
        if (cached && cached.length > 0) {
            localEvents.value = cached;
            isFromCache.value = true;
        }
    }
    
    updateCalendarEvents();
});

// Handle event click
function handleEventClick(info) {
    const event = localEvents.value.find(e => e.id == info.event.id);
    if (event) {
        currentEvent.value = event;
        form.value = {
            title: event.title,
            date: event.date,
            time: event.time || '',
            description: event.description,
            category: event.category,
            is_deadline: event.is_deadline || false,
            color: event.color,
            target_audience: event.target_audience || 'all_courses',
            course_id: event.course_id || null,
        };
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
        is_deadline: false,
        color: '#800000',
        target_audience: 'all_courses',
        course_id: null,
    };
    isEditing.value = false;
    currentEvent.value = null;
    showModal.value = true;
}

// Handle event drop (drag and drop)
function handleEventDrop(info) {
    const event = localEvents.value.find(e => e.id == info.event.id);
    if (event && event.is_own) {
        const newDate = info.event.startStr.split('T')[0];
        const newTime = info.event.startStr.includes('T') ? info.event.startStr.split('T')[1].substring(0, 5) : null;
        
        processing.value = true;
        
        router.patch(route('teacher.calendar.updateDateTime', event.id), {
            date: newDate,
            time: newTime
        }, {
            preserveScroll: true,
            onSuccess: () => {
                processing.value = false;
            },
            onError: () => {
                info.revert();
                processing.value = false;
                alert('Failed to update event date');
            }
        });
    } else {
        info.revert();
        alert('You can only edit your own events');
    }
}

// Handle event resize
function handleEventResize(info) {
    const event = localEvents.value.find(e => e.id == info.event.id);
    if (event && event.is_own) {
        const newDate = info.event.startStr.split('T')[0];
        event.date = newDate;
        updateCalendarEvents();
    } else {
        info.revert();
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
        is_deadline: false,
        color: '#800000',
        target_audience: 'all_courses',
        course_id: null,
    };
    isEditing.value = false;
    currentEvent.value = null;
    showModal.value = true;
}

// Save event
async function saveEvent() {
    if (!form.value.title || !form.value.date || !form.value.description) {
        alert('Please fill in all required fields');
        return;
    }

    if (form.value.target_audience === 'specific_course' && !form.value.course_id) {
        alert('Please select a course');
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
        is_deadline: form.value.is_deadline,
        color: category.color,
        target_audience: form.value.target_audience,
        course_id: form.value.target_audience === 'students' ? form.value.course_id : null,
    };

    // Handle offline mode
    if (!isOnline.value) {
        if (isEditing.value && currentEvent.value) {
            // Update event offline
            await createEventOffline({
                ...eventData,
                id: currentEvent.value.id,
                _method: 'PUT'
            });
            
            // Update local event
            const index = localEvents.value.findIndex(e => e.id === currentEvent.value.id);
            if (index !== -1) {
                localEvents.value[index] = { ...localEvents.value[index], ...eventData };
                updateCalendarEvents();
            }
        } else {
            // Create event offline
            const tempId = 'temp_' + Date.now();
            await createEventOffline({ ...eventData, id: tempId });
            
            // Add to local events
            localEvents.value.unshift({
                id: tempId,
                ...eventData,
                is_own: true,
                created_by: 'You'
            });
            updateCalendarEvents();
        }
        
        processing.value = false;
        closeModal();
        successMessage.value = isEditing.value 
            ? '✓ Event updated offline. Will sync when online.' 
            : '✓ Event created offline. Will sync when online.';
        showSuccessMessage.value = true;
        setTimeout(() => showSuccessMessage.value = false, 5000);
        return;
    }

    // Online mode - normal behavior
    if (isEditing.value && currentEvent.value) {
        router.put(route('teacher.calendar.update', currentEvent.value.id), eventData, {
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
        router.post(route('teacher.calendar.store'), eventData, {
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
        router.delete(route('teacher.calendar.destroy', eventId), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                processing.value = false;
            },
            onError: () => {
                processing.value = false;
                alert('Failed to delete event');
            }
        });
    }
}

// Close modal
function closeModal() {
    showModal.value = false;
    isEditing.value = false;
    currentEvent.value = null;
    form.value = {
        title: '',
        date: '',
        time: '',
        description: '',
        category: 'general',
        is_deadline: false,
        color: '#800000',
        target_audience: 'both',
        course_id: null,
    };
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
</script>

<template>
    <Head title="Calendar - Events & Announcements" />

    <TeacherLayout>
        <div class="space-y-6">
            <!-- Cache Indicator -->
            <div 
                v-if="isFromCache"
                class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-lg shadow-md"
            >
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-yellow-800 font-medium">📌 Viewing Cached Events (Offline Mode)</p>
                </div>
            </div>

            <!-- Success Message -->
            <div 
                v-if="showSuccessMessage"
                class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg shadow-md animate-fade-in"
            >
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-green-800 font-medium">{{ successMessage }}</p>
                    <button 
                        @click="showSuccessMessage = false"
                        class="ml-auto text-green-500 hover:text-green-700"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Page Header -->
            <div class="bg-gradient-to-r from-red-900 to-red-700 rounded-lg shadow-lg p-6 md:p-8 text-white">
                <div class="flex items-center gap-3 mb-2">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h1 class="text-2xl md:text-3xl font-bold">Events & Announcements Calendar</h1>
                </div>
                <p class="text-red-100">Manage course deadlines and announcements for your students</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Calendar -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow-md p-4 md:p-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                        <div class="flex gap-2">
                            <button 
                                @click="openCreateModal"
                                class="flex items-center gap-2 px-4 py-2 bg-red-900 hover:bg-red-800 text-white rounded-lg font-medium transition"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Create</span>
                            </button>
                        </div>
                    </div>

                    <FullCalendar :options="calendarOptions" />
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
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search:</label>
                            <input 
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search events..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent"
                            />
                        </div>

                        <!-- Color Legend -->
                        <div class="mb-6">
                            <p class="text-sm font-medium text-gray-700 mb-2">Color Legend:</p>
                            <div class="space-y-2">
                                <div v-for="cat in categories" :key="cat.value" class="flex items-center gap-2">
                                    <div class="w-4 h-4 rounded" :style="{ backgroundColor: cat.color }"></div>
                                    <span class="text-sm text-gray-700">{{ cat.label }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Events List -->
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            <div
                                v-for="event in filteredEvents.slice(0, 10)"
                                :key="event.id"
                                class="border rounded-lg p-3 hover:shadow-md transition cursor-pointer"
                                :class="{
                                    'border-l-4': true,
                                }"
                                :style="{ borderLeftColor: event.color }"
                                @click="handleEventClick({ event: { id: event.id } })"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-semibold text-sm text-gray-900">
                                                <span v-if="event.is_deadline" class="mr-1">📌</span>
                                                {{ event.title }}
                                            </h4>
                                            <span v-if="event.is_deadline" class="px-2 py-0.5 bg-orange-100 text-orange-800 text-xs rounded">
                                                Deadline
                                            </span>
                                            <span v-if="!event.is_own" class="px-2 py-0.5 bg-blue-100 text-blue-800 text-xs rounded">
                                                Admin
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-600 mt-1">
                                            {{ formatDate(event.date) }} • {{ formatTime(event.time) }}
                                        </p>
                                        <p v-if="event.course_name" class="text-xs text-gray-500 mt-1">
                                            📚 {{ event.course_name }}
                                        </p>
                                        <p class="text-xs text-gray-600 mt-1 line-clamp-2">{{ event.description }}</p>
                                    </div>
                                    <span 
                                        class="px-2 py-1 text-xs rounded"
                                        :style="{ backgroundColor: event.color + '20', color: event.color }"
                                    >
                                        {{ getCategoryLabel(event.category) }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="filteredEvents.length === 0" class="text-center py-6 text-gray-500 text-sm">
                                No events found
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Event Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50" @click.self="closeModal">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900">
                        {{ isEditing ? 'Edit Event' : 'Create Event' }}
                    </h3>
                    <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6">
                    <!-- Read-only notice for admin events -->
                    <div v-if="isEditing && currentEvent && !currentEvent.is_own" class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-sm text-blue-800">
                            ℹ️ This is an admin event. You can view details but cannot edit or delete it.
                        </p>
                        <p class="text-xs text-blue-600 mt-1">Created by: {{ currentEvent.created_by }}</p>
                    </div>

                    <form @submit.prevent="saveEvent" class="space-y-4">
                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                            <input
                                v-model="form.title"
                                type="text"
                                :disabled="isEditing && currentEvent && !currentEvent.is_own"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent disabled:bg-gray-100"
                                placeholder="Event title"
                                required
                            />
                        </div>

                        <!-- Date and Time -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                                <input
                                    v-model="form.date"
                                    type="date"
                                    :disabled="isEditing && currentEvent && !currentEvent.is_own"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent disabled:bg-gray-100"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Time (Optional)</label>
                                <input
                                    v-model="form.time"
                                    type="time"
                                    :disabled="isEditing && currentEvent && !currentEvent.is_own"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent disabled:bg-gray-100"
                                />
                            </div>
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                            <select
                                v-model="form.category"
                                :disabled="isEditing && currentEvent && !currentEvent.is_own"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent disabled:bg-gray-100"
                                required
                            >
                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                                    {{ cat.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Is Deadline Checkbox -->
                        <div>
                            <label class="flex items-center gap-2">
                                <input
                                    v-model="form.is_deadline"
                                    type="checkbox"
                                    :disabled="isEditing && currentEvent && !currentEvent.is_own"
                                    class="w-4 h-4 text-red-900 border-gray-300 rounded focus:ring-red-900 disabled:bg-gray-100"
                                />
                                <span class="text-sm font-medium text-gray-700">Mark as Deadline (Quiz, Activity, etc.)</span>
                            </label>
                        </div>

                        <!-- Target Audience -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Target Audience *</label>
                            <select
                                v-model="form.target_audience"
                                :disabled="isEditing && currentEvent && !currentEvent.is_own"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent disabled:bg-gray-100"
                                required
                            >
                                <option value="both">All Students (All Courses)</option>
                                <option value="students">Specific Course</option>
                            </select>
                        </div>

                        <!-- Course Selection -->
                        <div v-if="form.target_audience === 'students'">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Select Course *</label>
                            <select
                                v-model="form.course_id"
                                :disabled="isEditing && currentEvent && !currentEvent.is_own"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent disabled:bg-gray-100"
                                required
                            >
                                <option :value="null">Select a course...</option>
                                <option v-for="course in courses" :key="course.id" :value="course.id">
                                    {{ course.title }} {{ course.section ? `- ${course.section}` : '' }}
                                </option>
                            </select>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
                            <textarea
                                v-model="form.description"
                                :disabled="isEditing && currentEvent && !currentEvent.is_own"
                                rows="4"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-900 focus:border-transparent disabled:bg-gray-100"
                                placeholder="Event description"
                                required
                            ></textarea>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-between pt-4">
                            <button
                                v-if="isEditing && currentEvent && currentEvent.is_own"
                                type="button"
                                @click="deleteEvent(currentEvent.id)"
                                :disabled="processing"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition disabled:opacity-50"
                            >
                                Delete
                            </button>
                            <div class="flex gap-2 ml-auto">
                                <button
                                    type="button"
                                    @click="closeModal"
                                    :disabled="processing"
                                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium transition disabled:opacity-50"
                                >
                                    {{ isEditing && currentEvent && !currentEvent.is_own ? 'Close' : 'Cancel' }}
                                </button>
                                <button
                                    v-if="!isEditing || (currentEvent && currentEvent.is_own)"
                                    type="submit"
                                    :disabled="processing"
                                    class="px-4 py-2 bg-red-900 hover:bg-red-800 text-white rounded-lg font-medium transition disabled:opacity-50"
                                >
                                    {{ processing ? 'Saving...' : (isEditing ? 'Update' : 'Create') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </TeacherLayout>
</template>

<style scoped>
/* FullCalendar custom styles */
:deep(.fc) {
    font-family: inherit;
}

:deep(.fc .fc-button) {
    background-color: #7f1d1d;
    border-color: #7f1d1d;
    text-transform: capitalize;
    padding: 0.5rem 1rem;
}

:deep(.fc .fc-button:hover) {
    background-color: #991b1b;
    border-color: #991b1b;
}

:deep(.fc .fc-button:disabled) {
    opacity: 0.5;
}

:deep(.fc .fc-button-primary:not(:disabled):active),
:deep(.fc .fc-button-primary:not(:disabled).fc-button-active) {
    background-color: #991b1b;
    border-color: #991b1b;
}

:deep(.fc-theme-standard td),
:deep(.fc-theme-standard th) {
    border-color: #e5e7eb;
}

:deep(.fc-daygrid-day-number) {
    color: #374151;
    padding: 0.5rem;
}

:deep(.fc-col-header-cell) {
    background-color: #f9fafb;
    font-weight: 600;
}

:deep(.fc-event) {
    cursor: pointer;
    border-radius: 0.25rem;
    padding: 2px 4px;
}

:deep(.fc-daygrid-event) {
    margin: 1px 2px;
}

@media (max-width: 768px) {
    :deep(.fc .fc-toolbar) {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    :deep(.fc .fc-toolbar-title) {
        font-size: 1.25rem;
    }
}
</style>

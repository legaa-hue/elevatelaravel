<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    metrics: {
        type: Object,
        required: true
    },
    events: {
        type: Array,
        default: () => []
    },
    courseStats: {
        type: Object,
        required: true
    },
    courses: {
        type: Array,
        default: () => []
    },
    activeAcademicYear: {
        type: Object,
        default: null
    },
    recentActivity: {
        type: Array,
        default: () => []
    },
    todayStats: {
        type: Object,
        default: () => ({
            actions: 0,
            logins: 0,
            new_users: 0
        })
    }
});

// Course Management
const selectedStatus = ref('all');
const selectedProgram = ref('all');
const searchQuery = ref('');
const autoRefresh = ref(true);

const filteredCourses = computed(() => {
    let filtered = props.courses;
    
    // Filter by status
    if (selectedStatus.value !== 'all') {
        filtered = filtered.filter(course => course.status === selectedStatus.value);
    }
    
    // Filter by program (based on course code prefixes)
    if (selectedProgram.value !== 'all') {
        const programPrefixes = {
            'ADMINISTRATION': ['EDUAS'],
            'MATHEMATICS': ['EDUMT'],
            'SCIENCE': ['EDUSC'],
            'FILIPINO': ['EDUFI'],
            'MAPEH': ['EDUMAP'],
            'TLE': ['EDUTLE'],
            'HISTORY': ['EDUHI'],
            'ENGLISH': ['EDUEN'],
            'PRESCHOOL': ['EDUPRE'],
            'GUIDANCE': ['EDUGC'],
            'ALTERNATIVE': ['EDUAL'],
            'SPECIAL': ['EDUSN']
        };
        
        const prefixes = programPrefixes[selectedProgram.value] || [];
        filtered = filtered.filter(course => {
            // Check if course title starts with any of the program's prefixes
            // Also include common courses (EDUCN, EDUC) for all programs
            return prefixes.some(prefix => course.courseName.startsWith(prefix)) ||
                   course.courseName.startsWith('EDUCN') ||
                   course.courseName.startsWith('EDUC ');
        });
    }
    
    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(course =>
            course.courseName.toLowerCase().includes(query) ||
            course.instructorName.toLowerCase().includes(query) ||
            course.userId.toLowerCase().includes(query)
        );
    }
    
    return filtered;
});

// Programs data (same format used in TeacherLayout) - used to display courses for selected program
const programsData = {
    ADMINISTRATION: {
        label: 'MASTER OF ARTS IN EDUCATION MAJOR IN ADMINISTRATION AND SUPERVISION',
        basic: [
            'EDUCN 204 – Statistics in Education',
            'EDUCN 210 – Methods in Educational Research',
            'EDUCN 212 – Foundations of Education'
        ],
        major: [
            'EDUAS 201 – Educational Leadership and Management',
            'EDUAS 202 – Educational Planning and Development',
            'EDUAS 203 – Dynamics, Organization, Theory, Research and Practice in Educational Administration',
            'EDUAS 204 – Media and Technology Education with AI Integration',
            'EDUAS 205 – Instructional Supervision and Curriculum Development',
            'EDUAS 206 – School Personnel Administration and its Legal Aspects',
            'EDUAS 207 – Current Issues and Problems in Philippine Education'
        ],
        thesis: [
            'EDUC 229 – Thesis Seminar',
            'EDUC 300 – Thesis Writing'
        ]
    },
    MATHEMATICS: {
        label: 'MASTER OF ARTS IN EDUCATION MAJOR IN MATHEMATICS',
        basic: [
            'EDUCN 204 – Statistics in Education',
            'EDUCN 210 – Methods in Educational Research',
            'EDUCN 212 – Foundations of Education'
        ],
        major: [
            'EDUMT 201 – Advanced Algebra',
            'EDUMT 202 – Advanced Geometry',
            'EDUMT 203 – Advanced Calculus',
            'EDUMT 204 – Modern Mathematics',
            'EDUMT 205 – Seminar in Mathematics Education',
            'EDUMT 206 – Probability and Statistics',
            'EDUMT 207 – Research Problems in Mathematics Education'
        ],
        thesis: [
            'EDUC 229 – Thesis Seminar',
            'EDUC 300 – Thesis Writing'
        ]
    },
    SCIENCE: {
        label: 'MASTER OF ARTS IN EDUCATION MAJOR IN SCIENCE',
        basic: [
            'EDUCN 204 – Statistics in Education',
            'EDUCN 210 – Methods in Educational Research',
            'EDUCN 212 – Foundations of Education'
        ],
        major: [
            'EDUSC 201 – Research in Science Education',
            'EDUSC 202 – Advanced General Science',
            'EDUSC 203 – Modern Physics',
            'EDUSC 204 – Chemistry of the Environment',
            'EDUSC 205 – Biology and Ecology',
            'EDUSC 206 – Science Curriculum and Instruction',
            'EDUSC 207 – Seminar in Environmental Science'
        ],
        thesis: [
            'EDUC 229 – Thesis Seminar',
            'EDUC 300 – Thesis Writing'
        ]
    },
    FILIPINO: {
        label: 'MASTER OF ARTS IN EDUCATION MAJOR IN FILIPINO',
        basic: [
            'EDUCN 204 – Statistics in Education',
            'EDUCN 210 – Methods in Educational Research',
            'EDUCN 212 – Foundations of Education'
        ],
        major: [
            'EDUFI 201 – Pagpaplanong Pampagtuturo ng Filipino',
            'EDUFI 202 – Pagsasaling Pampanitikan',
            'EDUFI 203 – Barayti at Baryasyon ng Filipino',
            'EDUFI 204 – Pagtuturo ng Panitikan',
            'EDUFI 205 – Seminar sa Pagsasalin at Panitikan',
            'EDUFI 206 – Pamamaraan ng Pagtuturo ng Filipino',
            'EDUFI 207 – Pananaliksik sa Filipino'
        ],
        thesis: [
            'EDUC 229 – Thesis Seminar',
            'EDUC 300 – Thesis Writing'
        ]
    },
    MAPEH: {
        label: 'MASTER OF ARTS IN EDUCATION MAJOR IN MAPEH',
        basic: [
            'EDUCN 204 – Statistics in Education',
            'EDUCN 210 – Methods in Educational Research',
            'EDUCN 212 – Foundations of Education'
        ],
        major: [
            'EDUMAP 201 – Music Education',
            'EDUMAP 202 – Arts Education',
            'EDUMAP 203 – Physical Education Pedagogy',
            'EDUMAP 204 – Health Education',
            'EDUMAP 205 – Movement and Dance Education',
            'EDUMAP 206 – Sport Science and Coaching',
            'EDUMAP 207 – Seminar in MAPEH'
        ],
        thesis: [
            'EDUC 229 – Thesis Seminar',
            'EDUC 300 – Thesis Writing'
        ]
    },
    TLE: {
        label: 'MASTER OF ARTS IN EDUCATION MAJOR IN TLE',
        basic: [
            'EDUCN 204 – Statistics in Education',
            'EDUCN 210 – Methods in Educational Research',
            'EDUCN 212 – Foundations of Education'
        ],
        major: [
            'EDUTLE 201 – Technology and Livelihood Education Curriculum',
            'EDUTLE 202 – Advanced Industrial Arts',
            'EDUTLE 203 – Agricultural Education',
            'EDUTLE 204 – Home Economics Education',
            'EDUTLE 205 – Industrial Technology Integration',
            'EDUTLE 206 – Seminar in TLE Research'
        ],
        thesis: [
            'EDUC 229 – Thesis Seminar',
            'EDUC 300 – Thesis Writing'
        ]
    },
    HISTORY: {
        label: 'MASTER OF ARTS IN EDUCATION MAJOR IN HISTORY',
        basic: [
            'EDUCN 204 – Statistics in Education',
            'EDUCN 210 – Methods in Educational Research',
            'EDUCN 212 – Foundations of Education'
        ],
        major: [
            'EDUHI 201 – Philippine History and Historiography',
            'EDUHI 202 – World History Perspectives',
            'EDUHI 203 – Historical Methods and Research',
            'EDUHI 204 – Curriculum in History Education',
            'EDUHI 205 – Seminar in History Education'
        ],
        thesis: [
            'EDUC 229 – Thesis Seminar',
            'EDUC 300 – Thesis Writing'
        ]
    },
    ENGLISH: {
        label: 'MASTER OF ARTS IN EDUCATION MAJOR IN ENGLISH',
        basic: [
            'EDUCN 204 – Statistics in Education',
            'EDUCN 210 – Methods in Educational Research',
            'EDUCN 212 – Foundations of Education'
        ],
        major: [
            'EDUEN 201 – Linguistics in Language Teaching',
            'EDUEN 202 – Teaching Literature',
            'EDUEN 203 – Curriculum Development in English',
            'EDUEN 204 – Language Assessment and Testing',
            'EDUEN 205 – Seminar in English Language Education'
        ],
        thesis: [
            'EDUC 229 – Thesis Seminar',
            'EDUC 300 – Thesis Writing'
        ]
    },
    PRESCHOOL: {
        label: 'MASTER OF ARTS IN EDUCATION MAJOR IN PRESCHOOL EDUCATION',
        basic: [
            'EDUCN 204 – Statistics in Education',
            'EDUCN 210 – Methods in Educational Research',
            'EDUCN 212 – Foundations of Education'
        ],
        major: [
            'EDUPRE 201 – Early Childhood Curriculum',
            'EDUPRE 202 – Play and Learning',
            'EDUPRE 203 – Assessment of Young Children',
            'EDUPRE 204 – Health and Nutrition in Early Childhood'
        ],
        thesis: [
            'EDUC 229 – Thesis Seminar',
            'EDUC 300 – Thesis Writing'
        ]
    },
    GUIDANCE: {
        label: 'MASTER OF ARTS IN EDUCATION MAJOR IN GUIDANCE & COUNSELING',
        basic: [
            'EDUCN 204 – Statistics in Education',
            'EDUCN 210 – Methods in Educational Research',
            'EDUCN 212 – Foundations of Education'
        ],
        major: [
            'EDUGC 201 – Theories of Counseling',
            'EDUGC 202 – Group Counseling Methods',
            'EDUGC 203 – Career Development and Guidance',
            'EDUGC 204 – Assessment in Guidance'
        ],
        thesis: [
            'EDUC 229 – Thesis Seminar',
            'EDUC 300 – Thesis Writing'
        ]
    },
    ALTERNATIVE: {
        label: 'MASTER OF ARTS IN EDUCATION MAJOR IN ALTERNATIVE LEARNING SYSTEM',
        basic: [
            'EDUCN 204 – Statistics in Education',
            'EDUCN 210 – Methods in Educational Research',
            'EDUCN 212 – Foundations of Education'
        ],
        major: [
            'EDUAL 201 – Alternative Learning Systems Theory',
            'EDUAL 202 – Curriculum for ALS',
            'EDUAL 203 – Community-Based Learning Strategies'
        ],
        thesis: [
            'EDUC 229 – Thesis Seminar',
            'EDUC 300 – Thesis Writing'
        ]
    },
    SPECIAL: {
        label: 'MASTER OF ARTS IN EDUCATION MAJOR IN SPECIAL NEEDS EDUCATION',
        basic: [
            'EDUCN 204 – Statistics in Education',
            'EDUCN 210 – Methods in Educational Research',
            'EDUCN 212 – Foundations of Education'
        ],
        major: [
            'EDUSN 201 – Inclusive Education Principles',
            'EDUSN 202 – Assessment and Intervention',
            'EDUSN 203 – Curriculum Adaptation and Differentiation'
        ],
        thesis: [
            'EDUC 229 – Thesis Seminar',
            'EDUC 300 – Thesis Writing'
        ]
    }
};

const selectedProgramCourses = computed(() => {
    if (selectedProgram.value === 'all') return null;
    return programsData[selectedProgram.value] || null;
});

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const formatTime = (timeString) => {
    if (!timeString) return '';
    return new Date('2000-01-01 ' + timeString).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
};

// Auto-refresh simulation
onMounted(() => {
    if (autoRefresh.value) {
        setInterval(() => {
            // Simulate refresh - replace with actual API call
            console.log('Auto-refreshing dashboard data...');
        }, 30000); // Refresh every 30 seconds
    }
});

// Actions
const approveCourse = (courseId) => {
    if (confirm('Are you sure you want to approve this course?')) {
        router.post(`/admin/courses/${courseId}/approve`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                console.log('Course approved successfully');
            }
        });
    }
};

const rejectCourse = (courseId) => {
    if (confirm('Are you sure you want to reject this course?')) {
        router.post(`/admin/courses/${courseId}/reject`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                console.log('Course rejected successfully');
            }
        });
    }
};

const archiveCourse = (courseId) => {
    if (confirm('Are you sure you want to archive this course?')) {
        router.post(`/admin/courses/${courseId}/archive`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                console.log('Course archived successfully');
            }
        });
    }
};

const viewDetails = (courseId) => {
    console.log('Viewing course details:', courseId);
    // Navigate to course details page
};

const getStatusColor = (status) => {
    switch (status) {
        case 'active':
            return 'bg-green-100 text-green-800 border-green-200';
        case 'pending':
            return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'archived':
            return 'bg-gray-100 text-gray-800 border-gray-200';
        default:
            return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

const getCategoryColor = (category) => {
    const colors = {
        'Academic': 'bg-blue-100 text-blue-800',
        'Event': 'bg-purple-100 text-purple-800',
        'Holiday': 'bg-green-100 text-green-800',
        'Meeting': 'bg-yellow-100 text-yellow-800',
        'Exam': 'bg-red-100 text-red-800',
        'Other': 'bg-gray-100 text-gray-800'
    };
    return colors[category] || colors['Other'];
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout>
        <!-- Summary Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Active Users Card -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow duration-300">
                <div class="h-2 bg-red-900"></div>
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Users</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ metrics.activeUsers }}</p>
                        </div>
                        <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center">
                            <span class="text-2xl">🧑‍💻</span>
                        </div>
                    </div>

                    <!-- Program Courses Preview -->
                    <div v-if="selectedProgramCourses" class="ml-4 bg-white border border-gray-200 rounded-md shadow-sm p-3 max-w-lg">
                        <div class="text-sm font-semibold text-gray-800 mb-2">{{ selectedProgramCourses.label }}</div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-sm">
                            <div>
                                <div class="text-xs font-semibold text-gray-600">Basic</div>
                                <ul class="list-disc list-inside mt-1 text-gray-700">
                                    <li v-for="(c, idx) in selectedProgramCourses.basic" :key="'basic-'+idx">{{ c }}</li>
                                </ul>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-gray-600">Major</div>
                                <ul class="list-disc list-inside mt-1 text-gray-700">
                                    <li v-for="(c, idx) in selectedProgramCourses.major" :key="'major-'+idx">{{ c }}</li>
                                </ul>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-gray-600">Thesis</div>
                                <ul class="list-disc list-inside mt-1 text-gray-700">
                                    <li v-for="(c, idx) in selectedProgramCourses.thesis" :key="'thesis-'+idx">{{ c }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span :class="metrics.userGrowth >= 0 ? 'text-green-600' : 'text-red-600'" class="font-semibold">
                            {{ metrics.userGrowth >= 0 ? '↑' : '↓' }} {{ Math.abs(metrics.userGrowth) }}%
                        </span>
                        <span class="text-gray-600 ml-2">from last month</span>
                    </div>
                </div>
            </div>

            <!-- Active Courses Card -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow duration-300">
                <div class="h-2 bg-yellow-500"></div>
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Active Courses</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ courseStats.total }}</p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-50 rounded-full flex items-center justify-center">
                            <span class="text-2xl">📚</span>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="text-gray-600">{{ courseStats.active }} active • {{ courseStats.pending }} pending</span>
                    </div>
                </div>
            </div>

            <!-- Active Teachers Card -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow duration-300">
                <div class="h-2 bg-red-900"></div>
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Teachers</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ metrics.activeInstructors }}</p>
                        </div>
                        <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center">
                            <span class="text-2xl">👨‍🏫</span>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span :class="metrics.teacherGrowth >= 0 ? 'text-green-600' : 'text-red-600'" class="font-semibold">
                            {{ metrics.teacherGrowth >= 0 ? '↑' : '↓' }} {{ Math.abs(metrics.teacherGrowth) }}%
                        </span>
                        <span class="text-gray-600 ml-2">from last month</span>
                    </div>
                </div>
            </div>

            <!-- Active Students Card -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow duration-300">
                <div class="h-2 bg-yellow-500"></div>
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Students</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ metrics.activeStudents }}</p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-50 rounded-full flex items-center justify-center">
                            <span class="text-2xl">👩‍🎓</span>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span :class="metrics.studentGrowth >= 0 ? 'text-green-600' : 'text-red-600'" class="font-semibold">
                            {{ metrics.studentGrowth >= 0 ? '↑' : '↓' }} {{ Math.abs(metrics.studentGrowth) }}%
                        </span>
                        <span class="text-gray-600 ml-2">from last month</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Events and Course Status Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Events and Announcements -->
            <div class="lg:col-span-2 bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow duration-300">
                <div class="h-2 bg-red-900"></div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">📅 Events & Announcements</h3>
                        <button class="text-sm text-red-900 hover:text-red-800 font-semibold hover:underline">View All</button>
                    </div>
                    <div class="space-y-4">
                        <!-- Real Calendar Events -->
                        <div
                            v-for="event in events.slice(0, 3)"
                            :key="event.id"
                            class="flex items-start space-x-4 p-4 bg-gray-50 rounded-lg border border-gray-200 hover:shadow-md transition-shadow duration-200"
                        >
                            <div class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center" :style="{ backgroundColor: event.color + '20' }">
                                <span class="text-xl">�</span>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h4 class="text-sm font-semibold text-gray-900">{{ event.title }}</h4>
                                    <span :class="getCategoryColor(event.category)" class="px-2 py-0.5 text-xs font-medium rounded-full">
                                        {{ event.category }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-700 mt-1">{{ event.description || 'No description' }}</p>
                                <p class="text-xs text-gray-500 mt-2">
                                    {{ formatDate(event.date) }} 
                                    <span v-if="event.time">• {{ formatTime(event.time) }}</span>
                                    <span class="ml-2">• by {{ event.created_by }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Empty state if no events -->
                        <div v-if="events.length === 0" class="text-center py-8">
                            <span class="text-4xl">📅</span>
                            <p class="text-sm text-gray-500 mt-2">No upcoming events</p>
                            <p class="text-xs text-gray-400 mt-1">Create events in the Calendar page</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Course Status Distribution -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow duration-300">
                <div class="h-2 bg-yellow-500"></div>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Course Status</h3>
                    <div class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Active Courses</span>
                                <span class="text-sm font-bold text-green-600">{{ courseStats.active }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div 
                                    class="bg-green-500 h-3 rounded-full transition-all" 
                                    :style="{ width: courseStats.total > 0 ? (courseStats.active / courseStats.total * 100) + '%' : '0%' }"
                                ></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Pending Courses</span>
                                <span class="text-sm font-bold text-yellow-600">{{ courseStats.pending }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div 
                                    class="bg-yellow-500 h-3 rounded-full transition-all" 
                                    :style="{ width: courseStats.total > 0 ? (courseStats.pending / courseStats.total * 100) + '%' : '0%' }"
                                ></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Archived Courses</span>
                                <span class="text-sm font-bold text-gray-600">{{ courseStats.archived }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div 
                                    class="bg-gray-400 h-3 rounded-full transition-all" 
                                    :style="{ width: courseStats.total > 0 ? (courseStats.archived / courseStats.total * 100) + '%' : '0%' }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Summary -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900">Total Courses</span>
                            <span class="text-2xl font-bold text-gray-900">{{ courseStats.total }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Monitoring Panel -->
        <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow duration-300">
            <div class="h-2 bg-red-900"></div>
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">📚 Course Monitoring</h3>
                    <div class="flex items-center space-x-2">
                        <label class="flex items-center space-x-2 text-sm text-gray-900">
                            <input
                                type="checkbox"
                                v-model="autoRefresh"
                                class="rounded border-gray-300 text-red-900 focus:ring-red-900 bg-white"
                            />
                            <span class="font-medium">Auto-refresh</span>
                        </label>
                    </div>
                </div>

                <!-- Filters and Search -->
                <div class="flex flex-col sm:flex-row gap-4 mb-6">
                    <!-- Status Filter -->
                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-semibold text-gray-900">Status:</label>
                        <select
                            v-model="selectedStatus"
                            class="rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-red-900 focus:ring-red-900 text-sm font-medium"
                        >
                            <option value="all">All</option>
                            <option value="active">Active</option>
                            <option value="pending">Pending</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>

                    <!-- Program Filter -->
                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-semibold text-gray-900">Program:</label>
                        <select
                            v-model="selectedProgram"
                            class="rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-red-900 focus:ring-red-900 text-sm font-medium"
                        >
                            <option value="all">All Programs</option>
                            <option value="ADMINISTRATION">MASTER OF ARTS IN EDUCATION MAJOR IN ADMINISTRATION & SUPERVISION</option>
                            <option value="MATHEMATICS">MASTER OF ARTS IN EDUCATION MAJOR IN MATHEMATICS</option>
                            <option value="SCIENCE">MASTER OF ARTS IN EDUCATION MAJOR IN SCIENCE</option>
                            <option value="FILIPINO">MASTER OF ARTS IN EDUCATION MAJOR IN FILIPINO</option>
                            <option value="MAPEH">MASTER OF ARTS IN EDUCATION MAJOR IN MAPEH</option>
                            <option value="TLE">MASTER OF ARTS IN EDUCATION MAJOR IN TLE</option>
                            <option value="HISTORY">MASTER OF ARTS IN EDUCATION MAJOR IN HISTORY</option>
                            <option value="ENGLISH">MASTER OF ARTS IN EDUCATION MAJOR IN ENGLISH</option>
                            <option value="PRESCHOOL">MASTER OF ARTS IN EDUCATION MAJOR IN PRESCHOOL EDUCATION</option>
                            <option value="GUIDANCE">MASTER OF ARTS IN EDUCATION MAJOR IN GUIDANCE & COUNSELING</option>
                            <option value="ALTERNATIVE">MASTER OF ARTS IN EDUCATION MAJOR IN ALTERNATIVE LEARNING SYSTEM</option>
                            <option value="SPECIAL">MASTER OF ARTS IN EDUCATION MAJOR IN SPECIAL NEEDS EDUCATION</option>
                        </select>
                    </div>

                    <!-- Search Bar -->
                    <div class="flex-1">
                        <div class="relative">
                            <input
                                type="text"
                                v-model="searchQuery"
                                placeholder="Search by course name, instructor, or ID..."
                                class="w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-red-900 focus:ring-red-900 pl-10 font-medium"
                            />
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">User ID</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Course Name</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Instructor</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Students</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Date Created</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-900 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="course in filteredCourses" :key="course.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-4 text-sm font-semibold text-gray-900">{{ course.userId }}</td>
                                <td class="px-4 py-4 text-sm font-medium text-gray-900">{{ course.courseName }}</td>
                                <td class="px-4 py-4 text-sm text-gray-900">{{ course.instructorName }}</td>
                                <td class="px-4 py-4 text-sm text-gray-900 text-center font-medium">{{ course.studentCount }}</td>
                                <td class="px-4 py-4 text-sm text-gray-900">{{ course.dateCreated }}</td>
                                <td class="px-4 py-4">
                                    <span :class="[
                                        'px-3 py-1 text-xs font-semibold rounded-full border capitalize',
                                        getStatusColor(course.status)
                                    ]">
                                        {{ course.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center space-x-2">
                                        <button
                                            v-if="course.status === 'pending'"
                                            @click="approveCourse(course.id)"
                                            class="p-2 text-green-700 hover:bg-green-100 rounded-lg transition-colors"
                                            title="Approve"
                                            aria-label="Approve Course"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="viewDetails(course.id)"
                                            class="p-2 text-blue-700 hover:bg-blue-100 rounded-lg transition-colors"
                                            title="View Details"
                                            aria-label="View Course Details"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button
                                            v-if="course.status !== 'archived'"
                                            @click="archiveCourse(course.id)"
                                            class="p-2 text-yellow-700 hover:bg-yellow-100 rounded-lg transition-colors"
                                            title="Archive"
                                            aria-label="Archive Course"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="deleteCourse(course.id)"
                                            class="p-2 text-red-900 hover:bg-red-100 rounded-lg transition-colors"
                                            title="Delete"
                                            aria-label="Delete Course"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div v-if="filteredCourses.length === 0" class="text-center py-12 bg-white">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <h3 class="mt-2 text-sm font-semibold text-gray-900">No courses found</h3>
                        <p class="mt-1 text-sm text-gray-600">Try adjusting your search or filter criteria.</p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

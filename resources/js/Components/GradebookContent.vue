<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    course: Object,
    students: Array,
    classworks: Array,
});

// Main settings
const midtermPercentage = ref(50);
const finalsPercentage = ref(50);
const activeGradingPeriod = ref(null); // 'midterm' or 'finals'

// Default table structure for Midterm/Finals
const createDefaultTables = () => ({
    asynchronous: {
        name: 'Asynchronous',
        percentage: 35,
        columns: [
            {
                id: 'written-works',
                name: 'Written Works',
                percentage: 15,
                isFixed: true,
                subcolumns: [] // Auto-populated from classworks
            }
        ]
    },
    synchronous: {
        name: 'Synchronous',
        percentage: 35,
        columns: []
    },
    majorExam: {
        name: 'Major Exam',
        percentage: 30,
        columns: [
            {
                id: 'exam-1',
                name: 'Midterm Exam',
                percentage: 30,
                subcolumns: []
            }
        ]
    }
});

const midtermTables = ref(createDefaultTables());
const finalsTables = ref(createDefaultTables());

// Get active tables based on current grading period
const activeTables = computed(() => {
    return activeGradingPeriod.value === 'midterm' ? midtermTables.value : 
           activeGradingPeriod.value === 'finals' ? finalsTables.value : null;
});

// Auto-populate Written Works subcolumns from classworks
const populateWrittenWorksFromClassworks = () => {
    const writtenWorksTypes = ['assignment', 'quiz', 'activity'];
    const filteredClassworks = props.classworks.filter(cw => writtenWorksTypes.includes(cw.type));
    
    const subcolumns = filteredClassworks.map(cw => ({
        id: `classwork-${cw.id}`,
        name: cw.title,
        classworkId: cw.id,
        maxPoints: cw.points || 100,
        isAutoFetched: true
    }));
    
    // Update both midterm and finals
    const writtenWorksColumnMidterm = midtermTables.value.asynchronous.columns.find(c => c.id === 'written-works');
    const writtenWorksColumnFinals = finalsTables.value.asynchronous.columns.find(c => c.id === 'written-works');
    
    if (writtenWorksColumnMidterm) {
        writtenWorksColumnMidterm.subcolumns = subcolumns;
    }
    if (writtenWorksColumnFinals) {
        writtenWorksColumnFinals.subcolumns = subcolumns;
    }
};

// Initialize on mount
populateWrittenWorksFromClassworks();

// Student grades storage
const studentGrades = ref({});

// Initialize student grades
props.students.forEach(student => {
    studentGrades.value[student.id] = {
        midterm: {},
        finals: {}
    };
});

// Open grading period
const openGradingPeriod = (period) => {
    activeGradingPeriod.value = activeGradingPeriod.value === period ? null : period;
};

// Validation: Check if midterm + finals percentage != 100%
const gradingPeriodWarning = computed(() => {
    const total = midtermPercentage.value + finalsPercentage.value;
    if (total !== 100) {
        return `Midterm + Finals must equal 100% (currently ${total}%)`;
    }
    return null;
});

// Validate and auto-correct grading period percentages
const validateGradingPeriodPercentage = (period) => {
    if (period === 'midterm') {
        if (isNaN(midtermPercentage.value) || midtermPercentage.value < 0) {
            midtermPercentage.value = 0;
        } else if (midtermPercentage.value > 100) {
            midtermPercentage.value = 100;
            finalsPercentage.value = 0;
        } else {
            finalsPercentage.value = 100 - midtermPercentage.value;
        }
    } else {
        if (isNaN(finalsPercentage.value) || finalsPercentage.value < 0) {
            finalsPercentage.value = 0;
        } else if (finalsPercentage.value > 100) {
            finalsPercentage.value = 100;
            midtermPercentage.value = 0;
        } else {
            midtermPercentage.value = 100 - finalsPercentage.value;
        }
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- Grading Period Percentage Settings -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Grading Period Settings</h2>
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Midterm Percentage
                    </label>
                    <div class="flex items-center gap-2">
                        <input 
                            v-model.number="midtermPercentage"
                            @blur="validateGradingPeriodPercentage('midterm')"
                            type="number"
                            min="0"
                            max="100"
                            class="w-32 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        />
                        <span class="text-gray-600">%</span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Finals Percentage
                    </label>
                    <div class="flex items-center gap-2">
                        <input 
                            v-model.number="finalsPercentage"
                            @blur="validateGradingPeriodPercentage('finals')"
                            type="number"
                            min="0"
                            max="100"
                            class="w-32 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        />
                        <span class="text-gray-600">%</span>
                    </div>
                </div>
            </div>
            <div v-if="gradingPeriodWarning" class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p class="text-sm text-yellow-800">{{ gradingPeriodWarning }}</p>
                </div>
            </div>
        </div>

        <!-- Grading Period Cards -->
        <div class="grid grid-cols-2 gap-6">
            <!-- Midterm Card -->
            <button
                @click="openGradingPeriod('midterm')"
                class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-lg p-8 shadow-lg hover:shadow-xl transition transform hover:scale-105"
                :class="{ 'ring-4 ring-green-300': activeGradingPeriod === 'midterm' }"
            >
                <div class="text-6xl mb-4">🟩</div>
                <h3 class="text-2xl font-bold mb-2">Midterm</h3>
                <p class="text-green-100">{{ midtermPercentage }}% of Final Grade</p>
            </button>

            <!-- Finals Card -->
            <button
                @click="openGradingPeriod('finals')"
                class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg p-8 shadow-lg hover:shadow-xl transition transform hover:scale-105"
                :class="{ 'ring-4 ring-blue-300': activeGradingPeriod === 'finals' }"
            >
                <div class="text-6xl mb-4">🟦</div>
                <h3 class="text-2xl font-bold mb-2">Finals</h3>
                <p class="text-blue-100">{{ finalsPercentage }}% of Final Grade</p>
            </button>
        </div>

        <!-- Message when no period selected -->
        <div v-if="!activeGradingPeriod" class="bg-gray-50 rounded-lg p-12 text-center">
            <div class="text-6xl mb-4">📊</div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">Select a Grading Period</h3>
            <p class="text-gray-600">Click on Midterm or Finals above to start entering grades</p>
        </div>

        <!-- Active Period Content -->
        <div v-else class="space-y-4">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-blue-800 font-medium">
                    Now viewing: <span class="font-bold">{{ activeGradingPeriod === 'midterm' ? 'Midterm' : 'Finals' }}</span> grades
                </p>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Grade Tables</h3>
                <p class="text-gray-600 mb-4">Gradebook tables will appear here. You can customize columns and enter student grades.</p>
                
                <!-- Placeholder for students list -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Student Name</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-700">Email</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-700">Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="student in students" :key="student.id" class="border-t border-gray-200">
                                <td class="px-4 py-3 text-gray-900">{{ student.name }}</td>
                                <td class="px-4 py-3 text-gray-600 text-center">{{ student.email }}</td>
                                <td class="px-4 py-3 text-center">
                                    <input 
                                        type="number"
                                        class="w-20 px-2 py-1 border border-gray-300 rounded text-center"
                                        placeholder="--"
                                    />
                                </td>
                            </tr>
                            <tr v-if="students.length === 0">
                                <td colspan="3" class="px-4 py-8 text-center text-gray-500">
                                    No students enrolled in this course yet
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

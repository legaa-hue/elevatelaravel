<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    course: Object,
    students: Array,
    classworks: Array,
    submissions: Array,
    gradebook: { type: Object, default: null }
});

// Debug: Check if students are being passed
console.log('Gradebook Students Prop:', props.students);
console.log('Students Count:', props.students?.length);

// Get current user to determine layout
const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const isAdmin = computed(() => currentUser.value?.role === 'admin');

// Use appropriate layout based on user role
const LayoutComponent = computed(() => isAdmin.value ? AdminLayout : TeacherLayout);

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
                maxPoints: 100,
                subcolumns: []
            }
        ]
    }
});

const midtermTables = ref(createDefaultTables());
const finalsTables = ref(createDefaultTables());

// Custom tables for each grading period
const midtermCustomTables = ref([]);
const finalsCustomTables = ref([]);

// Get active tables based on current grading period
const activeTables = computed(() => {
    return activeGradingPeriod.value === 'midterm' ? midtermTables.value : 
           activeGradingPeriod.value === 'finals' ? finalsTables.value : null;
});

// Get active custom tables based on current grading period
const activeCustomTables = computed(() => {
    return activeGradingPeriod.value === 'midterm' ? midtermCustomTables.value : 
           activeGradingPeriod.value === 'finals' ? finalsCustomTables.value : [];
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

// Helper to deep merge saved table data with default structure
const mergeTableData = (defaultTable, savedTable) => {
    if (!savedTable) return defaultTable;
    
    return {
        ...defaultTable,
        ...savedTable,
        columns: savedTable.columns || defaultTable.columns,
    };
};

// Load existing saved gradebook (if any)
const loadGradebookFromProps = () => {
    const gb = props.gradebook ?? props.course?.gradebook ?? null;
    if (!gb) return;

    // Load percentages
    if (gb.midtermPercentage !== undefined) midtermPercentage.value = gb.midtermPercentage;
    if (gb.finalsPercentage !== undefined) finalsPercentage.value = gb.finalsPercentage;

    // Load saved tables/structure for midterm/finals
    // The saved structure has { tables: {...}, custom: [...] }
    if (gb.midterm && gb.midterm.tables) {
        const savedTables = gb.midterm.tables;
        
        // Check if it has the nested 'tables' property (new structure)
        if (savedTables.tables) {
            // Merge saved data with default structure to preserve all properties
            if (savedTables.tables.asynchronous) {
                midtermTables.value.asynchronous = mergeTableData(midtermTables.value.asynchronous, savedTables.tables.asynchronous);
            }
            if (savedTables.tables.synchronous) {
                midtermTables.value.synchronous = mergeTableData(midtermTables.value.synchronous, savedTables.tables.synchronous);
            }
            if (savedTables.tables.majorExam) {
                midtermTables.value.majorExam = mergeTableData(midtermTables.value.majorExam, savedTables.tables.majorExam);
            }
            if (savedTables.custom) {
                midtermCustomTables.value = savedTables.custom;
            }
        } else {
            // Old structure - direct tables, merge each table
            if (savedTables.asynchronous) {
                midtermTables.value.asynchronous = mergeTableData(midtermTables.value.asynchronous, savedTables.asynchronous);
            }
            if (savedTables.synchronous) {
                midtermTables.value.synchronous = mergeTableData(midtermTables.value.synchronous, savedTables.synchronous);
            }
            if (savedTables.majorExam) {
                midtermTables.value.majorExam = mergeTableData(midtermTables.value.majorExam, savedTables.majorExam);
            }
        }
    }
    
    if (gb.finals && gb.finals.tables) {
        const savedTables = gb.finals.tables;
        
        // Check if it has the nested 'tables' property (new structure)
        if (savedTables.tables) {
            // Merge saved data with default structure to preserve all properties
            if (savedTables.tables.asynchronous) {
                finalsTables.value.asynchronous = mergeTableData(finalsTables.value.asynchronous, savedTables.tables.asynchronous);
            }
            if (savedTables.tables.synchronous) {
                finalsTables.value.synchronous = mergeTableData(finalsTables.value.synchronous, savedTables.tables.synchronous);
            }
            if (savedTables.tables.majorExam) {
                finalsTables.value.majorExam = mergeTableData(finalsTables.value.majorExam, savedTables.tables.majorExam);
            }
            if (savedTables.custom) {
                finalsCustomTables.value = savedTables.custom;
            }
        } else {
            // Old structure - direct tables, merge each table
            if (savedTables.asynchronous) {
                finalsTables.value.asynchronous = mergeTableData(finalsTables.value.asynchronous, savedTables.asynchronous);
            }
            if (savedTables.synchronous) {
                finalsTables.value.synchronous = mergeTableData(finalsTables.value.synchronous, savedTables.synchronous);
            }
            if (savedTables.majorExam) {
                finalsTables.value.majorExam = mergeTableData(finalsTables.value.majorExam, savedTables.majorExam);
            }
        }
    }

    // Load saved grades
    if (gb.midterm && gb.midterm.grades) {
        Object.keys(gb.midterm.grades).forEach(studentId => {
            if (!studentGrades.value[studentId]) studentGrades.value[studentId] = { midterm: {}, finals: {} };
            studentGrades.value[studentId].midterm = gb.midterm.grades[studentId];
        });
    }
    if (gb.finals && gb.finals.grades) {
        Object.keys(gb.finals.grades).forEach(studentId => {
            if (!studentGrades.value[studentId]) studentGrades.value[studentId] = { midterm: {}, finals: {} };
            studentGrades.value[studentId].finals = gb.finals.grades[studentId];
        });
    }
};

onMounted(() => {
    loadGradebookFromProps();
});

// Helpers to prepare and send save request
const getTablesForPeriod = (period) => {
    if (period === 'midterm') {
        return {
            tables: midtermTables.value,
            custom: midtermCustomTables.value,
        };
    }
    return {
        tables: finalsTables.value,
        custom: finalsCustomTables.value,
    };
};

const saveGradebookForPeriod = (period) => {
    if (!period) return;

    // Prepare tableStructure
    const { tables, custom } = getTablesForPeriod(period);
    const tableStructure = {
        tables: tables,
        custom: custom,
    };

    // Prepare grades per student for the period
    const gradesPayload = {};
    Object.keys(studentGrades.value).forEach(studentId => {
        const entry = studentGrades.value[studentId]?.[period] || {};
        gradesPayload[studentId] = entry;
    });

    const payload = {
        gradingPeriod: period,
        grades: gradesPayload,
        tableStructure: tableStructure,
        midtermPercentage: midtermPercentage.value,
        finalsPercentage: finalsPercentage.value,
    };

    // Post to server
    try {
        router.post(route('teacher.courses.gradebook.save', { course: props.course.id }), payload, {
            preserveScroll: true,
            preserveState: true,
        });
    } catch (e) {
        console.error('Error saving gradebook:', e);
    }
};

// Open grading period
const openGradingPeriod = (period) => {
    activeGradingPeriod.value = activeGradingPeriod.value === period ? null : period;
    // Save current open/changes when switching grading period to persist structure
    if (activeGradingPeriod.value === period) {
        saveGradebookForPeriod(period);
    }
};

// Add new column to table
const addColumn = (tableKey) => {
    const table = activeTables.value[tableKey];
    
    // Calculate current total
    const currentTotal = table.columns.reduce((sum, col) => sum + (col.percentage || 0), 0);
    const remaining = table.percentage - currentTotal;
    
    if (remaining <= 0) {
        alert(`Cannot add more columns. The ${table.name} table is already at ${table.percentage}%. Please reduce other column percentages first.`);
        return;
    }
    
    const newColumn = {
        id: `col-${Date.now()}`,
        name: 'New Column',
        percentage: 0,
        subcolumns: []
    };
    table.columns.push(newColumn);
};

// Validate and auto-correct column percentage
const validateColumnPercentage = (tableKey, columnId) => {
    const table = activeTables.value[tableKey];
    const column = table.columns.find(c => c.id === columnId);
    
    if (!column) return;
    
    // If percentage is invalid, set to 0
    if (isNaN(column.percentage) || column.percentage < 0) {
        column.percentage = 0;
        return;
    }
    
    // Calculate total of other columns
    const otherColumnsTotal = table.columns
        .filter(c => c.id !== columnId)
        .reduce((sum, col) => sum + (col.percentage || 0), 0);
    
    // Calculate maximum allowed for this column
    const maxAllowed = table.percentage - otherColumnsTotal;
    
    // Auto-correct if exceeds
    if (column.percentage > maxAllowed) {
        column.percentage = Math.max(0, maxAllowed);
    }
    // Persist change
    saveGradebookForPeriod(activeGradingPeriod.value);
};

// Validate and auto-correct table percentage
const validateTablePercentage = (tableKey) => {
    const table = activeTables.value[tableKey];
    
    if (isNaN(table.percentage) || table.percentage < 0) {
        table.percentage = 0;
        return;
    }
    
    // Calculate total of other tables
    const otherTablesTotal = Object.keys(activeTables.value)
        .filter(key => key !== tableKey)
        .reduce((sum, key) => sum + (activeTables.value[key].percentage || 0), 0);
    
    // Calculate maximum allowed for this table
    const maxAllowed = 100 - otherTablesTotal;
    
    // Auto-correct if exceeds
    if (table.percentage > maxAllowed) {
        table.percentage = Math.max(0, maxAllowed);
    }
    
    // Also adjust column percentages if they exceed new table limit
    const columnsTotal = table.columns.reduce((sum, col) => sum + (col.percentage || 0), 0);
    if (columnsTotal > table.percentage) {
        // Proportionally reduce all column percentages
        const ratio = table.percentage / columnsTotal;
        table.columns.forEach(col => {
            col.percentage = Math.round((col.percentage || 0) * ratio * 100) / 100;
        });
    }
    // Persist change
    saveGradebookForPeriod(activeGradingPeriod.value);
};

// Add subcolumn to column
const addSubcolumn = (tableKey, columnId) => {
    const table = activeTables.value[tableKey];
    const column = table.columns.find(c => c.id === columnId);
    if (column) {
        column.subcolumns.push({
            id: `subcol-${Date.now()}`,
            name: 'New Item',
            maxPoints: 100,
            isAutoFetched: false
        });
    }
};

// Remove column
const removeColumn = (tableKey, columnId) => {
    const table = activeTables.value[tableKey];
    const columnIndex = table.columns.findIndex(c => c.id === columnId);
    if (columnIndex > -1 && !table.columns[columnIndex].isFixed) {
        table.columns.splice(columnIndex, 1);
    }
};

// Remove subcolumn
const removeSubcolumn = (tableKey, columnId, subcolumnId) => {
    const table = activeTables.value[tableKey];
    const column = table.columns.find(c => c.id === columnId);
    if (column) {
        const subcolIndex = column.subcolumns.findIndex(sc => sc.id === subcolumnId);
        if (subcolIndex > -1 && !column.subcolumns[subcolIndex].isAutoFetched) {
            column.subcolumns.splice(subcolIndex, 1);
        }
    }
};

// Add new table
const addNewTable = () => {
    if (!activeGradingPeriod.value) {
        alert('Please select a grading period first (Midterm or Finals)');
        return;
    }
    
    // Calculate remaining percentage
    const totalExisting = Object.values(activeTables.value).reduce((sum, table) => sum + (table.percentage || 0), 0) +
                          activeCustomTables.value.reduce((sum, table) => sum + (table.percentage || 0), 0);
    
    if (totalExisting >= 100) {
        alert('Cannot add more tables. Total percentage already at 100%. Please reduce existing table percentages first.');
        return;
    }
    
    const newTable = {
        id: `table-${Date.now()}`,
        name: 'New Table',
        percentage: 0,
        columns: [],
        isCustom: true
    };
    
    if (activeGradingPeriod.value === 'midterm') {
        midtermCustomTables.value.push(newTable);
    } else {
        finalsCustomTables.value.push(newTable);
    }
};

// Remove custom table
const removeCustomTable = (tableId) => {
    if (activeGradingPeriod.value === 'midterm') {
        const index = midtermCustomTables.value.findIndex(t => t.id === tableId);
        if (index > -1) {
            midtermCustomTables.value.splice(index, 1);
        }
    } else {
        const index = finalsCustomTables.value.findIndex(t => t.id === tableId);
        if (index > -1) {
            finalsCustomTables.value.splice(index, 1);
        }
    }
};

// Validate and auto-correct custom table percentage
const validateCustomTablePercentage = (tableId) => {
    const customTable = activeCustomTables.value.find(t => t.id === tableId);
    if (!customTable) return;
    
    // If percentage is invalid, set to 0
    if (isNaN(customTable.percentage) || customTable.percentage < 0) {
        customTable.percentage = 0;
        return;
    }
    
    // Calculate total of default tables and other custom tables
    const defaultTablesTotal = Object.values(activeTables.value).reduce((sum, table) => sum + (table.percentage || 0), 0);
    const otherCustomTotal = activeCustomTables.value
        .filter(t => t.id !== tableId)
        .reduce((sum, table) => sum + (table.percentage || 0), 0);
    
    // Calculate maximum allowed for this table
    const maxAllowed = 100 - defaultTablesTotal - otherCustomTotal;
    
    // Auto-correct if exceeds
    if (customTable.percentage > maxAllowed) {
        customTable.percentage = Math.max(0, maxAllowed);
    }
    // Persist change
    saveGradebookForPeriod(activeGradingPeriod.value);
};

// Get warning for custom table
const getCustomTablePercentageWarning = (tableId) => {
    const table = activeCustomTables.value.find(t => t.id === tableId);
    if (!table) return null;
    
    const totalColumnPercentage = table.columns.reduce((sum, col) => sum + (col.percentage || 0), 0);
    
    if (totalColumnPercentage > table.percentage) {
        return `Total column percentage (${totalColumnPercentage.toFixed(2)}%) exceeds table limit (${table.percentage}%). Please adjust column percentages.`;
    }
    
    const remaining = table.percentage - totalColumnPercentage;
    if (remaining > 0) {
        return `${remaining.toFixed(2)}% remaining in ${table.name}`;
    }
    
    return null;
};

// Get remaining percentage for custom table
const getRemainingPercentageCustom = (tableId) => {
    const table = activeCustomTables.value.find(t => t.id === tableId);
    if (!table) return 0;
    
    const totalColumnPercentage = table.columns.reduce((sum, col) => sum + (col.percentage || 0), 0);
    return Math.max(0, table.percentage - totalColumnPercentage);
};

// Add column to custom table
const addColumnToCustomTable = (tableId) => {
    const table = activeCustomTables.value.find(t => t.id === tableId);
    if (!table) return;
    
    // Calculate current total
    const currentTotal = table.columns.reduce((sum, col) => sum + (col.percentage || 0), 0);
    const remaining = table.percentage - currentTotal;
    
    if (remaining <= 0) {
        alert(`Cannot add more columns. The ${table.name} table is already at ${table.percentage}%. Please reduce other column percentages first.`);
        return;
    }
    
    const newColumn = {
        id: `col-${Date.now()}`,
        name: 'New Column',
        percentage: 0,
        subcolumns: []
    };
    table.columns.push(newColumn);
};

// Remove column from custom table
const removeColumnFromCustomTable = (tableId, columnId) => {
    const table = activeCustomTables.value.find(t => t.id === tableId);
    if (!table) return;
    
    const columnIndex = table.columns.findIndex(c => c.id === columnId);
    if (columnIndex > -1) {
        table.columns.splice(columnIndex, 1);
    }
};

// Validate column percentage in custom table
const validateCustomColumnPercentage = (tableId, columnId) => {
    const table = activeCustomTables.value.find(t => t.id === tableId);
    if (!table) return;
    
    const column = table.columns.find(c => c.id === columnId);
    if (!column) return;
    
    // If percentage is invalid, set to 0
    if (isNaN(column.percentage) || column.percentage < 0) {
        column.percentage = 0;
        return;
    }
    
    // Calculate total of other columns
    const otherColumnsTotal = table.columns
        .filter(c => c.id !== columnId)
        .reduce((sum, col) => sum + (col.percentage || 0), 0);
    
    // Calculate maximum allowed for this column
    const maxAllowed = table.percentage - otherColumnsTotal;
    
    // Auto-correct if exceeds
    if (column.percentage > maxAllowed) {
        column.percentage = Math.max(0, maxAllowed);
    }
    // Persist change
    saveGradebookForPeriod(activeGradingPeriod.value);
};

// Add subcolumn to custom table column
const addSubcolumnToCustom = (tableId, columnId) => {
    const table = activeCustomTables.value.find(t => t.id === tableId);
    if (!table) return;
    
    const column = table.columns.find(c => c.id === columnId);
    if (column) {
        column.subcolumns.push({
            id: `subcol-${Date.now()}`,
            name: 'New Item',
            maxPoints: 100,
            isAutoFetched: false
        });
    }
};

// Remove subcolumn from custom table
const removeSubcolumnFromCustom = (tableId, columnId, subcolumnId) => {
    const table = activeCustomTables.value.find(t => t.id === tableId);
    if (!table) return;
    
    const column = table.columns.find(c => c.id === columnId);
    if (column) {
        const subcolIndex = column.subcolumns.findIndex(sc => sc.id === subcolumnId);
        if (subcolIndex > -1) {
            column.subcolumns.splice(subcolIndex, 1);
        }
    }
};

// Calculate column grade for custom table (average of item percentages, then weighted by column %)
const calculateCustomColumnGrade = (studentId, tableId, columnId) => {
    const periodKey = activeGradingPeriod.value;
    const grades = studentGrades.value[studentId]?.[periodKey] || {};
    const key = `custom-${tableId}-${columnId}`;
    
    const table = activeCustomTables.value.find(t => t.id === tableId);
    if (!table) return 0;
    
    const column = table.columns.find(c => c.id === columnId);
    if (!column || column.subcolumns.length === 0) return 0;
    
    // Calculate average of per-item percentages (each item contributes equally to the subcomponent)
    let sumItemPercentages = 0;
    const itemsCount = column.subcolumns.length;
    
    column.subcolumns.forEach(subcol => {
        const subKey = `${key}-${subcol.id}`;
        const score = parseFloat(grades[subKey] || 0);
        const maxPoints = subcol.maxPoints || 100;
        const itemPct = maxPoints > 0 ? (score / maxPoints) * 100 : 0;
        sumItemPercentages += itemPct;
    });
    
    const averagePercentage = itemsCount > 0 ? (sumItemPercentages / itemsCount) : 0;
    const weighted = (averagePercentage / 100) * (column.percentage || 0);
    
    return weighted.toFixed(2);
};

// Calculate table total for custom table
// If no grades/items, table contributes zero
const calculateCustomTableTotal = (studentId, tableId) => {
    const table = activeCustomTables.value.find(t => t.id === tableId);
    if (!table) return 0;

    let total = 0;
    let hasAnyGrade = false;
    table.columns.forEach(column => {
        column.subcolumns.forEach(subcol => {
            const periodKey = activeGradingPeriod.value;
            const grades = studentGrades.value[studentId]?.[periodKey] || {};
            const key = `custom-${tableId}-${column.id}-${subcol.id}`;
            if (grades[key] && !isNaN(parseFloat(grades[key]))) hasAnyGrade = true;
        });
        total += parseFloat(calculateCustomColumnGrade(studentId, tableId, column.id) || 0);
    });
    if (!hasAnyGrade) return '0.00';
    return total.toFixed(2);
};

// Validation: Check if table percentage exceeds limit
const getTablePercentageWarning = (tableKey) => {
    const table = activeTables.value[tableKey];
    const totalColumnPercentage = table.columns.reduce((sum, col) => sum + (col.percentage || 0), 0);
    
    if (totalColumnPercentage > table.percentage) {
        return `Total column percentage (${totalColumnPercentage.toFixed(2)}%) exceeds table limit (${table.percentage}%). Please adjust column percentages.`;
    }
    
    const remaining = table.percentage - totalColumnPercentage;
    if (remaining > 0) {
        return `${remaining.toFixed(2)}% remaining in ${table.name}`;
    }
    
    return null;
};

// Get remaining percentage for a table
const getRemainingPercentage = (tableKey) => {
    const table = activeTables.value[tableKey];
    const totalColumnPercentage = table.columns.reduce((sum, col) => sum + (col.percentage || 0), 0);
    return Math.max(0, table.percentage - totalColumnPercentage);
};

// Validation: Check if overall percentage exceeds 100%
const overallPercentageWarning = computed(() => {
    if (!activeTables.value) return null;
    
    const total = Object.values(activeTables.value).reduce((sum, table) => sum + (table.percentage || 0), 0) +
                  activeCustomTables.value.reduce((sum, table) => sum + (table.percentage || 0), 0);
    
    const remaining = 100 - total;
    
    if (total > 100) {
        return `Total grading percentage (${total.toFixed(2)}%) exceeds 100%. Please adjust your table weights.`;
    }
    
    if (remaining > 0) {
        return `${remaining.toFixed(2)}% remaining to distribute`;
    }
    
    return null;
});

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
        // Persist overall percentages to backend (save both periods' structures so the percentages are stored)
        saveGradebookForPeriod('midterm');
        saveGradebookForPeriod('finals');
    }
};

// Calculate student's grade for a specific column
// For non-exam tables: average the percent across items (subcolumns), then multiply by column %
const calculateColumnGrade = (studentId, tableKey, columnId) => {
    const periodKey = activeGradingPeriod.value;
    const grades = studentGrades.value[studentId]?.[periodKey] || {};
    const key = `${tableKey}-${columnId}`;
    
    const table = activeTables.value[tableKey];
    const column = table.columns.find(c => c.id === columnId);
    
    if (!column) return 0;
    
    // Special handling for Major Exam (no subcolumns, direct score)
    if (tableKey === 'majorExam') {
        const examKey = `${key}-exam`;
        const score = grades[examKey] || 0;
        const maxPoints = column.maxPoints || 100;
        const rawPercentage = maxPoints > 0 ? (parseFloat(score) / maxPoints) * 100 : 0;
        const weighted = (rawPercentage / 100) * (column.percentage || 0);
        return weighted.toFixed(2);
    }
    
    // Regular handling for other tables with subcolumns
    if (column.subcolumns.length === 0) return 0;
    
    // Calculate average of per-item percentages (each item contributes equally to the subcomponent)
    let sumItemPercentages = 0;
    const itemsCount = column.subcolumns.length;
    
    column.subcolumns.forEach(subcol => {
        const subKey = `${key}-${subcol.id}`;
        const score = parseFloat(grades[subKey] || 0);
        const maxPoints = subcol.maxPoints || 100;
        const itemPct = maxPoints > 0 ? (score / maxPoints) * 100 : 0;
        sumItemPercentages += itemPct;
    });
    
    const averagePercentage = itemsCount > 0 ? (sumItemPercentages / itemsCount) : 0;
    const weighted = (averagePercentage / 100) * (column.percentage || 0);
    
    return weighted.toFixed(2);
};

// Calculate student's total for a table
// If the table has no items/grades, its contribution is zero
const calculateTableTotal = (studentId, tableKey) => {
    const table = activeTables.value[tableKey];
    let total = 0;
    let hasAnyGrade = false;

    table.columns.forEach(column => {
        // Check if any subcolumn has a grade entered
        if (tableKey === 'majorExam') {
            // Major Exam: single entry
            const periodKey = activeGradingPeriod.value;
            const grades = studentGrades.value[studentId]?.[periodKey] || {};
            const key = `${tableKey}-${column.id}-exam`;
            if (grades[key] && !isNaN(parseFloat(grades[key]))) hasAnyGrade = true;
        } else {
            column.subcolumns.forEach(subcol => {
                const periodKey = activeGradingPeriod.value;
                const grades = studentGrades.value[studentId]?.[periodKey] || {};
                const key = `${tableKey}-${column.id}-${subcol.id}`;
                if (grades[key] && !isNaN(parseFloat(grades[key]))) hasAnyGrade = true;
            });
        }
        total += parseFloat(calculateColumnGrade(studentId, tableKey, column.id) || 0);
    });

    // If no grades/items at all, table contributes zero
    if (!hasAnyGrade) return '0.00';
    return total.toFixed(2);
};

// Calculate final grade for grading period
// Only sum tables that have at least one graded item, and cap at 100%
const calculatePeriodGrade = (studentId) => {
    if (!activeTables.value) return 0;

    let total = 0;
    Object.keys(activeTables.value).forEach(tableKey => {
        total += parseFloat(calculateTableTotal(studentId, tableKey) || 0);
    });

    activeCustomTables.value.forEach(table => {
        total += parseFloat(calculateCustomTableTotal(studentId, table.id) || 0);
    });

    // Cap at 100%
    return Math.min(total, 100).toFixed(2);
};

// Update grade for a subcolumn
const updateGrade = (studentId, tableKey, columnId, subcolumnId, value) => {
    const periodKey = activeGradingPeriod.value;
    if (!studentGrades.value[studentId][periodKey]) {
        studentGrades.value[studentId][periodKey] = {};
    }
    
    const key = `${tableKey}-${columnId}-${subcolumnId}`;
    studentGrades.value[studentId][periodKey][key] = value;
    // Persist grade change for this period
    saveGradebookForPeriod(periodKey);
};

// Update grade for custom table
const updateCustomGrade = (studentId, tableId, columnId, subcolumnId, value) => {
    const periodKey = activeGradingPeriod.value;
    if (!studentGrades.value[studentId][periodKey]) {
        studentGrades.value[studentId][periodKey] = {};
    }
    
    const key = `custom-${tableId}-${columnId}-${subcolumnId}`;
    studentGrades.value[studentId][periodKey][key] = value;
    // Persist grade change for this period
    saveGradebookForPeriod(periodKey);
};

// Get grade value
const getGrade = (studentId, tableKey, columnId, subcolumnId) => {
    const periodKey = activeGradingPeriod.value;
    const key = `${tableKey}-${columnId}-${subcolumnId}`;
    return studentGrades.value[studentId]?.[periodKey]?.[key] || '';
};

// Get custom grade value
const getCustomGrade = (studentId, tableId, columnId, subcolumnId) => {
    const periodKey = activeGradingPeriod.value;
    const key = `custom-${tableId}-${columnId}-${subcolumnId}`;
    return studentGrades.value[studentId]?.[periodKey]?.[key] || '';
};
</script>

<template>
    <Head :title="`Gradebook - ${course.title}`" />

    <component :is="LayoutComponent">
        <div class="max-w-[1800px] mx-auto space-y-6 p-6">
            <!-- Simple Header with Back Navigation -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-4 border-b border-gray-200 bg-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <Link
                                :href="route('teacher.courses.show', course.id)"
                                class="flex items-center gap-2 text-gray-600 hover:text-gray-900 transition"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Back to Course
                            </Link>
                            <div class="h-6 w-px bg-gray-300"></div>
                            <h1 class="text-xl font-bold text-gray-900">{{ course.title }} - Gradebook</h1>
                        </div>
                        <Link
                            :href="route('teacher.courses.class-record', course.id)"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Class Record
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Gradebook Content -->
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

            <!-- Grading Tables (shown when period is active) -->
            <div v-if="activeGradingPeriod" class="space-y-6">
                <!-- Overall Warning -->
                <div v-if="overallPercentageWarning" class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <p class="text-sm font-semibold text-red-800">{{ overallPercentageWarning }}</p>
                    </div>
                </div>

                <!-- Asynchronous Table -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="bg-green-600 text-white px-6 py-4 flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <h3 class="text-lg font-bold">🟩 Asynchronous</h3>
                                <div class="flex items-center gap-2">
                                    <input 
                                        v-model.number="activeTables.asynchronous.percentage"
                                        @blur="validateTablePercentage('asynchronous')"
                                        type="number"
                                        min="0"
                                        max="100"
                                        class="w-20 px-2 py-1 bg-white/20 border border-white/30 rounded text-white text-center font-bold"
                                    />
                                    <span class="text-white font-bold">%</span>
                                </div>
                            </div>
                            <p class="text-sm text-green-100 mt-1">
                                {{ activeTables.asynchronous.percentage }}% of {{ activeGradingPeriod === 'midterm' ? 'Midterm' : 'Finals' }}
                                <span v-if="getRemainingPercentage('asynchronous') > 0" class="ml-2 text-xs">
                                    ({{ getRemainingPercentage('asynchronous').toFixed(2) }}% remaining)
                                </span>
                            </p>
                        </div>
                        <button
                            @click="addColumn('asynchronous')"
                            class="px-4 py-2 bg-white text-green-600 rounded-lg hover:bg-green-50 transition flex items-center gap-2"
                            :disabled="getRemainingPercentage('asynchronous') <= 0"
                            :class="{ 'opacity-50 cursor-not-allowed': getRemainingPercentage('asynchronous') <= 0 }"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Column
                        </button>
                    </div>
                    
                    <!-- Warning for this table -->
                    <div v-if="getTablePercentageWarning('asynchronous')" class="bg-yellow-50 border-b border-yellow-200 px-6 py-3">
                        <p class="text-sm text-yellow-800">⚠️ {{ getTablePercentageWarning('asynchronous') }}</p>
                    </div>

                    <!-- Table Content -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b-2 border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 sticky left-0 bg-gray-50 z-10" rowspan="2">
                                        Student Name
                                    </th>
                                    <th 
                                        v-for="column in activeTables.asynchronous.columns" 
                                        :key="column.id"
                                        :colspan="Math.max(column.subcolumns.length, 1)"
                                        class="px-4 py-2 text-center font-semibold text-gray-700 border-l border-gray-200"
                                    >
                                        <div class="flex items-center justify-center gap-2">
                                            <input 
                                                v-if="!column.isFixed"
                                                v-model="column.name"
                                                type="text"
                                                class="px-2 py-1 border border-gray-300 rounded text-center max-w-[150px]"
                                            />
                                            <span v-else class="font-semibold">{{ column.name }}</span>
                                            <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded">({{ column.percentage }}%)</span>
                                            <button
                                                v-if="!column.isFixed"
                                                @click="removeColumn('asynchronous', column.id)"
                                                class="text-red-500 hover:text-red-700"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="mt-2 flex items-center justify-center gap-2">
                                            <input 
                                                v-if="!column.isFixed"
                                                v-model.number="column.percentage"
                                                @blur="validateColumnPercentage('asynchronous', column.id)"
                                                type="number"
                                                min="0"
                                                :max="activeTables.asynchronous.percentage"
                                                class="w-16 px-2 py-1 border border-gray-300 rounded text-center text-xs"
                                            />
                                            <input 
                                                v-else
                                                v-model.number="column.percentage"
                                                @blur="validateColumnPercentage('asynchronous', column.id)"
                                                type="number"
                                                min="0"
                                                :max="activeTables.asynchronous.percentage"
                                                class="w-16 px-2 py-1 border border-gray-300 rounded text-center text-xs"
                                            />
                                            <button
                                                @click="addSubcolumn('asynchronous', column.id)"
                                                class="text-xs px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600"
                                            >
                                                + Item
                                            </button>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-700 border-l-2 border-gray-300 bg-green-50" rowspan="2">
                                        Total<br>({{ activeTables.asynchronous.percentage }}%)
                                    </th>
                                </tr>
                                <tr>
                                    <template v-for="column in activeTables.asynchronous.columns" :key="`sub-${column.id}`">
                                        <th 
                                            v-for="subcol in column.subcolumns"
                                            :key="subcol.id"
                                            class="px-2 py-2 text-center text-xs font-medium text-gray-600 border-l border-gray-200"
                                        >
                                            <div class="flex items-center justify-center gap-1">
                                                <input 
                                                    v-if="!subcol.isAutoFetched"
                                                    v-model="subcol.name"
                                                    type="text"
                                                    class="px-1 py-0.5 border border-gray-300 rounded text-center max-w-[100px] text-xs"
                                                />
                                                <span v-else class="text-xs">{{ subcol.name }}</span>
                                                <button
                                                    v-if="!subcol.isAutoFetched"
                                                    @click="removeSubcolumn('asynchronous', column.id, subcol.id)"
                                                    class="text-red-500 hover:text-red-700"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">({{ subcol.maxPoints }}pts)</div>
                                        </th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <tr 
                                    v-for="student in students" 
                                    :key="student.id"
                                    class="border-b border-gray-100 hover:bg-gray-50"
                                >
                                    <td class="px-4 py-3 font-medium text-gray-900 sticky left-0 bg-white z-10">
                                        {{ student.name }}
                                    </td>
                                    <template v-for="column in activeTables.asynchronous.columns" :key="`grade-${column.id}`">
                                        <td 
                                            v-for="subcol in column.subcolumns"
                                            :key="`${column.id}-${subcol.id}`"
                                            class="px-2 py-2 text-center border-l border-gray-100"
                                        >
                                            <input 
                                                :value="getGrade(student.id, 'asynchronous', column.id, subcol.id)"
                                                @input="updateGrade(student.id, 'asynchronous', column.id, subcol.id, $event.target.value)"
                                                type="number"
                                                min="0"
                                                :max="subcol.maxPoints"
                                                step="0.5"
                                                class="w-16 px-2 py-1 border border-gray-300 rounded text-center text-sm"
                                            />
                                        </td>
                                    </template>
                                    <td class="px-4 py-3 text-center font-semibold text-gray-900 border-l-2 border-gray-300 bg-green-50">
                                        {{ calculateTableTotal(student.id, 'asynchronous') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Synchronous Table -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="bg-blue-600 text-white px-6 py-4 flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <h3 class="text-lg font-bold">🟦 Synchronous</h3>
                                <div class="flex items-center gap-2">
                                    <input 
                                        v-model.number="activeTables.synchronous.percentage"
                                        @blur="validateTablePercentage('synchronous')"
                                        type="number"
                                        min="0"
                                        max="100"
                                        class="w-20 px-2 py-1 bg-white/20 border border-white/30 rounded text-white text-center font-bold"
                                    />
                                    <span class="text-white font-bold">%</span>
                                </div>
                            </div>
                            <p class="text-sm text-blue-100 mt-1">
                                {{ activeTables.synchronous.percentage }}% of {{ activeGradingPeriod === 'midterm' ? 'Midterm' : 'Finals' }}
                                <span v-if="getRemainingPercentage('synchronous') > 0" class="ml-2 text-xs">
                                    ({{ getRemainingPercentage('synchronous').toFixed(2) }}% remaining)
                                </span>
                            </p>
                        </div>
                        <button
                            @click="addColumn('synchronous')"
                            class="px-4 py-2 bg-white text-blue-600 rounded-lg hover:bg-blue-50 transition flex items-center gap-2"
                            :disabled="getRemainingPercentage('synchronous') <= 0"
                            :class="{ 'opacity-50 cursor-not-allowed': getRemainingPercentage('synchronous') <= 0 }"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Column
                        </button>
                    </div>
                    
                    <div v-if="getTablePercentageWarning('synchronous')" class="bg-yellow-50 border-b border-yellow-200 px-6 py-3">
                        <p class="text-sm text-yellow-800">⚠️ {{ getTablePercentageWarning('synchronous') }}</p>
                    </div>

                    <!-- Similar table structure for Synchronous -->
                    <div v-if="activeTables.synchronous.columns.length === 0" class="p-12 text-center text-gray-500">
                        <p class="text-lg">No columns added yet. Click "Add Column" to start.</p>
                    </div>
                    <div v-else class="overflow-x-auto">
                        <!-- Similar table structure as Asynchronous -->
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b-2 border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 sticky left-0 bg-gray-50 z-10" rowspan="2">
                                        Student Name
                                    </th>
                                    <th 
                                        v-for="column in activeTables.synchronous.columns" 
                                        :key="column.id"
                                        :colspan="Math.max(column.subcolumns.length, 1)"
                                        class="px-4 py-2 text-center font-semibold text-gray-700 border-l border-gray-200"
                                    >
                                        <div class="flex items-center justify-center gap-2">
                                            <input 
                                                v-model="column.name"
                                                type="text"
                                                class="px-2 py-1 border border-gray-300 rounded text-center max-w-[150px]"
                                            />
                                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded">({{ column.percentage }}%)</span>
                                            <button
                                                @click="removeColumn('synchronous', column.id)"
                                                class="text-red-500 hover:text-red-700"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="mt-2 flex items-center justify-center gap-2">
                                            <input 
                                                v-model.number="column.percentage"
                                                @blur="validateColumnPercentage('synchronous', column.id)"
                                                type="number"
                                                min="0"
                                                :max="activeTables.synchronous.percentage"
                                                class="w-16 px-2 py-1 border border-gray-300 rounded text-center text-xs"
                                            />
                                            <button
                                                @click="addSubcolumn('synchronous', column.id)"
                                                class="text-xs px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600"
                                            >
                                                + Item
                                            </button>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-700 border-l-2 border-gray-300 bg-blue-50" rowspan="2">
                                        Total<br>({{ activeTables.synchronous.percentage }}%)
                                    </th>
                                </tr>
                                <tr>
                                    <template v-for="column in activeTables.synchronous.columns" :key="`sub-${column.id}`">
                                        <th 
                                            v-for="subcol in column.subcolumns"
                                            :key="subcol.id"
                                            class="px-2 py-2 text-center text-xs font-medium text-gray-600 border-l border-gray-200"
                                        >
                                            <div class="flex items-center justify-center gap-1">
                                                <input 
                                                    v-model="subcol.name"
                                                    type="text"
                                                    class="px-1 py-0.5 border border-gray-300 rounded text-center max-w-[100px] text-xs"
                                                />
                                                <button
                                                    @click="removeSubcolumn('synchronous', column.id, subcol.id)"
                                                    class="text-red-500 hover:text-red-700"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">({{ subcol.maxPoints }}pts)</div>
                                        </th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <tr 
                                    v-for="student in students" 
                                    :key="student.id"
                                    class="border-b border-gray-100 hover:bg-gray-50"
                                >
                                    <td class="px-4 py-3 font-medium text-gray-900 sticky left-0 bg-white z-10">
                                        {{ student.name }}
                                    </td>
                                    <template v-for="column in activeTables.synchronous.columns" :key="`grade-${column.id}`">
                                        <td 
                                            v-for="subcol in column.subcolumns"
                                            :key="`${column.id}-${subcol.id}`"
                                            class="px-2 py-2 text-center border-l border-gray-100"
                                        >
                                            <input 
                                                :value="getGrade(student.id, 'synchronous', column.id, subcol.id)"
                                                @input="updateGrade(student.id, 'synchronous', column.id, subcol.id, $event.target.value)"
                                                type="number"
                                                min="0"
                                                :max="subcol.maxPoints"
                                                step="0.5"
                                                class="w-16 px-2 py-1 border border-gray-300 rounded text-center text-sm"
                                            />
                                        </td>
                                    </template>
                                    <td class="px-4 py-3 text-center font-semibold text-gray-900 border-l-2 border-gray-300 bg-blue-50">
                                        {{ calculateTableTotal(student.id, 'synchronous') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Major Exam Table -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="bg-red-600 text-white px-6 py-4 flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <h3 class="text-lg font-bold">🟥 Major Exam</h3>
                                <div class="flex items-center gap-2">
                                    <input 
                                        v-model.number="activeTables.majorExam.percentage"
                                        @blur="validateTablePercentage('majorExam')"
                                        type="number"
                                        min="0"
                                        max="100"
                                        class="w-20 px-2 py-1 bg-white/20 border border-white/30 rounded text-white text-center font-bold"
                                    />
                                    <span class="text-white font-bold">%</span>
                                </div>
                            </div>
                            <p class="text-sm text-red-100 mt-1">{{ activeTables.majorExam.percentage }}% of {{ activeGradingPeriod === 'midterm' ? 'Midterm' : 'Finals' }}</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b-2 border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 sticky left-0 bg-gray-50 z-10">
                                        Student Name
                                    </th>
                                    <th 
                                        v-for="column in activeTables.majorExam.columns" 
                                        :key="column.id"
                                        class="px-4 py-3 text-center font-semibold text-gray-700 border-l border-gray-200"
                                    >
                                        <input 
                                            v-model="column.name"
                                            type="text"
                                            class="px-2 py-1 border border-gray-300 rounded text-center max-w-[200px]"
                                            placeholder="Exam Name"
                                        />
                                        <div class="flex items-center justify-center gap-1 mt-1">
                                            <span class="text-xs text-gray-500">(</span>
                                            <input 
                                                v-model.number="column.maxPoints"
                                                type="number"
                                                min="1"
                                                class="w-16 px-1 py-0.5 border border-gray-300 rounded text-center text-xs"
                                                placeholder="100"
                                            />
                                            <span class="text-xs text-gray-500">pts)</span>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-700 border-l-2 border-gray-300 bg-red-50">
                                        Total<br>({{ activeTables.majorExam.percentage }}%)
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr 
                                    v-for="student in students" 
                                    :key="student.id"
                                    class="border-b border-gray-100 hover:bg-gray-50"
                                >
                                    <td class="px-4 py-3 font-medium text-gray-900 sticky left-0 bg-white z-10">
                                        {{ student.name }}
                                    </td>
                                    <td 
                                        v-for="column in activeTables.majorExam.columns"
                                        :key="`${column.id}`"
                                        class="px-4 py-3 text-center border-l border-gray-100"
                                    >
                                        <input 
                                            :value="getGrade(student.id, 'majorExam', column.id, 'exam')"
                                            @input="updateGrade(student.id, 'majorExam', column.id, 'exam', $event.target.value)"
                                            type="number"
                                            min="0"
                                            :max="column.maxPoints || 100"
                                            step="0.5"
                                            class="w-20 px-2 py-1 border border-gray-300 rounded text-center"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-center font-semibold text-gray-900 border-l-2 border-gray-300 bg-red-50">
                                        {{ calculateTableTotal(student.id, 'majorExam') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Custom Tables -->
                <div v-for="customTable in activeCustomTables" :key="customTable.id" class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-6 py-4 flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <input 
                                    v-model="customTable.name"
                                    type="text"
                                    class="px-3 py-1 bg-white/20 border border-white/30 rounded text-white placeholder-white/70 font-bold text-lg"
                                    placeholder="Table Name"
                                />
                                <div class="flex items-center gap-2">
                                    <input 
                                        v-model.number="customTable.percentage"
                                        @blur="validateCustomTablePercentage(customTable.id)"
                                        type="number"
                                        min="0"
                                        max="100"
                                        class="w-20 px-2 py-1 bg-white/20 border border-white/30 rounded text-white text-center"
                                    />
                                    <span class="text-white">%</span>
                                </div>
                            </div>
                            <p class="text-sm text-white/80 mt-1">
                                {{ customTable.percentage }}% of {{ activeGradingPeriod === 'midterm' ? 'Midterm' : 'Finals' }}
                                <span v-if="getRemainingPercentageCustom(customTable.id) > 0" class="ml-2 text-xs">
                                    ({{ getRemainingPercentageCustom(customTable.id).toFixed(2) }}% remaining)
                                </span>
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                @click="addColumnToCustomTable(customTable.id)"
                                class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition flex items-center gap-2"
                                :disabled="getRemainingPercentageCustom(customTable.id) <= 0"
                                :class="{ 'opacity-50 cursor-not-allowed': getRemainingPercentageCustom(customTable.id) <= 0 }"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Column
                            </button>
                            <button
                                @click="removeCustomTable(customTable.id)"
                                class="px-3 py-2 bg-red-500/80 hover:bg-red-600 text-white rounded-lg transition"
                                title="Delete Table"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div v-if="getCustomTablePercentageWarning(customTable.id)" class="bg-yellow-50 border-b border-yellow-200 px-6 py-3">
                        <p class="text-sm text-yellow-800">⚠️ {{ getCustomTablePercentageWarning(customTable.id) }}</p>
                    </div>

                    <div v-if="customTable.columns.length === 0" class="p-12 text-center text-gray-500">
                        <p class="text-lg">No columns added yet. Click "Add Column" to start.</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b-2 border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700 sticky left-0 bg-gray-50 z-10" rowspan="2">
                                        Student Name
                                    </th>
                                    <th 
                                        v-for="column in customTable.columns" 
                                        :key="column.id"
                                        :colspan="Math.max(column.subcolumns.length, 1)"
                                        class="px-4 py-2 text-center font-semibold text-gray-700 border-l border-gray-200"
                                    >
                                        <div class="flex items-center justify-center gap-2">
                                            <input 
                                                v-model="column.name"
                                                type="text"
                                                class="px-2 py-1 border border-gray-300 rounded text-center max-w-[150px]"
                                                placeholder="Column Name"
                                            />
                                            <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded">({{ column.percentage }}%)</span>
                                            <button
                                                @click="removeColumnFromCustomTable(customTable.id, column.id)"
                                                class="text-red-500 hover:text-red-700"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="mt-2 flex items-center justify-center gap-2">
                                            <input 
                                                v-model.number="column.percentage"
                                                @blur="validateCustomColumnPercentage(customTable.id, column.id)"
                                                type="number"
                                                min="0"
                                                :max="customTable.percentage"
                                                class="w-16 px-2 py-1 border border-gray-300 rounded text-center text-xs"
                                            />
                                            <button
                                                @click="addSubcolumnToCustom(customTable.id, column.id)"
                                                class="text-xs px-2 py-1 bg-purple-500 text-white rounded hover:bg-purple-600"
                                            >
                                                + Item
                                            </button>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-700 border-l-2 border-gray-300 bg-purple-50" rowspan="2">
                                        Total<br>({{ customTable.percentage }}%)
                                    </th>
                                </tr>
                                <tr>
                                    <template v-for="column in customTable.columns" :key="`sub-${column.id}`">
                                        <th 
                                            v-for="subcol in column.subcolumns"
                                            :key="subcol.id"
                                            class="px-2 py-2 text-center text-xs font-medium text-gray-600 border-l border-gray-200"
                                        >
                                            <div class="flex items-center justify-center gap-1">
                                                <input 
                                                    v-model="subcol.name"
                                                    type="text"
                                                    class="px-1 py-0.5 border border-gray-300 rounded text-center max-w-[100px] text-xs"
                                                    placeholder="Item Name"
                                                />
                                                <button
                                                    @click="removeSubcolumnFromCustom(customTable.id, column.id, subcol.id)"
                                                    class="text-red-500 hover:text-red-700"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="flex items-center justify-center gap-1 mt-1">
                                                <input 
                                                    v-model.number="subcol.maxPoints"
                                                    type="number"
                                                    min="1"
                                                    class="w-12 px-1 py-0.5 border border-gray-300 rounded text-center text-xs"
                                                />
                                                <span class="text-xs text-gray-500">pts</span>
                                            </div>
                                        </th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <tr 
                                    v-for="student in students" 
                                    :key="student.id"
                                    class="border-b border-gray-100 hover:bg-gray-50"
                                >
                                    <td class="px-4 py-3 font-medium text-gray-900 sticky left-0 bg-white z-10">
                                        {{ student.name }}
                                    </td>
                                    <template v-for="column in customTable.columns" :key="`grade-${column.id}`">
                                        <td 
                                            v-for="subcol in column.subcolumns"
                                            :key="`${column.id}-${subcol.id}`"
                                            class="px-2 py-2 text-center border-l border-gray-100"
                                        >
                                            <input 
                                                :value="getCustomGrade(student.id, customTable.id, column.id, subcol.id)"
                                                @input="updateCustomGrade(student.id, customTable.id, column.id, subcol.id, $event.target.value)"
                                                type="number"
                                                min="0"
                                                :max="subcol.maxPoints"
                                                step="0.5"
                                                class="w-16 px-2 py-1 border border-gray-300 rounded text-center text-sm"
                                            />
                                        </td>
                                    </template>
                                    <td class="px-4 py-3 text-center font-semibold text-gray-900 border-l-2 border-gray-300 bg-purple-50">
                                        {{ calculateCustomTableTotal(student.id, customTable.id) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add Custom Table Button -->
                <div class="flex justify-center">
                    <button
                        @click="addNewTable"
                        class="px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-lg font-medium transition flex items-center gap-2 shadow-lg"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Custom Table
                    </button>
                </div>

                <!-- Computation Summary Table -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-6 py-4">
                        <h3 class="text-lg font-bold">📊 {{ activeGradingPeriod === 'midterm' ? 'Midterm' : 'Finals' }} Grade Summary</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b-2 border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Student Name</th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Asynchronous ({{ activeTables.asynchronous.percentage }}%)</th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Synchronous ({{ activeTables.synchronous.percentage }}%)</th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Major Exam ({{ activeTables.majorExam.percentage }}%)</th>
                                    <th 
                                        v-for="customTable in activeCustomTables" 
                                        :key="customTable.id"
                                        class="px-4 py-3 text-center font-semibold text-gray-700"
                                    >
                                        {{ customTable.name }} ({{ customTable.percentage }}%)
                                    </th>
                                    <th class="px-4 py-3 text-center font-bold text-purple-700 bg-purple-50">
                                        {{ activeGradingPeriod === 'midterm' ? 'Midterm' : 'Finals' }} Grade (100%)
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr 
                                    v-for="student in students" 
                                    :key="student.id"
                                    class="border-b border-gray-100 hover:bg-gray-50"
                                >
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ student.name }}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">{{ calculateTableTotal(student.id, 'asynchronous') }}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">{{ calculateTableTotal(student.id, 'synchronous') }}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">{{ calculateTableTotal(student.id, 'majorExam') }}</td>
                                    <td 
                                        v-for="customTable in activeCustomTables" 
                                        :key="customTable.id"
                                        class="px-4 py-3 text-center text-gray-700"
                                    >
                                        {{ calculateCustomTableTotal(student.id, customTable.id) }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-lg text-purple-700 bg-purple-50">
                                        {{ calculatePeriodGrade(student.id) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </component>
</template>

<style scoped>
/* Custom scrollbar for tables */
.overflow-x-auto::-webkit-scrollbar {
    height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>

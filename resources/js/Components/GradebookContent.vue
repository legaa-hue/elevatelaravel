<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    course: Object,
    students: Array,
    classworks: Array,
    gradebook: Object,
});

const midtermPercentage = ref(50);
const finalsPercentage = ref(50);
const showMidtermSection = ref(true);
const showFinalsSection = ref(false);
const isSaving = ref(false);
const saveIndicator = ref(''); // '', 'Saving…', 'All changes saved'
let saveTimer = null;
const showTooltip = ref(false);

const showAddTableModal = ref(false);
const showEditTableModal = ref(false);
const showAddColumnModal = ref(false);
const showEditColumnModal = ref(false);
const showAddSubcolumnModal = ref(false);
const showEditSubcolumnModal = ref(false);

const tableForm = ref({ name: '', percentage: 0 });
const columnForm = ref({ name: '', percentage: 0 });
const subcolumnForm = ref({ name: '', maxPoints: 100 });
const currentPeriod = ref(null);
const editingTableKey = ref(null);
const editingColumnIndex = ref(null);
const editingSubcolumnIndex = ref(null);

// Inline editing states
const editingTableName = ref(null);
const editingTablePercentage = ref(null);
const tempTableName = ref('');
const tempTablePercentage = ref(0);

// Inline editing for columns
const editingColumnName = ref(null);
const editingColumnPercentage = ref(null);
const tempColumnName = ref('');
const tempColumnPercentage = ref(0);

// Inline editing for subcolumns
const editingSubcolumnName = ref(null);
const editingSubcolumnMaxPoints = ref(null);
const tempSubcolumnName = ref('');
const tempSubcolumnMaxPoints = ref(100);

// Create default tables structure
const createDefaultTables = (period = 'midterm') => {
    const examName = period === 'midterm' ? 'Midterm Examination' : 'Final Examination';
    
    return {
        'async': {
            id: 'async',
            name: 'Asynchronous',
            percentage: 35,
            columns: []
        },
        'sync': {
            id: 'sync',
            name: 'Synchronous',
            percentage: 35,
            columns: []
        },
        'exam': {
            id: 'exam',
            name: examName,
            percentage: 30,
            columns: []
        },
        'summary': {
            id: 'summary',
            name: 'Summary',
            percentage: 0,
            isReadOnly: true,
            isSummary: true,
            columns: [
                {
                    id: 'summary-col-1',
                    name: 'Total Grade',
                    percentage: 0,
                    subcolumns: []
                }
            ]
        }
    };
};

const midtermTables = ref(createDefaultTables('midterm'));
const finalsTables = ref(createDefaultTables('finals'));
const studentGrades = ref({});

// Debug and initialize student grades
console.log('GradebookContent - Students prop:', props.students);
console.log('GradebookContent - Students count:', props.students?.length);
console.log('GradebookContent - Students data:', JSON.stringify(props.students));

if (props.students && Array.isArray(props.students)) {
    props.students.forEach(student => {
        studentGrades.value[student.id] = { midterm: {}, finals: {} };
    });
    console.log('GradebookContent - Initialized grades for students:', Object.keys(studentGrades.value));
} else {
    console.error('GradebookContent - No students array received!', props.students);
}

const midtermPercentageError = computed(() => {
    const total = midtermPercentage.value + finalsPercentage.value;
    if (total !== 100) {
        return `Total computation weight must be 100%. Currently: ${total}%`;
    }
    return null;
});

const getCurrentTables = (period) => {
    return period === 'midterm' ? midtermTables.value : finalsTables.value;
};

const getTotalTablesPercentage = (period) => {
    const tables = getCurrentTables(period);
    return Object.values(tables).reduce((sum, table) => sum + (table.percentage || 0), 0);
};

const getRemainingPercentage = (period) => {
    return 100 - getTotalTablesPercentage(period);
};

const canAddTable = (period) => {
    return getTotalTablesPercentage(period) < 100;
};

const getColumnsTotalPercentage = (period, tableKey) => {
    const tables = getCurrentTables(period);
    const table = tables[tableKey];
    if (!table || !table.columns) return 0;
    return table.columns.reduce((sum, col) => sum + (col.percentage || 0), 0);
};

const canAddColumn = (period, tableKey) => {
    const tables = getCurrentTables(period);
    const table = tables[tableKey];
    if (!table) return false;
    return getColumnsTotalPercentage(period, tableKey) < table.percentage;
};

const getRemainingColumnPercentage = (period, tableKey) => {
    const tables = getCurrentTables(period);
    const table = tables[tableKey];
    if (!table) return 0;
    return table.percentage - getColumnsTotalPercentage(period, tableKey);
};

// Compute table total as: (totalScore / totalMaxPoints) * table.percentage (as decimal)
const calculateStudentTableTotal = (studentId, period, tableKey) => {
    const tables = getCurrentTables(period);
    const table = tables[tableKey];
    if (!table) return '0.00';

    // If this is the Summary table, calculate the total of all other tables
    if (table.isSummary) {
        let grandTotal = 0;
        Object.keys(tables).forEach(key => {
            if (key !== tableKey && !tables[key].isSummary) {
                grandTotal += parseFloat(calculateStudentTableTotal(studentId, period, key)) || 0;
            }
        });
        return grandTotal.toFixed(2);
    }

    // Normalize column weights so their sum is always 100% for the table
    let tableTotal = 0;
    let totalColumnPercent = 0;
    // First, sum up all column percentages (ignore columns with no subcolumns)
    table.columns.forEach(column => {
        if (!column.subcolumns || column.subcolumns.length === 0) return;
        totalColumnPercent += (column.percentage || 0);
    });
    if (totalColumnPercent === 0) return '0.00';
    // Now, compute each column's normalized weight and contribution
    table.columns.forEach(column => {
        if (!column.subcolumns || column.subcolumns.length === 0) return;
        let colScore = 0;
        let colMax = 0;
        column.subcolumns.forEach(subcolumn => {
            const gradeKey = `${tableKey}-${column.id}-${subcolumn.id}`;
            const raw = parseFloat(studentGrades.value[studentId]?.[period]?.[gradeKey] ?? 0);
            const maxPoints = subcolumn.maxPoints || 100;
            const effectiveScore = (raw <= 1 && maxPoints > 1) ? (raw * maxPoints) : raw;
            colScore += isNaN(effectiveScore) ? 0 : effectiveScore;
            colMax += maxPoints;
        });
        if (colMax === 0) return;
        const colPercentScore = colScore / colMax;
        // Normalized column weight (so all columns sum to 1.0)
        const colWeight = (column.percentage || 0) / totalColumnPercent;
        tableTotal += colPercentScore * colWeight;
    });
    // Table's percentage (e.g., 35% for Synchronous)
    const tableWeight = (table.percentage || 0) / 100;
    return (tableTotal * tableWeight * 100).toFixed(2);
};

const calculateStudentPeriodGrade = (studentId, period) => {
    const tables = getCurrentTables(period);
    let total = 0;

    // Sum only real grading tables; skip the auto "Summary" table to avoid double counting
    Object.keys(tables).forEach(tableKey => {
        const table = tables[tableKey];
        if (table && table.isSummary) return; // ignore summary rows
        total += parseFloat(calculateStudentTableTotal(studentId, period, tableKey)) || 0;
    });

    // Cap at 100% to avoid overflow when weights are misconfigured
    return Math.min(total, 100).toFixed(2);
};

const validateGradingPeriodPercentage = (period) => {
    if (period === 'midterm') {
        if (isNaN(midtermPercentage.value) || midtermPercentage.value < 0) {
            midtermPercentage.value = 0;
        } else if (midtermPercentage.value > 100) {
            midtermPercentage.value = 100;
        }
        finalsPercentage.value = 100 - midtermPercentage.value;
    } else {
        if (isNaN(finalsPercentage.value) || finalsPercentage.value < 0) {
            finalsPercentage.value = 0;
        } else if (finalsPercentage.value > 100) {
            finalsPercentage.value = 100;
        }
        midtermPercentage.value = 100 - finalsPercentage.value;
    }
};

const populateSubcolumnsFromClassworks = () => {
    const slugify = (s) => (s || '')
        .toString()
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)+/g, '') || ('id-' + Date.now());
    const linkedClassworks = props.classworks.filter(cw => 
        cw.grading_period && cw.grade_table_name && cw.grade_main_column
    );
    
    const grouped = {};
    linkedClassworks.forEach(cw => {
        const period = cw.grading_period.toLowerCase();
        const tableName = cw.grade_table_name;
        const columnName = cw.grade_main_column;
        
        if (!grouped[period]) grouped[period] = {};
        if (!grouped[period][tableName]) grouped[period][tableName] = {};
        if (!grouped[period][tableName][columnName]) grouped[period][tableName][columnName] = [];
        
        grouped[period][tableName][columnName].push({
            id: `classwork-${cw.id}`,
            name: cw.grade_sub_column || cw.title,
            classworkId: cw.id,
            maxPoints: cw.points || 100,
            isAutoPopulated: true
        });
    });
    
    // Helper function to merge subcolumns (keep existing ones, add new ones from classworks)
    const mergeSubcolumns = (existingSubcolumns, newSubcolumns) => {
        const merged = [...(existingSubcolumns || [])];
        
        newSubcolumns.forEach(newSub => {
            // Check if this classwork-based subcolumn already exists
            const existingIndex = merged.findIndex(existing => 
                existing.classworkId === newSub.classworkId || 
                (existing.name === newSub.name && existing.isAutoPopulated)
            );
            
            if (existingIndex === -1) {
                // New auto subcolumn from classwork
                merged.push(newSub);
            } else {
                // Update existing auto subcolumn details (keep in sync with classwork)
                merged[existingIndex] = {
                    ...merged[existingIndex],
                    name: newSub.name,
                    maxPoints: newSub.maxPoints,
                    classworkId: newSub.classworkId,
                    isAutoPopulated: true,
                };
            }
        });
        
        return merged;
    };
    
    if (grouped.midterm) {
        Object.keys(grouped.midterm).forEach(tableName => {
            // Ensure table exists; create if missing (0% to avoid affecting weights)
            let tableEntryKey = Object.keys(midtermTables.value).find(
                key => midtermTables.value[key].name.toLowerCase() === tableName.toLowerCase()
            );
            if (!tableEntryKey) {
                const newId = slugify(tableName);
                midtermTables.value[newId] = {
                    id: newId,
                    name: tableName,
                    percentage: midtermTables.value[newId]?.percentage || 0,
                    columns: []
                };
                tableEntryKey = newId;
            }
            const table = midtermTables.value[tableEntryKey];

            Object.keys(grouped.midterm[tableName]).forEach(columnName => {
                // Ensure column exists; create if missing (0% to avoid affecting weights)
                let column = table.columns.find(c => 
                    c.name.toLowerCase() === columnName.toLowerCase()
                );
                if (!column) {
                    column = {
                        id: slugify(columnName),
                        name: columnName,
                        percentage: 0,
                        subcolumns: []
                    };
                    table.columns.push(column);
                }
                // Merge instead of replace
                column.subcolumns = mergeSubcolumns(
                    column.subcolumns, 
                    grouped.midterm[tableName][columnName]
                );
            });
        });
    }
    
    if (grouped.finals) {
        Object.keys(grouped.finals).forEach(tableName => {
            // Ensure table exists; create if missing (0% to avoid affecting weights)
            let tableEntryKey = Object.keys(finalsTables.value).find(
                key => finalsTables.value[key].name.toLowerCase() === tableName.toLowerCase()
            );
            if (!tableEntryKey) {
                const newId = slugify(tableName);
                finalsTables.value[newId] = {
                    id: newId,
                    name: tableName,
                    percentage: finalsTables.value[newId]?.percentage || 0,
                    columns: []
                };
                tableEntryKey = newId;
            }
            const table = finalsTables.value[tableEntryKey];

            Object.keys(grouped.finals[tableName]).forEach(columnName => {
                // Ensure column exists; create if missing (0% to avoid affecting weights)
                let column = table.columns.find(c => 
                    c.name.toLowerCase() === columnName.toLowerCase()
                );
                if (!column) {
                    column = {
                        id: slugify(columnName),
                        name: columnName,
                        percentage: 0,
                        subcolumns: []
                    };
                    table.columns.push(column);
                }
                // Merge instead of replace
                column.subcolumns = mergeSubcolumns(
                    column.subcolumns, 
                    grouped.finals[tableName][columnName]
                );
            });
        });
    }
};

// Populate grades from graded submissions
const populateGradesFromSubmissions = async () => {
    try {
        // Fetch graded submissions for this course
        const response = await axios.get(`/teacher/courses/${props.course.id}/graded-submissions`);
        const submissions = response.data.submissions || [];
        
        // Map submissions to gradebook
        submissions.forEach(submission => {
            const classwork = props.classworks.find(cw => cw.id === submission.classwork_id);
            
            if (!classwork || !classwork.grading_period || !classwork.grade_table_name || 
                !classwork.grade_main_column || !classwork.grade_sub_column) {
                return; // Skip if classwork is not linked to gradebook
            }
            
            const period = classwork.grading_period.toLowerCase();
            const tables = period === 'midterm' ? midtermTables.value : finalsTables.value;
            
            // Find the table
            const table = Object.values(tables).find(t => 
                t.name.toLowerCase() === classwork.grade_table_name.toLowerCase()
            );
            
            if (!table) return;
            
            // Find the column
            const column = table.columns.find(c => 
                c.name.toLowerCase() === classwork.grade_main_column.toLowerCase()
            );
            
            if (!column) return;
            
            // Find the subcolumn
            const subcolumn = column.subcolumns.find(sc => 
                sc.classworkId === classwork.id || 
                (sc.name === classwork.grade_sub_column && sc.isAutoPopulated)
            );
            
            if (!subcolumn) return;
            
            // Set the grade
            const gradeKey = `${table.id}-${column.id}-${subcolumn.id}`;
            if (studentGrades.value[submission.student_id] && 
                studentGrades.value[submission.student_id][period]) {
                studentGrades.value[submission.student_id][period][gradeKey] = submission.grade;
            }
        });

        // Default 0 for students without submissions in auto-populated subcolumns
        const ensureZeroForMissing = (periodTables, periodKey) => {
            Object.entries(periodTables).forEach(([tableKey, table]) => {
                table.columns.forEach(column => {
                    (column.subcolumns || []).forEach(subcolumn => {
                        if (!subcolumn.isAutoPopulated) return;
                        const gradeKey = `${tableKey}-${column.id}-${subcolumn.id}`;
                        Object.keys(studentGrades.value).forEach(studentId => {
                            const periodGrades = studentGrades.value[studentId]?.[periodKey];
                            if (periodGrades && (periodGrades[gradeKey] === undefined || periodGrades[gradeKey] === null || periodGrades[gradeKey] === '')) {
                                periodGrades[gradeKey] = 0;
                            }
                        });
                    });
                });
            });
        };

        ensureZeroForMissing(midtermTables.value, 'midterm');
        ensureZeroForMissing(finalsTables.value, 'finals');
    } catch (error) {
        console.error('Error loading submission grades:', error);
    }
};

// Remove auto-populated subcolumns whose classwork no longer exists and clear their grades
const cleanupRemovedClassworks = () => {
    const validIds = new Set((props.classworks || []).map(cw => cw.id));
    const cleanup = (periodTables, periodKey) => {
        Object.entries(periodTables).forEach(([tableKey, table]) => {
            table.columns.forEach(column => {
                const before = column.subcolumns?.length || 0;
                column.subcolumns = (column.subcolumns || []).filter(sub => {
                    const keep = !sub.isAutoPopulated || validIds.has(sub.classworkId);
                    if (!keep) {
                        // Delete saved grades for this subcolumn for all students
                        const gradeKey = `${tableKey}-${column.id}-${sub.id}`;
                        Object.keys(studentGrades.value).forEach(studentId => {
                            if (studentGrades.value[studentId]?.[periodKey]?.[gradeKey] !== undefined) {
                                delete studentGrades.value[studentId][periodKey][gradeKey];
                            }
                        });
                    }
                    return keep;
                });
                const after = column.subcolumns.length;
                if (before !== after) {
                    // Column changed; could trigger save
                }
            });
        });
    };
    cleanup(midtermTables.value, 'midterm');
    cleanup(finalsTables.value, 'finals');
};

const toggleSection = (period) => {
    if (period === 'midterm') {
        showMidtermSection.value = !showMidtermSection.value;
        if (showMidtermSection.value) {
            showFinalsSection.value = false;
        }
    } else {
        showFinalsSection.value = !showFinalsSection.value;
        if (showFinalsSection.value) {
            showMidtermSection.value = false;
        }
    }
};

const openAddTableModal = (period) => {
    currentPeriod.value = period;
    const remaining = getRemainingPercentage(period);
    tableForm.value = {
        name: '',
        percentage: remaining > 0 ? remaining : 0
    };
    showAddTableModal.value = true;
};

const addTable = () => {
    if (!tableForm.value.name || tableForm.value.percentage <= 0) {
        alert('Please enter a valid table name and percentage');
        return;
    }
    
    const currentTotal = getTotalTablesPercentage(currentPeriod.value);
    if (currentTotal + tableForm.value.percentage > 100) {
        alert(`You've exceeded 100% of total grading weight for ${currentPeriod.value === 'midterm' ? 'Midterm' : 'Finals'}. Adjust your table percentages.`);
        return;
    }
    
    const tableKey = tableForm.value.name.toLowerCase().replace(/\s+/g, '-') + '-' + Date.now();
    const tables = currentPeriod.value === 'midterm' ? midtermTables : finalsTables;
    
    tables.value[tableKey] = {
        id: tableKey,
        name: tableForm.value.name,
        percentage: tableForm.value.percentage,
        columns: []
    };
    
    showAddTableModal.value = false;
    saveGradebook();
};

const openEditTableModal = (period, tableKey) => {
    currentPeriod.value = period;
    const tables = getCurrentTables(period);
    const table = tables[tableKey];
    editingTableKey.value = tableKey;
    tableForm.value = {
        name: table.name,
        percentage: table.percentage
    };
    showEditTableModal.value = true;
};

const updateTable = () => {
    if (!tableForm.value.name || tableForm.value.percentage <= 0) {
        alert('Please enter a valid table name and percentage');
        return;
    }
    
    const tables = getCurrentTables(currentPeriod.value);
    const table = tables[editingTableKey.value];
    const otherTablesTotal = getTotalTablesPercentage(currentPeriod.value) - table.percentage;
    
    if (otherTablesTotal + tableForm.value.percentage > 100) {
        alert(`You've exceeded 100% of total grading weight for ${currentPeriod.value === 'midterm' ? 'Midterm' : 'Finals'}. Adjust your table percentages.`);
        return;
    }
    
    // Check if new percentage is less than current columns total
    const columnsTotal = getColumnsTotalPercentage(currentPeriod.value, editingTableKey.value);
    if (tableForm.value.percentage < columnsTotal) {
        if (!confirm(`Warning: The new table percentage (${tableForm.value.percentage}%) is less than the current columns total (${columnsTotal}%). You will need to adjust the column percentages. Continue?`)) {
            return;
        }
    }
    
    table.name = tableForm.value.name;
    table.percentage = tableForm.value.percentage;
    
    showEditTableModal.value = false;
    editingTableKey.value = null;
    saveGradebook();
};

const deleteTable = (period, tableKey) => {
    if (confirm('Are you sure you want to delete this table? All data will be lost.')) {
        const tables = period === 'midterm' ? midtermTables : finalsTables;
        delete tables.value[tableKey];
        // Recreate any required auto tables/columns from classworks
        populateSubcolumnsFromClassworks();
        cleanupRemovedClassworks();
        populateGradesFromSubmissions();
        saveGradebook();
    }
};

// Inline editing functions
const startEditTableName = (period, tableKey) => {
    const tables = getCurrentTables(period);
    const table = tables[tableKey];
    editingTableName.value = `${period}-${tableKey}`;
    tempTableName.value = table.name;
};

const finishEditTableName = (period, tableKey) => {
    if (tempTableName.value.trim()) {
        const tables = getCurrentTables(period);
        tables[tableKey].name = tempTableName.value.trim();
        saveGradebook();
    }
    editingTableName.value = null;
    tempTableName.value = '';
};

const startEditTablePercentage = (period, tableKey) => {
    const tables = getCurrentTables(period);
    const table = tables[tableKey];
    editingTablePercentage.value = `${period}-${tableKey}`;
    tempTablePercentage.value = table.percentage;
};

const finishEditTablePercentage = (period, tableKey) => {
    const tables = getCurrentTables(period);
    const table = tables[tableKey];
    const otherTablesTotal = getTotalTablesPercentage(period) - table.percentage;
    
    // Auto-adjust if exceeds 100%
    if (otherTablesTotal + tempTablePercentage.value > 100) {
        tempTablePercentage.value = 100 - otherTablesTotal;
    }
    
    // Ensure minimum of 0
    if (tempTablePercentage.value < 0) {
        tempTablePercentage.value = 0;
    }
    
    table.percentage = tempTablePercentage.value;
    editingTablePercentage.value = null;
    saveGradebook();
};

// Inline editing for columns
const startEditColumnName = (period, tableKey, columnIndex) => {
    const tables = getCurrentTables(period);
    const table = tables[tableKey];
    const column = table.columns[columnIndex];
    editingColumnName.value = `${period}-${tableKey}-${columnIndex}`;
    tempColumnName.value = column.name;
};

const finishEditColumnName = (period, tableKey, columnIndex) => {
    if (tempColumnName.value.trim()) {
        const tables = getCurrentTables(period);
        const table = tables[tableKey];
        table.columns[columnIndex].name = tempColumnName.value.trim();
        saveGradebook();
    }
    editingColumnName.value = null;
    tempColumnName.value = '';
};

const startEditColumnPercentage = (period, tableKey, columnIndex) => {
    const tables = getCurrentTables(period);
    const table = tables[tableKey];
    const column = table.columns[columnIndex];
    editingColumnPercentage.value = `${period}-${tableKey}-${columnIndex}`;
    tempColumnPercentage.value = column.percentage;
};

const finishEditColumnPercentage = (period, tableKey, columnIndex) => {
    const tables = getCurrentTables(period);
    const table = tables[tableKey];
    const column = table.columns[columnIndex];
    const otherColumnsTotal = getColumnsTotalPercentage(period, tableKey) - column.percentage;
    
    // Auto-adjust if exceeds table percentage
    if (otherColumnsTotal + tempColumnPercentage.value > table.percentage) {
        tempColumnPercentage.value = table.percentage - otherColumnsTotal;
    }
    
    // Ensure minimum of 0
    if (tempColumnPercentage.value < 0) {
        tempColumnPercentage.value = 0;
    }
    
    column.percentage = tempColumnPercentage.value;
    editingColumnPercentage.value = null;
    saveGradebook();
};

// Inline editing for subcolumns
const startEditSubcolumnName = (period, tableKey, columnIndex, subcolumnIndex) => {
    const tables = getCurrentTables(period);
    const table = tables[tableKey];
    const column = table.columns[columnIndex];
    const subcolumn = column.subcolumns[subcolumnIndex];
    
    if (subcolumn.isAutoPopulated) return; // Don't edit auto-populated
    
    editingSubcolumnName.value = `${period}-${tableKey}-${columnIndex}-${subcolumnIndex}`;
    tempSubcolumnName.value = subcolumn.name;
};

const finishEditSubcolumnName = (period, tableKey, columnIndex, subcolumnIndex) => {
    if (tempSubcolumnName.value.trim()) {
        const tables = getCurrentTables(period);
        const table = tables[tableKey];
        const column = table.columns[columnIndex];
        column.subcolumns[subcolumnIndex].name = tempSubcolumnName.value.trim();
        saveGradebook();
    }
    editingSubcolumnName.value = null;
    tempSubcolumnName.value = '';
};

const startEditSubcolumnMaxPoints = (period, tableKey, columnIndex, subcolumnIndex) => {
    const tables = getCurrentTables(period);
    const table = tables[tableKey];
    const column = table.columns[columnIndex];
    const subcolumn = column.subcolumns[subcolumnIndex];
    
    if (subcolumn.isAutoPopulated) return; // Don't edit auto-populated
    
    editingSubcolumnMaxPoints.value = `${period}-${tableKey}-${columnIndex}-${subcolumnIndex}`;
    tempSubcolumnMaxPoints.value = subcolumn.maxPoints;
};

const finishEditSubcolumnMaxPoints = (period, tableKey, columnIndex, subcolumnIndex) => {
    if (tempSubcolumnMaxPoints.value > 0) {
        const tables = getCurrentTables(period);
        const table = tables[tableKey];
        const column = table.columns[columnIndex];
        column.subcolumns[subcolumnIndex].maxPoints = tempSubcolumnMaxPoints.value;
        saveGradebook();
    }
    editingSubcolumnMaxPoints.value = null;
    tempSubcolumnMaxPoints.value = 100;
};

const openAddColumnModal = (period, tableKey) => {
    currentPeriod.value = period;
    editingTableKey.value = tableKey;
    const remaining = getRemainingColumnPercentage(period, tableKey);
    columnForm.value = {
        name: '',
        percentage: remaining > 0 ? remaining : 0
    };
    showAddColumnModal.value = true;
};

const addColumn = () => {
    if (!columnForm.value.name || columnForm.value.percentage <= 0) {
        alert('Please enter a valid column name and percentage');
        return;
    }
    
    const tables = getCurrentTables(currentPeriod.value);
    const table = tables[editingTableKey.value];
    const currentTotal = getColumnsTotalPercentage(currentPeriod.value, editingTableKey.value);
    
    if (currentTotal + columnForm.value.percentage > table.percentage) {
        alert(`${table.name} table exceeds its total weight of ${table.percentage}%. Adjust column percentages.`);
        return;
    }
    
    const columnId = columnForm.value.name.toLowerCase().replace(/\s+/g, '-') + '-' + Date.now();
    table.columns.push({
        id: columnId,
        name: columnForm.value.name,
        percentage: columnForm.value.percentage,
        subcolumns: []
    });
    
    showAddColumnModal.value = false;
    editingTableKey.value = null;
    saveGradebook();
};

const openEditColumnModal = (period, tableKey, columnIndex) => {
    currentPeriod.value = period;
    editingTableKey.value = tableKey;
    editingColumnIndex.value = columnIndex;
    
    const tables = getCurrentTables(period);
    const table = tables[tableKey];
    const column = table.columns[columnIndex];
    
    columnForm.value = {
        name: column.name,
        percentage: column.percentage
    };
    showEditColumnModal.value = true;
};

const updateColumn = () => {
    if (!columnForm.value.name || columnForm.value.percentage <= 0) {
        alert('Please enter a valid column name and percentage');
        return;
    }
    
    const tables = getCurrentTables(currentPeriod.value);
    const table = tables[editingTableKey.value];
    const column = table.columns[editingColumnIndex.value];
    
    const otherColumnsTotal = getColumnsTotalPercentage(currentPeriod.value, editingTableKey.value) - column.percentage;
    
    if (otherColumnsTotal + columnForm.value.percentage > table.percentage) {
        alert(`${table.name} table exceeds its total weight of ${table.percentage}%. Adjust column percentages.`);
        return;
    }
    
    column.name = columnForm.value.name;
    column.percentage = columnForm.value.percentage;
    
    showEditColumnModal.value = false;
    editingTableKey.value = null;
    editingColumnIndex.value = null;
    saveGradebook();
};

const deleteColumn = (period, tableKey, columnIndex) => {
    if (confirm('Are you sure you want to delete this column? All data will be lost.')) {
        const tables = getCurrentTables(period);
        const table = tables[tableKey];
        table.columns.splice(columnIndex, 1);
        // Recreate any required auto columns/subcolumns from classworks
        populateSubcolumnsFromClassworks();
        cleanupRemovedClassworks();
        populateGradesFromSubmissions();
        saveGradebook();
    }
};

const openAddSubcolumnModal = (period, tableKey, columnIndex) => {
    currentPeriod.value = period;
    editingTableKey.value = tableKey;
    editingColumnIndex.value = columnIndex;
    subcolumnForm.value = {
        name: '',
        maxPoints: 100
    };
    showAddSubcolumnModal.value = true;
};

const addSubcolumn = () => {
    if (!subcolumnForm.value.name || subcolumnForm.value.maxPoints <= 0) {
        alert('Please enter a valid subcolumn name and max points');
        return;
    }
    
    const tables = getCurrentTables(currentPeriod.value);
    const table = tables[editingTableKey.value];
    const column = table.columns[editingColumnIndex.value];
    
    const subcolumnId = subcolumnForm.value.name.toLowerCase().replace(/\s+/g, '-') + '-' + Date.now();
    column.subcolumns.push({
        id: subcolumnId,
        name: subcolumnForm.value.name,
        maxPoints: subcolumnForm.value.maxPoints,
        isAutoPopulated: false
    });
    
    showAddSubcolumnModal.value = false;
    editingTableKey.value = null;
    editingColumnIndex.value = null;
    saveGradebook();
};

const openEditSubcolumnModal = (period, tableKey, columnIndex, subcolumnIndex) => {
    currentPeriod.value = period;
    editingTableKey.value = tableKey;
    editingColumnIndex.value = columnIndex;
    editingSubcolumnIndex.value = subcolumnIndex;
    
    const tables = getCurrentTables(period);
    const table = tables[tableKey];
    const column = table.columns[columnIndex];
    const subcolumn = column.subcolumns[subcolumnIndex];
    
    subcolumnForm.value = {
        name: subcolumn.name,
        maxPoints: subcolumn.maxPoints
    };
    showEditSubcolumnModal.value = true;
};

const updateSubcolumn = () => {
    if (!subcolumnForm.value.name || subcolumnForm.value.maxPoints <= 0) {
        alert('Please enter a valid subcolumn name and max points');
        return;
    }
    
    const tables = getCurrentTables(currentPeriod.value);
    const table = tables[editingTableKey.value];
    const column = table.columns[editingColumnIndex.value];
    const subcolumn = column.subcolumns[editingSubcolumnIndex.value];
    
    subcolumn.name = subcolumnForm.value.name;
    subcolumn.maxPoints = subcolumnForm.value.maxPoints;
    
    showEditSubcolumnModal.value = false;
    editingTableKey.value = null;
    editingColumnIndex.value = null;
    editingSubcolumnIndex.value = null;
    saveGradebook();
};

const deleteSubcolumn = (period, tableKey, columnIndex, subcolumnIndex) => {
    if (confirm('Are you sure you want to delete this subcolumn? All data will be lost.')) {
        const tables = getCurrentTables(period);
        const table = tables[tableKey];
        const column = table.columns[columnIndex];
        column.subcolumns.splice(subcolumnIndex, 1);
        saveGradebook();
    }
};

const saveGradebook = async () => {
    isSaving.value = true;
    saveIndicator.value = 'Saving…';
    try {
        // Ensure grades are stored as flat objects, not arrays
        const flattenGrades = (gradesObj) => {
            if (!gradesObj || typeof gradesObj !== 'object') return {};
            // If gradesObj is already flat, return as is
            if (!Array.isArray(gradesObj)) return gradesObj;
            // If gradesObj is an array, flatten it
            const flat = {};
            gradesObj.forEach(item => {
                if (typeof item === 'object' && !Array.isArray(item)) {
                    Object.assign(flat, item);
                }
            });
            return flat;
        };
        // Compute and persist period grades for all students
        const periodGrades = { midterm: {}, finals: {} };
        Object.keys(studentGrades.value).forEach(studentId => {
            periodGrades.midterm[studentId] = calculateStudentPeriodGrade(studentId, 'midterm');
            periodGrades.finals[studentId] = calculateStudentPeriodGrade(studentId, 'finals');
        });
        const data = {
            midtermPercentage: midtermPercentage.value,
            finalsPercentage: finalsPercentage.value,
            midterm: {
                tables: Object.values(midtermTables.value),
                grades: Object.keys(studentGrades.value).reduce((acc, studentId) => {
                    acc[studentId] = flattenGrades(studentGrades.value[studentId].midterm);
                    return acc;
                }, {}),
                periodGrades: periodGrades.midterm
            },
            finals: {
                tables: Object.values(finalsTables.value),
                grades: Object.keys(studentGrades.value).reduce((acc, studentId) => {
                    acc[studentId] = flattenGrades(studentGrades.value[studentId].finals);
                    return acc;
                }, {}),
                periodGrades: periodGrades.finals
            }
        };
        
        // Debug: log data to be saved
        console.log('[DEBUG] Saving gradebook data to backend and localStorage:', data);
        // Save to backend
        try {
            const response = await axios.post(`/teacher/courses/${props.course.id}/gradebook/save`, data);
            console.log('[DEBUG] Gradebook saved to backend:', response.data);
        } catch (err) {
            console.warn('[DEBUG] Backend save failed, will rely on localStorage:', err);
        }
        // Always save to localStorage
        try {
            localStorage.setItem(`gradebook:${props.course.id}`, JSON.stringify({ ...data, _savedAt: Date.now() }));
            console.log('[DEBUG] Gradebook data saved to localStorage:', localStorage.getItem(`gradebook:${props.course.id}`));
        } catch (e) {
            console.error('[DEBUG] Error saving to localStorage:', e);
        }
    } catch (error) {
        console.error('Error saving gradebook:', error);
        alert('Error saving gradebook: ' + (error.response?.data?.message || error.message));
    } finally {
        isSaving.value = false;
        saveIndicator.value = 'All changes saved';
        setTimeout(() => { if (saveIndicator.value === 'All changes saved') saveIndicator.value = ''; }, 2000);
    }
};

// Debounced autosave of numeric inputs so entries persist after reload/logout
const scheduleSave = () => {
    if (saveTimer) clearTimeout(saveTimer);
    saveTimer = setTimeout(() => {
        saveGradebook();
    }, 500);
};

watch(studentGrades, () => {
    scheduleSave();
}, { deep: true });

const loadGradebook = async () => {
    try {
        const response = await axios.get(`/teacher/courses/${props.course.id}/gradebook/load`);
        if (response.data && response.data.gradebook) {
            const gradebook = response.data.gradebook;
            
            // Load percentages
            if (gradebook.midtermPercentage !== undefined) {
                midtermPercentage.value = gradebook.midtermPercentage;
            }
            if (gradebook.finalsPercentage !== undefined) {
                finalsPercentage.value = gradebook.finalsPercentage;
            }
            
            // Load midterm tables structure (keep defaults if no saved data)
            if (gradebook.midterm && gradebook.midterm.tables && gradebook.midterm.tables.length > 0) {
                const midtermTablesObj = {};
                gradebook.midterm.tables.forEach(table => {
                    midtermTablesObj[table.id] = table;
                });
                midtermTables.value = midtermTablesObj;
            }
            // If no saved tables, keep the default structure
            
            // Load finals tables structure (keep defaults if no saved data)
            if (gradebook.finals && gradebook.finals.tables && gradebook.finals.tables.length > 0) {
                const finalsTablesObj = {};
                gradebook.finals.tables.forEach(table => {
                    finalsTablesObj[table.id] = table;
                });
                finalsTables.value = finalsTablesObj;
            }
            // If no saved tables, keep the default structure
            
            // Load grades
            if (gradebook.midterm && gradebook.midterm.grades) {
                Object.keys(gradebook.midterm.grades).forEach(studentId => {
                    if (studentGrades.value[studentId]) {
                        studentGrades.value[studentId].midterm = gradebook.midterm.grades[studentId];
                    }
                });
            }
            
            if (gradebook.finals && gradebook.finals.grades) {
                Object.keys(gradebook.finals.grades).forEach(studentId => {
                    if (studentGrades.value[studentId]) {
                        studentGrades.value[studentId].finals = gradebook.finals.grades[studentId];
                    }
                });
            }
        }
        // If no gradebook data at all, defaults will be used automatically
    } catch (error) {
        console.error('Error loading gradebook:', error);
        // On error, keep the default tables
    }
};

onMounted(async () => {
    await loadGradebook();
    populateSubcolumnsFromClassworks();
    populateGradesFromSubmissions();
    // Always try to restore from local backup (offline safety)
    try {
        const cached = localStorage.getItem(`gradebook:${props.course.id}`);
        console.log('[DEBUG] Attempting to restore gradebook from localStorage:', cached);
        if (cached) {
            const parsed = JSON.parse(cached);
            if (parsed?.midterm || parsed?.finals) {
                // Restore grades to UI if not already present
                if (parsed.midterm?.grades) {
                    Object.keys(parsed.midterm.grades).forEach(studentId => {
                        if (!studentGrades.value[studentId]) studentGrades.value[studentId] = { midterm: {}, finals: {} };
                        if (Object.keys(studentGrades.value[studentId].midterm || {}).length === 0) {
                            studentGrades.value[studentId].midterm = parsed.midterm.grades[studentId] || {};
                        }
                    });
                }
                if (parsed.finals?.grades) {
                    Object.keys(parsed.finals.grades).forEach(studentId => {
                        if (!studentGrades.value[studentId]) studentGrades.value[studentId] = { midterm: {}, finals: {} };
                        if (Object.keys(studentGrades.value[studentId].finals || {}).length === 0) {
                            studentGrades.value[studentId].finals = parsed.finals.grades[studentId] || {};
                        }
                    });
                }
                console.log('[DEBUG] Gradebook restored from localStorage:', parsed);
            }
        }
    } catch (e) {
        console.error('[DEBUG] Error restoring from localStorage:', e);
    }
});

// Ensure pending changes are flushed before leaving the page
if (typeof window !== 'undefined') {
    window.addEventListener('beforeunload', () => {
        if (saveTimer) {
            clearTimeout(saveTimer);
            saveTimer = null;
        }
        // Best-effort synchronous save indicator; network call may not complete
        try {
            localStorage.setItem(`gradebook:${props.course.id}:lastExit`, String(Date.now()));
        } catch (_) {}
    });
}

// Watch for classworks change to keep gradebook in sync (add new, update points, remove deleted)
watch(
    () => props.classworks,
    async () => {
        populateSubcolumnsFromClassworks();
        cleanupRemovedClassworks();
        await populateGradesFromSubmissions();
        // Persist changes after sync
        saveGradebook();
    },
    { deep: true }
);
</script>

<template>
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    ⚙️ Grade Computation Setup
                </h2>
                <div class="relative">
                    <button 
                        @mouseenter="showTooltip = true" 
                        @mouseleave="showTooltip = false"
                        class="text-blue-600 hover:text-blue-800"
                    >
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div v-if="showTooltip" class="absolute right-0 top-8 w-64 bg-gray-900 text-white text-sm rounded-lg p-3 shadow-xl z-10">
                        This only affects the final grade computation: Final Grade = (Midterm × %Midterm) + (Finals × %Finals)
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Midterm Percentage</label>
                    <div class="flex items-center gap-2">
                        <input 
                            v-model.number="midtermPercentage"
                            @blur="validateGradingPeriodPercentage('midterm')"
                            type="number"
                            min="0"
                            max="100"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                        <span class="text-lg font-semibold text-gray-700">%</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Finals Percentage</label>
                    <div class="flex items-center gap-2">
                        <input 
                            v-model.number="finalsPercentage"
                            @blur="validateGradingPeriodPercentage('finals')"
                            type="number"
                            min="0"
                            max="100"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                        <span class="text-lg font-semibold text-gray-700">%</span>
                    </div>
                </div>
            </div>

            <div v-if="midtermPercentageError" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-800 text-sm font-medium">⚠️ {{ midtermPercentageError }}</p>
            </div>

            <div v-else class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-green-800 text-sm font-medium">✅ Total computation weight: 100%</p>
            </div>
        </div>

        <!-- Midterm and Finals Button Cards Side by Side -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Midterm Button Card -->
            <button 
                @click="toggleSection('midterm')"
                class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-lg overflow-hidden px-6 py-4 flex items-center justify-between text-white hover:from-blue-600 hover:to-blue-700 transition-all"
                :class="{ 'ring-4 ring-blue-300': showMidtermSection }"
            >
                <div class="flex items-center gap-3">
                    <span class="text-2xl">📘</span>
                    <h3 class="text-xl font-bold">Midterm Grading Setup</h3>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm bg-white/20 px-3 py-1 rounded-full">
                        Remaining: {{ getRemainingPercentage('midterm') }}%
                    </span>
                    <svg 
                        class="w-6 h-6 transform transition-transform" 
                        :class="{ 'rotate-180': showMidtermSection }"
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </button>

            <!-- Finals Button Card -->
            <button 
                @click="toggleSection('finals')"
                class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg overflow-hidden px-6 py-4 flex items-center justify-between text-white hover:from-green-600 hover:to-green-700 transition-all"
                :class="{ 'ring-4 ring-green-300': showFinalsSection }"
            >
                <div class="flex items-center gap-3">
                    <span class="text-2xl">�</span>
                    <h3 class="text-xl font-bold">Finals Grading Setup</h3>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm bg-white/20 px-3 py-1 rounded-full">
                        Remaining: {{ getRemainingPercentage('finals') }}%
                    </span>
                    <svg 
                        class="w-6 h-6 transform transition-transform" 
                        :class="{ 'rotate-180': showFinalsSection }"
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </button>
        </div>

        <!-- Midterm Content (Full Width Below) -->
        <div v-if="showMidtermSection" class="bg-white rounded-lg shadow-lg border-4 border-blue-500 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-3">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    📘 Midterm Period Grading
                </h3>
            </div>
            <div class="p-6 space-y-6">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-lg font-semibold text-gray-900">Grading Tables</h4>
                    <button 
                        @click="openAddTableModal('midterm')"
                        :disabled="!canAddTable('midterm')"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Table
                    </button>
                </div>

                <!-- Empty State Message -->
                <div v-if="Object.keys(midtermTables).length === 0" class="text-center py-12 bg-blue-50 rounded-lg border-2 border-dashed border-blue-300">
                    <svg class="w-16 h-16 mx-auto text-blue-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No Grading Tables Yet</h3>
                    <p class="text-gray-600 mb-4">Start designing your grading system by adding your first table</p>
                    <button 
                        @click="openAddTableModal('midterm')"
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 inline-flex items-center gap-2 font-semibold"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create First Table
                    </button>
                </div>

                <div v-for="(table, tableKey) in midtermTables" :key="tableKey" class="border border-gray-300 rounded-lg overflow-hidden">
                    <div class="bg-gray-100 px-4 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <!-- Summary Table Badge -->
                            <span v-if="table.isSummary" class="px-3 py-1 bg-purple-100 text-purple-700 text-xs font-bold rounded-full">
                                AUTO-COMPUTED
                            </span>
                            
                            <!-- Editable Table Name (disabled for summary) -->
                            <div v-if="editingTableName === `midterm-${tableKey}` && !table.isReadOnly" class="flex items-center gap-2">
                                <input 
                                    v-model="tempTableName"
                                    @blur="finishEditTableName('midterm', tableKey)"
                                    @keyup.enter="finishEditTableName('midterm', tableKey)"
                                    @keyup.esc="editingTableName = null"
                                    class="px-3 py-1 border-2 border-blue-500 rounded font-semibold text-gray-900 focus:outline-none"
                                    autofocus
                                />
                            </div>
                            <h5 
                                v-else
                                @click="!table.isReadOnly && startEditTableName('midterm', tableKey)"
                                :class="[
                                    'font-semibold text-gray-900',
                                    table.isReadOnly ? 'cursor-default' : 'cursor-pointer hover:text-blue-600 hover:underline'
                                ]"
                                :title="table.isReadOnly ? 'Summary table (read-only)' : 'Click to edit table name'"
                            >
                                {{ table.name }}
                            </h5>
                            
                            <!-- Editable Table Percentage (disabled for summary) -->
                            <div v-if="editingTablePercentage === `midterm-${tableKey}` && !table.isReadOnly" class="flex items-center gap-1">
                                <input 
                                    v-model.number="tempTablePercentage"
                                    @blur="finishEditTablePercentage('midterm', tableKey)"
                                    @keyup.enter="finishEditTablePercentage('midterm', tableKey)"
                                    @keyup.esc="editingTablePercentage = null"
                                    type="number"
                                    min="0"
                                    max="100"
                                    class="w-16 px-2 py-1 border-2 border-blue-500 rounded text-sm text-gray-600 text-center focus:outline-none"
                                    autofocus
                                />
                                <span class="text-sm text-gray-600">%</span>
                            </div>
                            <span 
                                v-else-if="!table.isSummary"
                                @click="!table.isReadOnly && startEditTablePercentage('midterm', tableKey)"
                                :class="[
                                    'text-sm text-gray-600 bg-white px-3 py-1 rounded-full',
                                    table.isReadOnly ? 'cursor-default' : 'cursor-pointer hover:bg-blue-100 hover:text-blue-700'
                                ]"
                                :title="table.isReadOnly ? 'Summary table (read-only)' : 'Click to edit percentage'"
                            >
                                {{ table.percentage }}%
                            </span>
                            
                            <span v-if="!table.isSummary" class="text-xs text-gray-500">
                                (Columns: {{ getColumnsTotalPercentage('midterm', tableKey) }}% / {{ table.percentage }}%)
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <button 
                                v-if="!table.isReadOnly"
                                @click="openAddColumnModal('midterm', tableKey)"
                                :disabled="!canAddColumn('midterm', tableKey)"
                                class="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700 disabled:bg-gray-300"
                            >
                                + Column
                            </button>
                            <button 
                                v-if="!table.isReadOnly"
                                @click="deleteTable('midterm', tableKey)"
                                class="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700"
                            >
                                Delete
                            </button>
                        </div>
                    </div>

                    <div v-if="table.columns && table.columns.length > 0" class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 border-b">Student</th>
                                    <th 
                                        v-for="(column, colIndex) in table.columns" 
                                        :key="column.id"
                                        :colspan="column.subcolumns.length || 1"
                                        class="px-4 py-2 text-center text-sm font-semibold text-gray-700 border-b border-l"
                                    >
                                        <div class="flex items-center justify-center gap-2 mb-2">
                                            <!-- Editable Column Name (disabled for read-only tables) -->
                                            <div v-if="editingColumnName === `midterm-${tableKey}-${colIndex}` && !table.isReadOnly" class="flex items-center gap-1">
                                                <input 
                                                    v-model="tempColumnName"
                                                    @blur="finishEditColumnName('midterm', tableKey, colIndex)"
                                                    @keyup.enter="finishEditColumnName('midterm', tableKey, colIndex)"
                                                    @keyup.esc="editingColumnName = null"
                                                    class="px-2 py-1 border-2 border-blue-500 rounded text-sm focus:outline-none"
                                                    autofocus
                                                />
                                            </div>
                                            <span 
                                                v-else
                                                @click="!table.isReadOnly && startEditColumnName('midterm', tableKey, colIndex)"
                                                :class="[
                                                    table.isReadOnly ? 'cursor-default' : 'cursor-pointer hover:text-blue-600 hover:underline'
                                                ]"
                                                :title="table.isReadOnly ? 'Read-only column' : 'Click to edit column name'"
                                            >
                                                {{ column.name }}
                                            </span>
                                            
                                            <!-- Editable Column Percentage (disabled for read-only tables) -->
                                            <div v-if="editingColumnPercentage === `midterm-${tableKey}-${colIndex}` && !table.isReadOnly" class="flex items-center gap-1">
                                                <span>(</span>
                                                <input 
                                                    v-model.number="tempColumnPercentage"
                                                    @blur="finishEditColumnPercentage('midterm', tableKey, colIndex)"
                                                    @keyup.enter="finishEditColumnPercentage('midterm', tableKey, colIndex)"
                                                    @keyup.esc="editingColumnPercentage = null"
                                                    type="number"
                                                    min="0"
                                                    class="w-12 px-1 py-1 border-2 border-blue-500 rounded text-sm text-center focus:outline-none"
                                                    autofocus
                                                />
                                                <span>%)</span>
                                            </div>
                                            <span 
                                                v-else-if="!table.isSummary"
                                                @click="!table.isReadOnly && startEditColumnPercentage('midterm', tableKey, colIndex)"
                                                :class="[
                                                    table.isReadOnly ? 'cursor-default' : 'cursor-pointer hover:text-blue-600 hover:underline'
                                                ]"
                                                :title="table.isReadOnly ? 'Read-only column' : 'Click to edit percentage'"
                                            >
                                                ({{ column.percentage }}%)
                                            </span>
                                            
                                            <button 
                                                v-if="!table.isReadOnly"
                                                @click="openAddSubcolumnModal('midterm', tableKey, colIndex)"
                                                class="text-xs px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600"
                                                title="Add Subcolumn"
                                            >
                                                + Sub
                                            </button>
                                            <button 
                                                v-if="!table.isReadOnly"
                                                @click="deleteColumn('midterm', tableKey, colIndex)"
                                                class="text-red-600 hover:text-red-800"
                                            >
                                                🗑️
                                            </button>
                                        </div>
                                    </th>
                                    <th class="px-4 py-2 text-center text-sm font-semibold text-gray-700 border-b border-l">Total</th>
                                </tr>
                                <tr v-if="table.columns.some(col => col.subcolumns && col.subcolumns.length > 0)">
                                    <th class="px-4 py-2 border-b"></th>
                                    <template v-for="(column, colIndex) in table.columns" :key="column.id + '-sub'">
                                        <th 
                                            v-for="(subcolumn, subIndex) in column.subcolumns" 
                                            :key="subcolumn.id"
                                            class="px-2 py-2 text-center text-xs text-gray-600 border-b border-l"
                                        >
                                            <div class="flex flex-col items-center gap-1">
                                                <!-- Editable Subcolumn Name -->
                                                <div v-if="editingSubcolumnName === `midterm-${tableKey}-${colIndex}-${subIndex}`">
                                                    <input 
                                                        v-model="tempSubcolumnName"
                                                        @blur="finishEditSubcolumnName('midterm', tableKey, colIndex, subIndex)"
                                                        @keyup.enter="finishEditSubcolumnName('midterm', tableKey, colIndex, subIndex)"
                                                        @keyup.esc="editingSubcolumnName = null"
                                                        class="px-2 py-1 border-2 border-blue-500 rounded text-xs focus:outline-none w-24"
                                                        autofocus
                                                    />
                                                </div>
                                                <div 
                                                    v-else
                                                    @click="startEditSubcolumnName('midterm', tableKey, colIndex, subIndex)"
                                                    class="cursor-pointer hover:text-blue-600 hover:underline"
                                                    :class="{ 'cursor-not-allowed opacity-50': subcolumn.isAutoPopulated }"
                                                    :title="subcolumn.isAutoPopulated ? 'Auto-populated from classwork' : 'Click to edit name'"
                                                >
                                                    {{ subcolumn.name }}
                                                </div>
                                                
                                                <!-- Editable Max Points -->
                                                <div v-if="editingSubcolumnMaxPoints === `midterm-${tableKey}-${colIndex}-${subIndex}`" class="flex items-center gap-1">
                                                    <span class="text-gray-500">(</span>
                                                    <input 
                                                        v-model.number="tempSubcolumnMaxPoints"
                                                        @blur="finishEditSubcolumnMaxPoints('midterm', tableKey, colIndex, subIndex)"
                                                        @keyup.enter="finishEditSubcolumnMaxPoints('midterm', tableKey, colIndex, subIndex)"
                                                        @keyup.esc="editingSubcolumnMaxPoints = null"
                                                        type="number"
                                                        min="1"
                                                        class="w-12 px-1 py-1 border-2 border-blue-500 rounded text-xs text-center focus:outline-none"
                                                        autofocus
                                                    />
                                                    <span class="text-gray-500">pts)</span>
                                                </div>
                                                <div 
                                                    v-else
                                                    @click="startEditSubcolumnMaxPoints('midterm', tableKey, colIndex, subIndex)"
                                                    class="text-gray-500 cursor-pointer hover:text-blue-600"
                                                    :class="{ 'cursor-not-allowed opacity-50': subcolumn.isAutoPopulated }"
                                                    :title="subcolumn.isAutoPopulated ? 'Auto-populated from classwork' : 'Click to edit max points'"
                                                >
                                                    ({{ subcolumn.maxPoints }} pts)
                                                </div>
                                                
                                                <div class="flex gap-1" v-if="!subcolumn.isAutoPopulated">
                                                    <button 
                                                        @click="deleteSubcolumn('midterm', tableKey, colIndex, subIndex)"
                                                        class="text-red-600 hover:text-red-800 text-xs"
                                                        title="Delete Subcolumn"
                                                    >
                                                        🗑️
                                                    </button>
                                                </div>
                                                <div v-else class="text-xs text-blue-600" title="Auto-populated from classwork">
                                                    📝 Auto
                                                </div>
                                            </div>
                                        </th>
                                        <th v-if="!column.subcolumns || column.subcolumns.length === 0" class="border-b border-l"></th>
                                    </template>
                                    <th class="px-4 py-2 border-b border-l"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="student in props.students" :key="student.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-2 border-b text-sm">{{ student.name }}</td>
                                    <template v-for="column in table.columns" :key="column.id">
                                        <td 
                                            v-for="subcolumn in column.subcolumns" 
                                            :key="subcolumn.id"
                                            class="px-2 py-2 border-b border-l"
                                        >
                                            <!-- Read-only display for Summary table -->
                                            <div 
                                                v-if="table.isSummary"
                                                class="w-full px-2 py-1 text-sm border border-purple-300 rounded text-center bg-purple-50 font-semibold text-purple-700"
                                            >
                                                {{ calculateStudentTableTotal(student.id, 'midterm', tableKey) }}
                                            </div>
                                            <!-- Editable input for other tables -->
                                            <input 
                                                v-else
                                                v-model="studentGrades[student.id].midterm[`${tableKey}-${column.id}-${subcolumn.id}`]"
                                                @blur="saveGradebook"
                                                type="number"
                                                :max="subcolumn.maxPoints"
                                                min="0"
                                                step="0.01"
                                                :disabled="subcolumn.isAutoPopulated"
                                                :class="subcolumn.isAutoPopulated 
                                                    ? 'w-full px-2 py-1 text-sm border border-gray-300 rounded text-center bg-gray-100 cursor-not-allowed' 
                                                    : 'w-full px-2 py-1 text-sm border border-gray-300 rounded text-center focus:ring-2 focus:ring-blue-500'"
                                                :title="subcolumn.isAutoPopulated ? 'This grade is automatically updated from classwork submissions' : ''"
                                            />
                                        </td>
                                        <td v-if="!column.subcolumns || column.subcolumns.length === 0" class="px-4 py-2 border-b border-l text-center text-sm text-gray-500">
                                            N/A
                                        </td>
                                    </template>
                                    <td class="px-4 py-2 border-b border-l text-center font-semibold text-sm">
                                        {{ calculateStudentTableTotal(student.id, 'midterm', tableKey) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="p-4 text-center text-gray-500 text-sm">
                        No columns added yet. Click "+ Column" to add grading columns.
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h5 class="font-semibold text-blue-900 mb-3">Midterm Period Grades</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div v-for="student in props.students" :key="student.id" class="bg-white p-3 rounded-lg border border-blue-200">
                            <p class="text-sm text-gray-700">{{ student.name }}</p>
                            <p class="text-2xl font-bold text-blue-600">{{ calculateStudentPeriodGrade(student.id, 'midterm') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Finals Content (Full Width Below) -->
        <div v-if="showFinalsSection" class="bg-white rounded-lg shadow-lg border-4 border-green-500 overflow-hidden">
            <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-3">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    📗 Finals Period Grading
                </h3>
            </div>
            <div class="p-6 space-y-6">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-lg font-semibold text-gray-900">Grading Tables</h4>
                    <button 
                        @click="openAddTableModal('finals')"
                        :disabled="!canAddTable('finals')"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-300 disabled:cursor-not-allowed flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Table
                    </button>
                </div>

                <!-- Empty State Message -->
                <div v-if="Object.keys(finalsTables).length === 0" class="text-center py-12 bg-green-50 rounded-lg border-2 border-dashed border-green-300">
                    <svg class="w-16 h-16 mx-auto text-green-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">No Grading Tables Yet</h3>
                    <p class="text-gray-600 mb-4">Start designing your grading system by adding your first table</p>
                    <button 
                        @click="openAddTableModal('finals')"
                        class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 inline-flex items-center gap-2 font-semibold"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create First Table
                    </button>
                </div>

                <div v-for="(table, tableKey) in finalsTables" :key="tableKey" class="border border-gray-300 rounded-lg overflow-hidden">
                    <div class="bg-gray-100 px-4 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <!-- Summary Table Badge -->
                            <span v-if="table.isSummary" class="px-3 py-1 bg-purple-100 text-purple-700 text-xs font-bold rounded-full">
                                AUTO-COMPUTED
                            </span>
                            
                            <!-- Editable Table Name (disabled for summary) -->
                            <div v-if="editingTableName === `finals-${tableKey}` && !table.isReadOnly" class="flex items-center gap-2">
                                <input 
                                    v-model="tempTableName"
                                    @blur="finishEditTableName('finals', tableKey)"
                                    @keyup.enter="finishEditTableName('finals', tableKey)"
                                    @keyup.esc="editingTableName = null"
                                    class="px-3 py-1 border-2 border-green-500 rounded font-semibold text-gray-900 focus:outline-none"
                                    autofocus
                                />
                            </div>
                            <h5 
                                v-else
                                @click="!table.isReadOnly && startEditTableName('finals', tableKey)"
                                :class="[
                                    'font-semibold text-gray-900',
                                    table.isReadOnly ? 'cursor-default' : 'cursor-pointer hover:text-green-600 hover:underline'
                                ]"
                                :title="table.isReadOnly ? 'Summary table (read-only)' : 'Click to edit table name'"
                            >
                                {{ table.name }}
                            </h5>
                            
                            <!-- Editable Table Percentage (disabled for summary) -->
                            <div v-if="editingTablePercentage === `finals-${tableKey}` && !table.isReadOnly" class="flex items-center gap-1">
                                <input 
                                    v-model.number="tempTablePercentage"
                                    @blur="finishEditTablePercentage('finals', tableKey)"
                                    @keyup.enter="finishEditTablePercentage('finals', tableKey)"
                                    @keyup.esc="editingTablePercentage = null"
                                    type="number"
                                    min="0"
                                    max="100"
                                    class="w-16 px-2 py-1 border-2 border-green-500 rounded text-sm text-gray-600 text-center focus:outline-none"
                                    autofocus
                                />
                                <span class="text-sm text-gray-600">%</span>
                            </div>
                            <span 
                                v-else-if="!table.isSummary"
                                @click="!table.isReadOnly && startEditTablePercentage('finals', tableKey)"
                                :class="[
                                    'text-sm text-gray-600 bg-white px-3 py-1 rounded-full',
                                    table.isReadOnly ? 'cursor-default' : 'cursor-pointer hover:bg-green-100 hover:text-green-700'
                                ]"
                                :title="table.isReadOnly ? 'Summary table (read-only)' : 'Click to edit percentage'"
                            >
                                {{ table.percentage }}%
                            </span>
                            
                            <span v-if="!table.isSummary" class="text-xs text-gray-500">
                                (Columns: {{ getColumnsTotalPercentage('finals', tableKey) }}% / {{ table.percentage }}%)
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <button 
                                v-if="!table.isReadOnly"
                                @click="openAddColumnModal('finals', tableKey)"
                                :disabled="!canAddColumn('finals', tableKey)"
                                class="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700 disabled:bg-gray-300"
                            >
                                + Column
                            </button>
                            <button 
                                v-if="!table.isReadOnly"
                                @click="deleteTable('finals', tableKey)"
                                class="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700"
                            >
                                Delete
                            </button>
                        </div>
                    </div>

                    <div v-if="table.columns && table.columns.length > 0" class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700 border-b">Student</th>
                                    <th 
                                        v-for="(column, colIndex) in table.columns" 
                                        :key="column.id"
                                        :colspan="column.subcolumns.length || 1"
                                        class="px-4 py-2 text-center text-sm font-semibold text-gray-700 border-b border-l"
                                    >
                                        <div class="flex items-center justify-center gap-2 mb-2">
                                            <!-- Editable Column Name (disabled for read-only tables) -->
                                            <div v-if="editingColumnName === `finals-${tableKey}-${colIndex}` && !table.isReadOnly" class="flex items-center gap-1">
                                                <input 
                                                    v-model="tempColumnName"
                                                    @blur="finishEditColumnName('finals', tableKey, colIndex)"
                                                    @keyup.enter="finishEditColumnName('finals', tableKey, colIndex)"
                                                    @keyup.esc="editingColumnName = null"
                                                    class="px-2 py-1 border-2 border-green-500 rounded text-sm focus:outline-none"
                                                    autofocus
                                                />
                                            </div>
                                            <span 
                                                v-else
                                                @click="!table.isReadOnly && startEditColumnName('finals', tableKey, colIndex)"
                                                :class="[
                                                    table.isReadOnly ? 'cursor-default' : 'cursor-pointer hover:text-green-600 hover:underline'
                                                ]"
                                                :title="table.isReadOnly ? 'Read-only column' : 'Click to edit column name'"
                                            >
                                                {{ column.name }}
                                            </span>
                                            
                                            <!-- Editable Column Percentage (disabled for read-only tables) -->
                                            <div v-if="editingColumnPercentage === `finals-${tableKey}-${colIndex}` && !table.isReadOnly" class="flex items-center gap-1">
                                                <span>(</span>
                                                <input 
                                                    v-model.number="tempColumnPercentage"
                                                    @blur="finishEditColumnPercentage('finals', tableKey, colIndex)"
                                                    @keyup.enter="finishEditColumnPercentage('finals', tableKey, colIndex)"
                                                    @keyup.esc="editingColumnPercentage = null"
                                                    type="number"
                                                    min="0"
                                                    class="w-12 px-1 py-1 border-2 border-green-500 rounded text-sm text-center focus:outline-none"
                                                    autofocus
                                                />
                                                <span>%)</span>
                                            </div>
                                            <span 
                                                v-else-if="!table.isSummary"
                                                @click="!table.isReadOnly && startEditColumnPercentage('finals', tableKey, colIndex)"
                                                :class="[
                                                    table.isReadOnly ? 'cursor-default' : 'cursor-pointer hover:text-green-600 hover:underline'
                                                ]"
                                                :title="table.isReadOnly ? 'Read-only column' : 'Click to edit percentage'"
                                            >
                                                ({{ column.percentage }}%)
                                            </span>
                                            
                                            <button 
                                                v-if="!table.isReadOnly"
                                                @click="openAddSubcolumnModal('finals', tableKey, colIndex)"
                                                class="text-xs px-2 py-1 bg-green-500 text-white rounded hover:bg-green-600"
                                                title="Add Subcolumn"
                                            >
                                                + Sub
                                            </button>
                                            <button 
                                                v-if="!table.isReadOnly"
                                                @click="deleteColumn('finals', tableKey, colIndex)"
                                                class="text-red-600 hover:text-red-800"
                                            >
                                                🗑️
                                            </button>
                                        </div>
                                    </th>
                                    <th class="px-4 py-2 text-center text-sm font-semibold text-gray-700 border-b border-l">Total</th>
                                </tr>
                                <tr v-if="table.columns.some(col => col.subcolumns && col.subcolumns.length > 0)">
                                    <th class="px-4 py-2 border-b"></th>
                                    <template v-for="(column, colIndex) in table.columns" :key="column.id + '-sub'">
                                        <th 
                                            v-for="(subcolumn, subIndex) in column.subcolumns" 
                                            :key="subcolumn.id"
                                            class="px-2 py-2 text-center text-xs text-gray-600 border-b border-l"
                                        >
                                            <div class="flex flex-col items-center gap-1">
                                                <!-- Editable Subcolumn Name -->
                                                <div v-if="editingSubcolumnName === `finals-${tableKey}-${colIndex}-${subIndex}`">
                                                    <input 
                                                        v-model="tempSubcolumnName"
                                                        @blur="finishEditSubcolumnName('finals', tableKey, colIndex, subIndex)"
                                                        @keyup.enter="finishEditSubcolumnName('finals', tableKey, colIndex, subIndex)"
                                                        @keyup.esc="editingSubcolumnName = null"
                                                        class="px-2 py-1 border-2 border-green-500 rounded text-xs focus:outline-none w-24"
                                                        autofocus
                                                    />
                                                </div>
                                                <div 
                                                    v-else
                                                    @click="startEditSubcolumnName('finals', tableKey, colIndex, subIndex)"
                                                    class="cursor-pointer hover:text-green-600 hover:underline"
                                                    :class="{ 'cursor-not-allowed opacity-50': subcolumn.isAutoPopulated }"
                                                    :title="subcolumn.isAutoPopulated ? 'Auto-populated from classwork' : 'Click to edit name'"
                                                >
                                                    {{ subcolumn.name }}
                                                </div>
                                                
                                                <!-- Editable Max Points -->
                                                <div v-if="editingSubcolumnMaxPoints === `finals-${tableKey}-${colIndex}-${subIndex}`" class="flex items-center gap-1">
                                                    <span class="text-gray-500">(</span>
                                                    <input 
                                                        v-model.number="tempSubcolumnMaxPoints"
                                                        @blur="finishEditSubcolumnMaxPoints('finals', tableKey, colIndex, subIndex)"
                                                        @keyup.enter="finishEditSubcolumnMaxPoints('finals', tableKey, colIndex, subIndex)"
                                                        @keyup.esc="editingSubcolumnMaxPoints = null"
                                                        type="number"
                                                        min="1"
                                                        class="w-12 px-1 py-1 border-2 border-green-500 rounded text-xs text-center focus:outline-none"
                                                        autofocus
                                                    />
                                                    <span class="text-gray-500">pts)</span>
                                                </div>
                                                <div 
                                                    v-else
                                                    @click="startEditSubcolumnMaxPoints('finals', tableKey, colIndex, subIndex)"
                                                    class="text-gray-500 cursor-pointer hover:text-green-600"
                                                    :class="{ 'cursor-not-allowed opacity-50': subcolumn.isAutoPopulated }"
                                                    :title="subcolumn.isAutoPopulated ? 'Auto-populated from classwork' : 'Click to edit max points'"
                                                >
                                                    ({{ subcolumn.maxPoints }} pts)
                                                </div>
                                                
                                                <div class="flex gap-1" v-if="!subcolumn.isAutoPopulated">
                                                    <button 
                                                        @click="deleteSubcolumn('finals', tableKey, colIndex, subIndex)"
                                                        class="text-red-600 hover:text-red-800 text-xs"
                                                        title="Delete Subcolumn"
                                                    >
                                                        🗑️
                                                    </button>
                                                </div>
                                                <div v-else class="text-xs text-blue-600" title="Auto-populated from classwork">
                                                    📝 Auto
                                                </div>
                                            </div>
                                        </th>
                                        <th v-if="!column.subcolumns || column.subcolumns.length === 0" class="border-b border-l"></th>
                                    </template>
                                    <th class="px-4 py-2 border-b border-l"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="student in props.students" :key="student.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-2 border-b text-sm">{{ student.name }}</td>
                                    <template v-for="column in table.columns" :key="column.id">
                                        <td 
                                            v-for="subcolumn in column.subcolumns" 
                                            :key="subcolumn.id"
                                            class="px-2 py-2 border-b border-l"
                                        >
                                            <!-- Read-only display for Summary table -->
                                            <div 
                                                v-if="table.isSummary"
                                                class="w-full px-2 py-1 text-sm border border-purple-300 rounded text-center bg-purple-50 font-semibold text-purple-700"
                                            >
                                                {{ calculateStudentTableTotal(student.id, 'finals', tableKey) }}
                                            </div>
                                            <!-- Editable input for other tables -->
                                            <input 
                                                v-else
                                                v-model="studentGrades[student.id].finals[`${tableKey}-${column.id}-${subcolumn.id}`]"
                                                @blur="saveGradebook"
                                                type="number"
                                                :max="subcolumn.maxPoints"
                                                min="0"
                                                step="0.01"
                                                :disabled="subcolumn.isAutoPopulated"
                                                :class="subcolumn.isAutoPopulated 
                                                    ? 'w-full px-2 py-1 text-sm border border-gray-300 rounded text-center bg-gray-100 cursor-not-allowed' 
                                                    : 'w-full px-2 py-1 text-sm border border-gray-300 rounded text-center focus:ring-2 focus:ring-green-500'"
                                                :title="subcolumn.isAutoPopulated ? 'This grade is automatically updated from classwork submissions' : ''"
                                            />
                                        </td>
                                        <td v-if="!column.subcolumns || column.subcolumns.length === 0" class="px-4 py-2 border-b border-l text-center text-sm text-gray-500">
                                            N/A
                                        </td>
                                    </template>
                                    <td class="px-4 py-2 border-b border-l text-center font-semibold text-sm">
                                        {{ calculateStudentTableTotal(student.id, 'finals', tableKey) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="p-4 text-center text-gray-500 text-sm">
                        No columns added yet. Click "+ Column" to add grading columns.
                    </div>
                </div>

                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h5 class="font-semibold text-green-900 mb-3">Finals Period Grades</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div v-for="student in props.students" :key="student.id" class="bg-white p-3 rounded-lg border border-green-200">
                            <p class="text-sm text-gray-700">{{ student.name }}</p>
                            <p class="text-2xl font-bold text-green-600">{{ calculateStudentPeriodGrade(student.id, 'finals') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <div v-if="showAddTableModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-xl font-bold mb-4">Add New Table</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Table Name</label>
                        <input 
                            v-model="tableForm.name"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            placeholder="e.g., Quizzes"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Percentage (%)</label>
                        <input 
                            v-model.number="tableForm.percentage"
                            type="number"
                            min="0"
                            :max="getRemainingPercentage(currentPeriod)"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        />
                        <p class="text-xs text-gray-500 mt-1">Remaining: {{ getRemainingPercentage(currentPeriod) }}%</p>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button 
                        @click="addTable"
                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        Add Table
                    </button>
                    <button 
                        @click="showAddTableModal = false"
                        class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <div v-if="showEditTableModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-xl font-bold mb-4">Edit Table</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Table Name</label>
                        <input 
                            v-model="tableForm.name"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Percentage (%)</label>
                        <input 
                            v-model.number="tableForm.percentage"
                            type="number"
                            min="0"
                            max="100"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button 
                        @click="updateTable"
                        class="flex-1 px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700"
                    >
                        Update
                    </button>
                    <button 
                        @click="showEditTableModal = false"
                        class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <div v-if="showAddColumnModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-xl font-bold mb-4">Add New Column</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Column Name</label>
                        <input 
                            v-model="columnForm.name"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            placeholder="e.g., Quiz 1"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Percentage (%)</label>
                        <input 
                            v-model.number="columnForm.percentage"
                            type="number"
                            min="0"
                            :max="getRemainingColumnPercentage(currentPeriod, editingTableKey)"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        />
                        <p class="text-xs text-gray-500 mt-1">Remaining: {{ getRemainingColumnPercentage(currentPeriod, editingTableKey) }}%</p>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button 
                        @click="addColumn"
                        class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                    >
                        Add Column
                    </button>
                    <button 
                        @click="showAddColumnModal = false"
                        class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <div v-if="showEditColumnModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-xl font-bold mb-4">Edit Column</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Column Name</label>
                        <input 
                            v-model="columnForm.name"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Percentage (%)</label>
                        <input 
                            v-model.number="columnForm.percentage"
                            type="number"
                            min="0"
                            max="100"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button 
                        @click="updateColumn"
                        class="flex-1 px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700"
                    >
                        Update
                    </button>
                    <button 
                        @click="showEditColumnModal = false"
                        class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <!-- Add Subcolumn Modal -->
        <div v-if="showAddSubcolumnModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-xl font-bold mb-4">Add New Subcolumn</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subcolumn Name</label>
                        <input 
                            v-model="subcolumnForm.name"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            placeholder="e.g., Quiz 1, Assignment 1"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Max Points</label>
                        <input 
                            v-model.number="subcolumnForm.maxPoints"
                            type="number"
                            min="1"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        />
                        <p class="text-xs text-gray-500 mt-1">Maximum points students can earn</p>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button 
                        @click="addSubcolumn"
                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        Add Subcolumn
                    </button>
                    <button 
                        @click="showAddSubcolumnModal = false"
                        class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <!-- Edit Subcolumn Modal -->
        <div v-if="showEditSubcolumnModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-xl font-bold mb-4">Edit Subcolumn</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subcolumn Name</label>
                        <input 
                            v-model="subcolumnForm.name"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Max Points</label>
                        <input 
                            v-model.number="subcolumnForm.maxPoints"
                            type="number"
                            min="1"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        />
                        <p class="text-xs text-gray-500 mt-1">Maximum points students can earn</p>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button 
                        @click="updateSubcolumn"
                        class="flex-1 px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700"
                    >
                        Update
                    </button>
                    <button 
                        @click="showEditSubcolumnModal = false"
                        class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <div v-if="isSaving" class="fixed bottom-4 right-4 bg-blue-600 text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-2">
            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Saving...
        </div>
    </div>
</template>

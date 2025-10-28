<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Course;
use App\Models\Classwork;
use App\Models\ClassworkSubmission;
use App\Models\RubricCriteria;
use App\Models\QuizQuestion;
use App\Models\JoinedCourse;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;

class ClassworkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();
        
        try {
            // Get or create teacher
            $teacher = User::where('role', 'teacher')->first();
            if (!$teacher) {
                $teacher = User::create([
                    'name' => 'John Doe',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'teacher@elevategs.com',
                    'password' => bcrypt('password'),
                    'role' => 'teacher',
                    'status' => 'active',
                ]);
            }

            // Get or create students
            $students = [];
            $studentNames = [
                ['Juan', 'Dela Cruz'],
                ['Maria', 'Santos'],
                ['Pedro', 'Reyes'],
                ['Ana', 'Garcia'],
                ['Carlos', 'Martinez'],
            ];

            foreach ($studentNames as $index => $name) {
                $student = User::where('email', strtolower($name[0]) . '@student.com')->first();
                if (!$student) {
                    $student = User::create([
                        'name' => $name[0] . ' ' . $name[1],
                        'first_name' => $name[0],
                        'last_name' => $name[1],
                        'email' => strtolower($name[0]) . '@student.com',
                        'password' => bcrypt('password'),
                        'role' => 'student',
                        'status' => 'active',
                    ]);
                }
                $students[] = $student;
            }

            // Get or create academic year
            $academicYear = AcademicYear::where('status', 'Active')->first();
            if (!$academicYear) {
                $academicYear = AcademicYear::create([
                    'year_name' => '2024-2025',
                    'status' => 'Active',
                    'version' => 'v1.0',
                    'notes' => 'Sample academic year for testing',
                ]);
            }

            // Get or create course
            $course = Course::where('title', 'Introduction to Programming')->first();
            if (!$course) {
                $course = Course::create([
                    'title' => 'Introduction to Programming',
                    'section' => 'CS101-A',
                    'description' => 'Learn the fundamentals of programming',
                    'teacher_id' => $teacher->id,
                    'academic_year_id' => $academicYear->id,
                    'join_code' => 'CS101A',
                    'status' => 'Active',
                ]);
            }

            // Enroll students in course
            foreach ($students as $student) {
                JoinedCourse::firstOrCreate([
                    'user_id' => $student->id,
                    'course_id' => $course->id,
                ], [
                    'role' => 'Student',
                ]);
            }

            // Create sample classwork
            
            // 1. Lesson
            $lesson = Classwork::create([
                'course_id' => $course->id,
                'type' => 'lesson',
                'title' => 'Introduction to Variables',
                'description' => 'Learn about variables, data types, and how to declare them in programming.',
                'due_date' => null,
                'points' => 100,
                'attachments' => ['variables_guide.pdf', 'examples.txt'],
                'has_submission' => false,
                'status' => 'active',
                'color_code' => '#3b82f6',
                'created_by' => $teacher->id,
            ]);

            // 2. Assignment with rubric
            $assignment = Classwork::create([
                'course_id' => $course->id,
                'type' => 'assignment',
                'title' => 'Create a Calculator Program',
                'description' => 'Build a simple calculator that can perform basic arithmetic operations (add, subtract, multiply, divide).',
                'due_date' => now()->addDays(7),
                'points' => 50,
                'attachments' => ['requirements.docx'],
                'has_submission' => true,
                'status' => 'active',
                'color_code' => '#eab308',
                'created_by' => $teacher->id,
            ]);

            // Add rubric criteria for assignment
            RubricCriteria::create([
                'classwork_id' => $assignment->id,
                'description' => 'Code functionality and correctness',
                'points' => 20,
                'order' => 0,
            ]);
            RubricCriteria::create([
                'classwork_id' => $assignment->id,
                'description' => 'Code organization and readability',
                'points' => 15,
                'order' => 1,
            ]);
            RubricCriteria::create([
                'classwork_id' => $assignment->id,
                'description' => 'Error handling',
                'points' => 10,
                'order' => 2,
            ]);
            RubricCriteria::create([
                'classwork_id' => $assignment->id,
                'description' => 'Documentation and comments',
                'points' => 5,
                'order' => 3,
            ]);

            // 3. Quiz
            $quiz = Classwork::create([
                'course_id' => $course->id,
                'type' => 'quiz',
                'title' => 'Variables and Data Types Quiz',
                'description' => 'Test your understanding of variables and data types.',
                'due_date' => now()->addDays(3),
                'points' => 30,
                'attachments' => [],
                'has_submission' => true,
                'status' => 'active',
                'color_code' => '#ef4444',
                'created_by' => $teacher->id,
            ]);

            // Add quiz questions
            QuizQuestion::create([
                'classwork_id' => $quiz->id,
                'type' => 'multiple_choice',
                'question' => 'Which of the following is NOT a primitive data type?',
                'options' => ['int', 'string', 'boolean', 'array'],
                'correct_answer' => 'array',
                'points' => 10,
                'order' => 0,
            ]);
            QuizQuestion::create([
                'classwork_id' => $quiz->id,
                'type' => 'identification',
                'question' => 'What keyword is used to declare a constant variable in JavaScript?',
                'correct_answer' => 'const',
                'points' => 10,
                'order' => 1,
            ]);
            QuizQuestion::create([
                'classwork_id' => $quiz->id,
                'type' => 'enumeration',
                'question' => 'List three primitive data types in programming (comma-separated)',
                'correct_answers' => ['int', 'string', 'boolean'],
                'points' => 10,
                'order' => 2,
            ]);

            // 4. Activity with rubric
            $activity = Classwork::create([
                'course_id' => $course->id,
                'type' => 'activity',
                'title' => 'Debug the Code Challenge',
                'description' => 'Find and fix all errors in the provided code snippets.',
                'due_date' => now()->addDays(5),
                'points' => 40,
                'attachments' => ['buggy_code.txt'],
                'has_submission' => true,
                'status' => 'active',
                'color_code' => '#10b981',
                'created_by' => $teacher->id,
            ]);

            // Add rubric criteria for activity
            RubricCriteria::create([
                'classwork_id' => $activity->id,
                'description' => 'All errors identified',
                'points' => 20,
                'order' => 0,
            ]);
            RubricCriteria::create([
                'classwork_id' => $activity->id,
                'description' => 'Correct fixes applied',
                'points' => 15,
                'order' => 1,
            ]);
            RubricCriteria::create([
                'classwork_id' => $activity->id,
                'description' => 'Explanation of errors',
                'points' => 5,
                'order' => 2,
            ]);

            // Create submissions with varying grades for students
            $classworkWithSubmissions = [$assignment, $quiz, $activity];

            foreach ($students as $index => $student) {
                // Simulate different student performance levels
                foreach ($classworkWithSubmissions as $classIndex => $classwork) {
                    // All students submit assignment and quiz
                    // Only first 3 students submit activity
                    if ($classIndex == 2 && $index >= 3) continue;
                    
                    // Reload classwork to get rubricCriteria
                    $classwork->load('rubricCriteria');
                    
                    $maxPoints = $classwork->points;
                    
                    // Vary performance based on student
                    $performanceMultiplier = match($index) {
                        0 => 0.95, // Excellent student (95%)
                        1 => 0.85, // Good student (85%)
                        2 => 0.75, // Average student (75%)
                        3 => 0.65, // Below average (65%)
                        4 => 0.55, // Struggling student (55%)
                    };
                    
                    $grade = round($maxPoints * $performanceMultiplier, 2);
                    
                    // Build rubric scores if applicable
                    $rubricScores = null;
                    if ($classwork->type !== 'quiz' && $classwork->rubricCriteria->count() > 0) {
                        $rubricScores = [];
                        foreach ($classwork->rubricCriteria as $criteria) {
                            $rubricScores[$criteria->id] = round($criteria->points * $performanceMultiplier, 2);
                        }
                    }
                    
                    // Some students have submitted but not yet graded (for testing grading functionality)
                    $isGraded = !($index >= 3 && $classIndex == 1); // Last 2 students' quiz submissions are ungraded
                    
                    $submission = ClassworkSubmission::create([
                        'classwork_id' => $classwork->id,
                        'student_id' => $student->id,
                        'submission_content' => "This is {$student->first_name}'s submission for {$classwork->title}. " . 
                                               "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Completed the requirements as specified.\n\n" .
                                               "Detailed work:\n" .
                                               "1. Analyzed the problem thoroughly\n" .
                                               "2. Implemented the solution step by step\n" .
                                               "3. Tested all edge cases\n" .
                                               "4. Added proper documentation",
                        'attachments' => [
                            'sample-submission.txt', 
                            'submission_' . $student->first_name . '_essay.pdf',
                            'code_' . $student->first_name . '.txt'
                        ],
                        'rubric_scores' => $isGraded ? $rubricScores : null,
                        'grade' => $isGraded ? $grade : null,
                        'feedback' => $isGraded ? match(true) {
                            $grade >= $maxPoints * 0.9 => 'Excellent work! Keep it up. Your attention to detail is impressive.',
                            $grade >= $maxPoints * 0.8 => 'Good job! Minor improvements needed in code organization.',
                            $grade >= $maxPoints * 0.7 => 'Satisfactory work. Review the concepts on error handling.',
                            default => 'Needs improvement. Please review the material and see me for help during office hours.',
                        } : null,
                        'status' => $isGraded ? 'graded' : 'submitted',
                        'submitted_at' => now()->subDays(rand(1, 5)),
                        'graded_at' => $isGraded ? now()->subDays(rand(0, 2)) : null,
                        'graded_by' => $isGraded ? $teacher->id : null,
                    ]);
                }
            }

            DB::commit();
            
            $this->command->info('Sample classwork and submissions created successfully!');
            $this->command->info("Teacher: {$teacher->email} (password: password)");
            $this->command->info("Course: {$course->title} - {$course->section}");
            $this->command->info("Students: " . count($students) . " enrolled");
            $this->command->info("Classwork: 1 Lesson, 1 Assignment, 1 Quiz, 1 Activity");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error creating sample data: ' . $e->getMessage());
        }
    }
}

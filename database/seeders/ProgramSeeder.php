<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Program;
use App\Models\CourseTemplate;

class ProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data to prevent duplicates and avoid foreign key constraint errors
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('course_templates')->truncate();
            DB::table('programs')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } catch (\Exception $e) {
            // If truncate fails, try delete
            DB::table('course_templates')->delete();
            DB::table('programs')->delete();
        }

        $programs = [
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN ADMINISTRATION AND SUPERVISION',
                'description' => 'Academic Track',
                'courses' => [
                    // Basic Courses
                    ['code' => 'EDUCN 204', 'name' => 'Statistics in Education', 'units' => 3, 'type' => 'Basic Course'],
                    ['code' => 'EDUCN 210', 'name' => 'Methods in Educational Research', 'units' => 3, 'type' => 'Basic Course'],
                    ['code' => 'EDUCN 212', 'name' => 'Foundations of Education', 'units' => 3, 'type' => 'Basic Course'],
                    // Major Courses
                    ['code' => 'EDUAS 201', 'name' => 'Educational Leadership and Management', 'units' => 3, 'type' => 'Major Course'],
                    ['code' => 'EDUAS 202', 'name' => 'Educational Planning and Development', 'units' => 3, 'type' => 'Major Course'],
                    ['code' => 'EDUAS 203', 'name' => 'Dynamics, Organization, Theory, Research and Practice in Educational Administration', 'units' => 3, 'type' => 'Major Course'],
                    ['code' => 'EDUAS 204', 'name' => 'Media and Technology Education with AI Integration', 'units' => 3, 'type' => 'Major Course'],
                    ['code' => 'EDUAS 205', 'name' => 'Instructional Supervision and Curriculum Development', 'units' => 3, 'type' => 'Major Course'],
                    ['code' => 'EDUAS 206', 'name' => 'School Personnel Administration and its Legal Aspects', 'units' => 3, 'type' => 'Major Course'],
                    ['code' => 'EDUAS 207', 'name' => 'Current Issues and Problems in Philippine Education', 'units' => 3, 'type' => 'Major Course'],
                    // Thesis Courses
                    ['code' => 'EDUC 229', 'name' => 'Thesis Seminar', 'units' => 3, 'type' => 'Thesis'],
                    ['code' => 'EDUC 300', 'name' => 'Thesis Writing', 'units' => 3, 'type' => 'Thesis'],
                ]
            ],
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN MATHEMATICS',
                'description' => 'Academic Track',
                'courses' => [
                    // Basic Courses
                    ['code' => 'EDUCN 204', 'name' => 'Statistics in Education', 'units' => 3],
                    ['code' => 'EDUCN 210', 'name' => 'Methods in Educational Research', 'units' => 3],
                    ['code' => 'EDUCN 212', 'name' => 'Foundations of Education', 'units' => 3],
                    // Major Courses
                    ['code' => 'EDUMT 201', 'name' => 'Advanced Algebra', 'units' => 3],
                    ['code' => 'EDUMT 202', 'name' => 'Advanced Geometry', 'units' => 3],
                    ['code' => 'EDUMT 203', 'name' => 'Advanced Calculus', 'units' => 3],
                    ['code' => 'EDUMT 204', 'name' => 'Modern Mathematics', 'units' => 3],
                    ['code' => 'EDUMT 205', 'name' => 'Seminar in Mathematics Education', 'units' => 3],
                    ['code' => 'EDUMT 206', 'name' => 'Probability and Statistics', 'units' => 3],
                    ['code' => 'EDUMT 207', 'name' => 'Research Problems in Mathematics Education', 'units' => 3],
                    // Thesis Courses
                    ['code' => 'EDUC 229', 'name' => 'Thesis Seminar', 'units' => 3],
                    ['code' => 'EDUC 300', 'name' => 'Thesis Writing', 'units' => 3],
                ]
            ],
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN SCIENCE',
                'description' => 'Academic Track',
                'courses' => [
                    // Basic Courses
                    ['code' => 'EDUCN 204', 'name' => 'Statistics in Education', 'units' => 3],
                    ['code' => 'EDUCN 210', 'name' => 'Methods in Educational Research', 'units' => 3],
                    ['code' => 'EDUCN 212', 'name' => 'Foundations of Education', 'units' => 3],
                    // Major Courses
                    ['code' => 'EDUSC 201', 'name' => 'Research in Science Education', 'units' => 3],
                    ['code' => 'EDUSC 202', 'name' => 'Advanced General Science', 'units' => 3],
                    ['code' => 'EDUSC 203', 'name' => 'Modern Physics', 'units' => 3],
                    ['code' => 'EDUSC 204', 'name' => 'Chemistry of the Environment', 'units' => 3],
                    ['code' => 'EDUSC 205', 'name' => 'Biology and Ecology', 'units' => 3],
                    ['code' => 'EDUSC 206', 'name' => 'Science Curriculum and Instruction', 'units' => 3],
                    ['code' => 'EDUSC 207', 'name' => 'Seminar in Environmental Science', 'units' => 3],
                    // Thesis Courses
                    ['code' => 'EDUC 229', 'name' => 'Thesis Seminar', 'units' => 3],
                    ['code' => 'EDUC 300', 'name' => 'Thesis Writing', 'units' => 3],
                ]
            ],
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN FILIPINO',
                'description' => 'Academic Track',
                'courses' => [
                    // Basic Courses
                    ['code' => 'EDUCN 204', 'name' => 'Statistics in Education', 'units' => 3],
                    ['code' => 'EDUCN 210', 'name' => 'Methods in Educational Research', 'units' => 3],
                    ['code' => 'EDUCN 212', 'name' => 'Foundations of Education', 'units' => 3],
                    // Major Courses
                    ['code' => 'EDUFI 201', 'name' => 'Pagpaplanong Pampagtuturo ng Filipino', 'units' => 3],
                    ['code' => 'EDUFI 202', 'name' => 'Pagsasaling Pampanitikan', 'units' => 3],
                    ['code' => 'EDUFI 203', 'name' => 'Barayti at Baryasyon ng Filipino', 'units' => 3],
                    ['code' => 'EDUFI 204', 'name' => 'Pagtuturo ng Panitikan', 'units' => 3],
                    ['code' => 'EDUFI 205', 'name' => 'Seminar sa Pagsasalin at Panitikan', 'units' => 3],
                    ['code' => 'EDUFI 206', 'name' => 'Pamamaraan ng Pagtuturo ng Filipino', 'units' => 3],
                    ['code' => 'EDUFI 207', 'name' => 'Pananaliksik sa Filipino', 'units' => 3],
                    // Thesis Courses
                    ['code' => 'EDUC 229', 'name' => 'Thesis Seminar', 'units' => 3],
                    ['code' => 'EDUC 300', 'name' => 'Thesis Writing', 'units' => 3],
                ]
            ],
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN MAPEH',
                'description' => 'Academic Track',
                'courses' => [
                    // Basic Courses
                    ['code' => 'EDUCN 204', 'name' => 'Statistics in Education', 'units' => 3],
                    ['code' => 'EDUCN 210', 'name' => 'Methods in Educational Research', 'units' => 3],
                    ['code' => 'EDUCN 212', 'name' => 'Foundations of Education', 'units' => 3],
                    // Major Courses
                    ['code' => 'EDUMH 201', 'name' => 'Advanced Methods in Physical Education', 'units' => 3],
                    ['code' => 'EDUMH 202', 'name' => 'Trends and Issues in MAPEH', 'units' => 3],
                    ['code' => 'EDUMH 203', 'name' => 'Advanced Coaching and Officiating', 'units' => 3],
                    ['code' => 'EDUMH 204', 'name' => 'Sports Psychology and Management', 'units' => 3],
                    ['code' => 'EDUMH 205', 'name' => 'Creative Movement and Dance Education', 'units' => 3],
                    ['code' => 'EDUMH 206', 'name' => 'Health Education and Promotion', 'units' => 3],
                    ['code' => 'EDUMH 207', 'name' => 'Research in MAPEH', 'units' => 3],
                    // Thesis Courses
                    ['code' => 'EDUC 229', 'name' => 'Thesis Seminar', 'units' => 3],
                    ['code' => 'EDUC 300', 'name' => 'Thesis Writing', 'units' => 3],
                ]
            ],
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN TLE',
                'description' => 'Academic Track',
                'courses' => [
                    // Basic Courses
                    ['code' => 'EDUCN 204', 'name' => 'Statistics in Education', 'units' => 3],
                    ['code' => 'EDUCN 210', 'name' => 'Methods in Educational Research', 'units' => 3],
                    ['code' => 'EDUCN 212', 'name' => 'Foundations of Education', 'units' => 3],
                    // Major Courses
                    ['code' => 'EDUTL 201', 'name' => 'Research in TLE Education', 'units' => 3],
                    ['code' => 'EDUTL 202', 'name' => 'Advanced Methods in TLE', 'units' => 3],
                    ['code' => 'EDUTL 203', 'name' => 'Curriculum Development in TLE', 'units' => 3],
                    ['code' => 'EDUTL 204', 'name' => 'Entrepreneurship and Livelihood Education', 'units' => 3],
                    ['code' => 'EDUTL 205', 'name' => 'Instructional Materials and Technology in TLE', 'units' => 3],
                    ['code' => 'EDUTL 206', 'name' => 'Trends and Issues in TLE', 'units' => 3],
                    ['code' => 'EDUTL 207', 'name' => 'Seminar in TLE Supervision', 'units' => 3],
                    // Thesis Courses
                    ['code' => 'EDUC 229', 'name' => 'Thesis Seminar', 'units' => 3],
                    ['code' => 'EDUC 300', 'name' => 'Thesis Writing', 'units' => 3],
                ]
            ],
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN HISTORY',
                'description' => 'Academic Track',
                'courses' => [
                    // Basic Courses
                    ['code' => 'EDUCN 204', 'name' => 'Statistics in Education', 'units' => 3],
                    ['code' => 'EDUCN 210', 'name' => 'Methods in Educational Research', 'units' => 3],
                    ['code' => 'EDUCN 212', 'name' => 'Foundations of Education', 'units' => 3],
                    // Major Courses
                    ['code' => 'EDUHS 201', 'name' => 'Philippine Historiography', 'units' => 3],
                    ['code' => 'EDUHS 202', 'name' => 'Local and Oral History', 'units' => 3],
                    ['code' => 'EDUHS 203', 'name' => 'Asian Civilization', 'units' => 3],
                    ['code' => 'EDUHS 204', 'name' => 'Western Civilization', 'units' => 3],
                    ['code' => 'EDUHS 205', 'name' => 'History of Ideas', 'units' => 3],
                    ['code' => 'EDUHS 206', 'name' => 'Philippine Institutional History', 'units' => 3],
                    ['code' => 'EDUHS 207', 'name' => 'Seminar in Teaching History', 'units' => 3],
                    // Thesis Courses
                    ['code' => 'EDUC 229', 'name' => 'Thesis Seminar', 'units' => 3],
                    ['code' => 'EDUC 300', 'name' => 'Thesis Writing', 'units' => 3],
                ]
            ],
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN ENGLISH',
                'description' => 'Academic Track',
                'courses' => [
                    // Basic Courses
                    ['code' => 'EDUCN 204', 'name' => 'Statistics in Education', 'units' => 3],
                    ['code' => 'EDUCN 210', 'name' => 'Methods in Educational Research', 'units' => 3],
                    ['code' => 'EDUCN 212', 'name' => 'Foundations of Education', 'units' => 3],
                    // Major Courses
                    ['code' => 'EDUEN 201', 'name' => 'Advanced Grammar', 'units' => 3],
                    ['code' => 'EDUEN 202', 'name' => 'Theories of Language Learning and Teaching', 'units' => 3],
                    ['code' => 'EDUEN 203', 'name' => 'Literature for Language Development', 'units' => 3],
                    ['code' => 'EDUEN 204', 'name' => 'Language Assessment and Evaluation', 'units' => 3],
                    ['code' => 'EDUEN 205', 'name' => 'Research in English Language Education', 'units' => 3],
                    ['code' => 'EDUEN 206', 'name' => 'Teaching English with Technology', 'units' => 3],
                    ['code' => 'EDUEN 207', 'name' => 'Seminar in Literature and Culture', 'units' => 3],
                    // Thesis Courses
                    ['code' => 'EDUC 229', 'name' => 'Thesis Seminar', 'units' => 3],
                    ['code' => 'EDUC 300', 'name' => 'Thesis Writing', 'units' => 3],
                ]
            ],
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN PRESCHOOL EDUCATION',
                'description' => 'Academic Track',
                'courses' => [
                    // Basic Courses
                    ['code' => 'EDUCN 204', 'name' => 'Statistics in Education', 'units' => 3],
                    ['code' => 'EDUCN 210', 'name' => 'Methods in Educational Research', 'units' => 3],
                    ['code' => 'EDUCN 212', 'name' => 'Foundations of Education', 'units' => 3],
                    // Major Courses
                    ['code' => 'EDUPR 201', 'name' => 'Child Growth and Development', 'units' => 3],
                    ['code' => 'EDUPR 202', 'name' => 'Early Childhood Curriculum', 'units' => 3],
                    ['code' => 'EDUPR 203', 'name' => 'Play and Learning', 'units' => 3],
                    ['code' => 'EDUPR 204', 'name' => 'Assessment in Early Childhood', 'units' => 3],
                    ['code' => 'EDUPR 205', 'name' => 'Learning Environment and Classroom Management', 'units' => 3],
                    ['code' => 'EDUPR 206', 'name' => 'Family, School and Community Partnership', 'units' => 3],
                    ['code' => 'EDUPR 207', 'name' => 'Research in Early Childhood Education', 'units' => 3],
                    // Thesis Courses
                    ['code' => 'EDUC 229', 'name' => 'Thesis Seminar', 'units' => 3],
                    ['code' => 'EDUC 300', 'name' => 'Thesis Writing', 'units' => 3],
                ]
            ],
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN GUIDANCE AND COUNSELING',
                'description' => 'Academic Track',
                'courses' => [
                    // Basic Courses
                    ['code' => 'EDUCN 204', 'name' => 'Statistics in Education', 'units' => 3],
                    ['code' => 'EDUCN 210', 'name' => 'Methods in Educational Research', 'units' => 3],
                    ['code' => 'EDUCN 212', 'name' => 'Foundations of Education', 'units' => 3],
                    // Major Courses
                    ['code' => 'EDUGC 201', 'name' => 'Theories of Counseling', 'units' => 3],
                    ['code' => 'EDUGC 202', 'name' => 'Counseling Techniques and Strategies', 'units' => 3],
                    ['code' => 'EDUGC 203', 'name' => 'Psychological Testing and Assessment', 'units' => 3],
                    ['code' => 'EDUGC 204', 'name' => 'Group Dynamics and Counseling', 'units' => 3],
                    ['code' => 'EDUGC 205', 'name' => 'Career Guidance and Counseling', 'units' => 3],
                    ['code' => 'EDUGC 206', 'name' => 'Ethical and Legal Issues in Counseling', 'units' => 3],
                    ['code' => 'EDUGC 207', 'name' => 'Research in Guidance and Counseling', 'units' => 3],
                    // Thesis Courses
                    ['code' => 'EDUC 229', 'name' => 'Thesis Seminar', 'units' => 3],
                    ['code' => 'EDUC 300', 'name' => 'Thesis Writing', 'units' => 3],
                ]
            ],
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN ALTERNATIVE LEARNING SYSTEM',
                'description' => 'Academic Track',
                'courses' => [
                    // Basic Courses
                    ['code' => 'EDUCN 204', 'name' => 'Statistics in Education', 'units' => 3],
                    ['code' => 'EDUCN 210', 'name' => 'Methods in Educational Research', 'units' => 3],
                    ['code' => 'EDUCN 212', 'name' => 'Foundations of Education', 'units' => 3],
                    // Major Courses
                    ['code' => 'EDUAL 201', 'name' => 'Philosophy and Framework of ALS', 'units' => 3],
                    ['code' => 'EDUAL 202', 'name' => 'Program Planning and Management in ALS', 'units' => 3],
                    ['code' => 'EDUAL 203', 'name' => 'Curriculum Development for ALS', 'units' => 3],
                    ['code' => 'EDUAL 204', 'name' => 'Instructional Materials and Technology in ALS', 'units' => 3],
                    ['code' => 'EDUAL 205', 'name' => 'Assessment and Evaluation in ALS', 'units' => 3],
                    ['code' => 'EDUAL 206', 'name' => 'Trends and Issues in Nonformal Education', 'units' => 3],
                    ['code' => 'EDUAL 207', 'name' => 'Research in ALS', 'units' => 3],
                    // Thesis Courses
                    ['code' => 'EDUC 229', 'name' => 'Thesis Seminar', 'units' => 3],
                    ['code' => 'EDUC 300', 'name' => 'Thesis Writing', 'units' => 3],
                ]
            ],
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN SPECIAL NEEDS EDUCATION',
                'description' => 'Academic Track',
                'courses' => [
                    // Basic Courses
                    ['code' => 'EDUCN 204', 'name' => 'Statistics in Education', 'units' => 3],
                    ['code' => 'EDUCN 210', 'name' => 'Methods in Educational Research', 'units' => 3],
                    ['code' => 'EDUCN 212', 'name' => 'Foundations of Education', 'units' => 3],
                    // Major Courses
                    ['code' => 'EDUSP 201', 'name' => 'Nature and Needs of Learners with Disabilities', 'units' => 3],
                    ['code' => 'EDUSP 202', 'name' => 'Assessment of Children with Special Needs', 'units' => 3],
                    ['code' => 'EDUSP 203', 'name' => 'Curriculum Adaptation and Modification', 'units' => 3],
                    ['code' => 'EDUSP 204', 'name' => 'Behavior Management', 'units' => 3],
                    ['code' => 'EDUSP 205', 'name' => 'Collaboration and Inclusion Practices', 'units' => 3],
                    ['code' => 'EDUSP 206', 'name' => 'Trends and Issues in Special Education', 'units' => 3],
                    ['code' => 'EDUSP 207', 'name' => 'Research in Special Education', 'units' => 3],
                    // Thesis Courses
                    ['code' => 'EDUC 229', 'name' => 'Thesis Seminar', 'units' => 3],
                    ['code' => 'EDUC 300', 'name' => 'Thesis Writing', 'units' => 3],
                ]
            ],
        ];

        foreach ($programs as $prog) {
            $program = Program::create([
                'name' => $prog['name'],
                'description' => $prog['description'],
                'status' => 'Active',
            ]);
            foreach ($prog['courses'] as $course) {
                // Determine course type based on course code if not specified
                $courseType = $course['type'] ?? 'Major Course';
                if (!isset($course['type'])) {
                    if (in_array($course['code'], ['EDUCN 204', 'EDUCN 210', 'EDUCN 212'])) {
                        $courseType = 'Basic Course';
                    } elseif (in_array($course['code'], ['EDUC 229', 'EDUC 300'])) {
                        $courseType = 'Thesis';
                    }
                }
                
                CourseTemplate::create([
                    'program_id' => $program->id,
                    'course_code' => $course['code'],
                    'course_name' => $course['name'],
                    'course_type' => $courseType,
                    'units' => $course['units'],
                    'status' => 'Active',
                ]);
            }
        }
    }
}

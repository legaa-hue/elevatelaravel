<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Program;
use App\Models\CourseTemplate;

class ProgramsAndCourseTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $programs = [
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN ADMINISTRATION AND SUPERVISION',
                'description' => 'Academic Track',
                'basic' => [
                    ['EDUCN 204', 'Statistics in Education', 3],
                    ['EDUCN 210', 'Methods in Educational Research', 3],
                    ['EDUCN 212', 'Foundations of Education', 3],
                ],
                'major' => [
                    ['EDUAS 201', 'Educational Leadership and Management', 3],
                    ['EDUAS 202', 'Educational Planning and Development', 3],
                    ['EDUAS 203', 'Dynamics, Organization, Theory, Research and Practice in Educational Administration', 3],
                    ['EDUAS 204', 'Media and Technology Education with AI Integration', 3],
                    ['EDUAS 205', 'Instructional Supervision and Curriculum Development', 3],
                    ['EDUAS 206', 'School Personnel Administration and its Legal Aspects', 3],
                    ['EDUAS 207', 'Current Issues and Problems in Philippine Education', 3],
                ],
                'thesis' => [
                    ['EDUC 229', 'Thesis Seminar', 3],
                    ['EDUC 300', 'Thesis Writing', 3],
                ],
            ],
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN MATHEMATICS',
                'description' => 'Academic Track',
                'basic' => [
                    ['EDUCN 204', 'Statistics in Education', 3],
                    ['EDUCN 210', 'Methods in Educational Research', 3],
                    ['EDUCN 212', 'Foundations of Education', 3],
                ],
                'major' => [
                    ['EDUMT 201', 'Advanced Algebra', 3],
                    ['EDUMT 202', 'Advanced Geometry', 3],
                    ['EDUMT 203', 'Advanced Calculus', 3],
                    ['EDUMT 204', 'Modern Mathematics', 3],
                    ['EDUMT 205', 'Seminar in Mathematics Education', 3],
                    ['EDUMT 206', 'Probability and Statistics', 3],
                    ['EDUMT 207', 'Research Problems in Mathematics Education', 3],
                ],
                'thesis' => [
                    ['EDUC 229', 'Thesis Seminar', 3],
                    ['EDUC 300', 'Thesis Writing', 3],
                ],
            ],
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN SCIENCE',
                'description' => 'Academic Track',
                'basic' => [
                    ['EDUCN 204', 'Statistics in Education', 3],
                    ['EDUCN 210', 'Methods in Educational Research', 3],
                    ['EDUCN 212', 'Foundations of Education', 3],
                ],
                'major' => [
                    ['EDUSC 201', 'Research in Science Education', 3],
                    ['EDUSC 202', 'Advanced General Science', 3],
                    ['EDUSC 203', 'Modern Physics', 3],
                    ['EDUSC 204', 'Chemistry of the Environment', 3],
                    ['EDUSC 205', 'Biology and Ecology', 3],
                    ['EDUSC 206', 'Science Curriculum and Instruction', 3],
                    ['EDUSC 207', 'Seminar in Environmental Science', 3],
                ],
                'thesis' => [
                    ['EDUC 229', 'Thesis Seminar', 3],
                    ['EDUC 300', 'Thesis Writing', 3],
                ],
            ],
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN FILIPINO',
                'description' => 'Academic Track',
                'basic' => [
                    ['EDUCN 204', 'Statistics in Education', 3],
                    ['EDUCN 210', 'Methods in Educational Research', 3],
                    ['EDUCN 212', 'Foundations of Education', 3],
                ],
                'major' => [
                    ['EDUFI 201', 'Pagpaplanong Pampagtuturo ng Filipino', 3],
                    ['EDUFI 202', 'Pagsasaling Pampanitikan', 3],
                    ['EDUFI 203', 'Barayti at Baryasyon ng Filipino', 3],
                    ['EDUFI 204', 'Pagtuturo ng Panitikan', 3],
                    ['EDUFI 205', 'Seminar sa Pagsasalin at Panitikan', 3],
                    ['EDUFI 206', 'Pamamaraan ng Pagtuturo ng Filipino', 3],
                    ['EDUFI 207', 'Pananaliksik sa Filipino', 3],
                ],
                'thesis' => [
                    ['EDUC 229', 'Thesis Seminar', 3],
                    ['EDUC 300', 'Thesis Writing', 3],
                ],
            ],
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN MAPEH',
                'description' => 'Academic Track',
                'basic' => [
                    ['EDUCN 204', 'Statistics in Education', 3],
                    ['EDUCN 210', 'Methods in Educational Research', 3],
                    ['EDUCN 212', 'Foundations of Education', 3],
                ],
                'major' => [
                    ['EDUMH 201', 'Advanced Methods in Physical Education', 3],
                    ['EDUMH 202', 'Trends and Issues in MAPEH', 3],
                    ['EDUMH 203', 'Advanced Coaching and Officiating', 3],
                    ['EDUMH 204', 'Sports Psychology and Management', 3],
                    ['EDUMH 205', 'Creative Movement and Dance Education', 3],
                    ['EDUMH 206', 'Health Education and Promotion', 3],
                    ['EDUMH 207', 'Research in MAPEH', 3],
                ],
                'thesis' => [
                    ['EDUC 229', 'Thesis Seminar', 3],
                    ['EDUC 300', 'Thesis Writing', 3],
                ],
            ],
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN TLE',
                'description' => 'Academic Track',
                'basic' => [
                    ['EDUCN 204', 'Statistics in Education', 3],
                    ['EDUCN 210', 'Methods in Educational Research', 3],
                    ['EDUCN 212', 'Foundations of Education', 3],
                ],
                'major' => [
                    ['EDUTL 201', 'Research in TLE Education', 3],
                    ['EDUTL 202', 'Advanced Methods in TLE', 3],
                    ['EDUTL 203', 'Curriculum Development in TLE', 3],
                    ['EDUTL 204', 'Entrepreneurship and Livelihood Education', 3],
                    ['EDUTL 205', 'Instructional Materials and Technology in TLE', 3],
                    ['EDUTL 206', 'Trends and Issues in TLE', 3],
                    ['EDUTL 207', 'Seminar in TLE Supervision', 3],
                ],
                'thesis' => [
                    ['EDUC 229', 'Thesis Seminar', 3],
                    ['EDUC 300', 'Thesis Writing', 3],
                ],
            ],
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN HISTORY',
                'description' => 'Academic Track',
                'basic' => [
                    ['EDUCN 204', 'Statistics in Education', 3],
                    ['EDUCN 210', 'Methods in Educational Research', 3],
                    ['EDUCN 212', 'Foundations of Education', 3],
                ],
                'major' => [
                    ['EDUHS 201', 'Philippine Historiography', 3],
                    ['EDUHS 202', 'Local and Oral History', 3],
                    ['EDUHS 203', 'Asian Civilization', 3],
                    ['EDUHS 204', 'Western Civilization', 3],
                    ['EDUHS 205', 'History of Ideas', 3],
                    ['EDUHS 206', 'Philippine Institutional History', 3],
                    ['EDUHS 207', 'Seminar in Teaching History', 3],
                ],
                'thesis' => [
                    ['EDUC 229', 'Thesis Seminar', 3],
                    ['EDUC 300', 'Thesis Writing', 3],
                ],
            ],
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN ENGLISH',
                'description' => 'Academic Track',
                'basic' => [
                    ['EDUCN 204', 'Statistics in Education', 3],
                    ['EDUCN 210', 'Methods in Educational Research', 3],
                    ['EDUCN 212', 'Foundations of Education', 3],
                ],
                'major' => [
                    ['EDUEN 201', 'Advanced Grammar', 3],
                    ['EDUEN 202', 'Theories of Language Learning and Teaching', 3],
                    ['EDUEN 203', 'Literature for Language Development', 3],
                    ['EDUEN 204', 'Language Assessment and Evaluation', 3],
                    ['EDUEN 205', 'Research in English Language Education', 3],
                    ['EDUEN 206', 'Teaching English with Technology', 3],
                    ['EDUEN 207', 'Seminar in Literature and Culture', 3],
                ],
                'thesis' => [
                    ['EDUC 229', 'Thesis Seminar', 3],
                    ['EDUC 300', 'Thesis Writing', 3],
                ],
            ],
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN PRESCHOOL EDUCATION',
                'description' => 'Academic Track',
                'basic' => [
                    ['EDUCN 204', 'Statistics in Education', 3],
                    ['EDUCN 210', 'Methods in Educational Research', 3],
                    ['EDUCN 212', 'Foundations of Education', 3],
                ],
                'major' => [
                    ['EDUPR 201', 'Child Growth and Development', 3],
                    ['EDUPR 202', 'Early Childhood Curriculum', 3],
                    ['EDUPR 203', 'Play and Learning', 3],
                    ['EDUPR 204', 'Assessment in Early Childhood', 3],
                    ['EDUPR 205', 'Learning Environment and Classroom Management', 3],
                    ['EDUPR 206', 'Family, School and Community Partnership', 3],
                    ['EDUPR 207', 'Research in Early Childhood Education', 3],
                ],
                'thesis' => [
                    ['EDUC 229', 'Thesis Seminar', 3],
                    ['EDUC 300', 'Thesis Writing', 3],
                ],
            ],
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN GUIDANCE AND COUNSELING',
                'description' => 'Academic Track',
                'basic' => [
                    ['EDUCN 204', 'Statistics in Education', 3],
                    ['EDUCN 210', 'Methods in Educational Research', 3],
                    ['EDUCN 212', 'Foundations of Education', 3],
                ],
                'major' => [
                    ['EDUGC 201', 'Theories of Counseling', 3],
                    ['EDUGC 202', 'Counseling Techniques and Strategies', 3],
                    ['EDUGC 203', 'Psychological Testing and Assessment', 3],
                    ['EDUGC 204', 'Group Dynamics and Counseling', 3],
                    ['EDUGC 205', 'Career Guidance and Counseling', 3],
                    ['EDUGC 206', 'Ethical and Legal Issues in Counseling', 3],
                    ['EDUGC 207', 'Research in Guidance and Counseling', 3],
                ],
                'thesis' => [
                    ['EDUC 229', 'Thesis Seminar', 3],
                    ['EDUC 300', 'Thesis Writing', 3],
                ],
            ],
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN ALTERNATIVE LEARNING SYSTEM',
                'description' => 'Academic Track',
                'basic' => [
                    ['EDUCN 204', 'Statistics in Education', 3],
                    ['EDUCN 210', 'Methods in Educational Research', 3],
                    ['EDUCN 212', 'Foundations of Education', 3],
                ],
                'major' => [
                    ['EDUAL 201', 'Philosophy and Framework of ALS', 3],
                    ['EDUAL 202', 'Program Planning and Management in ALS', 3],
                    ['EDUAL 203', 'Curriculum Development for ALS', 3],
                    ['EDUAL 204', 'Instructional Materials and Technology in ALS', 3],
                    ['EDUAL 205', 'Assessment and Evaluation in ALS', 3],
                    ['EDUAL 206', 'Trends and Issues in Nonformal Education', 3],
                    ['EDUAL 207', 'Research in ALS', 3],
                ],
                'thesis' => [
                    ['EDUC 229', 'Thesis Seminar', 3],
                    ['EDUC 300', 'Thesis Writing', 3],
                ],
            ],
            [
                'name' => 'MASTER OF ARTS IN EDUCATION MAJOR IN SPECIAL NEEDS EDUCATION',
                'description' => 'Academic Track',
                'basic' => [
                    ['EDUCN 204', 'Statistics in Education', 3],
                    ['EDUCN 210', 'Methods in Educational Research', 3],
                    ['EDUCN 212', 'Foundations of Education', 3],
                ],
                'major' => [
                    ['EDUSP 201', 'Nature and Needs of Learners with Disabilities', 3],
                    ['EDUSP 202', 'Assessment of Children with Special Needs', 3],
                    ['EDUSP 203', 'Curriculum Adaptation and Modification', 3],
                    ['EDUSP 204', 'Behavior Management', 3],
                    ['EDUSP 205', 'Collaboration and Inclusion Practices', 3],
                    ['EDUSP 206', 'Trends and Issues in Special Education', 3],
                    ['EDUSP 207', 'Research in Special Education', 3],
                ],
                'thesis' => [
                    ['EDUC 229', 'Thesis Seminar', 3],
                    ['EDUC 300', 'Thesis Writing', 3],
                ],
            ],
        ];

        foreach ($programs as $programData) {
            // Create or update the program
            $program = Program::updateOrCreate(
                ['name' => $programData['name']],
                [
                    'description' => $programData['description'],
                    'status' => 'Active',
                ]
            );

            // Create Basic Courses
            foreach ($programData['basic'] as $course) {
                CourseTemplate::updateOrCreate(
                    [
                        'program_id' => $program->id,
                        'course_code' => $course[0],
                    ],
                    [
                        'course_name' => $course[1],
                        'course_type' => 'Basic Course',
                        'units' => $course[2],
                        'status' => 'Active',
                    ]
                );
            }

            // Create Major Courses
            foreach ($programData['major'] as $course) {
                CourseTemplate::updateOrCreate(
                    [
                        'program_id' => $program->id,
                        'course_code' => $course[0],
                    ],
                    [
                        'course_name' => $course[1],
                        'course_type' => 'Major Course',
                        'units' => $course[2],
                        'status' => 'Active',
                    ]
                );
            }

            // Create Thesis Courses
            foreach ($programData['thesis'] as $course) {
                CourseTemplate::updateOrCreate(
                    [
                        'program_id' => $program->id,
                        'course_code' => $course[0],
                    ],
                    [
                        'course_name' => $course[1],
                        'course_type' => 'Thesis',
                        'units' => $course[2],
                        'status' => 'Active',
                    ]
                );
            }

            $this->command->info("Program '{$program->name}' created/updated with courses.");
        }

        $this->command->info('All programs and course templates have been seeded successfully!');
    }
}

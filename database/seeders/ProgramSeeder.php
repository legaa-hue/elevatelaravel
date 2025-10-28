<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Program;
use App\Models\CourseTemplate;

class ProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create MASTER OF ARTS IN EDUCATION program
        $maed = Program::create([
            'name' => 'MASTER OF ARTS IN EDUCATION',
            'description' => 'Graduate program for aspiring educators and educational leaders',
            'status' => 'Active'
        ]);

        // Create course templates for MAED
        CourseTemplate::create([
            'program_id' => $maed->id,
            'course_code' => 'EDUC501',
            'course_name' => 'Educational Psychology',
            'units' => 3,
            'status' => 'Active'
        ]);

        CourseTemplate::create([
            'program_id' => $maed->id,
            'course_code' => 'EDUC502',
            'course_name' => 'Curriculum Development',
            'units' => 3,
            'status' => 'Active'
        ]);

        CourseTemplate::create([
            'program_id' => $maed->id,
            'course_code' => 'EDUC503',
            'course_name' => 'Research Methods in Education',
            'units' => 3,
            'status' => 'Active'
        ]);

        CourseTemplate::create([
            'program_id' => $maed->id,
            'course_code' => 'EDUC504',
            'course_name' => 'Educational Leadership',
            'units' => 3,
            'status' => 'Active'
        ]);

        // Create BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY program
        $bsit = Program::create([
            'name' => 'BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY',
            'description' => 'Undergraduate program focused on IT skills and knowledge',
            'status' => 'Active'
        ]);

        // Create course templates for BSIT
        CourseTemplate::create([
            'program_id' => $bsit->id,
            'course_code' => 'IT101',
            'course_name' => 'Introduction to Computing',
            'units' => 3,
            'status' => 'Active'
        ]);

        CourseTemplate::create([
            'program_id' => $bsit->id,
            'course_code' => 'IT102',
            'course_name' => 'Programming Fundamentals',
            'units' => 3,
            'status' => 'Active'
        ]);

        CourseTemplate::create([
            'program_id' => $bsit->id,
            'course_code' => 'IT201',
            'course_name' => 'Data Structures and Algorithms',
            'units' => 3,
            'status' => 'Active'
        ]);

        CourseTemplate::create([
            'program_id' => $bsit->id,
            'course_code' => 'IT202',
            'course_name' => 'Database Management Systems',
            'units' => 3,
            'status' => 'Active'
        ]);

        CourseTemplate::create([
            'program_id' => $bsit->id,
            'course_code' => 'IT301',
            'course_name' => 'Web Development',
            'units' => 3,
            'status' => 'Active'
        ]);

        CourseTemplate::create([
            'program_id' => $bsit->id,
            'course_code' => 'IT302',
            'course_name' => 'Software Engineering',
            'units' => 3,
            'status' => 'Active'
        ]);

        // Create BACHELOR OF SCIENCE IN COMPUTER SCIENCE program
        $bscs = Program::create([
            'name' => 'BACHELOR OF SCIENCE IN COMPUTER SCIENCE',
            'description' => 'Undergraduate program focused on computer science theory and applications',
            'status' => 'Active'
        ]);

        // Create course templates for BSCS
        CourseTemplate::create([
            'program_id' => $bscs->id,
            'course_code' => 'CS101',
            'course_name' => 'Introduction to Computer Science',
            'units' => 3,
            'status' => 'Active'
        ]);

        CourseTemplate::create([
            'program_id' => $bscs->id,
            'course_code' => 'CS102',
            'course_name' => 'Discrete Mathematics',
            'units' => 3,
            'status' => 'Active'
        ]);

        CourseTemplate::create([
            'program_id' => $bscs->id,
            'course_code' => 'CS201',
            'course_name' => 'Object-Oriented Programming',
            'units' => 3,
            'status' => 'Active'
        ]);

        CourseTemplate::create([
            'program_id' => $bscs->id,
            'course_code' => 'CS202',
            'course_name' => 'Computer Architecture',
            'units' => 3,
            'status' => 'Active'
        ]);

        CourseTemplate::create([
            'program_id' => $bscs->id,
            'course_code' => 'CS301',
            'course_name' => 'Operating Systems',
            'units' => 3,
            'status' => 'Active'
        ]);

        CourseTemplate::create([
            'program_id' => $bscs->id,
            'course_code' => 'CS302',
            'course_name' => 'Artificial Intelligence',
            'units' => 3,
            'status' => 'Active'
        ]);
    }
}

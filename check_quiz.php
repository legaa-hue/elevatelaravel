<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$quizzes = App\Models\Classwork::where('type', 'quiz')
    ->with('quizQuestions')
    ->get();

echo "Total quizzes: " . $quizzes->count() . "\n\n";

foreach ($quizzes as $quiz) {
    echo "======================\n";
    echo "Quiz ID: " . $quiz->id . "\n";
    echo "Quiz Title: " . $quiz->title . "\n";
    echo "Quiz Questions Count: " . $quiz->quizQuestions->count() . "\n\n";
    
    if ($quiz->quizQuestions->count() > 0) {
        foreach ($quiz->quizQuestions as $index => $question) {
            echo "Question " . ($index + 1) . ":\n";
            echo "  Type: " . $question->type . "\n";
            echo "  Question: " . substr($question->question, 0, 50) . "...\n";
            
            if ($question->type === 'enumeration') {
                echo "  Correct Answers (array): ";
                if ($question->correct_answers) {
                    echo json_encode($question->correct_answers);
                    echo " (Count: " . count($question->correct_answers) . ")";
                } else {
                    echo "NULL";
                }
                echo "\n";
                
                echo "  Correct Answer (string): ";
                if ($question->correct_answer) {
                    echo json_encode($question->correct_answer);
                } else {
                    echo "NULL";
                }
                echo "\n";
            }
            echo "\n";
        }
    } else {
        echo "  ⚠️ NO QUESTIONS FOUND!\n\n";
    }
}


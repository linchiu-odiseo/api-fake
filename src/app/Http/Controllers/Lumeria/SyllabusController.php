<?php

namespace App\Http\Controllers\Lumeria;

use App\Http\Controllers\Controller;
use App\Services\LumeriaDataGenerator;
use Illuminate\Http\JsonResponse;

class SyllabusController extends Controller
{
    public function __construct(private readonly LumeriaDataGenerator $lumeria)
    {
    }

    /** GET /v3/cycles/{cycle_id}/courses */
    public function courses(string $cycle_id): JsonResponse
    {
        $payload = $this->lumeria->coursesForCycle($cycle_id);

        if ($payload === null) {
            return response()->json(['error' => 'cycle_not_found'], 404);
        }

        return response()->json($payload);
    }

    /** GET /v3/cycles/{cycle_id}/courses/{course_id}/syllabus */
    public function syllabus(string $cycle_id, string $course_id): JsonResponse
    {
        $payload = $this->lumeria->syllabusForCourse($cycle_id, $course_id);

        if ($payload === null) {
            return response()->json(['error' => 'course_not_found'], 404);
        }

        return response()->json($payload);
    }
}

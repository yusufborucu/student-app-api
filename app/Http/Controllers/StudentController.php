<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\ClassroomLesson;
use App\Models\Lesson;
use App\Models\Student;
use Validator;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::select('id', 'name', 'surname', 'no', 'classroom_id')->get();
        foreach ($students as $student) {
            $student->classroom = Classroom::where('id', $student->classroom_id)->first()->name;
            $lesson_ids = ClassroomLesson::select('lesson_id')->where('classroom_id', $student->classroom_id)->get();
            $lessons = '';
            foreach ($lesson_ids as $lesson_id) {
                $lesson = Lesson::find($lesson_id->lesson_id);
                $lessons .= $lesson->name . ',';
            }
            $lessons = substr($lessons, 0, -1);
            $student->lessons = $lessons;
        }
        return response()->json($students, 200);
    }

    public function store()
    {
        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'surname' => 'required',
            'no' => 'required',
            'classroom_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Lütfen tüm alanları doldurunuz.'], 400);
        }

        DB::beginTransaction();
        try {
            $input = request()->all();
            Student::create($input);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Öğrenci eklenirken bir sorun oluştu.'], 500);
        }
        DB::commit();
        return response()->json(['message' => 'Öğrenci başarıyla eklendi.'], 200);
    }

    public function show($id)
    {
        $student = Student::select('id', 'name', 'surname', 'no', 'classroom_id')->where('id', $id)->first();
        if ($student != null) {
            $student->classroom = Classroom::where('id', $student->classroom_id)->first()->name;
            $student->classrooms = Classroom::select('id', 'name')->get();
            return response()->json($student, 200);
        } else {
            return response()->json(['message' => 'Böyle bir öğrenci mevcut değil.'], 500);
        }
    }

    public function update($id)
    {
        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'surname' => 'required',
            'no' => 'required',
            'classroom_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Lütfen tüm alanları doldurunuz.'], 400);
        }

        $student = Student::where('id', $id)->first();
        if ($student != null) {
            DB::beginTransaction();
            try {
                $student->fill(request()->all());
                $student->update();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['message' => 'Öğrenci düzenlenirken bir sorun oluştu.'], 500);
            }
            DB::commit();
            return response()->json(['message' => 'Öğrenci başarıyla düzenlendi.'], 200);
        } else {
            return response()->json(['message' => 'Böyle bir öğrenci mevcut değil.'], 500);
        }
    }

    public function destroy($id)
    {
        $student = Student::where('id', $id)->first();
        if ($student != null) {
            DB::beginTransaction();
            try {
                $student->delete();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['message' => 'Öğrenci silinirken bir sorun oluştu.'], 500);
            }
            DB::commit();
            return response()->json(['message' => 'Öğrenci başarıyla silindi.'], 200);
        } else {
            return response()->json(['message' => 'Böyle bir öğrenci mevcut değil.'], 500);
        }
    }
}

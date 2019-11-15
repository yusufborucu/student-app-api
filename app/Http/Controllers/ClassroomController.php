<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\ClassroomLesson;
use App\Models\Lesson;
use App\Models\Student;
use Validator;
use Illuminate\Support\Facades\DB;

class ClassroomController extends Controller
{
    public function index()
    {
        $classrooms = Classroom::select('id', 'name')->get();
        foreach ($classrooms as $classroom) {
            $lesson_ids = ClassroomLesson::select('lesson_id')->where('classroom_id', $classroom->id)->get();
            $lessons = '';
            foreach ($lesson_ids as $lesson_id) {
                $lesson = Lesson::find($lesson_id->lesson_id);
                $lessons .= $lesson->name . ',';
            }
            $lessons = substr($lessons, 0, -1);
            $classroom->lessons = $lessons;
        }
        return response()->json($classrooms, 200);
    }

    public function store()
    {
        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'lesson_ids' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Lütfen tüm alanları doldurunuz.'], 400);
        }

        DB::beginTransaction();
        try {
            $input = request()->all();
            $lesson_ids = request()->lesson_ids;
            $classroom = Classroom::create($input);
            foreach ($lesson_ids as $lesson_id) {
                $classroom_lesson = new ClassroomLesson;
                $classroom_lesson->classroom_id = $classroom->id;
                $classroom_lesson->lesson_id = $lesson_id;
                $classroom_lesson->save();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Sınıf eklenirken bir sorun oluştu.'], 500);
        }
        DB::commit();
        return response()->json(['message' => 'Sınıf başarıyla eklendi.'], 200);
    }

    public function show($id)
    {
        $classroom = Classroom::select('name')->where('id', $id)->first();
        if ($classroom != null) {
            $lesson_ids = ClassroomLesson::select('lesson_id')->where('classroom_id', $id)->get();
            $lessons = array();
            foreach ($lesson_ids as $lesson_id) {
                array_push($lessons, $lesson_id->lesson_id);
            }
            $all_lessons = Lesson::select('id', 'name')->get();
            foreach ($all_lessons as $lesson) {
                if (in_array($lesson->id, $lessons))
                    $lesson->isChecked = true;
                else
                    $lesson->isChecked = false;
            }
            $classroom->lessons = $all_lessons;
            return response()->json($classroom, 200);
        } else {
            return response()->json(['message' => 'Böyle bir sınıf mevcut değil.'], 500);
        }
    }

    public function update($id)
    {
        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'lesson_ids' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Lütfen tüm alanları doldurunuz.'], 400);
        }

        $classroom = Classroom::where('id', $id)->first();
        if ($classroom != null) {
            DB::beginTransaction();
            try {
                $classroom->fill(request()->all());
                $classroom->update();

                ClassroomLesson::where('classroom_id', $id)->delete();

                $lesson_ids = request()->lesson_ids;
                foreach ($lesson_ids as $lesson_id) {
                    $classroom_lesson = new ClassroomLesson;
                    $classroom_lesson->classroom_id = $id;
                    $classroom_lesson->lesson_id = $lesson_id;
                    $classroom_lesson->save();
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['message' => 'Sınıf düzenlenirken bir sorun oluştu.'], 500);
            }
            DB::commit();
            return response()->json(['message' => 'Sınıf başarıyla düzenlendi.'], 200);
        } else {
            return response()->json(['message' => 'Böyle bir sınıf mevcut değil.'], 500);
        }
    }

    public function destroy($id)
    {
        $classroom = Classroom::where('id', $id)->first();
        if ($classroom != null) {
            $students = Student::where('classroom_id', $id)->count();
            if ($students > 0) {
                return response()->json([
                    'message' => 'Bu sınıf silinemez. Öncelikle bu sınıfta ders alan öğrenciler silinmelidir.',
                    'type' => 'warn'
                ], 200);
            }
            DB::beginTransaction();
            try {
                ClassroomLesson::where('classroom_id', $id)->delete();
                $classroom->delete();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['message' => $e->getMessage()], 500);
            }
            DB::commit();
            return response()->json([
                'message' => 'Sınıf başarıyla silindi.',
                'type' => 'success'
            ], 200);
        } else {
            return response()->json(['message' => 'Böyle bir sınıf mevcut değil.'], 500);
        }
    }
}

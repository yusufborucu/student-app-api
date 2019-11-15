<?php

namespace App\Http\Controllers;

use App\Models\ClassroomLesson;
use App\Models\Lesson;
use Validator;
use Illuminate\Support\Facades\DB;

class LessonController extends Controller
{
    public function index()
    {
        $lessons = Lesson::select('id', 'name')->get();
        foreach ($lessons as $lesson) {
            $lesson->isChecked = false;
        }
        return response()->json($lessons, 200);
    }

    public function store()
    {
        $validator = Validator::make(request()->all(), [
            'name' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Lütfen tüm alanları doldurunuz.'], 400);
        }

        DB::beginTransaction();
        try {
            $input = request()->all();
            Lesson::create($input);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Ders eklenirken bir sorun oluştu.'], 500);
        }
        DB::commit();
        return response()->json(['message' => 'Ders başarıyla eklendi.'], 200);
    }

    public function show($id)
    {
        $lesson = Lesson::select('name')->where('id', $id)->first();
        if ($lesson != null) {
            return response()->json($lesson, 200);
        } else {
            return response()->json(['message' => 'Böyle bir ders mevcut değil.'], 500);
        }
    }

    public function update($id)
    {
        $validator = Validator::make(request()->all(), [
            'name' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Lütfen tüm alanları doldurunuz.'], 400);
        }

        $lesson = Lesson::where('id', $id)->first();
        if ($lesson != null) {
            DB::beginTransaction();
            try {
                $lesson->fill(request()->all());
                $lesson->update();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['message' => 'Ders düzenlenirken bir sorun oluştu.'], 500);
            }
            DB::commit();
            return response()->json(['message' => 'Ders başarıyla düzenlendi.'], 200);
        } else {
            return response()->json(['message' => 'Böyle bir ders mevcut değil.'], 500);
        }
    }

    public function destroy($id)
    {
        $lesson = Lesson::where('id', $id)->first();
        if ($lesson != null) {
            $classroom_lessons = ClassroomLesson::where('lesson_id', $id)->count();
            if ($classroom_lessons > 0) {
                return response()->json([
                    'message' => 'Bu ders silinemez. Öncelikle bu dersin verildiği sınıflar silinmelidir.',
                    'type' => 'warn'
                ], 200);
            }
            DB::beginTransaction();
            try {
                $lesson->delete();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['message' => 'Ders silinirken bir sorun oluştu.'], 500);
            }
            DB::commit();
            return response()->json([
                'message' => 'Ders başarıyla silindi.',
                'type' => 'success'
            ], 200);
        } else {
            return response()->json(['message' => 'Böyle bir ders mevcut değil.'], 500);
        }
    }
}

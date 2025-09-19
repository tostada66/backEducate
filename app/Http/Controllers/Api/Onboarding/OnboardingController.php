<?php

namespace App\Http\Controllers\Api\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function experience(Request $request)
    {
        $data = $request->validate([
            'nivelacademico' => ['required','string','max:80'],
        ]);

        $user = $request->user();
        $est = Estudiante::firstOrCreate(['idusuario' => $user->idusuario]);
        $est->nivelacademico = $data['nivelacademico'];
        $est->save();

        return response()->json(['ok' => true]);
    }

    public function interests(Request $request)
    {
        $data = $request->validate([
            'intereses' => ['required','array','min:1'],
            'intereses.*' => ['string','max:80'],
        ]);

        $user = $request->user();
        $est = Estudiante::firstOrCreate(['idusuario' => $user->idusuario]);
        $est->intereses = array_values($data['intereses']);
        $est->save();

        return response()->json(['ok' => true]);
    }
}

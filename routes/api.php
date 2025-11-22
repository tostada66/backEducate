<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\RegisteredUserController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Account\RolController;
use App\Http\Controllers\Api\Onboarding\OnboardingController;
use App\Http\Controllers\Api\Account\ProfileController;
use App\Http\Controllers\Api\Account\EditarProfileController;
use App\Http\Controllers\Api\Account\EditarProfileProfesorController;
use App\Http\Controllers\Api\Account\EstudianteController;
use App\Http\Controllers\Api\Account\EstudianteCategoriaController;
use App\Http\Controllers\Api\Account\ProfesorController;
use App\Http\Controllers\Api\CursoController;
use App\Http\Controllers\Api\UnidadController;
use App\Http\Controllers\Api\ClaseController;
use App\Http\Controllers\Api\ContenidoController;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

// ---------- Salud ----------
Route::get('/ping', fn () => response()->json(['pong' => true]));

// ---------- Públicas (sin login) ----------
Route::withoutMiddleware([EnsureFrontendRequestsAreStateful::class])->group(function () {
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:12,1')
        ->name('api.register');

    // Estudiante (registro)
    Route::post('/register/estudiante/nivel', [EstudianteController::class, 'guardarNivelRegistro'])->name('api.register.estudiante.nivel');
    Route::post('/register/estudiante/intereses', [EstudianteCategoriaController::class, 'guardarInteresesRegistro'])->name('api.register.estudiante.intereses');
    Route::post('/register/estudiante/profile', [ProfileController::class, 'guardarProfileRegistro'])->name('api.register.estudiante.profile');
    Route::post('/register/estudiante/foto', [ProfileController::class, 'guardarFotoRegistro'])->name('api.register.estudiante.foto');
    Route::get('/register/estudiante/show/{idusuario}', [ProfileController::class, 'showRegistro'])->name('api.register.estudiante.show');

    // Profesor (registro)
    Route::post('/register/profesor', [ProfesorController::class, 'guardarRegistro'])->name('api.register.profesor');
    Route::post('/register/profesor/foto', [ProfesorController::class, 'guardarFotoRegistro'])->name('api.register.profesor.foto');
    Route::get('/register/profesor/show/{idusuario}', [ProfesorController::class, 'showRegistro'])->name('api.register.profesor.show');

    // Login
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:18,1')
        ->name('api.login');
});

// Preflight CORS
Route::options('/{any}', fn () => response()->noContent())->where('any', '.*');

// ---------- Password reset ----------
Route::post('/password/forgot', [PasswordResetController::class, 'sendLink'])->name('api.password.forgot');
Route::post('/password/reset', [PasswordResetController::class, 'reset'])->name('api.password.reset');
Route::post('/password/reset/by-email', [PasswordResetController::class, 'resetByEmail'])->name('api.password.reset.byEmail');

// ---------- Protegidas (requiere login con Sanctum) ----------
Route::middleware('auth:sanctum')->group(function () {

    // Perfil básico
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    // Rol
    Route::post('/me/role', [RolController::class, 'choose'])->name('api.me.role');

    // Onboarding
    Route::patch('/me/experience', [OnboardingController::class, 'experience'])->name('api.me.experience');
    Route::patch('/me/interests', [OnboardingController::class, 'interests'])->name('api.me.interests');

    // Perfil Estudiante
    Route::get('/me/profile', [EditarProfileController::class, 'show'])->name('api.me.profile.show');
    Route::patch('/me/profile', [EditarProfileController::class, 'update'])->name('api.me.profile.update');
    Route::post('/me/profile/foto', [EditarProfileController::class, 'updateFoto'])->name('api.me.profile.foto');
    Route::post('/me/profile/password', [EditarProfileController::class, 'changePassword'])->name('api.me.profile.password');

    // Estudiantes
    Route::post('/estudiantes/nivel', [EstudianteController::class, 'guardarNivel'])->name('api.estudiantes.nivel');
    Route::get('/estudiantes/me', [EstudianteController::class, 'show'])->name('api.estudiantes.me');
    Route::patch('/estudiantes/me', [EstudianteController::class, 'update'])->name('api.estudiantes.update');
    Route::post('/estudiantes/intereses', [EstudianteCategoriaController::class, 'updateIntereses'])->name('api.estudiantes.intereses');
    Route::get('/estudiantes/{idusuario}/intereses', [EstudianteCategoriaController::class, 'getIntereses'])->name('api.estudiantes.getIntereses');
    Route::get('/estudiantes/{idusuario}/categorias', [EstudianteCategoriaController::class, 'getTodasConEstado'])->name('api.estudiantes.getTodasCategorias');

    // Profesores
    Route::get('/me/profile/profesor', [EditarProfileProfesorController::class, 'show'])->name('api.me.profesor.show');
    Route::patch('/me/profile/profesor', [EditarProfileProfesorController::class, 'update'])->name('api.me.profesor.update');
    Route::post('/me/profile/profesor/foto', [EditarProfileProfesorController::class, 'updateFoto'])->name('api.me.profesor.foto');
    Route::post('/me/profile/profesor/password', [EditarProfileProfesorController::class, 'changePassword'])->name('api.me.profesor.password');

    // ---------- Cursos ----------
    Route::get('/cursos', [CursoController::class, 'index'])->name('api.cursos.index');
    Route::post('/cursos', [CursoController::class, 'store'])->name('api.cursos.store');
    Route::get('/cursos/{idcurso}', [CursoController::class, 'show'])->name('api.cursos.show');
    Route::patch('/cursos/{idcurso}', [CursoController::class, 'update'])->name('api.cursos.update');
    Route::delete('/cursos/{idcurso}', [CursoController::class, 'destroy'])->name('api.cursos.destroy');

    // ---------- Unidades ----------
    Route::get('/cursos/{idcurso}/unidades', [UnidadController::class, 'index'])->name('api.unidades.index');
    Route::post('/cursos/{idcurso}/unidades', [UnidadController::class, 'store'])->name('api.unidades.store');
    Route::get('/cursos/{idcurso}/unidades/{idunidad}', [UnidadController::class, 'show'])->name('api.unidades.show');
    Route::patch('/cursos/{idcurso}/unidades/{idunidad}', [UnidadController::class, 'update'])->name('api.unidades.update');
    Route::delete('/cursos/{idcurso}/unidades/{idunidad}', [UnidadController::class, 'destroy'])->name('api.unidades.destroy');

    // ---------- Clases ----------
    Route::get('/cursos/{idcurso}/unidades/{idunidad}/clases', [ClaseController::class, 'index'])->name('api.clases.index');
    Route::post('/cursos/{idcurso}/unidades/{idunidad}/clases', [ClaseController::class, 'store'])->name('api.clases.store');
    Route::get('/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}', [ClaseController::class, 'show'])->name('api.clases.show');
    Route::patch('/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}', [ClaseController::class, 'update'])->name('api.clases.update');
    Route::delete('/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}', [ClaseController::class, 'destroy'])->name('api.clases.destroy');

    // 🔄 Cambiar orden de clases
    Route::patch('/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}/orden', [ClaseController::class, 'cambiarOrden'])->name('api.clases.cambiarOrden');

    // ---------- Contenidos ----------
    Route::get('/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}/contenidos', [ContenidoController::class, 'index'])->name('api.contenidos.index');
    Route::post('/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}/contenidos', [ContenidoController::class, 'store'])->name('api.contenidos.store');
    Route::get('/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}/contenidos/{idcontenido}', [ContenidoController::class, 'show'])->name('api.contenidos.show');
    Route::patch('/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}/contenidos/{idcontenido}', [ContenidoController::class, 'update'])->name('api.contenidos.update');
    Route::delete('/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}/contenidos/{idcontenido}', [ContenidoController::class, 'destroy'])->name('api.contenidos.destroy');

    // 🔄 Cambiar orden de contenidos
    Route::patch('/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}/contenidos/{idcontenido}/orden', [ContenidoController::class, 'cambiarOrden'])->name('api.contenidos.cambiarOrden');
});

// ---------- Categorías públicas ----------
Route::get('/categorias', fn () => \App\Models\Categoria::all(['idcategoria','nombre','descripcion']))
    ->name('api.categorias.list');

// ---------- Catálogo público (para estudiantes) ----------
Route::get('/catalogo/cursos', [CursoController::class, 'catalogo'])->name('api.catalogo.cursos');
Route::get('/catalogo/cursos/{idcurso}/unidades', [UnidadController::class, 'catalogo'])->name('api.catalogo.unidades');
Route::get('/catalogo/cursos/{idcurso}/unidades/{idunidad}/clases', [ClaseController::class, 'catalogo'])->name('api.catalogo.clases');
Route::get('/catalogo/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}/contenidos', [ContenidoController::class, 'catalogo'])->name('api.catalogo.contenidos');

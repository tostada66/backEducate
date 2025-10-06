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
use App\Http\Controllers\Api\TipoPlanController;
use App\Http\Controllers\Api\TipoPagoController;
use App\Http\Controllers\Api\FacturaController;
use App\Http\Controllers\Api\SuscripcionController;
use App\Http\Controllers\Api\MatriculaController;
use App\Http\Controllers\Api\ProgresoClaseController;
use App\Http\Controllers\Api\Admin\AdminCursoController;
use App\Http\Controllers\Api\LicenciaController;
use App\Http\Controllers\Api\Account\ProfesorCursoController;
use App\Http\Controllers\Api\ObservacionController;
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

    // Profesores (perfil propio)
    Route::get('/me/profile/profesor', [EditarProfileProfesorController::class, 'show'])->name('api.me.profesor.show');
    Route::patch('/me/profile/profesor', [EditarProfileProfesorController::class, 'update'])->name('api.me.profesor.update');
    Route::post('/me/profile/profesor/foto', [EditarProfileProfesorController::class, 'updateFoto'])->name('api.me.profesor.foto');
    Route::post('/me/profile/profesor/password', [EditarProfileProfesorController::class, 'changePassword'])->name('api.me.profesor.password');

    // ---------- ADMIN ----------
    Route::prefix('admin')->group(function () {

        // Solicitudes de profesores
        Route::get('/profesores/solicitudes', [ProfesorController::class, 'solicitudesPendientes'])->name('api.admin.profesores.solicitudes');
        Route::post('/profesores/{idprofesor}/estado', [ProfesorController::class, 'cambiarEstado'])->name('api.admin.profesores.estado');
        Route::get('/profesores/{idprofesor}', [ProfesorController::class, 'detalle'])->name('api.admin.profesores.detalle');

        // 🔹 Licencias
        Route::get('/licencias', [LicenciaController::class, 'indexAdmin'])->name('api.admin.licencias.index');

        // 🔹 Cursos
        Route::prefix('cursos')->group(function () {
            Route::get('/pendientes', [AdminCursoController::class, 'pendientes'])->name('api.admin.cursos.pendientes');
            Route::get('/rechazados', [AdminCursoController::class, 'rechazados'])->name('api.admin.cursos.rechazados');
            Route::get('/{idcurso}/aprobar-preview', [AdminCursoController::class, 'aprobarPreview'])->name('api.admin.cursos.aprobar.preview');
            Route::patch('/{idcurso}/aprobar', [AdminCursoController::class, 'aprobar'])->name('api.admin.cursos.aprobar');
            Route::patch('/{idcurso}/rechazar', [AdminCursoController::class, 'rechazar'])->name('api.admin.cursos.rechazar');
            Route::get('/{idcurso}/conteo-clases', [AdminCursoController::class, 'contarClases'])->name('api.admin.cursos.conteoClases');

        });
    });

    // ---------- PROFESOR ----------
    Route::prefix('profesor')->group(function () {
        Route::get('/licencias', [LicenciaController::class, 'indexProfesor'])->name('api.profesor.licencias.index');
        Route::get('/cursos', [ProfesorCursoController::class, 'index'])->name('api.profesor.cursos.index');
        Route::patch('/cursos/{idcurso}/enviar-revision', [ProfesorCursoController::class, 'enviarRevision'])->name('api.profesor.cursos.enviarRevision');
        Route::patch('/cursos/{idcurso}/volver-enviar', [ProfesorCursoController::class, 'volverAEnviar'])->name('api.profesor.cursos.volverEnviar'); // ✅ NUEVA RUTA
        Route::get('/cursos/{idcurso}/oferta', [ProfesorCursoController::class, 'verOferta'])->name('api.profesor.cursos.verOferta');
        Route::patch('/cursos/{idcurso}/aceptar-oferta', [ProfesorCursoController::class, 'aceptarOferta'])->name('api.profesor.cursos.aceptarOferta');
        Route::post('/cursos/{idcurso}/rechazar-oferta', [ProfesorCursoController::class, 'rechazarOferta'])->name('api.profesor.cursos.rechazarOferta');
    });

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
    Route::patch('/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}/orden', [ClaseController::class, 'cambiarOrden'])->name('api.clases.cambiarOrden');

    // ---------- Contenidos ----------
    Route::get('/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}/contenidos', [ContenidoController::class, 'index'])->name('api.contenidos.index');
    Route::post('/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}/contenidos', [ContenidoController::class, 'store'])->name('api.contenidos.store');
    Route::get('/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}/contenidos/{idcontenido}', [ContenidoController::class, 'show'])->name('api.contenidos.show');
    Route::patch('/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}/contenidos/{idcontenido}', [ContenidoController::class, 'update'])->name('api.contenidos.update');
    Route::delete('/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}/contenidos/{idcontenido}', [ContenidoController::class, 'destroy'])->name('api.contenidos.destroy');
    Route::patch('/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}/contenidos/{idcontenido}/orden', [ContenidoController::class, 'cambiarOrden'])->name('api.contenidos.cambiarOrden');
    Route::get('/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}/contenidos/{idcontenido}/descargar', [ContenidoController::class, 'descargar'])->name('api.contenidos.descargar');

    // ---------- Planes ----------
    Route::post('/planes', [TipoPlanController::class, 'store'])->name('api.planes.store');
    Route::patch('/planes/{idplan}', [TipoPlanController::class, 'update'])->name('api.planes.update');
    Route::delete('/planes/{idplan}', [TipoPlanController::class, 'destroy'])->name('api.planes.destroy');

    // ---------- Tipos de pago ----------
    Route::post('/tipos-pagos', [TipoPagoController::class, 'store'])->name('api.tipos-pagos.store');
    Route::patch('/tipos-pagos/{idpago}', [TipoPagoController::class, 'update'])->name('api.tipos-pagos.update');
    Route::delete('/tipos-pagos/{idpago}', [TipoPagoController::class, 'destroy'])->name('api.tipos-pagos.destroy');

    // ---------- Facturas ----------
    Route::get('/facturas', [FacturaController::class, 'index'])->name('api.facturas.index');
    Route::post('/facturas', [FacturaController::class, 'store'])->name('api.facturas.store');
    Route::get('/facturas/{idfactura}', [FacturaController::class, 'show'])->name('api.facturas.show');
    Route::get('/facturas/{idfactura}/pdf', [FacturaController::class, 'descargarPdf'])->name('api.facturas.pdf');

    // ---------- Suscripciones ----------
    Route::get('/suscripciones', [SuscripcionController::class, 'index'])->name('api.suscripciones.index');
    Route::post('/suscripciones/pagar', [SuscripcionController::class, 'pagar'])->name('api.suscripciones.pagar');
    Route::get('/suscripciones/{idsus}', [SuscripcionController::class, 'show'])->name('api.suscripciones.show');

    // ---------- Matriculas ----------
    Route::post('/cursos/{idcurso}/inscribir', [MatriculaController::class, 'inscribir'])->name('api.matriculas.inscribir');
    Route::get('/mis-cursos', [MatriculaController::class, 'misCursos'])->name('api.matriculas.misCursos');
    Route::post('/cursos/{idcurso}/desuscribir', [MatriculaController::class, 'desuscribir']);

    // ---------- Progreso ----------
    Route::post('/matriculas/{idmatricula}/clases/{idclase}/completar', [ProgresoClaseController::class, 'completar'])->name('api.progreso.completar');

    // ---------- Observaciones ----------
    Route::prefix('observaciones')->group(function () {
        Route::get('/curso/{idcurso}', [ObservacionController::class, 'listarPorCurso'])->name('api.observaciones.curso');
        Route::get('/oferta/{idoferta}', [ObservacionController::class, 'listarPorOferta'])->name('api.observaciones.oferta');
        Route::post('/', [ObservacionController::class, 'store'])->name('api.observaciones.store');
        Route::delete('/{id}', [ObservacionController::class, 'destroy'])->name('api.observaciones.destroy');
    });
});

// ---------- Categorías públicas ----------
Route::get('/categorias', fn () => \App\Models\Categoria::all(['idcategoria', 'nombre', 'descripcion']))
    ->name('api.categorias.list');

// ---------- Catálogo público ----------
Route::get('/catalogo/cursos', [CursoController::class, 'catalogo'])->name('api.catalogo.cursos');
Route::get('/catalogo/cursos/{idcurso}', [CursoController::class, 'showPublic'])->name('api.catalogo.curso');

// ---------- Planes públicos ----------
Route::get('/planes', [TipoPlanController::class, 'index'])->name('api.planes.index');
Route::get('/planes/{idplan}', [TipoPlanController::class, 'show'])->name('api.planes.show');

// ---------- Tipos de pago públicos ----------
Route::get('/tipos-pagos', [TipoPagoController::class, 'index'])->name('api.tipos-pagos.index');
Route::get('/tipos-pagos/{idpago}', [TipoPagoController::class, 'show'])->name('api.tipos-pagos.show');

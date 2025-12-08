<?php

use App\Http\Controllers\Api\Account\EditarProfileController;
use App\Http\Controllers\Api\Account\EditarProfileProfesorController;
use App\Http\Controllers\Api\Account\EstudianteCategoriaController;
use App\Http\Controllers\Api\Account\EstudianteController;
use App\Http\Controllers\Api\Account\ProfesorController;
use App\Http\Controllers\Api\Account\ProfesorCursoController;
use App\Http\Controllers\Api\Account\ProfileController;
use App\Http\Controllers\Api\Account\RolController;
use App\Http\Controllers\Api\Admin\AdminCursoController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\RegisteredUserController;
use App\Http\Controllers\Api\ClaseController;
use App\Http\Controllers\Api\ClaseVistaController;
use App\Http\Controllers\Api\ComentarioController;
use App\Http\Controllers\Api\ContenidoController;
use App\Http\Controllers\Api\CursoController;
use App\Http\Controllers\Api\EstudianteAdminController;
use App\Http\Controllers\Api\ExamenController;
use App\Http\Controllers\Api\FacturaController;
use App\Http\Controllers\Api\IntentoExamenController;
use App\Http\Controllers\Api\JuegoCartasController;
use App\Http\Controllers\Api\JuegoReciclajeController;
use App\Http\Controllers\Api\LicenciaController;
use App\Http\Controllers\Api\MatriculaController;
use App\Http\Controllers\Api\ObservacionController;
use App\Http\Controllers\Api\Onboarding\OnboardingController;
use App\Http\Controllers\Api\PagoProfesorController;
use App\Http\Controllers\Api\ProgresoClaseController;
use App\Http\Controllers\Api\ResenaController;
use App\Http\Controllers\Api\SuscripcionController;
use App\Http\Controllers\Api\TipoPagoController;
use App\Http\Controllers\Api\TipoPlanController;
use App\Http\Controllers\Api\UnidadController;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

// ===============================
// 🆕 USE adicionales (Juegos)
// ===============================
use App\Http\Controllers\Api\JuegoController;
use App\Http\Controllers\Api\CursoJuegoController;
use App\Http\Controllers\Api\IntentoJuegoController;
use App\Http\Controllers\Api\JuegoMecanografiaController;

// 🆕 NOTIFICACIONES
use App\Http\Controllers\Api\NotificacionController;

// ======================================================
// 🔹 HEALTH CHECK
// ======================================================
Route::get('/ping', fn () => response()->json(['pong' => true]));

// ======================================================
// 🔹 PÚBLICAS (sin autenticación)
// ======================================================
Route::withoutMiddleware([EnsureFrontendRequestsAreStateful::class])->group(function () {

    // 🧾 Registro general
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:12,1')
        ->name('api.register');

    // 🧑‍🎓 Registro de estudiante
    Route::post('/register/estudiante/nivel', [EstudianteController::class, 'guardarNivelRegistro'])->name('api.register.estudiante.nivel');
    Route::post('/register/estudiante/intereses', [EstudianteCategoriaController::class, 'guardarInteresesRegistro'])->name('api.register.estudiante.intereses');
    Route::post('/register/estudiante/profile', [ProfileController::class, 'guardarProfileRegistro'])->name('api.register.estudiante.profile');
    Route::post('/register/estudiante/foto', [ProfileController::class, 'guardarFotoRegistro'])->name('api.register.estudiante.foto');
    Route::get('/register/estudiante/show/{idusuario}', [ProfileController::class, 'showRegistro'])->name('api.register.estudiante.show');

    // 👨‍🏫 Registro de profesor
    Route::post('/register/profesor', [ProfesorController::class, 'guardarRegistro'])->name('api.register.profesor');
    Route::post('/register/profesor/foto', [ProfesorController::class, 'guardarFotoRegistro'])->name('api.register.profesor.foto');
    Route::get('/register/profesor/show/{idusuario}', [ProfesorController::class, 'showRegistro'])->name('api.register.profesor.show');

    // 🔐 Login
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:18,1')
        ->name('api.login');

    // ✅ Logout sin necesidad de token
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('api.logout');
});

// ======================================================
// 🔹 PASSWORD RESET (Público)
// ======================================================
Route::post('/password/forgot', [PasswordResetController::class, 'sendLink'])->name('api.password.forgot');
Route::post('/password/reset', [PasswordResetController::class, 'reset'])->name('api.password.reset');
Route::post('/password/reset/by-email', [PasswordResetController::class, 'resetByEmail'])->name('api.password.reset.byEmail');

// ======================================================
// 🔹 CORS Preflight
// ======================================================
Route::options('/{any}', fn () => response()->noContent())->where('any', '.*');

// ======================================================
// 🔹 PROTEGIDAS (auth:sanctum)
// ======================================================
Route::middleware('auth:sanctum')->group(function () {

    // PERFIL BÁSICO
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');

    // ROL
    Route::post('/me/role', [RolController::class, 'choose'])->name('api.me.role');

    // ONBOARDING
    Route::patch('/me/experience', [OnboardingController::class, 'experience'])->name('api.me.experience');
    Route::patch('/me/interests', [OnboardingController::class, 'interests'])->name('api.me.interests');

    // PERFIL ESTUDIANTE (datos básicos del usuario)
    Route::get('/me/profile', [ProfileController::class, 'show'])->name('api.me.profile.show');
    Route::patch('/me/profile', [EditarProfileController::class, 'update'])->name('api.me.profile.update');
    Route::post('/me/profile/foto', [EditarProfileController::class, 'updateFoto'])->name('api.me.profile.foto');
    Route::post('/me/profile/password', [EditarProfileController::class, 'changePassword'])->name('api.me.profile.password');

    // 🆕 DATOS ADICIONALES DEL ESTUDIANTE
    Route::get('/me/estudiante', [EstudianteController::class, 'show'])->name('api.me.estudiante.show');
    Route::patch('/me/estudiante', [EstudianteController::class, 'guardarNivel'])->name('api.me.estudiante.update');

    // ESTUDIANTES (rutas antiguas)
    Route::post('/estudiantes/nivel', [EstudianteController::class, 'guardarNivel'])->name('api.estudiantes.nivel');
    Route::get('/estudiantes/me', [EstudianteController::class, 'show'])->name('api.estudiantes.me');
    Route::patch('/estudiantes/me', [EstudianteController::class, 'update'])->name('api.estudiantes.update');
    Route::post('/estudiantes/intereses', [EstudianteCategoriaController::class, 'updateIntereses'])->name('api.estudiantes.intereses');
    Route::get('/estudiantes/{idusuario}/intereses', [EstudianteCategoriaController::class, 'getIntereses'])->name('api.estudiantes.getIntereses');
    Route::get('/estudiantes/{idusuario}/categorias', [EstudianteCategoriaController::class, 'getTodasConEstado'])->name('api.estudiantes.getTodasCategorias');

    Route::get('/estudiantes', [EstudianteAdminController::class, 'index'])->name('api.estudiantes.index');

    // PROFESORES (perfil propio)
    Route::get('/me/profile/profesor', [EditarProfileProfesorController::class, 'show'])->name('api.me.profesor.show');
    Route::patch('/me/profile/profesor', [EditarProfileProfesorController::class, 'update'])->name('api.me.profesor.update');
    Route::post('/me/profile/profesor/foto', [EditarProfileProfesorController::class, 'updateFoto'])->name('api.me.profesor.foto');
    Route::post('/me/profile/profesor/password', [EditarProfileProfesorController::class, 'changePassword'])->name('api.me.profesor.password');

    // 🔔 NOTIFICACIONES
    Route::get('/notificaciones', [NotificacionController::class, 'index'])->name('api.notificaciones.index');
    Route::get('/notificaciones/resumen', [NotificacionController::class, 'resumen'])->name('api.notificaciones.resumen');
    Route::post('/notificaciones/leidas', [NotificacionController::class, 'marcarLeidas'])->name('api.notificaciones.leidas');

    // ======================================================
    // 🔹 ADMIN
    // ======================================================
    Route::prefix('admin')->group(function () {

        // 👨‍🏫 PROFESORES
        Route::get('/profesores', [ProfesorController::class, 'listarProfesores'])->name('api.admin.profesores.index');
        Route::get('/profesores/solicitudes', [ProfesorController::class, 'solicitudesPendientes'])->name('api.admin.profesores.solicitudes');
        Route::post('/profesores/{idprofesor}/estado', [ProfesorController::class, 'cambiarEstado'])->name('api.admin.profesores.estado');
        Route::get('/profesores/{idprofesor}', [ProfesorController::class, 'detalle'])->name('api.admin.profesores.detalle');
        Route::get('/profesores/{idprofesor}/cursos', [ProfesorCursoController::class, 'cursosPorProfesor'])->name('api.admin.profesores.cursos');

        // 💳 SUSCRIPCIONES
        Route::get('/suscripciones', [SuscripcionController::class, 'adminIndex'])->name('api.admin.suscripciones.index');
        Route::get('/suscripciones/export', [SuscripcionController::class, 'exportExcel'])->name('api.admin.suscripciones.export');

        // 👤 PERFIL DE USUARIOS (visto por admin)
        Route::get('/usuarios/{idusuario}/perfil', [ProfileController::class, 'showAdmin'])->name('api.admin.usuarios.perfil');

        // 🧾 LICENCIAS
        Route::get('/licencias', [LicenciaController::class, 'indexAdmin'])->name('api.admin.licencias.index');

        // 📘 CURSOS
        Route::prefix('cursos')->group(function () {
            Route::get('/pendientes', [AdminCursoController::class, 'pendientes'])->name('api.admin.cursos.pendientes');
            Route::get('/rechazados', [AdminCursoController::class, 'rechazados'])->name('api.admin.cursos.rechazados');
            Route::get('/{idcurso}/aprobar-preview', [AdminCursoController::class, 'aprobarPreview'])->name('api.admin.cursos.aprobar.preview');
            Route::patch('/{idcurso}/aprobar', [AdminCursoController::class, 'aprobar'])->name('api.admin.cursos.aprobar');
            Route::patch('/{idcurso}/rechazar', [AdminCursoController::class, 'rechazar'])->name('api.admin.cursos.rechazar');
            Route::get('/{idcurso}/conteo-clases', [AdminCursoController::class, 'contarClases'])->name('api.admin.cursos.conteoClases');
        });

        // ✏️ EDICIONES DE CURSO
        Route::patch(
            '/curso-ediciones/{idcursoEdicion}/aprobar',
            [AdminCursoController::class, 'aprobarEdicion']
        )->name('api.admin.cursoEdiciones.aprobar');

        Route::patch(
            '/curso-ediciones/{idcursoEdicion}/cerrar',
            [AdminCursoController::class, 'cerrarEdicion']
        )->name('api.admin.cursoEdiciones.cerrar');

        // 💰 PAGOS A PROFESORES
        Route::prefix('pagos-profesores')->group(function () {
            Route::get('/', [PagoProfesorController::class, 'index'])->name('api.admin.pagos.index');
            Route::get('/pendientes', [PagoProfesorController::class, 'pendientes'])->name('api.admin.pagos.pendientes');
            Route::post('/', [PagoProfesorController::class, 'store'])->name('api.admin.pagos.store');
            Route::get('/metodos-pago', [PagoProfesorController::class, 'metodosPago'])->name('api.admin.pagos.metodos');
            Route::get('/{id}', [PagoProfesorController::class, 'show'])->name('api.admin.pagos.show');
            Route::patch('/{idpago}/confirmar', [PagoProfesorController::class, 'confirmarPago']);
            Route::delete('/{idpago}', [PagoProfesorController::class, 'destroy'])->name('api.admin.pagos.destroy');
        });
    });

    // ======================================================
    // 🔹 PROFESOR
    // ======================================================
    Route::prefix('profesor')->group(function () {
        Route::get('/licencias', [LicenciaController::class, 'indexProfesor'])->name('api.profesor.licencias.index');
        Route::get('/cursos', [ProfesorCursoController::class, 'index'])->name('api.profesor.cursos.index');
        Route::patch('/cursos/{idcurso}/enviar-revision', [ProfesorCursoController::class, 'enviarRevision'])->name('api.profesor.cursos.enviarRevision');
        Route::patch('/cursos/{idcurso}/volver-enviar', [ProfesorCursoController::class, 'volverAEnviar'])->name('api.profesor.cursos.volverEnviar');
        Route::get('/cursos/{idcurso}/oferta', [ProfesorCursoController::class, 'verOferta'])->name('api.profesor.cursos.verOferta');
        Route::patch('/cursos/{idcurso}/aceptar-oferta', [ProfesorCursoController::class, 'aceptarOferta'])->name('api.profesor.cursos.aceptarOferta');
        Route::post('/cursos/{idcurso}/rechazar-oferta', [ProfesorCursoController::class, 'rechazarOferta'])->name('api.profesor.cursos.rechazarOferta');

        // ✏️ Profesor solicita edición
        Route::post(
            '/cursos/{idcurso}/solicitar-edicion',
            [ProfesorCursoController::class, 'solicitarEdicion']
        )->name('api.profesor.cursos.solicitarEdicion');

        // ✅ Profesor finaliza edición
        Route::patch(
            '/curso-ediciones/{idcursoEdicion}/finalizar',
            [ProfesorCursoController::class, 'finalizarEdicion']
        )->name('api.profesor.cursoEdiciones.finalizar');
    });

    // ======================================================
    // 🔹 CURSOS, UNIDADES, CLASES, CONTENIDOS, EXÁMENES
    // ======================================================
    Route::get('/cursos', [CursoController::class, 'index'])->name('api.cursos.index');
    Route::apiResource('cursos', CursoController::class);
    Route::apiResource('cursos.unidades', UnidadController::class);
    Route::apiResource('cursos.unidades.clases', ClaseController::class);
    Route::apiResource('cursos.unidades.clases.contenidos', ContenidoController::class);

    // Extras para contenidos
    Route::patch(
        '/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}/contenidos/{idcontenido}/orden',
        [ContenidoController::class, 'cambiarOrden']
    )->name('api.contenidos.cambiarOrden');

    Route::get(
        '/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}/contenidos/{idcontenido}/descargar',
        [ContenidoController::class, 'descargar']
    )->name('api.contenidos.descargar');

    Route::get(
        '/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}/contenidos-catalogo',
        [ContenidoController::class, 'catalogo']
    )->name('api.contenidos.catalogo');

    // 🧠 EXÁMENES
    Route::prefix('examenes')->group(function () {
        Route::get('/unidad/{idunidad}', [ExamenController::class, 'index'])->name('api.examenes.unidad');
        Route::get('/by-unidad/{idunidad}', [ExamenController::class, 'getByUnidad'])->name('api.examenes.byUnidad');
        Route::get('/{idexamen}', [ExamenController::class, 'show'])->name('api.examenes.show');
        Route::post('/', [ExamenController::class, 'store'])->name('api.examenes.store');
        Route::put('/{idexamen}', [ExamenController::class, 'update'])->name('api.examenes.update');
        Route::delete('/{idexamen}', [ExamenController::class, 'destroy'])->name('api.examenes.destroy');
        Route::get('/{idexamen}/estadisticas', [IntentoExamenController::class, 'estadisticas'])->name('api.examenes.estadisticas');
    });

    // 🧩 INTENTOS DE EXAMEN
    Route::prefix('intentos')->group(function () {
        Route::post('/', [IntentoExamenController::class, 'store'])->name('api.intentos.store');
        Route::post('/{idintento}/responder', [IntentoExamenController::class, 'responder'])->name('api.intentos.responder');
        Route::post('/{idintento}/finalizar', [IntentoExamenController::class, 'finalizar'])->name('api.intentos.finalizar');
        Route::get('/{idintento}', [IntentoExamenController::class, 'show'])->name('api.intentos.show');
    });

    // ======================================================
    // 🔹 PLANES, PAGOS, FACTURAS, SUSCRIPCIONES
    // ======================================================
    Route::post('/planes', [TipoPlanController::class, 'store'])->name('api.planes.store');
    Route::patch('/planes/{idplan}', [TipoPlanController::class, 'update'])->name('api.planes.update');
    Route::delete('/planes/{idplan}', [TipoPlanController::class, 'destroy'])->name('api.planes.destroy');

    Route::post('/tipos-pagos', [TipoPagoController::class, 'store'])->name('api.tipos-pagos.store');
    Route::patch('/tipos-pagos/{idpago}', [TipoPagoController::class, 'update'])->name('api.tipos-pagos.update');
    Route::delete('/tipos-pagos/{idpago}', [TipoPagoController::class, 'destroy'])->name('api.tipos-pagos.destroy');

    // FACTURAS
    Route::get('/facturas', [FacturaController::class, 'index'])->name('api.facturas.index');
    Route::post('/facturas', [FacturaController::class, 'store'])->name('api.facturas.store');
    Route::get('/facturas/{idfactura}/pdf', [FacturaController::class, 'descargarPdf'])->name('api.facturas.pdf');
    Route::get('/facturas/{idfactura}', [FacturaController::class, 'show'])->name('api.facturas.show');
    Route::get('/facturas/historial', [FacturaController::class, 'historial'])->name('api.facturas.historial');

    Route::get('/suscripciones', [SuscripcionController::class, 'index'])->name('api.suscripciones.index');
    Route::post('/suscripciones/pagar', [SuscripcionController::class, 'pagar'])->name('api.suscripciones.pagar');
    Route::get('/suscripciones/{idsus}', [SuscripcionController::class, 'show'])->name('api.suscripciones.show');

    // ======================================================
    // 🔹 MATRÍCULAS Y PROGRESO
    // ======================================================
    Route::post('/cursos/{idcurso}/inscribir', [MatriculaController::class, 'inscribir'])->name('api.matriculas.inscribir');
    Route::get('/mis-cursos', [MatriculaController::class, 'misCursos'])->name('api.matriculas.misCursos');
    Route::post('/cursos/{idcurso}/desuscribir', [MatriculaController::class, 'desuscribir']);
    Route::post('/matriculas/{idmatricula}/clases/{idclase}/completar', [ProgresoClaseController::class, 'completar'])->name('api.progreso.completar');

    // 🎞️ PROGRESO DE VIDEOS
    Route::patch('/vistas', [ClaseVistaController::class, 'updateProgreso'])->name('api.vistas.update');
    Route::get('/vistas/{idcontenido}', [ClaseVistaController::class, 'getProgreso'])->name('api.vistas.get');

    // ======================================================
    // 🔹 OBSERVACIONES, COMENTARIOS, RESEÑAS
    // ======================================================
    Route::prefix('observaciones')->group(function () {
        Route::get('/curso/{idcurso}', [ObservacionController::class, 'listarPorCurso'])->name('api.observaciones.curso');
        Route::get('/oferta/{idoferta}', [ObservacionController::class, 'listarPorOferta'])->name('api.observaciones.oferta');
        Route::post('/', [ObservacionController::class, 'store'])->name('api.observaciones.store');
        Route::delete('/{id}', [ObservacionController::class, 'destroy'])->name('api.observaciones.destroy');
    });

    Route::prefix('clases')->group(function () {
        Route::get('/{idclase}/comentarios', [ComentarioController::class, 'index'])->name('api.clases.comentarios.index');
        Route::post('/{idclase}/comentarios', [ComentarioController::class, 'store'])->name('api.clases.comentarios.store');
        Route::delete('/comentarios/{idcomentario}', [ComentarioController::class, 'destroy'])->name('api.clases.comentarios.destroy');
    });

    Route::prefix('cursos')->group(function () {
        Route::post('/{idcurso}/resenas', [ResenaController::class, 'store'])->name('api.cursos.resenas.store');
    });
    Route::delete('/resenas/{idresena}', [ResenaController::class, 'destroy'])->name('api.resenas.destroy');

    // ======================================================
    // 🆕 🎮 MÓDULO DE JUEGOS
    // ======================================================
    Route::prefix('juegos')->group(function () {
        // Catálogo base
        Route::get('/', [JuegoController::class, 'index'])->name('api.juegos.index');
        Route::get('/{idjuego}', [JuegoController::class, 'show'])->name('api.juegos.show');
        Route::post('/', [JuegoController::class, 'store'])->name('api.juegos.store');
        Route::put('/{idjuego}', [JuegoController::class, 'update'])->name('api.juegos.update');
        Route::delete('/{idjuego}', [JuegoController::class, 'destroy'])->name('api.juegos.destroy');

        // Intentos
        Route::get('/intentos/mis', [IntentoJuegoController::class, 'index'])->name('api.juegos.intentos.index');
        Route::get('/intentos/{idintento}', [IntentoJuegoController::class, 'show'])->name('api.juegos.intentos.show');
        Route::post('/curso-juego/{idcursojuego}/intentos', [IntentoJuegoController::class, 'store'])->name('api.juegos.intentos.store');
        Route::get('/curso-juego/{idcursojuego}/ranking', [IntentoJuegoController::class, 'ranking'])->name('api.juegos.intentos.ranking');

        // Atajos por UNIDAD
        Route::get('/unidad/{idunidad}', [CursoJuegoController::class, 'index'])->name('api.juegos.unidad.index');
        Route::post('/unidad/{idunidad}', [CursoJuegoController::class, 'store'])->name('api.juegos.unidad.store');
        Route::post('/unidad/{idunidad}/asignar', [CursoJuegoController::class, 'asignarJuegoUnidad'])->name('api.juegos.unidad.asignar');

        // CRUD instancia curso_juego
        Route::get('/curso-juego/{idcursojuego}', [CursoJuegoController::class, 'show'])->name('api.juegos.curso.show');
        Route::put('/curso-juego/{idcursojuego}', [CursoJuegoController::class, 'update'])->name('api.juegos.curso.update');
        Route::delete('/curso-juego/{idcursojuego}', [CursoJuegoController::class, 'destroy'])->name('api.juegos.curso.destroy');
    });

    // Configuración curso_juego (fuera del /juegos para usar URLs cortas)
    Route::prefix('curso-juego')->group(function () {
        Route::get('/{idcursojuego}', [CursoJuegoController::class, 'show'])->name('api.cursojuego.show');
        Route::put('/{idcursojuego}', [CursoJuegoController::class, 'update'])->name('api.cursojuego.update');

        // 🟥 NUEVAS: baja y reactivar (las que usa tu front)
        Route::patch('/{idcursojuego}/baja', [CursoJuegoController::class, 'darDeBaja'])->name('api.cursojuego.baja');
        Route::patch('/{idcursojuego}/reactivar', [CursoJuegoController::class, 'reactivar'])->name('api.cursojuego.reactivar');

        // (si tienes implementado limpiarAntiguos en el controlador)
        Route::delete('/limpiar', [CursoJuegoController::class, 'limpiarAntiguos'])->name('api.cursojuego.limpiar');

        // MECANOGRAFÍA
        Route::get('/{idcursojuego}/mecanografia', [JuegoMecanografiaController::class, 'listarPalabras'])
            ->name('api.cursojuego.mecanografia.listar');
        Route::post('/{idcursojuego}/guardar', [JuegoMecanografiaController::class, 'guardarPalabras'])
            ->name('api.cursojuego.mecanografia.guardar');
        Route::put('/mecanografia/{idpalabra}', [JuegoMecanografiaController::class, 'update'])
            ->name('api.cursojuego.mecanografia.update');
        Route::delete('/mecanografia/{idpalabra}', [JuegoMecanografiaController::class, 'destroy'])
            ->name('api.cursojuego.mecanografia.destroy');

        // CARTAS
        Route::get('/{idcursojuego}/cartas', [JuegoCartasController::class, 'listarCartas'])->name('api.cursojuego.cartas.listar');
        Route::post('/{idcursojuego}/cartas', [JuegoCartasController::class, 'guardarCarta'])->name('api.cursojuego.cartas.guardar');
        Route::put('/carta/{idpar}', [JuegoCartasController::class, 'updateCarta'])->name('api.cursojuego.cartas.update');
        Route::delete('/carta/{idpar}', [JuegoCartasController::class, 'eliminarCarta'])->name('api.cursojuego.cartas.destroy');

        // RECICLAJE / CLASIFICA OPERACIONES
        Route::get('/{idcursojuego}/reciclaje', [JuegoReciclajeController::class, 'listarItems'])
            ->name('api.cursojuego.reciclaje.listar');
        Route::post('/{idcursojuego}/reciclaje', [JuegoReciclajeController::class, 'guardarItem'])
            ->name('api.cursojuego.reciclaje.guardar');
        Route::put('/reciclaje/{iditem}', [JuegoReciclajeController::class, 'updateItem'])
            ->name('api.cursojuego.reciclaje.update');
        Route::delete('/reciclaje/{iditem}', [JuegoReciclajeController::class, 'eliminarItem'])
            ->name('api.cursojuego.reciclaje.destroy');
    });

    // 🃏 Juego de cartas (ejecución estudiante)
    Route::prefix('juego-cartas')->group(function () {
        Route::get('/{idcursojuego}', [JuegoCartasController::class, 'listar'])->name('api.juego-cartas.listar');
        Route::post('/guardar-intento', [JuegoCartasController::class, 'guardarIntento'])->name('api.juego-cartas.guardar-intento');
    });

    // ♻️ Juego de reciclaje / clasifica operaciones (ejecución estudiante)
    Route::prefix('juego-reciclaje')->group(function () {
        Route::get('/{idcursojuego}', [JuegoReciclajeController::class, 'listar'])->name('api.juego-reciclaje.listar');
        Route::post('/guardar-intento', [JuegoReciclajeController::class, 'guardarIntento'])->name('api.juego-reciclaje.guardar-intento');
    });
});

// ======================================================
// 🔹 CATÁLOGO Y DATOS PÚBLICOS
// ======================================================
Route::get('/categorias', fn () => \App\Models\Categoria::all(['idcategoria', 'nombre', 'descripcion']))->name('api.categorias.list');
Route::get('/catalogo/cursos', [CursoController::class, 'catalogo'])->name('api.catalogo.cursos');
Route::get('/catalogo/cursos/{idcurso}', [CursoController::class, 'showPublic'])->name('api.catalogo.curso');
Route::get('/planes', [TipoPlanController::class, 'index'])->name('api.planes.index');
Route::get('/planes/{idplan}', [TipoPlanController::class, 'show'])->name('api.planes.show');
Route::get('/tipos-pagos', [TipoPagoController::class, 'index'])->name('api.tipos-pagos.index');
Route::get('/tipos-pagos/{idpago}', [TipoPagoController::class, 'show'])->name('api.tipos-pagos.show');
Route::get('/cursos/{idcurso}/resenas', [ResenaController::class, 'listarPorCurso'])->name('api.cursos.resenas.index');

// Previews públicos de contenidos
Route::get(
    '/catalogo/cursos/{idcurso}/unidades/{idunidad}/clases/{idclase}/contenidos',
    [ContenidoController::class, 'catalogo']
)->name('api.catalogo.contenidos');

Route::get('/stream/{path}', [ContenidoController::class, 'stream'])
    ->where('path', '.*')
    ->name('media.stream');

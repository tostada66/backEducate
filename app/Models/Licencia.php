<?php

namespace App\Models\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Licencia extends Model
{
    protected $table='licencias';
    protected $primaryKey='idlicencia';
    protected $fillable=['idcurso','idtermino','precio'];

    public function curso(){ return $this->belongsTo(Curso::class,'idcurso','idcurso'); }
    public function termino(){ return $this->belongsTo(TerminoLicencia::class,'idtermino','idtermino'); }
}
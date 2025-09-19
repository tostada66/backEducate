<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TerminoLicencia extends Model
{
    protected $table='termino_licencias';
    protected $primaryKey='idtermino';
    protected $fillable=['nombre','meses'];
}